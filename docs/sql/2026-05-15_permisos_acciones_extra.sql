-- Acciones avanzadas por módulo (JSON en columna Acciones_Extra)
-- Ejecutar una vez en la base de datos del proyecto.
-- Formato JSON por fila: {"clave_accion":1,"otra":0}

ALTER TABLE permisos
    ADD COLUMN Acciones_Extra LONGTEXT NULL
    COMMENT 'JSON: mapa clave_accion => 0|1 para permisos mas alla de CRUD'
    AFTER Puede_Eliminar;
