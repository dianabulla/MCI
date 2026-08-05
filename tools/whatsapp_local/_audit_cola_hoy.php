<?php
declare(strict_types=1);
$root = dirname(__DIR__, 2);
chdir($root);
ob_start();
require_once $root . '/conexion.php';
ob_end_clean();

echo "Zona servidor (PHP): " . date_default_timezone_get() . "\n";
echo "Hora MySQL (session): ";
echo $pdo->query("SELECT @@session.time_zone AS tz, NOW() AS now_mysql")->fetch(PDO::FETCH_ASSOC)['now_mysql'] ?? '?';
echo "\n\n";

$queries = [
    'Registros CREADOS hoy (DATE creado_en) por estado' =>
        "SELECT estado, COUNT(*) AS n FROM whatsapp_local_queue WHERE DATE(creado_en) = CURDATE() GROUP BY estado ORDER BY estado",
    'Registros con procesado_en HOY por estado' =>
        "SELECT estado, COUNT(*) AS n FROM whatsapp_local_queue WHERE procesado_en IS NOT NULL AND DATE(procesado_en) = CURDATE() GROUP BY estado ORDER BY estado",
];

foreach ($queries as $title => $sql) {
    echo "=== {$title} ===\n";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        echo "(sin filas)\n";
    } else {
        foreach ($rows as $r) {
            echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
        }
    }
    echo "\n";
}

echo "=== Encolados hoy aún pendiente / procesando ===\n";
$sql = "SELECT id, telefono, tipo_evento, estado, intentos, creado_en, programado_en
        FROM whatsapp_local_queue
        WHERE DATE(creado_en) = CURDATE() AND estado IN ('pendiente','procesando')
        ORDER BY id ASC LIMIT 40";
foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}
echo "\n=== Enviados hoy (últimos 20 por procesado_en) ===\n";
$sql = "SELECT id, telefono, tipo_evento, creado_en, procesado_en
        FROM whatsapp_local_queue
        WHERE estado = 'enviado' AND DATE(procesado_en) = CURDATE()
        ORDER BY procesado_en DESC LIMIT 20";
foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== Fallidos hoy (procesado_en) ===\n";
$sql = "SELECT id, telefono, tipo_evento, intentos, creado_en, procesado_en, LEFT(COALESCE(ultimo_error,''), 200) AS err
        FROM whatsapp_local_queue
        WHERE estado = 'fallido' AND procesado_en IS NOT NULL AND DATE(procesado_en) = CURDATE()
        ORDER BY id DESC LIMIT 15";
foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== Últimos 15 registros de la cola (cualquier fecha) ===\n";
$sql = "SELECT id, telefono, tipo_evento, estado, creado_en, procesado_en,
        LEFT(COALESCE(ultimo_error,''), 80) AS err
        FROM whatsapp_local_queue ORDER BY id DESC LIMIT 15";
foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== Resumen por día (creado_en), últimos 7 días ===\n";
$sql = "SELECT DATE(creado_en) AS dia, estado, COUNT(*) AS n
        FROM whatsapp_local_queue
        WHERE creado_en >= CURDATE() - INTERVAL 7 DAY
        GROUP BY DATE(creado_en), estado
        ORDER BY dia DESC, estado";
$last7 = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
if (!$last7) {
    echo "(sin encolados en los últimos 7 días)\n";
} else {
    foreach ($last7 as $r) {
        echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

echo "\n=== Resumen enviados por día (procesado_en), últimos 7 días ===\n";
$sql = "SELECT DATE(procesado_en) AS dia, COUNT(*) AS enviados
        FROM whatsapp_local_queue
        WHERE estado = 'enviado' AND procesado_en IS NOT NULL
          AND procesado_en >= CURDATE() - INTERVAL 7 DAY
        GROUP BY DATE(procesado_en)
        ORDER BY dia DESC";
$sent7 = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
if (!$sent7) {
    echo "(sin enviados registrados en los últimos 7 días)\n";
} else {
    foreach ($sent7 as $r) {
        echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

$cntHoy = (int)$pdo->query(
    "SELECT COUNT(*) FROM whatsapp_local_queue WHERE estado = 'enviado' AND DATE(procesado_en) = CURDATE()"
)->fetchColumn();
echo "\n>>> Conclusión: enviados HOY (estado=enviado, fecha procesado_en = CURDATE): {$cntHoy}\n";
