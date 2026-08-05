<?php
/**
 * Pruebas RBAC Fase A — rutas destructivas y permisos en controladores.
 * Uso: C:\xampp\php\php.exe tools/test_rbac_fase_a.php
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
require_once APP . '/Helpers/PermisoGuard.php';
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

function simularRol(array $permisos, int $idRol = 3, string $nombreRol = 'Lider de Celula'): void {
    $_SESSION = [];
    $_SESSION['auth_user_id'] = 99;
    $_SESSION['usuario_id'] = 99;
    $_SESSION['usuario_rol'] = $idRol;
    $_SESSION['usuario_rol_nombre'] = $nombreRol;
    $_SESSION['permisos'] = $permisos;
    $_SESSION['permisos_configurados'] = !empty($permisos);
    $_SESSION['permisos_last_sync'] = time();
    $_SESSION['active_context'] = 'lider';
}

// Rol con solo ver en eventos — no debe crear ni eliminar evento
simularRol(['eventos' => ['ver' => 1, 'crear' => 0, 'editar' => 0, 'eliminar' => 0]]);
$assert(RouteGuard::puedeAcceder('eventos'), 'eventos lista con ver');
$assert(!RouteGuard::puedeAcceder('eventos/crear'), 'eventos/crear denegado sin crear');
$assert(!RouteGuard::puedeAcceder('eventos/eliminar'), 'eventos/eliminar denegado sin eliminar');
$assert(!RouteGuard::puedeAcceder('eventos/modulo/guardar'), 'eventos/modulo/guardar denegado');

// Sin permiso eliminar en escuelas ni personas
simularRol([
    'escuelas_formacion' => ['ver' => 1, 'crear' => 0, 'editar' => 0, 'eliminar' => 0],
    'personas' => ['ver' => 1, 'crear' => 0, 'editar' => 0, 'eliminar' => 0],
]);
$assert(!RouteGuard::puedeAcceder('escuelas_formacion/inscritos/eliminar'), 'inscritos/eliminar denegado sin permiso');
$assert(!RouteGuard::puedeAcceder('home/eliminar-inscripcion-formacion'), 'home eliminar inscripcion denegado');

simularRol([
    'escuelas_formacion' => ['ver' => 1, 'crear' => 0, 'editar' => 0, 'eliminar' => 1],
    'personas' => ['ver' => 1, 'crear' => 0, 'editar' => 0, 'eliminar' => 0],
]);
$assert(RouteGuard::puedeAcceder('escuelas_formacion/inscritos/eliminar'), 'inscritos/eliminar con escuelas eliminar');
$assert(RouteGuard::puedeAcceder('home/eliminar-inscripcion-formacion'), 'home eliminar con escuelas eliminar');

simularRol(['personas' => ['ver' => 1, 'crear' => 0, 'editar' => 0, 'eliminar' => 1]]);
$assert(RouteGuard::puedeAcceder('home/eliminar-inscripcion-formacion'), 'home eliminar inscripcion con personas eliminar');

// Ministerios discipular
simularRol(['ministerios' => ['ver' => 1, 'crear' => 0, 'editar' => 0, 'eliminar' => 0]]);
$assert(!RouteGuard::puedeAcceder('discipular/ministerios/eliminar'), 'ministerios eliminar denegado');

simularRol(['ministerios' => ['ver' => 1, 'crear' => 0, 'editar' => 0, 'eliminar' => 1]]);
$assert(RouteGuard::puedeAcceder('discipular/ministerios/eliminar'), 'ministerios eliminar permitido');

echo $fallos === 0 ? "\nTodas las pruebas Fase A pasaron.\n" : "\n{$fallos} prueba(s) fallaron.\n";
exit($fallos > 0 ? 1 : 0);
