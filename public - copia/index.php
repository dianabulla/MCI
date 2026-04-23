<?php
/**
 * Front Controller - Punto de entrada de la aplicación
 */

// Configurar zona horaria Colombia
date_default_timezone_set('America/Bogota');

// Iniciar sesión
session_start();

// Configurar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Definir constantes
define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
define('VIEWS', ROOT . '/views');

// Cargar conexión a base de datos
require_once ROOT . '/conexion.php';

// Cargar configuración con fallback de mayúsculas/minúsculas (Linux es case-sensitive)
$configPathCandidates = [
    APP . '/Config/config.php',
    APP . '/config/config.php',
];

$databasePathCandidates = [
    APP . '/Config/Database.php',
    APP . '/config/Database.php',
    APP . '/config/database.php',
];

$routesPathCandidates = [
    APP . '/Config/routes.php',
    APP . '/config/routes.php',
];

$configPath = null;
foreach ($configPathCandidates as $candidate) {
    if (is_file($candidate)) {
        $configPath = $candidate;
        break;
    }
}
if ($configPath === null) {
    die('No se encontró el archivo de configuración principal (config.php).');
}
require_once $configPath;

$databasePath = null;
foreach ($databasePathCandidates as $candidate) {
    if (is_file($candidate)) {
        $databasePath = $candidate;
        break;
    }
}
if ($databasePath === null) {
    die('No se encontró el archivo de conexión de aplicación (Database.php).');
}
require_once $databasePath;

// Fallback de URLs para entornos donde no estén definidas en config.php
if (!defined('PUBLIC_URL')) {
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/public/index.php'));
    $publicPath = rtrim(dirname($scriptName), '/');
    $publicBase = $publicPath !== '' ? $publicPath : '/';
    define('PUBLIC_URL', rtrim($publicBase, '/') . '/');
}

if (!defined('BASE_URL')) {
    $basePath = preg_replace('#/public$#', '', PUBLIC_URL);
    define('BASE_URL', $basePath !== '' ? $basePath : '/');
}

if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', rtrim(PUBLIC_URL, '/') . '/assets');
}

// Cargar el controlador base
require_once APP . '/Controllers/BaseController.php';

// Cargar rutas
$routesPath = null;
foreach ($routesPathCandidates as $candidate) {
    if (is_file($candidate)) {
        $routesPath = $candidate;
        break;
    }
}

if ($routesPath === null) {
    die('No se encontró el archivo de rutas (routes.php).');
}

$routes = require_once $routesPath;

if (!is_array($routes)) {
    $routes = [];
}

// Fallback para produccion: asegurar rutas minimas de autenticacion
$authRoutesFallback = [
    'auth/login' => 'AuthController@login',
    'auth/logout' => 'AuthController@logout',
    'auth/acceso-denegado' => 'AuthController@accesoDenegado',
];

foreach ($authRoutesFallback as $routeKey => $routeTarget) {
    if (!array_key_exists($routeKey, $routes)) {
        $routes[$routeKey] = $routeTarget;
    }
}

// Obtener la URL solicitada (soporta tanto 'url' como 'route')
$url = isset($_GET['url']) ? trim($_GET['url'], '/') : (isset($_GET['route']) ? trim($_GET['route'], '/') : 'home');

// Rutas públicas que no requieren autenticación
$rutasPublicas = [
    'auth/login',
    'auth/cambiar-cuenta',
    'registro_obsequio',
    'registro_obsequio/guardar',
    'registro_personas',
    'registro_personas/guardar',
    'teen/registro-publico',
    'teen/guardar-menor-publico',
    'teen/consulta-codigo',
    'escuelas_formacion/codigos',
    'escuelas_formacion/registro-publico',
    'escuelas_formacion/registro-publico/buscar-persona',
    'escuelas_formacion/registro-publico/buscar-lideres',
    'escuelas_formacion/registro-publico/guardar',
    'escuelas_formacion/asistencia-publica',
    'escuelas_formacion/asistencia-publica/buscar',
    'escuelas_formacion/asistencia-publica/guardar',
    'peticiones_publica',
    'peticiones_publica/guardar',
    'stream/live',
    'stream/gallery',
    'eventos/proximos',
    'eventos/universidad-vida/publico',
    'eventos/capacitacion-destino/publico',
    'transmisiones-publico',
    'nehemias',
    'nehemias/formulario',
    'nehemias/guardar',
    'nehemias/testigos-electorales/formulario',
    'nehemias/testigos-electorales/guardar',
    'nehemias/whatsapp/webhook'
];

// Verificar autenticación (excepto para rutas públicas)
if (!in_array($url, $rutasPublicas)) {
    require_once APP . '/Controllers/AuthController.php';
    
    if (!AuthController::estaAutenticado()) {
        header('Location: ' . rtrim(PUBLIC_URL, '/') . '/index.php?url=auth/login');
        exit;
    }
}

// Buscar la ruta
if (array_key_exists($url, $routes)) {
    list($controllerName, $method) = explode('@', $routes[$url]);
    
    $controllerFile = APP . '/Controllers/' . $controllerName . '.php';
    
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        
        if (class_exists($controllerName)) {
            $controller = new $controllerName();
            
            if (method_exists($controller, $method)) {
                $controller->$method();
            } else {
                die("Método $method no encontrado en $controllerName");
            }
        } else {
            die("Clase $controllerName no encontrada");
        }
    } else {
        die("Archivo del controlador no encontrado: $controllerFile");
    }
} else {
    // Ruta no encontrada
    http_response_code(404);
    echo "<h1>404 - Página no encontrada</h1>";
    echo "<p>La ruta solicitada no existe: $url</p>";
    echo "<a href='index.php?url=home'>Volver al inicio</a>";
}
