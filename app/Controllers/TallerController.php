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

                'eliminar_inscripcion' => false,

                'asignar_ministerio' => false,

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

            'eliminar_inscripcion' => $this->puedeTaller('editar') || $this->puedeTaller('eliminar'),

            'asignar_ministerio' => $this->puedeTaller('editar') || $this->puedeTaller('eliminar'),

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

            'es_presentacion_ninos' => false,

            'acepta_pago' => true,

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

            if (in_array(($item['bloque']['Tipo'] ?? ''), ['persona', 'autorizacion', 'padres', 'nino', 'documentos'], true)) {

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

            'es_presentacion_ninos' => $this->model->esPresentacionNinos($formulario),

            'acepta_pago' => $this->model->aceptaPago($formulario),

        ]);

    }



    public function guardar(): void {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            $_SESSION['talleres_flash_error'] = 'No llegó la información del formulario. Ábralo de nuevo y pulse Guardar formulario.';

            header('Location: ' . public_app_url('talleres'));

            exit;

        }

        if ($_POST === []) {

            $_SESSION['talleres_flash_error'] = 'El servidor no recibió los datos. Recargue la página y vuelva a guardar.';

            $idVacio = (int)($_GET['id'] ?? 0);

            header('Location: ' . public_app_url($idVacio > 0 ? 'talleres/editar' : 'talleres/crear', $idVacio > 0 ? ['id' => $idVacio] : []));

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

        $mensajeInactivo = trim((string)($_POST['mensaje_inactivo'] ?? ''));

        $activo = ((string)($_POST['activo'] ?? '1') === '1') ? 1 : 0;



        if ($titulo === '') {

            $_SESSION['talleres_flash_error'] = 'Escriba el nombre del formulario.';

            header('Location: ' . PUBLIC_URL . '?url=talleres/' . ($esEdicion ? 'editar&id=' . $id : 'crear'));

            exit;

        }



        if ($this->model->tituloExiste($titulo, $esEdicion ? $id : 0)) {
            $_SESSION['talleres_flash_error'] = 'Ya existe un formulario con ese nombre. Edítelo y use «Nueva actividad» para la próxima fecha: el enlace y el QR no cambian.';
            header('Location: ' . PUBLIC_URL . '?url=talleres/' . ($esEdicion ? 'editar&id=' . $id : 'crear'));
            exit;
        }

        if ($esEdicion) {
            $actualFormulario = $this->model->getById($id);
            $slug = trim((string)($actualFormulario['Slug'] ?? ''));
            if ($slug === '') {
                $slug = $this->model->generarSlugUnico($titulo, $id);
            }
        } else {
            $slug = $this->model->generarSlugUnico($titulo);
        }



        $data = [

            'Titulo' => $titulo,

            'Slug' => $slug,

            'Descripcion' => $descripcion !== '' ? $descripcion : null,

            'Activo' => $activo,

            'Mensaje_Gracias' => $mensajeGracias !== '' ? $mensajeGracias : null,

            'Mensaje_Inactivo' => $mensajeInactivo !== '' ? $mensajeInactivo : null,

            'Texto_Autorizacion' => $textoAutorizacion !== '' ? $textoAutorizacion : null,

            'Acepta_Pago' => 1,

        ];



        try {

            if (!$esEdicion) {

                $idPersona = (int)($_SESSION['id_persona'] ?? 0);

                if ($idPersona > 0) {

                    $data['Creado_Por'] = $idPersona;

                }

            }

            $bloquesPost = $this->parsearBloquesDesdePost($_POST);

            $id = $this->model->guardarFormularioCompleto($esEdicion ? $id : 0, $data, $bloquesPost);
            if ($id > 0) {
                $this->model->asegurarAperturaActual($id);
            }

        } catch (Throwable $e) {

            error_log('No se pudo guardar el formulario de taller: ' . $e->getMessage());

            $detalle = trim((string)preg_replace('/\s+/', ' ', $e->getMessage()));

            if (strlen($detalle) > 180) {

                $detalle = substr($detalle, 0, 180) . '…';

            }

            $_SESSION['talleres_flash_error'] = 'No se pudo guardar el formulario.'

                . ($detalle !== '' ? ' ' . $detalle : ' Revise los bloques e intente de nuevo.');

            header('Location: ' . public_app_url($esEdicion && $id > 0 ? 'talleres/editar' : 'talleres/crear', $esEdicion && $id > 0 ? ['id' => $id] : []));

            exit;

        }

        $_SESSION['talleres_flash_ok'] = 'Formulario guardado correctamente.';

        header('Location: ' . public_app_url('talleres/editar', ['id' => $id]));

        exit;

    }



    public function cambiarEstado(): void {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header('Location: ' . PUBLIC_URL . '?url=talleres');

            exit;

        }

        $this->requierePermiso('editar');

        $id = (int)($_POST['id_formulario'] ?? 0);

        $formulario = $id > 0 ? $this->model->getById($id) : null;

        if (!$formulario) {

            $_SESSION['talleres_flash_error'] = 'No se encontró el formulario.';

            header('Location: ' . PUBLIC_URL . '?url=talleres');

            exit;

        }

        $activo = ((string)($_POST['activo'] ?? '0') === '1') ? 1 : 0;

        $this->model->update($id, ['Activo' => $activo]);

        $_SESSION['talleres_flash_ok'] = $activo === 1
            ? 'El formulario quedó activo. Quien tenga el enlace puede inscribirse.'
            : 'El formulario quedó cerrado. Quien tenga el enlace verá el mensaje configurado.';

        header('Location: ' . PUBLIC_URL . '?url=talleres');

        exit;

    }

    public function nuevaActividad(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . '?url=talleres');
            exit;
        }

        $this->requierePermiso('editar', ['crear']);

        $id = (int)($_POST['id_formulario'] ?? 0);
        $formulario = $id > 0 ? $this->model->getById($id) : null;
        if (!$formulario) {
            $_SESSION['talleres_flash_error'] = 'No se encontró el formulario.';
            header('Location: ' . PUBLIC_URL . '?url=talleres');
            exit;
        }

        $nombre = trim((string)($_POST['nombre_actividad'] ?? ''));
        $idApertura = $this->model->abrirNuevaActividad($id, $nombre);
        if ($idApertura <= 0) {
            $_SESSION['talleres_flash_error'] = 'No se pudo abrir la nueva actividad.';
            header('Location: ' . PUBLIC_URL . '?url=talleres/respuestas&id=' . $id);
            exit;
        }

        $this->model->update($id, ['Activo' => 1]);
        $nombreFinal = $nombre !== '' ? $nombre : ('Actividad ' . date('d/m/Y'));
        $_SESSION['talleres_flash_ok'] = 'Quedó abierta «' . $nombreFinal . '». El mismo enlace y el mismo QR sirven. Las inscripciones anteriores quedan en la actividad previa; la misma persona puede volver a inscribirse.';
        header('Location: ' . PUBLIC_URL . '?url=talleres/respuestas&id=' . $id . '&apertura=' . $idApertura);
        exit;
    }



    /**

     * @param array<string, mixed> $formulario
     */

    private function mostrarFormularioCerrado(array $formulario, string $slug): void {

        http_response_code(200);

        $mensaje = trim((string)($formulario['Mensaje_Inactivo'] ?? ''));

        if ($mensaje === '') {

            $mensaje = 'Este formulario ya no está recibiendo inscripciones. Gracias por su interés.';

        }

        $this->view('talleres_publico/cerrado', [

            'formulario' => $formulario,

            'slug' => $slug,

            'mensaje' => $mensaje,

        ]);

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



    public function eliminarRespuesta(): void {
        $this->requiereAccesoRespuestas();
        if (!$this->puedeTaller('editar') && !$this->puedeTaller('eliminar')) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        $idFormulario = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $idRespuesta = (int)($_POST['id_respuesta'] ?? $_GET['id_respuesta'] ?? 0);
        $respuesta = $idRespuesta > 0 ? $this->model->getRespuestaPorId($idRespuesta) : null;

        if (!$respuesta || (int)($respuesta['Id_Formulario'] ?? 0) !== $idFormulario) {
            $_SESSION['talleres_flash_error'] = 'No se encontró la inscripción a eliminar.';
            header('Location: ' . PUBLIC_URL . '?url=talleres/respuestas&id=' . $idFormulario);
            exit;
        }

        $this->model->eliminarRespuesta($idRespuesta);
        $dirDocs = TallerPresentacionDocumentos::directorioRespuesta($idRespuesta);
        if (is_dir($dirDocs)) {
            foreach (glob($dirDocs . '/*') ?: [] as $archivoDoc) {
                if (is_file($archivoDoc)) {
                    @unlink($archivoDoc);
                }
            }
            @rmdir($dirDocs);
        }

        $_SESSION['talleres_flash_ok'] = 'Inscripción eliminada. Esa persona ya puede volver a registrarse en este taller si hace falta.';
        header('Location: ' . PUBLIC_URL . '?url=talleres/respuestas&id=' . $idFormulario);
        exit;
    }

    public function asignarMinisterioInscripcion(): void {
        $this->requiereAccesoRespuestas();
        if (!$this->puedeTaller('editar') && !$this->puedeTaller('eliminar')) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        $idFormulario = (int)($_POST['id'] ?? 0);
        $idRespuesta = (int)($_POST['id_respuesta'] ?? 0);
        $idMinisterio = (int)($_POST['id_ministerio'] ?? 0);
        $tipoPersona = strtolower(trim((string)($_POST['tipo_persona'] ?? '')));
        $esAntiguo = in_array($tipoPersona, ['antiguo', 'antigua', '1'], true) ? 1 : 0;
        if (!in_array($tipoPersona, ['antiguo', 'antigua', 'nuevo', 'nueva', '1', '0'], true)) {
            $esAntiguo = 0;
        }

        $redirBase = PUBLIC_URL . '?url=talleres/respuestas&id=' . $idFormulario;

        if ($idMinisterio <= 0) {
            $_SESSION['talleres_flash_error'] = 'Elija el ministerio antes de asignar.';
            header('Location: ' . $redirBase . '&abrir=sin-ministerio');
            exit;
        }

        $formulario = $idFormulario > 0 ? $this->model->getById($idFormulario) : null;
        $respuesta = $idRespuesta > 0 ? $this->model->getRespuestaPorId($idRespuesta) : null;
        if (!$formulario || !$respuesta || (int)($respuesta['Id_Formulario'] ?? 0) !== $idFormulario) {
            $_SESSION['talleres_flash_error'] = 'No se encontró la inscripción.';
            header('Location: ' . $redirBase);
            exit;
        }

        if ($this->model->esPresentacionNinos($formulario)) {
            $_SESSION['talleres_flash_error'] = 'Esta asignación no aplica a presentación de niños.';
            header('Location: ' . $redirBase);
            exit;
        }

        require_once APP . '/Models/Ministerio.php';
        $ministerioModel = new Ministerio();
        $ministerio = $ministerioModel->getById($idMinisterio);
        $nombreMinisterio = trim((string)($ministerio['Nombre_Ministerio'] ?? ''));
        if ($nombreMinisterio === '') {
            $_SESSION['talleres_flash_error'] = 'El ministerio elegido no existe.';
            header('Location: ' . $redirBase . '&abrir=sin-ministerio');
            exit;
        }

        $json = $this->decodificarDatosJsonRespuesta($respuesta['Datos_JSON'] ?? null);

        $idPersona = (int)($respuesta['Id_Persona'] ?? 0);
        if ($idPersona <= 0) {
            $idPersona = $this->personaSync->asegurarPersonaDesdeJson($json, false);
        }
        if ($idPersona <= 0) {
            $datosCrear = $this->personaSync->jsonAPost($json);
            $datosCrear['id_ministerio'] = (string)$idMinisterio;
            header('Location: ' . $this->urlFormularioCrearPersona($datosCrear, $redirBase . '&abrir=sin-ministerio'));
            exit;
        }

        $this->personaSync->completarCamposVaciosDesdeJson($idPersona, $json);

        require_once APP . '/Models/Persona.php';
        $personaModel = new Persona();
        $personaModel->ensureEsAntiguoColumnExists();
        $datosPersona = ['Id_Ministerio' => $idMinisterio];
        if ($personaModel->tieneColumna('Es_Antiguo')) {
            $datosPersona['Es_Antiguo'] = $esAntiguo;
        }
        $actualizoPersona = (bool)$personaModel->update($idPersona, $datosPersona);
        if (!$actualizoPersona) {
            $_SESSION['talleres_flash_error'] = 'No se pudo guardar el ministerio en la ficha de la persona.';
            header('Location: ' . $redirBase . '&abrir=sin-ministerio');
            exit;
        }

        $this->model->vincularPersonaRespuesta($idRespuesta, $idPersona);

        $extras = is_array($json['_persona_extra'] ?? null) ? $json['_persona_extra'] : [];
        $json['persona_ministerio'] = $nombreMinisterio;
        $json['ministerio'] = $nombreMinisterio;
        $extras['persona_ministerio'] = $nombreMinisterio;
        $extras['Nombre_Ministerio'] = $nombreMinisterio;
        $json['_persona_extra'] = $extras;
        $json['_asignacion_ministerio'] = [
            'id_ministerio' => $idMinisterio,
            'nombre_ministerio' => $nombreMinisterio,
            'es_antiguo' => $esAntiguo,
            'fecha' => date('Y-m-d H:i:s'),
        ];
        $this->model->actualizarDatosJsonRespuesta($idRespuesta, $json);

        $etiquetaTipo = $esAntiguo === 1 ? 'antigua' : 'nueva';
        $_SESSION['talleres_flash_ok'] = 'Ya no está en Sin ministerio: pasó a ' . $nombreMinisterio . ' como persona ' . $etiquetaTipo . '.';
        $cacheAsig = [];
        $personaModelAsig = $personaModel;
        $nombreLider12 = $this->resolverNombreLider12Inscripcion($idPersona, $personaModelAsig, $cacheAsig);
        header('Location: ' . $redirBase . '&abrir_ministerio=' . rawurlencode($nombreMinisterio) . '&abrir_lider12=' . rawurlencode($nombreLider12) . '&resaltar=' . $idRespuesta);
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

        $this->model->asegurarAperturaActual($id);
        $aperturas = $this->model->getAperturasPorFormulario($id);
        $aperturaRaw = trim((string)($_GET['apertura'] ?? ''));
        $verTodasActividades = ($aperturaRaw === 'todas');
        $idAperturaVista = 0;
        if (!$verTodasActividades) {
            if ($aperturaRaw !== '' && ctype_digit($aperturaRaw)) {
                $pedida = $this->model->getAperturaPorId((int)$aperturaRaw, $id);
                if ($pedida) {
                    $idAperturaVista = (int)$pedida['Id_Apertura'];
                }
            }
            if ($idAperturaVista <= 0) {
                $activaVista = $this->model->getAperturaActiva($id);
                $idAperturaVista = (int)($activaVista['Id_Apertura'] ?? 0);
            }
        }
        $aperturaActual = $idAperturaVista > 0 ? $this->model->getAperturaPorId($idAperturaVista, $id) : null;

        $respuestasRaw = $this->model->getRespuestasPorFormulario($id, 5000, $verTodasActividades ? 0 : $idAperturaVista);

        $secciones = $soloGraficas ? [] : $this->buildSeccionesParaVista($id);

        $respuestas = [];

        $totalesPagos = [];

        if (!$soloGraficas) {

            $idsRespuesta = array_map(static fn(array $fila): int => (int)($fila['Id_Respuesta'] ?? 0), $respuestasRaw);

            $totalesPagos = $this->model->getTotalesPagosPorRespuestas($idsRespuesta);

            $personaModelResumen = null;
            $cachePersonasResumen = [];

            foreach ($respuestasRaw as $fila) {

                $json = $this->decodificarDatosJsonRespuesta($fila['Datos_JSON'] ?? null);

                $idRespuesta = (int)($fila['Id_Respuesta'] ?? 0);
                $idPersonaRespuesta = (int)($fila['Id_Persona'] ?? 0);

                $valores = $this->construirValoresRespuesta($campos, $json, $idPersonaRespuesta);

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

                    'id_persona' => $idPersonaRespuesta,

                    'ministerio' => $this->resolverMinisterioInscripcion(
                        $json,
                        $campos,
                        $idPersonaRespuesta,
                        $personaModelResumen,
                        $cachePersonasResumen
                    ),

                    'lider_12' => $this->resolverNombreLider12Inscripcion(
                        $idPersonaRespuesta,
                        $personaModelResumen,
                        $cachePersonasResumen
                    ),

                    'genero' => $this->clasificarGeneroInscripcionTaller(
                        $idPersonaRespuesta,
                        $json,
                        $valores,
                        $personaModelResumen,
                        $cachePersonasResumen
                    ),

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

        $resumenPorMinisterioLider12 = !$soloGraficas
            ? $this->construirResumenPorMinisterioLider12($respuestas)
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

            'acepta_pago' => $this->model->aceptaPago($formulario),

            'es_tour_levantate' => $esTourLevantate,

            'resumen_por_ministerio_lider12' => $resumenPorMinisterioLider12,

            'ministerios_asignacion' => $this->listarMinisteriosAsignacion(),

            'lista_pagos' => $soloGraficas ? [] : $this->model->getPagosPorFormulario($id, $verTodasActividades ? 0 : $idAperturaVista),

            'aperturas' => $aperturas,

            'apertura_id' => $idAperturaVista,

            'ver_todas_actividades' => $verTodasActividades,

            'apertura_actual' => $aperturaActual,

        ]);

    }



    /**
     * Agrupa inscripciones por ministerio y, dentro de cada uno, por líder de 12.
     *
     * @param array<int, array<string, mixed>> $respuestas
     * @return array<int, array{ministerio: string, hombres: int, mujeres: int, total: int, lideres: array<int, array{lider_12: string, hombres: int, mujeres: int, total: int}>}>
     */
    private function construirResumenPorMinisterioLider12(array $respuestas): array {
        $map = [];
        foreach ($respuestas as $fila) {
            $ministerio = trim((string)($fila['ministerio'] ?? ''));
            if ($ministerio === '') {
                $ministerio = 'Sin ministerio asignado';
            }
            $lider12 = trim((string)($fila['lider_12'] ?? ''));
            if ($lider12 === '') {
                $lider12 = 'Sin líder de 12';
            }
            $genero = (string)($fila['genero'] ?? '');

            if (!isset($map[$ministerio])) {
                $map[$ministerio] = [
                    'ministerio' => $ministerio,
                    'hombres' => 0,
                    'mujeres' => 0,
                    'total' => 0,
                    'lideres' => [],
                ];
            }
            if (!isset($map[$ministerio]['lideres'][$lider12])) {
                $map[$ministerio]['lideres'][$lider12] = [
                    'lider_12' => $lider12,
                    'hombres' => 0,
                    'mujeres' => 0,
                    'total' => 0,
                ];
            }
            if ($genero === 'hombre') {
                $map[$ministerio]['hombres']++;
                $map[$ministerio]['lideres'][$lider12]['hombres']++;
            } elseif ($genero === 'mujer') {
                $map[$ministerio]['mujeres']++;
                $map[$ministerio]['lideres'][$lider12]['mujeres']++;
            }
            $map[$ministerio]['total']++;
            $map[$ministerio]['lideres'][$lider12]['total']++;
        }

        $ordenarGrupo = static function (array $a, array $b, string $claveVacio, string $campoNombre): int {
            $aSin = strcasecmp((string)$a[$campoNombre], $claveVacio) === 0;
            $bSin = strcasecmp((string)$b[$campoNombre], $claveVacio) === 0;
            if ($aSin !== $bSin) {
                return $aSin ? 1 : -1;
            }
            $cmp = ((int)$b['total']) <=> ((int)$a['total']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcasecmp((string)$a[$campoNombre], (string)$b[$campoNombre]);
        };

        $filas = [];
        foreach ($map as $fila) {
            $lideres = array_values($fila['lideres']);
            usort($lideres, static function (array $a, array $b) use ($ordenarGrupo): int {
                return $ordenarGrupo($a, $b, 'Sin líder de 12', 'lider_12');
            });
            $fila['lideres'] = $lideres;
            $filas[] = $fila;
        }
        usort($filas, static function (array $a, array $b) use ($ordenarGrupo): int {
            return $ordenarGrupo($a, $b, 'Sin ministerio asignado', 'ministerio');
        });

        return $filas;
    }

    /**
     * @param object|null $personaModel
     * @param array<int, array<string, mixed>|null> $cachePersonas
     * @return array<string, mixed>|null
     */
    private function obtenerPersonaInscripcionCache(int $idPersona, &$personaModel, array &$cachePersonas): ?array {
        if ($idPersona <= 0) {
            return null;
        }
        if (!array_key_exists($idPersona, $cachePersonas)) {
            if ($personaModel === null) {
                require_once APP . '/Models/Persona.php';
                $personaModel = new Persona();
            }
            $persona = $personaModel->getById($idPersona);
            $cachePersonas[$idPersona] = is_array($persona) ? $persona : null;
        }
        $persona = $cachePersonas[$idPersona];
        return is_array($persona) ? $persona : null;
    }

    private function nombreCompletoPersonaTaller(array $persona): string {
        return trim(trim((string)($persona['Nombre'] ?? '')) . ' ' . trim((string)($persona['Apellido'] ?? '')));
    }

    /**
     * @param object|null $personaModel
     * @param array<int, array<string, mixed>|null> $cachePersonas
     */
    private function resolverNombreLider12Inscripcion(int $idPersona, &$personaModel, array &$cachePersonas): string {
        if ($idPersona <= 0) {
            return 'Sin líder de 12';
        }

        $visitados = [];
        $actualId = $idPersona;
        for ($i = 0; $i < 20 && $actualId > 0; $i++) {
            if (!empty($visitados[$actualId])) {
                break;
            }
            $visitados[$actualId] = true;

            $persona = $this->obtenerPersonaInscripcionCache($actualId, $personaModel, $cachePersonas);
            if ($persona === null) {
                break;
            }

            if ($personaModel === null) {
                require_once APP . '/Models/Persona.php';
                $personaModel = new Persona();
            }
            $jerarquia = (string)$personaModel->getJerarquiaByRol((int)($persona['Id_Rol'] ?? 0));
            if ($jerarquia === 'lider_12') {
                $nombre = $this->nombreCompletoPersonaTaller($persona);
                return $nombre !== '' ? $nombre : ('Líder de 12 #' . $actualId);
            }

            $siguiente = (int)($persona['Id_Lider'] ?? 0);
            if ($siguiente <= 0 || $siguiente === $actualId) {
                break;
            }
            $actualId = $siguiente;
        }

        return 'Sin líder de 12';
    }

    /**
     * @param array<string, mixed> $json
     * @param array<string, mixed> $valores
     * @param object|null $personaModel
     * @param array<int, array<string, mixed>|null> $cachePersonas
     */
    private function clasificarGeneroInscripcionTaller(
        int $idPersona,
        array $json,
        array $valores,
        &$personaModel,
        array &$cachePersonas
    ): string {
        $candidatos = [];
        $persona = $this->obtenerPersonaInscripcionCache($idPersona, $personaModel, $cachePersonas);
        if (is_array($persona)) {
            $candidatos[] = $persona['Genero'] ?? '';
        }

        $extras = is_array($json['_persona_extra'] ?? null) ? $json['_persona_extra'] : [];
        $claves = [
            'persona_genero', 'genero', 'sexo', 'persona_sexo',
            'nino_genero', 'nino_sexo', 'Genero',
        ];
        foreach ($claves as $clave) {
            $candidatos[] = $valores[$clave] ?? '';
            $candidatos[] = $json[$clave] ?? '';
            $candidatos[] = $extras[$clave] ?? '';
        }

        foreach ($valores as $clave => $valor) {
            $nombreClave = strtolower((string)$clave);
            if (strpos($nombreClave, 'genero') === false && strpos($nombreClave, 'sexo') === false) {
                continue;
            }
            $candidatos[] = $valor;
        }

        foreach ($candidatos as $candidato) {
            $genero = $this->normalizarGeneroTaller($candidato);
            if ($genero !== '') {
                return $genero;
            }
        }

        return '';
    }

    private function normalizarGeneroTaller($valor): string {
        $genero = strtolower(trim((string)$valor));
        if ($genero === '') {
            return '';
        }
        $genero = strtr($genero, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
        if (
            strpos($genero, 'mujer') !== false
            || strpos($genero, 'femen') !== false
            || in_array($genero, ['f', 'fem', 'female'], true)
        ) {
            return 'mujer';
        }
        if (
            strpos($genero, 'hombre') !== false
            || strpos($genero, 'mascul') !== false
            || in_array($genero, ['m', 'h', 'masc', 'male'], true)
        ) {
            return 'hombre';
        }

        return '';
    }

    /**
     * @return array<int, array{id: int, nombre: string}>
     */
    private function listarMinisteriosAsignacion(): array {
        require_once APP . '/Models/Ministerio.php';
        $filas = [];
        $todos = (new Ministerio())->getAll();
        if (!is_array($todos)) {
            return [];
        }
        foreach ($todos as $fila) {
            $id = (int)($fila['Id_Ministerio'] ?? 0);
            $nombre = trim((string)($fila['Nombre_Ministerio'] ?? ''));
            if ($id <= 0 || $nombre === '') {
                continue;
            }
            $filas[] = ['id' => $id, 'nombre' => $nombre];
        }
        usort($filas, static fn(array $a, array $b): int => strcasecmp($a['nombre'], $b['nombre']));
        return $filas;
    }

    /**
     * @param mixed $raw
     * @return array<string, mixed>
     */
    private function decodificarDatosJsonRespuesta($raw): array {
        if (is_array($raw)) {
            return $raw;
        }
        $texto = trim((string)$raw);
        if ($texto === '') {
            return [];
        }
        $json = json_decode($texto, true);
        return is_array($json) ? $json : [];
    }

    private function esNombreMinisterioVacio(string $nombre): bool {
        $texto = trim($nombre);
        if ($texto === '') {
            return true;
        }
        $normal = mb_strtolower($texto);
        $normal = strtr($normal, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
        $vacios = [
            'sin ministerio',
            'sin ministerio asignado',
            'ninguno',
            'ninguna',
            'n/a',
            'na',
            '-',
            'no',
            'no aplica',
        ];
        return in_array($normal, $vacios, true);
    }

    private function nombreMinisterioPorId(int $idMinisterio): string {
        if ($idMinisterio <= 0) {
            return '';
        }
        require_once APP . '/Models/Ministerio.php';
        $ministerio = (new Ministerio())->getById($idMinisterio);
        return is_array($ministerio) ? trim((string)($ministerio['Nombre_Ministerio'] ?? '')) : '';
    }

    /**
     * @param array<string, mixed> $json
     * @param array<int, array<string, mixed>> $campos
     * @param object|null $personaModel
     * @param array<int, array<string, mixed>|null> $cachePersonas
     */
    private function resolverMinisterioInscripcion(
        array $json,
        array $campos,
        int $idPersona,
        &$personaModel,
        array &$cachePersonas
    ): string {
        $asig = is_array($json['_asignacion_ministerio'] ?? null) ? $json['_asignacion_ministerio'] : [];
        $idAsig = (int)($asig['id_ministerio'] ?? 0);
        $nombreAsig = trim((string)($asig['nombre_ministerio'] ?? ''));
        if ($idAsig > 0) {
            $nombreDb = $this->nombreMinisterioPorId($idAsig);
            if ($nombreDb !== '') {
                return $nombreDb;
            }
            if ($nombreAsig !== '' && !$this->esNombreMinisterioVacio($nombreAsig)) {
                return $nombreAsig;
            }
        }
        if ($nombreAsig !== '' && !$this->esNombreMinisterioVacio($nombreAsig)) {
            return $nombreAsig;
        }

        if ($idPersona > 0) {
            if (!array_key_exists($idPersona, $cachePersonas)) {
                if ($personaModel === null) {
                    require_once APP . '/Models/Persona.php';
                    $personaModel = new Persona();
                }
                $persona = $personaModel->getById($idPersona);
                $cachePersonas[$idPersona] = is_array($persona) ? $persona : null;
            }
            $persona = $cachePersonas[$idPersona];
            if (is_array($persona)) {
                $idMinPersona = (int)($persona['Id_Ministerio'] ?? 0);
                $nombrePersona = trim((string)($persona['Nombre_Ministerio'] ?? ''));
                if ($idMinPersona > 0 && ($nombrePersona === '' || $this->esNombreMinisterioVacio($nombrePersona))) {
                    $nombrePersona = $this->nombreMinisterioPorId($idMinPersona);
                }
                if ($nombrePersona !== '' && !$this->esNombreMinisterioVacio($nombrePersona)) {
                    return $nombrePersona;
                }
            }
        }

        $ministerio = $this->extraerMinisterioDeRespuestaTaller($json, $campos);
        if ($ministerio !== '' && !$this->esNombreMinisterioVacio($ministerio)) {
            return $ministerio;
        }

        return 'Sin ministerio asignado';
    }

    /**
     * @param array<string, mixed> $json
     * @param array<int, array<string, mixed>> $campos
     */
    private function extraerMinisterioDeRespuestaTaller(array $json, array $campos): string {
        $extras = is_array($json['_persona_extra'] ?? null) ? $json['_persona_extra'] : [];
        $candidatos = [
            $json['persona_ministerio'] ?? '',
            $extras['persona_ministerio'] ?? '',
            $json['ministerio'] ?? '',
            $extras['ministerio'] ?? '',
            $extras['Nombre_Ministerio'] ?? '',
        ];

        foreach ($campos as $campo) {
            $nombreCampo = trim((string)($campo['Nombre_Campo'] ?? ''));
            if ($nombreCampo === '' || stripos($nombreCampo, 'ministerio') === false) {
                continue;
            }
            $valorCampo = $json[$nombreCampo] ?? '';
            if (is_array($valorCampo)) {
                $valorCampo = implode(', ', array_map('strval', $valorCampo));
            }
            $candidatos[] = $valorCampo;
        }

        foreach ($candidatos as $candidato) {
            $texto = trim((string)$candidato);
            if ($texto !== '' && !$this->esNombreMinisterioVacio($texto)) {
                return $texto;
            }
        }

        return '';
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

        $this->model->asegurarAperturaActual($id);
        $aperturaExport = trim((string)($_GET['apertura'] ?? ''));
        $idAperturaExport = 0;
        if ($aperturaExport === 'todas') {
            $idAperturaExport = 0;
        } elseif ($aperturaExport !== '' && ctype_digit($aperturaExport)) {
            $pedidaExp = $this->model->getAperturaPorId((int)$aperturaExport, $id);
            $idAperturaExport = (int)($pedidaExp['Id_Apertura'] ?? 0);
        } else {
            $activaExp = $this->model->getAperturaActiva($id);
            $idAperturaExport = (int)($activaExp['Id_Apertura'] ?? 0);
        }

        $respuestasRaw = $this->model->getRespuestasPorFormulario($id, 5000, $idAperturaExport);



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

        if (trim((string)($valores['persona_lider'] ?? '')) === '') {

            $valores['persona_lider'] = (string)($json['persona_lider'] ?? $extras['persona_lider'] ?? '');

        }

        if (trim((string)($valores['persona_ministerio'] ?? '')) === '') {

            $valores['persona_ministerio'] = (string)($json['persona_ministerio'] ?? $extras['persona_ministerio'] ?? '');

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



        if (!$formulario) {

            http_response_code(404);

            $this->view('talleres_publico/no_encontrado', ['slug' => $slug]);

            return;

        }

        if (empty($formulario['Activo'])) {

            $this->mostrarFormularioCerrado($formulario, $slug);

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

        $enlaceCrearPersona = (string)($_SESSION['talleres_enlace_crear_persona'] ?? '');

        unset($_SESSION['talleres_publico_errores'], $_SESSION['talleres_publico_valores'], $_SESSION['talleres_enlace_crear_persona']);



        $this->view('talleres_publico/formulario', [

            'formulario' => $formulario,

            'bloques' => $completo['bloques'],

            'es_presentacion_ninos' => $this->model->esPresentacionNinos($formulario),

            'es_tour_levantate' => $this->model->esTourLevantate($formulario),

            'estados_civiles' => TallerFormulario::ESTADOS_CIVILES,

            'texto_autorizacion' => $completo['texto_autorizacion'] ?? TallerAutorizacionSync::textoDefault(),

            'errores' => $errores,

            'valores' => $valores,

            'enlace_crear_persona' => $enlaceCrearPersona,

            'enviado_ok' => !empty($_GET['ok']),

        ]);

    }



    /**
     * Abre el formulario de personas con los datos que ya trae el taller.
     *
     * @param array<string, mixed> $campos
     */
    private function urlFormularioCrearPersona(array $campos, string $returnUrl): string {
        $nombre = trim((string)($campos['persona_nombre'] ?? $campos['nombre'] ?? ''));
        $apellido = trim((string)($campos['persona_apellido'] ?? $campos['apellido'] ?? ''));
        if ($apellido === '' && str_contains($nombre, ' ')) {
            $partes = preg_split('/\s+/', $nombre, 2) ?: [];
            $nombre = trim((string)($partes[0] ?? ''));
            $apellido = trim((string)($partes[1] ?? ''));
        }

        $query = [
            'url' => 'personas/crear',
            'return_to' => 'talleres',
            'return_url' => $returnUrl,
            'nombre' => $nombre,
            'apellido' => $apellido,
            'numero_documento' => preg_replace('/\D+/', '', (string)($campos['persona_documento'] ?? $campos['numero_documento'] ?? $campos['buscar_documento'] ?? '')),
            'telefono' => preg_replace('/\D+/', '', (string)($campos['persona_telefono'] ?? $campos['telefono'] ?? '')),
            'tipo_documento' => trim((string)($campos['persona_tipo_documento'] ?? $campos['tipo_documento'] ?? '')),
            'fecha_nacimiento' => trim((string)($campos['persona_fecha_nacimiento'] ?? $campos['fecha_nacimiento'] ?? '')),
            'direccion' => trim((string)($campos['persona_direccion'] ?? $campos['direccion'] ?? '')),
            'genero' => trim((string)($campos['persona_genero'] ?? $campos['genero'] ?? '')),
            'id_ministerio' => trim((string)($campos['id_ministerio'] ?? '')),
        ];
        $query = array_filter($query, static function ($valor) {
            return trim((string)$valor) !== '';
        });
        $query['url'] = 'personas/crear';
        $query['return_to'] = 'talleres';
        $query['return_url'] = $returnUrl;

        return PUBLIC_URL . '?' . http_build_query($query);
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

        if (is_array($formularioBusqueda) && empty($formularioBusqueda['Activo'])) {
            echo json_encode(['ok' => false, 'mensaje' => 'Este formulario está cerrado.'], JSON_UNESCAPED_UNICODE);
            return;
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
        $idAperturaActiva = $idFormulario > 0 ? $this->model->asegurarAperturaActual($idFormulario) : 0;
        if ($modo === 'nino' && $idFormulario > 0 && $documento !== '') {
            $inscripcionPrevia = $this->model->buscarInscripcionPorDocumentoNino($idFormulario, $documento, 0, $idAperturaActiva);
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
            && !in_array($modo, ['padres', 'nino'], true)
            && is_array($formularioBusqueda)
        ) {
            $idPersonaEncontrada = (int)($persona['id_persona'] ?? 0);
            $inscripcionPreviaPersona = $this->model->buscarInscripcionPorDocumentoPersona(
                $idFormulario,
                $documento,
                $idPersonaEncontrada,
                0,
                $idAperturaActiva
            );
            if ($inscripcionPreviaPersona) {
                echo json_encode([
                    'ok' => $persona !== null,
                    'persona' => $persona,
                    'ya_inscrito' => true,
                    'inscripcion' => $inscripcionPreviaPersona,
                    'mensaje' => $this->mensajeInscripcionDuplicadaTaller($formularioBusqueda, $inscripcionPreviaPersona),
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

    /**
     * @param array<string, mixed> $formulario
     * @param array<string, mixed> $inscripcion
     */
    private function mensajeInscripcionDuplicadaTaller(array $formulario, array $inscripcion): string {
        $titulo = trim((string)($formulario['Titulo'] ?? 'este taller'));
        if ($titulo === '') {
            $titulo = 'este taller';
        }
        $fechaPrev = substr((string)($inscripcion['fecha'] ?? ''), 0, 10);
        $nombrePrev = trim((string)($inscripcion['persona_nombre'] ?? ''));
        $mensaje = 'Ya está inscrito(a) en «' . $titulo . '». No puede registrarse otra vez con el mismo documento.';
        if ($fechaPrev !== '') {
            $mensaje .= ' Inscripción del ' . $fechaPrev . '.';
        }
        if ($nombrePrev !== '') {
            $mensaje .= ' Nombre registrado: ' . $nombrePrev . '.';
        }
        return $mensaje;
    }

    private function generarReferenciaCorta(): string {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $ref = 'T';
        for ($i = 0; $i < 5; $i++) {
            $ref .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $ref;
    }

    private function generarReferenciaPagoUnica(): string {
        for ($i = 0; $i < 20; $i++) {
            $ref = $this->generarReferenciaCorta();
            if ($this->model->getPagoPorReferencia($ref) === null) {
                return $ref;
            }
        }
        return $this->generarReferenciaCorta() . str_pad((string)random_int(0, 99), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Interpreta 50.000 o 50,000 como cincuenta mil (igual que Comunidad).
     */
    private function normalizarValorPago($valor): float {
        $raw = trim((string)$valor);
        if ($raw === '') {
            return 0.0;
        }
        $raw = str_replace([' ', "\u{00A0}"], '', $raw);
        $tieneComa = strpos($raw, ',') !== false;
        $tienePunto = strpos($raw, '.') !== false;
        if ($tieneComa && $tienePunto) {
            if ((int)strrpos($raw, ',') > (int)strrpos($raw, '.')) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        } elseif ($tieneComa) {
            $partes = explode(',', $raw);
            $raw = count($partes) === 2 && strlen(end($partes)) <= 2
                ? implode('.', $partes)
                : str_replace(',', '', $raw);
        } elseif ($tienePunto) {
            $partes = explode('.', $raw);
            if (count($partes) > 2 || (count($partes) === 2 && strlen(end($partes)) === 3 && strlen($partes[0]) <= 3)) {
                $raw = str_replace('.', '', $raw);
            }
        }
        return round((float)$raw, 2);
    }

    /**
     * @param array<string, mixed> $formulario
     * @param array<string, mixed> $json
     * @return array<string, mixed>
     */
    private function metadatosTicketPago(array $formulario, array $json): array {
        $titulo = trim((string)($formulario['Titulo'] ?? 'Taller'));
        $esPresentacion = $this->model->esPresentacionNinos($formulario);
        return [
            'etiqueta_nombre' => $esPresentacion ? 'Niño(a)' : 'Inscrito',
            'pie_comprobante' => 'Comprobante de pago — ' . ($titulo !== '' ? $titulo : 'Taller'),
            'nombre' => trim((string)($json['nino_nombre'] ?? $json['persona_nombre'] ?? '')),
            'documento' => trim((string)($json['nino_documento'] ?? $json['persona_documento'] ?? '')),
            'acudiente' => trim((string)($json['padres_nombre'] ?? '')),
        ];
    }

    private function construirTicketPagoDesdeDatos(array $formulario, array $json, array $pago): array {
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

        $meta = $this->metadatosTicketPago($formulario, $json);

        return [
            'fecha' => $fechaRegistro !== '' ? $fechaRegistro : date('Y-m-d H:i'),
            'formulario' => (string)($formulario['Titulo'] ?? 'Taller'),
            'nombre' => $meta['nombre'],
            'documento' => $meta['documento'],
            'acudiente' => $meta['acudiente'],
            'etiqueta_nombre' => $meta['etiqueta_nombre'],
            'pie_comprobante' => $meta['pie_comprobante'],
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
        $referencia = trim((string)($data['referencia_pago'] ?? ''));
        $pagoDb = $referencia !== '' ? $this->model->getPagoPorReferencia($referencia) : null;
        if ($pagoDb === null) {
            unset($_SESSION['talleres_ticket']);
            header('Location: ' . PUBLIC_URL . '?url=talleres&tipo=error&mensaje=' . urlencode('Ese ticket no tiene un pago guardado. No se puede mostrar.'));
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

        if (!$this->model->aceptaPago($formulario)) {

            $_SESSION['talleres_flash_error'] = 'Este formulario no tiene habilitado el registro de pagos. Actívelo al editar el enlace.';

            header('Location: ' . PUBLIC_URL . '?url=talleres/respuestas&id=' . $idFormulario);

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
            foreach ($pagos as $pago) {
                if (trim((string)($pago['Referencia_Pago'] ?? '')) === $referenciaPago) {
                    $ticketDatos = $this->construirTicketPagoDesdeDatos($formulario, $json, $pago);
                    break;
                }
            }
        }

        $mensaje = trim((string)($_GET['mensaje'] ?? ''));
        $tipoMensaje = trim((string)($_GET['tipo'] ?? ''));
        if ($tipoMensaje === 'ok' && $referenciaPago !== '' && $ticketDatos === null) {
            $tipoMensaje = 'error';
            $mensaje = 'Esa referencia no está guardada en el sistema. No se muestra ticket.';
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

            'mensaje' => $mensaje,

            'tipo_mensaje' => $tipoMensaje,

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
        // Registrar un pago modifica datos: no basta con poder ver las respuestas.
        $this->requierePermiso('editar', ['crear']);

        $idRespuesta = (int)($_POST['id_respuesta'] ?? 0);

        $idFormulario = (int)($_POST['id_formulario'] ?? 0);

        $respuesta = $idRespuesta > 0 ? $this->model->getRespuestaPorId($idRespuesta) : null;

        if (!$respuesta || (int)($respuesta['Id_Formulario'] ?? 0) !== $idFormulario) {

            header('Location: ' . PUBLIC_URL . '?url=talleres');

            exit;

        }

        $formulario = $this->model->getById($idFormulario);

        if (!$formulario || !$this->model->aceptaPago($formulario)) {

            $_SESSION['talleres_flash_error'] = 'Este formulario no tiene habilitado el registro de pagos.';

            header('Location: ' . PUBLIC_URL . '?url=talleres/respuestas&id=' . $idFormulario);

            exit;

        }

        $json = json_decode((string)($respuesta['Datos_JSON'] ?? '{}'), true);

        if (!is_array($json)) {
            $json = [];
        }

        $valor = $this->normalizarValorPago($_POST['valor_pago'] ?? '0');

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

        $referenciaPago = $this->generarReferenciaPagoUnica();

        $tipoNormalizado = in_array($tipo, ['completo', 'abono'], true) ? $tipo : 'completo';

        $idPago = $this->model->registrarPago($idRespuesta, $idFormulario, [

            'metodo_pago' => $metodo !== '' ? $metodo : 'Efectivo',

            'tipo_pago' => $tipoNormalizado,

            'valor_pago' => $valor,

            'referencia_pago' => $referenciaPago,

            'recibido_por' => $recibidoPor,

        ]);

        $pagoGuardado = $idPago > 0
            ? $this->model->getPagoPorRespuestaYReferencia($idRespuesta, $referenciaPago)
            : null;

        if ($idPago <= 0 || $pagoGuardado === null) {
            header('Location: ' . PUBLIC_URL . '?url=talleres/pago&id=' . $idFormulario . '&id_respuesta=' . $idRespuesta
                . '&tipo=error&mensaje=' . urlencode('No se pudo guardar el pago. No se generó ticket. Intente de nuevo.'));
            exit;
        }

        $_SESSION['talleres_ticket'] = $this->construirTicketPagoDesdeDatos($formulario, $json, $pagoGuardado);

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



        if (!$formulario) {

            http_response_code(404);

            $this->view('talleres_publico/no_encontrado', ['slug' => $slug]);

            return;

        }

        if (empty($formulario['Activo'])) {

            $this->mostrarFormularioCerrado($formulario, $slug);

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

            $personaResult = $this->personaSync->procesarDesdePost($_POST);

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
                $idAperturaGuardar = $this->model->asegurarAperturaActual($id);
                $inscripcionPrevia = $this->model->buscarInscripcionPorDocumentoNino($id, $docNino, 0, $idAperturaGuardar);
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
        }

        if (!$esPresentacionNinos && $idPersona <= 0 && empty($personaResult['errores'])) {
            if (!is_array($errores ?? null)) {
                $errores = [];
            }
            $errores['persona_documento'] = 'Esta persona no está en el registro. Créala en el formulario de personas y vuelve a buscarla por cédula.';
            $_SESSION['talleres_enlace_crear_persona'] = $this->urlFormularioCrearPersona(
                $_POST,
                PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug)
            );
        }

        if (!$esPresentacionNinos) {
            if (!is_array($errores ?? null)) {
                $errores = [];
            }
            $docPersona = preg_replace('/\D+/', '', (string)(
                ($_POST['persona_documento'] ?? '')
                ?: ($_POST['buscar_documento'] ?? '')
            ));
            if ($docPersona !== '' || $idPersona > 0) {
                $idAperturaGuardarPersona = $this->model->asegurarAperturaActual($id);
                $inscripcionPrevia = $this->model->buscarInscripcionPorDocumentoPersona($id, $docPersona, $idPersona, 0, $idAperturaGuardarPersona);
                if ($inscripcionPrevia) {
                    $errores['persona_documento'] = $this->mensajeInscripcionDuplicadaTaller($formulario, $inscripcionPrevia);
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
        if ($docInscripcion === '' || $docPost === '' || $docInscripcion !== $docPost) {
            $_SESSION['talleres_publico_errores'] = [
                'nino_documento' => 'Para subir documentos busque al niño por su documento. El número debe coincidir con la inscripción.',
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



    public function documentoPresentacion(): void {
        $this->requiereAccesoRespuestas();

        $idRespuesta = (int)($_GET['id_respuesta'] ?? 0);
        $archivo = basename(str_replace('\\', '/', (string)($_GET['archivo'] ?? '')));
        if ($idRespuesta <= 0 || $archivo === '' || str_contains($archivo, '..')) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            return;
        }

        $respuesta = $this->model->getRespuestaPorId($idRespuesta);
        if (!$respuesta) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            return;
        }

        $ruta = TallerPresentacionDocumentos::directorioRespuesta($idRespuesta) . DIRECTORY_SEPARATOR . $archivo;
        if (!is_file($ruta)) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            return;
        }

        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detectado = (string)@finfo_file($finfo, $ruta);
                @finfo_close($finfo);
                if ($detectado !== '') {
                    $mime = $detectado;
                }
            }
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . str_replace(['"', "\r", "\n"], '', $archivo) . '"');
        header('Content-Length: ' . (string)filesize($ruta));
        header('X-Content-Type-Options: nosniff');
        readfile($ruta);
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

        if (!$formulario) {

            http_response_code(404);

            $this->view('talleres_publico/no_encontrado', ['slug' => $slug]);

            return;

        }

        if (empty($formulario['Activo'])) {

            $this->mostrarFormularioCerrado($formulario, $slug);

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

