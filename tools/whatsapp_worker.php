<?php
/**
 * Worker CLI para procesar cola de WhatsApp
 * Uso:
 *   php tools/whatsapp_worker.php
 *   php tools/whatsapp_worker.php --limit=100
 *   php tools/whatsapp_worker.php --limit=20 --dry-run=1
 */

date_default_timezone_set('America/Bogota');

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');

require_once ROOT . '/conexion.php';
require_once APP . '/Models/BaseModel.php';
require_once APP . '/Models/WhatsappCampana.php';

$limit = 50;
$dryRun = false;

foreach ($argv as $arg) {
    if (strpos($arg, '--limit=') === 0) {
        $limit = (int)substr($arg, 8);
    }
    if ($arg === '--dry-run=1' || $arg === '--dry-run') {
        $dryRun = true;
    }
}

$limit = max(1, min($limit, 500));

$model = new WhatsappCampana();

try {
    $result = $model->procesarLotePendiente($limit, $dryRun);
    echo json_encode([
        'ok' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'dry_run' => $dryRun,
        'limit' => $limit,
        'result' => $result
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    exit(1);
}
