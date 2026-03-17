-- Producción: quitar espacios y saltos de línea del campo Telefono en nehemias
-- IMPORTANTE: NO toca Telefono_Normalizado
-- Fecha: 2026-03-03

-- 1) Ver cuántos registros tienen espacios/tab/saltos en Telefono
SELECT COUNT(*) AS total_con_espacios
FROM nehemias
WHERE TRIM(COALESCE(Telefono, '')) REGEXP '[[:space:]]';

-- 2) (Opcional recomendado) respaldo rápido solo de afectados
CREATE TABLE IF NOT EXISTS nehemias_backup_telefono_espacios_20260303 AS
SELECT *
FROM nehemias
WHERE 1 = 0;

INSERT INTO nehemias_backup_telefono_espacios_20260303
SELECT *
FROM nehemias
WHERE TRIM(COALESCE(Telefono, '')) REGEXP '[[:space:]]';

-- 3) Aplicar limpieza SOLO sobre Telefono
-- Quita espacios normales, tabulaciones y saltos de línea
UPDATE nehemias
SET Telefono = REPLACE(
                    REPLACE(
                        REPLACE(
                            REPLACE(TRIM(Telefono), ' ', ''),
                        CHAR(9), ''),
                    CHAR(10), ''),
                CHAR(13), '')
WHERE TRIM(COALESCE(Telefono, '')) REGEXP '[[:space:]]';

-- 4) Validar que ya no queden con espacios/tab/saltos
SELECT COUNT(*) AS total_con_espacios_despues
FROM nehemias
WHERE TRIM(COALESCE(Telefono, '')) REGEXP '[[:space:]]';

-- 5) Muestra rápida de validación
SELECT Id_Nehemias, Telefono, Telefono_Normalizado
FROM nehemias
ORDER BY Id_Nehemias DESC
LIMIT 25;
