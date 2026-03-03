-- Soporte multimedia para módulo de eventos
ALTER TABLE evento
    ADD COLUMN IF NOT EXISTS Imagen_Evento VARCHAR(255) NULL AFTER Lugar_Evento,
    ADD COLUMN IF NOT EXISTS Video_Evento VARCHAR(255) NULL AFTER Imagen_Evento;
