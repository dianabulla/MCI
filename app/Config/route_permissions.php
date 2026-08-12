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

    'personas/plantillas-whatsapp' => 'personas_plantillas_whatsapp:ver',

    'personas/plantillas-whatsapp/programar' => 'personas_plantillas_whatsapp:ver',

    'personas/guardarPlantillasWhatsapp' => 'personas_plantillas_whatsapp:editar',

    'personas/whatsapp/bandeja' => 'personas_plantillas_whatsapp:ver',

    'personas/eliminar' => ['checker' => 'puedeEliminarPersonaDesdeDiscipular'],

    'personas/exportarExcel' => ['any' => ['personas:exportar_excel', 'personas:editar']],

    'personas/crear' => ['any' => ['personas:crear', 'acceso_rapido_nuevo_discipulo:crear']],

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

    'home/cambiar-segmento-inscripcion' => ['checker' => 'puedeAccederEscuelasFormacion'],

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

    'asistencias/*' => ['modulo' => 'asistencias', 'inferir_accion' => true],



    // --- Discipular / ministerios ---

    'discipular/ministerios' => 'ministerios:ver',

    'discipular/ministerios/eliminar' => 'ministerios:eliminar',

    'discipular/ministerios/personas-asignables' => 'ministerios:ver',

    'discipular/ministerios/*' => ['modulo' => 'ministerios', 'inferir_accion' => true],

    'discipular/migrar-consolidados' => ['admin_only' => true],



    // --- Teen (privado; formularios públicos sin permiso de módulo) ---

    'teen' => 'teen:ver',

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

    'teen/qr-registro' => ['allow' => true],

    'teen/registro-publico' => ['allow' => true],

    'teen/guardar-menor-publico' => ['allow' => true],

    'teen/consulta-codigo' => ['allow' => true],

    'teen/buscar-menor-publico-telefono' => ['allow' => true],



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

    'reportes/dashboard-escuelas-uv' => ['checker' => 'puedeVerDashboardProgramaUv'],

    'reportes/dashboard-escuelas-uv-detalle' => ['checker' => 'puedeVerDashboardProgramaUv'],

    'reportes/dashboard-escuelas-capacitacion' => ['checker' => 'puedeVerDashboardProgramaCap'],

    'reportes/*' => 'reportes:ver',



    // --- Peticiones (panel) ---

    'peticiones' => 'peticiones:ver',

    'peticiones/eliminar' => 'peticiones:eliminar',

    'peticiones/*' => ['modulo' => 'peticiones', 'inferir_accion' => true],



    // --- Talleres (formularios dinámicos) ---

    'talleres' => ['checker' => 'puedeAccederModuloTalleres'],

    'talleres/crear' => 'talleres:crear',

    'talleres/guardar' => ['modulo' => 'talleres', 'inferir_accion' => true],

    'talleres/editar' => 'talleres:editar',

    'talleres/eliminar' => 'talleres:eliminar',

    'talleres/respuestas' => ['checker' => 'puedeAccederRespuestasTalleres'],

    'talleres/exportar' => ['any' => ['talleres:exportar_excel', 'talleres:ver_respuestas', 'talleres:ver']],

    'talleres/qr' => ['any' => ['talleres:gestionar_enlace', 'talleres:editar', 'talleres:ver']],

    'talleres/servicio-social' => ['checker' => 'puedeAccederModuloTalleres'],

    'talleres/servicio-social/ver' => ['checker' => 'puedeAccederModuloTalleres'],

    'talleres/servicio-social/actualizar' => ['any' => ['talleres:editar', 'talleres:ver_respuestas']],

    'talleres/servicio-social/horarios' => ['any' => ['talleres:editar', 'talleres:ver_respuestas']],

    'talleres/servicio-social/horarios/guardar' => ['any' => ['talleres:editar', 'talleres:ver_respuestas']],

    'talleres/servicio-social/guardar-historia' => ['any' => ['talleres:editar', 'talleres:ver_respuestas']],

    'talleres/servicio-social/exportar' => ['any' => ['talleres:exportar_excel', 'talleres:ver_respuestas', 'talleres:ver']],

    'talleres/*' => ['modulo' => 'talleres', 'inferir_accion' => true],



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

    'escuelas_formacion/pagos' => ['any' => ['programas:gestionar_pagos_universidad_vida', 'programas:gestionar_pagos_capacitacion_destino', 'escuelas_formacion:gestionar_pagos', 'programas:ver']],

    'escuelas_formacion/pagos/*' => ['any' => ['programas:gestionar_pagos_universidad_vida', 'programas:gestionar_pagos_capacitacion_destino', 'escuelas_formacion:gestionar_pagos', 'programas:ver']],

    'escuelas_formacion/inscritos' => ['checker' => 'puedeAccederEscuelasFormacion'],

    'escuelas_formacion/inscritos/eliminar' => ['any' => ['escuelas_formacion:eliminar', 'personas:eliminar']],

    'escuelas_formacion/inscritos/guardar-asistencia' => 'escuelas_formacion_marcar_asistencia:editar',

    'escuelas_formacion/inscritos/*' => ['checker' => 'puedeAccederEscuelasFormacion'],

    'escuelas_formacion/abonos/*' => ['checker' => 'puedeAccederEscuelasFormacion'],

    'escuelas_formacion/codigos' => ['checker' => 'puedeAccederEscuelasFormacion'],



    // --- Administración (Fase B: permisos/cuentas delegables) ---

    'cuentas' => 'cuentas:ver',

    'cuentas/crear' => 'cuentas:crear',

    'cuentas/editar' => 'cuentas:editar',

    'cuentas/asignar-segundo-rol' => 'cuentas:editar',

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

];


