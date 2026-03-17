-- Actualiza el identificador de número (phone_number_id) en la configuración activa de WhatsApp campañas
-- Valor solicitado: 1026869523838926

UPDATE whatsapp_config
SET phone_number_id = '1026869523838926'
WHERE activo = 1;
