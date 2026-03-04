-- =========================================================
-- MCI Madrid - Agregar campos de mensaje en nehemias
-- Fecha: 2026-03-04
-- Motor: MySQL 5.7+
-- =========================================================

START TRANSACTION;

SET @db_name = DATABASE();

SET @exists_col_mesaje_1enviado = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'nehemias'
    AND COLUMN_NAME = 'mesaje_1enviado'
);
SET @sql_col_mesaje_1enviado = IF(
  @exists_col_mesaje_1enviado = 0,
  'ALTER TABLE nehemias ADD COLUMN mesaje_1enviado TINYINT(1) NULL DEFAULT 0 AFTER Acepta',
  'SELECT 1'
);
PREPARE stmt_col_mesaje_1enviado FROM @sql_col_mesaje_1enviado;
EXECUTE stmt_col_mesaje_1enviado;
DEALLOCATE PREPARE stmt_col_mesaje_1enviado;

SET @exists_col_no_recibir_mas = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'nehemias'
    AND COLUMN_NAME = 'no_recibir_mas'
);
SET @sql_col_no_recibir_mas = IF(
  @exists_col_no_recibir_mas = 0,
  'ALTER TABLE nehemias ADD COLUMN no_recibir_mas TINYINT(1) NULL DEFAULT 0 AFTER mesaje_1enviado',
  'SELECT 1'
);
PREPARE stmt_col_no_recibir_mas FROM @sql_col_no_recibir_mas;
EXECUTE stmt_col_no_recibir_mas;
DEALLOCATE PREPARE stmt_col_no_recibir_mas;

SET @exists_col_mesaje1_fehca = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'nehemias'
    AND COLUMN_NAME = 'mesaje1_fehca'
);
SET @sql_col_mesaje1_fehca = IF(
  @exists_col_mesaje1_fehca = 0,
  'ALTER TABLE nehemias ADD COLUMN mesaje1_fehca BIGINT NULL AFTER no_recibir_mas',
  'SELECT 1'
);
PREPARE stmt_col_mesaje1_fehca FROM @sql_col_mesaje1_fehca;
EXECUTE stmt_col_mesaje1_fehca;
DEALLOCATE PREPARE stmt_col_mesaje1_fehca;

COMMIT;
