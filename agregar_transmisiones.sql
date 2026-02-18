-- =============================================
-- TABLA: TRANSMISIONES_YOUTUBE
-- Almacena transmisiones en vivo de YouTube
-- =============================================

CREATE TABLE IF NOT EXISTS TRANSMISIONES_YOUTUBE (
    Id_Transmision INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(150) NOT NULL,
    URL_YouTube VARCHAR(255) NOT NULL,
    Fecha_Transmision DATE NOT NULL,
    Hora_Transmision TIME,
    Fecha_Creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Fecha_Actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    Estado ENUM('en_vivo', 'finalizada', 'proximamente') DEFAULT 'proximamente',
    Descripcion TEXT,
    Id_Usuario_Creador INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- CLAVE FORÁNEA (Opcional - usar solo si funciona)
-- =============================================

-- Descomenta la siguiente línea si quieres agregar la clave foránea
-- ALTER TABLE TRANSMISIONES_YOUTUBE
-- ADD CONSTRAINT fk_transmision_usuario 
-- FOREIGN KEY (Id_Usuario_Creador) REFERENCES persona(Id_Persona) 
-- ON DELETE SET NULL;
