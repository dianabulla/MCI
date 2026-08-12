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
    'auth/selector-contexto' => 'AuthController@selectorContexto',
    'auth/seleccionar-contexto' => 'AuthController@seleccionarContexto',
    'auth/acuerdo-confidencialidad' => 'AuthController@acuerdoConfidencialidad',
    'auth/aceptar-acuerdo-confidencialidad' => 'AuthController@aceptarAcuerdoConfidencialidad',
    'auth/acceso-denegado' => 'AuthController@accesoDenegado',
    
    // Home
    'home' => 'HomeController@index',
    'home/material' => 'HomeController@material',
    'home/material/celulas' => 'HomeController@materialCelulas',
    'home/material/teens' => 'HomeController@materialTeens',
    'home/material/universidad-vida' => 'HomeController@materialUniversidadVida',
    'home/material/capacitacion-destino' => 'HomeController@materialCapacitacionDestino',
    'home/material/ver' => 'HomeController@materialVerPdf',
    'home/material/detalle-vistas' => 'HomeController@materialDetalleVistas',
    'home/lideres-celula' => 'HomeController@lideresCelula',
    'home/consolidar' => 'HomeController@consolidar',
    'home/consolidar/asistencias' => 'HomeController@consolidarAsistencias',
    'home/consolidar/exportar' => 'HomeController@exportarConsolidar',
    'home/guardar-asistencia-clase' => 'HomeController@guardarAsistenciaClase',
    'home/calificar-tarea-entrega-cap' => 'HomeController@calificarTareaEntregaCapAjax',
    'home/exportar-planilla-cap-destino' => 'HomeController@exportarPlanillaCapDestino',
    'programas' => 'HomeController@programas',
    'programas/consolidar' => 'HomeController@programasConsolidar',
    'programas/asistencias' => 'HomeController@programasAsistencias',
    'programas/consolidar/asistencias' => 'HomeController@programasConsolidarAsistencias',
    'programas/exportar' => 'HomeController@programasExportar',
    'programas/consolidar/exportar' => 'HomeController@programasConsolidarExportar',
    'programas/evaluaciones' => 'DiscipularEvaluacionController@index',
    'programas/evaluaciones/presentar' => 'DiscipularEvaluacionController@presentar',
    'programas/tareas' => 'DiscipularEvaluacionController@tareas',

    'programas/ir-clase' => 'DiscipularEvaluacionController@irClase',
    'home/escuelas-formacion' => 'HomeController@escuelasFormacion',
    'home/escuelas-formacion/exportar' => 'HomeController@exportarEscuelasFormacion',
    'home/escuelas-formacion/actualizar-estado' => 'HomeController@actualizarEstadoEscuelaFormacion',
    'home/escuelas-formacion/actualizar-asistencia-clase' => 'HomeController@actualizarAsistenciaClaseEscuelaFormacion',
    'home/escuelas-formacion/actualizar-matriz-asistencia' => 'HomeController@actualizarAsistenciaMatrizEscuelaFormacion',
    'home/escuelas-formacion/actualizar-fecha-clase' => 'HomeController@actualizarFechaClaseEscuelaFormacion',
    'home/cambiar-segmento-inscripcion' => 'HomeController@cambiarSegmentoInscripcion',
    'home/eliminar-inscripcion-formacion' => 'HomeController@eliminarInscripcionFormacion',
    
    // Personas
    'personas' => 'PersonaController@index',
    'personas/ganar' => 'PersonaController@ganar',
    'personas/universidad-vida' => 'PersonaController@universidadVida',
    'personas/notificaciones' => 'PersonaController@notificaciones',
    'personas/escalera' => 'PersonaController@escalera',
    'personas/actualizarChecklistEscalera' => 'PersonaController@actualizarChecklistEscalera',
    'personas/asignarMinisterioGanar' => 'PersonaController@asignarMinisterioGanar',
    'personas/reasignarMinisterioGanar' => 'PersonaController@reasignarMinisterioGanar',
    'personas/plantillas-whatsapp' => 'PersonaController@plantillasWhatsapp',
    'personas/plantillas-whatsapp/programar' => 'PersonaController@programarPlantillaWhatsapp',
    'personas/whatsapp/bandeja' => 'PersonaController@bandejaWhatsapp',
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
    'teen/registro-menores' => 'TeenController@registroMenores',
    'teen/codigos' => 'TeenController@codigos',
    'teen/guardar-menor' => 'TeenController@guardarMenor',
    'teen/qr-registro' => 'TeenController@qrRegistroPublico',
    'teen/registro-publico' => 'TeenController@registroPublico',
    'teen/guardar-menor-publico' => 'TeenController@guardarMenorPublico',
    'teen/consulta-codigo' => 'TeenController@consultarCodigoPublico',
    'teen/buscar-menor-publico-telefono' => 'TeenController@buscarMenorPublicoPorTelefono',
    'teen/buscar-menor-publico-documento' => 'TeenController@buscarMenorPublicoPorDocumento',
    'teen/buscarAcudientes' => 'TeenController@buscarAcudientes',
    'teen/verPdf' => 'TeenController@verPdf',
    'teen/recuperar-archivos' => 'TeenController@recuperarArchivos',
    'teen/subir-mes' => 'TeenController@subirMes',
    'teen/guardar-tema-mes' => 'TeenController@guardarTemaMes',
    'teen/asignar-profesor' => 'TeenController@asignarProfesorSemana',
    'teen/editar' => 'TeenController@editar',
    'teen/eliminar' => 'TeenController@eliminar',
    'teen/detalleVistas' => 'TeenController@detalleVistas',
    
    // Ministerios (Dentro de Discipular)
    'discipular/ministerios' => 'MinisterioController@index',
    'discipular/ministerios/crear' => 'MinisterioController@crear',
    'discipular/ministerios/editar' => 'MinisterioController@editar',
    'discipular/ministerios/guardar-metas' => 'MinisterioController@guardarMetas',
    'discipular/ministerios/actualizarMeta' => 'MinisterioController@actualizarMeta',
    'discipular/ministerios/actualizar-lideres-principales' => 'MinisterioController@actualizarLideresPrincipales',
    'discipular/ministerios/lideres' => 'MinisterioController@lideres',
    'discipular/ministerios/equipo-principal' => 'MinisterioController@equipoPrincipal',
    'discipular/ministerios/personas-asignables' => 'MinisterioController@personasAsignablesJson',
    'discipular/ministerios/equipo-12' => 'MinisterioController@equipo12',
    'discipular/ministerios/lideres-celula' => 'MinisterioController@lideresCelula',
    'discipular/ministerios/validar-cupo-lider' => 'MinisterioController@validarCupoLider',
    'discipular/ministerios/asignar-cupo' => 'MinisterioController@asignarCupo',
    'discipular/ministerios/liberar-cupo' => 'MinisterioController@liberarCupo',
    'discipular/ministerios/reasignar-cupo' => 'MinisterioController@reasignarCupo',
    'discipular/ministerios/eliminar' => 'MinisterioController@eliminar',
    'discipular/ministerios/exportarExcel' => 'MinisterioController@exportarExcel',

    // Asistente / chatbot
    'chatbot/consultar' => 'ChatbotController@consultar',
    'chatbot/sugerencias' => 'ChatbotController@sugerencias',

    // Cuentas
    'cuentas' => 'CuentaController@index',
    'cuentas/crear' => 'CuentaController@crear',
    'cuentas/editar' => 'CuentaController@editar',
    'cuentas/asignar-segundo-rol' => 'CuentaController@asignarSegundoRol',
    
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
    'eventos/otros' => 'EventoController@otros',
    'eventos/modulo/guardar' => 'EventoController@guardarModuloContenido',
    'eventos/modulo/guardar-masivo' => 'EventoController@guardarModuloContenidoMasivo',
    'eventos/modulo/duplicar' => 'EventoController@duplicarModuloContenido',
    'eventos/modulo/eliminar' => 'EventoController@eliminarModuloContenido',
    'eventos/universidad-vida/publico' => 'EventoController@universidadVidaPublico',
    'eventos/capacitacion-destino/publico' => 'EventoController@capacitacionDestinoPublico',
    'eventos/otros/publico' => 'EventoController@otrosPublico',
    
    // Peticiones
    'peticiones' => 'PeticionController@index',
    'peticiones/crear' => 'PeticionController@crear',
    'peticiones/editar' => 'PeticionController@editar',
    'peticiones/eliminar' => 'PeticionController@eliminar',
    'peticiones/exportarExcel' => 'PeticionController@exportarExcel',
    
    // Peticiones Públicas (No requiere autenticación)
    'peticiones_publica' => 'PeticionController@formularioPublico',
    'peticiones_publica/guardar' => 'PeticionController@guardarPublico',

    // Talleres (formularios dinámicos)
    'talleres' => 'TallerController@index',
    'talleres/crear' => 'TallerController@crear',
    'talleres/editar' => 'TallerController@editar',
    'talleres/guardar' => 'TallerController@guardar',
    'talleres/eliminar' => 'TallerController@eliminar',
    'talleres/respuestas' => 'TallerController@respuestas',
    'talleres/exportar' => 'TallerController@exportar',
    'talleres/qr' => 'TallerController@qr',
    'talleres/crear-presentacion-ninos' => 'TallerController@crearPresentacionNinos',
    'talleres/crear-tour-levantate' => 'TallerController@crearTourLevantate',
    'talleres/corregir-personas-tour' => 'TallerController@corregirPersonasTour',
    'talleres/pago' => 'TallerController@pagoRespuesta',
    'talleres/guardar-pago' => 'TallerController@guardarPago',
    'talleres/ticket-pago' => 'TallerController@ticketPago',

    // Talleres — Servicio Social (agendamiento)
    'talleres/servicio-social' => 'TallerServicioSocialController@index',
    'talleres/servicio-social/ver' => 'TallerServicioSocialController@ver',
    'talleres/servicio-social/actualizar' => 'TallerServicioSocialController@actualizar',
    'talleres/servicio-social/horarios' => 'TallerServicioSocialController@horariosSabado',
    'talleres/servicio-social/horarios/guardar' => 'TallerServicioSocialController@guardarHorariosSabado',
    'talleres/servicio-social/guardar-historia' => 'TallerServicioSocialController@guardarHistoriaClinica',
    'talleres/servicio-social/exportar' => 'TallerServicioSocialController@exportar',

    // Talleres — formulario público (sin autenticación)
    'talleres_publico' => 'TallerController@formularioPublico',
    'talleres_publico/guardar' => 'TallerController@guardarPublico',
    'talleres_publico/buscar-persona' => 'TallerController@buscarPersonaPublico',
    'talleres_publico/qr' => 'TallerController@qrPublico',
    'talleres_publico/servicio-social' => 'TallerServicioSocialController@formularioPublico',
    'talleres_publico/servicio-social/guardar' => 'TallerServicioSocialController@guardarPublico',
    'talleres_publico/servicio-social/buscar-persona' => 'TallerServicioSocialController@buscarPersonaPublico',
    'talleres_publico/servicio-social/disponibilidad' => 'TallerServicioSocialController@disponibilidadPublico',
    
    // Asistencias
    'asistencias' => 'AsistenciaController@index',
    'asistencias/registrar' => 'AsistenciaController@registrar',
    'asistencias/miembros-celula' => 'AsistenciaController@miembrosCelula',
    'asistencias/porCelula' => 'AsistenciaController@porCelula',
    'asistencias/actualizarEntregoSobre' => 'AsistenciaController@actualizarEntregoSobre',
    'asistencias/marcarNoDisponible' => 'AsistenciaController@marcarNoDisponible',
    'asistencias/exportarExcel' => 'AsistenciaController@exportarExcel',
    
    // Reportes
    'reportes' => 'ReporteController@index',
    'reportes/ministerial' => 'ReporteController@ministerial',
    'reportes/dashboard-ganar' => 'ReporteController@dashboardGanar',
    'reportes/dashboard-escuelas-uv' => 'ReporteController@dashboardEscuelasUniversidadVida',
    'reportes/dashboard-escuelas-uv-detalle' => 'ReporteController@dashboardEscuelasUvDetalleMinisterio',
    'reportes/dashboard-escuelas-capacitacion' => 'ReporteController@dashboardEscuelasCapacitacionDestino',
    'reportes/almasGanadas' => 'ReporteController@almasGanadas',
    'reportes/asistenciaCelulas' => 'ReporteController@asistenciaCelulas',
    'reportes/exportarExcel' => 'ReporteController@exportarExcel',
    
    // Permisos
    'permisos' => 'PermisosController@index',
    'permisos/actualizar' => 'PermisosController@actualizar',
    'permisos/limpiar-obsoletos' => 'PermisosController@limpiarObsoletos',
    'permisos/exportarExcel' => 'PermisosController@exportarExcel',

    // Herramientas (admin)
    'herramientas/diagnostico-documento' => 'DiagnosticoDocumentoController@index',
    'herramientas/diagnostico-documento/exportar' => 'DiagnosticoDocumentoController@exportar',
    'herramientas/diagnostico-permisos-persona' => 'DiagnosticoPermisosPersonaController@index',
    'herramientas/diagnostico-reporte-celulas' => 'DiagnosticoReporteCelulasController@index',
    
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

    // Escuelas de Formación (Público - No requiere autenticación)
    'escuelas_formacion/registro-publico/universidad-vida' => 'EscuelaFormacionRegistroController@registroPublicoUniversidadVida',
    'escuelas_formacion/registro-publico/capacitacion-destino' => 'EscuelaFormacionRegistroController@registroPublicoCapacitacionDestino',
    'escuelas_formacion/codigos' => 'EscuelaFormacionRegistroController@codigos',
    'escuelas_formacion/registro-publico/buscar-persona' => 'EscuelaFormacionRegistroController@buscarPersona',
    'escuelas_formacion/registro-publico/buscar-lideres' => 'EscuelaFormacionRegistroController@buscarLideres',
    'escuelas_formacion/registro-publico/validar-abono' => 'EscuelaFormacionRegistroController@validarAccesoAbono',
    'escuelas_formacion/registro-publico/guardar' => 'EscuelaFormacionRegistroController@guardar',
    'escuelas_formacion/registro-publico/subir-documentos' => 'EscuelaFormacionRegistroController@subirDocumentosPublico',
    'escuelas_formacion/registro-publico/ticket' => 'EscuelaFormacionRegistroController@ticket',
    'escuelas_formacion/pagos' => 'EscuelaFormacionRegistroController@pagos',
    'escuelas_formacion/pagos/consolidar' => 'EscuelaFormacionRegistroController@pagosConsolidar',
    'escuelas_formacion/pagos/enviar' => 'EscuelaFormacionRegistroController@pagosEnviar',
    'escuelas_formacion/abonos/universidad-vida' => 'EscuelaFormacionRegistroController@abonosUniversidadVida',
    'escuelas_formacion/abonos/universidad-vida/guardar' => 'EscuelaFormacionRegistroController@guardarAbonosUniversidadVida',
    'escuelas_formacion/abonos/capacitacion-destino' => 'EscuelaFormacionRegistroController@abonosCapacitacionDestino',
    'escuelas_formacion/abonos/capacitacion-destino/guardar' => 'EscuelaFormacionRegistroController@guardarAbonosUniversidadVida',
    'escuelas_formacion/inscritos' => 'EscuelaFormacionRegistroController@listadoInscritos',
    'escuelas_formacion/inscritos/guardar-asistencia' => 'EscuelaFormacionRegistroController@guardarAsistenciaClase',
    'escuelas_formacion/inscritos/eliminar' => 'EscuelaFormacionRegistroController@eliminarInscripcionListado',
    'escuelas_formacion/inscritos/abono-admin' => 'EscuelaFormacionRegistroController@abonoAdminPreauth',
    'escuelas_formacion/asistencia-publica' => 'EscuelaFormacionRegistroController@asistenciaPublica',
    'escuelas_formacion/asistencia-publica/buscar' => 'EscuelaFormacionRegistroController@buscarAsistenciaPublica',
    'escuelas_formacion/asistencia-publica/guardar' => 'EscuelaFormacionRegistroController@guardarAsistenciaPublica',
    
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
    'discipular/migrar-consolidados' => 'EscuelaFormacionRegistroController@migrarConsolidadosADiscipular',
];