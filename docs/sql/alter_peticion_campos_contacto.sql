-- Script para agregar campos de contacto a la tabla peticion
-- Permite captar peticiones públicas sin requerer una persona registrada

ALTER TABLE peticion 
MODIFY COLUMN Id_Persona INT(11) NULL COMMENT 'NULL para peticiones públicas sin persona registrada',
ADD COLUMN nombre_contacto VARCHAR(100) NULL COMMENT 'Nombre del contacto para peticiones públicas',
ADD COLUMN email_contacto VARCHAR(150) NULL COMMENT 'Email de contacto para peticiones públicas',
ADD COLUMN telefono_contacto VARCHAR(20) NULL COMMENT 'Teléfono de contacto para peticiones públicas',
ADD INDEX idx_peticion_contacto (nombre_contacto, email_contacto, telefono_contacto);

-- Verificar que los cambios se aplicaron
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'peticion' 
ORDER BY ORDINAL_POSITION;
