<?php
/**
 * Redirección al diagnóstico dentro de la aplicación (misma sesión y bootstrap).
 *
 * URL antigua: /tools/diagnostico_numero_documento_web.php
 * URL recomendada:
 *   /public/index.php?url=herramientas/diagnostico-documento
 */
declare(strict_types=1);

session_start();

$destino = '../public/index.php?url=herramientas/diagnostico-documento';

if (is_file(dirname(__DIR__) . '/app/Config/config.php')) {
    require_once dirname(__DIR__) . '/app/Config/config.php';
    if (defined('PUBLIC_URL')) {
        $destino = rtrim(PUBLIC_URL, '/') . '/index.php?url=herramientas/diagnostico-documento';
    }
}

$query = $_SERVER['QUERY_STRING'] ?? '';
if ($query !== '') {
    $destino .= (strpos($destino, '?') !== false ? '&' : '?') . $query;
}

header('Location: ' . $destino, true, 302);
exit;
