<?php
/**
 * Pruebas RBAC Fase B — delegación permisos y cuentas.
 * Uso: C:\xampp\php\php.exe tools/test_rbac_fase_b.php
 */
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
define('VIEWS', ROOT . '/views');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once APP . '/Controllers/BaseController.php';
require_once APP . '/Helpers/PermisosCatalogo.php';
require_once APP . '/Helpers/GestionSistemaAccess.php';
require_once APP . '/Helpers/RouteGuard.php';
require_once APP . '/Controllers/AuthController.php';

$fallos = 0;
$assert = static function (bool $cond, string $msg) use (&$fallos): void {
    if ($cond) {
        echo "[OK] {$msg}\n";
        return;
    }
    echo "[FAIL] {$msg}\n";
    $fallos++;
};

function simularRol(array $permisos, int $idRol = 3): void {
    $_SESSION = [];
    $_SESSION['auth_user_id'] = 99;
    $_SESSION['usuario_id'] = 99;
    $_SESSION['usuario_rol'] = $idRol;
    $_SESSION['usuario_rol_nombre'] = 'Coordinador';
    $_SESSION['permisos'] = $permisos;
    $_SESSION['permisos_configurados'] = !empty($permisos);
    $_SESSION['permisos_last_sync'] = time();
    $_SESSION['active_context'] = 'lider';
}

// Sin permisos sistema
simularRol(['personas' => ['ver' => 1, 'crear' => 0, 'editar' => 0, 'eliminar' => 0]]);
$assert(!GestionSistemaAccess::puedeVerMatrizPermisos(), 'sin permisos:ver no ve matriz');
$assert(!RouteGuard::puedeAcceder('permisos'), 'ruta permisos denegada');
$assert(!RouteGuard::puedeAcceder('cuentas'), 'ruta cuentas denegada');

// Solo ver permisos
simularRol(['permisos' => ['ver' => 1, 'crear' => 0, 'editar' => 0, 'eliminar' => 0]]);
$assert(GestionSistemaAccess::puedeVerMatrizPermisos(), 'permisos:ver ve matriz');
$assert(!GestionSistemaAccess::puedeEditarMatrizPermisos(), 'permisos:ver no edita');
$assert(RouteGuard::puedeAcceder('permisos'), 'ruta permisos permitida');
$assert(!RouteGuard::puedeAcceder('permisos/actualizar'), 'actualizar denegado sin editar');

// Editar permisos
simularRol(['permisos' => ['ver' => 1, 'crear' => 0, 'editar' => 1, 'eliminar' => 0]]);
$assert(GestionSistemaAccess::puedeEditarMatrizPermisos(), 'permisos:editar edita');
$assert(RouteGuard::puedeAcceder('permisos/actualizar'), 'actualizar permitido');

// Cuentas ver / crear
simularRol(['cuentas' => ['ver' => 1, 'crear' => 1, 'editar' => 0, 'eliminar' => 0]]);
$assert(GestionSistemaAccess::puedeVerCuentas(), 'cuentas:ver');
$assert(GestionSistemaAccess::puedeCrearCuentas(), 'cuentas:crear');
$assert(!GestionSistemaAccess::puedeEditarCuentas(), 'sin cuentas:editar');
$assert(RouteGuard::puedeAcceder('cuentas'), 'ruta cuentas');
$assert(RouteGuard::puedeAcceder('cuentas/crear'), 'ruta cuentas/crear');
$assert(!RouteGuard::puedeAcceder('cuentas/editar'), 'cuentas/editar denegado');

echo $fallos === 0 ? "\nFase B: todas las pruebas pasaron.\n" : "\n{$fallos} prueba(s) fallaron.\n";
exit($fallos > 0 ? 1 : 0);
