-- Corrección de ministerio en módulo Nehemías
-- Fecha: 2026-02-20
-- Objetivo:
--   Reemplazar "ALEJANDRO Y MADE" por "ALEJANDRO y MADELINE"
--   en la tabla nehemias (campo Lider).

START TRANSACTION;

-- 1) Ver cuántos registros serán afectados (exacto, ignorando espacios extremos)
SELECT COUNT(*) AS total_a_corregir
FROM nehemias
WHERE TRIM(Lider) = 'ALEJANDRO Y MADE';

-- 2) Respaldo de registros a modificar
CREATE TABLE IF NOT EXISTS nehemias_backup_alejandro_made_20260220 AS
SELECT *
FROM nehemias
WHERE TRIM(Lider) = 'ALEJANDRO Y MADE';

-- 3) Actualización
UPDATE nehemias
SET Lider = 'ALEJANDRO y MADELINE'
WHERE TRIM(Lider) = 'ALEJANDRO Y MADE';

-- 4) Validación posterior
SELECT COUNT(*) AS total_restante_incorrecto
FROM nehemias
WHERE TRIM(Lider) = 'ALEJANDRO Y MADE';

SELECT COUNT(*) AS total_correcto
FROM nehemias
WHERE TRIM(Lider) = 'ALEJANDRO y MADELINE';

COMMIT;

-- Si necesitas revertir:
-- UPDATE nehemias n
-- JOIN nehemias_backup_alejandro_made_20260220 b
--   ON b.Id_Nehemias = n.Id_Nehemias
-- SET n.Lider = b.Lider;
