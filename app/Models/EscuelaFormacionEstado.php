<?php

class EscuelaFormacionEstado extends BaseModel {
    protected $table = 'escuela_formacion_estado';
    protected $primaryKey = 'Id_Estado';

    public function __construct() {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            Id_Estado INT AUTO_INCREMENT PRIMARY KEY,
            Id_Persona INT NOT NULL,
            Programa VARCHAR(80) NOT NULL,
            Va TINYINT(1) NOT NULL DEFAULT 0,
            Fecha_Actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_persona_programa (Id_Persona, Programa),
            KEY idx_programa (Programa),
            KEY idx_persona (Id_Persona)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);
    }

    public function getEstadosPorPrograma(array $personIds, string $programa): array {
        $personIds = array_values(array_filter(array_map('intval', $personIds), static function($id) {
            return $id > 0;
        }));
        if (empty($personIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($personIds), '?'));
        $sql = "SELECT Id_Persona, Va FROM {$this->table} WHERE Programa = ? AND Id_Persona IN ({$placeholders})";
        $rows = $this->query($sql, array_merge([$programa], $personIds));

        $map = [];
        foreach ($rows as $row) {
            $map[(int)($row['Id_Persona'] ?? 0)] = (int)($row['Va'] ?? 0) === 1;
        }
        return $map;
    }

    public function getEstadosDetallePorPrograma(array $personIds, string $programa): array {
        $personIds = array_values(array_filter(array_map('intval', $personIds), static function($id) {
            return $id > 0;
        }));
        if (empty($personIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($personIds), '?'));
        $sql = "SELECT Id_Persona, Va FROM {$this->table} WHERE Programa = ? AND Id_Persona IN ({$placeholders})";
        $rows = $this->query($sql, array_merge([$programa], $personIds));

        $map = [];
        foreach ($rows as $row) {
            $idPersona = (int)($row['Id_Persona'] ?? 0);
            if ($idPersona <= 0) {
                continue;
            }

            $map[$idPersona] = [
                'existe' => true,
                'va' => (int)($row['Va'] ?? 0) === 1,
            ];
        }

        return $map;
    }

    public function upsertEstado(int $idPersona, string $programa, bool $va): bool {
        $sql = "INSERT INTO {$this->table} (Id_Persona, Programa, Va, Fecha_Actualizacion)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE Va = VALUES(Va), Fecha_Actualizacion = NOW()";

        return $this->execute($sql, [$idPersona, $programa, $va ? 1 : 0]);
    }
}