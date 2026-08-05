-- Servicio Social: tipo documento, EPS, documentos remisión e historia clínica
-- Ejecutar en MySQL/MariaDB si no se usa ensureSchema() automático del modelo.

ALTER TABLE talleres_servicio_social_cita
    ADD COLUMN IF NOT EXISTS Tipo_Documento VARCHAR(60) NULL AFTER Apellido,
    ADD COLUMN IF NOT EXISTS Nombre_Eps VARCHAR(120) NULL AFTER Documento,
    ADD COLUMN IF NOT EXISTS Documentos_Remision JSON NULL AFTER Remitido_Detalle;

CREATE TABLE IF NOT EXISTS talleres_servicio_social_historia_clinica (
    Id_Entrada INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Tipo_Documento VARCHAR(60) NOT NULL,
    Documento VARCHAR(40) NOT NULL,
    Id_Cita INT UNSIGNED NULL,
    Fecha_Atencion DATETIME NOT NULL,
    Motivo_Consulta TEXT NULL,
    Diagnostico TEXT NULL,
    Formula TEXT NULL,
    Recomendaciones TEXT NULL,
    Observaciones TEXT NULL,
    Creado_Por INT UNSIGNED NULL,
    Fecha_Creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (Id_Entrada),
    KEY idx_hc_paciente (Tipo_Documento, Documento),
    KEY idx_hc_cita (Id_Cita),
    KEY idx_hc_fecha (Fecha_Atencion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
