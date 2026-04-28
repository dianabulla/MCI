-- =============================================================================
-- ELIMINACIÓN COMPLETA: Eliminar 2 personas de todas las tablas
-- LAURA VALENTINA APONTE CONTRERAS (CC 1073156731)
-- LAURA VALENTINA POSADA PARDO (CC 1015994401)
-- ENTORNO: PRODUCCIÓN
-- Fecha: 2026-04-27
-- =============================================================================

START TRANSACTION;

-- ── PASO 0: Crear tabla de backup (OPCIONAL - comentar si no quieres backup) ──
-- CREATE TABLE backup_personas_eliminadas AS
-- SELECT * FROM persona WHERE Numero_Documento IN ('1073156731', '1015994401');
-- 
-- CREATE TABLE backup_inscripciones_eliminadas AS
-- SELECT * FROM escuela_formacion_inscripcion WHERE Cedula IN ('1073156731', '1015994401');


-- ── PASO 1: Obtener Ids de personas a eliminar ──
SELECT 'PASO 1: Identificar personas a eliminar' AS paso;
SELECT @id_persona_1 := Id_Persona FROM persona WHERE Numero_Documento = '1073156731' LIMIT 1;
SELECT @id_persona_2 := Id_Persona FROM persona WHERE Numero_Documento = '1015994401' LIMIT 1;

SELECT CONCAT('LAURA VALENTINA APONTE - ID: ', COALESCE(@id_persona_1, 'NO ENCONTRADA')) AS persona_1;
SELECT CONCAT('LAURA VALENTINA POSADA - ID: ', COALESCE(@id_persona_2, 'NO ENCONTRADA')) AS persona_2;


-- ── PASO 2: Eliminar de escuela_formacion_asistencia_clase ──
SELECT 'PASO 2: Eliminar de escuela_formacion_asistencia_clase' AS paso;
DELETE FROM escuela_formacion_asistencia_clase
WHERE Id_Persona IN (@id_persona_1, @id_persona_2);
SELECT ROW_COUNT() AS filas_eliminadas_asistencia;


-- ── PASO 3: Eliminar de escuela_formacion_estado ──
SELECT 'PASO 3: Eliminar de escuela_formacion_estado' AS paso;
DELETE FROM escuela_formacion_estado
WHERE Id_Persona IN (@id_persona_1, @id_persona_2);
SELECT ROW_COUNT() AS filas_eliminadas_estado;


-- ── PASO 4: Eliminar de escuela_formacion_inscripcion ──
SELECT 'PASO 4: Eliminar de escuela_formacion_inscripcion' AS paso;
DELETE FROM escuela_formacion_inscripcion
WHERE Cedula IN ('1073156731', '1015994401');
SELECT ROW_COUNT() AS filas_eliminadas_inscripcion;


-- ── PASO 5: Eliminar de tabla persona ──
SELECT 'PASO 5: Eliminar de tabla persona' AS paso;
DELETE FROM persona
WHERE Numero_Documento IN ('1073156731', '1015994401');
SELECT ROW_COUNT() AS filas_eliminadas_persona;


-- ── PASO 6: Verificar que fueron eliminadas ──
SELECT 'PASO 6: VERIFICACIÓN - Deben mostrar 0 registros' AS paso;

SELECT '  - Búsqueda en persona:' AS verificacion;
SELECT COUNT(*) AS registros_persona FROM persona WHERE Numero_Documento IN ('1073156731', '1015994401');

SELECT '  - Búsqueda en escuela_formacion_inscripcion:' AS verificacion;
SELECT COUNT(*) AS registros_inscripcion FROM escuela_formacion_inscripcion WHERE Cedula IN ('1073156731', '1015994401');

SELECT '  - Búsqueda en escuela_formacion_estado:' AS verificacion;
SELECT COUNT(*) AS registros_estado FROM escuela_formacion_estado WHERE Id_Persona IN (@id_persona_1, @id_persona_2);


-- ── PASO 7: CONFIRMACIÓN MANUAL ──
SELECT '
=============================================================================
ATENCIÓN: CONFIRMAR ANTES DE HACER COMMIT
=============================================================================

Para CONFIRMAR la eliminación (HACER PERMANENTE), ejecuta:
    COMMIT;

Para CANCELAR la eliminación (DESHACER TODO), ejecuta:
    ROLLBACK;

Si todo está correcto y el conteo de verificación en PASO 6 muestra 0:
    COMMIT;
=============================================================================
' AS INSTRUCCIONES;
