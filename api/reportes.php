<?php
/**
 * API Endpoint: Reportes
 */

require_once 'config.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Asistencia.php';
require_once APP . '/Models/Celula.php';

$method = getRequestMethod();

// Validar autenticación
$userId = validateToken();

if ($method !== 'GET') {
    jsonResponse(false, null, 'Método no permitido', 405);
}

// Determinar tipo de reporte
$tipo = $_GET['tipo'] ?? 'dashboard';

switch ($tipo) {
    case 'dashboard':
        getDashboard();
        break;
    case 'almas_ganadas':
        getAlmasGanadas();
        break;
    case 'asistencias_periodo':
        getAsistenciasPeriodo();
        break;
    case 'personas_ministerio':
        getPersonasPorMinisterio();
        break;
    case 'personas_lider':
        getPersonasPorLider();
        break;
    case 'celulas_stats':
        getCelulasStats();
        break;
    default:
        jsonResponse(false, null, 'Tipo de reporte no válido', 400);
}

/**
 * Dashboard general - GET /api/reportes.php?tipo=dashboard
 */
function getDashboard() {
    $personaModel = new Persona();
    
    // Total de personas
    $sql = "SELECT COUNT(*) as total FROM persona";
    $totalPersonas = $personaModel->query($sql)[0]['total'];
    
    // Personas por rol
    $sql = "SELECT r.Nombre_Rol, COUNT(p.Id_Persona) as cantidad
            FROM rol r
            LEFT JOIN persona p ON r.Id_Rol = p.Id_Rol
            GROUP BY r.Id_Rol, r.Nombre_Rol";
    $personasPorRol = $personaModel->query($sql);
    
    // Personas por ministerio
    $sql = "SELECT m.Nombre_Ministerio, COUNT(p.Id_Persona) as cantidad
            FROM ministerio m
            LEFT JOIN persona p ON m.Id_Ministerio = p.Id_Ministerio
            GROUP BY m.Id_Ministerio, m.Nombre_Ministerio";
    $personasPorMinisterio = $personaModel->query($sql);
    
    // Total células
    $sql = "SELECT COUNT(*) as total FROM celula";
    $totalCelulas = $personaModel->query($sql)[0]['total'];
    
    // Nuevos miembros (últimos 30 días)
    $sql = "SELECT COUNT(*) as total FROM persona 
            WHERE Fecha_Registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $nuevosMiembros = $personaModel->query($sql)[0]['total'];
    
    jsonResponse(true, [
        'total_personas' => (int)$totalPersonas,
        'total_celulas' => (int)$totalCelulas,
        'nuevos_miembros_mes' => (int)$nuevosMiembros,
        'personas_por_rol' => $personasPorRol,
        'personas_por_ministerio' => $personasPorMinisterio
    ], 'Dashboard obtenido correctamente', 200);
}

/**
 * Almas ganadas - GET /api/reportes.php?tipo=almas_ganadas&fecha_inicio=Y-m-d&fecha_fin=Y-m-d
 */
function getAlmasGanadas() {
    $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
    
    $personaModel = new Persona();
    
    $almasGanadas = $personaModel->getAlmasGanadasPorMinisterio($fechaInicio, $fechaFin);
    
    jsonResponse(true, $almasGanadas, 'Reporte de almas ganadas generado', 200);
}

/**
 * Asistencias por período - GET /api/reportes.php?tipo=asistencias_periodo&fecha_inicio=Y-m-d&fecha_fin=Y-m-d
 */
function getAsistenciasPeriodo() {
    $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
    
    $asistenciaModel = new Asistencia();
    
    $sql = "SELECT 
            DATE(a.Fecha_Asistencia) as fecha,
            COUNT(DISTINCT a.Id_Persona) as total_personas,
            COUNT(a.Id_Asistencia) as total_asistencias,
            e.Nombre_Evento,
            c.Nombre_Celula
            FROM asistencia a
            LEFT JOIN evento e ON a.Id_Evento = e.Id_Evento
            LEFT JOIN celula c ON a.Id_Celula = c.Id_Celula
            WHERE DATE(a.Fecha_Asistencia) BETWEEN ? AND ?
            GROUP BY DATE(a.Fecha_Asistencia), e.Nombre_Evento, c.Nombre_Celula
            ORDER BY a.Fecha_Asistencia DESC";
    
    $asistencias = $asistenciaModel->query($sql, [$fechaInicio, $fechaFin]);
    
    jsonResponse(true, $asistencias, 'Reporte de asistencias generado', 200);
}

/**
 * Personas por ministerio - GET /api/reportes.php?tipo=personas_ministerio
 */
function getPersonasPorMinisterio() {
    $personaModel = new Persona();
    
    $sql = "SELECT 
            m.Id_Ministerio,
            m.Nombre_Ministerio,
            COUNT(p.Id_Persona) as total_personas,
            COUNT(CASE WHEN p.Genero IN ('Hombre', 'Joven Hombre') THEN 1 END) as hombres,
            COUNT(CASE WHEN p.Genero IN ('Mujer', 'Joven Mujer') THEN 1 END) as mujeres
            FROM ministerio m
            LEFT JOIN persona p ON m.Id_Ministerio = p.Id_Ministerio
            GROUP BY m.Id_Ministerio, m.Nombre_Ministerio
            ORDER BY total_personas DESC";
    
    $resultado = $personaModel->query($sql);
    
    jsonResponse(true, $resultado, 'Reporte por ministerio generado', 200);
}

/**
 * Personas por líder - GET /api/reportes.php?tipo=personas_lider
 */
function getPersonasPorLider() {
    $personaModel = new Persona();
    
    $sql = "SELECT 
            l.Id_Persona as Id_Lider,
            CONCAT(l.Nombre, ' ', l.Apellido) as Nombre_Lider,
            COUNT(p.Id_Persona) as total_miembros,
            COUNT(CASE WHEN p.Genero IN ('Hombre', 'Joven Hombre') THEN 1 END) as hombres,
            COUNT(CASE WHEN p.Genero IN ('Mujer', 'Joven Mujer') THEN 1 END) as mujeres,
            m.Nombre_Ministerio
            FROM persona l
            LEFT JOIN persona p ON l.Id_Persona = p.Id_Lider
            LEFT JOIN ministerio m ON l.Id_Ministerio = m.Id_Ministerio
            WHERE l.Id_Rol IN (1, 2)
            GROUP BY l.Id_Persona, l.Nombre, l.Apellido, m.Nombre_Ministerio
            ORDER BY total_miembros DESC";
    
    $resultado = $personaModel->query($sql);
    
    jsonResponse(true, $resultado, 'Reporte por líder generado', 200);
}

/**
 * Estadísticas de células - GET /api/reportes.php?tipo=celulas_stats
 */
function getCelulasStats() {
    $celulaModel = new Celula();
    
    $sql = "SELECT 
            c.Id_Celula,
            c.Nombre_Celula,
            c.Direccion_Celula,
            c.Dia_Reunion,
            c.Hora_Reunion,
            CONCAT(l.Nombre, ' ', l.Apellido) as Nombre_Lider,
            COUNT(p.Id_Persona) as total_miembros,
            COUNT(CASE WHEN p.Genero IN ('Hombre', 'Joven Hombre') THEN 1 END) as hombres,
            COUNT(CASE WHEN p.Genero IN ('Mujer', 'Joven Mujer') THEN 1 END) as mujeres
            FROM celula c
            LEFT JOIN persona l ON c.Id_Lider = l.Id_Persona
            LEFT JOIN persona p ON c.Id_Celula = p.Id_Celula
            GROUP BY c.Id_Celula, c.Nombre_Celula, c.Direccion_Celula, 
                     c.Dia_Reunion, c.Hora_Reunion, l.Nombre, l.Apellido
            ORDER BY total_miembros DESC";
    
    $resultado = $celulaModel->query($sql);
    
    jsonResponse(true, $resultado, 'Estadísticas de células generadas', 200);
}
