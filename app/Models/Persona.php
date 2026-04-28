<?php
/**
 * Modelo Persona
 */

require_once APP . '/Models/BaseModel.php';

class Persona extends BaseModel {
    protected $table = 'persona';
    protected $primaryKey = 'Id_Persona';
    private $columnasCache = [];
    private $tablasCache = [];

    private function tieneTabla($tabla) {
        $tabla = trim((string)$tabla);
        if ($tabla === '') {
            return false;
        }

        if (array_key_exists($tabla, $this->tablasCache)) {
            return $this->tablasCache[$tabla];
        }

        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tabla]);
            $existe = (bool)$stmt->fetch();
            $this->tablasCache[$tabla] = $existe;
            return $existe;
        } catch (Exception $e) {
            error_log('Error verificando tabla en persona: ' . $e->getMessage());
            $this->tablasCache[$tabla] = false;
            return false;
        }
    }

    public function tieneColumna($columna) {
        $columna = trim((string)$columna);
        if ($columna === '') {
            return false;
        }

        if (array_key_exists($columna, $this->columnasCache)) {
            return $this->columnasCache[$columna];
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute([$columna]);
            $existe = (bool)$stmt->fetch();
            $this->columnasCache[$columna] = $existe;
            return $existe;
        } catch (Exception $e) {
            error_log('Error verificando columna en persona: ' . $e->getMessage());
            $this->columnasCache[$columna] = false;
            return false;
        }
    }

    private function normalizarDocumentoParaComparacion($documento) {
        $documento = strtoupper(trim((string)$documento));
        if ($documento === '') {
            return '';
        }

        return preg_replace('/[^A-Z0-9]/', '', $documento);
    }

    private function normalizarTelefonoParaComparacion($telefono) {
        $telefono = trim((string)$telefono);
        if ($telefono === '') {
            return '';
        }

        return preg_replace('/\D+/', '', $telefono);
    }

    public function findDuplicateByCedulaOrTelefono($numeroDocumento, $telefono, $excludeId = null) {
        $documentoNormalizado = $this->normalizarDocumentoParaComparacion($numeroDocumento);
        $telefonoNormalizado = $this->normalizarTelefonoParaComparacion($telefono);
        $excludeId = $excludeId !== null ? (int)$excludeId : 0;

        $condiciones = [];
        $params = [];

        if ($documentoNormalizado !== '') {
            $condiciones[] = "REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(Numero_Documento, ''))), ' ', ''), '.', ''), '-', '') = ?";
            $params[] = $documentoNormalizado;
        }

        if ($telefonoNormalizado !== '') {
            $condiciones[] = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(Telefono, '')), ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '') = ?";
            $params[] = $telefonoNormalizado;
        }

        if (empty($condiciones)) {
            return null;
        }

        $sql = "SELECT Id_Persona, Nombre, Apellido, Numero_Documento, Telefono
                FROM {$this->table}
                WHERE (" . implode(' OR ', $condiciones) . ")";

        if ($excludeId > 0) {
            $sql .= " AND {$this->primaryKey} <> ?";
            $params[] = $excludeId;
        }

        $sql .= " ORDER BY {$this->primaryKey} DESC LIMIT 1";
        $rows = $this->query($sql, $params);
        return $rows[0] ?? null;
    }

    public function buscarParaInscripcionEscuela($numeroDocumento, $telefono, $nombreCompleto = '') {
        $documentoNormalizado = $this->normalizarDocumentoParaComparacion($numeroDocumento);
        $telefonoNormalizado = $this->normalizarTelefonoParaComparacion($telefono);

        $sqlBase = "SELECT
                        p.Id_Persona,
                        p.Nombre,
                        p.Apellido,
                        p.Id_Rol,
                        p.Genero,
                        p.Telefono,
                        p.Numero_Documento,
                        p.Id_Ministerio,
                        m.Nombre_Ministerio,
                        p.Id_Lider,
                        CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, '')) AS Nombre_Lider
                    FROM {$this->table} p
                    LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                    LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona";

        $params = [];
        $condiciones = [];

        if ($documentoNormalizado !== '') {
            $condiciones[] = "REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(p.Numero_Documento, ''))), ' ', ''), '.', ''), '-', '') = ?";
            $params[] = $documentoNormalizado;
        }

        if ($telefonoNormalizado !== '') {
            $condiciones[] = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(p.Telefono, '')), ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '') = ?";
            $params[] = $telefonoNormalizado;
        }

        if (!empty($condiciones)) {
            $sql = $sqlBase . " WHERE (" . implode(' OR ', $condiciones) . ") ORDER BY p.Id_Persona DESC LIMIT 1";
            $rows = $this->query($sql, $params);
            if (!empty($rows)) {
                return $rows[0];
            }
        }

        $nombreCompleto = trim((string)$nombreCompleto);
        if ($nombreCompleto === '') {
            return null;
        }

        $nombreCompleto = preg_replace('/\s+/', ' ', $nombreCompleto);
        $nombreNormalizado = function_exists('mb_strtoupper') ? mb_strtoupper($nombreCompleto, 'UTF-8') : strtoupper($nombreCompleto);

        $sql = $sqlBase . "
                WHERE UPPER(TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, '')))) = ?
                ORDER BY p.Id_Persona DESC
                LIMIT 1";

        $rows = $this->query($sql, [$nombreNormalizado]);
        return $rows[0] ?? null;
    }

    public function ensureProcesoColumnExists() {
        if ($this->tieneColumna('Proceso')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Proceso ENUM('Ganar','Consolidar','Discipular','Enviar') NULL AFTER Tipo_Reunion";
            $this->db->exec($sql);
            $this->columnasCache['Proceso'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Proceso en persona: ' . $e->getMessage());
            $this->columnasCache['Proceso'] = false;
            return false;
        }
    }

    public function ensureOrigenGanarColumnExists() {
        if ($this->tieneColumna('Origen_Ganar')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Origen_Ganar ENUM('Domingo','Celula') NULL AFTER Proceso";
            $this->db->exec($sql);
            $this->columnasCache['Origen_Ganar'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Origen_Ganar en persona: ' . $e->getMessage());
            $this->columnasCache['Origen_Ganar'] = false;
            return false;
        }
    }

    public function ensureObservacionGanadoEnColumnExists() {
        if ($this->tieneColumna('Observacion_Ganado_En')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Observacion_Ganado_En TEXT NULL AFTER Tipo_Reunion";
            $this->db->exec($sql);
            $this->columnasCache['Observacion_Ganado_En'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Observacion_Ganado_En en persona: ' . $e->getMessage());
            $this->columnasCache['Observacion_Ganado_En'] = false;
            return false;
        }
    }

    public function ensureTipoReunionOtrosValueExists() {
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Tipo_Reunion']);
            $col = $stmt->fetch();
            if (empty($col)) {
                return false;
            }

            $type = (string)($col['Type'] ?? '');
            if (stripos($type, 'enum(') !== 0) {
                return true;
            }

            $matches = [];
            preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $type, $matches);
            $enumValues = array_map(static function($value) {
                return stripcslashes((string)$value);
            }, $matches[1] ?? []);

            if (empty($enumValues)) {
                return true;
            }

            $requeridos = ['Domingo', 'Somos Uno', 'Celula', 'Migrados', 'Otros', 'Asignados'];
            $normalizadosActuales = [];
            foreach ($enumValues as $value) {
                $normalizadosActuales[] = strtolower(trim((string)$value));
            }

            $requiereCambios = false;
            foreach ($requeridos as $requerido) {
                if (!in_array(strtolower($requerido), $normalizadosActuales, true)) {
                    $enumValues[] = $requerido;
                    $requiereCambios = true;
                }
            }

            if (!$requiereCambios) {
                return true;
            }

            $enumSqlValues = array_map([$this->db, 'quote'], $enumValues);
            $nullable = strtoupper((string)($col['Null'] ?? 'YES')) === 'NO' ? 'NOT NULL' : 'NULL';
            $defaultSql = '';

            if (array_key_exists('Default', $col) && $col['Default'] !== null) {
                $defaultSql = ' DEFAULT ' . $this->db->quote((string)$col['Default']);
            }

            $sql = "ALTER TABLE {$this->table} MODIFY COLUMN Tipo_Reunion ENUM(" . implode(',', $enumSqlValues) . ") {$nullable}{$defaultSql}";
            $this->db->exec($sql);
            return true;
        } catch (Exception $e) {
            error_log('No se pudo asegurar el valor Otros en Tipo_Reunion: ' . $e->getMessage());
            return false;
        }
    }

    public function repararTipoReunionOtrosSinDato() {
        if (!$this->tieneColumna('Observacion_Ganado_En')) {
            return false;
        }

        try {
            $sql = "UPDATE {$this->table}
                    SET Tipo_Reunion = 'Otros'
                    WHERE (Tipo_Reunion IS NULL OR TRIM(Tipo_Reunion) = '')
                      AND Observacion_Ganado_En IS NOT NULL
                      AND TRIM(Observacion_Ganado_En) <> ''";

            return $this->execute($sql);
        } catch (Exception $e) {
            error_log('No se pudo reparar Tipo_Reunion sin dato para Otros: ' . $e->getMessage());
            return false;
        }
    }

    public function ensureConvencionColumnExists() {
        if ($this->tieneColumna('Convencion')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Convencion ENUM('Convencion Enero','Convencion Mujeres','Convencion Jovenes','Convencion Hombres') NULL AFTER Tipo_Reunion";
            $this->db->exec($sql);
            $this->columnasCache['Convencion'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Convencion en persona: ' . $e->getMessage());
            $this->columnasCache['Convencion'] = false;
            return false;
        }
    }

    public function ensureEscaleraChecklistColumnExists() {
        if ($this->tieneColumna('Escalera_Checklist')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Escalera_Checklist TEXT NULL AFTER Proceso";
            $this->db->exec($sql);
            $this->columnasCache['Escalera_Checklist'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Escalera_Checklist en persona: ' . $e->getMessage());
            $this->columnasCache['Escalera_Checklist'] = false;
            return false;
        }
    }

    public function ensureFechaAsignacionLiderColumnExists() {
        if ($this->tieneColumna('Fecha_Asignacion_Lider')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Fecha_Asignacion_Lider DATETIME NULL AFTER Id_Lider";
            $this->db->exec($sql);
            $this->columnasCache['Fecha_Asignacion_Lider'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Fecha_Asignacion_Lider en persona: ' . $e->getMessage());
            $this->columnasCache['Fecha_Asignacion_Lider'] = false;
            return false;
        }
    }

    public function ensureCreadoPorColumnExists() {
        if ($this->tieneColumna('Creado_Por')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Creado_Por INT NULL AFTER Fecha_Registro_Unix";
            $this->db->exec($sql);
            $this->columnasCache['Creado_Por'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Creado_Por en persona: ' . $e->getMessage());
            $this->columnasCache['Creado_Por'] = false;
            return false;
        }
    }

    public function ensureCanalCreacionColumnExists() {
        if ($this->tieneColumna('Canal_Creacion')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Canal_Creacion VARCHAR(80) NULL AFTER Creado_Por";
            $this->db->exec($sql);
            $this->columnasCache['Canal_Creacion'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Canal_Creacion en persona: ' . $e->getMessage());
            $this->columnasCache['Canal_Creacion'] = false;
            return false;
        }
    }

    public function ensureEsAntiguoColumnExists() {
        if ($this->tieneColumna('Es_Antiguo')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Es_Antiguo TINYINT(1) NOT NULL DEFAULT 1 AFTER Estado_Cuenta";
            $this->db->exec($sql);
            $this->columnasCache['Es_Antiguo'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Es_Antiguo en persona: ' . $e->getMessage());
            $this->columnasCache['Es_Antiguo'] = false;
            return false;
        }
    }

    public function puedeEditarEscaleraPorRol($idPersona, $filtroRol) {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0) {
            return false;
        }

        $sql = "SELECT 1
                FROM persona p
                WHERE p.Id_Persona = ?
                AND {$filtroRol}
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idPersona]);
        return (bool)$stmt->fetchColumn();
    }

    public function updateEscaleraChecklistYProceso($idPersona, $checklistJson, $proceso = null) {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0) {
            return false;
        }

        if ($this->tieneColumna('Proceso')) {
            $sql = "UPDATE {$this->table} SET Escalera_Checklist = ?, Proceso = ? WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$checklistJson, $proceso, $idPersona]);
        }

        $sql = "UPDATE {$this->table} SET Escalera_Checklist = ? WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$checklistJson, $idPersona]);
    }

    /**
     * NUEVO: obtener personas para la vista de escalera
     */
    public function getPersonasEscalera($filtroRol = '', $etapaFiltro = '') {
        $where = ["(p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)"];

        if (trim((string)$filtroRol) !== '') {
            $where[] = '(' . $filtroRol . ')';
        }

        $etapaFiltro = trim((string)$etapaFiltro);
        if ($etapaFiltro === 'sin_etapa') {
            $where[] = "(p.Proceso IS NULL OR TRIM(p.Proceso) = '')";
        } elseif (in_array($etapaFiltro, ['Ganar', 'Consolidar', 'Discipular', 'Enviar'], true)) {
            $where[] = "p.Proceso = " . $this->db->quote($etapaFiltro);
        }

        $sql = "SELECT
                    p.Id_Persona,
                    p.Nombre,
                    p.Apellido,
                    p.Proceso,
                    p.Escalera_Checklist,
                    CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, '')) AS Nombre_Lider
                FROM persona p
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.Nombre ASC, p.Apellido ASC";

        return $this->query($sql);
    }

    /**
     * NUEVO: totales de personas por etapa para la escalera
     */
    public function getTotalesEscalera($filtroRol = '') {
        $where = ["(p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)"];

        if (trim((string)$filtroRol) !== '') {
            $where[] = '(' . $filtroRol . ')';
        }

        $sql = "SELECT
                    SUM(CASE WHEN p.Proceso = 'Ganar' THEN 1 ELSE 0 END) AS Ganar,
                    SUM(CASE WHEN p.Proceso = 'Consolidar' THEN 1 ELSE 0 END) AS Consolidar,
                    SUM(CASE WHEN p.Proceso = 'Discipular' THEN 1 ELSE 0 END) AS Discipular,
                    SUM(CASE WHEN p.Proceso = 'Enviar' THEN 1 ELSE 0 END) AS Enviar,
                    SUM(CASE WHEN p.Proceso IS NULL OR TRIM(p.Proceso) = '' THEN 1 ELSE 0 END) AS sin_etapa
                FROM persona p
                WHERE " . implode(' AND ', $where);

        $rows = $this->query($sql);
        $row = $rows[0] ?? [];

        return [
            'Ganar' => (int)($row['Ganar'] ?? 0),
            'Consolidar' => (int)($row['Consolidar'] ?? 0),
            'Discipular' => (int)($row['Discipular'] ?? 0),
            'Enviar' => (int)($row['Enviar'] ?? 0),
            'sin_etapa' => (int)($row['sin_etapa'] ?? 0),
        ];
    }

    /**
     * NUEVO: reporte mensual de escalera del éxito por peldaño
     */
    public function getReporteEscaleraMesActual($filtroRol = '', $fechaInicio = null, $fechaFin = null, $idMinisterio = '', $idLider = '', $idCelula = '') {
        $inicioMes = is_string($fechaInicio) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaInicio)
            ? $fechaInicio
            : date('Y-m-01');
        $finMes = is_string($fechaFin) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFin)
            ? $fechaFin
            : date('Y-m-t');

        if (strcmp($inicioMes, $finMes) > 0) {
            [$inicioMes, $finMes] = [$finMes, $inicioMes];
        }

        $where = [
            "(p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)",
            "DATE(p.Fecha_Registro) BETWEEN ? AND ?"
        ];
        $params = [$inicioMes, $finMes];

        if (trim((string)$filtroRol) !== '') {
            $where[] = '(' . $filtroRol . ')';
        }

        if ($idCelula !== null && $idCelula !== '') {
            if ((string)$idCelula === '0') {
                $where[] = 'p.Id_Celula IS NULL';
            } else {
                $where[] = 'p.Id_Celula = ?';
                $params[] = (int)$idCelula;
            }
        }

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $where[] = 'p.Id_Ministerio = ?';
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $where[] = 'p.Id_Lider = ?';
            $params[] = (int)$idLider;
        }

        $sql = "SELECT
                    p.Id_Persona,
                    p.Nombre,
                    p.Apellido,
                    p.Proceso,
                    p.Escalera_Checklist,
                    p.Fecha_Registro,
                    c.Nombre_Celula,
                    m.Nombre_Ministerio,
                    TRIM(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, ''))) AS Nombre_Lider
                FROM {$this->table} p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.Fecha_Registro ASC, p.Id_Persona ASC";

        $rows = $this->query($sql, $params);

        $reporte = [
            'inicio' => $inicioMes,
            'fin' => $finMes,
            'mes_label' => ($inicioMes === $finMes)
                ? date('d/m/Y', strtotime($inicioMes))
                : date('d/m/Y', strtotime($inicioMes)) . ' al ' . date('d/m/Y', strtotime($finMes)),
            'total_personas_mes' => 0,
            'totales_etapa' => [
                'Ganar' => 0,
                'Consolidar' => 0,
                'Discipular' => 0,
                'Enviar' => 0,
                'sin_etapa' => 0,
            ],
            'peldaños' => [
                'Ganar' => [
                    'Primer contacto' => 0,
                    'Asignacion a lideres y ministerio' => 0,
                    'Fonovisita' => 0,
                    'Visita' => 0,
                    'Asignacion a una celula' => 0,
                    'No se dispone' => 0,
                ],
                'Consolidar' => [
                    'Universidad de la vida' => 0,
                    'Encuentro' => 0,
                    'Bautismo' => 0,
                ],
                'Discipular' => [
                    'Capacitacion destino nivel 1' => 0,
                    'Capacitacion destino nivel 2' => 0,
                    'Capacitacion destino nivel 3' => 0,
                ],
                'Enviar' => [
                    'Celula' => 0,
                ],
            ],
            'detalles_etapa' => [
                'Ganar' => [],
                'Consolidar' => [],
                'Discipular' => [],
                'Enviar' => [],
                'sin_etapa' => [],
            ],
            'detalles_peldanos' => [
                'Ganar' => [
                    'Primer contacto' => [],
                    'Asignacion a lideres y ministerio' => [],
                    'Fonovisita' => [],
                    'Visita' => [],
                    'Asignacion a una celula' => [],
                    'No se dispone' => [],
                ],
                'Consolidar' => [
                    'Universidad de la vida' => [],
                    'Encuentro' => [],
                    'Bautismo' => [],
                ],
                'Discipular' => [
                    'Capacitacion destino nivel 1' => [],
                    'Capacitacion destino nivel 2' => [],
                    'Capacitacion destino nivel 3' => [],
                ],
                'Enviar' => [
                    'Celula' => [],
                ],
            ],
        ];

        $ordenEtapas = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];

        $mapaPeldaños = [
            'Ganar' => ['Primer contacto', 'Asignacion a lideres y ministerio', 'Fonovisita', 'Visita', 'Asignacion a una celula', 'No se dispone'],
            'Consolidar' => ['Universidad de la vida', 'Encuentro', 'Bautismo'],
            'Discipular' => ['Capacitacion destino nivel 1', 'Capacitacion destino nivel 2', 'Capacitacion destino nivel 3'],
            'Enviar' => [2 => 'Celula'],
        ];

        foreach ($rows as $persona) {
            $reporte['total_personas_mes']++;

            $proceso = trim((string)($persona['Proceso'] ?? ''));
            if (!in_array($proceso, $ordenEtapas, true)) {
                $proceso = 'sin_etapa';
            }

            $reporte['totales_etapa'][$proceso]++;

            $detallePersona = [
                'Id_Persona' => (int)($persona['Id_Persona'] ?? 0),
                'Nombre' => (string)($persona['Nombre'] ?? ''),
                'Apellido' => (string)($persona['Apellido'] ?? ''),
                'Nombre_Lider' => trim((string)($persona['Nombre_Lider'] ?? '')),
                'Nombre_Celula' => (string)($persona['Nombre_Celula'] ?? ''),
                'Nombre_Ministerio' => (string)($persona['Nombre_Ministerio'] ?? ''),
                'Proceso' => $proceso === 'sin_etapa' ? 'Sin etapa' : $proceso,
                'Fecha_Registro' => (string)($persona['Fecha_Registro'] ?? ''),
            ];
            $reporte['detalles_etapa'][$proceso][] = $detallePersona;

            $checklist = [];
            $rawChecklist = (string)($persona['Escalera_Checklist'] ?? '');

            if ($rawChecklist !== '') {
                $decoded = json_decode($rawChecklist, true);
                if (is_array($decoded)) {
                    $checklist = $decoded;
                }
            }

            foreach ($mapaPeldaños as $etapa => $peldaños) {
                $checksEtapa = $checklist[$etapa] ?? [];

                foreach ($peldaños as $indice => $nombrePeldaño) {
                    $marcado = array_key_exists($indice, $checksEtapa) ? !empty($checksEtapa[$indice]) : false;

                    // Si no hay checklist persistido, la etapa activa arranca con el primer peldaño visible
                    if (!$marcado && $etapa === $proceso && $indice === 0) {
                        $marcado = true;
                    }

                    // Si la persona ya avanzó a una etapa posterior, se consideran completos
                    // los peldaños visibles de las etapas anteriores
                    $indiceEtapaActual = array_search($proceso, $ordenEtapas, true);
                    $indiceEtapaIterada = array_search($etapa, $ordenEtapas, true);

                    if (
                        !$marcado &&
                        $indiceEtapaActual !== false &&
                        $indiceEtapaIterada !== false &&
                        $indiceEtapaActual > $indiceEtapaIterada
                    ) {
                        $marcado = true;
                    }

                    if ($marcado) {
                        $reporte['peldaños'][$etapa][$nombrePeldaño]++;
                        $reporte['detalles_peldanos'][$etapa][$nombrePeldaño][] = $detallePersona;
                    }
                }
            }
        }

        return $reporte;
    }

    /**
     * Obtener personas con líder y ministerio asignados que superaron el límite
     * de horas para registrar el primer contacto.
     */
    public function getCandidatosReasignacionPrimerContacto($horasLimite = 48) {
        $horasLimite = max(1, (int)$horasLimite);

        // Regla de seguridad: sin Fecha_Asignacion_Lider no se ejecuta
        // la reasignación automática para evitar efectos retroactivos.
        if (!$this->tieneColumna('Fecha_Asignacion_Lider')) {
            return [];
        }

        $campoTiempoControl = "p.Fecha_Asignacion_Lider";

        $sql = "SELECT p.Id_Persona, p.Id_Lider, p.Id_Ministerio, p.Fecha_Registro, p.Fecha_Asignacion_Lider, p.Escalera_Checklist, p.Proceso, p.Estado_Cuenta
            FROM {$this->table} p
            WHERE p.Id_Lider IS NOT NULL
              AND p.Id_Ministerio IS NOT NULL
              AND {$campoTiempoControl} IS NOT NULL
              AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
              AND (p.Proceso = 'Ganar' OR p.Proceso IS NULL OR p.Proceso = '')
              AND TIMESTAMPDIFF(HOUR, {$campoTiempoControl}, NOW()) >= ?
            ORDER BY {$campoTiempoControl} ASC, p.Id_Persona ASC";

        return $this->query($sql, [$horasLimite]);
    }

    /**
     * Quitar asignación de líder/ministerio y marcar persona como reasignada.
     */
    public function aplicarReasignacionAutomatica($idPersona, $checklistJson, $proceso = 'Ganar') {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0) {
            return false;
        }

        $camposUpdate = [
            'Id_Lider = NULL',
            'Id_Ministerio = NULL',
            'Escalera_Checklist = ?'
        ];

        if ($this->tieneColumna('Fecha_Asignacion_Lider')) {
            $camposUpdate[] = 'Fecha_Asignacion_Lider = NULL';
        }

        if ($this->tieneColumna('Proceso')) {
            $camposConProceso = $camposUpdate;
            $camposConProceso[] = 'Proceso = ?';

            $sql = "UPDATE {$this->table}
                    SET " . implode(",\n                        ", $camposConProceso) . "
                    WHERE {$this->primaryKey} = ?
                      AND Id_Lider IS NOT NULL
                      AND Id_Ministerio IS NOT NULL";

            return $this->execute($sql, [$checklistJson, $proceso, $idPersona]);
        }

        $sql = "UPDATE {$this->table}
                SET " . implode(",\n                    ", $camposUpdate) . "
                WHERE {$this->primaryKey} = ?
                  AND Id_Lider IS NOT NULL
                  AND Id_Ministerio IS NOT NULL";

        return $this->execute($sql, [$checklistJson, $idPersona]);
    }

    /**
     * Obtener persona por ID con relaciones
     */
    public function getById($id) {
        $joinReporte = '';
        $campoReporte = 'NULL AS Ultimo_Reporte_Celula';

        if ($this->tieneTabla('asistencia') && $this->tieneTabla('celula')) {
            $campoReporte = 'rep.Ultimo_Reporte_Celula';
            $joinReporte = "
                LEFT JOIN (
                    SELECT c.Id_Lider, MAX(a.Fecha_Asistencia) AS Ultimo_Reporte_Celula
                    FROM asistencia a
                    INNER JOIN celula c ON c.Id_Celula = a.Id_Celula
                    WHERE c.Id_Lider IS NOT NULL
                    GROUP BY c.Id_Lider
                ) rep ON rep.Id_Lider = p.Id_Persona";
        }

        $sql = "SELECT p.*, 
                c.Nombre_Celula, 
                r.Nombre_Rol, 
                m.Nombre_Ministerio,
                TRIM(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, ''))) AS Nombre_Lider,
                COALESCE(creador.Usuario, '') AS Usuario_Creador,
                TRIM(CONCAT(COALESCE(creador.Nombre, ''), ' ', COALESCE(creador.Apellido, ''))) AS Nombre_Creador,
                {$campoReporte}
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                LEFT JOIN persona creador ON p.Creado_Por = creador.Id_Persona
                {$joinReporte}
                WHERE p.{$this->primaryKey} = ?";
        $result = $this->query($sql, [$id]);
        return $result[0] ?? null;
    }

    /**
     * Obtener todas las personas con sus relaciones
     */
    public function getAllWithRelations() {
        $sql = "SELECT p.*, 
                c.Nombre_Celula, 
                r.Nombre_Rol, 
                m.Nombre_Ministerio,
                TRIM(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, ''))) AS Nombre_Lider,
                TRIM(CONCAT(COALESCE(creador.Nombre, ''), ' ', COALESCE(creador.Apellido, ''))) AS Nombre_Creador
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                LEFT JOIN persona creador ON p.Creado_Por = creador.Id_Persona
                ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC";
        return $this->query($sql);
    }

    /**
     * Obtener personas con filtros
     */
    public function getWithFilters($idMinisterio = null, $idLider = null) {
        $sql = "SELECT p.*, 
                c.Nombre_Celula, 
                r.Nombre_Rol, 
                m.Nombre_Ministerio,
                TRIM(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, ''))) AS Nombre_Lider,
                TRIM(CONCAT(COALESCE(creador.Nombre, ''), ' ', COALESCE(creador.Apellido, ''))) AS Nombre_Creador
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                LEFT JOIN persona creador ON p.Creado_Por = creador.Id_Persona
                WHERE 1=1";
        
        $params = [];
        
        if ($idMinisterio !== null && $idMinisterio !== '') {
            if ($idMinisterio == '0') {
                $sql .= " AND p.Id_Ministerio IS NULL";
            } else {
                $sql .= " AND p.Id_Ministerio = ?";
                $params[] = $idMinisterio;
            }
        }
        
        if ($idLider !== null && $idLider !== '') {
            if ($idLider == '0') {
                $sql .= " AND p.Id_Lider IS NULL";
            } else {
                $sql .= " AND p.Id_Lider = ?";
                $params[] = $idLider;
            }
        }
        
        $sql .= " ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC";
        
        return $this->query($sql, $params);
    }

    /**
     * Buscar personas por nombre o apellido
     */
    public function search($term) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Nombre LIKE ? OR Apellido LIKE ?
                ORDER BY Apellido, Nombre";
        $searchTerm = "%$term%";
        return $this->query($sql, [$searchTerm, $searchTerm]);
    }

    /**
     * Obtener personas por célula
     */
    public function getByCelula($idCelula) {
        $sql = "SELECT * FROM {$this->table} WHERE Id_Celula = ? ORDER BY Apellido, Nombre";
        return $this->query($sql, [$idCelula]);
    }

    /**
     * Obtener solo personas activas (para reportes/gráficos/células)
     */
    public function getAllActivos() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL
                ORDER BY Apellido, Nombre";
        return $this->query($sql);
    }

    /**
     * Obtener personas activas con aislamiento de rol
     */
    public function getAllActivosWithRole($filtroRol) {
        $sql = "SELECT p.*
                FROM persona p
                WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND $filtroRol
                ORDER BY p.Apellido, p.Nombre";
        return $this->query($sql);
    }

    /**
     * Obtener personas por ministerio
     */
    public function getByMinisterio($idMinisterio) {
        $sql = "SELECT * FROM {$this->table} WHERE Id_Ministerio = ? ORDER BY Apellido, Nombre";
        return $this->query($sql, [$idMinisterio]);
    }

    /**
     * Obtener personas por rol
     */
    public function getByRol($idRol) {
        $sql = "SELECT * FROM {$this->table} WHERE Id_Rol = ? ORDER BY Apellido, Nombre";
        return $this->query($sql, [$idRol]);
    }

    private function getCondicionRolesLiderazgoSql($aliasPersona = 'p', $aliasRol = 'r') {
        $aliasPersona = trim((string)$aliasPersona) ?: 'p';
        $aliasRol = trim((string)$aliasRol) ?: 'r';

        return "(
            LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%pastor%'
            OR LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%lider de celula%'
            OR LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%lider celula%'
            OR LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%lider de 12%'
            OR LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%lider 12%'
            OR LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%lideres de 12%'
        )";
    }

    private function normalizarNombreRol($nombreRol) {
        $nombreRol = strtolower(trim((string)$nombreRol));
        return strtr($nombreRol, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n'
        ]);
    }

    public function getJerarquiaByRol($idRol) {
        $idRol = (int)$idRol;
        if ($idRol <= 0) {
            return 'miembro';
        }

        $sql = "SELECT Nombre_Rol FROM rol WHERE Id_Rol = ? LIMIT 1";
        $rows = $this->query($sql, [$idRol]);
        $nombreRol = $this->normalizarNombreRol($rows[0]['Nombre_Rol'] ?? '');

        if (strpos($nombreRol, 'admin') !== false) {
            return 'administrativo';
        }

        if (strpos($nombreRol, 'pastor') !== false) {
            return 'pastor';
        }

        if (
            $idRol === 8
            || strpos($nombreRol, 'lider de 12') !== false
            || strpos($nombreRol, 'lider 12') !== false
            || strpos($nombreRol, 'lideres de 12') !== false
        ) {
            return 'lider_12';
        }

        if (
            $idRol === 3
            || strpos($nombreRol, 'lider de celula') !== false
            || strpos($nombreRol, 'lider celula') !== false
        ) {
            return 'lider_celula';
        }

        return 'miembro';
    }

    public function contarCoberturaDirectaLiderazgo($idLider, $excludePersonaId = null) {
        $idLider = (int)$idLider;
        $excludePersonaId = $excludePersonaId !== null ? (int)$excludePersonaId : 0;

        if ($idLider <= 0) {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS Total
                FROM {$this->table} p
                LEFT JOIN rol r ON r.Id_Rol = p.Id_Rol
                WHERE p.Id_Lider = ?
                  AND (
                        p.Id_Rol = 3
                        OR p.Id_Rol = 8
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%pastor%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider de celula%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider celula%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider de 12%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider 12%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lideres de 12%'
                  )";

        $params = [$idLider];

        if ($excludePersonaId > 0) {
            $sql .= " AND p.Id_Persona <> ?";
            $params[] = $excludePersonaId;
        }

        $sql .= " LIMIT 1";
        $rows = $this->query($sql, $params);
        return (int)($rows[0]['Total'] ?? 0);
    }

    public function validarAsignacionJerarquica($idLider, $idRolPersona, $excludePersonaId = null) {
        $idLider = (int)$idLider;
        $idRolPersona = (int)$idRolPersona;
        $excludePersonaId = $excludePersonaId !== null ? (int)$excludePersonaId : 0;

        if ($idLider <= 0 || $idRolPersona <= 0) {
            return [
                'ok' => true,
                'message' => ''
            ];
        }

        $lider = $this->getById($idLider);
        if (empty($lider)) {
            return [
                'ok' => false,
                'message' => 'El líder asignado no existe.'
            ];
        }

        $jerarquiaLider = $this->getJerarquiaByRol((int)($lider['Id_Rol'] ?? 0));
        $jerarquiaPersona = $this->getJerarquiaByRol($idRolPersona);

        if (in_array($jerarquiaPersona, ['pastor', 'administrativo'], true)) {
            return [
                'ok' => $idLider <= 0,
                'message' => 'Un pastor o usuario administrativo no debe quedar bajo cobertura de un líder.'
            ];
        }

        if ($jerarquiaLider === 'miembro' || $jerarquiaLider === 'administrativo') {
            return [
                'ok' => false,
                'message' => 'Solo un pastor, líder de 12 o líder de célula puede recibir cobertura.'
            ];
        }

        if ($jerarquiaLider === 'lider_celula' && $jerarquiaPersona !== 'miembro') {
            return [
                'ok' => false,
                'message' => 'Un líder de célula solo puede cubrir personas de su célula, no otros líderes.'
            ];
        }

        if ($jerarquiaLider === 'lider_12' && !in_array($jerarquiaPersona, ['lider_celula', 'miembro'], true)) {
            return [
                'ok' => false,
                'message' => 'Un líder de 12 solo puede cubrir líderes de célula o miembros.'
            ];
        }

        if ($jerarquiaLider === 'pastor' && !in_array($jerarquiaPersona, ['lider_12', 'lider_celula', 'miembro'], true)) {
            return [
                'ok' => false,
                'message' => 'La cobertura pastoral solo aplica a la red ministerial.'
            ];
        }

        $aplicaCupoLiderazgo = in_array($jerarquiaLider, ['pastor', 'lider_12'], true)
            && in_array($jerarquiaPersona, ['lider_12', 'lider_celula'], true);

        if ($aplicaCupoLiderazgo) {
            $totalDirectos = $this->contarCoberturaDirectaLiderazgo($idLider, $excludePersonaId);
            if ($totalDirectos >= 12) {
                return [
                    'ok' => false,
                    'message' => 'Ese líder ya completó su cobertura directa de 12 líderes.'
                ];
            }
        }

        return [
            'ok' => true,
            'message' => ''
        ];
    }

    public function ajustarEscaleraPorRol($idPersona, $idRol = null) {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0 || !$this->tieneColumna('Escalera_Checklist')) {
            return false;
        }

        if ($idRol === null) {
            $persona = $this->getById($idPersona);
            if (empty($persona)) {
                return false;
            }
            $idRol = (int)($persona['Id_Rol'] ?? 0);
        } else {
            $idRol = (int)$idRol;
        }

        if ($idRol <= 0) {
            return false;
        }

        $jerarquia = $this->getJerarquiaByRol($idRol);
        $debeAutoCompletar = in_array($jerarquia, ['pastor', 'lider_12', 'lider_celula'], true);
        if (!$debeAutoCompletar) {
            return true;
        }

        $checklist = [
            'Ganar' => [true, true, true, true, true, false],
            'Consolidar' => [true, true, true],
            'Discipular' => [true, true, true],
            'Enviar' => [true, true, true],
            '_meta' => [
                'no_disponible_observacion' => '',
                'convenciones' => [],
                'reasignado_automatico' => false,
                'reasignado_automatico_at' => '',
                'reasignado_automatico_motivo' => '',
                'reasignado_manual' => false,
                'reasignado_manual_at' => '',
                'reasignado_manual_motivo' => ''
            ]
        ];

        $checklistJson = json_encode($checklist, JSON_UNESCAPED_UNICODE);
        if ($checklistJson === false) {
            return false;
        }

        if ($this->tieneColumna('Proceso')) {
            $sql = "UPDATE {$this->table}
                    SET Escalera_Checklist = ?, Proceso = 'Enviar'
                    WHERE {$this->primaryKey} = ?";
            return (bool)$this->execute($sql, [$checklistJson, $idPersona]);
        }

        $sql = "UPDATE {$this->table}
                SET Escalera_Checklist = ?
                WHERE {$this->primaryKey} = ?";
        return (bool)$this->execute($sql, [$checklistJson, $idPersona]);
    }

    /**
     * Obtener personas con perfil de liderazgo/pastorado.
     */
    public function getLideresYPastores() {
        $condicionRoles = $this->getCondicionRolesLiderazgoSql('p', 'r');
        $sql = "SELECT DISTINCT p.*
                FROM {$this->table} p
                LEFT JOIN rol r ON r.Id_Rol = p.Id_Rol
                WHERE {$condicionRoles}
                ORDER BY p.Apellido, p.Nombre";
        return $this->query($sql);
    }

    /**
     * Obtener líderes/pastores por ministerio.
     */
    public function getLideresByMinisterio($idMinisterio) {
        $condicionRoles = $this->getCondicionRolesLiderazgoSql('p', 'r');
        $sql = "SELECT DISTINCT p.*
                FROM {$this->table} p
                LEFT JOIN rol r ON r.Id_Rol = p.Id_Rol
                WHERE p.Id_Ministerio = ?
                  AND {$condicionRoles}
                ORDER BY p.Apellido, p.Nombre";
        return $this->query($sql, [$idMinisterio]);
    }

    /**
     * Obtener personas con rol Líder de 12
     */
    public function getLideres12() {
        $sql = "SELECT * FROM {$this->table} WHERE Id_Rol = 8 ORDER BY Apellido, Nombre";
        return $this->query($sql);
    }

    /**
     * Obtener estadísticas de almas ganadas por ministerio
     * Agrupa por ministerio y género en un rango de fechas
     */
    public function getAlmasGanadasPorMinisterio($fechaInicio, $fechaFin) {
        $sql = "SELECT 
                    COALESCE(m.Nombre_Ministerio, 'Sin Ministerio') as Nombre_Ministerio,
                    m.Id_Ministerio,
                    COUNT(*) as Total,
                    SUM(CASE WHEN p.Genero = 'Hombre' THEN 1 ELSE 0 END) as Hombres,
                    SUM(CASE WHEN p.Genero = 'Mujer' THEN 1 ELSE 0 END) as Mujeres,
                    SUM(CASE WHEN p.Genero = 'Joven Hombre' THEN 1 ELSE 0 END) as Jovenes_Hombres,
                    SUM(CASE WHEN p.Genero = 'Joven Mujer' THEN 1 ELSE 0 END) as Jovenes_Mujeres
                FROM persona p
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                GROUP BY m.Id_Ministerio, m.Nombre_Ministerio
                HAVING Total > 0
                ORDER BY m.Nombre_Ministerio";
        return $this->query($sql, [$fechaInicio, $fechaFin]);
    }

    /**
     * Autenticar usuario
     */
    public function autenticar($usuario, $contrasena) {
        $sql = "SELECT p.*, r.Nombre_Rol, p.Id_Ministerio 
                FROM persona p 
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol 
                WHERE p.Usuario = ?";
        $result = $this->query($sql, [$usuario]);

        if (!empty($result)) {
            $user = $result[0];

            $hashAlmacenado = $user['Contrasena'] ?? '';
            if ($hashAlmacenado === '') {
                return null;
            }

            if (password_verify($contrasena, $hashAlmacenado)) {
                return $user;
            }

            if (hash_equals((string) $hashAlmacenado, (string) $contrasena)) {
                $this->setUsuario($user['Id_Persona'], $user['Usuario'], $contrasena);
                return $user;
            }
        }

        return null;
    }

    /**
     * Actualizar último acceso
     */
    public function actualizarUltimoAcceso($idPersona) {
        try {
            $sql = "UPDATE persona SET Ultimo_Acceso = NOW() WHERE Id_Persona = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$idPersona]);
        } catch (Exception $e) {
            error_log("Error actualizando último acceso: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener permisos por rol
     */
    public function getPermisosPorRol($idRol) {
        $sql = "SELECT * FROM permisos WHERE Id_Rol = ?";
        return $this->query($sql, [$idRol]);
    }

    /**
     * Crear o actualizar usuario
     */
    public function setUsuario($idPersona, $usuario, $contrasena = null) {
        if ($contrasena) {
            $hash = password_hash($contrasena, PASSWORD_BCRYPT);
            $sql = "UPDATE persona SET Usuario = ?, Contrasena = ? WHERE Id_Persona = ?";
            return $this->execute($sql, [$usuario, $hash, $idPersona]);
        } else {
            $sql = "UPDATE persona SET Usuario = ? WHERE Id_Persona = ?";
            return $this->execute($sql, [$usuario, $idPersona]);
        }
    }

    /**
     * Listar personas que ya tienen cuenta de acceso en el modelo legado.
     */
    public function getPersonasConUsuario() {
        $sql = "SELECT
                    p.Id_Persona,
                    p.Usuario,
                    p.Estado_Cuenta,
                    p.Ultimo_Acceso,
                    p.Id_Rol,
                    p.Id_Ministerio,
                    p.Nombre,
                    p.Apellido,
                    r.Nombre_Rol,
                    m.Nombre_Ministerio
                FROM persona p
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                WHERE p.Usuario IS NOT NULL
                  AND TRIM(p.Usuario) <> ''
                ORDER BY p.Usuario ASC, p.Id_Persona ASC";

        return $this->query($sql);
    }

    public function getByNumeroDocumento($numeroDocumento) {
        $documentoNormalizado = $this->normalizarDocumentoParaComparacion($numeroDocumento);
        if ($documentoNormalizado === '') {
            return null;
        }

        $sql = "SELECT p.*, r.Nombre_Rol, m.Nombre_Ministerio
                FROM {$this->table} p
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                WHERE REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(p.Numero_Documento, ''))), ' ', ''), '.', ''), '-', '') = ?
                LIMIT 1";

        $rows = $this->query($sql, [$documentoNormalizado]);
        return $rows[0] ?? null;
    }

    public function existeUsuario($usuario, $excludePersonaId = null) {
        $usuario = trim((string)$usuario);
        if ($usuario === '') {
            return false;
        }

        $sql = "SELECT Id_Persona FROM {$this->table} WHERE Usuario = ?";
        $params = [$usuario];

        $excludePersonaId = $excludePersonaId !== null ? (int)$excludePersonaId : 0;
        if ($excludePersonaId > 0) {
            $sql .= " AND Id_Persona <> ?";
            $params[] = $excludePersonaId;
        }

        $sql .= ' LIMIT 1';
        $rows = $this->query($sql, $params);
        return !empty($rows);
    }

    /**
     * Cambiar estado de cuenta
     */
    public function cambiarEstado($idPersona, $estado) {
        $sql = "UPDATE persona SET Estado_Cuenta = ? WHERE Id_Persona = ?";
        return $this->execute($sql, [$estado, $idPersona]);
    }

    /**
     * Contar personas con acciones pendientes en Ganar respetando aislamiento por rol.
     */
    public function contarPendientesPorConectarWithRole($filtroRol) {
        $sql = "SELECT COUNT(*) AS total
                FROM persona p
                LEFT JOIN rol r ON r.Id_Rol = p.Id_Rol
                WHERE $filtroRol
                  AND p.Id_Ministerio IS NOT NULL
                  AND (p.Id_Lider IS NULL OR p.Id_Celula IS NULL)
                  AND (
                        p.Id_Rol IS NULL
                        OR (
                            LOWER(COALESCE(r.Nombre_Rol, '')) NOT LIKE '%pastor%'
                            AND LOWER(COALESCE(r.Nombre_Rol, '')) NOT LIKE '%lider de 12%'
                            AND LOWER(COALESCE(r.Nombre_Rol, '')) NOT LIKE '%lider 12%'
                            AND LOWER(COALESCE(r.Nombre_Rol, '')) NOT LIKE '%lideres de 12%'
                            AND LOWER(COALESCE(r.Nombre_Rol, '')) NOT LIKE '%lider de celula%'
                            AND LOWER(COALESCE(r.Nombre_Rol, '')) NOT LIKE '%lider celula%'
                        )
                  )";

        $resultado = $this->query($sql);
        if (empty($resultado)) {
            return 0;
        }

        return (int)($resultado[0]['total'] ?? 0);
    }

    public function contarNuevasAlmasGanadasWithRole($filtroRol) {
        $sql = "SELECT COUNT(*) AS total
                FROM persona p
                WHERE $filtroRol
                  AND COALESCE(p.Es_Antiguo, 0) = 0
                  AND (p.Id_Ministerio IS NULL OR p.Id_Lider IS NULL OR p.Id_Celula IS NULL)";

        $resultado = $this->query($sql);
        if (empty($resultado)) {
            return 0;
        }

        return (int)($resultado[0]['total'] ?? 0);
    }

    public function contarPendientesGanarWithRole($filtroRol) {
        $sql = "SELECT COUNT(*) AS total
                FROM persona p
                WHERE $filtroRol
                  AND (p.Id_Ministerio IS NULL OR p.Id_Lider IS NULL OR p.Id_Celula IS NULL)";

        $resultado = $this->query($sql);
        if (empty($resultado)) {
            return 0;
        }

        return (int)($resultado[0]['total'] ?? 0);
    }

    /**
     * Obtener todas las personas con aislamiento de rol
     */
    public function getAllWithRole($filtroRol, $soloGanar = false, $estadoCuenta = null, $idCelula = null, $proceso = null, $origen = null, $fechaInicioRegistro = null, $fechaFinRegistro = null) {
        $sql = "SELECT p.*, 
                c.Nombre_Celula, 
                r.Nombre_Rol, 
                m.Nombre_Ministerio,
                TRIM(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, ''))) AS Nombre_Lider,
                COALESCE(creador.Usuario, '') AS Usuario_Creador,
                TRIM(CONCAT(COALESCE(creador.Nombre, ''), ' ', COALESCE(creador.Apellido, ''))) AS Nombre_Creador
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                LEFT JOIN persona creador ON p.Creado_Por = creador.Id_Persona
                WHERE $filtroRol";

        if ($soloGanar === true) {
            $sql .= " AND (p.Id_Ministerio IS NULL OR p.Id_Lider IS NULL OR p.Id_Celula IS NULL)";
        } elseif ($soloGanar === false) {
            $sql .= " AND p.Id_Ministerio IS NOT NULL AND p.Id_Lider IS NOT NULL AND p.Id_Celula IS NOT NULL";
        }

        $params = [];

        if ($idCelula !== null && $idCelula !== '') {
            if ((string)$idCelula === '0') {
                $sql .= " AND p.Id_Celula IS NULL";
            } else {
                $sql .= " AND p.Id_Celula = ?";
                $params[] = $idCelula;
            }
        }

        if ($estadoCuenta !== null && $estadoCuenta !== '') {
            if ($estadoCuenta === 'Activo') {
                $sql .= " AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)";
            } else {
                $sql .= " AND p.Estado_Cuenta = ?";
                $params[] = $estadoCuenta;
            }
        }

        if ($proceso !== null && $proceso !== '') {
            $sql .= " AND p.Proceso = ?";
            $params[] = $proceso;
        }

        if ($origen !== null && $origen !== '') {
            $tipoReunionExpr = "LOWER(TRIM(COALESCE(p.Tipo_Reunion, '')))";
            $invitadoExpr = "TRIM(COALESCE(p.Invitado_Por, ''))";
            $esCelulaExpr = "({$tipoReunionExpr} LIKE '%celula%' OR {$tipoReunionExpr} LIKE '%célula%')";
            $esIglesiaExpr = "(NOT {$esCelulaExpr})";
            $tieneAsignacionExpr = "((p.Id_Lider IS NOT NULL AND p.Id_Lider > 0) OR (p.Id_Ministerio IS NOT NULL AND p.Id_Ministerio > 0))";
            $esAsignadoExpr = "({$esIglesiaExpr} AND {$invitadoExpr} = '' AND {$tieneAsignacionExpr})";

            if ($origen === 'celula') {
                $sql .= " AND {$esCelulaExpr}";
            } elseif ($origen === 'domingo') {
                $sql .= " AND {$esIglesiaExpr} AND NOT {$esAsignadoExpr}";
            } elseif ($origen === 'asignados') {
                $sql .= " AND {$esAsignadoExpr}";
            }
        }

        if ($fechaInicioRegistro !== null && $fechaInicioRegistro !== '' && $fechaFinRegistro !== null && $fechaFinRegistro !== '') {
            $sql .= " AND DATE(p.Fecha_Registro) BETWEEN ? AND ?";
            $params[] = $fechaInicioRegistro;
            $params[] = $fechaFinRegistro;
        }

        $sql .= "
                ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC";
        return $this->query($sql, $params);
    }

    /**
     * Obtener personas registradas desde el formulario público de Escuelas de Formación (Universidad de la Vida)
     */
    public function getPersonasUniversidadVida($filtroRol = '') {
        $canalFormacion = 'Escuelas Formacion (Formulario publico)';
        $where = [
            "(p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)",
            "p.Canal_Creacion = ?",
        ];
        $params = [$canalFormacion];

        if (trim((string)$filtroRol) !== '') {
            $where[] = '(' . $filtroRol . ')';
        }

        $sql = "SELECT p.*,
                    c.Nombre_Celula,
                    r.Nombre_Rol,
                    m.Nombre_Ministerio,
                    TRIM(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, ''))) AS Nombre_Lider
                FROM {$this->table} p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC";

        return $this->query($sql, $params);
    }

    /**
     * Obtener personas con filtros y aislamiento de rol
     */
    public function getWithFiltersAndRole($filtroRol, $idMinisterio = null, $idLider = null, $soloGanar = false, $estadoCuenta = null, $idCelula = null, $proceso = null, $origen = null, $fechaInicioRegistro = null, $fechaFinRegistro = null) {
        $sql = "SELECT p.*, 
                c.Nombre_Celula, 
                r.Nombre_Rol, 
                m.Nombre_Ministerio,
                TRIM(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, ''))) AS Nombre_Lider,
                COALESCE(creador.Usuario, '') AS Usuario_Creador,
                TRIM(CONCAT(COALESCE(creador.Nombre, ''), ' ', COALESCE(creador.Apellido, ''))) AS Nombre_Creador
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                LEFT JOIN persona creador ON p.Creado_Por = creador.Id_Persona
                WHERE $filtroRol";

        if ($soloGanar === true) {
            $sql .= " AND (p.Id_Ministerio IS NULL OR p.Id_Lider IS NULL OR p.Id_Celula IS NULL)";
        } elseif ($soloGanar === false) {
            $sql .= " AND p.Id_Ministerio IS NOT NULL AND p.Id_Lider IS NOT NULL AND p.Id_Celula IS NOT NULL";
        }
        
        $params = [];
        
        if ($idMinisterio !== null && $idMinisterio !== '') {
            if ($idMinisterio == '0') {
                $sql .= " AND p.Id_Ministerio IS NULL";
            } else {
                $sql .= " AND p.Id_Ministerio = ?";
                $params[] = $idMinisterio;
            }
        }
        
        if ($idLider !== null && $idLider !== '') {
            if ($idLider == '0') {
                $sql .= " AND p.Id_Lider IS NULL";
            } else {
                $sql .= " AND p.Id_Lider = ?";
                $params[] = $idLider;
            }
        }

        if ($idCelula !== null && $idCelula !== '') {
            if ((string)$idCelula === '0') {
                $sql .= " AND p.Id_Celula IS NULL";
            } else {
                $sql .= " AND p.Id_Celula = ?";
                $params[] = $idCelula;
            }
        }

        if ($estadoCuenta !== null && $estadoCuenta !== '') {
            if ($estadoCuenta === 'Activo') {
                $sql .= " AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)";
            } else {
                $sql .= " AND p.Estado_Cuenta = ?";
                $params[] = $estadoCuenta;
            }
        }

        if ($proceso !== null && $proceso !== '') {
            $sql .= " AND p.Proceso = ?";
            $params[] = $proceso;
        }

        if ($origen !== null && $origen !== '') {
            $tipoReunionExpr = "LOWER(TRIM(COALESCE(p.Tipo_Reunion, '')))";
            $invitadoExpr = "TRIM(COALESCE(p.Invitado_Por, ''))";
            $esCelulaExpr = "({$tipoReunionExpr} LIKE '%celula%' OR {$tipoReunionExpr} LIKE '%célula%')";
            $esIglesiaExpr = "(NOT {$esCelulaExpr})";
            $tieneAsignacionExpr = "((p.Id_Lider IS NOT NULL AND p.Id_Lider > 0) OR (p.Id_Ministerio IS NOT NULL AND p.Id_Ministerio > 0))";
            $esAsignadoExpr = "({$esIglesiaExpr} AND {$invitadoExpr} = '' AND {$tieneAsignacionExpr})";

            if ($origen === 'celula') {
                $sql .= " AND {$esCelulaExpr}";
            } elseif ($origen === 'domingo') {
                $sql .= " AND {$esIglesiaExpr} AND NOT {$esAsignadoExpr}";
            } elseif ($origen === 'asignados') {
                $sql .= " AND {$esAsignadoExpr}";
            }
        }

        if ($fechaInicioRegistro !== null && $fechaInicioRegistro !== '' && $fechaFinRegistro !== null && $fechaFinRegistro !== '') {
            $sql .= " AND DATE(p.Fecha_Registro) BETWEEN ? AND ?";
            $params[] = $fechaInicioRegistro;
            $params[] = $fechaFinRegistro;
        }

        $sql .= " ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC";
        
        return $this->query($sql, $params);
    }

    /**
     * Obtener almas ganadas por ministerio con aislamiento de rol
     */
    public function getAlmasGanadasPorMinisterioWithRole($fechaInicio, $fechaFin, $filtroRol, $idMinisterio = '', $idLider = '') {
        $filtroNuevas = $this->tieneColumna('Es_Antiguo') ? " AND p.Es_Antiguo = 0" : '';
        $sql = "SELECT 
                    COALESCE(m.Nombre_Ministerio, 'Sin Ministerio') as Nombre_Ministerio,
                    m.Id_Ministerio,
                    COUNT(*) as Total,
                    SUM(CASE WHEN p.Genero = 'Hombre' THEN 1 ELSE 0 END) as Hombres,
                    SUM(CASE WHEN p.Genero = 'Mujer' THEN 1 ELSE 0 END) as Mujeres,
                    SUM(CASE WHEN p.Genero = 'Joven Hombre' THEN 1 ELSE 0 END) as Jovenes_Hombres,
                    SUM(CASE WHEN p.Genero = 'Joven Mujer' THEN 1 ELSE 0 END) as Jovenes_Mujeres
                FROM persona p
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                AND $filtroRol" . $filtroNuevas;

        $params = [$fechaInicio, $fechaFin];

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $sql .= " AND p.Id_Ministerio = ?";
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $sql .= " AND p.Id_Lider = ?";
            $params[] = (int)$idLider;
        }

        $sql .= "
                GROUP BY m.Id_Ministerio, m.Nombre_Ministerio
                HAVING Total > 0
                ORDER BY m.Nombre_Ministerio";
        return $this->query($sql, $params);
    }

    /**
     * Resumen de etapas del proceso de ganar por período.
     */
    public function getResumenProcesoGanarWithRole($fechaInicio, $fechaFin, $filtroRol, $idCelula = '', $idMinisterio = '', $idLider = '') {
        if (!$this->tieneColumna('Proceso')) {
            return [
                'Ganar' => 0,
                'Consolidar' => 0,
                'Discipular' => 0,
                'Enviar' => 0,
                'Sin_Proceso' => 0,
                'Total' => 0
            ];
        }

        $filtroNuevas = $this->tieneColumna('Es_Antiguo') ? " AND p.Es_Antiguo = 0" : '';
        $sql = "SELECT
                    SUM(CASE WHEN p.Proceso = 'Ganar' THEN 1 ELSE 0 END) AS Ganar,
                    SUM(CASE WHEN p.Proceso = 'Consolidar' THEN 1 ELSE 0 END) AS Consolidar,
                    SUM(CASE WHEN p.Proceso = 'Discipular' THEN 1 ELSE 0 END) AS Discipular,
                    SUM(CASE WHEN p.Proceso = 'Enviar' THEN 1 ELSE 0 END) AS Enviar,
                    SUM(CASE WHEN p.Proceso IS NULL OR p.Proceso = '' THEN 1 ELSE 0 END) AS Sin_Proceso,
                    COUNT(*) AS Total
                FROM persona p
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                AND $filtroRol" . $filtroNuevas;

        $params = [$fechaInicio, $fechaFin];

        if ($idCelula !== null && $idCelula !== '') {
            if ((string)$idCelula === '0') {
                $sql .= " AND p.Id_Celula IS NULL";
            } else {
                $sql .= " AND p.Id_Celula = ?";
                $params[] = (int)$idCelula;
            }
        }

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $sql .= " AND p.Id_Ministerio = ?";
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $sql .= " AND p.Id_Lider = ?";
            $params[] = (int)$idLider;
        }

        $rows = $this->query($sql, $params);
        $row = $rows[0] ?? [];

        return [
            'Ganar' => (int)($row['Ganar'] ?? 0),
            'Consolidar' => (int)($row['Consolidar'] ?? 0),
            'Discipular' => (int)($row['Discipular'] ?? 0),
            'Enviar' => (int)($row['Enviar'] ?? 0),
            'Sin_Proceso' => (int)($row['Sin_Proceso'] ?? 0),
            'Total' => (int)($row['Total'] ?? 0)
        ];
    }

    public function getResumenGanadosOrigenWithRole($fechaInicio, $fechaFin, $filtroRol, $idMinisterio = '', $idLider = '') {
        $tipoReunionExpr = "LOWER(TRIM(COALESCE(p.Tipo_Reunion, '')))";
        $invitadoExpr = "TRIM(COALESCE(p.Invitado_Por, ''))";
        $filtroNuevas = $this->tieneColumna('Es_Antiguo') ? " AND p.Es_Antiguo = 0" : '';

        $esCelulaExpr = "({$tipoReunionExpr} LIKE '%celula%' OR {$tipoReunionExpr} LIKE '%célula%')";
        $esIglesiaExpr = "(NOT {$esCelulaExpr})";
        $tieneAsignacionExpr = "((p.Id_Lider IS NOT NULL AND p.Id_Lider > 0) OR (p.Id_Ministerio IS NOT NULL AND p.Id_Ministerio > 0))";

        $sql = "SELECT
                    SUM(CASE WHEN {$tipoReunionExpr} LIKE '%celula%' THEN 1 ELSE 0 END) AS Ganados_Celula,
                    SUM(CASE WHEN {$esIglesiaExpr} THEN 1 ELSE 0 END) AS Ganados_Iglesia,
                    SUM(CASE WHEN {$esIglesiaExpr} AND {$invitadoExpr} = '' AND {$tieneAsignacionExpr} THEN 1 ELSE 0 END) AS Asignados,
                    COUNT(*) AS Total
                FROM persona p
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                AND $filtroRol" . $filtroNuevas;

        $params = [$fechaInicio, $fechaFin];

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $sql .= " AND p.Id_Ministerio = ?";
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $sql .= " AND p.Id_Lider = ?";
            $params[] = (int)$idLider;
        }

        $rows = $this->query($sql, $params);
        $row = $rows[0] ?? [];

        return [
            'Ganados_Celula' => (int)($row['Ganados_Celula'] ?? 0),
            'Ganados_Iglesia' => (int)($row['Ganados_Iglesia'] ?? 0),
            'Ganados_Domingo' => (int)($row['Ganados_Iglesia'] ?? 0),
            'Asignados' => (int)($row['Asignados'] ?? 0),
            'Total' => (int)($row['Total'] ?? 0)
        ];
    }

    public function getDetalleGanadosOrigenWithRole($fechaInicio, $fechaFin, $filtroRol, $origen, $idMinisterio = '', $idLider = '') {
        $tipoReunionExpr = "LOWER(TRIM(COALESCE(p.Tipo_Reunion, '')))";
        $invitadoExpr = "TRIM(COALESCE(p.Invitado_Por, ''))";
        $filtroNuevas = $this->tieneColumna('Es_Antiguo') ? " AND p.Es_Antiguo = 0" : '';

        $sql = "SELECT
                    p.Id_Persona,
                    p.Nombre,
                    p.Apellido,
                    p.Fecha_Registro,
                    p.Tipo_Reunion,
                    COALESCE(c.Nombre_Celula, 'Sin célula') AS Nombre_Celula,
                    COALESCE(m.Nombre_Ministerio, 'Sin ministerio') AS Nombre_Ministerio,
                    COALESCE(CONCAT(lid.Nombre, ' ', lid.Apellido), 'Sin líder') AS Nombre_Lider
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                AND $filtroRol" . $filtroNuevas;

        $params = [$fechaInicio, $fechaFin];

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $sql .= " AND p.Id_Ministerio = ?";
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $sql .= " AND p.Id_Lider = ?";
            $params[] = (int)$idLider;
        }

        $esCelulaExpr = "({$tipoReunionExpr} LIKE '%celula%' OR {$tipoReunionExpr} LIKE '%célula%')";
        $esIglesiaExpr = "(NOT {$esCelulaExpr})";
        $tieneAsignacionExpr = "((p.Id_Lider IS NOT NULL AND p.Id_Lider > 0) OR (p.Id_Ministerio IS NOT NULL AND p.Id_Ministerio > 0))";

        if ($origen === 'celula') {
            $sql .= " AND {$esCelulaExpr}";
        } elseif ($origen === 'iglesia' || $origen === 'domingo') {
            $sql .= " AND {$esIglesiaExpr}";
        } elseif ($origen === 'asignados') {
            $sql .= " AND {$esIglesiaExpr} AND {$invitadoExpr} = '' AND {$tieneAsignacionExpr}";
        } else {
            return [];
        }

        $sql .= " ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC";

        return $this->query($sql, $params);
    }

    /**
     * Resumen por ministerio para el reporte de fin de semana anterior.
     * Ganados: domingo con invitador.
     * Asignados: domingo sin invitador.
     * Por verificar: domingo sin líder asignado.
     */
    public function getResumenGanadosFinSemanaAnteriorPorMinisterioWithRole($fechaInicio, $fechaFin, $filtroRol, $idMinisterio = '', $idLider = '') {
        $tipoReunionExpr = "LOWER(TRIM(COALESCE(p.Tipo_Reunion, '')))";
        $invitadoExpr = "TRIM(COALESCE(p.Invitado_Por, ''))";
        $filtroNuevas = $this->tieneColumna('Es_Antiguo') ? " AND p.Es_Antiguo = 0" : '';

        $esCelulaExpr = "({$tipoReunionExpr} LIKE '%celula%' OR {$tipoReunionExpr} LIKE '%célula%')";
        $esIglesiaExpr = "(NOT {$esCelulaExpr})";
        $tieneAsignacionExpr = "((p.Id_Lider IS NOT NULL AND p.Id_Lider > 0) OR (p.Id_Ministerio IS NOT NULL AND p.Id_Ministerio > 0))";

        $sql = "SELECT
                    COALESCE(m.Nombre_Ministerio, 'Sin ministerio') AS Nombre_Ministerio,
                    SUM(CASE WHEN {$esIglesiaExpr} AND {$invitadoExpr} <> '' THEN 1 ELSE 0 END) AS Ganados,
                    SUM(CASE WHEN {$esIglesiaExpr} AND {$invitadoExpr} = '' AND {$tieneAsignacionExpr} THEN 1 ELSE 0 END) AS Asignados,
                    SUM(CASE WHEN {$esIglesiaExpr} AND (p.Id_Lider IS NULL OR p.Id_Lider = 0) THEN 1 ELSE 0 END) AS Por_Verificar,
                    SUM(CASE WHEN {$esIglesiaExpr} THEN 1 ELSE 0 END) AS Total_Iglesia
                FROM persona p
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                AND $filtroRol" . $filtroNuevas;

        $params = [$fechaInicio, $fechaFin];

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $sql .= " AND p.Id_Ministerio = ?";
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $sql .= " AND p.Id_Lider = ?";
            $params[] = (int)$idLider;
        }

        $sql .= "
                GROUP BY m.Id_Ministerio, m.Nombre_Ministerio
            HAVING Total_Iglesia > 0
                ORDER BY m.Nombre_Ministerio";

        $rows = $this->query($sql, $params);

        $resultadoRows = [];
        $totales = [
            'ganados' => 0,
            'asignados' => 0,
            'por_verificar' => 0,
            'total_iglesia' => 0,
            'total_domingo' => 0
        ];

        foreach ($rows as $row) {
            $item = [
                'ministerio' => (string)($row['Nombre_Ministerio'] ?? 'Sin ministerio'),
                'ganados' => (int)($row['Ganados'] ?? 0),
                'asignados' => (int)($row['Asignados'] ?? 0),
                'por_verificar' => (int)($row['Por_Verificar'] ?? 0),
                'total_iglesia' => (int)($row['Total_Iglesia'] ?? 0),
                'total_domingo' => (int)($row['Total_Iglesia'] ?? 0)
            ];

            $resultadoRows[] = $item;
            $totales['ganados'] += $item['ganados'];
            $totales['asignados'] += $item['asignados'];
            $totales['por_verificar'] += $item['por_verificar'];
            $totales['total_iglesia'] += $item['total_iglesia'];
            $totales['total_domingo'] += $item['total_domingo'];
        }

        return [
            'rows' => $resultadoRows,
            'totales' => $totales
        ];
    }

    public function getDetalleGanadosFinSemanaAnteriorPorMinisterioWithRole($fechaInicio, $fechaFin, $filtroRol, $idMinisterio = '', $idLider = '') {
        $tipoReunionExpr = "LOWER(TRIM(COALESCE(p.Tipo_Reunion, '')))";
        $filtroNuevas = $this->tieneColumna('Es_Antiguo') ? " AND p.Es_Antiguo = 0" : '';

        $esCelulaExpr = "({$tipoReunionExpr} LIKE '%celula%' OR {$tipoReunionExpr} LIKE '%célula%')";
        $esIglesiaExpr = "(NOT {$esCelulaExpr})";

        $sql = "SELECT
                    p.Id_Persona,
                    p.Nombre,
                    p.Apellido,
                    p.Fecha_Registro,
                    p.Proceso,
                    COALESCE(m.Nombre_Ministerio, 'Sin ministerio') AS Nombre_Ministerio,
                    COALESCE(c.Nombre_Celula, 'Sin célula') AS Nombre_Celula,
                    TRIM(CONCAT(COALESCE(l.Nombre, ''), ' ', COALESCE(l.Apellido, ''))) AS Nombre_Lider
                FROM persona p
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN persona l ON p.Id_Lider = l.Id_Persona
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                  AND {$esIglesiaExpr}
                  AND $filtroRol" . $filtroNuevas;

        $params = [$fechaInicio, $fechaFin];

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $sql .= " AND p.Id_Ministerio = ?";
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $sql .= " AND p.Id_Lider = ?";
            $params[] = (int)$idLider;
        }

        $sql .= "
                ORDER BY Nombre_Ministerio ASC, p.Fecha_Registro DESC, p.Id_Persona DESC";

        return $this->query($sql, $params);
    }

    public function getAlmasGanadasPorEdadesWithRole($fechaInicio, $fechaFin, $filtroRol, $idMinisterio = '', $idLider = '') {
        $filtroNuevas = $this->tieneColumna('Es_Antiguo') ? " AND p.Es_Antiguo = 0" : '';
        $sql = "SELECT
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) BETWEEN 3 AND 8 THEN 1 ELSE 0 END) AS Kids,
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) BETWEEN 9 AND 12 THEN 1 ELSE 0 END) AS Teens,
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) BETWEEN 13 AND 17 THEN 1 ELSE 0 END) AS Rocas,
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) BETWEEN 18 AND 30 THEN 1 ELSE 0 END) AS Jovenes,
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) BETWEEN 31 AND 59 THEN 1 ELSE 0 END) AS Adultos,
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) >= 61 THEN 1 ELSE 0 END) AS Adultos_Mayores,
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) = 60 THEN 1 ELSE 0 END) AS Adultos_Mayores_60,
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) IS NULL OR COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) < 3 THEN 1 ELSE 0 END) AS Sin_Dato
                FROM persona p
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                AND $filtroRol" . $filtroNuevas;

        $params = [$fechaInicio, $fechaFin];

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $sql .= " AND p.Id_Ministerio = ?";
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $sql .= " AND p.Id_Lider = ?";
            $params[] = (int)$idLider;
        }

        $rows = $this->query($sql, $params);
        $row = $rows[0] ?? [];

        return [
            'Kids' => (int)($row['Kids'] ?? 0),
            'Teens' => (int)($row['Teens'] ?? 0),
            'Rocas' => (int)($row['Rocas'] ?? 0),
            'Jovenes' => (int)($row['Jovenes'] ?? 0),
            'Adultos' => (int)($row['Adultos'] ?? 0),
            'Adultos_Mayores' => (int)($row['Adultos_Mayores'] ?? 0) + (int)($row['Adultos_Mayores_60'] ?? 0),
            'Sin_Dato' => (int)($row['Sin_Dato'] ?? 0)
        ];
    }

    /**
     * Obtener miembros activos agrupables por múltiples células
     */
    public function getActivosByCelulaIds(array $celulaIds) {
        $celulaIds = array_values(array_filter(array_map('intval', $celulaIds), function ($id) {
            return $id > 0;
        }));

        if (empty($celulaIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($celulaIds), '?'));

        $sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Numero_Documento, p.Telefono, p.Id_Celula
                FROM persona p
                WHERE p.Id_Celula IN ($placeholders)
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                ORDER BY p.Id_Celula, p.Apellido, p.Nombre";

        return $this->query($sql, $celulaIds);
    }

    /**
     * Obtener miembros activos agrupables por múltiples ministerios
     */
    public function getActivosByMinisterioIds(array $ministerioIds, $idRol = null) {
        $ministerioIds = array_values(array_filter(array_map('intval', $ministerioIds), function ($id) {
            return $id > 0;
        }));

        if (empty($ministerioIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ministerioIds), '?'));

        $sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Numero_Documento, p.Telefono, p.Direccion, p.Genero, p.Id_Ministerio,
               p.Id_Rol, p.Id_Lider, p.Tipo_Reunion, p.Fecha_Registro, p.Proceso, p.Escalera_Checklist, p.Convencion,
                   c.Nombre_Celula,
               r.Nombre_Rol,
               CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, '')) AS Nombre_Lider
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
            LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                WHERE p.Id_Ministerio IN ($placeholders)
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)";

        $params = $ministerioIds;
        $idRol = $idRol !== null ? (int)$idRol : null;
        if ($idRol !== null && $idRol > 0) {
            $sql .= " AND p.Id_Rol = ?";
            $params[] = $idRol;
        }

        $sql .= "
                ORDER BY p.Id_Ministerio, p.Apellido, p.Nombre";

        return $this->query($sql, $params);
    }

    /**
     * Total de líderes de célula visibles según aislamiento.
     */
    public function getTotalLideresCelulaWithRole($filtroRol) {
        $sql = "SELECT COUNT(*) AS Total
                FROM persona p
                WHERE (
                    EXISTS (
                        SELECT 1 FROM celula c
                        WHERE c.Id_Lider = p.Id_Persona
                    )
                    OR EXISTS (
                        SELECT 1 FROM persona p2
                        WHERE p2.Id_Lider = p.Id_Persona
                          AND (p2.Estado_Cuenta = 'Activo' OR p2.Estado_Cuenta IS NULL)
                    )
                )
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND $filtroRol";

        $rows = $this->query($sql);
        return (int)($rows[0]['Total'] ?? 0);
    }

    /**
     * Resumen de líderes de célula con actividad y cantidad de personas.
     */
    public function getResumenLideresCelulaWithRole($filtroRol) {
        $sql = "SELECT
                    p.Id_Persona,
                    p.Nombre,
                    p.Apellido,
                    p.Genero,
                    p.Telefono,
                    p.Direccion,
                    p.Id_Lider,
                    p.Id_Ministerio,
                    p.Ultimo_Acceso,
                    m.Nombre_Ministerio,
                    TRIM(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, ''))) AS Nombre_Lider,
                    CASE WHEN cel.Id_Persona IS NULL THEN 0 ELSE 1 END AS Es_Lider_Celula,
                    CASE WHEN l12r.Id_Persona IS NULL THEN 0 ELSE 1 END AS Es_Lider_12,
                    CASE
                        WHEN cel.Id_Persona IS NOT NULL AND l12r.Id_Persona IS NOT NULL THEN 'Ambos'
                        WHEN cel.Id_Persona IS NOT NULL THEN 'Líder de célula'
                        WHEN l12r.Id_Persona IS NOT NULL THEN 'Líder de 12'
                        ELSE 'Sin clasificación'
                    END AS Tipo_Liderazgo,
                    COALESCE(per.Total_Personas, 0) AS Total_Personas,
                    rep.Ultimo_Reporte_Celula
                FROM persona p
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                LEFT JOIN (
                    SELECT DISTINCT Id_Lider AS Id_Persona
                    FROM celula
                    WHERE Id_Lider IS NOT NULL
                ) cel ON cel.Id_Persona = p.Id_Persona
                LEFT JOIN (
                    SELECT p12.Id_Persona
                    FROM persona p12
                    LEFT JOIN rol r12 ON r12.Id_Rol = p12.Id_Rol
                    WHERE p12.Id_Rol = 8
                       OR LOWER(COALESCE(r12.Nombre_Rol, '')) LIKE '%lider de 12%'
                       OR LOWER(COALESCE(r12.Nombre_Rol, '')) LIKE '%lider 12%'
                       OR LOWER(COALESCE(r12.Nombre_Rol, '')) LIKE '%lideres de 12%'
                    GROUP BY p12.Id_Persona
                ) l12r ON l12r.Id_Persona = p.Id_Persona
                LEFT JOIN (
                    SELECT Id_Lider, COUNT(*) AS Total_Personas
                    FROM persona
                    WHERE Id_Lider IS NOT NULL
                    AND (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL)
                    GROUP BY Id_Lider
                ) per ON per.Id_Lider = p.Id_Persona
                LEFT JOIN (
                    SELECT c.Id_Lider, MAX(a.Fecha_Asistencia) AS Ultimo_Reporte_Celula
                    FROM asistencia_celula a
                    INNER JOIN celula c ON c.Id_Celula = a.Id_Celula
                    GROUP BY c.Id_Lider
                ) rep ON rep.Id_Lider = p.Id_Persona
                WHERE (cel.Id_Persona IS NOT NULL OR l12r.Id_Persona IS NOT NULL)
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND $filtroRol
                ORDER BY p.Apellido, p.Nombre";

        return $this->query($sql);
    }
}

