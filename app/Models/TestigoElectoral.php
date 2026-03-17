<?php
/**
 * Modelo TestigoElectoral
 */

require_once APP . '/Models/BaseModel.php';

class TestigoElectoral extends BaseModel {
    protected $table = 'testigos_electorales';
    protected $primaryKey = 'Id_TestigoElectoral';

    public function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            Id_TestigoElectoral INT AUTO_INCREMENT PRIMARY KEY,
            Codigo_Envio VARCHAR(64) DEFAULT NULL,
            Testigo_Nombre VARCHAR(180) NOT NULL,
            Tipo_Votacion VARCHAR(20) NOT NULL,
            Puesto_Votacion VARCHAR(255) NOT NULL,
            Mesa_Votacion VARCHAR(100) NOT NULL,
            Observaciones VARCHAR(500) DEFAULT NULL,
            Foto_Evidencia VARCHAR(255) DEFAULT NULL,
            Votos_Contados INT NOT NULL DEFAULT 0,
            Fecha_Registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            Fecha_Actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_codigo_envio (Codigo_Envio),
            INDEX idx_puesto (Puesto_Votacion),
            INDEX idx_fecha_registro (Fecha_Registro)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $this->db->exec($sql);

        $this->ensureCodigoEnvioColumnExists();
        $this->ensureTipoVotacionColumnExists();
        $this->ensureMesaVotacionColumnExists();
        $this->ensureObservacionesColumnExists();
        $this->ensureFotoEvidenciaColumnExists();
    }

    private function ensureCodigoEnvioColumnExists() {
        $sql = "SHOW COLUMNS FROM {$this->table} LIKE 'Codigo_Envio'";
        $stmt = $this->db->query($sql);
        $existe = $stmt ? $stmt->fetch() : false;

        if (!$existe) {
            $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Codigo_Envio VARCHAR(64) DEFAULT NULL AFTER Id_TestigoElectoral");
            $this->db->exec("ALTER TABLE {$this->table} ADD INDEX idx_codigo_envio (Codigo_Envio)");
        }
    }

    private function ensureTipoVotacionColumnExists() {
        $sql = "SHOW COLUMNS FROM {$this->table} LIKE 'Tipo_Votacion'";
        $stmt = $this->db->query($sql);
        $existe = $stmt ? $stmt->fetch() : false;

        if (!$existe) {
            $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Tipo_Votacion VARCHAR(20) NOT NULL DEFAULT 'CAMARA' AFTER Testigo_Nombre");
        }
    }

    private function ensureMesaVotacionColumnExists() {
        $sql = "SHOW COLUMNS FROM {$this->table} LIKE 'Mesa_Votacion'";
        $stmt = $this->db->query($sql);
        $existe = $stmt ? $stmt->fetch() : false;

        if (!$existe) {
            $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Mesa_Votacion VARCHAR(100) NOT NULL DEFAULT '' AFTER Puesto_Votacion");
        }
    }

    private function ensureObservacionesColumnExists() {
        $sql = "SHOW COLUMNS FROM {$this->table} LIKE 'Observaciones'";
        $stmt = $this->db->query($sql);
        $existe = $stmt ? $stmt->fetch() : false;

        if (!$existe) {
            $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Observaciones VARCHAR(500) DEFAULT NULL AFTER Puesto_Votacion");
        }
    }

    private function ensureFotoEvidenciaColumnExists() {
        $sql = "SHOW COLUMNS FROM {$this->table} LIKE 'Foto_Evidencia'";
        $stmt = $this->db->query($sql);
        $existe = $stmt ? $stmt->fetch() : false;

        if (!$existe) {
            $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Foto_Evidencia VARCHAR(255) DEFAULT NULL AFTER Observaciones");
        }
    }

    public function getAllOrdered() {
        $sql = "SELECT * FROM {$this->table} ORDER BY Fecha_Registro DESC, Id_TestigoElectoral DESC";
        return $this->query($sql);
    }
}
