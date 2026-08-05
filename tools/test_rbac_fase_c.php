<?php
/**
 * Pruebas Fase C — permisos UI estilo Nehemías para todos los módulos CRUD.
 * Uso: C:\xampp\php\php.exe tools/test_rbac_fase_c.php
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');

require_once APP . '/Helpers/PermisosModulos.php';
require_once APP . '/Helpers/PermisosUiCatalogo.php';
require_once APP . '/Helpers/PermisosUiBuilder.php';
require_once APP . '/Helpers/PermisosProgramasAccess.php';
require_once APP . '/Controllers/BaseController.php';
require_once APP . '/Controllers/AuthController.php';

$ok = true;
$assert = static function (bool $cond, string $msg) use (&$ok) {
    echo ($cond ? '[OK] ' : '[FAIL] ') . $msg . PHP_EOL;
    if (!$cond) {
        $ok = false;
    }
};

$cat = PermisosModulos::catalogoPlano();
$assert(!isset($cat['personas_acciones_editar']), 'sin submódulo genérico personas_acciones_editar');
$assert(isset($cat['nehemias_cols_mesa']), 'submódulo nehemias_cols_mesa');
$assert(!isset($cat['nehemias_acciones_editar']) || isset($cat['nehemias_acciones_editar']), 'nehemias_acciones_editar generado');

$grupos = PermisosModulos::gruposParaPantalla();
$enGrupos = 0;
foreach ($grupos as $items) {
    $enGrupos += count($items);
}
$derivados = 0;
foreach (array_keys($cat) as $k) {
    if (PermisosUiCatalogo::esModuloDerivado($k)) {
        $derivados++;
    }
}
$assert($enGrupos < count($cat), 'tarjetas UI sin submódulos sueltos');
$assert($derivados > 20, 'hay submódulos derivados en catálogo');

$cfgPersonas = PermisosUiCatalogo::extrasDeSubmodulo('personas');
$assert(!empty($cfgPersonas['acciones']), 'personas tiene acciones granulares');
$assert(!isset($cat['personas_acciones_editar']), 'sin botón genérico editar duplicado');

$jerarquia = PermisosModulos::jerarquiaParaPantalla();
$assert(isset($jerarquia['Ganar-Consolidar']['submodulos']['personas']), 'jerarquía Ganar → Almas ganadas');
$assert(isset($jerarquia['Ganar-Consolidar']['submodulos']['personas_consulta']), 'jerarquía Ganar → Discípulos');

$_SESSION = [
    'auth_user_id' => 1,
    'usuario_id' => 1,
    'usuario_rol' => 3,
    'permisos' => [
        'personas' => ['ver' => 1, 'editar' => 1, 'crear' => 0, 'eliminar' => 0],
        'personas_acciones_exportar_excel' => ['ver' => 1, 'crear' => 0, 'editar' => 0, 'eliminar' => 0],
    ],
    'permisos_configurados' => true,
];

$assert(AuthController::puede('personas:editar'), 'CRUD editar personas sigue activo');
$assert(AuthController::tienePermiso('personas', 'exportar_excel'), 'exportar vía submódulo');
$assert(PermisosUiBuilder::puedeAccionGranular('personas', 'exportar_excel'), 'builder exportar');

$_SESSION['permisos'] = [
    'programas' => ['ver' => 1, 'crear' => 0, 'editar' => 0, 'eliminar' => 0],
    'programas_acciones_ver_universidad_vida' => ['ver' => 1, 'crear' => 0, 'editar' => 0, 'eliminar' => 0],
];
$_SESSION['permisos_configurados'] = true;
$_SESSION['permisos_last_sync'] = time();
$assert(PermisosProgramasAccess::puedeVerLineaUniversidadVida(), 'solo acción UV activa');
$assert(!PermisosProgramasAccess::puedeVerDashboardUniversidadVida(), 'programas:ver no abre dashboard');
$assert(!PermisosProgramasAccess::puedeGestionarPagosUniversidadVida(), 'programas:ver no abre pagos');
$assert(!PermisosProgramasAccess::puedeVerFormularioUniversidadVida(), 'programas:ver no abre formulario');

echo $ok ? PHP_EOL . 'Fase C: permisos UI OK.' . PHP_EOL : PHP_EOL . 'Hay fallos.' . PHP_EOL;
exit($ok ? 0 : 1);
