<?php
define('ROOT', __DIR__);
define('APP', ROOT . '/app');
require_once 'app/Config/config.php';
require_once 'app/Config/Database.php';
require_once 'app/Models/BaseModel.php';
require_once 'app/Models/EscuelaFormacionAsistenciaClase.php';

$pdo = Database::getInstance()->getConnection();
$ayer = date('Y-m-d', strtotime('-1 day'));

echo "=== TEST: SIMULAR MARCADO DE ASISTENCIA PARA AYER ($ayer) ===\n\n";

// 1. Clases disponibles
echo "1. CLASES DISPONIBLES PARA AYER:\n";
$sql = "SELECT Modulo, Programa, Numero_Clase, Fecha_Clase FROM escuela_formacion_clase_fecha WHERE DATE(Fecha_Clase) = '$ayer'";
$clases = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
foreach ($clases as $c) {
    echo "   - {$c['Modulo']} / {$c['Programa']} / Clase {$c['Numero_Clase']} / Fecha: {$c['Fecha_Clase']}\n";
}

// 2. Test getNumeroClasePorFecha
echo "\n2. TEST getNumeroClasePorFecha:\n";
$asistenciaModel = new EscuelaFormacionAsistenciaClase();
$numeroClase = $asistenciaModel->getNumeroClasePorFecha('consolidar', 'universidad_vida', $ayer);
echo "   getNumeroClasePorFecha('consolidar', 'universidad_vida', '$ayer') = $numeroClase\n";

// 3. Ver error_log
echo "\n3. ÚLTIMOS LOGS DEL ERROR_LOG:\n";
$logFile = ini_get('error_log');
if ($logFile && file_exists($logFile)) {
    $lines = array_slice(file($logFile), -15);
    foreach ($lines as $line) {
        echo "   " . trim($line) . "\n";
    }
} else {
    echo "   No error log found at: $logFile\n";
}
?>
