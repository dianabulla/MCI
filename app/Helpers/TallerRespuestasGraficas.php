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
