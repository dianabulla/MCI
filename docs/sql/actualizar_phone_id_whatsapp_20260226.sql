-- Actualiza el identificador de número (phone_number_id) en la configuración activa de WhatsApp campañas
-- Valor solicitado: 1061529400372298

UPDATE whatsapp_config
SET phone_number_id = '1061529400372298'
WHERE activo = 1;
