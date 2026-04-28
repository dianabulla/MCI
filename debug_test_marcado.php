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

// Buscar inscripciones creadas ayer
echo "1. BUSCANDO INSCRIPCIONES CREADAS AYER:\n";
$stmt = $pdo->prepare('
    SELECT Id_Inscripcion, Id_Persona, Programa, Fecha_Creacion 
    FROM escuela_formacion_inscripcion 
    WHERE DATE(Fecha_Creacion) = ? 
    ORDER BY Fecha_Creacion DESC
');
$stmt->execute([$ayer]);
$inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($inscripciones) === 0) {
    echo "   No hay inscripciones creadas ayer.\n";
} else {
    echo "   Encontradas " . count($inscripciones) . " inscripciones:\n";
    foreach ($inscripciones as $insc) {
        echo "   - ID: {$insc['Id_Inscripcion']}, Persona: {$insc['Id_Persona']}, Programa: {$insc['Programa']}, Fecha: {$insc['Fecha_Creacion']}\n";
    }
}

// Verificar clases disponibles para ayer
echo "\n2. CLASES DISPONIBLES PARA AYER:\n";
$stmt = $pdo->prepare('
    SELECT Modulo, Programa, Numero_Clase, Fecha_Clase, Grupo 
    FROM escuela_formacion_clase_fecha 
    WHERE DATE(Fecha_Clase) = ? 
    ORDER BY Programa, Numero_Clase
');
$stmt->execute([$ayer]);
$clases = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($clases) === 0) {
    echo "   No hay clases para ayer.\n";
} else {
    echo "   Encontradas " . count($clases) . " clases:\n";
    foreach ($clases as $clase) {
        echo "   - Modulo: {$clase['Modulo']}, Programa: {$clase['Programa']}, Clase: {$clase['Numero_Clase']}, Fecha: {$clase['Fecha_Clase']}, Grupo: {$clase['Grupo']}\n";
    }
}

// Mapeo de programas a módulos
echo "\n3. MAPEO PROGRAMA -> MODULO:\n";
$programaMappings = [
    'universidad_vida' => 'consolidar',
    'capacitacion_destino_nivel_1' => 'discipular',
    'capacitacion_liderazgo_nivel_1' => 'entrenar',
];

foreach ($programaMappings as $prog => $mod) {
    echo "   $prog -> $mod\n";
}

// Test: Buscar clase específicamente
echo "\n4. TEST DE BÚSQUEDA DE CLASES:\n";
$asistenciaModel = new EscuelaFormacionAsistenciaClase();

foreach (['universidad_vida', 'capacitacion_destino_nivel_1'] as $programa) {
    $modulo = $programaMappings[$programa] ?? 'desconocido';
    $numeroClase = $asistenciaModel->getNumeroClasePorFecha($modulo, $programa, $ayer);
    echo "   getNumeroClasePorFecha($modulo, $programa, $ayer) = " . ($numeroClase > 0 ? $numeroClase : "NO ENCONTRADO") . "\n";
}

// Verificar formato de fechas en la BD
echo "\n5. VERIFICAR FORMATO DE FECHAS EN BD:\n";
$stmt = $pdo->prepare('
    SELECT Fecha_Clase, DATE(Fecha_Clase) as fecha_solo, TIME(Fecha_Clase) as hora 
    FROM escuela_formacion_clase_fecha 
    WHERE DATE(Fecha_Clase) = ? 
    LIMIT 3
');
$stmt->execute([$ayer]);
$fechas = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($fechas as $f) {
    echo "   Fecha_Clase: {$f['Fecha_Clase']}, Fecha: {$f['fecha_solo']}, Hora: {$f['hora']}\n";
}

echo "\n6. REVISAR ERROR LOG:\n";
$logFile = ini_get('error_log');
echo "   Error log: $logFile\n";
if (file_exists($logFile)) {
    echo "   Últimas 20 líneas del error_log:\n";
    $lines = array_slice(file($logFile), -20);
    foreach ($lines as $line) {
        echo "   " . trim($line) . "\n";
    }
} else {
    echo "   (No se encontró archivo de error_log)\n";
}
?>
