<?php
define('ROOT', __DIR__);
define('APP', ROOT . '/app');
require_once 'app/Config/config.php';
require_once 'app/Config/Database.php';

$pdo = Database::getInstance()->getConnection();
$hoy = date('Y-m-d');
$ayer = date('Y-m-d', strtotime('-1 day'));

echo "=== ANÁLISIS DETALLADO: ÚLTIMOS 3 DÍAS ===\n\n";

for ($dias = 2; $dias >= 0; $dias--) {
    $fecha = date('Y-m-d', strtotime("-$dias days"));
    echo "DÍA: $fecha\n";
    
    // Inscripciones
    $stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM escuela_formacion_inscripcion WHERE DATE(Fecha_Registro) = ?');
    $stmt->execute([$fecha]);
    $insc = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    // Asistencias en esa fecha
    $stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM escuela_formacion_asistencia_clase WHERE DATE(Fecha_Actualizacion) = ?');
    $stmt->execute([$fecha]);
    $asist = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    // Clases
    $stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM escuela_formacion_clase_fecha WHERE DATE(Fecha_Clase) = ?');
    $stmt->execute([$fecha]);
    $clases = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    // Inscripciones con asistencia en esa fecha
    $stmt = $pdo->prepare('SELECT COUNT(*) as cnt FROM escuela_formacion_inscripcion WHERE DATE(Fecha_Asistencia_Clase) = ?');
    $stmt->execute([$fecha]);
    $asistEnInsc = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    echo "  Inscripciones creadas: $insc\n";
    echo "  Asistencias en tabla asistencia_clase: $asist\n";
    echo "  Clases disponibles: $clases\n";
    echo "  Inscripciones marcadas con asistencia: $asistEnInsc\n";
    
    if ($insc > 0 && $asistEnInsc < $insc) {
        echo "  ⚠️  PROBLEMA: $insc inscripción(es) pero solo $asistEnInsc marcada(s) con asistencia\n";
    } elseif ($insc > 0 && $clases > 0 && $asist == 0) {
        echo "  ⚠️  PROBLEMA: $insc inscripción(es) y $clases clase(s), pero 0 asistencias marcadas\n";
    }
    
    echo "\n";
}
?>
