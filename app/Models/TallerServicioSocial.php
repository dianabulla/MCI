<?php
/**
 * Agendamiento de citas — Servicio Social (submódulo de Talleres).
 *
 * IMPORTANTE: las solicitudes viven SOLO en talleres_servicio_social_cita.
 * No se crean ni actualizan registros en la tabla persona ni aparecen como "nuevas".
 */

require_once APP . '/Models/BaseModel.php';
require_once APP . '/Helpers/ServicioSocialDocumentos.php';

class TallerServicioSocial extends BaseModel {
    protected $table = 'talleres_servicio_social_cita';
    protected $primaryKey = 'Id_Cita';
    private string $tablaHistoria = 'talleres_servicio_social_historia_clinica';
    private string $tablaHorarioSabado = 'talleres_servicio_social_horario_sabado';

    /** @var array<string, string> */
    public const TIPOS_DOCUMENTO = [
        'Cedula de Ciudadania' => 'Cédula de Ciudadanía',
        'Cedula Extranjera' => 'Cédula Extranjera',
        'Tarjeta de Identidad' => 'Tarjeta de Identidad',
        'Registro Civil' => 'Registro Civil',
    ];

    /** @var array<string, string> */
    public const TIPOS_CITA = [
        'orientacion' => 'Orientación psicológica',
        'acompanamiento' => 'Acompañamiento psicosocial',
        'ansiedad' => 'Ansiedad / estrés',
        'depresion' => 'Estado de ánimo / depresión',
        'duelo' => 'Duelo / pérdida',
        'familia' => 'Familia / pareja',
        'infancia' => 'Niños / adolescentes',
        'crisis' => 'Crisis emocional',
        'otro' => 'Otro tema psicológico',
    ];

    /** Tipos antiguos: se siguen mostrando en citas ya guardadas. */
    /** @var array<string, string> */
    public const TIPOS_CITA_LEGADO = [
        'ayuda_alimentaria' => 'Ayuda alimentaria',
        'vivienda' => 'Vivienda / vivienda temporal',
        'salud' => 'Salud / remisión médica',
        'empleo' => 'Empleo / emprendimiento',
        'juridico' => 'Orientación jurídica',
    ];

    /** @var array<string, string> */
    public const REMITIDO_POR = [
        'ninguno' => 'No fui remitido(a)',
        'trabajo' => 'Trabajo / empresa',
        'eps' => 'EPS / IPS',
        'colegio' => 'Colegio / institución educativa',
        'iglesia' => 'Iglesia / ministerio',
        'familiar' => 'Familiar o amigo(a)',
        'entidad' => 'Entidad / fundación',
        'otro' => 'Otro',
    ];

    /** @var array<string, string> */
    public const ESTADOS = [
        'pendiente' => 'Pendiente',
        'confirmada' => 'Confirmada',
        'atendida' => 'Atendida',
        'cancelada' => 'Cancelada',
        'no_asistio' => 'No asistió',
    ];

    /**
     * Horarios de atención los sábados: las 24 horas del día (clave 24h => etiqueta).
     *
     * @return array<string, string>
     */
    public static function catalogoHorariosSabado(): array {
        static $horas = null;
        if (is_array($horas)) {
            return $horas;
        }

        $horas = [];
        for ($h = 0; $h < 24; $h++) {
            $clave = sprintf('%02d:00', $h);
            $hora12 = $h % 12;
            if ($hora12 === 0) {
                $hora12 = 12;
            }
            $sufijo = $h < 12 ? 'a.m.' : 'p.m.';
            $horas[$clave] = $hora12 . ':00 ' . $sufijo;
        }

        return $horas;
    }

    /** Estados que ocupan un cupo horario. */
    private const ESTADOS_OCUPAN_CUPO = ['pendiente', 'confirmada', 'atendida'];

    public function __construct() {
        parent::__construct();
        $this->ensureSchema();
        $this->ensureHistoriaSchema();
        $this->ensureHorarioSabadoSchema();
    }

    private function ensureSchema(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS talleres_servicio_social_cita (
                Id_Cita INT UNSIGNED NOT NULL AUTO_INCREMENT,
                Nombre VARCHAR(120) NOT NULL,
                Apellido VARCHAR(120) NOT NULL DEFAULT '',
                Tipo_Documento VARCHAR(60) NULL,
                Documento VARCHAR(40) NULL,
                Nombre_Eps VARCHAR(120) NULL,
                Telefono VARCHAR(40) NOT NULL,
                Email VARCHAR(160) NULL,
                Fecha_Preferida DATE NOT NULL,
                Hora_Preferida VARCHAR(20) NULL,
                Tipo_Cita VARCHAR(60) NOT NULL,
                Necesidad_Principal TEXT NOT NULL,
                Remitido_Por VARCHAR(60) NOT NULL DEFAULT 'ninguno',
                Remitido_Detalle VARCHAR(255) NULL,
                Documentos_Remision JSON NULL,
                Observaciones TEXT NULL,
                Estado VARCHAR(30) NOT NULL DEFAULT 'pendiente',
                Notas_Internas TEXT NULL,
                Fecha_Atencion DATETIME NULL,
                Ip_Origen VARCHAR(45) NULL,
                Fecha_Creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                Fecha_Actualizacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                Actualizado_Por INT UNSIGNED NULL,
                PRIMARY KEY (Id_Cita),
                KEY idx_ss_estado (Estado),
                KEY idx_ss_fecha_pref (Fecha_Preferida),
                KEY idx_ss_tipo (Tipo_Cita),
                KEY idx_ss_remitido (Remitido_Por),
                KEY idx_ss_creacion (Fecha_Creacion),
                KEY idx_ss_documento (Tipo_Documento, Documento)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->ensureColumn('talleres_servicio_social_cita', 'Tipo_Documento', 'VARCHAR(60) NULL AFTER Apellido');
        $this->ensureColumn('talleres_servicio_social_cita', 'Nombre_Eps', 'VARCHAR(120) NULL AFTER Documento');
        $this->ensureColumn('talleres_servicio_social_cita', 'Documentos_Remision', 'JSON NULL AFTER Remitido_Detalle');
        $this->ensureEstadoColumnaFlexible();
    }

    private function ensureHorarioSabadoSchema(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS {$this->tablaHorarioSabado} (
                Fecha DATE NOT NULL,
                Horas_Habilitadas JSON NOT NULL,
                Notas VARCHAR(255) NULL,
                Actualizado_Por INT UNSIGNED NULL,
                Fecha_Actualizacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (Fecha)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function ensureHistoriaSchema(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS {$this->tablaHistoria} (
                Id_Entrada INT UNSIGNED NOT NULL AUTO_INCREMENT,
                Tipo_Documento VARCHAR(60) NOT NULL,
                Documento VARCHAR(40) NOT NULL,
                Id_Cita INT UNSIGNED NULL,
                Fecha_Atencion DATETIME NOT NULL,
                Motivo_Consulta TEXT NULL,
                Diagnostico TEXT NULL,
                Formula TEXT NULL,
                Recomendaciones TEXT NULL,
                Observaciones TEXT NULL,
                Creado_Por INT UNSIGNED NULL,
                Fecha_Creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (Id_Entrada),
                KEY idx_hc_paciente (Tipo_Documento, Documento),
                KEY idx_hc_cita (Id_Cita),
                KEY idx_hc_fecha (Fecha_Atencion)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    private function ensureColumn(string $table, string $column, string $definition): void {
        try {
            $rows = $this->query("SHOW COLUMNS FROM {$table} LIKE ?", [$column]);
            if (empty($rows)) {
                $this->db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            }
        } catch (Throwable $e) {
            // Ignorar si la columna ya existe.
        }
    }

    private function ensureEstadoColumnaFlexible(): void {
        try {
            $this->db->exec("ALTER TABLE {$this->table} MODIFY COLUMN Estado VARCHAR(30) NOT NULL DEFAULT 'pendiente'");
        } catch (Throwable $e) {
            // Columna ya es VARCHAR o el motor no permite el cambio.
        }
    }

    public static function etiquetaTipoDocumento(string $clave): string {
        return self::TIPOS_DOCUMENTO[$clave] ?? ($clave !== '' ? $clave : '—');
    }

    /**
     * Si hay cédula pero no tipo (citas viejas o copiadas), se asume cédula de ciudadanía.
     *
     * @param array<string, mixed> $cita
     * @return array{ok:bool,tipo:string,documento:string,message:string}
     */
    public function resolverIdentidadPaciente(array $cita, bool $persistir = false): array {
        $documento = trim((string)($cita['Documento'] ?? ''));
        $tipo = self::normalizarTipoDocumento(trim((string)($cita['Tipo_Documento'] ?? '')));
        if ($documento === '') {
            return [
                'ok' => false,
                'tipo' => '',
                'documento' => '',
                'message' => 'La cita no tiene documento registrado.',
            ];
        }
        if ($tipo === '' || !isset(self::TIPOS_DOCUMENTO[$tipo])) {
            $tipo = 'Cedula de Ciudadania';
            $id = (int)($cita['Id_Cita'] ?? 0);
            if ($persistir && $id > 0) {
                try {
                    $this->update($id, ['Tipo_Documento' => $tipo]);
                } catch (Throwable $e) {
                    error_log('Servicio Social resolverIdentidadPaciente: ' . $e->getMessage());
                }
            }
        }

        return ['ok' => true, 'tipo' => $tipo, 'documento' => $documento, 'message' => ''];
    }

    public static function normalizarTipoDocumento(string $valor): string {
        $valor = trim($valor);
        if ($valor === '') {
            return '';
        }
        if (isset(self::TIPOS_DOCUMENTO[$valor])) {
            return $valor;
        }
        $map = [
            'cc' => 'Cedula de Ciudadania',
            'cedula de ciudadania' => 'Cedula de Ciudadania',
            'cédula de ciudadanía' => 'Cedula de Ciudadania',
            'ce' => 'Cedula Extranjera',
            'cedula extranjera' => 'Cedula Extranjera',
            'ti' => 'Tarjeta de Identidad',
            'tarjeta de identidad' => 'Tarjeta de Identidad',
            'rc' => 'Registro Civil',
            'registro civil' => 'Registro Civil',
        ];
        $k = mb_strtolower($valor);
        return $map[$k] ?? $valor;
    }

    public static function etiquetaTipo(string $clave): string {
        if (isset(self::TIPOS_CITA[$clave])) {
            return self::TIPOS_CITA[$clave];
        }
        if (isset(self::TIPOS_CITA_LEGADO[$clave])) {
            return self::TIPOS_CITA_LEGADO[$clave];
        }
        return $clave !== '' ? $clave : '—';
    }

    /**
     * @return array<string, string>
     */
    public static function tiposCitaParaFiltro(): array {
        return self::TIPOS_CITA + self::TIPOS_CITA_LEGADO;
    }

    public static function etiquetaRemitido(string $clave): string {
        return self::REMITIDO_POR[$clave] ?? ($clave !== '' ? $clave : '—');
    }

    public static function etiquetaEstado(string $clave): string {
        $clave = self::normalizarEstado($clave);
        return self::ESTADOS[$clave] ?? ($clave !== '' ? $clave : '—');
    }

    public static function normalizarEstado(string $valor): string {
        $valor = strtolower(trim($valor));
        $valor = str_replace([' ', '-'], '_', $valor);
        $alias = [
            'noasistio' => 'no_asistio',
            'no_asiste' => 'no_asistio',
            'inasistencia' => 'no_asistio',
            'cancelado' => 'cancelada',
            'anulado' => 'cancelada',
            'anulada' => 'cancelada',
        ];
        if (isset($alias[$valor])) {
            return $alias[$valor];
        }
        return $valor;
    }

    public static function normalizarDocumento(string $documento): string {
        $documento = strtoupper(trim($documento));
        if ($documento === '') {
            return '';
        }
        return (string)preg_replace('/[^A-Z0-9]/', '', $documento);
    }

    /** Lunes (1) a viernes (5) se pueden enviar solicitudes. */
    public static function puedeAgendarHoy(): bool {
        $dow = (int)date('N');
        return $dow >= 1 && $dow <= 5;
    }

    public static function esSabado(string $fecha): bool {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }
        return (int)date('N', strtotime($fecha)) === 6;
    }

    /**
     * Sábado de la semana en curso (lunes a sábado). El domingo esa semana ya cerró.
     */
    public static function sabadoSemanaActual(): ?string {
        $hoy = new DateTime('today');
        $dow = (int)$hoy->format('N');
        if ($dow === 7) {
            return null;
        }
        if ($dow === 6) {
            return $hoy->format('Y-m-d');
        }
        $hoy->modify('+' . (6 - $dow) . ' days');
        return $hoy->format('Y-m-d');
    }

    /**
     * Único sábado que el público puede agendar: el de esta semana.
     *
     * @return array<int, array{fecha:string,label:string}>
     */
    public static function sabadosAgendaPublica(): array {
        $fecha = self::sabadoSemanaActual();
        if ($fecha === null || $fecha < date('Y-m-d')) {
            return [];
        }

        return [[
            'fecha' => $fecha,
            'label' => self::etiquetaSabado($fecha),
        ]];
    }

    /**
     * Próximos sábados (configuración interna / admin).
     *
     * @return array<int, array{fecha:string,label:string}>
     */
    public static function proximosSabados(int $cantidad = 12): array {
        $cantidad = max(1, min(52, $cantidad));
        $base = new DateTime('today');
        while ((int)$base->format('N') !== 6) {
            $base->modify('+1 day');
        }

        $out = [];
        for ($i = 0; $i < $cantidad; $i++) {
            $fecha = $base->format('Y-m-d');
            $out[] = [
                'fecha' => $fecha,
                'label' => self::etiquetaSabado($fecha),
            ];
            $base->modify('+7 days');
        }

        return $out;
    }

    /**
     * Sábados para filtrar la agenda del listado (pasados y próximos).
     *
     * @return array<int, array{fecha:string,label:string}>
     */
    public static function sabadosParaFiltroAgenda(int $atras = 10, int $adelante = 12): array {
        $atras = max(0, min(52, $atras));
        $adelante = max(0, min(52, $adelante));
        $base = new DateTime('today');
        while ((int)$base->format('N') !== 6) {
            $base->modify('+1 day');
        }
        if ($atras > 0) {
            $base->modify('-' . ($atras * 7) . ' days');
        }

        $out = [];
        $total = $atras + $adelante + 1;
        for ($i = 0; $i < $total; $i++) {
            $fecha = $base->format('Y-m-d');
            $out[] = [
                'fecha' => $fecha,
                'label' => self::etiquetaSabado($fecha),
            ];
            $base->modify('+7 days');
        }

        return $out;
    }

    public static function etiquetaSabado(string $fecha): string {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $fecha;
        }
        $ts = strtotime($fecha);
        if (!$ts) {
            return $fecha;
        }

        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];
        $dia = (int)date('j', $ts);
        $mes = (int)date('n', $ts);
        $anio = date('Y', $ts);

        return 'Sábado ' . $dia . ' de ' . ($meses[$mes] ?? date('F', $ts)) . ' de ' . $anio;
    }

    private static function sqlExprDocumentoNorm(string $columna): string {
        return "REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE({$columna}, ''))), ' ', ''), '.', ''), '-', '')";
    }

    /**
     * Horas habilitadas manualmente para un sábado. Null = todas las del catálogo.
     *
     * @return array<int, string>|null
     */
    public function horasHabilitadasSabado(string $fecha): ?array {
        if (!self::esSabado($fecha)) {
            return null;
        }

        $rows = $this->query(
            "SELECT Horas_Habilitadas FROM {$this->tablaHorarioSabado} WHERE Fecha = ? LIMIT 1",
            [$fecha]
        );
        if (empty($rows[0]['Horas_Habilitadas'])) {
            return null;
        }

        $decoded = json_decode((string)$rows[0]['Horas_Habilitadas'], true);
        if (!is_array($decoded)) {
            return null;
        }

        $horas = [];
        foreach ($decoded as $hora) {
            $norm = self::normalizarHora((string)$hora);
            if ($norm !== '' && isset(self::catalogoHorariosSabado()[$norm])) {
                $horas[] = $norm;
            }
        }

        return array_values(array_unique($horas));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerConfigHorarioSabado(string $fecha): ?array {
        if (!self::esSabado($fecha)) {
            return null;
        }

        $rows = $this->query(
            "SELECT * FROM {$this->tablaHorarioSabado} WHERE Fecha = ? LIMIT 1",
            [$fecha]
        );

        return $rows[0] ?? null;
    }

    /**
     * @param array<int, string> $horasHabilitadas
     * @return array{ok:bool,message:string}
     */
    public function guardarHorarioSabado(string $fecha, array $horasHabilitadas, string $notas = '', int $idUsuario = 0): array {
        if (!self::esSabado($fecha)) {
            return ['ok' => false, 'message' => 'Solo se configuran sábados.'];
        }
        if ($fecha < date('Y-m-d')) {
            return ['ok' => false, 'message' => 'No se puede modificar un sábado pasado.'];
        }

        $horas = [];
        foreach ($horasHabilitadas as $hora) {
            $norm = self::normalizarHora((string)$hora);
            if ($norm !== '' && isset(self::catalogoHorariosSabado()[$norm])) {
                $horas[] = $norm;
            }
        }
        $horas = array_values(array_unique($horas));

        if ($horas === []) {
            return ['ok' => false, 'message' => 'Debes habilitar al menos un horario o eliminar la configuración.'];
        }

        $json = json_encode($horas, JSON_UNESCAPED_UNICODE);
        $notas = trim($notas);
        $params = [
            $fecha,
            $json,
            $notas !== '' ? mb_substr($notas, 0, 255) : null,
            $idUsuario > 0 ? $idUsuario : null,
        ];
        $sql = "INSERT INTO {$this->tablaHorarioSabado} (Fecha, Horas_Habilitadas, Notas, Actualizado_Por)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    Horas_Habilitadas = VALUES(Horas_Habilitadas),
                    Notas = VALUES(Notas),
                    Actualizado_Por = VALUES(Actualizado_Por)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt || !$stmt->execute($params)) {
            return ['ok' => false, 'message' => 'No se pudo guardar la configuración de horarios.'];
        }

        return ['ok' => true, 'message' => 'Horarios del sábado actualizados.'];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public function eliminarHorarioSabado(string $fecha): array {
        if (!self::esSabado($fecha)) {
            return ['ok' => false, 'message' => 'Fecha no válida.'];
        }

        $stmt = $this->db->prepare("DELETE FROM {$this->tablaHorarioSabado} WHERE Fecha = ?");
        if (!$stmt || !$stmt->execute([$fecha])) {
            return ['ok' => false, 'message' => 'No se pudo eliminar la configuración.'];
        }

        return ['ok' => true, 'message' => 'Se restauraron todos los horarios para ese sábado.'];
    }

    /**
     * Horas no disponibles por bloqueo manual (fuera de la ventana configurada).
     *
     * @return array<int, string>
     */
    public function horasBloqueadasManualmente(string $fecha): array {
        $habilitadas = $this->horasHabilitadasSabado($fecha);
        if ($habilitadas === null) {
            return [];
        }

        $bloqueadas = [];
        foreach (array_keys(self::catalogoHorariosSabado()) as $hora) {
            if (!in_array($hora, $habilitadas, true)) {
                $bloqueadas[] = $hora;
            }
        }

        return $bloqueadas;
    }

    public static function normalizarHora(string $hora): string {
        $hora = trim($hora);
        if ($hora === '') {
            return '';
        }
        if (isset(self::catalogoHorariosSabado()[$hora])) {
            return $hora;
        }
        if (preg_match('/^(\d{1,2}):(\d{2})/', $hora, $m)) {
            $normalizada = sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
            if (isset(self::catalogoHorariosSabado()[$normalizada])) {
                return $normalizada;
            }
        }
        return $hora;
    }

    public static function etiquetaHora(string $hora): string {
        $hora = self::normalizarHora($hora);
        return self::catalogoHorariosSabado()[$hora] ?? ($hora !== '' ? $hora : '—');
    }

    /**
     * Fecha y hora de atención = día y cupo agendados (no un campo aparte).
     */
    public static function fechaHoraDesdeAgenda(?string $fecha, ?string $hora): string {
        $fecha = trim((string)$fecha);
        $hora = self::normalizarHora((string)$hora);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return date('Y-m-d H:i:s');
        }
        $hm = '08:00';
        if ($hora !== '' && preg_match('/^(\d{2}:\d{2})/', $hora, $m)) {
            $hm = $m[1];
        }
        return $fecha . ' ' . $hm . ':00';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarEnPersonas(string $tipoDocumento, string $documento): ?array {
        $tipoDocumento = self::normalizarTipoDocumento(trim($tipoDocumento));
        $docNorm = self::normalizarDocumento($documento);
        if ($docNorm === '') {
            return null;
        }

        $exprDoc = self::sqlExprDocumentoNorm('Numero_Documento');
        $sql = "SELECT Id_Persona, Nombre, Apellido, Tipo_Documento, Numero_Documento, Telefono, Email
                FROM persona
                WHERE {$exprDoc} = ?";
        $params = [$docNorm];

        if ($tipoDocumento !== '') {
            $sql .= ' AND (Tipo_Documento = ? OR Tipo_Documento IS NULL OR TRIM(Tipo_Documento) = \'\')';
            $params[] = $tipoDocumento;
        }

        $sql .= ' ORDER BY Id_Persona DESC LIMIT 1';
        $rows = $this->query($sql, $params);
        if (empty($rows[0])) {
            $rows = $this->query(
                "SELECT Id_Persona, Nombre, Apellido, Tipo_Documento, Numero_Documento, Telefono, Email
                 FROM persona
                 WHERE {$exprDoc} = ?
                 ORDER BY Id_Persona DESC LIMIT 1",
                [$docNorm]
            );
        }
        if (empty($rows[0])) {
            return null;
        }

        $p = $rows[0];
        $tipoPersona = self::normalizarTipoDocumento(trim((string)($p['Tipo_Documento'] ?? '')));
        return [
            'fuente' => 'persona',
            'nombre' => trim((string)($p['Nombre'] ?? '')),
            'apellido' => trim((string)($p['Apellido'] ?? '')),
            'tipo_documento' => $tipoPersona !== '' ? $tipoPersona : ($tipoDocumento !== '' ? $tipoDocumento : 'Cedula de Ciudadania'),
            'documento' => trim((string)($p['Numero_Documento'] ?? '')),
            'telefono' => trim((string)($p['Telefono'] ?? '')),
            'email' => trim((string)($p['Email'] ?? '')),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarUltimaCitaPaciente(string $tipoDocumento, string $documento): ?array {
        $tipoDocumento = self::normalizarTipoDocumento(trim($tipoDocumento));
        $docNorm = self::normalizarDocumento($documento);
        if ($docNorm === '') {
            return null;
        }

        $exprDoc = self::sqlExprDocumentoNorm('Documento');
        $params = [$docNorm];
        $sql = "SELECT * FROM {$this->table} WHERE {$exprDoc} = ?";
        if ($tipoDocumento !== '') {
            $sql .= ' AND Tipo_Documento = ?';
            $params[] = $tipoDocumento;
        }
        $sql .= ' ORDER BY Fecha_Creacion DESC, Id_Cita DESC LIMIT 1';

        $rows = $this->query($sql, $params);
        if (empty($rows[0]) && $tipoDocumento !== '') {
            $rows = $this->query(
                "SELECT * FROM {$this->table} WHERE {$exprDoc} = ?
                 ORDER BY Fecha_Creacion DESC, Id_Cita DESC LIMIT 1",
                [$docNorm]
            );
        }

        return $rows[0] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function buscarUltimaCitaPorDocumento(string $documento): ?array {
        $docNorm = self::normalizarDocumento($documento);
        if ($docNorm === '') {
            return null;
        }

        $rows = $this->query(
            "SELECT * FROM {$this->table}
             WHERE REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(Documento, ''))), ' ', ''), '.', ''), '-', '') = ?
             ORDER BY Fecha_Creacion DESC, Id_Cita DESC
             LIMIT 1",
            [$docNorm]
        );

        return $rows[0] ?? null;
    }

    public function contarCitasPaciente(string $tipoDocumento, string $documento): int {
        $tipoDocumento = self::normalizarTipoDocumento(trim($tipoDocumento));
        $docNorm = self::normalizarDocumento($documento);
        if ($docNorm === '') {
            return 0;
        }

        $exprDoc = self::sqlExprDocumentoNorm('Documento');
        $params = [$docNorm];
        $sql = "SELECT COUNT(*) AS Total FROM {$this->table} WHERE {$exprDoc} = ?";
        if ($tipoDocumento !== '') {
            $sql .= ' AND Tipo_Documento = ?';
            $params[] = $tipoDocumento;
        }

        $rows = $this->query($sql, $params);

        return (int)($rows[0]['Total'] ?? 0);
    }

    /**
     * @return array{prefill:array<string,string>,fuentes:array<int,string>,mensaje:string,citas_anteriores:int}
     */
    public function combinarDatosPrefill(?array $persona, ?array $citaSs): array {
        $fuentes = [];
        $prefill = [
            'nombre' => '',
            'apellido' => '',
            'tipo_documento' => '',
            'documento' => '',
            'telefono' => '',
            'email' => '',
            'nombre_eps' => '',
        ];

        if (is_array($persona)) {
            $fuentes[] = 'persona';
            foreach (['nombre', 'apellido', 'tipo_documento', 'documento', 'telefono', 'email'] as $campo) {
                if (trim((string)($persona[$campo] ?? '')) !== '') {
                    $prefill[$campo] = trim((string)$persona[$campo]);
                }
            }
        }

        if (is_array($citaSs)) {
            $fuentes[] = 'servicio_social';
            $map = [
                'nombre' => 'Nombre',
                'apellido' => 'Apellido',
                'tipo_documento' => 'Tipo_Documento',
                'documento' => 'Documento',
                'telefono' => 'Telefono',
                'email' => 'Email',
                'nombre_eps' => 'Nombre_Eps',
            ];
            foreach ($map as $dest => $src) {
                $val = trim((string)($citaSs[$src] ?? ''));
                if ($val !== '') {
                    $prefill[$dest] = $val;
                }
            }
        }

        $citasAnteriores = 0;
        if ($prefill['documento'] !== '') {
            $citasAnteriores = $this->contarCitasPaciente(
                $prefill['tipo_documento'],
                $prefill['documento']
            );
        }

        $mensaje = '';
        if (in_array('persona', $fuentes, true) && in_array('servicio_social', $fuentes, true)) {
            $mensaje = 'Encontramos tus datos en el directorio de personas y en citas anteriores de Servicio Social.';
        } elseif (in_array('servicio_social', $fuentes, true)) {
            $mensaje = 'Ya tienes citas previas en Servicio Social. Trajimos tus datos registrados.';
        } elseif (in_array('persona', $fuentes, true)) {
            $mensaje = 'Encontramos tus datos en nuestro directorio. Solo los usamos para completar el formulario.';
        }

        return [
            'prefill' => $prefill,
            'fuentes' => $fuentes,
            'mensaje' => $mensaje,
            'citas_anteriores' => $citasAnteriores,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function horasOcupadas(string $fecha, int $excluirIdCita = 0): array {
        if (!self::esSabado($fecha)) {
            return array_keys(self::catalogoHorariosSabado());
        }

        $ocupadas = $this->horasBloqueadasManualmente($fecha);

        $sql = "SELECT Hora_Preferida FROM {$this->table}
                WHERE Fecha_Preferida = ? AND Estado IN ('" . implode("','", self::ESTADOS_OCUPAN_CUPO) . "')";
        $params = [$fecha];
        if ($excluirIdCita > 0) {
            $sql .= ' AND Id_Cita <> ?';
            $params[] = $excluirIdCita;
        }

        $rows = $this->query($sql, $params);
        foreach ($rows as $row) {
            $h = self::normalizarHora((string)($row['Hora_Preferida'] ?? ''));
            if ($h !== '') {
                $ocupadas[] = $h;
            }
        }

        return array_values(array_unique($ocupadas));
    }

    /**
     * Una persona (mismo documento) no puede tener más de una cita activa el mismo sábado.
     */
    public function tieneCitaActivaEnFecha(string $documento, string $fecha, int $excluirIdCita = 0): bool {
        $docNorm = self::normalizarDocumento($documento);
        if ($docNorm === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }

        $exprDoc = self::sqlExprDocumentoNorm('Documento');
        $estados = implode("','", self::ESTADOS_OCUPAN_CUPO);
        $sql = "SELECT Id_Cita FROM {$this->table}
                WHERE {$exprDoc} = ?
                  AND Fecha_Preferida = ?
                  AND Estado IN ('{$estados}')";
        $params = [$docNorm, $fecha];
        if ($excluirIdCita > 0) {
            $sql .= ' AND Id_Cita <> ?';
            $params[] = $excluirIdCita;
        }
        $sql .= ' LIMIT 1';
        $rows = $this->query($sql, $params);

        return !empty($rows[0]);
    }

    /**
     * @return array<int, string>
     */
    public function horasDisponiblesCatalogo(string $fecha): array {
        $habilitadas = $this->horasHabilitadasSabado($fecha);
        if ($habilitadas === null) {
            return array_keys(self::catalogoHorariosSabado());
        }

        return $habilitadas;
    }

    /**
     * @return array{fecha:string,horas:array<int,array{hora:string,label:string,disponible:bool}>}
     */
    public function disponibilidadFecha(string $fecha, int $excluirIdCita = 0): array {
        $ocupadas = $this->horasOcupadas($fecha, $excluirIdCita);
        $catalogo = $this->horasDisponiblesCatalogo($fecha);
        $horas = [];
        foreach (self::catalogoHorariosSabado() as $hora => $label) {
            $enCatalogo = in_array($hora, $catalogo, true);
            $horas[] = [
                'hora' => $hora,
                'label' => $label,
                'disponible' => $enCatalogo && !in_array($hora, $ocupadas, true),
                'bloqueada' => !$enCatalogo,
            ];
        }

        return ['fecha' => $fecha, 'horas' => $horas];
    }

    /**
     * @return array{ok:bool,error?:string}
     */
    public function validarAgendamiento(string $fecha, string $hora, string $documento = ''): array {
        if (!self::puedeAgendarHoy()) {
            return ['ok' => false, 'error' => 'Las solicitudes de cita solo se reciben de lunes a viernes.'];
        }
        if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return ['ok' => false, 'error' => 'Indica una fecha preferida válida.'];
        }
        if (!self::esSabado($fecha)) {
            return ['ok' => false, 'error' => 'Las citas solo se agendan los sábados.'];
        }
        if ($fecha < date('Y-m-d')) {
            return ['ok' => false, 'error' => 'La fecha preferida no puede ser anterior a hoy.'];
        }

        $sabadoSemana = self::sabadoSemanaActual();
        if ($sabadoSemana === null || $fecha !== $sabadoSemana) {
            return ['ok' => false, 'error' => 'Solo se puede agendar el sábado de esta semana.'];
        }

        if ($documento !== '' && $this->tieneCitaActivaEnFecha($documento, $fecha)) {
            return ['ok' => false, 'error' => 'Ya tienes una cita para ese sábado. Solo se permite una agenda por persona el mismo día.'];
        }

        $horaNorm = self::normalizarHora($hora);
        if ($horaNorm === '' || !isset(self::catalogoHorariosSabado()[$horaNorm])) {
            return ['ok' => false, 'error' => 'Selecciona un horario válido.'];
        }

        $catalogo = $this->horasDisponiblesCatalogo($fecha);
        if (!in_array($horaNorm, $catalogo, true)) {
            return ['ok' => false, 'error' => 'Ese horario no está disponible. Elige otra hora.'];
        }

        $ocupadas = $this->horasOcupadas($fecha);
        if (in_array($horaNorm, $ocupadas, true)) {
            return ['ok' => false, 'error' => 'Ese horario ya no está disponible. Elige otra hora.'];
        }

        return ['ok' => true];
    }

    /**
     * El equipo puede agendar cualquier sábado futuro (no solo el de esta semana).
     *
     * @return array{ok:bool,error?:string}
     */
    public function validarAgendamientoInterno(string $fecha, string $hora, string $documento = ''): array {
        if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return ['ok' => false, 'error' => 'Indica un sábado válido.'];
        }
        if (!self::esSabado($fecha)) {
            return ['ok' => false, 'error' => 'Las citas solo se agendan los sábados.'];
        }
        if ($fecha < date('Y-m-d')) {
            return ['ok' => false, 'error' => 'No se puede agendar en una fecha pasada.'];
        }
        if ($documento !== '' && $this->tieneCitaActivaEnFecha($documento, $fecha)) {
            return ['ok' => false, 'error' => 'Esta persona ya tiene una cita activa ese sábado.'];
        }

        $horaNorm = self::normalizarHora($hora);
        if ($horaNorm === '' || !isset(self::catalogoHorariosSabado()[$horaNorm])) {
            return ['ok' => false, 'error' => 'Selecciona un horario.'];
        }

        $catalogo = $this->horasDisponiblesCatalogo($fecha);
        if (!in_array($horaNorm, $catalogo, true)) {
            return ['ok' => false, 'error' => 'Ese horario no está habilitado ese sábado.'];
        }

        $ocupadas = $this->horasOcupadas($fecha);
        if (in_array($horaNorm, $ocupadas, true)) {
            return ['ok' => false, 'error' => 'Ese horario ya está ocupado. Elige otra hora.'];
        }

        return ['ok' => true];
    }

    /**
     * @param array{estado?:string,tipo?:string,remitido?:string,buscar?:string,desde?:string,hasta?:string} $filtros
     * @return array<int, array<string, mixed>>
     */
    public function listar(array $filtros = []): array {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        $estado = trim((string)($filtros['estado'] ?? ''));
        if ($estado !== '' && isset(self::ESTADOS[$estado])) {
            $sql .= ' AND Estado = ?';
            $params[] = $estado;
        }

        $tipo = trim((string)($filtros['tipo'] ?? ''));
        $tiposFiltro = self::tiposCitaParaFiltro();
        if ($tipo !== '' && isset($tiposFiltro[$tipo])) {
            $sql .= ' AND Tipo_Cita = ?';
            $params[] = $tipo;
        }

        $remitido = trim((string)($filtros['remitido'] ?? ''));
        if ($remitido !== '' && isset(self::REMITIDO_POR[$remitido])) {
            $sql .= ' AND Remitido_Por = ?';
            $params[] = $remitido;
        }

        $desde = trim((string)($filtros['desde'] ?? ''));
        if ($desde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $sql .= ' AND Fecha_Preferida >= ?';
            $params[] = $desde;
        }

        $hasta = trim((string)($filtros['hasta'] ?? ''));
        if ($hasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $sql .= ' AND Fecha_Preferida <= ?';
            $params[] = $hasta;
        }

        $sabado = trim((string)($filtros['sabado'] ?? ''));
        if ($sabado !== '' && self::esSabado($sabado)) {
            $sql .= ' AND Fecha_Preferida = ?';
            $params[] = $sabado;
        }

        $buscar = trim((string)($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $docNorm = self::normalizarDocumento($buscar);
            $telNorm = preg_replace('/\D+/', '', $buscar) ?? '';
            $exprDoc = self::sqlExprDocumentoNorm('Documento');
            $exprTel = "REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(Telefono, ''), ' ', ''), '-', ''), '(', ''), ')', '')";
            $exprNombre = "CONCAT_WS(' ', COALESCE(Nombre, ''), COALESCE(Apellido, ''))";

            $tokens = preg_split('/\s+/u', $buscar) ?: [$buscar];
            $tokens = array_values(array_filter($tokens, static function ($t) {
                return mb_strlen(trim((string)$t), 'UTF-8') >= 2;
            }));
            if ($tokens === []) {
                $tokens = [$buscar];
            }

            $sql .= ' AND (';
            $partes = [];
            if ($docNorm !== '' && preg_match('/[0-9A-Z]/', $docNorm)) {
                $partes[] = "({$exprDoc} LIKE ? OR Documento LIKE ?)";
                $likeDoc = '%' . $docNorm . '%';
                $params[] = $likeDoc;
                $params[] = '%' . $buscar . '%';
            }
            if ($telNorm !== '' && strlen($telNorm) >= 4) {
                $partes[] = "({$exprTel} LIKE ? OR Telefono LIKE ?)";
                $params[] = '%' . $telNorm . '%';
                $params[] = '%' . $buscar . '%';
            }
            $nombreAnd = [];
            foreach ($tokens as $tok) {
                $nombreAnd[] = "{$exprNombre} LIKE ?";
                $params[] = '%' . $tok . '%';
            }
            $partes[] = '(' . implode(' AND ', $nombreAnd) . ')';
            $partes[] = 'Email LIKE ?';
            $params[] = '%' . $buscar . '%';
            $partes[] = 'Necesidad_Principal LIKE ?';
            $params[] = '%' . $buscar . '%';
            $sql .= implode(' OR ', $partes) . ')';
        }

        $sql .= ' ORDER BY Fecha_Preferida DESC, Fecha_Creacion DESC, Id_Cita DESC';

        return self::quedarseUltimaCitaPorPersona($this->query($sql, $params));
    }

    /**
     * @param array<string, mixed> $cita
     */
    public static function clavePersonaCita(array $cita): string {
        $doc = self::normalizarDocumento((string)($cita['Documento'] ?? ''));
        if ($doc !== '') {
            return 'd:' . $doc;
        }
        $tel = preg_replace('/\D+/', '', (string)($cita['Telefono'] ?? '')) ?? '';
        $nombre = mb_strtolower(trim((string)($cita['Nombre'] ?? '') . ' ' . (string)($cita['Apellido'] ?? '')), 'UTF-8');
        if ($tel !== '' || $nombre !== '') {
            return 'p:' . $tel . '|' . $nombre;
        }

        return 'id:' . (int)($cita['Id_Cita'] ?? 0);
    }

    /**
     * Una fila por persona: se conserva la cita más reciente (ya ordenada desc).
     *
     * @param array<int, array<string, mixed>> $citas
     * @return array<int, array<string, mixed>>
     */
    public static function quedarseUltimaCitaPorPersona(array $citas): array {
        $visto = [];
        $out = [];
        foreach ($citas as $cita) {
            $clave = self::clavePersonaCita($cita);
            if (isset($visto[$clave])) {
                continue;
            }
            $visto[$clave] = true;
            $out[] = $cita;
        }

        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $citas
     * @return array{pendiente:int,confirmada:int,atendida:int,cancelada:int,no_asistio:int,total:int}
     */
    public static function contarEstadosDeCitas(array $citas): array {
        $out = [
            'pendiente' => 0,
            'confirmada' => 0,
            'atendida' => 0,
            'cancelada' => 0,
            'no_asistio' => 0,
            'total' => 0,
        ];
        foreach ($citas as $cita) {
            $est = self::normalizarEstado((string)($cita['Estado'] ?? ''));
            if (isset($out[$est])) {
                $out[$est]++;
            }
            $out['total']++;
        }

        return $out;
    }

    /**
     * @return array{pendiente:int,confirmada:int,atendida:int,cancelada:int,no_asistio:int,total:int}
     */
    public function contarPorEstado(): array {
        $out = [
            'pendiente' => 0,
            'confirmada' => 0,
            'atendida' => 0,
            'cancelada' => 0,
            'no_asistio' => 0,
            'total' => 0,
        ];
        $rows = $this->query("SELECT Estado, COUNT(*) AS Total FROM {$this->table} GROUP BY Estado");
        foreach ($rows as $row) {
            $est = (string)($row['Estado'] ?? '');
            $n = (int)($row['Total'] ?? 0);
            if (isset($out[$est])) {
                $out[$est] = $n;
            }
            $out['total'] += $n;
        }
        return $out;
    }

    /**
     * Guarda una cita del formulario público.
     * No usa ni crea registros en persona: aislamiento total respecto a discipular/personas.
     *
     * @param array<string, mixed> $datos
     * @return array{ok:bool,errors:array<int,string>,id?:int}
     */
    public function crearCitaPublica(array $datos, ?string $ip = null): array {
        $errors = [];

        $nombre = trim((string)($datos['nombre'] ?? ''));
        $apellido = trim((string)($datos['apellido'] ?? ''));
        $tipoDocumento = self::normalizarTipoDocumento(trim((string)($datos['tipo_documento'] ?? '')));
        $documento = trim((string)($datos['documento'] ?? ''));
        $nombreEps = trim((string)($datos['nombre_eps'] ?? ''));
        $telefono = trim((string)($datos['telefono'] ?? ''));
        $email = trim((string)($datos['email'] ?? ''));
        $fechaPref = trim((string)($datos['fecha_preferida'] ?? ''));
        $horaPref = trim((string)($datos['hora_preferida'] ?? ''));
        $tipo = trim((string)($datos['tipo_cita'] ?? ''));
        $necesidad = trim((string)($datos['necesidad_principal'] ?? ''));
        $remitido = trim((string)($datos['remitido_por'] ?? 'ninguno'));
        $remitidoDetalle = trim((string)($datos['remitido_detalle'] ?? ''));
        $observaciones = trim((string)($datos['observaciones'] ?? ''));

        if ($nombre === '') {
            $errors[] = 'El nombre es obligatorio.';
        }
        if ($tipoDocumento === '' || !isset(self::TIPOS_DOCUMENTO[$tipoDocumento])) {
            $errors[] = 'Selecciona el tipo de documento.';
        }
        if ($documento === '') {
            $errors[] = 'El número de documento (cédula) es obligatorio.';
        } elseif (!preg_match('/^[0-9A-Za-z.-]{3,40}$/', $documento)) {
            $errors[] = 'El número de documento no es válido.';
        }
        if ($telefono === '') {
            $errors[] = 'El teléfono es obligatorio.';
        }
        $validacionAgenda = $this->validarAgendamiento($fechaPref, $horaPref, $documento);
        if (!$validacionAgenda['ok']) {
            $errors[] = (string)($validacionAgenda['error'] ?? 'Fecha u hora no válida.');
        }
        if ($tipo === '' || !isset(self::TIPOS_CITA[$tipo])) {
            $errors[] = 'Selecciona el tipo de cita.';
        }
        if ($necesidad === '') {
            $errors[] = 'Describe tu principal necesidad.';
        }
        if ($remitido === '' || !isset(self::REMITIDO_POR[$remitido])) {
            $remitido = 'ninguno';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El correo no es válido.';
        }
        if (in_array($remitido, ['otro', 'entidad', 'trabajo', 'eps', 'colegio'], true) && $remitidoDetalle === '') {
            $errors[] = 'Indica el detalle de quién te remitió.';
        }
        if ($remitido === 'eps' && $nombreEps === '') {
            $errors[] = 'Indica el nombre de tu EPS.';
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $horaPrefNorm = self::normalizarHora($horaPref);

        $id = $this->create([
            'Nombre' => mb_substr($nombre, 0, 120),
            'Apellido' => mb_substr($apellido, 0, 120),
            'Tipo_Documento' => $tipoDocumento,
            'Documento' => mb_substr($documento, 0, 40),
            'Nombre_Eps' => $nombreEps !== '' ? mb_substr($nombreEps, 0, 120) : null,
            'Telefono' => mb_substr($telefono, 0, 40),
            'Email' => $email !== '' ? mb_substr($email, 0, 160) : null,
            'Fecha_Preferida' => $fechaPref,
            'Hora_Preferida' => $horaPrefNorm,
            'Tipo_Cita' => $tipo,
            'Necesidad_Principal' => $necesidad,
            'Remitido_Por' => $remitido,
            'Remitido_Detalle' => $remitidoDetalle !== '' ? mb_substr($remitidoDetalle, 0, 255) : null,
            'Observaciones' => $observaciones !== '' ? $observaciones : null,
            'Estado' => 'pendiente',
            'Ip_Origen' => $ip !== null && $ip !== '' ? mb_substr($ip, 0, 45) : null,
        ]);

        if (!$id) {
            return ['ok' => false, 'errors' => ['No se pudo guardar la solicitud. Inténtalo de nuevo.']];
        }

        return ['ok' => true, 'errors' => [], 'id' => (int)$id];
    }

    /**
     * Nueva cita para la misma persona (seguimiento), desde el detalle.
     *
     * @param array<string, mixed> $datos
     * @return array{ok:bool,message:string,id?:int}
     */
    public function crearCitaSeguimiento(int $idCitaOrigen, array $datos): array {
        $origen = $this->getById($idCitaOrigen);
        if (empty($origen)) {
            return ['ok' => false, 'message' => 'No se encontró la cita de origen.'];
        }

        $fechaPref = trim((string)($datos['fecha_preferida'] ?? ''));
        $horaPref = trim((string)($datos['hora_preferida'] ?? ''));
        $tipo = trim((string)($datos['tipo_cita'] ?? ''));
        $necesidad = trim((string)($datos['necesidad_principal'] ?? ''));
        $identidad = $this->resolverIdentidadPaciente($origen, true);
        $documento = $identidad['documento'];
        $tipoDocumento = $identidad['ok'] ? $identidad['tipo'] : trim((string)($origen['Tipo_Documento'] ?? ''));

        $validacion = $this->validarAgendamientoInterno($fechaPref, $horaPref, $documento);
        if (!$validacion['ok']) {
            return ['ok' => false, 'message' => (string)($validacion['error'] ?? 'No se pudo agendar.')];
        }
        if ($tipo === '' || !isset(self::TIPOS_CITA[$tipo])) {
            return ['ok' => false, 'message' => 'Selecciona el tipo de cita.'];
        }
        if ($necesidad === '') {
            $necesidad = 'Seguimiento';
        }

        $id = $this->create([
            'Nombre' => $origen['Nombre'] ?? '',
            'Apellido' => $origen['Apellido'] ?? '',
            'Tipo_Documento' => $tipoDocumento !== '' ? $tipoDocumento : null,
            'Documento' => $documento,
            'Nombre_Eps' => $origen['Nombre_Eps'] ?? null,
            'Telefono' => $origen['Telefono'] ?? '',
            'Email' => $origen['Email'] ?? null,
            'Fecha_Preferida' => $fechaPref,
            'Hora_Preferida' => self::normalizarHora($horaPref),
            'Tipo_Cita' => $tipo,
            'Necesidad_Principal' => $necesidad,
            'Remitido_Por' => $origen['Remitido_Por'] ?? 'ninguno',
            'Remitido_Detalle' => $origen['Remitido_Detalle'] ?? null,
            'Observaciones' => null,
            'Estado' => 'confirmada',
        ]);

        if (!$id) {
            return ['ok' => false, 'message' => 'No se pudo guardar la nueva cita.'];
        }

        return ['ok' => true, 'message' => 'Cita nueva agendada.', 'id' => (int)$id];
    }

    /**
     * @param mixed $filesInput
     * @return array{ok:bool,errors:array<int,string>}
     */
    public function guardarDocumentosRemision(int $idCita, $filesInput): array {
        $idCita = (int)$idCita;
        $cita = $this->getById($idCita);
        if (empty($cita)) {
            return ['ok' => false, 'errors' => ['La cita no existe.']];
        }

        $erroresValidacion = ServicioSocialDocumentos::validarUpload($filesInput);
        if ($erroresValidacion !== []) {
            return ['ok' => false, 'errors' => $erroresValidacion];
        }

        if (!ServicioSocialDocumentos::tieneArchivosEnUpload($filesInput)) {
            return ['ok' => true, 'errors' => []];
        }

        $existentes = ServicioSocialDocumentos::decodificar($cita['Documentos_Remision'] ?? null, $idCita);
        $helper = new ServicioSocialDocumentos();
        $resultado = $helper->adjuntarDesdeUpload($idCita, $filesInput, $existentes);

        if ($resultado['documentos'] !== []) {
            $this->update($idCita, [
                'Documentos_Remision' => json_encode($resultado['documentos'], JSON_UNESCAPED_UNICODE),
            ]);
        }

        $errores = $resultado['errores'];
        return $errores === []
            ? ['ok' => true, 'errors' => []]
            : ['ok' => false, 'errors' => $errores];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listarHistoriaPaciente(string $tipoDocumento, string $documento): array {
        $docNorm = self::normalizarDocumento($documento);
        if ($docNorm === '') {
            return [];
        }

        $exprDoc = self::sqlExprDocumentoNorm('Documento');
        return $this->query(
            "SELECT * FROM {$this->tablaHistoria}
             WHERE {$exprDoc} = ?
             ORDER BY Fecha_Atencion DESC, Id_Entrada DESC",
            [$docNorm]
        );
    }

    /**
     * @param array<string, mixed> $datos
     * @return array{ok:bool,message:string,id?:int}
     */
    public function crearEntradaHistoria(int $idCita, array $datos, int $idUsuario = 0): array {
        $cita = $this->getById($idCita);
        if (empty($cita)) {
            return ['ok' => false, 'message' => 'La cita no existe.'];
        }

        $identidad = $this->resolverIdentidadPaciente($cita, true);
        if (!$identidad['ok']) {
            return ['ok' => false, 'message' => $identidad['message']];
        }
        $tipoDocumento = $identidad['tipo'];
        $documento = $identidad['documento'];

        $motivo = trim((string)($datos['motivo_consulta'] ?? ''));
        $diagnostico = trim((string)($datos['diagnostico'] ?? ''));
        $formula = trim((string)($datos['formula'] ?? ''));
        $recomendaciones = trim((string)($datos['recomendaciones'] ?? ''));
        $observaciones = trim((string)($datos['observaciones'] ?? ''));

        if ($motivo === '' && $diagnostico === '' && $formula === '' && $recomendaciones === '' && $observaciones === '') {
            return ['ok' => false, 'message' => 'Registra al menos motivo, diagnóstico, plan o recomendaciones.'];
        }

        $fechaSql = self::fechaHoraDesdeAgenda(
            (string)($cita['Fecha_Preferida'] ?? ''),
            (string)($cita['Hora_Preferida'] ?? '')
        );

        $id = $this->createHistoria([
            'Tipo_Documento' => $tipoDocumento,
            'Documento' => $documento,
            'Id_Cita' => $idCita,
            'Fecha_Atencion' => $fechaSql,
            'Motivo_Consulta' => $motivo !== '' ? $motivo : null,
            'Diagnostico' => $diagnostico !== '' ? $diagnostico : null,
            'Formula' => $formula !== '' ? $formula : null,
            'Recomendaciones' => $recomendaciones !== '' ? $recomendaciones : null,
            'Observaciones' => $observaciones !== '' ? $observaciones : null,
            'Creado_Por' => $idUsuario > 0 ? $idUsuario : null,
        ]);

        if (!$id) {
            return ['ok' => false, 'message' => 'No se pudo guardar la entrada de historia clínica.'];
        }

        return ['ok' => true, 'message' => 'Entrada de historia clínica guardada.', 'id' => (int)$id];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createHistoria(array $data) {
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = "INSERT INTO {$this->tablaHistoria} (" . implode(', ', $fields) . ")
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->db->prepare($sql);
        if (!$stmt || !$stmt->execute($values)) {
            return false;
        }

        return (int)$this->db->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listarCitasPaciente(string $tipoDocumento, string $documento, int $excluirId = 0): array {
        $docNorm = self::normalizarDocumento($documento);
        if ($docNorm === '') {
            return [];
        }

        $exprDoc = self::sqlExprDocumentoNorm('Documento');
        $sql = "SELECT * FROM {$this->table} WHERE {$exprDoc} = ?";
        $params = [$docNorm];
        if ($excluirId > 0) {
            $sql .= ' AND Id_Cita <> ?';
            $params[] = $excluirId;
        }
        $sql .= ' ORDER BY Fecha_Creacion DESC';

        return $this->query($sql, $params);
    }

    /**
     * @param array<string, mixed> $datos
     * @return array{ok:bool,message:string}
     */
    public function actualizarGestion(int $idCita, array $datos, int $idUsuario = 0): array {
        $idCita = (int)$idCita;
        $citaActual = $this->getById($idCita);
        if ($idCita <= 0 || empty($citaActual)) {
            return ['ok' => false, 'message' => 'La cita no existe.'];
        }

        $estado = self::normalizarEstado((string)($datos['estado'] ?? ''));
        if ($estado === '' || !isset(self::ESTADOS[$estado])) {
            return ['ok' => false, 'message' => 'Estado no válido.'];
        }

        $notas = trim((string)($datos['notas_internas'] ?? ''));
        $fechaPref = trim((string)($datos['fecha_preferida'] ?? ''));
        $horaPref = trim((string)($datos['hora_preferida'] ?? ''));

        $fechaActual = (string)($citaActual['Fecha_Preferida'] ?? '');
        $horaActual = self::normalizarHora((string)($citaActual['Hora_Preferida'] ?? ''));

        $update = [
            'Estado' => $estado,
            'Notas_Internas' => $notas !== '' ? $notas : null,
        ];

        if ($idUsuario > 0) {
            $update['Actualizado_Por'] = $idUsuario;
        }

        $avisos = [];
        $cambioFecha = false;
        if ($fechaPref !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPref)) {
            if (!self::esSabado($fechaPref)) {
                $avisos[] = 'La fecha no se cambió porque no es sábado.';
            } elseif ($fechaPref !== $fechaActual) {
                $update['Fecha_Preferida'] = $fechaPref;
                $cambioFecha = true;
            }
        }

        $horaNorm = $horaPref !== '' ? self::normalizarHora($horaPref) : '';
        $fechaValidar = $update['Fecha_Preferida'] ?? $fechaActual;
        if ($horaNorm !== '' && ($cambioFecha || $horaNorm !== $horaActual)) {
            if (!isset(self::catalogoHorariosSabado()[$horaNorm])) {
                $avisos[] = 'La hora no se cambió porque no es válida.';
            } else {
                $catalogo = $this->horasDisponiblesCatalogo($fechaValidar);
                $ocupadas = $this->horasOcupadas($fechaValidar, $idCita);
                if (!in_array($horaNorm, $catalogo, true) || in_array($horaNorm, $ocupadas, true)) {
                    $avisos[] = 'La hora no se cambió porque ese cupo no está disponible.';
                } else {
                    $update['Hora_Preferida'] = $horaNorm;
                }
            }
        }

        $fechaFinal = (string)($update['Fecha_Preferida'] ?? $fechaActual);
        $horaFinal = (string)($update['Hora_Preferida'] ?? $horaActual);
        if ($estado === 'atendida') {
            $update['Fecha_Atencion'] = self::fechaHoraDesdeAgenda($fechaFinal, $horaFinal);
        }

        try {
            $ok = $this->update($idCita, $update);
        } catch (Throwable $e) {
            error_log('Servicio Social actualizarGestion: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'Error al guardar: ' . $e->getMessage()];
        }

        if (!$ok) {
            return ['ok' => false, 'message' => 'No se pudo guardar los cambios.'];
        }

        $verificacion = $this->getById($idCita);
        $estadoGuardado = self::normalizarEstado((string)($verificacion['Estado'] ?? $verificacion['estado'] ?? ''));
        if (empty($verificacion) || $estadoGuardado !== $estado) {
            return ['ok' => false, 'message' => 'No se confirmó el cambio de estado. Intenta de nuevo.'];
        }

        $mensaje = 'Estado actualizado a «' . self::etiquetaEstado($estado) . '».';
        if ($avisos !== []) {
            $mensaje .= ' ' . implode(' ', $avisos);
        }

        return ['ok' => true, 'message' => $mensaje];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public function eliminarCita(int $idCita): array {
        $idCita = (int)$idCita;
        $cita = $idCita > 0 ? $this->getById($idCita) : null;
        if (empty($cita)) {
            return ['ok' => false, 'message' => 'La cita no existe o ya fue eliminada.'];
        }

        try {
            $ok = $this->delete($idCita);
        } catch (Throwable $e) {
            error_log('Servicio Social eliminarCita: ' . $e->getMessage());
            return ['ok' => false, 'message' => 'No se pudo eliminar la cita.'];
        }

        if (!$ok) {
            return ['ok' => false, 'message' => 'No se pudo eliminar la cita.'];
        }

        $dir = ServicioSocialDocumentos::directorioCita($idCita);
        if (is_dir($dir)) {
            $archivos = scandir($dir);
            if (is_array($archivos)) {
                foreach ($archivos as $archivo) {
                    if ($archivo === '.' || $archivo === '..') {
                        continue;
                    }
                    $ruta = $dir . DIRECTORY_SEPARATOR . $archivo;
                    if (is_file($ruta)) {
                        @unlink($ruta);
                    }
                }
            }
            @rmdir($dir);
        }

        $nombre = trim((string)($cita['Nombre'] ?? '') . ' ' . (string)($cita['Apellido'] ?? ''));
        $etiqueta = $nombre !== '' ? $nombre : ('#' . $idCita);

        return ['ok' => true, 'message' => 'Se eliminó la cita de ' . $etiqueta . '.'];
    }
}
