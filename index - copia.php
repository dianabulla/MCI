<?php
/**
 * Fallback de entrada en raíz para entornos donde no funciona bien .htaccess.
 */

$publicIndex = __DIR__ . '/public/index.php';

if (!is_file($publicIndex)) {
    http_response_code(500);
    echo 'No se encontró public/index.php. Verifica la estructura del despliegue.';
    exit;
}

require $publicIndex;
