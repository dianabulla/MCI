<?php
/**
 * Script para listar todos los lideres de celula en el sistema
 */

define('APP', dirname(__FILE__) . '/app');
define('BASE_URL', 'http://localhost/mcimadrid');

require_once APP . '/Config/conexion.php';

$db = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Buscar rol de "lider"
$sqlRol = "SELECT Id_Rol FROM rol WHERE Nombre_Rol LIKE '%lder%' OR Nombre_Rol LIKE '%lider%' LIMIT 1";
$stmtRol = $db->query($sqlRol);
$rolData = $stmtRol->fetch(PDO::FETCH_ASSOC);

if (empty($rolData)) {
    echo '<p style="color: red;">No se encontró rol "Lider" en la tabla rol</p>';
    exit;
}

$idRolLider = $rolData['Id_Rol'];

echo '<h2>Usuarios con Rol Líder</h2>';
echo '<p>Rol ID: ' . htmlspecialchars($idRolLider) . '</p>';

$sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, r.Nombre_Rol, p.Id_Rol
        FROM persona p
        JOIN rol r ON r.Id_Rol = p.Id_Rol
        WHERE p.Id_Rol = ?
        ORDER BY p.Nombre ASC";

$stmt = $db->prepare($sql);
$stmt->execute([$idRolLider]);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($usuarios)) {
    echo '<p>No se encontraron usuarios con rol Líder.</p>';
    exit;
}

echo '<table border="1" cellpadding="8">';
echo '<tr>';
echo '<th>ID</th>';
echo '<th>Nombre</th>';
echo '<th>Rol Actual</th>';
echo '<th>Debug</th>';
echo '</tr>';

foreach ($usuarios as $usuario) {
    $idPersona = $usuario['Id_Persona'];
    $nombre = $usuario['Nombre'] . ' ' . $usuario['Apellido'];
    echo '<tr>';
    echo '<td>' . htmlspecialchars($idPersona) . '</td>';
    echo '<td>' . htmlspecialchars($nombre) . '</td>';
    echo '<td>' . htmlspecialchars($usuario['Nombre_Rol']) . '</td>';
    echo '<td><a href="debug_roles_lider.php?id=' . htmlspecialchars($idPersona) . '">Ver detalles</a></td>';
    echo '</tr>';
}

echo '</table>';
?>
