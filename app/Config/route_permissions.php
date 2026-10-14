<?php

/**

 * Permisos requeridos por ruta (url sin query).

 * Clave: ruta exacta o prefijo con /* al final.

 * Valor: clave modulo:accion, array con policy especial, o ['allow' => true].

 *

 * Rutas no listadas: si el rol tiene matriz configurada, se deniegan; si no, solo autenticación (legacy).

 */



return [

    'home' => ['checker' => 'puedeAccederHome'],

    // --- Células (piloto Fase 1) ---

    'celulas' => 'celulas:ver',

    'celulas/crear' => 'celulas:crear',

    'celulas/editar' => 'celulas:editar',

    'celulas/detalle' => 'celulas:ver',

    'celulas/eliminar' => 'celulas:eliminar',

    'celulas/materiales' => ['checker' => 'puedeVerMaterialCelulas'],

    'celulas/materiales/ver' => ['checker' => 'puedeVerMaterialCelulas'],

    'celulas/detalleVistasMaterial' => ['checker' => 'puedeVerMaterialCelulas'],

    'celulas/buscarLideres' => 'celulas:ver',

    'celulas/buscarLideres12' => 'celulas:ver',

    'celulas/buscarPastores' => 'celulas:ver',

    'celulas/buscarAnfitriones' => 'celulas:ver',

    'celulas/exportarExcel' => 'celulas:exportar_datos',



    // --- Personas ---

    'personas' => ['checker' => 'puedeVerPersonasConsulta'],

    'personas/ganar' => ['checker' => 'puedeVerModuloPersonasGanar'],

    'personas/notasSeguimiento' => ['checker' => 'puedeUsarSeguimientoPersonaListado'],

    'personas/guardarNotaSeguimiento' => ['checker' => 'puedeGuardarSeguimientoPersonaListado'],

    'personas/plantillas-whatsapp' => 'personas_plantillas_whatsapp:ver',

    'personas/plantillas-whatsapp/programar' => 'personas_plantillas_whatsapp:ver',

    'personas/guardarPlantillasWhatsapp' => 'personas_plantillas_whatsapp:editar',

    'personas/whatsapp/bandeja' => 'personas_plantillas_whatsapp:ver',

    'personas/eliminar' => ['checker' => 'puedeEliminarPersonaDesdeDiscipular'],

    'personas/exportarExcel' => ['any' => ['personas:exportar_excel', 'personas:editar']],

    'personas/crear' => ['any' => ['personas:crear', 'acceso_rapido_nuevo_discipulo:crear']],

    'personas/sincronizar-edades' => ['admin_only' => true],

    'personas/editar' => ['checker' => 'puedeEditarPersonaDesdeDiscipular'],

    'personas/*' => [

        'modulo' => 'personas',

        'inferir_accion' => true,

        'checker_ver' => 'puedeVerPersonasConsulta',

    ],



    // --- Programas y formación (HomeController) ---

    'programas' => ['checker' => 'puedeAccederModuloProgramas'],

    'programas/exportar' => 'programas:exportar_consolidado',

    'programas/consolidar/exportar' => 'programas:exportar_consolidado',

    'programas/consolidar/asistencias' => ['checker' => 'puedeVerAsistenciasProgramas'],

    'programas/evaluaciones' => [
        'permiso' => 'discipular_evaluaciones:ver',
        'layout_discipulo' => true,
        'layout_maestro' => true,
    ],

    'programas/evaluaciones/presentar' => [
        'permiso' => 'discipular_evaluaciones:ver',
        'layout_discipulo' => true,
        'layout_maestro' => true,
    ],

    'programas/tareas' => ['layout_discipulo' => true, 'permiso' => 'discipular_evaluaciones:ver'],

    'programas/ir-clase' => ['layout_discipulo' => true, 'permiso' => 'discipular_evaluaciones:ver'],

    'programas/*' => [

        'checker' => 'puedeAccederModuloProgramas',

        'modulo' => 'programas',

        'inferir_accion' => true,

    ],

    'home/consolidar' => ['checker' => 'puedeAccederModuloProgramas'],

    'home/consolidar/*' => ['checker' => 'puedeAccederModuloProgramas'],

    'home/escuelas-formacion' => ['checker' => 'puedeAccederEscuelasFormacion'],

    'home/escuelas-formacion/*' => ['checker' => 'puedeAccederEscuelasFormacion'],

    'home/cambiar-segmento-inscripcion' => ['checker' => 'puedeVerListadoAsistenciasUniversidadVida'],

    'home/eliminar-inscripcion-formacion' => ['any' => ['personas:eliminar', 'escuelas_formacion:eliminar']],

    'home/guardar-asistencia-clase' => 'escuelas_formacion_marcar_asistencia:editar',
    'home/calificar-tarea-entrega-cap' => [
        'any' => [
            'material_capacitacion_destino:editar',
            'material_capacitacion_destino:crear',
        ],
    ],
    'home/exportar-planilla-cap-destino' => [
        'any' => [
            'material_capacitacion_destino:editar',
            'material_capacitacion_destino:crear',
        ],
    ],

    'home/lideres-celula' => 'celulas:ver',



    // --- Material ---

    'home/material' => ['checker' => 'puedeVerCentroMaterial'],

    'home/material/celulas' => ['checker' => 'puedeVerMaterialCelulas'],

    'home/material/teens' => ['checker' => 'puedeVerMaterialTeens'],

    'home/material/universidad-vida' => ['checker' => 'puedeVerMaterialUniversidadVida'],

    'home/material/capacitacion-destino' => [

        'checker' => 'puedeVerMaterialCapacitacionDestino',

        'layout_maestro' => true,

    ],

    'home/material/ver' => ['checker' => 'puedeVerCentroMaterial'],

    'home/material/detalle-vistas' => ['checker' => 'puedeVerCentroMaterial'],



    // --- Asistencias ---

    'asistencias' => 'asistencias:ver',

    'asistencias/actualizarEntregoSobre' => ['checker' => 'puedeMarcarEntregoSobre'],

    'asistencias/marcarNoDisponible' => ['any' => ['asistencias:crear', 'asistencias:editar']],
    'asistencias/quitarDeCelula' => ['any' => ['asistencias:crear', 'asistencias:editar']],
    'asistencias/migrarCelula' => ['any' => ['asistencias:crear', 'asistencias:editar']],

    'asistencias/*' => ['modulo' => 'asistencias', 'inferir_accion' => true],



    // --- Discipular / ministerios ---

    'discipular' => 'ministerios:ver',

    'discipular/ministerios' => 'ministerios:ver',

    'discipular/ministerios/eliminar' => 'ministerios:eliminar',

    'discipular/ministerios/personas-asignables' => 'ministerios:ver',

    'discipular/ministerios/*' => ['modulo' => 'ministerios', 'inferir_accion' => true],

    'discipular/migrar-consolidados' => ['admin_only' => true],

    'home/discipular' => 'ministerios:ver',

    'home/discipular/evaluaciones' => [
        'permiso' => 'discipular_evaluaciones:ver',
        'layout_discipulo' => true,
        'layout_maestro' => true,
    ],

    'home/discipular/tareas' => ['layout_discipulo' => true, 'permiso' => 'discipular_evaluaciones:ver'],

    'home/discipular/*' => 'ministerios:ver',

    'ministerios' => 'ministerios:ver',

    'ministerios/eliminar' => 'ministerios:eliminar',

    'ministerios/personas-asignables' => 'ministerios:ver',

    'ministerios/*' => ['modulo' => 'ministerios', 'inferir_accion' => true],



    // --- Teen (privado; formularios públicos sin permiso de módulo) ---

    'teen' => ['checker' => 'puedeVerMaterialTeens'],

    'teen/registro-menores' => 'teen:ver',

    'teen/codigos' => 'teen:ver',

    'teen/guardar-menor' => 'teen:crear',

    'teen/buscarAcudientes' => 'teen:ver',

    'teen/verPdf' => ['checker' => 'puedeVerMaterialTeens'],

    'teen/recuperar-archivos' => 'teen:editar',

    'teen/subir-mes' => 'teen:crear',

    'teen/guardar-tema-mes' => 'teen:editar',

    'teen/editar' => 'teen:editar',

    'teen/asignar-profesor' => 'teen:editar',

    'teen/eliminar' => 'teen:eliminar',

    'teen/detalleVistas' => ['checker' => 'puedeVerMaterialTeens'],

    'teen/qr-registro' => 'teen:ver',

    'teen/registro-publico' => ['allow' => true],

    'teen/guardar-menor-publico' => ['allow' => true],

    'teen/consulta-codigo' => ['allow' => true],

    'teen/buscar-menor-publico-telefono' => ['allow' => true],

    'teen/buscar-menor-publico-documento' => ['allow' => true],



    // --- Nehemias (panel privado) ---

    'nehemias/lista' => 'nehemias:ver',

    'nehemias/reportes' => 'nehemias:ver',

    'nehemias/exportarExcel' => 'nehemias:ver',

    'nehemias/editar' => 'nehemias:editar',

    'nehemias/actualizar' => 'nehemias:editar',

    'nehemias/eliminar' => 'nehemias:eliminar',

    'nehemias/importar' => 'nehemias:crear',

    'nehemias/importar-directo' => 'nehemias:crear',

    'nehemias/reparar-importacion' => 'nehemias:editar',

    'nehemias/testigos-electorales' => 'nehemias:ver',

    'nehemias/seremos1200' => 'nehemias:ver',

    'nehemias/seremos1200/*' => ['modulo' => 'nehemias', 'inferir_accion' => true],

    'nehemias/whatsapp-campanas' => 'nehemias:ver',

    'nehemias/whatsapp-campanas/*' => ['modulo' => 'nehemias', 'inferir_accion' => true],



    // --- Asistente (chatbot) ---

    'chatbot/consultar' => ['checker' => 'puedeUsarChatbotAsistente'],

    'chatbot/sugerencias' => ['checker' => 'puedeUsarChatbotAsistente'],



    // --- Reportes ---

    'reportes' => 'reportes:ver',

    'reportes/dashboard-ganar' => ['checker' => 'puedeVerDashboardGanar'],

    'reportes/dashboard-ganar-redes' => ['checker' => 'puedeVerDashboardGanar'],

    'reportes/dashboard-ganar/exportar-celulas-lider' => ['checker' => 'puedeVerDashboardGanar'],

    'reportes/dashboard-escuelas-uv' => ['checker' => 'puedeVerDashboardProgramaUv'],

    'reportes/dashboard-escuelas-uv-detalle' => ['checker' => 'puedeVerDashboardProgramaUv'],

    'reportes/dashboard-escuelas-uv-detalle-avance' => ['checker' => 'puedeVerDashboardProgramaUv'],

    'reportes/dashboard-escuelas-capacitacion' => ['checker' => 'puedeVerDashboardProgramaCap'],

    'reportes/*' => 'reportes:ver',



    // --- Peticiones (panel) ---

    'peticiones' => 'peticiones:ver',

    'peticiones/eliminar' => 'peticiones:eliminar',

    'peticiones/*' => ['modulo' => 'peticiones', 'inferir_accion' => true],



    // --- Talleres (formularios dinámicos) ---

    'talleres' => ['checker' => 'puedeAccederModuloTalleres'],

    'talleres/crear' => 'talleres:crear',

    'talleres/guardar' => ['any' => ['talleres:crear', 'talleres:editar']],

    'talleres/editar' => 'talleres:editar',

    'talleres/eliminar' => 'talleres:eliminar',

    'talleres/respuestas' => ['checker' => 'puedeAccederRespuestasTalleres'],

    'talleres/exportar' => ['any' => ['talleres:exportar_excel', 'talleres:ver_respuestas', 'talleres:ver']],

    'talleres/qr' => ['any' => ['talleres:gestionar_enlace', 'talleres:editar', 'talleres:ver']],

    'talleres/servicio-social' => ['checker' => 'puedeVerServicioSocial'],

    'talleres/servicio-social/ver' => ['checker' => 'puedeVerServicioSocial'],

    'talleres/servicio-social/actualizar' => ['checker' => 'puedeGestionarServicioSocial'],
    'talleres/servicio-social/agendar-nueva' => ['checker' => 'puedeGestionarServicioSocial'],

    'talleres/servicio-social/eliminar' => ['checker' => 'puedeGestionarServicioSocial'],

    'talleres/servicio-social/horarios' => ['checker' => 'puedeGestionarServicioSocial'],

    'talleres/servicio-social/horarios/guardar' => ['checker' => 'puedeGestionarServicioSocial'],

    'talleres/servicio-social/inscripciones' => ['checker' => 'puedeGestionarServicioSocial'],

    'talleres/servicio-social/inscripciones/guardar' => ['checker' => 'puedeGestionarServicioSocial'],

    'talleres/servicio-social/guardar-historia' => ['checker' => 'puedeGestionarServicioSocial'],

    'talleres/servicio-social/exportar' => ['any' => ['talleres:exportar_excel', 'talleres:ver_respuestas', 'talleres:ver']],

    'talleres/*' => ['modulo' => 'talleres', 'inferir_accion' => true],

    'comunidad/pagos' => ['checker' => 'puedeAccederPagosComunidad'],
    'comunidad/pagos/*' => ['checker' => 'puedeAccederPagosComunidad'],



    // --- Eventos (panel; rutas /publico sin sesión no pasan por aquí) ---

    'eventos' => 'eventos:ver',

    'eventos/crear' => 'eventos:crear',

    'eventos/editar' => 'eventos:editar',

    'eventos/eliminar' => 'eventos:eliminar',

    'eventos/exportarExcel' => 'eventos:ver',

    'eventos/universidad-vida' => 'eventos:ver',

    'eventos/capacitacion-destino' => 'eventos:ver',

    'eventos/otros' => 'eventos:ver',

    'eventos/modulo/guardar' => ['any' => ['eventos:gestionar_contenido_publico', 'eventos:editar']],

    'eventos/modulo/guardar-masivo' => ['any' => ['eventos:gestionar_contenido_publico', 'eventos:editar']],

    'eventos/modulo/eliminar' => ['any' => ['eventos:gestionar_contenido_publico', 'eventos:eliminar']],

    'eventos/modulo/*' => ['any' => ['eventos:gestionar_contenido_publico', 'eventos:editar', 'eventos:ver']],



    // --- Transmisiones ---

    'transmisiones' => 'transmisiones:ver',

    'transmisiones/eliminar' => 'transmisiones:eliminar',

    'transmisiones/guardar' => ['any' => ['transmisiones:crear', 'transmisiones:editar']],

    'transmisiones/*' => ['modulo' => 'transmisiones', 'inferir_accion' => true],



    // --- Entrega obsequio ---

    'entrega_obsequio' => 'entrega_obsequio:ver',

    'entrega_obsequio/*' => ['modulo' => 'entrega_obsequio', 'inferir_accion' => true],



    // --- Escuelas formación (privado autenticado) ---

    'escuelas_formacion/pagos/consolidar' => ['checker' => 'puedeGestionarPagosProgramaUv'],

    'escuelas_formacion/pagos/enviar' => ['checker' => 'puedeGestionarPagosProgramaCap'],

    'escuelas_formacion/pagos/eliminar' => ['any' => ['programas:gestionar_pagos_universidad_vida', 'programas:gestionar_pagos_capacitacion_destino', 'escuelas_formacion:gestionar_pagos']],
    'escuelas_formacion/pagos/editar' => ['any' => ['programas:gestionar_pagos_universidad_vida', 'programas:gestionar_pagos_capacitacion_destino', 'escuelas_formacion:gestionar_pagos']],

    'escuelas_formacion/pagos' => ['any' => ['programas:gestionar_pagos_universidad_vida', 'programas:gestionar_pagos_capacitacion_destino', 'escuelas_formacion:gestionar_pagos', 'programas:ver']],

    'escuelas_formacion/pagos/*' => ['any' => ['programas:gestionar_pagos_universidad_vida', 'programas:gestionar_pagos_capacitacion_destino', 'escuelas_formacion:gestionar_pagos', 'programas:ver']],

    'escuelas_formacion/inscritos' => ['checker' => 'puedeVerListadoAsistenciasUniversidadVida'],

    'escuelas_formacion/inscritos/eliminar' => ['any' => ['escuelas_formacion:eliminar', 'personas:eliminar']],

    'escuelas_formacion/inscritos/guardar-asistencia' => ['checker' => 'puedeMarcarAsistenciaListadoUniversidadVida'],

    'escuelas_formacion/inscritos/*' => ['checker' => 'puedeVerListadoAsistenciasUniversidadVida'],

    'escuelas_formacion/abonos/*' => ['checker' => 'puedeAccederEscuelasFormacion'],

    'escuelas_formacion/codigos' => ['checker' => 'puedeAccederEscuelasFormacion'],
    'escuelas_formacion/asistencia-publica' => ['allow' => true],
    'escuelas_formacion/asistencia-publica/qr' => ['allow' => true],
    'escuelas_formacion/asistencia-publica/buscar' => ['allow' => true],
    'escuelas_formacion/asistencia-publica/guardar' => ['allow' => true],



    // --- Administración (Fase B: permisos/cuentas delegables) ---

    'cuentas' => 'cuentas:ver',
    'auditoria' => 'cuentas:ver',

    'cuentas/crear' => 'cuentas:crear',

    'cuentas/editar' => 'cuentas:editar',

    'cuentas/asignar-segundo-rol' => 'cuentas:editar',
    'cuentas/migrar-legacy' => 'cuentas:editar',

    'cuentas/*' => ['modulo' => 'cuentas', 'inferir_accion' => true],

    'permisos' => ['any' => ['permisos:ver', 'permisos:editar']],

    'permisos/actualizar' => 'permisos:editar',

    'permisos/limpiar-obsoletos' => 'permisos:editar',

    'permisos/exportarExcel' => ['any' => ['permisos:ver', 'permisos:editar']],

    'permisos/*' => ['any' => ['permisos:ver', 'permisos:editar']],

    'roles' => 'roles:ver',

    'roles/eliminar' => 'roles:eliminar',

    'roles/*' => ['modulo' => 'roles', 'inferir_accion' => true],

    'herramientas/diagnostico-documento' => ['admin_only' => true],

    'herramientas/diagnostico-documento/exportar' => ['admin_only' => true],

    'herramientas/diagnostico-permisos-persona' => ['admin_only' => true],

    'herramientas/corregir-rol-celulas' => ['admin_only' => true],

    'herramientas/corregir-rol-celulas/aplicar' => ['admin_only' => true],

    // --- Acciones que modifican datos y cuyo nombre lleva guion o mayúsculas ---
    // RouteGuard solo deduce crear/editar/eliminar cuando la palabra va sola entre barras;
    // estas rutas se habrían tratado como "ver". Cada regla replica lo que ya exige su controlador.

    'discipular/ministerios/asignar-cupo' => ['checker' => 'puedeEditarPersonaDesdeDiscipular'],
    'discipular/ministerios/liberar-cupo' => ['checker' => 'puedeEditarPersonaDesdeDiscipular'],
    'discipular/ministerios/reasignar-cupo' => ['checker' => 'puedeEditarPersonaDesdeDiscipular'],
    'ministerios/asignar-cupo' => ['checker' => 'puedeEditarPersonaDesdeDiscipular'],
    'ministerios/liberar-cupo' => ['checker' => 'puedeEditarPersonaDesdeDiscipular'],
    'ministerios/reasignar-cupo' => ['checker' => 'puedeEditarPersonaDesdeDiscipular'],

    'discipular/ministerios/guardar-metas' => 'ministerios:editar',
    'discipular/ministerios/actualizarMeta' => 'ministerios:editar',
    'discipular/ministerios/actualizar-lideres-principales' => 'ministerios:editar',
    'ministerios/guardar-metas' => 'ministerios:editar',
    'ministerios/actualizarMeta' => 'ministerios:editar',
    'ministerios/actualizar-lideres-principales' => 'ministerios:editar',

    'asistencias/registrar' => 'asistencias:crear',

    'personas/asignarMinisterioGanar' => 'personas:editar',
    'personas/reasignarMinisterioGanar' => 'personas:editar',

    'talleres/estado' => 'talleres:editar',
    'talleres/respuestas/asignar-ministerio' => ['any' => ['talleres:editar', 'talleres:eliminar']],
    'talleres/crear-presentacion-ninos' => 'talleres:crear',
    'talleres/crear-tour-levantate' => 'talleres:crear',
    'talleres/corregir-personas-tour' => ['any' => ['talleres:editar', 'talleres:crear']],
    'talleres/guardar-pago' => ['any' => ['talleres:editar', 'talleres:crear']],
    'talleres/nueva-actividad' => ['any' => ['talleres:editar', 'talleres:crear']],
    'talleres/documento-presentacion' => ['checker' => 'puedeAccederRespuestasTalleres'],

    'entrega_obsequio/marcarEntregado' => 'entrega_obsequio:editar',

    'nehemias/seremos1200/importar' => 'nehemias:crear',
    'nehemias/seremos1200/decision' => 'nehemias:editar',
    'nehemias/whatsapp-campanas/generar-cola' => 'nehemias:editar',
    'nehemias/whatsapp-campanas/procesar-cola' => 'nehemias:editar',
    'nehemias/whatsapp-campanas/reintentar-fallidos' => 'nehemias:editar',

    'transmisiones/cambiarEstado' => 'transmisiones:editar',

];


