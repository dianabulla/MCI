<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'MCI Madrid Colombia' ?></title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/styles.css?v=20260223-38">
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
?>

<div class="app-shell">
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-main">
                <i class="bi bi-shield-fill"></i>
                <span class="sidebar-link-text">MCI Madrid</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a class="sidebar-link <?= $isActive(['home']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=home">
                <i class="bi bi-house"></i> <span class="sidebar-link-text">Inicio</span>
            </a>

            <?php if ($puedeVer('personas')): ?>
            <a class="sidebar-link <?= $isActive(['personas', 'personas/ganar']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=personas/ganar">
                <i class="bi bi-person-plus"></i> <span class="sidebar-link-text">Ganar</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('celulas')): ?>
            <a class="sidebar-link <?= $isActive(['celulas']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=celulas">
                <i class="bi bi-diagram-3"></i> <span class="sidebar-link-text">Células</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('ministerios')): ?>
            <a class="sidebar-link <?= $isActive(['ministerios']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=ministerios">
                <i class="bi bi-bank"></i> <span class="sidebar-link-text">Ministerios</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('asistencias')): ?>
            <a class="sidebar-link <?= $isActive(['asistencias']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=asistencias">
                <i class="bi bi-check2-square"></i> <span class="sidebar-link-text">Asistencias</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('peticiones')): ?>
            <a class="sidebar-link <?= $isActive(['peticiones']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=peticiones">
                <i class="bi bi-chat-heart"></i> <span class="sidebar-link-text">Peticiones</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('transmisiones')): ?>
            <a class="sidebar-link <?= $isActive(['transmisiones']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=transmisiones">
                <i class="bi bi-broadcast"></i> <span class="sidebar-link-text">Transmisiones</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('eventos')): ?>
            <a class="sidebar-link <?= $isActive(['eventos']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=eventos">
                <i class="bi bi-calendar-event"></i> <span class="sidebar-link-text">Eventos</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('entrega_obsequio')): ?>
            <a class="sidebar-link <?= $isActive(['entrega_obsequio', 'registro_obsequio']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=entrega_obsequio">
                <i class="bi bi-gift-fill"></i> <span class="sidebar-link-text">Obsequios</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('nehemias')): ?>
            <a class="sidebar-link <?= $isActive(['nehemias']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=nehemias/lista">
                <i class="bi bi-clipboard-data"></i> <span class="sidebar-link-text">Nehemias</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('reportes')): ?>
            <a class="sidebar-link <?= $isActive(['reportes']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=reportes">
                <i class="bi bi-bar-chart"></i> <span class="sidebar-link-text">Reportes</span>
            </a>
            <?php endif; ?>

            <?php if ($puedeVer('roles')): ?>
            <a class="sidebar-link <?= $isActive(['roles']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=roles">
                <i class="bi bi-person-badge"></i> <span class="sidebar-link-text">Roles</span>
            </a>
            <?php endif; ?>

            <?php if (AuthController::esAdministrador()): ?>
            <a class="sidebar-link <?= $isActive(['permisos']) ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=permisos">
                <i class="bi bi-shield-check"></i> <span class="sidebar-link-text">Permisos</span>
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
            <a href="<?= PUBLIC_URL ?>?url=auth/logout" class="btn-logout sidebar-logout">
                <i class="bi bi-box-arrow-right"></i> <span class="sidebar-link-text">Salir</span>
            </a>
        </div>
    </aside>

    <div class="app-main">
        <button type="button" id="sidebarArrowToggle" class="sidebar-arrow-toggle" aria-label="Ocultar menú lateral">
            <i class="bi bi-chevron-left"></i>
        </button>
        <main class="main-content">
