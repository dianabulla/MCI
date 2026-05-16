<?php
/**
 * Una sola consulta rápida a whatsapp_local_queue usando la misma conexión que la web (conexion.php).
 * Uso: php tools/whatsapp_local/check_queue_once.php
 */
declare(strict_types=1);

chdir(dirname(dirname(__DIR__)));
require_once __DIR__ . '/../../conexion.php';

/** @var PDO $pdo */

try {
    $t = $pdo->query("SHOW TABLES LIKE 'whatsapp_local_queue'")->fetch(PDO::FETCH_NUM);
    if (!$t) {
        echo "TABLA whatsapp_local_queue: no existe en esta base.\n";
        exit(0);
    }

    echo "=== Resumen por estado ===\n";
    $r = $pdo->query("SELECT estado, COUNT(*) AS total FROM whatsapp_local_queue GROUP BY estado ORDER BY estado");
    foreach ($r->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo sprintf("%s: %s\n", $row['estado'], $row['total']);
    }

    echo "\n=== Pendientes / procesando (últimos 15) ===\n";
    $sql = "SELECT id, telefono, tipo_evento, estado, intentos, programado_en, creado_en,
            LEFT(COALESCE(ultimo_error, ''), 80) AS err
            FROM whatsapp_local_queue
            WHERE estado IN ('pendiente', 'procesando')
            ORDER BY id DESC LIMIT 15";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        echo "(ninguno)\n";
    } else {
        echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    }

    echo "\n=== Mensajes CD programados (tipo_evento programacion_mensaje_capacitacion_destino) ===\n";
    $sql2 = "SELECT id, estado, telefono, programado_en, creado_en
             FROM whatsapp_local_queue
             WHERE tipo_evento = 'programacion_mensaje_capacitacion_destino'
             ORDER BY id DESC LIMIT 15";
    try {
        $rows2 = $pdo->query($sql2)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $rows2 = [];
    }
    if (!$rows2) {
        echo "(ninguno en esta BD)\n";
    } else {
        echo json_encode($rows2, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . "\n");
    exit(1);
}
