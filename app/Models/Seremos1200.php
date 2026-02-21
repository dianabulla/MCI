<?php
/**
 * Modelo Seremos1200
 */

require_once APP . '/Models/BaseModel.php';

class Seremos1200 extends BaseModel {
    protected $table = 'seremos_1200';
    protected $primaryKey = 'Id_Seremos1200';

    public function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            Id_Seremos1200 INT AUTO_INCREMENT PRIMARY KEY,
            Nombres VARCHAR(150) NOT NULL,
            Apellidos VARCHAR(150) NOT NULL,
            Numero_Cedula VARCHAR(50) DEFAULT NULL,
            Telefono VARCHAR(50) DEFAULT NULL,
            Lider VARCHAR(150) DEFAULT NULL,
            Lider_Nehemias VARCHAR(150) DEFAULT NULL,
            Subido_Link VARCHAR(255) DEFAULT NULL,
            En_Bogota_Subio VARCHAR(255) DEFAULT NULL,
            Puesto_Votacion VARCHAR(255) DEFAULT NULL,
            Mesa_Votacion VARCHAR(100) DEFAULT NULL,
            Decision_Acepta TINYINT(1) DEFAULT NULL,
            Fue_Migrado_Nehemias TINYINT(1) NOT NULL DEFAULT 0,
            Nehemias_Id INT DEFAULT NULL,
            Fecha_Decision DATETIME DEFAULT NULL,
            Fecha_Registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            Fecha_Actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_cedula (Numero_Cedula),
            INDEX idx_decision (Decision_Acepta),
            INDEX idx_migrado (Fue_Migrado_Nehemias)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        $this->db->exec($sql);
    }

    public function getAllOrdered() {
        $sql = "SELECT * FROM {$this->table} ORDER BY Fecha_Registro DESC, Id_Seremos1200 DESC";
        return $this->query($sql);
    }

    public function getAllWithFilters($filtros = []) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (Nombres LIKE ? OR Apellidos LIKE ? OR Numero_Cedula LIKE ? OR Telefono LIKE ? OR Lider LIKE ? OR Lider_Nehemias LIKE ?)";
            $termino = '%' . $filtros['busqueda'] . '%';
            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
        }

        if (isset($filtros['decision']) && $filtros['decision'] !== '') {
            if ($filtros['decision'] === 'pendiente') {
                $sql .= " AND Decision_Acepta IS NULL";
            } elseif ($filtros['decision'] === '1' || $filtros['decision'] === '0') {
                $sql .= " AND Decision_Acepta = ?";
                $params[] = (int)$filtros['decision'];
            }
        }

        if (isset($filtros['lider']) && trim((string)$filtros['lider']) !== '') {
            $sql .= " AND Lider = ?";
            $params[] = trim((string)$filtros['lider']);
        }

        if (isset($filtros['migrado']) && $filtros['migrado'] !== '') {
            if ($filtros['migrado'] === '1' || $filtros['migrado'] === '0') {
                $sql .= " AND Fue_Migrado_Nehemias = ?";
                $params[] = (int)$filtros['migrado'];
            }
        }

        $sql .= " ORDER BY Fecha_Registro DESC, Id_Seremos1200 DESC";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        return $this->query($sql);
    }

    public function getLideresDistinct() {
        $sql = "SELECT DISTINCT Lider
                FROM {$this->table}
                WHERE Lider IS NOT NULL AND TRIM(Lider) <> ''
                ORDER BY Lider ASC";

        return $this->query($sql);
    }

    public function existeCedula($cedulaNormalizada) {
        $cedulaNormalizada = trim((string)$cedulaNormalizada);
        if ($cedulaNormalizada === '') {
            return false;
        }

        $sql = "SELECT 1
                FROM {$this->table}
                WHERE REPLACE(REPLACE(REPLACE(REPLACE(LOWER(TRIM(Numero_Cedula)), '.', ''), '-', ''), ' ', ''), '_', '') = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([strtolower($cedulaNormalizada)]);
        return (bool)$stmt->fetchColumn();
    }

    public function marcarDecision($id, $acepta, $migrado = null, $nehemiasId = null) {
        $sql = "UPDATE {$this->table}
                SET Decision_Acepta = ?,
                    Fue_Migrado_Nehemias = ?,
                    Nehemias_Id = ?,
                    Fecha_Decision = NOW()
                WHERE {$this->primaryKey} = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$acepta, $migrado, $nehemiasId, $id]);
    }
}
