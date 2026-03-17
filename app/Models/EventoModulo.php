<?php
/**
 * Modelo para contenidos de mini-módulos de eventos.
 */

require_once APP . '/Models/BaseModel.php';

class EventoModulo extends BaseModel {
    protected $table = 'evento_modulo_contenido';
    protected $primaryKey = 'Id_Contenido';

    public function __construct() {
        parent::__construct();
        $this->asegurarTabla();
    }

    private function asegurarTabla() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    Id_Contenido INT AUTO_INCREMENT PRIMARY KEY,
                    Tipo_Modulo VARCHAR(60) NOT NULL,
                    Titulo VARCHAR(180) NOT NULL,
                    Parrafo TEXT NOT NULL,
                    Imagen VARCHAR(255) NULL,
                    Video VARCHAR(255) NULL,
                    Orden INT NOT NULL DEFAULT 0,
                    Estado_Activo TINYINT(1) NOT NULL DEFAULT 1,
                    Fecha_Publicacion_Desde DATE NULL,
                    Fecha_Publicacion_Hasta DATE NULL,
                    Fecha_Creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_tipo_modulo (Tipo_Modulo),
                    INDEX idx_orden (Orden)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->execute($sql);

        $columnas = [
            'Estado_Activo' => "ALTER TABLE {$this->table} ADD COLUMN Estado_Activo TINYINT(1) NOT NULL DEFAULT 1 AFTER Orden",
            'Fecha_Publicacion_Desde' => "ALTER TABLE {$this->table} ADD COLUMN Fecha_Publicacion_Desde DATE NULL AFTER Estado_Activo",
            'Fecha_Publicacion_Hasta' => "ALTER TABLE {$this->table} ADD COLUMN Fecha_Publicacion_Hasta DATE NULL AFTER Fecha_Publicacion_Desde"
        ];

        foreach ($columnas as $nombre => $sqlAlter) {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute([$nombre]);
            if (!$stmt->fetch()) {
                $this->db->exec($sqlAlter);
            }
        }
    }

    public function getByModulo($tipoModulo) {
        $sql = "SELECT * FROM {$this->table}
                WHERE Tipo_Modulo = ?
                ORDER BY Orden ASC, Id_Contenido DESC";
        return $this->query($sql, [$tipoModulo]);
    }

    public function getByModuloPublico($tipoModulo, $fechaHoy = null) {
        $fechaHoy = $fechaHoy ?: date('Y-m-d');

        $sql = "SELECT * FROM {$this->table}
                WHERE Tipo_Modulo = ?
                AND Estado_Activo = 1
                AND (Fecha_Publicacion_Desde IS NULL OR Fecha_Publicacion_Desde <= ?)
                AND (Fecha_Publicacion_Hasta IS NULL OR Fecha_Publicacion_Hasta >= ?)
                ORDER BY Orden ASC, Id_Contenido DESC";

        return $this->query($sql, [$tipoModulo, $fechaHoy, $fechaHoy]);
    }

    public function getById($id) {
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }

        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ? LIMIT 1";
        $rows = $this->query($sql, [$id]);
        return $rows[0] ?? null;
    }

    public function duplicar($idContenido) {
        $item = $this->getById($idContenido);
        if (empty($item)) {
            return false;
        }

        $data = [
            'Tipo_Modulo' => (string)($item['Tipo_Modulo'] ?? ''),
            'Titulo' => (string)($item['Titulo'] ?? ''),
            'Parrafo' => (string)($item['Parrafo'] ?? ''),
            'Imagen' => !empty($item['Imagen']) ? basename((string)$item['Imagen']) : null,
            'Video' => !empty($item['Video']) ? basename((string)$item['Video']) : null,
            'Orden' => ((int)($item['Orden'] ?? 0)) + 1,
            'Estado_Activo' => (int)($item['Estado_Activo'] ?? 1),
            'Fecha_Publicacion_Desde' => !empty($item['Fecha_Publicacion_Desde']) ? (string)$item['Fecha_Publicacion_Desde'] : null,
            'Fecha_Publicacion_Hasta' => !empty($item['Fecha_Publicacion_Hasta']) ? (string)$item['Fecha_Publicacion_Hasta'] : null
        ];

        return $this->create($data);
    }
}
