<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'MCI Madrid Colombia' ?></title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/styles.css?v=20260422-home-refresh-1">
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

$puedeVer = function(string $modulo) {
    return AuthController::esAdministrador() || AuthController::tienePermiso($modulo, 'ver');
};

$puedeVerGanar = $puedeVer('personas');
$puedeVerConsolidar = $puedeVer('personas');
$puedeVerDiscipular = $puedeVer('personas');
$puedeVerEnviar = $puedeVer('celulas');
$puedeVerMaterial = AuthController::esAdministrador()
    || AuthController::tienePermiso('materiales_celulas', 'ver')
    || AuthController::tienePermiso('teen', 'ver')
    || AuthController::tienePermiso('eventos', 'ver');
$puedeVerMinisterios = $puedeVer('ministerios');
$puedeVerRegistroTeensKids = $puedeVer('teen');
$puedeVerEventosMenu = AuthController::esAdministrador() || AuthController::tienePermiso('eventos', 'ver');

$puedeVerPendientesGanar = $puedeVerGanar;
$totalPendientesGanar = 0;
$totalPendientesPorConectar = 0;
$totalNuevasAlmasGanadas = 0;
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

        // Usar la misma lógica de la bandeja de notificaciones para evitar desfases.
        $personasCampana = $personaCampanaModel->getAllWithRole($filtroRolPendientes, true);

        $normalizarRol = static function($rolNombre) {
            $rol = strtolower(trim((string)$rolNombre));
            return strtr($rol, [
                'á' => 'a',
                'é' => 'e',
                'í' => 'i',
                'ó' => 'o',
                'ú' => 'u',
                'ü' => 'u',
                'ñ' => 'n'
            ]);
        };

        $esRolLiderazgo = static function($rolNombre) use ($normalizarRol) {
            $rol = $normalizarRol($rolNombre);
            return strpos($rol, 'pastor') !== false
                || strpos($rol, 'lider de 12') !== false
                || strpos($rol, 'lider 12') !== false
                || strpos($rol, 'lideres de 12') !== false
                || strpos($rol, 'lider de celula') !== false
                || strpos($rol, 'lider celula') !== false;
        };

        $esRolDiscipular = static function($rolNombre) use ($normalizarRol) {
            $rol = $normalizarRol($rolNombre);
            return strpos($rol, 'discipul') !== false || strpos($rol, 'disipul') !== false;
        };

        $totalPendientesPorConectar = 0;
        $totalNuevasAlmasGanadas = 0;
        $idsPendientesPorConectar = [];
        $idsNuevasAlmasGanadas = [];

        foreach ((array)$personasCampana as $personaTmp) {
            $esNuevo = ((int)($personaTmp['Es_Antiguo'] ?? 0) !== 1);
            $esAntiguo = !$esNuevo;

            $checklistRaw = (string)($personaTmp['Escalera_Checklist'] ?? '');
            $noDisponible = false;
            if ($checklistRaw !== '') {
                $checklist = json_decode($checklistRaw, true);
                if (is_array($checklist) && !empty($checklist['Ganar'][5])) {
                    $noDisponible = true;
                }
            }
            if ($noDisponible) {
                continue;
            }

            $idMinisterio = (int)($personaTmp['Id_Ministerio'] ?? 0);
            $idLider = (int)($personaTmp['Id_Lider'] ?? 0);
            $idCelula = (int)($personaTmp['Id_Celula'] ?? 0);

            if ($esNuevo && !$esRolLiderazgo((string)($personaTmp['Nombre_Rol'] ?? ''))) {
                $totalNuevasAlmasGanadas++;
                $idPersonaTmp = (int)($personaTmp['Id_Persona'] ?? 0);
                if ($idPersonaTmp > 0) {
                    $idsNuevasAlmasGanadas[$idPersonaTmp] = true;
                }
            }

            if (
                $esAntiguo
                && ($idMinisterio <= 0 || $idLider <= 0 || $idCelula <= 0)
                && !$esRolLiderazgo((string)($personaTmp['Nombre_Rol'] ?? ''))
                && $esRolDiscipular((string)($personaTmp['Nombre_Rol'] ?? ''))
            ) {
                $totalPendientesPorConectar++;
                $idPersonaTmp = (int)($personaTmp['Id_Persona'] ?? 0);
                if ($idPersonaTmp > 0) {
                    $idsPendientesPorConectar[$idPersonaTmp] = true;
                }
            }
        }

        $totalPendientesGanar = $totalNuevasAlmasGanadas;
        $idsCampanaUnicos = $idsPendientesPorConectar + $idsNuevasAlmasGanadas;
        $totalCampanaGanar = count($idsCampanaUnicos);
    } catch (Exception $e) {
        error_log('No se pudo cargar contador de pendientes en Ganar: ' . $e->getMessage());
        $totalPendientesGanar = 0;
        $totalPendientesPorConectar = 0;
        $totalNuevasAlmasGanadas = 0;
        $totalCampanaGanar = 0;
    }
}
?>

<div class="app-shell">
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-main">
                <img src="<?= ASSETS_URL ?>/img/logo-mci-madrid.svg" alt="Logo MCI Madrid" class="sidebar-brand-logo">
                <div class="sidebar-brand-copy">
                    <span class="sidebar-link-text">MCI Madrid</span>
                    <small class="sidebar-brand-subtitle">Vision y seguimiento</small>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a class="sidebar-link <?= $isActive(['home']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=home">
                <span class="sidebar-link-icon"><i class="bi bi-house-heart"></i></span><span class="sidebar-link-text">Inicio</span>
            </a>

            <?php if ($puedeVerGanar): ?>
            <a class="sidebar-link <?= $isActive(['personas', 'personas/ganar']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=personas/ganar">
                <span class="sidebar-link-icon"><i class="bi bi-person-heart"></i></span><span class="sidebar-link-text">Ganar</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVerConsolidar): ?>
            <a class="sidebar-link <?= $isActive(['home/consolidar']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=home/consolidar">
                <span class="sidebar-link-icon"><i class="bi bi-people-fill"></i></span><span class="sidebar-link-text">Consolidar</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVerDiscipular): ?>
            <a class="sidebar-link <?= $isActive(['home/discipular']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=home/discipular">
                <span class="sidebar-link-icon"><i class="bi bi-journal-richtext"></i></span><span class="sidebar-link-text">Discipular</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVerEnviar): ?>
            <a class="sidebar-link <?= $isActive(['celulas']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=celulas">
                <span class="sidebar-link-icon"><i class="bi bi-send-check"></i></span><span class="sidebar-link-text">Enviar</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVerMaterial): ?>
            <a class="sidebar-link <?= $isActive(['home/material']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=home/material">
                <span class="sidebar-link-icon"><i class="bi bi-book-half"></i></span><span class="sidebar-link-text">Material</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVerMinisterios): ?>
            <a class="sidebar-link <?= $isActive(['ministerios']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=ministerios">
                <span class="sidebar-link-icon"><i class="bi bi-bank2"></i></span><span class="sidebar-link-text">Ministerios</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVerRegistroTeensKids): ?>
            <a class="sidebar-link <?= $isActive(['teen']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=teen/registro-menores">
                <span class="sidebar-link-icon"><i class="bi bi-balloon-heart"></i></span><span class="sidebar-link-text">Registro Teens y Kids</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('asistencias')): ?>
            <a class="sidebar-link <?= $isActive(['asistencias']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=asistencias">
                <span class="sidebar-link-icon"><i class="bi bi-check2-square"></i></span><span class="sidebar-link-text">Asistencias</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('peticiones')): ?>
            <a class="sidebar-link <?= $isActive(['peticiones']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=peticiones">
                <span class="sidebar-link-icon"><i class="bi bi-chat-heart"></i></span><span class="sidebar-link-text">Peticiones</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('transmisiones')): ?>
            <a class="sidebar-link <?= $isActive(['transmisiones']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=transmisiones">
                <span class="sidebar-link-icon"><i class="bi bi-broadcast"></i></span><span class="sidebar-link-text">Transmisiones</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVerEventosMenu): ?>
            <a class="sidebar-link <?= $isActive(['eventos']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=eventos">
                <span class="sidebar-link-icon"><i class="bi bi-calendar-event"></i></span><span class="sidebar-link-text">Eventos</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('entrega_obsequio')): ?>
            <a class="sidebar-link <?= $isActive(['entrega_obsequio', 'registro_obsequio']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=entrega_obsequio">
                <span class="sidebar-link-icon"><i class="bi bi-gift-fill"></i></span><span class="sidebar-link-text">Obsequios</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('nehemias')): ?>
            <a class="sidebar-link <?= $isActive(['nehemias']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=nehemias/lista">
                <span class="sidebar-link-icon"><i class="bi bi-clipboard-data"></i></span><span class="sidebar-link-text">Nehemias</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('reportes')): ?>
            <a class="sidebar-link <?= $isActive(['reportes']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=reportes">
                <span class="sidebar-link-icon"><i class="bi bi-bar-chart"></i></span><span class="sidebar-link-text">Reportes</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('roles')): ?>
            <a class="sidebar-link <?= $isActive(['roles']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=roles">
                <span class="sidebar-link-icon"><i class="bi bi-person-badge"></i></span><span class="sidebar-link-text">Roles</span>
            </a>
            <?php endif; ?>

            <?php if (AuthController::esAdministrador()): ?>
            <a class="sidebar-link <?= $isActive(['cuentas']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=cuentas">
                <span class="sidebar-link-icon"><i class="bi bi-people-fill"></i></span><span class="sidebar-link-text">Cuentas</span>
            </a>
            <a class="sidebar-link <?= $isActive(['permisos']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=permisos">
                <span class="sidebar-link-icon"><i class="bi bi-shield-check"></i></span><span class="sidebar-link-text">Permisos</span>
            </a>
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

        <?php if ($puedeVerPendientesGanar): ?>
        <button
            type="button"
            class="ganar-alert-bell"
            id="ganarAlertBell"
            title="Pendientes por conectar: <?= (int)$totalPendientesPorConectar ?> · Nuevas en Almas ganadas: <?= (int)$totalNuevasAlmasGanadas ?>"
            aria-label="Abrir resumen de notificaciones"
            aria-expanded="false"
            aria-controls="ganarAlertPanel"
            data-pendientes="<?= (int)$totalCampanaGanar ?>"
            data-pendientes-conectar="<?= (int)$totalPendientesPorConectar ?>"
            data-nuevas-ganadas="<?= (int)$totalNuevasAlmasGanadas ?>"
        >
            <i class="bi bi-bell-fill"></i>
            <?php if ((int)$totalCampanaGanar > 0): ?>
            <span class="ganar-alert-badge"><?= (int)$totalCampanaGanar ?></span>
            <?php endif; ?>
        </button>

        <div class="ganar-alert-panel" id="ganarAlertPanel" aria-hidden="true">
            <div class="ganar-alert-panel-head">
                <strong>Notificaciones</strong>
                <button type="button" class="ganar-alert-panel-close" id="ganarAlertPanelClose" aria-label="Cerrar notificaciones">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <a href="<?= PUBLIC_URL ?>?url=personas&panel=pendientes_ubicacion" class="ganar-alert-item">
                <span class="ganar-alert-item-icon icon-conectar"><i class="bi bi-diagram-3"></i></span>
                <span class="ganar-alert-item-content">
                    <span class="ganar-alert-item-title">Pendientes por conectar</span>
                    <span class="ganar-alert-item-desc">Discipulos antiguos con asignacion incompleta</span>
                </span>
                <span class="ganar-alert-item-count"><?= (int)$totalPendientesPorConectar ?></span>
            </a>

            <a href="<?= PUBLIC_URL ?>?url=personas/ganar" class="ganar-alert-item">
                <span class="ganar-alert-item-icon icon-nuevos"><i class="bi bi-person-plus-fill"></i></span>
                <span class="ganar-alert-item-content">
                    <span class="ganar-alert-item-title">Nuevas en Almas ganadas</span>
                    <span class="ganar-alert-item-desc">Personas nuevas asignadas o recién llegadas</span>
                </span>
                <span class="ganar-alert-item-count"><?= (int)$totalNuevasAlmasGanadas ?></span>
            </a>
        </div>

        <div
            class="ganar-alert-toast"
            id="ganarAlertToast"
            data-pendientes="<?= (int)$totalCampanaGanar ?>"
            data-show-on-login="<?= $mostrarAlertaIngresoGanar ? '1' : '0' ?>"
            role="status"
            aria-live="polite"
        >
            <div class="ganar-alert-toast-content">
                <i class="bi bi-bell-fill"></i>
                <div>
                    <strong>Resumen de seguimiento</strong>
                    <p>
                        Pendientes por conectar: <strong><?= (int)$totalPendientesPorConectar ?></strong><br>
                        Nuevas en Almas ganadas: <strong><?= (int)$totalNuevasAlmasGanadas ?></strong>
                    </p>
                </div>
            </div>
            <button type="button" class="ganar-alert-toast-close" id="ganarAlertToastClose" aria-label="Cerrar aviso">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <?php endif; ?>

        <main class="main-content">
