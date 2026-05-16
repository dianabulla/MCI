<?php
declare(strict_types=1);
chdir(dirname(dirname(__DIR__)));
require_once __DIR__ . '/../../conexion.php';

echo "Capacitación Destino - whatsapp_local_queue (BD de conexion.php)\n\n";

foreach (['mensaje_capacitacion_destino', 'programacion_mensaje_capacitacion_destino'] as $tipo) {
    echo "=== {$tipo} ===\n";
    $st = $pdo->prepare('SELECT estado, COUNT(*) AS n FROM whatsapp_local_queue WHERE tipo_evento = ? GROUP BY estado ORDER BY estado');
    $st->execute([$tipo]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        echo "(sin registros de este tipo)\n";
    } else {
        foreach ($rows as $r) {
            echo "{$r['estado']}: {$r['n']}\n";
        }
    }
    echo "\n";
}

echo "=== Últimos 12 registros CD ===\n";
$sql = "SELECT id, tipo_evento, estado, telefono,
        DATE_FORMAT(programado_en,'%Y-%m-%d %H:%i') AS prog,
        DATE_FORMAT(procesado_en,'%Y-%m-%d %H:%i') AS proc,
        DATE_FORMAT(creado_en,'%Y-%m-%d %H:%i') AS creado
        FROM whatsapp_local_queue
        WHERE tipo_evento IN ('mensaje_capacitacion_destino','programacion_mensaje_capacitacion_destino')
        ORDER BY id DESC LIMIT 12";
foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}
