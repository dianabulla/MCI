-- =============================================================================
-- Auditoría y recuperación: personas afectadas por registro público UV / Cap. Destino
-- y/o reasignación automática (pierden líder/ministerio y aparecen en Discípulos pendientes)
-- Fecha guía: 2026-05-26
-- =============================================================================
--
-- IMPORTANTE:
-- 1) Ejecutar primero TODOS los SELECT (secciones 1 a 4).
-- 2) Hacer respaldo antes de cualquier UPDATE (sección 5).
-- 3) Ajustar nombre de BD de respaldo si aplica (sección 6).
-- 4) Herramienta PHP opcional: php tools/auditar_formulario_uv_afectados.php
--    Restaurar por célula:     php tools/restaurar_reasignados_automaticos.php
--                             php tools/restaurar_reasignados_automaticos.php --aplicar
--
-- =============================================================================
-- 1) RESUMEN RÁPIDO
-- =============================================================================

SELECT 'Inscritos UV/CD sin célula (personas nuevas)' AS metrica,
       COUNT(DISTINCT p.Id_Persona) AS total
FROM persona p
INNER JOIN escuela_formacion_inscripcion efi ON efi.Id_Persona = p.Id_Persona
WHERE efi.Programa IN (
    'universidad_vida', 'encuentro',
    'capacitacion_destino', 'capacitacion_destino_nivel_1',
    'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'
)
  AND (p.Id_Celula IS NULL OR p.Id_Celula = 0)
  AND COALESCE(p.Es_Antiguo, 0) = 0;

-- Candidatos fuertes al bug: ya existían en persona ANTES de inscribirse y hoy no tienen célula
SELECT 'Existían antes de inscripción UV/CD y hoy sin célula' AS metrica,
       COUNT(*) AS total
FROM (
    SELECT
        p.Id_Persona,
        p.Fecha_Registro AS fecha_persona,
        MIN(efi.Fecha_Registro) AS primera_inscripcion
    FROM persona p
    INNER JOIN escuela_formacion_inscripcion efi ON efi.Id_Persona = p.Id_Persona
    WHERE efi.Programa IN (
        'universidad_vida', 'encuentro',
        'capacitacion_destino', 'capacitacion_destino_nivel_1',
        'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'
    )
      AND (p.Id_Celula IS NULL OR p.Id_Celula = 0)
    GROUP BY p.Id_Persona, p.Fecha_Registro
) t
WHERE t.fecha_persona < DATE_SUB(t.primera_inscripcion, INTERVAL 1 DAY);

-- Tienen célula pero perdieron líder/ministerio (reasignación automática u otro)
SELECT 'Con célula pero sin líder/ministerio (reasignado auto)' AS metrica,
       COUNT(*) AS total
FROM persona p
WHERE p.Id_Celula IS NOT NULL AND p.Id_Celula > 0
  AND (p.Id_Lider IS NULL OR p.Id_Lider = 0)
  AND (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
  AND p.Escalera_Checklist LIKE '%"reasignado_automatico":true%';

-- Restaurables solo con datos actuales (célula → líder/ministerio de la célula)
SELECT 'Restaurables desde célula actual' AS metrica,
       COUNT(*) AS total
FROM persona p
INNER JOIN celula c ON c.Id_Celula = p.Id_Celula
LEFT JOIN persona pl ON pl.Id_Persona = c.Id_Lider
WHERE p.Id_Celula IS NOT NULL AND p.Id_Celula > 0
  AND (p.Id_Lider IS NULL OR p.Id_Lider = 0)
  AND (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
  AND c.Id_Lider IS NOT NULL AND c.Id_Lider > 0
  AND COALESCE(pl.Id_Ministerio, c.Id_Ministerio, 0) > 0;
  -- Si su tabla celula usa Id_Ministerio_Lider, cambie la línea anterior por:
  -- AND COALESCE(pl.Id_Ministerio, c.Id_Ministerio_Lider, 0) > 0;


-- =============================================================================
-- 2) DETALLE: posibles afectados por formulario (existían antes + inscripción UV/CD)
-- =============================================================================

SELECT
    p.Id_Persona,
    TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS nombre_completo,
    p.Numero_Documento,
    p.Telefono,
    p.Fecha_Registro AS fecha_alta_persona,
    t.primera_inscripcion,
    t.ultima_inscripcion,
    t.programas,
    p.Id_Celula,
    p.Id_Lider,
    p.Id_Ministerio,
    c.Nombre_Celula,
    p.Canal_Creacion,
    p.Proceso
FROM persona p
INNER JOIN (
    SELECT
        efi.Id_Persona,
        MIN(efi.Fecha_Registro) AS primera_inscripcion,
        MAX(efi.Fecha_Registro) AS ultima_inscripcion,
        GROUP_CONCAT(DISTINCT efi.Programa ORDER BY efi.Programa SEPARATOR ', ') AS programas
    FROM escuela_formacion_inscripcion efi
    WHERE efi.Programa IN (
        'universidad_vida', 'encuentro',
        'capacitacion_destino', 'capacitacion_destino_nivel_1',
        'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'
    )
      AND efi.Id_Persona IS NOT NULL
      AND efi.Id_Persona > 0
    GROUP BY efi.Id_Persona
) t ON t.Id_Persona = p.Id_Persona
LEFT JOIN celula c ON c.Id_Celula = p.Id_Celula
WHERE p.Fecha_Registro < DATE_SUB(t.primera_inscripcion, INTERVAL 1 DAY)
  AND (p.Id_Celula IS NULL OR p.Id_Celula = 0)
ORDER BY t.ultima_inscripcion DESC
LIMIT 500;


-- =============================================================================
-- 3) DETALLE: con célula pero pendientes por conectar (líder/ministerio vacíos)
-- =============================================================================

SELECT
    p.Id_Persona,
    TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS nombre_completo,
    p.Numero_Documento,
    p.Id_Celula,
    c.Nombre_Celula,
    c.Id_Lider AS lider_desde_celula,
    TRIM(CONCAT(COALESCE(pl.Nombre, ''), ' ', COALESCE(pl.Apellido, ''))) AS nombre_lider_celula,
    COALESCE(pl.Id_Ministerio, c.Id_Ministerio_Lider) AS ministerio_desde_celula,
    p.Id_Lider AS lider_actual,
    p.Id_Ministerio AS ministerio_actual,
    p.Escalera_Checklist
FROM persona p
INNER JOIN celula c ON c.Id_Celula = p.Id_Celula
LEFT JOIN persona pl ON pl.Id_Persona = c.Id_Lider
WHERE p.Id_Celula IS NOT NULL AND p.Id_Celula > 0
  AND (p.Id_Lider IS NULL OR p.Id_Lider = 0)
  AND (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
ORDER BY p.Id_Persona DESC
LIMIT 500;


-- =============================================================================
-- 4) CRUCE con inscripción UV/CD (última inscripción por persona)
-- =============================================================================

SELECT
    p.Id_Persona,
    TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS nombre_completo,
    efi.Programa,
    efi.Fecha_Registro AS fecha_inscripcion,
    efi.Fuente,
    p.Id_Celula,
    c.Nombre_Celula,
    p.Id_Lider,
    p.Id_Ministerio
FROM persona p
INNER JOIN escuela_formacion_inscripcion efi ON efi.Id_Persona = p.Id_Persona
INNER JOIN (
    SELECT Id_Persona, MAX(Id_Inscripcion) AS ultima_id
    FROM escuela_formacion_inscripcion
    WHERE Id_Persona IS NOT NULL AND Id_Persona > 0
    GROUP BY Id_Persona
) u ON u.ultima_id = efi.Id_Inscripcion
LEFT JOIN celula c ON c.Id_Celula = p.Id_Celula
WHERE efi.Programa IN (
    'universidad_vida', 'encuentro',
    'capacitacion_destino', 'capacitacion_destino_nivel_1',
    'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'
)
  AND (
        (p.Id_Celula IS NULL OR p.Id_Celula = 0)
        OR (
            (p.Id_Lider IS NULL OR p.Id_Lider = 0)
            AND (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
        )
      )
ORDER BY efi.Fecha_Registro DESC
LIMIT 500;


-- =============================================================================
-- 5) RESPALDO ANTES DE RECUPERAR (ejecutar una vez)
-- =============================================================================

-- CREATE TABLE persona_backup_pre_fix_formulario_uv_20260526 AS
-- SELECT * FROM persona;


-- =============================================================================
-- 6) RECUPERACIÓN A — Desde célula actual (líder y ministerio de la célula)
--    Solo si Id_Celula sigue poblado. Revisar SELECT de sección 3 antes.
-- =============================================================================

-- START TRANSACTION;
--
-- UPDATE persona p
-- INNER JOIN celula c ON c.Id_Celula = p.Id_Celula
-- LEFT JOIN persona pl ON pl.Id_Persona = c.Id_Lider
-- SET
--     p.Id_Lider = c.Id_Lider,
--     p.Id_Ministerio = COALESCE(pl.Id_Ministerio, c.Id_Ministerio_Lider),
--     p.Fecha_Asignacion_Lider = COALESCE(NULLIF(p.Fecha_Asignacion_Lider, '0000-00-00 00:00:00'), NOW())
-- WHERE p.Id_Celula IS NOT NULL AND p.Id_Celula > 0
--   AND (p.Id_Lider IS NULL OR p.Id_Lider = 0)
--   AND (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
--   AND c.Id_Lider IS NOT NULL AND c.Id_Lider > 0
--   AND COALESCE(pl.Id_Ministerio, c.Id_Ministerio, 0) > 0;
  -- Si su tabla celula usa Id_Ministerio_Lider, cambie la línea anterior por:
  -- AND COALESCE(pl.Id_Ministerio, c.Id_Ministerio_Lider, 0) > 0;
--
-- SELECT ROW_COUNT() AS filas_actualizadas_recuperacion_celula;
-- COMMIT;
-- -- ROLLBACK;  -- si algo no cuadra


-- =============================================================================
-- 7) RECUPERACIÓN B — Desde BD de respaldo (si tenían célula y hoy está vacía)
--    Ajustar: mcimadrid = BD actual, u694856656_mci = respaldo (ejemplo del proyecto)
-- =============================================================================

-- SELECT COUNT(*) AS candidatos_desde_backup
-- FROM persona p
-- INNER JOIN u694856656_mci.persona pb ON pb.Id_Persona = p.Id_Persona
-- INNER JOIN escuela_formacion_inscripcion efi ON efi.Id_Persona = p.Id_Persona
-- WHERE efi.Programa IN ('universidad_vida', 'encuentro', 'capacitacion_destino',
--       'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3')
--   AND (p.Id_Celula IS NULL OR p.Id_Celula = 0)
--   AND (pb.Id_Celula IS NOT NULL AND pb.Id_Celula > 0);
--
-- START TRANSACTION;
--
-- UPDATE persona p
-- INNER JOIN u694856656_mci.persona pb ON pb.Id_Persona = p.Id_Persona
-- SET
--     p.Id_Celula = pb.Id_Celula,
--     p.Id_Lider = CASE
--         WHEN (p.Id_Lider IS NULL OR p.Id_Lider = 0) AND (pb.Id_Lider IS NOT NULL AND pb.Id_Lider > 0)
--         THEN pb.Id_Lider ELSE p.Id_Lider END,
--     p.Id_Ministerio = CASE
--         WHEN (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0) AND (pb.Id_Ministerio IS NOT NULL AND pb.Id_Ministerio > 0)
--         THEN pb.Id_Ministerio ELSE p.Id_Ministerio END
-- WHERE (p.Id_Celula IS NULL OR p.Id_Celula = 0)
--   AND (pb.Id_Celula IS NOT NULL AND pb.Id_Celula > 0);
--
-- COMMIT;


-- =============================================================================
-- 8) Verificación post-recuperación
-- =============================================================================

SELECT
    COUNT(*) AS pendientes_sin_celula_con_inscripcion_uv_cd
FROM persona p
INNER JOIN escuela_formacion_inscripcion efi ON efi.Id_Persona = p.Id_Persona
WHERE efi.Programa IN (
    'universidad_vida', 'encuentro',
    'capacitacion_destino', 'capacitacion_destino_nivel_1',
    'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'
)
  AND (p.Id_Celula IS NULL OR p.Id_Celula = 0)
  AND p.Fecha_Registro < DATE_SUB(efi.Fecha_Registro, INTERVAL 1 DAY);
