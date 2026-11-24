<?php
/**
 * Controlador Celula
 */

require_once APP . '/Models/Celula.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/Asistencia.php';
require_once APP . '/Models/Celugrama.php';
require_once APP . '/Models/PersonaSalidaCelula.php';
require_once APP . '/Models/EscuelaFormacionInscripcion.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Helpers/DataIsolation.php';
require_once APP . '/Helpers/ProgresoFormacionFlujo.php';

class CelulaController extends BaseController {
    private $celulaModel;
    private $personaModel;
    private $ministerioModel;
    private $asistenciaModel;
    private $celugramaModel;
    private $salidaCelulaModel;
    private $inscripcionFormacionModel;

    public function __construct() {
        $this->celulaModel = new Celula();
        $this->personaModel = new Persona();
        $this->ministerioModel = new Ministerio();
        $this->asistenciaModel = new Asistencia();
        $this->celugramaModel = new Celugrama();
        $this->salidaCelulaModel = new PersonaSalidaCelula();
        $this->inscripcionFormacionModel = new EscuelaFormacionInscripcion();
    }

    /**
     * Verifica si la célula está dentro de la cobertura del usuario actual.
     */
    private function puedeAccederCelula($idCelula) {
        $idCelula = (int)$idCelula;
        if ($idCelula <= 0) {
            return false;
        }

        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        return $this->celulaModel->existsByIdWithRole($idCelula, $filtroCelulas);
    }

    /**
     * Datos de ficha según diagrama. Sin valor_sobre.
     * Entregó sobre / reportó célula se marcan en Asistencias (por semana).
     */
    private function datosFichaDesdePost(array $post, ?array $antes = null): array {
        $idLider = trim((string)($post['id_lider'] ?? ''));
        $idLider = $idLider !== '' ? (int)$idLider : null;
        if ($idLider === null && $antes && !empty($antes['Id_Lider'])) {
            $idLider = (int)$antes['Id_Lider'];
        }
        if (DataIsolation::esLiderCelula() && $antes === null) {
            $idLider = (int)($_SESSION['usuario_id'] ?? $idLider);
            if ($idLider <= 0) {
                $idLider = null;
            }
        }

        $idPastor = (int)($post['id_pastor'] ?? 0);
        if ($idPastor <= 0 && $antes) {
            $idPastor = (int)($antes['Id_Pastor'] ?? 0);
        }
        $nombrePastor = '';
        if ($idPastor > 0) {
            $pastor = $this->personaModel->getById($idPastor);
            $nombrePastor = trim((string)($pastor['Nombre'] ?? '') . ' ' . (string)($pastor['Apellido'] ?? ''));
        } elseif ($antes) {
            $nombrePastor = trim((string)($antes['Pastor_Principal'] ?? ''));
        }

        $idAnfitrion = trim((string)($post['id_anfitrion'] ?? ''));
        $idAnfitrion = $idAnfitrion !== '' ? (int)$idAnfitrion : null;
        $anfitrionTexto = trim((string)($post['anfitrion_search'] ?? ''));
        if ($idAnfitrion === null && $antes && !empty($antes['Id_Anfitrion']) && $anfitrionTexto !== '') {
            $nombreAntes = trim((string)($antes['Nombre_Anfitrion'] ?? ''));
            if ($nombreAntes !== '' && mb_strtolower($anfitrionTexto) === mb_strtolower($nombreAntes)) {
                $idAnfitrion = (int)$antes['Id_Anfitrion'];
            }
        }
        $telefonoAnfitrion = null;
        if ($idAnfitrion > 0) {
            $anfitrion = $this->personaModel->getById($idAnfitrion);
            $tel = trim((string)($anfitrion['Telefono'] ?? ''));
            $telefonoAnfitrion = $tel !== '' ? $tel : null;
        }

        $idMinisterio = (int)($post['id_ministerio'] ?? 0);
        if ($idMinisterio <= 0 && $idLider) {
            $lider = $this->personaModel->getById($idLider);
            $idMinisterio = (int)($lider['Id_Ministerio'] ?? 0);
        }

        $estado = strtolower(trim((string)($post['estado_celula'] ?? 'activa')));
        $estado = $estado === 'cerrada' ? 'Cerrada' : 'Activa';
        $fechaApertura = trim((string)($post['fecha_apertura'] ?? ''));
        $fechaCierre = trim((string)($post['fecha_cierre'] ?? ''));
        $observacionesCierre = trim((string)($post['observaciones_cierre'] ?? ''));
        if ($estado === 'Activa') {
            $fechaCierre = null;
            $observacionesCierre = null;
        } elseif ($fechaCierre === '') {
            $fechaCierre = date('Y-m-d');
        }

        $tipoCelulaInput = strtolower(trim((string)($post['tipo_celula'] ?? '')));
        if ($tipoCelulaInput !== '') {
            $esAntiguo = in_array($tipoCelulaInput, ['antigua', 'antiguo', '1'], true) ? 1 : 0;
        } elseif ($antes !== null && isset($antes['Es_Antiguo'])) {
            $esAntiguo = ((int)$antes['Es_Antiguo'] === 1) ? 1 : 0;
        } else {
            $esAntiguo = 0;
        }

        $data = [
            'Nombre_Celula' => $post['nombre_celula'] ?? '',
            'Direccion_Celula' => $post['direccion_celula'] ?? '',
            'Dia_Reunion' => $post['dia_reunion'] ?? null,
            'Hora_Reunion' => $post['hora_reunion'] ?? null,
            'Id_Lider' => $idLider,
            'Id_Pastor' => $idPastor > 0 ? $idPastor : null,
            'Pastor_Principal' => $nombrePastor !== '' ? $nombrePastor : null,
            'Id_Lider_Inmediato' => trim((string)($post['id_lider_inmediato'] ?? '')) !== ''
                ? (int)$post['id_lider_inmediato']
                : (($antes && !empty($antes['Id_Lider_Inmediato'])) ? (int)$antes['Id_Lider_Inmediato'] : null),
            'Barrio' => trim((string)($post['barrio'] ?? '')) !== '' ? $post['barrio'] : null,
            'Red' => trim((string)($post['red'] ?? '')) !== '' ? $post['red'] : null,
            'Id_Anfitrion' => $idAnfitrion,
            'Telefono_Anfitrion' => $telefonoAnfitrion,
            'Es_Antiguo' => $esAntiguo,
            'Estado_Celula' => $estado,
            'Fecha_Cierre' => $fechaCierre,
            'Observaciones_Cierre' => $observacionesCierre !== '' ? $observacionesCierre : null,
            'Id_Ministerio' => $idMinisterio > 0 ? $idMinisterio : null,
        ];

        if ($fechaApertura !== '') {
            $data['Fecha_Apertura'] = $fechaApertura . ' 00:00:00';
        } elseif ($antes === null) {
            $data['Fecha_Apertura'] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    private function datosFormularioCelula(?array $celula = null): array {
        return [
            'celula' => $celula,
            'ministerios' => $this->ministerioModel->getAll(),
        ];
    }

    /**
     * Filtro de personas visibles para búsquedas AJAX.
     */
    private function getFiltroPersonasBusqueda() {
        return DataIsolation::generarFiltroPersonas();
    }

    public function index() {
        if (!AuthController::puede('celulas:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        // Generar filtro segun el rol del usuario
        $filtroCelulas = DataIsolation::generarFiltroCelulas();
            // ...existing code...

        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';

        // Base para opciones de filtros según visibilidad por rol
        $celulasBase = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);

        $ministeriosDisponibles = [];
        $ministerioIdsPermitidos = [];
        $lideresDisponibles = [];
        $liderIdsPermitidos = [];

        foreach ($celulasBase as $celulaBase) {
            $idMinisterioLider = (int)($celulaBase['Id_Ministerio_Lider'] ?? 0);
            $nombreMinisterioLider = trim((string)($celulaBase['Nombre_Ministerio_Lider'] ?? ''));
            if ($idMinisterioLider > 0 && $nombreMinisterioLider !== '') {
                $ministeriosDisponibles[$idMinisterioLider] = [
                    'Id_Ministerio' => $idMinisterioLider,
                    'Nombre_Ministerio' => $nombreMinisterioLider
                ];
                $ministerioIdsPermitidos[$idMinisterioLider] = true;
            }

            $idLider = (int)($celulaBase['Id_Lider'] ?? 0);
            $nombreLider = trim((string)($celulaBase['Nombre_Lider'] ?? ''));
            $idMinisterioLider = (int)($celulaBase['Id_Ministerio_Lider'] ?? 0);
            if ($idLider > 0 && $nombreLider !== '') {
                $lideresDisponibles[$idLider] = [
                    'Id_Persona' => $idLider,
                    'Nombre_Completo' => $nombreLider,
                    'Id_Ministerio' => $idMinisterioLider
                ];
                $liderIdsPermitidos[$idLider] = true;
            }
        }

        ksort($ministeriosDisponibles);
        ksort($lideresDisponibles);

        $filtroMinisterio = ($filtroMinisterio !== '' && isset($ministerioIdsPermitidos[(int)$filtroMinisterio])) ? (int)$filtroMinisterio : '';
        $filtroLider = ($filtroLider !== '' && isset($liderIdsPermitidos[(int)$filtroLider])) ? (int)$filtroLider : '';

        // Obtener células con aislamiento y filtros
        $celulas = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas, $filtroMinisterio, $filtroLider);

        $celulaIds = array_map(static function ($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $celulas);

        $miembros = $this->personaModel->getActivosByCelulaIds($celulaIds);
        $miembrosPorCelula = [];
        foreach ($miembros as $miembro) {
            $idCelula = (int)($miembro['Id_Celula'] ?? 0);
            if ($idCelula <= 0) {
                continue;
            }

            if (!isset($miembrosPorCelula[$idCelula])) {
                $miembrosPorCelula[$idCelula] = [];
            }
            $miembrosPorCelula[$idCelula][] = $miembro;
        }

        $sections = [];
        foreach ($celulas as $celula) {
            $idCelula = (int)($celula['Id_Celula'] ?? 0);
            $idLiderCelula = (int)($celula['Id_Lider'] ?? 0);
            $miembrosCelula = $miembrosPorCelula[$idCelula] ?? [];

            $rows = [];
            $nro = 1;
            foreach ($miembrosCelula as $miembro) {
                $idPersonaMiembro = (int)($miembro['Id_Persona'] ?? 0);
                if ($idLiderCelula > 0 && $idPersonaMiembro === $idLiderCelula) {
                    continue;
                }
                $nombreCompleto = trim(((string)($miembro['Nombre'] ?? '')) . ' ' . ((string)($miembro['Apellido'] ?? '')));
                $rows[] = [
                    'nro' => $nro++,
                    'id_persona' => (int)$miembro['Id_Persona'],
                    'nombre' => $nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre',
                    'telefono' => (string)($miembro['Telefono'] ?? ''),
                    'documento' => (string)($miembro['Numero_Documento'] ?? '')
                ];
            }

            $sections[] = [
                'id_celula' => $idCelula,
                'label' => (string)($celula['Nombre_Celula'] ?? 'Célula sin nombre'),
                'id_ministerio' => (int)($celula['Id_Ministerio_Lider'] ?? 0),
                'ministerio' => (string)($celula['Nombre_Ministerio_Lider'] ?? 'Sin ministerio'),
                'lider' => (string)($celula['Nombre_Lider'] ?? 'Sin líder'),
                'anfitrion' => (string)($celula['Nombre_Anfitrion'] ?? 'Sin anfitrión'),
                'direccion' => (string)($celula['Direccion_Celula'] ?? ''),
                'dia' => (string)($celula['Dia_Reunion'] ?? ''),
                'hora' => (string)($celula['Hora_Reunion'] ?? ''),
                'rows' => $rows,
                'total_personas' => count($rows),
                'estado' => (string)($celula['Estado_Celula'] ?? 'Activa'),
                'entrego_sobre' => null,
            ];
        }

        $puedeMarcarEntregoSobre = DataIsolation::tieneAccesoTotal();
        $diaNHoy = (int)date('N');
        $lunesActualTs = strtotime('-' . ($diaNHoy - 1) . ' days');
        $lunesVencidaTs = strtotime('-7 days', $lunesActualTs);
        $semanaInicioPorDefecto = date('Y-m-d', $lunesVencidaTs);

        $fechaSemanaSobre = trim((string)($_GET['semana_sobre'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSemanaSobre)) {
            $fechaSemanaSobre = $semanaInicioPorDefecto;
        }
        $tsSemanaSobre = strtotime($fechaSemanaSobre);
        if ($tsSemanaSobre === false) {
            $tsSemanaSobre = $lunesVencidaTs;
        }
        $diaNSobre = (int)date('N', $tsSemanaSobre);
        $semanaInicioSobreTs = strtotime('-' . ($diaNSobre - 1) . ' days', $tsSemanaSobre);
        $semanaInicioSobre = date('Y-m-d', $semanaInicioSobreTs);
        $semanaFinSobre = date('Y-m-d', strtotime('+6 days', $semanaInicioSobreTs));
        $esSemanaVencidaSobre = $semanaInicioSobre === $semanaInicioPorDefecto;

        if ($puedeMarcarEntregoSobre && $sections !== []) {
            $this->asistenciaModel->ensureEntregaSobreTableExists();
            $estadoEntregoSobre = $this->asistenciaModel->getEstadoEntregoSobrePorCelulaSemana(
                array_map(static function ($section) {
                    return (int)($section['id_celula'] ?? 0);
                }, $sections),
                $semanaInicioSobre
            );
            foreach ($sections as &$sectionSobre) {
                $idCelulaSobre = (int)($sectionSobre['id_celula'] ?? 0);
                $sectionSobre['entrego_sobre'] = array_key_exists($idCelulaSobre, $estadoEntregoSobre)
                    ? (!empty($estadoEntregoSobre[$idCelulaSobre]) ? 1 : 0)
                    : null;
            }
            unset($sectionSobre);
        }

        $filasSeguimiento = $this->personaModel->getEnSeguimientoWithRole(
            DataIsolation::generarFiltroPersonas(),
            $filtroMinisterio,
            $filtroLider
        );
        $ministerioPorCelula = [];
        foreach ($sections as $sectionMinCel) {
            $idCelMap = (int)($sectionMinCel['id_celula'] ?? 0);
            if ($idCelMap <= 0) {
                continue;
            }
            $minCel = trim((string)($sectionMinCel['ministerio'] ?? ''));
            $ministerioPorCelula[$idCelMap] = $minCel !== '' ? $minCel : 'Sin ministerio';
        }
        $seguimientoPorMinisterio = [];
        $seguimientoPorCelula = [];
        foreach ($filasSeguimiento as $filaSeg) {
            $itemSeg = $this->mapearPersonaEnSeguimiento($filaSeg);
            $idCelSeg = (int)$itemSeg['id_celula'];
            if ($idCelSeg > 0 && isset($ministerioPorCelula[$idCelSeg])) {
                $itemSeg['ministerio'] = $ministerioPorCelula[$idCelSeg];
            }
            $minKey = (string)$itemSeg['ministerio'];
            if (!isset($seguimientoPorMinisterio[$minKey])) {
                $seguimientoPorMinisterio[$minKey] = [];
            }
            $seguimientoPorMinisterio[$minKey][] = $itemSeg;
            if ($idCelSeg > 0) {
                if (!isset($seguimientoPorCelula[$idCelSeg])) {
                    $seguimientoPorCelula[$idCelSeg] = [];
                }
                $seguimientoPorCelula[$idCelSeg][] = $itemSeg;
            }
        }

        $this->view('celulas/lista', [
            'celulas' => $celulas,
            'sections' => $sections,
            'seguimientoPorMinisterio' => $seguimientoPorMinisterio,
            'seguimientoPorCelula' => $seguimientoPorCelula,
            'ministerios_disponibles' => array_values($ministeriosDisponibles),
            'lideres_disponibles' => array_values($lideresDisponibles),
            'filtro_ministerio_actual' => (string)$filtroMinisterio,
            'filtro_lider_actual' => (string)$filtroLider,
            'puede_marcar_entrego_sobre' => $puedeMarcarEntregoSobre,
            'semana_inicio_sobre' => $semanaInicioSobre,
            'semana_fin_sobre' => $semanaFinSobre,
            'semana_sobre_referencia' => $semanaInicioSobre,
            'es_semana_vencida_sobre' => $esSemanaVencidaSobre,
        ]);
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::puede('celulas:crear')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ficha = $this->datosFichaDesdePost($_POST, null);
            if (($ficha['Estado_Celula'] ?? '') === 'Cerrada'
                && trim((string)($ficha['Observaciones_Cierre'] ?? '')) === ''
            ) {
                $this->view('celulas/formulario', array_merge(
                    $this->datosFormularioCelula($ficha),
                    ['error_cierre' => 'Indica por qué se cierra la célula.']
                ));
                return;
            }
            $this->celulaModel->create($ficha);
            $this->redirect('celulas&guardado=1');
        } else {
            $this->view('celulas/formulario', $this->datosFormularioCelula());
        }
    }

    public function editar() {
        // Verificar permiso de editar
        if (!AuthController::puede('celulas:editar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }
        
        $id = $_POST['id_celula'] ?? $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('celulas');
        }

        if (!$this->puedeAccederCelula($id)) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $celulaAntes = $this->celulaModel->getById($id);
            if (!$celulaAntes) {
                $this->redirect('celulas');
            }

            $ficha = $this->datosFichaDesdePost($_POST, $celulaAntes);
            if (($ficha['Estado_Celula'] ?? '') === 'Cerrada'
                && trim((string)($ficha['Observaciones_Cierre'] ?? '')) === ''
            ) {
                $this->view('celulas/formulario', array_merge(
                    $this->datosFormularioCelula(array_merge($celulaAntes, $ficha)),
                    ['error_cierre' => 'Indica por qué se cierra la célula.']
                ));
                return;
            }

            $this->celulaModel->update($id, $ficha);
            $this->redirect('celulas&guardado=1');
        } else {
            $this->view('celulas/formulario', $this->datosFormularioCelula($this->celulaModel->getById($id)));
        }
    }

    public function detalle() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('celulas');
        }

        if (!$this->puedeAccederCelula($id)) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $celula = $this->celulaModel->getWithMembers($id);
        if ($celula && !empty($celula['miembros'])) {
            $idsPersona = array_column($celula['miembros'], 'Id_Persona');
            $conteos = $this->asistenciaModel->getConteoAsistenciasPorPersonaYCelula(
                $idsPersona,
                (int)$id
            );

            $flagsFormacion = [];
            try {
                $flagsFormacion = $this->inscripcionFormacionModel->getFlagsFormacionPorIdsPersonas($idsPersona);
            } catch (Throwable $e) {
                $flagsFormacion = [];
            }

            $ordenProceso = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
            foreach ($celula['miembros'] as &$miembro) {
                $idPersona = (int)($miembro['Id_Persona'] ?? 0);
                $total = (int)($conteos[$idPersona] ?? 0);
                $miembro['Total_Asistencias'] = $total;
                if ($total <= 0) {
                    $miembro['Indicador_Asistencia'] = 'Sin asistencia';
                } elseif ($total >= 3) {
                    $miembro['Indicador_Asistencia'] = 'Frecuente';
                } else {
                    $miembro['Indicador_Asistencia'] = 'Regular';
                }
                $proceso = $this->personaModel->resolverProcesoVisible($miembro);
                $flags = $flagsFormacion[$idPersona] ?? [];
                if (!empty($flags['capacitacion_destino'])
                    && array_search($proceso, $ordenProceso, true) < array_search('Discipular', $ordenProceso, true)
                ) {
                    $proceso = 'Discipular';
                } elseif (
                    (empty($flags['capacitacion_destino']))
                    && (!empty($flags['universidad_vida']) || !empty($flags['encuentro']) || !empty($flags['bautismo']))
                    && array_search($proceso, $ordenProceso, true) < array_search('Consolidar', $ordenProceso, true)
                ) {
                    $proceso = 'Consolidar';
                }
                $miembro['Proceso'] = $proceso;
                $miembro['Proceso_Detalle'] = $this->personaModel->describirProcesoConDetalle($miembro, $proceso, $flags);
            }
            unset($miembro);
        }

        $personasEnSeguimiento = [];
        $filasSeguimientoCelula = $this->personaModel->getEnSeguimientoWithRole(DataIsolation::generarFiltroPersonas());
        foreach ($filasSeguimientoCelula as $filaSegCel) {
            if ((int)($filaSegCel['Id_Celula'] ?? 0) === (int)$id) {
                $personasEnSeguimiento[] = $this->mapearPersonaEnSeguimiento($filaSegCel);
            }
        }

        $trimestreClave = trim((string)($_GET['trimestre'] ?? ''));
        $celugrama = $this->construirCelugramaDetalle((int)$id, is_array($celula) ? $celula : [], $trimestreClave);
        $mensajeCelugrama = trim((string)($_GET['celugrama'] ?? ''));
        $mensajeReporte = trim((string)($_GET['reportado'] ?? ''));
        $abrirReporte = trim((string)($_GET['reporte'] ?? '')) === '1';

        $fechaReporteDefault = $this->fechaPorDefectoReporteCelugrama();
        $fechaSolicitada = trim((string)($_GET['fecha'] ?? ''));
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaSolicitada)) {
            $fechaReporteDefault = $fechaSolicitada;
        }

        $this->view('celulas/detalle', [
            'celula' => $celula,
            'personasEnSeguimiento' => $personasEnSeguimiento,
            'celugrama' => $celugrama,
            'mensajeCelugrama' => $mensajeCelugrama,
            'mensajeReporte' => $mensajeReporte,
            'abrirReporte' => $abrirReporte,
            'fechaReporteDefault' => $fechaReporteDefault,
            'puedeReportarCelula' => AuthController::puede('asistencias:crear'),
            'celulasMigracion' => $this->celulasMismoMinisterioParaMigrar((int)$id),
        ]);
    }

    /**
     * Otras células del mismo ministerio (para migrar desde Celugrama).
     *
     * @return list<array{id:int,nombre:string}>
     */
    private function celulasMismoMinisterioParaMigrar(int $idCelulaActual): array {
        $idCelulaActual = (int)$idCelulaActual;
        if ($idCelulaActual <= 0) {
            return [];
        }

        $filas = $this->celulaModel->getAllWithMemberCountAndRole(DataIsolation::generarFiltroCelulas());
        $minActual = 0;
        foreach ($filas as $fila) {
            if ((int)($fila['Id_Celula'] ?? 0) === $idCelulaActual) {
                $minActual = (int)($fila['Id_Ministerio_Lider'] ?? $fila['Id_Ministerio'] ?? 0);
                break;
            }
        }
        if ($minActual <= 0) {
            return [];
        }

        $salida = [];
        foreach ($filas as $fila) {
            $id = (int)($fila['Id_Celula'] ?? 0);
            $min = (int)($fila['Id_Ministerio_Lider'] ?? $fila['Id_Ministerio'] ?? 0);
            if ($id <= 0 || $id === $idCelulaActual || $min !== $minActual) {
                continue;
            }
            $nombre = trim((string)($fila['Nombre_Celula'] ?? ''));
            $salida[] = [
                'id' => $id,
                'nombre' => $nombre !== '' ? $nombre : ('Célula #' . $id),
            ];
        }
        usort($salida, static function (array $a, array $b): int {
            return strnatcasecmp((string)$a['nombre'], (string)$b['nombre']);
        });
        return $salida;
    }

    private function fechaPorDefectoReporteCelugrama(): string {
        $hoy = new DateTimeImmutable('today');
        $diaSemana = (int)$hoy->format('N');
        if ($diaSemana === 7) {
            return $hoy->format('Y-m-d');
        }
        return $hoy->modify('monday this week')->modify('-1 day')->format('Y-m-d');
    }

    /**
     * Guarda temas e indicadores del celugrama sin salir del detalle.
     */
    public function guardarCelugrama() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('celulas');
        }

        $idCelula = (int)($_POST['id_celula'] ?? 0);
        $trimestre = trim((string)($_POST['trimestre'] ?? ''));
        if ($idCelula <= 0 || !$this->puedeAccederCelula($idCelula)) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }
        if (!AuthController::puede('asistencias:crear') && !AuthController::puede('celulas:editar') && !AuthController::esAdministrador()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $temas = is_array($_POST['temas'] ?? null) ? $_POST['temas'] : [];
        foreach ($temas as $semana => $filaTema) {
            $semana = substr(trim((string)$semana), 0, 10);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $semana)) {
                continue;
            }
            $titulo = trim((string)($filaTema['titulo'] ?? ''));
            $texto = trim((string)($filaTema['texto'] ?? ''));
            $this->celugramaModel->guardarTema($idCelula, $semana, $titulo, $texto);
        }

        $indicadores = is_array($_POST['indicadores'] ?? null) ? $_POST['indicadores'] : [];
        foreach ($indicadores as $claveMes => $filaInd) {
            $claveMes = trim((string)$claveMes);
            if (!preg_match('/^(\d{4})-(\d{2})$/', $claveMes, $m)) {
                continue;
            }
            $anio = (int)$m[1];
            $mes = (int)$m[2];
            $invCel = max(0, (int)($filaInd['invitados_celula'] ?? 0));
            $invCol = max(0, (int)($filaInd['invitados_coliseo'] ?? 0));
            $noRealRaw = trim((string)($filaInd['celulas_no_realizadas'] ?? ''));
            $noReal = $noRealRaw === '' ? null : max(0, (int)$noRealRaw);
            $this->celugramaModel->guardarIndicadorMes($idCelula, $anio, $mes, $invCel, $invCol, $noReal);
        }

        $params = ['id' => $idCelula, 'celugrama' => 'ok'];
        if ($trimestre !== '') {
            $params['trimestre'] = $trimestre;
        }
        header('Location: ' . public_app_url('celulas/detalle', $params) . '#celugrama');
        exit;
    }

    /**
     * @param array<string, mixed> $fila
     * @return array<string, mixed>
     */
    private function mapearPersonaEnSeguimiento(array $fila): array {
        $nombre = trim(((string)($fila['Nombre'] ?? '')) . ' ' . ((string)($fila['Apellido'] ?? '')));
        $ministerio = trim((string)($fila['Nombre_Ministerio'] ?? ''));
        return [
            'Id_Persona' => (int)($fila['Id_Persona'] ?? 0),
            'Nombre' => (string)($fila['Nombre'] ?? ''),
            'Apellido' => (string)($fila['Apellido'] ?? ''),
            'nombre' => $nombre !== '' ? $nombre : 'Sin nombre',
            'telefono' => (string)($fila['Telefono'] ?? ''),
            'celula' => (string)($fila['Nombre_Celula'] ?? 'Sin célula'),
            'lider' => trim((string)($fila['Nombre_Lider'] ?? '')),
            'ministerio' => $ministerio !== '' ? $ministerio : 'Sin ministerio',
            'id_celula' => (int)($fila['Id_Celula'] ?? 0),
        ];
    }

    /**
     * Celugrama digital: resumen fácil + detalle tipo papel, sin códigos.
     *
     * @param array<string, mixed> $celula
     * @return array<string, mixed>
     */
    private function construirCelugramaDetalle(int $idCelula, array $celula, string $trimestreClave = ''): array {
        $trimestre = $this->resolverTrimestreCelugrama($trimestreClave);
        $desde = (string)$trimestre['desde'];
        $hasta = (string)$trimestre['hasta'];
        $semanas = $this->semanasTrimestreCelugrama($desde, $hasta);
        $meses = $this->mesesTrimestreCelugrama((int)$trimestre['anio'], (int)$trimestre['numero']);

        $miembros = is_array($celula['miembros'] ?? null) ? $celula['miembros'] : [];
        $idsPersona = [];
        foreach ($miembros as $miembro) {
            $idPersona = (int)($miembro['Id_Persona'] ?? 0);
            if ($idPersona > 0) {
                $idsPersona[] = $idPersona;
            }
        }

        $matriz = $this->asistenciaModel->getMatrizCelugramaPorCelula($idCelula, $desde, $hasta);
        $porPersona = is_array($matriz['por_persona'] ?? null) ? $matriz['por_persona'] : [];
        $totalesSemana = is_array($matriz['totales_semana'] ?? null) ? $matriz['totales_semana'] : [];
        $semanasReportadas = is_array($matriz['semanas_reportadas'] ?? null) ? $matriz['semanas_reportadas'] : [];
        $temasAsistencia = is_array($matriz['temas_asistencia'] ?? null) ? $matriz['temas_asistencia'] : [];

        $temasGuardados = $this->celugramaModel->getTemasPorCelulaYRango($idCelula, $desde, $hasta);
        $indicadoresGuardados = $this->celugramaModel->getIndicadoresPorCelulaYAnios(
            $idCelula,
            [(int)$trimestre['anio']]
        );

        $hechos = [];
        if ($idsPersona !== []) {
            try {
                $hechos = (new ProgresoFormacionFlujo())->hechosPorPersonas($idsPersona);
            } catch (Throwable $e) {
                $hechos = [];
            }
        }

        $filasMiembros = [];
        $conEncuentro = 0;
        $conPosEncuentro = 0;
        $conCap = 0;
        $maxAsistenciasTrimestre = 0;

        foreach ($miembros as $miembro) {
            $idPersona = (int)($miembro['Id_Persona'] ?? 0);
            if ($idPersona <= 0) {
                continue;
            }
            $hecho = is_array($hechos[$idPersona] ?? null) ? $hechos[$idPersona] : [];
            $encuentro = !empty($hecho['encuentro']);
            $posEncuentro = !empty($hecho['inscrito_uv']) || !empty($hecho['asistio_uv']);
            $bautismo = !empty($hecho['bautismo']);
            $nivel = $this->resolverNivelCapDestinoDesdeHecho($hecho);
            $cap = !empty($hecho['cap_destino']) || $nivel > 0;
            if ($encuentro) {
                $conEncuentro++;
            }
            if ($posEncuentro) {
                $conPosEncuentro++;
            }
            if ($cap) {
                $conCap++;
            }

            $asistenciasPersona = is_array($porPersona[$idPersona] ?? null) ? $porPersona[$idPersona] : [];
            $totalTrim = 0;
            $marcas = [];
            foreach ($semanas as $semana) {
                $clave = (string)$semana['inicio'];
                $vino = !empty($asistenciasPersona[$clave]);
                $marcas[$clave] = $vino;
                if ($vino) {
                    $totalTrim++;
                }
            }
            if ($totalTrim > $maxAsistenciasTrimestre) {
                $maxAsistenciasTrimestre = $totalTrim;
            }

            $nombre = trim(((string)($miembro['Nombre'] ?? '')) . ' ' . ((string)($miembro['Apellido'] ?? '')));
            $indicador = trim((string)($miembro['Indicador_Asistencia'] ?? ''));
            if ($indicador === '') {
                $totalHist = (int)($miembro['Total_Asistencias'] ?? 0);
                if ($totalHist <= 0) {
                    $indicador = 'Sin asistencia';
                } elseif ($totalHist >= 3) {
                    $indicador = 'Frecuente';
                } else {
                    $indicador = 'Regular';
                }
            }
            $proceso = trim((string)($miembro['Proceso_Detalle'] ?? $miembro['Proceso'] ?? ''));
            if ($proceso === '') {
                $proceso = 'Sin proceso';
            }
            $filasMiembros[] = [
                'id_persona' => $idPersona,
                'nombre' => $nombre !== '' ? $nombre : 'Sin nombre',
                'documento' => trim((string)($miembro['Numero_Documento'] ?? '')) ?: 'N/A',
                'telefono' => trim((string)($miembro['Telefono'] ?? '')) ?: 'N/A',
                'edad' => $this->edadDesdeFechaNacimiento((string)($miembro['Fecha_Nacimiento'] ?? '')),
                'indicador' => $indicador,
                'proceso' => $proceso,
                'total_asistencias' => (int)($miembro['Total_Asistencias'] ?? 0),
                'asistencias' => $marcas,
                'total_trimestre' => $totalTrim,
                'encuentro' => $encuentro,
                'pos_encuentro' => $posEncuentro,
                'bautismo' => $bautismo,
                'cap_destino' => $cap,
                'nivel_cap' => $nivel,
                'nivel_cap_texto' => $this->textoNivelCapDestino($nivel, $cap),
                'escalera_checklist' => (string)($miembro['Escalera_Checklist'] ?? ''),
                'en_seguimiento' => Persona::checklistIndicaEnSeguimiento($miembro['Escalera_Checklist'] ?? ''),
                'id_ministerio' => (int)($miembro['Id_Ministerio'] ?? 0),
            ];
        }

        usort($filasMiembros, static function (array $a, array $b): int {
            return strnatcasecmp((string)$a['nombre'], (string)$b['nombre']);
        });

        $serieGrafica = [];
        $sumaAsistentes = 0;
        $semanasConReporte = 0;
        foreach ($semanas as $semana) {
            $clave = (string)$semana['inicio'];
            $total = (int)($totalesSemana[$clave] ?? 0);
            $reportada = !empty($semanasReportadas[$clave]);
            if ($reportada) {
                $semanasConReporte++;
                $sumaAsistentes += $total;
            }
            $serieGrafica[] = [
                'semana' => $clave,
                'etiqueta' => (string)$semana['etiqueta_corta'],
                'mes' => (string)$semana['mes_clave'],
                'total' => $total,
                'reportada' => $reportada,
            ];
        }

        $promedioTrimestre = $semanasConReporte > 0
            ? round($sumaAsistentes / $semanasConReporte, 1)
            : 0.0;

        $temasFilas = [];
        foreach ($semanas as $semana) {
            $clave = (string)$semana['inicio'];
            $guardado = is_array($temasGuardados[$clave] ?? null) ? $temasGuardados[$clave] : [];
            $titulo = trim((string)($guardado['titulo'] ?? ''));
            $texto = trim((string)($guardado['texto'] ?? ''));
            if ($titulo === '' && isset($temasAsistencia[$clave])) {
                $titulo = trim((string)$temasAsistencia[$clave]);
            }
            $temasFilas[] = [
                'semana' => $clave,
                'etiqueta' => (string)$semana['etiqueta'],
                'mes_clave' => (string)$semana['mes_clave'],
                'mes_nombre' => (string)$semana['mes_nombre'],
                'titulo' => $titulo,
                'texto' => $texto,
                'reportada' => !empty($semanasReportadas[$clave]),
            ];
        }

        $indicadoresMes = [];
        $invitadosCelulaTrim = 0;
        $invitadosColiseoTrim = 0;
        $noRealizadasTrim = 0;
        foreach ($meses as $mesInfo) {
            $claveMes = (string)$mesInfo['clave'];
            $guardado = is_array($indicadoresGuardados[$claveMes] ?? null) ? $indicadoresGuardados[$claveMes] : [];
            $semanasMes = array_values(array_filter($semanas, static function (array $s) use ($claveMes): bool {
                return (string)$s['mes_clave'] === $claveMes;
            }));
            $reportadasMes = 0;
            $sumaMes = 0;
            foreach ($semanasMes as $sm) {
                $claveSem = (string)$sm['inicio'];
                if (!empty($semanasReportadas[$claveSem])) {
                    $reportadasMes++;
                    $sumaMes += (int)($totalesSemana[$claveSem] ?? 0);
                }
            }
            $esperadas = count($semanasMes);
            $noRealizadasAuto = max(0, $esperadas - $reportadasMes);
            $noRealizadasManual = array_key_exists('celulas_no_realizadas', $guardado)
                ? $guardado['celulas_no_realizadas']
                : null;
            $noRealizadas = $noRealizadasManual !== null ? (int)$noRealizadasManual : $noRealizadasAuto;
            $invCel = (int)($guardado['invitados_celula'] ?? 0);
            $invCol = (int)($guardado['invitados_coliseo'] ?? 0);
            $invitadosCelulaTrim += $invCel;
            $invitadosColiseoTrim += $invCol;
            $noRealizadasTrim += $noRealizadas;

            $indicadoresMes[] = [
                'clave' => $claveMes,
                'nombre' => (string)$mesInfo['nombre'],
                'anio' => (int)$mesInfo['anio'],
                'mes' => (int)$mesInfo['mes'],
                'promedio' => $reportadasMes > 0 ? round($sumaMes / $reportadasMes, 1) : 0.0,
                'semanas_reportadas' => $reportadasMes,
                'semanas_esperadas' => $esperadas,
                'invitados_celula' => $invCel,
                'invitados_coliseo' => $invCol,
                'celulas_no_realizadas' => $noRealizadas,
                'celulas_no_realizadas_auto' => $noRealizadasAuto,
                'enviados_encuentro' => $conEncuentro,
                'graduados_pos_encuentro' => $conPosEncuentro,
            ];
        }

        $maxGrafica = max(1, $maxAsistenciasTrimestre);
        foreach ($serieGrafica as $puntoGrafica) {
            $maxGrafica = max($maxGrafica, (int)($puntoGrafica['total'] ?? 0));
        }

        return [
            'trimestre' => $trimestre,
            'opciones_trimestre' => $this->opcionesTrimestreCelugrama((int)$trimestre['anio']),
            'semanas' => $semanas,
            'meses' => $meses,
            'miembros' => $filasMiembros,
            'serie_grafica' => $serieGrafica,
            'max_grafica' => $maxGrafica,
            'temas' => $temasFilas,
            'indicadores_mes' => $indicadoresMes,
            'resumen' => [
                'miembros' => count($filasMiembros),
                'promedio_asistencia' => $promedioTrimestre,
                'semanas_reportadas' => $semanasConReporte,
                'semanas_totales' => count($semanas),
                'invitados_celula' => $invitadosCelulaTrim,
                'invitados_coliseo' => $invitadosColiseoTrim,
                'celulas_no_realizadas' => $noRealizadasTrim,
                'con_encuentro' => $conEncuentro,
                'con_pos_encuentro' => $conPosEncuentro,
                'con_cap' => $conCap,
            ],
            'puede_editar' => AuthController::puede('asistencias:crear')
                || AuthController::puede('celulas:editar')
                || AuthController::esAdministrador(),
            'puede_seguimiento' => AuthController::puedeUsarSeguimientoPersonaListado(),
            'puede_guardar_seguimiento' => AuthController::puedeGuardarSeguimientoPersonaListado(),
            'puede_eliminar_miembro' => AuthController::puede('asistencias:crear')
                || AuthController::puede('asistencias:editar')
                || AuthController::esAdministrador(),
            'puede_migrar' => AuthController::puede('asistencias:crear')
                || AuthController::puede('asistencias:editar')
                || AuthController::esAdministrador(),
            'puede_eliminar_persona' => AuthController::puedeEliminarPersonaDesdeDiscipular()
                || AuthController::puede('personas:eliminar')
                || AuthController::esAdministrador(),
            'puede_escalera' => AuthController::puedeActualizarChecklistEscalera(),
            'puede_editar_persona' => AuthController::puede('personas:editar')
                || AuthController::puedeEditarPersonaDesdeDiscipular()
                || AuthController::esAdministrador(),
        ];
    }

    /**
     * @return array{clave:string,anio:int,numero:int,etiqueta:string,desde:string,hasta:string}
     */
    private function resolverTrimestreCelugrama(string $clave = ''): array {
        $ahora = new DateTimeImmutable('today');
        $anio = (int)$ahora->format('Y');
        $numero = (int)ceil(((int)$ahora->format('n')) / 3);

        if (preg_match('/^(\d{4})-Q([1-4])$/', trim($clave), $m)) {
            $anio = (int)$m[1];
            $numero = (int)$m[2];
        }

        $mesInicio = (($numero - 1) * 3) + 1;
        $mesFin = $mesInicio + 2;
        $desde = sprintf('%04d-%02d-01', $anio, $mesInicio);
        $hastaTs = strtotime(sprintf('%04d-%02d-01', $anio, $mesFin) . ' +1 month -1 day');
        $hasta = $hastaTs ? date('Y-m-d', $hastaTs) : sprintf('%04d-%02d-28', $anio, $mesFin);

        $nombres = [1 => 'Ene–Mar', 2 => 'Abr–Jun', 3 => 'Jul–Sep', 4 => 'Oct–Dic'];
        return [
            'clave' => sprintf('%04d-Q%d', $anio, $numero),
            'anio' => $anio,
            'numero' => $numero,
            'etiqueta' => ($nombres[$numero] ?? ('T' . $numero)) . ' ' . $anio,
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    /**
     * @return list<array{clave:string,etiqueta:string}>
     */
    private function opcionesTrimestreCelugrama(int $anioBase): array {
        $opciones = [];
        for ($anio = $anioBase - 1; $anio <= $anioBase + 1; $anio++) {
            for ($q = 1; $q <= 4; $q++) {
                $nombres = [1 => 'Ene–Mar', 2 => 'Abr–Jun', 3 => 'Jul–Sep', 4 => 'Oct–Dic'];
                $opciones[] = [
                    'clave' => sprintf('%04d-Q%d', $anio, $q),
                    'etiqueta' => ($nombres[$q] ?? ('T' . $q)) . ' ' . $anio,
                ];
            }
        }
        return $opciones;
    }

    /**
     * @return list<array{inicio:string,fin:string,etiqueta:string,etiqueta_corta:string,mes_clave:string,mes_nombre:string,mes:int}>
     */
    private function semanasTrimestreCelugrama(string $desde, string $hasta): array {
        $lunes = $this->asistenciaModel->lunesDeSemana($desde);
        if ($lunes === '') {
            return [];
        }
        $nombresMes = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $out = [];
        $cursor = strtotime($lunes);
        $finTs = strtotime($hasta);
        if ($cursor === false || $finTs === false) {
            return [];
        }
        while ($cursor <= $finTs) {
            $inicio = date('Y-m-d', $cursor);
            $fin = date('Y-m-d', strtotime('+6 days', $cursor));
            $mes = (int)date('n', $cursor);
            $anio = (int)date('Y', $cursor);
            $out[] = [
                'inicio' => $inicio,
                'fin' => $fin,
                'etiqueta' => date('d/m', $cursor) . '–' . date('d/m', strtotime('+6 days', $cursor)),
                'etiqueta_corta' => date('d/m', $cursor),
                'mes_clave' => sprintf('%04d-%02d', $anio, $mes),
                'mes_nombre' => $nombresMes[$mes] ?? ('Mes ' . $mes),
                'mes' => $mes,
            ];
            $cursor = strtotime('+7 days', $cursor);
            if ($cursor === false) {
                break;
            }
        }
        return $out;
    }

    /**
     * @return list<array{clave:string,anio:int,mes:int,nombre:string}>
     */
    private function mesesTrimestreCelugrama(int $anio, int $numero): array {
        $mesInicio = (($numero - 1) * 3) + 1;
        $nombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $out = [];
        for ($i = 0; $i < 3; $i++) {
            $mes = $mesInicio + $i;
            $out[] = [
                'clave' => sprintf('%04d-%02d', $anio, $mes),
                'anio' => $anio,
                'mes' => $mes,
                'nombre' => $nombres[$mes] ?? ('Mes ' . $mes),
            ];
        }
        return $out;
    }

    private function edadDesdeFechaNacimiento(string $fechaNacimiento): string {
        $fechaNacimiento = substr(trim($fechaNacimiento), 0, 10);
        if ($fechaNacimiento === '' || $fechaNacimiento === '0000-00-00') {
            return '—';
        }
        try {
            $nac = new DateTimeImmutable($fechaNacimiento);
            $hoy = new DateTimeImmutable('today');
            return (string)$nac->diff($hoy)->y;
        } catch (Throwable $e) {
            return '—';
        }
    }

    public function exportarExcel() {
        if (!AuthController::puede('celulas:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';

        $celulas = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas, $filtroMinisterio, $filtroLider);

        $rows = [];
        foreach ($celulas as $celula) {
            $rows[] = [
                (string)($celula['Nombre_Celula'] ?? ''),
                (string)($celula['Nombre_Lider'] ?? ''),
                (string)($celula['Nombre_Anfitrion'] ?? ''),
                (string)($celula['Direccion_Celula'] ?? ''),
                (string)($celula['Dia_Reunion'] ?? ''),
                (string)($celula['Hora_Reunion'] ?? ''),
                (string)($celula['Nombre_Ministerio_Lider'] ?? ''),
                (string)($celula['Total_Miembros'] ?? 0)
            ];
        }

        $this->exportCsv(
            'celulas_' . date('Ymd_His'),
            ['Celula', 'Lider', 'Anfitrion', 'Direccion', 'Dia Reunion', 'Hora Reunion', 'Ministerio', 'Total Miembros'],
            $rows
        );
    }

    /**
     * Reporte individual por célula (nuevo, aparte de detalle/listados existentes).
     */
    public function reporteIndividual() {
        if (!AuthController::puede('celulas:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('celulas');
        }
        if (!$this->puedeAccederCelula($id)) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $reporte = $this->construirDatosReporteIndividual($id);
        if ($reporte === null) {
            $this->redirect('celulas');
        }

        $this->view('celulas/reporte_individual', $reporte);
    }

    /**
     * Exportación Excel del reporte individual de todas las células de un ministerio.
     */
    public function exportarReporteIndividualMinisterio() {
        if (!AuthController::puede('celulas:exportar_datos') && !AuthController::puede('celulas:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $idMinisterio = (int)($_GET['ministerio'] ?? 0);
        if ($idMinisterio <= 0) {
            $this->redirect('celulas');
        }

        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $celulas = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas, $idMinisterio, '');
        if ($celulas === []) {
            $this->redirect('celulas&ministerio=' . $idMinisterio);
        }

        usort($celulas, static function ($a, $b) {
            $na = mb_strtolower(trim((string)($a['Nombre_Celula'] ?? '')), 'UTF-8');
            $nb = mb_strtolower(trim((string)($b['Nombre_Celula'] ?? '')), 'UTF-8');
            return strnatcasecmp($na, $nb);
        });

        $nombreMinisterio = trim((string)($celulas[0]['Nombre_Ministerio_Lider'] ?? ''));
        if ($nombreMinisterio === '') {
            $ministerio = $this->ministerioModel->getById($idMinisterio);
            $nombreMinisterio = trim((string)($ministerio['Nombre_Ministerio'] ?? 'Ministerio'));
        }

        $reportes = [];
        $totalIntegrantes = 0;
        $totalEncuentro = 0;
        $totalBautismo = 0;
        $totalCap = 0;
        $totalGanadosSemana = 0;
        $rangoSemana = '';

        foreach ($celulas as $celulaFila) {
            $idCelula = (int)($celulaFila['Id_Celula'] ?? 0);
            if ($idCelula <= 0 || !$this->puedeAccederCelula($idCelula)) {
                continue;
            }
            $reporte = $this->construirDatosReporteIndividual($idCelula);
            if ($reporte === null) {
                continue;
            }
            $reportes[] = $reporte;
            $totalIntegrantes += (int)($reporte['resumen']['total_integrantes'] ?? 0);
            $totalEncuentro += (int)($reporte['resumen']['con_encuentro'] ?? 0);
            $totalBautismo += (int)($reporte['resumen']['con_bautismo'] ?? 0);
            $totalCap += (int)($reporte['resumen']['con_cap_destino'] ?? 0);
            $totalGanadosSemana += (int)($reporte['semana']['total_ganados'] ?? 0);
            if ($rangoSemana === '') {
                $rangoSemana = (string)($reporte['semana']['rango_texto'] ?? '');
            }
        }

        if ($reportes === []) {
            $this->redirect('celulas&ministerio=' . $idMinisterio);
        }

        $colSpan = 16;
        $blank = array_fill(0, $colSpan, '');
        $filaTitulo = static function (string $texto) use ($blank): array {
            $row = $blank;
            $row[0] = $texto;
            return $row;
        };

        $rows = [];
        $rows[] = $filaTitulo('REPORTE INDIVIDUAL POR CELULAS · MINISTERIO');
        $rows[] = $filaTitulo('Ministerio: ' . $nombreMinisterio);
        $rows[] = $filaTitulo('Generado: ' . date('d/m/Y H:i'));
        $rows[] = $filaTitulo('Semana actual: ' . $rangoSemana);
        $rows[] = $filaTitulo(
            'Totales ministerio -> Celulas: ' . count($reportes)
            . ' | Integrantes: ' . $totalIntegrantes
            . ' | Ganados semana: ' . $totalGanadosSemana
            . ' | Encuentro: ' . $totalEncuentro
            . ' | Bautizados: ' . $totalBautismo
            . ' | Cap. Destino: ' . $totalCap
        );
        $rows[] = $blank;
        $rows[] = $filaTitulo('=== RESUMEN POR CELULA ===');

        foreach ($reportes as $reporte) {
            $celula = $reporte['celula'];
            $resumen = $reporte['resumen'];
            $semana = $reporte['semana'];
            $cierreOTiempo = !empty($resumen['esta_cerrada'])
                ? ('Cerrada: ' . (string)$resumen['fecha_cierre_texto'])
                : ('Abierta: ' . (string)$resumen['tiempo_abierta_texto']);

            $rows[] = [
                'RESUMEN',
                (string)($celula['Nombre_Celula'] ?? ''),
                (string)($celula['Nombre_Lider'] ?? ''),
                (string)($celula['Estado_Celula'] ?? 'Activa'),
                (string)$resumen['fecha_apertura_texto'],
                $cierreOTiempo,
                (string)((int)$semana['total_ganados']),
                'de ' . (int)$resumen['total_integrantes'] . ' activos / ' . (int)($resumen['total_retirados'] ?? 0) . ' retiros',
                '',
                '',
                '',
                '',
                (string)((int)$resumen['con_encuentro']),
                (string)((int)$resumen['con_bautismo']),
                '',
                'N1:' . (int)$resumen['cap_n1'] . ' N2:' . (int)$resumen['cap_n2'] . ' N3:' . (int)$resumen['cap_n3'],
            ];
        }

        $rows[] = $blank;
        $rows[] = $filaTitulo('=== DETALLE POR INTEGRANTE ===');

        foreach ($reportes as $reporte) {
            $celula = $reporte['celula'];
            $resumen = $reporte['resumen'];
            $semana = $reporte['semana'];
            $cierreOTiempo = !empty($resumen['esta_cerrada'])
                ? ('Cerrada: ' . (string)$resumen['fecha_cierre_texto'])
                : ('Abierta: ' . (string)$resumen['tiempo_abierta_texto']);

            $rows[] = $blank;
            $rows[] = $filaTitulo(
                'CELULA: ' . (string)($celula['Nombre_Celula'] ?? '')
                . ' | Lider: ' . (string)($celula['Nombre_Lider'] ?? 'No especificado')
                . ' | Estado: ' . (string)($celula['Estado_Celula'] ?? 'Activa')
                . ' | Apertura: ' . (string)$resumen['fecha_apertura_texto']
                . ' | ' . $cierreOTiempo
                . ' | Ganados semana: ' . (int)$semana['total_ganados']
            );

            $integrantes = is_array($reporte['integrantes'] ?? null) ? $reporte['integrantes'] : [];
            if ($integrantes === []) {
                $rows[] = [
                    'DETALLE',
                    (string)($celula['Nombre_Celula'] ?? ''),
                    (string)($celula['Nombre_Lider'] ?? ''),
                    (string)($celula['Estado_Celula'] ?? 'Activa'),
                    (string)$resumen['fecha_apertura_texto'],
                    $cierreOTiempo,
                    (string)((int)$semana['total_ganados']),
                    'Sin integrantes',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ];
                continue;
            }

            foreach ($integrantes as $fila) {
                $rows[] = [
                    'DETALLE',
                    (string)($celula['Nombre_Celula'] ?? ''),
                    (string)($celula['Nombre_Lider'] ?? ''),
                    (string)($celula['Estado_Celula'] ?? 'Activa'),
                    (string)$resumen['fecha_apertura_texto'],
                    $cierreOTiempo,
                    (string)((int)$semana['total_ganados']),
                    (string)($fila['nombre'] ?? ''),
                    (($fila['estado_en_celula'] ?? '') === 'retirado') ? 'Retirado' : 'Activo',
                    (string)($fila['tiempo_activo_texto'] ?? 'No disponible'),
                    (string)($fila['desde_activo_texto'] ?? '-'),
                    (string)($fila['fecha_retiro_texto'] ?? '-'),
                    (string)($fila['observacion_retiro'] ?? '-'),
                    !empty($fila['encuentro']) ? 'Si' : 'No',
                    !empty($fila['bautismo']) ? 'Si' : 'No',
                    (string)(
                        !empty($fila['cap_destino'])
                            ? ((string)($fila['nivel_cap_texto'] ?? 'Si'))
                            : 'No'
                    ),
                ];
            }
        }

        $slug = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $nombreMinisterio);
        $this->exportCsv(
            'reporte_celulas_ministerio_' . $slug . '_' . date('Ymd_His'),
            [
                'Seccion',
                'Celula',
                'Lider',
                'Estado celula',
                'Fecha apertura',
                'Cierre o tiempo abierta',
                'Ganados semana actual',
                'Integrante',
                'Estado persona',
                'Tiempo activo en celula',
                'Desde',
                'Fecha retiro',
                'Observacion retiro',
                'Fue a encuentro',
                'Bautizado',
                'Cap. Destino / Nivel',
            ],
            $rows,
            false
        );
    }

    /**
     * Exportación Excel del reporte individual por célula.
     */
    public function exportarReporteIndividual() {
        if (!AuthController::puede('celulas:exportar_datos') && !AuthController::puede('celulas:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0 || !$this->puedeAccederCelula($id)) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $reporte = $this->construirDatosReporteIndividual($id);
        if ($reporte === null) {
            $this->redirect('celulas');
        }

        $celula = $reporte['celula'];
        $resumen = $reporte['resumen'];
        $semana = $reporte['semana'];
        $integrantes = $reporte['integrantes'];

        $nombreCelula = (string)($celula['Nombre_Celula'] ?? 'Celula');
        $colSpan = 10;
        $blank = array_fill(0, $colSpan, '');

        $filaTitulo = static function (string $texto) use ($blank): array {
            $row = $blank;
            $row[0] = $texto;
            return $row;
        };

        $rows = [];
        $rows[] = $filaTitulo('REPORTE INDIVIDUAL DE CELULA');
        $rows[] = $filaTitulo('Celula: ' . $nombreCelula);
        $rows[] = $filaTitulo('Lider: ' . (string)($celula['Nombre_Lider'] ?? 'No especificado'));
        $rows[] = $filaTitulo('Ministerio: ' . (string)($celula['Nombre_Ministerio'] ?? 'No especificado'));
        $rows[] = $filaTitulo('Estado: ' . (string)($celula['Estado_Celula'] ?? 'Activa'));
        $rows[] = $filaTitulo('Fecha de apertura: ' . (string)$resumen['fecha_apertura_texto']);
        $rows[] = $filaTitulo(
            !empty($resumen['esta_cerrada'])
                ? ('Fecha de cierre: ' . (string)$resumen['fecha_cierre_texto'])
                : ('Tiempo abierta: ' . (string)$resumen['tiempo_abierta_texto'])
        );
        $rows[] = $filaTitulo(
            'Personas ganadas semana actual (' . (string)$semana['rango_texto'] . '): '
            . (int)$semana['total_ganados']
        );
        $rows[] = $blank;
        $rows[] = $filaTitulo(
            'Resumen formacion: de ' . (int)$resumen['total_integrantes']
            . ' integrantes activos -> Encuentro: ' . (int)$resumen['con_encuentro']
            . ' | Bautizados: ' . (int)$resumen['con_bautismo']
            . ' | Cap. Destino: ' . (int)$resumen['con_cap_destino']
            . ' (N1: ' . (int)$resumen['cap_n1']
            . ' / N2: ' . (int)$resumen['cap_n2']
            . ' / N3: ' . (int)$resumen['cap_n3'] . ')'
            . ' | Retiros: ' . (int)($resumen['total_retirados'] ?? 0)
        );
        $rows[] = $blank;
        $rows[] = $filaTitulo('Detalle por integrante');

        foreach ($integrantes as $fila) {
            $rows[] = [
                (string)($fila['nombre'] ?? ''),
                (($fila['estado_en_celula'] ?? '') === 'retirado') ? 'Retirado' : 'Activo',
                (string)($fila['tiempo_activo_texto'] ?? 'No disponible'),
                (string)($fila['desde_activo_texto'] ?? '-'),
                (string)($fila['fecha_retiro_texto'] ?? '-'),
                (string)($fila['observacion_retiro'] ?? '-'),
                !empty($fila['encuentro']) ? 'Si' : 'No',
                !empty($fila['bautismo']) ? 'Si' : 'No',
                !empty($fila['cap_destino']) ? 'Si' : 'No',
                (string)($fila['nivel_cap_texto'] ?? '-'),
            ];
        }

        $slug = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $nombreCelula);
        $this->exportCsv(
            'reporte_celula_' . $slug . '_' . date('Ymd_His'),
            [
                'Integrante',
                'Estado',
                'Tiempo activo en celula',
                'Desde',
                'Fecha retiro',
                'Observacion retiro',
                'Fue a encuentro',
                'Bautizado',
                'En Cap. Destino',
                'Nivel Cap. Destino',
            ],
            $rows,
            false
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function construirDatosReporteIndividual(int $idCelula): ?array {
        $celula = $this->celulaModel->getWithMembers($idCelula);
        if (!$celula) {
            return null;
        }

        $miembros = is_array($celula['miembros'] ?? null) ? $celula['miembros'] : [];
        $idsPersona = [];
        foreach ($miembros as $miembro) {
            $idPersona = (int)($miembro['Id_Persona'] ?? 0);
            if ($idPersona > 0) {
                $idsPersona[] = $idPersona;
            }
        }

        $hechos = [];
        if ($idsPersona !== []) {
            try {
                $hechos = (new ProgresoFormacionFlujo())->hechosPorPersonas($idsPersona);
            } catch (Throwable $e) {
                $hechos = [];
            }
        }

        $estado = strtolower(trim((string)($celula['Estado_Celula'] ?? 'Activa')));
        $estaCerrada = ($estado === 'cerrada');
        $fechaAperturaRaw = substr(trim((string)($celula['Fecha_Apertura'] ?? '')), 0, 10);
        $fechaCierreRaw = substr(trim((string)($celula['Fecha_Cierre'] ?? '')), 0, 10);
        $fechaAperturaTexto = $this->formatearFechaReporte($fechaAperturaRaw);
        $fechaCierreTexto = $estaCerrada ? $this->formatearFechaReporte($fechaCierreRaw) : '';
        $tiempoAbiertaTexto = $estaCerrada
            ? ''
            : $this->calcularTiempoAbiertaTexto($fechaAperturaRaw);
        $fechaFinActividad = ($estaCerrada && $fechaCierreRaw !== '' && $fechaCierreRaw !== '0000-00-00')
            ? $fechaCierreRaw
            : date('Y-m-d');

        $primerasAsistencias = $this->mapaPrimeraAsistenciaEnCelula($idsPersona, $idCelula);

        $integrantes = [];
        $conEncuentro = 0;
        $conBautismo = 0;
        $conCap = 0;
        $capN1 = 0;
        $capN2 = 0;
        $capN3 = 0;

        foreach ($miembros as $miembro) {
            $idPersona = (int)($miembro['Id_Persona'] ?? 0);
            $hecho = is_array($hechos[$idPersona] ?? null) ? $hechos[$idPersona] : [];
            $nivel = $this->resolverNivelCapDestinoDesdeHecho($hecho);
            $encuentro = !empty($hecho['encuentro']);
            $bautismo = !empty($hecho['bautismo']);
            $capDestino = !empty($hecho['cap_destino']) || $nivel > 0;

            if ($encuentro) {
                $conEncuentro++;
            }
            if ($bautismo) {
                $conBautismo++;
            }
            if ($capDestino) {
                $conCap++;
            }
            if ($nivel === 1) {
                $capN1++;
            } elseif ($nivel === 2) {
                $capN2++;
            } elseif ($nivel === 3) {
                $capN3++;
            }

            $nombre = trim(((string)($miembro['Nombre'] ?? '')) . ' ' . ((string)($miembro['Apellido'] ?? '')));
            $documento = trim((string)($miembro['Numero_Documento'] ?? ''));
            $telefono = trim((string)($miembro['Telefono'] ?? ''));

            $desdeActivoRaw = (string)($primerasAsistencias[$idPersona] ?? '');
            if ($desdeActivoRaw === '') {
                $desdeActivoRaw = substr(trim((string)($miembro['Fecha_Registro'] ?? '')), 0, 10);
            }
            $tiempoActivoTexto = $this->calcularTiempoEntreFechasTexto($desdeActivoRaw, $fechaFinActividad);

            $integrantes[] = [
                'id_persona' => $idPersona,
                'nombre' => $nombre !== '' ? $nombre : 'Sin nombre',
                'documento' => $documento !== '' ? $documento : 'N/A',
                'telefono' => $telefono !== '' ? $telefono : 'N/A',
                'estado_en_celula' => 'activo',
                'desde_activo' => $desdeActivoRaw,
                'desde_activo_texto' => $this->formatearFechaReporte($desdeActivoRaw),
                'tiempo_activo_texto' => $tiempoActivoTexto !== '' ? $tiempoActivoTexto : 'No disponible',
                'fecha_retiro' => '',
                'fecha_retiro_texto' => '—',
                'observacion_retiro' => '—',
                'encuentro' => $encuentro,
                'bautismo' => $bautismo,
                'cap_destino' => $capDestino,
                'nivel_cap' => $nivel,
                'nivel_cap_texto' => $this->textoNivelCapDestino($nivel, $capDestino),
            ];
        }

        $idsActivos = array_fill_keys($idsPersona, true);
        $idsSalida = [];
        $salidas = [];
        try {
            $salidas = $this->salidaCelulaModel->listarPorCelula($idCelula);
        } catch (Throwable $e) {
            $salidas = [];
        }
        foreach ($salidas as $salida) {
            $idSalidaPersona = (int)($salida['Id_Persona'] ?? 0);
            if ($idSalidaPersona > 0) {
                $idsSalida[] = $idSalidaPersona;
            }
        }
        $hechosSalida = [];
        if ($idsSalida !== []) {
            try {
                $hechosSalida = (new ProgresoFormacionFlujo())->hechosPorPersonas($idsSalida);
            } catch (Throwable $e) {
                $hechosSalida = [];
            }
        }
        $primerasAsistenciasSalida = $this->mapaPrimeraAsistenciaEnCelula($idsSalida, $idCelula);

        foreach ($salidas as $salida) {
            $idPersona = (int)($salida['Id_Persona'] ?? 0);
            if ($idPersona <= 0 || isset($idsActivos[$idPersona])) {
                // Si volvió a la célula, no duplicar como retiro vigente.
                continue;
            }

            $hecho = is_array($hechosSalida[$idPersona] ?? null) ? $hechosSalida[$idPersona] : [];
            $nivel = $this->resolverNivelCapDestinoDesdeHecho($hecho);
            $encuentro = !empty($hecho['encuentro']);
            $bautismo = !empty($hecho['bautismo']);
            $capDestino = !empty($hecho['cap_destino']) || $nivel > 0;

            $nombre = trim(((string)($salida['Nombre'] ?? '')) . ' ' . ((string)($salida['Apellido'] ?? '')));
            $fechaRetiroRaw = substr(trim((string)($salida['Fecha_Retiro'] ?? '')), 0, 10);
            $desdeActivoRaw = (string)($primerasAsistenciasSalida[$idPersona] ?? '');
            if ($desdeActivoRaw === '') {
                $desdeActivoRaw = substr(trim((string)($salida['Fecha_Registro'] ?? '')), 0, 10);
            }
            $finTiempo = ($fechaRetiroRaw !== '' && $fechaRetiroRaw !== '0000-00-00')
                ? $fechaRetiroRaw
                : $fechaFinActividad;
            $tiempoActivoTexto = $this->calcularTiempoEntreFechasTexto($desdeActivoRaw, $finTiempo);

            $integrantes[] = [
                'id_persona' => $idPersona,
                'nombre' => $nombre !== '' ? $nombre : 'Sin nombre',
                'documento' => 'N/A',
                'telefono' => 'N/A',
                'estado_en_celula' => 'retirado',
                'desde_activo' => $desdeActivoRaw,
                'desde_activo_texto' => $this->formatearFechaReporte($desdeActivoRaw),
                'tiempo_activo_texto' => $tiempoActivoTexto !== '' ? $tiempoActivoTexto : 'No disponible',
                'fecha_retiro' => $fechaRetiroRaw,
                'fecha_retiro_texto' => $this->formatearFechaReporte($fechaRetiroRaw),
                'observacion_retiro' => PersonaSalidaCelula::armarObservacionVisible($salida),
                'encuentro' => $encuentro,
                'bautismo' => $bautismo,
                'cap_destino' => $capDestino,
                'nivel_cap' => $nivel,
                'nivel_cap_texto' => $this->textoNivelCapDestino($nivel, $capDestino),
            ];
        }

        [$semanaInicio, $semanaFin] = $this->rangoSemanaActualLunesDomingo();
        $totalGanadosSemana = $this->contarGanadosCelulaEnRango($idCelula, $semanaInicio, $semanaFin);
        $totalActivos = 0;
        $totalRetirados = 0;
        foreach ($integrantes as $filaInt) {
            if (($filaInt['estado_en_celula'] ?? '') === 'retirado') {
                $totalRetirados++;
            } else {
                $totalActivos++;
            }
        }

        return [
            'celula' => $celula,
            'integrantes' => $integrantes,
            'semana' => [
                'inicio' => $semanaInicio,
                'fin' => $semanaFin,
                'rango_texto' => $this->formatearFechaReporte($semanaInicio) . ' – ' . $this->formatearFechaReporte($semanaFin),
                'total_ganados' => $totalGanadosSemana,
            ],
            'resumen' => [
                'fecha_apertura_texto' => $fechaAperturaTexto,
                'fecha_cierre_texto' => $fechaCierreTexto !== '' ? $fechaCierreTexto : 'No registrada',
                'tiempo_abierta_texto' => $tiempoAbiertaTexto !== '' ? $tiempoAbiertaTexto : 'No disponible',
                'esta_cerrada' => $estaCerrada,
                'total_integrantes' => $totalActivos,
                'total_retirados' => $totalRetirados,
                'con_encuentro' => $conEncuentro,
                'con_bautismo' => $conBautismo,
                'con_cap_destino' => $conCap,
                'cap_n1' => $capN1,
                'cap_n2' => $capN2,
                'cap_n3' => $capN3,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $hecho
     */
    private function resolverNivelCapDestinoDesdeHecho(array $hecho): int {
        if (!empty($hecho['cap_n3'])) {
            return 3;
        }
        if (!empty($hecho['cap_n2'])) {
            return 2;
        }
        if (!empty($hecho['cap_n1'])) {
            return 1;
        }
        return 0;
    }

    private function textoNivelCapDestino(int $nivel, bool $enCap): string {
        if ($nivel === 1) {
            return 'Nivel 1';
        }
        if ($nivel === 2) {
            return 'Nivel 2';
        }
        if ($nivel === 3) {
            return 'Nivel 3';
        }
        if ($enCap) {
            return 'En Cap. Destino (sin nivel)';
        }
        return '—';
    }

    private function formatearFechaReporte(string $fechaYmd): string {
        $fechaYmd = substr(trim($fechaYmd), 0, 10);
        if ($fechaYmd === '' || $fechaYmd === '0000-00-00') {
            return 'No especificada';
        }
        $ts = strtotime($fechaYmd);
        return $ts ? date('d/m/Y', $ts) : $fechaYmd;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function rangoSemanaActualLunesDomingo(?string $fechaReferencia = null): array {
        $timestamp = strtotime((string)($fechaReferencia ?: date('Y-m-d')));
        if ($timestamp === false) {
            $timestamp = time();
        }
        $diaSemana = (int)date('N', $timestamp); // 1 lunes … 7 domingo
        $inicio = strtotime('-' . ($diaSemana - 1) . ' days', $timestamp);
        $fin = strtotime('+6 days', $inicio);
        return [date('Y-m-d', $inicio), date('Y-m-d', $fin)];
    }

    private function calcularTiempoAbiertaTexto(string $fechaAperturaYmd): string {
        return $this->calcularTiempoEntreFechasTexto($fechaAperturaYmd, date('Y-m-d'));
    }

    private function calcularTiempoEntreFechasTexto(string $fechaInicioYmd, string $fechaFinYmd): string {
        $fechaInicioYmd = substr(trim($fechaInicioYmd), 0, 10);
        $fechaFinYmd = substr(trim($fechaFinYmd), 0, 10);
        if ($fechaInicioYmd === '' || $fechaInicioYmd === '0000-00-00') {
            return '';
        }
        if ($fechaFinYmd === '' || $fechaFinYmd === '0000-00-00') {
            $fechaFinYmd = date('Y-m-d');
        }
        try {
            $inicio = new DateTimeImmutable($fechaInicioYmd);
            $fin = new DateTimeImmutable($fechaFinYmd);
        } catch (Throwable $e) {
            return '';
        }
        if ($inicio > $fin) {
            return '0 días';
        }

        $diff = $inicio->diff($fin);
        $partes = [];
        if ((int)$diff->y > 0) {
            $partes[] = (int)$diff->y . ((int)$diff->y === 1 ? ' año' : ' años');
        }
        if ((int)$diff->m > 0) {
            $partes[] = (int)$diff->m . ((int)$diff->m === 1 ? ' mes' : ' meses');
        }
        if ((int)$diff->d > 0 || $partes === []) {
            $partes[] = (int)$diff->d . ((int)$diff->d === 1 ? ' día' : ' días');
        }
        return implode(', ', $partes);
    }

    /**
     * Primera fecha de asistencia reportada en la célula por persona.
     *
     * @param array<int, int> $idsPersona
     * @return array<int, string> Id_Persona => Y-m-d
     */
    private function mapaPrimeraAsistenciaEnCelula(array $idsPersona, int $idCelula): array {
        $idsPersona = array_values(array_unique(array_filter(array_map('intval', $idsPersona))));
        if ($idsPersona === [] || $idCelula <= 0) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsPersona), '?'));
        $params = $idsPersona;
        $params[] = $idCelula;
        $sql = "SELECT a.Id_Persona, MIN(DATE(a.Fecha_Asistencia)) AS Primera_Fecha
                FROM asistencia_celula a
                WHERE a.Id_Persona IN ({$placeholders})
                  AND a.Id_Celula = ?
                  AND a.Fecha_Asistencia IS NOT NULL
                  AND DATE(a.Fecha_Asistencia) > '0000-00-00'
                GROUP BY a.Id_Persona";

        $mapa = [];
        try {
            $filas = $this->asistenciaModel->query($sql, $params);
        } catch (Throwable $e) {
            return [];
        }
        foreach ((array)$filas as $fila) {
            $id = (int)($fila['Id_Persona'] ?? 0);
            $fecha = substr(trim((string)($fila['Primera_Fecha'] ?? '')), 0, 10);
            if ($id > 0 && $fecha !== '' && $fecha !== '0000-00-00') {
                $mapa[$id] = $fecha;
            }
        }
        return $mapa;
    }

    private function contarGanadosCelulaEnRango(int $idCelula, string $desde, string $hasta): int {
        $filtroNuevas = $this->personaModel->tieneColumna('Es_Antiguo')
            ? ' AND COALESCE(p.Es_Antiguo, 1) = 0'
            : '';
        $sql = "SELECT COUNT(*) AS total
                FROM persona p
                WHERE p.Id_Celula = ?
                  AND DATE(p.Fecha_Registro) BETWEEN ? AND ?
                  AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  {$filtroNuevas}";
        $filas = $this->personaModel->query($sql, [$idCelula, $desde, $hasta]);
        return (int)($filas[0]['total'] ?? 0);
    }

    public function eliminar() {
        // Verificar permiso de eliminar
        if (!AuthController::puede('celulas:eliminar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            if (!$this->puedeAccederCelula($id)) {
                header('Location: ' . public_app_url('auth/acceso-denegado'));
                exit;
            }
            $this->personaModel->limpiarMiembrosPorCelula($id);
            $this->celulaModel->delete($id);
        }
        
            $this->redirect('reportes&tipo=celulas');
    }

    /**
     * Asegurar tabla para tracking de vistas de materiales.
     */
    private function asegurarTablaVistasMateriales() {
        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS material_celula_vista (
                    Id_Vista INT AUTO_INCREMENT PRIMARY KEY,
                    Archivo VARCHAR(255) NOT NULL,
                    Id_Persona INT NOT NULL,
                    Total_Vistas INT NOT NULL DEFAULT 1,
                    Fecha_Primera_Vista DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    Fecha_Ultima_Vista DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_archivo_persona (Archivo, Id_Persona),
                    KEY idx_archivo (Archivo),
                    KEY idx_persona (Id_Persona)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);
    }

    /**
     * Obtener mapa de vistas únicas por archivo.
     */
    private function obtenerConteoVistasMateriales() {
        $this->asegurarTablaVistasMateriales();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return [];
        }

        $rows = $pdo->query("SELECT Archivo, COUNT(DISTINCT Id_Persona) AS Personas_Vieron FROM material_celula_vista GROUP BY Archivo")
                    ->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $archivo = (string)($row['Archivo'] ?? '');
            if ($archivo === '') {
                continue;
            }
            $map[$archivo] = (int)($row['Personas_Vieron'] ?? 0);
        }

        return $map;
    }

    /**
     * Registrar visualización de material por persona.
     */
    private function registrarVistaMaterial($archivo, $idPersona) {
        $archivo = basename((string)$archivo);
        $idPersona = (int)$idPersona;
        if ($archivo === '' || $idPersona <= 0) {
            return;
        }

        $this->asegurarTablaVistasMateriales();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return;
        }

        $sql = "INSERT INTO material_celula_vista (Archivo, Id_Persona, Total_Vistas)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE Total_Vistas = Total_Vistas + 1, Fecha_Ultima_Vista = NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$archivo, $idPersona]);
    }

    private function obtenerDirectorioMaterialesCelulas(): string {
        return ROOT . '/public/assets/celulas_materiales';
    }

    private function migrarMaterialesCelulasLegacy(): void {
        $directorioDestino = $this->obtenerDirectorioMaterialesCelulas();
        $directorioLegacy = ROOT . '/public/uploads/material_hub/celulas';

        if (!is_dir($directorioLegacy)) {
            return;
        }

        if (!is_dir($directorioDestino) && !@mkdir($directorioDestino, 0775, true) && !is_dir($directorioDestino)) {
            return;
        }

        $archivos = @scandir($directorioLegacy) ?: [];
        foreach ($archivos as $archivo) {
            if ($archivo === '.' || $archivo === '..') {
                continue;
            }

            if (strtolower((string)pathinfo($archivo, PATHINFO_EXTENSION)) !== 'pdf') {
                continue;
            }

            $origen = $directorioLegacy . '/' . $archivo;
            if (!is_file($origen)) {
                continue;
            }

            $destino = $directorioDestino . '/' . $archivo;
            if (!is_file($destino)) {
                @rename($origen, $destino);
                continue;
            }

            $base = (string)pathinfo($archivo, PATHINFO_FILENAME);
            $ext = (string)pathinfo($archivo, PATHINFO_EXTENSION);
            $i = 1;
            do {
                $destinoAlterno = $directorioDestino . '/' . $base . '_legacy_' . $i . '.' . $ext;
                $i++;
            } while (is_file($destinoAlterno));

            @rename($origen, $destinoAlterno);
        }
    }

    public function materiales() {
        if (!AuthController::puedeVerMaterialCelulas()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $directorioMateriales = $this->obtenerDirectorioMaterialesCelulas();
        if (!is_dir($directorioMateriales)) {
            @mkdir($directorioMateriales, 0775, true);
        }

        $this->migrarMaterialesCelulasLegacy();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $accion = trim((string)($_POST['accion'] ?? 'subir'));

                if ($accion === 'eliminar') {
                    if (!AuthController::puede('materiales_celulas:eliminar')) {
                        throw new Exception('No tienes permiso para eliminar material.');
                    }

                    $archivoEliminar = trim((string)($_POST['archivo'] ?? ''));
                    $archivoEliminar = basename($archivoEliminar);

                    if ($archivoEliminar === '' || strtolower(pathinfo($archivoEliminar, PATHINFO_EXTENSION)) !== 'pdf') {
                        throw new Exception('Archivo inválido para eliminar.');
                    }

                    $rutaEliminar = $directorioMateriales . '/' . $archivoEliminar;
                    if (!is_file($rutaEliminar)) {
                        throw new Exception('El archivo no existe o ya fue eliminado.');
                    }

                    if (!@unlink($rutaEliminar)) {
                        throw new Exception('No se pudo eliminar el archivo.');
                    }

                    $this->redirect('celulas/materiales&mensaje=' . urlencode('Material PDF eliminado correctamente') . '&tipo=success');
                    return;
                }

                if ($accion === 'editar') {
                    $puedeEditarMaterial = AuthController::esAdministrador()
                        || AuthController::puede('materiales_celulas:editar')
                        || AuthController::puede('materiales_celulas:crear');

                    if (!$puedeEditarMaterial) {
                        throw new Exception('No tienes permiso para editar material.');
                    }

                    $archivoActual = basename(trim((string)($_POST['archivo_actual'] ?? '')));
                    $archivoNuevo = basename(trim((string)($_POST['archivo_nuevo'] ?? '')));

                    if ($archivoActual === '' || strtolower(pathinfo($archivoActual, PATHINFO_EXTENSION)) !== 'pdf') {
                        throw new Exception('Archivo actual inválido.');
                    }

                    if ($archivoNuevo === '') {
                        throw new Exception('Debes indicar el nuevo nombre del archivo.');
                    }

                    if (strtolower(pathinfo($archivoNuevo, PATHINFO_EXTENSION)) !== 'pdf') {
                        $archivoNuevo .= '.pdf';
                    }

                    $archivoNuevo = preg_replace('/[\\\\\/:*?"<>|]/', '_', $archivoNuevo);
                    $archivoNuevo = trim((string)$archivoNuevo);
                    if ($archivoNuevo === '' || strtolower(pathinfo($archivoNuevo, PATHINFO_EXTENSION)) !== 'pdf') {
                        throw new Exception('Nombre de archivo inválido.');
                    }

                    if (strcasecmp($archivoActual, $archivoNuevo) === 0) {
                        throw new Exception('El nuevo nombre es igual al actual.');
                    }

                    $rutaActual = $directorioMateriales . '/' . $archivoActual;
                    if (!is_file($rutaActual)) {
                        throw new Exception('El archivo a editar no existe.');
                    }

                    $rutaNueva = $directorioMateriales . '/' . $archivoNuevo;
                    if (file_exists($rutaNueva)) {
                        throw new Exception('Ya existe un archivo con ese nombre.');
                    }

                    if (!@rename($rutaActual, $rutaNueva)) {
                        throw new Exception('No se pudo renombrar el archivo.');
                    }

                    try {
                        $this->asegurarTablaVistasMateriales();
                        require ROOT . '/conexion.php';
                        $stmtActualizarVistas = $pdo->prepare("UPDATE material_celula_vista SET Archivo = ? WHERE Archivo = ?");
                        $stmtActualizarVistas->execute([$archivoNuevo, $archivoActual]);
                    } catch (Throwable $e) {
                        // No revertir renombrado de archivo por un fallo de tracking.
                    }

                    $this->redirect('celulas/materiales&mensaje=' . urlencode('Material PDF renombrado correctamente') . '&tipo=success');
                    return;
                }

                if (!AuthController::puede('materiales_celulas:crear')) {
                    throw new Exception('No tienes permiso para subir material.');
                }

                if (!isset($_FILES['material_pdf'])) {
                    throw new Exception('No se recibió el archivo PDF.');
                }

                $archivo = $_FILES['material_pdf'];
                if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    throw new Exception('Error al subir archivo. Intente nuevamente.');
                }

                $nombreOriginal = trim((string)($archivo['name'] ?? 'material.pdf'));
                $nombreOriginal = basename($nombreOriginal);
                $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                if ($extension !== 'pdf') {
                    throw new Exception('Solo se permiten archivos PDF.');
                }

                $tamanio = (int)($archivo['size'] ?? 0);
                if ($tamanio <= 0) {
                    throw new Exception('El archivo está vacío.');
                }

                $maxBytes = 20 * 1024 * 1024;
                if ($tamanio > $maxBytes) {
                    throw new Exception('El PDF supera el tamaño máximo de 20MB.');
                }

                $tmp = (string)($archivo['tmp_name'] ?? '');
                $finfo = function_exists('finfo_open') ? @finfo_open(FILEINFO_MIME_TYPE) : false;
                $mime = $finfo ? (string)@finfo_file($finfo, $tmp) : '';
                if ($finfo) {
                    @finfo_close($finfo);
                }

                if ($mime !== '' && stripos($mime, 'pdf') === false) {
                    throw new Exception('El archivo no es un PDF válido.');
                }

                // Mantener nombre original del archivo (sin prefijos automáticos).
                $nombreFinal = preg_replace('/[\\\\\/:*?"<>|]/', '_', $nombreOriginal);
                $nombreFinal = trim((string)$nombreFinal);
                if ($nombreFinal === '' || strtolower(pathinfo($nombreFinal, PATHINFO_EXTENSION)) !== 'pdf') {
                    throw new Exception('Nombre de archivo inválido.');
                }

                $destino = $directorioMateriales . '/' . $nombreFinal;

                if (file_exists($destino)) {
                    throw new Exception('Ya existe un archivo con ese nombre. Renómbralo y vuelve a subirlo.');
                }

                if (!move_uploaded_file($tmp, $destino)) {
                    throw new Exception('No se pudo guardar el PDF en el servidor.');
                }

                $this->redirect('celulas/materiales&mensaje=' . urlencode('Material PDF subido correctamente') . '&tipo=success');
            } catch (Exception $e) {
                $this->redirect('celulas/materiales&mensaje=' . urlencode($e->getMessage()) . '&tipo=error');
            }
            return;
        }

        $materiales = [];
        $vistasPorArchivo = [];
        try {
            $vistasPorArchivo = $this->obtenerConteoVistasMateriales();
        } catch (Throwable $e) {
            $vistasPorArchivo = [];
        }

        if (is_dir($directorioMateriales)) {
            $archivos = @scandir($directorioMateriales) ?: [];
            foreach ($archivos as $archivo) {
                if ($archivo === '.' || $archivo === '..') {
                    continue;
                }

                $ruta = $directorioMateriales . '/' . $archivo;
                if (!is_file($ruta)) {
                    continue;
                }

                if (strtolower((string)pathinfo($archivo, PATHINFO_EXTENSION)) !== 'pdf') {
                    continue;
                }

                $nombre = basename((string)$ruta);
                $fechaCreacion = @filectime($ruta) ?: (@filemtime($ruta) ?: 0);
                $materiales[] = [
                    'nombre_archivo' => $nombre,
                    'url' => PUBLIC_URL . '?url=celulas/materiales/ver&archivo=' . rawurlencode($nombre),
                    'peso_kb' => round(((int)@filesize($ruta)) / 1024, 2),
                    'fecha_creacion' => $fechaCreacion,
                    'fecha_modificacion' => @filemtime($ruta) ?: 0,
                    'personas_vieron' => (int)($vistasPorArchivo[$nombre] ?? 0)
                ];
            }
        }

        usort($materiales, static function ($a, $b) {
            return ($b['fecha_creacion'] ?? 0) <=> ($a['fecha_creacion'] ?? 0);
        });

        $this->view('celulas/materiales', [
            'materiales' => $materiales,
            'mensaje' => $_GET['mensaje'] ?? '',
            'tipo' => $_GET['tipo'] ?? ''
        ]);
    }

    /**
     * Abrir material PDF y registrar visualización.
     */
    public function verMaterial() {
        if (!AuthController::puedeVerMaterialCelulas()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $archivo = basename((string)($_GET['archivo'] ?? ''));
        if ($archivo === '' || strtolower(pathinfo($archivo, PATHINFO_EXTENSION)) !== 'pdf') {
            $this->redirect('celulas/materiales&mensaje=' . urlencode('Archivo inválido') . '&tipo=error');
            return;
        }

        $directorioMateriales = $this->obtenerDirectorioMaterialesCelulas();
        $this->migrarMaterialesCelulasLegacy();
        $ruta = $directorioMateriales . '/' . $archivo;
        if (!is_file($ruta)) {
            $this->redirect('celulas/materiales&mensaje=' . urlencode('El archivo no existe') . '&tipo=error');
            return;
        }

        $idPersona = (int)($_SESSION['usuario_id'] ?? 0);
        try {
            $this->registrarVistaMaterial($archivo, $idPersona);
        } catch (Throwable $e) {
            // No bloquear la apertura del PDF por fallo de tracking.
        }

        header('Location: ' . ASSETS_URL . '/celulas_materiales/' . rawurlencode($archivo));
        exit;
    }

    /**
     * Buscar líderes para autocompletar (AJAX)
     */
    public function buscarLideres() {
        header('Content-Type: application/json');
        
        $term = $_GET['term'] ?? '';
        
        if (strlen($term) < 2) {
            echo json_encode(['success' => false, 'message' => 'Mínimo 2 caracteres']);
            exit;
        }
        
        try {
            // Buscar líderes: Líder de célula (3), Pastores (6), Líder de 12 (8)
            // Sin filtro DataIsolation para permitir asignar cualquier líder del sistema.
            $sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Telefono, r.Nombre_Rol as Rol
                    FROM persona p
                    LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                    WHERE (
                        LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%pastor%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider de 12%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider 12%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider de celula%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider celula%'
                    )
                    AND (p.Nombre LIKE ? OR p.Apellido LIKE ?)
                    AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                    ORDER BY p.Nombre, p.Apellido
                    LIMIT 30";
            
            $searchTerm = "%$term%";
            $lideres = $this->personaModel->query($sql, [$searchTerm, $searchTerm]);
            
            echo json_encode([
                'success' => true,
                'data' => $lideres
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar líderes'
            ]);
        }
        exit;
    }

    /**
     * Buscar líderes de 12 para autocompletar (AJAX)
     */
    public function buscarLideres12() {
        header('Content-Type: application/json');
        
        $term = $_GET['term'] ?? '';
        
        if (strlen($term) < 2) {
            echo json_encode(['success' => false, 'message' => 'Mínimo 2 caracteres']);
            exit;
        }
        
        try {
            // Sin filtro DataIsolation para permitir asignar cualquier líder de 12 del sistema.
            $sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Telefono
                    FROM persona p
                    LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                    WHERE (
                        LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider de 12%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider 12%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lideres de 12%'
                    )
                    AND (p.Nombre LIKE ? OR p.Apellido LIKE ?)
                    AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                    ORDER BY p.Nombre, p.Apellido
                    LIMIT 30";
            
            $searchTerm = "%$term%";
            $lideres = $this->personaModel->query($sql, [$searchTerm, $searchTerm]);
            
            echo json_encode([
                'success' => true,
                'data' => $lideres
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar líderes de 12'
            ]);
        }
        exit;
    }

    /**
     * Buscar pastores para autocompletar (AJAX)
     */
    public function buscarPastores() {
        header('Content-Type: application/json');
        
        $term = $_GET['term'] ?? '';
        
        if (strlen($term) < 2) {
            echo json_encode(['success' => false, 'message' => 'Mínimo 2 caracteres']);
            exit;
        }
        
        try {
            // Sin filtro DataIsolation para permitir asignar cualquier pastor del sistema.
            $sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Telefono, r.Nombre_Rol as Rol
                    FROM persona p
                    LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                    WHERE (
                        p.Id_Rol = 6
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%pastor%'
                    )
                    AND (p.Nombre LIKE ? OR p.Apellido LIKE ?)
                    AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                    ORDER BY p.Nombre, p.Apellido
                    LIMIT 30";
            
            $searchTerm = "%$term%";
            $pastores = $this->personaModel->query($sql, [$searchTerm, $searchTerm]);
            
            echo json_encode([
                'success' => true,
                'data' => $pastores
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar pastores'
            ]);
        }
        exit;
    }

    /**
     * Buscar anfitriones (todas las personas) para autocompletar (AJAX)
     */
    public function buscarAnfitriones() {
        header('Content-Type: application/json');
        
        $term = $_GET['term'] ?? '';
        
        if (strlen($term) < 2) {
            echo json_encode(['success' => false, 'message' => 'Mínimo 2 caracteres']);
            exit;
        }
        
        try {
            $filtroPersonas = $this->getFiltroPersonasBusqueda();

            $sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Telefono
                    FROM persona p
                    WHERE (p.Nombre LIKE ? OR p.Apellido LIKE ?)
                    AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND ($filtroPersonas)
                    ORDER BY p.Nombre, p.Apellido
                    LIMIT 20";
            
            $searchTerm = "%$term%";
            $personas = $this->personaModel->query($sql, [$searchTerm, $searchTerm]);
            
            echo json_encode([
                'success' => true,
                'data' => $personas
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar anfitriones'
            ]);
        }
        exit;
    }

    /**
     * Obtener detalles de quiénes vieron un material específico (AJAX)
     */
    public function detalleVistasMaterial() {
        if (!AuthController::puedeVerMaterialCelulas()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            exit;
        }

        header('Content-Type: application/json');
        
        $archivo = basename((string)($_GET['archivo'] ?? ''));
        if ($archivo === '' || strtolower(pathinfo($archivo, PATHINFO_EXTENSION)) !== 'pdf') {
            echo json_encode(['success' => false, 'message' => 'Archivo inválido']);
            exit;
        }

        try {
            $this->asegurarTablaVistasMateriales();

            global $pdo;
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                echo json_encode(['success' => false, 'message' => 'Error de conexión']);
                exit;
            }

            $sql = "SELECT 
                        mv.Id_Persona,
                        p.Nombre,
                        p.Apellido,
                        p.Telefono,
                        m.Nombre_Ministerio,
                        mv.Total_Vistas,
                        mv.Fecha_Primera_Vista,
                        mv.Fecha_Ultima_Vista
                    FROM material_celula_vista mv
                    LEFT JOIN persona p ON mv.Id_Persona = p.Id_Persona
                    LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                    WHERE mv.Archivo = ?
                    ORDER BY mv.Fecha_Ultima_Vista DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$archivo]);
            $vistas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'archivo' => htmlspecialchars($archivo),
                'total_personas' => count($vistas),
                'vistas' => $vistas
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener datos']);
        }
        exit;
    }
}
