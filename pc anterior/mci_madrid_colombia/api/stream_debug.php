<?php
/**
 * Archivo de debug para diagnosticar problemas con ESP32-CAM
 */

$logFile = __DIR__ . '/../public/assets/stream/debug.log';

// Crear log con toda la información
$log = "\n\n=== " . date('Y-m-d H:i:s') . " ===\n";
$log .= "Método: " . $_SERVER['REQUEST_METHOD'] . "\n";
$log .= "IP Cliente: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
$log .= "User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'none') . "\n";
$log .= "Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'none') . "\n";
$log .= "Content-Length: " . ($_SERVER['CONTENT_LENGTH'] ?? '0') . "\n";

// Verificar si hay datos POST
$postData = file_get_contents('php://input');
$log .= "Tamaño datos recibidos: " . strlen($postData) . " bytes\n";

if (strlen($postData) > 0) {
    $log .= "Primeros 20 bytes: " . bin2hex(substr($postData, 0, 20)) . "\n";
    
    // Verificar si es JPEG
    if (substr($postData, 0, 2) === "\xFF\xD8") {
        $log .= "✓ ES JPEG VÁLIDO\n";
    } else {
        $log .= "✗ NO ES JPEG (magic bytes incorrectos)\n";
    }
}

// Headers recibidos
$log .= "Headers:\n";
foreach (getallheaders() as $name => $value) {
    $log .= "  $name: $value\n";
}

// Guardar log
file_put_contents($logFile, $log, FILE_APPEND);

// Responder
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Debug: petición registrada',
    'received_bytes' => strlen($postData),
    'is_jpeg' => (strlen($postData) > 2 && substr($postData, 0, 2) === "\xFF\xD8")
]);
