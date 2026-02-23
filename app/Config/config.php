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

// URLs
$isHttps = (
	(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
	(isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) ||
	(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
);

$scheme = $isHttps ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/public/index.php'));
$publicPath = rtrim(dirname($scriptName), '/');

if ($publicPath === '') {
	$publicPath = '/public';
}

$basePath = preg_replace('#/public$#', '', $publicPath);
$baseUrl = $scheme . '://' . $host . $basePath;
$publicUrl = $scheme . '://' . $host . $publicPath;

define('BASE_URL', $baseUrl);
define('PUBLIC_URL', $publicUrl);
define('ASSETS_URL', rtrim(PUBLIC_URL, '/') . '/assets');
