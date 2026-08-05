<?php
/**
 * Pruebas catálogo de módulos + RBAC Fase 3.
 * Uso: C:\xampp\php\php.exe tools\test_rbac_phase3.php
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');

require_once APP . '/Helpers/PermisosModulos.php';
require_once APP . '/Helpers/PermisosUiCatalogo.php';
require_once APP . '/Helpers/PermisosCatalogo.php';
require_once APP . '/Controllers/BaseController.php';
require_once APP . '/Controllers/AuthController.php';

$ok = true;
$assert = static function (bool $cond, string $msg) use (&$ok) {
    echo ($cond ? '[OK] ' : '[FAIL] ') . $msg . PHP_EOL;
    if (!$cond) {
        $ok = false;
    }
};

$catalogo = PermisosModulos::catalogoPlano();
$assert(count($catalogo) >= 35, 'catálogo tiene al menos 35 módulos');
$assert(isset($catalogo['cuentas']), 'módulo cuentas en catálogo');
$assert(isset($catalogo['nehemias_cols_mesa']), 'módulo nehemias_cols_mesa en catálogo');

$grupos = PermisosModulos::gruposParaPantalla();
$totalEnGrupos = 0;
foreach ($grupos as $items) {
    $totalEnGrupos += count($items);
}
$modulosEnTarjetas = 0;
foreach (array_keys($catalogo) as $k) {
    if (!PermisosUiCatalogo::esModuloDerivado($k)) {
        $modulosEnTarjetas++;
    }
}
$assert($totalEnGrupos === $modulosEnTarjetas, 'módulos base (no derivados) están en grupos UI');

$acciones = PermisosCatalogo::accionesPorModulo();
$assert(isset($acciones['programas']['coordinacion_total']), 'acción coordinacion_total programas');
$assert(isset($acciones['asistencias']['exportar_excel']), 'acción exportar asistencias');

$_SESSION = [
    'auth_user_id' => 1,
    'usuario_id' => 1,
    'usuario_rol' => 3,
    'usuario_rol_nombre' => 'Lider',
    'active_context' => 'lider',
    'permisos' => ['ministerios' => ['ver' => 1, 'editar' => 0]],
    'permisos_configurados' => true,
];

$assert(AuthController::puedeVerModulo('ministerios'), 'puedeVerModulo ministerios');
$assert(!AuthController::puede('ministerios:editar'), 'niega ministerios editar');
$assert(AuthController::puede('ministerios:ver'), 'puede ministerios:ver');

echo $ok ? PHP_EOL . 'Fase 3: catálogo y API OK.' . PHP_EOL : PHP_EOL . 'Hay fallos.' . PHP_EOL;
exit($ok ? 0 : 1);
