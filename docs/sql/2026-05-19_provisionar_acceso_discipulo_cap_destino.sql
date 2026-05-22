-- =============================================================================
-- Provisionar acceso discípulo: inscritos Capacitación Destino (todos los niveles)
-- Fecha: 2026-05-19
--
-- Replica la lógica de app/Helpers/AccesoDiscipuloCapDestino.php:
--   - Usuario y contraseña = cédula (mayúsculas, sin espacios)
--   - Rol discípulo en user_roles (segundo rol si ya es líder/pastor/admin)
--   - Id_Rol principal: solo cambia a discípulo si NO es liderazgo
--
-- IMPORTANTE producción:
--   1) Ejecutar primero las consultas de PREVIEW (sección 0).
--   2) Hacer backup de tablas persona y user_roles.
--   3) La contraseña se guarda en texto plano (= cédula); al primer login
--      la app la convierte a bcrypt (ver Persona::autenticar).
--   4) No sobrescribe Usuario/Contrasena si la persona ya tiene Usuario.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 0) PREVIEW (solo lectura) — ejecutar y revisar antes del UPDATE
-- -----------------------------------------------------------------------------

SELECT COUNT(DISTINCT i.Id_Inscripcion) AS total_inscripciones_cap_destino
FROM escuela_formacion_inscripcion i
WHERE LOWER(TRIM(i.Programa)) IN (
    'capacitacion_destino',
    'capacitacion_destino_nivel_1',
    'capacitacion_destino_nivel_2',
    'capacitacion_destino_nivel_3'
);

-- Personas que se provisionarían (con cédula resoluble)
-- (ejecutar después de crear tmp_cap_destino_personas en sección 1, o usar la vista lógica abajo)

-- -----------------------------------------------------------------------------
-- 1) Tabla temporal: una fila por persona inscrita en Cap. Destino
-- -----------------------------------------------------------------------------

DROP TEMPORARY TABLE IF EXISTS tmp_cap_destino_personas;

CREATE TEMPORARY TABLE tmp_cap_destino_personas (
    Id_Persona INT NOT NULL PRIMARY KEY,
    Cedula VARCHAR(64) NOT NULL,
    Id_Rol_Actual INT NOT NULL DEFAULT 0,
    Es_Liderazgo TINYINT(1) NOT NULL DEFAULT 0,
    Tiene_Usuario TINYINT(1) NOT NULL DEFAULT 0
);

INSERT INTO tmp_cap_destino_personas (Id_Persona, Cedula, Id_Rol_Actual, Es_Liderazgo, Tiene_Usuario)
SELECT
    p.Id_Persona,
    UPPER(REPLACE(REPLACE(REPLACE(TRIM(src.Cedula), ' ', ''), '.', ''), '-', '')) AS Cedula,
    COALESCE(p.Id_Rol, 0) AS Id_Rol_Actual,
    CASE
        WHEN COALESCE(p.Id_Rol, 0) IN (3, 8) THEN 1
        WHEN LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(r.Nombre_Rol, ''), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%admin%' THEN 1
        WHEN LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(r.Nombre_Rol, ''), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%pastor%' THEN 1
        WHEN LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(r.Nombre_Rol, ''), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%lider de 12%'
          OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(r.Nombre_Rol, ''), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%lider 12%'
          OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(r.Nombre_Rol, ''), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%lideres de 12%' THEN 1
        WHEN LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(r.Nombre_Rol, ''), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%lider de 144%'
          OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(r.Nombre_Rol, ''), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%lider 144%'
          OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(r.Nombre_Rol, ''), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%lideres de 144%' THEN 1
        WHEN LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(r.Nombre_Rol, ''), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%lider de celula%'
          OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(r.Nombre_Rol, ''), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%lider celula%' THEN 1
        ELSE 0
    END AS Es_Liderazgo,
    CASE WHEN NULLIF(TRIM(p.Usuario), '') IS NOT NULL THEN 1 ELSE 0 END AS Tiene_Usuario
FROM (
    SELECT DISTINCT
        COALESCE(
            NULLIF(i.Id_Persona, 0),
            (
                SELECT p2.Id_Persona
                FROM persona p2
                WHERE REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(p2.Numero_Documento, ''))), ' ', ''), '.', ''), '-', '')
                    = REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(i.Cedula, ''))), ' ', ''), '.', ''), '-', '')
                ORDER BY p2.Id_Persona DESC
                LIMIT 1
            )
        ) AS Id_Persona,
        COALESCE(
            NULLIF(TRIM(i.Cedula), ''),
            (
                SELECT p3.Numero_Documento
                FROM persona p3
                WHERE p3.Id_Persona = NULLIF(i.Id_Persona, 0)
                LIMIT 1
            )
        ) AS Cedula
    FROM escuela_formacion_inscripcion i
    WHERE LOWER(TRIM(i.Programa)) IN (
        'capacitacion_destino',
        'capacitacion_destino_nivel_1',
        'capacitacion_destino_nivel_2',
        'capacitacion_destino_nivel_3'
    )
) src
INNER JOIN persona p ON p.Id_Persona = src.Id_Persona
LEFT JOIN rol r ON r.Id_Rol = p.Id_Rol
WHERE src.Id_Persona IS NOT NULL
  AND src.Id_Persona > 0
  AND NULLIF(REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(src.Cedula, ''))), ' ', ''), '.', ''), '-', ''), '') IS NOT NULL
ON DUPLICATE KEY UPDATE
    Cedula = VALUES(Cedula),
    Id_Rol_Actual = VALUES(Id_Rol_Actual),
    Es_Liderazgo = VALUES(Es_Liderazgo),
    Tiene_Usuario = VALUES(Tiene_Usuario);

-- Preview resumen
SELECT
    COUNT(*) AS personas_a_provisionar,
    SUM(Es_Liderazgo) AS con_rol_liderazgo_mantienen_principal,
    SUM(CASE WHEN Es_Liderazgo = 0 THEN 1 ELSE 0 END) AS pasaran_rol_principal_discipulo,
    SUM(Tiene_Usuario) AS ya_tienen_usuario_no_se_toca_login,
    SUM(CASE WHEN Tiene_Usuario = 0 THEN 1 ELSE 0 END) AS reciben_usuario_cedula
FROM tmp_cap_destino_personas;

-- Inscripciones sin persona/cédula (revisar manualmente)
SELECT i.Id_Inscripcion, i.Programa, i.Cedula, i.Nombre, i.Telefono, i.Id_Persona
FROM escuela_formacion_inscripcion i
WHERE LOWER(TRIM(i.Programa)) IN (
    'capacitacion_destino',
    'capacitacion_destino_nivel_1',
    'capacitacion_destino_nivel_2',
    'capacitacion_destino_nivel_3'
)
AND NOT EXISTS (
    SELECT 1 FROM tmp_cap_destino_personas t WHERE t.Id_Persona = COALESCE(NULLIF(i.Id_Persona, 0), -1)
)
AND COALESCE(NULLIF(i.Id_Persona, 0), 0) = 0
AND NULLIF(REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(i.Cedula, ''))), ' ', ''), '.', ''), '-', ''), '') IS NULL;

-- -----------------------------------------------------------------------------
-- 2) Id rol Discípulo (misma búsqueda que UserRole::buscarRolPorAlias)
-- -----------------------------------------------------------------------------

SET @id_rol_discipulo := (
    SELECT r.Id_Rol
    FROM rol r
    WHERE LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(r.Nombre_Rol, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%discipul%'
       OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(r.Nombre_Rol, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%disipul%'
       OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(r.Nombre_Rol, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%discipl%'
       OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(r.Nombre_Rol, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%disipl%'
    ORDER BY r.Id_Rol ASC
    LIMIT 1
);

SELECT @id_rol_discipulo AS id_rol_discipulo;
-- Si sale NULL, detener: no existe rol Discípulo en tabla rol.

-- -----------------------------------------------------------------------------
-- 3) APLICAR CAMBIOS (ejecutar en transacción)
-- -----------------------------------------------------------------------------

START TRANSACTION;

-- 3.1 Completar número de documento si está vacío
UPDATE persona p
INNER JOIN tmp_cap_destino_personas t ON t.Id_Persona = p.Id_Persona
SET p.Numero_Documento = t.Cedula
WHERE NULLIF(TRIM(COALESCE(p.Numero_Documento, '')), '') IS NULL;

-- 3.2 Rol principal: solo no-liderazgo → discípulo
UPDATE persona p
INNER JOIN tmp_cap_destino_personas t ON t.Id_Persona = p.Id_Persona
SET p.Id_Rol = @id_rol_discipulo
WHERE t.Es_Liderazgo = 0
  AND COALESCE(p.Id_Rol, 0) <> @id_rol_discipulo
  AND @id_rol_discipulo IS NOT NULL;

-- 3.3 Estado cuenta activo
UPDATE persona p
INNER JOIN tmp_cap_destino_personas t ON t.Id_Persona = p.Id_Persona
SET p.Estado_Cuenta = 'Activo'
WHERE p.Estado_Cuenta IS NULL
   OR LOWER(TRIM(p.Estado_Cuenta)) <> 'activo';

-- 3.4 Usuario / contraseña (= cédula) solo si aún no tiene usuario
UPDATE persona p
INNER JOIN tmp_cap_destino_personas t ON t.Id_Persona = p.Id_Persona
SET p.Usuario = t.Cedula,
    p.Contrasena = t.Cedula
WHERE t.Tiene_Usuario = 0
  AND NULLIF(TRIM(t.Cedula), '') IS NOT NULL;

-- 3.5 user_roles: rol principal actual (líder conserva su rol; otros ya quedaron discípulo)
INSERT INTO user_roles (Id_Persona, Id_Rol, Activo)
SELECT t.Id_Persona, COALESCE(NULLIF(p.Id_Rol, 0), t.Id_Rol_Actual), 1
FROM tmp_cap_destino_personas t
INNER JOIN persona p ON p.Id_Persona = t.Id_Persona
WHERE COALESCE(NULLIF(p.Id_Rol, 0), t.Id_Rol_Actual) > 0
ON DUPLICATE KEY UPDATE Activo = 1, Actualizado_En = CURRENT_TIMESTAMP;

-- 3.6 user_roles: segundo rol discípulo (todos, incluidos líderes)
INSERT INTO user_roles (Id_Persona, Id_Rol, Activo)
SELECT t.Id_Persona, @id_rol_discipulo, 1
FROM tmp_cap_destino_personas t
WHERE @id_rol_discipulo IS NOT NULL
ON DUPLICATE KEY UPDATE Activo = 1, Actualizado_En = CURRENT_TIMESTAMP;

-- 3.7 Vincular inscripciones que tenían Id_Persona = 0 pero sí hay match por cédula
UPDATE escuela_formacion_inscripcion i
INNER JOIN tmp_cap_destino_personas t
    ON REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(i.Cedula, ''))), ' ', ''), '.', ''), '-', '') = t.Cedula
SET i.Id_Persona = t.Id_Persona
WHERE COALESCE(i.Id_Persona, 0) = 0
  AND LOWER(TRIM(i.Programa)) IN (
      'capacitacion_destino',
      'capacitacion_destino_nivel_1',
      'capacitacion_destino_nivel_2',
      'capacitacion_destino_nivel_3'
  );

COMMIT;
-- Si algo no cuadra en preview: ROLLBACK; (antes de COMMIT)

-- -----------------------------------------------------------------------------
-- 4) POST-CHECK (antes de borrar la tabla temporal)
-- -----------------------------------------------------------------------------

SELECT
    t.Id_Persona,
    t.Cedula,
    t.Es_Liderazgo,
    p.Usuario,
    r.Nombre_Rol AS rol_principal,
    (
        SELECT GROUP_CONCAT(r2.Nombre_Rol ORDER BY ur.Id_Rol SEPARATOR ' | ')
        FROM user_roles ur
        INNER JOIN rol r2 ON r2.Id_Rol = ur.Id_Rol
        WHERE ur.Id_Persona = t.Id_Persona AND ur.Activo = 1
    ) AS roles_en_user_roles
FROM tmp_cap_destino_personas t
INNER JOIN persona p ON p.Id_Persona = t.Id_Persona
LEFT JOIN rol r ON r.Id_Rol = p.Id_Rol
ORDER BY t.Es_Liderazgo DESC, t.Id_Persona
LIMIT 50;

DROP TEMPORARY TABLE IF EXISTS tmp_cap_destino_personas;

-- -----------------------------------------------------------------------------
-- OPCIONAL: forzar usuario/contraseña = cédula también quienes ya tenían usuario
-- (descomentar solo si lo necesitan explícitamente; requiere volver a armar tmp_*)
-- -----------------------------------------------------------------------------
/*
UPDATE persona p
INNER JOIN tmp_cap_destino_personas t ON t.Id_Persona = p.Id_Persona
SET p.Usuario = t.Cedula,
    p.Contrasena = t.Cedula
WHERE NULLIF(TRIM(t.Cedula), '') IS NOT NULL;
*/
