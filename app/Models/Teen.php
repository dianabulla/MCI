<?php

require_once APP . '/Models/BaseModel.php';

class Teen extends BaseModel {
    protected $table = 'teens';
    protected $primaryKey = 'id';
    private $tablaMenores = 'teen_menores';
    private $tablaAsistenciaSemanal = 'teen_menores_asistencia';

    public function __construct() {
        parent::__construct();
        $this->ensureTableStructure();
        $this->ensureMenoresTableStructure();
        $this->ensureAsistenciaSemanalStructure();
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

    private function ensureAsistenciaSemanalStructure() {
        try {
            $this->execute(" 
                CREATE TABLE IF NOT EXISTS {$this->tablaAsistenciaSemanal} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    id_menor INT NOT NULL,
                    fecha_domingo DATE NOT NULL,
                    codigo_semana VARCHAR(24) NOT NULL,
                    registrado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_menor_domingo (id_menor, fecha_domingo),
                    UNIQUE KEY uq_codigo_semana (codigo_semana),
                    KEY idx_fecha_domingo (fecha_domingo)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            if (!$this->indexExists('uq_menor_domingo', $this->tablaAsistenciaSemanal)) {
                $this->execute("CREATE UNIQUE INDEX uq_menor_domingo ON {$this->tablaAsistenciaSemanal} (id_menor, fecha_domingo)");
            }

            if (!$this->indexExists('uq_codigo_semana', $this->tablaAsistenciaSemanal)) {
                $this->execute("CREATE UNIQUE INDEX uq_codigo_semana ON {$this->tablaAsistenciaSemanal} (codigo_semana)");
            }
        } catch (Throwable $e) {
            error_log('Error asegurando estructura de teen_menores_asistencia: ' . $e->getMessage());
        }
    }

    private function getFechaDomingoSemana(?DateTimeInterface $fechaReferencia = null) {
        $base = $fechaReferencia ? DateTimeImmutable::createFromInterface($fechaReferencia) : new DateTimeImmutable('today');
        if ($base === false) {
            $base = new DateTimeImmutable('today');
        }

        $diaSemana = (int)$base->format('w'); // 0 = domingo
        if ($diaSemana > 0) {
            $base = $base->modify('-' . $diaSemana . ' days');
        }

        return $base->format('Y-m-d');
    }

    public function getMenoresRegistrados() {
        $this->ensureMenoresTableStructure();
        $this->ensureAsistenciaSemanalStructure();

        $sql = "SELECT tm.*, 
                       COALESCE(m.Nombre_Ministerio, 'Sin ministerio') AS Nombre_Ministerio,
                       TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre_Acudiente_Base,
                       COALESCE(NULLIF(TRIM(COALESCE(p.Telefono, '')), ''), tm.telefono_contacto) AS Telefono_Acudiente_Actual,
                       COALESCE(agg.total_asistencias, 0) AS total_asistencias,
                       agg.ultima_fecha_asistencia,
                       sem.codigo_semana AS codigo_semana_actual,
                       sem.registrado_en AS fecha_asistencia_actual
                FROM {$this->tablaMenores} tm
                LEFT JOIN ministerio m ON m.Id_Ministerio = tm.id_ministerio
                LEFT JOIN persona p ON p.Id_Persona = tm.id_acudiente
                LEFT JOIN (
                    SELECT id_menor,
                           COUNT(*) AS total_asistencias,
                           MAX(fecha_domingo) AS ultima_fecha_asistencia
                    FROM {$this->tablaAsistenciaSemanal}
                    GROUP BY id_menor
                ) agg ON agg.id_menor = tm.id
                LEFT JOIN {$this->tablaAsistenciaSemanal} sem ON sem.id_menor = tm.id
                    AND sem.fecha_domingo = DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 1) DAY)
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

    public function existeCodigoSemanal($codigo) {
        $codigo = trim((string)$codigo);
        if ($codigo === '') {
            return false;
        }

        $rows = $this->query(
            "SELECT id FROM {$this->tablaAsistenciaSemanal} WHERE codigo_semana = ? LIMIT 1",
            [$codigo]
        );

        return !empty($rows);
    }

    public function getMenorByCodigoRegistro($codigo) {
        $this->ensureAsistenciaSemanalStructure();

        $codigo = trim((string)$codigo);
        if ($codigo === '') {
            return null;
        }

        $sql = "SELECT tm.*,
                       COALESCE(m.Nombre_Ministerio, 'Sin ministerio') AS Nombre_Ministerio,
                       TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre_Acudiente_Base,
                       COALESCE(NULLIF(TRIM(COALESCE(p.Telefono, '')), ''), tm.telefono_contacto) AS Telefono_Acudiente_Actual,
                       ult.fecha_domingo AS ultima_fecha_asistencia,
                       ult.codigo_semana AS ultimo_codigo_semana,
                       COALESCE(agg.total_asistencias, 0) AS total_asistencias
                FROM {$this->tablaMenores} tm
                LEFT JOIN ministerio m ON m.Id_Ministerio = tm.id_ministerio
                LEFT JOIN persona p ON p.Id_Persona = tm.id_acudiente
                LEFT JOIN (
                    SELECT a1.id_menor, a1.fecha_domingo, a1.codigo_semana
                    FROM {$this->tablaAsistenciaSemanal} a1
                    INNER JOIN (
                        SELECT id_menor, MAX(fecha_domingo) AS max_domingo
                        FROM {$this->tablaAsistenciaSemanal}
                        GROUP BY id_menor
                    ) ult1 ON ult1.id_menor = a1.id_menor AND ult1.max_domingo = a1.fecha_domingo
                ) ult ON ult.id_menor = tm.id
                LEFT JOIN (
                    SELECT id_menor, COUNT(*) AS total_asistencias
                    FROM {$this->tablaAsistenciaSemanal}
                    GROUP BY id_menor
                ) agg ON agg.id_menor = tm.id
                WHERE tm.codigo_registro = ?
                LIMIT 1";

        $rows = $this->query($sql, [$codigo]);
        return $rows[0] ?? null;
    }

    public function getMenorByCodigoSemanal($codigoSemanal) {
        $this->ensureAsistenciaSemanalStructure();

        $codigoSemanal = trim((string)$codigoSemanal);
        if ($codigoSemanal === '') {
            return null;
        }

        $sql = "SELECT tm.*,
                       COALESCE(m.Nombre_Ministerio, 'Sin ministerio') AS Nombre_Ministerio,
                       TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre_Acudiente_Base,
                       COALESCE(NULLIF(TRIM(COALESCE(p.Telefono, '')), ''), tm.telefono_contacto) AS Telefono_Acudiente_Actual,
                       a.fecha_domingo AS fecha_asistencia_codigo,
                       a.codigo_semana AS codigo_semana,
                       COALESCE(agg.total_asistencias, 0) AS total_asistencias
                FROM {$this->tablaAsistenciaSemanal} a
                INNER JOIN {$this->tablaMenores} tm ON tm.id = a.id_menor
                LEFT JOIN ministerio m ON m.Id_Ministerio = tm.id_ministerio
                LEFT JOIN persona p ON p.Id_Persona = tm.id_acudiente
                LEFT JOIN (
                    SELECT id_menor, COUNT(*) AS total_asistencias
                    FROM {$this->tablaAsistenciaSemanal}
                    GROUP BY id_menor
                ) agg ON agg.id_menor = tm.id
                WHERE a.codigo_semana = ?
                LIMIT 1";

        $rows = $this->query($sql, [$codigoSemanal]);
        return $rows[0] ?? null;
    }

    public function findMenorExistentePublico($nombreMenor, $fechaNacimiento, $nombreAcudiente, $telefonoContacto) {
        $nombreMenor = trim((string)$nombreMenor);
        $fechaNacimiento = trim((string)$fechaNacimiento);
        $nombreAcudiente = trim((string)$nombreAcudiente);
        $telefonoContacto = preg_replace('/\D+/', '', (string)$telefonoContacto);

        if ($nombreMenor === '') {
            return null;
        }

        $telefonoExpr = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(telefono_contacto, ''), ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')";

        if ($fechaNacimiento !== '') {
            $sqlConFecha = "SELECT *
                            FROM {$this->tablaMenores}
                            WHERE nombre_menor = ?
                              AND fecha_nacimiento = ?
                              AND (
                                (nombre_acudiente = ? AND ? <> '')
                                OR ({$telefonoExpr} = ? AND ? <> '')
                              )
                            ORDER BY id DESC
                            LIMIT 1";

            $rowsConFecha = $this->query($sqlConFecha, [
                $nombreMenor,
                $fechaNacimiento,
                $nombreAcudiente,
                $nombreAcudiente,
                $telefonoContacto,
                $telefonoContacto
            ]);

            if (!empty($rowsConFecha[0])) {
                return $rowsConFecha[0];
            }
        }

        if ($telefonoContacto !== '') {
            $sqlSinFecha = "SELECT *
                            FROM {$this->tablaMenores}
                            WHERE nombre_menor = ?
                              AND {$telefonoExpr} = ?
                            ORDER BY id DESC
                            LIMIT 1";

            $rowsSinFecha = $this->query($sqlSinFecha, [$nombreMenor, $telefonoContacto]);
            if (!empty($rowsSinFecha[0])) {
                return $rowsSinFecha[0];
            }
        }

        return null;
    }

    public function getMenorRegistradoById($idMenor) {
        $idMenor = (int)$idMenor;
        if ($idMenor <= 0) {
            return null;
        }

        $rows = $this->query(
            "SELECT * FROM {$this->tablaMenores} WHERE id = ? LIMIT 1",
            [$idMenor]
        );

        return $rows[0] ?? null;
    }

    public function getMenorByTelefonoContacto($telefonoContacto) {
        $this->ensureAsistenciaSemanalStructure();

        $telefonoContacto = preg_replace('/\D+/', '', (string)$telefonoContacto);
        if ($telefonoContacto === '' || strlen($telefonoContacto) < 7) {
            return null;
        }

        $telefonoExpr = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(tm.telefono_contacto, ''), ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')";

        $sql = "SELECT tm.*,
                       COALESCE(m.Nombre_Ministerio, 'Sin ministerio') AS Nombre_Ministerio,
                       COALESCE(agg.total_asistencias, 0) AS total_asistencias,
                       ult.fecha_domingo AS ultima_fecha_asistencia,
                       ult.codigo_semana AS ultimo_codigo_semana,
                       sem.codigo_semana AS codigo_semana_actual,
                       sem.registrado_en AS fecha_asistencia_actual
                FROM {$this->tablaMenores} tm
                LEFT JOIN ministerio m ON m.Id_Ministerio = tm.id_ministerio
                LEFT JOIN (
                    SELECT id_menor, COUNT(*) AS total_asistencias
                    FROM {$this->tablaAsistenciaSemanal}
                    GROUP BY id_menor
                ) agg ON agg.id_menor = tm.id
                LEFT JOIN (
                    SELECT a1.id_menor, a1.fecha_domingo, a1.codigo_semana
                    FROM {$this->tablaAsistenciaSemanal} a1
                    INNER JOIN (
                        SELECT id_menor, MAX(fecha_domingo) AS max_domingo
                        FROM {$this->tablaAsistenciaSemanal}
                        GROUP BY id_menor
                    ) ult1 ON ult1.id_menor = a1.id_menor AND ult1.max_domingo = a1.fecha_domingo
                ) ult ON ult.id_menor = tm.id
                LEFT JOIN {$this->tablaAsistenciaSemanal} sem ON sem.id_menor = tm.id
                    AND sem.fecha_domingo = DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 1) DAY)
                WHERE {$telefonoExpr} = ?
                ORDER BY tm.updated_at DESC, tm.id DESC
                LIMIT 1";

        $rows = $this->query($sql, [$telefonoContacto]);
        return $rows[0] ?? null;
    }

    public function updateMenorById($idMenor, array $data) {
        $idMenor = (int)$idMenor;
        if ($idMenor <= 0 || empty($data)) {
            return false;
        }

        $sets = [];
        $params = [];
        foreach ($data as $campo => $valor) {
            $sets[] = "{$campo} = ?";
            $params[] = $valor;
        }

        $params[] = $idMenor;

        $sql = "UPDATE {$this->tablaMenores}
                SET " . implode(', ', $sets) . "
                WHERE id = ?
                LIMIT 1";

        return $this->execute($sql, $params);
    }

    public function registrarAsistenciaSemanal($idMenor, $codigoSemanal, ?DateTimeInterface $fechaReferencia = null) {
        $this->ensureAsistenciaSemanalStructure();

        $idMenor = (int)$idMenor;
        $codigoSemanal = trim((string)$codigoSemanal);
        if ($idMenor <= 0 || $codigoSemanal === '') {
            return false;
        }

        $fechaDomingo = $this->getFechaDomingoSemana($fechaReferencia);
        $sql = "INSERT INTO {$this->tablaAsistenciaSemanal} (id_menor, fecha_domingo, codigo_semana)
                VALUES (?, ?, ?)";

        return $this->execute($sql, [$idMenor, $fechaDomingo, $codigoSemanal]);
    }

    public function getAsistenciaSemanalActualByMenor($idMenor, ?DateTimeInterface $fechaReferencia = null) {
        $this->ensureAsistenciaSemanalStructure();

        $idMenor = (int)$idMenor;
        if ($idMenor <= 0) {
            return null;
        }

        $fechaDomingo = $this->getFechaDomingoSemana($fechaReferencia);
        $rows = $this->query(
            "SELECT * FROM {$this->tablaAsistenciaSemanal}
             WHERE id_menor = ? AND fecha_domingo = ?
             LIMIT 1",
            [$idMenor, $fechaDomingo]
        );

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