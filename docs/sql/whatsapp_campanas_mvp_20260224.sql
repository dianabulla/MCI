-- =========================================================
-- MCI Madrid - MVP Campañas WhatsApp (Nehemias)
-- Fecha: 2026-02-24
-- Motor: MySQL 5.7+
-- =========================================================

START TRANSACTION;

-- 1) Ajustes en nehemias para normalización y consentimiento WhatsApp
SET @db_name = DATABASE();

SET @exists_col_tel = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'nehemias'
    AND COLUMN_NAME = 'Telefono_Normalizado'
);
SET @sql_col_tel = IF(
  @exists_col_tel = 0,
  'ALTER TABLE nehemias ADD COLUMN Telefono_Normalizado VARCHAR(20) NULL AFTER Telefono',
  'SELECT 1'
);
PREPARE stmt_col_tel FROM @sql_col_tel;
EXECUTE stmt_col_tel;
DEALLOCATE PREPARE stmt_col_tel;

SET @exists_col_consent = (
  SELECT COUNT(*)
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'nehemias'
    AND COLUMN_NAME = 'Consentimiento_Whatsapp'
);
SET @sql_col_consent = IF(
  @exists_col_consent = 0,
  'ALTER TABLE nehemias ADD COLUMN Consentimiento_Whatsapp TINYINT(1) NOT NULL DEFAULT 0 AFTER Acepta',
  'SELECT 1'
);
PREPARE stmt_col_consent FROM @sql_col_consent;
EXECUTE stmt_col_consent;
DEALLOCATE PREPARE stmt_col_consent;

SET @exists_idx_tel = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'nehemias'
    AND INDEX_NAME = 'idx_nehemias_tel_norm'
);
SET @sql_idx_tel = IF(
  @exists_idx_tel = 0,
  'CREATE INDEX idx_nehemias_tel_norm ON nehemias (Telefono_Normalizado)',
  'SELECT 1'
);
PREPARE stmt_idx_tel FROM @sql_idx_tel;
EXECUTE stmt_idx_tel;
DEALLOCATE PREPARE stmt_idx_tel;

SET @exists_idx_lider = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'nehemias'
    AND INDEX_NAME = 'idx_nehemias_lider'
);
SET @sql_idx_lider = IF(
  @exists_idx_lider = 0,
  'CREATE INDEX idx_nehemias_lider ON nehemias (Lider)',
  'SELECT 1'
);
PREPARE stmt_idx_lider FROM @sql_idx_lider;
EXECUTE stmt_idx_lider;
DEALLOCATE PREPARE stmt_idx_lider;

SET @exists_idx_lider_n = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'nehemias'
    AND INDEX_NAME = 'idx_nehemias_lider_nehemias'
);
SET @sql_idx_lider_n = IF(
  @exists_idx_lider_n = 0,
  'CREATE INDEX idx_nehemias_lider_nehemias ON nehemias (Lider_Nehemias)',
  'SELECT 1'
);
PREPARE stmt_idx_lider_n FROM @sql_idx_lider_n;
EXECUTE stmt_idx_lider_n;
DEALLOCATE PREPARE stmt_idx_lider_n;

SET @exists_idx_consent = (
  SELECT COUNT(*)
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = @db_name
    AND TABLE_NAME = 'nehemias'
    AND INDEX_NAME = 'idx_nehemias_consent_whatsapp'
);
SET @sql_idx_consent = IF(
  @exists_idx_consent = 0,
  'CREATE INDEX idx_nehemias_consent_whatsapp ON nehemias (Consentimiento_Whatsapp)',
  'SELECT 1'
);
PREPARE stmt_idx_consent FROM @sql_idx_consent;
EXECUTE stmt_idx_consent;
DEALLOCATE PREPARE stmt_idx_consent;

-- 2) Configuración de proveedor WhatsApp (BSP)
CREATE TABLE IF NOT EXISTS whatsapp_config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  proveedor VARCHAR(50) NOT NULL,
  endpoint_base VARCHAR(255) NOT NULL,
  phone_number_id VARCHAR(100) NULL,
  api_key_encriptada TEXT NOT NULL,
  webhook_verify_token VARCHAR(255) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) Plantillas
CREATE TABLE IF NOT EXISTS whatsapp_plantillas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  cuerpo TEXT NOT NULL,
  tipo ENUM('texto','imagen','video','documento') NOT NULL DEFAULT 'texto',
  media_url VARCHAR(500) NULL,
  activa TINYINT(1) NOT NULL DEFAULT 1,
  creado_por INT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Campañas
CREATE TABLE IF NOT EXISTS whatsapp_campanas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(150) NOT NULL,
  objetivo VARCHAR(255) NULL,
  id_plantilla INT NOT NULL,
  fecha_programada DATETIME NOT NULL,
  estado ENUM('borrador','programada','enviando','pausada','finalizada','cancelada') NOT NULL DEFAULT 'borrador',
  limite_lote INT NOT NULL DEFAULT 100,
  pausa_segundos INT NOT NULL DEFAULT 5,
  creado_por INT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_whatsapp_campanas_plantilla
    FOREIGN KEY (id_plantilla) REFERENCES whatsapp_plantillas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Filtros aplicados a la campaña (auditoría)
CREATE TABLE IF NOT EXISTS whatsapp_campana_filtros (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_campana INT NOT NULL,
  filtro_json JSON NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_whatsapp_filtros_campana
    FOREIGN KEY (id_campana) REFERENCES whatsapp_campanas(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) Cola de envíos
CREATE TABLE IF NOT EXISTS whatsapp_cola_envio (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_campana INT NOT NULL,
  id_nehemias INT NOT NULL,
  telefono VARCHAR(20) NOT NULL,
  payload_json JSON NULL,
  estado ENUM('pendiente','procesando','enviado','entregado','leido','fallido','omitido') NOT NULL DEFAULT 'pendiente',
  intentos INT NOT NULL DEFAULT 0,
  ultimo_error TEXT NULL,
  proveedor_message_id VARCHAR(120) NULL,
  programado_en DATETIME NOT NULL,
  procesado_en DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_campana_contacto (id_campana, id_nehemias),
  KEY idx_cola_estado_programado (estado, programado_en),
  KEY idx_cola_campana (id_campana),
  CONSTRAINT fk_whatsapp_cola_campana
    FOREIGN KEY (id_campana) REFERENCES whatsapp_campanas(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_whatsapp_cola_nehemias
    FOREIGN KEY (id_nehemias) REFERENCES nehemias(Id_Nehemias)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7) Eventos de estado (webhook)
CREATE TABLE IF NOT EXISTS whatsapp_eventos (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  id_cola BIGINT NULL,
  proveedor_message_id VARCHAR(120) NULL,
  evento ENUM('sent','delivered','read','failed','blocked','spam_report') NOT NULL,
  detalle_json JSON NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_evento_message (proveedor_message_id),
  KEY idx_evento_cola (id_cola),
  CONSTRAINT fk_whatsapp_evento_cola
    FOREIGN KEY (id_cola) REFERENCES whatsapp_cola_envio(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8) Opt-out / no contactar
CREATE TABLE IF NOT EXISTS whatsapp_optout (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  telefono VARCHAR(20) NOT NULL,
  motivo VARCHAR(100) NOT NULL,
  origen VARCHAR(50) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_optout_telefono (telefono)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;

-- =========================================================
-- Nota de compatibilidad:
-- Este script usa information_schema + PREPARE para evitar errores por objetos ya existentes.
-- =========================================================
