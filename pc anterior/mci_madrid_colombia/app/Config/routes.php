<?php
/**
 * Archivo de rutas de la aplicación
 * Define todas las rutas disponibles en el formato 'url' => 'Controller@method'
 */

return [
    // Autenticación
    'auth/login' => 'AuthController@login',
    'auth/logout' => 'AuthController@logout',
    'auth/acceso-denegado' => 'AuthController@accesoDenegado',
    
    // Home
    'home' => 'HomeController@index',
    
    // Personas
    'personas' => 'PersonaController@index',
    'personas/crear' => 'PersonaController@crear',
    'personas/editar' => 'PersonaController@editar',
    'personas/detalle' => 'PersonaController@detalle',
    'personas/eliminar' => 'PersonaController@eliminar',
    
    // Células
    'celulas' => 'CelulaController@index',
    'celulas/crear' => 'CelulaController@crear',
    'celulas/editar' => 'CelulaController@editar',
    'celulas/detalle' => 'CelulaController@detalle',
    'celulas/eliminar' => 'CelulaController@eliminar',
    
    // Ministerios
    'ministerios' => 'MinisterioController@index',
    'ministerios/crear' => 'MinisterioController@crear',
    'ministerios/editar' => 'MinisterioController@editar',
    'ministerios/eliminar' => 'MinisterioController@eliminar',
    
    // Roles
    'roles' => 'RolController@index',
    'roles/crear' => 'RolController@crear',
    'roles/editar' => 'RolController@editar',
    'roles/eliminar' => 'RolController@eliminar',
    
    // Eventos
    'eventos' => 'EventoController@index',
    'eventos/crear' => 'EventoController@crear',
    'eventos/editar' => 'EventoController@editar',
    'eventos/eliminar' => 'EventoController@eliminar',
    
    // Peticiones
    'peticiones' => 'PeticionController@index',
    'peticiones/crear' => 'PeticionController@crear',
    'peticiones/editar' => 'PeticionController@editar',
    'peticiones/eliminar' => 'PeticionController@eliminar',
    
    // Asistencias
    'asistencias' => 'AsistenciaController@index',
    'asistencias/registrar' => 'AsistenciaController@registrar',
    'asistencias/porCelula' => 'AsistenciaController@porCelula',
    
    // Reportes
    'reportes' => 'ReporteController@index',
    'reportes/almasGanadas' => 'ReporteController@almasGanadas',
    'reportes/asistenciaCelulas' => 'ReporteController@asistenciaCelulas',
    
    // Permisos
    'permisos' => 'PermisosController@index',
    'permisos/actualizar' => 'PermisosController@actualizar',
    
    // Entrega de Obsequios (Requiere autenticación)
    'entrega_obsequio' => 'EntregaObsequioController@index',
    'entrega_obsequio/marcarEntregado' => 'EntregaObsequioController@marcarEntregado',
    'entrega_obsequio/exportarPDF' => 'EntregaObsequioController@exportarPDF',
    'entrega_obsequio/exportarExcel' => 'EntregaObsequioController@exportarExcel',
    
    // Registro de Obsequios (Público - No requiere autenticación)
    'registro_obsequio' => 'RegistroObsequioController@index',
    'registro_obsequio/guardar' => 'RegistroObsequioController@guardar',
    
    // Stream ESP32-CAM (Público - No requiere autenticación)
    'stream/live' => 'StreamController@live',
    'stream/gallery' => 'StreamController@gallery',
];
