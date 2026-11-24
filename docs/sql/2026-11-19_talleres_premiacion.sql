-- Premiación de talleres. No enlaza con persona ni celula.
-- La pantalla también crea esta tabla al abrirse.

CREATE TABLE IF NOT EXISTS talleres_premiacion_elegido (
    Id_Elegido INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Anio SMALLINT UNSIGNED NOT NULL,
    Clave_Premio VARCHAR(64) NOT NULL,
    Nombre VARCHAR(1000) NOT NULL DEFAULT '',
    Id_Persona INT NULL,
    Id_Celula INT NULL,
    Detalle VARCHAR(255) NULL,
    Actualizado_Por INT NULL,
    Fecha_Actualizacion DATETIME NULL,
    PRIMARY KEY (Id_Elegido),
    UNIQUE KEY uq_premiacion_anio_premio (Anio, Clave_Premio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS talleres_premiacion_voto (
    Id_Voto INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Anio SMALLINT UNSIGNED NOT NULL,
    Telefono VARCHAR(20) NOT NULL,
    Nombre_Votante VARCHAR(120) NOT NULL,
    Votos_JSON LONGTEXT NOT NULL,
    Ip_Origen VARCHAR(45) NULL,
    Fecha_Creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (Id_Voto),
    UNIQUE KEY uq_premiacion_voto_tel (Anio, Telefono)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
