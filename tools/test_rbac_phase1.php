<?php
/**
 * Pruebas rápidas Fase 1 RBAC (CLI).
 * Uso: C:\xampp\php\php.exe tools\test_rbac_phase1.php
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
require_once APP . '/Helpers/MenuBuilder.php';
require_once APP . '/Helpers/RouteGuard.php';
require_once APP . '/Controllers/AuthController.php';

$_SESSION = [];
$_SESSION['auth_user_id'] = 1;
$_SESSION['usuario_id'] = 1;
$_SESSION['usuario_rol'] = 3;
$_SESSION['usuario_rol_nombre'] = 'Lider de Celula';
$_SESSION['active_context'] = 'lider';
$_SESSION['permisos'] = [
    'celulas' => ['ver' => 1, 'crear' => 0, 'editar' => 1, 'eliminar' => 0],
];
$_SESSION['permisos_configurados'] = true;

$ok = true;
$assert = static function (bool $cond, string $msg) use (&$ok) {
    echo ($cond ? '[OK] ' : '[FAIL] ') . $msg . PHP_EOL;
    if (!$cond) {
        $ok = false;
    }
};

$assert(AuthController::puede('celulas:ver'), 'puede celulas:ver');
$assert(!AuthController::puede('celulas:eliminar'), 'niega celulas:eliminar');
$assert(!AuthController::puede('ministerios:ver'), 'niega modulo no configurado');

MenuBuilder::sincronizarSesion();
$assert(!empty($_SESSION['sidebar_menu']), 'sidebar_menu generado');
$assert(!empty($_SESSION['permisos_planos']), 'permisos_planos generado');

$ids = array_column($_SESSION['sidebar_menu'], 'id');
$assert(in_array('celulas', $ids, true), 'menu incluye celulas para lider con permiso');

$assert(RouteGuard::puedeAcceder('celulas'), 'ruta celulas permitida');
$assert(!RouteGuard::puedeAcceder('celulas/crear'), 'ruta celulas/crear denegada sin crear');

$_SESSION['active_context'] = 'discipulo';
$_SESSION['usuario_rol_nombre'] = 'Discipulo';
MenuBuilder::sincronizarSesion();
$assert(count($_SESSION['sidebar_menu']) === 1, 'menu discipulo un item');
$assert(!RouteGuard::puedeAcceder('celulas'), 'discipulo no accede celulas');

$_SESSION['active_context'] = 'maestro';
$_SESSION['usuario_rol_nombre'] = 'Maestro';
MenuBuilder::sincronizarSesion();
$assert(count($_SESSION['sidebar_menu']) >= 1, 'menu maestro tiene items');
$assert(!RouteGuard::puedeAcceder('celulas'), 'maestro no accede celulas por layout');
$assert(RouteGuard::puedeAcceder('programas/evaluaciones'), 'maestro accede evaluaciones cap destino');
$assert(AuthController::puedeGestionarEvaluacionesDiscipular(), 'maestro puede gestionar evaluaciones');

echo $ok ? PHP_EOL . 'Todas las pruebas pasaron.' . PHP_EOL : PHP_EOL . 'Hay fallos.' . PHP_EOL;
exit($ok ? 0 : 1);
