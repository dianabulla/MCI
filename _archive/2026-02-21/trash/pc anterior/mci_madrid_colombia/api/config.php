<?php
/**
 * Configuración API REST
 */

// Headers para permitir peticiones desde React Native
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir configuración principal del proyecto
define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
require_once APP . '/Config/config.php';
require_once APP . '/Config/Database.php';

/**
 * Función helper para retornar respuestas JSON
 */
function jsonResponse($success, $data = null, $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Función helper para validar token JWT (básico)
 */
function validateToken() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';
    
    if (empty($token)) {
        jsonResponse(false, null, 'Token no proporcionado', 401);
    }
    
    // Remover 'Bearer ' si está presente
    $token = str_replace('Bearer ', '', $token);
    
    // Aquí validarías el token JWT
    // Por ahora usamos sesión simple
    session_start();
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, null, 'No autorizado', 401);
    }
    
    return $_SESSION['user_id'];
}

/**
 * Obtener el método HTTP
 */
function getRequestMethod() {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Obtener datos del body (JSON)
 */
function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true);
}
