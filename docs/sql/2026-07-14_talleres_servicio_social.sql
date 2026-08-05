-- Submódulo Talleres → Servicio Social (agendamiento de citas)
-- Ejecutar en phpMyAdmin o consola MySQL si ensureSchema no ha corrido aún.

CREATE TABLE IF NOT EXISTS talleres_servicio_social_cita (
    Id_Cita INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Nombre VARCHAR(120) NOT NULL,
    Apellido VARCHAR(120) NOT NULL DEFAULT '',
    Documento VARCHAR(40) NULL,
    Telefono VARCHAR(40) NOT NULL,
    Email VARCHAR(160) NULL,
    Fecha_Preferida DATE NOT NULL,
    Hora_Preferida VARCHAR(20) NULL,
    Tipo_Cita VARCHAR(60) NOT NULL,
    Necesidad_Principal TEXT NOT NULL,
    Remitido_Por VARCHAR(60) NOT NULL DEFAULT 'ninguno',
    Remitido_Detalle VARCHAR(255) NULL,
    Observaciones TEXT NULL,
    Estado VARCHAR(30) NOT NULL DEFAULT 'pendiente',
    Notas_Internas TEXT NULL,
    Fecha_Atencion DATETIME NULL,
    Ip_Origen VARCHAR(45) NULL,
    Fecha_Creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Fecha_Actualizacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    Actualizado_Por INT UNSIGNED NULL,
    PRIMARY KEY (Id_Cita),
    KEY idx_ss_estado (Estado),
    KEY idx_ss_fecha_pref (Fecha_Preferida),
    KEY idx_ss_tipo (Tipo_Cita),
    KEY idx_ss_remitido (Remitido_Por),
    KEY idx_ss_creacion (Fecha_Creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
