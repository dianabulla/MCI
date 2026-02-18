<?php
/**
 * Archivo de configuración principal
 * Contiene constantes y configuraciones globales de la aplicación
 */

// Configuración de zona horaria
date_default_timezone_set('America/Bogota');

// Configuración de base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'iglesia_db');
define('DB_PORT', 3306);

// Configuración de la aplicación
define('APP_NAME', 'Iglesia App - MCI Madrid Colombia');
define('APP_URL', 'http://localhost/mci_madrid_colombia/iglesia_app/public');
define('APP_DEBUG', true);

// Configuración de directorios
define('BASE_PATH', dirname(dirname(dirname(__FILE__))));
define('APP_PATH', BASE_PATH . '/app');
define('VIEWS_PATH', BASE_PATH . '/views');
define('ASSETS_URL', APP_URL . '/assets');

// Mensajes de error
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');

// Crear carpeta de logs si no existe
if (!is_dir(BASE_PATH . '/logs')) {
    mkdir(BASE_PATH . '/logs', 0777, true);
}

// Función de depuración
if (!function_exists('dd')) {
    function dd($data) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        die;
    }
}

// Función para registrar errores
if (!function_exists('log_error')) {
    function log_error($message) {
        $message = date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL;
        error_log($message);
    }
}

// Función para redireccionamiento
if (!function_exists('redirect')) {
    function redirect($path) {
        header('Location: ' . APP_URL . '/' . $path);
        exit;
    }
}
