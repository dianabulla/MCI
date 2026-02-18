-- =============================================
-- AGREGAR CAMPO LIDER A LA TABLA PERSONA
-- =============================================

USE mci;

-- Agregar campo Id_Lider a la tabla PERSONA
ALTER TABLE PERSONA 
ADD COLUMN IF NOT EXISTS Id_Lider INT NULL AFTER Tipo_Reunion;

-- Agregar clave foránea para Id_Lider
ALTER TABLE PERSONA
ADD CONSTRAINT fk_persona_lider 
FOREIGN KEY (Id_Lider) REFERENCES PERSONA(Id_Persona) 
ON DELETE SET NULL;

-- =============================================
-- FIN DE LA ACTUALIZACIÓN
-- =============================================
