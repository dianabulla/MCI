<?php
/**
 * Archivo de configuración de la aplicación
 */

// Zona horaria
date_default_timezone_set('America/Bogota');

// Configuración de la base de datos
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'mcimadrid');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// URLs (usar rutas relativas para evitar problemas en producción con proxy/SSL)
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/public/index.php'));
$publicPath = rtrim(dirname($scriptName), '/');

if ($publicPath === '' || $publicPath === '.') {
	$publicPath = '/';
} elseif ($publicPath[0] !== '/') {
	$publicPath = '/' . $publicPath;
}

$basePath = preg_replace('#/public$#', '', $publicPath);
$baseUrl = $basePath !== '' ? $basePath : '/';
$publicUrl = rtrim($publicPath, '/') . '/';

define('BASE_URL', $baseUrl);
define('PUBLIC_URL', $publicUrl);
define('ASSETS_URL', rtrim(PUBLIC_URL, '/') . '/assets');

$assetVersionHelper = __DIR__ . '/../Helpers/AssetVersion.php';
if (is_file($assetVersionHelper)) {
    require_once $assetVersionHelper;
}
if (!function_exists('asset_url')) {
    function app_asset_version() {
        return defined('APP_ASSET_VERSION') ? (string)APP_ASSET_VERSION : date('Ymd');
    }
    function asset_url($relativePath) {
        $relativePath = ltrim(str_replace('\\', '/', (string)$relativePath), '/');
        $base = defined('ASSETS_URL') ? rtrim(ASSETS_URL, '/') : '/assets';
        return $base . '/' . $relativePath . '?v=' . rawurlencode(app_asset_version());
    }
}

/**
 * URL interna de la app (?url=...) sin generar //public/ (host inválido en el navegador).
 */
if (!function_exists('public_app_url')) {
    function public_app_url(string $route = 'home', array $queryParams = []): string {
        $route = ltrim($route, '/');

        $fragment = '';
        if (($hashPos = strpos($route, '#')) !== false) {
            $fragment = substr($route, $hashPos);
            $route = substr($route, 0, $hashPos);
        }

        $extraQuery = '';
        if (($qPos = strpos($route, '?')) !== false) {
            $extraQuery = substr($route, $qPos + 1);
            $route = substr($route, 0, $qPos);
        }
        if (($ampPos = strpos($route, '&')) !== false) {
            $extraFromAmp = substr($route, $ampPos + 1);
            $route = substr($route, 0, $ampPos);
            $extraQuery = $extraQuery === '' ? $extraFromAmp : ($extraQuery . '&' . $extraFromAmp);
        }

        $route = trim($route, '/');
        if ($route === '') {
            $route = 'home';
        }

        $parsedExtra = [];
        if ($extraQuery !== '') {
            parse_str($extraQuery, $parsedExtra);
            if (!is_array($parsedExtra)) {
                $parsedExtra = [];
            }
        }
        $queryParams = array_merge($parsedExtra, $queryParams);

        $base = rtrim(PUBLIC_URL, '/');
        $url = $base . '/index.php?url=' . $route;
        if ($queryParams !== []) {
            $url .= '&' . http_build_query($queryParams);
        }

        return $url . $fragment;
    }
}

/**
 * Convierte una ruta relativa de la app en URL absoluta (necesaria para códigos QR).
 */
if (!function_exists('absolute_public_app_url')) {
    function absolute_public_app_url(string $pathOrUrl): string {
        if (preg_match('#^https?://#i', $pathOrUrl)) {
            return $pathOrUrl;
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if ($pathOrUrl === '' || $pathOrUrl[0] !== '/') {
            $pathOrUrl = '/' . ltrim($pathOrUrl, '/');
        }
        return $scheme . '://' . $host . $pathOrUrl;
    }
}
