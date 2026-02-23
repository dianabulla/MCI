-- Tabla de trabajo para el módulo Nehemias > Seremos 1200
-- Ejecutar en la misma base de datos del sistema

CREATE TABLE IF NOT EXISTS seremos_1200 (
    Id_Seremos1200 INT AUTO_INCREMENT PRIMARY KEY,
    Nombres VARCHAR(150) NOT NULL,
    Apellidos VARCHAR(150) NOT NULL,
    Numero_Cedula VARCHAR(50) DEFAULT NULL,
    Telefono VARCHAR(50) DEFAULT NULL,
    Lider VARCHAR(150) DEFAULT NULL,
    Lider_Nehemias VARCHAR(150) DEFAULT NULL,
    Subido_Link VARCHAR(255) DEFAULT NULL,
    En_Bogota_Subio VARCHAR(255) DEFAULT NULL,
    Puesto_Votacion VARCHAR(255) DEFAULT NULL,
    Mesa_Votacion VARCHAR(100) DEFAULT NULL,
    Decision_Acepta TINYINT(1) DEFAULT NULL,
    Fue_Migrado_Nehemias TINYINT(1) NOT NULL DEFAULT 0,
    Nehemias_Id INT DEFAULT NULL,
    Fecha_Decision DATETIME DEFAULT NULL,
    Fecha_Registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Fecha_Actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cedula (Numero_Cedula),
    INDEX idx_decision (Decision_Acepta),
    INDEX idx_migrado (Fue_Migrado_Nehemias)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
