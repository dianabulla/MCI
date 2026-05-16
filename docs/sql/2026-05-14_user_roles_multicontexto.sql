-- Migracion: roles multiples por usuario (user_roles)
-- Fecha: 2026-05-14

CREATE TABLE IF NOT EXISTS user_roles (
    Id_User_Role INT AUTO_INCREMENT PRIMARY KEY,
    Id_Persona INT NOT NULL,
    Id_Rol INT NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    Creado_En DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Actualizado_En DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_roles_persona_rol (Id_Persona, Id_Rol),
    KEY idx_user_roles_persona (Id_Persona),
    KEY idx_user_roles_rol (Id_Rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Semilla inicial: copiar rol principal actual de persona a user_roles.
INSERT INTO user_roles (Id_Persona, Id_Rol, Activo)
SELECT p.Id_Persona, p.Id_Rol, 1
FROM persona p
WHERE p.Id_Persona IS NOT NULL
  AND p.Id_Rol IS NOT NULL
  AND p.Id_Rol > 0
ON DUPLICATE KEY UPDATE Activo = VALUES(Activo), Actualizado_En = CURRENT_TIMESTAMP;

DROP TRIGGER IF EXISTS trg_user_roles_cap_destino;
DELIMITER $$
CREATE TRIGGER trg_user_roles_cap_destino
AFTER INSERT ON escuela_formacion_inscripcion
FOR EACH ROW
BEGIN
    DECLARE v_id_rol_discipulo INT DEFAULT 0;
    DECLARE v_id_rol_persona INT DEFAULT 0;

    IF NEW.Id_Persona IS NOT NULL
       AND NEW.Id_Persona > 0
       AND NEW.Programa IN ('capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3') THEN

        SELECT COALESCE(p.Id_Rol, 0)
          INTO v_id_rol_persona
          FROM persona p
         WHERE p.Id_Persona = NEW.Id_Persona
         LIMIT 1;

        IF v_id_rol_persona > 0 THEN
            INSERT INTO user_roles (Id_Persona, Id_Rol, Activo)
            VALUES (NEW.Id_Persona, v_id_rol_persona, 1)
            ON DUPLICATE KEY UPDATE Activo = VALUES(Activo), Actualizado_En = CURRENT_TIMESTAMP;
        END IF;

        SELECT r.Id_Rol
          INTO v_id_rol_discipulo
          FROM rol r
         WHERE LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(r.Nombre_Rol, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%discipul%'
            OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(r.Nombre_Rol, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%disipul%'
         ORDER BY r.Id_Rol ASC
         LIMIT 1;

        IF COALESCE(v_id_rol_discipulo, 0) > 0 THEN
            INSERT INTO user_roles (Id_Persona, Id_Rol, Activo)
            VALUES (NEW.Id_Persona, v_id_rol_discipulo, 1)
            ON DUPLICATE KEY UPDATE Activo = VALUES(Activo), Actualizado_En = CURRENT_TIMESTAMP;
        END IF;
    END IF;
END$$
DELIMITER ;

-- Procedimientos opcionales para administracion de segundo rol Maestro.
DROP PROCEDURE IF EXISTS sp_asignar_segundo_rol_maestro;
DELIMITER $$
CREATE PROCEDURE sp_asignar_segundo_rol_maestro(IN p_id_persona INT)
BEGIN
    DECLARE v_id_rol_maestro INT DEFAULT 0;
    DECLARE v_id_rol_persona INT DEFAULT 0;

    IF p_id_persona IS NOT NULL AND p_id_persona > 0 THEN
        SELECT COALESCE(Id_Rol, 0) INTO v_id_rol_persona
          FROM persona
         WHERE Id_Persona = p_id_persona
         LIMIT 1;

        IF v_id_rol_persona > 0 THEN
            INSERT INTO user_roles (Id_Persona, Id_Rol, Activo)
            VALUES (p_id_persona, v_id_rol_persona, 1)
            ON DUPLICATE KEY UPDATE Activo = VALUES(Activo), Actualizado_En = CURRENT_TIMESTAMP;
        END IF;

        SELECT Id_Rol INTO v_id_rol_maestro
          FROM rol
         WHERE LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(Nombre_Rol, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%maestro%'
         ORDER BY Id_Rol ASC
         LIMIT 1;

        IF COALESCE(v_id_rol_maestro, 0) > 0 THEN
            INSERT INTO user_roles (Id_Persona, Id_Rol, Activo)
            VALUES (p_id_persona, v_id_rol_maestro, 1)
            ON DUPLICATE KEY UPDATE Activo = VALUES(Activo), Actualizado_En = CURRENT_TIMESTAMP;
        END IF;
    END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_quitar_segundo_rol_maestro;
DELIMITER $$
CREATE PROCEDURE sp_quitar_segundo_rol_maestro(IN p_id_persona INT)
BEGIN
    DECLARE v_id_rol_maestro INT DEFAULT 0;
    DECLARE v_id_rol_persona INT DEFAULT 0;

    SELECT COALESCE(Id_Rol, 0) INTO v_id_rol_persona
      FROM persona
     WHERE Id_Persona = p_id_persona
     LIMIT 1;

    SELECT Id_Rol INTO v_id_rol_maestro
      FROM rol
     WHERE LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(Nombre_Rol, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n')) LIKE '%maestro%'
     ORDER BY Id_Rol ASC
     LIMIT 1;

    IF COALESCE(v_id_rol_maestro, 0) > 0 AND v_id_rol_persona <> v_id_rol_maestro THEN
        DELETE FROM user_roles
         WHERE Id_Persona = p_id_persona
           AND Id_Rol = v_id_rol_maestro;
    END IF;
END$$
DELIMITER ;
