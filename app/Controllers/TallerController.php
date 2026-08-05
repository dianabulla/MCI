<?php

/**

 * Módulo Talleres — formularios dinámicos y respuestas.

 */



require_once APP . '/Models/TallerFormulario.php';

require_once APP . '/Helpers/TallerPersonaSync.php';

require_once APP . '/Helpers/TallerPresentacionNinosSync.php';

require_once APP . '/Helpers/TallerPresentacionDocumentos.php';

require_once APP . '/Helpers/TallerAutorizacionSync.php';

require_once APP . '/Helpers/TallerRespuestasGraficas.php';

require_once APP . '/Controllers/AuthController.php';



class TallerController extends BaseController {

    private TallerFormulario $model;

    private TallerPersonaSync $personaSync;



    public function __construct() {

        $this->model = new TallerFormulario();

        $this->personaSync = new TallerPersonaSync();

    }



    /**
     * @param array<int, string> $alternativas Acciones que también autorizan (p. ej. ver_respuestas → ver).
     */
    private function puedeTaller(string $accion, array $alternativas = []): bool {

        if (AuthController::esAdministrador()) {

            return true;

        }

        if (AuthController::puede('talleres:' . $accion)) {

            return true;

        }

        foreach ($alternativas as $alt) {

            if (AuthController::puede('talleres:' . $alt)) {

                return true;

            }

        }

        return false;

    }



    /**
     * @param array<int, string> $alternativas
     */
    private function requierePermiso(string $accion, array $alternativas = []): void {

        if (!$this->puedeTaller($accion, $alternativas)) {

            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');

            exit;

        }

    }



    private function requiereAccesoTaller(): void {

        if (!AuthController::puedeAccederModuloTalleres()) {

            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');

            exit;

        }

    }



    private function requiereAccesoRespuestas(): void {

        if (!AuthController::puedeAccederRespuestasTalleres()) {

            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');

            exit;

        }

    }



    /**
     * @return array<string, bool>
     */
    private function permisosTallerVista(): array {

        if (AuthController::esRolSoloGraficasTalleres()) {

            return [

                'crear' => false,

                'editar' => false,

                'eliminar' => false,

                'ver_respuestas' => false,

                'ver_graficas' => true,

                'exportar_excel' => false,

                'gestionar_enlace' => false,

                'solo_graficas' => true,

            ];

        }

        return [

            'crear' => $this->puedeTaller('crear'),

            'editar' => $this->puedeTaller('editar'),

            'eliminar' => $this->puedeTaller('eliminar'),

            'ver_respuestas' => $this->puedeTaller('ver_respuestas', ['ver']),

            'ver_graficas' => $this->puedeTaller('ver_graficas', ['ver_respuestas', 'ver']),

            'exportar_excel' => $this->puedeTaller('exportar_excel', ['ver_respuestas', 'ver']),

            'gestionar_enlace' => $this->puedeTaller('gestionar_enlace', ['editar', 'ver']),

            'solo_graficas' => false,

        ];

    }



    /**
     * Entrada directa a gráficas para roles que solo tienen ese permiso.
     */
    private function redirigirEntradaSoloGraficas(): void {

        $formularios = $this->model->getAllConConteo();

        $candidatos = array_values(array_filter(

            $formularios,

            static fn(array $f): bool => (int)($f['Total_Respuestas'] ?? 0) > 0

        ));

        if ($candidatos === []) {

            $candidatos = array_values($formularios);

        }

        if (count($candidatos) === 1) {

            $id = (int)($candidatos[0]['Id_Formulario'] ?? 0);

            if ($id > 0) {

                header('Location: ' . PUBLIC_URL . '?url=talleres/respuestas&id=' . $id . '&tab=graficas');

                exit;

            }

        }

        $this->view('talleres/selector_graficas', [

            'formularios' => $candidatos,

        ]);

        exit;

    }



    public function index(): void {

        $this->requiereAccesoTaller();

        if (AuthController::esPerfilServicioSocialTalleres()) {
            $this->redirect('talleres/servicio-social');
        }

        if (AuthController::esRolSoloGraficasTalleres()) {

            $this->redirigirEntradaSoloGraficas();

        }

        $formularios = $this->model->getAllConConteo();

        $this->view('talleres/lista', [

            'formularios' => $formularios,

            'permisos_taller' => $this->permisosTallerVista(),

        ]);

    }



    public function crear(): void {

        $this->requierePermiso('crear');

        $this->view('talleres/editor', [

            'formulario' => null,

            'bloques' => [],

            'modo' => 'crear',

        ]);

    }



    public function crearPresentacionNinos(): void {

        $this->requierePermiso('crear');

        $existente = $this->model->getBySlug('presentacion-ninos');

        if ($existente && (int)($existente['Id_Formulario'] ?? 0) > 0) {

            $_SESSION['talleres_flash_ok'] = 'El formulario «Presentación de niños» ya existe.';

            header('Location: ' . PUBLIC_URL . '?url=talleres/respuestas&id=' . (int)$existente['Id_Formulario']);

            exit;

        }



        $creadoPor = (int)($_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? 0);

        $id = $this->model->crearFormularioPresentacionNinos($creadoPor);

        $_SESSION['talleres_flash_ok'] = 'Formulario «Presentación de niños» creado correctamente.';

        header('Location: ' . PUBLIC_URL . '?url=talleres/respuestas&id=' . $id);

        exit;

    }



    public function crearTourLevantate(): void {

        $this->requierePermiso('crear');

        $existente = $this->model->getBySlug('tour-levantate-y-resplandece');

        if ($existente && (int)($existente['Id_Formulario'] ?? 0) > 0) {

            $_SESSION['talleres_flash_ok'] = 'El formulario «Tour Levántate y Resplandece» ya existe.';

            header('Location: ' . PUBLIC_URL . '?url=talleres/respuestas&id=' . (int)$existente['Id_Formulario']);

            exit;

        }



        $creadoPor = (int)($_SESSION['usuario_id'] ?? $_SESSION['id_usuario'] ?? 0);

        $id = $this->model->crearFormularioTourLevantate($creadoPor);

        $_SESSION['talleres_flash_ok'] = 'Formulario «Tour Levántate y Resplandece» creado correctamente.';

        header('Location: ' . PUBLIC_URL . '?url=talleres/respuestas&id=' . $id);

        exit;

    }



    public function corregirPersonasTour(): void {

        $this->requierePermiso('editar', ['crear']);

        require_once APP . '/Helpers/TallerTourLevantateCorreccion.php';

        $servicio = new TallerTourLevantateCorreccion();

        $aplicado = false;

        $mensaje = '';

        $totalCorregidas = 0;



        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $resultado = $servicio->aplicar();

            $aplicado = true;

            $mensaje = (string)($resultado['mensaje'] ?? '');

            $totalCorregidas = (int)($resultado['total'] ?? 0);

            $pendientes = [];

        } else {

            $pendientes = $servicio->obtenerPendientes();

        }



        $this->view('talleres/corregir_personas_tour', [

            'pendientes' => $pendientes ?? [],

            'aplicado' => $aplicado,

            'mensaje' => $mensaje,

            'total_corregidas' => $totalCorregidas,

        ]);

    }



    public function editar(): void {

        $this->requierePermiso('editar');

        $id = (int)($_GET['id'] ?? 0);

        $formulario = $id > 0 ? $this->model->getById($id) : null;

        if (!$formulario) {

            header('Location: ' . PUBLIC_URL . '?url=talleres');

            exit;

        }

        $completo = $this->model->getFormularioCompleto($id);

        $bloques = [];

        foreach ($completo['bloques'] as $item) {

            if (in_array(($item['bloque']['Tipo'] ?? ''), ['persona', 'autorizacion', 'padres', 'nino'], true)) {

                continue;

            }

            $bloques[] = [

                'titulo' => (string)($item['bloque']['Titulo'] ?? ''),

                'campos' => $item['campos'],

            ];

        }

        $this->view('talleres/editor', [

            'formulario' => $formulario,

            'bloques' => $bloques,

            'modo' => 'editar',

        ]);

    }



    public function guardar(): void {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header('Location: ' . PUBLIC_URL . '?url=talleres');

            exit;

        }



        $id = (int)($_POST['id_formulario'] ?? 0);

        $esEdicion = $id > 0;



        if ($esEdicion) {

            $this->requierePermiso('editar');

        } else {

            $this->requierePermiso('crear');

        }



        $titulo = trim((string)($_POST['titulo'] ?? ''));

        $descripcion = trim((string)($_POST['descripcion'] ?? ''));

        $mensajeGracias = trim((string)($_POST['mensaje_gracias'] ?? ''));

        $textoAutorizacion = trim((string)($_POST['texto_autorizacion'] ?? ''));

        $activo = !empty($_POST['activo']) ? 1 : 0;



        if ($titulo === '') {

            $_SESSION['talleres_flash_error'] = 'Escriba el nombre del formulario.';

            header('Location: ' . PUBLIC_URL . '?url=talleres/' . ($esEdicion ? 'editar&id=' . $id : 'crear'));

            exit;

        }



        $slug = $this->model->generarSlugUnico($titulo, $esEdicion ? $id : 0);



        $data = [

            'Titulo' => $titulo,

            'Slug' => $slug,

            'Descripcion' => $descripcion !== '' ? $descripcion : null,

            'Activo' => $activo,

            'Mensaje_Gracias' => $mensajeGracias !== '' ? $mensajeGracias : null,

            'Texto_Autorizacion' => $textoAutorizacion !== '' ? $textoAutorizacion : null,

        ];



        if ($esEdicion) {

            $this->model->update($id, $data);

        } else {

            $idPersona = (int)($_SESSION['id_persona'] ?? 0);

            if ($idPersona > 0) {

                $data['Creado_Por'] = $idPersona;

            }

            $id = (int)$this->model->create($data);

        }



        $bloquesPost = $this->parsearBloquesDesdePost($_POST);

        $this->model->reemplazarBloquesYCampos($id, $bloquesPost);

        $_SESSION['talleres_flash_ok'] = 'Formulario guardado correctamente.';

        header('Location: ' . PUBLIC_URL . '?url=talleres/editar&id=' . $id);

        exit;

    }



    /**

     * @param array<string, mixed> $post

     * @return array<int, array<string, mixed>>

     */

    private function parsearBloquesDesdePost(array $post): array {

        $titulos = (array)($post['bloque_titulo'] ?? []);

        $indicesCampo = (array)($post['campo_bloque_idx'] ?? []);

        $etiquetas = (array)($post['campo_etiqueta'] ?? []);

        $tipos = (array)($post['campo_tipo'] ?? []);

        $requeridos = (array)($post['campo_requerido'] ?? []);

        $opciones = (array)($post['campo_opciones'] ?? []);

        $columnasTabla = (array)($post['campo_tabla_columnas'] ?? []);



        $bloquesMap = [];

        foreach ($titulos as $idx => $titulo) {

            $titulo = trim((string)$titulo);

            if ($titulo === '') {

                continue;

            }

            $bloquesMap[(int)$idx] = [

                'titulo' => $titulo,

                'campos' => [],

            ];

        }



        $total = count($etiquetas);

        for ($i = 0; $i < $total; $i++) {

            $etiqueta = trim((string)($etiquetas[$i] ?? ''));

            if ($etiqueta === '') {

                continue;

            }

            $bloqueIdx = (int)($indicesCampo[$i] ?? 0);

            if (!isset($bloquesMap[$bloqueIdx])) {

                $bloquesMap[$bloqueIdx] = [

                    'titulo' => 'Bloque ' . ($bloqueIdx + 1),

                    'campos' => [],

                ];

            }



            $tipo = strtolower(trim((string)($tipos[$i] ?? 'text')));

            $campo = [

                'etiqueta' => $etiqueta,

                'tipo' => $tipo,

                'requerido' => isset($requeridos[$i]),

                'opciones' => [],

                'columnas' => [],

            ];



            if ($tipo === 'tabla') {

                $columnasRaw = trim((string)($columnasTabla[$i] ?? ''));

                if ($columnasRaw !== '') {

                    $campo['columnas'] = preg_split('/\r\n|\r|\n|,/', $columnasRaw) ?: [];

                }

            } else {

                $opcionesRaw = trim((string)($opciones[$i] ?? ''));

                if ($opcionesRaw !== '') {

                    $campo['opciones'] = preg_split('/\r\n|\r|\n|,/', $opcionesRaw) ?: [];

                }

            }



            $bloquesMap[$bloqueIdx]['campos'][] = $campo;

        }



        ksort($bloquesMap);

        return array_values($bloquesMap);

    }



    public function eliminar(): void {

        $this->requierePermiso('eliminar');

        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

        if ($id > 0) {

            $this->model->eliminarFormulario($id);

            $_SESSION['talleres_flash_ok'] = 'Formulario eliminado.';

        }

        header('Location: ' . PUBLIC_URL . '?url=talleres');

        exit;

    }



    public function respuestas(): void {

        $this->requiereAccesoRespuestas();

        $id = (int)($_GET['id'] ?? 0);

        $formulario = $id > 0 ? $this->model->getById($id) : null;

        if (!$formulario) {

            header('Location: ' . PUBLIC_URL . '?url=talleres');

            exit;

        }



        $permisosTaller = $this->permisosTallerVista();

        $soloGraficas = AuthController::esRolSoloGraficasTalleres();

        $puedeGraficas = !empty($permisosTaller['ver_graficas']);

        $campos = $this->model->getCamposPersonalizadosPorFormulario($id);

        $respuestasRaw = $this->model->getRespuestasPorFormulario($id);

        $secciones = $soloGraficas ? [] : $this->buildSeccionesParaVista($id);

        $respuestas = [];

        $totalesPagos = [];

        if (!$soloGraficas) {

            $idsRespuesta = array_map(static fn(array $fila): int => (int)($fila['Id_Respuesta'] ?? 0), $respuestasRaw);

            $totalesPagos = $this->model->getTotalesPagosPorRespuestas($idsRespuesta);

            foreach ($respuestasRaw as $fila) {

                $json = json_decode((string)($fila['Datos_JSON'] ?? '{}'), true);

                if (!is_array($json)) {

                    $json = [];

                }

                $idRespuesta = (int)($fila['Id_Respuesta'] ?? 0);

                $valores = $this->construirValoresRespuesta($campos, $json, (int)($fila['Id_Persona'] ?? 0));

                $auth = is_array($json['_autorizacion'] ?? null) ? $json['_autorizacion'] : [];

                $firmaRaw = (string)($auth['firma'] ?? '');

                $documentosPresentacion = [];
                if ($this->model->esPresentacionNinos($formulario)) {
                    $documentosPresentacion = TallerPresentacionDocumentos::obtenerParaRespuesta(
                        $idRespuesta,
                        $json['documentos_presentacion'] ?? []
                    );
                }

                $respuestas[] = [

                    'id' => $idRespuesta,

                    'fecha' => (string)($fila['Fecha_Registro'] ?? ''),

                    'ip' => (string)($fila['Ip_Origen'] ?? ''),

                    'id_persona' => (int)($fila['Id_Persona'] ?? 0),

                    'valores' => $valores,

                    'documentos_presentacion' => $documentosPresentacion,

                    'firma_imagen' => (str_starts_with($firmaRaw, 'data:image')) ? $firmaRaw : '',

                    'total_pagado' => (float)($totalesPagos[$idRespuesta] ?? 0),

                ];

            }

        }

        $tabSolicitada = (($_GET['tab'] ?? 'lista') === 'graficas') ? 'graficas' : 'lista';

        if ($soloGraficas) {

            $tabActiva = $puedeGraficas ? 'graficas' : 'lista';

        } else {

            $tabActiva = ($tabSolicitada === 'graficas' && $puedeGraficas) ? 'graficas' : 'lista';

        }

        $estadisticasGraficas = ['total' => count($respuestas), 'graficas' => [], 'total_hijos_tabla' => 0];

        if ($puedeGraficas) {

            $seccionesGraficas = $this->buildSeccionesParaGraficas($id);

            $graficasHelper = new TallerRespuestasGraficas();

            $estadisticasGraficas = $graficasHelper->construir($seccionesGraficas, $respuestasRaw);

        }

        $esTourLevantate = $this->model->esTourLevantate($formulario);

        $resumenPorMinisterio = $esTourLevantate && !$soloGraficas
            ? $this->construirResumenPorMinisterioTour($respuestasRaw)
            : [];



        $this->view('talleres/respuestas', [

            'formulario' => $formulario,

            'campos' => $campos,

            'secciones' => $secciones,

            'columnas_persona' => TallerFormulario::CAMPOS_PERSONA_FIJOS,

            'columnas_autorizacion' => TallerFormulario::CAMPOS_AUTORIZACION_FIJOS,

            'respuestas' => $respuestas,

            'estadisticas_graficas' => $estadisticasGraficas,

            'permisos_taller' => $permisosTaller,

            'tab_activa' => $tabActiva,

            'solo_graficas' => $soloGraficas,

            'es_presentacion_ninos' => $this->model->esPresentacionNinos($formulario),

            'es_tour_levantate' => $esTourLevantate,

            'resumen_por_ministerio' => $resumenPorMinisterio,

        ]);

    }



    /**
     * Agrupa inscripciones del Tour Levántate por ministerio.
     *
     * @param array<int, array<string, mixed>> $respuestasRaw
     * @return array<int, array{ministerio: string, total: int}>
     */
    private function construirResumenPorMinisterioTour(array $respuestasRaw): array {

        $conteo = [];

        $personaModel = null;



        foreach ($respuestasRaw as $fila) {

            $json = json_decode((string)($fila['Datos_JSON'] ?? '{}'), true);

            if (!is_array($json)) {

                $json = [];

            }



            $extras = is_array($json['_persona_extra'] ?? null) ? $json['_persona_extra'] : [];

            $ministerio = trim((string)($json['persona_ministerio'] ?? $extras['persona_ministerio'] ?? ''));



            if ($ministerio === '' && (int)($fila['Id_Persona'] ?? 0) > 0) {

                if ($personaModel === null) {

                    require_once APP . '/Models/Persona.php';

                    $personaModel = new Persona();

                }

                $persona = $personaModel->getById((int)$fila['Id_Persona']);

                if (is_array($persona)) {

                    $ministerio = trim((string)($persona['Nombre_Ministerio'] ?? ''));

                }

            }



            if ($ministerio === '') {

                $ministerio = 'Sin ministerio asignado';

            }



            $conteo[$ministerio] = ($conteo[$ministerio] ?? 0) + 1;

        }



        $filas = [];

        foreach ($conteo as $ministerio => $total) {

            $filas[] = ['ministerio' => (string)$ministerio, 'total' => (int)$total];

        }



        usort($filas, static function (array $a, array $b): int {

            $cmp = $b['total'] <=> $a['total'];

            if ($cmp !== 0) {

                return $cmp;

            }

            return strcasecmp($a['ministerio'], $b['ministerio']);

        });



        return $filas;

    }



    /**

     * Estructura de bloques para mostrar el detalle de cada respuesta.

     *

     * @return array<int, array<string, mixed>>

     */

    private function buildSeccionesParaVista(int $idFormulario): array {

        $completo = $this->model->getFormularioCompleto($idFormulario);

        $secciones = [];

        foreach ($completo['bloques'] as $item) {

            $tipo = (string)($item['bloque']['Tipo'] ?? 'personalizado');

            $titulo = (string)($item['bloque']['Titulo'] ?? '');

            $campos = [];

            if ($tipo === 'persona') {

                foreach (TallerFormulario::CAMPOS_PERSONA_FIJOS as $clave => $etiqueta) {

                    $campos[] = ['clave' => $clave, 'etiqueta' => $etiqueta, 'tipo' => 'text'];

                }

            } elseif ($tipo === 'padres') {

                foreach (TallerPresentacionNinosSync::camposPadres() as $clave => $etiqueta) {

                    $campos[] = ['clave' => $clave, 'etiqueta' => $etiqueta, 'tipo' => 'text'];

                }

            } elseif ($tipo === 'nino') {

                foreach (TallerPresentacionNinosSync::camposNino() as $clave => $etiqueta) {

                    $campos[] = ['clave' => $clave, 'etiqueta' => $etiqueta, 'tipo' => 'text'];

                }

            } elseif ($tipo === 'autorizacion') {

                foreach (TallerFormulario::CAMPOS_AUTORIZACION_FIJOS as $clave => $etiqueta) {

                    $campos[] = [

                        'clave' => $clave,

                        'etiqueta' => $etiqueta,

                        'tipo' => $clave === 'autorizacion_firma' ? 'firma' : 'text',

                    ];

                }

            } else {

                foreach ($item['campos'] as $campo) {

                    $campos[] = [

                        'clave' => (string)($campo['Nombre_Campo'] ?? ''),

                        'etiqueta' => (string)($campo['Etiqueta'] ?? ''),

                        'tipo' => strtolower((string)($campo['Tipo'] ?? 'text')),

                    ];

                }

            }

            if ($campos !== []) {

                $secciones[] = [

                    'titulo' => $titulo,

                    'tipo' => $tipo,

                    'campos' => $campos,

                ];

            }

        }

        return $secciones;

    }



    /**

     * Secciones con metadatos (opciones, columnas) para agregar gráficas.

     *

     * @return array<int, array<string, mixed>>

     */

    private function buildSeccionesParaGraficas(int $idFormulario): array {

        $completo = $this->model->getFormularioCompleto($idFormulario);

        $secciones = [];

        foreach ($completo['bloques'] as $item) {

            $tipo = (string)($item['bloque']['Tipo'] ?? 'personalizado');

            $titulo = (string)($item['bloque']['Titulo'] ?? '');

            $campos = [];

            if ($tipo === 'persona' || $tipo === 'autorizacion') {

                continue;

            } else {

                foreach ($item['campos'] as $campo) {

                    $campos[] = [

                        'clave' => (string)($campo['Nombre_Campo'] ?? ''),

                        'etiqueta' => (string)($campo['Etiqueta'] ?? ''),

                        'tipo' => strtolower((string)($campo['Tipo'] ?? 'text')),

                        'opciones' => $this->model->decodificarOpcionesCampo($campo),

                        'columnas' => $this->model->decodificarColumnasTabla($campo),

                    ];

                }

            }

            if ($campos !== []) {

                $secciones[] = [

                    'titulo' => $titulo,

                    'tipo' => $tipo,

                    'campos' => $campos,

                ];

            }

        }

        return $secciones;

    }



    public function exportar(): void {

        $this->requierePermiso('exportar_excel', ['ver_respuestas', 'ver']);

        $id = (int)($_GET['id'] ?? 0);

        $formulario = $id > 0 ? $this->model->getById($id) : null;

        if (!$formulario) {

            header('Location: ' . PUBLIC_URL . '?url=talleres');

            exit;

        }



        $campos = $this->model->getCamposPersonalizadosPorFormulario($id);

        $respuestasRaw = $this->model->getRespuestasPorFormulario($id);



        $headers = ['Fecha registro', 'Id persona'];

        foreach (TallerFormulario::CAMPOS_PERSONA_FIJOS as $etiqueta) {

            $headers[] = $etiqueta;

        }

        foreach ($campos as $campo) {

            $headers[] = (string)($campo['Etiqueta'] ?? $campo['Nombre_Campo'] ?? 'Campo');

        }

        foreach (TallerFormulario::CAMPOS_AUTORIZACION_FIJOS as $etiqueta) {

            $headers[] = $etiqueta;

        }



        $rows = [];

        foreach ($respuestasRaw as $fila) {

            $json = json_decode((string)($fila['Datos_JSON'] ?? '{}'), true);

            if (!is_array($json)) {

                $json = [];

            }

            $valores = $this->construirValoresRespuesta($campos, $json, (int)($fila['Id_Persona'] ?? 0));

            $row = [

                (string)($fila['Fecha_Registro'] ?? ''),

                (string)($fila['Id_Persona'] ?? ''),

            ];

            foreach (array_keys(TallerFormulario::CAMPOS_PERSONA_FIJOS) as $clave) {

                $row[] = (string)($valores[$clave] ?? '');

            }

            foreach ($campos as $campo) {

                $nom = (string)($campo['Nombre_Campo'] ?? '');

                $row[] = (string)($valores[$nom] ?? '');

            }

            foreach (array_keys(TallerFormulario::CAMPOS_AUTORIZACION_FIJOS) as $claveAuth) {

                $row[] = (string)($valores[$claveAuth] ?? '');

            }

            $rows[] = $row;

        }



        $slug = (string)($formulario['Slug'] ?? 'taller');

        $this->exportCsv('talleres_' . $slug . '_' . date('Ymd_His'), $headers, $rows, false);

    }



    /**

     * @param array<int, array<string, mixed>> $campos

     * @param array<string, mixed> $json

     * @return array<string, string>

     */

    private function construirValoresRespuesta(array $campos, array $json, int $idPersona): array {

        $valores = [];

        $extras = is_array($json['_persona_extra'] ?? null) ? $json['_persona_extra'] : [];



        if ($idPersona > 0) {

            require_once APP . '/Models/Persona.php';

            $personaModel = new Persona();

            $persona = $personaModel->getById($idPersona);

            if ($persona) {

                $sync = new TallerPersonaSync();

                $fmt = $sync->formatearPersonaParaFormulario($persona);

                $valores['persona_nombre'] = (string)($fmt['nombre'] ?? '');

                $valores['persona_documento'] = (string)($fmt['documento'] ?? '');

                $valores['persona_fecha_nacimiento'] = (string)($fmt['fecha_nacimiento'] ?? '');

                $valores['persona_edad'] = (string)($fmt['edad'] ?? '');

                $valores['persona_telefono'] = (string)($fmt['telefono'] ?? '');

                $valores['persona_email'] = (string)($fmt['email'] ?? '');

                $valores['persona_direccion'] = (string)($fmt['direccion'] ?? '');

            }

        } else {

            foreach (TallerFormulario::CAMPOS_PERSONA_FIJOS as $clave => $_etiqueta) {

                if (array_key_exists($clave, $json)) {

                    $valores[$clave] = is_array($json[$clave])

                        ? json_encode($json[$clave], JSON_UNESCAPED_UNICODE)

                        : (string)$json[$clave];

                }

            }

        }



        if (trim((string)($valores['persona_estado_civil'] ?? '')) === '') {

            $valores['persona_estado_civil'] = (string)($json['persona_estado_civil'] ?? $extras['estado_civil'] ?? '');

        }

        if (trim((string)($valores['persona_ocupacion'] ?? '')) === '') {

            $valores['persona_ocupacion'] = (string)($json['persona_ocupacion'] ?? $extras['ocupacion'] ?? '');

        }



        foreach ($campos as $campo) {

            $nombre = (string)($campo['Nombre_Campo'] ?? '');

            $valores[$nombre] = $this->model->formatearValorCampoRespuesta($campo, $json);

        }



        $authVals = TallerAutorizacionSync::extraerValoresParaTabla($json);

        foreach ($authVals as $k => $v) {

            $valores[$k] = $v;

        }



        foreach (TallerPresentacionNinosSync::camposPadres() as $clave => $_etiqueta) {

            if (array_key_exists($clave, $json)) {

                $valores[$clave] = (string)$json[$clave];

            }

        }

        foreach (TallerPresentacionNinosSync::camposNino() as $clave => $_etiqueta) {

            if (array_key_exists($clave, $json)) {

                $valores[$clave] = (string)$json[$clave];

            }

        }



        return $valores;

    }



    public function formularioPublico(): void {

        $slug = trim((string)($_GET['slug'] ?? ''));

        $formulario = $slug !== '' ? $this->model->getBySlug($slug) : null;



        if (!$formulario || empty($formulario['Activo'])) {

            http_response_code(404);

            $this->view('talleres_publico/no_encontrado', ['slug' => $slug]);

            return;

        }



        $id = (int)($formulario['Id_Formulario'] ?? 0);

        $this->model->asegurarTipoPlantillaPresentacionNinos($id);

        $formulario = $this->model->getById($id) ?: $formulario;

        $completo = $this->model->getFormularioCompleto($id);

        if (empty($completo['bloques'])) {

            http_response_code(404);

            $this->view('talleres_publico/no_encontrado', ['slug' => $slug]);

            return;

        }



        $errores = $_SESSION['talleres_publico_errores'] ?? [];

        $valores = $_SESSION['talleres_publico_valores'] ?? [];

        unset($_SESSION['talleres_publico_errores'], $_SESSION['talleres_publico_valores']);



        $this->view('talleres_publico/formulario', [

            'formulario' => $formulario,

            'bloques' => $completo['bloques'],

            'es_presentacion_ninos' => $this->model->esPresentacionNinos($formulario),

            'es_tour_levantate' => $this->model->esTourLevantate($formulario),

            'estados_civiles' => TallerFormulario::ESTADOS_CIVILES,

            'texto_autorizacion' => $completo['texto_autorizacion'] ?? TallerAutorizacionSync::textoDefault(),

            'errores' => $errores,

            'valores' => $valores,

            'enviado_ok' => !empty($_GET['ok']),

        ]);

    }



    public function buscarPersonaPublico(): void {

        header('Content-Type: application/json; charset=utf-8');

        $documento = trim((string)($_GET['documento'] ?? $_POST['documento'] ?? ''));

        $telefono = preg_replace('/\D+/', '', trim((string)($_GET['telefono'] ?? $_POST['telefono'] ?? '')));

        $modo = trim((string)($_GET['modo'] ?? $_POST['modo'] ?? ''));

        $idFormulario = (int)($_GET['id_formulario'] ?? $_POST['id_formulario'] ?? 0);
        $slugFormulario = trim((string)($_GET['slug'] ?? $_POST['slug'] ?? ''));
        $formularioBusqueda = null;
        if ($idFormulario <= 0 && $slugFormulario !== '') {
            $formularioBusqueda = $this->model->getBySlug($slugFormulario);
            $idFormulario = (int)($formularioBusqueda['Id_Formulario'] ?? 0);
        } elseif ($idFormulario > 0) {
            $formularioBusqueda = $this->model->getById($idFormulario);
        }

        $presentacionSync = new TallerPresentacionNinosSync();

        $persona = null;

        if ($documento !== '') {

            if (in_array($modo, ['padres', 'nino'], true)) {

                $persona = $presentacionSync->buscarPorDocumento($documento);

            } else {

                $persona = $this->personaSync->buscarPorDocumento($documento);

            }

        }

        if (!$persona && $telefono !== '' && !in_array($modo, ['padres', 'nino'], true)) {

            $persona = $this->personaSync->buscarPorTelefono($telefono);

        }

        $inscripcionPrevia = null;
        if ($modo === 'nino' && $idFormulario > 0 && $documento !== '') {
            $inscripcionPrevia = $this->model->buscarInscripcionPorDocumentoNino($idFormulario, $documento);
        }

        if ($modo === 'nino' && $inscripcionPrevia) {
            $fechaPrev = substr((string)($inscripcionPrevia['fecha'] ?? ''), 0, 10);
            $nombrePrev = trim((string)($inscripcionPrevia['nino_nombre'] ?? ''));
            $mensaje = 'Este niño(a) ya está inscrito en este formulario';
            if ($fechaPrev !== '') {
                $mensaje .= ' (registro del ' . $fechaPrev . ')';
            }
            $mensaje .= '. Puede subir documentos en la sección de abajo.';
            if ($nombrePrev !== '') {
                $mensaje .= ' Nombre registrado: ' . $nombrePrev . '.';
            }

            echo json_encode([
                'ok' => $persona !== null,
                'persona' => $persona,
                'ya_inscrito' => true,
                'inscripcion' => $inscripcionPrevia,
                'mensaje' => $mensaje,
            ], JSON_UNESCAPED_UNICODE);

            return;
        }

        if (
            $idFormulario > 0
            && $documento !== ''
            && !in_array($modo, ['padres', 'nino'], true)
            && is_array($formularioBusqueda)
            && $this->model->esTourLevantate($formularioBusqueda)
        ) {
            $inscripcionTour = $this->model->buscarInscripcionPorDocumentoPersona($idFormulario, $documento);
            if ($inscripcionTour) {
                $fechaPrev = substr((string)($inscripcionTour['fecha'] ?? ''), 0, 10);
                $nombrePrev = trim((string)($inscripcionTour['persona_nombre'] ?? ''));
                $mensaje = 'Esta cédula ya está inscrita en el Tour Levántate y Resplandece';
                if ($fechaPrev !== '') {
                    $mensaje .= ' (registro del ' . $fechaPrev . ')';
                }
                if ($nombrePrev !== '') {
                    $mensaje .= '. Nombre registrado: ' . $nombrePrev . '.';
                }

                echo json_encode([
                    'ok' => $persona !== null,
                    'persona' => $persona,
                    'ya_inscrito' => true,
                    'inscripcion' => $inscripcionTour,
                    'mensaje' => $mensaje,
                ], JSON_UNESCAPED_UNICODE);

                return;
            }
        }

        if (!$persona) {

            echo json_encode(['ok' => false, 'persona' => null], JSON_UNESCAPED_UNICODE);

            return;

        }

        echo json_encode(['ok' => true, 'persona' => $persona], JSON_UNESCAPED_UNICODE);

    }



    private function generarReferenciaCorta(): string {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $ref = 'T';
        for ($i = 0; $i < 5; $i++) {
            $ref .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $ref;
    }

    /**
     * @param array<string, mixed> $formulario
     * @param array<string, mixed> $json
     * @param array<string, mixed> $pago
     * @return array<string, mixed>
     */
    private function construirTicketPagoDesdeDatos(array $formulario, array $json, array $pago): array {
        $nombreNino = trim((string)($json['nino_nombre'] ?? $json['persona_nombre'] ?? ''));
        $tipoPago = trim((string)($pago['Tipo_Pago'] ?? 'completo'));
        if ($tipoPago === 'completo') {
            $tipoEtiqueta = 'Pago total';
        } elseif ($tipoPago === 'abono') {
            $tipoEtiqueta = 'Abono';
        } else {
            $tipoEtiqueta = $tipoPago;
        }

        $fechaRegistro = trim((string)($pago['Fecha_Registro'] ?? ''));
        if ($fechaRegistro !== '') {
            $fechaRegistro = substr($fechaRegistro, 0, 16);
        }

        return [
            'fecha' => $fechaRegistro !== '' ? $fechaRegistro : date('Y-m-d H:i'),
            'formulario' => (string)($formulario['Titulo'] ?? 'Taller'),
            'nombre' => $nombreNino,
            'documento' => trim((string)($json['nino_documento'] ?? $json['persona_documento'] ?? '')),
            'acudiente' => trim((string)($json['padres_nombre'] ?? '')),
            'metodo_pago' => trim((string)($pago['Metodo_Pago'] ?? 'Efectivo')),
            'recibido_por' => trim((string)($pago['Recibido_Por'] ?? '')),
            'tipo_pago' => $tipoEtiqueta,
            'valor_pago' => number_format((float)($pago['Valor_Pago'] ?? 0), 0, ',', '.'),
            'referencia_pago' => trim((string)($pago['Referencia_Pago'] ?? '')),
        ];
    }

    public function ticketPago(): void {
        $this->requiereAccesoRespuestas();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $data = $_SESSION['talleres_ticket'] ?? null;
        if (empty($data) || !is_array($data)) {
            header('Location: ' . PUBLIC_URL . '?url=talleres&tipo=error&mensaje=' . urlencode('No hay ticket disponible para mostrar.'));
            exit;
        }
        $this->view('talleres/ticket_pago', ['ticket' => $data]);
    }

    public function pagoRespuesta(): void {

        $this->requiereAccesoRespuestas();

        $idRespuesta = (int)($_GET['id_respuesta'] ?? 0);

        $idFormulario = (int)($_GET['id'] ?? 0);

        $respuesta = $idRespuesta > 0 ? $this->model->getRespuestaPorId($idRespuesta) : null;

        if (!$respuesta || (int)($respuesta['Id_Formulario'] ?? 0) !== $idFormulario) {

            header('Location: ' . PUBLIC_URL . '?url=talleres');

            exit;

        }

        $formulario = $this->model->getById($idFormulario);

        if (!$formulario) {

            header('Location: ' . PUBLIC_URL . '?url=talleres');

            exit;

        }

        $json = json_decode((string)($respuesta['Datos_JSON'] ?? '{}'), true);

        if (!is_array($json)) {

            $json = [];

        }

        $pagos = $this->model->getPagosPorRespuesta($idRespuesta);

        $totalPagado = 0.0;

        foreach ($pagos as $pago) {

            $totalPagado += (float)($pago['Valor_Pago'] ?? 0);

        }

        $nombreNino = trim((string)($json['nino_nombre'] ?? ''));

        $nombrePersona = trim((string)($json['persona_nombre'] ?? ''));

        $referenciaPago = trim((string)($_GET['referencia_pago'] ?? ''));

        $ticketDatos = null;

        if ($referenciaPago !== '') {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $ticket = $_SESSION['talleres_ticket'] ?? null;
            if (is_array($ticket) && (string)($ticket['referencia_pago'] ?? '') === $referenciaPago) {
                $ticketDatos = $ticket;
            } else {
                foreach ($pagos as $pago) {
                    if (trim((string)($pago['Referencia_Pago'] ?? '')) === $referenciaPago) {
                        $ticketDatos = $this->construirTicketPagoDesdeDatos($formulario, $json, $pago);
                        break;
                    }
                }
            }
        }

        $usuarioNombre = '';
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $usuarioNombre = trim((string)($_SESSION['usuario_nombre'] ?? ''));

        $this->view('talleres/pago', [

            'formulario' => $formulario,

            'respuesta' => $respuesta,

            'pagos' => $pagos,

            'total_pagado' => $totalPagado,

            'nombre_inscrito' => $nombreNino !== '' ? $nombreNino : $nombrePersona,

            'documento_inscrito' => trim((string)($json['nino_documento'] ?? $json['persona_documento'] ?? '')),

            'mensaje' => trim((string)($_GET['mensaje'] ?? '')),

            'tipo_mensaje' => trim((string)($_GET['tipo'] ?? '')),

            'referencia_pago' => $referenciaPago,

            'ticket_datos' => $ticketDatos,

            'usuario_nombre' => $usuarioNombre,

        ]);

    }



    public function guardarPago(): void {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header('Location: ' . PUBLIC_URL . '?url=talleres');

            exit;

        }

        $this->requiereAccesoRespuestas();

        $idRespuesta = (int)($_POST['id_respuesta'] ?? 0);

        $idFormulario = (int)($_POST['id_formulario'] ?? 0);

        $respuesta = $idRespuesta > 0 ? $this->model->getRespuestaPorId($idRespuesta) : null;

        if (!$respuesta || (int)($respuesta['Id_Formulario'] ?? 0) !== $idFormulario) {

            header('Location: ' . PUBLIC_URL . '?url=talleres');

            exit;

        }

        $formulario = $this->model->getById($idFormulario);

        $json = json_decode((string)($respuesta['Datos_JSON'] ?? '{}'), true);

        if (!is_array($json)) {
            $json = [];
        }

        $valor = (float)str_replace([',', ' '], ['.', ''], (string)($_POST['valor_pago'] ?? '0'));

        $metodo = trim((string)($_POST['metodo_pago'] ?? 'Efectivo'));

        $tipo = trim((string)($_POST['tipo_pago'] ?? 'completo'));

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $recibidoPor = trim((string)($_POST['recibido_por'] ?? ''));
        if ($recibidoPor === '') {
            $recibidoPor = trim((string)($_SESSION['usuario_nombre'] ?? ''));
        }

        if ($valor <= 0) {

            header('Location: ' . PUBLIC_URL . '?url=talleres/pago&id=' . $idFormulario . '&id_respuesta=' . $idRespuesta . '&tipo=error&mensaje=' . urlencode('Indique un valor de pago válido.'));

            exit;

        }

        $referenciaPago = $this->generarReferenciaCorta();

        $tipoNormalizado = in_array($tipo, ['completo', 'abono'], true) ? $tipo : 'completo';

        $this->model->registrarPago($idRespuesta, $idFormulario, [

            'metodo_pago' => $metodo !== '' ? $metodo : 'Efectivo',

            'tipo_pago' => $tipoNormalizado,

            'valor_pago' => $valor,

            'referencia_pago' => $referenciaPago,

            'recibido_por' => $recibidoPor,

        ]);

        $nombreNino = trim((string)($json['nino_nombre'] ?? $json['persona_nombre'] ?? ''));

        $tipoEtiqueta = $tipoNormalizado === 'abono' ? 'Abono' : 'Pago total';

        $_SESSION['talleres_ticket'] = [
            'fecha' => date('Y-m-d H:i'),
            'formulario' => (string)($formulario['Titulo'] ?? 'Taller'),
            'nombre' => $nombreNino,
            'documento' => trim((string)($json['nino_documento'] ?? $json['persona_documento'] ?? '')),
            'acudiente' => trim((string)($json['padres_nombre'] ?? '')),
            'telefono_acudiente' => trim((string)($json['padres_telefono'] ?? '')),
            'metodo_pago' => $metodo !== '' ? $metodo : 'Efectivo',
            'recibido_por' => $recibidoPor,
            'tipo_pago' => $tipoEtiqueta,
            'valor_pago' => number_format($valor, 0, ',', '.'),
            'referencia_pago' => $referenciaPago,
        ];

        header('Location: ' . PUBLIC_URL . '?url=talleres/pago&id=' . $idFormulario . '&id_respuesta=' . $idRespuesta
            . '&tipo=ok&mensaje=' . urlencode('Pago registrado correctamente.')
            . '&referencia_pago=' . urlencode($referenciaPago));

        exit;

    }



    public function guardarPublico(): void {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header('Location: ' . PUBLIC_URL . '?url=talleres_publico');

            exit;

        }



        $slug = trim((string)($_POST['slug'] ?? ''));

        $formulario = $slug !== '' ? $this->model->getBySlug($slug) : null;



        if (!$formulario || empty($formulario['Activo'])) {

            http_response_code(404);

            $this->view('talleres_publico/no_encontrado', ['slug' => $slug]);

            return;

        }



        $id = (int)($formulario['Id_Formulario'] ?? 0);

        $esPresentacionNinos = $this->model->esPresentacionNinos($formulario);

        $esTourLevantate = $this->model->esTourLevantate($formulario);

        if ($esPresentacionNinos
            && (string)($_POST['solo_subir_documentos'] ?? '') === '1'
            && (int)($_POST['id_respuesta_existente'] ?? 0) > 0
        ) {
            $this->subirDocumentosInscripcionExistente(
                $formulario,
                (int)$_POST['id_respuesta_existente'],
                $slug
            );
            return;
        }

        $campos = $this->model->getCamposPersonalizadosPorFormulario($id);

        $resultado = $this->model->validarRespuestaPublica($campos, $_POST);

        $authResult = $esTourLevantate
            ? ['ok' => true, 'errores' => [], 'datos' => []]
            : TallerAutorizacionSync::procesarDesdePost($_POST);



        if ($esPresentacionNinos) {

            $presentacionSync = new TallerPresentacionNinosSync();

            $padresResult = $presentacionSync->procesarPadresDesdePost($_POST);

            $ninoResult = $presentacionSync->procesarNinoDesdePost($_POST);

            $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
            if ($contentLength > 0 && $_POST === [] && empty($_FILES)) {
                $errores = ['Los archivos superan el límite del servidor. Suba menos archivos o reduzca el tamaño (máx. 8 MB por archivo).'];
            } else {
                $erroresDocs = TallerPresentacionDocumentos::validarUpload($_FILES[TallerPresentacionDocumentos::INPUT_NAME] ?? null);
                $errores = array_merge(
                    $padresResult['errores'],
                    $ninoResult['errores'],
                    $resultado['errores'],
                    $authResult['errores']
                );
                if ($erroresDocs !== []) {
                    $errores['documentos_presentacion'] = implode(' ', $erroresDocs);
                }
            }

            $idPersona = (int)($ninoResult['id_persona'] ?? 0);

            if ($idPersona <= 0) {

                $idPersona = (int)($padresResult['id_persona'] ?? 0);

            }

            $personaResult = ['id_persona' => $idPersona, 'extras' => []];

        } else if ($esTourLevantate) {

            $personaResult = $this->personaSync->procesarDesdePostConCreacion($_POST);

            $errores = array_merge($personaResult['errores'], $resultado['errores']);

            $idPersona = (int)($personaResult['id_persona'] ?? 0);

        } else {

            $personaResult = $this->personaSync->procesarDesdePost($_POST);

            $errores = array_merge($personaResult['errores'], $resultado['errores'], $authResult['errores']);

            $idPersona = (int)($personaResult['id_persona'] ?? 0);

        }

        if ($esPresentacionNinos) {
            $docNino = preg_replace('/\D+/', '', (string)(
                ($_POST['nino_documento'] ?? '')
                ?: ($_POST['buscar_nino_documento'] ?? '')
            ));
            if ($docNino !== '') {
                $inscripcionPrevia = $this->model->buscarInscripcionPorDocumentoNino($id, $docNino);
                if ($inscripcionPrevia) {
                    if (!is_array($errores ?? null)) {
                        $errores = [];
                    }
                    $fechaPrev = substr((string)($inscripcionPrevia['fecha'] ?? ''), 0, 10);
                    $errores['nino_documento'] = 'Este niño(a) ya está inscrito en este formulario'
                        . ($fechaPrev !== '' ? ' (registro del ' . $fechaPrev . ')' : '')
                        . '. Busque el documento del niño y use la sección Documentos para subir archivos.';
                }
            }
        }

        if ($esTourLevantate) {
            if (!is_array($errores ?? null)) {
                $errores = [];
            }
            $yaTieneLibro = trim((string)($_POST['ya_tiene_el_libro'] ?? ''));
            if ($yaTieneLibro === 'No, aún no tengo el libro') {
                $deseaComprar = trim((string)($_POST['desea_comprar_libro'] ?? ''));
                if ($deseaComprar === '') {
                    $errores['desea_comprar_libro'] = 'Indique si desea comprar el libro.';
                }
            }

            $docPersona = preg_replace('/\D+/', '', (string)(
                ($_POST['persona_documento'] ?? '')
                ?: ($_POST['buscar_documento'] ?? '')
            ));
            if ($docPersona !== '' || $idPersona > 0) {
                $inscripcionPrevia = $this->model->buscarInscripcionPorDocumentoPersona($id, $docPersona, $idPersona);
                if ($inscripcionPrevia) {
                    $fechaPrev = substr((string)($inscripcionPrevia['fecha'] ?? ''), 0, 10);
                    $nombrePrev = trim((string)($inscripcionPrevia['persona_nombre'] ?? ''));
                    $errores['persona_documento'] = 'Ya existe una inscripción con esta cédula en el Tour Levántate y Resplandece'
                        . ($fechaPrev !== '' ? ' (registro del ' . $fechaPrev . ')' : '')
                        . ($nombrePrev !== '' ? '. Nombre registrado: ' . $nombrePrev . '.' : '.');
                }
            }
        }



        if (!empty($errores)) {

            $_SESSION['talleres_publico_errores'] = $errores;

            $_SESSION['talleres_publico_valores'] = $_POST;

            header('Location: ' . PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug));

            exit;

        }



        if ($esPresentacionNinos) {

            $datos = array_merge(

                $resultado['datos'],

                $authResult['datos'],

                $padresResult['datos'] ?? [],

                $ninoResult['datos'] ?? []

            );

        } else {

            $datos = array_merge(
                $resultado['datos'],
                $authResult['datos'],
                is_array($personaResult['datos_persona'] ?? null) ? $personaResult['datos_persona'] : []
            );

            if (!empty($personaResult['extras'])) {

                $datos['_persona_extra'] = $personaResult['extras'];

            }

            $idPersona = (int)($personaResult['id_persona'] ?? 0);

        }



        $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));

        $idRespuesta = $this->model->guardarRespuesta(

            $id,

            $datos,

            $ip !== '' ? $ip : null,

            $idPersona

        );

        if ($esPresentacionNinos && $idRespuesta > 0) {
            try {
                $docService = new TallerPresentacionDocumentos();
                $docResult = $docService->adjuntarDesdeUpload(
                    $idRespuesta,
                    $_FILES[TallerPresentacionDocumentos::INPUT_NAME] ?? null
                );
                if (!empty($docResult['documentos'])) {
                    $this->model->agregarDocumentosAJsonRespuesta($idRespuesta, $docResult['documentos']);
                }
                if (!empty($docResult['errores'])) {
                    error_log('Taller presentación niños documentos: ' . implode(' ', $docResult['errores']));
                }
            } catch (Throwable $e) {
                error_log('Taller presentación niños documentos: ' . $e->getMessage());
            }
        }



        header('Location: ' . PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug) . '&ok=1');

        exit;

    }

    /**
     * @param array<string, mixed> $formulario
     */
    private function subirDocumentosInscripcionExistente(array $formulario, int $idRespuesta, string $slug): void {
        $idFormulario = (int)($formulario['Id_Formulario'] ?? 0);
        $idRespuesta = (int)$idRespuesta;

        $respuesta = $this->model->getRespuestaPorId($idRespuesta);
        if (!$respuesta || (int)($respuesta['Id_Formulario'] ?? 0) !== $idFormulario) {
            $_SESSION['talleres_publico_errores'] = [
                'documentos_presentacion' => 'No se encontró la inscripción indicada.',
            ];
            $_SESSION['talleres_publico_valores'] = $_POST;
            header('Location: ' . PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug));
            exit;
        }

        $json = json_decode((string)($respuesta['Datos_JSON'] ?? '{}'), true);
        if (!is_array($json)) {
            $json = [];
        }

        $docInscripcion = preg_replace('/\D+/', '', (string)($json['nino_documento'] ?? ''));
        $docPost = preg_replace('/\D+/', '', (string)(
            ($_POST['nino_documento'] ?? '')
            ?: ($_POST['buscar_nino_documento'] ?? '')
        ));
        if ($docInscripcion !== '' && $docPost !== '' && $docInscripcion !== $docPost) {
            $_SESSION['talleres_publico_errores'] = [
                'nino_documento' => 'El documento no coincide con la inscripción existente.',
            ];
            $_SESSION['talleres_publico_valores'] = $_POST;
            header('Location: ' . PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug));
            exit;
        }

        $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($contentLength > 0 && $_POST === [] && empty($_FILES)) {
            $_SESSION['talleres_publico_errores'] = [
                'documentos_presentacion' => 'Los archivos superan el límite del servidor. Suba menos archivos o reduzca el tamaño (máx. 8 MB por archivo).',
            ];
            $_SESSION['talleres_publico_valores'] = $_POST;
            header('Location: ' . PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug));
            exit;
        }

        $erroresDocs = TallerPresentacionDocumentos::validarUpload($_FILES[TallerPresentacionDocumentos::INPUT_NAME] ?? null);
        if ($erroresDocs !== []) {
            $_SESSION['talleres_publico_errores'] = [
                'documentos_presentacion' => implode(' ', $erroresDocs),
            ];
            $_SESSION['talleres_publico_valores'] = $_POST;
            header('Location: ' . PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug));
            exit;
        }

        if (!TallerPresentacionDocumentos::tieneArchivosEnUpload($_FILES[TallerPresentacionDocumentos::INPUT_NAME] ?? null)) {
            $_SESSION['talleres_publico_errores'] = [
                'documentos_presentacion' => 'Seleccione al menos un archivo para subir.',
            ];
            $_SESSION['talleres_publico_valores'] = $_POST;
            header('Location: ' . PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug));
            exit;
        }

        $existentes = TallerPresentacionDocumentos::obtenerParaRespuesta(
            $idRespuesta,
            $json['documentos_presentacion'] ?? []
        );

        try {
            $docService = new TallerPresentacionDocumentos();
            $docResult = $docService->adjuntarDesdeUpload(
                $idRespuesta,
                $_FILES[TallerPresentacionDocumentos::INPUT_NAME] ?? null,
                $existentes
            );
            if (empty($docResult['documentos'])) {
                $mensajeError = !empty($docResult['errores'])
                    ? implode(' ', $docResult['errores'])
                    : 'No se recibió ningún archivo válido.';
                $_SESSION['talleres_publico_errores'] = ['documentos_presentacion' => $mensajeError];
                $_SESSION['talleres_publico_valores'] = $_POST;
                header('Location: ' . PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug));
                exit;
            }

            $this->model->agregarDocumentosAJsonRespuesta($idRespuesta, $docResult['documentos']);
            if (!empty($docResult['errores'])) {
                error_log('Taller presentación niños documentos (inscripción existente): ' . implode(' ', $docResult['errores']));
            }
        } catch (Throwable $e) {
            $_SESSION['talleres_publico_errores'] = [
                'documentos_presentacion' => 'No se pudieron guardar los documentos. Intente de nuevo.',
            ];
            $_SESSION['talleres_publico_valores'] = $_POST;
            header('Location: ' . PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug));
            exit;
        }

        header('Location: ' . PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug) . '&ok=docs');
        exit;
    }



    public function qr(): void {

        $this->requierePermiso('gestionar_enlace', ['editar', 'ver']);

        $id = (int)($_GET['id'] ?? 0);

        $formulario = $id > 0 ? $this->model->getById($id) : null;

        if (!$formulario) {

            header('Location: ' . PUBLIC_URL . '?url=talleres');

            exit;

        }

        $this->renderQrFormulario($formulario);

    }



    public function qrPublico(): void {

        $slug = trim((string)($_GET['slug'] ?? ''));

        $formulario = $slug !== '' ? $this->model->getBySlug($slug) : null;

        if (!$formulario || empty($formulario['Activo'])) {

            http_response_code(404);

            $this->view('talleres_publico/no_encontrado', ['slug' => $slug]);

            return;

        }

        $this->renderQrFormulario($formulario);

    }



    /**

     * @param array<string, mixed> $formulario

     */

    private function renderQrFormulario(array $formulario): void {

        $slug = (string)($formulario['Slug'] ?? '');

        $urlRelativa = PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug);

        $this->view('talleres_publico/qr', [

            'formulario' => $formulario,

            'url_formulario' => $urlRelativa,

            'url_absoluta' => absolute_public_app_url($urlRelativa),

        ]);

    }

}

