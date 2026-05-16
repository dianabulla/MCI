<?php

require_once APP . '/Controllers/BaseController.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Models/DiscipularEvaluacion.php';

class DiscipularEvaluacionController extends BaseController {
    private $model;
    private const MAX_INTENTOS = 2;
    private const MAX_SEGUNDOS_INTENTO = 1200;
    private const MODULO_CONFIG_FECHAS = 'discipular_evaluaciones_fechas';
    private const CONFIG_CAP_DESTINO = [
        1 => [1, 2],
        2 => [3, 4],
        3 => [5, 6],
    ];

    public function __construct() {
        $this->model = new DiscipularEvaluacion();
    }

    public function index() {
        if (!AuthController::estaAutenticado()) {
            $this->redirect('auth/login');
        }

        $esAdmin = AuthController::esAdministrador();
        $esDiscipulo = AuthController::esRolDiscipuloUsuario();
        $puedeVer = $esAdmin || $esDiscipulo || AuthController::tienePermiso('discipular_evaluaciones', 'ver');
        $puedeCrear = !$esDiscipulo && ($esAdmin || AuthController::tienePermiso('discipular_evaluaciones', 'crear'));
        $puedeEditar = !$esDiscipulo && ($esAdmin || AuthController::tienePermiso('discipular_evaluaciones', 'editar'));
        $puedeEliminar = !$esDiscipulo && ($esAdmin || AuthController::tienePermiso('discipular_evaluaciones', 'eliminar'));
        $puedeConfigurarFechas = $this->tienePermisoConfigurarFechas();
        $puedeGestionar = $puedeCrear || $puedeEditar || $puedeEliminar;

        if (!$puedeVer) {
            header('Location: ' . rtrim(BASE_URL, '/') . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $contextoMaterial = $this->obtenerContextoMaterialDesdeRequest();
        if (empty($contextoMaterial) && !$esDiscipulo) {
            $this->redirect('home/material/capacitacion-destino');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = trim((string)($_POST['accion'] ?? ''));

            if ($accion === 'crear_evaluacion' && $puedeCrear) {
                $this->procesarCrearEvaluacion();
            }

            if ($accion === 'desactivar_evaluacion' && ($puedeEliminar || $puedeEditar)) {
                $idEvaluacion = (int)($_POST['id_evaluacion'] ?? 0);
                if ($idEvaluacion > 0) {
                    $this->model->desactivarEvaluacion($idEvaluacion);
                }
                $this->redirigirConMensaje('Evaluación desactivada.', 'success');
            }

            if ($accion === 'configurar_fechas' && $puedeConfigurarFechas) {
                $this->procesarConfigurarFechas();
            }

            if ($accion === 'presentar_evaluacion' && $puedeVer) {
                $this->procesarPresentacion();
            }

            if ($accion === 'subir_tarea_entrega' && $esDiscipulo) {
                $this->procesarEntregaTareaDiscipulo();
            }
        }

        $idPersona = (int)($_SESSION['usuario_id'] ?? 0);
        $idEvaluacionSeleccionada = (int)($_GET['evaluacion'] ?? 0);
        $contextoDesdeMaterial = !empty($contextoMaterial);
        $filtroNivelContexto = (int)($contextoMaterial['nivel'] ?? 0);
        $filtroModuloContexto = (int)($contextoMaterial['modulo'] ?? 0);
        $mapaLecciones = $this->obtenerMapaLeccionesMaterial();
        $leccionesContexto = $this->obtenerLeccionesParaNivelModulo($mapaLecciones, $filtroNivelContexto, $filtroModuloContexto);
        $filtroLeccionContexto = $this->normalizarLeccionTexto($contextoMaterial['leccion'] ?? '');
        if (!empty($leccionesContexto) && !in_array($filtroLeccionContexto, $leccionesContexto, true)) {
            $filtroLeccionContexto = (string)$leccionesContexto[0];
        }
        $nivelesPermitidos = $this->obtenerNivelesPermitidosPorInscripcion($idPersona);

        $evaluaciones = $puedeGestionar
            ? $this->model->listarEvaluaciones()
            : $this->model->listarEvaluacionesActivas();

        if (!$puedeGestionar) {
            $evaluaciones = array_values(array_filter($evaluaciones, function($evaluacion) use ($nivelesPermitidos) {
                $nivel = (int)($evaluacion['Nivel'] ?? 0);
                return in_array($nivel, $nivelesPermitidos, true);
            }));

            $evaluaciones = array_values(array_filter($evaluaciones, function($evaluacion) {
                return $this->estaDisponiblePorFecha($evaluacion);
            }));
        }

        if ($filtroNivelContexto > 0 && $filtroModuloContexto > 0) {
            $evaluaciones = array_values(array_filter($evaluaciones, static function($evaluacion) use ($filtroNivelContexto, $filtroModuloContexto) {
                return (int)($evaluacion['Nivel'] ?? 0) === $filtroNivelContexto
                    && (int)($evaluacion['Modulo_Numero'] ?? 0) === $filtroModuloContexto;
            }));
        }

        $intentosPorEvaluacion = [];
        if (!$puedeGestionar && $idPersona > 0 && !empty($evaluaciones)) {
            $idsEvaluaciones = array_map(static function($evaluacion) {
                return (int)($evaluacion['Id_Evaluacion'] ?? 0);
            }, $evaluaciones);
            $intentosPorEvaluacion = $this->model->contarIntentosPorEvaluacionesPersona($idsEvaluaciones, $idPersona);
        }

        $evaluacionSeleccionada = null;
        $estadoIntento = [
            'intentos_realizados' => 0,
            'intentos_disponibles' => self::MAX_INTENTOS,
            'max_intentos' => self::MAX_INTENTOS,
            'tiempo_maximo_segundos' => self::MAX_SEGUNDOS_INTENTO,
            'tiempo_inicio' => 0,
            'tiempo_restante' => 0,
            'puede_responder' => false,
        ];

        if ($idEvaluacionSeleccionada > 0) {
            $evaluacionSeleccionada = $this->model->obtenerEvaluacion($idEvaluacionSeleccionada);
            if ($evaluacionSeleccionada && $filtroNivelContexto > 0 && $filtroModuloContexto > 0) {
                $nivelEval = (int)($evaluacionSeleccionada['Nivel'] ?? 0);
                $moduloEval = (int)($evaluacionSeleccionada['Modulo_Numero'] ?? 0);
                if ($nivelEval !== $filtroNivelContexto || $moduloEval !== $filtroModuloContexto) {
                    $evaluacionSeleccionada = null;
                }
            }
            if ($evaluacionSeleccionada && !$puedeGestionar) {
                $nivelEvaluacion = (int)($evaluacionSeleccionada['Nivel'] ?? 0);
                if (!in_array($nivelEvaluacion, $nivelesPermitidos, true)) {
                    $evaluacionSeleccionada = null;
                }
            }
            if ($evaluacionSeleccionada && !$puedeGestionar && (int)$evaluacionSeleccionada['Activa'] !== 1) {
                $evaluacionSeleccionada = null;
            }

            if ($evaluacionSeleccionada && !$puedeGestionar && !$this->estaDisponiblePorFecha($evaluacionSeleccionada)) {
                $evaluacionSeleccionada = null;
            }

            if ($evaluacionSeleccionada && $idPersona > 0) {
                $estadoIntento = $this->construirEstadoIntento((int)$evaluacionSeleccionada['Id_Evaluacion'], $idPersona);
            }
        }

        $resultadosUsuario = $idPersona > 0 ? $this->model->listarResultadosPorPersona($idPersona) : [];

        $resultadosEvaluacion = [];
        $resumenTodosResultados = [];
        if ($puedeGestionar) {
            if (empty($evaluacionSeleccionada)) {
                // Solo último intento por persona/evaluación y solo nivel·módulo del contexto (material).
                $resumenTodosResultados = $this->model->listarPresentacionesResumenUltimoIntento($filtroNivelContexto, $filtroModuloContexto);
            } else {
                // Una fila por persona: último intento presentado de esta evaluación.
                $resultadosEvaluacion = $this->model->listarUltimosResultadosPorEvaluacion((int)$evaluacionSeleccionada['Id_Evaluacion']);
            }
        }

        $resultadoDetalle = null;
        $idResultadoDetalle = (int)($_GET['resultado'] ?? 0);
        if ($idResultadoDetalle > 0) {
            $resultadoTmp = $this->model->obtenerResultadoPorId($idResultadoDetalle);
            if ($resultadoTmp) {
                $idPersonaResultado = (int)($resultadoTmp['Id_Persona'] ?? 0);
                $esPropio = $idPersona > 0 && $idPersonaResultado === $idPersona;
                if ($puedeGestionar || $esPropio) {
                    $resultadoDetalle = $resultadoTmp;
                }
            }
        }

        $accesosDirectosDiscipulo = $esDiscipulo
            ? $this->construirAccesosDirectosDiscipulo($nivelesPermitidos, $evaluaciones)
            : [];

        $tareasPorModuloDiscipulo = [];
        if ($esDiscipulo && $idPersona > 0 && !empty($accesosDirectosDiscipulo)) {
            $modulosPermitidos = [];
            foreach ($accesosDirectosDiscipulo as $accesoTmp) {
                $nivelTmp = (int)($accesoTmp['nivel'] ?? 0);
                $moduloTmp = (int)($accesoTmp['modulo'] ?? 0);
                if ($nivelTmp <= 0 || $moduloTmp <= 0) {
                    continue;
                }
                $modulosPermitidos[$nivelTmp . '_' . $moduloTmp] = ['nivel' => $nivelTmp, 'modulo' => $moduloTmp];
            }
            $tareasPorModuloDiscipulo = $this->listarTareasActivasPorModulosDiscipulo($idPersona, array_values($modulosPermitidos));
        }

        $resumenCapacitacionPorNivel = [];
        if ($puedeGestionar) {
            $resumenFilas = $this->model->listarResumenPorNivelCapacitacionDestino();
            foreach ($resumenFilas as $filaResumen) {
                $nivelResumen = (int)($filaResumen['Nivel'] ?? 0);
                if (!isset($resumenCapacitacionPorNivel[$nivelResumen])) {
                    $resumenCapacitacionPorNivel[$nivelResumen] = [
                        'nivel' => $nivelResumen,
                        'aprobados' => [],
                        'reprobados' => [],
                    ];
                }

                if (!empty($filaResumen['Aprobado'])) {
                    $resumenCapacitacionPorNivel[$nivelResumen]['aprobados'][] = $filaResumen;
                } else {
                    $resumenCapacitacionPorNivel[$nivelResumen]['reprobados'][] = $filaResumen;
                }
            }

            if (!empty($resumenCapacitacionPorNivel)) {
                ksort($resumenCapacitacionPorNivel);
                $resumenCapacitacionPorNivel = array_values($resumenCapacitacionPorNivel);
            }
        }

        $this->view('programas/evaluaciones', [
            'pageTitle' => 'Discipular - Evaluaciones',
            'es_admin' => $esAdmin,
            'es_discipulo' => $esDiscipulo,
            'puede_gestionar' => $puedeGestionar,
            'evaluaciones' => $evaluaciones,
            'evaluacion_seleccionada' => $evaluacionSeleccionada,
            'resultados_usuario' => $resultadosUsuario,
            'resultados_evaluacion' => $resultadosEvaluacion,
            'resumen_todos_resultados' => $resumenTodosResultados,
            'resultado_detalle' => $resultadoDetalle,
            'resumen_capacitacion_por_nivel' => $resumenCapacitacionPorNivel,
            'estado_intento' => $estadoIntento,
            'niveles_permitidos' => $nivelesPermitidos,
            'clases_links' => $this->construirLinksClases($nivelesPermitidos),
            'accesos_directos_discipulo' => $accesosDirectosDiscipulo,
            'tareas_por_modulo_discipulo' => $tareasPorModuloDiscipulo,
            'intentos_por_evaluacion' => $intentosPorEvaluacion,
            'max_intentos' => self::MAX_INTENTOS,
            'puede_configurar_fechas' => $puedeConfigurarFechas,
            'filtro_nivel_contexto' => $filtroNivelContexto,
            'filtro_modulo_contexto' => $filtroModuloContexto,
            'filtro_leccion_contexto' => $filtroLeccionContexto,
            'contexto_desde_material' => $contextoDesdeMaterial,
            'lecciones_por_nivel_modulo' => $mapaLecciones,
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'tipo' => (string)($_GET['tipo'] ?? ''),
        ]);
    }

    public function tareas(): void {
        if (!AuthController::estaAutenticado()) {
            $this->redirect('auth/login');
        }

        $esDiscipulo = AuthController::esRolDiscipuloUsuario();
        if (!$esDiscipulo) {
            header('Location: ' . rtrim(BASE_URL, '/') . '/public/?url=auth/acceso-denegado');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = trim((string)($_POST['accion'] ?? ''));
            if ($accion === 'subir_tarea_entrega') {
                $this->procesarEntregaTareaDiscipulo();
            } elseif ($accion === 'editar_tarea_entrega') {
                $this->procesarEditarEntregaTareaDiscipulo();
            } elseif ($accion === 'eliminar_tarea_entrega') {
                $this->procesarEliminarEntregaTareaDiscipulo();
            }
        }

        $idPersona = (int)($_SESSION['usuario_id'] ?? 0);
        $nivelesPermitidos = $this->obtenerNivelesPermitidosPorInscripcion($idPersona);
        $evaluacionesActivas = $this->model->listarEvaluacionesActivas();
        $evaluacionesActivas = array_values(array_filter($evaluacionesActivas, static function($evaluacion) use ($nivelesPermitidos) {
            $nivel = (int)($evaluacion['Nivel'] ?? 0);
            return in_array($nivel, $nivelesPermitidos, true);
        }));
        $evaluacionesActivas = array_values(array_filter($evaluacionesActivas, function($evaluacion) {
            return $this->estaDisponiblePorFecha($evaluacion);
        }));

        $accesosDirectosDiscipulo = $this->construirAccesosDirectosDiscipulo($nivelesPermitidos, $evaluacionesActivas);

        $modulosPermitidos = [];
        foreach ($accesosDirectosDiscipulo as $accesoTmp) {
            $nivelTmp = (int)($accesoTmp['nivel'] ?? 0);
            $moduloTmp = (int)($accesoTmp['modulo'] ?? 0);
            if ($nivelTmp <= 0 || $moduloTmp <= 0) {
                continue;
            }
            $modulosPermitidos[$nivelTmp . '_' . $moduloTmp] = ['nivel' => $nivelTmp, 'modulo' => $moduloTmp];
        }

        $tareasPorModuloDiscipulo = $this->listarTareasActivasPorModulosDiscipulo($idPersona, array_values($modulosPermitidos));

        $filtroNivel = (int)($_GET['nivel'] ?? 0);
        $filtroModulo = (int)($_GET['modulo'] ?? 0);
        if ($filtroNivel > 0 && $filtroModulo > 0) {
            $accesosDirectosDiscipulo = array_values(array_filter($accesosDirectosDiscipulo, static function($acceso) use ($filtroNivel, $filtroModulo) {
                return (int)($acceso['nivel'] ?? 0) === $filtroNivel && (int)($acceso['modulo'] ?? 0) === $filtroModulo;
            }));
        }

        $this->view('programas/tareas', [
            'pageTitle' => 'Discipular - Tareas',
            'accesos_directos_discipulo' => $accesosDirectosDiscipulo,
            'tareas_por_modulo_discipulo' => $tareasPorModuloDiscipulo,
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'tipo' => (string)($_GET['tipo'] ?? ''),
            'filtro_nivel' => $filtroNivel,
            'filtro_modulo' => $filtroModulo,
        ]);
    }

    public function irClase(): void {
        if (!AuthController::estaAutenticado()) {
            $this->redirect('auth/login');
        }

        $esDiscipulo = AuthController::esRolDiscipuloUsuario();
        if (!$esDiscipulo) {
            header('Location: ' . rtrim(BASE_URL, '/') . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $idPersona = (int)($_SESSION['usuario_id'] ?? 0);
        $nivel = (int)($_GET['nivel'] ?? 0);
        $modulo = (int)($_GET['modulo'] ?? 0);

        if ($idPersona <= 0 || !$this->esContextoNivelModuloValido($nivel, $modulo)) {
            $this->redirect('programas/evaluaciones&tipo=error&mensaje=' . urlencode('Acceso a clase inválido.'));
        }

        $nivelesPermitidos = $this->obtenerNivelesPermitidosPorInscripcion($idPersona);
        if (!in_array($nivel, $nivelesPermitidos, true)) {
            $this->redirect('programas/evaluaciones&tipo=error&mensaje=' . urlencode('No tienes acceso a este nivel de clase.'));
        }

        $urlClase = '';
        $conexiones = (array)$this->model->listarConexionesClaseCapacitacionDestino();
        foreach ($conexiones as $fila) {
            $nivelFila = (int)($fila['Nivel'] ?? 0);
            $moduloFila = (int)($fila['Modulo_Numero'] ?? 0);
            if ($nivelFila !== $nivel || $moduloFila !== $modulo) {
                continue;
            }

            $urlClase = $this->normalizarUrlClase($fila['Conexion_Zoom_URL'] ?? '');
            if ($urlClase !== '') {
                break;
            }
        }

        // Compatibilidad: si no hay link exacto del módulo, usa el primero del mismo nivel.
        if ($urlClase === '') {
            foreach ($conexiones as $fila) {
                $nivelFila = (int)($fila['Nivel'] ?? 0);
                if ($nivelFila !== $nivel) {
                    continue;
                }

                $urlClase = $this->normalizarUrlClase($fila['Conexion_Zoom_URL'] ?? '');
                if ($urlClase !== '') {
                    break;
                }
            }
        }

        if ($urlClase === '') {
            $this->redirect('programas/evaluaciones&tipo=error&mensaje=' . urlencode('Este módulo aún no tiene link de clase configurado.'));
        }

        $this->registrarAsistenciaClaseDiscipulo($idPersona, $nivel);

        header('Location: ' . $urlClase);
        exit;
    }

    private function esContextoNivelModuloValido(int $nivel, int $modulo): bool {
        if ($nivel <= 0 || $modulo <= 0) {
            return false;
        }

        $permitidos = self::CONFIG_CAP_DESTINO[$nivel] ?? [];
        return in_array($modulo, $permitidos, true);
    }

    private function procesarCrearEvaluacion(): void {
        $titulo = trim((string)($_POST['titulo'] ?? ''));
        $descripcion = trim((string)($_POST['descripcion'] ?? ''));
        $nivel = (int)($_POST['nivel'] ?? 0);
        $moduloNumero = (int)($_POST['modulo_numero'] ?? 0);
        $leccion = $this->normalizarLeccionTexto($_POST['leccion'] ?? '');
        $puntajeMinimo = max(80.0, (float)($_POST['puntaje_minimo'] ?? 80));
        $esAutoSave = !empty($_POST['auto_save']);
        // Forzar solo preguntas cerradas en CD
        $modoRespuestas = 'cerrada';
        $preguntasRaw = $this->extraerPreguntasDesdeRequest();
        $puedeConfigurarFechas = $this->tienePermisoConfigurarFechas();

        $fechaHabilitacionInicio = '';
        $fechaHabilitacionFin = '';
        if ($puedeConfigurarFechas) {
            $fechaHabilitacionInicio = $this->normalizarFechaYmd($_POST['fecha_habilitacion_inicio'] ?? '');
            $fechaHabilitacionFin = $this->normalizarFechaYmd($_POST['fecha_habilitacion_fin'] ?? '');

            if ($fechaHabilitacionInicio !== '' && $fechaHabilitacionFin !== '' && strcmp($fechaHabilitacionInicio, $fechaHabilitacionFin) > 0) {
                if ($esAutoSave) {
                    echo 'error: La fecha inicial no puede ser mayor que la final.';
                    return;
                }
                $this->redirigirConMensaje('La fecha inicial no puede ser mayor que la final.', 'error');
            }
        }

        // Asegura una ventana de publicación válida para que el discípulo
        // no quede sin ver la evaluación por fechas vacías.
        if ($fechaHabilitacionInicio === '' && $fechaHabilitacionFin === '') {
            $hoy = date('Y-m-d');
            $fechaHabilitacionInicio = $hoy;
            $fechaHabilitacionFin = $hoy;
        } elseif ($fechaHabilitacionInicio === '' && $fechaHabilitacionFin !== '') {
            $fechaHabilitacionInicio = $fechaHabilitacionFin;
        } elseif ($fechaHabilitacionFin === '' && $fechaHabilitacionInicio !== '') {
            $fechaHabilitacionFin = $fechaHabilitacionInicio;
        }

        if ($titulo === '' || $nivel <= 0 || $moduloNumero <= 0) {
            if ($esAutoSave) {
                echo 'error: Completa título, nivel y módulo.';
                return;
            }
            $this->redirigirConMensaje('Completa título, nivel y módulo.', 'error');
        }

        $mapaLecciones = $this->obtenerMapaLeccionesMaterial();
        $leccionesDisponibles = $this->obtenerLeccionesParaNivelModulo($mapaLecciones, $nivel, $moduloNumero);
        if (!empty($leccionesDisponibles) && !in_array($leccion, $leccionesDisponibles, true)) {
            if ($esAutoSave) {
                echo 'error: Selecciona una lección válida del material cargado para ese nivel y módulo.';
                return;
            }
            $this->redirigirConMensaje('Selecciona una lección válida del material cargado para ese nivel y módulo.', 'error');
        }

        $preguntas = $this->normalizarPreguntas((array)$preguntasRaw, $modoRespuestas);
        // Ya no se requiere mínimo de preguntas
        if (count($preguntas) < 1) {
            if ($esAutoSave) {
                echo 'error: La evaluación debe tener al menos 1 pregunta cerrada.';
                return;
            }
            $this->redirigirConMensaje('La evaluación debe tener al menos 1 pregunta cerrada.', 'error');
        }

        if ($modoRespuestas === 'mixta') {
            $tieneAbiertas = false;
            $tieneCerradas = false;
            foreach ($preguntas as $pregunta) {
                $tipoPregunta = strtolower(trim((string)($pregunta['tipo'] ?? '')));
                if ($tipoPregunta === 'abierta') {
                    $tieneAbiertas = true;
                }
                if ($tipoPregunta === 'cerrada') {
                    $tieneCerradas = true;
                }
            }

            if (!$tieneAbiertas || !$tieneCerradas) {
                if ($esAutoSave) {
                    echo 'error: En modo mixto debes incluir al menos una pregunta abierta y una cerrada.';
                    return;
                }
                $this->redirigirConMensaje('En modo mixto debes incluir al menos una pregunta abierta y una cerrada.', 'error');
            }
        }

        $this->model->crearEvaluacion([
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'nivel' => $nivel,
            'modulo_numero' => $moduloNumero,
            'leccion' => $leccion,
            'puntaje_minimo' => $puntajeMinimo,
            'preguntas_json' => json_encode($preguntas, JSON_UNESCAPED_UNICODE),
            'fecha_habilitacion_inicio' => $fechaHabilitacionInicio !== '' ? $fechaHabilitacionInicio : null,
            'fecha_habilitacion_fin' => $fechaHabilitacionFin !== '' ? $fechaHabilitacionFin : null,
            'creado_por' => (int)($_SESSION['usuario_id'] ?? 0),
        ]);

        if ($esAutoSave) {
            echo 'success: Evaluación guardada automáticamente.';
            return;
        }

        $this->redirigirConMensaje('Evaluación creada correctamente.', 'success');
    }

    private function normalizarPreguntas(array $preguntasRaw, string $modoRespuestas = 'mixta'): array {
        $salida = [];
        // Solo permitir preguntas cerradas
        $modo = 'cerrada';

        foreach ($preguntasRaw as $pregunta) {
            $enunciado = trim((string)($pregunta['enunciado'] ?? ''));
            if ($enunciado === '') {
                continue;
            }
            $opciones = [];
            foreach (['a', 'b', 'c', 'd'] as $clave) {
                $texto = trim((string)($pregunta['opcion_' . $clave] ?? ''));
                if ($texto !== '') {
                    $opciones[$clave] = $texto;
                }
            }
            if (count($opciones) < 2) {
                continue;
            }
            $salida[] = [
                'tipo' => 'cerrada',
                'enunciado' => $enunciado,
                'opciones' => $opciones,
                'respuesta_correcta' => $this->normalizarRespuestaCorrecta($pregunta, $opciones),
            ];
        }
        return $salida;
    }

    private function extraerPreguntasDesdeRequest(): array {
        $preguntasRaw = $_POST['preguntas'] ?? [];

        if (is_string($preguntasRaw)) {
            $decodificado = json_decode($preguntasRaw, true);
            if (is_array($decodificado)) {
                return $this->normalizarEstructuraPreguntasEntrada($decodificado);
            }
        }

        if (is_array($preguntasRaw) && !empty($preguntasRaw)) {
            return $this->normalizarEstructuraPreguntasEntrada($preguntasRaw);
        }

        // Compatibilidad con el formulario de preguntas dinámicas (arrays paralelos).
        $enunciados = (array)($_POST['pregunta_enunciado'] ?? []);
        $opcionesPlanas = (array)($_POST['pregunta_opciones'] ?? []);
        $respuestas = (array)($_POST['pregunta_correcta'] ?? []);

        $salida = [];
        foreach ($enunciados as $i => $enunciadoRaw) {
            $enunciado = trim((string)$enunciadoRaw);
            if ($enunciado === '') {
                continue;
            }

            $base = ((int)$i) * 4;
            $salida[] = [
                'enunciado' => $enunciado,
                'opcion_a' => trim((string)($opcionesPlanas[$base] ?? '')),
                'opcion_b' => trim((string)($opcionesPlanas[$base + 1] ?? '')),
                'opcion_c' => trim((string)($opcionesPlanas[$base + 2] ?? '')),
                'opcion_d' => trim((string)($opcionesPlanas[$base + 3] ?? '')),
                'respuesta_correcta' => strtolower(trim((string)($respuestas[$i] ?? ''))),
            ];
        }

        return $salida;
    }

    private function normalizarEstructuraPreguntasEntrada(array $preguntas): array {
        $salida = [];

        foreach ($preguntas as $pregunta) {
            if (!is_array($pregunta)) {
                continue;
            }

            $enunciado = trim((string)($pregunta['enunciado'] ?? ''));
            if ($enunciado === '') {
                continue;
            }

            $item = [
                'enunciado' => $enunciado,
                'opcion_a' => trim((string)($pregunta['opcion_a'] ?? '')),
                'opcion_b' => trim((string)($pregunta['opcion_b'] ?? '')),
                'opcion_c' => trim((string)($pregunta['opcion_c'] ?? '')),
                'opcion_d' => trim((string)($pregunta['opcion_d'] ?? '')),
                'respuesta_correcta' => strtolower(trim((string)($pregunta['respuesta_correcta'] ?? ''))),
            ];

            $opcionesEntrada = $pregunta['opciones'] ?? null;
            if (is_array($opcionesEntrada)) {
                foreach ($opcionesEntrada as $clave => $valor) {
                    $claveNorm = '';
                    $texto = '';

                    if (is_array($valor)) {
                        $claveNorm = strtolower(trim((string)($valor['clave'] ?? $clave)));
                        $texto = trim((string)($valor['opcion'] ?? $valor['texto'] ?? ''));
                    } else {
                        $claveNorm = strtolower(trim((string)$clave));
                        $texto = trim((string)$valor);
                    }

                    if (!in_array($claveNorm, ['a', 'b', 'c', 'd'], true)) {
                        continue;
                    }

                    $item['opcion_' . $claveNorm] = $texto;
                }
            }

            $salida[] = $item;
        }

        return $salida;
    }

    private function normalizarRespuestaCorrecta(array $preguntaRaw, array $opciones): string {
        $clave = strtolower(trim((string)($preguntaRaw['respuesta_correcta'] ?? '')));
        
        // Validar que la respuesta correcta sea una opción válida
        if ($clave !== '' && isset($opciones[$clave])) {
            return $clave;
        }

        // Fallback: usar primera opción disponible (solo para compatibilidad)
        $primera = array_key_first($opciones);
        $fallback = is_string($primera) ? strtolower($primera) : 'a';
        
        // Log: detectar si falta respuesta correcta
        if ($clave === '') {
            error_log('⚠️ ADVERTENCIA: Pregunta sin respuesta_correcta. Usando fallback: ' . $fallback);
        } elseif (!isset($opciones[$clave])) {
            error_log('⚠️ ADVERTENCIA: Respuesta_correcta "' . $clave . '" no existe en opciones. Usando fallback: ' . $fallback);
        }
        
        return $fallback;
    }

    private function procesarPresentacion(): void {
        $idEvaluacion = (int)($_POST['id_evaluacion'] ?? 0);
        $idPersona = (int)($_SESSION['usuario_id'] ?? 0);

        if ($idEvaluacion <= 0 || $idPersona <= 0) {
            $this->redirigirConMensaje('No fue posible registrar tu evaluación.', 'error');
        }

        $evaluacion = $this->model->obtenerEvaluacion($idEvaluacion);
        if (!$evaluacion || (int)($evaluacion['Activa'] ?? 0) !== 1) {
            $this->redirigirConMensaje('La evaluación no está disponible.', 'error', $idEvaluacion);
        }

        if (!$this->estaDisponiblePorFecha($evaluacion)) {
            $this->redirigirConMensaje('La evaluación está fuera del rango de fechas habilitado.', 'error', $idEvaluacion);
        }

        $nivelesPermitidos = $this->obtenerNivelesPermitidosPorInscripcion($idPersona);
        $nivelEvaluacion = (int)($evaluacion['Nivel'] ?? 0);
        if (!in_array($nivelEvaluacion, $nivelesPermitidos, true)) {
            $this->redirigirConMensaje('No tienes acceso a esta evaluación.', 'error', $idEvaluacion);
        }

        $intentosRealizados = $this->model->contarIntentosPersonaEvaluacion($idEvaluacion, $idPersona);
        if ($intentosRealizados >= self::MAX_INTENTOS) {
            $this->redirigirConMensaje('Ya agotaste el máximo de 2 intentos para esta evaluación.', 'error', $idEvaluacion);
        }

        $timer = $this->obtenerTimerIntento($idEvaluacion);
        $inicioIntento = (int)($timer['started_at'] ?? 0);
        $intentoTimer = (int)($timer['attempt'] ?? 0);
        $intentoEsperado = $intentosRealizados + 1;

        if ($inicioIntento <= 0 || $intentoTimer !== $intentoEsperado) {
            $this->setTimerIntento($idEvaluacion, $intentoEsperado, time());
            $this->redirigirConMensaje('Se inició tu intento. Vuelve a enviar la evaluación dentro de 20 minutos.', 'error', $idEvaluacion);
        }

        $segundosTranscurridos = time() - $inicioIntento;
        if ($segundosTranscurridos > self::MAX_SEGUNDOS_INTENTO) {
            $preguntasTimeout = json_decode((string)($evaluacion['Preguntas_JSON'] ?? '[]'), true);
            $totalTimeout = is_array($preguntasTimeout) ? count($preguntasTimeout) : 0;

            $this->model->guardarResultado([
                'id_evaluacion' => $idEvaluacion,
                'id_persona' => $idPersona,
                'intento_numero' => $intentoEsperado,
                'respuestas_json' => json_encode([], JSON_UNESCAPED_UNICODE),
                'puntaje' => 0,
                'total_preguntas' => $totalTimeout,
                'correctas' => 0,
                'aprobado' => false,
            ]);

            $this->limpiarTimerIntento($idEvaluacion);
            $this->redirigirConMensaje('Tiempo agotado (20 minutos). El intento quedó registrado con 0%.', 'error', $idEvaluacion);
        }

        $preguntas = json_decode((string)($evaluacion['Preguntas_JSON'] ?? '[]'), true);
        if (!is_array($preguntas) || empty($preguntas)) {
            $this->redirigirConMensaje('La evaluación no tiene preguntas válidas.', 'error', $idEvaluacion);
        }

        $respuestasCerradas = (array)($_POST['respuesta'] ?? []);
        $correctas = 0;
        $total = 0;
        $respuestasGuardadas = [];

        foreach ($preguntas as $index => $pregunta) {
            $indice = (string)$index;
            $tipoPregunta = strtolower(trim((string)($pregunta['tipo'] ?? 'cerrada')));
            if ($tipoPregunta !== 'cerrada') {
                continue;
            }

            $opciones = (array)($pregunta['opciones'] ?? []);
            if (empty($opciones)) {
                continue;
            }

            $total++;
            $respuestaClave = trim((string)($respuestasCerradas[$indice] ?? ''));
            $estaRespondida = ($respuestaClave !== '' && isset($opciones[$respuestaClave]));
            $correctaEsperada = strtolower(trim((string)($pregunta['respuesta_correcta'] ?? '')));

            // VALIDACIÓN CRÍTICA: respuesta_correcta DEBE estar definida
            if ($correctaEsperada === '' || !isset($opciones[$correctaEsperada])) {
                // NO marcar como correcta si falta respuesta esperada
                // Esto indica un problema en los datos
                $esCorrecta = false;
                error_log('⚠️ ERROR: Pregunta sin respuesta_correcta válida. ID Evaluación: ' . $idEvaluacion . ', Índice: ' . $indice);
            } else {
                // Comparación normal
                $esCorrecta = $estaRespondida && ($respuestaClave === $correctaEsperada);
            }

            if ($esCorrecta) {
                $correctas++;
            }

            $respuestasGuardadas[] = [
                'tipo' => 'cerrada',
                'pregunta' => (string)($pregunta['enunciado'] ?? ''),
                'respuesta' => $respuestaClave,
                'texto_respuesta' => $estaRespondida ? (string)($opciones[$respuestaClave] ?? '') : '',
                'respondida' => $estaRespondida,
                'correcta_esperada' => $correctaEsperada,
                'es_correcta' => $esCorrecta,
            ];
        }

        if ($total <= 0) {
            $this->redirigirConMensaje('La evaluación no tiene preguntas cerradas válidas.', 'error', $idEvaluacion);
        }

        $puntaje = round(($correctas / $total) * 100, 2);
        $puntajeMinimo = max(80.0, (float)($evaluacion['Puntaje_Minimo'] ?? 80));
        $aprobado = $puntaje >= $puntajeMinimo;

        $idResultado = $this->model->guardarResultado([
            'id_evaluacion' => $idEvaluacion,
            'id_persona' => $idPersona,
            'intento_numero' => $intentoEsperado,
            'respuestas_json' => json_encode($respuestasGuardadas, JSON_UNESCAPED_UNICODE),
            'puntaje' => $puntaje,
            'total_preguntas' => $total,
            'correctas' => $correctas,
            'aprobado' => $aprobado,
        ]);

        // Modo automatico: al presentar evaluacion se marca asistencia en Capacitacion Destino.
        $nivelEvaluacion = (int)($evaluacion['Nivel'] ?? 0);
        $moduloEvaluacion = (int)($evaluacion['Modulo_Numero'] ?? 0);
        if ($idResultado > 0 && $nivelEvaluacion > 0) {
            $this->registrarAsistenciaClaseDiscipulo($idPersona, $nivelEvaluacion, $moduloEvaluacion);
        }

        $this->limpiarTimerIntento($idEvaluacion);

        $mensaje = $aprobado
            ? 'Evaluación enviada. Correctas: ' . $correctas . ' de ' . $total . '. Puntaje: ' . $puntaje . '%. ¡Aprobaste!'
            : 'Evaluación enviada. Correctas: ' . $correctas . ' de ' . $total . '. Puntaje: ' . $puntaje . '%. No alcanzaste el mínimo.';

        // Tras enviar, se muestra feedback inmediato del intento guardado.
        $this->redirigirConMensaje($mensaje, $aprobado ? 'success' : 'error', $idEvaluacion, $idResultado);
    }

    private function procesarConfigurarFechas(): void {
        $idEvaluacion = (int)($_POST['id_evaluacion'] ?? 0);
        if ($idEvaluacion <= 0) {
            $this->redirigirConMensaje('Evaluación inválida para configurar fechas.', 'error');
        }

        $fechaInicio = $this->normalizarFechaYmd($_POST['fecha_habilitacion_inicio'] ?? '');
        $fechaFin = $this->normalizarFechaYmd($_POST['fecha_habilitacion_fin'] ?? '');

        if ($fechaInicio !== '' && $fechaFin !== '' && strcmp($fechaInicio, $fechaFin) > 0) {
            $this->redirigirConMensaje('La fecha inicial no puede ser mayor que la final.', 'error', $idEvaluacion);
        }

        $ok = $this->model->actualizarFechasHabilitacion(
            $idEvaluacion,
            $fechaInicio !== '' ? $fechaInicio : null,
            $fechaFin !== '' ? $fechaFin : null
        );

        if (!$ok) {
            $this->redirigirConMensaje('No se pudieron guardar las fechas de habilitación.', 'error', $idEvaluacion);
        }

        $this->redirigirConMensaje('Fechas de habilitación actualizadas.', 'success', $idEvaluacion);
    }

    private function normalizarFechaYmd($valor): string {
        $valor = trim((string)$valor);
        if ($valor === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return '';
        }

        $fecha = DateTimeImmutable::createFromFormat('Y-m-d', $valor);
        return $fecha ? $fecha->format('Y-m-d') : '';
    }

    private function estaDisponiblePorFecha(array $evaluacion): bool {
        $hoy = date('Y-m-d');
        $inicio = $this->normalizarFechaYmd($evaluacion['Fecha_Habilitacion_Inicio'] ?? '');
        $fin = $this->normalizarFechaYmd($evaluacion['Fecha_Habilitacion_Fin'] ?? '');

        // Compatibilidad: evaluaciones históricas sin fechas quedan visibles.
        if ($inicio === '' && $fin === '') {
            return true;
        }

        // Si tiene solo una fecha, se considera configuración incompleta.
        if ($inicio === '' || $fin === '') {
            return false;
        }

        if (strcmp($inicio, $fin) > 0) {
            return false;
        }

        if ($inicio !== '' && $hoy < $inicio) {
            return false;
        }

        if ($fin !== '' && $hoy > $fin) {
            return false;
        }

        return true;
    }

    private function tienePermisoConfigurarFechas(): bool {
        if (AuthController::esAdministrador()) {
            return true;
        }

        // Permiso granular obligatorio para controlar publicación por fechas.
        return AuthController::tienePermiso(self::MODULO_CONFIG_FECHAS, 'editar');
    }

    private function redirigirConMensaje(string $mensaje, string $tipo, int $idEvaluacion = 0, int $idResultado = 0): void {
        $contexto = $this->obtenerContextoMaterialDesdeRequest();
        $queryContexto = $this->construirQueryContextoMaterial($contexto);
        $queryEvaluacion = $idEvaluacion > 0 ? '&evaluacion=' . $idEvaluacion : '';
        $queryResultado = $idResultado > 0 ? '&resultado=' . $idResultado : '';
        $this->redirect('programas/evaluaciones' . $queryContexto . $queryEvaluacion . $queryResultado . '&mensaje=' . urlencode($mensaje) . '&tipo=' . urlencode($tipo));
    }

    private function obtenerContextoMaterialDesdeRequest(): array {
        $fromMaterialRaw = $_POST['from_material'] ?? $_GET['from_material'] ?? '';
        $nivelRaw = $_POST['nivel'] ?? $_POST['filtro_nivel_contexto'] ?? $_GET['nivel'] ?? 0;
        $moduloRaw = $_POST['modulo'] ?? $_POST['modulo_numero'] ?? $_POST['filtro_modulo_contexto'] ?? $_GET['modulo'] ?? 0;
        $leccionRaw = $_POST['leccion'] ?? $_POST['filtro_leccion_contexto'] ?? $_GET['leccion'] ?? '';

        $nivel = (int)$nivelRaw;
        $modulo = (int)$moduloRaw;

        if (!$this->esContextoNivelModuloValido($nivel, $modulo)) {
            return [];
        }

        $leccion = $this->normalizarLeccionTexto($leccionRaw);
        $mapaLecciones = $this->obtenerMapaLeccionesMaterial();
        $leccionesDisponibles = $this->obtenerLeccionesParaNivelModulo($mapaLecciones, $nivel, $modulo);
        if (!empty($leccionesDisponibles) && !in_array($leccion, $leccionesDisponibles, true)) {
            $leccion = (string)$leccionesDisponibles[0];
        }

        return [
            'from_material' => 1,
            'nivel' => $nivel,
            'modulo' => $modulo,
            'leccion' => $leccion,
        ];
    }

    private function construirQueryContextoMaterial(array $contexto): string {
        if (empty($contexto)) {
            return '';
        }

        return '&from_material=1'
            . '&nivel=' . (int)($contexto['nivel'] ?? 0)
            . '&modulo=' . (int)($contexto['modulo'] ?? 0)
            . '&leccion=' . urlencode((string)($contexto['leccion'] ?? ''));
    }

    private function normalizarLeccionTexto($valor): string {
        $texto = trim((string)$valor);
        if ($texto === '') {
            return 'Sin lección';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($texto, 0, 120);
        }

        return substr($texto, 0, 120);
    }

    private function normalizarUrlClase($valor): string {
        $url = trim((string)$valor);
        if ($url === '') {
            return '';
        }

        // Si no trae esquema, asumir https para enlaces de Zoom/Meet.
        if (!preg_match('#^[a-z][a-z0-9+\-.]*://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        return $url;
    }

    private function construirUrlIrClase(int $nivel, int $modulo): string {
        return PUBLIC_URL
            . '?url=programas/ir-clase'
            . '&nivel=' . $nivel
            . '&modulo=' . $modulo;
    }

    private function resolverProgramaCapDestinoPorNivel(int $nivel): string {
        if ($nivel === 1) {
            return 'capacitacion_destino_nivel_1';
        }
        if ($nivel === 2) {
            return 'capacitacion_destino_nivel_2';
        }
        if ($nivel === 3) {
            return 'capacitacion_destino_nivel_3';
        }

        return '';
    }

    private function registrarAsistenciaClaseDiscipulo(int $idPersona, int $nivel, int $moduloNumero = 0): void {
        $idPersona = (int)$idPersona;
        $programaLinea = $this->resolverProgramaCapDestinoPorNivel($nivel);
        if ($idPersona <= 0 || $programaLinea === '') {
            return;
        }

        require_once APP . '/Models/EscuelaFormacionInscripcion.php';
        require_once APP . '/Models/EscuelaFormacionAsistenciaClase.php';

        $inscripcionModel = new EscuelaFormacionInscripcion();
        $asistenciaModel = new EscuelaFormacionAsistenciaClase();

        $programasInscripcion = [$programaLinea];
        if ($nivel === 1) {
            $programasInscripcion[] = 'capacitacion_destino';
        }

        $idInscripcion = 0;
        foreach ($programasInscripcion as $progIns) {
            $rows = $inscripcionModel->query(
                "SELECT Id_Inscripcion
                 FROM escuela_formacion_inscripcion
                 WHERE Id_Persona = ? AND Programa = ?
                 ORDER BY Fecha_Registro DESC, Id_Inscripcion DESC
                 LIMIT 1",
                [$idPersona, $progIns]
            );
            if (!empty($rows)) {
                $idInscripcion = (int)($rows[0]['Id_Inscripcion'] ?? 0);
                if ($idInscripcion > 0) {
                    break;
                }
            }
        }

        if ($idInscripcion > 0) {
            $inscripcionModel->actualizarAsistenciaClase($idInscripcion, true);
        }

        // Vista material/pagos usan claves distintas; registramos en ambas para que cuadren reportes y planilla.
        $moduloMaterial = 'modulo_' . $nivel;
        $programaMaterial = 'capacitacion_destino';
        $moduloPagos = 'discipular';

        $numeroClase = 0;
        if ($moduloNumero > 0) {
            $permitidos = self::CONFIG_CAP_DESTINO[$nivel] ?? [];
            $idx = array_search($moduloNumero, $permitidos, true);
            if ($idx !== false) {
                $numeroClase = $idx + 1;
            }
        }

        if ($numeroClase <= 0) {
            $numeroClase = $asistenciaModel->getNumeroClasePorFecha($moduloMaterial, $programaMaterial, date('Y-m-d'));
        }
        if ($numeroClase <= 0) {
            $numeroClase = $asistenciaModel->getNumeroClasePorFecha($moduloPagos, $programaLinea, date('Y-m-d'));
        }

        if ($numeroClase <= 0 && $moduloNumero > 0) {
            $numeroClase = (($moduloNumero - 1) % 2) + 1;
        }

        if ($numeroClase <= 0) {
            $actuales = $asistenciaModel->getAsistenciasPorPrograma([$idPersona], $moduloMaterial, $programaMaterial);
            $clasesPersona = (array)($actuales[$idPersona] ?? []);
            for ($i = 1; $i <= 10; $i++) {
                if (empty($clasesPersona[$i])) {
                    $numeroClase = $i;
                    break;
                }
            }
        }

        if ($numeroClase > 0) {
            $asistenciaModel->upsertAsistencia($idPersona, $moduloMaterial, $programaMaterial, $numeroClase, true);
            $asistenciaModel->upsertAsistencia($idPersona, $moduloPagos, $programaLinea, $numeroClase, true);
        }
    }

    private function obtenerMapaLeccionesMaterial(): array {
        $filas = $this->model->listarLeccionesMaterialCapacitacionDestino();
        $mapa = [];

        foreach ($filas as $fila) {
            $nivel = (int)($fila['Nivel'] ?? 0);
            $modulo = (int)($fila['Modulo_Numero'] ?? 0);
            $leccion = $this->normalizarLeccionTexto($fila['Leccion'] ?? '');
            if ($nivel <= 0 || $modulo <= 0) {
                continue;
            }

            if (!isset($mapa[$nivel])) {
                $mapa[$nivel] = [];
            }
            if (!isset($mapa[$nivel][$modulo])) {
                $mapa[$nivel][$modulo] = [];
            }
            if (!in_array($leccion, $mapa[$nivel][$modulo], true)) {
                $mapa[$nivel][$modulo][] = $leccion;
            }
        }

        return $mapa;
    }

    private function obtenerLeccionesParaNivelModulo(array $mapa, int $nivel, int $modulo): array {
        if ($nivel <= 0 || $modulo <= 0) {
            return [];
        }

        $lecciones = (array)($mapa[$nivel][$modulo] ?? []);
        sort($lecciones);
        return $lecciones;
    }

    private function obtenerNivelesPermitidosPorInscripcion(int $idPersona): array {
        if ($idPersona <= 0) {
            return [];
        }

        require_once APP . '/Models/EscuelaFormacionInscripcion.php';
        $inscripcionModel = new EscuelaFormacionInscripcion();
        $programas = (array)$inscripcionModel->getProgramasInscritosPersona($idPersona);

        $niveles = [];
        foreach ($programas as $programa) {
            $programa = trim((string)$programa);
            if ($programa === 'capacitacion_destino' || $programa === 'capacitacion_destino_nivel_1') {
                $niveles[1] = true;
            }
            if ($programa === 'capacitacion_destino_nivel_2') {
                $niveles[2] = true;
            }
            if ($programa === 'capacitacion_destino_nivel_3') {
                $niveles[3] = true;
            }
        }

        $resultado = array_map('intval', array_keys($niveles));
        sort($resultado);
        return $resultado;
    }

    private function construirLinksClases(array $nivelesPermitidos): array {
        $nivelesPermitidos = array_values(array_unique(array_map('intval', $nivelesPermitidos)));
        $nivelesSet = array_fill_keys($nivelesPermitidos, true);

        $links = [];

        $filas = $this->model->listarConexionesClaseCapacitacionDestino();
        foreach ((array)$filas as $fila) {
            $nivel = (int)($fila['Nivel'] ?? 0);
            $modulo = (int)($fila['Modulo_Numero'] ?? 0);
            $url = trim((string)($fila['Conexion_Zoom_URL'] ?? ''));
            if ($nivel <= 0 || $modulo <= 0 || $url === '') {
                continue;
            }

            if (!isset($nivelesSet[$nivel])) {
                continue;
            }

            $links[] = [
                'nivel' => $nivel,
                'label' => 'Clase Nivel ' . $nivel . ' - Modulo ' . $modulo,
                'url' => $url,
            ];
        }

        return $links;
    }

    private function construirAccesosDirectosDiscipulo(array $nivelesPermitidos, array $evaluaciones): array {
        $nivelesPermitidos = array_values(array_unique(array_map('intval', $nivelesPermitidos)));
        if (empty($nivelesPermitidos)) {
            return [];
        }

        $nivelesSet = array_fill_keys($nivelesPermitidos, true);
        $accesos = [];
        $primerLinkPorNivel = [];
        $primerEvalPorNivelModulo = [];

        $conexiones = (array)$this->model->listarConexionesClaseCapacitacionDestino();
        foreach ($conexiones as $fila) {
            $nivel = (int)($fila['Nivel'] ?? 0);
            $modulo = (int)($fila['Modulo_Numero'] ?? 0);
            $urlClase = $this->normalizarUrlClase($fila['Conexion_Zoom_URL'] ?? '');
            if ($nivel <= 0 || $modulo <= 0 || !isset($nivelesSet[$nivel])) {
                continue;
            }

            if ($urlClase !== '' && !isset($primerLinkPorNivel[$nivel])) {
                $primerLinkPorNivel[$nivel] = $urlClase;
            }

            $key = $nivel . '_' . $modulo;
            if (!isset($accesos[$key])) {
                $accesos[$key] = [
                    'nivel' => $nivel,
                    'modulo' => $modulo,
                    'leccion' => 'Sin lección activa',
                    'url_clase' => $urlClase,
                    'url_evaluacion' => '',
                ];
            } elseif ($urlClase !== '') {
                $accesos[$key]['url_clase'] = $urlClase;
            }
        }

        foreach ((array)$evaluaciones as $evaluacion) {
            $nivel = (int)($evaluacion['Nivel'] ?? 0);
            $modulo = (int)($evaluacion['Modulo_Numero'] ?? 0);
            $idEvaluacion = (int)($evaluacion['Id_Evaluacion'] ?? 0);
            if ($nivel <= 0 || $modulo <= 0 || $idEvaluacion <= 0 || !isset($nivelesSet[$nivel])) {
                continue;
            }

            $keyNivelModulo = $nivel . '_' . $modulo;
            if (!isset($primerEvalPorNivelModulo[$keyNivelModulo])) {
                $primerEvalPorNivelModulo[$keyNivelModulo] = $idEvaluacion;
            }

            $key = $nivel . '_' . $modulo;
            if (!isset($accesos[$key])) {
                $accesos[$key] = [
                    'nivel' => $nivel,
                    'modulo' => $modulo,
                    'leccion' => 'Sin lección activa',
                    'url_clase' => '',
                    'url_evaluacion' => '',
                ];
            }

            $leccion = $this->normalizarLeccionTexto($evaluacion['Leccion'] ?? '');
            if ($leccion !== '') {
                $accesos[$key]['leccion'] = $leccion;
            }

            if ((string)$accesos[$key]['url_evaluacion'] === '') {
                $accesos[$key]['url_evaluacion'] = PUBLIC_URL . '?url=programas/evaluaciones&evaluacion=' . $idEvaluacion;
            }
        }

        $configCap = self::CONFIG_CAP_DESTINO;
        foreach ($nivelesPermitidos as $nivelPermitido) {
            $modulosNivel = array_values(array_map('intval', (array)($configCap[(int)$nivelPermitido] ?? [])));
            if (empty($modulosNivel)) {
                continue;
            }

            $moduloBase = (int)$modulosNivel[0];
            $keyBase = (int)$nivelPermitido . '_' . $moduloBase;
            if (!isset($accesos[$keyBase])) {
                $accesos[$keyBase] = [
                    'nivel' => (int)$nivelPermitido,
                    'modulo' => $moduloBase,
                    'leccion' => 'Sin lección activa',
                    'url_clase' => '',
                    'url_evaluacion' => '',
                ];
            }
        }

        foreach ($accesos as $key => $acceso) {
            $nivelAcceso = (int)($acceso['nivel'] ?? 0);
            if (trim((string)($acceso['url_clase'] ?? '')) === '' && isset($primerLinkPorNivel[$nivelAcceso])) {
                $accesos[$key]['url_clase'] = (string)$primerLinkPorNivel[$nivelAcceso];
            }

            if (trim((string)($acceso['url_evaluacion'] ?? '')) !== '') {
                continue;
            }

            $nivel = (int)($acceso['nivel'] ?? 0);
            $modulo = (int)($acceso['modulo'] ?? 0);

            $keyNivelModulo = $nivel . '_' . $modulo;
            $idEvaluacionDestino = (int)($primerEvalPorNivelModulo[$keyNivelModulo] ?? 0);

            if ($idEvaluacionDestino > 0) {
                $accesos[$key]['url_evaluacion'] = PUBLIC_URL . '?url=programas/evaluaciones&evaluacion=' . $idEvaluacionDestino;
            }

            if (trim((string)($accesos[$key]['url_clase'] ?? '')) !== '') {
                $accesos[$key]['url_clase'] = $this->construirUrlIrClase($nivel, $modulo);
            }
        }

        if (empty($accesos)) {
            return [];
        }

        usort($accesos, static function($a, $b) {
            $cmpNivel = ((int)($a['nivel'] ?? 0)) <=> ((int)($b['nivel'] ?? 0));
            if ($cmpNivel !== 0) {
                return $cmpNivel;
            }
            return ((int)($a['modulo'] ?? 0)) <=> ((int)($b['modulo'] ?? 0));
        });

        return array_values($accesos);
    }

    private function asegurarTablasTareasMaterialHub(): void {
        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return;
        }

        $sqlTarea = "CREATE TABLE IF NOT EXISTS material_hub_tarea (
                        Id_Tarea INT AUTO_INCREMENT PRIMARY KEY,
                        Modulo VARCHAR(80) NOT NULL,
                        Nivel TINYINT UNSIGNED NOT NULL,
                        Modulo_Numero TINYINT UNSIGNED NULL,
                        Titulo VARCHAR(255) NOT NULL,
                        Descripcion TEXT NULL,
                        Fecha_Limite DATE NULL,
                        Estado VARCHAR(20) NOT NULL DEFAULT 'activa',
                        Creado_Por INT NULL,
                        Fecha_Creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        KEY idx_modulo_nivel (Modulo, Nivel),
                        KEY idx_modulo_nivel_modulo (Modulo, Nivel, Modulo_Numero)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sqlTarea);

        $sqlEntrega = "CREATE TABLE IF NOT EXISTS material_hub_tarea_entrega (
                        Id_Entrega INT AUTO_INCREMENT PRIMARY KEY,
                        Id_Tarea INT NOT NULL,
                        Id_Persona INT NOT NULL,
                        Nombre_Archivo VARCHAR(255) NOT NULL,
                        Nombre_Original VARCHAR(255) NULL,
                        Comentario TEXT NULL,
                        Nota DECIMAL(5,2) NULL,
                        Retroalimentacion TEXT NULL,
                        Estado_Calificacion VARCHAR(20) NOT NULL DEFAULT 'pendiente',
                        Calificado_Por INT NULL,
                        Fecha_Entrega DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        Fecha_Calificacion DATETIME NULL,
                        KEY idx_tarea (Id_Tarea),
                        KEY idx_persona (Id_Persona),
                        KEY idx_tarea_persona (Id_Tarea, Id_Persona)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sqlEntrega);
    }

    private function obtenerDirectorioTareasCapDestino(): string {
        return ROOT . '/public/uploads/material_hub_tareas/capacitacion_destino';
    }

    private function guardarArchivoEntregaTareaDiscipulo(int $idTarea, int $idPersona, array $archivo, int $indice = 1): array {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir archivo de tarea.');
        }

        $tamano = (int)($archivo['size'] ?? 0);
        if ($tamano <= 0) {
            throw new Exception('Archivo de tarea vacío o inválido.');
        }
        if ($tamano > 20 * 1024 * 1024) {
            throw new Exception('Cada archivo de tarea debe pesar máximo 20MB.');
        }

        $nombreOriginal = trim((string)($archivo['name'] ?? 'tarea.bin'));
        $extension = strtolower((string)pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $extension = preg_replace('/[^a-z0-9]/', '', $extension);
        if ($extension === '') {
            $extension = 'bin';
        }
        $extension = substr($extension, 0, 10);

        $directorio = $this->obtenerDirectorioTareasCapDestino();
        if (!is_dir($directorio) && !@mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new Exception('No se pudo crear el directorio de tareas.');
        }

        $base = 'tarea_' . max(1, $idTarea) . '_' . max(1, $idPersona) . '_' . date('Ymd_His') . '_' . max(1, $indice);
        $nombreFinal = $base . '.' . $extension;
        $iter = 1;
        while (is_file($directorio . '/' . $nombreFinal)) {
            $iter++;
            $nombreFinal = $base . '_' . $iter . '.' . $extension;
        }

        $destino = $directorio . '/' . $nombreFinal;
        if (!@move_uploaded_file((string)($archivo['tmp_name'] ?? ''), $destino)) {
            throw new Exception('No se pudo guardar un archivo de tarea en el servidor.');
        }

        return [
            'nombre' => $nombreFinal,
            'original' => $nombreOriginal,
        ];
    }

    private function listarTareasActivasPorModulosDiscipulo(int $idPersona, array $modulosPermitidos): array {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0 || empty($modulosPermitidos)) {
            return [];
        }

        $this->asegurarTablasTareasMaterialHub();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return [];
        }

        $condiciones = [];
        $params = [$idPersona, 'capacitacion_destino'];
        foreach ($modulosPermitidos as $permiso) {
            $nivel = (int)($permiso['nivel'] ?? 0);
            $modulo = (int)($permiso['modulo'] ?? 0);
            if ($nivel <= 0 || $modulo <= 0) {
                continue;
            }
            $condiciones[] = '(t.Nivel = ? AND t.Modulo_Numero = ?)';
            $params[] = $nivel;
            $params[] = $modulo;
        }

        if (empty($condiciones)) {
            return [];
        }

        $sql = "SELECT
                    t.Id_Tarea,
                    t.Nivel,
                    COALESCE(t.Modulo_Numero, 0) AS Modulo_Numero,
                    t.Titulo,
                    t.Descripcion,
                    t.Fecha_Limite,
                    t.Fecha_Creacion,
                    (SELECT COUNT(*) FROM material_hub_tarea_entrega e WHERE e.Id_Tarea = t.Id_Tarea AND e.Id_Persona = ?) AS total_entregas_usuario
                FROM material_hub_tarea t
                WHERE t.Modulo = ?
                  AND t.Estado = 'activa'
                  AND (" . implode(' OR ', $condiciones) . ")
                ORDER BY t.Fecha_Creacion DESC, t.Id_Tarea DESC";

        // Mueve idPersona al inicio real de params de subconsulta.
        $paramsSql = array_merge([$idPersona, 'capacitacion_destino'], array_slice($params, 2));
        $stmt = $pdo->prepare($sql);
        $stmt->execute($paramsSql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $idsTarea = [];
        foreach ($rows as $rowTmp) {
            $idTareaTmp = (int)($rowTmp['Id_Tarea'] ?? 0);
            if ($idTareaTmp > 0) {
                $idsTarea[$idTareaTmp] = $idTareaTmp;
            }
        }
        $entregasPorTarea = $this->listarEntregasUsuarioPorTareasDiscipulo($idPersona, array_values($idsTarea));

        $map = [];
        foreach ($rows as $row) {
            $nivel = (int)($row['Nivel'] ?? 0);
            $modulo = (int)($row['Modulo_Numero'] ?? 0);
            if ($nivel <= 0 || $modulo <= 0) {
                continue;
            }

            $key = $nivel . '_' . $modulo;
            if (!isset($map[$key])) {
                $map[$key] = [];
            }
            $row['entregas_usuario'] = (array)($entregasPorTarea[(int)($row['Id_Tarea'] ?? 0)] ?? []);
            $map[$key][] = $row;
        }

        return $map;
    }

    private function listarEntregasUsuarioPorTareasDiscipulo(int $idPersona, array $idsTarea): array {
        $idPersona = (int)$idPersona;
        $idsTarea = array_values(array_filter(array_map('intval', $idsTarea), static function($id) {
            return $id > 0;
        }));

        if ($idPersona <= 0 || empty($idsTarea)) {
            return [];
        }

        $this->asegurarTablasTareasMaterialHub();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsTarea), '?'));
        $sql = "SELECT
                    e.Id_Entrega,
                    e.Id_Tarea,
                    e.Nombre_Archivo,
                    e.Nombre_Original,
                    e.Comentario,
                    e.Nota,
                    e.Retroalimentacion,
                    e.Estado_Calificacion,
                    e.Fecha_Entrega,
                    e.Fecha_Calificacion
                FROM material_hub_tarea_entrega e
                WHERE e.Id_Persona = ?
                  AND e.Id_Tarea IN ({$placeholders})
                ORDER BY e.Fecha_Entrega DESC, e.Id_Entrega DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$idPersona], $idsTarea));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $map = [];
        foreach ($rows as $row) {
            $idTarea = (int)($row['Id_Tarea'] ?? 0);
            if ($idTarea <= 0) {
                continue;
            }
            if (!isset($map[$idTarea])) {
                $map[$idTarea] = [];
            }
            $map[$idTarea][] = $row;
        }

        return $map;
    }

    private function redirigirAccionTareaDiscipulo(string $mensaje, string $tipo, bool $volverTareas, int $nivelRetorno, int $moduloRetorno): void {
        if ($volverTareas) {
            $ruta = 'programas/tareas';
            if ($nivelRetorno > 0) {
                $ruta .= '&nivel=' . $nivelRetorno;
            }
            if ($moduloRetorno > 0) {
                $ruta .= '&modulo=' . $moduloRetorno;
            }
            $ruta .= '&mensaje=' . urlencode($mensaje) . '&tipo=' . urlencode($tipo);
            $this->redirect($ruta);
        }

        $this->redirigirConMensaje($mensaje, $tipo);
    }

    private function procesarEntregaTareaDiscipulo(): void {
        $volverTareas = !empty($_POST['volver_tareas']) || !empty($_GET['volver_tareas']);
        $nivelRetorno = (int)($_POST['nivel'] ?? $_GET['nivel'] ?? 0);
        $moduloRetorno = (int)($_POST['modulo_numero'] ?? $_GET['modulo'] ?? 0);

        $redirigir = function(string $mensaje, string $tipo) use ($volverTareas, $nivelRetorno, $moduloRetorno): void {
            $this->redirigirAccionTareaDiscipulo($mensaje, $tipo, $volverTareas, $nivelRetorno, $moduloRetorno);
        };

        $idPersona = (int)($_SESSION['usuario_id'] ?? 0);
        if ($idPersona <= 0) {
            $redirigir('No se pudo identificar tu usuario para entregar la tarea.', 'error');
        }

        $idTarea = (int)($_POST['id_tarea'] ?? 0);
        $nivel = (int)($_POST['nivel'] ?? 0);
        $modulo = (int)($_POST['modulo_numero'] ?? 0);
        $comentario = trim((string)($_POST['comentario_entrega'] ?? ''));

        if ($idTarea <= 0 || $nivel <= 0 || $modulo <= 0) {
            $redirigir('Datos incompletos para subir la tarea.', 'error');
        }

        if (!isset($_FILES['tarea_archivos'])) {
            $redirigir('Debes seleccionar al menos un archivo.', 'error');
        }

        $nivelesPermitidos = $this->obtenerNivelesPermitidosPorInscripcion($idPersona);
        if (!in_array($nivel, $nivelesPermitidos, true)) {
            $redirigir('No tienes acceso a tareas de ese nivel.', 'error');
        }

        $modulosNivel = self::CONFIG_CAP_DESTINO[$nivel] ?? [];
        if (!in_array($modulo, $modulosNivel, true)) {
            $redirigir('No tienes acceso a tareas de ese módulo.', 'error');
        }

        $this->asegurarTablasTareasMaterialHub();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            $redirigir('No se pudo conectar para guardar tu entrega.', 'error');
        }

        $stmtTarea = $pdo->prepare("SELECT Id_Tarea, Nivel, COALESCE(Modulo_Numero, 0) AS Modulo_Numero FROM material_hub_tarea WHERE Id_Tarea = ? AND Modulo = 'capacitacion_destino' AND Estado = 'activa' LIMIT 1");
        $stmtTarea->execute([$idTarea]);
        $tarea = $stmtTarea->fetch(PDO::FETCH_ASSOC);
        if (!$tarea) {
            $redirigir('La tarea no está disponible.', 'error');
        }

        $nivelTarea = (int)($tarea['Nivel'] ?? 0);
        $moduloTarea = (int)($tarea['Modulo_Numero'] ?? 0);
        if ($nivelTarea !== $nivel) {
            $redirigir('La tarea no corresponde a tu nivel seleccionado.', 'error');
        }
        if ($moduloTarea > 0 && $moduloTarea !== $modulo) {
            $redirigir('La tarea no corresponde al módulo seleccionado.', 'error');
        }

        $insert = $pdo->prepare(
            "INSERT INTO material_hub_tarea_entrega
             (Id_Tarea, Id_Persona, Nombre_Archivo, Nombre_Original, Comentario, Estado_Calificacion)
             VALUES (?, ?, ?, ?, ?, 'pendiente')"
        );

        $cantidad = 0;
        $indice = 1;
        $archivos = $_FILES['tarea_archivos'];
        if (is_array($archivos['name'] ?? null)) {
            $total = count($archivos['name']);
            for ($i = 0; $i < $total; $i++) {
                if ((int)($archivos['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $archivo = [
                    'name' => $archivos['name'][$i] ?? '',
                    'type' => $archivos['type'][$i] ?? '',
                    'tmp_name' => $archivos['tmp_name'][$i] ?? '',
                    'error' => $archivos['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $archivos['size'][$i] ?? 0,
                ];

                $guardado = $this->guardarArchivoEntregaTareaDiscipulo($idTarea, $idPersona, $archivo, $indice);
                $insert->execute([
                    $idTarea,
                    $idPersona,
                    (string)($guardado['nombre'] ?? ''),
                    (string)($guardado['original'] ?? ''),
                    $comentario !== '' ? $comentario : null,
                ]);
                $cantidad++;
                $indice++;
            }
        } else {
            $guardado = $this->guardarArchivoEntregaTareaDiscipulo($idTarea, $idPersona, $archivos, $indice);
            $insert->execute([
                $idTarea,
                $idPersona,
                (string)($guardado['nombre'] ?? ''),
                (string)($guardado['original'] ?? ''),
                $comentario !== '' ? $comentario : null,
            ]);
            $cantidad = 1;
        }

        if ($cantidad <= 0) {
            $redirigir('No se detectaron archivos válidos para la entrega.', 'error');
        }

        $redirigir('Tarea enviada correctamente con ' . $cantidad . ' archivo(s).', 'success');
    }

    private function procesarEditarEntregaTareaDiscipulo(): void {
        $volverTareas = !empty($_POST['volver_tareas']) || !empty($_GET['volver_tareas']);
        $nivelRetorno = (int)($_POST['nivel'] ?? $_GET['nivel'] ?? 0);
        $moduloRetorno = (int)($_POST['modulo_numero'] ?? $_GET['modulo'] ?? 0);

        $redirigir = function(string $mensaje, string $tipo) use ($volverTareas, $nivelRetorno, $moduloRetorno): void {
            $this->redirigirAccionTareaDiscipulo($mensaje, $tipo, $volverTareas, $nivelRetorno, $moduloRetorno);
        };

        $idPersona = (int)($_SESSION['usuario_id'] ?? 0);
        $idEntrega = (int)($_POST['id_entrega'] ?? 0);
        $comentario = trim((string)($_POST['comentario_entrega_editar'] ?? ''));

        if ($idPersona <= 0 || $idEntrega <= 0) {
            $redirigir('No se pudo identificar la entrega a editar.', 'error');
        }

        $this->asegurarTablasTareasMaterialHub();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            $redirigir('No se pudo conectar para editar tu entrega.', 'error');
        }

        $sqlEntrega = "SELECT
                            e.Id_Entrega,
                            e.Id_Tarea,
                            e.Id_Persona,
                            e.Nombre_Archivo,
                            t.Nivel,
                            COALESCE(t.Modulo_Numero, 0) AS Modulo_Numero
                        FROM material_hub_tarea_entrega e
                        INNER JOIN material_hub_tarea t ON t.Id_Tarea = e.Id_Tarea
                        WHERE e.Id_Entrega = ?
                          AND e.Id_Persona = ?
                          AND t.Modulo = 'capacitacion_destino'
                        LIMIT 1";
        $stmtEntrega = $pdo->prepare($sqlEntrega);
        $stmtEntrega->execute([$idEntrega, $idPersona]);
        $entrega = $stmtEntrega->fetch(PDO::FETCH_ASSOC);

        if (!$entrega) {
            $redirigir('No se encontró la entrega seleccionada.', 'error');
        }

        $nivelEntrega = (int)($entrega['Nivel'] ?? 0);
        $moduloEntrega = (int)($entrega['Modulo_Numero'] ?? 0);
        if ($nivelRetorno > 0 && $nivelEntrega !== $nivelRetorno) {
            $redirigir('La entrega no corresponde al nivel seleccionado.', 'error');
        }
        if ($moduloRetorno > 0 && $moduloEntrega > 0 && $moduloEntrega !== $moduloRetorno) {
            $redirigir('La entrega no corresponde al módulo seleccionado.', 'error');
        }

        $campos = [
            'Comentario = ?',
            'Estado_Calificacion = \'pendiente\'',
            'Nota = NULL',
            'Retroalimentacion = NULL',
            'Calificado_Por = NULL',
            'Fecha_Calificacion = NULL',
        ];
        $params = [$comentario !== '' ? $comentario : null];

        $archivoAnterior = trim((string)($entrega['Nombre_Archivo'] ?? ''));
        if (isset($_FILES['tarea_archivo_editar']) && (int)($_FILES['tarea_archivo_editar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $idTarea = (int)($entrega['Id_Tarea'] ?? 0);
            $guardado = $this->guardarArchivoEntregaTareaDiscipulo($idTarea, $idPersona, $_FILES['tarea_archivo_editar'], 1);
            $campos[] = 'Nombre_Archivo = ?';
            $campos[] = 'Nombre_Original = ?';
            $params[] = (string)($guardado['nombre'] ?? '');
            $params[] = (string)($guardado['original'] ?? '');
        }

        $params[] = $idEntrega;
        $params[] = $idPersona;

        $sqlUpdate = "UPDATE material_hub_tarea_entrega
                      SET " . implode(', ', $campos) . "
                      WHERE Id_Entrega = ? AND Id_Persona = ?";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute($params);

        if (isset($guardado) && !empty($guardado['nombre']) && $archivoAnterior !== '' && $archivoAnterior !== (string)$guardado['nombre']) {
            $this->eliminarArchivoEntregaTareaDiscipulo($archivoAnterior);
        }

        $redirigir('Entrega actualizada correctamente.', 'success');
    }

    private function procesarEliminarEntregaTareaDiscipulo(): void {
        $volverTareas = !empty($_POST['volver_tareas']) || !empty($_GET['volver_tareas']);
        $nivelRetorno = (int)($_POST['nivel'] ?? $_GET['nivel'] ?? 0);
        $moduloRetorno = (int)($_POST['modulo_numero'] ?? $_GET['modulo'] ?? 0);

        $redirigir = function(string $mensaje, string $tipo) use ($volverTareas, $nivelRetorno, $moduloRetorno): void {
            $this->redirigirAccionTareaDiscipulo($mensaje, $tipo, $volverTareas, $nivelRetorno, $moduloRetorno);
        };

        $idPersona = (int)($_SESSION['usuario_id'] ?? 0);
        $idEntrega = (int)($_POST['id_entrega'] ?? 0);

        if ($idPersona <= 0 || $idEntrega <= 0) {
            $redirigir('No se pudo identificar la entrega a eliminar.', 'error');
        }

        $this->asegurarTablasTareasMaterialHub();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            $redirigir('No se pudo conectar para eliminar tu entrega.', 'error');
        }

        $sqlEntrega = "SELECT
                            e.Id_Entrega,
                            e.Nombre_Archivo,
                            t.Nivel,
                            COALESCE(t.Modulo_Numero, 0) AS Modulo_Numero
                        FROM material_hub_tarea_entrega e
                        INNER JOIN material_hub_tarea t ON t.Id_Tarea = e.Id_Tarea
                        WHERE e.Id_Entrega = ?
                          AND e.Id_Persona = ?
                          AND t.Modulo = 'capacitacion_destino'
                        LIMIT 1";
        $stmtEntrega = $pdo->prepare($sqlEntrega);
        $stmtEntrega->execute([$idEntrega, $idPersona]);
        $entrega = $stmtEntrega->fetch(PDO::FETCH_ASSOC);

        if (!$entrega) {
            $redirigir('No se encontró la entrega seleccionada.', 'error');
        }

        $nivelEntrega = (int)($entrega['Nivel'] ?? 0);
        $moduloEntrega = (int)($entrega['Modulo_Numero'] ?? 0);
        if ($nivelRetorno > 0 && $nivelEntrega !== $nivelRetorno) {
            $redirigir('La entrega no corresponde al nivel seleccionado.', 'error');
        }
        if ($moduloRetorno > 0 && $moduloEntrega > 0 && $moduloEntrega !== $moduloRetorno) {
            $redirigir('La entrega no corresponde al módulo seleccionado.', 'error');
        }

        $stmtDelete = $pdo->prepare("DELETE FROM material_hub_tarea_entrega WHERE Id_Entrega = ? AND Id_Persona = ? LIMIT 1");
        $stmtDelete->execute([$idEntrega, $idPersona]);

        if ((int)$stmtDelete->rowCount() <= 0) {
            $redirigir('No se pudo eliminar la entrega seleccionada.', 'error');
        }

        $archivo = trim((string)($entrega['Nombre_Archivo'] ?? ''));
        if ($archivo !== '') {
            $this->eliminarArchivoEntregaTareaDiscipulo($archivo);
        }

        $redirigir('Entrega eliminada correctamente.', 'success');
    }

    private function eliminarArchivoEntregaTareaDiscipulo(string $nombreArchivo): void {
        $nombreArchivo = trim(basename($nombreArchivo));
        if ($nombreArchivo === '') {
            return;
        }

        $ruta = $this->obtenerDirectorioTareasCapDestino() . '/' . $nombreArchivo;
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }

    private function construirEstadoIntento(int $idEvaluacion, int $idPersona): array {
        $intentosRealizados = $this->model->contarIntentosPersonaEvaluacion($idEvaluacion, $idPersona);
        $intentosDisponibles = max(0, self::MAX_INTENTOS - $intentosRealizados);
        $puedeResponder = $intentosDisponibles > 0;

        $tiempoInicio = 0;
        $tiempoRestante = 0;

        if ($puedeResponder) {
            $intentoEsperado = $intentosRealizados + 1;
            $timer = $this->obtenerTimerIntento($idEvaluacion);
            if ((int)($timer['attempt'] ?? 0) !== $intentoEsperado || (int)($timer['started_at'] ?? 0) <= 0) {
                $timer = $this->setTimerIntento($idEvaluacion, $intentoEsperado, time());
            }

            $tiempoInicio = (int)($timer['started_at'] ?? 0);
            $tiempoRestante = max(0, self::MAX_SEGUNDOS_INTENTO - (time() - $tiempoInicio));
        }

        return [
            'intentos_realizados' => $intentosRealizados,
            'intentos_disponibles' => $intentosDisponibles,
            'max_intentos' => self::MAX_INTENTOS,
            'tiempo_maximo_segundos' => self::MAX_SEGUNDOS_INTENTO,
            'tiempo_inicio' => $tiempoInicio,
            'tiempo_restante' => $tiempoRestante,
            'puede_responder' => $puedeResponder,
        ];
    }

    private function obtenerTimerIntento(int $idEvaluacion): array {
        $timers = $_SESSION['discipular_eval_timers'] ?? [];
        if (!is_array($timers)) {
            return [];
        }

        return (array)($timers[$idEvaluacion] ?? []);
    }

    private function setTimerIntento(int $idEvaluacion, int $attempt, int $startedAt): array {
        if (!isset($_SESSION['discipular_eval_timers']) || !is_array($_SESSION['discipular_eval_timers'])) {
            $_SESSION['discipular_eval_timers'] = [];
        }

        $_SESSION['discipular_eval_timers'][$idEvaluacion] = [
            'attempt' => (int)$attempt,
            'started_at' => (int)$startedAt,
        ];

        return $_SESSION['discipular_eval_timers'][$idEvaluacion];
    }

    private function limpiarTimerIntento(int $idEvaluacion): void {
        if (isset($_SESSION['discipular_eval_timers'][$idEvaluacion])) {
            unset($_SESSION['discipular_eval_timers'][$idEvaluacion]);
        }
    }
}
