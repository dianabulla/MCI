<?php
/**
 * Vuelve a poner en cola los cumpleaños de HOY que figuran como enviado
 * pero no llegaron al celular (tras corregir worker.js).
 *
 * Uso:
 *   php tools/whatsapp_local/reencolar_cumpleanos_hoy.php
 *   php tools/whatsapp_local/reencolar_cumpleanos_hoy.php --execute
 *   php tools/whatsapp_local/reencolar_cumpleanos_hoy.php --fallidos --execute
 */
declare(strict_types=1);

require_once __DIR__ . '/db_from_env.php';

$execute = in_array('--execute', $argv ?? [], true);
$soloFallidos = in_array('--fallidos', $argv ?? [], true);
$pdo = wa_pdo_from_env();

$estados = $soloFallidos ? "estado IN ('fallido', 'pendiente')" : "estado = 'enviado'";
$sql = "SELECT id, telefono, estado, procesado_en, ultimo_error
        FROM whatsapp_local_queue
        WHERE tipo_evento = 'felicitacion_cumpleanos'
          AND DATE(creado_en) = CURDATE()
          AND {$estados}
        ORDER BY id";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$etiqueta = $soloFallidos ? 'fallidos/pendientes de hoy' : 'marcados enviado hoy';
echo "Cumpleaños de hoy ({$etiqueta}): " . count($rows) . PHP_EOL;
foreach ($rows as $r) {
    echo "  #{$r['id']} {$r['telefono']} procesado_en={$r['procesado_en']}" . PHP_EOL;
}

if (!$rows) {
    exit(0);
}

if (!$execute) {
    echo PHP_EOL . 'Modo vista. Para reencolar ejecuta:' . PHP_EOL;
    echo '  C:\\xampp\\php\\php.exe tools\\whatsapp_local\\reencolar_cumpleanos_hoy.php --fallidos --execute' . PHP_EOL;
    echo '  C:\\xampp\\php\\php.exe tools\\whatsapp_local\\reencolar_cumpleanos_hoy.php --execute' . PHP_EOL;
    exit(0);
}

$ids = array_column($rows, 'id');
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$upd = $pdo->prepare(
    "UPDATE whatsapp_local_queue
     SET estado = 'pendiente', intentos = 0, ultimo_error = NULL, procesado_en = NULL
     WHERE id IN ({$placeholders})"
);
$upd->execute($ids);

echo PHP_EOL . 'Reencolados ' . $upd->rowCount() . ' mensaje(s). Reinicia el worker y revisa [OK] con chatId real en consola.' . PHP_EOL;
