<?php
/**
 * Script para debuguear roles de un usuario lider de celula
 */

// Requerir config
define('APP', dirname(__FILE__) . '/app');
define('BASE_URL', 'http://localhost/mcimadrid');

require_once APP . '/Config/conexion.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/UserRole.php';
require_once APP . '/Models/Rol.php';
require_once APP . '/Models/EscuelaFormacionInscripcion.php';

$db = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$personaModel = new Persona();
$userRoleModel = new UserRole();
$rolModel = new Rol();
$inscripcionModel = new EscuelaFormacionInscripcion();

// Obtener ID del usuario desde parametro
$idPersona = (int)($_GET['id'] ?? 0);

if ($idPersona <= 0) {
    echo '<h3>Uso: debug_roles_lider.php?id=ID_PERSONA</h3>';
    echo '<p>Ejemplo: debug_roles_lider.php?id=123</p>';
    exit;
}

$persona = $personaModel->getById($idPersona);
if (empty($persona)) {
    echo "No se encontró persona con ID: $idPersona";
    exit;
}

echo '<h2>Información del Usuario</h2>';
echo '<p><strong>ID Persona:</strong> ' . htmlspecialchars($idPersona) . '</p>';
echo '<p><strong>Nombre:</strong> ' . htmlspecialchars($persona['Nombre'] . ' ' . $persona['Apellido']) . '</p>';
echo '<p><strong>Rol Legacy (tabla persona):</strong> ' . htmlspecialchars($persona['Id_Rol'] ?? 'N/A') . ' - ' . htmlspecialchars($persona['Nombre_Rol'] ?? 'N/A') . '</p>';

echo '<h2>Roles en tabla user_roles</h2>';
$userRoleModel->asegurarTabla();
$rolesDb = $userRoleModel->listarRolesPersona($idPersona);

if (empty($rolesDb)) {
    echo '<p style="color: red;"><strong>PROBLEMA:</strong> No hay roles en la tabla user_roles</p>';
} else {
    echo '<ul>';
    foreach ($rolesDb as $rol) {
        echo '<li>ID Rol: ' . htmlspecialchars($rol['Id_Rol']) . ' - Nombre: ' . htmlspecialchars($rol['Nombre_Rol']) . '</li>';
    }
    echo '</ul>';
}

echo '<h2>Inscripciones en Capacitación Destino</h2>';
$programas = (array)$inscripcionModel->getProgramasInscritosPersona($idPersona);

if (empty($programas)) {
    echo '<p style="color: orange;"><strong>Sin inscripciones</strong></p>';
} else {
    echo '<ul>';
    $tieneCapacitacionDestino = false;
    foreach ($programas as $programa) {
        $prog = trim((string)$programa);
        $esCapDest = strpos($prog, 'capacitacion_destino') !== false;
        $color = $esCapDest ? 'green' : 'black';
        echo '<li style="color: ' . $color . ';">' . htmlspecialchars($prog) . ($esCapDest ? ' ✓' : '') . '</li>';
        if ($esCapDest) {
            $tieneCapacitacionDestino = true;
        }
    }
    echo '</ul>';
    echo '<p><strong>¿Tiene Capacitación Destino?</strong> <span style="color: ' . ($tieneCapacitacionDestino ? 'green' : 'red') . ';">' . ($tieneCapacitacionDestino ? 'SÍ' : 'NO') . '</span></p>';
}

echo '<h2>Acciones</h2>';
if (!empty($_GET['sync'])) {
    $idRolLegacy = (int)($persona['Id_Rol'] ?? 0);
    if ($idRolLegacy > 0) {
        $result = $userRoleModel->sincronizarRolPrincipal($idPersona, $idRolLegacy);
        echo '<p style="color: ' . ($result ? 'green' : 'red') . ';">';
        echo ($result ? 'Sincronización exitosa' : 'Fallo en sincronización');
        echo ' del rol legacy ID: ' . htmlspecialchars($idRolLegacy) . '</p>';
    }
}

// Tabla de todos los roles disponibles
echo '<h2>Todos los Roles del Sistema</h2>';
$todoRoles = $db->query('SELECT * FROM rol ORDER BY Nombre_Rol ASC')->fetchAll(PDO::FETCH_ASSOC);
if (!empty($todoRoles)) {
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>ID</th><th>Nombre</th><th>Asignado a Este Usuario</th><th>Acción</th></tr>';
    foreach ($todoRoles as $rol) {
        $idRol = $rol['Id_Rol'];
        $tieneRol = !empty(array_filter($rolesDb, fn($r) => $r['Id_Rol'] == $idRol));
        echo '<tr>';
        echo '<td>' . htmlspecialchars($idRol) . '</td>';
        echo '<td>' . htmlspecialchars($rol['Nombre_Rol']) . '</td>';
        echo '<td style="color: ' . ($tieneRol ? 'green' : 'gray') . ';">' . ($tieneRol ? 'SÍ ✓' : 'NO') . '</td>';
        echo '<td><a href="?id=' . htmlspecialchars($idPersona) . '&assign=' . htmlspecialchars($idRol) . '">Asignar</a></td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Asignar rol si se solicita
if (!empty($_GET['assign'])) {
    $idRolAsignar = (int)($_GET['assign'] ?? 0);
    if ($idRolAsignar > 0) {
        $result = $userRoleModel->asignarRol($idPersona, $idRolAsignar);
        echo '<p style="color: ' . ($result ? 'green' : 'red') . ';">';
        echo ($result ? 'Rol asignado exitosamente' : 'Fallo al asignar rol');
        echo ' ID: ' . htmlspecialchars($idRolAsignar) . '</p>';
        echo '<meta http-equiv="refresh" content="2; url=?id=' . htmlspecialchars($idPersona) . '">';
    }
}

echo '<h2>Resumen para Selector de Contexto</h2>';
$tieneCapacitacionDestino = false;
foreach ($programas as $programa) {
    if (strpos(trim((string)$programa), 'capacitacion_destino') !== false) {
        $tieneCapacitacionDestino = true;
        break;
    }
}

$esLider = false;
foreach ($rolesDb as $rolDb) {
    $nombre = (string)($rolDb['Nombre_Rol'] ?? '');
    if (strpos(strtolower($nombre), 'lider') !== false) {
        $esLider = true;
        break;
    }
}

$mostrarSelector = $esLider && $tieneCapacitacionDestino;
echo '<p><strong>Es Líder?</strong> ' . ($esLider ? '<span style="color: green;">SÍ</span>' : '<span style="color: red;">NO</span>') . '</p>';
echo '<p><strong>Está en Capacitación Destino?</strong> ' . ($tieneCapacitacionDestino ? '<span style="color: green;">SÍ</span>' : '<span style="color: red;">NO</span>') . '</p>';
echo '<p><strong>¿Mostrar selector de contexto?</strong> ' . ($mostrarSelector ? '<span style="color: green; font-weight: bold;">SÍ - Debe ver opción discípulo/líder</span>' : '<span style="color: red;">NO</span>') . '</p>';
?>
