<?php

require_once APP . '/Models/Asistencia.php';
require_once APP . '/Models/Celula.php';

class DiagnosticoReporteCelulas {

    /**
     * @return array{0:DateTimeImmutable,1:DateTimeImmutable,2:string,3:bool}
     */
    public static function resolverRangoSemana(string $semanaParam = '', bool $semanaAnteriorPorDefecto = true): array {
        $semanaParam = trim($semanaParam);

        if (preg_match('/^(\d{4})-W(\d{2})$/', $semanaParam, $m)) {
            $anio = (int)$m[1];
            $semana = (int)$m[2];
            if ($semana >= 1 && $semana <= 53) {
                $inicio = (new DateTimeImmutable('today'))->setISODate($anio, $semana, 1);
                $fin = $inicio->modify('+6 days');
                return [$inicio, $fin, $inicio->format('o-\\WW'), false];
            }
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $semanaParam)) {
            $inicio = new DateTimeImmutable($semanaParam);
            $fin = $inicio->modify('+6 days');
            return [$inicio, $fin, $inicio->format('o-\\WW'), false];
        }

        $hoy = new DateTimeImmutable('today');
        if ($semanaAnteriorPorDefecto) {
            $inicioSemanaActual = $hoy->modify('monday this week');
            $inicio = $inicioSemanaActual->modify('-7 days');
        } else {
            $inicio = $hoy->modify('monday this week');
        }
        $fin = $inicio->modify('+6 days');

        return [$inicio, $fin, $inicio->format('o-\\WW'), $semanaAnteriorPorDefecto];
    }

    /**
     * @return array<string, int>
     */
    public static function obtenerResumenSemana(string $fechaInicio, string $fechaFin): array {
        $asistenciaModel = new Asistencia();
        $celulaModel = new Celula();

        $asistenciaModel->ensureEntregaSobreTableExists();

        $totalCelulas = $celulaModel->query(
            "SELECT COUNT(*) AS total FROM celula WHERE COALESCE(Estado_Celula, 'Activa') NOT IN ('Inactiva', 'Cerrada', '0')"
        );
        $totalCelulasAct = (int)($totalCelulas[0]['total'] ?? 0);

        $reportaron = $asistenciaModel->query(
            "SELECT COUNT(DISTINCT Id_Celula) AS total
             FROM asistencia_celula
             WHERE Fecha_Asistencia BETWEEN ? AND ?
               AND Id_Celula IS NOT NULL
               AND Id_Celula > 0",
            [$fechaInicio, $fechaFin]
        );

        $totalRegistros = $asistenciaModel->query(
            "SELECT COUNT(*) AS total
             FROM asistencia_celula
             WHERE Fecha_Asistencia BETWEEN ? AND ?
               AND Id_Celula IS NOT NULL
               AND Id_Celula > 0",
            [$fechaInicio, $fechaFin]
        );

        $sinCelula = $asistenciaModel->query(
            "SELECT COUNT(*) AS total
             FROM asistencia_celula
             WHERE Fecha_Asistencia BETWEEN ? AND ?
               AND (Id_Celula IS NULL OR Id_Celula <= 0)",
            [$fechaInicio, $fechaFin]
        );

        $conSobre = $asistenciaModel->query(
            "SELECT COUNT(*) AS total
             FROM asistencia_entrega_sobre_semana
             WHERE Semana_Inicio = ?
               AND Entrego_Sobre = 1",
            [$fechaInicio]
        );

        $celulasReportaron = (int)($reportaron[0]['total'] ?? 0);

        return [
            'total_celulas_activas' => $totalCelulasAct,
            'celulas_reportaron' => $celulasReportaron,
            'celulas_sin_reporte' => max(0, $totalCelulasAct - $celulasReportaron),
            'total_registros_asistencia' => (int)($totalRegistros[0]['total'] ?? 0),
            'registros_sin_celula' => (int)($sinCelula[0]['total'] ?? 0),
            'celulas_entregaron_sobre' => (int)($conSobre[0]['total'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function obtenerDetalleCelulasSemana(string $fechaInicio, string $fechaFin): array {
        $asistenciaModel = new Asistencia();

        $sql = "SELECT
                    c.Id_Celula,
                    c.Nombre_Celula,
                    COALESCE(c.Estado_Celula, 'Activa') AS Estado_Celula,
                    TRIM(CONCAT(COALESCE(l.Nombre, ''), ' ', COALESCE(l.Apellido, ''))) AS Nombre_Lider,
                    COALESCE(m.Nombre_Ministerio, '') AS Nombre_Ministerio,
                    (SELECT COUNT(*)
                     FROM persona p
                     WHERE p.Id_Celula = c.Id_Celula
                       AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)) AS Total_Miembros,
                    COUNT(DISTINCT a.Fecha_Asistencia) AS Dias_Reportados,
                    COUNT(a.Id_Asistencia) AS Total_Registros,
                    SUM(CASE WHEN a.Asistio = 1 THEN 1 ELSE 0 END) AS Total_Asistieron,
                    MIN(a.Fecha_Asistencia) AS Primera_Fecha,
                    MAX(a.Fecha_Asistencia) AS Ultima_Fecha,
                    MAX(a.Observaciones) AS Ultima_Observacion,
                    COALESCE(es.Entrego_Sobre, 0) AS Entrego_Sobre
                FROM celula c
                LEFT JOIN persona l ON c.Id_Lider = l.Id_Persona
                LEFT JOIN ministerio m ON l.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN asistencia_celula a
                    ON a.Id_Celula = c.Id_Celula
                   AND a.Fecha_Asistencia BETWEEN ? AND ?
                LEFT JOIN asistencia_entrega_sobre_semana es
                    ON es.Id_Celula = c.Id_Celula
                   AND es.Semana_Inicio = ?
                WHERE COALESCE(c.Estado_Celula, 'Activa') NOT IN ('Inactiva', 'Cerrada', '0')
                GROUP BY c.Id_Celula, c.Nombre_Celula, c.Estado_Celula, Nombre_Lider, Nombre_Ministerio, es.Entrego_Sobre
                ORDER BY Total_Registros DESC, c.Nombre_Celula ASC";

        return $asistenciaModel->query($sql, [$fechaInicio, $fechaFin, $fechaInicio]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function obtenerRegistrosRecientes(string $fechaInicio, string $fechaFin, int $limite = 200): array {
        $asistenciaModel = new Asistencia();
        $limite = max(1, min(500, $limite));

        $sql = "SELECT
                    a.Id_Asistencia,
                    a.Id_Celula,
                    c.Nombre_Celula,
                    a.Id_Persona,
                    TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre_Persona,
                    a.Fecha_Asistencia,
                    a.Asistio,
                    a.Tema,
                    a.Tipo_Celula,
                    a.Observaciones
                FROM asistencia_celula a
                LEFT JOIN celula c ON a.Id_Celula = c.Id_Celula
                LEFT JOIN persona p ON a.Id_Persona = p.Id_Persona
                WHERE a.Fecha_Asistencia BETWEEN ? AND ?
                ORDER BY a.Fecha_Asistencia DESC, a.Id_Asistencia DESC
                LIMIT {$limite}";

        return $asistenciaModel->query($sql, [$fechaInicio, $fechaFin]);
    }

    /**
     * Registros guardados sin célula válida (Id_Celula vacío o 0).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function obtenerRegistrosSinCelula(string $fechaInicio, string $fechaFin, int $limite = 100): array {
        $asistenciaModel = new Asistencia();
        $limite = max(1, min(300, $limite));

        $sql = "SELECT
                    a.Id_Asistencia,
                    a.Id_Celula,
                    a.Id_Persona,
                    TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre_Persona,
                    a.Fecha_Asistencia,
                    a.Asistio,
                    a.Tema,
                    a.Observaciones
                FROM asistencia_celula a
                LEFT JOIN persona p ON a.Id_Persona = p.Id_Persona
                WHERE a.Fecha_Asistencia BETWEEN ? AND ?
                  AND (a.Id_Celula IS NULL OR a.Id_Celula <= 0)
                ORDER BY a.Fecha_Asistencia DESC, a.Id_Asistencia DESC
                LIMIT {$limite}";

        return $asistenciaModel->query($sql, [$fechaInicio, $fechaFin]);
    }

    /**
     * Grupos de células que pueden confundir al reportar: mismo nombre exacto
     * o mismo par líder/anfitrión con el nombre en distinto orden.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function obtenerGruposCelulasConfusas(): array {
        $celulaModel = new Celula();

        $sql = "SELECT
                    c.Id_Celula,
                    c.Nombre_Celula,
                    c.Id_Lider,
                    TRIM(CONCAT(COALESCE(l.Nombre, ''), ' ', COALESCE(l.Apellido, ''))) AS Nombre_Lider,
                    COALESCE(c.Estado_Celula, 'Activa') AS Estado_Celula,
                    (SELECT COUNT(*)
                     FROM persona p
                     WHERE p.Id_Celula = c.Id_Celula
                       AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)) AS Total_Miembros
                FROM celula c
                LEFT JOIN persona l ON c.Id_Lider = l.Id_Persona
                WHERE COALESCE(c.Estado_Celula, 'Activa') NOT IN ('Inactiva', 'Cerrada', '0')
                ORDER BY c.Nombre_Celula ASC, c.Id_Celula ASC";

        $filas = $celulaModel->query($sql);
        $porNombreExacto = [];
        $porFirma = [];

        foreach ($filas as $fila) {
            $nombre = (string)($fila['Nombre_Celula'] ?? '');
            $claveExacta = self::normalizarNombreCelula($nombre);
            $claveFirma = self::firmaNombreCelula($nombre);

            if ($claveExacta !== '') {
                $porNombreExacto[$claveExacta][] = $fila;
            }
            if ($claveFirma !== '') {
                $porFirma[$claveFirma][] = $fila;
            }
        }

        $grupos = [];
        $vistos = [];

        foreach ($porNombreExacto as $clave => $items) {
            if (count($items) < 2) {
                continue;
            }
            $ids = self::idsCelulasDeGrupo($items);
            $key = implode(',', $ids);
            if (isset($vistos[$key])) {
                continue;
            }
            $vistos[$key] = true;
            $grupos[] = [
                'tipo' => 'nombre_duplicado',
                'etiqueta' => 'Mismo nombre exacto (' . count($items) . ' registros)',
                'nombre_referencia' => (string)($items[0]['Nombre_Celula'] ?? ''),
                'celulas' => $items,
            ];
        }

        foreach ($porFirma as $items) {
            if (count($items) < 2) {
                continue;
            }
            $nombresUnicos = [];
            foreach ($items as $item) {
                $nombresUnicos[self::normalizarNombreCelula((string)($item['Nombre_Celula'] ?? ''))] = true;
            }
            if (count($nombresUnicos) < 2) {
                continue;
            }

            $ids = self::idsCelulasDeGrupo($items);
            $key = implode(',', $ids);
            if (isset($vistos[$key])) {
                continue;
            }
            $vistos[$key] = true;
            $grupos[] = [
                'tipo' => 'nombre_invertido',
                'etiqueta' => 'Mismo par de nombres en distinto orden (' . count($items) . ' registros)',
                'nombre_referencia' => (string)($items[0]['Nombre_Celula'] ?? ''),
                'celulas' => $items,
            ];
        }

        usort($grupos, static function (array $a, array $b): int {
            return strcmp((string)($a['nombre_referencia'] ?? ''), (string)($b['nombre_referencia'] ?? ''));
        });

        return $grupos;
    }

    /**
     * @param array<int, array<string, mixed>> $celulas
     * @return array<int, array<string, mixed>>
     */
    public static function filtrarCelulasPorTexto(array $celulas, string $buscar): array {
        $buscar = trim($buscar);
        if ($buscar === '') {
            return $celulas;
        }

        $needle = self::normalizarNombreCelula($buscar);

        return array_values(array_filter($celulas, static function (array $f) use ($needle): bool {
            $campos = [
                (string)($f['Nombre_Celula'] ?? ''),
                (string)($f['Nombre_Lider'] ?? ''),
                (string)($f['Nombre_Ministerio'] ?? ''),
            ];
            foreach ($campos as $campo) {
                if (str_contains(DiagnosticoReporteCelulas::normalizarNombreCelula($campo), $needle)) {
                    return true;
                }
            }
            return false;
        }));
    }

    private static function normalizarNombreCelula(string $nombre): string {
        $nombre = trim($nombre);
        if ($nombre === '') {
            return '';
        }
        if (function_exists('mb_strtolower')) {
            $nombre = mb_strtolower($nombre, 'UTF-8');
        } else {
            $nombre = strtolower($nombre);
        }
        return preg_replace('/\s+/', ' ', $nombre) ?? $nombre;
    }

    private static function firmaNombreCelula(string $nombre): string {
        $nombre = self::normalizarNombreCelula($nombre);
        if ($nombre === '') {
            return '';
        }
        $partes = preg_split('/\s*-\s*/', $nombre) ?: [];
        $partes = array_values(array_filter(array_map('trim', $partes), static function (string $p): bool {
            return $p !== '';
        }));
        if ($partes === []) {
            return $nombre;
        }
        sort($partes);
        return implode('|', $partes);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, int>
     */
    private static function idsCelulasDeGrupo(array $items): array {
        $ids = array_map(static function (array $f): int {
            return (int)($f['Id_Celula'] ?? 0);
        }, $items);
        $ids = array_values(array_unique(array_filter($ids, static function (int $id): bool {
            return $id > 0;
        })));
        sort($ids);
        return $ids;
    }

    /**
     * @return array<int, string>
     */
    public static function rutasPosiblesLog(): array {
        $rutas = [];
        $ini = trim((string)ini_get('error_log'));
        if ($ini !== '' && $ini !== 'syslog') {
            $rutas[] = $ini;
        }

        $root = defined('ROOT') ? ROOT : dirname(__DIR__, 2);
        $candidatas = [
            $root . '/logs/error.log',
            $root . '/log/error.log',
            $root . '/storage/logs/error.log',
            $root . '/public/logs/error.log',
            dirname($root) . '/logs/error.log',
            '/home/*/logs/error.log',
        ];

        foreach ($candidatas as $ruta) {
            if (strpos($ruta, '*') === false && is_file($ruta)) {
                $rutas[] = $ruta;
            }
        }

        return array_values(array_unique($rutas));
    }

    /**
     * @return array{archivo:string, lineas:array<int,string>, advertencia:string}
     */
    public static function leerLineasLogRelevantes(string $fechaInicio, string $fechaFin, int $maxLineas = 300): array {
        $rutas = self::rutasPosiblesLog();
        if ($rutas === []) {
            return [
                'archivo' => '',
                'lineas' => [],
                'advertencia' => 'No se encontró archivo error_log en el servidor. Revise el panel de hosting (Hostinger → Logs).',
            ];
        }

        $archivo = '';
        foreach ($rutas as $ruta) {
            if (is_readable($ruta)) {
                $archivo = $ruta;
                break;
            }
        }

        if ($archivo === '') {
            return [
                'archivo' => implode(', ', $rutas),
                'lineas' => [],
                'advertencia' => 'Hay rutas configuradas pero no son legibles desde PHP. Use el panel del hosting para ver error_log.',
            ];
        }

        $contenido = @file($archivo, FILE_IGNORE_NEW_LINES);
        if (!is_array($contenido)) {
            return [
                'archivo' => $archivo,
                'lineas' => [],
                'advertencia' => 'No se pudo leer el archivo de log.',
            ];
        }

        $inicio = new DateTimeImmutable($fechaInicio);
        $fin = new DateTimeImmutable($fechaFin);
        $keywords = [
            'asistencia', 'celula', 'célula', 'reporte', 'fatal', 'error', 'exception',
            'warning', 'mysqli', 'pdo', 'guardar', 'registrar', 'create()', 'execute(',
        ];

        $coincidencias = [];
        foreach ($contenido as $linea) {
            $lineaLower = strtolower($linea);
            $tieneKeyword = false;
            foreach ($keywords as $kw) {
                if (str_contains($lineaLower, $kw)) {
                    $tieneKeyword = true;
                    break;
                }
            }
            if (!$tieneKeyword) {
                continue;
            }

            if (preg_match('/^\s*\[Id_Celula\]\s*=>\s*$/', $linea)) {
                continue;
            }
            if (preg_match('/^\s*\[Nombre_Rol\]\s*=>\s*Lider de Celula\s*$/', $linea)) {
                continue;
            }
            if (preg_match('/^\s*\[Tipo_Reunion\]\s*=>\s*Celula\s*$/', $linea)) {
                continue;
            }

            $fechaLinea = self::extraerFechaDeLineaLog($linea);
            if ($fechaLinea !== null) {
                if ($fechaLinea < $inicio || $fechaLinea > $fin) {
                    continue;
                }
            }

            $coincidencias[] = $linea;
        }

        $total = count($coincidencias);
        if ($total > $maxLineas) {
            $coincidencias = array_slice($coincidencias, -$maxLineas);
        }

        $advertencia = '';
        if ($total === 0) {
            $advertencia = 'No hay líneas del log con errores de asistencias/células en ese rango. Eso no garantiza que no hubo fallos: muchos errores no se registran en error_log.';
        } elseif ($total > $maxLineas) {
            $advertencia = "Se muestran las últimas {$maxLineas} de {$total} líneas coincidentes.";
        }

        return [
            'archivo' => $archivo,
            'lineas' => $coincidencias,
            'advertencia' => $advertencia,
        ];
    }

    private static function extraerFechaDeLineaLog(string $linea): ?DateTimeImmutable {
        if (preg_match('/\[(\d{2}-\w{3}-\d{4})/', $linea, $m)) {
            $dt = DateTimeImmutable::createFromFormat('d-M-Y', $m[1]);
            return $dt instanceof DateTimeImmutable ? $dt : null;
        }
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $linea, $m)) {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d', $m[1]);
            return $dt instanceof DateTimeImmutable ? $dt : null;
        }
        return null;
    }

    /**
     * @return array<int, array{semana:string,inicio:string,fin:string}>
     */
    public static function opcionesSemanasRecientes(int $cantidad = 8): array {
        $opciones = [];
        $hoy = new DateTimeImmutable('today');
        $lunesActual = $hoy->modify('monday this week');

        for ($i = 1; $i <= $cantidad; $i++) {
            $inicio = $lunesActual->modify('-' . ($i * 7) . ' days');
            $fin = $inicio->modify('+6 days');
            $opciones[] = [
                'semana' => $inicio->format('o-\\WW'),
                'inicio' => $inicio->format('Y-m-d'),
                'fin' => $fin->format('Y-m-d'),
                'etiqueta' => $inicio->format('d/m/Y') . ' – ' . $fin->format('d/m/Y'),
            ];
        }

        return $opciones;
    }
}
