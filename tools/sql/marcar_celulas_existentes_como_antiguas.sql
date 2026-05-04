-- ============================================================================
-- Script SQL: Marcar celulas existentes como antiguas
-- Base objetivo: mcimadrid (tabla: celula)
--
-- Que hace:
-- 1) Asegura la columna Es_Antiguo (0=nueva, 1=antigua).
-- 2) Muestra resumen previo.
-- 3) Marca todas las celulas existentes como antiguas.
-- 4) Muestra resumen final.
--
-- Uso sugerido:
-- - Ejecutar completo en phpMyAdmin o cliente MySQL.
-- ============================================================================

-- 0) Seleccionar la base (ajusta si aplica)
USE mcimadrid;

-- 1) Asegurar columna Es_Antiguo de forma compatible
SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'celula'
      AND COLUMN_NAME = 'Es_Antiguo'
);

SET @ddl := IF(
    @col_exists = 0,
    'ALTER TABLE celula ADD COLUMN Es_Antiguo TINYINT(1) NOT NULL DEFAULT 0',
    'SELECT "Columna Es_Antiguo ya existe" AS info'
);

PREPARE stmt_ddl FROM @ddl;
EXECUTE stmt_ddl;
DEALLOCATE PREPARE stmt_ddl;

-- 2) Resumen previo
SELECT
    COUNT(*) AS total_celulas,
    SUM(CASE WHEN Es_Antiguo = 1 THEN 1 ELSE 0 END) AS total_antiguas,
    SUM(CASE WHEN Es_Antiguo = 0 THEN 1 ELSE 0 END) AS total_nuevas,
    SUM(CASE WHEN Es_Antiguo <> 1 OR Es_Antiguo IS NULL THEN 1 ELSE 0 END) AS pendientes_por_marcar
FROM celula;

-- 3) Actualizacion masiva (todas las existentes -> antiguas)
UPDATE celula
SET Es_Antiguo = 1
WHERE Es_Antiguo <> 1 OR Es_Antiguo IS NULL;

-- 4) Resultado de la actualizacion
SELECT ROW_COUNT() AS filas_afectadas;

-- 5) Resumen final
SELECT
    COUNT(*) AS total_celulas,
    SUM(CASE WHEN Es_Antiguo = 1 THEN 1 ELSE 0 END) AS total_antiguas,
    SUM(CASE WHEN Es_Antiguo = 0 THEN 1 ELSE 0 END) AS total_nuevas,
    SUM(CASE WHEN Es_Antiguo <> 1 OR Es_Antiguo IS NULL THEN 1 ELSE 0 END) AS pendientes_por_marcar
FROM celula;
