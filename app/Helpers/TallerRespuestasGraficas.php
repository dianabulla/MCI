<?php
/**
 * Agrega respuestas de formularios de talleres para gráficas.
 */

require_once APP . '/Models/TallerFormulario.php';

class TallerRespuestasGraficas {
    /** @var string[] */
    private const COLORES = [
        '#2563eb', '#7c3aed', '#059669', '#d97706', '#dc2626',
        '#0891b2', '#4f46e5', '#be185d', '#65a30d', '#ea580c',
    ];

    /**
     * @param array<int, array<string, mixed>> $secciones
     * @param array<int, array<string, mixed>> $respuestasRaw Filas BD con Datos_JSON
     * @return array<string, mixed>
     */
    public function construir(array $secciones, array $respuestasRaw): array {
        $jsonFilas = [];

        foreach ($respuestasRaw as $fila) {
            $json = json_decode((string)($fila['Datos_JSON'] ?? '{}'), true);
            if (!is_array($json)) {
                $json = [];
            }
            $jsonFilas[] = $json;
        }

        $total = count($jsonFilas);
        $graficas = [];

        if ($total > 0) {
            $graficas[] = $this->graficaPersonaEdad($jsonFilas);
            $graficas[] = $this->graficaPersonaEstadoCivil($jsonFilas);
        }

        $tablaHijos = null;
        foreach ($secciones as $seccion) {
            $campos = is_array($seccion['campos'] ?? null) ? $seccion['campos'] : [];
            foreach ($campos as $campo) {
                $tipo = strtolower((string)($campo['tipo'] ?? 'text'));
                $clave = (string)($campo['clave'] ?? '');
                $etiqueta = (string)($campo['etiqueta'] ?? $clave);
                if ($clave === '') {
                    continue;
                }

                if ($tipo === 'tabla') {
                    $cols = is_array($campo['columnas'] ?? null) ? $campo['columnas'] : [];
                    if ($this->esTablaHijos($etiqueta, $cols)) {
                        $tablaHijos = $clave;
                    }
                    foreach ($this->graficasDesdeTabla($clave, $etiqueta, $cols, $jsonFilas) as $g) {
                        $graficas[] = $g;
                    }
                    continue;
                }

                if (in_array($tipo, ['radio', 'select'], true)) {
                    $opciones = is_array($campo['opciones'] ?? null) ? $campo['opciones'] : [];
                    $graficas[] = $this->graficaOpcionUnica($clave, $etiqueta, $opciones, $jsonFilas);
                    continue;
                }

                if ($tipo === 'checkbox') {
                    $opciones = is_array($campo['opciones'] ?? null) ? $campo['opciones'] : [];
                    $graficas[] = $this->graficaCheckbox($clave, $etiqueta, $opciones, $jsonFilas);
                    continue;
                }

                if ($tipo === 'number') {
                    $graficas[] = $this->graficaNumero($clave, $etiqueta, $jsonFilas);
                    continue;
                }

                if (($tipo === 'text' || $tipo === 'textarea')
                    && !$this->esCampoIdentidadAbierta($clave, $etiqueta)) {
                    $graficaAbierta = $this->graficaRespuestasAbiertas($clave, $etiqueta, $jsonFilas);
                    if ($graficaAbierta !== null) {
                        $graficas[] = $graficaAbierta;
                    }
                }
            }
        }

        $graficas = array_values(array_filter($graficas, static function ($g) {
            return is_array($g) && !empty($g['labels']) && array_sum($g['data'] ?? []) > 0;
        }));

        return [
            'total' => $total,
            'graficas' => $graficas,
            'total_hijos_tabla' => $tablaHijos !== null ? $this->contarFilasTabla($tablaHijos, $jsonFilas) : 0,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $jsonFilas
     * @return array<string, mixed>
     */
    private function graficaPersonaEdad(array $jsonFilas): array {
        $buckets = [
            'Menores de 18' => 0,
            '18–29' => 0,
            '30–44' => 0,
            '45–59' => 0,
            '60 o más' => 0,
            'Sin dato' => 0,
        ];

        foreach ($jsonFilas as $json) {
            $edad = (int)($json['persona_edad'] ?? 0);
            if ($edad <= 0) {
                $buckets['Sin dato']++;
            } elseif ($edad < 18) {
                $buckets['Menores de 18']++;
            } elseif ($edad <= 29) {
                $buckets['18–29']++;
            } elseif ($edad <= 44) {
                $buckets['30–44']++;
            } elseif ($edad <= 59) {
                $buckets['45–59']++;
            } else {
                $buckets['60 o más']++;
            }
        }

        return [
            'id' => 'taller_chart_edad',
            'titulo' => 'Edad de los participantes',
            'tipo' => 'doughnut',
            'labels' => array_keys($buckets),
            'data' => array_values($buckets),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $jsonFilas
     * @return array<string, mixed>
     */
    private function graficaPersonaEstadoCivil(array $jsonFilas): array {
        $conteo = [];
        foreach (TallerFormulario::ESTADOS_CIVILES as $ec) {
            $conteo[$ec] = 0;
        }
        $conteo['Sin dato'] = 0;

        foreach ($jsonFilas as $json) {
            $valor = trim((string)($json['persona_estado_civil'] ?? ''));
            if ($valor === '') {
                $extra = is_array($json['_persona_extra'] ?? null) ? $json['_persona_extra'] : [];
                $valor = trim((string)($extra['estado_civil'] ?? ''));
            }
            if ($valor === '' || !isset($conteo[$valor])) {
                $conteo['Sin dato']++;
            } else {
                $conteo[$valor]++;
            }
        }

        return [
            'id' => 'taller_chart_estado_civil',
            'titulo' => 'Estado civil',
            'tipo' => 'doughnut',
            'labels' => array_keys($conteo),
            'data' => array_values($conteo),
        ];
    }

    /**
     * @param array<int, string> $opciones
     * @param array<int, array<string, mixed>> $jsonFilas
     * @return array<string, mixed>
     */
    private function graficaOpcionUnica(string $clave, string $etiqueta, array $opciones, array $jsonFilas): array {
        $conteo = [];
        foreach ($opciones as $opt) {
            $conteo[$opt] = 0;
        }
        $conteo['Otro / no listado'] = 0;
        $conteo['Sin respuesta'] = 0;

        foreach ($jsonFilas as $json) {
            $valor = trim((string)($json[$clave] ?? ''));
            if ($valor === '') {
                $conteo['Sin respuesta']++;
            } elseif (isset($conteo[$valor])) {
                $conteo[$valor]++;
            } else {
                $conteo['Otro / no listado']++;
            }
        }

        return [
            'id' => $this->idGrafica($clave),
            'titulo' => $etiqueta,
            'tipo' => 'bar',
            'labels' => array_keys($conteo),
            'data' => array_values($conteo),
        ];
    }

    /**
     * @param array<int, string> $opciones
     * @param array<int, array<string, mixed>> $jsonFilas
     * @return array<string, mixed>
     */
    private function graficaCheckbox(string $clave, string $etiqueta, array $opciones, array $jsonFilas): array {
        $conteo = [];
        foreach ($opciones as $opt) {
            $conteo[$opt] = 0;
        }

        foreach ($jsonFilas as $json) {
            $valor = $json[$clave] ?? [];
            if (!is_array($valor)) {
                $valor = $valor !== '' && $valor !== null ? [trim((string)$valor)] : [];
            }
            $marcados = array_map('trim', array_map('strval', $valor));
            foreach ($opciones as $opt) {
                if (in_array($opt, $marcados, true)) {
                    $conteo[$opt]++;
                }
            }
        }

        $conteo = array_filter($conteo, static fn(int $n): bool => $n > 0);
        arsort($conteo);

        return [
            'id' => $this->idGrafica($clave),
            'titulo' => $etiqueta,
            'tipo' => 'bar',
            'horizontal' => true,
            'labels' => array_keys($conteo),
            'data' => array_values($conteo),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $jsonFilas
     * @return array<string, mixed>
     */
    private function graficaNumero(string $clave, string $etiqueta, array $jsonFilas): array {
        $conteo = [];
        $sinDato = 0;

        foreach ($jsonFilas as $json) {
            $raw = trim((string)($json[$clave] ?? ''));
            if ($raw === '' || !is_numeric($raw)) {
                $sinDato++;
                continue;
            }
            $n = (int)$raw;
            $key = (string)$n;
            $conteo[$key] = ($conteo[$key] ?? 0) + 1;
        }

        if ($sinDato > 0) {
            $conteo['Sin dato'] = $sinDato;
        }

        ksort($conteo, SORT_NATURAL);

        return [
            'id' => $this->idGrafica($clave),
            'titulo' => $etiqueta,
            'tipo' => 'bar',
            'labels' => array_keys($conteo),
            'data' => array_values($conteo),
        ];
    }

    /**
     * Tabula texto libre y agrupa frases que significan lo mismo.
     *
     * @param array<int, array<string, mixed>> $jsonFilas
     * @return array<string, mixed>|null
     */
    private function graficaRespuestasAbiertas(string $clave, string $etiqueta, array $jsonFilas): ?array {
        $textos = $this->extraerTextosCampoAbierto($clave, $jsonFilas);
        if ($textos === []) {
            return null;
        }

        $usarTemas = $this->esPreguntaExpectativa($clave, $etiqueta);
        $grupos = $this->agruparTextosSimilares($textos, $usarTemas);
        if ($grupos === []) {
            return null;
        }

        $labels = [];
        $data = [];
        $filas = [];
        foreach ($grupos as $grupo) {
            $labels[] = (string)$grupo['label'];
            $data[] = (int)$grupo['count'];
            $variantes = [];
            foreach ($grupo['variantes'] as $frase => $n) {
                $variantes[] = $n > 1 ? $frase . ' (' . $n . ')' : $frase;
            }
            $filas[] = [
                'grupo' => (string)$grupo['label'],
                'total' => (int)$grupo['count'],
                'variantes' => $variantes,
            ];
        }

        $labelsChart = array_slice($labels, 0, 12);
        $dataChart = array_slice($data, 0, 12);

        return [
            'id' => $this->idGrafica($clave),
            'titulo' => $etiqueta,
            'tipo' => 'abierta',
            'horizontal' => true,
            'labels' => $labelsChart,
            'data' => $dataChart,
            'filas' => $filas,
            'total_respuestas' => count($textos),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $jsonFilas
     * @return list<string>
     */
    private function extraerTextosCampoAbierto(string $clave, array $jsonFilas): array {
        $out = [];
        foreach ($jsonFilas as $json) {
            $raw = $json[$clave] ?? '';
            if (is_array($raw)) {
                foreach ($raw as $item) {
                    $t = trim((string)$item);
                    if ($t !== '') {
                        $out[] = $t;
                    }
                }
                continue;
            }
            $t = trim((string)$raw);
            if ($t !== '') {
                $out[] = $t;
            }
        }
        return $out;
    }

    private function esCampoIdentidadAbierta(string $clave, string $etiqueta): bool {
        $n = $this->sinAcentos(mb_strtolower($clave . ' ' . $etiqueta, 'UTF-8'));
        $patrones = [
            'nombre', 'apellido', 'documento', 'cedula', 'identidad', 'telefono',
            'celular', 'whatsapp', 'correo', 'email', 'direccion', 'firma',
            'fecha de nacimiento', 'fecha_nacimiento',
        ];
        foreach ($patrones as $p) {
            if (str_contains($n, $p)) {
                return true;
            }
        }
        return false;
    }

    private function esPreguntaExpectativa(string $clave, string $etiqueta): bool {
        $n = $this->sinAcentos(mb_strtolower($clave . ' ' . $etiqueta, 'UTF-8'));
        return str_contains($n, 'espera')
            || str_contains($n, 'expectativ')
            || str_contains($n, 'motivo')
            || str_contains($n, 'por que')
            || str_contains($n, 'que busca')
            || str_contains($n, 'que desea');
    }

    /**
     * @param list<string> $textos
     * @return list<array{label: string, count: int, variantes: array<string, int>, clave: string}>
     */
    private function agruparTextosSimilares(array $textos, bool $usarTemas): array {
        $grupos = [];
        foreach ($textos as $texto) {
            $norm = $this->normalizarTextoAbierto($texto);
            if ($norm === '') {
                continue;
            }
            $tema = $usarTemas ? $this->clasificarTemaAbierto($norm) : null;
            $idx = $this->indiceGrupoSimilar($grupos, $norm, $tema);
            if ($idx === null) {
                $grupos[] = [
                    'clave' => $norm,
                    'tema' => $tema,
                    'label' => $tema !== null ? $tema : $this->capitalizarEtiquetaAbierta($texto),
                    'count' => 1,
                    'variantes' => [$texto => 1],
                ];
            } else {
                $grupos[$idx]['count']++;
                $grupos[$idx]['variantes'][$texto] = ($grupos[$idx]['variantes'][$texto] ?? 0) + 1;
                if ($grupos[$idx]['tema'] === null && $tema !== null) {
                    $grupos[$idx]['tema'] = $tema;
                    $grupos[$idx]['label'] = $tema;
                }
            }
        }

        $grupos = $this->fusionarGruposCercanos($grupos);
        usort($grupos, static function (array $a, array $b): int {
            return ((int)$b['count'] <=> (int)$a['count']);
        });

        foreach ($grupos as &$grupo) {
            arsort($grupo['variantes']);
            if ($grupo['tema'] === null) {
                $masComun = (string)array_key_first($grupo['variantes']);
                $grupo['label'] = $this->capitalizarEtiquetaAbierta($masComun);
            }
        }
        unset($grupo);

        return $grupos;
    }

    /**
     * @param list<array<string, mixed>> $grupos
     */
    private function indiceGrupoSimilar(array $grupos, string $norm, ?string $tema): ?int {
        foreach ($grupos as $i => $grupo) {
            if ($tema !== null && ($grupo['tema'] ?? null) === $tema) {
                return $i;
            }
            $clave = (string)($grupo['clave'] ?? '');
            if ($clave === $norm) {
                return $i;
            }
            if ($this->textosAbiertosEquivalentes($clave, $norm)) {
                return $i;
            }
        }
        return null;
    }

    /**
     * @param list<array<string, mixed>> $grupos
     * @return list<array<string, mixed>>
     */
    private function fusionarGruposCercanos(array $grupos): array {
        $n = count($grupos);
        if ($n < 2) {
            return $grupos;
        }
        $usado = array_fill(0, $n, false);
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            if ($usado[$i]) {
                continue;
            }
            $base = $grupos[$i];
            for ($j = $i + 1; $j < $n; $j++) {
                if ($usado[$j]) {
                    continue;
                }
                $temaA = $base['tema'] ?? null;
                $temaB = $grupos[$j]['tema'] ?? null;
                $mismoTema = $temaA !== null && $temaA === $temaB;
                if ($mismoTema || $this->textosAbiertosEquivalentes((string)$base['clave'], (string)$grupos[$j]['clave'])) {
                    $base['count'] += (int)$grupos[$j]['count'];
                    foreach ($grupos[$j]['variantes'] as $frase => $cant) {
                        $base['variantes'][$frase] = ($base['variantes'][$frase] ?? 0) + (int)$cant;
                    }
                    if (mb_strlen((string)$grupos[$j]['clave'], 'UTF-8') < mb_strlen((string)$base['clave'], 'UTF-8')) {
                        $base['clave'] = $grupos[$j]['clave'];
                    }
                    $usado[$j] = true;
                }
            }
            $out[] = $base;
        }
        return $out;
    }

    private function textosAbiertosEquivalentes(string $a, string $b): bool {
        if ($a === '' || $b === '') {
            return false;
        }
        if ($a === $b) {
            return true;
        }
        if (str_contains($a, $b) || str_contains($b, $a)) {
            $corta = mb_strlen($a, 'UTF-8') <= mb_strlen($b, 'UTF-8') ? $a : $b;
            return mb_strlen($corta, 'UTF-8') >= 8;
        }
        $ta = $this->tokensAbiertos($a);
        $tb = $this->tokensAbiertos($b);
        if ($ta === [] || $tb === []) {
            return $this->distanciaRelativa($a, $b) <= 0.22;
        }
        $inter = count(array_intersect($ta, $tb));
        $union = count(array_unique(array_merge($ta, $tb)));
        if ($union > 0 && ($inter / $union) >= 0.5) {
            return true;
        }
        if ($inter >= 2 && (count(array_diff($ta, $tb)) === 0 || count(array_diff($tb, $ta)) === 0)) {
            return true;
        }
        return $this->distanciaRelativa($a, $b) <= 0.2;
    }

    /**
     * @return list<string>
     */
    private function tokensAbiertos(string $norm): array {
        $stop = [
            'el', 'la', 'los', 'las', 'de', 'del', 'un', 'una', 'unos', 'unas', 'y', 'o', 'u',
            'que', 'este', 'esta', 'estos', 'estas', 'taller', 'espero', 'espera', 'esperamos',
            'me', 'mi', 'mis', 'a', 'en', 'para', 'con', 'por', 'al', 'lo', 'como', 'mas',
            'muy', 'tambien', 'quiero', 'quisiera', 'deseo', 'poder', 'algo', 'alguna',
            'su', 'sus', 'se', 'es', 'son', 'ser', 'hay', 'sobre', 'hacia', 'desde',
            'entre', 'sin', 'si', 'no', 'ya', 'solo', 'pues', 'porque', 'este',
        ];
        $parts = preg_split('/\s+/', $norm) ?: [];
        $out = [];
        foreach ($parts as $p) {
            if ($p === '' || in_array($p, $stop, true) || mb_strlen($p, 'UTF-8') < 3) {
                continue;
            }
            $out[] = $p;
        }
        return array_values(array_unique($out));
    }

    private function distanciaRelativa(string $a, string $b): float {
        $a = mb_substr($a, 0, 48, 'UTF-8');
        $b = mb_substr($b, 0, 48, 'UTF-8');
        $max = max(mb_strlen($a, 'UTF-8'), mb_strlen($b, 'UTF-8'), 1);
        if (!function_exists('levenshtein')) {
            return $a === $b ? 0.0 : 1.0;
        }
        return levenshtein($a, $b) / $max;
    }

    private function clasificarTemaAbierto(string $norm): ?string {
        $temas = [
            'Aprender más de Dios / la Palabra' => [
                'conocer a dios', 'mas de dios', 'palabra de dios', 'la palabra', 'biblia',
                'aprender', 'conocimiento', 'ensenanza', 'estudio biblico', 'estudio',
            ],
            'Crecimiento espiritual' => [
                'crecimiento espiritual', 'crecer espiritual', 'crecer', 'crecimiento',
                'madurar', 'fortalecer', 'fortaleza', 'mi fe', 'la fe', 'en fe',
            ],
            'Sanidad y restauración' => [
                'sanidad', 'sanar', 'restauracion', 'restaurar', 'libertad', 'sanacion',
            ],
            'Comunión y nuevas amistades' => [
                'conocer personas', 'conocer gente', 'amistad', 'amigos', 'hermanos',
                'comunidad', 'comunion', 'compañia', 'integrarme',
            ],
            'Familia y matrimonio' => [
                'familia', 'matrimonio', 'hijos', 'esposo', 'esposa', 'hogar',
            ],
            'Servir / liderazgo' => [
                'servir', 'servicio', 'ministerio', 'liderazgo', 'lider',
            ],
            'Evangelizar / ganar almas' => [
                'evangel', 'almas', 'predicar', 'ganar almas',
            ],
            'Bendición y milagros' => [
                'bendicion', 'milagro', 'provision', 'favor de dios',
            ],
            'No indica / ninguna' => [
                'ninguna', 'ninguno', 'nada', 'no se',
            ],
        ];
        $mejor = null;
        $mejorPuntos = 0;
        foreach ($temas as $label => $claves) {
            $puntos = 0;
            foreach ($claves as $kw) {
                if (str_contains($norm, $kw)) {
                    $puntos += mb_strlen($kw, 'UTF-8');
                }
            }
            if ($puntos > $mejorPuntos) {
                $mejorPuntos = $puntos;
                $mejor = $label;
            }
        }
        return $mejorPuntos > 0 ? $mejor : null;
    }

    private function normalizarTextoAbierto(string $texto): string {
        $t = trim(mb_strtolower($texto, 'UTF-8'));
        $t = $this->sinAcentos($t);
        $t = str_replace(['ñ'], ['n'], $t);
        $t = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $t) ?? $t;
        $t = preg_replace('/\s+/', ' ', $t) ?? $t;
        return trim($t);
    }

    private function sinAcentos(string $texto): string {
        $map = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ü' => 'u',
            'ñ' => 'n', 'Ñ' => 'n',
        ];
        return strtr($texto, $map);
    }

    private function capitalizarEtiquetaAbierta(string $texto): string {
        $texto = trim(preg_replace('/\s+/', ' ', $texto) ?? $texto);
        if (mb_strlen($texto, 'UTF-8') > 80) {
            return rtrim(mb_substr($texto, 0, 77, 'UTF-8')) . '…';
        }
        return mb_strtoupper(mb_substr($texto, 0, 1, 'UTF-8'), 'UTF-8')
            . mb_substr($texto, 1, mb_strlen($texto, 'UTF-8'), 'UTF-8');
    }

    /**
     * @param array<int, string> $columnas
     * @param array<int, array<string, mixed>> $jsonFilas
     * @return array<int, array<string, mixed>>
     */
    private function graficasDesdeTabla(string $clave, string $etiqueta, array $columnas, array $jsonFilas): array {
        $graficas = [];
        $filas = $this->extraerFilasTabla($clave, $jsonFilas);

        if ($filas === []) {
            return [];
        }

        $prefijoTitulo = $this->tituloCortoTabla($etiqueta);

        foreach ($columnas as $col) {
            if (!$this->columnaTablaEsGraficable($col)) {
                continue;
            }

            if ($this->esColumnaEdad($col)) {
                $grafica = $this->graficaTablaEdad($clave, $col, $prefijoTitulo, $filas);
            } elseif ($this->esColumnaSexo($col)) {
                $grafica = $this->graficaTablaSexo($clave, $col, $prefijoTitulo, $filas);
            } elseif ($this->esColumnaEscolaridad($col)) {
                $grafica = $this->graficaTablaEscolaridad($clave, $col, $prefijoTitulo, $filas);
            } else {
                $grafica = $this->graficaTablaGenerica($clave, $col, $prefijoTitulo, $filas);
            }

            if ($grafica !== null) {
                $graficas[] = $grafica;
            }
        }

        return $graficas;
    }

    /**
     * @param array<int, array<string, mixed>> $filas
     * @return array<string, mixed>|null
     */
    private function graficaTablaEdad(string $clave, string $col, string $prefijoTitulo, array $filas): ?array {
        $buckets = [
            '0–2 años' => 0,
            '3–5 años' => 0,
            '6–11 años' => 0,
            '12–17 años' => 0,
            '18 o más' => 0,
            'Sin dato' => 0,
        ];

        foreach ($filas as $fila) {
            $edad = $this->extraerEdadNumerica($this->valorCeldaTabla($fila, $col));
            if ($edad === null) {
                $buckets['Sin dato']++;
            } elseif ($edad <= 2) {
                $buckets['0–2 años']++;
            } elseif ($edad <= 5) {
                $buckets['3–5 años']++;
            } elseif ($edad <= 11) {
                $buckets['6–11 años']++;
            } elseif ($edad <= 17) {
                $buckets['12–17 años']++;
            } else {
                $buckets['18 o más']++;
            }
        }

        $labels = [];
        $data = [];
        foreach ($buckets as $label => $count) {
            if ($count > 0) {
                $labels[] = $label;
                $data[] = $count;
            }
        }

        if ($labels === []) {
            return null;
        }

        return [
            'id' => $this->idGrafica($clave . '_' . $col),
            'titulo' => $prefijoTitulo . ' — Rango de edad',
            'tipo' => 'doughnut',
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $filas
     * @return array<string, mixed>|null
     */
    private function graficaTablaSexo(string $clave, string $col, string $prefijoTitulo, array $filas): ?array {
        $conteo = [
            'Masculino' => 0,
            'Femenino' => 0,
            'Otro / no especificado' => 0,
        ];

        foreach ($filas as $fila) {
            $sexo = $this->normalizarSexo($this->valorCeldaTabla($fila, $col));
            if ($sexo === '') {
                continue;
            }
            if (!isset($conteo[$sexo])) {
                $conteo['Otro / no especificado']++;
            } else {
                $conteo[$sexo]++;
            }
        }

        $conteo = array_filter($conteo, static fn(int $n): bool => $n > 0);
        if ($conteo === []) {
            return null;
        }

        return [
            'id' => $this->idGrafica($clave . '_' . $col),
            'titulo' => $prefijoTitulo . ' — Sexo',
            'tipo' => 'doughnut',
            'labels' => array_keys($conteo),
            'data' => array_values($conteo),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $filas
     * @return array<string, mixed>|null
     */
    private function graficaTablaEscolaridad(string $clave, string $col, string $prefijoTitulo, array $filas): ?array {
        $buckets = [
            'Preescolar / Jardín' => 0,
            'Primaria' => 0,
            'Secundaria' => 0,
            'Bachillerato' => 0,
            'Universidad / Técnico' => 0,
            'Ninguno / No aplica' => 0,
            'Otro / No especificado' => 0,
        ];

        foreach ($filas as $fila) {
            $val = $this->valorCeldaTabla($fila, $col);
            if ($val === '') {
                continue;
            }
            $nivel = $this->normalizarEscolaridadNivel($val);
            $buckets[$nivel] = ($buckets[$nivel] ?? 0) + 1;
        }

        $labels = [];
        $data = [];
        foreach ($buckets as $label => $count) {
            if ($count > 0) {
                $labels[] = $label;
                $data[] = $count;
            }
        }

        if ($labels === []) {
            return null;
        }

        return [
            'id' => $this->idGrafica($clave . '_' . $col),
            'titulo' => $prefijoTitulo . ' — Nivel escolar',
            'tipo' => 'doughnut',
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $filas
     * @return array<string, mixed>|null
     */
    private function graficaTablaGenerica(string $clave, string $col, string $prefijoTitulo, array $filas): ?array {
        $conteo = [];
        foreach ($filas as $fila) {
            $val = $this->normalizarEtiqueta($this->valorCeldaTabla($fila, $col));
            if ($val === '') {
                continue;
            }
            $conteo[$val] = ($conteo[$val] ?? 0) + 1;
        }

        if ($conteo === []) {
            return null;
        }

        arsort($conteo);
        if (count($conteo) > 8) {
            $conteo = array_slice($conteo, 0, 8, true);
        }

        return [
            'id' => $this->idGrafica($clave . '_' . $col),
            'titulo' => $prefijoTitulo . ' — ' . $col,
            'tipo' => 'bar',
            'horizontal' => true,
            'labels' => array_keys($conteo),
            'data' => array_values($conteo),
        ];
    }

    private function tituloCortoTabla(string $etiqueta): string {
        $e = strtolower(trim($etiqueta));
        if (str_contains($e, 'hijo')) {
            return 'Hijos registrados';
        }
        return trim($etiqueta) !== '' ? $etiqueta : 'Tabla';
    }

    private function esColumnaEdad(string $col): bool {
        return str_contains(strtolower($col), 'edad');
    }

    private function esColumnaSexo(string $col): bool {
        $n = strtolower($col);
        return str_contains($n, 'sexo') || str_contains($n, 'género') || str_contains($n, 'genero');
    }

    private function esColumnaEscolaridad(string $col): bool {
        $n = strtolower($col);
        return str_contains($n, 'escolar') || str_contains($n, 'grado') || str_contains($n, 'nivel');
    }

    private function extraerEdadNumerica(string $valor): ?int {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }
        if (preg_match('/(\d{1,2})/', $valor, $m)) {
            $edad = (int)$m[1];
            return ($edad >= 0 && $edad <= 99) ? $edad : null;
        }
        return null;
    }

    private function normalizarSexo(string $valor): string {
        $v = strtolower(trim($valor));
        if ($v === '' || $v === 'b' || $v === '-') {
            return '';
        }

        if ($v === 'f' || $v === 'm' || $v === 'h') {
            return $v === 'f' ? 'Femenino' : 'Masculino';
        }

        $vSinAcentos = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n'],
            $v
        );

        if ($this->textoPareceFemenino($vSinAcentos)) {
            return 'Femenino';
        }
        if ($this->textoPareceMasculino($vSinAcentos)) {
            return 'Masculino';
        }

        return 'Otro / no especificado';
    }

    private function textoPareceFemenino(string $v): bool {
        $patrones = ['femen', 'mujer', 'nina', 'female', 'fem'];
        foreach ($patrones as $p) {
            if (str_contains($v, $p)) {
                return true;
            }
        }
        return $this->similitudTexto($v, 'femenino') <= 2;
    }

    private function textoPareceMasculino(string $v): bool {
        $patrones = ['masc', 'hombre', 'varon', 'nino', 'male'];
        foreach ($patrones as $p) {
            if (str_contains($v, $p)) {
                return true;
            }
        }
        return $this->similitudTexto($v, 'masculino') <= 2;
    }

    private function similitudTexto(string $a, string $b): int {
        if (function_exists('levenshtein')) {
            return levenshtein(substr($a, 0, 12), substr($b, 0, 12));
        }
        return $a === $b ? 0 : 99;
    }

    private function normalizarEscolaridadNivel(string $valor): string {
        $v = strtolower(trim($valor));
        $v = preg_replace('/\s+/', ' ', $v) ?? '';

        if ($v === '' || $v === 'no' || $v === 'na' || str_contains($v, 'no aplica') || str_contains($v, 'ninguno')) {
            return 'Ninguno / No aplica';
        }

        if (preg_match('/^\d{1,2}$/', $v)) {
            $grado = (int)$v;
            if ($grado <= 5) {
                return 'Primaria';
            }
            if ($grado <= 9) {
                return 'Secundaria';
            }
            return 'Bachillerato';
        }

        $v = str_replace(['á', 'é', 'í', 'ó', 'ú'], ['a', 'e', 'i', 'o', 'u'], $v);

        if (preg_match('/(pre\s*escolar|prescolar|jardin|kinder|maternal|infantil|transicion)/', $v)) {
            return 'Preescolar / Jardín';
        }
        if (preg_match('/(primaria|primero|segundo|tercero|cuarto|quinto|grado\s*[1-5]|arto\s*de\s*primaria|basica|basico)/', $v)) {
            return 'Primaria';
        }
        if (preg_match('/(secundaria|sexto|septimo|octavo|noveno|grado\s*[6-9]|estudiante|colegio)/', $v)) {
            return 'Secundaria';
        }
        if (preg_match('/(bachiller|bachillerato|decimo|once|media)/', $v)) {
            return 'Bachillerato';
        }
        if (preg_match('/(univers|universit|profesional|tecnolog|tecnico|niversit)/', $v)) {
            return 'Universidad / Técnico';
        }

        return 'Otro / No especificado';
    }

    private function normalizarEtiqueta(string $valor): string {
        $valor = trim($valor);
        if ($valor === '') {
            return '';
        }
        return mb_convert_case($valor, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * @param array<int, array<string, mixed>> $jsonFilas
     * @return array<int, array<string, mixed>>
     */
    private function extraerFilasTabla(string $clave, array $jsonFilas): array {
        $filas = [];
        foreach ($jsonFilas as $json) {
            $raw = $json[$clave] ?? [];
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                $raw = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($raw)) {
                continue;
            }
            foreach ($raw as $fila) {
                if (is_array($fila) && $this->filaTablaTieneDatos($fila)) {
                    $filas[] = $fila;
                }
            }
        }
        return $filas;
    }

    /**
     * @param array<string, mixed> $fila
     */
    private function filaTablaTieneDatos(array $fila): bool {
        foreach ($fila as $celda) {
            if (trim((string)$celda) !== '') {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<string, mixed> $fila
     */
    private function valorCeldaTabla(array $fila, string $col): string {
        if (isset($fila[$col])) {
            return trim((string)$fila[$col]);
        }
        $colKey = preg_replace('/[^a-z0-9_]/i', '_', strtolower($col)) ?? '';
        return trim((string)($fila[$colKey] ?? ''));
    }

    /**
     * Columnas de tabla que no deben graficarse (texto libre, identificadores).
     */
    private function columnaTablaEsGraficable(string $col): bool {
        $norm = strtolower(trim($col));
        $excluir = ['nombre', 'nombres', 'apellido', 'apellidos', 'observaciones', 'comentarios', 'otro', 'otros'];
        foreach ($excluir as $palabra) {
            if ($norm === $palabra || str_contains($norm, $palabra)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<int, string> $columnas
     */
    private function esTablaHijos(string $etiqueta, array $columnas): bool {
        $etiqueta = strtolower($etiqueta);
        if (strpos($etiqueta, 'hijo') !== false) {
            return true;
        }
        $cols = array_map('strtolower', $columnas);
        return in_array('edad', $cols, true) && in_array('sexo', $cols, true);
    }

    /**
     * @param array<int, array<string, mixed>> $jsonFilas
     */
    private function contarFilasTabla(string $clave, array $jsonFilas): int {
        return count($this->extraerFilasTabla($clave, $jsonFilas));
    }

    private function idGrafica(string $clave): string {
        $slug = preg_replace('/[^a-z0-9_]+/i', '_', strtolower($clave)) ?? 'campo';
        return 'taller_chart_' . trim($slug, '_');
    }
}
