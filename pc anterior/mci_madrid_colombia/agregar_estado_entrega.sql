-- Agregar campo Estado_Entrega a la tabla ninos_navidad
ALTER TABLE ninos_navidad 
ADD COLUMN Estado_Entrega ENUM('Pendiente', 'Entregado') DEFAULT 'Pendiente' AFTER Id_Ministerio,
ADD COLUMN Fecha_Entrega DATETIME NULL AFTER Estado_Entrega;

-- Actualizar la vista para incluir el nuevo campo
CREATE OR REPLACE VIEW vista_ninos_navidad AS
SELECT 
    n.*,
    m.Nombre_Ministerio,
    TIMESTAMPDIFF(YEAR, n.Fecha_Nacimiento, CURDATE()) as Edad_Actual
FROM ninos_navidad n
LEFT JOIN ministerio m ON n.Id_Ministerio = m.Id_Ministerio
ORDER BY n.Nombre_Apellidos ASC;
