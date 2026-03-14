<?php
/**
 * Modelo Ministerio
 */

require_once APP . '/Models/BaseModel.php';

class Ministerio extends BaseModel {
    protected $table = 'ministerio';
    protected $primaryKey = 'Id_Ministerio';

    /**
     * Obtener ministerio por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $result = $this->query($sql, [$id]);
        return $result ? $result[0] : null;
    }

    /**
     * Obtener ministerios con contador de miembros
     */
    public function getAllWithMemberCount() {
        $sql = "SELECT m.*, 
                COUNT(p.Id_Persona) as Total_Miembros
                FROM {$this->table} m
                LEFT JOIN persona p ON m.Id_Ministerio = p.Id_Ministerio
                GROUP BY m.Id_Ministerio
                ORDER BY m.Nombre_Ministerio";
        return $this->query($sql);
    }

    /**
     * Obtener ministerios con contador de miembros y aislamiento de rol
     */
    public function getAllWithMemberCountAndRole($filtroRol) {
        $sql = "SELECT m.*, 
                COUNT(p.Id_Persona) as Total_Miembros
                FROM {$this->table} m
                LEFT JOIN persona p ON m.Id_Ministerio = p.Id_Ministerio
                WHERE $filtroRol
                GROUP BY m.Id_Ministerio
                ORDER BY m.Nombre_Ministerio";
        return $this->query($sql);
    }

    /**
     * Asegura la tabla de metas por ministerio.
     */
    private function asegurarTablaMetas() {
        $sql = "CREATE TABLE IF NOT EXISTS ministerio_meta (
                    Id_Ministerio INT NOT NULL,
                    Meta_Ganados INT NOT NULL DEFAULT 0,
                    Meta_Ganados_S1 INT NOT NULL DEFAULT 0,
                    Meta_Ganados_S2 INT NOT NULL DEFAULT 0,
                    Meta_UV_S1 INT NOT NULL DEFAULT 0,
                    Meta_UV_S2 INT NOT NULL DEFAULT 0,
                    Meta_Encuentro_S1 INT NOT NULL DEFAULT 0,
                    Meta_Encuentro_S2 INT NOT NULL DEFAULT 0,
                    Meta_Convencion_N1_S1 INT NOT NULL DEFAULT 0,
                    Meta_Convencion_N1_S2 INT NOT NULL DEFAULT 0,
                    Meta_Convencion_N2_S1 INT NOT NULL DEFAULT 0,
                    Meta_Convencion_N2_S2 INT NOT NULL DEFAULT 0,
                    Meta_Convencion_N3_S1 INT NOT NULL DEFAULT 0,
                    Meta_Convencion_N3_S2 INT NOT NULL DEFAULT 0,
                    Fecha_Actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (Id_Ministerio)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->execute($sql);

        // Compatibilidad con instalaciones donde la tabla ya existía solo con Meta_Ganados.
        $columnas = [
            'Meta_Ganados_S1',
            'Meta_Ganados_S2',
            'Meta_UV_S1',
            'Meta_UV_S2',
            'Meta_Encuentro_S1',
            'Meta_Encuentro_S2',
            'Meta_Convencion_N1_S1',
            'Meta_Convencion_N1_S2',
            'Meta_Convencion_N2_S1',
            'Meta_Convencion_N2_S2',
            'Meta_Convencion_N3_S1',
            'Meta_Convencion_N3_S2'
        ];

        foreach ($columnas as $columna) {
            if (!$this->existeColumnaMeta($columna)) {
                $this->execute("ALTER TABLE ministerio_meta ADD COLUMN {$columna} INT NOT NULL DEFAULT 0");
            }
        }
    }

    private function existeColumnaMeta($nombreColumna) {
        $sql = "SELECT COUNT(*) AS total
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'ministerio_meta'
                AND COLUMN_NAME = ?";
        $rows = $this->query($sql, [$nombreColumna]);
        return !empty($rows) && (int)($rows[0]['total'] ?? 0) > 0;
    }

    private function metasDetallePorDefecto() {
        return [
            'meta_ganados_s1' => 0,
            'meta_ganados_s2' => 0,
            'meta_uv_s1' => 0,
            'meta_uv_s2' => 0,
            'meta_encuentro_s1' => 0,
            'meta_encuentro_s2' => 0,
            'meta_n1_s1' => 0,
            'meta_n1_s2' => 0,
            'meta_n2_s1' => 0,
            'meta_n2_s2' => 0,
            'meta_n3_s1' => 0,
            'meta_n3_s2' => 0
        ];
    }

    /**
     * Obtiene metas de ganados para un conjunto de ministerios.
     *
     * @return array [Id_Ministerio => Meta_Ganados]
     */
    public function getMetasByMinisterioIds(array $ministerioIds) {
        $ministerioIds = array_values(array_unique(array_filter(array_map('intval', $ministerioIds), static function($id) {
            return $id > 0;
        })));

        if (empty($ministerioIds)) {
            return [];
        }

        $this->asegurarTablaMetas();

        $placeholders = implode(',', array_fill(0, count($ministerioIds), '?'));
        $sql = "SELECT Id_Ministerio, Meta_Ganados FROM ministerio_meta WHERE Id_Ministerio IN ($placeholders)";
        $rows = $this->query($sql, $ministerioIds);

        $resultado = [];
        foreach ($rows as $row) {
            $id = (int)($row['Id_Ministerio'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $resultado[$id] = max(0, (int)($row['Meta_Ganados'] ?? 0));
        }

        return $resultado;
    }

    /**
     * Obtener metas por ministerio para eventos y ganados por semestre.
     *
     * @return array [Id_Ministerio => metas]
     */
    public function getMetasDetalleByMinisterioIds(array $ministerioIds) {
        $ministerioIds = array_values(array_unique(array_filter(array_map('intval', $ministerioIds), static function($id) {
            return $id > 0;
        })));

        if (empty($ministerioIds)) {
            return [];
        }

        $this->asegurarTablaMetas();

        $placeholders = implode(',', array_fill(0, count($ministerioIds), '?'));
        $sql = "SELECT * FROM ministerio_meta WHERE Id_Ministerio IN ($placeholders)";
        $rows = $this->query($sql, $ministerioIds);

        $resultado = [];
        foreach ($rows as $row) {
            $id = (int)($row['Id_Ministerio'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $metas = $this->metasDetallePorDefecto();
            $metas['meta_ganados_s1'] = max(0, (int)($row['Meta_Ganados_S1'] ?? 0));
            $metas['meta_ganados_s2'] = max(0, (int)($row['Meta_Ganados_S2'] ?? 0));
            $metas['meta_uv_s1'] = max(0, (int)($row['Meta_UV_S1'] ?? 0));
            $metas['meta_uv_s2'] = max(0, (int)($row['Meta_UV_S2'] ?? 0));
            $metas['meta_encuentro_s1'] = max(0, (int)($row['Meta_Encuentro_S1'] ?? 0));
            $metas['meta_encuentro_s2'] = max(0, (int)($row['Meta_Encuentro_S2'] ?? 0));
            $metas['meta_n1_s1'] = max(0, (int)($row['Meta_Convencion_N1_S1'] ?? 0));
            $metas['meta_n1_s2'] = max(0, (int)($row['Meta_Convencion_N1_S2'] ?? 0));
            $metas['meta_n2_s1'] = max(0, (int)($row['Meta_Convencion_N2_S1'] ?? 0));
            $metas['meta_n2_s2'] = max(0, (int)($row['Meta_Convencion_N2_S2'] ?? 0));
            $metas['meta_n3_s1'] = max(0, (int)($row['Meta_Convencion_N3_S1'] ?? 0));
            $metas['meta_n3_s2'] = max(0, (int)($row['Meta_Convencion_N3_S2'] ?? 0));

            // Compatibilidad: si aún no se cargó por semestre, reutiliza la meta antigua.
            $metaLegacy = max(0, (int)($row['Meta_Ganados'] ?? 0));
            if ($metas['meta_ganados_s1'] === 0 && $metas['meta_ganados_s2'] === 0 && $metaLegacy > 0) {
                $metas['meta_ganados_s1'] = $metaLegacy;
                $metas['meta_ganados_s2'] = $metaLegacy;
            }

            $resultado[$id] = $metas;
        }

        return $resultado;
    }

    public function getMetaDetalleByMinisterioId($idMinisterio) {
        $idMinisterio = (int)$idMinisterio;
        if ($idMinisterio <= 0) {
            return $this->metasDetallePorDefecto();
        }

        $metas = $this->getMetasDetalleByMinisterioIds([$idMinisterio]);
        return $metas[$idMinisterio] ?? $this->metasDetallePorDefecto();
    }

    /**
     * Guarda metas por semestre y por evento.
     */
    public function setMetasDetalle($idMinisterio, array $metas) {
        $idMinisterio = (int)$idMinisterio;
        if ($idMinisterio <= 0) {
            return false;
        }

        $this->asegurarTablaMetas();

        $normalizadas = $this->metasDetallePorDefecto();
        foreach ($normalizadas as $clave => $valorDefecto) {
            $normalizadas[$clave] = max(0, (int)($metas[$clave] ?? 0));
        }

        $metaLegacy = max($normalizadas['meta_ganados_s1'], $normalizadas['meta_ganados_s2']);

        $sql = "INSERT INTO ministerio_meta (
                    Id_Ministerio,
                    Meta_Ganados,
                    Meta_Ganados_S1,
                    Meta_Ganados_S2,
                    Meta_UV_S1,
                    Meta_UV_S2,
                    Meta_Encuentro_S1,
                    Meta_Encuentro_S2,
                    Meta_Convencion_N1_S1,
                    Meta_Convencion_N1_S2,
                    Meta_Convencion_N2_S1,
                    Meta_Convencion_N2_S2,
                    Meta_Convencion_N3_S1,
                    Meta_Convencion_N3_S2
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    Meta_Ganados = VALUES(Meta_Ganados),
                    Meta_Ganados_S1 = VALUES(Meta_Ganados_S1),
                    Meta_Ganados_S2 = VALUES(Meta_Ganados_S2),
                    Meta_UV_S1 = VALUES(Meta_UV_S1),
                    Meta_UV_S2 = VALUES(Meta_UV_S2),
                    Meta_Encuentro_S1 = VALUES(Meta_Encuentro_S1),
                    Meta_Encuentro_S2 = VALUES(Meta_Encuentro_S2),
                    Meta_Convencion_N1_S1 = VALUES(Meta_Convencion_N1_S1),
                    Meta_Convencion_N1_S2 = VALUES(Meta_Convencion_N1_S2),
                    Meta_Convencion_N2_S1 = VALUES(Meta_Convencion_N2_S1),
                    Meta_Convencion_N2_S2 = VALUES(Meta_Convencion_N2_S2),
                    Meta_Convencion_N3_S1 = VALUES(Meta_Convencion_N3_S1),
                    Meta_Convencion_N3_S2 = VALUES(Meta_Convencion_N3_S2),
                    Fecha_Actualizacion = NOW()";

        return $this->execute($sql, [
            $idMinisterio,
            $metaLegacy,
            $normalizadas['meta_ganados_s1'],
            $normalizadas['meta_ganados_s2'],
            $normalizadas['meta_uv_s1'],
            $normalizadas['meta_uv_s2'],
            $normalizadas['meta_encuentro_s1'],
            $normalizadas['meta_encuentro_s2'],
            $normalizadas['meta_n1_s1'],
            $normalizadas['meta_n1_s2'],
            $normalizadas['meta_n2_s1'],
            $normalizadas['meta_n2_s2'],
            $normalizadas['meta_n3_s1'],
            $normalizadas['meta_n3_s2']
        ]);
    }

    /**
     * Guarda/actualiza la meta de ganados para un ministerio.
     */
    public function setMetaGanados($idMinisterio, $metaGanados) {
        $idMinisterio = (int)$idMinisterio;
        $metaGanados = max(0, (int)$metaGanados);

        if ($idMinisterio <= 0) {
            return false;
        }

        $this->asegurarTablaMetas();

        $sql = "INSERT INTO ministerio_meta (Id_Ministerio, Meta_Ganados)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE Meta_Ganados = VALUES(Meta_Ganados), Fecha_Actualizacion = NOW()";

        return $this->execute($sql, [$idMinisterio, $metaGanados]);
    }
}
