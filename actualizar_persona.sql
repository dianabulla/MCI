-- =============================================
-- ACTUALIZACIÓN DE LA TABLA PERSONA
-- Agrega nuevos campos sin perder datos existentes
-- =============================================

USE mci;

-- Agregar nuevos campos a la tabla PERSONA
ALTER TABLE PERSONA 
ADD COLUMN IF NOT EXISTS Tipo_Documento ENUM('Registro Civil', 'Cedula de Ciudadania', 'Cedula Extranjera') DEFAULT 'Cedula de Ciudadania' AFTER Apellido,
ADD COLUMN IF NOT EXISTS Numero_Documento VARCHAR(50) AFTER Tipo_Documento,
ADD COLUMN IF NOT EXISTS Edad INT AFTER Fecha_Nacimiento,
ADD COLUMN IF NOT EXISTS Genero ENUM('Hombre', 'Mujer', 'Joven Hombre', 'Joven Mujer') AFTER Edad,
ADD COLUMN IF NOT EXISTS Hora_Llamada ENUM('Mañana', 'Medio Dia', 'Tarde', 'Noche', 'Cualquier Hora') DEFAULT 'Cualquier Hora' AFTER Email,
ADD COLUMN IF NOT EXISTS Barrio VARCHAR(100) AFTER Direccion,
ADD COLUMN IF NOT EXISTS Peticion TEXT AFTER Barrio,
ADD COLUMN IF NOT EXISTS Invitado_Por INT NULL AFTER Peticion,
ADD COLUMN IF NOT EXISTS Tipo_Reunion ENUM('Domingo', 'Celula', 'Reu Jovenes', 'Reu Hombre', 'Reu Mujeres', 'Grupo Go', 'Seminario', 'Pesca', 'Semana Santa', 'Otro') AFTER Invitado_Por;

-- Agregar clave foránea para Invitado_Por
ALTER TABLE PERSONA
ADD CONSTRAINT fk_persona_invitado_por 
FOREIGN KEY (Invitado_Por) REFERENCES PERSONA(Id_Persona) 
ON DELETE SET NULL;

-- Agregar índice para el número de documento
CREATE INDEX idx_persona_documento ON PERSONA(Numero_Documento);

-- =============================================
-- FIN DE LA ACTUALIZACIÓN
-- =============================================
