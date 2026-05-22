-- Consultas para revisar whatsapp_local_queue (ejecutar en phpMyAdmin / MySQL contra la BD de produccion que usa el worker).

-- Resumen por estado
SELECT estado, COUNT(*) AS total
FROM whatsapp_local_queue
GROUP BY estado
ORDER BY FIELD(estado, 'pendiente', 'procesando', 'enviado', 'fallido');

-- Pendientes que el worker enviaria HOY (misma logica que worker.js con WA_ONLY_TODAY=1)
-- Ajusta @fecha_hoy si revisas otro dia; en MySQL con time_zone -05:00 puede usarse CURDATE().
SET @fecha_hoy = CURDATE();

SELECT id, telefono, tipo_evento, estado, intentos,
       programado_en, creado_en, referencia, LEFT(COALESCE(ultimo_error,''), 120) AS error_corto
FROM whatsapp_local_queue
WHERE estado = 'pendiente'
  AND (
    (programado_en IS NULL AND DATE(creado_en) = @fecha_hoy
     AND (tipo_evento <> 'felicitacion_cumpleanos' OR referencia LIKE CONCAT('cumpleanos:', @fecha_hoy, ':%')))
    OR (programado_en IS NOT NULL AND DATE(programado_en) = @fecha_hoy AND programado_en <= NOW())
  )
ORDER BY id ASC
LIMIT 50;

-- Ultimos pendientes (todos, sin filtro de hoy)
-- SELECT id, telefono, tipo_evento, estado, intentos,
--        programado_en, creado_en, LEFT(COALESCE(ultimo_error,''), 120) AS error_corto
-- FROM whatsapp_local_queue
-- WHERE estado = 'pendiente'
-- ORDER BY id ASC
-- LIMIT 50;

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
