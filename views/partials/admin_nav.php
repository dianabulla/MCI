<?php
/**
 * Pestañas Cuentas / Roles / Permisos según permisos del usuario.
 * @var string $admin_nav_active Una de: cuentas, roles, permisos
 */
require_once APP . '/Helpers/GestionSistemaAccess.php';
$adminNavActive = (string)($admin_nav_active ?? '');
$puedeVerCuentasNav = GestionSistemaAccess::puedeVerCuentas();
$puedeVerPermisosNav = GestionSistemaAccess::puedeVerMatrizPermisos();
$puedeVerRolesNav = class_exists('AuthController') && AuthController::puedeVerModulo('roles');

if (!$puedeVerCuentasNav && !$puedeVerPermisosNav && !$puedeVerRolesNav) {
    return;
}
?>
<div class="page-header" style="margin-bottom: 20px;">
    <h2 style="margin: 0;">Administración</h2>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body page-actions personas-mobile-stack" style="display:flex; gap:8px; flex-wrap:wrap;">
        <?php if ($puedeVerCuentasNav): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=cuentas" class="btn btn-nav-pill<?= $adminNavActive === 'cuentas' ? ' active' : '' ?>">Cuentas</a>
        <?php endif; ?>
        <?php if ($puedeVerRolesNav): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=roles" class="btn btn-nav-pill<?= $adminNavActive === 'roles' ? ' active' : '' ?>">Roles</a>
        <?php endif; ?>
        <?php if ($puedeVerPermisosNav): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=permisos" class="btn btn-nav-pill<?= $adminNavActive === 'permisos' ? ' active' : '' ?>">Permisos</a>
        <?php endif; ?>
    </div>
</div>
