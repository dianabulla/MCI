<?php
// Script de diagnóstico para asistencia de clases

define('ROOT', __DIR__);
define('APP', ROOT . '/app');
require_once APP . '/Config/config.php';
require_once ROOT . '/conexion.php';
require_once APP . '/Models/BaseModel.php';
require_once 'app/Models/EscuelaFormacionAsistenciaClase.php';

$modeloAsistencia = new EscuelaFormacionAsistenciaClase();

// Verificar si la tabla existe
echo "<h2>Diagnóstico de Asistencia de Clases</h2>";
echo "<pre>";

// Mostrar últimos registros guardados
echo "\n--- Últimos registros en BD ---\n";
$sql = "SELECT * FROM escuela_formacion_asistencia_clase ORDER BY Fecha_Actualizacion DESC LIMIT 20";
try {
    $rows = $modeloAsistencia->query($sql, []);
    echo "Total de registros encontrados: " . count($rows) . "\n\n";
    foreach ($rows as $row) {
        echo "ID_Persona: {$row['Id_Persona']}, Modulo: {$row['Modulo']}, Programa: {$row['Programa']}, Clase: {$row['Numero_Clase']}, Asistio: {$row['Asistio']}, Fecha: {$row['Fecha_Actualizacion']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// Probar guardar un registro de prueba
echo "\n--- Intentando guardar registro de prueba ---\n";
$testIdPersona = 9999;
$testClase = 5;
$testModulo = 'modulo_1';
$testPrograma = 'capacitacion_destino';

$exitoTest = $modeloAsistencia->upsertAsistencia($testIdPersona, $testModulo, $testPrograma, $testClase, true);
echo "Resultado de upsertAsistencia (ID_Persona=9999): " . ($exitoTest ? 'TRUE' : 'FALSE') . "\n";

// Verificar si se guardó
$sqlCheck = "SELECT * FROM escuela_formacion_asistencia_clase WHERE Id_Persona = 9999";
$checkRows = $modeloAsistencia->query($sqlCheck, []);
echo "Registros encontrados para ID_Persona=9999: " . count($checkRows) . "\n";
if (!empty($checkRows)) {
    foreach ($checkRows as $row) {
        echo "  Clase {$row['Numero_Clase']}: Asistio={$row['Asistio']}\n";
    }
}

echo "\n--- Fin del diagnóstico ---\n";
echo "</pre>";
?>
