<?php
/**
 * Front Controller - Punto de entrada de la aplicación
 */

// Configurar zona horaria Colombia
date_default_timezone_set('America/Bogota');

// Configurar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Definir constantes
define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
define('VIEWS', ROOT . '/views');

// Errores fatales visibles en producción (pantalla en blanco)
register_shutdown_function(static function () {
    $err = error_get_last();
    if (!$err || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    if (headers_sent()) {
        return;
    }
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Error</title></head><body style="font-family:sans-serif;padding:24px;">';
    echo '<h1>Error al cargar la aplicación</h1>';
    echo '<p><strong>' . htmlspecialchars((string)$err['message'], ENT_QUOTES, 'UTF-8') . '</strong></p>';
    echo '<p style="color:#64748b;font-size:14px;">' . htmlspecialchars((string)$err['file'], ENT_QUOTES, 'UTF-8') . ':' . (int)$err['line'] . '</p>';
    echo '<p>Si acabas de desplegar, verifica que subiste <code>app/Helpers/AssetVersion.php</code>, <code>app/Helpers/SessionManager.php</code> y <code>app/Config/config.php</code>.</p>';
    echo '</body></html>';
});

// Cargar configuración ANTES que conexion.php (evita config antiguo en caché de require_once)
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

// Cargar conexión a base de datos (después de config)
require_once ROOT . '/conexion.php';

$sessionManagerCandidates = [
    APP . '/Helpers/SessionManager.php',
    APP . '/helpers/SessionManager.php',
];
$sessionManagerLoaded = false;
foreach ($sessionManagerCandidates as $candidate) {
    if (is_file($candidate)) {
        require_once $candidate;
        $sessionManagerLoaded = true;
        break;
    }
}
if ($sessionManagerLoaded && class_exists('SessionManager', false)) {
    SessionManager::configure();
}
session_start();
if ($sessionManagerLoaded && class_exists('SessionManager', false)) {
    SessionManager::bootstrap();
}

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

$reportesRoutesFallback = [
    'reportes/dashboard-escuelas-uv-detalle' => 'ReporteController@dashboardEscuelasUvDetalleMinisterio',
];
foreach ($reportesRoutesFallback as $routeKey => $routeTarget) {
    if (!array_key_exists($routeKey, $routes)) {
        $routes[$routeKey] = $routeTarget;
    }
}

$herramientasRoutesFallback = [
    'herramientas/diagnostico-permisos-persona' => 'DiagnosticoPermisosPersonaController@index',
];

$discipularRoutesFallback = [
    'discipular/ministerios/personas-asignables' => 'MinisterioController@personasAsignablesJson',
];
foreach ($discipularRoutesFallback as $routeKey => $routeTarget) {
    if (!array_key_exists($routeKey, $routes)) {
        $routes[$routeKey] = $routeTarget;
    }
}
foreach ($herramientasRoutesFallback as $routeKey => $routeTarget) {
    if (!array_key_exists($routeKey, $routes)) {
        $routes[$routeKey] = $routeTarget;
    }
}

$escuelasRegistroPublicoRoutesFallback = [
    'escuelas_formacion/registro-publico/subir-documentos' => 'EscuelaFormacionRegistroController@subirDocumentosPublico',
];
foreach ($escuelasRegistroPublicoRoutesFallback as $routeKey => $routeTarget) {
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
    'teen/buscar-menor-publico-telefono',
    'teen/buscar-menor-publico-documento',
    'escuelas_formacion/codigos',
    'escuelas_formacion/registro-publico/universidad-vida',
    'escuelas_formacion/registro-publico/capacitacion-destino',
    'escuelas_formacion/registro-publico/buscar-persona',
    'escuelas_formacion/registro-publico/buscar-lideres',
    'escuelas_formacion/registro-publico/validar-abono',
    'escuelas_formacion/registro-publico/guardar',
    'escuelas_formacion/registro-publico/subir-documentos',
    'escuelas_formacion/registro-publico/ticket',
    'escuelas_formacion/asistencia-publica',
    'escuelas_formacion/asistencia-publica/buscar',
    'escuelas_formacion/asistencia-publica/guardar',
    'peticiones_publica',
    'peticiones_publica/guardar',
    'talleres_publico',
    'talleres_publico/guardar',
    'talleres_publico/buscar-persona',
    'talleres_publico/qr',
    'talleres_publico/servicio-social',
    'talleres_publico/servicio-social/guardar',
    'talleres_publico/servicio-social/buscar-persona',
    'talleres_publico/servicio-social/disponibilidad',
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

// Rutas públicas mínimas por si el despliegue no actualizó todo el array anterior
$rutasPublicasExtra = [
    'escuelas_formacion/registro-publico/subir-documentos',
];
$rutasPublicas = array_values(array_unique(array_merge($rutasPublicas, $rutasPublicasExtra)));

// Enlace temporal para probar la migración
if (isset($_GET['accion']) && $_GET['accion'] === 'migrar_consolidados') {
    header('Location: ' . BASE_URL . 'discipular/migrar-consolidados');
    exit;
}

// Verificar autenticación (excepto para rutas públicas)
if (!in_array($url, $rutasPublicas)) {
    require_once APP . '/Controllers/AuthController.php';

    if (AuthController::estaAutenticado() && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    if (!AuthController::estaAutenticado()) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'mensaje' => 'No se pudo completar la solicitud. Recargue la página e intente de nuevo.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        header('Location: ' . rtrim(PUBLIC_URL, '/') . '/index.php?url=auth/login');
        exit;
    }

    $rutasSinAcuerdoConfidencialidad = [
        'auth/acuerdo-confidencialidad',
        'auth/aceptar-acuerdo-confidencialidad',
        'auth/logout',
    ];
    if (AuthController::debeAceptarAcuerdoConfidencialidad() && !in_array($url, $rutasSinAcuerdoConfidencialidad, true)) {
        header('Location: ' . rtrim(PUBLIC_URL, '/') . '/index.php?url=auth/acuerdo-confidencialidad');
        exit;
    }

    require_once APP . '/Helpers/RouteGuard.php';
    RouteGuard::denegarSiNoPuede($url);
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
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'mensaje' => 'El servicio solicitado no está disponible. Actualice la página o contacte al administrador.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo "<h1>404 - Página no encontrada</h1>";
    echo "<p>La ruta solicitada no existe: $url</p>";
    $homeHref = htmlspecialchars(rtrim(PUBLIC_URL, '/') . '/index.php?url=home', ENT_QUOTES, 'UTF-8');
    echo "<a href='" . $homeHref . "'>Volver al inicio</a>";
}
