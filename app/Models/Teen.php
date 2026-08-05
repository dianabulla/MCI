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
        $this->ensureMesConfigTableStructure();
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

            if (!$this->columnExists('anio')) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN anio SMALLINT UNSIGNED NULL AFTER created_at");
            }

            if (!$this->columnExists('mes')) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN mes TINYINT UNSIGNED NULL AFTER anio");
            }

            if (!$this->columnExists('semana_mes')) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN semana_mes TINYINT UNSIGNED NULL AFTER mes");
            }

            if (!$this->columnExists('id_profesor')) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN id_profesor INT NULL AFTER semana_mes");
            }

            if (!$this->columnExists('profesor_nombre')) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN profesor_nombre VARCHAR(255) NULL AFTER id_profesor");
            }

            if (!$this->indexExists('idx_material_anio_mes', $this->table)) {
                $this->execute("CREATE INDEX idx_material_anio_mes ON {$this->table} (anio, mes, semana_mes)");
            }

        } catch (Throwable $e) {
            error_log('Error asegurando estructura de teens: ' . $e->getMessage());
        }
    }

    private function ensureMesConfigTableStructure(): void {
        try {
            $this->execute("
                CREATE TABLE IF NOT EXISTS teen_mes_config (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    anio SMALLINT UNSIGNED NOT NULL,
                    mes TINYINT UNSIGNED NOT NULL,
                    tema_mes VARCHAR(255) NULL,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_teen_mes_config (anio, mes)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (Throwable $e) {
            error_log('Error asegurando teen_mes_config: ' . $e->getMessage());
        }
    }

    /**
     * @return array<int, string> mes => tema
     */
    public function getTemasMesPorAnio(int $anio): array {
        if ($anio <= 0) {
            return [];
        }

        $rows = $this->query(
            "SELECT mes, tema_mes FROM teen_mes_config WHERE anio = ? ORDER BY mes ASC",
            [$anio]
        );

        $map = [];
        foreach ((array)$rows as $row) {
            $mes = (int)($row['mes'] ?? 0);
            if ($mes < 1 || $mes > 12) {
                continue;
            }
            $map[$mes] = trim((string)($row['tema_mes'] ?? ''));
        }

        return $map;
    }

    public function getTemaMes(int $anio, int $mes): string {
        if ($anio <= 0 || $mes < 1 || $mes > 12) {
            return '';
        }

        $rows = $this->query(
            "SELECT tema_mes FROM teen_mes_config WHERE anio = ? AND mes = ? LIMIT 1",
            [$anio, $mes]
        );

        return trim((string)($rows[0]['tema_mes'] ?? ''));
    }

    public function guardarTemaMes(int $anio, int $mes, string $temaMes): bool {
        if ($anio <= 0 || $mes < 1 || $mes > 12) {
            return false;
        }

        $temaMes = trim($temaMes);
        if (function_exists('mb_substr')) {
            $temaMes = mb_substr($temaMes, 0, 255, 'UTF-8');
        } else {
            $temaMes = substr($temaMes, 0, 255);
        }

        $existente = $this->query(
            "SELECT id FROM teen_mes_config WHERE anio = ? AND mes = ? LIMIT 1",
            [$anio, $mes]
        );

        if (!empty($existente[0]['id'])) {
            return $this->execute(
                "UPDATE teen_mes_config SET tema_mes = ?, updated_at = NOW() WHERE id = ?",
                [$temaMes !== '' ? $temaMes : null, (int)$existente[0]['id']]
            );
        }

        if ($temaMes === '') {
            return true;
        }

        return $this->execute(
            "INSERT INTO teen_mes_config (anio, mes, tema_mes) VALUES (?, ?, ?)",
            [$anio, $mes, $temaMes]
        );
    }

    /** @return array<int, string> */
    public static function nombresMeses(): array {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
    }

    public static function nombreMes(int $mes): string {
        return self::nombresMeses()[$mes] ?? ('Mes ' . $mes);
    }

    public static function semanasPorMes(): int {
        return 5;
    }

    /** semana_mes = 0 identifica material de decoración del mes. */
    public static function semanaDecoracion(): int {
        return 0;
    }

    public static function esSemanaDecoracion(int $semanaMes): bool {
        return $semanaMes === self::semanaDecoracion();
    }

    /** Semana del mes según el día (1–7 → 1, 8–14 → 2, …). */
    public static function semanaActualDelMes(?DateTimeInterface $fecha = null): int {
        $base = $fecha ? DateTimeImmutable::createFromInterface($fecha) : new DateTimeImmutable('today');
        if ($base === false) {
            $base = new DateTimeImmutable('today');
        }
        $dia = (int)$base->format('j');

        return min(self::semanasPorMes(), max(1, (int)ceil($dia / 7)));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMaterialesPorAnio(int $anio): array {
        $sql = "SELECT * FROM {$this->table}
                WHERE anio = ?
                ORDER BY mes ASC, semana_mes ASC, id ASC";

        return $this->query($sql, [$anio]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMaterialPorSemana(int $anio, int $mes, int $semanaMes): ?array {
        if ($anio <= 0 || $mes < 1 || $mes > 12 || $semanaMes < 1 || $semanaMes > self::semanasPorMes()) {
            return null;
        }

        $rows = $this->query(
            "SELECT * FROM {$this->table}
             WHERE anio = ? AND mes = ? AND semana_mes = ?
             ORDER BY id DESC
             LIMIT 1",
            [$anio, $mes, $semanaMes]
        );

        return $rows[0] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMaterialDecoracionPorMes(int $anio, int $mes): ?array {
        if ($anio <= 0 || $mes < 1 || $mes > 12) {
            return null;
        }

        $rows = $this->query(
            "SELECT * FROM {$this->table}
             WHERE anio = ? AND mes = ? AND semana_mes = ?
             ORDER BY id DESC
             LIMIT 1",
            [$anio, $mes, self::semanaDecoracion()]
        );

        return $rows[0] ?? null;
    }

    public function actualizarProfesorMaterial(int $id, int $idProfesor, string $profesorNombre): bool {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }

        return $this->updateTeen($id, [
            'id_profesor' => $idProfesor > 0 ? $idProfesor : null,
            'profesor_nombre' => trim($profesorNombre) !== '' ? trim($profesorNombre) : null,
        ]);
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

            if (!$this->columnExists('documento', $this->tablaMenores)) {
                $this->execute("ALTER TABLE {$this->tablaMenores} ADD COLUMN documento VARCHAR(40) NULL AFTER nombre_menor");
            }

            if (!$this->columnExists('es_nuevo', $this->tablaMenores)) {
                $this->execute("ALTER TABLE {$this->tablaMenores} ADD COLUMN es_nuevo TINYINT(1) NOT NULL DEFAULT 1 AFTER barrio");
            }

            if (!$this->columnExists('invitado_por', $this->tablaMenores)) {
                $this->execute("ALTER TABLE {$this->tablaMenores} ADD COLUMN invitado_por VARCHAR(180) NULL AFTER es_nuevo");
            }

            if (!$this->indexExists('idx_documento', $this->tablaMenores)) {
                $this->execute("CREATE INDEX idx_documento ON {$this->tablaMenores} (documento)");
            }

            if (!$this->indexExists('uq_documento_menor', $this->tablaMenores)) {
                try {
                    $this->execute("CREATE UNIQUE INDEX uq_documento_menor ON {$this->tablaMenores} (documento)");
                } catch (Throwable $e) {
                    error_log('No se pudo crear índice único uq_documento_menor (puede haber duplicados previos): ' . $e->getMessage());
                }
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
                    UNIQUE KEY uq_fecha_codigo_semana (fecha_domingo, codigo_semana),
                    KEY idx_fecha_domingo (fecha_domingo)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            if (!$this->indexExists('uq_menor_domingo', $this->tablaAsistenciaSemanal)) {
                $this->execute("CREATE UNIQUE INDEX uq_menor_domingo ON {$this->tablaAsistenciaSemanal} (id_menor, fecha_domingo)");
            }

            if ($this->indexExists('uq_codigo_semana', $this->tablaAsistenciaSemanal)) {
                $this->execute("ALTER TABLE {$this->tablaAsistenciaSemanal} DROP INDEX uq_codigo_semana");
            }

            if (!$this->indexExists('uq_fecha_codigo_semana', $this->tablaAsistenciaSemanal)) {
                $this->execute("CREATE UNIQUE INDEX uq_fecha_codigo_semana ON {$this->tablaAsistenciaSemanal} (fecha_domingo, codigo_semana)");
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
                      ult.codigo_semana AS ultimo_codigo_semana,
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

    public function existeCodigoSemanal($codigo, $fechaDomingo = null) {
        $codigo = trim((string)$codigo);
        if ($codigo === '') {
            return false;
        }

        if ($fechaDomingo !== null && trim((string)$fechaDomingo) !== '') {
            $rows = $this->query(
                "SELECT id FROM {$this->tablaAsistenciaSemanal} WHERE codigo_semana = ? AND fecha_domingo = ? LIMIT 1",
                [$codigo, $fechaDomingo]
            );
        } else {
            $rows = $this->query(
                "SELECT id FROM {$this->tablaAsistenciaSemanal} WHERE codigo_semana = ? LIMIT 1",
                [$codigo]
            );
        }

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

        $fechaDomingo = $this->getFechaDomingoSemana();

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
                  AND a.fecha_domingo = ?
                LIMIT 1";

        $rows = $this->query($sql, [$codigoSemanal, $fechaDomingo]);
        return $rows[0] ?? null;
    }

    public static function normalizarDocumentoMenor(string $documento): string {
        $documento = strtoupper(trim($documento));
        if ($documento === '') {
            return '';
        }
        return (string)preg_replace('/[^A-Z0-9]/', '', $documento);
    }

    public static function normalizarNombreMenor(string $nombre): string {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return '';
        }
        $nombre = preg_replace('/\s+/', ' ', $nombre) ?? $nombre;

        return function_exists('mb_strtoupper')
            ? mb_strtoupper($nombre, 'UTF-8')
            : strtoupper($nombre);
    }

    private function exprTelefonoNormalizado(string $columna = 'telefono_contacto'): string {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$columna}, ''), ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')";
    }

    private function exprNombreNormalizado(string $columna = 'nombre_menor'): string {
        return "UPPER(TRIM(COALESCE({$columna}, '')))";
    }

    private function exprDocumentoNormalizado(string $alias = 'tm'): string {
        $col = $alias !== '' ? "{$alias}.documento" : 'documento';
        return "REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE({$col}, ''))), ' ', ''), '.', ''), '-', '')";
    }

    public function getMenorByDocumento(string $documento) {
        $this->ensureMenoresTableStructure();
        $this->ensureAsistenciaSemanalStructure();

        $docNorm = self::normalizarDocumentoMenor($documento);
        if ($docNorm === '' || strlen($docNorm) < 3) {
            return null;
        }

        $docExpr = $this->exprDocumentoNormalizado('tm');

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
                WHERE {$docExpr} = ?
                ORDER BY tm.updated_at DESC, tm.id DESC
                LIMIT 1";

        $rows = $this->query($sql, [$docNorm]);
        return $rows[0] ?? null;
    }

    /**
     * Resuelve un menor ya registrado (evita duplicados).
     *
     * @return array<string, mixed>|null
     */
    public function resolverMenorRegistrado(
        int $idPreferido = 0,
        string $documento = '',
        string $nombreMenor = '',
        string $fechaNacimiento = '',
        string $telefono = '',
        string $nombreAcudiente = ''
    ): ?array {
        if ($idPreferido > 0) {
            $porId = $this->getMenorCompletoById($idPreferido);
            if (!empty($porId)) {
                return $porId;
            }
        }

        $documentoNorm = self::normalizarDocumentoMenor($documento);
        if ($documentoNorm !== '') {
            $porDoc = $this->getMenorByDocumento($documentoNorm);
            if (!empty($porDoc)) {
                return $this->enriquecerMenorCompleto($porDoc);
            }
        }

        $porCoincidencia = $this->findMenorExistentePublico(
            $nombreMenor,
            $fechaNacimiento,
            $nombreAcudiente,
            $telefono,
            $documentoNorm
        );
        if (!empty($porCoincidencia)) {
            return $this->enriquecerMenorCompleto($porCoincidencia);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $menor
     * @return array<string, mixed>
     */
    private function enriquecerMenorCompleto(array $menor): array {
        $id = (int)($menor['id'] ?? 0);
        if ($id <= 0) {
            return $menor;
        }

        $completo = $this->getMenorCompletoById($id);

        return !empty($completo) ? $completo : $menor;
    }

    /**
     * Guarda el documento en un registro legacy que no lo tenía.
     */
    public function vincularDocumentoMenor(int $idMenor, string $documento): void {
        $idMenor = (int)$idMenor;
        $documentoNorm = self::normalizarDocumentoMenor($documento);
        if ($idMenor <= 0 || $documentoNorm === '') {
            return;
        }

        $actual = $this->getMenorRegistradoById($idMenor);
        if (empty($actual)) {
            return;
        }

        $docActual = self::normalizarDocumentoMenor((string)($actual['documento'] ?? ''));
        if ($docActual !== '' && $docActual !== $documentoNorm) {
            return;
        }

        if ($docActual === $documentoNorm) {
            return;
        }

        $this->updateMenorById($idMenor, [
            'documento' => $documentoNorm,
            'es_nuevo' => 0,
        ]);
    }

    public function documentoMenorExiste(string $documento, int $excluirId = 0): bool {
        $registro = $this->getMenorByDocumento($documento);
        if (empty($registro)) {
            return false;
        }
        if ($excluirId > 0 && (int)($registro['id'] ?? 0) === $excluirId) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMenorCompletoById(int $idMenor): ?array {
        if ($idMenor <= 0) {
            return null;
        }

        $this->ensureAsistenciaSemanalStructure();

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
                WHERE tm.id = ?
                LIMIT 1";

        $rows = $this->query($sql, [$idMenor]);

        return $rows[0] ?? null;
    }

    public function findMenorExistentePublico($nombreMenor, $fechaNacimiento, $nombreAcudiente, $telefonoContacto, $documento = '') {
        $documentoNorm = self::normalizarDocumentoMenor((string)$documento);
        if ($documentoNorm !== '') {
            $porDocumento = $this->getMenorByDocumento($documentoNorm);
            if (!empty($porDocumento)) {
                return $porDocumento;
            }
        }

        $nombreMenor = self::normalizarNombreMenor((string)$nombreMenor);
        $fechaNacimiento = trim((string)$fechaNacimiento);
        $nombreAcudiente = self::normalizarNombreMenor((string)$nombreAcudiente);
        $telefonoContacto = preg_replace('/\D+/', '', (string)$telefonoContacto);

        if ($nombreMenor === '' && $telefonoContacto === '') {
            return null;
        }

        $telefonoExpr = $this->exprTelefonoNormalizado('telefono_contacto');
        $nombreExpr = $this->exprNombreNormalizado('nombre_menor');
        $acudienteExpr = $this->exprNombreNormalizado('nombre_acudiente');

        if ($nombreMenor !== '' && $fechaNacimiento !== '') {
            $sqlConFecha = "SELECT *
                            FROM {$this->tablaMenores}
                            WHERE {$nombreExpr} = ?
                              AND fecha_nacimiento = ?
                              AND (
                                ({$acudienteExpr} = ? AND ? <> '')
                                OR ({$telefonoExpr} = ? AND ? <> '')
                              )
                            ORDER BY id ASC
                            LIMIT 1";

            $rowsConFecha = $this->query($sqlConFecha, [
                $nombreMenor,
                $fechaNacimiento,
                $nombreAcudiente,
                $nombreAcudiente,
                $telefonoContacto,
                $telefonoContacto,
            ]);

            if (!empty($rowsConFecha[0])) {
                return $rowsConFecha[0];
            }
        }

        if ($nombreMenor !== '' && $telefonoContacto !== '') {
            $sqlNombreTel = "SELECT *
                             FROM {$this->tablaMenores}
                             WHERE {$nombreExpr} = ?
                               AND {$telefonoExpr} = ?
                             ORDER BY id ASC
                             LIMIT 1";

            $rowsNombreTel = $this->query($sqlNombreTel, [$nombreMenor, $telefonoContacto]);
            if (!empty($rowsNombreTel[0])) {
                return $rowsNombreTel[0];
            }
        }

        if ($telefonoContacto !== '' && strlen($telefonoContacto) === 10) {
            $sqlSoloTel = "SELECT *
                           FROM {$this->tablaMenores}
                           WHERE {$telefonoExpr} = ?
                           ORDER BY id ASC";

            $rowsSoloTel = $this->query($sqlSoloTel, [$telefonoContacto]);
            if (count($rowsSoloTel) === 1) {
                return $rowsSoloTel[0];
            }
            if ($nombreMenor !== '' && count($rowsSoloTel) > 1) {
                foreach ($rowsSoloTel as $row) {
                    if (self::normalizarNombreMenor((string)($row['nombre_menor'] ?? '')) === $nombreMenor) {
                        return $row;
                    }
                }
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

    public function actualizarCodigoAsistenciaSemanal(int $idAsistencia, string $codigoSemanal): bool {
        $this->ensureAsistenciaSemanalStructure();

        $idAsistencia = (int)$idAsistencia;
        $codigoSemanal = trim($codigoSemanal);
        if ($idAsistencia <= 0 || $codigoSemanal === '') {
            return false;
        }

        $sql = "UPDATE {$this->tablaAsistenciaSemanal}
                SET codigo_semana = ?, registrado_en = NOW()
                WHERE id = ?
                LIMIT 1";

        return $this->execute($sql, [$codigoSemanal, $idAsistencia]);
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