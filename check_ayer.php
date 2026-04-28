<?php
define('ROOT', __DIR__);
define('APP', ROOT . '/app');
require_once 'app/Config/config.php';
require_once 'app/Config/Database.php';

$pdo = Database::getInstance()->getConnection();
$ayer = date('Y-m-d', strtotime('-1 day'));

echo "=== DIAGNÓSTICO ===\n";
echo "Ayer: $ayer\n\n";

// Inscripciones creadas ayer
$stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM escuela_formacion_inscripcion WHERE DATE(Fecha_Registro) = ?');
$stmt->execute([$ayer]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "1. Inscripciones creadas ayer: " . $result['cnt'] . "\n\n";

// Asistencias marcadas ayer
$stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM escuela_formacion_asistencia_clase WHERE DATE(Fecha_Actualizacion) = ?');
$stmt->execute([$ayer]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "2. Asistencias marcadas ayer: " . $result['cnt'] . "\n\n";

// Clases para ayer
$stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM escuela_formacion_clase_fecha WHERE DATE(Fecha_Clase) = ?');
$stmt->execute([$ayer]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "3. Clases programadas para ayer: " . $result['cnt'] . "\n\n";

// CONCLUSIÓN
echo "=== CONCLUSIÓN ===\n";
echo "Si inscripciones > 0 y asistencias < inscripciones:\n";
echo "=> Alguien se inscribió ayer pero su asistencia NO se marcó.\n";
?>
