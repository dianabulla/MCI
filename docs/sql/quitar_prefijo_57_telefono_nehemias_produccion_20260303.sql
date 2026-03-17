-- Producción: quitar prefijo +57 del campo Telefono en nehemias
-- IMPORTANTE: NO toca Telefono_Normalizado
-- Fecha: 2026-03-03

-- 1) Ver cuántos registros se van a afectar
SELECT COUNT(*) AS total_con_prefijo
FROM nehemias
WHERE TRIM(COALESCE(Telefono, '')) LIKE '+57%';

-- 2) (Opcional recomendado) respaldo rápido de los registros afectados
CREATE TABLE IF NOT EXISTS nehemias_backup_telefono_20260303 AS
SELECT *
FROM nehemias
WHERE 1 = 0;

INSERT INTO nehemias_backup_telefono_20260303
SELECT *
FROM nehemias
WHERE TRIM(COALESCE(Telefono, '')) LIKE '+57%';

-- 3) Aplicar cambio SOLO sobre Telefono
UPDATE nehemias
SET Telefono = CASE
    WHEN TRIM(Telefono) LIKE '+57 %' THEN TRIM(SUBSTRING(TRIM(Telefono), 5))
    WHEN TRIM(Telefono) LIKE '+57%' THEN TRIM(SUBSTRING(TRIM(Telefono), 4))
    ELSE Telefono
END
WHERE TRIM(COALESCE(Telefono, '')) LIKE '+57%';

-- 4) Validar resultado
SELECT COUNT(*) AS total_con_prefijo_despues
FROM nehemias
WHERE TRIM(COALESCE(Telefono, '')) LIKE '+57%';

-- 5) Muestra rápida
SELECT Id_Nehemias, Telefono, Telefono_Normalizado
FROM nehemias
ORDER BY Id_Nehemias DESC
LIMIT 25;
