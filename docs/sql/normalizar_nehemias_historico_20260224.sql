-- =========================================================
-- MCI Madrid - Normalización histórica Nehemías (local)
-- Fecha: 2026-02-24
-- Motor: MySQL 5.7+
-- =========================================================

START TRANSACTION;

-- 0) Verificación rápida previa
SELECT
  COUNT(*) AS total,
  SUM(CASE WHEN Acepta = 1 THEN 1 ELSE 0 END) AS acepta_si,
  SUM(CASE WHEN Telefono_Normalizado IS NOT NULL AND Telefono_Normalizado <> '' THEN 1 ELSE 0 END) AS con_tel_norm,
  SUM(CASE WHEN Consentimiento_Whatsapp = 1 THEN 1 ELSE 0 END) AS con_consent_whatsapp
FROM nehemias;

-- 1) Normalizar Telefono_Normalizado desde Telefono
--    Reglas:
--    - Si queda exactamente 10 dígitos -> +57XXXXXXXXXX
--    - Si queda 57 + 10 dígitos    -> +57XXXXXXXXXX
--    - Cualquier otro formato      -> NULL
UPDATE nehemias
SET Telefono_Normalizado = (
  CASE
    WHEN Telefono IS NULL OR TRIM(Telefono) = '' THEN NULL

    WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(Telefono), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '+', '') REGEXP '^[0-9]{10}$'
      THEN CONCAT(
        '+57',
        REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(Telefono), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '+', '')
      )

    WHEN REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(Telefono), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '+', '') REGEXP '^57[0-9]{10}$'
      THEN CONCAT(
        '+57',
        RIGHT(
          REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(Telefono), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '+', ''),
          10
        )
      )

    ELSE NULL
  END
);

-- 2) Normalizar Acepta (soporta valores históricos tipo texto)
--    Se consideran afirmativos: 1, si, sí, s, yes, true, x
--    Todo lo demás queda en 0
UPDATE nehemias
SET Acepta = CASE
  WHEN Acepta IS NULL THEN 0
  WHEN TRIM(CAST(Acepta AS CHAR)) = '' THEN 0
  WHEN LOWER(TRIM(CAST(Acepta AS CHAR))) IN ('1', 'si', 'sí', 's', 'yes', 'true', 'x') THEN 1
  ELSE 0
END;

-- 3) Normalizar consentimiento WhatsApp desde Acepta ya normalizado
UPDATE nehemias
SET Consentimiento_Whatsapp = CASE WHEN Acepta = 1 THEN 1 ELSE 0 END;

COMMIT;

-- 4) Verificación posterior
SELECT
  COUNT(*) AS total,
  SUM(CASE WHEN Acepta = 1 THEN 1 ELSE 0 END) AS acepta_si,
  SUM(CASE WHEN Telefono_Normalizado IS NOT NULL AND Telefono_Normalizado <> '' THEN 1 ELSE 0 END) AS con_tel_norm,
  SUM(CASE WHEN Consentimiento_Whatsapp = 1 THEN 1 ELSE 0 END) AS con_consent_whatsapp,
  SUM(CASE WHEN Acepta = 1 AND Telefono_Normalizado IS NOT NULL AND Telefono_Normalizado <> '' THEN 1 ELSE 0 END) AS base_elegible
FROM nehemias;
