-- =============================================================================
-- Normalización masiva: marcar como APROBADAS intentos reprobados en un rango
-- de fechas (presentación): desde el último sábado hasta AYER (inclusive),
-- según la fecha en que EJECUTES este script en producción.
--
-- IMPORTANTE:
-- 1) Haz BACKUP de discipular_evaluacion_resultados (y ideally BBDD completa).
-- 2) Ejecuta primero el SELECT de vista previa y revisa filas / conteos.
-- 3) Descomenta START TRANSACTION / COMMIT solo cuando estés seguro.
--
-- Tablas: discipular_evaluacion_resultados (r), discipular_evaluaciones (e)
-- Criterio reprobado: r.Aprobado = 0
-- Fecha de rango: DATE(r.Fecha_Presentacion)
-- =============================================================================

-- Opción A (recomendada): rango automático — último sábado ≤ ayer  →  ayer
SET @fecha_hasta := DATE_SUB(CURDATE(), INTERVAL 1 DAY);
SET @fecha_desde := DATE_SUB(
    @fecha_hasta,
    INTERVAL IF(DAYOFWEEK(@fecha_hasta) = 7, 0, DAYOFWEEK(@fecha_hasta)) DAY
);
-- DAYOFWEEK en MySQL: 1=domingo … 7=sábado. Esto lleva @fecha_desde al sábado
-- de la misma semana calendario que @fecha_hasta (sábado = inicio del tramo).

-- Opción B: fechas fijas (descomenta y ajusta si no quieres depender de CURDATE())
-- SET @fecha_desde := '2026-05-10';
-- SET @fecha_hasta := '2026-05-14';

SELECT @fecha_desde AS fecha_desde_inclusive, @fecha_hasta AS fecha_hasta_inclusive;

-- -----------------------------------------------------------------------------
-- Vista previa: qué se va a actualizar
-- -----------------------------------------------------------------------------
SELECT
    r.Id_Resultado,
    r.Id_Evaluacion,
    r.Id_Persona,
    r.Intento_Numero,
    r.Puntaje,
    r.Correctas,
    r.Total_Preguntas,
    r.Aprobado AS aprobado_actual,
    DATE(r.Fecha_Presentacion) AS fecha_presentacion,
    e.Titulo,
    e.Nivel,
    e.Modulo_Numero,
    e.Puntaje_Minimo
FROM discipular_evaluacion_resultados r
INNER JOIN discipular_evaluaciones e ON e.Id_Evaluacion = r.Id_Evaluacion
WHERE r.Aprobado = 0
  AND DATE(r.Fecha_Presentacion) BETWEEN @fecha_desde AND @fecha_hasta
ORDER BY r.Fecha_Presentacion ASC, r.Id_Resultado ASC;

SELECT COUNT(*) AS total_reprobadas_a_normalizar
FROM discipular_evaluacion_resultados r
WHERE r.Aprobado = 0
  AND DATE(r.Fecha_Presentacion) BETWEEN @fecha_desde AND @fecha_hasta;

-- -----------------------------------------------------------------------------
-- Aplicar cambios (solo marca Aprobado = 1; no altera Puntaje/Correctas)
-- -----------------------------------------------------------------------------
-- START TRANSACTION;

UPDATE discipular_evaluacion_resultados r
INNER JOIN discipular_evaluaciones e ON e.Id_Evaluacion = r.Id_Evaluacion
SET r.Aprobado = 1
WHERE r.Aprobado = 0
  AND DATE(r.Fecha_Presentacion) BETWEEN @fecha_desde AND @fecha_hasta;

-- SELECT ROW_COUNT() AS filas_actualizadas;

-- COMMIT;
-- Si algo no cuadra: ROLLBACK;

-- =============================================================================
-- VARIANTE OPCIONAL (NO ejecutar junto con la anterior sin acordar criterio):
-- Además de Aprobado=1, sube el puntaje mostrado al mínimo exigido por la
-- evaluación si era inferior (no recalcula Correctas desde JSON).
-- =============================================================================
/*
START TRANSACTION;

UPDATE discipular_evaluacion_resultados r
INNER JOIN discipular_evaluaciones e ON e.Id_Evaluacion = r.Id_Evaluacion
SET
    r.Aprobado = 1,
    r.Puntaje = GREATEST(r.Puntaje, LEAST(100.00, COALESCE(e.Puntaje_Minimo, 80.00)))
WHERE r.Aprobado = 0
  AND DATE(r.Fecha_Presentacion) BETWEEN @fecha_desde AND @fecha_hasta;

COMMIT;
*/
