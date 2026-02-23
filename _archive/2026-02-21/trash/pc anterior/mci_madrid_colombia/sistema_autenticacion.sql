-- =============================================
-- Sistema de Autenticación y Permisos
-- MCI Madrid Colombia
-- =============================================

USE mci;

-- =============================================
-- 1. Agregar campos de autenticación a PERSONA
-- =============================================

-- Verificar y agregar campo Usuario si no existe
SET @dbname = DATABASE();
SET @tablename = 'persona';
SET @columnname = 'Usuario';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (TABLE_SCHEMA = @dbname)
     AND (TABLE_NAME = @tablename)
     AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE persona ADD COLUMN Usuario VARCHAR(50) UNIQUE NULL AFTER Email'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar campo Contrasena si no existe
SET @columnname = 'Contrasena';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (TABLE_SCHEMA = @dbname)
     AND (TABLE_NAME = @tablename)
     AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE persona ADD COLUMN Contrasena VARCHAR(255) NULL AFTER Usuario'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar campo Estado_Cuenta si no existe
SET @columnname = 'Estado_Cuenta';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (TABLE_SCHEMA = @dbname)
     AND (TABLE_NAME = @tablename)
     AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE persona ADD COLUMN Estado_Cuenta ENUM(\'Activo\', \'Inactivo\', \'Bloqueado\') DEFAULT \'Activo\' AFTER Contrasena'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Verificar y agregar campo Ultimo_Acceso si no existe
SET @columnname = 'Ultimo_Acceso';
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE (TABLE_SCHEMA = @dbname)
     AND (TABLE_NAME = @tablename)
     AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE persona ADD COLUMN Ultimo_Acceso DATETIME NULL AFTER Estado_Cuenta'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =============================================
-- 2. Crear tabla de PERMISOS
-- =============================================

CREATE TABLE IF NOT EXISTS permisos (
    Id_Permiso INT PRIMARY KEY AUTO_INCREMENT,
    Id_Rol INT NOT NULL,
    Modulo VARCHAR(50) NOT NULL,
    Puede_Ver BOOLEAN DEFAULT FALSE,
    Puede_Crear BOOLEAN DEFAULT FALSE,
    Puede_Editar BOOLEAN DEFAULT FALSE,
    Puede_Eliminar BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (Id_Rol) REFERENCES rol(Id_Rol) ON DELETE CASCADE,
    UNIQUE KEY unique_rol_modulo (Id_Rol, Modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 3. Insertar permisos por defecto
-- =============================================

-- Administrador del Sistema (Id_Rol = 6) - Acceso TOTAL
INSERT INTO permisos (Id_Rol, Modulo, Puede_Ver, Puede_Crear, Puede_Editar, Puede_Eliminar) VALUES
(6, 'personas', 1, 1, 1, 1),
(6, 'celulas', 1, 1, 1, 1),
(6, 'ministerios', 1, 1, 1, 1),
(6, 'roles', 1, 1, 1, 1),
(6, 'eventos', 1, 1, 1, 1),
(6, 'peticiones', 1, 1, 1, 1),
(6, 'asistencias', 1, 1, 1, 1),
(6, 'reportes', 1, 1, 1, 1),
(6, 'permisos', 1, 1, 1, 1)
ON DUPLICATE KEY UPDATE 
    Puede_Ver = VALUES(Puede_Ver),
    Puede_Crear = VALUES(Puede_Crear),
    Puede_Editar = VALUES(Puede_Editar),
    Puede_Eliminar = VALUES(Puede_Eliminar);

-- Pastor (Id_Rol = 1) - Acceso amplio sin gestión de permisos
INSERT INTO permisos (Id_Rol, Modulo, Puede_Ver, Puede_Crear, Puede_Editar, Puede_Eliminar) VALUES
(1, 'personas', 1, 1, 1, 1),
(1, 'celulas', 1, 1, 1, 1),
(1, 'ministerios', 1, 1, 1, 1),
(1, 'roles', 1, 0, 0, 0),
(1, 'eventos', 1, 1, 1, 1),
(1, 'peticiones', 1, 1, 1, 1),
(1, 'asistencias', 1, 1, 1, 1),
(1, 'reportes', 1, 0, 0, 0)
ON DUPLICATE KEY UPDATE 
    Puede_Ver = VALUES(Puede_Ver),
    Puede_Crear = VALUES(Puede_Crear),
    Puede_Editar = VALUES(Puede_Editar),
    Puede_Eliminar = VALUES(Puede_Eliminar);

-- Líder (Id_Rol = 2) - Acceso limitado a su célula
INSERT INTO permisos (Id_Rol, Modulo, Puede_Ver, Puede_Crear, Puede_Editar, Puede_Eliminar) VALUES
(2, 'personas', 1, 1, 1, 0),
(2, 'celulas', 1, 0, 0, 0),
(2, 'ministerios', 1, 0, 0, 0),
(2, 'eventos', 1, 0, 0, 0),
(2, 'peticiones', 1, 1, 0, 0),
(2, 'asistencias', 1, 1, 0, 0),
(2, 'reportes', 1, 0, 0, 0)
ON DUPLICATE KEY UPDATE 
    Puede_Ver = VALUES(Puede_Ver),
    Puede_Crear = VALUES(Puede_Crear),
    Puede_Editar = VALUES(Puede_Editar),
    Puede_Eliminar = VALUES(Puede_Eliminar);

-- Miembro (Id_Rol = 3) - Solo lectura básica
INSERT INTO permisos (Id_Rol, Modulo, Puede_Ver, Puede_Crear, Puede_Editar, Puede_Eliminar) VALUES
(3, 'personas', 1, 0, 0, 0),
(3, 'celulas', 1, 0, 0, 0),
(3, 'eventos', 1, 0, 0, 0),
(3, 'peticiones', 1, 1, 0, 0)
ON DUPLICATE KEY UPDATE 
    Puede_Ver = VALUES(Puede_Ver),
    Puede_Crear = VALUES(Puede_Crear),
    Puede_Editar = VALUES(Puede_Editar),
    Puede_Eliminar = VALUES(Puede_Eliminar);

-- =============================================
-- 4. Crear usuarios de ejemplo
-- =============================================

-- Limpiar usuarios duplicados que puedan existir
UPDATE persona SET Usuario = NULL WHERE Usuario = 'admin' AND Id_Persona != 12;
UPDATE persona SET Usuario = NULL WHERE Usuario = 'leonardo' AND Id_Persona != 15;
UPDATE persona SET Usuario = NULL WHERE Usuario = 'diana' AND Id_Persona != 13;

-- Usuario administrador (Id_Persona = 12, contraseña: admin123)
UPDATE persona 
SET Usuario = 'admin',
    Contrasena = '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi',
    Estado_Cuenta = 'Activo'
WHERE Id_Persona = 12;

-- Usuario pastor leonardo (Id_Persona = 15, contraseña: pastor123)
UPDATE persona 
SET Usuario = 'leonardo',
    Contrasena = '$2y$10$uXh77J5AJvATu3DuosgYeuxDY128aAADdELbvtSh9wP.0Jb623nIG',
    Estado_Cuenta = 'Activo'
WHERE Id_Persona = 15;

-- Usuario líder diana (Id_Persona = 13, contraseña: diana123)
UPDATE persona 
SET Usuario = 'diana',
    Contrasena = '$2y$10$YMZixhmWXdkd5tIiGEkvtOOq9zZSznhYHhKLDKtGxPC.GH3Mih04C',
    Estado_Cuenta = 'Activo'
WHERE Id_Persona = 13;

-- =============================================
-- 5. Verificar cambios
-- =============================================

SELECT Id_Persona, Nombre, Apellido, Usuario, Estado_Cuenta, Nombre_Rol
FROM persona p
LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
WHERE Usuario IS NOT NULL;

SELECT * FROM permisos ORDER BY Id_Rol, Modulo;
