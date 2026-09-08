<?php
require_once APP . '/Models/BaseModel.php';

class AuditoriaEvento extends BaseModel {
    protected $table = 'auditoria_evento';
    protected $primaryKey = 'Id_Auditoria';

    public function asegurarTabla(): bool {
        try {
            $this->db->exec(
                "CREATE TABLE IF NOT EXISTS auditoria_evento (
                    Id_Auditoria INT AUTO_INCREMENT PRIMARY KEY,
                    Fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    Id_Actor INT NULL,
                    Usuario_Actor VARCHAR(120) NULL,
                    Accion VARCHAR(40) NOT NULL,
                    Modulo VARCHAR(60) NOT NULL,
                    Entidad VARCHAR(60) NULL,
                    Id_Registro INT NULL,
                    Exito TINYINT(1) NOT NULL DEFAULT 1,
                    Mensaje VARCHAR(255) NULL,
                    Detalle TEXT NULL,
                    Ip VARCHAR(45) NULL,
                    Url VARCHAR(255) NULL,
                    KEY idx_auditoria_fecha (Fecha),
                    KEY idx_auditoria_modulo (Modulo, Accion),
                    KEY idx_auditoria_actor (Id_Actor)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            return true;
        } catch (Throwable $e) {
            error_log('No se pudo crear auditoria_evento: ' . $e->getMessage());
            return false;
        }
    }

    public function listarRecientes(array $filtros = [], int $limite = 200): array {
        if (!$this->asegurarTabla()) {
            return [];
        }

        $where = ['1=1'];
        $params = [];

        $modulo = trim((string)($filtros['modulo'] ?? ''));
        if ($modulo !== '') {
            $where[] = 'Modulo = ?';
            $params[] = $modulo;
        }

        $accion = trim((string)($filtros['accion'] ?? ''));
        if ($accion !== '') {
            $where[] = 'Accion = ?';
            $params[] = $accion;
        }

        if (isset($filtros['exito']) && $filtros['exito'] !== '' && $filtros['exito'] !== null) {
            $where[] = 'Exito = ?';
            $params[] = ((int)$filtros['exito'] === 1) ? 1 : 0;
        }

        $limite = max(20, min(500, $limite));
        $sql = 'SELECT * FROM auditoria_evento WHERE ' . implode(' AND ', $where)
            . ' ORDER BY Fecha DESC, Id_Auditoria DESC LIMIT ' . $limite;

        return $this->query($sql, $params);
    }
}
