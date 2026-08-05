<?php
declare(strict_types=1);

/**
 * Cancela mensajes atrasados en whatsapp_local_queue (producción vía .env del worker).
 *
 * Uso:
 *   php tools/whatsapp_local/limpiar_cola_atrasada.php           # solo vista previa
 *   php tools/whatsapp_local/limpiar_cola_atrasada.php --execute # aplicar en BD del .env
 */
date_default_timezone_set('America/Bogota');

$execute = in_array('--execute', $argv ?? [], true);
$motivo = 'Cancelado: cola atrasada (limpieza ' . date('Y-m-d') . ')';

require_once __DIR__ . '/db_from_env.php';

try {
    $pdo = wa_pdo_from_env();
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR conexión: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$mysqlNow = (string)$pdo->query('SELECT NOW() AS n, CURDATE() AS d')->fetch()['n'];
echo "MySQL NOW(): {$mysqlNow}\n";
echo 'Modo: ' . ($execute ? 'EJECUTAR (cambios reales)' : 'VISTA PREVIA (--execute para aplicar)') . "\n\n";

$sqlWhere = "
    estado IN ('pendiente', 'procesando')
    AND (
        (programado_en IS NULL AND DATE(creado_en) < CURDATE())
        OR (programado_en IS NOT NULL AND DATE(programado_en) < CURDATE())
    )
";

echo "=== Resumen actual por estado ===\n";
foreach ($pdo->query('SELECT estado, COUNT(*) AS total FROM whatsapp_local_queue GROUP BY estado ORDER BY estado') as $row) {
    echo sprintf("  %s: %s\n", $row['estado'], $row['total']);
}

echo "\n=== Atrasados a cancelar (creado o programado antes de hoy) ===\n";
$countSql = "SELECT COUNT(*) AS total FROM whatsapp_local_queue WHERE {$sqlWhere}";
$totalCancelar = (int)$pdo->query($countSql)->fetch()['total'];
echo "Total filas: {$totalCancelar}\n\n";

if ($totalCancelar > 0) {
    $previewSql = "SELECT id, telefono, tipo_evento, estado, creado_en, programado_en,
                          LEFT(mensaje, 80) AS mensaje_corto
                   FROM whatsapp_local_queue
                   WHERE {$sqlWhere}
                   ORDER BY id ASC
                   LIMIT 25";
    foreach ($pdo->query($previewSql) as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
    }
    if ($totalCancelar > 25) {
        echo "... y " . ($totalCancelar - 25) . " más.\n";
    }
}

echo "\n=== Pendientes que QUEDAN (solo hoy) ===\n";
$sqlHoy = "
    estado IN ('pendiente', 'procesando')
    AND (
        (programado_en IS NULL AND DATE(creado_en) = CURDATE())
        OR (programado_en IS NOT NULL AND DATE(programado_en) = CURDATE())
    )
";
$totalHoy = (int)$pdo->query("SELECT COUNT(*) AS total FROM whatsapp_local_queue WHERE {$sqlHoy}")->fetch()['total'];
echo "Total filas válidas para hoy: {$totalHoy}\n";

if ($totalHoy > 0) {
    $previewHoy = "SELECT id, telefono, tipo_evento, estado, creado_en, programado_en
                    FROM whatsapp_local_queue
                    WHERE {$sqlHoy}
                    ORDER BY id ASC
                    LIMIT 20";
    foreach ($pdo->query($previewHoy) as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
    }
}

if (!$execute) {
    echo "\nSin cambios. Para cancelar los atrasados ejecuta:\n";
    echo "  C:\\xampp\\php\\php.exe tools\\whatsapp_local\\limpiar_cola_atrasada.php --execute\n";
    exit(0);
}

if ($totalCancelar === 0) {
    echo "\nNo hay filas atrasadas que cancelar.\n";
    exit(0);
}

$updateSql = "UPDATE whatsapp_local_queue
              SET estado = 'fallido',
                  ultimo_error = ?,
                  procesado_en = NOW()
              WHERE {$sqlWhere}";
$stmt = $pdo->prepare($updateSql);
$stmt->execute([$motivo]);
$afectadas = $stmt->rowCount();

echo "\n=== Listo ===\n";
echo "Filas marcadas como fallido (canceladas): {$afectadas}\n";
echo "Reinicia el worker para que solo procese pendientes de hoy.\n";
