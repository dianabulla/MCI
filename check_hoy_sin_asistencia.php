<?php
define('ROOT', __DIR__);
define('APP', ROOT . '/app');
require_once 'app/Config/config.php';
require_once 'app/Config/Database.php';

$pdo = Database::getInstance()->getConnection();
$hoy = date('Y-m-d');

echo "=== INSCRIPCIONES DE HOY SIN ASISTENCIA ===\n\n";

$sql = "SELECT i.Id_Inscripcion, i.Id_Persona, i.Nombre, i.Cedula, i.Programa, i.Asistio_Clase, i.Fecha_Asistencia_Clase, i.Fecha_Registro 
FROM escuela_formacion_inscripcion i
WHERE DATE(i.Fecha_Registro) = '$hoy'
ORDER BY i.Fecha_Registro DESC";

$inscripciones = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo "Inscripciones de hoy:\n\n";
foreach ($inscripciones as $insc) {
    $asistioMarcado = $insc['Asistio_Clase'] ? 'SÍ' : 'NO';
    echo "ID {$insc['Id_Inscripcion']}: {$insc['Nombre']} ({$insc['Cedula']}) - Programa: {$insc['Programa']}\n";
    echo "  Fecha registro: {$insc['Fecha_Registro']}\n";
    echo "  Asistencia marcada: $asistioMarcado\n";
    echo "  Fecha asistencia: {$insc['Fecha_Asistencia_Clase']}\n\n";
}

// Clase disponible hoy
echo "=== CLASES DISPONIBLES HOY ===\n";
$sql = "SELECT Modulo, Programa, Numero_Clase, Fecha_Clase FROM escuela_formacion_clase_fecha WHERE DATE(Fecha_Clase) = '$hoy'";
$clases = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
foreach ($clases as $clase) {
    echo "Clase {$clase['Numero_Clase']}: {$clase['Modulo']} / {$clase['Programa']} / {$clase['Fecha_Clase']}\n";
}
?>
