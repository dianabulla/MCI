-- Recuperar asignaciones de persona desde respaldo del 2025-03-13
-- Objetivo: completar Id_Ministerio, Id_Celula, Id_Lider SOLO cuando falten en la BD actual.
-- Clave de cruce: Id_Persona
--
-- IMPORTANTE:
-- 1) Cambia los nombres de base si en tu servidor son distintos.
-- 2) Primero ejecuta los SELECT de vista previa.
-- 3) Ejecuta el UPDATE dentro de transaccion.

-- =====================================================================
-- CONFIGURACION (ajustar si aplica)
-- =====================================================================
-- BD actual
--   mcimadrid
-- BD respaldo (13 de marzo)
--   u694856656_mci

-- =====================================================================
-- 1) VISTA PREVIA: cuantos registros serian actualizados
-- =====================================================================
SELECT
    COUNT(*) AS total_candidatos
FROM mcimadrid.persona p
INNER JOIN u694856656_mci.persona pb
    ON pb.Id_Persona = p.Id_Persona
WHERE
    (
        (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
        AND (pb.Id_Ministerio IS NOT NULL AND pb.Id_Ministerio > 0)
    )
    OR
    (
        (p.Id_Celula IS NULL OR p.Id_Celula = 0)
        AND (pb.Id_Celula IS NOT NULL AND pb.Id_Celula > 0)
    )
    OR
    (
        (p.Id_Lider IS NULL OR p.Id_Lider = 0)
        AND (pb.Id_Lider IS NOT NULL AND pb.Id_Lider > 0)
    );

-- =====================================================================
-- 2) VISTA PREVIA: detalle de personas afectadas (muestra 300)
-- =====================================================================
SELECT
    p.Id_Persona,
    p.Nombre,
    p.Apellido,
    p.Id_Ministerio AS actual_ministerio,
    pb.Id_Ministerio AS backup_ministerio,
    p.Id_Celula AS actual_celula,
    pb.Id_Celula AS backup_celula,
    p.Id_Lider AS actual_lider,
    pb.Id_Lider AS backup_lider
FROM mcimadrid.persona p
INNER JOIN u694856656_mci.persona pb
    ON pb.Id_Persona = p.Id_Persona
WHERE
    (
        (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
        AND (pb.Id_Ministerio IS NOT NULL AND pb.Id_Ministerio > 0)
    )
    OR
    (
        (p.Id_Celula IS NULL OR p.Id_Celula = 0)
        AND (pb.Id_Celula IS NOT NULL AND pb.Id_Celula > 0)
    )
    OR
    (
        (p.Id_Lider IS NULL OR p.Id_Lider = 0)
        AND (pb.Id_Lider IS NOT NULL AND pb.Id_Lider > 0)
    )
ORDER BY p.Id_Persona
LIMIT 300;

-- =====================================================================
-- 3) RESPALDO RAPIDO RECOMENDADO
-- =====================================================================
-- CREATE TABLE mcimadrid.persona_backup_pre_reasignacion_20260319 AS
-- SELECT * FROM mcimadrid.persona;

-- =====================================================================
-- 4) UPDATE CONTROLADO (solo rellena faltantes)
-- =====================================================================
START TRANSACTION;

UPDATE mcimadrid.persona p
INNER JOIN u694856656_mci.persona pb
    ON pb.Id_Persona = p.Id_Persona
SET
    p.Id_Ministerio = CASE
        WHEN (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
             AND (pb.Id_Ministerio IS NOT NULL AND pb.Id_Ministerio > 0)
        THEN pb.Id_Ministerio
        ELSE p.Id_Ministerio
    END,
    p.Id_Celula = CASE
        WHEN (p.Id_Celula IS NULL OR p.Id_Celula = 0)
             AND (pb.Id_Celula IS NOT NULL AND pb.Id_Celula > 0)
        THEN pb.Id_Celula
        ELSE p.Id_Celula
    END,
    p.Id_Lider = CASE
        WHEN (p.Id_Lider IS NULL OR p.Id_Lider = 0)
             AND (pb.Id_Lider IS NOT NULL AND pb.Id_Lider > 0)
        THEN pb.Id_Lider
        ELSE p.Id_Lider
    END,
    p.Fecha_Asignacion_Lider = CASE
        WHEN (p.Fecha_Asignacion_Lider IS NULL OR p.Fecha_Asignacion_Lider = '0000-00-00 00:00:00')
             AND (
                 ((p.Id_Lider IS NULL OR p.Id_Lider = 0) AND (pb.Id_Lider IS NOT NULL AND pb.Id_Lider > 0))
                 OR
                 ((p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0) AND (pb.Id_Ministerio IS NOT NULL AND pb.Id_Ministerio > 0))
             )
        THEN NOW()
        ELSE p.Fecha_Asignacion_Lider
    END
WHERE
    (
        (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
        AND (pb.Id_Ministerio IS NOT NULL AND pb.Id_Ministerio > 0)
    )
    OR
    (
        (p.Id_Celula IS NULL OR p.Id_Celula = 0)
        AND (pb.Id_Celula IS NOT NULL AND pb.Id_Celula > 0)
    )
    OR
    (
        (p.Id_Lider IS NULL OR p.Id_Lider = 0)
        AND (pb.Id_Lider IS NOT NULL AND pb.Id_Lider > 0)
    );

-- Ver cuantas filas tocaste
SELECT ROW_COUNT() AS filas_actualizadas;

-- Validacion post-update
SELECT
    SUM(CASE WHEN Id_Ministerio IS NULL OR Id_Ministerio = 0 THEN 1 ELSE 0 END) AS sin_ministerio,
    SUM(CASE WHEN Id_Celula IS NULL OR Id_Celula = 0 THEN 1 ELSE 0 END) AS sin_celula,
    SUM(CASE WHEN Id_Lider IS NULL OR Id_Lider = 0 THEN 1 ELSE 0 END) AS sin_lider
FROM mcimadrid.persona;

-- Si todo bien:
COMMIT;

-- Si algo no cuadra, usar:
-- ROLLBACK;
