<?php
/**
 * Permisos granulares por submódulo real (columnas y acciones concretas).
 * No genera botones genéricos CRUD duplicados — eso ya está en cada submódulo.
 */
class PermisosUiCatalogo {

    /**
     * Botones / acciones extra por clave de submódulo (personas, nehemias, …).
     * Las claves deben coincidir con PermisosCatalogo cuando aplique.
     *
     * @var array<string, array<string, string>>
     */
    private const ACCIONES_POR_SUBMODULO = [
        'personas' => [
            'exportar_excel' => 'Exportar Excel',
            'gestionar_cuenta_acceso' => 'Gestionar cuenta de acceso',
        ],
        'personas_consulta' => [
            'exportar_excel' => 'Exportar Excel',
        ],
        'celulas' => [
            'exportar_datos' => 'Exportar datos',
        ],
        'programas' => [
            'coordinacion_total' => 'Coordinación total (programas)',
            'ver_universidad_vida' => 'Ver consolidado Universidad de la Vida',
            'ver_capacitacion_destino' => 'Ver consolidado Capacitación Destino',
            'dashboard_universidad_vida' => 'Dashboard Universidad de la Vida',
            'dashboard_capacitacion_destino' => 'Dashboard Capacitación Destino',
            'gestionar_pagos_universidad_vida' => 'Gestionar pagos Universidad de la Vida',
            'gestionar_pagos_capacitacion_destino' => 'Gestionar pagos Capacitación Destino',
            'formulario_universidad_vida' => 'Formulario público Universidad de la Vida',
            'formulario_capacitacion_destino' => 'Formulario público Capacitación Destino',
            'asistencias_universidad_vida' => 'Asistencias Universidad de la Vida',
            'exportar_consolidado' => 'Exportar consolidado programas',
        ],
        'escuelas_formacion' => [
            'ver_matriz_completa' => 'Ver matriz completa',
            'exportar_inscritos' => 'Exportar inscritos',
            'gestionar_pagos' => 'Gestionar pagos escuela',
        ],
        'reportes' => [
            'exportar' => 'Exportar reportes',
            'ver_dashboard_auditoria' => 'Dashboards sensibles',
        ],
        'ministerios' => [
            'editar_metas' => 'Editar metas ministeriales',
        ],
        'eventos' => [
            'gestionar_contenido_publico' => 'Contenido y QR público',
        ],
        'peticiones' => [
            'moderar' => 'Moderar peticiones',
        ],
        'discipular_evaluaciones' => [
            'calificar_terceros' => 'Calificar a otros',
        ],
        'asistencias' => [
            'exportar_excel' => 'Exportar asistencias',
        ],
        'teen' => [
            'exportar_datos' => 'Exportar datos teens',
        ],
        'nehemias' => [
            'editar' => 'Editar en listado',
            'eliminar' => 'Eliminar en listado',
            'importar_masivo' => 'Importación masiva',
        ],
        'transmisiones' => [
            'exportar_excel' => 'Exportar transmisiones',
        ],
        'entrega_obsequio' => [
            'exportar_pdf' => 'Exportar PDF',
            'exportar_excel' => 'Exportar Excel',
        ],
        'roles' => [
            'exportar_excel' => 'Exportar roles',
        ],
        'material' => [
            'gestionar_subida' => 'Gestionar subida global',
        ],
        'materiales_celulas' => [
            'exportar_datos' => 'Exportar material células',
        ],
    ];

    /** @var array<string, array<string, string>> */
    private const COLUMNAS_POR_SUBMODULO = [
        'nehemias' => [
            'cedula' => 'Cédula',
            'telefono' => 'Teléfono',
            'subido_link' => 'Link subido',
            'bogota_subio' => 'En Bogotá se le subió',
            'puesto' => 'Puesto',
            'mesa' => 'Mesa',
            'acepta' => 'Acepta',
        ],
    ];

    /**
     * @return array<string, array{acciones: array<string, string>, columnas: array<string, string>}>
     */
    public static function configuracionPorSubmodulo(): array {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = [];
        foreach (self::ACCIONES_POR_SUBMODULO as $sub => $acciones) {
            $cache[$sub] = [
                'acciones' => $acciones,
                'columnas' => self::COLUMNAS_POR_SUBMODULO[$sub] ?? [],
            ];
        }
        foreach (self::COLUMNAS_POR_SUBMODULO as $sub => $columnas) {
            if (!isset($cache[$sub])) {
                $cache[$sub] = ['acciones' => [], 'columnas' => $columnas];
            }
        }

        return $cache;
    }

    /** @deprecated usar configuracionPorSubmodulo */
    public static function configuracionPorModuloBase(): array {
        return self::configuracionPorSubmodulo();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function modulosDerivadosParaMatriz(): array {
        $out = [];
        foreach (self::configuracionPorSubmodulo() as $sub => $cfg) {
            $metaSub = PermisosModulos::definicionBase($sub);
            if ($metaSub === null) {
                continue;
            }
            $grupo = (string)($metaSub['grupo'] ?? 'Otros');
            $labelSub = (string)($metaSub['label'] ?? $sub);

            foreach ($cfg['acciones'] as $clave => $etiqueta) {
                $mod = self::claveAccion($sub, $clave);
                $out[$mod] = [
                    'label' => $labelSub . ' — ' . $etiqueta,
                    'descripcion' => 'Acción «' . $etiqueta . '» en ' . $labelSub . '.',
                    'grupo' => $grupo,
                    'solo_ver' => true,
                    'padre' => $sub,
                    'tipo_ui' => 'accion',
                    'clave_ui' => $clave,
                    'crud_labels' => ['puede_ver' => 'Permitir'],
                ];
            }

            foreach ($cfg['columnas'] as $clave => $etiqueta) {
                $mod = self::claveColumna($sub, $clave);
                $out[$mod] = [
                    'label' => $labelSub . ' — columna ' . $etiqueta,
                    'descripcion' => 'Ver columna «' . $etiqueta . '» en ' . $labelSub . '.',
                    'grupo' => $grupo,
                    'solo_ver' => true,
                    'padre' => $sub,
                    'tipo_ui' => 'columna',
                    'clave_ui' => $clave,
                    'crud_labels' => ['puede_ver' => 'Ver columna'],
                ];
            }
        }

        return $out;
    }

    public static function esModuloDerivado(string $modulo): bool {
        $modulo = trim($modulo);
        return $modulo !== '' && (bool)preg_match('/_(acciones|cols)_[a-z0-9_]+$/', $modulo);
    }

    public static function padreDeModuloDerivado(string $modulo): ?string {
        if (!self::esModuloDerivado($modulo)) {
            return null;
        }
        if (preg_match('/^(.+)_(acciones|cols)_/', $modulo, $m)) {
            return $m[1];
        }

        return null;
    }

    public static function moduloPadre(string $modulo): ?string {
        $padre = self::padreDeModuloDerivado($modulo);
        if ($padre !== null) {
            return $padre;
        }
        $def = PermisosModulos::definicionesInternas()[$modulo] ?? [];
        $p = trim((string)($def['padre'] ?? ''));

        return $p !== '' ? $p : null;
    }

    /**
     * Acciones y columnas granulares de un submódulo (para la tarjeta anidada).
     *
     * @return array{acciones: array<int, array{clave:string, modulo:string, label:string}>, columnas: array<int, array{clave:string, modulo:string, label:string}>}
     */
    public static function extrasDeSubmodulo(string $submodulo): array {
        $cfg = self::configuracionPorSubmodulo()[$submodulo] ?? ['acciones' => [], 'columnas' => []];
        $acciones = [];
        foreach ($cfg['acciones'] as $key => $label) {
            $acciones[] = [
                'clave' => (string)$key,
                'modulo' => self::claveAccion($submodulo, (string)$key),
                'label' => (string)$label,
            ];
        }
        $columnas = [];
        foreach ($cfg['columnas'] as $key => $label) {
            $columnas[] = [
                'clave' => (string)$key,
                'modulo' => self::claveColumna($submodulo, (string)$key),
                'label' => (string)$label,
            ];
        }

        return ['acciones' => $acciones, 'columnas' => $columnas];
    }

    /** @deprecated */
    public static function submodulosDe(string $moduloBase): array {
        return self::extrasDeSubmodulo($moduloBase);
    }

    public static function accionesMigradasASubmodulo(string $submodulo): array {
        return array_keys(self::configuracionPorSubmodulo()[$submodulo]['acciones'] ?? []);
    }

    public static function claveAccion(string $submodulo, string $accion): string {
        return strtolower(trim($submodulo)) . '_acciones_' . strtolower(trim($accion));
    }

    public static function claveColumna(string $submodulo, string $columna): string {
        return strtolower(trim($submodulo)) . '_cols_' . strtolower(trim($columna));
    }

    public static function tieneAccionUi(string $submodulo, string $accion): bool {
        return isset(self::configuracionPorSubmodulo()[$submodulo]['acciones'][$accion]);
    }

    /**
     * Grupo del menú para colocar un módulo (incluye derivados huérfanos en BD).
     */
    public static function grupoDeModulo(string $modulo): string {
        $padre = self::padreDeModuloDerivado($modulo);
        if ($padre !== null) {
            $modulo = $padre;
        }
        $meta = PermisosModulos::definicionBase($modulo);
        if ($meta !== null) {
            return (string)($meta['grupo'] ?? 'Otros');
        }
        $def = PermisosModulos::definiciones()[$modulo] ?? [];

        return (string)($def['grupo'] ?? 'Otros');
    }
}
