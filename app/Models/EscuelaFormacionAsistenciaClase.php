<?php

require_once APP . '/Models/BaseModel.php';

class EscuelaFormacionAsistenciaClase extends BaseModel {
    protected $table = 'escuela_formacion_asistencia_clase';
    protected $primaryKey = 'Id_Asistencia_Clase';

    private $tablaFechas = 'escuela_formacion_clase_fecha';

    public function __construct() {
        parent::__construct();
        $this->ensureTables();
    }

    private function ensureTables() {
        $sqlAsistencia = "CREATE TABLE IF NOT EXISTS {$this->table} (
            Id_Asistencia_Clase INT AUTO_INCREMENT PRIMARY KEY,
            Id_Persona INT NOT NULL,
            Modulo VARCHAR(40) NOT NULL,
            Programa VARCHAR(80) NOT NULL,
            Numero_Clase TINYINT UNSIGNED NOT NULL,
            Asistio TINYINT(1) NOT NULL DEFAULT 0,
            Fecha_Actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_persona_modulo_programa_clase (Id_Persona, Modulo, Programa, Numero_Clase),
            KEY idx_modulo_programa_clase (Modulo, Programa, Numero_Clase),
            KEY idx_persona (Id_Persona)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sqlFechas = "CREATE TABLE IF NOT EXISTS {$this->tablaFechas} (
            Id_Clase_Fecha INT AUTO_INCREMENT PRIMARY KEY,
            Modulo VARCHAR(40) NOT NULL,
            Programa VARCHAR(80) NOT NULL,
            Numero_Clase TINYINT UNSIGNED NOT NULL,
            Fecha_Clase DATE NULL,
            Fecha_Actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_modulo_programa_clase (Modulo, Programa, Numero_Clase)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->execute($sqlAsistencia);
        $this->execute($sqlFechas);

        if (!$this->tableHasColumn($this->tablaFechas, 'Grupo')) {
            $this->execute("ALTER TABLE {$this->tablaFechas} ADD COLUMN Grupo VARCHAR(20) NOT NULL DEFAULT 'general' AFTER Programa");
        }

        if ($this->tableHasIndex($this->tablaFechas, 'uniq_modulo_programa_clase') && !$this->tableHasIndex($this->tablaFechas, 'uniq_modulo_programa_grupo_clase')) {
            $this->execute("ALTER TABLE {$this->tablaFechas} DROP INDEX uniq_modulo_programa_clase");
        }

        if (!$this->tableHasIndex($this->tablaFechas, 'uniq_modulo_programa_grupo_clase')) {
            $this->execute("ALTER TABLE {$this->tablaFechas} ADD UNIQUE KEY uniq_modulo_programa_grupo_clase (Modulo, Programa, Grupo, Numero_Clase)");
        }
    }

    private function tableHasColumn(string $table, string $column): bool {
        $rows = $this->query("SHOW COLUMNS FROM {$table} LIKE ?", [$column]);
        return !empty($rows);
    }

    private function tableHasIndex(string $table, string $indexName): bool {
        $rows = $this->query("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($rows);
    }

    private function normalizeGrupo(?string $grupo): string {
        $grupo = strtolower(trim((string)$grupo));
        if (!in_array($grupo, ['hombres', 'mujeres', 'general'], true)) {
            return 'general';
        }
        return $grupo;
    }

    public function getAsistenciasPorPrograma(array $personIds, string $modulo, string $programa): array {
        $personIds = array_values(array_filter(array_map('intval', $personIds), static function($id) {
            return $id > 0;
        }));

        $modulo = trim($modulo);
        $programa = trim($programa);

        if (empty($personIds) || $modulo === '' || $programa === '') {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($personIds), '?'));
        $sql = "SELECT Id_Persona, Numero_Clase, Asistio
                FROM {$this->table}
                WHERE Modulo = ? AND Programa = ?
                  AND Id_Persona IN ({$placeholders})";

        $rows = $this->query($sql, array_merge([$modulo, $programa], $personIds));
        $map = [];

        foreach ($rows as $row) {
            $idPersona = (int)($row['Id_Persona'] ?? 0);
            $numeroClase = (int)($row['Numero_Clase'] ?? 0);
            if ($idPersona <= 0 || $numeroClase <= 0) {
                continue;
            }

            if (!isset($map[$idPersona])) {
                $map[$idPersona] = [];
            }

            $map[$idPersona][$numeroClase] = (int)($row['Asistio'] ?? 0) === 1;
        }

        return $map;
    }

    public function upsertAsistencia(int $idPersona, string $modulo, string $programa, int $numeroClase, bool $asistio): bool {
        if ($idPersona <= 0 || trim($modulo) === '' || trim($programa) === '' || $numeroClase <= 0) {
            return false;
        }

        $sql = "INSERT INTO {$this->table} (Id_Persona, Modulo, Programa, Numero_Clase, Asistio, Fecha_Actualizacion)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE Asistio = VALUES(Asistio), Fecha_Actualizacion = NOW()";

        return $this->execute($sql, [$idPersona, $modulo, $programa, $numeroClase, $asistio ? 1 : 0]);
    }

    public function getFechasClases(string $modulo, string $programa, int $totalClases = 5, ?string $grupo = 'general'): array {
        $modulo = trim($modulo);
        $programa = trim($programa);
        $totalClases = max(1, $totalClases);
        $grupo = $this->normalizeGrupo($grupo);

        if ($modulo === '' || $programa === '') {
            return [];
        }

        $sql = "SELECT Numero_Clase, Fecha_Clase, Grupo
                FROM {$this->tablaFechas}
                WHERE Modulo = ? AND Programa = ?
                  AND Grupo IN (?, 'general')
                ORDER BY Numero_Clase ASC, CASE WHEN Grupo = ? THEN 0 ELSE 1 END";

        $rows = $this->query($sql, [$modulo, $programa, $grupo, $grupo]);
        $map = [];

        foreach ($rows as $row) {
            $numeroClase = (int)($row['Numero_Clase'] ?? 0);
            if ($numeroClase <= 0) {
                continue;
            }
            if (!array_key_exists($numeroClase, $map)) {
                $map[$numeroClase] = (string)($row['Fecha_Clase'] ?? '');
            }
        }

        for ($i = 1; $i <= $totalClases; $i++) {
            if (!isset($map[$i])) {
                $map[$i] = '';
            }
        }

        ksort($map);
        return $map;
    }

    public function upsertFechaClase(string $modulo, string $programa, int $numeroClase, ?string $fechaClase, ?string $grupo = 'general'): bool {
        $modulo = trim($modulo);
        $programa = trim($programa);
        $grupo = $this->normalizeGrupo($grupo);

        if ($modulo === '' || $programa === '' || $numeroClase <= 0) {
            return false;
        }

        $fechaClase = $fechaClase !== null ? trim($fechaClase) : null;
        if ($fechaClase === '') {
            $fechaClase = null;
        }

        $sql = "INSERT INTO {$this->tablaFechas} (Modulo, Programa, Grupo, Numero_Clase, Fecha_Clase, Fecha_Actualizacion)
            VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE Fecha_Clase = VALUES(Fecha_Clase), Fecha_Actualizacion = NOW()";

        return $this->execute($sql, [$modulo, $programa, $grupo, $numeroClase, $fechaClase]);
    }

        public function getNumeroClasePorFecha(string $modulo, string $programa, ?string $fecha = null, ?string $grupo = 'general'): int {
        $modulo = trim($modulo);
        $programa = trim($programa);
        $fecha = trim((string)($fecha ?? date('Y-m-d')));
        $grupo = $this->normalizeGrupo($grupo);

        if ($modulo === '' || $programa === '' || $fecha === '') {
            return 0;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return 0;
        }

        $rows = $this->query(
            "SELECT Numero_Clase
             FROM {$this->tablaFechas}
                         WHERE Modulo = ? AND Programa = ? AND Fecha_Clase = ?
                             AND Grupo IN (?, 'general')
                         ORDER BY CASE WHEN Grupo = ? THEN 0 ELSE 1 END
             LIMIT 1",
                        [$modulo, $programa, $fecha, $grupo, $grupo]
        );

        if (empty($rows)) {
            return 0;
        }

        return max(0, (int)($rows[0]['Numero_Clase'] ?? 0));
    }
}
