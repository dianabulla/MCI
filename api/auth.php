<?php
/**
 * API Endpoint: Autenticación
 */

require_once 'config.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/UsuarioAcceso.php';

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
    
    $usuarioAccesoModel = new UsuarioAcceso();
    $user = $usuarioAccesoModel->autenticarUnificado((string)$input['usuario'], (string)$input['password']);

    if (empty($user)) {
        jsonResponse(false, null, 'Credenciales inválidas', 401);
    }

    $idPersona = (int)($user['Id_Persona'] ?? 0);
    $nombre = trim((string)($user['Nombre'] ?? '') . ' ' . (string)($user['Apellido'] ?? ''));
    if ($nombre === '') {
        $nombre = trim((string)($user['Nombre_Mostrar'] ?? ''));
    }
    if ($nombre === '') {
        $nombre = trim((string)($user['Usuario'] ?? 'Usuario'));
    }

    session_start();
    $_SESSION['user_id'] = $idPersona;
    $_SESSION['auth_user_id'] = (int)($user['Id_Usuario_Acceso'] ?? 0);
    $_SESSION['auth_user_source'] = 'acceso';
    $_SESSION['user_name'] = $nombre;
    $_SESSION['user_role'] = $user['Codigo_Rol'] ?? null;
    $_SESSION['user_role_name'] = $user['Nombre_Rol'] ?? '';

    unset($user['Contrasena'], $user['Password']);
    
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
    
    if (empty($_SESSION['auth_user_id']) && empty($_SESSION['user_id'])) {
        jsonResponse(false, null, 'No hay sesión activa', 401);
    }

    $idAcceso = (int)($_SESSION['auth_user_id'] ?? 0);
    if ($idAcceso > 0) {
        $usuarioAccesoModel = new UsuarioAcceso();
        $user = $usuarioAccesoModel->getById($idAcceso);
        if ($user) {
            unset($user['Contrasena'], $user['Password']);
            jsonResponse(true, [
                'user' => $user,
                'session' => [
                    'id' => (int)($user['Id_Persona'] ?? $_SESSION['user_id'] ?? 0),
                    'name' => $_SESSION['user_name'] ?? '',
                    'role' => $_SESSION['user_role'] ?? null
                ]
            ], 'Sesión activa', 200);
        }
    }

    $idPersona = (int)($_SESSION['user_id'] ?? 0);
    if ($idPersona <= 0) {
        jsonResponse(false, null, 'No hay sesión activa', 401);
    }

    $personaModel = new Persona();
    $user = $personaModel->getById($idPersona);
    
    if (!$user) {
        jsonResponse(false, null, 'Usuario no encontrado', 404);
    }
    
    unset($user['Contrasena'], $user['Password']);
    
    jsonResponse(true, [
        'user' => $user,
        'session' => [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? '',
            'role' => $_SESSION['user_role'] ?? null
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
