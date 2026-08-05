<?php

/**

 * Catálogo único de módulos de permisos: clave, nombre, descripción, agrupación y etiquetas CRUD.

 * Fuente de verdad para PermisosController, detección de obsoletos y pantalla Permisos.

 */

class PermisosModulos {



    /**

     * @return array<string, array{

     *   label:string,

     *   descripcion:string,

     *   grupo:string,

     *   solo_ver?:bool,

     *   crud_labels?:array<string, string>

     * }>

     */

    public static function definiciones(): array {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        require_once APP . '/Helpers/PermisosUiCatalogo.php';
        $cache = array_merge(
            self::definicionesInternas(),
            PermisosUiCatalogo::modulosDerivadosParaMatriz()
        );
        return $cache;
    }

    /**
     * @return array<string, array<string, mixed>>|null
     */
    public static function definicionBase(string $modulo): ?array {
        $def = self::definicionesInternas();
        return $def[$modulo] ?? null;
    }

    /**
     * Catálogo base (sin submódulos UI derivados).
     *
     * @return array<string, array{
     *   label:string,
     *   descripcion:string,
     *   grupo:string,
     *   solo_ver?:bool,
     *   crud_labels?:array<string, string>
     * }>
     */
    public static function definicionesInternas(): array {

        return [

            // --- Ganar-Consolidar ---

            'personas' => [

                'label' => 'Almas ganadas',

                'descripcion' => 'Pestaña Almas ganadas, campaña y registro de almas en Ganar-Consolidar.',

                'grupo' => 'Ganar-Consolidar',

                'crud_labels' => [

                    'puede_ver' => 'Entrar (Almas ganadas)',

                    'puede_crear' => 'Registrar almas',

                    'puede_editar' => 'Editar fichas',

                    'puede_eliminar' => 'Eliminar registros',

                ],

            ],

            'personas_consulta' => [

                'label' => 'Discípulos (consulta)',

                'descripcion' => 'Listado «Discípulos», fichas y Universidad de la Vida sin menú Ganar-Consolidar ni Almas ganadas.',

                'grupo' => 'Ganar-Consolidar',

                'crud_labels' => [

                    'puede_ver' => 'Ver Discípulos',

                    'puede_crear' => 'Crear fichas',

                    'puede_editar' => 'Editar fichas',

                    'puede_eliminar' => 'Eliminar fichas',

                ],

            ],

            'personas_ganar_asignados' => [

                'label' => 'Ganar: atajo Asignados',

                'descripcion' => 'Filtro «Asignados» en pendientes por consolidar (vista Ganar).',

                'grupo' => 'Ganar-Consolidar',

                'solo_ver' => true,

                'crud_labels' => ['puede_ver' => 'Ver atajo Asignados'],

            ],

            'personas_ganar_reasignados' => [

                'label' => 'Ganar: atajo Reasignados',

                'descripcion' => 'Filtro «Reasignados» en pendientes por consolidar (vista Ganar).',

                'grupo' => 'Ganar-Consolidar',

                'solo_ver' => true,

                'crud_labels' => ['puede_ver' => 'Ver atajo Reasignados'],

            ],



            // --- Accesos rápidos (sidebar) ---

            'acceso_rapido_nuevo_discipulo' => [

                'label' => 'Nuevo Discípulo (acceso rápido)',

                'descripcion' => 'Enlace del sidebar «Accesos rápidos» y formulario personas/crear.',

                'grupo' => 'Accesos rápidos',

                'crud_labels' => [

                    'puede_ver' => 'Ver enlace',

                    'puede_crear' => 'Registrar discípulo',

                    'puede_editar' => 'Editar desde formulario',

                    'puede_eliminar' => 'Eliminar desde formulario',

                ],

            ],

            'personas_plantillas_whatsapp' => [

                'label' => 'Plantillas WhatsApp',

                'descripcion' => 'Enlace «Plantillas WhatsApp» en accesos rápidos: plantillas, programación y bandeja.',

                'grupo' => 'Accesos rápidos',

                'crud_labels' => [

                    'puede_ver' => 'Ver plantillas y bandeja',

                    'puede_crear' => 'Crear plantillas',

                    'puede_editar' => 'Editar y programar',

                    'puede_eliminar' => 'Eliminar plantillas',

                ],

            ],

            'personas_formulario_publico' => [

                'label' => 'Formulario público',

                'descripcion' => 'Enlace «Formulario público» en accesos rápidos (registro_personas).',

                'grupo' => 'Accesos rápidos',

                'solo_ver' => true,

                'crud_labels' => ['puede_ver' => 'Ver enlace al formulario'],

            ],



            // --- Comunidad (sidebar) ---

            'peticiones' => [

                'label' => 'Peticiones',

                'descripcion' => 'Módulo Peticiones en el menú Comunidad del sidebar.',

                'grupo' => 'Comunidad',

                'crud_labels' => [

                    'puede_ver' => 'Ver peticiones',

                    'puede_crear' => 'Crear peticiones',

                    'puede_editar' => 'Editar peticiones',

                    'puede_eliminar' => 'Eliminar peticiones',

                ],

            ],

            'talleres' => [

                'label' => 'Talleres',

                'descripcion' => 'Formularios de talleres: listado, respuestas, gráficas, Excel y enlaces públicos. Use las acciones permitidas para afinar cada rol.',

                'grupo' => 'Comunidad',

                'crud_labels' => [

                    'puede_ver' => 'Ver listado de formularios',

                    'puede_crear' => 'Crear nuevos formularios',

                    'puede_editar' => 'Editar formularios y preguntas',

                    'puede_eliminar' => 'Eliminar formularios',

                ],

            ],

            'transmisiones' => [

                'label' => 'Transmisiones',

                'descripcion' => 'Módulo Transmisiones en el menú Comunidad del sidebar.',

                'grupo' => 'Comunidad',

                'crud_labels' => [

                    'puede_ver' => 'Ver transmisiones',

                    'puede_crear' => 'Crear transmisiones',

                    'puede_editar' => 'Editar transmisiones',

                    'puede_eliminar' => 'Eliminar transmisiones',

                ],

            ],

            'eventos' => [

                'label' => 'Eventos',

                'descripcion' => 'Módulo Eventos en el menú Comunidad del sidebar.',

                'grupo' => 'Comunidad',

                'crud_labels' => [

                    'puede_ver' => 'Ver eventos',

                    'puede_crear' => 'Crear eventos',

                    'puede_editar' => 'Editar eventos',

                    'puede_eliminar' => 'Eliminar eventos',

                ],

            ],



            // --- Enviar ---

            'celulas' => [

                'label' => 'Enviar (células)',

                'descripcion' => 'Menú «Enviar»: gestión de células, miembros y exportaciones.',

                'grupo' => 'Enviar',

                'crud_labels' => [

                    'puede_ver' => 'Ver células',

                    'puede_crear' => 'Crear células',

                    'puede_editar' => 'Editar células',

                    'puede_eliminar' => 'Eliminar células',

                ],

            ],

            'asistencias' => [

                'label' => 'Asistencias (Enviar)',

                'descripcion' => 'Registro y consulta de asistencias a reuniones de célula.',

                'grupo' => 'Enviar',

                'crud_labels' => [

                    'puede_ver' => 'Ver asistencias',

                    'puede_crear' => 'Registrar asistencias',

                    'puede_editar' => 'Editar asistencias',

                    'puede_eliminar' => 'Eliminar asistencias',

                ],

            ],



            // --- Programas ---

            'programas' => [

                'label' => 'Programas',

                'descripcion' => 'Menú Programas, consolidados UV/Cap. Destino, asistencias y exportación.',

                'grupo' => 'Programas',

                'crud_labels' => [

                    'puede_ver' => 'Entrar a Programas',

                    'puede_crear' => 'Crear registros',

                    'puede_editar' => 'Editar inscritos',

                    'puede_eliminar' => 'Eliminar registros',

                ],

            ],

            'escuelas_formacion' => [

                'label' => 'Escuelas de formación',

                'descripcion' => 'Panel de escuelas, inscritos, pagos y operaciones de formación.',

                'grupo' => 'Programas',

                'crud_labels' => [

                    'puede_ver' => 'Ver escuelas',

                    'puede_crear' => 'Crear inscripciones',

                    'puede_editar' => 'Editar inscripciones',

                    'puede_eliminar' => 'Eliminar inscripciones',

                ],

            ],

            'escuelas_formacion_marcar_asistencia' => [

                'label' => 'Escuelas: marcar asistencia',

                'descripcion' => 'Marcar o desmarcar asistencia en la matriz de clases.',

                'grupo' => 'Programas',

                'solo_ver' => true,

                'crud_labels' => ['puede_ver' => 'Marcar asistencia'],

            ],

            'escuelas_formacion_editar_fechas' => [

                'label' => 'Escuelas: editar fechas',

                'descripcion' => 'Cambiar fechas de clases en la matriz de escuelas.',

                'grupo' => 'Programas',

                'solo_ver' => true,

                'crud_labels' => ['puede_ver' => 'Editar fechas de clase'],

            ],



            // --- Discipular ---

            'ministerios' => [

                'label' => 'Discipular (ministerios)',

                'descripcion' => 'Menú «Discipular»: equipos ministeriales, líderes, cupos y metas.',

                'grupo' => 'Discipular',

                'crud_labels' => [

                    'puede_ver' => 'Ver ministerios',

                    'puede_crear' => 'Crear equipos',

                    'puede_editar' => 'Editar equipos',

                    'puede_eliminar' => 'Eliminar equipos',

                ],

            ],

            'discipular_evaluaciones' => [

                'label' => 'Evaluaciones',

                'descripcion' => 'Listado, presentación y gestión de evaluaciones formativas.',

                'grupo' => 'Discipular',

                'crud_labels' => [

                    'puede_ver' => 'Ver evaluaciones',

                    'puede_crear' => 'Crear evaluaciones',

                    'puede_editar' => 'Editar evaluaciones',

                    'puede_eliminar' => 'Eliminar evaluaciones',

                ],

            ],

            'discipular_evaluaciones_fechas' => [

                'label' => 'Evaluaciones: fechas',

                'descripcion' => 'Configurar ventanas de habilitación de cada evaluación.',

                'grupo' => 'Discipular',

                'solo_ver' => true,

                'crud_labels' => ['puede_ver' => 'Configurar fechas'],

            ],



            // --- Material ---

            'ver_material' => [

                'label' => 'Ver material',

                'descripcion' => 'Consultar materiales existentes (células, Teens, Capacitación Destino y Universidad de la Vida). No incluye subir ni eliminar; para eso use los submódulos correspondientes.',

                'grupo' => 'Material',

                'solo_ver' => true,

                'crud_labels' => ['puede_ver' => 'Ver materiales existentes'],

            ],

            'material' => [

                'label' => 'Centro de material (inicio)',

                'descripcion' => 'Tarjeta del inicio y ruta home/material.',

                'grupo' => 'Material',

                'crud_labels' => [

                    'puede_ver' => 'Ver centro de material',

                    'puede_crear' => 'Subir material',

                    'puede_editar' => 'Editar material',

                    'puede_eliminar' => 'Eliminar material',

                ],

            ],

            'materiales_celulas' => [

                'label' => 'Material células',

                'descripcion' => 'Documentos PDF y vistas del material para células (también desde Enviar).',

                'grupo' => 'Material',

                'crud_labels' => [

                    'puede_ver' => 'Ver material células',

                    'puede_crear' => 'Subir archivos',

                    'puede_editar' => 'Editar material',

                    'puede_eliminar' => 'Eliminar material',

                ],

            ],

            'teen' => [

                'label' => 'Teens',

                'descripcion' => 'Registro de menores, códigos QR y material del módulo Teens.',

                'grupo' => 'Material',

                'crud_labels' => [

                    'puede_ver' => 'Ver módulo Teens',

                    'puede_crear' => 'Registrar menores',

                    'puede_editar' => 'Editar registros',

                    'puede_eliminar' => 'Eliminar registros',

                ],

            ],

            'material_universidad_vida' => [

                'label' => 'Material Universidad de la Vida',

                'descripcion' => 'Biblioteca de material UV (independiente del consolidado Programas).',

                'grupo' => 'Material',

                'crud_labels' => [

                    'puede_ver' => 'Ver material UV',

                    'puede_crear' => 'Subir material',

                    'puede_editar' => 'Editar material',

                    'puede_eliminar' => 'Eliminar material',

                ],

            ],

            'material_capacitacion_destino' => [

                'label' => 'Material Cap. Destino',

                'descripcion' => 'Material formativo Capacitación Destino (vista maestro incluida).',

                'grupo' => 'Material',

                'crud_labels' => [

                    'puede_ver' => 'Ver material Cap. Destino',

                    'puede_crear' => 'Subir material',

                    'puede_editar' => 'Editar material',

                    'puede_eliminar' => 'Eliminar material',

                ],

            ],

            'material_capacitacion_destino_subir' => [

                'label' => 'Cap. Destino: solo subir',

                'descripcion' => 'Subir archivos en Cap. Destino sin CRUD completo del submódulo.',

                'grupo' => 'Material',

                'solo_ver' => true,

                'crud_labels' => ['puede_ver' => 'Subir archivos'],

            ],



            // --- Reportes y otros ---

            'reportes' => [

                'label' => 'Reportes',

                'descripcion' => 'Dashboards y reportes estadísticos (Ganar, escuelas, ministerial, etc.).',

                'grupo' => 'Reportes',

                'crud_labels' => [

                    'puede_ver' => 'Ver reportes',

                    'puede_crear' => 'Crear reportes',

                    'puede_editar' => 'Editar configuración',

                    'puede_eliminar' => 'Eliminar registros',

                ],

            ],



            // --- Obsequios ---

            'entrega_obsequio' => [

                'label' => 'Entrega de obsequios',

                'descripcion' => 'Panel de entrega y marcado de obsequios entregados.',

                'grupo' => 'Obsequios',

                'crud_labels' => [

                    'puede_ver' => 'Ver entregas',

                    'puede_crear' => 'Registrar entregas',

                    'puede_editar' => 'Editar entregas',

                    'puede_eliminar' => 'Eliminar entregas',

                ],

            ],

            'registro_obsequio' => [

                'label' => 'Registro de obsequios',

                'descripcion' => 'Consulta histórica de obsequios (panel interno).',

                'grupo' => 'Obsequios',

                'crud_labels' => [

                    'puede_ver' => 'Ver registros',

                    'puede_crear' => 'Crear registros',

                    'puede_editar' => 'Editar registros',

                    'puede_eliminar' => 'Eliminar registros',

                ],

            ],



            // --- Nehemias ---

            'nehemias' => [

                'label' => 'Nehemias',

                'descripcion' => 'Listados, reportes, importación y campañas del módulo Nehemias.',

                'grupo' => 'Nehemias',

                'crud_labels' => [

                    'puede_ver' => 'Ver Nehemias',

                    'puede_crear' => 'Crear registros',

                    'puede_editar' => 'Editar registros',

                    'puede_eliminar' => 'Eliminar registros',

                ],

            ],



            // --- Sistema ---

            'roles' => [

                'label' => 'Roles',

                'descripcion' => 'CRUD de roles de usuario.',

                'grupo' => 'Sistema',

                'crud_labels' => [

                    'puede_ver' => 'Ver roles',

                    'puede_crear' => 'Crear roles',

                    'puede_editar' => 'Editar roles',

                    'puede_eliminar' => 'Eliminar roles',

                ],

            ],

            'permisos' => [

                'label' => 'Permisos',

                'descripcion' => 'Matriz de permisos por rol. Ver = consultar; Editar = activar/desactivar módulos y botones.',

                'grupo' => 'Sistema',

                'crud_labels' => [

                    'puede_ver' => 'Ver permisos',

                    'puede_crear' => 'Crear permisos',

                    'puede_editar' => 'Editar permisos',

                    'puede_eliminar' => 'Eliminar permisos',

                ],

            ],

            'cuentas' => [

                'label' => 'Cuentas de acceso',

                'descripcion' => 'Gestión de cuentas persona y administrativas.',

                'grupo' => 'Sistema',

                'crud_labels' => [

                    'puede_ver' => 'Ver cuentas',

                    'puede_crear' => 'Crear cuentas',

                    'puede_editar' => 'Editar cuentas',

                    'puede_eliminar' => 'Eliminar cuentas',

                ],

            ],

        ];

    }



    /**

     * Etiquetas de los toggles Ver/Crear/Editar/Eliminar en la pantalla Permisos.

     *

     * @return array<string, string>

     */

    public static function crudLabels(string $modulo): array {

        $def = self::definiciones()[$modulo] ?? [];

        $defaults = [

            'puede_ver' => 'Ver',

            'puede_crear' => 'Crear',

            'puede_editar' => 'Editar',

            'puede_eliminar' => 'Eliminar',

        ];

        $custom = $def['crud_labels'] ?? [];

        if (!is_array($custom)) {

            $custom = [];

        }

        return array_merge($defaults, $custom);

    }



    /**

     * Mapa clave => etiqueta corta (compatibilidad getModulos).

     *

     * @return array<string, string>

     */

    public static function catalogoPlano(): array {

        $out = [];

        foreach (self::definiciones() as $clave => $meta) {

            $out[$clave] = (string)($meta['label'] ?? $clave);

        }

        return $out;

    }



    /**

     * Claves de módulos activos en la aplicación.

     *

     * @return array<int, string>

     */

    public static function modulosActivos(): array {

        return array_keys(self::definiciones());

    }



    /**

     * Agrupación para la UI de Permisos (grupo => módulo => [título, descripción]).

     *

     * @return array<string, array<string, array{0:string, 1:string}>>

     */

    public static function gruposParaPantalla(): array {

        $ordenGrupos = [

            'Ganar-Consolidar',

            'Accesos rápidos',

            'Comunidad',

            'Enviar',

            'Programas',

            'Discipular',

            'Material',

            'Reportes',

            'Obsequios',

            'Nehemias',

            'Sistema',

            'Otros',

        ];



        $grupos = [];

        require_once APP . '/Helpers/PermisosUiCatalogo.php';

        foreach (self::definiciones() as $clave => $meta) {

            if (PermisosUiCatalogo::esModuloDerivado($clave)) {

                continue;

            }

            $grupo = (string)($meta['grupo'] ?? 'Otros');

            $titulo = (string)($meta['label'] ?? $clave);

            $desc = (string)($meta['descripcion'] ?? '');

            if (!isset($grupos[$grupo])) {

                $grupos[$grupo] = [];

            }

            $grupos[$grupo][$clave] = [$titulo, $desc];

        }



        $ordenados = [];

        foreach ($ordenGrupos as $nombre) {

            if (!empty($grupos[$nombre])) {

                $ordenados[$nombre] = $grupos[$nombre];

                unset($grupos[$nombre]);

            }

        }

        foreach ($grupos as $nombre => $items) {

            $ordenados[$nombre] = $items;

        }



        return $ordenados;

    }



    /**
     * Jerarquía menú → submódulos para la pantalla Permisos (sin tarjetas sueltas ni derivados).
     *
     * @return array<string, array{submodulos: array<string, array{titulo:string, descripcion:string}>}>
     */
    public static function jerarquiaParaPantalla(): array {
        $ordenGrupos = [
            'Ganar-Consolidar',
            'Accesos rápidos',
            'Comunidad',
            'Enviar',
            'Programas',
            'Discipular',
            'Material',
            'Reportes',
            'Obsequios',
            'Nehemias',
            'Sistema',
            'Otros',
        ];

        $grupos = [];
        foreach (self::definicionesInternas() as $clave => $meta) {
            $grupo = (string)($meta['grupo'] ?? 'Otros');
            if (!isset($grupos[$grupo])) {
                $grupos[$grupo] = ['submodulos' => []];
            }
            $grupos[$grupo]['submodulos'][$clave] = [
                'titulo' => (string)($meta['label'] ?? $clave),
                'descripcion' => (string)($meta['descripcion'] ?? ''),
            ];
        }

        $ordenados = [];
        foreach ($ordenGrupos as $nombre) {
            if (!empty($grupos[$nombre]['submodulos'])) {
                $ordenados[$nombre] = $grupos[$nombre];
                unset($grupos[$nombre]);
            }
        }
        foreach ($grupos as $nombre => $data) {
            if (!empty($data['submodulos'])) {
                $ordenados[$nombre] = $data;
            }
        }

        return $ordenados;
    }



    public static function existe(string $modulo): bool {

        return isset(self::definiciones()[$modulo]);

    }

}

