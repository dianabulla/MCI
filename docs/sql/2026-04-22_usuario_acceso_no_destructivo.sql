-- Migracion no destructiva: cuentas de acceso desacopladas de persona
-- Fecha: 2026-04-22
-- Objetivo:
--   1) Permitir usuarios administrativos sin obligar registro en persona.
--   2) Mantener compatibilidad con autenticacion actual basada en persona.
-- Importante:
--   - NO elimina ni modifica datos existentes de persona.Usuario/Contrasena.
--   - Solo crea estructura adicional.

CREATE TABLE IF NOT EXISTS usuario_acceso (
    Id_Usuario_Acceso INT AUTO_INCREMENT PRIMARY KEY,
    Usuario VARCHAR(120) NOT NULL,
    Contrasena VARCHAR(255) NOT NULL,
    Nombre_Mostrar VARCHAR(160) NULL,
    Id_Rol INT NOT NULL,
    Id_Ministerio INT NULL,
    Id_Persona INT NULL,
    Estado_Cuenta ENUM('Activo', 'Inactivo', 'Bloqueado') NOT NULL DEFAULT 'Activo',
    Ultimo_Acceso DATETIME NULL,
    Creado_En DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Actualizado_En DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuario_acceso_usuario (Usuario),
    KEY idx_usuario_acceso_rol (Id_Rol),
    KEY idx_usuario_acceso_ministerio (Id_Ministerio),
    KEY idx_usuario_acceso_persona (Id_Persona),
    CONSTRAINT fk_usuario_acceso_rol
        FOREIGN KEY (Id_Rol) REFERENCES rol (Id_Rol),
    CONSTRAINT fk_usuario_acceso_ministerio
        FOREIGN KEY (Id_Ministerio) REFERENCES ministerio (Id_Ministerio)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_usuario_acceso_persona
        FOREIGN KEY (Id_Persona) REFERENCES persona (Id_Persona)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Opcional (ejemplo): crear usuario administrativo desacoplado
-- INSERT INTO usuario_acceso (Usuario, Contrasena, Nombre_Mostrar, Id_Rol, Estado_Cuenta)
-- VALUES ('admin.ops', '$2y$10$REEMPLAZAR_HASH_BCRYPT', 'Admin Operaciones', 6, 'Activo');
