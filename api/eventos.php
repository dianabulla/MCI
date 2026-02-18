<?php
/**
 * API Endpoint: Eventos
 */

require_once 'config.php';
require_once APP . '/Models/Evento.php';

$method = getRequestMethod();
$eventoModel = new Evento();

// Validar autenticación
$userId = validateToken();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getEvento($_GET['id']);
        } else {
            getEventos();
        }
        break;
    case 'POST':
        createEvento();
        break;
    case 'PUT':
        updateEvento();
        break;
    case 'DELETE':
        deleteEvento();
        break;
    default:
        jsonResponse(false, null, 'Método no permitido', 405);
}

/**
 * Listar eventos - GET /api/eventos.php
 */
function getEventos() {
    global $eventoModel;
    
    $eventos = $eventoModel->getAll();
    
    jsonResponse(true, $eventos, 'Eventos obtenidos correctamente', 200);
}

/**
 * Obtener evento por ID - GET /api/eventos.php?id=X
 */
function getEvento($id) {
    global $eventoModel;
    
    $evento = $eventoModel->getById($id);
    
    if (!$evento) {
        jsonResponse(false, null, 'Evento no encontrado', 404);
    }
    
    jsonResponse(true, $evento, 'Evento obtenido correctamente', 200);
}

/**
 * Crear evento - POST /api/eventos.php
 */
function createEvento() {
    global $eventoModel;
    
    $input = getJsonInput();
    
    if (empty($input['Nombre_Evento'])) {
        jsonResponse(false, null, 'Nombre de evento es requerido', 400);
    }
    
    $id = $eventoModel->create($input);
    
    if ($id) {
        $evento = $eventoModel->getById($id);
        jsonResponse(true, $evento, 'Evento creado correctamente', 201);
    } else {
        jsonResponse(false, null, 'Error al crear evento', 500);
    }
}

/**
 * Actualizar evento - PUT /api/eventos.php
 */
function updateEvento() {
    global $eventoModel;
    
    $input = getJsonInput();
    
    if (empty($input['Id_Evento'])) {
        jsonResponse(false, null, 'ID de evento es requerido', 400);
    }
    
    $id = $input['Id_Evento'];
    unset($input['Id_Evento']);
    
    $result = $eventoModel->update($id, $input);
    
    if ($result) {
        $evento = $eventoModel->getById($id);
        jsonResponse(true, $evento, 'Evento actualizado correctamente', 200);
    } else {
        jsonResponse(false, null, 'Error al actualizar evento', 500);
    }
}

/**
 * Eliminar evento - DELETE /api/eventos.php
 */
function deleteEvento() {
    global $eventoModel;
    
    $input = getJsonInput();
    
    if (empty($input['id'])) {
        jsonResponse(false, null, 'ID de evento es requerido', 400);
    }
    
    $result = $eventoModel->delete($input['id']);
    
    if ($result) {
        jsonResponse(true, null, 'Evento eliminado correctamente', 200);
    } else {
        jsonResponse(false, null, 'Error al eliminar evento', 500);
    }
}
