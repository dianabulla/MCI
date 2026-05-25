<?php
/**
 * Menú lateral dinámico según permisos y políticas de layout (maestro / discípulo).
 * Requiere AuthController cargado antes de usar.
 */
class MenuBuilder {
    /** Incrementar al cambiar estructura del menú lateral (invalida caché en sesión). */
    public const SIDEBAR_MENU_VERSION = 3;

    /**
     * Sincroniza permisos planos y menú en $_SESSION.
     */
    public static function sincronizarSesion(): void {
        if (!AuthController::estaAutenticado()) {
            return;
        }

        $_SESSION['permisos_planos'] = self::construirPermisosPlanos();
        $_SESSION['sidebar_menu'] = self::filtrarMenuMaestro(self::construirMenu());
        $_SESSION['sidebar_menu_version'] = self::SIDEBAR_MENU_VERSION;
        $_SESSION['sidebar_quick_access'] = self::construirAccesosRapidos();
    }

    /**
     * El maestro no lleva enlace global a Evaluaciones en el sidebar.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private static function filtrarMenuMaestro(array $items): array {
        if (!AuthController::esContextoMaestro() || AuthController::esAdministrador()) {
            return $items;
        }

        return array_values(array_filter($items, static function ($item) {
            return is_array($item) && (string)($item['id'] ?? '') !== 'evaluaciones_maestro';
        }));
    }

    /**
     * @return array<int, string>
     */
    public static function construirPermisosPlanos(): array {
        if (AuthController::esAdministrador()) {
            return ['*'];
        }

        $planos = [];
        $permisos = (array)($_SESSION['permisos'] ?? []);
        foreach ($permisos as $modulo => $acciones) {
            if (!is_array($acciones)) {
                continue;
            }
            $modulo = strtolower(trim((string)$modulo));
            if ($modulo === '') {
                continue;
            }
            foreach ($acciones as $accion => $valor) {
                if (!empty($valor)) {
                    $planos[] = $modulo . ':' . strtolower(trim((string)$accion));
                }
            }
        }

        sort($planos);
        return array_values(array_unique($planos));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function construirMenu(): array {
        if (AuthController::esContextoMaestro() && !AuthController::esAdministrador()) {
            return self::menuMaestro();
        }

        if (self::debeUsarMenuDiscipulo()) {
            return self::menuDiscipulo();
        }

        return self::menuEstandar();
    }

    /**
     * Menú reducido discípulo: por contexto activo o rol (sin consultar inscripciones).
     */
    private static function debeUsarMenuDiscipulo(): bool {
        if (AuthController::esAdministrador() || AuthController::esContextoMaestro()) {
            return false;
        }

        $contexto = AuthController::getActiveContext();
        if ($contexto === 'discipulo') {
            return true;
        }

        if ($contexto === 'lider' || $contexto === '') {
            return AuthController::esRolDiscipuloUsuario();
        }

        return false;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function menuMaestro(): array {
        if (!AuthController::puedeVerMaterialCapacitacionDestino() && !AuthController::puedeVerCentroMaterial()) {
            return [];
        }

        $items = [];
        if (AuthController::puedeAccederHubMaterialCompleto()) {
            $items[] = [
                'id' => 'material_hub',
                'label' => 'Material',
                'ruta' => 'home/material',
                'icon' => 'bi-folder2-open',
                'active_prefixes' => [
                    'home/material',
                    'celulas/materiales',
                    'teen',
                    'programas/evaluaciones',
                    'programas/tareas',
                ],
            ];
        } elseif (AuthController::puedeVerMaterialCapacitacionDestino()) {
            $items[] = [
                'id' => 'material_cap_destino',
                'label' => 'Material Cap. Destino',
                'ruta' => 'home/material/capacitacion-destino',
                'icon' => 'bi-signpost-split-fill',
                'active_prefixes' => [
                    'home/material/capacitacion-destino',
                    'home/material',
                    'programas/evaluaciones',
                    'programas/tareas',
                ],
            ];
        }

        // Evaluaciones: el maestro entra desde cada módulo en Material (no enlace global en sidebar).

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function menuDiscipulo(): array {
        return [[
            'id' => 'capacitacion_destino',
            'label' => 'Cap. Destino',
            'ruta' => 'home/material/capacitacion-destino',
            'icon' => 'bi-signpost-split-fill',
            'active_prefixes' => [
                'home/material/capacitacion-destino',
                'programas/evaluaciones',
                'programas/tareas',
            ],
        ]];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function menuEstandar(): array {
        $items = [
            [
                'id' => 'home',
                'label' => 'Inicio',
                'ruta' => 'home',
                'icon' => 'bi-house-heart',
                'active_prefixes' => ['home'],
                'active_exclude_prefixes' => ['home/material'],
                'permiso' => null,
            ],
            [
                'id' => 'personas_ganar',
                'label' => 'Ganar-Consolidar',
                'ruta' => 'personas/ganar',
                'icon' => 'bi-person-heart',
                'active_prefixes' => ['personas', 'personas/ganar'],
                'visible' => static function () {
                    return AuthController::puedeAccederAreaGanarConsolidar();
                },
                'ruta_resolver' => static function () {
                    return AuthController::urlEntradaGanarConsolidarRelativa();
                },
            ],
            [
                'id' => 'celulas',
                'label' => 'Enviar',
                'ruta' => 'celulas',
                'icon' => 'bi-send-check',
                'active_prefixes' => ['celulas', 'asistencias'],
                'permiso' => 'celulas:ver',
            ],
            [
                'id' => 'programas',
                'label' => 'Programas',
                'ruta' => 'programas',
                'icon' => 'bi-mortarboard',
                'active_prefixes' => ['programas'],
                'visible' => static function () {
                    return AuthController::puedeAccederModuloProgramas();
                },
            ],
            [
                'id' => 'discipular_ministerios',
                'label' => 'Discipular',
                'ruta' => 'discipular/ministerios/equipo-principal',
                'icon' => 'bi-bank2',
                'active_prefixes' => ['discipular/ministerios', 'discipular/ministerios/equipo-principal'],
                'permiso' => 'ministerios:ver',
            ],
            [
                'id' => 'teen',
                'label' => 'Teens',
                'ruta' => 'teen/registro-menores',
                'icon' => 'bi-balloon-heart',
                'active_prefixes' => ['teen'],
                'permiso' => 'teen:ver',
            ],
            [
                'id' => 'nehemias',
                'label' => 'Nehemias',
                'ruta' => 'nehemias/lista',
                'icon' => 'bi-clipboard-data',
                'active_prefixes' => ['nehemias'],
                'permiso' => 'nehemias:ver',
            ],
            [
                'id' => 'reportes_ganar',
                'label' => 'Dashboard Ganar',
                'ruta' => 'reportes/dashboard-ganar',
                'icon' => 'bi-speedometer2',
                'active_prefixes' => ['reportes/dashboard-ganar'],
                'active_exact' => ['reportes/dashboard-ganar'],
                'permiso' => 'reportes:ver',
            ],
            [
                'id' => 'administracion',
                'label' => 'Administración',
                'ruta' => 'cuentas',
                'icon' => 'bi-people-fill',
                'active_prefixes' => ['cuentas', 'roles', 'permisos'],
                'visible' => static function () {
                    require_once APP . '/Helpers/GestionSistemaAccess.php';
                    return GestionSistemaAccess::puedeVerBloqueAdministracion();
                },
            ],
        ];

        return self::filtrarItems($items);
    }

    /**
     * Enlaces del bloque «accesos rápidos» del sidebar (Comunidad, Material, formulario público).
     *
     * @return array<string, mixed>
     */
    public static function construirAccesosRapidos(): array {
        if (AuthController::esContextoMaestro() || self::debeUsarMenuDiscipulo()) {
            return [];
        }

        $comunidad = [];
        if (AuthController::puedeVerModulo('peticiones')) {
            $comunidad[] = ['id' => 'peticiones', 'label' => 'Peticiones', 'ruta' => 'peticiones', 'icon' => 'bi-chat-heart'];
        }
        if (AuthController::puedeVerModulo('transmisiones')) {
            $comunidad[] = ['id' => 'transmisiones', 'label' => 'Transmisiones', 'ruta' => 'transmisiones', 'icon' => 'bi-broadcast'];
        }
        if (AuthController::puedeVerModulo('eventos')) {
            $comunidad[] = ['id' => 'eventos', 'label' => 'Eventos', 'ruta' => 'eventos', 'icon' => 'bi-calendar-event'];
        }

        $material = [];
        if (self::puedeVerCentroMaterialEnMenu()) {
            $material[] = ['id' => 'material_centro', 'label' => 'Centro de material', 'ruta' => 'home/material', 'icon' => 'bi-folder2-open'];
        }
        if (AuthController::puedeVerMaterialCelulas()) {
            $material[] = ['id' => 'material_celulas', 'label' => 'Material células', 'ruta' => 'home/material/celulas', 'icon' => 'bi-send-check'];
        }
        if (AuthController::puedeVerMaterialTeens()) {
            $material[] = ['id' => 'material_teens', 'label' => 'Teens', 'ruta' => 'home/material/teens', 'icon' => 'bi-balloon-heart'];
        }
        if (AuthController::puedeVerMaterialUniversidadVida()) {
            $material[] = ['id' => 'material_uv', 'label' => 'Material U.V.', 'ruta' => 'home/material/universidad-vida', 'icon' => 'bi-mortarboard'];
        }
        if (AuthController::puedeVerMaterialCapacitacionDestino()) {
            $material[] = ['id' => 'material_cap', 'label' => 'Material Cap. Destino', 'ruta' => 'home/material/capacitacion-destino', 'icon' => 'bi-signpost-split-fill'];
        }

        $formularioPublico = AuthController::puedeVerModulo('personas_formulario_publico');

        return [
            'comunidad' => $comunidad,
            'material' => $material,
            'formulario_publico' => $formularioPublico,
        ];
    }

    /**
     * Centro de material en menú sin consultar inscripciones en BD (evita fallos en CLI/tests).
     */
    private static function puedeVerCentroMaterialEnMenu(): bool {
        return AuthController::puedeVerCentroMaterial();
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private static function filtrarItems(array $items): array {
        $resultado = [];

        foreach ($items as $item) {
            if (isset($item['visible']) && is_callable($item['visible']) && !$item['visible']()) {
                continue;
            }

            $permiso = $item['permiso'] ?? null;
            if ($permiso !== null && $permiso !== '' && !AuthController::puede((string)$permiso)) {
                continue;
            }

            if (isset($item['ruta_resolver']) && is_callable($item['ruta_resolver'])) {
                $item['ruta'] = (string)$item['ruta_resolver']();
            }

            unset($item['visible'], $item['permiso'], $item['ruta_resolver']);
            $resultado[] = $item;
        }

        return $resultado;
    }
}
