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

// Cargar configuración
require_once APP . '/Config/config.php';
require_once APP . '/Config/Database.php';

// Cargar el controlador base
require_once APP . '/Controllers/BaseController.php';

// Cargar rutas
$routes = require_once APP . '/Config/routes.php';

// Obtener la URL solicitada (soporta tanto 'url' como 'route')
$url = isset($_GET['url']) ? trim($_GET['url'], '/') : (isset($_GET['route']) ? trim($_GET['route'], '/') : 'home');

// Rutas públicas que no requieren autenticación
$rutasPublicas = [
    'auth/login',
    'registro_obsequio',
    'registro_obsequio/guardar',
    'stream/live',
    'stream/gallery'
];

// Verificar autenticación (excepto para rutas públicas)
if (!in_array($url, $rutasPublicas)) {
    require_once APP . '/Controllers/AuthController.php';
    
    if (!AuthController::estaAutenticado()) {
        header('Location: ' . PUBLIC_URL . '?url=auth/login');
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
