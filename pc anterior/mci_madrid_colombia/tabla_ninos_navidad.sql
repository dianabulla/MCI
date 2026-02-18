-- =============================================
-- Tabla para Registro de Niños Navidad
-- =============================================

CREATE TABLE IF NOT EXISTS ninos_navidad (
    Id_Registro INT PRIMARY KEY AUTO_INCREMENT,
    Nombre_Apellidos VARCHAR(200) NOT NULL,
    Fecha_Nacimiento DATE NOT NULL,
    Edad INT NOT NULL,
    Nombre_Acudiente VARCHAR(200) NOT NULL,
    Telefono_Acudiente VARCHAR(20) NOT NULL,
    Barrio VARCHAR(100) NOT NULL,
    Id_Ministerio INT NULL,
    Fecha_Registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Id_Ministerio) REFERENCES ministerio(Id_Ministerio) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para mejorar rendimiento
CREATE INDEX idx_fecha_nacimiento ON ninos_navidad(Fecha_Nacimiento);
CREATE INDEX idx_ministerio ON ninos_navidad(Id_Ministerio);
CREATE INDEX idx_fecha_registro ON ninos_navidad(Fecha_Registro);

-- =============================================
-- Vista para consultar registros con ministerio
-- =============================================
CREATE OR REPLACE VIEW vista_ninos_navidad AS
SELECT 
    n.*,
    m.Nombre_Ministerio,
    TIMESTAMPDIFF(YEAR, n.Fecha_Nacimiento, CURDATE()) as Edad_Actual
FROM ninos_navidad n
LEFT JOIN ministerio m ON n.Id_Ministerio = m.Id_Ministerio
ORDER BY n.Fecha_Registro DESC;
