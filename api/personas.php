<?php
/**
 * API Endpoint: Personas
 */

require_once 'config.php';
require_once APP . '/Models/Persona.php';

$method = getRequestMethod();
$personaModel = new Persona();

// Rutas públicas (sin autenticación)
$publicRoutes = [];

// Validar autenticación para rutas protegidas
if (!in_array($_SERVER['REQUEST_URI'], $publicRoutes)) {
    $userId = validateToken();
}

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getPersona($_GET['id']);
        } else {
            getPersonas();
        }
        break;
    case 'POST':
        createPersona();
        break;
    case 'PUT':
        updatePersona();
        break;
    case 'DELETE':
        deletePersona();
        break;
    default:
        jsonResponse(false, null, 'Método no permitido', 405);
}

/**
 * Listar personas - GET /api/personas.php
 */
function getPersonas() {
    global $personaModel;
    
    // Filtros opcionales
    $idMinisterio = $_GET['ministerio'] ?? null;
    $idLider = $_GET['lider'] ?? null;
    
    if ($idMinisterio !== null || $idLider !== null) {
        $personas = $personaModel->getWithFilters($idMinisterio, $idLider);
    } else {
        $personas = $personaModel->getAllWithRelations();
    }
    
    jsonResponse(true, $personas, 'Personas obtenidas correctamente', 200);
}

/**
 * Obtener persona por ID - GET /api/personas.php?id=X
 */
function getPersona($id) {
    global $personaModel;
    
    $persona = $personaModel->getById($id);
    
    if (!$persona) {
        jsonResponse(false, null, 'Persona no encontrada', 404);
    }
    
    jsonResponse(true, $persona, 'Persona obtenida correctamente', 200);
}

/**
 * Crear persona - POST /api/personas.php
 */
function createPersona() {
    global $personaModel;
    
    $input = getJsonInput();
    
    // Validaciones básicas
    if (empty($input['Nombre']) || empty($input['Apellido'])) {
        jsonResponse(false, null, 'Nombre y apellido son requeridos', 400);
    }
    
    // Crear persona
    $id = $personaModel->create($input);
    
    if ($id) {
        $persona = $personaModel->getById($id);
        jsonResponse(true, $persona, 'Persona creada correctamente', 201);
    } else {
        jsonResponse(false, null, 'Error al crear persona', 500);
    }
}

/**
 * Actualizar persona - PUT /api/personas.php
 */
function updatePersona() {
    global $personaModel;
    
    $input = getJsonInput();
    
    if (empty($input['Id_Persona'])) {
        jsonResponse(false, null, 'ID de persona es requerido', 400);
    }
    
    $id = $input['Id_Persona'];
    unset($input['Id_Persona']);
    
    $result = $personaModel->update($id, $input);
    
    if ($result) {
        $persona = $personaModel->getById($id);
        jsonResponse(true, $persona, 'Persona actualizada correctamente', 200);
    } else {
        jsonResponse(false, null, 'Error al actualizar persona', 500);
    }
}

/**
 * Eliminar persona - DELETE /api/personas.php
 */
function deletePersona() {
    global $personaModel;
    
    $input = getJsonInput();
    
    if (empty($input['id'])) {
        jsonResponse(false, null, 'ID de persona es requerido', 400);
    }
    
    $result = $personaModel->delete($input['id']);
    
    if ($result) {
        jsonResponse(true, null, 'Persona eliminada correctamente', 200);
    } else {
        jsonResponse(false, null, 'Error al eliminar persona', 500);
    }
}
