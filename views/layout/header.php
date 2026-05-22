<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'MCI Madrid Colombia' ?></title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/styles.css?v=20260515-sidebar-quick-1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
<?php
$currentUrl = $_GET['url'] ?? 'home';
$isActive = function(array $prefixes) use ($currentUrl) {
    foreach ($prefixes as $prefix) {
        if ($currentUrl === $prefix || strpos($currentUrl, $prefix . '/') === 0) {
            return true;
        }
    }
    return false;
};

$puedeVer = static function (string $modulo) {
    return AuthController::esAdministrador() || AuthController::puede($modulo . ':ver');
};

$esDiscipuloMenuDirecto = AuthController::esRolDiscipuloUsuario() && !AuthController::esAdministrador();
$esMenuMaestro = AuthController::esContextoMaestro() && !AuthController::esAdministrador();
$esMenuDiscipulo = AuthController::esVistaDiscipuloSimplificada();
$puedeVerMaterialCapDestino = AuthController::puedeVerMaterialCapacitacionDestino();

$puedeVerGanarArea = AuthController::puedeAccederAreaGanarConsolidar();
$puedeVerGanarAlmas = AuthController::puedeVerModuloPersonasGanar();
$urlEntradaGanarConsolidar = AuthController::urlEntradaGanarConsolidarRelativa();
$puedeVerPersonasConsultaSolo = false;
$puedeVerDiscipular = $esDiscipuloMenuDirecto || $puedeVer('personas') || $puedeVer('discipular_evaluaciones');
$puedeVerEnviar = $puedeVer('celulas');
$puedeVerMaterial = !$esDiscipuloMenuDirecto && AuthController::puedeVerCentroMaterial();
$puedeVerMinisterios = $puedeVer('ministerios');
$puedeVerRegistroTeensKids = $puedeVer('teen');
$puedeVerEventosMenu = AuthController::esAdministrador() || AuthController::puede('eventos:ver');
$puedeVerAsistencias = $puedeVer('asistencias');
$puedeVerPeticiones = $puedeVer('peticiones');
$puedeVerTransmisiones = $puedeVer('transmisiones');
$puedeVerObsequios = $puedeVer('entrega_obsequio');
$puedeVerNehemias = $puedeVer('nehemias');
$puedeVerReportes = $puedeVer('reportes');
$puedeVerRoles = $puedeVer('roles');
$puedeVerMaterialCelulasEnEnviar = $puedeVerEnviar && (AuthController::esAdministrador() || AuthController::puedeVerMaterialCelulas());
$puedeVerAsistenciasEnEnviar = $puedeVerEnviar && $puedeVerAsistencias;

$puedeVerPendientesGanar = $puedeVerGanarAlmas;
$totalPendientesGanar = 0;
$totalPendientesPorConectar = 0;
$totalNuevasAlmasGanadas = 0;
$totalUniversidadVida = 0;
$totalCampanaGanar = 0;
$mostrarAlertaIngresoGanar = !empty($_SESSION['mostrar_alerta_ganar_pendiente']);

if ($mostrarAlertaIngresoGanar) {
    unset($_SESSION['mostrar_alerta_ganar_pendiente']);
}

if ($puedeVerPendientesGanar) {
    require_once APP . '/Helpers/DataIsolation.php';
    require_once APP . '/Models/Persona.php';

    try {
        $personaCampanaModel = new Persona();
        $filtroRolPendientes = DataIsolation::generarFiltroPersonasPendienteConsolidar();
        $filtroRolDiscipulos = DataIsolation::generarFiltroPersonas();
        $personasCampana = $personaCampanaModel->getAllWithRole($filtroRolPendientes, true);

        $normalizarRol = static function ($rolNombre) {
            $rol = strtolower(trim((string)$rolNombre));
            return strtr($rol, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        };

        $esRolLiderazgo = static function ($rolNombre) use ($normalizarRol) {
            $rol = $normalizarRol($rolNombre);
            return strpos($rol, 'pastor') !== false
                || strpos($rol, 'lider de 12') !== false
                || strpos($rol, 'lider 12') !== false
                || strpos($rol, 'lideres de 12') !== false
                || strpos($rol, 'lider de celula') !== false
                || strpos($rol, 'lider celula') !== false;
        };

        $esRolDiscipular = static function ($rolNombre) use ($normalizarRol) {
            $rol = $normalizarRol($rolNombre);
            return strpos($rol, 'discipul') !== false || strpos($rol, 'disipul') !== false;
        };

        $idsPendientesPorConectar = [];
        $idsNuevasAlmasGanadas = [];
        $idsUniversidadVida = [];

        $personasPendientesConectarCampana = $personaCampanaModel->getWithFiltersAndRole(
            $filtroRolDiscipulos,
            null,
            null,
            null,
            'Activo',
            '0',
            null,
            null,
            null,
            null
        );
        foreach ((array)$personasPendientesConectarCampana as $personaTmpPendiente) {
            $rolPendiente = (string)($personaTmpPendiente['Nombre_Rol'] ?? '');
            if ($esRolLiderazgo($rolPendiente)) {
                continue;
            }
            if (!empty($personaTmpPendiente['Seguimiento_No_Disponible'])) {
                continue;
            }
            if ((int)($personaTmpPendiente['Id_Ministerio'] ?? 0) <= 0) {
                continue;
            }
            if ((int)($personaTmpPendiente['Id_Celula'] ?? 0) > 0) {
                continue;
            }
            $idPersonaPendiente = (int)($personaTmpPendiente['Id_Persona'] ?? 0);
            if ($idPersonaPendiente > 0) {
                $idsPendientesPorConectar[$idPersonaPendiente] = true;
            }
        }

        foreach ((array)$personasCampana as $personaTmp) {
            $esNuevo = ((int)($personaTmp['Es_Antiguo'] ?? 0) !== 1);
            if (trim((string)($personaTmp['Canal_Creacion'] ?? '')) === 'Escuelas Formacion (Formulario publico)') {
                continue;
            }
            $checklistRaw = (string)($personaTmp['Escalera_Checklist'] ?? '');
            if ($checklistRaw !== '') {
                $checklist = json_decode($checklistRaw, true);
                if (is_array($checklist) && !empty($checklist['Ganar'][5])) {
                    continue;
                }
            }
            if ($esNuevo && !$esRolLiderazgo((string)($personaTmp['Nombre_Rol'] ?? ''))) {
                $idPersonaTmp = (int)($personaTmp['Id_Persona'] ?? 0);
                if ($idPersonaTmp > 0) {
                    $idsNuevasAlmasGanadas[$idPersonaTmp] = true;
                }
            }
        }

        $filtroRolUniversidad = DataIsolation::generarFiltroPersonas();
        $personasUniversidad = $personaCampanaModel->getPersonasUniversidadVida($filtroRolUniversidad);
        foreach ((array)$personasUniversidad as $personaUvTmp) {
            $rolUv = (string)($personaUvTmp['Nombre_Rol'] ?? '');
            if ($esRolLiderazgo($rolUv) || !empty($personaUvTmp['Seguimiento_No_Disponible'])) {
                continue;
            }
            if ((int)($personaUvTmp['Id_Ministerio'] ?? 0) <= 0) {
                continue;
            }
            if ((int)($personaUvTmp['Id_Celula'] ?? 0) > 0) {
                continue;
            }
            $idPersonaUv = (int)($personaUvTmp['Id_Persona'] ?? 0);
            if ($idPersonaUv > 0) {
                $idsUniversidadVida[$idPersonaUv] = true;
            }
        }

        $idsListaDiscipulos = $idsPendientesPorConectar + $idsUniversidadVida;
        $totalPendientesPorConectar = count($idsListaDiscipulos);
        $totalUniversidadVida = 0;
        $totalPendientesGanar = count($idsNuevasAlmasGanadas);
        $totalCampanaGanar = count($idsListaDiscipulos + $idsNuevasAlmasGanadas);
    } catch (Exception $e) {
        error_log('No se pudo cargar contador de pendientes en Ganar: ' . $e->getMessage());
    }
}

if (class_exists('AuthController') && AuthController::estaAutenticado() && empty($_SESSION['sidebar_menu'])) {
    AuthController::reconstruirMenuYSesion();
}

$useDynamicSidebar = !empty($_SESSION['sidebar_menu']) && is_array($_SESSION['sidebar_menu']);
$sidebarMenu = $useDynamicSidebar ? (array)$_SESSION['sidebar_menu'] : [];
?>

<div class="app-shell">
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-main">
                <img src="<?= ASSETS_URL ?>/img/logo-mci-madrid.svg" alt="Logo MCI Madrid" class="sidebar-brand-logo">
                <div class="sidebar-brand-copy">
                    <span class="sidebar-link-text">MCI Madrid</span>
                    <small class="sidebar-brand-subtitle"><?= $esMenuMaestro ? 'Material Capacitación Destino' : ($esMenuDiscipulo ? 'Mis evaluaciones' : 'Vision y seguimiento') ?></small>
                </div>
            </div>
        </div>

        <?php
        $puedeNuevoDiscipuloRapido = AuthController::puede('personas:crear')
            || AuthController::puede('acceso_rapido_nuevo_discipulo:crear');
        $puedePlantillasWaRapido = AuthController::puedeVerModulo('personas_plantillas_whatsapp');
        $puedeFormularioPublicoRapido = AuthController::puedeVerModulo('personas_formulario_publico');
        $puedeAlgunAccesoRapido = $puedeNuevoDiscipuloRapido || $puedePlantillasWaRapido || $puedeFormularioPublicoRapido;
        ?>
        <?php if (!$esMenuMaestro && !$esMenuDiscipulo && ($puedeAlgunAccesoRapido || $puedeVerPeticiones || $puedeVerTransmisiones || $puedeVerEventosMenu)): ?>
        <div class="sidebar-quick-access">
            <?php if ($puedeAlgunAccesoRapido): ?>
            <details class="sidebar-quick-details">
                <summary class="sidebar-link sidebar-quick-summary">
                    <span class="sidebar-link-icon"><i class="bi bi-lightning-charge"></i></span>
                    <span class="sidebar-link-text">Accesos rápidos</span>
                    <span class="sidebar-quick-caret"><i class="bi bi-chevron-down"></i></span>
                </summary>
                <div class="sidebar-quick-modules">
                <?php if ($puedeNuevoDiscipuloRapido): ?>
                <a class="sidebar-quick-text-link" href="<?= PUBLIC_URL ?>?url=personas/crear">
                    <i class="bi bi-person-plus"></i>
                    <span>Nuevo Discípulo</span>
                </a>
                <?php endif; ?>
                <?php if ($puedePlantillasWaRapido): ?>
                <a class="sidebar-quick-text-link" href="<?= PUBLIC_URL ?>?url=personas/plantillas-whatsapp">
                    <i class="bi bi-whatsapp"></i>
                    <span>Plantillas WhatsApp</span>
                </a>
                <?php endif; ?>
                <?php if ($puedeFormularioPublicoRapido): ?>
                <a class="sidebar-quick-text-link" href="<?= PUBLIC_URL ?>?url=registro_personas">
                    <i class="bi bi-ui-checks"></i>
                    <span>Formulario público</span>
                </a>
                <?php endif; ?>
                </div>
            </details>
            <?php endif; ?>
            <?php
            $puedeAlgunEnlaceComunidad = $puedeVerPeticiones || $puedeVerTransmisiones || $puedeVerEventosMenu;
            $navComunidadActiva = $isActive(['peticiones']) || $isActive(['transmisiones']) || $isActive(['eventos']);
            ?>
            <?php if ($puedeAlgunEnlaceComunidad): ?>
            <details class="sidebar-quick-details sidebar-quick-details--comunidad"<?= $navComunidadActiva ? ' open' : '' ?>>
                <summary class="sidebar-link sidebar-quick-summary">
                    <span class="sidebar-link-icon"><i class="bi bi-people"></i></span>
                    <span class="sidebar-link-text">Comunidad</span>
                    <span class="sidebar-quick-caret"><i class="bi bi-chevron-down"></i></span>
                </summary>
                <div class="sidebar-quick-modules">
                    <?php if ($puedeVerPeticiones): ?>
                    <a class="sidebar-quick-text-link<?= $isActive(['peticiones']) ? ' is-active' : '' ?>" href="<?= PUBLIC_URL ?>?url=peticiones">
                        <i class="bi bi-chat-heart"></i>
                        <span>Peticiones</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($puedeVerTransmisiones): ?>
                    <a class="sidebar-quick-text-link<?= $isActive(['transmisiones']) ? ' is-active' : '' ?>" href="<?= PUBLIC_URL ?>?url=transmisiones">
                        <i class="bi bi-broadcast"></i>
                        <span>Transmisiones</span>
                    </a>
                    <?php endif; ?>
                    <?php if ($puedeVerEventosMenu): ?>
                    <a class="sidebar-quick-text-link<?= $isActive(['eventos']) ? ' is-active' : '' ?>" href="<?= PUBLIC_URL ?>?url=eventos">
                        <i class="bi bi-calendar-event"></i>
                        <span>Eventos</span>
                    </a>
                    <?php endif; ?>
                </div>
            </details>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <nav class="sidebar-nav">
            <?php if ($useDynamicSidebar): ?>
                <?php include VIEWS . '/layout/_sidebar_nav_dynamic.php'; ?>
            <?php elseif ($esMenuMaestro && AuthController::puedeAccederHubMaterialCompleto()): ?>
            <a class="sidebar-link <?= $isActive(['home/material', 'celulas/materiales', 'teen']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=home/material">
                <span class="sidebar-link-icon"><i class="bi bi-folder2-open"></i></span><span class="sidebar-link-text">Material</span>
            </a>
            <?php elseif ($esMenuMaestro && $puedeVerMaterialCapDestino): ?>
            <a class="sidebar-link <?= $isActive(['home/material/capacitacion-destino', 'home/material']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=home/material/capacitacion-destino">
                <span class="sidebar-link-icon"><i class="bi bi-signpost-split-fill"></i></span><span class="sidebar-link-text">Material Cap. Destino</span>
            </a>
            <?php elseif ($esMenuDiscipulo): ?>
            <a class="sidebar-link <?= $isActive(['programas/evaluaciones', 'programas/tareas']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=programas/evaluaciones">
                <span class="sidebar-link-icon"><i class="bi bi-journal-check"></i></span><span class="sidebar-link-text">Evaluaciones</span>
            </a>
            <?php else: ?>
            <a class="sidebar-link <?= $isActive(['home']) && !$isActive(['home/material']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=home">
                <span class="sidebar-link-icon"><i class="bi bi-house-heart"></i></span><span class="sidebar-link-text">Inicio</span>
            </a>

            <?php if ($puedeVerGanarArea): ?>
            <a class="sidebar-link <?= $isActive(['personas', 'personas/ganar']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($urlEntradaGanarConsolidar, ENT_QUOTES, 'UTF-8') ?>">
                <span class="sidebar-link-icon"><i class="bi bi-person-heart"></i></span><span class="sidebar-link-text">Ganar-Consolidar</span>
            </a>
            <?php endif; ?>
            <!-- Eliminado acceso directo y comentarios de Material para evitar restos visibles -->

            <?php if ($puedeVerEnviar): ?>
            <a class="sidebar-link <?= $isActive(['celulas', 'asistencias']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=celulas">
                <span class="sidebar-link-icon"><i class="bi bi-send-check"></i></span><span class="sidebar-link-text">Enviar</span>
            </a>
            <?php endif; ?>

            <?php if (
                AuthController::esAdministrador()
                || AuthController::puede('programas:ver')
                || AuthController::puede('personas:ver')
                || AuthController::puede('personas_consulta:ver')
                || AuthController::puede('programas:ver_universidad_vida')
                || AuthController::puede('programas:ver_capacitacion_destino')
            ): ?>
            <a class="sidebar-link <?= $isActive(['programas']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=programas">
                <span class="sidebar-link-icon"><i class="bi bi-mortarboard"></i></span><span class="sidebar-link-text">Programas</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVerMinisterios): ?>
            <a class="sidebar-link <?= $isActive(['discipular/ministerios','discipular/ministerios/equipo-principal']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=discipular/ministerios/equipo-principal">
                <span class="sidebar-link-icon"><i class="bi bi-bank2"></i></span><span class="sidebar-link-text">Discipular</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVerRegistroTeensKids): ?>
            <a class="sidebar-link <?= $isActive(['teen']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=teen/registro-menores">
                <span class="sidebar-link-icon"><i class="bi bi-balloon-heart"></i></span><span class="sidebar-link-text">Teens</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVerNehemias): ?>
            <a class="sidebar-link <?= $isActive(['nehemias']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=nehemias/lista">
                <span class="sidebar-link-icon"><i class="bi bi-clipboard-data"></i></span><span class="sidebar-link-text">Nehemias</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVerReportes): ?>
            <a class="sidebar-link <?= ($currentUrl === 'reportes/dashboard-ganar') ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=reportes/dashboard-ganar">
                <span class="sidebar-link-icon"><i class="bi bi-speedometer2"></i></span><span class="sidebar-link-text">Dashboard Ganar</span>
            </a>
            <?php endif; ?>

            <?php
            require_once APP . '/Helpers/GestionSistemaAccess.php';
            $urlAdminSidebar = GestionSistemaAccess::puedeVerCuentas()
                ? 'cuentas'
                : (GestionSistemaAccess::puedeVerMatrizPermisos() ? 'permisos' : 'roles');
            ?>
            <?php if (GestionSistemaAccess::puedeVerBloqueAdministracion()): ?>
            <a class="sidebar-link <?= $isActive(['cuentas', 'roles', 'permisos']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($urlAdminSidebar) ?>">
                <span class="sidebar-link-icon"><i class="bi bi-people-fill"></i></span><span class="sidebar-link-text">Administración</span>
            </a>
            <?php endif; ?>
            <?php endif; ?>
        </nav>

        <div class="sidebar-user-card">
            <div class="sidebar-user-meta">
                <?php $linkedAccounts = $_SESSION['account_pool'] ?? []; ?>
                <?php $hasLinkedAccounts = is_array($linkedAccounts) && count($linkedAccounts) > 1; ?>
                <button type="button" class="account-menu-toggle" id="accountMenuToggle" aria-label="Ver cuentas vinculadas" title="Ver cuentas vinculadas">
                    <i class="bi bi-person-circle"></i>
                </button>
                <div>
                    <div class="user-name"><?= $_SESSION['usuario_nombre'] ?? 'Usuario' ?></div>
                    <div class="user-role"><?= $_SESSION['usuario_rol_nombre'] ?? 'Sin Rol' ?></div>
                </div>
                <?php if ($hasLinkedAccounts): ?>
                    <i class="bi bi-caret-down-fill account-menu-caret" aria-hidden="true"></i>
                <?php endif; ?>
            </div>

            <?php if ($hasLinkedAccounts): ?>
                <div class="account-switch-menu" id="accountSwitchMenu" aria-hidden="true">
                    <?php foreach ($linkedAccounts as $linkedAccount): ?>
                        <?php $isCurrent = ((int)($linkedAccount['id'] ?? 0) === (int)($_SESSION['usuario_id'] ?? 0)); ?>
                        <?php if ($isCurrent): ?>
                            <div class="account-switch-item current">
                                <i class="bi bi-check-circle-fill"></i>
                                <span><?= htmlspecialchars((string)($linkedAccount['nombre'] ?? 'Cuenta actual')) ?></span>
                            </div>
                        <?php else: ?>
                            <a href="<?= PUBLIC_URL ?>?url=auth/cambiar-usuario&id=<?= (int)$linkedAccount['id'] ?>" class="account-switch-item">
                                <i class="bi bi-person-check"></i>
                                <span><?= htmlspecialchars((string)($linkedAccount['nombre'] ?? 'Cuenta')) ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <a href="<?= PUBLIC_URL ?>?url=auth/login&modo=agregar" class="btn-logout sidebar-logout" style="margin-bottom: 8px;">
                <i class="bi bi-person-plus"></i> <span class="sidebar-link-text">Agregar cuenta</span>
            </a>
            <a href="<?= PUBLIC_URL ?>?url=auth/mi-cuenta" class="btn-logout sidebar-logout" style="margin-bottom: 8px;">
                <i class="bi bi-person-gear"></i> <span class="sidebar-link-text">Mi cuenta</span>
            </a>
            <a href="<?= PUBLIC_URL ?>?url=auth/logout" class="btn-logout sidebar-logout">
                <i class="bi bi-box-arrow-right"></i> <span class="sidebar-link-text">Salir</span>
            </a>
        </div>
    </aside>

    <div class="app-main">
        <button type="button" id="sidebarArrowToggle" class="sidebar-arrow-toggle" aria-label="Ocultar menú lateral">
            <i class="bi bi-chevron-left"></i>
        </button>

        <!-- Notificaciones eliminadas: campana/resumen removidos -->

        <main class="main-content">
