<?php

require_once APP . '/Models/BaseModel.php';

class EscuelaFormacionInscripcion extends BaseModel {
    protected $table = 'escuela_formacion_inscripcion';
    protected $tablePagos = 'escuela_formacion_pago_movimiento';
    protected $primaryKey = 'Id_Inscripcion';
    private $columnExistsCache = [];

    public function __construct() {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            Id_Inscripcion INT AUTO_INCREMENT PRIMARY KEY,
            Id_Persona INT NULL,
            Nombre VARCHAR(160) NOT NULL,
            Genero VARCHAR(40) NULL,
            Edad TINYINT UNSIGNED NULL,
            Telefono VARCHAR(40) NULL,
            Cedula VARCHAR(50) NULL,
            Lider VARCHAR(160) NULL,
            Id_Ministerio INT NULL,
            Nombre_Ministerio VARCHAR(160) NULL,
            Programa VARCHAR(40) NOT NULL,
            Fuente VARCHAR(80) NOT NULL DEFAULT 'Formulario público',
            Metodo_Pago VARCHAR(60) NULL,
            Recibido_Por VARCHAR(160) NULL,
            Referencia_Pago VARCHAR(120) NULL,
            Asistio_Clase TINYINT(1) NULL,
            Fecha_Asistencia_Clase DATETIME NULL,
            Fecha_Registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_programa (Programa),
            KEY idx_persona (Id_Persona),
            KEY idx_telefono (Telefono),
            KEY idx_cedula (Cedula)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Genero']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Genero VARCHAR(40) NULL AFTER Nombre");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Genero en escuela_formacion_inscripcion: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Edad']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Edad TINYINT UNSIGNED NULL AFTER Genero");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Edad en escuela_formacion_inscripcion: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Asistio_Clase']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Asistio_Clase TINYINT(1) NULL AFTER Fuente");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Asistio_Clase en escuela_formacion_inscripcion: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Metodo_Pago']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Metodo_Pago VARCHAR(60) NULL AFTER Fuente");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Metodo_Pago en escuela_formacion_inscripcion: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Referencia_Pago']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Referencia_Pago VARCHAR(120) NULL AFTER Metodo_Pago");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Referencia_Pago en escuela_formacion_inscripcion: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Recibido_Por']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Recibido_Por VARCHAR(160) NULL AFTER Metodo_Pago");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Recibido_Por en escuela_formacion_inscripcion: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Fecha_Asistencia_Clase']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Fecha_Asistencia_Clase DATETIME NULL AFTER Asistio_Clase");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Fecha_Asistencia_Clase en escuela_formacion_inscripcion: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Tipo_Pago']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Tipo_Pago VARCHAR(20) NULL AFTER Metodo_Pago");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Tipo_Pago en escuela_formacion_inscripcion: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Valor_Pago']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Valor_Pago DECIMAL(12,2) NULL AFTER Tipo_Pago");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Valor_Pago en escuela_formacion_inscripcion: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Segmento_Preferido']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Segmento_Preferido VARCHAR(60) NULL AFTER Valor_Pago");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Segmento_Preferido en escuela_formacion_inscripcion: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Entrego_Libro']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Entrego_Libro TINYINT(1) NULL AFTER Valor_Pago");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Entrego_Libro en escuela_formacion_inscripcion: ' . $e->getMessage());
        }

        $sqlPagos = "CREATE TABLE IF NOT EXISTS {$this->tablePagos} (
            Id_Pago INT AUTO_INCREMENT PRIMARY KEY,
            Id_Inscripcion INT NOT NULL,
            Id_Persona INT NULL,
            Nombre VARCHAR(160) NOT NULL,
            Cedula VARCHAR(50) NULL,
            Telefono VARCHAR(40) NULL,
            Programa VARCHAR(60) NOT NULL,
            Metodo_Pago VARCHAR(60) NOT NULL,
            Recibido_Por VARCHAR(160) NULL,
            Tipo_Pago VARCHAR(20) NOT NULL DEFAULT 'completo',
            Valor_Pago DECIMAL(12,2) NOT NULL DEFAULT 0,
            Referencia_Pago VARCHAR(120) NULL,
            Fecha_Registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_pago_inscripcion (Id_Inscripcion),
            KEY idx_pago_cedula (Cedula),
            KEY idx_pago_fecha (Fecha_Registro)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sqlPagos);

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->tablePagos} LIKE ?");
            $stmt->execute(['Recibido_Por']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->tablePagos} ADD COLUMN Recibido_Por VARCHAR(160) NULL AFTER Metodo_Pago");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Recibido_Por en escuela_formacion_pago_movimiento: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->tablePagos} LIKE ?");
            $stmt->execute(['Entrego_Libro']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->tablePagos} ADD COLUMN Entrego_Libro TINYINT(1) NULL AFTER Valor_Pago");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Entrego_Libro en escuela_formacion_pago_movimiento: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Documentos']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Documentos TEXT NULL AFTER Fuente");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Documentos en escuela_formacion_inscripcion: ' . $e->getMessage());
        }
    }

    /**
     * @return array<int, array{nombre:string, archivo:string, url:string, fecha:string}>
     */
    public function decodificarDocumentos(?string $json): array {
        if ($json === null || trim($json) === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }
            $archivo = trim((string)($item['archivo'] ?? ''));
            $nombre = trim((string)($item['nombre'] ?? $archivo));
            if ($archivo === '') {
                continue;
            }
            $out[] = [
                'nombre' => $nombre !== '' ? $nombre : $archivo,
                'archivo' => $archivo,
                'url' => trim((string)($item['url'] ?? '')),
                'fecha' => trim((string)($item['fecha'] ?? '')),
            ];
        }
        return $out;
    }

    public const EXTENSIONES_DOCUMENTOS_UV = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];
    public const TAMANO_MAX_DOCUMENTO_UV = 8 * 1024 * 1024;

    /**
     * Guarda archivos subidos y los agrega al JSON Documentos de la inscripción.
     *
     * @param mixed $filesInput $_FILES['documentos_uv'] o null
     * @return array{guardados:int, errores:array<int,string>}
     */
    public function adjuntarDocumentosDesdeUpload(int $idInscripcion, $filesInput): array {
        $idInscripcion = (int)$idInscripcion;
        if ($idInscripcion <= 0) {
            return ['guardados' => 0, 'errores' => []];
        }

        $archivos = $this->normalizarArchivosMultiples(is_array($filesInput) ? $filesInput : []);
        if ($archivos === []) {
            return ['guardados' => 0, 'errores' => []];
        }

        $inscripcion = $this->getByIdInscripcion($idInscripcion);
        if (empty($inscripcion)) {
            throw new Exception('La inscripción no existe.');
        }

        $existentes = $this->decodificarDocumentos((string)($inscripcion['Documentos'] ?? ''));
        $dir = $this->directorioDocumentosInscripcion($idInscripcion);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new Exception('No se pudo crear la carpeta de documentos.');
        }

        $basePublic = rtrim((string)(defined('PUBLIC_URL') ? PUBLIC_URL : ''), '/');
        $guardados = 0;
        $errores = [];

        foreach ($archivos as $archivo) {
            $nombreOriginal = trim((string)($archivo['name'] ?? ''));
            $tmp = (string)($archivo['tmp_name'] ?? '');
            $error = (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
            $size = (int)($archivo['size'] ?? 0);

            if ($error === UPLOAD_ERR_NO_FILE || $nombreOriginal === '' || $tmp === '') {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                $errores[] = 'Error al subir «' . $nombreOriginal . '».';
                continue;
            }
            if ($size <= 0 || $size > self::TAMANO_MAX_DOCUMENTO_UV) {
                $errores[] = '«' . $nombreOriginal . '» supera el máximo de 8 MB.';
                continue;
            }

            $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            if (!in_array($ext, self::EXTENSIONES_DOCUMENTOS_UV, true)) {
                $errores[] = 'Tipo no permitido: «' . $nombreOriginal . '».';
                continue;
            }

            $slug = preg_replace('/[^a-zA-Z0-9._-]+/', '_', pathinfo($nombreOriginal, PATHINFO_FILENAME)) ?? 'documento';
            $slug = trim(substr($slug, 0, 60), '_');
            if ($slug === '') {
                $slug = 'documento';
            }

            $nombreFinal = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $slug . '.' . $ext;
            $destino = $dir . DIRECTORY_SEPARATOR . $nombreFinal;

            if (!is_uploaded_file($tmp) || !@move_uploaded_file($tmp, $destino)) {
                $errores[] = 'No se pudo guardar «' . $nombreOriginal . '».';
                continue;
            }

            $url = $basePublic . '/uploads/escuelas_uv/' . $idInscripcion . '/' . rawurlencode($nombreFinal);
            $existentes[] = [
                'nombre' => $nombreOriginal,
                'archivo' => $nombreFinal,
                'url' => $url,
                'fecha' => date('Y-m-d H:i'),
            ];
            $guardados++;
        }

        if ($guardados === 0) {
            return ['guardados' => 0, 'errores' => $errores];
        }

        $json = json_encode($existentes, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new Exception('No se pudo registrar la lista de documentos.');
        }

        $this->execute(
            "UPDATE {$this->table} SET Documentos = ? WHERE Id_Inscripcion = ?",
            [$json, $idInscripcion]
        );

        return ['guardados' => $guardados, 'errores' => $errores];
    }

    private function directorioDocumentosInscripcion(int $idInscripcion): string {
        $root = defined('ROOT') ? ROOT : dirname(__DIR__, 2);
        return $root . '/public/uploads/escuelas_uv/' . $idInscripcion;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<int, array<string, mixed>>
     */
    private function normalizarArchivosMultiples(array $input): array {
        if ($input === [] || !isset($input['name'])) {
            return [];
        }

        if (!is_array($input['name'])) {
            if ((int)($input['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                return [];
            }
            return [$input];
        }

        $out = [];
        foreach ($input['name'] as $i => $name) {
            if ((int)($input['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name' => $name,
                'type' => $input['type'][$i] ?? '',
                'tmp_name' => $input['tmp_name'][$i] ?? '',
                'error' => $input['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $input['size'][$i] ?? 0,
            ];
        }

        return $out;
    }

    public function actualizarPagoInscripcion($idInscripcion, $metodoPago, $tipoPago, $valorPago, $referenciaPago, $recibidoPor = '', $entregoLibro = null) {
        $idInscripcion = (int)$idInscripcion;
        if ($idInscripcion <= 0) {
            return false;
        }

        return $this->execute(
            "UPDATE {$this->table}
             SET Metodo_Pago = ?,
                 Recibido_Por = ?,
                 Tipo_Pago = ?,
                 Valor_Pago = ?,
                 Entrego_Libro = ?,
                 Referencia_Pago = ?
             WHERE Id_Inscripcion = ?",
            [
                trim((string)$metodoPago) !== '' ? trim((string)$metodoPago) : null,
                trim((string)$recibidoPor) !== '' ? trim((string)$recibidoPor) : null,
                trim((string)$tipoPago) !== '' ? trim((string)$tipoPago) : null,
                $valorPago !== null ? (float)$valorPago : null,
                $entregoLibro === null ? null : ((int)$entregoLibro === 1 ? 1 : 0),
                trim((string)$referenciaPago) !== '' ? trim((string)$referenciaPago) : null,
                $idInscripcion
            ]
        );
    }

    public function registrarMovimientoPago(array $inscripcion, $metodoPago, $tipoPago, $valorPago, $referenciaPago, $recibidoPor = '', $entregoLibro = null) {
        $idInscripcion = (int)($inscripcion['Id_Inscripcion'] ?? 0);
        if ($idInscripcion <= 0) {
            return false;
        }

        $programa = trim((string)($inscripcion['Programa'] ?? ''));
        $cedula = trim((string)($inscripcion['Cedula'] ?? ''));
        $idPersona = (int)($inscripcion['Id_Persona'] ?? 0);
        $tipoPago = $this->resolverTipoPagoTrasMovimiento($programa, (string)$tipoPago, (float)$valorPago, $cedula, $idPersona);

        $ok = $this->execute(
            "INSERT INTO {$this->tablePagos}
             (Id_Inscripcion, Id_Persona, Nombre, Cedula, Telefono, Programa, Metodo_Pago, Recibido_Por, Tipo_Pago, Valor_Pago, Entrego_Libro, Referencia_Pago)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $idInscripcion,
                $idPersona > 0 ? $idPersona : null,
                (string)($inscripcion['Nombre'] ?? ''),
                $cedula,
                (string)($inscripcion['Telefono'] ?? ''),
                $programa,
                (string)$metodoPago,
                trim((string)$recibidoPor) !== '' ? trim((string)$recibidoPor) : null,
                $tipoPago,
                (float)$valorPago,
                $entregoLibro === null ? null : ((int)$entregoLibro === 1 ? 1 : 0),
                (string)$referenciaPago
            ]
        );

        if ($ok && $this->programaUsaUmbralPagoCompletoUv($programa)) {
            $this->sincronizarTiposPagoAcumuladoCompleto($programa, 180000, $cedula, $idPersona);
        }

        return $ok;
    }

    public function programaUsaUmbralPagoCompletoUv(string $programa): bool {
        $programa = strtolower(trim($programa));
        return in_array($programa, ['universidad_vida', 'encuentro', 'bautismo'], true);
    }

    public function getTotalPagadoAcumuladoPorClave(string $cedula, int $idPersona, string $programa): float {
        $cedula = trim($cedula);
        $programa = trim($programa);
        $idPersona = (int)$idPersona;

        $where = ['COALESCE(s.Valor_Pago, 0) > 0'];
        $params = [];

        $wherePrograma = $this->getSqlFiltroProgramaPagos($programa, 's');
        if ($wherePrograma !== '') {
            $where[] = $wherePrograma;
        }

        if ($cedula !== '') {
            $where[] = 'TRIM(COALESCE(s.Cedula, \'\')) = ?';
            $params[] = $cedula;
        } elseif ($idPersona > 0) {
            $where[] = 's.Id_Persona = ?';
            $params[] = $idPersona;
        } else {
            return 0.0;
        }

        $sql = "SELECT COALESCE(SUM(COALESCE(s.Valor_Pago, 0)), 0) AS Total
                FROM {$this->tablePagos} s
                WHERE " . implode(' AND ', $where);

        $rows = $this->query($sql, $params);
        return (float)($rows[0]['Total'] ?? 0);
    }

    public function resolverTipoPagoTrasMovimiento(string $programa, string $tipoPago, float $valorNuevo, string $cedula, int $idPersona): string {
        $tipoPago = strtolower(trim($tipoPago)) === 'completo' ? 'completo' : 'abono';
        if (!$this->programaUsaUmbralPagoCompletoUv($programa) || $valorNuevo <= 0) {
            return $tipoPago;
        }

        $acumulado = $this->getTotalPagadoAcumuladoPorClave($cedula, $idPersona, $programa) + $valorNuevo;
        if ($acumulado >= 180000) {
            return 'completo';
        }

        return $tipoPago;
    }

    /**
     * Si la persona acumuló >= umbral en el programa, marca todos sus movimientos como pago completo.
     */
    public function sincronizarTiposPagoAcumuladoCompleto(string $programa, float $umbral = 180000, string $cedula = '', int $idPersona = 0): void {
        $programa = trim($programa);
        $umbral = max(1, (float)$umbral);
        $cedula = trim($cedula);
        $idPersona = (int)$idPersona;

        $whereProgramaSub = $this->getSqlFiltroProgramaPagos($programa, 's');
        $whereProgramaMov = $this->getSqlFiltroProgramaPagos($programa, 'm');
        if ($whereProgramaSub === '' || $whereProgramaMov === '') {
            return;
        }

        $filtroPersonaSub = '';
        $filtroPersonaMov = '';
        $paramsSub = [$umbral];
        $paramsMov = [];

        if ($cedula !== '') {
            $filtroPersonaSub = ' AND TRIM(COALESCE(s.Cedula, \'\')) = ?';
            $filtroPersonaMov = ' AND TRIM(COALESCE(m.Cedula, \'\')) = ?';
            $paramsSub[] = $cedula;
            $paramsMov[] = $cedula;
        } elseif ($idPersona > 0) {
            $filtroPersonaSub = ' AND s.Id_Persona = ?';
            $filtroPersonaMov = ' AND m.Id_Persona = ?';
            $paramsSub[] = $idPersona;
            $paramsMov[] = $idPersona;
        }

        $exprClaveSub = "COALESCE(NULLIF(TRIM(s.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(s.Id_Persona, 0)))";
        $exprClaveMov = "COALESCE(NULLIF(TRIM(m.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(m.Id_Persona, 0)))";
        $exprProgSub = "COALESCE(NULLIF(TRIM(s.Programa), ''), 'SIN-PROGRAMA')";
        $exprProgMov = "COALESCE(NULLIF(TRIM(m.Programa), ''), 'SIN-PROGRAMA')";

        $sqlMovimientos = "UPDATE {$this->tablePagos} m
            INNER JOIN (
                SELECT {$exprClaveSub} AS ClavePersona, {$exprProgSub} AS ClavePrograma
                FROM {$this->tablePagos} s
                WHERE {$whereProgramaSub}
                  AND COALESCE(s.Valor_Pago, 0) > 0
                  {$filtroPersonaSub}
                GROUP BY {$exprClaveSub}, {$exprProgSub}
                HAVING SUM(COALESCE(s.Valor_Pago, 0)) >= ?
            ) k ON k.ClavePersona = {$exprClaveMov} AND k.ClavePrograma = {$exprProgMov}
            SET m.Tipo_Pago = 'completo'
            WHERE {$whereProgramaMov}
              AND COALESCE(m.Valor_Pago, 0) > 0
              AND COALESCE(LOWER(TRIM(m.Tipo_Pago)), '') <> 'completo'
              {$filtroPersonaMov}";

        $this->execute($sqlMovimientos, array_merge($paramsSub, $paramsMov));

        $whereProgramaIns = $this->getSqlFiltroProgramaPagos($programa, 'i');
        if ($whereProgramaIns === '') {
            return;
        }

        $filtroPersonaIns = '';
        $paramsIns = [];
        if ($cedula !== '') {
            $filtroPersonaIns = ' AND TRIM(COALESCE(i.Cedula, \'\')) = ?';
            $paramsIns[] = $cedula;
        } elseif ($idPersona > 0) {
            $filtroPersonaIns = ' AND i.Id_Persona = ?';
            $paramsIns[] = $idPersona;
        }

        $exprClaveIns = "COALESCE(NULLIF(TRIM(i.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(i.Id_Persona, 0)))";

        $sqlInscripcion = "UPDATE {$this->table} i
            INNER JOIN (
                SELECT {$exprClaveSub} AS ClavePersona, {$exprProgSub} AS ClavePrograma
                FROM {$this->tablePagos} s
                WHERE {$whereProgramaSub}
                  AND COALESCE(s.Valor_Pago, 0) > 0
                  {$filtroPersonaSub}
                GROUP BY {$exprClaveSub}, {$exprProgSub}
                HAVING SUM(COALESCE(s.Valor_Pago, 0)) >= ?
            ) k ON k.ClavePersona = {$exprClaveIns}
               AND k.ClavePrograma = COALESCE(NULLIF(TRIM(i.Programa), ''), 'SIN-PROGRAMA')
            SET i.Tipo_Pago = 'completo'
            WHERE {$whereProgramaIns}
              AND COALESCE(i.Valor_Pago, 0) > 0
              AND COALESCE(LOWER(TRIM(i.Tipo_Pago)), '') <> 'completo'
              {$filtroPersonaIns}";

        $this->execute($sqlInscripcion, array_merge($paramsSub, $paramsIns));
    }

    public function actualizarAsistenciaClase($idInscripcion, $asistio) {
        $idInscripcion = (int)$idInscripcion;
        if ($idInscripcion <= 0) {
            return false;
        }

        if ($asistio === null) {
            return $this->execute(
                "UPDATE {$this->table}
                 SET Asistio_Clase = NULL,
                     Fecha_Asistencia_Clase = NULL
                 WHERE Id_Inscripcion = ?",
                [$idInscripcion]
            );
        }

        return $this->execute(
            "UPDATE {$this->table}
             SET Asistio_Clase = ?,
                 Fecha_Asistencia_Clase = NOW()
             WHERE Id_Inscripcion = ?",
            [$asistio ? 1 : 0, $idInscripcion]
        );
    }

    public function actualizarSegmentoPreferido($idInscripcion, $segmento) {
        $idInscripcion = (int)$idInscripcion;
        $segmento = trim((string)$segmento);
        
        if ($idInscripcion <= 0) {
            return false;
        }

        // Validar que sea un segmento válido
        $segmentosValidos = ['jovenes', 'teens', 'hombres_adultos', 'mujeres_adultas', 'nivel_1', 'nivel_2', 'nivel_3', ''];
        if (!in_array($segmento, $segmentosValidos, true)) {
            return false;
        }

        return $this->execute(
            "UPDATE {$this->table}
             SET Segmento_Preferido = ?
             WHERE Id_Inscripcion = ?",
            [$segmento !== '' ? $segmento : null, $idInscripcion]
        );
    }

    private function normalizarSoloDigitos($valor) {
        return preg_replace('/\D+/', '', (string)$valor);
    }

    private function tableColumnExists(string $table, string $column): bool {
        $key = strtolower($table . '::' . $column);
        if (array_key_exists($key, $this->columnExistsCache)) {
            return (bool)$this->columnExistsCache[$key];
        }

        try {
            $rows = $this->query("SHOW COLUMNS FROM {$table} LIKE ?", [$column]);
            $exists = !empty($rows);
        } catch (Throwable $e) {
            $exists = false;
        }

        $this->columnExistsCache[$key] = $exists;
        return $exists;
    }

    public function getByIdInscripcion($idInscripcion) {
        $idInscripcion = (int)$idInscripcion;
        if ($idInscripcion <= 0) {
            return null;
        }

        $rows = $this->query(
            "SELECT * FROM {$this->table} WHERE Id_Inscripcion = ? LIMIT 1",
            [$idInscripcion]
        );

        return $rows[0] ?? null;
    }

    public function buscarInscripcionesPorTelefonoOCedula($telefono = '', $cedula = '', $limit = 20) {
        $telefono = $this->normalizarSoloDigitos($telefono);
        $cedula = $this->normalizarSoloDigitos($cedula);
        $limit = max(1, min(50, (int)$limit));

        if ($telefono === '' && $cedula === '') {
            return [];
        }

        $telefonoExpr = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(s.Telefono, ''), ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')";
        $cedulaExpr = "REPLACE(REPLACE(REPLACE(COALESCE(s.Cedula, ''), ' ', ''), '-', ''), '.', '')";

        $where = [];
        $params = [];

        if ($telefono !== '') {
            $where[] = "{$telefonoExpr} = ?";
            $params[] = $telefono;
        }

        if ($cedula !== '') {
            $where[] = "{$cedulaExpr} = ?";
            $params[] = $cedula;
        }

        $sql = "SELECT s.*
                FROM {$this->table} s
                WHERE " . implode(' OR ', $where) . "
                ORDER BY s.Fecha_Registro DESC, s.Id_Inscripcion DESC
                LIMIT {$limit}";

        return $this->query($sql, $params);
    }

    public function existeInscripcionPersonaPrograma($idPersona, $programa) {
        $idPersona = (int)$idPersona;
        $programa = trim((string)$programa);
        if ($idPersona <= 0 || $programa === '') {
            return false;
        }

        $rows = $this->query(
            "SELECT Id_Inscripcion FROM {$this->table} WHERE Id_Persona = ? AND Programa = ? LIMIT 1",
            [$idPersona, $programa]
        );

        return !empty($rows);
    }

    /**
     * Retorna los programas en los que la persona ya está inscrita
     * @return string[] lista de valores Programa
     */
    public function getProgramasInscritosPersona($idPersona) {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0) {
            return [];
        }

        $programas = [];
        $this->agregarProgramasDesdeFilas(
            $this->query(
                "SELECT DISTINCT Programa FROM {$this->table} WHERE Id_Persona = ?",
                [$idPersona]
            ),
            $programas
        );

        $cedulaNorm = '';
        $telefonoNorm = '';
        require_once APP . '/Models/Persona.php';
        $personaModel = new Persona();
        $persona = $personaModel->getById($idPersona);
        if (!empty($persona)) {
            $cedulaNorm = $this->normalizarDocumentoParaComparacion(
                (string)($persona['Numero_Documento'] ?? $persona['Cedula'] ?? '')
            );
            if ($cedulaNorm === '') {
                $cedulaNorm = $this->normalizarDocumentoParaComparacion((string)($persona['Usuario'] ?? ''));
            }
            $telefonoNorm = $this->normalizarTelefonoParaComparacion((string)($persona['Telefono'] ?? ''));
        }

        if ($cedulaNorm !== '') {
            $this->vincularInscripcionesHuérfanasAPersona($idPersona, $cedulaNorm, '');
            $exprCedula = $this->sqlExprCedulaNormalizada('Cedula');
            $this->agregarProgramasDesdeFilas(
                $this->query(
                    "SELECT DISTINCT Programa FROM {$this->table}
                     WHERE {$exprCedula} = ?
                       AND (Id_Persona IS NULL OR Id_Persona = 0 OR Id_Persona = ?)",
                    [$cedulaNorm, $idPersona]
                ),
                $programas
            );
        }

        if ($telefonoNorm !== '') {
            $exprTelefono = $this->sqlExprTelefonoNormalizado('Telefono');
            $this->agregarProgramasDesdeFilas(
                $this->query(
                    "SELECT DISTINCT Programa FROM {$this->table}
                     WHERE {$exprTelefono} = ?
                       AND (Id_Persona IS NULL OR Id_Persona = 0 OR Id_Persona = ?)",
                    [$telefonoNorm, $idPersona]
                ),
                $programas
            );
        }

        return array_values(array_keys($programas));
    }

    private function agregarProgramasDesdeFilas(array $rows, array &$programas): void {
        foreach ((array)$rows as $row) {
            $programa = trim((string)($row['Programa'] ?? ''));
            if ($programa !== '') {
                $programas[$programa] = true;
            }
        }
    }

    /**
     * Programas de escuelas por Id_Persona (encuentro, UV, capacitación destino).
     *
     * @param int[] $idsPersona
     * @return array<int, array{encuentro: bool, universidad_vida: bool, capacitacion_destino: bool}>
     */
    public function getFlagsFormacionPorIdsPersonas(array $idsPersona): array {
        $ids = [];
        foreach ($idsPersona as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        if ($ids === []) {
            return [];
        }

        $inSql = implode(',', array_map('intval', array_values($ids)));
        $rows = $this->query(
            "SELECT Id_Persona, Programa
             FROM {$this->table}
             WHERE Id_Persona IN ({$inSql})"
        );

        $mapa = [];
        foreach ((array)$rows as $row) {
            $id = (int)($row['Id_Persona'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            if (!isset($mapa[$id])) {
                $mapa[$id] = [
                    'encuentro' => false,
                    'universidad_vida' => false,
                    'capacitacion_destino' => false,
                ];
            }
            $programa = strtolower(trim((string)($row['Programa'] ?? '')));
            if ($programa === 'encuentro') {
                $mapa[$id]['encuentro'] = true;
            } elseif ($programa === 'universidad_vida') {
                $mapa[$id]['universidad_vida'] = true;
            } elseif ($programa === 'capacitacion_destino' || strpos($programa, 'capacitacion_destino_') === 0) {
                $mapa[$id]['capacitacion_destino'] = true;
            }
        }

        return $mapa;
    }

    private function vincularInscripcionesHuérfanasAPersona(int $idPersona, string $cedulaNorm, string $telefonoNorm): void {
        if ($idPersona <= 0) {
            return;
        }

        $exprCedula = $this->sqlExprCedulaNormalizada('Cedula');
        if ($cedulaNorm !== '') {
            $this->execute(
                "UPDATE {$this->table}
                 SET Id_Persona = ?
                 WHERE (Id_Persona IS NULL OR Id_Persona = 0)
                   AND {$exprCedula} = ?",
                [$idPersona, $cedulaNorm]
            );
        }

        $exprTelefono = $this->sqlExprTelefonoNormalizado('Telefono');
        if ($telefonoNorm !== '') {
            $this->execute(
                "UPDATE {$this->table}
                 SET Id_Persona = ?
                 WHERE (Id_Persona IS NULL OR Id_Persona = 0)
                   AND {$exprTelefono} = ?",
                [$idPersona, $telefonoNorm]
            );
        }
    }

    private function sqlExprCedulaNormalizada(string $columna): string {
        return "REPLACE(REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE({$columna}, ''))), ' ', ''), '.', ''), '-', ''), '_', '')";
    }

    private function sqlExprTelefonoNormalizado(string $columna): string {
        return "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE({$columna}, '')), ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '')";
    }

    private function normalizarDocumentoParaComparacion(string $documento): string {
        $documento = strtoupper(trim($documento));
        if ($documento === '') {
            return '';
        }

        return preg_replace('/[^A-Z0-9]/', '', $documento);
    }

    private function normalizarTelefonoParaComparacion(string $telefono): string {
        $telefono = trim($telefono);
        if ($telefono === '') {
            return '';
        }

        return preg_replace('/\D+/', '', $telefono);
    }

    public function getIdInscripcionPersonaPrograma($idPersona, $programa) {
        $idPersona = (int)$idPersona;
        $programa = trim((string)$programa);
        if ($idPersona <= 0 || $programa === '') {
            return 0;
        }

        $rows = $this->query(
            "SELECT Id_Inscripcion FROM {$this->table} WHERE Id_Persona = ? AND Programa = ? ORDER BY Id_Inscripcion ASC LIMIT 1",
            [$idPersona, $programa]
        );

        return !empty($rows) ? (int)($rows[0]['Id_Inscripcion'] ?? 0) : 0;
    }

    /**
     * Resuelve Id_Persona a partir de la inscripción (columna Id_Persona o cédula/teléfono).
     */
    public function resolverIdPersonaDesdeInscripcion(array $inscripcion): int {
        $idPersona = (int)($inscripcion['Id_Persona'] ?? 0);
        if ($idPersona > 0) {
            return $idPersona;
        }

        require_once APP . '/Models/Persona.php';
        $personaModel = new Persona();

        $cedulaNorm = $this->normalizarDocumentoParaComparacion((string)($inscripcion['Cedula'] ?? ''));
        if ($cedulaNorm !== '') {
            $personaPorDocumento = $personaModel->query(
                "SELECT Id_Persona FROM persona
                 WHERE REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(Numero_Documento, ''))), ' ', ''), '.', ''), '-', '') = ?
                 ORDER BY Id_Persona DESC LIMIT 1",
                [$cedulaNorm]
            );
            if (!empty($personaPorDocumento)) {
                return (int)($personaPorDocumento[0]['Id_Persona'] ?? 0);
            }
        }

        $telefonoNorm = $this->normalizarTelefonoParaComparacion((string)($inscripcion['Telefono'] ?? ''));
        if ($telefonoNorm !== '') {
            $personaPorTelefono = $personaModel->query(
                "SELECT Id_Persona FROM persona
                 WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(Telefono, '')), ' ', ''), '-', ''), '+', ''), '(', ''), ')', ''), '.', '') = ?
                 ORDER BY Id_Persona DESC LIMIT 1",
                [$telefonoNorm]
            );
            if (!empty($personaPorTelefono)) {
                return (int)($personaPorTelefono[0]['Id_Persona'] ?? 0);
            }
        }

        return 0;
    }

    /**
     * Cambia el programa de la misma fila de inscripción (persiste en BD).
     */
    public function moverProgramaInscripcion(int $idInscripcion, string $programaDestino, ?int $idPersona = null): bool {
        $idInscripcion = (int)$idInscripcion;
        $programaDestino = trim($programaDestino);
        if ($idInscripcion <= 0 || $programaDestino === '') {
            return false;
        }

        $inscripcion = $this->getByIdInscripcion($idInscripcion);
        if (empty($inscripcion)) {
            return false;
        }

        $idPersona = (int)($idPersona ?? 0);
        if ($idPersona <= 0) {
            $idPersona = $this->resolverIdPersonaDesdeInscripcion($inscripcion);
        }

        $segmentoPreferido = $this->segmentoPreferidoParaPrograma($programaDestino);

        $sets = ['Programa = ?'];
        $params = [$programaDestino];

        if ($idPersona > 0) {
            $sets[] = 'Id_Persona = ?';
            $params[] = $idPersona;
        }

        if ($segmentoPreferido !== null) {
            $sets[] = 'Segmento_Preferido = ?';
            $params[] = $segmentoPreferido;
        }

        $params[] = $idInscripcion;

        $ok = $this->execute(
            'UPDATE ' . $this->table . ' SET ' . implode(', ', $sets) . ' WHERE Id_Inscripcion = ?',
            $params
        );

        if ($ok) {
            $inscripcion['Programa'] = $programaDestino;
            if ($idPersona > 0) {
                $inscripcion['Id_Persona'] = $idPersona;
            }
            $this->provisionarAccesoDiscipuloCapDestino($inscripcion);
        }

        return $ok;
    }

    public function create($data) {
        $idInscripcion = parent::create($data);
        if ((int)$idInscripcion > 0) {
            $fila = array_merge($data, ['Id_Inscripcion' => (int)$idInscripcion]);
            $this->provisionarAccesoDiscipuloCapDestino($fila);
        }

        return $idInscripcion;
    }

    private function provisionarAccesoDiscipuloCapDestino(array $inscripcion): void {
        require_once APP . '/Helpers/AccesoDiscipuloCapDestino.php';
        AccesoDiscipuloCapDestino::provisionarDesdeInscripcion($inscripcion);
    }

    private function segmentoPreferidoParaPrograma(string $programa): ?string {
        $programa = strtolower(trim($programa));
        if ($programa === 'capacitacion_destino_nivel_1' || $programa === 'capacitacion_destino') {
            return 'nivel_1';
        }
        if ($programa === 'capacitacion_destino_nivel_2') {
            return 'nivel_2';
        }
        if ($programa === 'capacitacion_destino_nivel_3') {
            return 'nivel_3';
        }

        return null;
    }

    public function sincronizarProgramaCapacitacionDestinoPorPersona($idPersona, $programaDestino) {
        $idPersona = (int)$idPersona;
        $programaDestino = trim((string)$programaDestino);

        if ($idPersona <= 0 || !in_array($programaDestino, ['capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            return false;
        }

        return $this->execute(
            "UPDATE {$this->table}
             SET Programa = ?
             WHERE Id_Persona = ?
               AND Programa IN ('capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3')",
            [$programaDestino, $idPersona]
        );
    }

    public function getResumenPagosAbonos($buscar = '', $limit = 300, $programa = '') {
        $buscar = trim((string)$buscar);
        $programa = trim((string)$programa);
        $limit = max(1, min(1000, (int)$limit));

        $where = ["1=1"];
        $params = [];

        $wherePrograma = $this->getSqlFiltroProgramaPagos($programa, 's');
        if ($wherePrograma !== '') {
            $where[] = $wherePrograma;
        }

        if ($buscar !== '') {
            $where[] = "(s.Nombre LIKE ? OR s.Cedula LIKE ? OR s.Telefono LIKE ? OR s.Referencia_Pago LIKE ? OR s.Programa LIKE ?)";
            $like = '%' . $buscar . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT
                    COALESCE(NULLIF(TRIM(s.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(s.Id_Persona, 0))) AS Cedula_Clave,
                    MAX(COALESCE(NULLIF(TRIM(s.Cedula), ''), '')) AS Cedula,
                    MAX(COALESCE(NULLIF(TRIM(s.Nombre), ''), 'SIN NOMBRE')) AS Nombre,
                    MAX(COALESCE(NULLIF(TRIM(s.Telefono), ''), '')) AS Telefono,
                    COUNT(*) AS Registros_Pago,
                    SUM(COALESCE(s.Valor_Pago, 0)) AS Total_Pagado,
                    SUM(CASE WHEN COALESCE(s.Tipo_Pago, '') = 'abono' THEN COALESCE(s.Valor_Pago, 0) ELSE 0 END) AS Total_Abonos,
                    SUM(CASE WHEN COALESCE(s.Tipo_Pago, '') = 'completo' THEN COALESCE(s.Valor_Pago, 0) ELSE 0 END) AS Total_Pago_Completo,
                    SUM(CASE WHEN COALESCE(s.Tipo_Pago, '') = 'abono' THEN 1 ELSE 0 END) AS Cantidad_Abonos,
                    MAX(s.Fecha_Registro) AS Ultimo_Movimiento
                FROM {$this->tablePagos} s
                WHERE " . implode(' AND ', $where) . "
                GROUP BY Cedula_Clave
                ORDER BY Ultimo_Movimiento DESC
                LIMIT {$limit}";

        try {
            $rows = $this->query($sql, $params);
            if (!empty($rows)) {
                return $rows;
            }
        } catch (Throwable $e) {
            error_log('Fallo resumen de pagos en tabla de movimientos, usando fallback legacy: ' . $e->getMessage());
        }

        return $this->getResumenPagosAbonosLegacy($buscar, $limit, $programa);
    }

    private function getResumenPagosAbonosLegacy($buscar = '', $limit = 300, $programa = '') {
        $buscar = trim((string)$buscar);
        $programa = trim((string)$programa);
        $limit = max(1, min(1000, (int)$limit));

        $hasValorPago = $this->tableColumnExists($this->table, 'Valor_Pago');
        $hasMetodoPago = $this->tableColumnExists($this->table, 'Metodo_Pago');
        $hasReferenciaPago = $this->tableColumnExists($this->table, 'Referencia_Pago');
        $hasTipoPago = $this->tableColumnExists($this->table, 'Tipo_Pago');
        $hasIdPersona = $this->tableColumnExists($this->table, 'Id_Persona');
        $hasPrograma = $this->tableColumnExists($this->table, 'Programa');
        $hasNombre = $this->tableColumnExists($this->table, 'Nombre');
        $hasCedula = $this->tableColumnExists($this->table, 'Cedula');
        $hasTelefono = $this->tableColumnExists($this->table, 'Telefono');
        $hasFechaRegistro = $this->tableColumnExists($this->table, 'Fecha_Registro');

        $exprValorPago = $hasValorPago ? "COALESCE(s.Valor_Pago, 0)" : "0";
        $exprMetodoPago = $hasMetodoPago ? "TRIM(COALESCE(s.Metodo_Pago, ''))" : "''";
        $exprReferenciaPago = $hasReferenciaPago ? "TRIM(COALESCE(s.Referencia_Pago, ''))" : "''";
        $exprTipoPago = $hasTipoPago ? "LOWER(COALESCE(s.Tipo_Pago, ''))" : "''";
        $exprPrograma = $hasPrograma ? "COALESCE(s.Programa, '')" : "''";
        $exprNombre = $hasNombre ? "COALESCE(NULLIF(TRIM(s.Nombre), ''), 'SIN NOMBRE')" : "'SIN NOMBRE'";
        $exprCedula = $hasCedula ? "COALESCE(NULLIF(TRIM(s.Cedula), ''), '')" : "''";
        $exprTelefono = $hasTelefono ? "COALESCE(NULLIF(TRIM(s.Telefono), ''), '')" : "''";
        $exprFechaRegistro = $hasFechaRegistro ? "s.Fecha_Registro" : "NULL";
        $exprCedulaClave = $hasCedula
            ? ($hasIdPersona
                ? "COALESCE(NULLIF(TRIM(s.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(s.Id_Persona, 0)))"
                : "COALESCE(NULLIF(TRIM(s.Cedula), ''), 'SIN-CEDULA-0')")
            : ($hasIdPersona ? "CONCAT('SIN-CEDULA-', COALESCE(s.Id_Persona, 0))" : "'SIN-CEDULA-0'");

        $where = [
            "1=1",
            "({$exprValorPago} > 0 OR {$exprMetodoPago} <> '' OR {$exprReferenciaPago} <> '')"
        ];
        $params = [];

        if ($hasPrograma) {
            $wherePrograma = $this->getSqlFiltroProgramaPagos($programa, 's');
            if ($wherePrograma !== '') {
                $where[] = $wherePrograma;
            }
        }

        if ($buscar !== '') {
            $camposBusqueda = [];
            if ($hasNombre) {
                $camposBusqueda[] = "s.Nombre LIKE ?";
            }
            if ($hasCedula) {
                $camposBusqueda[] = "s.Cedula LIKE ?";
            }
            if ($hasTelefono) {
                $camposBusqueda[] = "s.Telefono LIKE ?";
            }
            if ($hasReferenciaPago) {
                $camposBusqueda[] = "s.Referencia_Pago LIKE ?";
            }
            if ($hasPrograma) {
                $camposBusqueda[] = "s.Programa LIKE ?";
            }

            if (!empty($camposBusqueda)) {
                $where[] = '(' . implode(' OR ', $camposBusqueda) . ')';
            }

            $like = '%' . $buscar . '%';
            for ($i = 0; $i < count($camposBusqueda); $i++) {
                $params[] = $like;
            }
        }

        $sql = "SELECT
                    {$exprCedulaClave} AS Cedula_Clave,
                    MAX({$exprCedula}) AS Cedula,
                    MAX({$exprNombre}) AS Nombre,
                    MAX({$exprTelefono}) AS Telefono,
                    COUNT(*) AS Registros_Pago,
                    SUM({$exprValorPago}) AS Total_Pagado,
                    SUM(CASE WHEN {$exprTipoPago} = 'abono' THEN {$exprValorPago} ELSE 0 END) AS Total_Abonos,
                    SUM(CASE WHEN {$exprTipoPago} = 'completo' THEN {$exprValorPago} ELSE 0 END) AS Total_Pago_Completo,
                    SUM(CASE WHEN {$exprTipoPago} = 'abono' THEN 1 ELSE 0 END) AS Cantidad_Abonos,
                    MAX({$exprFechaRegistro}) AS Ultimo_Movimiento
                FROM {$this->table} s
                WHERE " . implode(' AND ', $where) . "
                GROUP BY Cedula_Clave
                ORDER BY Ultimo_Movimiento DESC
                LIMIT {$limit}";

        try {
            return $this->query($sql, $params);
        } catch (Throwable $e) {
            error_log('Fallo resumen legacy de pagos: ' . $e->getMessage());
            return [];
        }
    }

    public function getDetallePagosPorCedula($cedula, $limit = 100, $programa = '') {
        $cedula = trim((string)$cedula);
        $programa = trim((string)$programa);
        if ($cedula === '') {
            return [];
        }

        $limit = max(1, min(500, (int)$limit));
        $wherePrograma = '';
        $sqlPrograma = $this->getSqlFiltroProgramaPagos($programa, 's');
        if ($sqlPrograma !== '') {
            $wherePrograma = ' AND ' . $sqlPrograma;
        }

        $sql = "SELECT
                    s.Id_Inscripcion,
                    s.Nombre,
                    s.Cedula,
                    s.Telefono,
                    s.Programa,
                    s.Metodo_Pago,
                    s.Recibido_Por,
                    s.Tipo_Pago,
                    s.Valor_Pago,
                    s.Entrego_Libro,
                    s.Referencia_Pago,
                    s.Fecha_Registro
                                FROM {$this->tablePagos} s
                                WHERE 1=1
                  AND COALESCE(NULLIF(TRIM(s.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(s.Id_Persona, 0))) = ?
                                    {$wherePrograma}
                ORDER BY s.Fecha_Registro DESC, s.Id_Inscripcion DESC
                LIMIT {$limit}";

        try {
            $rows = $this->query($sql, [$cedula]);
            if (!empty($rows)) {
                return $rows;
            }
        } catch (Throwable $e) {
            error_log('Fallo detalle de pagos en tabla de movimientos, usando fallback legacy: ' . $e->getMessage());
        }

        return $this->getDetallePagosPorCedulaLegacy($cedula, $limit, $programa);
    }

    private function getDetallePagosPorCedulaLegacy($cedula, $limit = 100, $programa = '') {
        $cedula = trim((string)$cedula);
        $programa = trim((string)$programa);
        if ($cedula === '') {
            return [];
        }

        $limit = max(1, min(500, (int)$limit));
        $hasIdInscripcion = $this->tableColumnExists($this->table, 'Id_Inscripcion');
        $hasNombre = $this->tableColumnExists($this->table, 'Nombre');
        $hasCedula = $this->tableColumnExists($this->table, 'Cedula');
        $hasTelefono = $this->tableColumnExists($this->table, 'Telefono');
        $hasPrograma = $this->tableColumnExists($this->table, 'Programa');
        $hasMetodoPago = $this->tableColumnExists($this->table, 'Metodo_Pago');
        $hasRecibidoPor = $this->tableColumnExists($this->table, 'Recibido_Por');
        $hasTipoPago = $this->tableColumnExists($this->table, 'Tipo_Pago');
        $hasValorPago = $this->tableColumnExists($this->table, 'Valor_Pago');
        $hasEntregoLibro = $this->tableColumnExists($this->table, 'Entrego_Libro');
        $hasReferenciaPago = $this->tableColumnExists($this->table, 'Referencia_Pago');
        $hasFechaRegistro = $this->tableColumnExists($this->table, 'Fecha_Registro');
        $hasIdPersona = $this->tableColumnExists($this->table, 'Id_Persona');

        $exprIdInscripcion = $hasIdInscripcion ? 's.Id_Inscripcion' : '0';
        $exprNombre = $hasNombre ? 's.Nombre' : "''";
        $exprCedula = $hasCedula ? 's.Cedula' : "''";
        $exprTelefono = $hasTelefono ? 's.Telefono' : "''";
        $exprPrograma = $hasPrograma ? 's.Programa' : "''";
        $exprMetodoPago = $hasMetodoPago ? 's.Metodo_Pago' : "''";
        $exprRecibidoPor = $hasRecibidoPor ? 's.Recibido_Por' : 'NULL';
        $exprTipoPago = $hasTipoPago ? 's.Tipo_Pago' : "''";
        $exprValorPago = $hasValorPago ? 's.Valor_Pago' : '0';
        $exprEntregoLibro = $hasEntregoLibro ? 's.Entrego_Libro' : 'NULL';
        $exprReferenciaPago = $hasReferenciaPago ? 's.Referencia_Pago' : "''";
        $exprFechaRegistro = $hasFechaRegistro ? 's.Fecha_Registro' : 'NULL';
        $exprCedulaClave = $hasCedula
            ? ($hasIdPersona
                ? "COALESCE(NULLIF(TRIM(s.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(s.Id_Persona, 0)))"
                : "COALESCE(NULLIF(TRIM(s.Cedula), ''), 'SIN-CEDULA-0')")
            : ($hasIdPersona ? "CONCAT('SIN-CEDULA-', COALESCE(s.Id_Persona, 0))" : "'SIN-CEDULA-0'");

        $wherePrograma = '';
        if ($hasPrograma) {
            $sqlPrograma = $this->getSqlFiltroProgramaPagos($programa, 's');
            if ($sqlPrograma !== '') {
                $wherePrograma = ' AND ' . $sqlPrograma;
            }
        }

        $sql = "SELECT
                    {$exprIdInscripcion} AS Id_Inscripcion,
                    {$exprNombre} AS Nombre,
                    {$exprCedula} AS Cedula,
                    {$exprTelefono} AS Telefono,
                    {$exprPrograma} AS Programa,
                    {$exprMetodoPago} AS Metodo_Pago,
                    {$exprRecibidoPor} AS Recibido_Por,
                    {$exprTipoPago} AS Tipo_Pago,
                    {$exprValorPago} AS Valor_Pago,
                    {$exprEntregoLibro} AS Entrego_Libro,
                    {$exprReferenciaPago} AS Referencia_Pago,
                    {$exprFechaRegistro} AS Fecha_Registro
                FROM {$this->table} s
                WHERE ({$exprValorPago} > 0 OR TRIM(COALESCE({$exprMetodoPago}, '')) <> '' OR TRIM(COALESCE({$exprReferenciaPago}, '')) <> '')
                  AND {$exprCedulaClave} = ?
                  {$wherePrograma}
                ORDER BY {$exprFechaRegistro} DESC, {$exprIdInscripcion} DESC
                LIMIT {$limit}";

        try {
            return $this->query($sql, [$cedula]);
        } catch (Throwable $e) {
            error_log('Fallo detalle legacy de pagos: ' . $e->getMessage());
            return [];
        }
    }

    public function crearDesdePersonaSiNoExiste($idPersona, $programa, $fuente = 'Escuelas de formacion (asistencia)') {
        $idPersona = (int)$idPersona;
        $programa = trim((string)$programa);

        if ($idPersona <= 0 || $programa === '') {
            return false;
        }

        if ($this->getIdInscripcionPersonaPrograma($idPersona, $programa) > 0) {
            return true;
        }

        $rows = $this->query(
            "SELECT
                p.Id_Persona,
                TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre,
                p.Genero,
                p.Telefono,
                p.Numero_Documento,
                p.Id_Ministerio,
                m.Nombre_Ministerio,
                TRIM(CONCAT(COALESCE(l.Nombre, ''), ' ', COALESCE(l.Apellido, ''))) AS Nombre_Lider
             FROM persona p
             LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
             LEFT JOIN persona l ON p.Id_Lider = l.Id_Persona
             WHERE p.Id_Persona = ?
             LIMIT 1",
            [$idPersona]
        );

        if (empty($rows)) {
            return false;
        }

        $persona = $rows[0];
        $data = [
            'Id_Persona' => $idPersona,
            'Nombre' => (string)($persona['Nombre'] ?? ''),
            'Genero' => trim((string)($persona['Genero'] ?? '')) !== '' ? (string)$persona['Genero'] : null,
            'Edad' => null,
            'Telefono' => trim((string)($persona['Telefono'] ?? '')) !== '' ? (string)$persona['Telefono'] : null,
            'Cedula' => trim((string)($persona['Numero_Documento'] ?? '')) !== '' ? (string)$persona['Numero_Documento'] : null,
            'Lider' => trim((string)($persona['Nombre_Lider'] ?? '')) !== '' ? (string)$persona['Nombre_Lider'] : null,
            'Id_Ministerio' => (int)($persona['Id_Ministerio'] ?? 0) > 0 ? (int)$persona['Id_Ministerio'] : null,
            'Nombre_Ministerio' => trim((string)($persona['Nombre_Ministerio'] ?? '')) !== '' ? (string)$persona['Nombre_Ministerio'] : null,
            'Programa' => $programa,
            'Fuente' => $fuente
        ];

        $this->create($data);
        return true;
    }

    public function sincronizarDatosDesdePersona($idPersona) {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0) {
            return false;
        }

        $rows = $this->query(
            "SELECT
                p.Id_Persona,
                TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre,
                p.Genero,
                p.Edad,
                p.Telefono,
                p.Numero_Documento,
                p.Id_Ministerio,
                m.Nombre_Ministerio,
                TRIM(CONCAT(COALESCE(l.Nombre, ''), ' ', COALESCE(l.Apellido, ''))) AS Nombre_Lider
             FROM persona p
             LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
             LEFT JOIN persona l ON p.Id_Lider = l.Id_Persona
             WHERE p.Id_Persona = ?
             LIMIT 1",
            [$idPersona]
        );

        if (empty($rows)) {
            return false;
        }

        $persona = $rows[0];

        return $this->execute(
            "UPDATE {$this->table}
             SET Nombre = ?,
                 Genero = ?,
                 Edad = CASE WHEN ? IS NULL OR ? <= 0 THEN Edad ELSE ? END,
                 Telefono = ?,
                 Cedula = ?,
                 Lider = ?,
                 Id_Ministerio = ?,
                 Nombre_Ministerio = ?
             WHERE Id_Persona = ?",
            [
                trim((string)($persona['Nombre'] ?? '')) !== '' ? (string)$persona['Nombre'] : null,
                trim((string)($persona['Genero'] ?? '')) !== '' ? (string)$persona['Genero'] : null,
                isset($persona['Edad']) ? (int)$persona['Edad'] : null,
                isset($persona['Edad']) ? (int)$persona['Edad'] : null,
                isset($persona['Edad']) ? (int)$persona['Edad'] : null,
                trim((string)($persona['Telefono'] ?? '')) !== '' ? (string)$persona['Telefono'] : null,
                trim((string)($persona['Numero_Documento'] ?? '')) !== '' ? (string)$persona['Numero_Documento'] : null,
                trim((string)($persona['Nombre_Lider'] ?? '')) !== '' ? (string)$persona['Nombre_Lider'] : null,
                (int)($persona['Id_Ministerio'] ?? 0) > 0 ? (int)$persona['Id_Ministerio'] : null,
                trim((string)($persona['Nombre_Ministerio'] ?? '')) !== '' ? (string)$persona['Nombre_Ministerio'] : null,
                $idPersona
            ]
        );
    }

    public function getListado($programa = '', $buscar = '', $limit = 300, $genero = 'todos', $idMinisterio = null, $idLider = null, $limiteMaximo = 1000) {
        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = 300;
        }
        $limiteMaximo = max(1000, (int)$limiteMaximo);
        if ($limit > $limiteMaximo) {
            $limit = $limiteMaximo;
        }

        $programa = trim((string)$programa);
        $buscar = trim((string)$buscar);
        $genero = strtolower(trim((string)$genero));
        $idMinisterio = (int)$idMinisterio;
        $idLider = (int)$idLider;

        $where = [];
        $params = [];

        if (in_array($programa, ['universidad_vida', 'encuentro', 'bautismo', 'capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            if ($programa === 'capacitacion_destino') {
                $where[] = "s.Programa IN ('capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3')";
            } elseif ($programa === 'capacitacion_destino_nivel_1') {
                $where[] = "s.Programa IN ('capacitacion_destino_nivel_1', 'capacitacion_destino')";
            } else {
                $where[] = 's.Programa = ?';
                $params[] = $programa;
            }
        }

        if ($genero === 'mujeres') {
            $where[] = "(LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%mujer%' OR LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%femen%' OR LOWER(TRIM(COALESCE(s.Genero, ''))) = 'f')";
        } elseif ($genero === 'hombres') {
            $where[] = "(LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%hombre%' OR LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%mascul%' OR LOWER(TRIM(COALESCE(s.Genero, ''))) = 'm')";
        } elseif ($genero === 'joven_hombre') {
            $where[] = "(LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%joven%' AND (LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%hombre%' OR LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%mascul%'))";
        } elseif ($genero === 'joven_mujer') {
            $where[] = "(LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%joven%' AND (LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%mujer%' OR LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%femen%'))";
        }

        if ($buscar !== '') {
            $where[] = '(s.Nombre LIKE ? OR s.Genero LIKE ? OR s.Telefono LIKE ? OR s.Cedula LIKE ? OR s.Lider LIKE ? OR s.Nombre_Ministerio LIKE ?)';
            $like = '%' . $buscar . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($idMinisterio > 0) {
            $where[] = '(s.Id_Ministerio = ? OR p.Id_Ministerio = ?)';
            $params[] = $idMinisterio;
            $params[] = $idMinisterio;
        }

        if ($idLider > 0) {
            $where[] = 'p.Id_Lider = ?';
            $params[] = $idLider;
        }

        $sql = "SELECT
                    s.*,
                    TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre_Persona_Actual,
                    p.Genero AS Genero_Persona_Actual,
                    p.Edad AS Edad_Persona_Actual,
                    p.Id_Lider AS Id_Lider_Persona_Actual,
                    TRIM(CONCAT(COALESCE(lp.Nombre, ''), ' ', COALESCE(lp.Apellido, ''))) AS Lider_Persona_Actual,
                    mp.Nombre_Ministerio AS Nombre_Ministerio_Persona_Actual
                FROM {$this->table} s
                LEFT JOIN persona p ON s.Id_Persona = p.Id_Persona
                LEFT JOIN persona lp ON p.Id_Lider = lp.Id_Persona
                LEFT JOIN ministerio mp ON p.Id_Ministerio = mp.Id_Ministerio";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY s.Fecha_Registro DESC, s.Id_Inscripcion DESC LIMIT {$limit}";

        return $this->query($sql, $params);
    }

    public function getResumenProgramas() {
        $sql = "SELECT Programa, COUNT(*) AS Total
                FROM {$this->table}
                GROUP BY Programa";

        $rows = $this->query($sql);
        $resumen = [
            'total' => 0,
            'universidad_vida' => 0,
            'encuentro' => 0,
            'bautismo' => 0,
            'capacitacion_destino' => 0,
            'capacitacion_destino_nivel_1' => 0,
            'capacitacion_destino_nivel_2' => 0,
            'capacitacion_destino_nivel_3' => 0,
            'otros' => 0,
        ];

        foreach ($rows as $row) {
            $programa = (string)($row['Programa'] ?? '');
            $total = (int)($row['Total'] ?? 0);
            $resumen['total'] += $total;

            if (in_array($programa, ['universidad_vida', 'encuentro', 'bautismo', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
                $resumen[$programa] = $total;
            } elseif ($programa === 'capacitacion_destino') {
                // Compatibilidad con registros anteriores sin nivel explícito.
                $resumen['capacitacion_destino_nivel_1'] += $total;
            } else {
                $resumen['otros'] += $total;
            }
        }

        $resumen['capacitacion_destino'] =
            (int)$resumen['capacitacion_destino_nivel_1'] +
            (int)$resumen['capacitacion_destino_nivel_2'] +
            (int)$resumen['capacitacion_destino_nivel_3'];

        return $resumen;
    }

    public function getTotalConsolidarUnico($filtroPersonas = '1=1') {
        $filtroPersonas = trim((string)$filtroPersonas);
        if ($filtroPersonas === '') {
            $filtroPersonas = '1=1';
        }

        $sql = "SELECT COUNT(*) AS Total
                FROM {$this->table} s
                LEFT JOIN persona p ON s.Id_Persona = p.Id_Persona
                                WHERE s.Programa = 'universidad_vida'
                                    AND (
                                        LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%hombre%'
                                        OR LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%mascul%'
                                        OR LOWER(TRIM(COALESCE(s.Genero, ''))) IN ('m', 'masc', 'male', 'h')
                                        OR LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%mujer%'
                                        OR LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%femen%'
                                        OR LOWER(TRIM(COALESCE(s.Genero, ''))) IN ('f', 'fem', 'female')
                                    )
                  AND ({$filtroPersonas})";

        $rows = $this->query($sql);
        return (int)($rows[0]['Total'] ?? 0);
    }

    public function getResumenUvPorMinisterioGenero($idMinisterio = null, $idLider = null) {
        $idMinisterio = (int)$idMinisterio;
        $idLider = (int)$idLider;

        $where = ["s.Programa = 'universidad_vida'"];
        $params = [];

        if ($idMinisterio > 0) {
            $where[] = '(s.Id_Ministerio = ? OR p.Id_Ministerio = ?)';
            $params[] = $idMinisterio;
            $params[] = $idMinisterio;
        }

        if ($idLider > 0) {
            $where[] = 'p.Id_Lider = ?';
            $params[] = $idLider;
        }

        $ministerioExpr = "COALESCE(NULLIF(TRIM(s.Nombre_Ministerio), ''), NULLIF(TRIM(mp.Nombre_Ministerio), ''), 'Sin ministerio')";
        $generoExpr = "LOWER(TRIM(COALESCE(
                            NULLIF(CONVERT(s.Genero USING utf8mb4) COLLATE utf8mb4_general_ci, ''),
                            NULLIF(CONVERT(p.Genero USING utf8mb4) COLLATE utf8mb4_general_ci, ''),
                            ''
                        )))";

        $esHombre = "({$generoExpr} LIKE '%hombre%' OR {$generoExpr} LIKE '%mascul%' OR {$generoExpr} IN ('m', 'masc', 'male', 'h'))";
        $esMujer = "({$generoExpr} LIKE '%mujer%' OR {$generoExpr} LIKE '%femen%' OR {$generoExpr} IN ('f', 'fem', 'female'))";

        $sql = "SELECT
                    {$ministerioExpr} AS Ministerio,
                    COUNT(*) AS Inscritos_Total,
                    SUM(CASE WHEN {$esHombre} THEN 1 ELSE 0 END) AS Inscritos_Hombres,
                    SUM(CASE WHEN {$esMujer} THEN 1 ELSE 0 END) AS Inscritos_Mujeres,
                    SUM(CASE WHEN NOT ({$esHombre} OR {$esMujer}) THEN 1 ELSE 0 END) AS Inscritos_Sin_Genero,
                    SUM(CASE WHEN s.Asistio_Clase = 1 THEN 1 ELSE 0 END) AS Asistieron_Total,
                    SUM(CASE WHEN s.Asistio_Clase = 1 AND {$esHombre} THEN 1 ELSE 0 END) AS Asistieron_Hombres,
                    SUM(CASE WHEN s.Asistio_Clase = 1 AND {$esMujer} THEN 1 ELSE 0 END) AS Asistieron_Mujeres,
                    SUM(CASE WHEN s.Asistio_Clase = 1 AND NOT ({$esHombre} OR {$esMujer}) THEN 1 ELSE 0 END) AS Asistieron_Sin_Genero
                FROM {$this->table} s
                LEFT JOIN persona p ON s.Id_Persona = p.Id_Persona
                LEFT JOIN persona lp ON p.Id_Lider = lp.Id_Persona
                LEFT JOIN ministerio mp ON p.Id_Ministerio = mp.Id_Ministerio
                WHERE " . implode(' AND ', $where) . "
                GROUP BY {$ministerioExpr}
                ORDER BY {$ministerioExpr} ASC";

        return $this->query($sql, $params);
    }

    private function getSqlProgramaPorLinea($linea) {
        $linea = trim((string)$linea);
        if ($linea === 'capacitacion_destino') {
            return "s.Programa IN ('capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3')";
        }

        return "s.Programa IN ('universidad_vida', 'encuentro', 'bautismo')";
    }

    private function getSqlFiltroProgramaPagos($programa, $alias = 's') {
        $programa = trim((string)$programa);
        $alias = trim((string)$alias);
        if ($alias === '') {
            $alias = 's';
        }

        if ($programa === 'universidad_vida') {
            return "{$alias}.Programa IN ('universidad_vida', 'encuentro', 'bautismo')";
        }

        if ($programa === 'capacitacion_destino') {
            return "{$alias}.Programa IN ('capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3')";
        }

        if (in_array($programa, ['capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            // Incluir slug legacy 'capacitacion_destino' en Nivel 1 (inscripciones/pagos previos a la separación por nivel).
            if ($programa === 'capacitacion_destino_nivel_1') {
                return "{$alias}.Programa IN ('capacitacion_destino_nivel_1', 'capacitacion_destino')";
            }
            return "{$alias}.Programa = '" . $programa . "'";
        }

        return '';
    }

    /**
     * Conteo de inscritos por líder (Id_Lider de la persona) en un rango de fechas.
     *
     * @return array<int,int> mapa [Id_Lider => Total]
     */
    public function getConteoInscritosPorLiderLinea($linea, $fechaInicio, $fechaFin, $filtroPersonas = '1=1', $idMinisterio = null, $idLider = null) {
        $wherePrograma = $this->getSqlProgramaPorLinea($linea);
        $filtroPersonas = trim((string)$filtroPersonas);
        if ($filtroPersonas === '') {
            $filtroPersonas = '1=1';
        }

        $idMinisterio = (int)$idMinisterio;
        $idLider = (int)$idLider;

        $params = [$fechaInicio, $fechaFin];
        $whereExtra = '';

        if ($idMinisterio > 0) {
            $whereExtra .= ' AND p.Id_Ministerio = ?';
            $params[] = $idMinisterio;
        }

        if ($idLider > 0) {
            $whereExtra .= ' AND p.Id_Lider = ?';
            $params[] = $idLider;
        }

        $sql = "SELECT p.Id_Lider, COUNT(*) AS Total
                FROM {$this->table} s
                INNER JOIN persona p ON p.Id_Persona = s.Id_Persona
                WHERE DATE(s.Fecha_Registro) BETWEEN ? AND ?
                  AND {$wherePrograma}
                  AND p.Id_Lider IS NOT NULL
                  AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND ({$filtroPersonas})
                  {$whereExtra}
                GROUP BY p.Id_Lider";

        $rows = $this->query($sql, $params);
        $resultado = [];
        foreach ((array)$rows as $row) {
            $id = (int)($row['Id_Lider'] ?? 0);
            if ($id > 0) {
                $resultado[$id] = (int)($row['Total'] ?? 0);
            }
        }

        return $resultado;
    }

    /**
     * Conteo de inscritos por líder usando dos fuentes:
     * 1) Relación actual persona.Id_Lider
     * 2) Nombre de líder guardado en la inscripción (fallback)
     *
     * @return array<int,array<string,mixed>>
     */
    public function getConteoInscritosPorLiderLineaFlexible($linea, $fechaInicio, $fechaFin, $filtroPersonas = '1=1', $idMinisterio = null, $idLider = null) {
        $wherePrograma = $this->getSqlProgramaPorLinea($linea);
        $filtroPersonas = trim((string)$filtroPersonas);
        if ($filtroPersonas === '') {
            $filtroPersonas = '1=1';
        }

        $idMinisterio = (int)$idMinisterio;
        $idLider = (int)$idLider;

        $params = [$fechaInicio, $fechaFin];
        $whereExtra = '';

        if ($idMinisterio > 0) {
            $whereExtra .= ' AND (s.Id_Ministerio = ? OR p.Id_Ministerio = ?)';
            $params[] = $idMinisterio;
            $params[] = $idMinisterio;
        }

        if ($idLider > 0) {
            $whereExtra .= " AND (
                p.Id_Lider = ?
                OR TRIM(LOWER(CONVERT(COALESCE(NULLIF(s.Lider, ''), '') USING utf8mb4))) COLLATE utf8mb4_unicode_ci
                   = TRIM(LOWER(CONVERT(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, '')) USING utf8mb4))) COLLATE utf8mb4_unicode_ci
            )";
            $params[] = $idLider;
        }

        $sql = "SELECT
                    COALESCE(p.Id_Lider, plider.Id_Persona, 0) AS Id_Lider_Actual,
                    TRIM(
                        COALESCE(
                            NULLIF(s.Lider, ''),
                            NULLIF(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, '')), ''),
                            ''
                        )
                    ) AS Nombre_Lider_Referencia,
                    COUNT(*) AS Total
                FROM {$this->table} s
                LEFT JOIN persona p ON p.Id_Persona = s.Id_Persona
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                                LEFT JOIN persona plider ON TRIM(LOWER(CONVERT(CONCAT(COALESCE(plider.Nombre, ''), ' ', COALESCE(plider.Apellido, '')) USING utf8mb4))) COLLATE utf8mb4_unicode_ci
                                        = TRIM(LOWER(CONVERT(COALESCE(NULLIF(s.Lider, ''), '') USING utf8mb4))) COLLATE utf8mb4_unicode_ci
                WHERE DATE(s.Fecha_Registro) BETWEEN ? AND ?
                  AND {$wherePrograma}
                  AND (
                    p.Id_Persona IS NULL
                    OR (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  )
                  AND (
                    p.Id_Persona IS NULL
                    OR ({$filtroPersonas})
                  )
                  {$whereExtra}
                GROUP BY Id_Lider_Actual, Nombre_Lider_Referencia";

        return $this->query($sql, $params);
    }

    /**
     * Conteo de inscritos por ministerio en un rango de fechas.
     *
     * @return array<int,int> mapa [Id_Ministerio => Total]
     */
    public function getConteoInscritosPorMinisterioLinea($linea, $fechaInicio, $fechaFin, $filtroPersonas = '1=1', $idMinisterio = null, $idLider = null, array $ministerioIdsPermitidos = []) {
        $wherePrograma = $this->getSqlProgramaPorLinea($linea);
        $filtroPersonas = trim((string)$filtroPersonas);
        if ($filtroPersonas === '') {
            $filtroPersonas = '1=1';
        }

        $idMinisterio = (int)$idMinisterio;
        $idLider = (int)$idLider;

        $params = [$fechaInicio, $fechaFin];
        $whereExtra = '';

        if ($idMinisterio > 0) {
            $whereExtra .= ' AND p.Id_Ministerio = ?';
            $params[] = $idMinisterio;
        }

        if ($idLider > 0) {
            $whereExtra .= ' AND p.Id_Lider = ?';
            $params[] = $idLider;
        }

        $idsPermitidos = array_values(array_filter(array_map('intval', $ministerioIdsPermitidos), static function($id) {
            return $id > 0;
        }));
        if (!empty($idsPermitidos)) {
            $placeholders = implode(',', array_fill(0, count($idsPermitidos), '?'));
            $whereExtra .= " AND p.Id_Ministerio IN ({$placeholders})";
            $params = array_merge($params, $idsPermitidos);
        }

        $sql = "SELECT p.Id_Ministerio, COUNT(*) AS Total
                FROM {$this->table} s
                INNER JOIN persona p ON p.Id_Persona = s.Id_Persona
                WHERE DATE(s.Fecha_Registro) BETWEEN ? AND ?
                  AND {$wherePrograma}
                  AND p.Id_Ministerio IS NOT NULL
                  AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND ({$filtroPersonas})
                  {$whereExtra}
                GROUP BY p.Id_Ministerio";

        $rows = $this->query($sql, $params);
        $resultado = [];
        foreach ((array)$rows as $row) {
            $id = (int)($row['Id_Ministerio'] ?? 0);
            if ($id > 0) {
                $resultado[$id] = (int)($row['Total'] ?? 0);
            }
        }

        return $resultado;
    }

    /**
     * Resumen de pagos de Universidad de la Vida por ministerio.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getResumenPagosUniversidadVidaPorMinisterio($fechaInicio, $fechaFin, $filtroPersonas = '1=1', $idMinisterio = null, $idLider = null, array $ministerioIdsPermitidos = []) {
        $filtroPersonas = trim((string)$filtroPersonas);
        if ($filtroPersonas === '') {
            $filtroPersonas = '1=1';
        }

        $idMinisterio = (int)$idMinisterio;
        $idLider = (int)$idLider;

        $params = [$fechaInicio, $fechaFin];
        $whereExtra = '';

        if ($idMinisterio > 0) {
            $whereExtra .= ' AND p.Id_Ministerio = ?';
            $params[] = $idMinisterio;
        }

        if ($idLider > 0) {
            $whereExtra .= ' AND p.Id_Lider = ?';
            $params[] = $idLider;
        }

        $idsPermitidos = array_values(array_filter(array_map('intval', $ministerioIdsPermitidos), static function($id) {
            return $id > 0;
        }));
        if (!empty($idsPermitidos)) {
            $placeholders = implode(',', array_fill(0, count($idsPermitidos), '?'));
            $whereExtra .= " AND p.Id_Ministerio IN ({$placeholders})";
            $params = array_merge($params, $idsPermitidos);
        }

        $ministerioExpr = "COALESCE(NULLIF(TRIM(s.Nombre_Ministerio), ''), NULLIF(TRIM(mp.Nombre_Ministerio), ''), 'Sin ministerio')";
        $pagadoExpr = "(COALESCE(s.Valor_Pago, 0) > 0 OR (s.Metodo_Pago IS NOT NULL AND TRIM(s.Metodo_Pago) <> ''))";
        $esTeenExpr = "(COALESCE(s.Edad, p.Edad, 0) BETWEEN 9 AND 12)";
        $esJovenExpr = "(COALESCE(s.Edad, p.Edad, 0) BETWEEN 13 AND 30)";

        $sql = "SELECT
                    {$ministerioExpr} AS Ministerio,
                    COUNT(*) AS Inscritos,
                    SUM(CASE WHEN {$pagadoExpr} THEN 1 ELSE 0 END) AS Pagados,
                    SUM(CASE WHEN {$pagadoExpr} THEN COALESCE(s.Valor_Pago, 0) ELSE 0 END) AS Valor_Recaudado,
                    SUM(CASE WHEN {$esJovenExpr} THEN 1 ELSE 0 END) AS Inscritos_Jovenes,
                    SUM(CASE WHEN {$esTeenExpr} THEN 1 ELSE 0 END) AS Inscritos_Teens,
                    SUM(CASE WHEN {$esJovenExpr} AND {$pagadoExpr} THEN 1 ELSE 0 END) AS Pagados_Jovenes,
                    SUM(CASE WHEN {$esTeenExpr} AND {$pagadoExpr} THEN 1 ELSE 0 END) AS Pagados_Teens
                FROM {$this->table} s
                INNER JOIN persona p ON p.Id_Persona = s.Id_Persona
                LEFT JOIN ministerio mp ON p.Id_Ministerio = mp.Id_Ministerio
                WHERE DATE(s.Fecha_Registro) BETWEEN ? AND ?
                  AND s.Programa = 'capacitacion_destino'
                  AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND ({$filtroPersonas})
                  {$whereExtra}
                GROUP BY {$ministerioExpr}
                ORDER BY {$ministerioExpr} ASC";

        return $this->query($sql, $params);
    }

    /**
     * Retorna lista de personas únicas inscritas en un programa,
     * con datos básicos actualizados desde la tabla persona.
     * Deduplicación por Cedula_Clave (Cedula o SIN-CEDULA-{Id_Persona}).
     */
    public function getInscritosBasicos(string $programa = 'universidad_vida', string $buscar = '', int $limit = 800): array {
        $programa = trim($programa);
        $buscar   = trim($buscar);
        $limit    = max(1, min(2000, $limit));

        $innerWhere  = ['1=1'];
        $innerParams = [];

        if ($programa !== '') {
            if ($programa === 'capacitacion_destino') {
                $innerWhere[] = "i.Programa IN ('capacitacion_destino','capacitacion_destino_nivel_1','capacitacion_destino_nivel_2','capacitacion_destino_nivel_3')";
            } else {
                $innerWhere[] = 'i.Programa = ?';
                $innerParams[] = $programa;
            }
        }

        if ($buscar !== '') {
            $like = '%' . $buscar . '%';
            $innerWhere[] = "(i.Nombre LIKE ? OR i.Cedula LIKE ? OR i.Telefono LIKE ?)";
            $innerParams[] = $like;
            $innerParams[] = $like;
            $innerParams[] = $like;
        }

        $innerWhereStr = implode(' AND ', $innerWhere);

        $sql = "SELECT
                    base.Id_Persona,
                    base.Cedula_Clave,
                    base.Id_Inscripcion,
                    COALESCE(
                        NULLIF(TRIM(CONCAT(COALESCE(p.Nombre,''), ' ', COALESCE(p.Apellido,''))), ''),
                        base.Nombre
                    ) AS Nombre,
                    COALESCE(NULLIF(TRIM(p.Genero),''), base.Genero) AS Genero,
                    COALESCE(p.Edad, base.Edad) AS Edad,
                    base.Segmento_Preferido,
                    COALESCE(NULLIF(TRIM(p.Numero_Documento),''), base.Cedula) AS Cedula,
                    COALESCE(NULLIF(TRIM(p.Telefono),''), base.Telefono) AS Telefono,
                    COALESCE(
                        NULLIF(TRIM(CONCAT(COALESCE(lp.Nombre,''), ' ', COALESCE(lp.Apellido,''))), ''),
                        base.Lider
                    ) AS Lider,
                    COALESCE(p.Id_Ministerio, base.Id_Ministerio) AS Id_Ministerio,
                    COALESCE(mp.Nombre_Ministerio, base.Ministerio) AS Ministerio,
                    base.Programa,
                    base.Fecha_Registro,
                    base.Documentos
                FROM (
                    SELECT
                        COALESCE(i.Id_Persona, 0) AS Id_Persona,
                        COALESCE(NULLIF(TRIM(i.Cedula),''), CONCAT('SIN-CEDULA-', COALESCE(i.Id_Persona, 0))) AS Cedula_Clave,
                        MAX(i.Id_Inscripcion)                                          AS Id_Inscripcion,
                        MAX(COALESCE(NULLIF(TRIM(i.Nombre),''), 'SIN NOMBRE'))         AS Nombre,
                        MAX(COALESCE(NULLIF(TRIM(i.Genero),''), ''))                   AS Genero,
                        MAX(COALESCE(i.Edad, 0))                                       AS Edad,
                        MAX(COALESCE(NULLIF(TRIM(i.Segmento_Preferido),''), ''))       AS Segmento_Preferido,
                        MAX(COALESCE(NULLIF(TRIM(i.Cedula),''), ''))                   AS Cedula,
                        MAX(COALESCE(NULLIF(TRIM(i.Telefono),''), ''))                 AS Telefono,
                        MAX(COALESCE(NULLIF(TRIM(i.Lider),''), ''))                    AS Lider,
                        MAX(COALESCE(i.Id_Ministerio, 0))                              AS Id_Ministerio,
                        MAX(COALESCE(NULLIF(TRIM(i.Nombre_Ministerio),''), ''))        AS Ministerio,
                        MAX(i.Programa)                                                AS Programa,
                        MAX(i.Fecha_Registro)                                          AS Fecha_Registro,
                        MAX(COALESCE(i.Documentos, ''))                                AS Documentos
                    FROM {$this->table} i
                    WHERE {$innerWhereStr}
                    GROUP BY
                        COALESCE(i.Id_Persona, 0),
                        COALESCE(NULLIF(TRIM(i.Cedula),''), CONCAT('SIN-CEDULA-', COALESCE(i.Id_Persona, 0)))
                ) base
                LEFT JOIN persona p  ON base.Id_Persona = p.Id_Persona AND base.Id_Persona > 0
                LEFT JOIN persona lp ON p.Id_Lider = lp.Id_Persona
                LEFT JOIN ministerio mp ON p.Id_Ministerio = mp.Id_Ministerio
                ORDER BY Nombre ASC
                LIMIT {$limit}";

        return $this->query($sql, $innerParams);
    }

    /**
     * Pagos registrados en movimientos (tabla escuela_formacion_pago_movimiento) por inscripción.
     *
     * @param array<int, int> $idsInscripcion
     * @return array<int, array{pagado: bool, valor: float}>
     */
    public function getMapaPagosMovimientosPorInscripcion(array $idsInscripcion): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $idsInscripcion), static function ($id) {
            return $id > 0;
        })));

        if (empty($ids)) {
            return [];
        }

        $mapa = [];
        foreach (array_chunk($ids, 400) as $lote) {
            $placeholders = implode(',', array_fill(0, count($lote), '?'));
            $sql = "SELECT
                        Id_Inscripcion,
                        SUM(COALESCE(Valor_Pago, 0)) AS Total_Pagado,
                        MAX(CASE WHEN TRIM(COALESCE(Metodo_Pago, '')) <> '' THEN 1 ELSE 0 END) AS Tiene_Metodo,
                        MAX(CASE WHEN TRIM(COALESCE(Referencia_Pago, '')) <> '' THEN 1 ELSE 0 END) AS Tiene_Ref
                    FROM {$this->tablePagos}
                    WHERE Id_Inscripcion IN ({$placeholders})
                    GROUP BY Id_Inscripcion";

            try {
                $rows = $this->query($sql, $lote);
            } catch (Throwable $e) {
                error_log('getMapaPagosMovimientosPorInscripcion: ' . $e->getMessage());
                continue;
            }

            foreach ((array)$rows as $row) {
                $idIns = (int)($row['Id_Inscripcion'] ?? 0);
                if ($idIns <= 0) {
                    continue;
                }
                $total = (float)($row['Total_Pagado'] ?? 0);
                $pagado = $total > 0
                    || (int)($row['Tiene_Metodo'] ?? 0) === 1
                    || (int)($row['Tiene_Ref'] ?? 0) === 1;
                $mapa[$idIns] = [
                    'pagado' => $pagado,
                    'valor' => $total,
                ];
            }
        }

        return $mapa;
    }

    /**
     * Pagos en movimientos agrupados por cédula normalizada (misma lógica que pantalla Pagos UV).
     *
     * @param array<int, string> $cedulas
     * @return array<string, array{pagado: bool, valor: float}>
     */
    public function getMapaPagosMovimientosPorCedula(array $cedulas): array {
        $cedulasNorm = array_values(array_unique(array_filter(array_map(static function ($cedula) {
            return preg_replace('/\D+/', '', (string)$cedula);
        }, $cedulas), static function ($cedula) {
            return $cedula !== '';
        })));

        if (empty($cedulasNorm)) {
            return [];
        }

        $exprCedula = $this->sqlExprCedulaNormalizada('Cedula');
        $mapa = [];

        foreach (array_chunk($cedulasNorm, 200) as $lote) {
            $placeholders = implode(',', array_fill(0, count($lote), '?'));
            $sql = "SELECT
                        {$exprCedula} AS Cedula_Norm,
                        SUM(COALESCE(Valor_Pago, 0)) AS Total_Pagado,
                        MAX(CASE WHEN TRIM(COALESCE(Metodo_Pago, '')) <> '' THEN 1 ELSE 0 END) AS Tiene_Metodo,
                        MAX(CASE WHEN TRIM(COALESCE(Referencia_Pago, '')) <> '' THEN 1 ELSE 0 END) AS Tiene_Ref
                    FROM {$this->tablePagos}
                    WHERE {$exprCedula} IN ({$placeholders})
                    GROUP BY Cedula_Norm";

            try {
                $rows = $this->query($sql, $lote);
            } catch (Throwable $e) {
                error_log('getMapaPagosMovimientosPorCedula: ' . $e->getMessage());
                continue;
            }

            foreach ((array)$rows as $row) {
                $cedulaNorm = preg_replace('/\D+/', '', (string)($row['Cedula_Norm'] ?? ''));
                if ($cedulaNorm === '') {
                    continue;
                }
                $total = (float)($row['Total_Pagado'] ?? 0);
                $pagado = $total > 0
                    || (int)($row['Tiene_Metodo'] ?? 0) === 1
                    || (int)($row['Tiene_Ref'] ?? 0) === 1;
                $mapa[$cedulaNorm] = [
                    'pagado' => $pagado,
                    'valor' => $total,
                ];
            }
        }

        return $mapa;
    }

    /**
     * Total pagado en movimientos para una cédula dentro del mismo programa (evita duplicar UV + encuentro).
     */
    public function getTotalPagadoMovimientosPorCedulaPrograma(string $cedula, string $programa): float {
        $cedula = preg_replace('/\D+/', '', trim($cedula));
        $programa = trim($programa);
        if ($cedula === '' || $programa === '') {
            return 0.0;
        }

        $exprCedula = $this->sqlExprCedulaNormalizada('s.Cedula');
        $where = [
            "{$exprCedula} = ?",
            'COALESCE(s.Valor_Pago, 0) > 0',
        ];
        $params = [$cedula];

        $wherePrograma = $this->getSqlFiltroProgramaPagos($programa, 's');
        if ($wherePrograma !== '') {
            $where[] = $wherePrograma;
        }

        $sql = "SELECT COALESCE(SUM(COALESCE(s.Valor_Pago, 0)), 0) AS Total_Pagado
                FROM {$this->tablePagos} s
                WHERE " . implode(' AND ', $where);

        try {
            $rows = $this->query($sql, $params);
            return (float)($rows[0]['Total_Pagado'] ?? 0);
        } catch (Throwable $e) {
            error_log('getTotalPagadoMovimientosPorCedulaPrograma: ' . $e->getMessage());
            return 0.0;
        }
    }
}
