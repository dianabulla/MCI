-- Tratamiento de datos (personas registradas) y acuerdo de confidencialidad (cuentas con acceso a datos sensibles).
-- Ejecutar en la base de datos mcimadrid.

ALTER TABLE persona
    ADD COLUMN Tratamiento_Datos ENUM('Acepta', 'No acepta') NULL DEFAULT NULL
        COMMENT 'Consentimiento RGPD / tratamiento de datos personales'
        AFTER Peticion;

ALTER TABLE persona
    ADD COLUMN Acuerdo_Confidencialidad_At DATETIME NULL DEFAULT NULL
        COMMENT 'Fecha en que el líder/admin aceptó el acuerdo de confidencialidad'
        AFTER Tratamiento_Datos;

ALTER TABLE persona
    ADD COLUMN Acuerdo_Confidencialidad_Version VARCHAR(32) NULL DEFAULT NULL
        AFTER Acuerdo_Confidencialidad_At;

-- Cuentas administrativas sin fila en persona (opcional si existe la tabla).
ALTER TABLE usuario_acceso
    ADD COLUMN Acuerdo_Confidencialidad_At DATETIME NULL DEFAULT NULL
        AFTER Ultimo_Acceso;

ALTER TABLE usuario_acceso
    ADD COLUMN Acuerdo_Confidencialidad_Version VARCHAR(32) NULL DEFAULT NULL
        AFTER Acuerdo_Confidencialidad_At;
