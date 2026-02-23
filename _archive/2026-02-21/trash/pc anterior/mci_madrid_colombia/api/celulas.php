<?php
/**
 * API Endpoint: Células
 */

require_once 'config.php';
require_once APP . '/Models/Celula.php';

$method = getRequestMethod();
$celulaModel = new Celula();

// Validar autenticación
$userId = validateToken();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getCelula($_GET['id']);
        } else {
            getCelulas();
        }
        break;
    case 'POST':
        createCelula();
        break;
    case 'PUT':
        updateCelula();
        break;
    case 'DELETE':
        deleteCelula();
        break;
    default:
        jsonResponse(false, null, 'Método no permitido', 405);
}

/**
 * Listar células - GET /api/celulas.php
 */
function getCelulas() {
    global $celulaModel;
    
    $celulas = $celulaModel->getAllWithMemberCount();
    
    jsonResponse(true, $celulas, 'Células obtenidas correctamente', 200);
}

/**
 * Obtener célula por ID - GET /api/celulas.php?id=X
 */
function getCelula($id) {
    global $celulaModel;
    
    $celula = $celulaModel->getWithMembers($id);
    
    if (!$celula) {
        jsonResponse(false, null, 'Célula no encontrada', 404);
    }
    
    jsonResponse(true, $celula, 'Célula obtenida correctamente', 200);
}

/**
 * Crear célula - POST /api/celulas.php
 */
function createCelula() {
    global $celulaModel;
    
    $input = getJsonInput();
    
    if (empty($input['Nombre_Celula'])) {
        jsonResponse(false, null, 'Nombre de célula es requerido', 400);
    }
    
    $id = $celulaModel->create($input);
    
    if ($id) {
        $celula = $celulaModel->getById($id);
        jsonResponse(true, $celula, 'Célula creada correctamente', 201);
    } else {
        jsonResponse(false, null, 'Error al crear célula', 500);
    }
}

/**
 * Actualizar célula - PUT /api/celulas.php
 */
function updateCelula() {
    global $celulaModel;
    
    $input = getJsonInput();
    
    if (empty($input['Id_Celula'])) {
        jsonResponse(false, null, 'ID de célula es requerido', 400);
    }
    
    $id = $input['Id_Celula'];
    unset($input['Id_Celula']);
    
    $result = $celulaModel->update($id, $input);
    
    if ($result) {
        $celula = $celulaModel->getById($id);
        jsonResponse(true, $celula, 'Célula actualizada correctamente', 200);
    } else {
        jsonResponse(false, null, 'Error al actualizar célula', 500);
    }
}

/**
 * Eliminar célula - DELETE /api/celulas.php
 */
function deleteCelula() {
    global $celulaModel;
    
    $input = getJsonInput();
    
    if (empty($input['id'])) {
        jsonResponse(false, null, 'ID de célula es requerido', 400);
    }
    
    $result = $celulaModel->delete($input['id']);
    
    if ($result) {
        jsonResponse(true, null, 'Célula eliminada correctamente', 200);
    } else {
        jsonResponse(false, null, 'Error al eliminar célula', 500);
    }
}
