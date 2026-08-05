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
                    Meta_Anual_Ganados INT NOT NULL DEFAULT 0,
                    Meta_Mensual_Ganados INT NOT NULL DEFAULT 0,
                    Meta_Semanal_Ganados INT NOT NULL DEFAULT 0,
                    Anio_Meta INT NOT NULL DEFAULT 0,
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
            'Meta_Anual_Ganados',
            'Meta_Mensual_Ganados',
            'Meta_Semanal_Ganados',
            'Anio_Meta',
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
            'meta_anual' => 0,
            'meta_mensual' => 0,
            'meta_semanal' => 0,
            'anio_meta' => 0,
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
     * Corrige lectura de metas legacy y datos duplicados por semestre.
     */
    private function normalizarMetasDetalleDesdeFila(array $metas, $metaLegacy) {
        $metaLegacy = max(0, (int)$metaLegacy);
        $metaAnual = max(0, (int)($metas['meta_anual'] ?? 0));
        $metaS1 = max(0, (int)($metas['meta_ganados_s1'] ?? 0));
        $metaS2 = max(0, (int)($metas['meta_ganados_s2'] ?? 0));

        // Meta anual guardada al doble de Meta_Ganados legacy (ej. 10300 vs 5150).
        if ($metaAnual > 0 && $metaLegacy > 0 && $metaAnual === ($metaLegacy * 2)) {
            $metaAnual = $metaLegacy;
            $metaS1 = (int)round($metaAnual / 2);
            $metaS2 = max(0, $metaAnual - $metaS1);
        } elseif (
            // Bug histórico: Meta_Ganados se copiaba a S1 y S2 y luego se sumaba como anual (×2).
            $metaLegacy > 0
            && $metaS1 === $metaLegacy
            && $metaS2 === $metaLegacy
            && ($metaAnual === 0 || $metaAnual === ($metaS1 + $metaS2))
        ) {
            $metaAnual = $metaLegacy;
            $metaS1 = (int)round($metaLegacy / 2);
            $metaS2 = max(0, $metaLegacy - $metaS1);
        } elseif ($metaS1 === 0 && $metaS2 === 0 && $metaLegacy > 0 && $metaAnual === 0) {
            // Solo existía la columna legacy: Meta_Ganados era la meta anual completa.
            $metaAnual = $metaLegacy;
            $metaS1 = (int)round($metaLegacy / 2);
            $metaS2 = max(0, $metaLegacy - $metaS1);
        } elseif ($metaAnual === 0 && ($metaS1 > 0 || $metaS2 > 0)) {
            $metaAnual = max(0, $metaS1 + $metaS2);
        }

        $metas['meta_anual'] = $metaAnual;
        $metas['meta_ganados_s1'] = $metaS1;
        $metas['meta_ganados_s2'] = $metaS2;

        // La meta anual configurada debe repartirse en los dos semestres (S1 + S2 = anual).
        if ($metaAnual > 0 && ($metaS1 + $metaS2) !== $metaAnual) {
            $anioDistribucion = (int)($metas['anio_meta'] ?? 0);
            [$metaS1, $metaS2] = self::distribuirMetaAnualEnSemestres($metaAnual, $anioDistribucion);
            $metas['meta_ganados_s1'] = $metaS1;
            $metas['meta_ganados_s2'] = $metaS2;
        }

        return $metas;
    }

    /**
     * Reparte la meta anual en S1 (ene-jun) y S2 (jul-dic) según días del calendario.
     *
     * @return array{0:int,1:int} [meta_s1, meta_s2]
     */
    public static function distribuirMetaAnualEnSemestres($metaAnual, $anioMeta) {
        $metaAnual = max(0, (int)$metaAnual);
        $anioMeta = (int)$anioMeta;
        if ($anioMeta < 2000 || $anioMeta > 2100) {
            $anioMeta = (int)date('Y');
        }

        if ($metaAnual <= 0) {
            return [0, 0];
        }

        $inicio = new DateTime($anioMeta . '-01-01');
        $fin = new DateTime($anioMeta . '-12-31');
        $dias = (int)$inicio->diff($fin)->days + 1;
        $diasS1 = (int)(new DateTime($anioMeta . '-01-01'))->diff(new DateTime($anioMeta . '-06-30'))->days + 1;
        $metaS1 = (int)round($metaAnual * ($diasS1 / $dias));
        $metaS2 = max(0, $metaAnual - $metaS1);

        return [$metaS1, $metaS2];
    }

    /**
     * Meta de ganados del semestre indicado (1 o 2).
     */
    public static function metaGanadosPorSemestre(array $metas, $numeroSemestre) {
        $numeroSemestre = (int)$numeroSemestre === 2 ? 2 : 1;

        return $numeroSemestre === 2
            ? max(0, (int)($metas['meta_ganados_s2'] ?? 0))
            : max(0, (int)($metas['meta_ganados_s1'] ?? 0));
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
            $metas['meta_anual'] = max(0, (int)($row['Meta_Anual_Ganados'] ?? 0));
            $metas['meta_mensual'] = max(0, (int)($row['Meta_Mensual_Ganados'] ?? 0));
            $metas['meta_semanal'] = max(0, (int)($row['Meta_Semanal_Ganados'] ?? 0));
            $metas['anio_meta'] = max(0, (int)($row['Anio_Meta'] ?? 0));
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

            $resultado[$id] = $this->normalizarMetasDetalleDesdeFila($metas, max(0, (int)($row['Meta_Ganados'] ?? 0)));
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

        $metaLegacy = $normalizadas['meta_anual'] > 0
            ? $normalizadas['meta_anual']
            : max($normalizadas['meta_ganados_s1'], $normalizadas['meta_ganados_s2']);

        if ($normalizadas['meta_anual'] > 0) {
            [$metaS1, $metaS2] = self::distribuirMetaAnualEnSemestres(
                $normalizadas['meta_anual'],
                $normalizadas['anio_meta']
            );
            $normalizadas['meta_ganados_s1'] = $metaS1;
            $normalizadas['meta_ganados_s2'] = $metaS2;
        }

        $sql = "INSERT INTO ministerio_meta (
                    Id_Ministerio,
                    Meta_Ganados,
                    Meta_Anual_Ganados,
                    Meta_Mensual_Ganados,
                    Meta_Semanal_Ganados,
                    Anio_Meta,
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
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    Meta_Ganados = VALUES(Meta_Ganados),
                    Meta_Anual_Ganados = VALUES(Meta_Anual_Ganados),
                    Meta_Mensual_Ganados = VALUES(Meta_Mensual_Ganados),
                    Meta_Semanal_Ganados = VALUES(Meta_Semanal_Ganados),
                    Anio_Meta = VALUES(Anio_Meta),
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

        $ok = $this->execute($sql, [
            $idMinisterio,
            $metaLegacy,
            $normalizadas['meta_anual'],
            $normalizadas['meta_mensual'],
            $normalizadas['meta_semanal'],
            $normalizadas['anio_meta'],
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

        if (!$ok) {
            error_log('Ministerio::setMetasDetalle falló para Id_Ministerio=' . $idMinisterio);
        }

        return $ok;
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

    private function asegurarTablaLideresPrincipales() {
        $sql = "CREATE TABLE IF NOT EXISTS ministerio_lider_principal (
                    Id_Ministerio INT NOT NULL,
                    Id_Lider_Principal_1 INT NULL,
                    Id_Lider_Principal_2 INT NULL,
                    Fecha_Actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (Id_Ministerio),
                    KEY idx_lider_principal_1 (Id_Lider_Principal_1),
                    KEY idx_lider_principal_2 (Id_Lider_Principal_2)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->execute($sql);
    }

    public function getLideresPrincipalesByMinisterioIds(array $ministerioIds) {
        $ministerioIds = array_values(array_unique(array_filter(array_map('intval', $ministerioIds), static function($id) {
            return $id >= 0;
        })));

        if (empty($ministerioIds)) {
            return [];
        }

        $this->asegurarTablaLideresPrincipales();

        $placeholders = implode(',', array_fill(0, count($ministerioIds), '?'));
        $sql = "SELECT Id_Ministerio, Id_Lider_Principal_1, Id_Lider_Principal_2
                FROM ministerio_lider_principal
                WHERE Id_Ministerio IN ({$placeholders})";

        $rows = $this->query($sql, $ministerioIds);
        $resultado = [];
        foreach ($rows as $row) {
            $idMinisterio = (int)($row['Id_Ministerio'] ?? 0);
            if ($idMinisterio < 0) {
                continue;
            }

            $resultado[$idMinisterio] = [
                'id_lider_principal_1' => (int)($row['Id_Lider_Principal_1'] ?? 0),
                'id_lider_principal_2' => (int)($row['Id_Lider_Principal_2'] ?? 0),
            ];
        }

        return $resultado;
    }

    public function setLideresPrincipales($idMinisterio, $idLider1, $idLider2) {
        $idMinisterio = (int)$idMinisterio;
        $idLider1 = (int)$idLider1;
        $idLider2 = (int)$idLider2;

        if ($idMinisterio < 0) {
            return false;
        }

        $this->asegurarTablaLideresPrincipales();

        $sql = "INSERT INTO ministerio_lider_principal (Id_Ministerio, Id_Lider_Principal_1, Id_Lider_Principal_2)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    Id_Lider_Principal_1 = VALUES(Id_Lider_Principal_1),
                    Id_Lider_Principal_2 = VALUES(Id_Lider_Principal_2),
                    Fecha_Actualizacion = NOW()";

        return $this->execute($sql, [
            $idMinisterio,
            $idLider1 > 0 ? $idLider1 : null,
            $idLider2 > 0 ? $idLider2 : null,
        ]);
    }
}
