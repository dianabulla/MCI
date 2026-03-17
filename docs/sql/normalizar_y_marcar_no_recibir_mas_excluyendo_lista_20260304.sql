-- =========================================================
-- MCI Madrid - Normalizar teléfonos y marcar "No recibir más"
-- Fecha: 2026-03-04
-- Uso: Ejecutar en phpMyAdmin (BD de PRODUCCIÓN)
-- Regla: EXCLUIR la lista de teléfonos protegidos (no se tocan)
-- =========================================================

START TRANSACTION;

-- 0) Tabla temporal con teléfonos a EXCLUIR (últimos 10 dígitos)
DROP TEMPORARY TABLE IF EXISTS tmp_excluir_no_recibir;
CREATE TEMPORARY TABLE tmp_excluir_no_recibir (
  telefono10 VARCHAR(10) NOT NULL,
  PRIMARY KEY (telefono10)
) ENGINE=MEMORY;

INSERT INTO tmp_excluir_no_recibir (telefono10) VALUES
('3156061608'),('3113226487'),('3002092108'),('3103186528'),('3003949001'),
('3205550954'),('3006022379'),('3203579146'),('3214249184'),('3152523459'),
('3124860470'),('3148517408'),('3144587995'),('3117105477'),('3144066433'),
('3102509619'),('3024677465'),('3227960341'),('3203094073'),('3104814202'),
('3222970546'),('3227883527'),('3186934780'),('3019224259'),('3102935612'),
('3196223669'),('3229569723'),('3124117742'),('3235993901'),('3204502618'),
('3115440080'),('3123883822'),('3216966270'),('3145517087'),('1018445065'),
('3228439813'),('3183177742');

-- 1) Normalizar Telefono_Normalizado para los OTROS números válidos
--    Formato objetivo: XXXXXXXXXX (10 dígitos colombia, sin +57)
--    Se excluyen los teléfonos de tmp_excluir_no_recibir

UPDATE nehemias n
SET n.Telefono_Normalizado = RIGHT(
      REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
        COALESCE(n.Telefono, ''),
        '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '/', ''), '\\', ''),
      10
    )
WHERE
    LENGTH(
      REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
        COALESCE(n.Telefono, ''),
        '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '/', ''), '\\', '')
    ) >= 10
    AND RIGHT(
      REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
        COALESCE(n.Telefono, ''),
        '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '/', ''), '\\', ''),
      10
    ) COLLATE utf8mb4_unicode_ci NOT IN (
      SELECT telefono10 COLLATE utf8mb4_unicode_ci FROM tmp_excluir_no_recibir
    )
    AND (
      n.Telefono_Normalizado IS NULL
      OR TRIM(n.Telefono_Normalizado) = ''
      OR n.Telefono_Normalizado <> RIGHT(
            REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
              COALESCE(n.Telefono, ''),
              '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '/', ''), '\\', ''),
            10
         )
    );

-- 2) Marcar no_recibir_mas = 1 para los OTROS números válidos (si existe columna)
SET @exists_no_recibir_mas = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'nehemias'
    AND COLUMN_NAME = 'no_recibir_mas'
);

SET @sql_no_recibir = IF(
  @exists_no_recibir_mas = 1,
  "UPDATE nehemias n
   SET n.no_recibir_mas = 1
   WHERE
     NOT (
       RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(n.Telefono_Normalizado, ''), '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '/', ''), '\\\\', ''), 10) COLLATE utf8mb4_unicode_ci IN (
         SELECT telefono10 COLLATE utf8mb4_unicode_ci FROM tmp_excluir_no_recibir
       )
       OR RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(n.Telefono, ''), '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '/', ''), '\\\\', ''), 10) COLLATE utf8mb4_unicode_ci IN (
         SELECT telefono10 COLLATE utf8mb4_unicode_ci FROM tmp_excluir_no_recibir
       )
     )
     AND (
       LENGTH(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(n.Telefono_Normalizado, ''), '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '/', ''), '\\\\', '')) = 10
       OR LENGTH(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(n.Telefono, ''), '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '/', ''), '\\\\', '')) >= 10
     )",
  'SELECT 1'
);
PREPARE stmt_no_recibir FROM @sql_no_recibir;
EXECUTE stmt_no_recibir;
DEALLOCATE PREPARE stmt_no_recibir;

COMMIT;

-- 3) Verificación rápida
SELECT COUNT(*) AS total_excluidos_en_tabla_temp FROM tmp_excluir_no_recibir;

SELECT COUNT(*) AS total_nehemias_no_recibir_mas_si_excluyendo_lista
FROM nehemias n
WHERE COALESCE(n.no_recibir_mas, 0) = 1
  AND RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(n.Telefono_Normalizado, ''), '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '/', ''), '\\', ''), 10) COLLATE utf8mb4_unicode_ci
      NOT IN (SELECT telefono10 COLLATE utf8mb4_unicode_ci FROM tmp_excluir_no_recibir);

SELECT COUNT(*) AS total_excluidos_que_quedaron_no
FROM nehemias n
WHERE COALESCE(n.no_recibir_mas, 0) = 0
  AND (
    RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(n.Telefono_Normalizado, ''), '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '/', ''), '\\', ''), 10) COLLATE utf8mb4_unicode_ci
      IN (SELECT telefono10 COLLATE utf8mb4_unicode_ci FROM tmp_excluir_no_recibir)
    OR RIGHT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(n.Telefono, ''), '+', ''), ' ', ''), '-', ''), '(', ''), ')', ''), '.', ''), '/', ''), '\\', ''), 10) COLLATE utf8mb4_unicode_ci
      IN (SELECT telefono10 COLLATE utf8mb4_unicode_ci FROM tmp_excluir_no_recibir)
  );
