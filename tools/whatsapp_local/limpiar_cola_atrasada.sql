-- Limpieza de cola WhatsApp local (ejecutar en phpMyAdmin contra la BD de PRODUCCIÓN del worker).
-- Cancela pendientes/procesando cuyo día de envío ya pasó (no borra historial enviado).

-- 1) Vista previa: cuántos se cancelarían
SELECT COUNT(*) AS total_atrasados
FROM whatsapp_local_queue
WHERE estado IN ('pendiente', 'procesando')
  AND (
        (programado_en IS NULL AND DATE(creado_en) < CURDATE())
        OR (programado_en IS NOT NULL AND DATE(programado_en) < CURDATE())
      );

-- 2) Muestra de atrasados
SELECT id, telefono, tipo_evento, estado, creado_en, programado_en,
       LEFT(mensaje, 100) AS mensaje_corto
FROM whatsapp_local_queue
WHERE estado IN ('pendiente', 'procesando')
  AND (
        (programado_en IS NULL AND DATE(creado_en) < CURDATE())
        OR (programado_en IS NOT NULL AND DATE(programado_en) < CURDATE())
      )
ORDER BY id ASC
LIMIT 30;

-- 3) APLICAR cancelación (descomenta tras revisar el conteo)
/*
UPDATE whatsapp_local_queue
SET estado = 'fallido',
    ultimo_error = CONCAT('Cancelado: cola atrasada (limpieza ', CURDATE(), ')'),
    procesado_en = NOW()
WHERE estado IN ('pendiente', 'procesando')
  AND (
        (programado_en IS NULL AND DATE(creado_en) < CURDATE())
        OR (programado_en IS NOT NULL AND DATE(programado_en) < CURDATE())
      );
*/

-- 4) Pendientes válidos para hoy (lo que el worker debería enviar)
SELECT id, telefono, tipo_evento, creado_en, programado_en
FROM whatsapp_local_queue
WHERE estado = 'pendiente'
  AND (
        (programado_en IS NULL AND DATE(creado_en) = CURDATE())
        OR (programado_en IS NOT NULL AND DATE(programado_en) = CURDATE())
      )
ORDER BY id ASC;
