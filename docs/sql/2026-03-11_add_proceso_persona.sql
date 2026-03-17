-- Agrega la columna de proceso ministerial a la tabla persona.
-- Valores esperados: Ganar, Consolidar, Discipular, Enviar.

ALTER TABLE persona
ADD COLUMN Proceso ENUM('Ganar','Consolidar','Discipular','Enviar') NULL AFTER Tipo_Reunion;
