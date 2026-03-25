<?php

require_once APP . '/Models/BaseModel.php';

class Teen extends BaseModel {
    protected $table = 'teens';
    protected $primaryKey = 'id';

    public function __construct() {
        parent::__construct();
        $this->ensureTableStructure();
    }

    /**
     * Verifica si una columna existe
     */
    private function columnExists($columnName) {
        $sql = "SHOW COLUMNS FROM {$this->table} LIKE ?";
        $rows = $this->query($sql, [$columnName]);
        return !empty($rows);
    }

    /**
     * Crear tabla o columnas necesarias
     */
    private function ensureTableStructure() {
        try {
            $this->execute("
                CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    titulo VARCHAR(255) NOT NULL,
                    descripcion TEXT NULL,
                    archivos_pdf LONGTEXT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            if (!$this->columnExists('archivos_pdf')) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN archivos_pdf LONGTEXT NULL");
            }

            if (!$this->columnExists('descripcion')) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN descripcion TEXT NULL");
            }

            if (!$this->columnExists('created_at')) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
            }

        } catch (Throwable $e) {
            error_log('Error asegurando estructura de teens: ' . $e->getMessage());
        }
    }

    /**
     * Obtener todos
     */
    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY id DESC";
        return $this->query($sql);
    }

    /**
     * Obtener por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $result = $this->query($sql, [$id]);
        return $result[0] ?? null;
    }

    /**
     * Crear registro
     */
    public function create($data) {
        return parent::create($data);
    }

    /**
     * Actualizar
     */
    public function updateTeen($id, $data) {
        return parent::update($id, $data);
    }

    /**
     * Eliminar
     */
    public function deleteTeen($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->execute($sql, [$id]);
    }
}