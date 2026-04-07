<?php
/**
 * Cola local de WhatsApp (sin proveedor Meta).
 */

require_once APP . '/Models/BaseModel.php';

class WhatsappLocalQueue extends BaseModel {
    protected $table = 'whatsapp_local_queue';
    protected $primaryKey = 'id';

    public function __construct() {
        parent::__construct();
        $this->asegurarTabla();
    }

    private function asegurarTabla() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    telefono VARCHAR(20) NOT NULL,
                    mensaje TEXT NOT NULL,
                    media_url VARCHAR(500) NULL,
                    media_tipo VARCHAR(20) NULL,
                    tipo_evento VARCHAR(80) NOT NULL,
                    referencia VARCHAR(150) NULL,
                    estado ENUM('pendiente','procesando','enviado','fallido') NOT NULL DEFAULT 'pendiente',
                    intentos INT NOT NULL DEFAULT 0,
                    programado_en DATETIME NULL,
                    ultimo_error TEXT NULL,
                    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    procesado_en DATETIME NULL,
                    INDEX idx_estado (estado),
                    INDEX idx_evento (tipo_evento),
                    INDEX idx_creado (creado_en),
                    UNIQUE KEY uq_evento_ref_tel (tipo_evento, referencia, telefono)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->execute($sql);
        $this->asegurarColumna('media_url', "ALTER TABLE {$this->table} ADD COLUMN media_url VARCHAR(500) NULL AFTER mensaje");
        $this->asegurarColumna('media_tipo', "ALTER TABLE {$this->table} ADD COLUMN media_tipo VARCHAR(20) NULL AFTER media_url");
        $this->asegurarColumna('programado_en', "ALTER TABLE {$this->table} ADD COLUMN programado_en DATETIME NULL AFTER intentos");
    }

    private function asegurarColumna($columna, $sqlAlter) {
        $rows = $this->query(
            "SELECT COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?",
            [$this->table, $columna]
        );

        if (empty($rows)) {
            $this->execute($sqlAlter);
        }
    }

    public function normalizarTelefono($telefono) {
        $telefono = preg_replace('/\D+/', '', (string)$telefono);
        if ($telefono === '') {
            return null;
        }

        // 10 dígitos COL: agrega prefijo país.
        if (preg_match('/^\d{10}$/', $telefono)) {
            return '57' . $telefono;
        }

        // Formato internacional ya normalizado.
        if (preg_match('/^\d{11,15}$/', $telefono)) {
            return $telefono;
        }

        return null;
    }

    public function encolar($telefono, $mensaje, $tipoEvento, $referencia = null, $mediaUrl = null, $mediaTipo = null, $programadoEn = null) {
        $telefono = $this->normalizarTelefono($telefono);
        $mensaje = trim((string)$mensaje);
        $tipoEvento = trim((string)$tipoEvento);
        $referencia = $referencia !== null ? trim((string)$referencia) : null;
        $mediaUrl = $mediaUrl !== null ? trim((string)$mediaUrl) : null;
        $mediaTipo = $mediaTipo !== null ? trim((string)$mediaTipo) : null;
        if ($programadoEn instanceof DateTime) {
            $programadoEn = $programadoEn->format('Y-m-d H:i:s');
        } else {
            $programadoEn = $programadoEn !== null ? trim((string)$programadoEn) : null;
        }

        if ($mediaTipo !== null && !in_array($mediaTipo, ['image', 'video'], true)) {
            $mediaTipo = null;
        }
        if ($mediaUrl === '') {
            $mediaUrl = null;
            $mediaTipo = null;
        }

        if ($telefono === null || $mensaje === '' || $tipoEvento === '') {
            return false;
        }

        $sql = "INSERT INTO {$this->table} (telefono, mensaje, media_url, media_tipo, tipo_evento, referencia, estado, intentos, programado_en)
                VALUES (?, ?, ?, ?, ?, ?, 'pendiente', 0, ?)
                ON DUPLICATE KEY UPDATE
                    mensaje = VALUES(mensaje),
                    media_url = VALUES(media_url),
                    media_tipo = VALUES(media_tipo),
                    programado_en = VALUES(programado_en),
                    estado = 'pendiente',
                    ultimo_error = NULL";

        return $this->execute($sql, [$telefono, $mensaje, $mediaUrl, $mediaTipo, $tipoEvento, $referencia, $programadoEn]);
    }
}
