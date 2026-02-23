<?php
/**
 * API Endpoint: Peticiones
 */

require_once 'config.php';
require_once APP . '/Models/Peticion.php';

$method = getRequestMethod();
$peticionModel = new Peticion();

// Validar autenticación
$userId = validateToken();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getPeticion($_GET['id']);
        } else {
            getPeticiones();
        }
        break;
    case 'POST':
        createPeticion();
        break;
    case 'PUT':
        updatePeticion();
        break;
    case 'DELETE':
        deletePeticion();
        break;
    default:
        jsonResponse(false, null, 'Método no permitido', 405);
}

/**
 * Listar peticiones - GET /api/peticiones.php
 */
function getPeticiones() {
    global $peticionModel;
    
    // Filtros opcionales
    $idPersona = $_GET['persona'] ?? null;
    $estado = $_GET['estado'] ?? null;
    
    $sql = "SELECT pt.*, 
            CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Persona,
            p.Telefono AS Telefono_Persona
            FROM peticion pt
            LEFT JOIN persona p ON pt.Id_Persona = p.Id_Persona
            WHERE 1=1";
    
    $params = [];
    
    if ($idPersona !== null) {
        $sql .= " AND pt.Id_Persona = ?";
        $params[] = $idPersona;
    }
    
    if ($estado !== null) {
        $sql .= " AND pt.Estado = ?";
        $params[] = $estado;
    }
    
    $sql .= " ORDER BY pt.Fecha_Peticion DESC";
    
    $peticiones = $peticionModel->query($sql, $params);
    
    jsonResponse(true, $peticiones, 'Peticiones obtenidas correctamente', 200);
}

/**
 * Obtener petición por ID - GET /api/peticiones.php?id=X
 */
function getPeticion($id) {
    global $peticionModel;
    
    $peticion = $peticionModel->getById($id);
    
    if (!$peticion) {
        jsonResponse(false, null, 'Petición no encontrada', 404);
    }
    
    jsonResponse(true, $peticion, 'Petición obtenida correctamente', 200);
}

/**
 * Crear petición - POST /api/peticiones.php
 */
function createPeticion() {
    global $peticionModel;
    
    $input = getJsonInput();
    
    if (empty($input['Descripcion_Peticion'])) {
        jsonResponse(false, null, 'Descripción de petición es requerida', 400);
    }
    
    // Establecer fecha actual si no viene
    if (empty($input['Fecha_Peticion'])) {
        $input['Fecha_Peticion'] = date('Y-m-d H:i:s');
    }
    
    // Estado por defecto
    if (empty($input['Estado'])) {
        $input['Estado'] = 'Pendiente';
    }
    
    $id = $peticionModel->create($input);
    
    if ($id) {
        $peticion = $peticionModel->getById($id);
        jsonResponse(true, $peticion, 'Petición creada correctamente', 201);
    } else {
        jsonResponse(false, null, 'Error al crear petición', 500);
    }
}

/**
 * Actualizar petición - PUT /api/peticiones.php
 */
function updatePeticion() {
    global $peticionModel;
    
    $input = getJsonInput();
    
    if (empty($input['Id_Peticion'])) {
        jsonResponse(false, null, 'ID de petición es requerido', 400);
    }
    
    $id = $input['Id_Peticion'];
    unset($input['Id_Peticion']);
    
    $result = $peticionModel->update($id, $input);
    
    if ($result) {
        $peticion = $peticionModel->getById($id);
        jsonResponse(true, $peticion, 'Petición actualizada correctamente', 200);
    } else {
        jsonResponse(false, null, 'Error al actualizar petición', 500);
    }
}

/**
 * Eliminar petición - DELETE /api/peticiones.php
 */
function deletePeticion() {
    global $peticionModel;
    
    $input = getJsonInput();
    
    if (empty($input['id'])) {
        jsonResponse(false, null, 'ID de petición es requerido', 400);
    }
    
    $result = $peticionModel->delete($input['id']);
    
    if ($result) {
        jsonResponse(true, null, 'Petición eliminada correctamente', 200);
    } else {
        jsonResponse(false, null, 'Error al eliminar petición', 500);
    }
}
