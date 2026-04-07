<?php
/**
 * Archivo de rutas de la aplicación
 * Define todas las rutas disponibles en el formato 'url' => 'Controller@method'
 */

return [
    // Autenticación
    'auth/login' => 'AuthController@login',
    'auth/logout' => 'AuthController@logout',
    'auth/cambiar-cuenta' => 'AuthController@cambiarCuenta',
    'auth/cambiar-usuario' => 'AuthController@cambiarUsuario',
    'auth/siguiente-cuenta' => 'AuthController@siguienteCuenta',
    'auth/mi-cuenta' => 'AuthController@miCuenta',
    'auth/acceso-denegado' => 'AuthController@accesoDenegado',
    
    // Home
    'home' => 'HomeController@index',
    'home/lideres-celula' => 'HomeController@lideresCelula',
    
    // Personas
    'personas' => 'PersonaController@index',
    'personas/ganar' => 'PersonaController@ganar',
    'personas/escalera' => 'PersonaController@escalera',
    'personas/actualizarChecklistEscalera' => 'PersonaController@actualizarChecklistEscalera',
    'personas/plantillas-whatsapp' => 'PersonaController@plantillasWhatsapp',
    'personas/plantillas-whatsapp/programar' => 'PersonaController@programarPlantillaWhatsapp',
    'personas/crear' => 'PersonaController@crear',
    'personas/editar' => 'PersonaController@editar',
    'personas/detalle' => 'PersonaController@detalle',
    'personas/eliminar' => 'PersonaController@eliminar',
    'personas/exportarExcel' => 'PersonaController@exportarExcel',
    'personas/guardarPlantillasWhatsapp' => 'PersonaController@guardarPlantillasWhatsapp',
    
    // Células
    'celulas' => 'CelulaController@index',
    'celulas/crear' => 'CelulaController@crear',
    'celulas/editar' => 'CelulaController@editar',
    'celulas/detalle' => 'CelulaController@detalle',
    'celulas/eliminar' => 'CelulaController@eliminar',
    'celulas/materiales' => 'CelulaController@materiales',
    'celulas/materiales/ver' => 'CelulaController@verMaterial',
    'celulas/detalleVistasMaterial' => 'CelulaController@detalleVistasMaterial',
    'celulas/buscarLideres' => 'CelulaController@buscarLideres',
    'celulas/buscarLideres12' => 'CelulaController@buscarLideres12',
    'celulas/buscarPastores' => 'CelulaController@buscarPastores',
    'celulas/buscarAnfitriones' => 'CelulaController@buscarAnfitriones',
    'celulas/exportarExcel' => 'CelulaController@exportarExcel',

    // Teen
    'teen' => 'TeenController@index',
    'teen/verPdf' => 'TeenController@verPdf',
    'teen/editar' => 'TeenController@editar',
    'teen/eliminar' => 'TeenController@eliminar',
    'teen/detalleVistas' => 'TeenController@detalleVistas',
    
    // Ministerios
    'ministerios' => 'MinisterioController@index',
    'ministerios/crear' => 'MinisterioController@crear',
    'ministerios/editar' => 'MinisterioController@editar',
    'ministerios/actualizarMeta' => 'MinisterioController@actualizarMeta',
    'ministerios/eliminar' => 'MinisterioController@eliminar',
    'ministerios/exportarExcel' => 'MinisterioController@exportarExcel',
    
    // Roles
    'roles' => 'RolController@index',
    'roles/crear' => 'RolController@crear',
    'roles/editar' => 'RolController@editar',
    'roles/eliminar' => 'RolController@eliminar',
    'roles/exportarExcel' => 'RolController@exportarExcel',
    
    // Eventos
    
        // Eventos
    'eventos' => 'EventoController@index',
    'eventos/crear' => 'EventoController@crear',
    'eventos/editar' => 'EventoController@editar',
    'eventos/eliminar' => 'EventoController@eliminar',
    'eventos/exportarExcel' => 'EventoController@exportarExcel',
    'eventos/proximos' => 'EventoController@proximosPublico',
    'eventos/compartir' => 'EventoController@compartirPublico',
    'eventos/universidad-vida' => 'EventoController@universidadVida',
    'eventos/capacitacion-destino' => 'EventoController@capacitacionDestino',
    'eventos/modulo/guardar' => 'EventoController@guardarModuloContenido',
    'eventos/modulo/duplicar' => 'EventoController@duplicarModuloContenido',
    'eventos/modulo/eliminar' => 'EventoController@eliminarModuloContenido',
    'eventos/universidad-vida/publico' => 'EventoController@universidadVidaPublico',
    'eventos/capacitacion-destino/publico' => 'EventoController@capacitacionDestinoPublico',
    
    // Peticiones
    'peticiones' => 'PeticionController@index',
    'peticiones/crear' => 'PeticionController@crear',
    'peticiones/editar' => 'PeticionController@editar',
    'peticiones/eliminar' => 'PeticionController@eliminar',
    'peticiones/exportarExcel' => 'PeticionController@exportarExcel',
    
    // Peticiones Públicas (No requiere autenticación)
    'peticiones_publica' => 'PeticionController@formularioPublico',
    'peticiones_publica/guardar' => 'PeticionController@guardarPublico',
    
    // Asistencias
    'asistencias' => 'AsistenciaController@index',
    'asistencias/registrar' => 'AsistenciaController@registrar',
    'asistencias/porCelula' => 'AsistenciaController@porCelula',
    'asistencias/actualizarEntregoSobre' => 'AsistenciaController@actualizarEntregoSobre',
    'asistencias/exportarExcel' => 'AsistenciaController@exportarExcel',
    
    // Reportes
    'reportes' => 'ReporteController@index',
    'reportes/almasGanadas' => 'ReporteController@almasGanadas',
    'reportes/asistenciaCelulas' => 'ReporteController@asistenciaCelulas',
    'reportes/exportarExcel' => 'ReporteController@exportarExcel',
    
    // Permisos
    'permisos' => 'PermisosController@index',
    'permisos/actualizar' => 'PermisosController@actualizar',
    'permisos/exportarExcel' => 'PermisosController@exportarExcel',
    
    // Entrega de Obsequios (Requiere autenticación)
    'entrega_obsequio' => 'EntregaObsequioController@index',
    'entrega_obsequio/marcarEntregado' => 'EntregaObsequioController@marcarEntregado',
    'entrega_obsequio/exportarPDF' => 'EntregaObsequioController@exportarPDF',
    'entrega_obsequio/exportarExcel' => 'EntregaObsequioController@exportarExcel',
    
    // Registro de Obsequios (Público - No requiere autenticación)
    'registro_obsequio' => 'RegistroObsequioController@index',
    'registro_obsequio/guardar' => 'RegistroObsequioController@guardar',

    // Registro de Personas (Público - No requiere autenticación)
    'registro_personas' => 'RegistroPersonaController@index',
    'registro_personas/guardar' => 'RegistroPersonaController@guardar',
    
    // Stream ESP32-CAM (Público - No requiere autenticación)
    'stream/live' => 'StreamController@live',
    'stream/gallery' => 'StreamController@gallery',

    // Nehemias (Público - No requiere autenticación)
    'nehemias' => 'NehemiasController@index',
    'nehemias/formulario' => 'NehemiasController@formulario',
    'nehemias/guardar' => 'NehemiasController@guardar',
    'nehemias/testigos-electorales/formulario' => 'NehemiasController@formularioTestigosElectorales',
    'nehemias/testigos-electorales/guardar' => 'NehemiasController@guardarTestigoElectoral',

    // Nehemias (Privado - Administrador)
    'nehemias/lista' => 'NehemiasController@lista',
    'nehemias/reportes' => 'NehemiasController@reportes',
    'nehemias/exportarExcel' => 'NehemiasController@exportarExcel',
    'nehemias/editar' => 'NehemiasController@editar',
    'nehemias/actualizar' => 'NehemiasController@actualizar',
    'nehemias/eliminar' => 'NehemiasController@eliminar',
    'nehemias/importar' => 'NehemiasController@importar',
    'nehemias/importar-directo' => 'NehemiasController@importarDirecto',
    'nehemias/reparar-importacion' => 'NehemiasController@repararImportacion',
    'nehemias/testigos-electorales' => 'NehemiasController@testigosElectorales',
    'nehemias/seremos1200' => 'NehemiasController@seremos1200',
    'nehemias/seremos1200/importar' => 'NehemiasController@importarSeremos1200',
    'nehemias/seremos1200/exportarExcel' => 'NehemiasController@exportarExcelSeremos1200',
    'nehemias/seremos1200/decision' => 'NehemiasController@decisionSeremos1200',

    // Nehemias WhatsApp Campañas
    'nehemias/whatsapp-campanas' => 'WhatsappCampanaController@index',
    'nehemias/whatsapp-campanas/crear' => 'WhatsappCampanaController@crear',
    'nehemias/whatsapp-campanas/generar-cola' => 'WhatsappCampanaController@generarCola',
    'nehemias/whatsapp-campanas/reintentar-fallidos' => 'WhatsappCampanaController@reintentarFallidos',
    'nehemias/whatsapp-campanas/procesar-cola' => 'WhatsappCampanaController@procesarCola',
    'nehemias/whatsapp/webhook' => 'WhatsappCampanaController@webhook',
    
    // Transmisiones YouTube (Privadas - Requieren autenticación)
    'transmisiones' => 'TransmisionController@listar',
    'transmisiones/crear' => 'TransmisionController@crear',
    'transmisiones/guardar' => 'TransmisionController@guardar',
    'transmisiones/editar' => 'TransmisionController@editar',
    'transmisiones/actualizar' => 'TransmisionController@actualizar',
    'transmisiones/cambiarEstado' => 'TransmisionController@cambiarEstado',
    'transmisiones/eliminar' => 'TransmisionController@eliminar',
    'transmisiones/buscar' => 'TransmisionController@buscar',
    'transmisiones/obtenerEnVivo' => 'TransmisionController@obtenerEnVivo',
    'transmisiones/exportarExcel' => 'TransmisionController@exportarExcel',
    
    // Transmisiones YouTube (Público - No requiere autenticación)
    'transmisiones-publico' => 'TransmisionController@verPublico',
];