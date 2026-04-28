<?php
require_once 'app/Config/config.php';
require_once 'app/Config/Database.php';
$pdo = Database::getInstance()->getConnection();

// Datos de ayer
$ayer = date('Y-m-d', strtotime('-1 day'));
echo "=== BUSCANDO CLASES PARA AYER: $ayer ===\n\n";

// Query 1: Clases en clase_fecha para ayer
$stmt = $pdo->prepare('SELECT Modulo, Programa, Numero_Clase, Fecha_Clase, Grupo FROM escuela_formacion_clase_fecha WHERE DATE(Fecha_Clase) = ?');
$stmt->execute([$ayer]);
$clases = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Clases registradas para $ayer: " . count($clases) . " encontradas\n";
foreach ($clases as $clase) {
    echo "  - Modulo: {$clase['Modulo']}, Programa: {$clase['Programa']}, Numero_Clase: {$clase['Numero_Clase']}, Fecha: {$clase['Fecha_Clase']}, Grupo: {$clase['Grupo']}\n";
}

// Query 2: Asistencias marcadas ayer
echo "\n=== ASISTENCIAS MARCADAS AYER ===\n";
$stmt = $pdo->prepare('SELECT Id_Persona, Modulo, Programa, Numero_Clase, Asistio FROM escuela_formacion_asistencia_clase WHERE DATE(Fecha_Actualizacion) = ?');
$stmt->execute([$ayer]);
$asistencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Asistencias marcadas: " . count($asistencias) . " encontradas\n";
foreach ($asistencias as $asist) {
    echo "  - Persona: {$asist['Id_Persona']}, Modulo: {$asist['Modulo']}, Programa: {$asist['Programa']}, Numero_Clase: {$asist['Numero_Clase']}, Asistio: {$asist['Asistio']}\n";
}

// Query 3: Últimas 10 asistencias registradas
echo "\n=== ÚLTIMAS 10 ASISTENCIAS REGISTRADAS ===\n";
$stmt = $pdo->prepare('SELECT Id_Persona, Modulo, Programa, Numero_Clase, Asistio, Fecha_Actualizacion FROM escuela_formacion_asistencia_clase ORDER BY Fecha_Actualizacion DESC LIMIT 10');
$stmt->execute();
$ultimas = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($ultimas as $u) {
    echo "  - Persona: {$u['Id_Persona']}, Modulo: {$u['Modulo']}, Programa: {$u['Programa']}, Numero_Clase: {$u['Numero_Clase']}, Asistio: {$u['Asistio']}, Actualizado: {$u['Fecha_Actualizacion']}\n";
}

// Query 4: Verificar inscripciones recientes con asistencia
echo "\n=== ÚLTIMAS 5 INSCRIPCIONES CON ASISTIO_CLASE ACTUALIZADO ===\n";
$stmt = $pdo->prepare('SELECT Id_Inscripcion, Programa, Asistio_Clase, Fecha_Asistencia_Clase FROM escuela_formacion_inscripcion WHERE Asistio_Clase = 1 ORDER BY Fecha_Asistencia_Clase DESC LIMIT 5');
$stmt->execute();
$inscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($inscripciones as $insc) {
    echo "  - Inscripcion: {$insc['Id_Inscripcion']}, Programa: {$insc['Programa']}, Asistio_Clase: {$insc['Asistio_Clase']}, Fecha: {$insc['Fecha_Asistencia_Clase']}\n";
}
?>
