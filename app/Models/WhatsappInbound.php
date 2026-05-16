<?php
/**
 * Bandeja de mensajes entrantes de WhatsApp.
 */

require_once APP . '/Models/BaseModel.php';

class WhatsappInbound extends BaseModel {
    protected $table = 'whatsapp_local_inbound';
    protected $primaryKey = 'id';

    public function __construct() {
        parent::__construct();
        $this->asegurarTabla();
    }

    private function asegurarTabla() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    id BIGINT AUTO_INCREMENT PRIMARY KEY,
                    telefono VARCHAR(20) NOT NULL,
                    nombre_remitente VARCHAR(160) NULL,
                    mensaje TEXT NULL,
                    media_tipo VARCHAR(40) NULL,
                    media_url VARCHAR(500) NULL,
                    proveedor_message_id VARCHAR(120) NULL,
                    leido TINYINT(1) NOT NULL DEFAULT 0,
                    recibido_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_telefono (telefono),
                    INDEX idx_recibido_en (recibido_en),
                    INDEX idx_leido (leido),
                    UNIQUE KEY uq_proveedor_msg (proveedor_message_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->execute($sql);
    }

    public function normalizarTelefono($telefono) {
        $digits = preg_replace('/\D+/', '', (string)$telefono);
        if ($digits === '') {
            return null;
        }

        if (preg_match('/^\d{10}$/', $digits)) {
            return '57' . $digits;
        }

        if (preg_match('/^\d{11,15}$/', $digits)) {
            return $digits;
        }

        return null;
    }

    public function listarConversaciones($buscar = '', $limit = 200) {
        $buscar = trim((string)$buscar);
        $limit = max(1, min(1000, (int)$limit));

        $where = '';
        $params = [];

        if ($buscar !== '') {
            $where = 'WHERE x.telefono LIKE ? OR COALESCE(x.nombre_remitente,\'\') LIKE ? OR COALESCE(x.mensaje,\'\') LIKE ?';
            $like = '%' . $buscar . '%';
            $params = [$like, $like, $like];
        }

        $sql = "SELECT
                    x.telefono,
                    MAX(x.nombre_remitente) AS nombre_remitente,
                    MAX(x.recibido_en) AS ultimo_recibido_en,
                    SUBSTRING_INDEX(GROUP_CONCAT(COALESCE(x.mensaje, '') ORDER BY x.recibido_en DESC SEPARATOR '\\n'), '\\n', 1) AS ultimo_mensaje,
                    COUNT(*) AS total_mensajes,
                    SUM(CASE WHEN x.leido = 0 THEN 1 ELSE 0 END) AS no_leidos
                FROM {$this->table} x
                {$where}
                GROUP BY x.telefono
                ORDER BY ultimo_recibido_en DESC
                LIMIT {$limit}";

        return $this->query($sql, $params);
    }

    public function listarMensajesPorTelefono($telefono, $limit = 200) {
        $telefono = $this->normalizarTelefono($telefono);
        if ($telefono === null) {
            return [];
        }

        $limit = max(1, min(1000, (int)$limit));

        return $this->query(
            "SELECT id, telefono, nombre_remitente, mensaje, media_tipo, media_url, proveedor_message_id, leido, recibido_en
             FROM {$this->table}
             WHERE telefono = ?
             ORDER BY recibido_en DESC, id DESC
             LIMIT {$limit}",
            [$telefono]
        );
    }

    public function marcarLeidosPorTelefono($telefono) {
        $telefono = $this->normalizarTelefono($telefono);
        if ($telefono === null) {
            return false;
        }

        return $this->execute(
            "UPDATE {$this->table}
             SET leido = 1
             WHERE telefono = ? AND leido = 0",
            [$telefono]
        );
    }
}
