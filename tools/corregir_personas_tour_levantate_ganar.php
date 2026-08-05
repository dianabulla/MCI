<?php
/**
 * Corrige personas creadas por el Tour Levántate que quedaron como almas nuevas en Ganar.
 * No modifica las inscripciones del formulario (talleres_formulario_respuesta).
 *
 * Uso CLI:
 *   c:\xampp\php\php.exe tools/corregir_personas_tour_levantate_ganar.php
 *   c:\xampp\php\php.exe tools/corregir_personas_tour_levantate_ganar.php --apply
 *
 * Uso navegador (logueado con permiso editar talleres):
 *   ?url=talleres/corregir-personas-tour
 */

define('APP', dirname(__DIR__) . '/app');
define('ROOT', dirname(__DIR__));

require_once ROOT . '/app/Config/config.php';
require_once ROOT . '/conexion.php';
require_once APP . '/Helpers/TallerTourLevantateCorreccion.php';

$apply = in_array('--apply', $argv ?? [], true);
$servicio = new TallerTourLevantateCorreccion();
$rows = $servicio->obtenerPendientes();

if ($rows === []) {
    echo "No hay personas del Tour pendientes de corregir.\n";
    exit(0);
}

echo ($apply ? "Aplicando" : "Simulación") . " corrección para " . count($rows) . " persona(s):\n\n";
foreach ($rows as $row) {
    $id = (int)($row['Id_Persona'] ?? 0);
    $nombre = trim((string)($row['Nombre'] ?? '') . ' ' . (string)($row['Apellido'] ?? ''));
    $doc = (string)($row['Numero_Documento'] ?? '');
    echo "- Id {$id} | {$nombre} | doc {$doc}\n";
}

if (!$apply) {
    echo "\nEjecute con --apply para guardar los cambios.\n";
    echo "O abra en el navegador: ?url=talleres/corregir-personas-tour\n";
    exit(0);
}

$resultado = $servicio->aplicar();
echo "\n" . ($resultado['mensaje'] ?? 'Listo.') . "\n";
