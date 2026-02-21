-- Corrección de consentimientos en módulo Nehemías
-- Fecha: 2026-02-20
-- Objetivo:
--   Cambiar todos los registros con Acepta = 0 (No)
--   a Acepta = 1 (Sí) en la tabla nehemias.

START TRANSACTION;

-- 1) Conteo previo
SELECT
    SUM(CASE WHEN Acepta = 0 THEN 1 ELSE 0 END) AS total_no,
    SUM(CASE WHEN Acepta = 1 THEN 1 ELSE 0 END) AS total_si,
    COUNT(*) AS total_general
FROM nehemias;

-- 2) Respaldo de los registros que serán modificados
CREATE TABLE IF NOT EXISTS nehemias_backup_acepta_no_20260220 AS
SELECT *
FROM nehemias
WHERE Acepta = 0;

-- 3) Actualización (No -> Sí)
UPDATE nehemias
SET Acepta = 1
WHERE Acepta = 0;

-- 4) Validación posterior
SELECT
    SUM(CASE WHEN Acepta = 0 THEN 1 ELSE 0 END) AS total_no_restante,
    SUM(CASE WHEN Acepta = 1 THEN 1 ELSE 0 END) AS total_si,
    COUNT(*) AS total_general
FROM nehemias;

COMMIT;

-- Reversión (si la necesitas):
-- UPDATE nehemias n
-- JOIN nehemias_backup_acepta_no_20260220 b
--   ON b.Id_Nehemias = n.Id_Nehemias
-- SET n.Acepta = b.Acepta;
