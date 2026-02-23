<?php
/**
 * API Endpoint: Roles
 */

require_once 'config.php';
require_once APP . '/Models/Rol.php';

$method = getRequestMethod();
$rolModel = new Rol();

// Validar autenticación
$userId = validateToken();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getRol($_GET['id']);
        } else {
            getRoles();
        }
        break;
    case 'POST':
        createRol();
        break;
    case 'PUT':
        updateRol();
        break;
    case 'DELETE':
        deleteRol();
        break;
    default:
        jsonResponse(false, null, 'Método no permitido', 405);
}

/**
 * Listar roles - GET /api/roles.php
 */
function getRoles() {
    global $rolModel;
    
    $roles = $rolModel->getAll();
    
    jsonResponse(true, $roles, 'Roles obtenidos correctamente', 200);
}

/**
 * Obtener rol por ID - GET /api/roles.php?id=X
 */
function getRol($id) {
    global $rolModel;
    
    $rol = $rolModel->getById($id);
    
    if (!$rol) {
        jsonResponse(false, null, 'Rol no encontrado', 404);
    }
    
    jsonResponse(true, $rol, 'Rol obtenido correctamente', 200);
}

/**
 * Crear rol - POST /api/roles.php
 */
function createRol() {
    global $rolModel;
    
    $input = getJsonInput();
    
    if (empty($input['Nombre_Rol']) || empty($input['Codigo_Rol'])) {
        jsonResponse(false, null, 'Nombre y código de rol son requeridos', 400);
    }
    
    $id = $rolModel->create($input);
    
    if ($id) {
        $rol = $rolModel->getById($id);
        jsonResponse(true, $rol, 'Rol creado correctamente', 201);
    } else {
        jsonResponse(false, null, 'Error al crear rol', 500);
    }
}

/**
 * Actualizar rol - PUT /api/roles.php
 */
function updateRol() {
    global $rolModel;
    
    $input = getJsonInput();
    
    if (empty($input['Id_Rol'])) {
        jsonResponse(false, null, 'ID de rol es requerido', 400);
    }
    
    $id = $input['Id_Rol'];
    unset($input['Id_Rol']);
    
    $result = $rolModel->update($id, $input);
    
    if ($result) {
        $rol = $rolModel->getById($id);
        jsonResponse(true, $rol, 'Rol actualizado correctamente', 200);
    } else {
        jsonResponse(false, null, 'Error al actualizar rol', 500);
    }
}

/**
 * Eliminar rol - DELETE /api/roles.php
 */
function deleteRol() {
    global $rolModel;
    
    $input = getJsonInput();
    
    if (empty($input['id'])) {
        jsonResponse(false, null, 'ID de rol es requerido', 400);
    }
    
    $result = $rolModel->delete($input['id']);
    
    if ($result) {
        jsonResponse(true, null, 'Rol eliminado correctamente', 200);
    } else {
        jsonResponse(false, null, 'Error al eliminar rol', 500);
    }
}
