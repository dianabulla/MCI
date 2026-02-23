<?php
/**
 * API Endpoint: Autenticación
 */

require_once 'config.php';
require_once APP . '/Models/Persona.php';

$method = getRequestMethod();

switch ($method) {
    case 'POST':
        login();
        break;
    case 'GET':
        checkSession();
        break;
    case 'DELETE':
        logout();
        break;
    default:
        jsonResponse(false, null, 'Método no permitido', 405);
}

/**
 * Login - POST /api/auth.php
 */
function login() {
    $input = getJsonInput();
    
    if (empty($input['usuario']) || empty($input['password'])) {
        jsonResponse(false, null, 'Usuario y contraseña son requeridos', 400);
    }
    
    $personaModel = new Persona();
    
    // Buscar usuario
    $sql = "SELECT p.*, r.Nombre_Rol, r.Codigo_Rol 
            FROM persona p 
            INNER JOIN rol r ON p.Id_Rol = r.Id_Rol 
            WHERE p.Usuario = ?";
    
    $result = $personaModel->query($sql, [$input['usuario']]);
    
    if (empty($result)) {
        jsonResponse(false, null, 'Credenciales inválidas', 401);
    }
    
    $user = $result[0];
    
    // Verificar contraseña
    if (!password_verify($input['password'], $user['Password'])) {
        jsonResponse(false, null, 'Credenciales inválidas', 401);
    }
    
    // Crear sesión
    session_start();
    $_SESSION['user_id'] = $user['Id_Persona'];
    $_SESSION['user_name'] = $user['Nombre'] . ' ' . $user['Apellido'];
    $_SESSION['user_role'] = $user['Codigo_Rol'];
    $_SESSION['user_role_name'] = $user['Nombre_Rol'];
    
    // Retornar datos del usuario (sin password)
    unset($user['Password']);
    
    jsonResponse(true, [
        'user' => $user,
        'session_id' => session_id()
    ], 'Login exitoso', 200);
}

/**
 * Verificar sesión - GET /api/auth.php
 */
function checkSession() {
    session_start();
    
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, null, 'No hay sesión activa', 401);
    }
    
    $personaModel = new Persona();
    $user = $personaModel->getById($_SESSION['user_id']);
    
    if (!$user) {
        jsonResponse(false, null, 'Usuario no encontrado', 404);
    }
    
    unset($user['Password']);
    
    jsonResponse(true, [
        'user' => $user,
        'session' => [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role']
        ]
    ], 'Sesión activa', 200);
}

/**
 * Logout - DELETE /api/auth.php
 */
function logout() {
    session_start();
    session_destroy();
    
    jsonResponse(true, null, 'Logout exitoso', 200);
}
