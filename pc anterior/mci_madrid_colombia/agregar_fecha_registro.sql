-- =============================================
-- Agregar campos de Fecha de Registro a PERSONA
-- =============================================

USE mci;

-- Agregar columna Fecha_Registro (formato normal)
ALTER TABLE persona 
ADD COLUMN Fecha_Registro DATETIME NULL AFTER Id_Ministerio;

-- Agregar columna Fecha_Registro_Unix (timestamp)
ALTER TABLE persona 
ADD COLUMN Fecha_Registro_Unix BIGINT NULL AFTER Fecha_Registro;

-- Actualizar registros existentes con fecha actual
-- Asumiendo que las personas existentes se registraron en diferentes fechas del mes actual
UPDATE persona 
SET Fecha_Registro = DATE_SUB(NOW(), INTERVAL (Id_Persona % 30) DAY),
    Fecha_Registro_Unix = UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL (Id_Persona % 30) DAY))
WHERE Fecha_Registro IS NULL;

-- Verificar los cambios
SELECT Id_Persona, Nombre, Apellido, Fecha_Registro, Fecha_Registro_Unix 
FROM persona 
LIMIT 10;
