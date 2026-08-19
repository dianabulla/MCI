-- =============================================================================
-- Marcar Entrego_Sobre = 1 para TODAS las semanas de MARZO y ABRIL
-- Tabla: asistencia_entrega_sobre_semana
-- Semana_Inicio = lunes de la semana (igual que la pantalla de Asistencias)
--
-- Compatible con MariaDB / phpMyAdmin (sin CTE recursivo en el INSERT).
--
-- USO EN PRODUCCIÓN:
--   1. Cambia @anio si aplica (por defecto 2026).
--   2. Ejecuta TODO el script de una vez, o por bloques en orden.
--   3. Revisa los SELECT de vista previa y verificación.
--   4. Si cuadra: COMMIT. Si no: ROLLBACK.
-- =============================================================================

SET @anio := 2026;

SET @marzo_inicio := CONCAT(@anio, '-03-01');
SET @abril_fin    := CONCAT(@anio, '-04-30');

-- Primer y último lunes de semanas que tocan marzo/abril
SET @primer_lunes := DATE_SUB(@marzo_inicio, INTERVAL WEEKDAY(@marzo_inicio) DAY);
SET @ultimo_lunes := DATE_SUB(@abril_fin, INTERVAL WEEKDAY(@abril_fin) DAY);

DROP TEMPORARY TABLE IF EXISTS tmp_semanas_marzo_abril;
CREATE TEMPORARY TABLE tmp_semanas_marzo_abril (
    Semana_Inicio DATE NOT NULL PRIMARY KEY
) ENGINE=Memory;

INSERT INTO tmp_semanas_marzo_abril (Semana_Inicio)
SELECT DATE_ADD(@primer_lunes, INTERVAL (n.n * 7) DAY)
FROM (
    SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL
    SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL
    SELECT 8 UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
) n
WHERE DATE_ADD(@primer_lunes, INTERVAL (n.n * 7) DAY) <= @ultimo_lunes;

-- ── VISTA PREVIA ──────────────────────────────────────────────────────────────

SELECT
    @anio AS anio,
    @marzo_inicio AS marzo_inicio,
    @abril_fin AS abril_fin,
    @primer_lunes AS primer_lunes_semana,
    @ultimo_lunes AS ultimo_lunes_semana;

SELECT Semana_Inicio, DATE_ADD(Semana_Inicio, INTERVAL 6 DAY) AS semana_fin
FROM tmp_semanas_marzo_abril
ORDER BY Semana_Inicio;

SELECT COUNT(*) AS celulas_activas
FROM celula c
WHERE c.Id_Celula > 0
  AND COALESCE(c.Estado_Celula, 'Activa') NOT IN ('Inactiva', 'Cerrada', '0');

SELECT
    (SELECT COUNT(*) FROM celula c
      WHERE c.Id_Celula > 0
        AND COALESCE(c.Estado_Celula, 'Activa') NOT IN ('Inactiva', 'Cerrada', '0')
    ) AS celulas_activas,
    (SELECT COUNT(*) FROM tmp_semanas_marzo_abril) AS semanas,
    (SELECT COUNT(*) FROM celula c
      WHERE c.Id_Celula > 0
        AND COALESCE(c.Estado_Celula, 'Activa') NOT IN ('Inactiva', 'Cerrada', '0')
    ) * (SELECT COUNT(*) FROM tmp_semanas_marzo_abril) AS filas_a_insertar_o_actualizar;

SELECT
    SUM(CASE WHEN es.Entrego_Sobre = 1 THEN 1 ELSE 0 END) AS ya_entrego_sobre,
    SUM(CASE WHEN COALESCE(es.Entrego_Sobre, 0) = 0 THEN 1 ELSE 0 END) AS sin_marcar_o_no_existe
FROM celula c
CROSS JOIN tmp_semanas_marzo_abril s
LEFT JOIN asistencia_entrega_sobre_semana es
       ON es.Id_Celula = c.Id_Celula
      AND es.Semana_Inicio = s.Semana_Inicio
WHERE c.Id_Celula > 0
  AND COALESCE(c.Estado_Celula, 'Activa') NOT IN ('Inactiva', 'Cerrada', '0');

-- ── APLICAR CAMBIOS ───────────────────────────────────────────────────────────

START TRANSACTION;

INSERT INTO asistencia_entrega_sobre_semana (Id_Celula, Semana_Inicio, Entrego_Sobre)
SELECT c.Id_Celula, s.Semana_Inicio, 1
FROM celula c
CROSS JOIN tmp_semanas_marzo_abril s
WHERE c.Id_Celula > 0
  AND COALESCE(c.Estado_Celula, 'Activa') NOT IN ('Inactiva', 'Cerrada', '0')
ON DUPLICATE KEY UPDATE
    Entrego_Sobre = 1,
    Actualizado_En = NOW();

SELECT ROW_COUNT() AS filas_afectadas_por_insert;

SELECT
    COUNT(*) AS total_combinaciones,
    SUM(CASE WHEN es.Entrego_Sobre = 1 THEN 1 ELSE 0 END) AS con_entrego_sobre,
    SUM(CASE WHEN COALESCE(es.Entrego_Sobre, 0) = 0 THEN 1 ELSE 0 END) AS aun_sin_marcar
FROM celula c
CROSS JOIN tmp_semanas_marzo_abril s
INNER JOIN asistencia_entrega_sobre_semana es
        ON es.Id_Celula = c.Id_Celula
       AND es.Semana_Inicio = s.Semana_Inicio
WHERE c.Id_Celula > 0
  AND COALESCE(c.Estado_Celula, 'Activa') NOT IN ('Inactiva', 'Cerrada', '0');

-- Si todo está bien:
COMMIT;

-- Si algo no cuadra, descomenta esto en lugar de COMMIT:
-- ROLLBACK;

DROP TEMPORARY TABLE IF EXISTS tmp_semanas_marzo_abril;
