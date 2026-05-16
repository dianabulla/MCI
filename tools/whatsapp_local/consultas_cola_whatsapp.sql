-- Consultas para revisar whatsapp_local_queue (ejecutar en phpMyAdmin / MySQL contra la BD de produccion que usa el worker).

-- Resumen por estado
SELECT estado, COUNT(*) AS total
FROM whatsapp_local_queue
GROUP BY estado
ORDER BY FIELD(estado, 'pendiente', 'procesando', 'enviado', 'fallido');

-- Ultimos pendientes (los que el worker deberia procesar cuando toque)
SELECT id, telefono, tipo_evento, estado, intentos,
       programado_en, creado_en, LEFT(COALESCE(ultimo_error,''), 120) AS error_corto
FROM whatsapp_local_queue
WHERE estado = 'pendiente'
ORDER BY id ASC
LIMIT 50;

-- Ultimos fallidos (revisar ultimo_error)
SELECT id, telefono, tipo_evento, intentos, programado_en, creado_en, procesado_en, ultimo_error
FROM whatsapp_local_queue
WHERE estado = 'fallido'
ORDER BY id DESC
LIMIT 30;

-- Programados a futuro (aun no debe enviar hasta programado_en)
SELECT id, telefono, tipo_evento, programado_en, creado_en
FROM whatsapp_local_queue
WHERE estado = 'pendiente'
  AND programado_en IS NOT NULL
  AND programado_en > NOW()
ORDER BY programado_en ASC
LIMIT 30;
