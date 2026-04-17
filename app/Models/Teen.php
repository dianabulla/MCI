<?php

require_once APP . '/Models/BaseModel.php';

class Teen extends BaseModel {
    protected $table = 'teens';
    protected $primaryKey = 'id';
    private $tablaMenores = 'teen_menores';

    public function __construct() {
        parent::__construct();
        $this->ensureTableStructure();
        $this->ensureMenoresTableStructure();
    }

    /**
     * Verifica si una columna existe
     */
    private function columnExists($columnName, $tableName = null) {
        $tableName = $tableName ?: $this->table;
        $sql = "SHOW COLUMNS FROM {$tableName} LIKE ?";
        $rows = $this->query($sql, [$columnName]);
        return !empty($rows);
    }

    private function indexExists($indexName, $tableName = null) {
        $tableName = $tableName ?: $this->table;
        $sql = "SHOW INDEX FROM {$tableName} WHERE Key_name = ?";
        $rows = $this->query($sql, [$indexName]);
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

    private function ensureMenoresTableStructure() {
        try {
            $this->execute("
                CREATE TABLE IF NOT EXISTS {$this->tablaMenores} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    codigo_registro VARCHAR(24) NULL,
                    nombre_menor VARCHAR(180) NOT NULL,
                    id_acudiente INT NOT NULL,
                    nombre_acudiente VARCHAR(180) NOT NULL,
                    telefono_contacto VARCHAR(30) NULL,
                    fecha_nacimiento DATE NULL,
                    edad TINYINT UNSIGNED NOT NULL,
                    id_ministerio INT NULL,
                    asiste_celula TINYINT(1) NOT NULL DEFAULT 0,
                    barrio VARCHAR(150) NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_codigo_registro (codigo_registro),
                    KEY idx_acudiente (id_acudiente),
                    KEY idx_ministerio (id_ministerio)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            if (!$this->columnExists('codigo_registro', $this->tablaMenores)) {
                $this->execute("ALTER TABLE {$this->tablaMenores} ADD COLUMN codigo_registro VARCHAR(24) NULL FIRST");
            }

            if (!$this->columnExists('fecha_nacimiento', $this->tablaMenores)) {
                $this->execute("ALTER TABLE {$this->tablaMenores} ADD COLUMN fecha_nacimiento DATE NULL AFTER telefono_contacto");
            }

            if (!$this->columnExists('updated_at', $this->tablaMenores)) {
                $this->execute("ALTER TABLE {$this->tablaMenores} ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            }

            if (!$this->indexExists('uq_codigo_registro', $this->tablaMenores)) {
                $this->execute("CREATE UNIQUE INDEX uq_codigo_registro ON {$this->tablaMenores} (codigo_registro)");
            }
        } catch (Throwable $e) {
            error_log('Error asegurando estructura de teen_menores: ' . $e->getMessage());
        }
    }

    public function getMenoresRegistrados() {
        $this->ensureMenoresTableStructure();

        $sql = "SELECT tm.*, 
                       COALESCE(m.Nombre_Ministerio, 'Sin ministerio') AS Nombre_Ministerio,
                       TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre_Acudiente_Base,
                       COALESCE(NULLIF(TRIM(COALESCE(p.Telefono, '')), ''), tm.telefono_contacto) AS Telefono_Acudiente_Actual
                FROM {$this->tablaMenores} tm
                LEFT JOIN ministerio m ON m.Id_Ministerio = tm.id_ministerio
                LEFT JOIN persona p ON p.Id_Persona = tm.id_acudiente
                ORDER BY tm.created_at DESC, tm.id DESC";

        return $this->query($sql);
    }

    public function createMenor(array $data) {
        $tablaOriginal = $this->table;
        $llaveOriginal = $this->primaryKey;

        $this->table = $this->tablaMenores;
        $this->primaryKey = 'id';

        try {
            return parent::create($data);
        } finally {
            $this->table = $tablaOriginal;
            $this->primaryKey = $llaveOriginal;
        }
    }

    public function existeCodigoRegistro($codigo) {
        $codigo = trim((string)$codigo);
        if ($codigo === '') {
            return false;
        }

        $rows = $this->query(
            "SELECT id FROM {$this->tablaMenores} WHERE codigo_registro = ? LIMIT 1",
            [$codigo]
        );

        return !empty($rows);
    }

    public function getMenorByCodigoRegistro($codigo) {
        $codigo = trim((string)$codigo);
        if ($codigo === '') {
            return null;
        }

        $sql = "SELECT tm.*,
                       COALESCE(m.Nombre_Ministerio, 'Sin ministerio') AS Nombre_Ministerio,
                       TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre_Acudiente_Base,
                       COALESCE(NULLIF(TRIM(COALESCE(p.Telefono, '')), ''), tm.telefono_contacto) AS Telefono_Acudiente_Actual
                FROM {$this->tablaMenores} tm
                LEFT JOIN ministerio m ON m.Id_Ministerio = tm.id_ministerio
                LEFT JOIN persona p ON p.Id_Persona = tm.id_acudiente
                WHERE tm.codigo_registro = ?
                LIMIT 1";

        $rows = $this->query($sql, [$codigo]);
        return $rows[0] ?? null;
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