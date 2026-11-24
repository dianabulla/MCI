<?php
/**
 * Premiación de talleres.
 *
 * Los nombres elegidos viven solo en talleres_premiacion_elegido.
 * Células, personas y asistencias se leen para armar la categoría 1
 * y no se actualizan desde aquí.
 */

require_once APP . '/Models/BaseModel.php';

class TallerPremiacion extends BaseModel {
    protected $table = 'talleres_premiacion_elegido';
    protected $primaryKey = 'Id_Elegido';

    /** @var array<string, bool> */
    private array $columnas = [];

    /**
     * Categorías de la premiación.
     * origen datos: el nombre se confirma desde el ranking de células.
     * origen libre: se escribe el nombre. Si más adelante hay listado, se llena «candidatos».
     *
     * @return list<array<string, mixed>>
     */
    public static function catalogo(): array {
        return [
            [
                'clave' => 'lideres_celula',
                'titulo' => 'Líderes de 12 y Célula',
                'responsable' => 'Sara',
                'origen' => 'datos',
                'nota' => 'Sale de lo que las células ya reportaron. Sara confirma el nombre. No cambia la ficha de nadie.',
                'premios' => [
                    [
                        'clave' => 'celula_crecimiento',
                        'titulo' => 'Célula de Mayor Crecimiento',
                        'tipo' => 'celula',
                    ],
                    [
                        'clave' => 'pastor_corazon',
                        'titulo' => 'Pastor de Corazón',
                        'detalle' => 'El líder que más personas llevó a encuentro, sumando los dos semestres',
                        'tipo' => 'persona',
                        'ranking' => 'pastores',
                    ],
                    [
                        'clave' => 'lider_revelacion',
                        'titulo' => 'Líder Revelación',
                        'tipo' => 'persona',
                        'ranking' => 'revelacion',
                    ],
                ],
            ],
            [
                'clave' => 'voluntarios',
                'titulo' => 'Voluntarios',
                'responsable' => 'Escribir el nombre',
                'origen' => 'libre',
                'nota' => 'Escribe nombre y apellido. Queda en mayúsculas. Si la persona ya está registrada, aparecen sugerencias.',
                'nominados' => [],
                'premios' => [
                    ['clave' => 'corazon_servicio', 'titulo' => 'Corazón de Servicio', 'tipo' => 'voto'],
                    ['clave' => 'siempre_presente', 'titulo' => 'Siempre Presente', 'detalle' => 'Constancia y puntualidad', 'tipo' => 'voto'],
                    ['clave' => 'actitud_inspira', 'titulo' => 'Actitud que Inspira', 'tipo' => 'voto'],
                ],
            ],
            [
                'clave' => 'banda',
                'titulo' => 'Banda MCI',
                'responsable' => 'Josué',
                'origen' => 'voto',
                'nota' => 'Se elige una persona por premio, de este listado.',
                'nominados' => [
                    ['grupo' => '', 'nombres' => [
                        'Alejandro Arismendi', 'Camilo Cifuentes', 'Daniela Quevedo', 'Fabian Camargo',
                        'Fabian Villa', 'Felipe Giraldo', 'Juan David Torres', 'Juliana', 'Laura Romero', 'Juanita Acuña',
                    ]],
                ],
                'premios' => [
                    ['clave' => 'musico_anio', 'titulo' => 'Músico del Año', 'tipo' => 'voto'],
                    ['clave' => 'adorador_excelencia', 'titulo' => 'Adorador de Excelencia', 'tipo' => 'voto'],
                ],
            ],
            [
                'clave' => 'produccion',
                'titulo' => 'Producción',
                'responsable' => 'Josué',
                'origen' => 'voto',
                'nota' => 'Se elige una persona por premio. Redes y sonido van en el mismo listado, separados por grupo.',
                'nominados' => [
                    ['grupo' => 'Redes', 'nombres' => [
                        'Mariana Grillo', 'Brillit', 'Andrea Acosta', 'Julian', 'Sara Acosta',
                    ]],
                    ['grupo' => 'Producción de sonido', 'nombres' => [
                        'Michael Torres', 'Michael Grillo', 'Santiago Castro', 'Diego Melo',
                        'Cristian Cruz', 'Jair Acosta', 'Santiago Mendez',
                    ]],
                ],
                'premios' => [
                    ['clave' => 'excelencia_tecnica', 'titulo' => 'Excelencia Técnica', 'detalle' => 'Sonido, luces y transmisión', 'tipo' => 'voto'],
                    ['clave' => 'creativo_anio', 'titulo' => 'Creativo del Año', 'detalle' => 'Diseño, video y redes', 'tipo' => 'voto'],
                    ['clave' => 'heroe_escena', 'titulo' => 'Héroe detrás de escena', 'tipo' => 'voto'],
                    ['clave' => 'solucion_presion', 'titulo' => 'Solución bajo presión', 'detalle' => 'Quien resolvió cuando algo falló en vivo', 'tipo' => 'voto'],
                ],
            ],
            [
                'clave' => 'kids',
                'titulo' => 'Equipo de Kids',
                'responsable' => 'Josué',
                'origen' => 'voto',
                'nota' => 'Se elige una persona por premio, entre maestros y auxiliares.',
                'nominados' => [
                    ['grupo' => 'Maestros', 'nombres' => [
                        'Yuli Paola Salamanca Jimenez', 'Lady Johana Jimenez Velasco', 'Diana Cajamarca Alonso',
                        'Yurani Garzon Jimenez', 'Luz Yenir Rubio Neira', 'Nicolas Ramírez', 'Valentina Ruiz',
                        'Juan Sebastián Heredia Osorio', 'Carolina Madariaga', 'Elaene Mairolyn Nava Edwards',
                        'Deisy Natalia Rodriguez Yaya', 'Jorge Armando Gonzalez Vargas', 'Ana Rocio Guzman Rozo',
                        'Beatriz Elena Padilla Arteaga', 'Janine Marina Acacio Atencia', 'Verónica María Velasco Rodríguez',
                        'Jennifer Andrea Loaiza Cancelado', 'Sol Dayana Agudelo Pérez', 'Omar Yesith Ramirez Pedrozo',
                        'Laura Camila Rodriguez', 'Laura Camila Prieto Cardona', 'Marco Andrés Carreño Ovalle',
                        'Laura Valentina Aponte Contreras',
                    ]],
                    ['grupo' => 'Auxiliares', 'nombres' => [
                        'Luisa Maria Varon Sierra', 'Emmanuel Martinez Martinez', 'Juan Diego Melo Gonzalez',
                        'Danna Salome Bautista Gutierrez', 'Juan José Acuña Pacheco', 'Mia Salomé Cruz Sanchez',
                        'Sara Sofia', 'Valentina - Beatriz', 'Santiago Janine', 'Juan Sebastian Castro Ortíz',
                        'Dylan Joel Chara Capador', 'Ana Isabella Wilches Velasco', 'Gabriela Wilches Velasco',
                        'Paula', 'Santiago Parra', 'Daniel Parra', 'Aranza Isabel Azocar Nava', 'Taliana Arismendi',
                        'Emily Garcia', 'Salomé Garcia',
                    ]],
                ],
                'premios' => [
                    ['clave' => 'kids_maestro', 'titulo' => 'Maestro del Año', 'tipo' => 'voto'],
                    ['clave' => 'kids_clase', 'titulo' => 'Clase más creativa', 'tipo' => 'voto'],
                ],
            ],
            [
                'clave' => 'teens',
                'titulo' => 'Equipo de Teens',
                'responsable' => 'Josué',
                'origen' => 'voto',
                'nota' => 'Se elige una persona por premio, de este listado.',
                'nominados' => [
                    ['grupo' => '', 'nombres' => [
                        'Cristian Cruz', 'Laura Posada', 'Lorena Riano', 'Samuel Correa', 'Gabriela Garcia',
                        'Cristobal', 'Juanita Acuna', 'Sara Acosta', 'Gabriela Melo', 'Natalia Baron',
                        'Isabella Camargo', 'Michael Grillo', 'Esteban Dias', 'Mariana Grillo', 'Brillit Gómez',
                        'Omar Ramírez', 'Laura Contreras', 'Sofía Bermúdez', 'Samuel Bermúdez', 'Esteban Pinto',
                        'Sofía Zapata', 'Matías Rangel', 'Jenifer Loaiza', 'Andrea Acosta', 'Laura Romero',
                        'Valeria Saavedra', 'Luciana / Samuel Abello', 'Sofia Romero', 'Sebastian Leon',
                        'Santiago Mendez', 'Luisa Varon', 'Daniel Acosta', 'Sara Sofia Sierra',
                    ]],
                ],
                'premios' => [
                    ['clave' => 'teens_maestro', 'titulo' => 'Maestro del Año', 'tipo' => 'voto'],
                    ['clave' => 'teens_clase', 'titulo' => 'Clase más creativa', 'tipo' => 'voto'],
                ],
            ],
            [
                'clave' => 'protocolo',
                'titulo' => 'Protocolo',
                'responsable' => 'Josué',
                'origen' => 'voto',
                'nota' => 'Se elige una persona por premio, de este listado.',
                'nominados' => [
                    ['grupo' => '', 'nombres' => [
                        'María Miranda', 'Alejandro Leal', 'Anayibe Sierra', 'Jimena Wilches', 'Greicy Santiago',
                    ]],
                ],
                'premios' => [
                    ['clave' => 'excelencia_protocolo', 'titulo' => 'Excelencia en protocolo', 'tipo' => 'voto'],
                    ['clave' => 'orden_puntualidad', 'titulo' => 'Orden y puntualidad', 'tipo' => 'voto'],
                    ['clave' => 'servicio_impecable', 'titulo' => 'Servicio impecable', 'tipo' => 'voto'],
                ],
            ],
            [
                'clave' => 'equipo_12',
                'titulo' => 'Equipo de 12',
                'responsable' => 'Josué',
                'origen' => 'voto',
                'nota' => 'Se elige una persona por premio, de este listado.',
                'nominados' => [
                    ['grupo' => '', 'nombres' => [
                        'Alejandro Arismendi', 'Andrea Rangel', 'Angelica Gualteros', 'Disney Dias',
                        'Elizabeth Nuevo', 'Fabian Villa', 'Fabian Camargo', 'Hugo Correa', 'Julia Baron',
                        'Leidy Ortiz', 'Michael Torres', 'Madeline Sánchez', 'Nancy Diez',
                    ]],
                ],
                'premios' => [
                    ['clave' => 'trayectoria_fidelidad', 'titulo' => 'Trayectoria y fidelidad', 'detalle' => 'Los años de servicio', 'tipo' => 'voto'],
                    ['clave' => 'espiritu_unidad', 'titulo' => 'Espíritu de unidad', 'tipo' => 'voto'],
                    ['clave' => 'grandes_logros', 'titulo' => 'Grandes logros', 'detalle' => 'Logros destacados', 'tipo' => 'voto'],
                ],
            ],
            [
                'clave' => 'pastores',
                'titulo' => 'Reconocimiento especial a los pastores',
                'responsable' => 'Josué',
                'origen' => 'voto',
                'nota' => 'Se elige un pastor.',
                'nominados' => [
                    ['grupo' => '', 'nombres' => [
                        'Leonardo Alexander Peña Avellaneda', 'Yancly Escobar Sierra',
                    ]],
                ],
                'premios' => [
                    ['clave' => 'pastores_especial', 'titulo' => 'Reconocimiento especial a los pastores', 'tipo' => 'voto'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function premiosPorClave(): array {
        $mapa = [];
        foreach (self::catalogo() as $categoria) {
            foreach ($categoria['premios'] as $premio) {
                $premio['categoria'] = (string)$categoria['clave'];
                $premio['origen'] = (string)$categoria['origen'];
                $mapa[(string)$premio['clave']] = $premio;
            }
        }
        return $mapa;
    }

    /**
     * Categorías que salen en el enlace público. La de células no entra.
     *
     * @return list<array<string, mixed>>
     */
    public static function categoriasVotacion(): array {
        $salida = [];
        foreach (self::catalogo() as $categoria) {
            $origen = (string)($categoria['origen'] ?? '');
            if ($origen !== 'voto' && $origen !== 'libre') {
                continue;
            }
            if ($origen === 'voto' && self::nombresDeCategoria($categoria) === []) {
                continue;
            }
            $salida[] = $categoria;
        }
        return $salida;
    }

    /**
     * @param array<string, mixed> $categoria
     * @return list<string>
     */
    public static function nombresDeCategoria(array $categoria): array {
        $nombres = [];
        $grupos = $categoria['nominados'] ?? [];
        if (!is_array($grupos)) {
            return [];
        }
        foreach ($grupos as $grupo) {
            if (!is_array($grupo)) {
                continue;
            }
            foreach ($grupo['nombres'] ?? [] as $nombre) {
                $nombre = trim((string)$nombre);
                if ($nombre !== '') {
                    $nombres[] = $nombre;
                }
            }
        }
        return $nombres;
    }

    public static function normalizarNombreLibre(string $nombre): string {
        $nombre = trim(preg_replace('/\s+/u', ' ', $nombre) ?? '');
        return mb_strtoupper($nombre, 'UTF-8');
    }

    public static function esNombreYApellido(string $nombre): bool {
        return (bool)preg_match('/^\p{L}[\p{L}\'’.\-]{1,}(?:\s+\p{L}[\p{L}\'’.\-]{1,})+$/u', $nombre);
    }

    /**
     * Nombres ya registrados. Solo nombre y apellido, sin otros datos.
     *
     * @return list<string>
     */
    public function sugerirNombres(string $texto): array {
        $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? '');
        $texto = str_replace(['%', '_'], '', $texto);
        if (mb_strlen($texto) < 2) {
            return [];
        }
        $texto = mb_substr($texto, 0, 40);
        $filas = $this->query(
            "SELECT DISTINCT TRIM(CONCAT(COALESCE(Nombre, ''), ' ', COALESCE(Apellido, ''))) AS nombre
             FROM persona
             WHERE (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL)
               AND TRIM(CONCAT(COALESCE(Nombre, ''), ' ', COALESCE(Apellido, ''))) LIKE ?
             ORDER BY nombre
             LIMIT 12",
            ['%' . $texto . '%']
        );
        $nombres = [];
        foreach ($filas as $fila) {
            $nombre = self::normalizarNombreLibre((string)($fila['nombre'] ?? ''));
            if (!self::esNombreYApellido($nombre) || isset($nombres[$nombre])) {
                continue;
            }
            $nombres[$nombre] = true;
            if (count($nombres) >= 8) {
                break;
            }
        }
        return array_keys($nombres);
    }

    public static function normalizarTelefono(string $telefono): string {
        $digitos = preg_replace('/\D+/', '', $telefono) ?? '';
        if (str_starts_with($digitos, '57') && strlen($digitos) === 12) {
            $digitos = substr($digitos, 2);
        }
        return $digitos;
    }

    public function __construct() {
        parent::__construct();
        $this->ensureSchema();
    }

    public function ensureSchema(): void {
        $this->execute(
            "CREATE TABLE IF NOT EXISTS talleres_premiacion_elegido (
                Id_Elegido INT UNSIGNED NOT NULL AUTO_INCREMENT,
                Anio SMALLINT UNSIGNED NOT NULL,
                Clave_Premio VARCHAR(64) NOT NULL,
                Nombre VARCHAR(1000) NOT NULL DEFAULT '',
                Id_Persona INT NULL,
                Id_Celula INT NULL,
                Detalle VARCHAR(255) NULL,
                Actualizado_Por INT NULL,
                Fecha_Actualizacion DATETIME NULL,
                PRIMARY KEY (Id_Elegido),
                UNIQUE KEY uq_premiacion_anio_premio (Anio, Clave_Premio)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->execute(
            "CREATE TABLE IF NOT EXISTS talleres_premiacion_voto (
                Id_Voto INT UNSIGNED NOT NULL AUTO_INCREMENT,
                Anio SMALLINT UNSIGNED NOT NULL,
                Telefono VARCHAR(20) NOT NULL,
                Nombre_Votante VARCHAR(120) NOT NULL,
                Votos_JSON LONGTEXT NOT NULL,
                Ip_Origen VARCHAR(45) NULL,
                Fecha_Creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (Id_Voto),
                UNIQUE KEY uq_premiacion_voto_tel (Anio, Telefono)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function elegidos(int $anio): array {
        $filas = $this->query(
            "SELECT Anio, Clave_Premio, Nombre, Id_Persona, Id_Celula, Detalle, Fecha_Actualizacion
             FROM talleres_premiacion_elegido
             WHERE Anio = ?",
            [$anio]
        );
        $mapa = [];
        foreach ($filas as $fila) {
            $clave = (string)($fila['Clave_Premio'] ?? '');
            if ($clave !== '') {
                $mapa[$clave] = $fila;
            }
        }
        return $mapa;
    }

    /**
     * Rankings de solo lectura. No escribe en células ni personas.
     *
     * @return array{celulas: list<array<string, mixed>>, pastores: list<array<string, mixed>>, revelacion: list<array<string, mixed>>}
     */
    public function rankings(int $anio): array {
        $desde = sprintf('%04d-01-01', $anio);
        $hasta = sprintf('%04d-12-31', $anio);
        $hoy = date('Y-m-d');
        if (strcmp($hasta, $hoy) > 0) {
            $hasta = $hoy;
        }

        $celulas = $this->celulasActivas();
        $nuevos = $this->personasNuevasDelAnio($desde, $hasta);
        $progreso = $this->progresoFormacionDelAnio($desde, $hasta);

        $porCelula = [];
        foreach ($celulas as $celula) {
            $id = (int)$celula['Id_Celula'];
            $porCelula[$id] = [
                'id_celula' => $id,
                'id_persona' => (int)($celula['Id_Lider'] ?? 0),
                'nombre' => trim((string)($celula['Nombre_Celula'] ?? '')),
                'lider' => trim((string)($celula['Nombre_Lider'] ?? '')),
                'lider_12' => trim((string)($celula['Nombre_Lider_12'] ?? '')),
                'ministerio' => trim((string)($celula['Ministerio'] ?? '')),
                'nuevas' => 0,
                'crecimiento' => 0,
                'dos_dias' => 0,
                'un_dia' => 0,
                'cap' => 0,
                'siguieron' => 0,
                'asistencias' => 0,
                'asistentes' => 0,
                'bautismos' => 0,
                'inscripciones' => 0,
            ];
        }

        $asistencia = $this->asistenciaPorPersona($desde, $hasta);

        foreach ($nuevos as $persona) {
            $idCelula = (int)($persona['Id_Celula'] ?? 0);
            $idPersona = (int)($persona['Id_Persona'] ?? 0);
            if ($idCelula <= 0 || $idPersona <= 0 || !isset($porCelula[$idCelula])) {
                continue;
            }
            if ($idPersona === (int)$porCelula[$idCelula]['id_persona']) {
                continue;
            }
            $porCelula[$idCelula]['nuevas']++;
            $marca = $progreso[$idPersona] ?? null;
            if (!is_array($marca)) {
                continue;
            }
            if (!empty($marca['recorrido'])) {
                $porCelula[$idCelula]['crecimiento']++;
            }
            if (!empty($marca['encuentro'])) {
                $porCelula[$idCelula]['dos_dias']++;
            }
            if (!empty($marca['un_dia'])) {
                $porCelula[$idCelula]['un_dia']++;
            }
            if (!empty($marca['cap'])) {
                $porCelula[$idCelula]['cap']++;
            }
        }

        $liderPorCelula = [];
        foreach ($porCelula as $idCelula => $fila) {
            $liderPorCelula[(int)$idCelula] = (int)$fila['id_persona'];
        }
        $senales = $this->senalesPorCelula($desde, $hasta, $liderPorCelula);
        foreach ($senales as $idCelula => $senal) {
            if (!isset($porCelula[$idCelula])) {
                continue;
            }
            $porCelula[$idCelula]['bautismos'] = (int)$senal['bautismos'];
            $porCelula[$idCelula]['inscripciones'] = (int)$senal['inscripciones'];
        }

        $diagnostico = [
            'nuevos' => 0,
            'dos_dias' => 0,
            'cap' => 0,
            'recorrido' => 0,
            's1' => 0,
            's2' => 0,
            'total_encuentro' => 0,
            'bautismos' => 0,
            'inscripciones' => 0,
        ];
        foreach ($nuevos as $persona) {
            $diagnostico['nuevos']++;
            $marcaDiag = $progreso[(int)($persona['Id_Persona'] ?? 0)] ?? null;
            if (!is_array($marcaDiag)) {
                continue;
            }
            if (!empty($marcaDiag['encuentro'])) {
                $diagnostico['dos_dias']++;
            }
            $creditoDiag = $this->creditoEncuentroAnio(
                $marcaDiag,
                substr((string)($persona['Fecha_Registro'] ?? ''), 0, 10),
                $anio
            );
            $diagnostico['s1'] += $creditoDiag['s1'];
            $diagnostico['s2'] += $creditoDiag['s2'];
            $diagnostico['total_encuentro'] += $creditoDiag['total'];
            if (!empty($marcaDiag['cap'])) {
                $diagnostico['cap']++;
            }
            if (!empty($marcaDiag['recorrido'])) {
                $diagnostico['recorrido']++;
            }
        }
        foreach ($senales as $senal) {
            $diagnostico['bautismos'] += (int)$senal['bautismos'];
            $diagnostico['inscripciones'] += (int)$senal['inscripciones'];
        }

        $listaCelulas = [];
        foreach ($porCelula as $fila) {
            if ((int)$fila['nuevas'] <= 0) {
                continue;
            }
            $fila['detalle'] = $fila['nuevas'] . ' nuevos'
                . ' · ' . $fila['un_dia'] . ' de ellos fueron a un día de encuentro'
                . ' · ' . $fila['bautismos'] . ' bautismos'
                . ' · ' . $fila['inscripciones'] . ' inscripciones a encuentro';
            if ($fila['lider'] !== '') {
                $fila['detalle'] .= ' · líder ' . $fila['lider'];
            }
            $listaCelulas[] = $fila;
        }
        usort($listaCelulas, static function (array $a, array $b): int {
            foreach (['nuevas', 'un_dia', 'bautismos', 'inscripciones'] as $campo) {
                $cmp = ((int)$b[$campo]) <=> ((int)$a[$campo]);
                if ($cmp !== 0) {
                    return $cmp;
                }
            }
            return 0;
        });

        $pastores = $this->rankingPastores($anio, $progreso);
        $revelacion = $this->rankingRevelacion($anio, $desde, $hasta, $celulas, $progreso, $asistencia);

        return [
            'celulas' => array_slice($listaCelulas, 0, 25),
            'pastores' => array_slice($pastores, 0, 25),
            'revelacion' => array_slice($revelacion, 0, 25),
            'diagnostico' => $diagnostico,
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $previos
     * @param array{celulas: list<array<string, mixed>>, pastores: list<array<string, mixed>>, revelacion: list<array<string, mixed>>} $rankings
     */
    public function guardar(int $anio, array $post, array $previos, array $rankings, int $usuarioId): void {
        $premios = self::premiosPorClave();
        $celulas = [];
        foreach ($rankings['celulas'] as $fila) {
            $celulas[(int)$fila['id_celula']] = $fila;
        }
        $porRanking = [
            'pastores' => [],
            'revelacion' => [],
        ];
        foreach (['pastores', 'revelacion'] as $claveRanking) {
            foreach ($rankings[$claveRanking] as $fila) {
                $porRanking[$claveRanking][(int)$fila['id_persona']] = $fila;
            }
        }

        $this->db->beginTransaction();
        try {
            foreach ($premios as $clave => $premio) {
                $tipo = (string)($premio['tipo'] ?? 'texto');
                if ($tipo === 'celula') {
                    $this->guardarCelula($anio, $clave, (int)($post['celula'][$clave] ?? 0), $previos[$clave] ?? null, $celulas, $usuarioId);
                    continue;
                }
                if ($tipo === 'texto' || $tipo === 'texto_largo' || $tipo === 'voto') {
                    continue;
                }
                if ($tipo === 'persona') {
                    $rankingClave = (string)($premio['ranking'] ?? 'revelacion');
                    $this->guardarPersona(
                        $anio,
                        $clave,
                        (int)($post['persona'][$clave] ?? 0),
                        $previos[$clave] ?? null,
                        $porRanking[$rankingClave] ?? [],
                        $rankingClave,
                        $usuarioId
                    );
                    continue;
                }
                $max = $tipo === 'texto_largo' ? 1000 : 180;
                $this->guardarTexto($anio, $clave, (string)($post['nombre'][$clave] ?? ''), $max, $usuarioId);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * @param array<string, mixed>|null $previo
     * @param array<int, array<string, mixed>> $celulas
     */
    private function guardarCelula(int $anio, string $clave, int $idCelula, ?array $previo, array $celulas, int $usuarioId): void {
        if ($idCelula <= 0) {
            $this->borrarElegido($anio, $clave);
            return;
        }
        if (isset($celulas[$idCelula])) {
            $fila = $celulas[$idCelula];
            $this->upsert($anio, $clave, (string)$fila['nombre'], (int)$fila['id_persona'], $idCelula, (string)$fila['detalle'], $usuarioId);
            return;
        }
        if ($previo && (int)($previo['Id_Celula'] ?? 0) === $idCelula) {
            return;
        }
        throw new InvalidArgumentException('La célula elegida no está en el ranking de este año.');
    }

    /**
     * @param array<string, mixed>|null $previo
     * @param array<int, array<string, mixed>> $personas
     */
    private function guardarPersona(int $anio, string $clave, int $idPersona, ?array $previo, array $personas, string $ranking, int $usuarioId): void {
        if ($idPersona <= 0) {
            $this->borrarElegido($anio, $clave);
            return;
        }
        if (isset($personas[$idPersona])) {
            $fila = $personas[$idPersona];
            $detalle = $ranking === 'pastores'
                ? (string)$fila['detalle_pastor']
                : (string)$fila['detalle_revelacion'];
            $this->upsert($anio, $clave, (string)$fila['nombre'], $idPersona, 0, $detalle, $usuarioId);
            return;
        }
        if ($previo && (int)($previo['Id_Persona'] ?? 0) === $idPersona) {
            return;
        }
        throw new InvalidArgumentException('La persona elegida no está en el ranking de este año.');
    }

    private function guardarTexto(int $anio, string $clave, string $nombre, int $max, int $usuarioId): void {
        $nombre = str_replace(["\0", "\r"], ['', ''], $nombre);
        if ($max <= 180) {
            $nombre = preg_replace('/\s+/u', ' ', $nombre) ?? $nombre;
        }
        $nombre = trim($nombre);
        if ($nombre === '') {
            $this->borrarElegido($anio, $clave);
            return;
        }
        if (mb_strlen($nombre) > $max) {
            $nombre = mb_substr($nombre, 0, $max);
        }
        $this->upsert($anio, $clave, $nombre, 0, 0, '', $usuarioId);
    }

    private function upsert(int $anio, string $clave, string $nombre, int $idPersona, int $idCelula, string $detalle, int $usuarioId): void {
        $this->execute(
            "INSERT INTO talleres_premiacion_elegido
                (Anio, Clave_Premio, Nombre, Id_Persona, Id_Celula, Detalle, Actualizado_Por, Fecha_Actualizacion)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                Nombre = VALUES(Nombre),
                Id_Persona = VALUES(Id_Persona),
                Id_Celula = VALUES(Id_Celula),
                Detalle = VALUES(Detalle),
                Actualizado_Por = VALUES(Actualizado_Por),
                Fecha_Actualizacion = NOW()",
            [
                $anio,
                $clave,
                $nombre,
                $idPersona > 0 ? $idPersona : null,
                $idCelula > 0 ? $idCelula : null,
                $detalle !== '' ? mb_substr($detalle, 0, 255) : null,
                $usuarioId > 0 ? $usuarioId : null,
            ]
        );
    }

    private function borrarElegido(int $anio, string $clave): void {
        $this->execute(
            "DELETE FROM talleres_premiacion_elegido WHERE Anio = ? AND Clave_Premio = ?",
            [$anio, $clave]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function celulasActivas(): array {
        $filtroEstado = '';
        if ($this->columnaExiste('celula', 'Estado_Celula')) {
            $filtroEstado = "WHERE (c.Estado_Celula = 'Activa' OR c.Estado_Celula IS NULL OR c.Estado_Celula = '')";
        }
        $joinMinisterio = '';
        $selectMinisterio = "'' AS Ministerio";
        if ($this->columnaExiste('celula', 'Id_Ministerio')) {
            $joinMinisterio = 'LEFT JOIN ministerio m ON m.Id_Ministerio = c.Id_Ministerio';
            $selectMinisterio = 'COALESCE(m.Nombre_Ministerio, ml.Nombre_Ministerio, \'\') AS Ministerio';
        } else {
            $selectMinisterio = "COALESCE(ml.Nombre_Ministerio, '') AS Ministerio";
        }

        $selectApertura = $this->columnaExiste('celula', 'Fecha_Apertura')
            ? 'c.Fecha_Apertura'
            : 'NULL AS Fecha_Apertura';
        $selectNueva = $this->columnaExiste('celula', 'Es_Antiguo')
            ? 'COALESCE(c.Es_Antiguo, 0) AS Es_Antiguo_Celula'
            : '0 AS Es_Antiguo_Celula';

        $sql = "SELECT c.Id_Celula, c.Nombre_Celula, c.Id_Lider, c.Id_Lider_Inmediato, {$selectApertura}, {$selectNueva},
                       l.Fecha_Registro AS Fecha_Registro_Lider,
                       TRIM(CONCAT(COALESCE(l.Nombre, ''), ' ', COALESCE(l.Apellido, ''))) AS Nombre_Lider,
                       TRIM(CONCAT(COALESCE(li.Nombre, ''), ' ', COALESCE(li.Apellido, ''))) AS Nombre_Lider_12,
                       {$selectMinisterio}
                FROM celula c
                LEFT JOIN persona l ON l.Id_Persona = c.Id_Lider
                LEFT JOIN persona li ON li.Id_Persona = c.Id_Lider_Inmediato
                LEFT JOIN ministerio ml ON ml.Id_Ministerio = l.Id_Ministerio
                {$joinMinisterio}
                {$filtroEstado}";

        return $this->query($sql);
    }

    /**
     * Personas nuevas del año que siguen activas y ubicadas en una célula.
     *
     * @return list<array<string, mixed>>
     */
    private function personasNuevasDelAnio(string $desde, string $hasta): array {
        return $this->query(
            "SELECT Id_Persona, Id_Celula, Id_Lider, Fecha_Registro
             FROM persona
             WHERE (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL OR Estado_Cuenta = '')
               AND Id_Celula IS NOT NULL AND Id_Celula > 0
               AND Fecha_Registro >= ? AND Fecha_Registro < ?",
            [$desde . ' 00:00:00', date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00']
        );
    }

    /**
     * Asistencia real de formación en el año. Encuentro cuenta solo si asistió a las clases 5 y 6.
     *
     * @return array<int, array{uv:bool,encuentro:bool,bautismo:bool,cap:bool,semestre:int,encuentro_fecha:string}>
     */
    private function progresoFormacionDelAnio(string $desde, string $hasta): array {
        $anio = (int)substr($desde, 0, 4);
        $clases = [];
        try {
            $filas = $this->query(
                "SELECT Id_Persona, LOWER(TRIM(Programa)) AS Programa, Numero_Clase,
                        DATE(Fecha_Actualizacion) AS marca
                 FROM escuela_formacion_asistencia_clase
                 WHERE Asistio = 1 AND Id_Persona IS NOT NULL AND Id_Persona > 0"
            );
        } catch (Throwable $e) {
            error_log('Premiación formación: ' . $e->getMessage());
            return [];
        }

        foreach ($filas as $fila) {
            $id = (int)($fila['Id_Persona'] ?? 0);
            $programa = (string)($fila['Programa'] ?? '');
            $clase = (int)($fila['Numero_Clase'] ?? 0);
            if ($id <= 0 || $programa === '' || $clase <= 0) {
                continue;
            }
            $marca = substr(trim((string)($fila['marca'] ?? '')), 0, 10);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $marca) !== 1 || $marca === '0000-00-00') {
                $marca = '';
            }
            $fecha = $marca;
            $previa = $clases[$id][$programa][$clase] ?? '';
            $previaEnAnio = $this->fechaEnRango($previa, $desde, $hasta);
            $fechaEnAnio = $this->fechaEnRango($fecha, $desde, $hasta);
            if ($previa === '' || ($fechaEnAnio && !$previaEnAnio) || ($fechaEnAnio === $previaEnAnio && $fecha > $previa)) {
                $clases[$id][$programa][$clase] = $fecha;
            }
        }

        $inicioAnio = sprintf('%04d-01-01', $anio);
        $finAnio = sprintf('%04d-12-31', $anio);
        $progreso = [];
        foreach ($clases as $id => $programas) {
            $cap = false;
            $capEnAnio = false;
            $tiene5 = false;
            $tiene6 = false;
            $fecha5 = '';
            $fecha6 = '';
            $enS1 = false;
            $enS2 = false;
            foreach ($programas as $programa => $numeros) {
                if (strpos($programa, 'capacitacion_destino') === 0) {
                    $cap = true;
                    foreach ($numeros as $fechaCap) {
                        if ($this->fechaEnRango((string)$fechaCap, $inicioAnio, $finAnio)) {
                            $capEnAnio = true;
                        }
                    }
                }
                if ($programa !== 'universidad_vida' && $programa !== 'encuentro') {
                    continue;
                }
                foreach ($numeros as $numero => $fecha) {
                    $numero = (int)$numero;
                    $esDiaEncuentro = ($programa === 'universidad_vida' && ($numero === 5 || $numero === 6))
                        || ($programa === 'encuentro' && ($numero === 1 || $numero === 2 || $numero === 5 || $numero === 6));
                    if ($esDiaEncuentro) {
                        $semestreClase = $this->semestreDeFecha((string)$fecha, $anio);
                        if ($semestreClase === 1) {
                            $enS1 = true;
                        } elseif ($semestreClase === 2) {
                            $enS2 = true;
                        }
                    }
                    if ($numero === 5) {
                        $tiene5 = true;
                        if ((string)$fecha > $fecha5) {
                            $fecha5 = (string)$fecha;
                        }
                    }
                    if ($numero === 6) {
                        $tiene6 = true;
                        if ((string)$fecha > $fecha6) {
                            $fecha6 = (string)$fecha;
                        }
                    }
                }
            }
            if ((!$tiene5 || !$tiene6) && isset($programas['encuentro'][1], $programas['encuentro'][2])) {
                $tiene5 = true;
                $tiene6 = true;
                $fecha5 = (string)$programas['encuentro'][1];
                $fecha6 = (string)$programas['encuentro'][2];
            }
            $encuentro = $tiene5 && $tiene6;
            $enAnio = $this->fechaEnRango($fecha5, $inicioAnio, $finAnio)
                || $this->fechaEnRango($fecha6, $inicioAnio, $finAnio)
                || $capEnAnio;
            $cierre = '';
            foreach ([$fecha5, $fecha6] as $dia) {
                if ($this->semestreDeFecha($dia, $anio) > 0 && $dia > $cierre) {
                    $cierre = $dia;
                }
            }
            $progreso[(int)$id] = [
                'encuentro' => $encuentro,
                'un_dia' => $tiene5 || $tiene6 || isset($programas['encuentro'][1]) || isset($programas['encuentro'][2]),
                'cap' => $cap,
                'recorrido' => $encuentro && $cap,
                'en_anio' => $enAnio,
                's1' => $enS1,
                's2' => $enS2,
                'semestre' => $this->semestreDeFecha($cierre, $anio),
                'encuentro_fecha' => $cierre,
            ];
        }
        return $progreso;
    }

    /**
     * Célula abierta en el año, marcada como nueva, o de un líder que entró este año.
     *
     * @param array<string, mixed> $celula
     */
    private function celulaNuevaEnElAnio(array $celula, string $desde, string $hasta): bool {
        $apertura = substr(trim((string)($celula['Fecha_Apertura'] ?? '')), 0, 10);
        if ($apertura === '0000-00-00') {
            $apertura = '';
        }
        if ($this->fechaEnRango($apertura, $desde, $hasta)) {
            return true;
        }
        $anioApertura = (int)substr($apertura, 0, 4);
        $anio = (int)substr($desde, 0, 4);
        if ($anioApertura > 0 && $anioApertura < $anio) {
            return false;
        }
        return (int)($celula['Es_Antiguo_Celula'] ?? 1) === 0;
    }

    /**
     * @param list<int> $idsCelulas
     * @return list<array<string, mixed>>
     */
    private function miembrosDeCelulas(array $idsCelulas): array {
        $idsCelulas = array_values(array_filter(array_map('intval', $idsCelulas), static function (int $id): bool {
            return $id > 0;
        }));
        if ($idsCelulas === []) {
            return [];
        }
        $marcas = implode(',', array_fill(0, count($idsCelulas), '?'));
        return $this->query(
            "SELECT Id_Persona, Id_Celula, Fecha_Registro
             FROM persona
             WHERE (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL OR Estado_Cuenta = '')
               AND Id_Celula IN ({$marcas})",
            $idsCelulas
        );
    }

    /**
     * Bautismos e inscripciones a encuentro de cada célula en el año.
     * Cuentan personas distintas: un bautismo no tiene que ser quien se inscribió ni quien es nuevo.
     *
     * @param array<int, int> $liderPorCelula
     * @return array<int, array{bautismos:int,inscripciones:int}>
     */
    private function senalesPorCelula(string $desde, string $hasta, array $liderPorCelula): array {
        $anio = (int)substr($desde, 0, 4);
        $fin = date('Y-m-d', strtotime($hasta . ' +1 day')) . ' 00:00:00';
        $bautizados = [];
        $inscritos = [];

        try {
            $filas = $this->query(
                "SELECT p.Id_Celula, p.Id_Persona, LOWER(TRIM(i.Programa)) AS Programa
                 FROM escuela_formacion_inscripcion i
                 INNER JOIN persona p ON p.Id_Persona = i.Id_Persona
                 WHERE i.Fecha_Registro >= ? AND i.Fecha_Registro < ?
                   AND p.Id_Celula IS NOT NULL AND p.Id_Celula > 0
                   AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL OR p.Estado_Cuenta = '')
                   AND LOWER(TRIM(i.Programa)) IN ('universidad_vida', 'encuentro', 'bautismo')",
                [$desde . ' 00:00:00', $fin]
            );
        } catch (Throwable $e) {
            error_log('Premiación inscripciones: ' . $e->getMessage());
            $filas = [];
        }
        foreach ($filas as $fila) {
            $idCelula = (int)($fila['Id_Celula'] ?? 0);
            $idPersona = (int)($fila['Id_Persona'] ?? 0);
            if ($idCelula <= 0 || $idPersona <= 0 || $idPersona === (int)($liderPorCelula[$idCelula] ?? 0)) {
                continue;
            }
            $programa = (string)($fila['Programa'] ?? '');
            if ($programa === 'bautismo') {
                $bautizados[$idCelula][$idPersona] = true;
            }
            if ($programa === 'universidad_vida' || $programa === 'encuentro') {
                $inscritos[$idCelula][$idPersona] = true;
            }
        }

        try {
            $asistencias = $this->query(
                "SELECT p.Id_Celula, p.Id_Persona, DATE(a.Fecha_Actualizacion) AS marca, p.Fecha_Registro
                 FROM escuela_formacion_asistencia_clase a
                 INNER JOIN persona p ON p.Id_Persona = a.Id_Persona
                 WHERE a.Asistio = 1
                   AND LOWER(TRIM(a.Programa)) = 'bautismo'
                   AND p.Id_Celula IS NOT NULL AND p.Id_Celula > 0
                   AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL OR p.Estado_Cuenta = '')"
            );
        } catch (Throwable $e) {
            error_log('Premiación bautismos: ' . $e->getMessage());
            $asistencias = [];
        }
        foreach ($asistencias as $fila) {
            $idCelula = (int)($fila['Id_Celula'] ?? 0);
            $idPersona = (int)($fila['Id_Persona'] ?? 0);
            if ($idCelula <= 0 || $idPersona <= 0 || $idPersona === (int)($liderPorCelula[$idCelula] ?? 0)) {
                continue;
            }
            $marca = substr(trim((string)($fila['marca'] ?? '')), 0, 10);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $marca) !== 1 || $marca === '0000-00-00') {
                $marca = '';
            }
            $entroEsteAnio = (int)substr((string)($fila['Fecha_Registro'] ?? ''), 0, 4) === $anio;
            if ($this->fechaEnRango($marca, $desde, $hasta) || ($marca === '' && $entroEsteAnio)) {
                $bautizados[$idCelula][$idPersona] = true;
            }
        }

        $ids = array_unique(array_merge(array_keys($bautizados), array_keys($inscritos)));
        $mapa = [];
        foreach ($ids as $idCelula) {
            $mapa[(int)$idCelula] = [
                'bautismos' => count($bautizados[$idCelula] ?? []),
                'inscripciones' => count($inscritos[$idCelula] ?? []),
            ];
        }
        return $mapa;
    }

    /**
     * Asistencias reales a célula, partidas en semestres de seis meses.
     *
     * @return array<int, array<int, array{s1:int,s2:int,total:int}>>
     */
    private function asistenciaPorPersona(string $desde, string $hasta): array {
        $corte = sprintf('%04d-07-01', (int)substr($desde, 0, 4));
        $filas = $this->query(
            "SELECT Id_Celula, Id_Persona,
                    SUM(CASE WHEN Fecha_Asistencia < ? THEN 1 ELSE 0 END) AS s1,
                    SUM(CASE WHEN Fecha_Asistencia >= ? THEN 1 ELSE 0 END) AS s2
             FROM asistencia_celula
             WHERE Asistio = 1
               AND Fecha_Asistencia BETWEEN ? AND ?
             GROUP BY Id_Celula, Id_Persona",
            [$corte, $corte, $desde, $hasta]
        );
        $mapa = [];
        foreach ($filas as $fila) {
            $idCelula = (int)($fila['Id_Celula'] ?? 0);
            $idPersona = (int)($fila['Id_Persona'] ?? 0);
            if ($idCelula <= 0 || $idPersona <= 0) {
                continue;
            }
            $s1 = (int)($fila['s1'] ?? 0);
            $s2 = (int)($fila['s2'] ?? 0);
            $mapa[$idCelula][$idPersona] = [
                's1' => $s1,
                's2' => $s2,
                'total' => $s1 + $s2,
            ];
        }
        return $mapa;
    }

    private function fechaEnRango(string $fecha, string $desde, string $hasta): bool {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) === 1 && $fecha >= $desde && $fecha <= $hasta;
    }

    private function semestreDeFecha(string $fecha, int $anio): int {
        if (substr($fecha, 0, 4) !== sprintf('%04d', $anio)) {
            return 0;
        }
        $mes = (int)substr($fecha, 5, 2);
        if ($mes >= 1 && $mes <= 6) {
            return 1;
        }
        if ($mes >= 7 && $mes <= 12) {
            return 2;
        }
        return 0;
    }

    /**
     * Cuánto aporta una persona al total del año: semestre 1 más semestre 2.
     * Si fue a encuentro y no hay fecha del semestre, cuenta una vez cuando entró este año.
     *
     * @param array<string, mixed> $marca
     * @return array{s1:int,s2:int,total:int}
     */
    private function creditoEncuentroAnio(array $marca, string $fechaRegistro, int $anio): array {
        $s1 = !empty($marca['s1']) ? 1 : 0;
        $s2 = !empty($marca['s2']) ? 1 : 0;
        $extra = 0;
        if ($s1 === 0 && $s2 === 0 && !empty($marca['un_dia']) && (int)substr($fechaRegistro, 0, 4) === $anio) {
            $extra = 1;
        }
        return [
            's1' => $s1,
            's2' => $s2,
            'total' => $s1 + $s2 + $extra,
        ];
    }

    /**
     * El líder que más personas llevó a encuentro en lo que va del año.
     * Se suman los dos semestres. Se mira el líder de cada persona, no la célula.
     *
     * @param array<int, array<string, mixed>> $progreso
     * @return list<array<string, mixed>>
     */
    private function rankingPastores(int $anio, array $progreso): array {
        $filas = $this->query(
            "SELECT p.Id_Persona, p.Id_Lider, p.Fecha_Registro,
                    TRIM(CONCAT(COALESCE(l.Nombre, ''), ' ', COALESCE(l.Apellido, ''))) AS Nombre_Lider
             FROM persona p
             INNER JOIN persona l ON l.Id_Persona = p.Id_Lider
             WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL OR p.Estado_Cuenta = '')
               AND p.Id_Lider IS NOT NULL AND p.Id_Lider > 0
               AND p.Id_Persona <> p.Id_Lider"
        );

        $lideres = [];
        foreach ($filas as $fila) {
            $idPersona = (int)($fila['Id_Persona'] ?? 0);
            $idLider = (int)($fila['Id_Lider'] ?? 0);
            $nombre = trim((string)($fila['Nombre_Lider'] ?? ''));
            $marca = $progreso[$idPersona] ?? null;
            if ($idLider <= 0 || $nombre === '' || !is_array($marca)) {
                continue;
            }
            $credito = $this->creditoEncuentroAnio($marca, substr((string)($fila['Fecha_Registro'] ?? ''), 0, 10), $anio);
            if ($credito['total'] <= 0) {
                continue;
            }
            if (!isset($lideres[$idLider])) {
                $lideres[$idLider] = [
                    'id_persona' => $idLider,
                    'id_celula' => 0,
                    'nombre' => $nombre,
                    'rol' => 'Líder',
                    'encuentro' => 0,
                    's1' => 0,
                    's2' => 0,
                    'frecuentes' => 0,
                    'asistencias' => 0,
                    'apto' => 1,
                    'puntaje' => 0,
                ];
            }
            $lideres[$idLider]['s1'] += $credito['s1'];
            $lideres[$idLider]['s2'] += $credito['s2'];
            $lideres[$idLider]['encuentro'] += $credito['total'];
            $lideres[$idLider]['frecuentes'] += $credito['total'];
        }

        $lista = [];
        foreach ($lideres as $lider) {
            $lider['puntaje'] = (int)$lider['encuentro'];
            $lider['asistencias'] = (int)$lider['encuentro'];
            $lider['detalle_pastor'] = $lider['encuentro'] . ' en lo que va de ' . $anio
                . ' · ' . $lider['s1'] . ' del primer semestre + ' . $lider['s2'] . ' del segundo';
            $lista[] = $lider;
        }
        usort($lista, static function (array $a, array $b): int {
            return ((int)$b['encuentro']) <=> ((int)$a['encuentro']);
        });
        return $lista;
    }

    /**
     * @param array<int, array<string, mixed>> $lideres
     */
    private function sumarLiderPremio(array &$lideres, int $id, string $nombre, string $rol, int $idCelula, string $nombreCelula): void {
        if ($id <= 0 || $nombre === '') {
            return;
        }
        if (!isset($lideres[$id])) {
            $lideres[$id] = [
                'id_persona' => $id,
                'id_celula' => 0,
                'nombre' => $nombre,
                'rol' => $rol === '12' ? 'Líder de 12' : 'Líder de célula',
                'celulas' => [],
                'celulas_id' => [],
                's1' => 0,
                's2' => 0,
                'frecuentes' => 0,
                'asistencias' => 0,
                'apto' => 0,
                'puntaje' => 0,
            ];
        } elseif ($rol === '12' && $lideres[$id]['rol'] === 'Líder de célula') {
            $lideres[$id]['rol'] = 'Líder de 12 y de célula';
        } elseif ($rol === 'célula' && $lideres[$id]['rol'] === 'Líder de 12') {
            $lideres[$id]['rol'] = 'Líder de 12 y de célula';
        }
        if ($idCelula > 0) {
            $lideres[$id]['celulas_id'][$idCelula] = true;
        }
        if ($nombreCelula !== '' && !in_array($nombreCelula, $lideres[$id]['celulas'], true)) {
            $lideres[$id]['celulas'][] = $nombreCelula;
        }
    }

    /**
     * Líder nuevo: abrió su célula en uno de los dos semestres de este año, la célula sigue activa y llevó gente a encuentro.
     * No entra quien ya lideraba una célula de un año anterior.
     *
     * @param list<array<string, mixed>> $celulas
     * @param array<int, array<string, mixed>> $progreso
     * @param array<int, array<int, array{s1:int,s2:int,total:int}>> $asistencia
     * @return list<array<string, mixed>>
     */
    private function rankingRevelacion(int $anio, string $desde, string $hasta, array $celulas, array $progreso, array $asistencia): array {
        $porLider = [];
        foreach ($celulas as $celula) {
            $idLider = (int)($celula['Id_Lider'] ?? 0);
            $nombre = trim((string)($celula['Nombre_Lider'] ?? ''));
            $idCelula = (int)($celula['Id_Celula'] ?? 0);
            if ($idLider <= 0 || $nombre === '' || $idCelula <= 0) {
                continue;
            }
            if (!isset($porLider[$idLider])) {
                $porLider[$idLider] = [
                    'id_persona' => $idLider,
                    'id_celula' => $idCelula,
                    'nombre' => $nombre,
                    'celulas' => [],
                    'celulas_id' => [],
                    'abrio_este_anio' => 0,
                    'ya_lideraba' => 0,
                    'semestre' => 0,
                    'nuevas' => 0,
                    'crecimiento' => 0,
                    's1' => 0,
                    's2' => 0,
                    'cap' => 0,
                    'asistencias' => 0,
                    'puntaje' => 0,
                ];
            }
            $porLider[$idLider]['celulas_id'][$idCelula] = true;
            $nombreCelula = trim((string)($celula['Nombre_Celula'] ?? ''));
            if ($nombreCelula !== '') {
                $porLider[$idLider]['celulas'][] = $nombreCelula;
            }
            $apertura = substr(trim((string)($celula['Fecha_Apertura'] ?? '')), 0, 10);
            if ($apertura === '0000-00-00') {
                $apertura = '';
            }
            $semestre = $this->semestreDeFecha($apertura, $anio);
            if ($semestre === 1 || $semestre === 2) {
                $porLider[$idLider]['abrio_este_anio'] = 1;
                if ((int)$porLider[$idLider]['semestre'] === 0 || $semestre < (int)$porLider[$idLider]['semestre']) {
                    $porLider[$idLider]['semestre'] = $semestre;
                    $porLider[$idLider]['id_celula'] = $idCelula;
                }
            }
            $anioApertura = (int)substr($apertura, 0, 4);
            if ($anioApertura > 0 && $anioApertura < $anio) {
                $porLider[$idLider]['ya_lideraba'] = 1;
            }
        }

        $esLiderDe12 = [];
        foreach ($celulas as $celula) {
            $idDoce = (int)($celula['Id_Lider_Inmediato'] ?? 0);
            if ($idDoce > 0) {
                $esLiderDe12[$idDoce] = true;
            }
        }
        $lideres = [];
        foreach ($porLider as $idLider => $lider) {
            if (count($lider['celulas_id']) !== 1 || isset($esLiderDe12[$idLider])) {
                continue;
            }
            $lideres[$idLider] = $lider;
        }
        if ($lideres === []) {
            return [];
        }

        $idsCelulas = [];
        foreach ($lideres as $lider) {
            foreach (array_keys($lider['celulas_id']) as $idCelula) {
                $idsCelulas[(int)$idCelula] = (int)$idCelula;
            }
        }
        foreach ($this->miembrosDeCelulas(array_values($idsCelulas)) as $persona) {
            $idCelula = (int)($persona['Id_Celula'] ?? 0);
            $idPersona = (int)($persona['Id_Persona'] ?? 0);
            if ($idCelula <= 0 || $idPersona <= 0) {
                continue;
            }
            foreach ($lideres as $idLider => $lider) {
                if (!isset($lider['celulas_id'][$idCelula]) || $idPersona === (int)$lider['id_persona']) {
                    continue;
                }
                $veces = (int)($asistencia[$idCelula][$idPersona]['total'] ?? 0);
                $lideres[$idLider]['asistencias'] += $veces;
                $marca = $progreso[$idPersona] ?? null;
                if (!is_array($marca)) {
                    continue;
                }
                $credito = $this->creditoEncuentroAnio(
                    $marca,
                    substr((string)($persona['Fecha_Registro'] ?? ''), 0, 10),
                    $anio
                );
                if ($credito['total'] <= 0) {
                    continue;
                }
                $lideres[$idLider]['s1'] += $credito['s1'];
                $lideres[$idLider]['s2'] += $credito['s2'];
                $lideres[$idLider]['crecimiento'] += $credito['total'];
            }
        }

        $lista = [];
        foreach ($lideres as $lider) {
            unset($lider['celulas_id']);
            if ((int)$lider['crecimiento'] <= 0) {
                continue;
            }
            $lider['puntaje'] = (int)$lider['crecimiento'];
            $lider['detalle_revelacion'] = 'Líder nuevo con una sola célula'
                . ' · ' . $lider['crecimiento'] . ' en lo que va de ' . $anio
                . ' · ' . $lider['s1'] . ' del primer semestre + ' . $lider['s2'] . ' del segundo';
            $lista[] = $lider;
        }
        usort($lista, static function (array $a, array $b): int {
            $cmp = ((int)$b['crecimiento']) <=> ((int)$a['crecimiento']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return ((int)$b['asistencias']) <=> ((int)$a['asistencias']);
        });
        return $lista;
    }

    private function columnaExiste(string $tabla, string $columna): bool {
        $clave = $tabla . '.' . $columna;
        if (array_key_exists($clave, $this->columnas)) {
            return $this->columnas[$clave];
        }
        if (!preg_match('/^[a-z_]+$/', $tabla) || !preg_match('/^[A-Za-z0-9_]+$/', $columna)) {
            $this->columnas[$clave] = false;
            return false;
        }
        $filas = $this->query("SHOW COLUMNS FROM {$tabla} LIKE ?", [$columna]);
        $this->columnas[$clave] = $filas !== [];
        return $this->columnas[$clave];
    }

    public function telefonoYaVoto(int $anio, string $telefono): bool {
        $filas = $this->query(
            "SELECT Id_Voto FROM talleres_premiacion_voto WHERE Anio = ? AND Telefono = ? LIMIT 1",
            [$anio, $telefono]
        );
        return $filas !== [];
    }

    /**
     * @param array<string, string> $seleccion clave premio => nombre
     */
    public function registrarVoto(int $anio, string $nombre, string $telefono, array $seleccion, string $ip): void {
        $nombre = trim(preg_replace('/\s+/u', ' ', $nombre) ?? '');
        if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 120) {
            throw new InvalidArgumentException('Escribe tu nombre completo.');
        }
        $telefono = self::normalizarTelefono($telefono);
        if (!preg_match('/^3\d{9}$/', $telefono)) {
            throw new InvalidArgumentException('Escribe un celular de 10 dígitos.');
        }
        if ($this->telefonoYaVoto($anio, $telefono)) {
            throw new RuntimeException('ya_voto');
        }

        $limpio = [];
        foreach (self::categoriasVotacion() as $categoria) {
            $libre = (string)($categoria['origen'] ?? '') === 'libre';
            $permitidos = array_fill_keys(self::nombresDeCategoria($categoria), true);
            foreach ($categoria['premios'] as $premio) {
                $clave = (string)$premio['clave'];
                $elegido = trim((string)($seleccion[$clave] ?? ''));
                if ($libre) {
                    $elegido = self::normalizarNombreLibre($elegido);
                    if (!self::esNombreYApellido($elegido)) {
                        throw new InvalidArgumentException('En «' . $premio['titulo'] . '» escribe nombre y apellido.');
                    }
                    $limpio[$clave] = $elegido;
                    continue;
                }
                if ($elegido === '' || !isset($permitidos[$elegido])) {
                    throw new InvalidArgumentException('Elige una persona en «' . $premio['titulo'] . '».');
                }
                $limpio[$clave] = $elegido;
            }
        }
        $json = json_encode($limpio, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('No se pudo guardar el voto.');
        }

        try {
            $this->execute(
                "INSERT INTO talleres_premiacion_voto (Anio, Telefono, Nombre_Votante, Votos_JSON, Ip_Origen)
                 VALUES (?, ?, ?, ?, ?)",
                [$anio, $telefono, $nombre, $json, $ip !== '' ? mb_substr($ip, 0, 45) : null]
            );
        } catch (PDOException $e) {
            $codigo = (string)($e->errorInfo[1] ?? '');
            if ($codigo === '1062' || str_contains($e->getMessage(), 'uq_premiacion_voto_tel')) {
                throw new RuntimeException('ya_voto');
            }
            throw $e;
        }
    }

    public function contarVotos(int $anio): int {
        $filas = $this->query(
            "SELECT COUNT(*) AS total FROM talleres_premiacion_voto WHERE Anio = ?",
            [$anio]
        );
        return (int)($filas[0]['total'] ?? 0);
    }

    /**
     * @return list<array{nombre: string, telefono: string, fecha: string}>
     */
    public function listarVotantes(int $anio): array {
        $filas = $this->query(
            "SELECT Nombre_Votante, Telefono, Fecha_Creacion
             FROM talleres_premiacion_voto
             WHERE Anio = ?
             ORDER BY Fecha_Creacion DESC, Id_Voto DESC",
            [$anio]
        );
        $lista = [];
        foreach ($filas as $fila) {
            $lista[] = [
                'nombre' => (string)($fila['Nombre_Votante'] ?? ''),
                'telefono' => (string)($fila['Telefono'] ?? ''),
                'fecha' => (string)($fila['Fecha_Creacion'] ?? ''),
            ];
        }
        return $lista;
    }

    /**
     * Conteo por premio, incluyendo nominados en cero. Ordenado por votos.
     *
     * @return array<string, list<array{nombre: string, votos: int}>>
     */
    public function resultadosVotos(int $anio): array {
        $filas = $this->query(
            "SELECT Votos_JSON FROM talleres_premiacion_voto WHERE Anio = ?",
            [$anio]
        );
        $conteo = [];
        foreach ($filas as $fila) {
            $votos = json_decode((string)($fila['Votos_JSON'] ?? ''), true);
            if (!is_array($votos)) {
                continue;
            }
            foreach ($votos as $clave => $nombre) {
                $nombre = trim((string)$nombre);
                $clave = (string)$clave;
                if ($nombre === '' || $clave === '') {
                    continue;
                }
                $conteo[$clave][$nombre] = (int)($conteo[$clave][$nombre] ?? 0) + 1;
            }
        }

        $salida = [];
        foreach (self::categoriasVotacion() as $categoria) {
            foreach ($categoria['premios'] as $premio) {
                $clave = (string)$premio['clave'];
                $mapa = [];
                foreach (self::nombresDeCategoria($categoria) as $nombre) {
                    $mapa[$nombre] = (int)($conteo[$clave][$nombre] ?? 0);
                }
                foreach ($conteo[$clave] ?? [] as $nombre => $votosNombre) {
                    $mapa[(string)$nombre] = (int)$votosNombre;
                }
                $lista = [];
                foreach ($mapa as $nombre => $votosNombre) {
                    $lista[] = [
                        'nombre' => (string)$nombre,
                        'votos' => (int)$votosNombre,
                    ];
                }
                usort($lista, static function (array $a, array $b): int {
                    $cmp = ((int)$b['votos']) <=> ((int)$a['votos']);
                    if ($cmp !== 0) {
                        return $cmp;
                    }
                    return strcmp((string)$a['nombre'], (string)$b['nombre']);
                });
                $salida[$clave] = $lista;
            }
        }
        return $salida;
    }
}
