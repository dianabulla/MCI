<?php

/**
 * Selector de dashboards (Ganar, Células, UV, Cap) con URLs y etiquetas unificadas.
 */
class DashboardSelector
{
    /**
     * @return list<array{id:string,label:string,ruta:string,query?:array<string,string>}>
     */
    public static function opciones(): array
    {
        return [
            [
                'id' => 'ganar',
                'label' => 'Ganar',
                'ruta' => 'reportes/dashboard-ganar',
            ],
            [
                'id' => 'celulas',
                'label' => 'Células',
                'ruta' => 'reportes',
                'query' => ['tipo' => 'celulas'],
            ],
            [
                'id' => 'universidad_vida',
                'label' => 'Universidad de la Vida',
                'ruta' => 'reportes/dashboard-escuelas-uv',
            ],
            [
                'id' => 'capacitacion_destino',
                'label' => 'Capacitación Destino',
                'ruta' => 'reportes/dashboard-escuelas-capacitacion',
            ],
        ];
    }

    public static function detectarActivo(): string
    {
        $url = strtolower(trim((string)($_GET['url'] ?? '')));
        $tipo = strtolower(trim((string)($_GET['tipo'] ?? '')));

        if ($url === 'reportes/dashboard-escuelas-uv') {
            return 'universidad_vida';
        }
        if ($url === 'reportes/dashboard-escuelas-capacitacion') {
            return 'capacitacion_destino';
        }
        if ($url === 'reportes/dashboard-ganar') {
            return 'ganar';
        }
        if ($url === 'reportes' && $tipo === 'celulas') {
            return 'celulas';
        }

        return 'ganar';
    }

    public static function etiquetaActivo(string $id): string
    {
        foreach (self::opciones() as $op) {
            if (($op['id'] ?? '') === $id) {
                return (string)($op['label'] ?? 'Dashboard');
            }
        }

        return 'Dashboard';
    }

    /**
     * @param array<string, scalar|null> $paramsPreservar anio, ministerio, lider, mes
     */
    public static function urlOpcion(array $opcion, array $paramsPreservar = []): string
    {
        $query = ['url' => (string)($opcion['ruta'] ?? 'reportes')];
        if (!empty($opcion['query']) && is_array($opcion['query'])) {
            $query = array_merge($query, $opcion['query']);
        }

        foreach (['anio', 'ministerio', 'lider', 'mes'] as $clave) {
            if (!array_key_exists($clave, $paramsPreservar)) {
                continue;
            }
            $valor = $paramsPreservar[$clave];
            if ($valor === null || $valor === '') {
                continue;
            }
            $query[$clave] = $valor;
        }

        $base = defined('PUBLIC_URL') ? PUBLIC_URL : '';
        $sep = (strpos($base, '?') !== false) ? '&' : '?';

        return rtrim($base, '?&') . $sep . http_build_query($query);
    }

    /**
     * @param array{activo?:string,params?:array<string, scalar|null>} $opciones
     */
    public static function incluirPartial(array $opciones = []): void
    {
        $activo = trim((string)($opciones['activo'] ?? ''));
        if ($activo === '') {
            $activo = self::detectarActivo();
        }

        $paramsPreservar = is_array($opciones['params'] ?? null) ? $opciones['params'] : [];
        $dashboardSelectorActivo = $activo;
        $dashboardSelectorOpciones = [];
        foreach (self::opciones() as $op) {
            $dashboardSelectorOpciones[] = [
                'id' => (string)($op['id'] ?? ''),
                'label' => (string)($op['label'] ?? ''),
                'href' => self::urlOpcion($op, $paramsPreservar),
                'activa' => ($op['id'] ?? '') === $activo,
            ];
        }

        include VIEWS . '/reportes/_selector_dashboard.php';
    }
}
