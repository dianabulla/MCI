<?php

/**
 * Navegación unificada del ecosistema Programas (UV ↔ Cap, secciones, atrás).
 */
class ProgramasNavegacion
{
    private const SECCIONES = [
        'consolidado' => 'Consolidado',
        'asistencias' => 'Asistencias',
        'dashboard' => 'Dashboard',
        'pagos' => 'Pagos',
        'formulario' => 'Formulario',
        'material' => 'Material',
    ];

    /**
     * @param array{linea?:string,seccion?:string,modo?:string,forzar?:bool} $opciones
     * @return array<string, mixed>
     */
    public static function construir(array $opciones = []): array
    {
        require_once APP . '/Helpers/PermisosProgramasAccess.php';

        if (!class_exists('AuthController') || !AuthController::estaAutenticado()) {
            return ['mostrar' => false];
        }

        $url = self::normalizarRuta((string)($_GET['url'] ?? ''));
        $enEcosistema = self::estaEnEcosistemaProgramas($url);
        if (!$enEcosistema && empty($opciones['forzar'])) {
            return ['mostrar' => false];
        }

        $ctx = self::detectarContexto($url);
        if (!empty($opciones['modo'])) {
            $ctx['modo'] = (string)$opciones['modo'];
        }
        if (!empty($opciones['linea']) && in_array($opciones['linea'], ['universidad_vida', 'capacitacion_destino'], true)) {
            $ctx['linea'] = (string)$opciones['linea'];
        }
        if (!empty($opciones['seccion']) && isset(self::SECCIONES[$opciones['seccion']])) {
            $ctx['seccion'] = (string)$opciones['seccion'];
        }

        $lineas = self::construirLineas($ctx);
        if ($lineas === []) {
            return ['mostrar' => false];
        }

        $lineaActiva = (string)($ctx['linea'] ?? '');
        if ($lineaActiva === '' || !self::lineaVisible($lineaActiva)) {
            $lineaActiva = (string)($lineas[0]['clave'] ?? '');
        }

        $capNivel = self::resolverCapNivel($url, $lineaActiva);
        $secciones = self::construirSecciones($lineaActiva, $capNivel, $ctx);
        $seccionActiva = (string)($ctx['seccion'] ?? '');
        if ($seccionActiva === '' && $ctx['modo'] === 'hub') {
            $seccionActiva = '';
        }

        $lineaLabel = self::tituloLinea($lineaActiva);
        $seccionLabel = $seccionActiva !== '' ? (self::SECCIONES[$seccionActiva] ?? '') : '';

        return [
            'mostrar' => true,
            'modo' => $ctx['modo'],
            'hub_url' => self::urlPublic('programas'),
            'panel_url' => self::urlPublic('home'),
            'linea_activa' => $lineaActiva,
            'linea_label' => $lineaLabel,
            'seccion_activa' => $seccionActiva,
            'seccion_label' => $seccionLabel,
            'lineas' => $lineas,
            'secciones' => $secciones,
            'atras_url' => self::urlAtras($ctx, $lineaActiva, $seccionActiva, $capNivel),
            'atras_etiqueta' => self::etiquetaAtras($ctx, $seccionActiva),
            'breadcrumb' => self::breadcrumb($ctx, $lineaLabel, $seccionLabel),
        ];
    }

    public static function incluirPartial(array $opciones = []): void
    {
        $navProgramas = self::construir($opciones);
        if (empty($navProgramas['mostrar'])) {
            return;
        }
        include VIEWS . '/programas/_navegacion_programas.php';
    }

    private static function normalizarRuta(string $url): string
    {
        return strtolower(trim($url, '/'));
    }

    private static function estaEnEcosistemaProgramas(string $url): bool
    {
        if ($url === 'programas' || strpos($url, 'programas/') === 0) {
            return true;
        }
        if ($url === 'reportes/dashboard-escuelas-uv' || $url === 'reportes/dashboard-escuelas-capacitacion') {
            return true;
        }
        // Pagos y material UV/Cap dentro del ecosistema Programas.
        if (strpos($url, 'escuelas_formacion/pagos/') === 0) {
            return true;
        }
        if (strpos($url, 'home/material/universidad-vida') === 0
            || strpos($url, 'home/material/capacitacion-destino') === 0) {
            return true;
        }

        return false;
    }

    /**
     * @return array{modo:string,linea:?string,seccion:?string}
     */
    private static function detectarContexto(string $url): array
    {
        $modo = 'interior';
        $linea = null;
        $seccion = null;

        $insc = strtolower(trim((string)($_GET['insc_programa'] ?? '')));
        if ($insc === 'universidad_vida') {
            $linea = 'universidad_vida';
        } elseif ($insc === 'capacitacion_destino' || strpos($insc, 'capacitacion_destino') === 0) {
            $linea = 'capacitacion_destino';
        }

        if ($url === 'programas') {
            return ['modo' => 'hub', 'linea' => $linea, 'seccion' => null];
        }

        if (strpos($url, 'escuelas_formacion/pagos/consolidar') === 0) {
            return ['modo' => $modo, 'linea' => 'universidad_vida', 'seccion' => 'pagos'];
        }
        if (strpos($url, 'escuelas_formacion/pagos/enviar') === 0) {
            return ['modo' => $modo, 'linea' => 'capacitacion_destino', 'seccion' => 'pagos'];
        }
        if (strpos($url, 'home/material/universidad-vida') === 0) {
            return ['modo' => $modo, 'linea' => 'universidad_vida', 'seccion' => 'material'];
        }
        if (strpos($url, 'home/material/capacitacion-destino') === 0) {
            $capSeccion = strtolower(trim((string)($_GET['cap_seccion'] ?? '')));
            $seccionCap = ($capSeccion === 'inscritos') ? 'asistencias' : 'material';

            return ['modo' => $modo, 'linea' => 'capacitacion_destino', 'seccion' => $seccionCap];
        }
        if ($url === 'programas/asistencias' || strpos($url, 'programas/consolidar/asistencias') === 0) {
            if ($linea === null) {
                $linea = 'universidad_vida';
            }

            return ['modo' => $modo, 'linea' => $linea, 'seccion' => 'asistencias'];
        }
        if (strpos($url, 'programas/consolidar') === 0) {
            return ['modo' => $modo, 'linea' => $linea, 'seccion' => 'consolidado'];
        }
        if ($url === 'reportes/dashboard-escuelas-uv') {
            return ['modo' => $modo, 'linea' => 'universidad_vida', 'seccion' => 'dashboard'];
        }
        if ($url === 'reportes/dashboard-escuelas-capacitacion') {
            return ['modo' => $modo, 'linea' => 'capacitacion_destino', 'seccion' => 'dashboard'];
        }

        return ['modo' => $modo, 'linea' => $linea, 'seccion' => $seccion];
    }

    private static function definicionesLineas(): array
    {
        return [
            [
                'clave' => 'universidad_vida',
                'titulo' => 'Universidad de la Vida',
                'titulo_corto' => 'U. de la Vida',
                'icono' => 'bi bi-mortarboard-fill',
                'gradiente' => 'linear-gradient(135deg, #1e4a89 0%, #3f73be 100%)',
                'color' => '#1e4a89',
                'consolidar_url' => 'programas/consolidar&insc_programa=universidad_vida',
                'asistencias_url' => 'programas/consolidar/asistencias&insc_programa=universidad_vida',
                'dashboard_url' => 'reportes/dashboard-escuelas-uv',
                'pagos_url' => 'escuelas_formacion/pagos/consolidar',
                'formulario_url' => 'escuelas_formacion/registro-publico/universidad-vida',
                'material_url' => 'home/material/universidad-vida',
            ],
            [
                'clave' => 'capacitacion_destino',
                'titulo' => 'Capacitación Destino',
                'titulo_corto' => 'Cap. Destino',
                'icono' => 'bi bi-signpost-split-fill',
                'gradiente' => 'linear-gradient(135deg, #7a4e08 0%, #c8881e 100%)',
                'color' => '#7a4e08',
                'consolidar_url' => 'programas/consolidar&insc_programa=capacitacion_destino',
                'asistencias_url' => 'home/material/capacitacion-destino&cap_nivel=1&cap_seccion=inscritos',
                'dashboard_url' => 'reportes/dashboard-escuelas-capacitacion',
                'pagos_url' => 'escuelas_formacion/pagos/enviar',
                'formulario_url' => 'escuelas_formacion/registro-publico/capacitacion-destino',
                'material_url' => 'home/material/capacitacion-destino',
            ],
        ];
    }

    /**
     * @param array{modo:string,linea:?string,seccion:?string} $ctx
     * @return list<array<string, mixed>>
     */
    private static function construirLineas(array $ctx): array
    {
        $out = [];
        foreach (self::definicionesLineas() as $def) {
            $clave = (string)$def['clave'];
            if (!self::lineaVisible($clave)) {
                continue;
            }
            $perm = PermisosProgramasAccess::permisosUiLinea($clave);
            if (empty($perm['ver_linea'])) {
                continue;
            }
            $hrefConsolidar = !empty($perm['consolidado'])
                ? self::urlPublic((string)$def['consolidar_url'])
                : self::urlPublic('programas');
            $out[] = [
                'clave' => $clave,
                'titulo' => (string)$def['titulo'],
                'titulo_corto' => (string)$def['titulo_corto'],
                'icono' => (string)$def['icono'],
                'gradiente' => (string)$def['gradiente'],
                'color' => (string)$def['color'],
                'activa' => ($ctx['linea'] ?? '') === $clave,
                'href' => $hrefConsolidar,
            ];
        }

        return $out;
    }

    /**
     * @param array{modo:string,linea:?string,seccion:?string} $ctx
     * @return list<array<string, mixed>>
     */
    private static function construirSecciones(string $lineaActiva, int $capNivel, array $ctx): array
    {
        if ($ctx['modo'] === 'hub') {
            return [];
        }

        $def = null;
        foreach (self::definicionesLineas() as $d) {
            if ($d['clave'] === $lineaActiva) {
                $def = $d;
                break;
            }
        }
        if ($def === null) {
            return [];
        }

        $perm = PermisosProgramasAccess::permisosUiLinea($lineaActiva);
        $seccionActiva = (string)($ctx['seccion'] ?? '');

        $map = [
            'consolidado' => ['perm' => 'consolidado', 'url' => (string)$def['consolidar_url']],
            'asistencias' => ['perm' => 'asistencias', 'url' => self::urlAsistenciasLinea($lineaActiva, (string)$def['asistencias_url'], $capNivel)],
            'dashboard' => ['perm' => 'dashboard', 'url' => (string)$def['dashboard_url']],
            'pagos' => ['perm' => 'pagos', 'url' => (string)$def['pagos_url']],
            'formulario' => ['perm' => 'formulario', 'url' => (string)$def['formulario_url'], 'externa' => true],
            'material' => ['perm' => 'material', 'url' => (string)$def['material_url']],
        ];

        $out = [];
        foreach (self::SECCIONES as $id => $label) {
            $cfg = $map[$id] ?? null;
            if ($cfg === null || empty($perm[$cfg['perm']])) {
                continue;
            }
            $out[] = [
                'id' => $id,
                'label' => $label,
                'href' => self::urlPublic((string)$cfg['url']),
                'activa' => $seccionActiva === $id,
                'externa' => !empty($cfg['externa']),
            ];
        }

        return $out;
    }

    private static function urlAsistenciasLinea(string $linea, string $urlBase, int $capNivel): string
    {
        if ($linea !== 'capacitacion_destino') {
            return $urlBase;
        }
        $nivel = max(1, min(3, $capNivel));

        return 'home/material/capacitacion-destino&cap_nivel=' . $nivel . '&cap_seccion=inscritos';
    }

    private static function resolverCapNivel(string $url, string $linea): int
    {
        if ($linea !== 'capacitacion_destino') {
            return 1;
        }
        $nivel = (int)($_GET['cap_nivel'] ?? 0);
        if ($nivel >= 1 && $nivel <= 3) {
            return $nivel;
        }
        $insc = strtolower(trim((string)($_GET['insc_programa'] ?? '')));
        if (preg_match('/capacitacion_destino_nivel_(\d+)/', $insc, $m)) {
            return max(1, min(3, (int)($m[1] ?? 1)));
        }

        return 1;
    }

    private static function urlAtras(array $ctx, string $linea, string $seccion, int $capNivel): string
    {
        if ($ctx['modo'] === 'hub') {
            return self::urlPublic('home');
        }
        if ($seccion === 'dashboard') {
            foreach (self::definicionesLineas() as $def) {
                if ($def['clave'] !== $linea) {
                    continue;
                }
                $perm = PermisosProgramasAccess::permisosUiLinea($linea);
                if (!empty($perm['consolidado'])) {
                    return self::urlPublic((string)$def['consolidar_url']);
                }
            }

            return self::urlPublic('programas');
        }
        if ($seccion === 'consolidado' || $seccion === '') {
            return self::urlPublic('programas');
        }
        foreach (self::definicionesLineas() as $def) {
            if ($def['clave'] !== $linea) {
                continue;
            }
            $perm = PermisosProgramasAccess::permisosUiLinea($linea);
            if (!empty($perm['consolidado'])) {
                return self::urlPublic((string)$def['consolidar_url']);
            }
        }

        return self::urlPublic('programas');
    }

    private static function etiquetaAtras(array $ctx, string $seccion): string
    {
        if ($ctx['modo'] === 'hub') {
            return 'Panel principal';
        }
        if ($seccion === 'dashboard') {
            $linea = (string)($ctx['linea'] ?? '');
            $titulo = self::tituloLinea($linea);

            return $titulo !== '' ? $titulo : 'Consolidado';
        }
        if ($seccion === 'consolidado' || $seccion === '') {
            return 'Programas';
        }

        return 'Consolidado';
    }

    /**
     * @return list<array{label:string,href?:string,activo:bool}>
     */
    private static function breadcrumb(array $ctx, string $lineaLabel, string $seccionLabel): array
    {
        $items = [
            ['label' => 'Programas', 'href' => self::urlPublic('programas'), 'activo' => $ctx['modo'] === 'hub'],
        ];
        if ($ctx['modo'] !== 'hub' && $lineaLabel !== '') {
            $items[] = ['label' => $lineaLabel, 'href' => '', 'activo' => $seccionLabel === ''];
        }
        if ($seccionLabel !== '') {
            $items[] = ['label' => $seccionLabel, 'href' => '', 'activo' => true];
        }

        return $items;
    }

    private static function lineaVisible(string $clave): bool
    {
        if ($clave === 'universidad_vida') {
            return PermisosProgramasAccess::puedeVerLineaUniversidadVida();
        }
        if ($clave === 'capacitacion_destino') {
            return PermisosProgramasAccess::puedeVerLineaCapacitacionDestino();
        }

        return false;
    }

    private static function tituloLinea(string $clave): string
    {
        foreach (self::definicionesLineas() as $def) {
            if ($def['clave'] === $clave) {
                return (string)$def['titulo'];
            }
        }

        return '';
    }

    public static function urlPublic(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return defined('PUBLIC_URL') ? PUBLIC_URL : '';
        }

        return (defined('PUBLIC_URL') ? PUBLIC_URL : '') . '?url=' . $path;
    }
}
