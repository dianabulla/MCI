<?php
/**
 * Redirección al diagnóstico de permisos (misma sesión que la app).
 *
 * Producción:
 *   https://TU-DOMINIO/public/index.php?url=herramientas/diagnostico-permisos-persona
 *   o /tools/diagnostico_permisos_persona_web.php (redirige)
 */
declare(strict_types=1);

session_start();

$destino = '../public/index.php?url=herramientas/diagnostico-permisos-persona';

if (is_file(dirname(__DIR__) . '/app/Config/config.php')) {
    require_once dirname(__DIR__) . '/app/Config/config.php';
    if (function_exists('public_app_url')) {
        $destino = public_app_url('herramientas/diagnostico-permisos-persona');
    } elseif (defined('PUBLIC_URL')) {
        $destino = rtrim(PUBLIC_URL, '/') . '/index.php?url=herramientas/diagnostico-permisos-persona';
    }
}

$query = $_SERVER['QUERY_STRING'] ?? '';
if ($query !== '') {
    $destino .= (strpos($destino, '?') !== false ? '&' : '?') . $query;
}

header('Location: ' . $destino, true, 302);
exit;
