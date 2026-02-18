<?php
/**
 * API Endpoint: Ministerios
 */

require_once 'config.php';
require_once APP . '/Models/Ministerio.php';

$method = getRequestMethod();
$ministerioModel = new Ministerio();

// Validar autenticación
$userId = validateToken();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getMinisterio($_GET['id']);
        } else {
            getMinisterios();
        }
        break;
    case 'POST':
        createMinisterio();
        break;
    case 'PUT':
        updateMinisterio();
        break;
    case 'DELETE':
        deleteMinisterio();
        break;
    default:
        jsonResponse(false, null, 'Método no permitido', 405);
}

/**
 * Listar ministerios - GET /api/ministerios.php
 */
function getMinisterios() {
    global $ministerioModel;
    
    $ministerios = $ministerioModel->getAll();
    
    jsonResponse(true, $ministerios, 'Ministerios obtenidos correctamente', 200);
}

/**
 * Obtener ministerio por ID - GET /api/ministerios.php?id=X
 */
function getMinisterio($id) {
    global $ministerioModel;
    
    $ministerio = $ministerioModel->getById($id);
    
    if (!$ministerio) {
        jsonResponse(false, null, 'Ministerio no encontrado', 404);
    }
    
    jsonResponse(true, $ministerio, 'Ministerio obtenido correctamente', 200);
}

/**
 * Crear ministerio - POST /api/ministerios.php
 */
function createMinisterio() {
    global $ministerioModel;
    
    $input = getJsonInput();
    
    if (empty($input['Nombre_Ministerio'])) {
        jsonResponse(false, null, 'Nombre de ministerio es requerido', 400);
    }
    
    $id = $ministerioModel->create($input);
    
    if ($id) {
        $ministerio = $ministerioModel->getById($id);
        jsonResponse(true, $ministerio, 'Ministerio creado correctamente', 201);
    } else {
        jsonResponse(false, null, 'Error al crear ministerio', 500);
    }
}

/**
 * Actualizar ministerio - PUT /api/ministerios.php
 */
function updateMinisterio() {
    global $ministerioModel;
    
    $input = getJsonInput();
    
    if (empty($input['Id_Ministerio'])) {
        jsonResponse(false, null, 'ID de ministerio es requerido', 400);
    }
    
    $id = $input['Id_Ministerio'];
    unset($input['Id_Ministerio']);
    
    $result = $ministerioModel->update($id, $input);
    
    if ($result) {
        $ministerio = $ministerioModel->getById($id);
        jsonResponse(true, $ministerio, 'Ministerio actualizado correctamente', 200);
    } else {
        jsonResponse(false, null, 'Error al actualizar ministerio', 500);
    }
}

/**
 * Eliminar ministerio - DELETE /api/ministerios.php
 */
function deleteMinisterio() {
    global $ministerioModel;
    
    $input = getJsonInput();
    
    if (empty($input['id'])) {
        jsonResponse(false, null, 'ID de ministerio es requerido', 400);
    }
    
    $result = $ministerioModel->delete($input['id']);
    
    if ($result) {
        jsonResponse(true, null, 'Ministerio eliminado correctamente', 200);
    } else {
        jsonResponse(false, null, 'Error al eliminar ministerio', 500);
    }
}
