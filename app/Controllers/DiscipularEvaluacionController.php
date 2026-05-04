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
        $puedeVer = $esAdmin || AuthController::tienePermiso('discipular_evaluaciones', 'ver');
        $puedeCrear = !$esDiscipulo && ($esAdmin || AuthController::tienePermiso('discipular_evaluaciones', 'crear'));
        $puedeEditar = !$esDiscipulo && ($esAdmin || AuthController::tienePermiso('discipular_evaluaciones', 'editar'));
        $puedeEliminar = !$esDiscipulo && ($esAdmin || AuthController::tienePermiso('discipular_evaluaciones', 'eliminar'));
        $puedeConfigurarFechas = $this->tienePermisoConfigurarFechas();
        $puedeGestionar = $puedeCrear || $puedeEditar || $puedeEliminar;

        if (!$puedeVer) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $contextoMaterial = $this->obtenerContextoMaterialDesdeRequest();
        if (empty($contextoMaterial)) {
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
        }

        $idPersona = (int)($_SESSION['usuario_id'] ?? 0);
        $idEvaluacionSeleccionada = (int)($_GET['evaluacion'] ?? 0);
        $contextoDesdeMaterial = true;
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
            $evaluaciones = array_values(array_filter($evaluaciones, static function($evaluacion) use ($nivelesPermitidos) {
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
        if ($puedeGestionar && $evaluacionSeleccionada) {
            $resultadosEvaluacion = $this->model->listarResultadosPorEvaluacion((int)$evaluacionSeleccionada['Id_Evaluacion']);
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

        $this->view('home/discipular_evaluaciones', [
            'pageTitle' => 'Discipular - Evaluaciones',
            'es_admin' => $esAdmin,
            'es_discipulo' => $esDiscipulo,
            'puede_gestionar' => $puedeGestionar,
            'evaluaciones' => $evaluaciones,
            'evaluacion_seleccionada' => $evaluacionSeleccionada,
            'resultados_usuario' => $resultadosUsuario,
            'resultados_evaluacion' => $resultadosEvaluacion,
            'resumen_capacitacion_por_nivel' => $resumenCapacitacionPorNivel,
            'estado_intento' => $estadoIntento,
            'niveles_permitidos' => $nivelesPermitidos,
            'clases_links' => $this->construirLinksClases($nivelesPermitidos),
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
        $modoRespuestas = strtolower(trim((string)($_POST['modo_respuestas'] ?? 'mixta')));
        if (!in_array($modoRespuestas, ['cerrada', 'abierta', 'mixta'], true)) {
            $modoRespuestas = 'mixta';
        }
        $preguntasRaw = $_POST['preguntas'] ?? [];
        $puedeConfigurarFechas = $this->tienePermisoConfigurarFechas();

        $fechaHabilitacionInicio = '';
        $fechaHabilitacionFin = '';
        if ($puedeConfigurarFechas) {
            $fechaHabilitacionInicio = $this->normalizarFechaYmd($_POST['fecha_habilitacion_inicio'] ?? '');
            $fechaHabilitacionFin = $this->normalizarFechaYmd($_POST['fecha_habilitacion_fin'] ?? '');

            if ($fechaHabilitacionInicio !== '' && $fechaHabilitacionFin !== '' && strcmp($fechaHabilitacionInicio, $fechaHabilitacionFin) > 0) {
                $this->redirigirConMensaje('La fecha inicial no puede ser mayor que la final.', 'error');
            }
        }

        if ($titulo === '' || $nivel <= 0 || $moduloNumero <= 0) {
            $this->redirigirConMensaje('Completa título, nivel y módulo.', 'error');
        }

        $mapaLecciones = $this->obtenerMapaLeccionesMaterial();
        $leccionesDisponibles = $this->obtenerLeccionesParaNivelModulo($mapaLecciones, $nivel, $moduloNumero);
        if (!empty($leccionesDisponibles) && !in_array($leccion, $leccionesDisponibles, true)) {
            $this->redirigirConMensaje('Selecciona una lección válida del material cargado para ese nivel y módulo.', 'error');
        }

        $preguntas = $this->normalizarPreguntas((array)$preguntasRaw, $modoRespuestas);
        if (count($preguntas) < 5) {
            $this->redirigirConMensaje('La evaluación debe tener mínimo 5 preguntas válidas.', 'error');
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

        $this->redirigirConMensaje('Evaluación creada correctamente.', 'success');
    }

    private function normalizarPreguntas(array $preguntasRaw, string $modoRespuestas = 'mixta'): array {
        $salida = [];
        $modo = in_array($modoRespuestas, ['cerrada', 'abierta', 'mixta'], true) ? $modoRespuestas : 'mixta';

        foreach ($preguntasRaw as $pregunta) {
            $enunciado = trim((string)($pregunta['enunciado'] ?? ''));
            $tipoRaw = strtolower(trim((string)($pregunta['tipo'] ?? 'cerrada')));
            $tipo = $tipoRaw === 'abierta' ? 'abierta' : 'cerrada';

            if ($modo === 'abierta') {
                $tipo = 'abierta';
            } elseif ($modo === 'cerrada') {
                $tipo = 'cerrada';
            }

            if ($enunciado === '') {
                continue;
            }

            if ($tipo === 'abierta') {
                $salida[] = [
                    'tipo' => 'abierta',
                    'enunciado' => $enunciado,
                ];
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

    private function normalizarRespuestaCorrecta(array $preguntaRaw, array $opciones): string {
        $clave = strtolower(trim((string)($preguntaRaw['respuesta_correcta'] ?? '')));
        if ($clave !== '' && isset($opciones[$clave])) {
            return $clave;
        }

        $primera = array_key_first($opciones);
        return is_string($primera) ? strtolower($primera) : 'a';
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
        $respuestasAbiertas = (array)($_POST['respuesta_abierta'] ?? []);
        $respondidas = 0;
        $correctas = 0;
        $total = count($preguntas);
        $respuestasGuardadas = [];

        foreach ($preguntas as $index => $pregunta) {
            $indice = (string)$index;
            $tipoPregunta = strtolower(trim((string)($pregunta['tipo'] ?? 'cerrada')));
            if ($tipoPregunta === 'abierta') {
                $respuestaTexto = trim((string)($respuestasAbiertas[$indice] ?? ''));
                $estaRespondida = $respuestaTexto !== '';
                if ($estaRespondida) {
                    $respondidas++;
                    $correctas++;
                }

                $respuestasGuardadas[] = [
                    'tipo' => 'abierta',
                    'pregunta' => (string)($pregunta['enunciado'] ?? ''),
                    'respuesta_texto' => $respuestaTexto,
                    'respondida' => $estaRespondida,
                ];
                continue;
            }

            $opciones = (array)($pregunta['opciones'] ?? []);
            $respuestaClave = trim((string)($respuestasCerradas[$indice] ?? ''));
            $estaRespondida = ($respuestaClave !== '' && isset($opciones[$respuestaClave]));
            $correctaEsperada = strtolower(trim((string)($pregunta['respuesta_correcta'] ?? '')));
            if ($estaRespondida) {
                $respondidas++;
            }

            $esCorrecta = false;
            if ($estaRespondida) {
                // Compatibilidad: evaluaciones antiguas sin respuesta correcta definida.
                if ($correctaEsperada === '' || !isset($opciones[$correctaEsperada])) {
                    $esCorrecta = true;
                } else {
                    $esCorrecta = ($respuestaClave === $correctaEsperada);
                }
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

        $minimoRequerido = min(4, $total);
        if ($respondidas < $minimoRequerido) {
            $this->redirigirConMensaje('Debes responder al menos ' . $minimoRequerido . ' pregunta(s) para enviar la evaluación.', 'error', $idEvaluacion);
        }

        $puntaje = $total > 0 ? round(($correctas / $total) * 100, 2) : 0.0;
        $puntajeMinimo = max(80.0, (float)($evaluacion['Puntaje_Minimo'] ?? 80));
        $aprobado = $puntaje >= $puntajeMinimo;

        $this->model->guardarResultado([
            'id_evaluacion' => $idEvaluacion,
            'id_persona' => $idPersona,
            'intento_numero' => $intentoEsperado,
            'respuestas_json' => json_encode($respuestasGuardadas, JSON_UNESCAPED_UNICODE),
            'puntaje' => $puntaje,
            'total_preguntas' => $total,
            'correctas' => $correctas,
            'aprobado' => $aprobado,
        ]);

        $this->limpiarTimerIntento($idEvaluacion);

        $mensaje = $aprobado
            ? 'Evaluación enviada. Correctas: ' . $correctas . ' de ' . $total . '. Puntaje: ' . $puntaje . '%. ¡Aprobaste!'
            : 'Evaluación enviada. Correctas: ' . $correctas . ' de ' . $total . '. Puntaje: ' . $puntaje . '%. No alcanzaste el mínimo.';

        $this->redirigirConMensaje($mensaje, $aprobado ? 'success' : 'error', $idEvaluacion);
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

        // Reglas de publicación: si no tiene ventana completa, no debe ser visible.
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

    private function redirigirConMensaje(string $mensaje, string $tipo, int $idEvaluacion = 0): void {
        $contexto = $this->obtenerContextoMaterialDesdeRequest();
        $queryContexto = $this->construirQueryContextoMaterial($contexto);
        $queryEvaluacion = $idEvaluacion > 0 ? '&evaluacion=' . $idEvaluacion : '';
        $this->redirect('home/discipular/evaluaciones' . $queryContexto . $queryEvaluacion . '&mensaje=' . urlencode($mensaje) . '&tipo=' . urlencode($tipo));
    }

    private function obtenerContextoMaterialDesdeRequest(): array {
        $fromMaterialRaw = $_POST['from_material'] ?? $_GET['from_material'] ?? '';
        $nivelRaw = $_POST['nivel'] ?? $_POST['filtro_nivel_contexto'] ?? $_GET['nivel'] ?? 0;
        $moduloRaw = $_POST['modulo'] ?? $_POST['modulo_numero'] ?? $_POST['filtro_modulo_contexto'] ?? $_GET['modulo'] ?? 0;
        $leccionRaw = $_POST['leccion'] ?? $_POST['filtro_leccion_contexto'] ?? $_GET['leccion'] ?? '';

        $fromMaterial = !empty($fromMaterialRaw);
        $nivel = (int)$nivelRaw;
        $modulo = (int)$moduloRaw;

        if (!$fromMaterial || !$this->esContextoNivelModuloValido($nivel, $modulo)) {
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
        $links = [];
        foreach ($nivelesPermitidos as $nivel) {
            $links[] = [
                'nivel' => (int)$nivel,
                'label' => 'Conectarme a clase Nivel ' . (int)$nivel,
                'url' => PUBLIC_URL . '?url=escuelas_formacion/registro-publico&programa=capacitacion_destino',
            ];
        }
        return $links;
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
