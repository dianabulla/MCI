<?php

/**

 * Pruebas RBAC Fase 1 + Fase 2 (CLI).

 * Uso: C:\xampp\php\php.exe tools\test_rbac_phase2.php

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



// --- Fase 1 ---

$assert(AuthController::puede('celulas:ver'), 'puede celulas:ver');

$assert(!AuthController::puede('celulas:eliminar'), 'niega celulas:eliminar');



MenuBuilder::sincronizarSesion();

$assert(!empty($_SESSION['sidebar_menu']), 'sidebar_menu generado');

$assert(RouteGuard::puedeAcceder('celulas'), 'ruta celulas permitida');

$assert(!RouteGuard::puedeAcceder('celulas/crear'), 'celulas/crear denegada sin crear');



$_SESSION['active_context'] = 'discipulo';

$_SESSION['usuario_rol_nombre'] = 'Discipulo';

$_SESSION['permisos'] = ['discipular_evaluaciones' => ['ver' => 1]];

MenuBuilder::sincronizarSesion();

$assert(!RouteGuard::puedeAcceder('celulas'), 'discipulo no accede celulas');

$assert(RouteGuard::puedeAcceder('programas/ir-clase'), 'discipulo accede ir-clase');



// --- Fase 2: programas ---

$_SESSION['active_context'] = 'lider';
$_SESSION['usuario_rol_nombre'] = 'Lider de Celula';

$_SESSION['permisos'] = [

    'programas' => ['ver' => 0, 'ver_universidad_vida' => 1, 'ver_capacitacion_destino' => 0],

];

$assert(!AuthController::usaVistaDiscipuloCapacitacionDestino(), 'lider con contexto lider no fuerza vista discipulo por cap destino');
$assert(AuthController::obtenerUrlInicioSesion() === 'home', 'inicio lider va a home');

$_SESSION['active_context'] = 'discipulo';
$_SESSION['usuario_rol_nombre'] = 'Discipulo';
$assert(AuthController::usaVistaDiscipuloCapacitacionDestino(), 'contexto discipulo activa vista alumno');
$assert(AuthController::obtenerUrlInicioSesion() === 'programas/evaluaciones', 'inicio discipulo va a evaluaciones');

$_SESSION['active_context'] = 'lider';
$_SESSION['usuario_rol_nombre'] = 'Lider de Celula';
$_SESSION['permisos'] = [

    'programas' => ['ver' => 0, 'ver_universidad_vida' => 1, 'ver_capacitacion_destino' => 0],

];

$assert(AuthController::puedeAccederModuloProgramas(), 'UV puede acceder programas');

$assert(RouteGuard::puedeAcceder('programas'), 'ruta programas UV');

$assert(!RouteGuard::puedeAcceder('reportes'), 'sin reportes denegado');



$_SESSION['permisos'] = ['personas_consulta' => ['ver' => 1]];

$assert(AuthController::puedeVerPersonasConsulta(), 'personas_consulta ver');

$assert(RouteGuard::puedeAcceder('personas'), 'personas consulta lista');

$assert(!RouteGuard::puedeAcceder('personas/ganar'), 'consulta sin ganar');



$_SESSION['permisos'] = ['personas' => ['ver' => 1, 'editar' => 1]];

$assert(RouteGuard::puedeAcceder('personas/ganar'), 'ganar con personas ver');

$assert(RouteGuard::puedeAcceder('personas/editar'), 'personas editar');



// Admin

$_SESSION['usuario_rol'] = 6;

$_SESSION['usuario_rol_nombre'] = 'Administrador';

$_SESSION['permisos'] = [];

$assert(RouteGuard::puedeAcceder('cuentas'), 'admin cuentas');

$assert(RouteGuard::puedeAcceder('permisos'), 'admin permisos');



$_SESSION['usuario_rol'] = 3;

$_SESSION['usuario_rol_nombre'] = 'Lider de Celula';

$_SESSION['permisos'] = ['celulas' => ['ver' => 1]];

$assert(!RouteGuard::puedeAcceder('cuentas'), 'no admin sin cuentas');



echo $ok ? PHP_EOL . 'Todas las pruebas pasaron.' . PHP_EOL : PHP_EOL . 'Hay fallos.' . PHP_EOL;

exit($ok ? 0 : 1);


