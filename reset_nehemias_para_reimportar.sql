-- RESET SEGURO DE TABLA NEHEMIAS (para reimportar desde cero)
-- Fecha sugerida: 2026-02-18
--
-- Qué hace este script:
-- 1) Crea backup completo de la tabla actual.
-- 2) Vacía la tabla nehemias.
-- 3) Reinicia AUTO_INCREMENT.
--
-- Importante:
-- - Este script afecta SOLO la tabla nehemias.
-- - Ejecutar en phpMyAdmin en la base de datos correcta.

-- 1) Verificar cantidad actual
SELECT COUNT(*) AS total_antes FROM nehemias;

-- 2) Crear backup (si ya existe con ese nombre, no lo sobrescribe)
CREATE TABLE IF NOT EXISTS nehemias_backup_pre_reimport_20260218 AS
SELECT * FROM nehemias;

-- 3) Verificar backup
SELECT COUNT(*) AS total_backup FROM nehemias_backup_pre_reimport_20260218;

-- 4) Vaciar tabla original
TRUNCATE TABLE nehemias;

-- 5) Reiniciar autoincrement (TRUNCATE normalmente ya lo hace)
ALTER TABLE nehemias AUTO_INCREMENT = 1;

-- 6) Verificar que quedó vacía
SELECT COUNT(*) AS total_despues FROM nehemias;
