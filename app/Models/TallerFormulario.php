<?php
/**
 * Formularios dinámicos del módulo Talleres.
 */

require_once APP . '/Models/BaseModel.php';

class TallerFormulario extends BaseModel {
    protected $table = 'talleres_formulario';
    protected $primaryKey = 'Id_Formulario';

    /** @var array<int, string> */
    public const TIPOS_CAMPO_PERMITIDOS = [
        'text',
        'textarea',
        'email',
        'tel',
        'number',
        'date',
        'select',
        'radio',
        'checkbox',
        'tabla',
    ];

    /** @var array<int, string> */
    public const CAMPOS_PERSONA_FIJOS = [
        'persona_nombre' => 'Nombre completo',
        'persona_documento' => 'Documento de identidad',
        'persona_fecha_nacimiento' => 'Fecha de nacimiento',
        'persona_edad' => 'Edad',
        'persona_telefono' => 'Teléfono de contacto',
        'persona_email' => 'Correo electrónico',
        'persona_direccion' => 'Dirección de residencia',
        'persona_estado_civil' => 'Estado civil',
        'persona_ocupacion' => 'Ocupación',
    ];

    /** @var array<int, string> */
    public const ESTADOS_CIVILES = [
        'Soltero(a)',
        'Casado(a)',
        'Unión libre',
        'Separado(a)',
        'Viudo(a)',
    ];

    /** @var array<string, string> */
    public const CAMPOS_AUTORIZACION_FIJOS = [
        'autorizacion_acepto' => 'Autorización aceptada',
        'autorizacion_firma' => 'Firma',
        'autorizacion_fecha' => 'Fecha de firma',
    ];

    public const TIPO_PLANTILLA_GENERAL = 'general';
    public const TIPO_PLANTILLA_PRESENTACION_NINOS = 'presentacion_ninos';
    public const TIPO_PLANTILLA_TOUR_LEVANTATE = 'tour_levantate';

    public function __construct() {
        parent::__construct();
        $this->ensureSchema();
    }

    private function ensureSchema(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS talleres_formulario (
                Id_Formulario INT UNSIGNED NOT NULL AUTO_INCREMENT,
                Titulo VARCHAR(255) NOT NULL,
                Slug VARCHAR(120) NOT NULL,
                Descripcion TEXT NULL,
                Activo TINYINT(1) NOT NULL DEFAULT 1,
                Mensaje_Gracias TEXT NULL,
                Fecha_Creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                Fecha_Actualizacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                Creado_Por INT UNSIGNED NULL,
                PRIMARY KEY (Id_Formulario),
                UNIQUE KEY uk_talleres_formulario_slug (Slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS talleres_formulario_campo (
                Id_Campo INT UNSIGNED NOT NULL AUTO_INCREMENT,
                Id_Formulario INT UNSIGNED NOT NULL,
                Nombre_Campo VARCHAR(100) NOT NULL,
                Etiqueta VARCHAR(255) NOT NULL,
                Tipo VARCHAR(40) NOT NULL DEFAULT 'text',
                Requerido TINYINT(1) NOT NULL DEFAULT 0,
                Orden INT NOT NULL DEFAULT 0,
                Opciones TEXT NULL,
                Placeholder VARCHAR(255) NULL,
                Ayuda VARCHAR(500) NULL,
                PRIMARY KEY (Id_Campo),
                KEY idx_talleres_campo_formulario (Id_Formulario)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS talleres_formulario_respuesta (
                Id_Respuesta INT UNSIGNED NOT NULL AUTO_INCREMENT,
                Id_Formulario INT UNSIGNED NOT NULL,
                Id_Persona INT UNSIGNED NULL,
                Datos_JSON LONGTEXT NOT NULL,
                Ip_Origen VARCHAR(45) NULL,
                Fecha_Registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (Id_Respuesta),
                KEY idx_talleres_respuesta_formulario (Id_Formulario),
                KEY idx_talleres_respuesta_persona (Id_Persona),
                KEY idx_talleres_respuesta_fecha (Fecha_Registro)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS talleres_formulario_bloque (
                Id_Bloque INT UNSIGNED NOT NULL AUTO_INCREMENT,
                Id_Formulario INT UNSIGNED NOT NULL,
                Titulo VARCHAR(255) NOT NULL,
                Tipo ENUM('persona', 'personalizado') NOT NULL DEFAULT 'personalizado',
                Orden INT NOT NULL DEFAULT 0,
                PRIMARY KEY (Id_Bloque),
                KEY idx_talleres_bloque_formulario (Id_Formulario)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->ensureColumn('talleres_formulario_campo', 'Id_Bloque', 'INT UNSIGNED NULL AFTER Id_Formulario');
        $this->ensureColumn('talleres_formulario_respuesta', 'Id_Persona', 'INT UNSIGNED NULL AFTER Id_Formulario');
        $this->ensureColumn('talleres_formulario', 'Texto_Autorizacion', 'TEXT NULL AFTER Mensaje_Gracias');
        $this->ensureColumn('talleres_formulario', 'Tipo_Plantilla', "VARCHAR(40) NOT NULL DEFAULT 'general' AFTER Texto_Autorizacion");
        $this->ensureColumn('talleres_formulario', 'Imagen_Header', 'VARCHAR(255) NULL AFTER Descripcion');
        $this->ensureTipoBloqueAutorizacion();
        $this->ensureTablaPagos();
    }

    private function ensureTipoBloqueAutorizacion(): void {
        try {
            $this->db->exec("
                ALTER TABLE talleres_formulario_bloque
                MODIFY Tipo ENUM('persona', 'personalizado', 'autorizacion', 'padres', 'nino', 'documentos') NOT NULL DEFAULT 'personalizado'
            ");
        } catch (Throwable $e) {
            // Tabla nueva o ENUM ya actualizado.
        }
    }

    private function ensureTablaPagos(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS talleres_formulario_pago (
                Id_Pago INT UNSIGNED NOT NULL AUTO_INCREMENT,
                Id_Respuesta INT UNSIGNED NOT NULL,
                Id_Formulario INT UNSIGNED NOT NULL,
                Metodo_Pago VARCHAR(60) NOT NULL,
                Recibido_Por VARCHAR(160) NULL,
                Tipo_Pago VARCHAR(20) NOT NULL DEFAULT 'completo',
                Valor_Pago DECIMAL(12,2) NOT NULL DEFAULT 0,
                Referencia_Pago VARCHAR(120) NULL,
                Fecha_Registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (Id_Pago),
                KEY idx_taller_pago_respuesta (Id_Respuesta),
                KEY idx_taller_pago_formulario (Id_Formulario)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function esPresentacionNinos(?array $formulario): bool {
        if (!is_array($formulario)) {
            return false;
        }
        if (trim((string)($formulario['Tipo_Plantilla'] ?? '')) === self::TIPO_PLANTILLA_PRESENTACION_NINOS) {
            return true;
        }
        $slug = strtolower(trim((string)($formulario['Slug'] ?? '')));
        if ($slug === 'presentacion-ninos' || str_starts_with($slug, 'presentacion-ninos-')) {
            return true;
        }
        $titulo = strtolower(trim((string)($formulario['Titulo'] ?? '')));
        return str_contains($titulo, 'presentación de niños') || str_contains($titulo, 'presentacion de ninos');
    }

    public function esTourLevantate(?array $formulario): bool {
        if (!is_array($formulario)) {
            return false;
        }
        if (trim((string)($formulario['Tipo_Plantilla'] ?? '')) === self::TIPO_PLANTILLA_TOUR_LEVANTATE) {
            return true;
        }
        $slug = strtolower(trim((string)($formulario['Slug'] ?? '')));
        if ($slug === 'tour-levantate-y-resplandece' || str_starts_with($slug, 'tour-levantate')) {
            return true;
        }
        $titulo = strtolower(trim((string)($formulario['Titulo'] ?? '')));
        return str_contains($titulo, 'levantate y resplandece') || str_contains($titulo, 'levántate y resplandece');
    }

    public function asegurarTipoPlantillaPresentacionNinos(int $idFormulario): void {
        if ($idFormulario <= 0) {
            return;
        }
        $formulario = $this->getById($idFormulario);
        if (!$formulario || !$this->esPresentacionNinos($formulario)) {
            return;
        }
        if (trim((string)($formulario['Tipo_Plantilla'] ?? '')) === self::TIPO_PLANTILLA_PRESENTACION_NINOS) {
            return;
        }
        $this->update($idFormulario, [
            'Tipo_Plantilla' => self::TIPO_PLANTILLA_PRESENTACION_NINOS,
        ]);
    }

    private function ensureColumn(string $table, string $column, string $definition): void {
        try {
            $rows = $this->query("SHOW COLUMNS FROM {$table} LIKE ?", [$column]);
            if (empty($rows)) {
                $this->db->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            }
        } catch (Throwable $e) {
            // Ignorar si la columna ya existe o el motor no permite la operación.
        }
    }

    public function getAllConConteo(): array {
        $sql = "
            SELECT f.*,
                   (SELECT COUNT(*) FROM talleres_formulario_campo c WHERE c.Id_Formulario = f.Id_Formulario) AS Total_Campos,
                   (SELECT COUNT(*) FROM talleres_formulario_respuesta r WHERE r.Id_Formulario = f.Id_Formulario) AS Total_Respuestas
            FROM talleres_formulario f
            ORDER BY f.Fecha_Creacion DESC
        ";
        return (array)$this->query($sql);
    }

    public function getBySlug(string $slug): ?array {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }
        $rows = $this->query(
            'SELECT * FROM talleres_formulario WHERE Slug = ? LIMIT 1',
            [$slug]
        );
        return !empty($rows[0]) ? (array)$rows[0] : null;
    }

    public function slugExiste(string $slug, int $excluirId = 0): bool {
        $slug = trim($slug);
        if ($slug === '') {
            return false;
        }
        $sql = 'SELECT Id_Formulario FROM talleres_formulario WHERE Slug = ?';
        $params = [$slug];
        if ($excluirId > 0) {
            $sql .= ' AND Id_Formulario <> ?';
            $params[] = $excluirId;
        }
        $sql .= ' LIMIT 1';
        $rows = $this->query($sql, $params);
        return !empty($rows);
    }

    public function generarSlugUnico(string $titulo, int $excluirId = 0): string {
        $base = $this->normalizarSlug($titulo);
        if ($base === '') {
            $base = 'formulario';
        }
        $slug = $base;
        $i = 2;
        while ($this->slugExiste($slug, $excluirId)) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    public function normalizarSlug(string $texto): string {
        $texto = strtolower(trim($texto));
        $map = [
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n',
        ];
        $texto = strtr($texto, $map);
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
        $texto = trim((string)$texto, '-');
        return substr($texto, 0, 100);
    }

    public function normalizarNombreCampo(string $etiqueta, string $fallback = 'campo'): string {
        $nombre = $this->normalizarSlug($etiqueta);
        if ($nombre === '') {
            $nombre = $fallback;
        }
        return substr($nombre, 0, 80);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCamposPorFormulario(int $idFormulario): array {
        if ($idFormulario <= 0) {
            return [];
        }
        return (array)$this->query(
            'SELECT * FROM talleres_formulario_campo WHERE Id_Formulario = ? ORDER BY Orden ASC, Id_Campo ASC',
            [$idFormulario]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getBloquesPorFormulario(int $idFormulario): array {
        if ($idFormulario <= 0) {
            return [];
        }
        return (array)$this->query(
            'SELECT * FROM talleres_formulario_bloque WHERE Id_Formulario = ? ORDER BY Orden ASC, Id_Bloque ASC',
            [$idFormulario]
        );
    }

    /**
     * @return array{bloques: array<int, array<string, mixed>>, campos_persona: array<string, string>}
     */
    public function getFormularioCompleto(int $idFormulario): array {
        if ($idFormulario <= 0) {
            return ['bloques' => [], 'campos_persona' => self::CAMPOS_PERSONA_FIJOS];
        }

        $formulario = $this->getById($idFormulario) ?: [];
        $esPresentacionNinos = $this->esPresentacionNinos($formulario);

        $bloques = $this->getBloquesPorFormulario($idFormulario);
        if (empty($bloques)) {
            if ($esPresentacionNinos) {
                $this->reemplazarBloquesPresentacionNinos($idFormulario);
            } else {
                $this->migrarFormularioLegacy($idFormulario);
            }
            $bloques = $this->getBloquesPorFormulario($idFormulario);
        }

        if ($esPresentacionNinos) {
            $this->ensureBloquesPresentacionNinos($idFormulario, $bloques);
        } else {
            $this->ensureBloquePersona($idFormulario, $bloques);
        }
        $bloques = $this->getBloquesPorFormulario($idFormulario);
        $this->ensureBloqueAutorizacion($idFormulario, $bloques);
        $bloques = $this->getBloquesPorFormulario($idFormulario);
        $camposRaw = $this->getCamposPorFormulario($idFormulario);
        $camposPorBloque = [];
        foreach ($camposRaw as $campo) {
            $idBloque = (int)($campo['Id_Bloque'] ?? 0);
            if ($idBloque <= 0) {
                continue;
            }
            if (!isset($camposPorBloque[$idBloque])) {
                $camposPorBloque[$idBloque] = [];
            }
            $camposPorBloque[$idBloque][] = $campo;
        }

        $resultado = [];
        foreach ($bloques as $bloque) {
            $idBloque = (int)($bloque['Id_Bloque'] ?? 0);
            $tipo = (string)($bloque['Tipo'] ?? 'personalizado');
            $resultado[] = [
                'bloque' => $bloque,
                'campos' => in_array($tipo, ['persona', 'autorizacion', 'padres', 'nino', 'documentos'], true) ? [] : ($camposPorBloque[$idBloque] ?? []),
            ];
        }

        require_once APP . '/Helpers/TallerAutorizacionSync.php';
        require_once APP . '/Helpers/TallerPresentacionNinosSync.php';

        return [
            'bloques' => $resultado,
            'campos_persona' => self::CAMPOS_PERSONA_FIJOS,
            'campos_padres' => TallerPresentacionNinosSync::camposPadres(),
            'campos_nino' => TallerPresentacionNinosSync::camposNino(),
            'campos_autorizacion' => self::CAMPOS_AUTORIZACION_FIJOS,
            'texto_autorizacion' => TallerAutorizacionSync::textoParaFormulario($formulario),
            'tipo_plantilla' => (string)($formulario['Tipo_Plantilla'] ?? self::TIPO_PLANTILLA_GENERAL),
        ];
    }

    private function migrarFormularioLegacy(int $idFormulario): void {
        $campos = $this->getCamposPorFormulario($idFormulario);
        $idPersona = $this->insertEnTabla('talleres_formulario_bloque', [
            'Id_Formulario' => $idFormulario,
            'Titulo' => '1. DATOS PERSONALES',
            'Tipo' => 'persona',
            'Orden' => 0,
        ]);
        $idCustom = $this->insertEnTabla('talleres_formulario_bloque', [
            'Id_Formulario' => $idFormulario,
            'Titulo' => 'Preguntas del formulario',
            'Tipo' => 'personalizado',
            'Orden' => 1,
        ]);
        foreach ($campos as $campo) {
            $idCampo = (int)($campo['Id_Campo'] ?? 0);
            if ($idCampo <= 0) {
                continue;
            }
            $this->execute(
                'UPDATE talleres_formulario_campo SET Id_Bloque = ? WHERE Id_Campo = ?',
                [$idCustom, $idCampo]
            );
        }
        unset($idPersona);
        $this->insertEnTabla('talleres_formulario_bloque', [
            'Id_Formulario' => $idFormulario,
            'Titulo' => 'Autorización',
            'Tipo' => 'autorizacion',
            'Orden' => 2,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $bloquesExistentes
     */
    private function ensureBloqueAutorizacion(int $idFormulario, array $bloquesExistentes): void {
        $idAuth = 0;
        $maxOrden = 0;
        foreach ($bloquesExistentes as $bloque) {
            $orden = (int)($bloque['Orden'] ?? 0);
            if (($bloque['Tipo'] ?? '') === 'autorizacion') {
                $idAuth = (int)($bloque['Id_Bloque'] ?? 0);
            } else {
                $maxOrden = max($maxOrden, $orden);
            }
        }

        if ($idAuth <= 0) {
            $this->insertEnTabla('talleres_formulario_bloque', [
                'Id_Formulario' => $idFormulario,
                'Titulo' => 'Autorización',
                'Tipo' => 'autorizacion',
                'Orden' => $maxOrden + 1,
            ]);
            return;
        }

        if ($maxOrden > 0) {
            $this->execute(
                'UPDATE talleres_formulario_bloque SET Orden = ? WHERE Id_Bloque = ?',
                [$maxOrden + 1, $idAuth]
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $bloquesExistentes
     */
    private function ensureBloquePersona(int $idFormulario, array $bloquesExistentes): void {
        foreach ($bloquesExistentes as $bloque) {
            if (($bloque['Tipo'] ?? '') === 'persona') {
                return;
            }
        }
        $this->insertEnTabla('talleres_formulario_bloque', [
            'Id_Formulario' => $idFormulario,
            'Titulo' => '1. DATOS PERSONALES',
            'Tipo' => 'persona',
            'Orden' => 0,
        ]);
        $personalizados = array_values(array_filter($bloquesExistentes, static function ($b) {
            return ($b['Tipo'] ?? '') !== 'persona';
        }));
        foreach ($personalizados as $i => $bloque) {
            $id = (int)($bloque['Id_Bloque'] ?? 0);
            if ($id > 0) {
                $this->execute(
                    'UPDATE talleres_formulario_bloque SET Orden = ? WHERE Id_Bloque = ?',
                    [$i + 1, $id]
                );
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $bloquesExistentes
     */
    private function ensureBloquesPresentacionNinos(int $idFormulario, array $bloquesExistentes): void {
        $tienePadres = false;
        $tieneNino = false;
        $tieneDocumentos = false;
        foreach ($bloquesExistentes as $bloque) {
            $tipo = (string)($bloque['Tipo'] ?? '');
            if ($tipo === 'padres') {
                $tienePadres = true;
            }
            if ($tipo === 'nino') {
                $tieneNino = true;
            }
            if ($tipo === 'documentos') {
                $tieneDocumentos = true;
            }
        }
        if (!$tienePadres || !$tieneNino) {
            $this->reemplazarBloquesPresentacionNinos($idFormulario);
            return;
        }
        if (!$tieneDocumentos) {
            $this->insertarBloqueDocumentosPresentacion($idFormulario, $bloquesExistentes);
        }
    }

    private function insertarBloqueDocumentosPresentacion(int $idFormulario, array $bloquesExistentes): void {
        $ordenDocumentos = 2;
        $idAutorizacion = 0;
        foreach ($bloquesExistentes as $bloque) {
            $tipo = (string)($bloque['Tipo'] ?? '');
            if ($tipo === 'autorizacion') {
                $idAutorizacion = (int)($bloque['Id_Bloque'] ?? 0);
                $ordenDocumentos = min($ordenDocumentos, (int)($bloque['Orden'] ?? 2));
            }
        }
        if ($idAutorizacion > 0) {
            $this->execute(
                'UPDATE talleres_formulario_bloque SET Orden = Orden + 1 WHERE Id_Formulario = ? AND Orden >= ?',
                [$idFormulario, $ordenDocumentos]
            );
        }
        $this->insertEnTabla('talleres_formulario_bloque', [
            'Id_Formulario' => $idFormulario,
            'Titulo' => '3. Documentos',
            'Tipo' => 'documentos',
            'Orden' => $ordenDocumentos,
        ]);
    }

    public function reemplazarBloquesPresentacionNinos(int $idFormulario): void {
        if ($idFormulario <= 0) {
            return;
        }

        $this->execute('DELETE FROM talleres_formulario_campo WHERE Id_Formulario = ?', [$idFormulario]);
        $this->execute('DELETE FROM talleres_formulario_bloque WHERE Id_Formulario = ?', [$idFormulario]);

        $this->insertEnTabla('talleres_formulario_bloque', [
            'Id_Formulario' => $idFormulario,
            'Titulo' => '1. Datos de los padres o acudientes',
            'Tipo' => 'padres',
            'Orden' => 0,
        ]);
        $this->insertEnTabla('talleres_formulario_bloque', [
            'Id_Formulario' => $idFormulario,
            'Titulo' => '2. Datos del niño(a)',
            'Tipo' => 'nino',
            'Orden' => 1,
        ]);
        $this->insertEnTabla('talleres_formulario_bloque', [
            'Id_Formulario' => $idFormulario,
            'Titulo' => '3. Documentos',
            'Tipo' => 'documentos',
            'Orden' => 2,
        ]);
        $this->insertEnTabla('talleres_formulario_bloque', [
            'Id_Formulario' => $idFormulario,
            'Titulo' => '4. Consentimiento',
            'Tipo' => 'autorizacion',
            'Orden' => 3,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public static function getConfigPlantillaPresentacionNinos(): array {
        return [
            'titulo' => 'Presentación de niños',
            'slug' => 'presentacion-ninos',
            'descripcion' => 'Formulario de inscripción para la presentación de niños. Los datos del acudiente deben estar registrados en el sistema.',
            'mensaje_gracias' => '¡Gracias! Hemos recibido la inscripción para la presentación de su niño(a).',
            'texto_autorizacion' => 'Autorizo el tratamiento de datos personales e imágenes del niño(a) registrado en este formulario, '
                . 'exclusivamente para fines pastorales, de seguimiento y difusión interna del ministerio infantil y de la iglesia, '
                . 'de conformidad con la política de protección de datos de MCI Madrid Colombia.',
        ];
    }

    public function crearFormularioPresentacionNinos(int $creadoPor = 0): int {
        $config = self::getConfigPlantillaPresentacionNinos();
        $slug = $config['slug'];
        if ($this->slugExiste($slug)) {
            $slug = $this->generarSlugUnico($slug);
        }

        $id = $this->insertEnTabla('talleres_formulario', [
            'Titulo' => $config['titulo'],
            'Slug' => $slug,
            'Descripcion' => $config['descripcion'],
            'Activo' => 1,
            'Mensaje_Gracias' => $config['mensaje_gracias'],
            'Texto_Autorizacion' => $config['texto_autorizacion'],
            'Tipo_Plantilla' => self::TIPO_PLANTILLA_PRESENTACION_NINOS,
            'Creado_Por' => $creadoPor > 0 ? $creadoPor : null,
        ]);

        $this->reemplazarBloquesPresentacionNinos($id);
        return $id;
    }

    /**
     * @return array<string, string>
     */
    public static function getConfigPlantillaTourLevantate(): array {
        return [
            'titulo' => 'Tour Levántate y Resplandece',
            'slug' => 'tour-levantate-y-resplandece',
            'descripcion' => 'Inscripción al Tour Levántate y Resplandece con la Pastora Johanna Castellanos.',
            'mensaje_gracias' => '¡Gracias! Hemos recibido su inscripción al Tour Levántate y Resplandece.',
            'texto_autorizacion' => 'Autorizo el tratamiento de mis datos personales para fines relacionados con la organización '
                . 'del Tour Levántate y Resplandece, de conformidad con la política de protección de datos de MCI Madrid.',
            'imagen_header' => 'assets/img/talleres/tour-levantate-resplandece-header.png',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getPlantillaTourLevantate(): array {
        return [
            [
                'titulo' => '2. LIBRO Y REUNIÓN',
                'campos' => [
                    [
                        'nombre' => 'ya_tiene_el_libro',
                        'etiqueta' => '¿Ya tiene el libro «Levántate y Resplandece»?',
                        'tipo' => 'radio',
                        'requerido' => true,
                        'opciones' => ['Sí, ya tengo el libro', 'No, aún no tengo el libro'],
                        'ayuda' => 'Indique si ya cuenta con el libro del tour.',
                    ],
                    [
                        'nombre' => 'reserva_cupo_reunion',
                        'etiqueta' => 'Reserva de cupo para la reunión',
                        'tipo' => 'radio',
                        'requerido' => true,
                        'opciones' => ['Sí, confirmo mi cupo en la reunión', 'No podré asistir'],
                    ],
                    [
                        'nombre' => 'desea_comprar_libro',
                        'etiqueta' => '¿Desea comprar el libro?',
                        'tipo' => 'radio',
                        'requerido' => false,
                        'opciones' => ['Sí, deseo comprar el libro', 'No, gracias'],
                        'ayuda' => 'Complete esta opción si aún no tiene el libro.',
                    ],
                ],
            ],
        ];
    }

    public function reemplazarBloquesTourLevantate(int $idFormulario): void {
        if ($idFormulario <= 0) {
            return;
        }
        $this->reemplazarBloquesYCampos($idFormulario, self::getPlantillaTourLevantate(), false);
    }

    public function crearFormularioTourLevantate(int $creadoPor = 0): int {
        $config = self::getConfigPlantillaTourLevantate();
        $slug = $config['slug'];
        if ($this->slugExiste($slug)) {
            $slug = $this->generarSlugUnico($slug);
        }

        $id = $this->insertEnTabla('talleres_formulario', [
            'Titulo' => $config['titulo'],
            'Slug' => $slug,
            'Descripcion' => $config['descripcion'],
            'Activo' => 1,
            'Mensaje_Gracias' => $config['mensaje_gracias'],
            'Texto_Autorizacion' => $config['texto_autorizacion'],
            'Tipo_Plantilla' => self::TIPO_PLANTILLA_TOUR_LEVANTATE,
            'Imagen_Header' => $config['imagen_header'],
            'Creado_Por' => $creadoPor > 0 ? $creadoPor : null,
        ]);

        $this->reemplazarBloquesTourLevantate($id);
        return $id;
    }

    public function aplicarPlantillaPresentacionNinos(int $idFormulario): bool {
        if ($idFormulario <= 0) {
            return false;
        }
        $config = self::getConfigPlantillaPresentacionNinos();
        $this->update($idFormulario, [
            'Titulo' => $config['titulo'],
            'Descripcion' => $config['descripcion'],
            'Mensaje_Gracias' => $config['mensaje_gracias'],
            'Texto_Autorizacion' => $config['texto_autorizacion'],
            'Tipo_Plantilla' => self::TIPO_PLANTILLA_PRESENTACION_NINOS,
        ]);
        $this->reemplazarBloquesPresentacionNinos($idFormulario);
        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $bloquesPersonalizados
     */
    public function reemplazarBloquesYCampos(int $idFormulario, array $bloquesPersonalizados, bool $incluirAutorizacion = true): void {
        if ($idFormulario <= 0) {
            return;
        }

        $this->execute('DELETE FROM talleres_formulario_campo WHERE Id_Formulario = ?', [$idFormulario]);
        $this->execute('DELETE FROM talleres_formulario_bloque WHERE Id_Formulario = ?', [$idFormulario]);

        $this->insertEnTabla('talleres_formulario_bloque', [
            'Id_Formulario' => $idFormulario,
            'Titulo' => '1. DATOS PERSONALES',
            'Tipo' => 'persona',
            'Orden' => 0,
        ]);

        $ordenBloque = 1;
        foreach ($bloquesPersonalizados as $bloque) {
            $titulo = trim((string)($bloque['titulo'] ?? ''));
            if ($titulo === '') {
                $titulo = 'Bloque ' . $ordenBloque;
            }
            $idBloque = $this->insertEnTabla('talleres_formulario_bloque', [
                'Id_Formulario' => $idFormulario,
                'Titulo' => $titulo,
                'Tipo' => 'personalizado',
                'Orden' => $ordenBloque,
            ]);

            $campos = is_array($bloque['campos'] ?? null) ? $bloque['campos'] : [];
            $this->insertarCamposEnBloque($idFormulario, $idBloque, $campos);
            $ordenBloque++;
        }

        if ($incluirAutorizacion) {
        $this->insertEnTabla('talleres_formulario_bloque', [
            'Id_Formulario' => $idFormulario,
            'Titulo' => 'Autorización',
            'Tipo' => 'autorizacion',
            'Orden' => $ordenBloque,
        ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $campos
     */
    private function insertarCamposEnBloque(int $idFormulario, int $idBloque, array $campos): void {
        $orden = 0;
        $nombresUsados = [];
        foreach ($campos as $campo) {
            $etiqueta = trim((string)($campo['etiqueta'] ?? ''));
            if ($etiqueta === '') {
                continue;
            }

            $tipo = strtolower(trim((string)($campo['tipo'] ?? 'text')));
            if (!in_array($tipo, self::TIPOS_CAMPO_PERMITIDOS, true)) {
                $tipo = 'text';
            }

            $nombre = $this->normalizarNombreCampo($etiqueta, 'campo_' . ($orden + 1));
            if (trim((string)($campo['nombre'] ?? '')) !== '') {
                $nombre = substr(preg_replace('/[^a-z0-9_]/', '_', strtolower(trim((string)$campo['nombre']))), 0, 80);
            }
            while (isset($nombresUsados[$nombre])) {
                $nombre .= '_' . ($orden + 1);
            }
            $nombresUsados[$nombre] = true;

            $opcionesJson = null;
            if ($tipo === 'tabla') {
                $columnas = $campo['columnas'] ?? [];
                if (!is_array($columnas)) {
                    $columnas = [];
                }
                $columnas = array_values(array_filter(array_map('trim', $columnas), static function ($v) {
                    return $v !== '';
                }));
                if (!empty($columnas)) {
                    $opcionesJson = json_encode(['columnas' => $columnas], JSON_UNESCAPED_UNICODE);
                }
            } else {
                $opciones = $campo['opciones'] ?? [];
                if (!is_array($opciones)) {
                    $opciones = [];
                }
                $opciones = array_values(array_filter(array_map('trim', $opciones), static function ($v) {
                    return $v !== '';
                }));
                if (!empty($opciones)) {
                    $opcionesJson = json_encode($opciones, JSON_UNESCAPED_UNICODE);
                }
            }

            $this->insertEnTabla('talleres_formulario_campo', [
                'Id_Formulario' => $idFormulario,
                'Id_Bloque' => $idBloque,
                'Nombre_Campo' => $nombre,
                'Etiqueta' => $etiqueta,
                'Tipo' => $tipo,
                'Requerido' => !empty($campo['requerido']) ? 1 : 0,
                'Orden' => $orden,
                'Opciones' => $opcionesJson,
                'Placeholder' => trim((string)($campo['placeholder'] ?? '')) ?: null,
                'Ayuda' => trim((string)($campo['ayuda'] ?? '')) ?: null,
            ]);
            $orden++;
        }
    }

    /**
     * Configuración general de la plantilla Taller de Padres.
     *
     * @return array<string, string>
     */
    public static function getConfigPlantillaTallerPadres(): array {
        return [
            'titulo' => 'Taller de Padres',
            'descripcion' => 'FORMULARIO DE INSCRIPCIÓN Y DIAGNÓSTICO — Taller de Padres. Complete todos los datos con veracidad.',
            'mensaje_gracias' => '¡Gracias! Hemos recibido su inscripción al Taller de Padres.',
            'texto_autorizacion' => 'Declaro que la información suministrada es veraz y autorizo su uso exclusivamente '
                . 'para fines relacionados con el desarrollo del Taller de Padres.',
        ];
    }

    /**
     * Aplica la plantilla completa a un formulario existente.
     */
    public function aplicarPlantillaTallerPadres(int $idFormulario): bool {
        if ($idFormulario <= 0) {
            return false;
        }
        $config = self::getConfigPlantillaTallerPadres();
        $this->update($idFormulario, [
            'Titulo' => $config['titulo'],
            'Descripcion' => $config['descripcion'],
            'Mensaje_Gracias' => $config['mensaje_gracias'],
            'Texto_Autorizacion' => $config['texto_autorizacion'],
        ]);
        $this->reemplazarBloquesYCampos($idFormulario, self::getPlantillaTallerPadres());
        return true;
    }

    /**
     * Plantilla de referencia: Taller de Padres.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getPlantillaTallerPadres(): array {
        return [
            [
                'titulo' => '2. INFORMACIÓN FAMILIAR',
                'campos' => [
                    ['etiqueta' => 'Nombre del cónyuge o acudiente (si aplica)', 'tipo' => 'text', 'requerido' => false],
                    ['etiqueta' => '¿Cuántos hijos tiene?', 'tipo' => 'number', 'requerido' => false],
                    [
                        'etiqueta' => 'Información de los hijos',
                        'tipo' => 'tabla',
                        'requerido' => false,
                        'columnas' => ['Nombre', 'Edad', 'Sexo', 'Escolaridad'],
                    ],
                    [
                        'etiqueta' => '¿Quiénes conforman actualmente su hogar?',
                        'tipo' => 'checkbox',
                        'requerido' => false,
                        'opciones' => ['Padre', 'Madre', 'Hijos', 'Abuelos', 'Otros familiares', 'Otros'],
                    ],
                    ['etiqueta' => 'Otros (especifique quiénes conforman su hogar)', 'tipo' => 'text', 'requerido' => false],
                ],
            ],
            [
                'titulo' => '3. DIAGNÓSTICO FAMILIAR',
                'campos' => [
                    [
                        'etiqueta' => '¿Cómo describiría la relación familiar en su hogar?',
                        'tipo' => 'radio',
                        'requerido' => false,
                        'opciones' => ['Excelente', 'Buena', 'Regular', 'Necesita mejorar'],
                    ],
                    [
                        'etiqueta' => '¿Con qué frecuencia realizan actividades familiares juntos?',
                        'tipo' => 'radio',
                        'requerido' => false,
                        'opciones' => ['Diariamente', 'Semanalmente', 'Mensualmente', 'Rara vez'],
                    ],
                    [
                        'etiqueta' => '¿Considera que existe una comunicación efectiva entre padres e hijos?',
                        'tipo' => 'radio',
                        'requerido' => false,
                        'opciones' => ['Siempre', 'Casi siempre', 'Algunas veces', 'Rara vez'],
                    ],
                    [
                        'etiqueta' => '¿Cuál considera que es el principal desafío que enfrenta su familia actualmente?',
                        'tipo' => 'textarea',
                        'requerido' => false,
                    ],
                ],
            ],
            [
                'titulo' => '4. TEMAS DE INTERÉS PARA EL TALLER',
                'campos' => [
                    [
                        'etiqueta' => 'Marque los temas que considera más importantes',
                        'tipo' => 'checkbox',
                        'requerido' => false,
                        'opciones' => [
                            'Comunicación familiar',
                            'Crianza positiva',
                            'Manejo de conflictos',
                            'Disciplina y límites',
                            'Uso responsable de redes sociales',
                            'Prevención de adicciones',
                            'Salud mental y emocional',
                            'Proyecto de vida para hijos y jóvenes',
                            'Fortalecimiento de valores',
                            'Educación sexual en Familia / Prevención abuso sexual',
                            'Manejo del estrés',
                            'Relaciones de pareja',
                            'Espiritualidad y familia',
                            'Prevención del acoso escolar (bullying)',
                        ],
                    ],
                ],
            ],
            [
                'titulo' => '5. NECESIDADES ESPECÍFICAS',
                'campos' => [
                    [
                        'etiqueta' => '¿Qué situaciones o temas le gustaría que fueran abordados durante el taller?',
                        'tipo' => 'textarea',
                        'requerido' => false,
                    ],
                    [
                        'etiqueta' => '¿Tiene alguna pregunta o inquietud específica sobre la crianza o la dinámica familiar?',
                        'tipo' => 'textarea',
                        'requerido' => false,
                    ],
                ],
            ],
            [
                'titulo' => '6. EXPECTATIVAS DEL TALLER',
                'campos' => [
                    [
                        'etiqueta' => '¿Qué espera aprender o fortalecer mediante este taller?',
                        'tipo' => 'textarea',
                        'requerido' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCamposPersonalizadosPorFormulario(int $idFormulario): array {
        $completo = $this->getFormularioCompleto($idFormulario);
        $campos = [];
        foreach ($completo['bloques'] as $item) {
            if (in_array(($item['bloque']['Tipo'] ?? ''), ['persona', 'autorizacion'], true)) {
                continue;
            }
            foreach ($item['campos'] as $campo) {
                $campos[] = $campo;
            }
        }
        return $campos;
    }

    /**
     * @param array<int, array<string, mixed>> $campos
     */
    public function reemplazarCampos(int $idFormulario, array $campos): void {
        if ($idFormulario <= 0) {
            return;
        }
        $this->execute('DELETE FROM talleres_formulario_campo WHERE Id_Formulario = ?', [$idFormulario]);

        $orden = 0;
        $nombresUsados = [];
        foreach ($campos as $campo) {
            $etiqueta = trim((string)($campo['etiqueta'] ?? ''));
            if ($etiqueta === '') {
                continue;
            }

            $tipo = strtolower(trim((string)($campo['tipo'] ?? 'text')));
            if (!in_array($tipo, self::TIPOS_CAMPO_PERMITIDOS, true)) {
                $tipo = 'text';
            }

            $nombre = $this->normalizarNombreCampo($etiqueta, 'campo_' . ($orden + 1));
            if (trim((string)($campo['nombre'] ?? '')) !== '') {
                $nombre = substr(preg_replace('/[^a-z0-9_]/', '_', strtolower(trim((string)$campo['nombre']))), 0, 80);
            }
            while (isset($nombresUsados[$nombre])) {
                $nombre .= '_' . ($orden + 1);
            }
            $nombresUsados[$nombre] = true;

            $opciones = $campo['opciones'] ?? [];
            if (!is_array($opciones)) {
                $opciones = [];
            }
            $opciones = array_values(array_filter(array_map('trim', $opciones), static function ($v) {
                return $v !== '';
            }));

            $this->insertEnTabla('talleres_formulario_campo', [
                'Id_Formulario' => $idFormulario,
                'Nombre_Campo' => $nombre,
                'Etiqueta' => $etiqueta,
                'Tipo' => $tipo,
                'Requerido' => !empty($campo['requerido']) ? 1 : 0,
                'Orden' => $orden,
                'Opciones' => !empty($opciones) ? json_encode($opciones, JSON_UNESCAPED_UNICODE) : null,
                'Placeholder' => trim((string)($campo['placeholder'] ?? '')) ?: null,
                'Ayuda' => trim((string)($campo['ayuda'] ?? '')) ?: null,
            ]);
            $orden++;
        }
    }

    /**
     * Insert en tabla auxiliar (campos, respuestas).
     */
    private function insertEnTabla(string $table, array $data): int {
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($fields), '?');
        $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return (int)$this->db->lastInsertId();
    }

    public function actualizarDatosJsonRespuesta(int $idRespuesta, array $datos): bool {
        $idRespuesta = (int)$idRespuesta;
        if ($idRespuesta <= 0) {
            return false;
        }
        $json = json_encode($datos, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return false;
        }
        return $this->execute(
            'UPDATE talleres_formulario_respuesta SET Datos_JSON = ? WHERE Id_Respuesta = ?',
            [$json, $idRespuesta]
        );
    }

    /**
     * @param array<int, array<string, string>> $documentos
     */
    public function agregarDocumentosAJsonRespuesta(int $idRespuesta, array $documentos): bool {
        $idRespuesta = (int)$idRespuesta;
        if ($idRespuesta <= 0 || $documentos === []) {
            return false;
        }

        $rows = $this->query(
            'SELECT Datos_JSON FROM talleres_formulario_respuesta WHERE Id_Respuesta = ? LIMIT 1',
            [$idRespuesta]
        );
        if (empty($rows[0])) {
            return false;
        }

        $json = json_decode((string)($rows[0]['Datos_JSON'] ?? '{}'), true);
        if (!is_array($json)) {
            $json = [];
        }
        $json['documentos_presentacion'] = $documentos;

        $encoded = json_encode($json, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return false;
        }

        return $this->execute(
            'UPDATE talleres_formulario_respuesta SET Datos_JSON = ? WHERE Id_Respuesta = ?',
            [$encoded, $idRespuesta]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRespuestasPorFormulario(int $idFormulario, int $limite = 5000): array {
        if ($idFormulario <= 0) {
            return [];
        }
        $limite = max(1, min(10000, $limite));
        return (array)$this->query(
            'SELECT * FROM talleres_formulario_respuesta WHERE Id_Formulario = ? ORDER BY Fecha_Registro DESC LIMIT ' . (int)$limite,
            [$idFormulario]
        );
    }

    /**
     * Busca una inscripción previa del mismo niño (por documento) en este formulario.
     *
     * @return array{id_respuesta:int, fecha:string, nino_nombre:string}|null
     */
    public function buscarInscripcionPorDocumentoNino(int $idFormulario, string $documento, int $excluirIdRespuesta = 0): ?array {
        $documento = preg_replace('/\D+/', '', trim($documento));
        if ($idFormulario <= 0 || $documento === '') {
            return null;
        }

        $rows = $this->query(
            'SELECT Id_Respuesta, Datos_JSON, Fecha_Registro FROM talleres_formulario_respuesta WHERE Id_Formulario = ?',
            [$idFormulario]
        );

        foreach ($rows as $row) {
            $idRespuesta = (int)($row['Id_Respuesta'] ?? 0);
            if ($idRespuesta <= 0) {
                continue;
            }
            if ($excluirIdRespuesta > 0 && $idRespuesta === $excluirIdRespuesta) {
                continue;
            }

            $json = json_decode((string)($row['Datos_JSON'] ?? '{}'), true);
            if (!is_array($json)) {
                continue;
            }

            $docRespuesta = preg_replace('/\D+/', '', (string)($json['nino_documento'] ?? ''));
            if ($docRespuesta === '' || $docRespuesta !== $documento) {
                continue;
            }

            return [
                'id_respuesta' => $idRespuesta,
                'fecha' => (string)($row['Fecha_Registro'] ?? ''),
                'nino_nombre' => trim((string)($json['nino_nombre'] ?? '')),
            ];
        }

        return null;
    }

    /**
     * Busca una inscripción previa de la misma persona (por documento o Id_Persona) en este formulario.
     *
     * @return array{id_respuesta:int, fecha:string, persona_nombre:string}|null
     */
    public function buscarInscripcionPorDocumentoPersona(int $idFormulario, string $documento, int $idPersona = 0, int $excluirIdRespuesta = 0): ?array {
        $documento = preg_replace('/\D+/', '', trim($documento));
        $idPersona = (int)$idPersona;
        if ($idFormulario <= 0 || ($documento === '' && $idPersona <= 0)) {
            return null;
        }

        $rows = $this->query(
            'SELECT Id_Respuesta, Id_Persona, Datos_JSON, Fecha_Registro FROM talleres_formulario_respuesta WHERE Id_Formulario = ?',
            [$idFormulario]
        );

        foreach ($rows as $row) {
            $idRespuesta = (int)($row['Id_Respuesta'] ?? 0);
            if ($idRespuesta <= 0) {
                continue;
            }
            if ($excluirIdRespuesta > 0 && $idRespuesta === $excluirIdRespuesta) {
                continue;
            }

            $idPersonaRespuesta = (int)($row['Id_Persona'] ?? 0);
            if ($idPersona > 0 && $idPersonaRespuesta > 0 && $idPersonaRespuesta === $idPersona) {
                $json = json_decode((string)($row['Datos_JSON'] ?? '{}'), true);
                $nombre = is_array($json)
                    ? trim((string)($json['persona_nombre'] ?? '') . ' ' . (string)($json['persona_apellido'] ?? ''))
                    : '';
                return [
                    'id_respuesta' => $idRespuesta,
                    'fecha' => (string)($row['Fecha_Registro'] ?? ''),
                    'persona_nombre' => trim($nombre),
                ];
            }

            if ($documento === '') {
                continue;
            }

            $json = json_decode((string)($row['Datos_JSON'] ?? '{}'), true);
            if (!is_array($json)) {
                continue;
            }

            $docRespuesta = preg_replace('/\D+/', '', (string)($json['persona_documento'] ?? ''));
            if ($docRespuesta === '' || $docRespuesta !== $documento) {
                continue;
            }

            $nombre = trim((string)($json['persona_nombre'] ?? '') . ' ' . (string)($json['persona_apellido'] ?? ''));
            return [
                'id_respuesta' => $idRespuesta,
                'fecha' => (string)($row['Fecha_Registro'] ?? ''),
                'persona_nombre' => trim($nombre),
            ];
        }

        return null;
    }

    public function guardarRespuesta(int $idFormulario, array $datos, ?string $ip = null, int $idPersona = 0): int {
        $payload = [
            'Id_Formulario' => $idFormulario,
            'Datos_JSON' => json_encode($datos, JSON_UNESCAPED_UNICODE),
            'Ip_Origen' => $ip,
        ];
        if ($idPersona > 0) {
            $payload['Id_Persona'] = $idPersona;
        }
        return $this->insertEnTabla('talleres_formulario_respuesta', $payload);
    }

    public function eliminarFormulario(int $idFormulario): bool {
        if ($idFormulario <= 0) {
            return false;
        }
        $this->execute('DELETE FROM talleres_formulario_respuesta WHERE Id_Formulario = ?', [$idFormulario]);
        $this->execute('DELETE FROM talleres_formulario_campo WHERE Id_Formulario = ?', [$idFormulario]);
        $this->execute('DELETE FROM talleres_formulario_bloque WHERE Id_Formulario = ?', [$idFormulario]);
        return $this->delete($idFormulario);
    }

    /**
     * @param array<string, mixed> $campo
     * @return array<int, string>
     */
    public function decodificarOpcionesCampo(array $campo): array {
        $raw = $campo['Opciones'] ?? '';
        if ($raw === '' || $raw === null) {
            return [];
        }
        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        if (isset($decoded['columnas'])) {
            return [];
        }
        return array_values(array_filter(array_map('trim', $decoded), static function ($v) {
            return $v !== '';
        }));
    }

    /**
     * @param array<string, mixed> $campo
     * @return array<int, string>
     */
    public function decodificarColumnasTabla(array $campo): array {
        $raw = $campo['Opciones'] ?? '';
        if ($raw === '' || $raw === null) {
            return [];
        }
        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded) || !isset($decoded['columnas']) || !is_array($decoded['columnas'])) {
            return [];
        }
        return array_values(array_filter(array_map('trim', $decoded['columnas']), static function ($v) {
            return $v !== '';
        }));
    }

    /**
     * @param array<int, array<string, mixed>> $campos
     * @param array<string, mixed> $post
     * @return array{ok: bool, errores: array<string, string>, datos: array<string, mixed>}
     */
    public function validarRespuestaPublica(array $campos, array $post): array {
        $errores = [];
        $datos = [];

        foreach ($campos as $campo) {
            $nombre = (string)($campo['Nombre_Campo'] ?? '');
            $etiqueta = (string)($campo['Etiqueta'] ?? $nombre);
            $tipo = strtolower((string)($campo['Tipo'] ?? 'text'));
            $requerido = !empty($campo['Requerido']);

            if ($nombre === '') {
                continue;
            }

            $valor = $post[$nombre] ?? null;
            if ($tipo === 'tabla') {
                if (is_string($valor) && $valor !== '') {
                    $decoded = json_decode($valor, true);
                    $valor = is_array($decoded) ? $decoded : [];
                } elseif (!is_array($valor)) {
                    $valor = [];
                }
                $valor = array_values(array_filter($valor, static function ($fila) {
                    if (!is_array($fila)) {
                        return false;
                    }
                    foreach ($fila as $celda) {
                        if (trim((string)$celda) !== '') {
                            return true;
                        }
                    }
                    return false;
                }));
            } elseif ($tipo === 'checkbox') {
                if (!is_array($valor)) {
                    $valor = ($valor !== null && $valor !== '') ? [(string)$valor] : [];
                }
                $valor = array_values(array_filter(array_map('trim', $valor), static function ($v) {
                    return $v !== '';
                }));
            } else {
                $valor = is_array($valor) ? '' : trim((string)$valor);
            }

            if ($requerido) {
                $vacio = ($tipo === 'checkbox' || $tipo === 'tabla') ? empty($valor) : ($valor === '');
                if ($vacio) {
                    $errores[$nombre] = 'El campo «' . $etiqueta . '» es obligatorio.';
                    continue;
                }
            }

            if ($tipo === 'email' && $valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                $errores[$nombre] = 'Ingrese un correo válido en «' . $etiqueta . '».';
                continue;
            }

            if ($tipo === 'number' && $valor !== '' && !is_numeric($valor)) {
                $errores[$nombre] = 'Ingrese un número válido en «' . $etiqueta . '».';
                continue;
            }

            $opciones = $this->decodificarOpcionesCampo($campo);
            if (in_array($tipo, ['select', 'radio'], true) && $valor !== '' && !empty($opciones) && !in_array($valor, $opciones, true)) {
                $errores[$nombre] = 'Seleccione una opción válida en «' . $etiqueta . '».';
                continue;
            }
            if ($tipo === 'checkbox' && !empty($valor) && !empty($opciones)) {
                foreach ($valor as $item) {
                    if (!in_array($item, $opciones, true)) {
                        $errores[$nombre] = 'Opción no válida en «' . $etiqueta . '».';
                        break;
                    }
                }
            }

            $datos[$nombre] = $valor;
        }

        return [
            'ok' => empty($errores),
            'errores' => $errores,
            'datos' => $datos,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $campos
     * @param array<string, mixed> $datosJson
     */
    public function formatearValorCampoRespuesta(array $campo, $datosJson): string {
        $nombre = (string)($campo['Nombre_Campo'] ?? '');
        if ($nombre === '' || !array_key_exists($nombre, $datosJson)) {
            return '';
        }
        $valor = $datosJson[$nombre];
        $tipo = strtolower((string)($campo['Tipo'] ?? 'text'));
        if ($tipo === 'tabla' && is_array($valor)) {
            $lineas = [];
            foreach ($valor as $fila) {
                if (!is_array($fila)) {
                    continue;
                }
                $partes = [];
                foreach ($fila as $col => $celda) {
                    $celda = trim((string)$celda);
                    if ($celda !== '') {
                        $partes[] = $col . ': ' . $celda;
                    }
                }
                if (!empty($partes)) {
                    $lineas[] = implode(' | ', $partes);
                }
            }
            return implode('; ', $lineas);
        }
        if (is_array($valor)) {
            return implode(', ', array_map('strval', $valor));
        }
        return trim((string)$valor);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPagosPorRespuesta(int $idRespuesta): array {
        if ($idRespuesta <= 0) {
            return [];
        }
        return (array)$this->query(
            'SELECT * FROM talleres_formulario_pago WHERE Id_Respuesta = ? ORDER BY Fecha_Registro ASC',
            [$idRespuesta]
        );
    }

    /**
     * @param array<int, int> $idsRespuesta
     * @return array<int, float>
     */
    public function getTotalesPagosPorRespuestas(array $idsRespuesta): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $idsRespuesta), static fn($id) => $id > 0)));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = $this->query(
            "SELECT Id_Respuesta, COALESCE(SUM(Valor_Pago), 0) AS total
             FROM talleres_formulario_pago
             WHERE Id_Respuesta IN ({$placeholders})
             GROUP BY Id_Respuesta",
            $ids
        );
        $out = [];
        foreach ($rows as $row) {
            $out[(int)($row['Id_Respuesta'] ?? 0)] = (float)($row['total'] ?? 0);
        }
        return $out;
    }

    public function registrarPago(int $idRespuesta, int $idFormulario, array $datos): int {
        if ($idRespuesta <= 0 || $idFormulario <= 0) {
            return 0;
        }
        return $this->insertEnTabla('talleres_formulario_pago', [
            'Id_Respuesta' => $idRespuesta,
            'Id_Formulario' => $idFormulario,
            'Metodo_Pago' => trim((string)($datos['metodo_pago'] ?? 'Efectivo')),
            'Recibido_Por' => trim((string)($datos['recibido_por'] ?? '')) ?: null,
            'Tipo_Pago' => trim((string)($datos['tipo_pago'] ?? 'completo')),
            'Valor_Pago' => (float)($datos['valor_pago'] ?? 0),
            'Referencia_Pago' => trim((string)($datos['referencia_pago'] ?? '')) ?: null,
        ]);
    }

    public function getRespuestaPorId(int $idRespuesta): ?array {
        if ($idRespuesta <= 0) {
            return null;
        }
        $rows = $this->query(
            'SELECT * FROM talleres_formulario_respuesta WHERE Id_Respuesta = ? LIMIT 1',
            [$idRespuesta]
        );
        return !empty($rows[0]) ? (array)$rows[0] : null;
    }
}
