-- Módulo Talleres: formularios dinámicos y respuestas
-- Ejecutar en phpMyAdmin o consola MySQL

CREATE TABLE IF NOT EXISTS talleres_formulario (
    Id_Formulario INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Titulo VARCHAR(255) NOT NULL,
    Slug VARCHAR(120) NOT NULL,
    Descripcion TEXT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    Mensaje_Gracias TEXT NULL,
    Fecha_Creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Fecha_Actualizacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    Creado_Por INT UNSIGNED NULL,
    PRIMARY KEY (Id_Formulario),
    UNIQUE KEY uk_talleres_formulario_slug (Slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS talleres_formulario_campo (
    Id_Campo INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Id_Formulario INT UNSIGNED NOT NULL,
    Nombre_Campo VARCHAR(100) NOT NULL,
    Etiqueta VARCHAR(255) NOT NULL,
    Tipo VARCHAR(40) NOT NULL DEFAULT 'text',
    Requerido TINYINT(1) NOT NULL DEFAULT 0,
    Orden INT NOT NULL DEFAULT 0,
    Opciones TEXT NULL COMMENT 'JSON array para select/radio/checkbox',
    Placeholder VARCHAR(255) NULL,
    Ayuda VARCHAR(500) NULL,
    PRIMARY KEY (Id_Campo),
    KEY idx_talleres_campo_formulario (Id_Formulario),
    CONSTRAINT fk_talleres_campo_formulario
        FOREIGN KEY (Id_Formulario) REFERENCES talleres_formulario (Id_Formulario)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS talleres_formulario_respuesta (
    Id_Respuesta INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Id_Formulario INT UNSIGNED NOT NULL,
    Datos_JSON LONGTEXT NOT NULL,
    Ip_Origen VARCHAR(45) NULL,
    Fecha_Registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (Id_Respuesta),
    KEY idx_talleres_respuesta_formulario (Id_Formulario),
    KEY idx_talleres_respuesta_fecha (Fecha_Registro),
    CONSTRAINT fk_talleres_respuesta_formulario
        FOREIGN KEY (Id_Formulario) REFERENCES talleres_formulario (Id_Formulario)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
