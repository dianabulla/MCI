-- Talleres: bloques, vínculo con persona y campos tipo tabla
-- Se aplica también vía ensureSchema() en TallerFormulario.php

CREATE TABLE IF NOT EXISTS talleres_formulario_bloque (
    Id_Bloque INT UNSIGNED NOT NULL AUTO_INCREMENT,
    Id_Formulario INT UNSIGNED NOT NULL,
    Titulo VARCHAR(255) NOT NULL,
    Tipo ENUM('persona', 'personalizado') NOT NULL DEFAULT 'personalizado',
    Orden INT NOT NULL DEFAULT 0,
    PRIMARY KEY (Id_Bloque),
    KEY idx_talleres_bloque_formulario (Id_Formulario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Id_Bloque en campos (nullable para compatibilidad)
ALTER TABLE talleres_formulario_campo
    ADD COLUMN Id_Bloque INT UNSIGNED NULL AFTER Id_Formulario;

-- Id_Persona en respuestas
ALTER TABLE talleres_formulario_respuesta
    ADD COLUMN Id_Persona INT UNSIGNED NULL AFTER Id_Formulario,
    ADD KEY idx_talleres_respuesta_persona (Id_Persona);
