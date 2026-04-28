-- =============================================================================
-- Script: Pasar personas de Universidad de la Vida
--         a Capacitación Destino - Nivel 1
-- Ambiente: PRODUCCION
-- Objetivo: mover el programa actual de las 2 personas objetivo,
--           contemplando que en este sistema UV puede estar guardado como
--           'universidad_vida' o como 'encuentro'.
-- Personas: LAURA VALENTINA POSADA PARDO (CC 1015994401)
--           Laura Valentina Aponte Contreras (CC 1073156731)
-- Fecha: 2026-04-27
-- =============================================================================

-- IMPORTANTE:
-- 1) Revisar PASO 1 completo antes de tocar datos.
-- 2) Este script mueve registros de 'universidad_vida' o 'encuentro' a
--    'capacitacion_destino_nivel_1'.
-- 3) NO modifica escuela_formacion_estado.
--    Esa tabla se usa para marcar si una persona ya fue gestionada en una vista,
--    no para definir la inscripción activa.
-- 4) No toca asistencias históricas.
-- 5) Si ya ejecutaste una versión anterior de este script, este ajuste sigue
--    sirviendo para mover cualquier inscripción que todavía haya quedado en UV.


-- ── PASO 1A: Verificar identidad exacta de las personas ───────────────────────
SELECT
    p.Id_Persona,
    CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Completo,
    p.Numero_Documento,
    p.Telefono
FROM persona p
WHERE p.Numero_Documento IN ('1015994401', '1073156731')
ORDER BY p.Numero_Documento;


-- ── PASO 1B: Verificar inscripción actual en Universidad de la Vida ───────────
-- OJO: en algunos casos UV está guardado como 'encuentro'.
SELECT
    efi.Id_Inscripcion,
    efi.Id_Persona,
    CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Completo,
    p.Numero_Documento,
    efi.Programa AS Programa_Actual,
    efi.Fuente,
    efi.Fecha_Registro
FROM escuela_formacion_inscripcion efi
INNER JOIN persona p ON p.Id_Persona = efi.Id_Persona
WHERE p.Numero_Documento IN ('1015994401', '1073156731')
  AND efi.Programa IN ('universidad_vida', 'encuentro', 'capacitacion_destino_nivel_1')
ORDER BY p.Numero_Documento, efi.Id_Inscripcion;


-- ── PASO 1C: Verificación opcional de estado actual por programa ──────────────
-- Solo informativo. No se modifica en este script.
SELECT
    efe.Id_Estado,
    efe.Id_Persona,
    CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Completo,
    p.Numero_Documento,
    efe.Programa,
    efe.Va,
    efe.Fecha_Actualizacion
FROM escuela_formacion_estado efe
INNER JOIN persona p ON p.Id_Persona = efe.Id_Persona
WHERE p.Numero_Documento IN ('1015994401', '1073156731')
  AND efe.Programa IN ('universidad_vida', 'encuentro', 'capacitacion_destino_nivel_1')
ORDER BY p.Numero_Documento, efe.Programa;


-- ── PASO 0 OPCIONAL: respaldos lógicos antes del cambio ───────────────────────
-- CREATE TABLE respaldo_efi_20260427_uv_a_cd_n1 AS
-- SELECT efi.*
-- FROM escuela_formacion_inscripcion efi
-- INNER JOIN persona p ON p.Id_Persona = efi.Id_Persona
-- WHERE p.Numero_Documento IN ('1015994401', '1073156731')
--   AND efi.Programa IN ('universidad_vida', 'encuentro');
--
-- CREATE TABLE respaldo_efe_20260427_uv_a_cd_n1 AS
-- SELECT efe.*
-- FROM escuela_formacion_estado efe
-- INNER JOIN persona p ON p.Id_Persona = efe.Id_Persona
-- WHERE p.Numero_Documento IN ('1015994401', '1073156731')
--   AND efe.Programa IN ('universidad_vida', 'encuentro', 'capacitacion_destino_nivel_1');


-- ── PASO 2: Ejecutar el traslado dentro de transacción ────────────────────────
START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_personas_objetivo_cd_n1;
CREATE TEMPORARY TABLE tmp_personas_objetivo_cd_n1 AS
SELECT p.Id_Persona, p.Numero_Documento
FROM persona p
WHERE p.Numero_Documento IN ('1015994401', '1073156731');

-- 2A. Mover la inscripción: UV -> Capacitación Destino Nivel 1
UPDATE escuela_formacion_inscripcion efi
INNER JOIN tmp_personas_objetivo_cd_n1 t ON t.Id_Persona = efi.Id_Persona
SET efi.Programa = 'capacitacion_destino_nivel_1'
WHERE efi.Programa IN ('universidad_vida', 'encuentro');

SELECT ROW_COUNT() AS Filas_Inscripcion_Actualizadas;


-- ── PASO 3A: Validar resultado de inscripción antes del COMMIT ────────────────
SELECT
    efi.Id_Inscripcion,
    efi.Id_Persona,
    CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Completo,
    p.Numero_Documento,
    efi.Programa AS Programa_Final,
    efi.Fuente,
    efi.Fecha_Registro
FROM escuela_formacion_inscripcion efi
INNER JOIN persona p ON p.Id_Persona = efi.Id_Persona
WHERE p.Numero_Documento IN ('1015994401', '1073156731')
ORDER BY p.Numero_Documento, efi.Id_Inscripcion;


-- ── PASO 3B: Verificación opcional de estado antes del COMMIT ─────────────────
SELECT
    efe.Id_Estado,
    efe.Id_Persona,
    CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Completo,
    p.Numero_Documento,
    efe.Programa AS Programa_Final,
    efe.Va,
    efe.Fecha_Actualizacion
FROM escuela_formacion_estado efe
INNER JOIN persona p ON p.Id_Persona = efe.Id_Persona
WHERE p.Numero_Documento IN ('1015994401', '1073156731')
ORDER BY p.Numero_Documento, efe.Programa;


-- ── PASO 3C: Verificación opcional del proceso actual de la persona ───────────
SELECT
  p.Id_Persona,
  CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Completo,
  p.Numero_Documento,
  p.Proceso,
  p.Escalera_Checklist
FROM persona p
WHERE p.Numero_Documento IN ('1015994401', '1073156731')
ORDER BY p.Numero_Documento;


-- ── PASO 4: Confirmación manual ───────────────────────────────────────────────
-- Hacer COMMIT solo si se cumple esto:
-- 1) Filas_Inscripcion_Actualizadas = 2, o el PASO 1B ya mostraba que alguna
--    persona estaba previamente en 'capacitacion_destino_nivel_1'
-- 2) En PASO 3A ya no aparece 'universidad_vida' ni 'encuentro' para esas dos personas
-- 3) En PASO 3A sí aparece 'capacitacion_destino_nivel_1'
--
-- COMMIT;
-- ROLLBACK;
