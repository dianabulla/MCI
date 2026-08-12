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
    /** @var array<int, string>|null null = sin precargar; array = jerarquía por Id_Rol */
    private $jerarquiaPorRolCache = null;

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

    /**
     * Tipo_Reunion (Ganado en) normalizado para comparaciones en SQL.
     */
    private function sqlTipoReunionNormalizado(string $alias = 'p'): string {
        return "REPLACE(REPLACE(LOWER(TRIM(COALESCE({$alias}.Tipo_Reunion, ''))), 'é', 'e'), 'í', 'i')";
    }

    /** Ganado en célula. */
    private function sqlEsGanadoEnCelula(string $alias = 'p'): string {
        $tipo = $this->sqlTipoReunionNormalizado($alias);
        $porTipo = "({$tipo} LIKE '%celula%')";
        if ($this->tieneColumna('Origen_Ganar')) {
            return "({$porTipo} OR TRIM(COALESCE({$alias}.Origen_Ganar, '')) = 'Celula')";
        }
        return $porTipo;
    }

    /**
     * Ganado en iglesia: cualquier opción distinta de célula (Domingo, Somos Uno, Otros, etc.).
     * Excluye vacío y migrados.
     */
    private function sqlEsGanadoEnIglesia(string $alias = 'p'): string {
        $tipo = $this->sqlTipoReunionNormalizado($alias);
        $celula = $this->sqlEsGanadoEnCelula($alias);
        $base = "({$tipo} <> '' AND NOT {$celula} AND {$tipo} NOT LIKE '%migrados%')";
        if ($this->tieneColumna('Origen_Ganar')) {
            return "({$base} OR TRIM(COALESCE({$alias}.Origen_Ganar, '')) = 'Domingo')";
        }
        return $base;
    }

    /**
     * Asignados: sin invitador, con ministerio y/o líder asignado, no origen célula ni migrados.
     */
    private function sqlEsAsignadoGanar(string $alias = 'p'): string {
        $invitadoExpr = "TRIM(COALESCE({$alias}.Invitado_Por, ''))";
        $tieneAsignacionExpr = "(({$alias}.Id_Lider IS NOT NULL AND {$alias}.Id_Lider > 0) OR ({$alias}.Id_Ministerio IS NOT NULL AND {$alias}.Id_Ministerio > 0))";
        $celulaExpr = $this->sqlEsGanadoEnCelula($alias);
        $tipo = $this->sqlTipoReunionNormalizado($alias);
        $esIglesiaExpr = $this->sqlEsGanadoEnIglesia($alias);
        $asignacionInternaSinOrigen = "(TRIM(COALESCE({$alias}.Origen_Ganar, '')) = '' AND {$tipo} = '' AND NOT {$celulaExpr})";

        return "({$invitadoExpr} = '' AND {$tieneAsignacionExpr} AND NOT {$celulaExpr} AND {$tipo} NOT LIKE '%migrados%' AND ({$esIglesiaExpr} OR {$asignacionInternaSinOrigen}))";
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

    private function permiteTelefonoDuplicadoPorTipoDocumento($tipoDocumento) {
        $tipoDocumento = strtoupper(trim((string)$tipoDocumento));
        return in_array($tipoDocumento, ['TARJETA DE IDENTIDAD', 'REGISTRO CIVIL'], true);
    }

    public function findDuplicateByCedulaOrTelefono($numeroDocumento, $telefono, $excludeId = null, $tipoDocumento = null) {
        $documentoNormalizado = $this->normalizarDocumentoParaComparacion($numeroDocumento);
        $telefonoNormalizado = $this->normalizarTelefonoParaComparacion($telefono);
        $excludeId = $excludeId !== null ? (int)$excludeId : 0;
        $permiteTelefonoDuplicado = $this->permiteTelefonoDuplicadoPorTipoDocumento($tipoDocumento);

        $condiciones = [];
        $params = [];

        if ($documentoNormalizado !== '') {
            $condiciones[] = "REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(Numero_Documento, ''))), ' ', ''), '.', ''), '-', '') = ?";
            $params[] = $documentoNormalizado;
        }

        if ($telefonoNormalizado !== '' && !$permiteTelefonoDuplicado) {
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
                        p.Edad,
                        p.Telefono,
                        p.Numero_Documento,
                        p.Id_Ministerio,
                        m.Nombre_Ministerio,
                        p.Id_Lider,
                        CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, '')) AS Nombre_Lider
                    FROM {$this->table} p
                    LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                    LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona";

        if ($documentoNormalizado !== '') {
            $sql = $sqlBase . "
                    WHERE REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(p.Numero_Documento, ''))), ' ', ''), '.', ''), '-', '') = ?
                    ORDER BY p.Id_Persona DESC
                    LIMIT 1";
            $rows = $this->query($sql, [$documentoNormalizado]);
            if (!empty($rows)) {
                return $rows[0];
            }
        } elseif ($telefonoNormalizado !== '') {
            $sql = $sqlBase . "
                    WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(p.Telefono, '')), ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '') = ?
                    ORDER BY p.Id_Persona DESC
                    LIMIT 1";
            $rows = $this->query($sql, [$telefonoNormalizado]);
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

    public function ensureNumeroCupoColumnExists() {
        if ($this->tieneColumna('Numero_Cupo')) {
            return true;
        }

        try {
            $after = $this->tieneColumna('Fecha_Asignacion_Lider') ? 'Fecha_Asignacion_Lider' : 'Id_Lider';
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Numero_Cupo TINYINT UNSIGNED NULL AFTER {$after}";
            $this->db->exec($sql);
            $this->columnasCache['Numero_Cupo'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Numero_Cupo en persona: ' . $e->getMessage());
            $this->columnasCache['Numero_Cupo'] = false;
            return false;
        }
    }

    /**
     * @param int[] $excludeIds
     */
    public function getIdPersonaEnCupoDeLider(int $idLider, int $numeroCupo, array $excludeIds = []): int {
        $idLider = (int)$idLider;
        $numeroCupo = (int)$numeroCupo;
        if ($idLider <= 0 || $numeroCupo < 1 || $numeroCupo > 12 || !$this->tieneColumna('Numero_Cupo')) {
            return 0;
        }

        $excludeIds = array_values(array_filter(array_map('intval', $excludeIds), static function ($id) {
            return $id > 0;
        }));

        $sql = "SELECT Id_Persona FROM {$this->table}
                WHERE Id_Lider = ? AND Numero_Cupo = ?";
        $params = [$idLider, $numeroCupo];

        if (!empty($excludeIds)) {
            $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
            $sql .= " AND Id_Persona NOT IN ({$placeholders})";
            $params = array_merge($params, $excludeIds);
        }

        $sql .= ' LIMIT 1';
        $rows = $this->query($sql, $params);
        return (int)($rows[0]['Id_Persona'] ?? 0);
    }

    public function primerCupoLibreDeLider(int $idLider, array $excludeIds = []): int {
        $idLider = (int)$idLider;
        if ($idLider <= 0) {
            return 0;
        }

        if ($this->tieneColumna('Numero_Cupo')) {
            for ($n = 1; $n <= 12; $n++) {
                if ($this->getIdPersonaEnCupoDeLider($idLider, $n, $excludeIds) <= 0) {
                    return $n;
                }
            }
            return 0;
        }

        $ocupados = $this->contarEquipoPrincipalPorCupo($idLider);
        return $ocupados < 12 ? ($ocupados + 1) : 0;
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

    public function ensureTratamientoDatosColumnExists() {
        if ($this->tieneColumna('Tratamiento_Datos')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Tratamiento_Datos ENUM('Acepta', 'No acepta') NULL AFTER Peticion";
            $this->db->exec($sql);
            $this->columnasCache['Tratamiento_Datos'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Tratamiento_Datos en persona: ' . $e->getMessage());
            $this->columnasCache['Tratamiento_Datos'] = false;
            return false;
        }
    }

    public function ensureAcuerdoConfidencialidadColumnsExist() {
        $ok = true;
        if (!$this->tieneColumna('Acuerdo_Confidencialidad_At')) {
            try {
                $sql = "ALTER TABLE {$this->table} ADD COLUMN Acuerdo_Confidencialidad_At DATETIME NULL AFTER Tratamiento_Datos";
                $this->db->exec($sql);
                $this->columnasCache['Acuerdo_Confidencialidad_At'] = true;
            } catch (Exception $e) {
                error_log('No se pudo crear columna Acuerdo_Confidencialidad_At: ' . $e->getMessage());
                $this->columnasCache['Acuerdo_Confidencialidad_At'] = false;
                $ok = false;
            }
        }
        if (!$this->tieneColumna('Acuerdo_Confidencialidad_Version')) {
            try {
                $sql = "ALTER TABLE {$this->table} ADD COLUMN Acuerdo_Confidencialidad_Version VARCHAR(32) NULL AFTER Acuerdo_Confidencialidad_At";
                $this->db->exec($sql);
                $this->columnasCache['Acuerdo_Confidencialidad_Version'] = true;
            } catch (Exception $e) {
                error_log('No se pudo crear columna Acuerdo_Confidencialidad_Version: ' . $e->getMessage());
                $this->columnasCache['Acuerdo_Confidencialidad_Version'] = false;
                $ok = false;
            }
        }
        return $ok;
    }

    public function registrarAcuerdoConfidencialidadPersona(int $idPersona, string $version): bool {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0 || !$this->tieneColumna('Acuerdo_Confidencialidad_At')) {
            return false;
        }

        $data = ['Acuerdo_Confidencialidad_At' => date('Y-m-d H:i:s')];
        if ($this->tieneColumna('Acuerdo_Confidencialidad_Version')) {
            $data['Acuerdo_Confidencialidad_Version'] = $version;
        }

        return $this->update($idPersona, $data);
    }

    public function tieneAcuerdoConfidencialidadVigente(int $idPersona, string $versionActual): bool {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0 || !$this->tieneColumna('Acuerdo_Confidencialidad_At')) {
            return true;
        }

        $persona = $this->getById($idPersona);
        if (empty($persona)) {
            return false;
        }

        $fecha = trim((string)($persona['Acuerdo_Confidencialidad_At'] ?? ''));
        if ($fecha === '') {
            return false;
        }

        if ($this->tieneColumna('Acuerdo_Confidencialidad_Version')) {
            $versionGuardada = trim((string)($persona['Acuerdo_Confidencialidad_Version'] ?? ''));
            return $versionGuardada === $versionActual;
        }

        return true;
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
     * NUEVO: reporte mensual de escalera del Ã©xito por peldaÃ±o
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

        if ($this->tieneColumna('Es_Antiguo')) {
            $where[] = 'p.Es_Antiguo = 0';
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
            'peldaÃ±os' => [
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

        $mapaPeldaÃ±os = [
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

            foreach ($mapaPeldaÃ±os as $etapa => $peldaÃ±os) {
                $checksEtapa = $checklist[$etapa] ?? [];

                foreach ($peldaÃ±os as $indice => $nombrePeldaÃ±o) {
                    $marcado = array_key_exists($indice, $checksEtapa) ? !empty($checksEtapa[$indice]) : false;

                    // Si no hay checklist persistido, la etapa activa arranca con el primer peldaÃ±o visible
                    if (!$marcado && $etapa === $proceso && $indice === 0) {
                        $marcado = true;
                    }

                    // Si la persona ya avanzÃ³ a una etapa posterior, se consideran completos
                    // los peldaÃ±os visibles de las etapas anteriores
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
                        $reporte['peldaÃ±os'][$etapa][$nombrePeldaÃ±o]++;
                        $reporte['detalles_peldanos'][$etapa][$nombrePeldaÃ±o][] = $detallePersona;
                    }
                }
            }
        }

        return $reporte;
    }

    /**
     * Obtener personas con lÃ­der y ministerio asignados que superaron el lÃ­mite
     * de horas para registrar el primer contacto.
     */
    public function getCandidatosReasignacionPrimerContacto($horasLimite = 48) {
        $horasLimite = max(1, (int)$horasLimite);

        // Regla de seguridad: sin Fecha_Asignacion_Lider no se ejecuta
        // la reasignaciÃ³n automÃ¡tica para evitar efectos retroactivos.
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
     * Quitar asignaciÃ³n de lÃ­der/ministerio y marcar persona como reasignada.
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
     * Obtener personas por cÃ©lula
     */
    public function getByCelula($idCelula) {
        $sql = "SELECT * FROM {$this->table} WHERE Id_Celula = ? ORDER BY Apellido, Nombre";
        return $this->query($sql, [$idCelula]);
    }

    /**
     * Quitar asignación de célula a todos los miembros antes de eliminar la célula.
     */
    public function limpiarMiembrosPorCelula($idCelula) {
        $idCelula = (int)$idCelula;
        if ($idCelula <= 0) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET Id_Celula = NULL WHERE Id_Celula = ?";
        return $this->execute($sql, [$idCelula]);
    }

    /**
     * Obtener solo personas activas (para reportes/grÃ¡ficos/cÃ©lulas)
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

        // Incluye Id_Rol fijos (3 célula, 6 pastores, 8 líder 12, 13 líder 144)
        // además de patrones por nombre, por si el nombre del rol tiene tildes u otras variantes.
        return "(
            {$aliasPersona}.Id_Rol IN (3, 6, 8, 13)
            OR LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%pastor%'
            OR LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%lider de celula%'
            OR LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%lider celula%'
            OR LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%lider de 12%'
            OR LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%lider 12%'
            OR LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%lideres de 12%'
            OR LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%lider de 144%'
            OR LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%lider 144%'
            OR LOWER(COALESCE({$aliasRol}.Nombre_Rol, '')) LIKE '%lideres de 144%'
        )";
    }

    /**
     * Condición SQL: persona activa es líder para registro en Escuelas (rol de liderazgo o líder asignado en célula).
     * Requiere alias p y LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol.
     */
    public function condicionSqlPersonaEsLiderEscuela(string $aliasPersona = 'p', string $aliasRol = 'r'): string {
        $aliasPersona = trim((string)$aliasPersona) ?: 'p';
        $aliasRol = trim((string)$aliasRol) ?: 'r';
        $rolesLider = $this->getCondicionRolesLiderazgoSql($aliasPersona, $aliasRol);

        if ($this->tieneTabla('celula')) {
            return "(
                EXISTS (SELECT 1 FROM celula c WHERE c.Id_Lider = {$aliasPersona}.Id_Persona AND c.Id_Lider IS NOT NULL)
                OR ({$rolesLider})
            )";
        }

        return "({$rolesLider})";
    }

    /**
     * Autocomplete de líderes para el formulario público de escuelas (todos los roles de liderazgo + líderes de célula).
     *
     * @return list<array<string,mixed>>
     */
    public function buscarLideresParaRegistroEscuela(string $term, int $limit = 60): array {
        $term = trim((string)$term);
        if (strlen($term) < 2) {
            return [];
        }

        $like = '%' . $term . '%';
        $lim = max(1, min(100, (int)$limit));
        $condLider = $this->condicionSqlPersonaEsLiderEscuela('p', 'r');

        $sql = "SELECT DISTINCT p.Id_Persona, p.Nombre, p.Apellido, p.Id_Rol,
                       COALESCE(r.Nombre_Rol, '') AS Rol
                FROM persona p
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND ({$condLider})
                  AND (
                      p.Nombre LIKE ?
                      OR p.Apellido LIKE ?
                      OR CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, '')) LIKE ?
                  )
                ORDER BY p.Nombre ASC, p.Apellido ASC
                LIMIT {$lim}";

        return $this->query($sql, [$like, $like, $like]);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function obtenerPersonaLiderValidaEscuela(int $idPersona): ?array {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0) {
            return null;
        }

        $condLider = $this->condicionSqlPersonaEsLiderEscuela('p', 'r');
        $sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Id_Rol, p.Estado_Cuenta
                FROM persona p
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                WHERE p.Id_Persona = ?
                  AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND ({$condLider})
                LIMIT 1";

        $rows = $this->query($sql, [$idPersona]);
        if (empty($rows[0])) {
            return null;
        }

        return $rows[0];
    }

    private function normalizarNombreRol($nombreRol) {
        $nombreRol = trim((string)$nombreRol);
        $nombreRol = function_exists('mb_strtolower')
            ? mb_strtolower($nombreRol, 'UTF-8')
            : strtolower($nombreRol);
        $mapa = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
            'Ã¡' => 'a', 'Ã©' => 'e', 'Ã­' => 'i', 'Ã³' => 'o', 'Ãº' => 'u', 'Ã¼' => 'u', 'Ã±' => 'n',
        ];
        return strtr($nombreRol, $mapa);
    }

    private function asegurarCacheJerarquiaRoles(): void {
        if ($this->jerarquiaPorRolCache !== null) {
            return;
        }

        $this->jerarquiaPorRolCache = [0 => 'miembro'];
        $rows = $this->query('SELECT Id_Rol, Nombre_Rol FROM rol');
        foreach ((array)$rows as $row) {
            $idRol = (int)($row['Id_Rol'] ?? 0);
            if ($idRol <= 0) {
                continue;
            }
            $this->jerarquiaPorRolCache[$idRol] = $this->jerarquiaDesdeIdRolYNombre(
                $idRol,
                (string)($row['Nombre_Rol'] ?? '')
            );
        }
    }

    private function jerarquiaDesdeIdRolYNombre(int $idRol, string $nombreRol): string {
        $nombreRol = $this->normalizarNombreRol($nombreRol);

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
            $idRol === 13
            || strpos($nombreRol, 'lider de 144') !== false
            || strpos($nombreRol, 'lider 144') !== false
            || strpos($nombreRol, 'lideres de 144') !== false
        ) {
            return 'lider_144';
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

    public function getJerarquiaByRol($idRol) {
        $idRol = (int)$idRol;
        if ($idRol <= 0) {
            return 'miembro';
        }

        $this->asegurarCacheJerarquiaRoles();

        return $this->jerarquiaPorRolCache[$idRol] ?? 'miembro';
    }

    private function buscarIdRolPorPatrones(array $patrones) {
        $rows = $this->query("SELECT Id_Rol, Nombre_Rol FROM rol");
        if (empty($rows)) {
            return 0;
        }

        $patronesNorm = [];
        foreach ($patrones as $patron) {
            $patronNorm = $this->normalizarNombreRol($patron);
            if ($patronNorm !== '') {
                $patronesNorm[] = $patronNorm;
            }
        }

        if (empty($patronesNorm)) {
            return 0;
        }

        foreach ($rows as $row) {
            $idRol = (int)($row['Id_Rol'] ?? 0);
            if ($idRol <= 0) {
                continue;
            }

            $nombreNorm = $this->normalizarNombreRol($row['Nombre_Rol'] ?? '');
            if ($nombreNorm === '') {
                continue;
            }

            foreach ($patronesNorm as $patron) {
                if (strpos($nombreNorm, $patron) !== false) {
                    return $idRol;
                }
            }
        }

        return 0;
    }

    /**
     * Resuelve Id_Rol por nombre; si no hay match, usa un Id conocido si existe en BD.
     */
    private function resolverIdRolConFallback(array $patrones, int $idFallback = 0): int {
        $id = (int)$this->buscarIdRolPorPatrones($patrones);
        if ($id > 0) {
            return $id;
        }

        $idFallback = (int)$idFallback;
        if ($idFallback <= 0) {
            return 0;
        }

        $rows = $this->query('SELECT Id_Rol FROM rol WHERE Id_Rol = ? LIMIT 1', [$idFallback]);
        return !empty($rows[0]['Id_Rol']) ? (int)$rows[0]['Id_Rol'] : 0;
    }

    public function resolverRolAscensoPorLider($idRolLider, $idRolActualPersona) {
        $jerarquiaLider = $this->getJerarquiaByRol((int)$idRolLider);
        $jerarquiaActual = $this->getJerarquiaByRol((int)$idRolActualPersona);

        $objetivo = '';
        if ($jerarquiaLider === 'pastor') {
            $objetivo = 'lider_12';
        } elseif ($jerarquiaLider === 'lider_12') {
            $objetivo = 'lider_144';
        } elseif ($jerarquiaLider === 'lider_144') {
            $objetivo = 'lider_celula';
        }

        if ($objetivo === '' || $objetivo === $jerarquiaActual) {
            return [
                'ok' => true,
                'id_rol' => 0,
                'jerarquia_objetivo' => $objetivo,
                'message' => ''
            ];
        }

        $idRolObjetivo = 0;
        if ($objetivo === 'lider_12') {
            $idRolObjetivo = $this->resolverIdRolConFallback(['lider de 12', 'lider 12', 'lideres de 12'], 8);
        } elseif ($objetivo === 'lider_144') {
            $idRolObjetivo = $this->resolverIdRolConFallback(['lider de 144', 'lider 144', 'lideres de 144'], 13);
        } elseif ($objetivo === 'lider_celula') {
            $idRolObjetivo = $this->resolverIdRolConFallback(['lider de celula', 'lider celula'], 3);
        }

        if ($idRolObjetivo <= 0) {
            return [
                'ok' => false,
                'id_rol' => 0,
                'jerarquia_objetivo' => $objetivo,
                'message' => 'No existe un rol configurado para ' . $objetivo . '.'
            ];
        }

        return [
            'ok' => true,
            'id_rol' => $idRolObjetivo,
            'jerarquia_objetivo' => $objetivo,
            'message' => ''
        ];
    }

    public function resolverIdRolPastor() {
        return (int)$this->buscarIdRolPorPatrones(['pastor']);
    }

    public function resolverIdRolLider12() {
        return (int)$this->buscarIdRolPorPatrones(['lider de 12', 'lider 12', 'lideres de 12']);
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
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider de 144%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider 144%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lideres de 144%'
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

    /**
     * Personas activas con cupo numerado (1–12) bajo un líder (equipo principal).
     */
    public function contarEquipoPrincipalPorCupo(int $idLider, ?int $excludePersonaId = null): int {
        $idLider = (int)$idLider;
        $excludePersonaId = $excludePersonaId !== null ? (int)$excludePersonaId : 0;
        if ($idLider <= 0 || !$this->tieneColumna('Numero_Cupo')) {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS Total
             FROM {$this->table}
             WHERE Id_Lider = ?
               AND Numero_Cupo BETWEEN 1 AND 12
               AND (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL)";
        $params = [$idLider];

        if ($excludePersonaId > 0) {
            $sql .= ' AND Id_Persona <> ?';
            $params[] = $excludePersonaId;
        }

        $rows = $this->query($sql, $params);

        return min(12, (int)($rows[0]['Total'] ?? 0));
    }

    /**
     * Resumen de cupos numerados (1–12) para validación y UI.
     *
     * @return array{equipo_directo: int, limite_equipo: int, cupos_disponibles: int, cupo_lleno: bool}
     */
    public function getResumenCuposNumeradosLider(int $idLider, ?int $excludePersonaId = null): array {
        $limiteEquipo = $this->limiteEquipoDirectoPorJerarquiaLider($idLider);
        if ($limiteEquipo <= 0) {
            return [
                'equipo_directo' => 0,
                'limite_equipo' => 0,
                'cupos_disponibles' => 9999,
                'cupo_lleno' => false,
            ];
        }

        $ocupados = $this->contarEquipoPrincipalPorCupo($idLider, $excludePersonaId);

        return [
            'equipo_directo' => $ocupados,
            'limite_equipo' => $limiteEquipo,
            'cupos_disponibles' => max(0, $limiteEquipo - $ocupados),
            'cupo_lleno' => $ocupados >= $limiteEquipo,
        ];
    }

    /**
     * @param array<int, int> $idsLideres
     * @return array<int, int>
     */
    public function contarEquipoPrincipalPorCupoBatch(array $idsLideres): array {
        $idsLideres = array_values(array_unique(array_filter(array_map('intval', $idsLideres), static function ($id) {
            return $id > 0;
        })));

        if (empty($idsLideres) || !$this->tieneColumna('Numero_Cupo')) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsLideres), '?'));
        $rows = $this->query(
            "SELECT Id_Lider, COUNT(*) AS Total
             FROM {$this->table}
             WHERE Id_Lider IN ({$placeholders})
               AND Numero_Cupo BETWEEN 1 AND 12
               AND (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL)
             GROUP BY Id_Lider",
            $idsLideres
        );

        $resultado = [];
        foreach ((array)$rows as $row) {
            $idLider = (int)($row['Id_Lider'] ?? 0);
            if ($idLider <= 0) {
                continue;
            }
            $resultado[$idLider] = min(12, (int)($row['Total'] ?? 0));
        }

        return $resultado;
    }

    /**
     * Miembros del equipo principal (solo cupos 1–12) bajo un líder.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMiembrosEquipoPrincipalPorCupo(int $idLider): array {
        $idLider = (int)$idLider;
        if ($idLider <= 0 || !$this->tieneColumna('Numero_Cupo')) {
            return [];
        }

        return $this->query(
            "SELECT p.Id_Persona, p.Numero_Documento, p.Nombre, p.Apellido, p.Email, p.Telefono,
                    p.Id_Lider, p.Numero_Cupo, COALESCE(r.Nombre_Rol, '') AS Nombre_Rol
             FROM {$this->table} p
             LEFT JOIN rol r ON r.Id_Rol = p.Id_Rol
             WHERE p.Id_Lider = ?
               AND p.Numero_Cupo BETWEEN 1 AND 12
               AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
             ORDER BY p.Numero_Cupo ASC, p.Apellido ASC, p.Nombre ASC",
            [$idLider]
        );
    }

    /**
     * 12 para pastor / líder de 12 / líder de 144; 0 = sin tope (líder de célula).
     */
    public function limiteEquipoDirectoPorJerarquiaLider(int $idLider): int {
        $idLider = (int)$idLider;
        if ($idLider <= 0) {
            return 12;
        }
        $lider = $this->getById($idLider);
        if (empty($lider)) {
            return 12;
        }
        $jerarquia = $this->getJerarquiaByRol((int)($lider['Id_Rol'] ?? 0));
        return $jerarquia === 'lider_celula' ? 0 : 12;
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
                'message' => 'El lÃ­der asignado no existe.'
            ];
        }

        $jerarquiaLider = $this->getJerarquiaByRol((int)($lider['Id_Rol'] ?? 0));
        $jerarquiaPersona = $this->getJerarquiaByRol($idRolPersona);

        if (in_array($jerarquiaPersona, ['pastor', 'administrativo'], true)) {
            return [
                'ok' => $idLider <= 0,
                'message' => 'Un pastor o usuario administrativo no debe quedar bajo cobertura de un lÃ­der.'
            ];
        }

        if ($jerarquiaLider === 'miembro' || $jerarquiaLider === 'administrativo') {
            return [
                'ok' => false,
                'message' => 'Solo un pastor, lÃ­der de 12 o lÃ­der de cÃ©lula puede recibir cobertura.'
            ];
        }

        if ($jerarquiaLider === 'lider_celula' && $jerarquiaPersona !== 'miembro') {
            return [
                'ok' => false,
                'message' => 'Un lÃ­der de cÃ©lula solo puede cubrir personas de su cÃ©lula, no otros lÃ­deres.'
            ];
        }

        if ($jerarquiaLider === 'lider_12' && !in_array($jerarquiaPersona, ['lider_144', 'lider_celula', 'miembro'], true)) {
            return [
                'ok' => false,
                'message' => 'Un lÃ­der de 12 solo puede cubrir lÃ­deres de cÃ©lula o miembros.'
            ];
        }

        if ($jerarquiaLider === 'lider_144' && !in_array($jerarquiaPersona, ['lider_celula', 'miembro'], true)) {
            return [
                'ok' => false,
                'message' => 'Un lÃ­der de 144 solo puede cubrir lÃ­deres de cÃ©lula o miembros.'
            ];
        }

        if ($jerarquiaLider === 'pastor' && !in_array($jerarquiaPersona, ['lider_12', 'lider_144', 'lider_celula', 'miembro'], true)) {
            return [
                'ok' => false,
                'message' => 'La cobertura pastoral solo aplica a la red ministerial.'
            ];
        }

        $aplicaCupoLiderazgo = in_array($jerarquiaLider, ['pastor', 'lider_12', 'lider_144'], true)
            && in_array($jerarquiaPersona, ['lider_12', 'lider_144', 'lider_celula'], true)
            && !($jerarquiaLider === 'lider_144' && $jerarquiaPersona === 'lider_celula');

        if ($aplicaCupoLiderazgo) {
            $totalDirectos = $this->contarEquipoPrincipalPorCupo($idLider, $excludePersonaId);
            if ($totalDirectos >= 12) {
                return [
                    'ok' => false,
                    'message' => 'Ese líder ya tiene las 12 casillas del equipo principal ocupadas.'
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
                WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND (
                    {$condicionRoles}
                    OR EXISTS (
                        SELECT 1 FROM celula c
                        WHERE c.Id_Lider = p.Id_Persona
                          AND c.Id_Lider IS NOT NULL
                    )
                  )
                ORDER BY p.Apellido, p.Nombre";
        return $this->query($sql);
    }

    /**
     * Obtener lÃ­deres/pastores por ministerio.
     */
    public function getLideresByMinisterio($idMinisterio) {
        $condicionRoles = $this->getCondicionRolesLiderazgoSql('p', 'r');
        $sql = "SELECT DISTINCT p.*
                FROM {$this->table} p
                LEFT JOIN rol r ON r.Id_Rol = p.Id_Rol
                WHERE p.Id_Ministerio = ?
                  AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND (
                    {$condicionRoles}
                    OR EXISTS (
                        SELECT 1 FROM celula c
                        WHERE c.Id_Lider = p.Id_Persona
                          AND c.Id_Lider IS NOT NULL
                    )
                  )
                ORDER BY p.Apellido, p.Nombre";
        return $this->query($sql, [$idMinisterio]);
    }

    /**
     * Obtener personas con rol LÃ­der de 12
     */
    public function getLideres12() {
        $sql = "SELECT * FROM {$this->table} WHERE Id_Rol = 8 ORDER BY Apellido, Nombre";
        return $this->query($sql);
    }

    /**
     * Obtener datos bÃ¡sicos de lÃ­deres por IDs.
     *
     * @param int[] $idsLideres
     * @return array<int, array<string,mixed>> indexado por Id_Persona
     */
    public function getResumenLideresByIds(array $idsLideres) {
        $ids = array_values(array_unique(array_filter(array_map('intval', $idsLideres), static function($id) {
            return $id > 0;
        })));

        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT
                    p.Id_Persona,
                    p.Id_Rol,
                    p.Genero,
                    p.Id_Ministerio,
                    TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre_Completo,
                    m.Nombre_Ministerio
                FROM {$this->table} p
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                WHERE p.Id_Persona IN ({$placeholders})";

        $rows = $this->query($sql, $ids);
        $resultado = [];
        foreach ((array)$rows as $row) {
            $id = (int)($row['Id_Persona'] ?? 0);
            if ($id > 0) {
                $resultado[$id] = $row;
            }
        }

        return $resultado;
    }

    /**
     * Obtener estadÃ­sticas de almas ganadas por ministerio
     * Agrupa por ministerio y gÃ©nero en un rango de fechas
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
     * Actualizar Ãºltimo acceso
     */
    public function actualizarUltimoAcceso($idPersona) {
        try {
            $sql = "UPDATE persona SET Ultimo_Acceso = NOW() WHERE Id_Persona = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$idPersona]);
        } catch (Exception $e) {
            error_log("Error actualizando Ãºltimo acceso: " . $e->getMessage());
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
            $esCelulaExpr = $this->sqlEsGanadoEnCelula('p');
            $esIglesiaExpr = $this->sqlEsGanadoEnIglesia('p');
            $esAsignadoExpr = $this->sqlEsAsignadoGanar('p');

            if ($origen === 'celula') {
                $sql .= " AND {$esCelulaExpr}";
            } elseif ($origen === 'domingo' || $origen === 'iglesia') {
                $sql .= " AND {$esIglesiaExpr}";
                if ($origen === 'domingo') {
                    $sql .= " AND NOT {$esAsignadoExpr}";
                }
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
     * Obtener personas registradas desde el formulario pÃºblico de Escuelas de FormaciÃ³n (Universidad de la Vida)
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
            $esCelulaExpr = $this->sqlEsGanadoEnCelula('p');
            $esIglesiaExpr = $this->sqlEsGanadoEnIglesia('p');
            $esAsignadoExpr = $this->sqlEsAsignadoGanar('p');

            if ($origen === 'celula') {
                $sql .= " AND {$esCelulaExpr}";
            } elseif ($origen === 'domingo' || $origen === 'iglesia') {
                $sql .= " AND {$esIglesiaExpr}";
                if ($origen === 'domingo') {
                    $sql .= " AND NOT {$esAsignadoExpr}";
                }
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
     * Búsqueda por texto para listados: nombre/apellidos, documento y teléfono.
     * Respeta el aislamiento de rol en $filtroRol.
     */
    public function buscarPersonasTextoListadoConRole($filtroRol, $termino, $limite = 200) {
        $termino = preg_replace('/\s+/u', ' ', trim((string)$termino));
        $filtroRol = trim((string)$filtroRol);
        if ($termino === '' || $filtroRol === '') {
            return [];
        }

        $limite = (int)$limite;
        if ($limite < 1) {
            $limite = 1;
        }
        if ($limite > 500) {
            $limite = 500;
        }

        $params = [];
        $partesOr = [];

        $nombreCompleto = "TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, '')))";
        $nombreInvertido = "TRIM(CONCAT(COALESCE(p.Apellido, ''), ' ', COALESCE(p.Nombre, '')))";
        $partesOr[] = "(LOWER($nombreCompleto) LIKE LOWER(?) OR LOWER($nombreInvertido) LIKE LOWER(?))";
        $likeNombre = '%' . $termino . '%';
        $params[] = $likeNombre;
        $params[] = $likeNombre;

        $docNorm = $this->normalizarDocumentoParaComparacion($termino);
        if ($docNorm !== '') {
            $partesOr[] = "REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(p.Numero_Documento, ''))), ' ', ''), '.', ''), '-', '') LIKE ?";
            $params[] = '%' . $docNorm . '%';
        }

        $soloDigitos = preg_replace('/\D+/', '', $termino);
        if ($soloDigitos !== '') {
            $telExpr = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(p.Telefono, '')), ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '')";
            $partesOr[] = "$telExpr LIKE ?";
            $params[] = '%' . $soloDigitos . '%';
        }

        $whereBuscar = '(' . implode(' OR ', $partesOr) . ')';

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
                WHERE ($filtroRol)
                AND $whereBuscar
                ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC
                LIMIT " . $limite;

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
     * Resumen de etapas del proceso de ganar por perÃ­odo.
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
        $filtroNuevas = $this->tieneColumna('Es_Antiguo') ? " AND p.Es_Antiguo = 0" : '';

        $esCelulaExpr = $this->sqlEsGanadoEnCelula('p');
        $esIglesiaExpr = $this->sqlEsGanadoEnIglesia('p');
        $esAsignadoExpr = $this->sqlEsAsignadoGanar('p');

        $sql = "SELECT
                    SUM(CASE WHEN {$esCelulaExpr} THEN 1 ELSE 0 END) AS Ganados_Celula,
                    SUM(CASE WHEN {$esIglesiaExpr} THEN 1 ELSE 0 END) AS Ganados_Iglesia,
                    SUM(CASE WHEN {$esAsignadoExpr} THEN 1 ELSE 0 END) AS Asignados,
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

    /**
     * Ganados en iglesia y cuántos ya tienen célula asignada (Id_Celula > 0).
     *
     * @return array{Ganados_Iglesia: int, Ubicados_Celula: int, Pendientes_Iglesia: int, Ganados_Celula: int, Ubicados_Total: int, Total: int}
     */
    public function getResumenGanadosIglesiaUbicacionWithRole($fechaInicio, $fechaFin, $filtroRol, $idMinisterio = '', $idLider = '') {
        $filtroNuevas = $this->tieneColumna('Es_Antiguo') ? " AND p.Es_Antiguo = 0" : '';

        $esCelulaExpr = $this->sqlEsGanadoEnCelula('p');
        $esIglesiaExpr = $this->sqlEsGanadoEnIglesia('p');
        $tieneCelulaExpr = '(p.Id_Celula IS NOT NULL AND p.Id_Celula > 0)';

        $sql = "SELECT
                    SUM(CASE WHEN {$esIglesiaExpr} THEN 1 ELSE 0 END) AS Ganados_Iglesia,
                    SUM(CASE WHEN {$esIglesiaExpr} AND {$tieneCelulaExpr} THEN 1 ELSE 0 END) AS Ubicados_Celula,
                    SUM(CASE WHEN {$esIglesiaExpr} AND NOT {$tieneCelulaExpr} THEN 1 ELSE 0 END) AS Pendientes_Iglesia,
                    SUM(CASE WHEN {$esCelulaExpr} THEN 1 ELSE 0 END) AS Ganados_Celula,
                    SUM(CASE WHEN {$tieneCelulaExpr} THEN 1 ELSE 0 END) AS Ubicados_Total,
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
            'Ganados_Iglesia' => (int)($row['Ganados_Iglesia'] ?? 0),
            'Ubicados_Celula' => (int)($row['Ubicados_Celula'] ?? 0),
            'Pendientes_Iglesia' => (int)($row['Pendientes_Iglesia'] ?? 0),
            'Ganados_Celula' => (int)($row['Ganados_Celula'] ?? 0),
            'Ubicados_Total' => (int)($row['Ubicados_Total'] ?? 0),
            'Total' => (int)($row['Total'] ?? 0),
        ];
    }

    /**
     * Desglose semanal (domingo a domingo) de ganados en iglesia y ubicados en célula.
     *
     * @return array<int, array{semana_inicio: string, semana_fin: string, ganados_iglesia: int, ubicados_celula: int, pendientes: int}>
     */
    public function getResumenGanadosIglesiaUbicacionPorSemanaWithRole($fechaInicio, $fechaFin, $filtroRol, $idMinisterio = '', $idLider = '') {
        $filtroNuevas = $this->tieneColumna('Es_Antiguo') ? " AND p.Es_Antiguo = 0" : '';

        $esCelulaExpr = $this->sqlEsGanadoEnCelula('p');
        $esIglesiaExpr = $this->sqlEsGanadoEnIglesia('p');
        $tieneCelulaExpr = '(p.Id_Celula IS NOT NULL AND p.Id_Celula > 0)';

        $sql = "SELECT
                    DATE(DATE_SUB(p.Fecha_Registro, INTERVAL DAYOFWEEK(p.Fecha_Registro) - 1 DAY)) AS Semana_Inicio,
                    SUM(CASE WHEN {$esIglesiaExpr} THEN 1 ELSE 0 END) AS Ganados_Iglesia,
                    SUM(CASE WHEN {$esIglesiaExpr} AND {$tieneCelulaExpr} THEN 1 ELSE 0 END) AS Ubicados_Celula,
                    SUM(CASE WHEN {$esIglesiaExpr} AND NOT {$tieneCelulaExpr} THEN 1 ELSE 0 END) AS Pendientes_Iglesia
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

        $sql .= " GROUP BY Semana_Inicio ORDER BY Semana_Inicio ASC";

        $rows = $this->query($sql, $params);
        $resultado = [];

        foreach ((array)$rows as $row) {
            $inicio = (string)($row['Semana_Inicio'] ?? '');
            if ($inicio === '') {
                continue;
            }
            $ts = strtotime($inicio);
            if ($ts === false) {
                continue;
            }
            $fin = date('Y-m-d', strtotime('+6 days', $ts));

            $resultado[] = [
                'semana_inicio' => $inicio,
                'semana_fin' => $fin,
                'ganados_iglesia' => (int)($row['Ganados_Iglesia'] ?? 0),
                'ubicados_celula' => (int)($row['Ubicados_Celula'] ?? 0),
                'pendientes' => (int)($row['Pendientes_Iglesia'] ?? 0),
            ];
        }

        return $resultado;
    }

    /**
     * Líderes de 12 visibles bajo un pastor principal (cobertura pastoral global).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLideres12BajoPastorWithRole(int $idPastor, string $filtroRol, string $generoRed = '') {
        $idPastor = (int)$idPastor;
        if ($idPastor <= 0) {
            return [];
        }

        $filtroGenero = '';
        $generoRed = strtolower(trim($generoRed));
        if ($generoRed === 'mujeres') {
            $filtroGenero = " AND (LOWER(COALESCE(p.Genero, '')) LIKE '%mujer%' OR LOWER(COALESCE(p.Genero, '')) LIKE '%femen%')";
        } elseif ($generoRed === 'hombres') {
            $filtroGenero = " AND (LOWER(COALESCE(p.Genero, '')) NOT LIKE '%mujer%' AND LOWER(COALESCE(p.Genero, '')) NOT LIKE '%femen%')";
        }

        $sql = "SELECT
                    p.Id_Persona,
                    p.Nombre,
                    p.Apellido,
                    p.Genero,
                    p.Id_Ministerio,
                    p.Numero_Cupo,
                    COALESCE(m.Nombre_Ministerio, '') AS Nombre_Ministerio
                FROM {$this->table} p
                LEFT JOIN ministerio m ON m.Id_Ministerio = p.Id_Ministerio
                LEFT JOIN rol r ON r.Id_Rol = p.Id_Rol
                WHERE p.Id_Lider = ?
                  AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND (
                        p.Id_Rol = 8
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider de 12%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider 12%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lideres de 12%'
                  )
                  AND $filtroRol" . $filtroGenero . "
                ORDER BY COALESCE(p.Numero_Cupo, 999) ASC, p.Apellido ASC, p.Nombre ASC";

        return $this->query($sql, [$idPastor]);
    }

    public function getDetalleGanadosOrigenWithRole($fechaInicio, $fechaFin, $filtroRol, $origen, $idMinisterio = '', $idLider = '') {
        $filtroNuevas = $this->tieneColumna('Es_Antiguo') ? " AND p.Es_Antiguo = 0" : '';

        $esCelulaExpr = $this->sqlEsGanadoEnCelula('p');
        $esIglesiaExpr = $this->sqlEsGanadoEnIglesia('p');
        $esAsignadoExpr = $this->sqlEsAsignadoGanar('p');

        $sql = "SELECT
                    p.Id_Persona,
                    p.Nombre,
                    p.Apellido,
                    p.Fecha_Registro,
                    p.Tipo_Reunion,
                    COALESCE(c.Nombre_Celula, 'Sin cÃ©lula') AS Nombre_Celula,
                    COALESCE(m.Nombre_Ministerio, 'Sin ministerio') AS Nombre_Ministerio,
                    COALESCE(CONCAT(l.Nombre, ' ', l.Apellido), 'Sin lÃ­der') AS Nombre_Lider
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona l ON p.Id_Lider = l.Id_Persona
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

        if ($origen === 'celula') {
            $sql .= " AND {$esCelulaExpr}";
        } elseif ($origen === 'iglesia' || $origen === 'domingo') {
            $sql .= " AND {$esIglesiaExpr}";
        } elseif ($origen === 'asignados') {
            $sql .= " AND {$esAsignadoExpr}";
        } else {
            return [];
        }

        $sql .= " ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC";

        return $this->query($sql, $params);
    }

    public function getDetalleGanadosGeneroWithRole($fechaInicio, $fechaFin, $filtroRol, $generoGrupo, $idMinisterio = '', $idLider = '') {
        $filtroNuevas = $this->tieneColumna('Es_Antiguo') ? " AND p.Es_Antiguo = 0" : '';
        $generoGrupo = strtolower(trim((string)$generoGrupo));

        if ($generoGrupo === 'hombres') {
            $filtroGenero = " AND p.Genero IN ('Hombre', 'Joven Hombre')";
        } elseif ($generoGrupo === 'mujeres') {
            $filtroGenero = " AND p.Genero IN ('Mujer', 'Joven Mujer')";
        } else {
            return [];
        }

        $sql = "SELECT
                    p.Id_Persona,
                    p.Nombre,
                    p.Apellido,
                    p.Fecha_Registro,
                    p.Genero,
                    p.Proceso,
                    COALESCE(c.Nombre_Celula, 'Sin cÃ©lula') AS Nombre_Celula,
                    COALESCE(m.Nombre_Ministerio, 'Sin ministerio') AS Nombre_Ministerio,
                    COALESCE(CONCAT(l.Nombre, ' ', l.Apellido), 'Sin lÃ­der') AS Nombre_Lider
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona l ON p.Id_Lider = l.Id_Persona
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                AND $filtroRol" . $filtroNuevas . $filtroGenero;

        $params = [$fechaInicio, $fechaFin];

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $sql .= " AND p.Id_Ministerio = ?";
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $sql .= " AND p.Id_Lider = ?";
            $params[] = (int)$idLider;
        }

        $sql .= " ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC";
        return $this->query($sql, $params);
    }

    /**
     * Resumen por ministerio para el reporte de fin de semana anterior.
     * Ganados: domingo con invitador.
     * Asignados: sin invitador y con ministerio y/o líder asignado.
     * Por verificar: domingo sin líder asignado.
     */
    public function getResumenGanadosFinSemanaAnteriorPorMinisterioWithRole($fechaInicio, $fechaFin, $filtroRol, $idMinisterio = '', $idLider = '') {
        $invitadoExpr = "TRIM(COALESCE(p.Invitado_Por, ''))";
        $filtroNuevas = $this->tieneColumna('Es_Antiguo') ? " AND p.Es_Antiguo = 0" : '';

        $esIglesiaExpr = $this->sqlEsGanadoEnIglesia('p');
        $esAsignadoExpr = $this->sqlEsAsignadoGanar('p');

        $sql = "SELECT
                    COALESCE(m.Nombre_Ministerio, 'Sin ministerio') AS Nombre_Ministerio,
                    SUM(CASE WHEN {$esIglesiaExpr} AND {$invitadoExpr} <> '' THEN 1 ELSE 0 END) AS Ganados,
                    SUM(CASE WHEN {$esAsignadoExpr} THEN 1 ELSE 0 END) AS Asignados,
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
        $filtroNuevas = $this->tieneColumna('Es_Antiguo') ? " AND p.Es_Antiguo = 0" : '';

        $esIglesiaExpr = $this->sqlEsGanadoEnIglesia('p');

        $sql = "SELECT
                    p.Id_Persona,
                    p.Nombre,
                    p.Apellido,
                    p.Fecha_Registro,
                    p.Proceso,
                    COALESCE(m.Nombre_Ministerio, 'Sin ministerio') AS Nombre_Ministerio,
                    COALESCE(c.Nombre_Celula, 'Sin cÃ©lula') AS Nombre_Celula,
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
     * Obtener miembros activos agrupables por mÃºltiples cÃ©lulas
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
     * Obtener miembros activos agrupables por mÃºltiples ministerios
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
               r.Nombre_Rol AS Nombre_Rol,
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

        $sql .= " ORDER BY p.Id_Ministerio, p.Apellido, p.Nombre";

        return $this->query($sql, $params);
    }

    /**
     * @return array{total: int, hombres: int, mujeres: int}
     */
    public function contarPersonasMinisterioPorGenero(int $idMinisterio, string $filtroRol = '1=1'): array {
        $idMinisterio = (int)$idMinisterio;
        if ($idMinisterio <= 0) {
            return ['total' => 0, 'hombres' => 0, 'mujeres' => 0];
        }

        $sql = "SELECT
                    COUNT(*) AS Total,
                    SUM(
                        CASE
                            WHEN LOWER(COALESCE(p.Genero, '')) LIKE '%mujer%'
                              OR LOWER(COALESCE(p.Genero, '')) LIKE '%femen%'
                            THEN 1 ELSE 0
                        END
                    ) AS Mujeres
                FROM {$this->table} p
                WHERE p.Id_Ministerio = ?
                  AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND {$filtroRol}";

        $rows = $this->query($sql, [$idMinisterio]);
        $total = (int)($rows[0]['Total'] ?? 0);
        $mujeres = (int)($rows[0]['Mujeres'] ?? 0);
        $hombres = max(0, $total - $mujeres);

        return [
            'total' => $total,
            'hombres' => $hombres,
            'mujeres' => $mujeres,
        ];
    }

    /**
     * Personas activas visibles (cobertura pastoral / iglesia completa).
     *
     * @return array{total: int, hombres: int, mujeres: int}
     */
    public function contarPersonasActivasPorGenero(string $filtroRol = '1=1'): array {
        $sql = "SELECT
                    COUNT(*) AS Total,
                    SUM(
                        CASE
                            WHEN LOWER(COALESCE(p.Genero, '')) LIKE '%mujer%'
                              OR LOWER(COALESCE(p.Genero, '')) LIKE '%femen%'
                            THEN 1 ELSE 0
                        END
                    ) AS Mujeres
                FROM {$this->table} p
                WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND {$filtroRol}";

        $rows = $this->query($sql);
        $total = (int)($rows[0]['Total'] ?? 0);
        $mujeres = (int)($rows[0]['Mujeres'] ?? 0);

        return [
            'total' => $total,
            'hombres' => max(0, $total - $mujeres),
            'mujeres' => $mujeres,
        ];
    }

    /**
     * Total de lÃ­deres de cÃ©lula visibles segÃºn aislamiento.
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
     * Resumen de lÃ­deres de cÃ©lula con actividad y cantidad de personas.
     */
    public function getResumenLideresCelulaWithRole($filtroRol) {
        $sql = "SELECT
                    p.Id_Persona,
                    p.Numero_Documento,
                    p.Nombre,
                    p.Apellido,
                    p.Email,
                    p.Genero,
                    p.Telefono,
                    p.Direccion,
                    p.Id_Lider,
                    p.Id_Ministerio,
                    p.Id_Rol,
                    p.Numero_Cupo,
                    p.Ultimo_Acceso,
                    m.Nombre_Ministerio,
                    COALESCE(r.Nombre_Rol, '') AS Nombre_Rol,
                    TRIM(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, ''))) AS Nombre_Lider,
                    CASE WHEN cel.Id_Persona IS NULL THEN 0 ELSE 1 END AS Es_Lider_Celula,
                    CASE WHEN l12r.Id_Persona IS NULL THEN 0 ELSE 1 END AS Es_Lider_12,
                    CASE WHEN l144r.Id_Persona IS NULL THEN 0 ELSE 1 END AS Es_Lider_144,
                    CASE
                        WHEN cel.Id_Persona IS NOT NULL AND l12r.Id_Persona IS NOT NULL THEN 'Ambos'
                        WHEN cel.Id_Persona IS NOT NULL AND l144r.Id_Persona IS NOT NULL THEN 'Celula y 144'
                        WHEN l12r.Id_Persona IS NOT NULL AND l144r.Id_Persona IS NOT NULL THEN '12 y 144'
                        WHEN cel.Id_Persona IS NOT NULL THEN 'Lider de celula'
                        WHEN l144r.Id_Persona IS NOT NULL THEN 'Lider de 144'
                        WHEN l12r.Id_Persona IS NOT NULL THEN 'Lider de 12'
                        ELSE 'Sin clasificacion'
                    END AS Tipo_Liderazgo,
                    COALESCE(per.Total_Personas, 0) AS Total_Personas,
                    rep.Ultimo_Reporte_Celula
                FROM persona p
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN rol r ON r.Id_Rol = p.Id_Rol
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
                    SELECT p144.Id_Persona
                    FROM persona p144
                    LEFT JOIN rol r144 ON r144.Id_Rol = p144.Id_Rol
                    WHERE LOWER(COALESCE(r144.Nombre_Rol, '')) LIKE '%lider de 144%'
                       OR LOWER(COALESCE(r144.Nombre_Rol, '')) LIKE '%lider 144%'
                       OR LOWER(COALESCE(r144.Nombre_Rol, '')) LIKE '%lideres de 144%'
                    GROUP BY p144.Id_Persona
                ) l144r ON l144r.Id_Persona = p.Id_Persona
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
                WHERE (cel.Id_Persona IS NOT NULL OR l12r.Id_Persona IS NOT NULL OR l144r.Id_Persona IS NOT NULL)
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND $filtroRol
                ORDER BY p.Apellido, p.Nombre";

        return $this->query($sql);
    }

    /**
     * Condición SQL: roles de liderazgo (excluidos del listado de discípulos).
     * Nota: Id_Rol=3 (Lider de Celula) NO se excluye solo por rol; ver sqlEsLiderazgoActivoEnRed.
     */
    private function sqlEsRolJerarquiaLiderazgo(string $aliasPersona = 'p', string $aliasRol = 'r'): string {
        $nombreRol = 'LOWER(COALESCE(' . $aliasRol . '.Nombre_Rol, \'\'))';

        return '('
            . $aliasPersona . '.Id_Rol IN (3, 8)'
            . ' OR ' . $nombreRol . " LIKE '%pastor%'"
            . ' OR ' . $nombreRol . " LIKE '%admin%'"
            . ' OR ' . $nombreRol . " LIKE '%lider de 12%'"
            . ' OR ' . $nombreRol . " LIKE '%lider 12%'"
            . ' OR ' . $nombreRol . " LIKE '%lideres de 12%'"
            . ' OR ' . $nombreRol . " LIKE '%lider de 144%'"
            . ' OR ' . $nombreRol . " LIKE '%lider 144%'"
            . ' OR ' . $nombreRol . " LIKE '%lideres de 144%'"
            . ' OR ' . $nombreRol . " LIKE '%lider de celula%'"
            . ' OR ' . $nombreRol . " LIKE '%lider celula%'"
            . ')';
    }

    /**
     * Personas que SÍ aparecen en el listado de liderazgo de Discipular
     * (líder real de una célula, o rol líder 12 / 144).
     * Usar esto para excluir discípulos: evita el hueco de "rol Líder de Célula"
     * sin ser Id_Lider de ninguna fila en celula (quedaban invisibles).
     */
    private function sqlEsLiderazgoActivoEnRed(string $aliasPersona = 'p', string $aliasRol = 'r'): string {
        $nombreRol = 'LOWER(COALESCE(' . $aliasRol . '.Nombre_Rol, \'\'))';

        return '('
            . 'EXISTS (SELECT 1 FROM celula cx WHERE cx.Id_Lider = ' . $aliasPersona . '.Id_Persona)'
            . ' OR ' . $aliasPersona . '.Id_Rol = 8'
            . ' OR ' . $nombreRol . " LIKE '%pastor%'"
            . ' OR ' . $nombreRol . " LIKE '%admin%'"
            . ' OR ' . $nombreRol . " LIKE '%lider de 12%'"
            . ' OR ' . $nombreRol . " LIKE '%lider 12%'"
            . ' OR ' . $nombreRol . " LIKE '%lideres de 12%'"
            . ' OR ' . $nombreRol . " LIKE '%lider de 144%'"
            . ' OR ' . $nombreRol . " LIKE '%lider 144%'"
            . ' OR ' . $nombreRol . " LIKE '%lideres de 144%'"
            . ')';
    }

    /**
     * Discípulos / miembros de red (no líderes), filtrados en SQL.
     *
     * @param int[] $idsExcluirLiderazgo
     * @return array<int, array<string, mixed>>
     */
    public function getDiscipulosRedWithRole(string $filtroRol, array $idsExcluirLiderazgo = [], int $idMinisterio = 0): array {
        $idsExcluirLiderazgo = array_values(array_unique(array_filter(array_map('intval', $idsExcluirLiderazgo), static function ($id) {
            return $id > 0;
        })));

        $sql = "SELECT p.Id_Persona,
                    p.Id_Ministerio,
                    p.Id_Rol,
                    p.Id_Lider,
                    p.Id_Celula,
                    p.Numero_Documento,
                    p.Nombre,
                    p.Apellido,
                    p.Email,
                    p.Telefono,
                    p.Genero,
                    p.Numero_Cupo,
                    c.Nombre_Celula,
                    r.Nombre_Rol,
                    m.Nombre_Ministerio,
                    TRIM(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, ''))) AS Nombre_Lider
                FROM {$this->table} p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND {$filtroRol}
                AND NOT " . $this->sqlEsLiderazgoActivoEnRed('p', 'r') . "
                AND ((p.Id_Lider IS NOT NULL AND p.Id_Lider > 0) OR (p.Id_Ministerio IS NOT NULL AND p.Id_Ministerio > 0))";

        $params = [];
        $idMinisterio = (int)$idMinisterio;
        if ($idMinisterio > 0) {
            $sql .= ' AND p.Id_Ministerio = ?';
            $params[] = $idMinisterio;
        }

        if (!empty($idsExcluirLiderazgo)) {
            $placeholders = implode(',', array_fill(0, count($idsExcluirLiderazgo), '?'));
            $sql .= " AND p.Id_Persona NOT IN ({$placeholders})";
            $params = array_merge($params, $idsExcluirLiderazgo);
        }

        $sql .= ' ORDER BY p.Apellido, p.Nombre, p.Id_Persona';

        return $this->query($sql, $params);
    }

    /**
     * Total de discípulos en red (misma condición que getDiscipulosRedWithRole).
     */
    public function contarDiscipulosRedWithRole(string $filtroRol, array $idsExcluirLiderazgo = [], int $idMinisterio = 0): int {
        $idsExcluirLiderazgo = array_values(array_unique(array_filter(array_map('intval', $idsExcluirLiderazgo), static function ($id) {
            return $id > 0;
        })));

        $sql = "SELECT COUNT(*) AS Total
                FROM {$this->table} p
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND {$filtroRol}
                AND NOT " . $this->sqlEsLiderazgoActivoEnRed('p', 'r') . "
                AND ((p.Id_Lider IS NOT NULL AND p.Id_Lider > 0) OR (p.Id_Ministerio IS NOT NULL AND p.Id_Ministerio > 0))";

        $params = [];
        $idMinisterio = (int)$idMinisterio;
        if ($idMinisterio > 0) {
            $sql .= ' AND p.Id_Ministerio = ?';
            $params[] = $idMinisterio;
        }

        if (!empty($idsExcluirLiderazgo)) {
            $placeholders = implode(',', array_fill(0, count($idsExcluirLiderazgo), '?'));
            $sql .= " AND p.Id_Persona NOT IN ({$placeholders})";
            $params = array_merge($params, $idsExcluirLiderazgo);
        }

        $rows = $this->query($sql, $params);

        return (int)($rows[0]['Total'] ?? 0);
    }

    /**
     * Personas activas sin líder ni ministerio (fuera de la red pastoral).
     * Usado en Discipular al buscar: el listado normal exige Id_Lider o Id_Ministerio,
     * por eso fichas del Tour u otras sin asignación no aparecían.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buscarPersonasSinAsignacionRed(string $termino, int $limite = 80): array {
        $termino = preg_replace('/\s+/u', ' ', trim((string)$termino));
        if ($termino === '') {
            return [];
        }

        $limite = max(1, min(200, (int)$limite));
        $params = [];
        $partesOr = [];

        $nombreCompleto = "TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, '')))";
        $nombreInvertido = "TRIM(CONCAT(COALESCE(p.Apellido, ''), ' ', COALESCE(p.Nombre, '')))";
        $partesOr[] = "(LOWER($nombreCompleto) LIKE LOWER(?) OR LOWER($nombreInvertido) LIKE LOWER(?))";
        $likeNombre = '%' . $termino . '%';
        $params[] = $likeNombre;
        $params[] = $likeNombre;

        $docNorm = $this->normalizarDocumentoParaComparacion($termino);
        if ($docNorm !== '') {
            $partesOr[] = "REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(p.Numero_Documento, ''))), ' ', ''), '.', ''), '-', '') LIKE ?";
            $params[] = '%' . $docNorm . '%';
        }

        $soloDigitos = preg_replace('/\D+/', '', $termino);
        if ($soloDigitos !== '') {
            $telExpr = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(p.Telefono, '')), ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '')";
            $partesOr[] = "$telExpr LIKE ?";
            $params[] = '%' . $soloDigitos . '%';
        }

        $whereBuscar = '(' . implode(' OR ', $partesOr) . ')';

        $sql = "SELECT p.Id_Persona,
                    p.Id_Ministerio,
                    p.Id_Rol,
                    p.Id_Lider,
                    p.Id_Celula,
                    p.Numero_Documento,
                    p.Nombre,
                    p.Apellido,
                    p.Email,
                    p.Telefono,
                    p.Genero,
                    p.Numero_Cupo,
                    COALESCE(c.Nombre_Celula, '') AS Nombre_Celula,
                    COALESCE(r.Nombre_Rol, '') AS Nombre_Rol,
                    COALESCE(m.Nombre_Ministerio, '') AS Nombre_Ministerio,
                    '' AS Nombre_Lider,
                    1 AS Sin_Asignacion_Red
                FROM {$this->table} p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND (p.Id_Lider IS NULL OR p.Id_Lider <= 0)
                  AND (p.Id_Ministerio IS NULL OR p.Id_Ministerio <= 0)
                  AND NOT " . $this->sqlEsLiderazgoActivoEnRed('p', 'r') . "
                  AND {$whereBuscar}
                ORDER BY p.Apellido, p.Nombre, p.Id_Persona
                LIMIT {$limite}";

        return $this->query($sql, $params);
    }

    /**
     * Búsqueda de respaldo en Discipular: cualquier persona activa visible por filtro
     * que coincida con el texto y aún no esté en los listados ya cargados.
     * Cubre huecos (rol liderazgo sin célula, aislamiento, etc.).
     *
     * @param int[] $idsExcluir
     * @return array<int, array<string, mixed>>
     */
    public function buscarPersonasVisiblesNoListadasDiscipular(
        string $termino,
        string $filtroRol,
        array $idsExcluir = [],
        int $idMinisterio = 0,
        int $limite = 100
    ): array {
        $termino = preg_replace('/\s+/u', ' ', trim((string)$termino));
        $filtroRol = trim($filtroRol);
        if ($termino === '' || $filtroRol === '') {
            return [];
        }

        $limite = max(1, min(200, (int)$limite));
        $idsExcluir = array_values(array_unique(array_filter(array_map('intval', $idsExcluir), static function ($id) {
            return $id > 0;
        })));

        $params = [];
        $partesOr = [];

        $nombreCompleto = "TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, '')))";
        $nombreInvertido = "TRIM(CONCAT(COALESCE(p.Apellido, ''), ' ', COALESCE(p.Nombre, '')))";
        $partesOr[] = "(LOWER($nombreCompleto) LIKE LOWER(?) OR LOWER($nombreInvertido) LIKE LOWER(?))";
        $likeNombre = '%' . $termino . '%';
        $params[] = $likeNombre;
        $params[] = $likeNombre;

        $docNorm = $this->normalizarDocumentoParaComparacion($termino);
        if ($docNorm !== '') {
            $partesOr[] = "REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(p.Numero_Documento, ''))), ' ', ''), '.', ''), '-', '') LIKE ?";
            $params[] = '%' . $docNorm . '%';
        }

        $soloDigitos = preg_replace('/\D+/', '', $termino);
        if ($soloDigitos !== '') {
            $telExpr = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(p.Telefono, '')), ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '')";
            $partesOr[] = "$telExpr LIKE ?";
            $params[] = '%' . $soloDigitos . '%';
        }

        $whereBuscar = '(' . implode(' OR ', $partesOr) . ')';

        $sql = "SELECT p.Id_Persona,
                    p.Id_Ministerio,
                    p.Id_Rol,
                    p.Id_Lider,
                    p.Id_Celula,
                    p.Numero_Documento,
                    p.Nombre,
                    p.Apellido,
                    p.Email,
                    p.Telefono,
                    p.Genero,
                    p.Numero_Cupo,
                    COALESCE(c.Nombre_Celula, '') AS Nombre_Celula,
                    COALESCE(r.Nombre_Rol, '') AS Nombre_Rol,
                    COALESCE(m.Nombre_Ministerio, '') AS Nombre_Ministerio,
                    TRIM(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, ''))) AS Nombre_Lider,
                    CASE
                        WHEN (p.Id_Lider IS NULL OR p.Id_Lider <= 0)
                         AND (p.Id_Ministerio IS NULL OR p.Id_Ministerio <= 0) THEN 1
                        ELSE 0
                    END AS Sin_Asignacion_Red,
                    CASE
                        WHEN " . $this->sqlEsRolJerarquiaLiderazgo('p', 'r') . "
                         AND NOT " . $this->sqlEsLiderazgoActivoEnRed('p', 'r') . " THEN 1
                        ELSE 0
                    END AS Rol_Liderazgo_Sin_Celula
                FROM {$this->table} p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND ({$filtroRol})
                  AND {$whereBuscar}";

        $idMinisterio = (int)$idMinisterio;
        if ($idMinisterio > 0) {
            // En vista de un ministerio: incluir del ministerio o sin ministerio (para poder asignar).
            $sql .= ' AND (p.Id_Ministerio = ? OR p.Id_Ministerio IS NULL OR p.Id_Ministerio <= 0)';
            $params[] = $idMinisterio;
        }

        if (!empty($idsExcluir)) {
            $placeholders = implode(',', array_fill(0, count($idsExcluir), '?'));
            $sql .= " AND p.Id_Persona NOT IN ({$placeholders})";
            $params = array_merge($params, $idsExcluir);
        }

        $sql .= " ORDER BY p.Apellido, p.Nombre, p.Id_Persona LIMIT {$limite}";

        return $this->query($sql, $params);
    }

    /**
     * Personas activas para asignación de cupos (columnas mínimas).
     *
     * @param string $filtroRol
     * @param int $idMinisterio Si > 0, solo ese ministerio
     * @param bool $soloLiderCelulaO144 Solo roles líder de célula / líder de 144
     * @return array<int, array<string, mixed>>
     */
    public function getPersonasAsignablesParaEquipos(
        string $filtroRol,
        int $idMinisterio = 0,
        bool $soloLiderCelulaO144 = false
    ): array {
        $sql = "SELECT p.Id_Persona,
                    p.Id_Ministerio,
                    p.Id_Rol,
                    p.Id_Lider,
                    p.Nombre,
                    p.Apellido,
                    p.Genero,
                    p.Numero_Documento,
                    p.Telefono,
                    p.Email,
                    COALESCE(r.Nombre_Rol, '') AS Nombre_Rol,
                    TRIM(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, ''))) AS Nombre_Lider
                FROM {$this->table} p
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND {$filtroRol}";

        $params = [];
        $idMinisterio = (int)$idMinisterio;
        if ($idMinisterio > 0) {
            $sql .= ' AND p.Id_Ministerio = ?';
            $params[] = $idMinisterio;
        }

        if ($soloLiderCelulaO144) {
            $nombreRol = "LOWER(COALESCE(r.Nombre_Rol, ''))";
            $sql .= " AND (
                p.Id_Rol IN (3, 13)
                OR {$nombreRol} LIKE '%lider de celula%'
                OR {$nombreRol} LIKE '%lider celula%'
                OR {$nombreRol} LIKE '%lider de 144%'
                OR {$nombreRol} LIKE '%lider 144%'
                OR {$nombreRol} LIKE '%lideres de 144%'
            )";
        }

        $sql .= ' ORDER BY p.Apellido, p.Nombre, p.Id_Persona';

        return $params === [] ? $this->query($sql) : $this->query($sql, $params);
    }

    /**
     * Candidatos a líderes principales (solo roles de liderazgo).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCandidatosLideresPrincipalesRows(string $filtroRol): array {
        $sql = "SELECT p.Id_Persona,
                    p.Id_Rol,
                    p.Nombre,
                    p.Apellido,
                    p.Genero,
                    p.Id_Ministerio,
                    COALESCE(r.Nombre_Rol, '') AS Nombre_Rol,
                    COALESCE(m.Nombre_Ministerio, '') AS Nombre_Ministerio
                FROM {$this->table} p
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND {$filtroRol}
                AND " . $this->sqlEsRolJerarquiaLiderazgo('p', 'r') . "
                AND LOWER(COALESCE(r.Nombre_Rol, '')) NOT LIKE '%admin%'
                ORDER BY p.Apellido, p.Nombre, p.Id_Persona";

        return $this->query($sql);
    }

    private function construirMapaJerarquiaVisible($filtroRol, $idMinisterio = 0) {
        $sql = "SELECT p.Id_Persona, p.Id_Lider
            FROM persona p
            WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
            AND $filtroRol";

        $params = [];
        $idMinisterio = (int)$idMinisterio;
        if ($idMinisterio > 0) {
            $sql .= " AND p.Id_Ministerio = ?";
            $params[] = $idMinisterio;
        }

        $rows = $this->query($sql, $params);

        $hijosPorLider = [];
        foreach ($rows as $row) {
            $idPersona = (int)($row['Id_Persona'] ?? 0);
            $idLider = (int)($row['Id_Lider'] ?? 0);
            if ($idPersona <= 0 || $idLider <= 0) {
                continue;
            }

            if (!isset($hijosPorLider[$idLider])) {
                $hijosPorLider[$idLider] = [];
            }
            $hijosPorLider[$idLider][] = $idPersona;
        }

        return $hijosPorLider;
    }

    private function contarDescendenciaDesdeMapa($idLider, array $hijosPorLider) {
        $idLider = (int)$idLider;
        if ($idLider <= 0 || empty($hijosPorLider[$idLider])) {
            return 0;
        }

        $visitados = [];
        $stack = array_values(array_unique(array_map('intval', (array)$hijosPorLider[$idLider])));

        while (!empty($stack)) {
            $actual = array_pop($stack);
            if ($actual <= 0 || isset($visitados[$actual])) {
                continue;
            }

            $visitados[$actual] = true;
            if (!empty($hijosPorLider[$actual])) {
                foreach ((array)$hijosPorLider[$actual] as $hijo) {
                    $hijo = (int)$hijo;
                    if ($hijo > 0 && !isset($visitados[$hijo])) {
                        $stack[] = $hijo;
                    }
                }
            }
        }

        return count($visitados);
    }

    public function getResumenRedLideresWithRole(array $idsLideres, $filtroRol, $idMinisterio = 0, $limiteEquipo = 12) {
        $idsLideres = array_values(array_unique(array_filter(array_map('intval', $idsLideres), static function($id) {
            return $id > 0;
        })));

        if (empty($idsLideres)) {
            return [];
        }

        $limiteEquipo = max(1, (int)$limiteEquipo);
        $hijosPorLider = $this->construirMapaJerarquiaVisible($filtroRol, $idMinisterio);

        $resultado = [];
        foreach ($idsLideres as $idLider) {
            $equipoDirecto = isset($hijosPorLider[$idLider])
                ? count(array_unique(array_map('intval', (array)$hijosPorLider[$idLider])))
                : 0;
            $redCompleta = $this->contarDescendenciaDesdeMapa($idLider, $hijosPorLider);

            $resultado[$idLider] = [
                'equipo_directo' => $equipoDirecto,
                'red_total' => $redCompleta,
                'limite_equipo' => $limiteEquipo,
                'cupos_disponibles' => max(0, $limiteEquipo - $equipoDirecto),
                'cupo_lleno' => $equipoDirecto >= $limiteEquipo,
            ];
        }

        return $resultado;
    }

    public function getConsolidados() {
        $sql = "SELECT * FROM persona WHERE Id_Lider IS NOT NULL AND Id_Ministerio IS NOT NULL AND Id_Celula IS NOT NULL AND Rol = 'ganar'";
        return $this->db->query($sql)->fetchAll();
    }

    public function actualizarRol($idPersona, $nuevoRol) {
        $sql = "UPDATE persona SET Rol = :nuevoRol WHERE Id_Persona = :idPersona";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':nuevoRol', $nuevoRol);
        $stmt->bindParam(':idPersona', $idPersona);
        $stmt->execute();
    }

    public function create($data) {
        $id = parent::create($data);
        if ($id && isset($data['Id_Rol'])) {
            $this->sincronizarUserRolesDesdePersona((int)$id, (int)$data['Id_Rol']);
        }
        return $id;
    }

    public function update($id, $data) {
        $result = parent::update($id, $data);
        if ($result && isset($data['Id_Rol'])) {
            $this->sincronizarUserRolesDesdePersona((int)$id, (int)$data['Id_Rol']);
        }
        return $result;
    }

    private function sincronizarUserRolesDesdePersona(int $idPersona, int $idRol): void {
        if ($idPersona <= 0 || $idRol <= 0) {
            return;
        }

        require_once APP . '/Models/UserRole.php';
        $userRole = new UserRole();
        $userRole->sincronizarRolPrincipal($idPersona, $idRol);
    }
}


