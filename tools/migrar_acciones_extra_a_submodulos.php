<?php
/**
 * Copia flags de Acciones_Extra (JSON) a submódulos {modulo}_acciones_{clave}.
 * Uso: C:\xampp\php\php.exe tools/migrar_acciones_extra_a_submodulos.php [--dry-run]
 */
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');

require_once APP . '/Config/Database.php';
require_once APP . '/Helpers/PermisosCatalogo.php';
require_once APP . '/Helpers/PermisosModulos.php';
require_once APP . '/Helpers/PermisosUiCatalogo.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);
$db = Database::getInstance()->getConnection();

$insertados = 0;
$actualizados = 0;

foreach (PermisosUiCatalogo::configuracionPorModuloBase() as $base => $cfg) {
    foreach (array_keys($cfg['acciones']) as $accion) {
        if (in_array($accion, ['crear', 'editar', 'eliminar'], true)) {
            continue;
        }
        $modSub = PermisosUiCatalogo::claveAccion($base, $accion);
        $sql = "SELECT p.Id_Permiso, p.Id_Rol, p.Acciones_Extra
                FROM permisos p
                WHERE p.Modulo = ? AND p.Acciones_Extra IS NOT NULL AND TRIM(p.Acciones_Extra) <> ''";
        $stmt = $db->prepare($sql);
        $stmt->execute([$base]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mapa = PermisosCatalogo::mapaDesdeFila($row);
            if (empty($mapa[$accion])) {
                continue;
            }
            $idRol = (int)$row['Id_Rol'];
            $chk = $db->prepare('SELECT Id_Permiso, Puede_Ver FROM permisos WHERE Id_Rol = ? AND Modulo = ?');
            $chk->execute([$idRol, $modSub]);
            $ex = $chk->fetch(PDO::FETCH_ASSOC);
            if ($ex) {
                if ((int)$ex['Puede_Ver'] === 1) {
                    continue;
                }
                if (!$dryRun) {
                    $db->prepare('UPDATE permisos SET Puede_Ver = 1 WHERE Id_Permiso = ?')
                        ->execute([(int)$ex['Id_Permiso']]);
                }
                $actualizados++;
            } else {
                if (!$dryRun) {
                    $ins = $db->prepare(
                        'INSERT INTO permisos (Id_Rol, Modulo, Puede_Ver, Puede_Crear, Puede_Editar, Puede_Eliminar)
                         VALUES (?, ?, 1, 0, 0, 0)'
                    );
                    $ins->execute([$idRol, $modSub]);
                }
                $insertados++;
            }
        }
    }
}

echo ($dryRun ? '[DRY-RUN] ' : '') . "Migración Acciones_Extra → submódulos UI\n";
echo "Insertados: {$insertados}\n";
echo "Actualizados: {$actualizados}\n";
