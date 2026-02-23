<?php
/**
 * API Endpoint: Asistencias
 */

require_once 'config.php';
require_once APP . '/Models/Asistencia.php';

$method = getRequestMethod();
$asistenciaModel = new Asistencia();

// Validar autenticación
$userId = validateToken();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getAsistencia($_GET['id']);
        } else {
            getAsistencias();
        }
        break;
    case 'POST':
        createAsistencia();
        break;
    case 'PUT':
        updateAsistencia();
        break;
    case 'DELETE':
        deleteAsistencia();
        break;
    default:
        jsonResponse(false, null, 'Método no permitido', 405);
}

/**
 * Listar asistencias - GET /api/asistencias.php
 */
function getAsistencias() {
    global $asistenciaModel;
    
    // Filtros opcionales
    $idEvento = $_GET['evento'] ?? null;
    $idCelula = $_GET['celula'] ?? null;
    $fecha = $_GET['fecha'] ?? null;
    
    $sql = "SELECT a.*, 
            CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Persona,
            e.Nombre_Evento,
            c.Nombre_Celula
            FROM asistencia a
            LEFT JOIN persona p ON a.Id_Persona = p.Id_Persona
            LEFT JOIN evento e ON a.Id_Evento = e.Id_Evento
            LEFT JOIN celula c ON a.Id_Celula = c.Id_Celula
            WHERE 1=1";
    
    $params = [];
    
    if ($idEvento !== null) {
        $sql .= " AND a.Id_Evento = ?";
        $params[] = $idEvento;
    }
    
    if ($idCelula !== null) {
        $sql .= " AND a.Id_Celula = ?";
        $params[] = $idCelula;
    }
    
    if ($fecha !== null) {
        $sql .= " AND DATE(a.Fecha_Asistencia) = ?";
        $params[] = $fecha;
    }
    
    $sql .= " ORDER BY a.Fecha_Asistencia DESC";
    
    $asistencias = $asistenciaModel->query($sql, $params);
    
    jsonResponse(true, $asistencias, 'Asistencias obtenidas correctamente', 200);
}

/**
 * Obtener asistencia por ID - GET /api/asistencias.php?id=X
 */
function getAsistencia($id) {
    global $asistenciaModel;
    
    $asistencia = $asistenciaModel->getById($id);
    
    if (!$asistencia) {
        jsonResponse(false, null, 'Asistencia no encontrada', 404);
    }
    
    jsonResponse(true, $asistencia, 'Asistencia obtenida correctamente', 200);
}

/**
 * Crear asistencia - POST /api/asistencias.php
 */
function createAsistencia() {
    global $asistenciaModel;
    
    $input = getJsonInput();
    
    if (empty($input['Id_Persona'])) {
        jsonResponse(false, null, 'ID de persona es requerido', 400);
    }
    
    // Establecer fecha actual si no viene
    if (empty($input['Fecha_Asistencia'])) {
        $input['Fecha_Asistencia'] = date('Y-m-d H:i:s');
    }
    
    // Por defecto, asistencia confirmada
    if (!isset($input['Estado'])) {
        $input['Estado'] = 'Presente';
    }
    
    $id = $asistenciaModel->create($input);
    
    if ($id) {
        $asistencia = $asistenciaModel->getById($id);
        jsonResponse(true, $asistencia, 'Asistencia registrada correctamente', 201);
    } else {
        jsonResponse(false, null, 'Error al registrar asistencia', 500);
    }
}

/**
 * Actualizar asistencia - PUT /api/asistencias.php
 */
function updateAsistencia() {
    global $asistenciaModel;
    
    $input = getJsonInput();
    
    if (empty($input['Id_Asistencia'])) {
        jsonResponse(false, null, 'ID de asistencia es requerido', 400);
    }
    
    $id = $input['Id_Asistencia'];
    unset($input['Id_Asistencia']);
    
    $result = $asistenciaModel->update($id, $input);
    
    if ($result) {
        $asistencia = $asistenciaModel->getById($id);
        jsonResponse(true, $asistencia, 'Asistencia actualizada correctamente', 200);
    } else {
        jsonResponse(false, null, 'Error al actualizar asistencia', 500);
    }
}

/**
 * Eliminar asistencia - DELETE /api/asistencias.php
 */
function deleteAsistencia() {
    global $asistenciaModel;
    
    $input = getJsonInput();
    
    if (empty($input['id'])) {
        jsonResponse(false, null, 'ID de asistencia es requerido', 400);
    }
    
    $result = $asistenciaModel->delete($input['id']);
    
    if ($result) {
        jsonResponse(true, null, 'Asistencia eliminada correctamente', 200);
    } else {
        jsonResponse(false, null, 'Error al eliminar asistencia', 500);
    }
}
