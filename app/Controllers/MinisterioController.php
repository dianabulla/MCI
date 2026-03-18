<?php
/**
 * Controlador Ministerio
 */

require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Celula.php';
require_once APP . '/Helpers/DataIsolation.php';

class MinisterioController extends BaseController {
    private $ministerioModel;
    private $personaModel;
    private $celulaModel;

    public function __construct() {
        $this->ministerioModel = new Ministerio();
        $this->personaModel = new Persona();
        $this->celulaModel = new Celula();
    }

    private function calcularRangoSemanaDomingoADomingo($fechaReferencia) {
        $timestamp = strtotime((string)$fechaReferencia);
        if ($timestamp === false) {
            $timestamp = time();
        }

        $diaSemana = (int)date('w', $timestamp);
        $inicio = strtotime('-' . $diaSemana . ' days', $timestamp);
        $fin = strtotime('+6 days', $inicio);

        return [date('Y-m-d', $inicio), date('Y-m-d', $fin)];
    }

    private function normalizarTipoReunion($tipoReunion) {
        $valor = strtolower(trim((string)$tipoReunion));
        return strtr($valor, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n'
        ]);
    }

    private function normalizarConvencion($convencion) {
        $valor = strtolower(trim((string)$convencion));
        $valor = strtr($valor, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n'
        ]);

        if ($valor === 'convencion enero') {
            return 'enero';
        }

        if ($valor === 'convencion mujeres') {
            return 'mujeres';
        }

        if ($valor === 'convencion jovenes') {
            return 'jovenes';
        }

        if ($valor === 'convencion hombres' || $valor === 'convencion hombre') {
            return 'hombres';
        }

        return '';
    }

    private function construirChecklistEfectivo(array $persona) {
        $ordenEtapas = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
        $indiceEtapa = array_flip($ordenEtapas);
        $checklist = [];

        $raw = (string)($persona['Escalera_Checklist'] ?? '');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $checklist = $decoded;
            }
        }

        $etapaActual = trim((string)($persona['Proceso'] ?? ''));
        $indiceActual = $indiceEtapa[$etapaActual] ?? -1;

        $resultado = [];
        foreach ($ordenEtapas as $etapaNombre) {
            $resultado[$etapaNombre] = [false, false, false];
            $indiceBloque = $indiceEtapa[$etapaNombre];
            $bloqueCompletado = $indiceActual > $indiceBloque;
            $bloqueActivo = $indiceActual === $indiceBloque;

            for ($i = 0; $i < 3; $i++) {
                $persistido = null;
                if (isset($checklist[$etapaNombre]) && is_array($checklist[$etapaNombre]) && array_key_exists($i, $checklist[$etapaNombre])) {
                    $persistido = !empty($checklist[$etapaNombre][$i]);
                }

                $resultado[$etapaNombre][$i] = $persistido !== null
                    ? $persistido
                    : ($bloqueCompletado || ($bloqueActivo && $i === 0));
            }
        }

        return $resultado;
    }

    private function calcularAvanceSemestralPorMinisterio(array $ministerioIds, array $personas, $fechaReferencia) {
        $timestamp = strtotime((string)$fechaReferencia);
        if ($timestamp === false) {
            $timestamp = time();
        }

        $anio = (int)date('Y', $timestamp);
        $mes = (int)date('n', $timestamp);
        $esPrimerSemestre = $mes <= 6;

        $fechaInicio = $esPrimerSemestre
            ? sprintf('%04d-01-01', $anio)
            : sprintf('%04d-07-01', $anio);
        $fechaFin = $esPrimerSemestre
            ? sprintf('%04d-06-30', $anio)
            : sprintf('%04d-12-31', $anio);

        $avance = [];
        foreach ($ministerioIds as $idMinisterio) {
            $avance[$idMinisterio] = [
                'celula' => 0,
                'iglesia' => 0,
                'total' => 0
            ];
        }

        foreach ($personas as $persona) {
            $idMinisterio = (int)($persona['Id_Ministerio'] ?? 0);
            if (!isset($avance[$idMinisterio])) {
                continue;
            }

            $fechaRegistro = substr((string)($persona['Fecha_Registro'] ?? ''), 0, 10);
            if ($fechaRegistro === '' || $fechaRegistro < $fechaInicio || $fechaRegistro > $fechaFin) {
                continue;
            }

            $avance[$idMinisterio]['total']++;

            $tipoReunion = $this->normalizarTipoReunion($persona['Tipo_Reunion'] ?? '');
            if (strpos($tipoReunion, 'celula') !== false) {
                $avance[$idMinisterio]['celula']++;
            }

            if (strpos($tipoReunion, 'domingo') !== false || strpos($tipoReunion, 'iglesia') !== false) {
                $avance[$idMinisterio]['iglesia']++;
            }
        }

        return [
            'inicio' => $fechaInicio,
            'fin' => $fechaFin,
            'titulo' => $esPrimerSemestre ? ('1er semestre ' . $anio) : ('2do semestre ' . $anio),
            'avance' => $avance
        ];
    }

    private function calcularMetricasMinisterio(array $ministerioIds, array $personas, $fechaInicio, $fechaFin) {
        $metricas = [];

        foreach ($ministerioIds as $idMinisterio) {
            $metricas[$idMinisterio] = [
                'celulas' => 0,
                'lideres_celula' => 0,
                'asistentes_celula' => 0,
                'ganados_semana_total' => 0,
                'ganados_semana_celula' => 0,
                'ganados_semana_domingo' => 0,
                'convenciones' => [
                    'enero' => 0,
                    'mujeres' => 0,
                    'jovenes' => 0,
                    'hombres' => 0
                ],
                'escalera' => [
                    'Ganar' => ['Primer contacto' => 0, 'Ubicado en celula' => 0, 'No se dispone' => 0],
                    'Consolidar' => ['Universidad de la vida' => 0, 'Encuentro' => 0, 'Bautismo' => 0],
                    'Discipular' => ['Proyeccion' => 0, 'Equipo G12' => 0, 'Capacitacion destino nivel 1' => 0],
                    'Enviar' => ['Capacitacion destino nivel 2' => 0, 'Capacitacion destino nivel 3' => 0, 'Celula' => 0]
                ]
            ];
        }

        foreach ($personas as $persona) {
            $idMinisterio = (int)($persona['Id_Ministerio'] ?? 0);
            if (!isset($metricas[$idMinisterio])) {
                continue;
            }

            if ((int)($persona['Id_Rol'] ?? 0) === 3) {
                $metricas[$idMinisterio]['lideres_celula']++;
            }

            $rolNombre = $this->normalizarTipoReunion($persona['Nombre_Rol'] ?? '');
            if ($rolNombre !== '' && strpos($rolNombre, 'asistente') !== false) {
                $metricas[$idMinisterio]['asistentes_celula']++;
            }

            $convencion = $this->normalizarConvencion($persona['Convencion'] ?? '');
            if ($convencion !== '') {
                $metricas[$idMinisterio]['convenciones'][$convencion]++;
            }

            $fechaRegistro = substr((string)($persona['Fecha_Registro'] ?? ''), 0, 10);
            if ($fechaRegistro !== '' && $fechaRegistro >= $fechaInicio && $fechaRegistro <= $fechaFin) {
                $metricas[$idMinisterio]['ganados_semana_total']++;
                $tipoReunion = $this->normalizarTipoReunion($persona['Tipo_Reunion'] ?? '');
                if (strpos($tipoReunion, 'celula') !== false) {
                    $metricas[$idMinisterio]['ganados_semana_celula']++;
                }
                if (strpos($tipoReunion, 'domingo') !== false) {
                    $metricas[$idMinisterio]['ganados_semana_domingo']++;
                }
            }

            $checklist = $this->construirChecklistEfectivo($persona);
            $mapa = [
                'Ganar' => ['Primer contacto', 'Ubicado en celula', 'No se dispone'],
                'Consolidar' => ['Universidad de la vida', 'Encuentro', 'Bautismo'],
                'Discipular' => ['Proyeccion', 'Equipo G12', 'Capacitacion destino nivel 1'],
                'Enviar' => ['Capacitacion destino nivel 2', 'Capacitacion destino nivel 3', 'Celula']
            ];

            foreach ($mapa as $etapa => $subprocesos) {
                foreach ($subprocesos as $indice => $nombre) {
                    if (!empty($checklist[$etapa][$indice])) {
                        $metricas[$idMinisterio]['escalera'][$etapa][$nombre]++;
                    }
                }
            }
        }

        return $metricas;
    }

    public function index() {
        $fechaReferencia = $_GET['fecha_referencia'] ?? date('Y-m-d');
        [$fechaInicio, $fechaFin] = $this->calcularRangoSemanaDomingoADomingo($fechaReferencia);

        // Generar filtro según el rol del usuario
        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        
        // Obtener ministerios con aislamiento de rol
        $ministerios = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);

        $ministerioIds = array_map(static function ($ministerio) {
            return (int)($ministerio['Id_Ministerio'] ?? 0);
        }, $ministerios);

        $miembros = $this->personaModel->getActivosByMinisterioIds($ministerioIds);
        $personasVisibles = $this->personaModel->getAllWithRole($filtroPersonas, null, 'Activo');
        $metricasMinisterio = $this->calcularMetricasMinisterio($ministerioIds, $personasVisibles, $fechaInicio, $fechaFin);

        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $celulasVisibles = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);
        foreach ($celulasVisibles as $celula) {
            $idMinisterioLider = (int)($celula['Id_Ministerio_Lider'] ?? 0);
            if ($idMinisterioLider > 0 && isset($metricasMinisterio[$idMinisterioLider])) {
                $metricasMinisterio[$idMinisterioLider]['celulas']++;
            }
        }

        $miembrosPorMinisterio = [];
        foreach ($miembros as $miembro) {
            $idMinisterio = (int)($miembro['Id_Ministerio'] ?? 0);
            if ($idMinisterio <= 0) {
                continue;
            }

            if (!isset($miembrosPorMinisterio[$idMinisterio])) {
                $miembrosPorMinisterio[$idMinisterio] = [];
            }
            $miembrosPorMinisterio[$idMinisterio][] = $miembro;
        }

        $sections = [];
        foreach ($ministerios as $ministerio) {
            $idMinisterio = (int)($ministerio['Id_Ministerio'] ?? 0);
            $miembrosMinisterio = $miembrosPorMinisterio[$idMinisterio] ?? [];

            $rows = [];
            $nro = 1;
            foreach ($miembrosMinisterio as $miembro) {
                $nombreCompleto = trim(((string)($miembro['Nombre'] ?? '')) . ' ' . ((string)($miembro['Apellido'] ?? '')));
                $fechaRegistro = substr((string)($miembro['Fecha_Registro'] ?? ''), 0, 10);
                $esGanadoSemanaTotal = $fechaRegistro !== '' && $fechaRegistro >= $fechaInicio && $fechaRegistro <= $fechaFin;

                $tipoReunionNorm = $this->normalizarTipoReunion($miembro['Tipo_Reunion'] ?? '');
                $rolNombreNorm = $this->normalizarTipoReunion($miembro['Nombre_Rol'] ?? '');
                $convencionNorm = $this->normalizarConvencion($miembro['Convencion'] ?? '');
                $checklist = $this->construirChecklistEfectivo($miembro);

                $esLiderCelula = ((int)($miembro['Id_Rol'] ?? 0) === 3) || (strpos($rolNombreNorm, 'lider de celula') !== false);
                $esAsistenteCelula = strpos($rolNombreNorm, 'asistente') !== false;
                $tieneCelula = trim((string)($miembro['Nombre_Celula'] ?? '')) !== '';

                $rows[] = [
                    'nro' => $nro++,
                    'id_persona' => (int)$miembro['Id_Persona'],
                    'nombre' => $nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre',
                    'rol' => (string)($miembro['Nombre_Rol'] ?? 'Sin rol'),
                    'telefono' => (string)($miembro['Telefono'] ?? ''),
                    'documento' => (string)($miembro['Numero_Documento'] ?? ''),
                    'celula' => (string)($miembro['Nombre_Celula'] ?? ''),
                    'tipo_reunion' => (string)($miembro['Tipo_Reunion'] ?? ''),
                    'fecha_registro' => (string)($miembro['Fecha_Registro'] ?? ''),
                    'match_total_personas' => true,
                    'match_celulas' => $tieneCelula,
                    'match_lideres_celula' => $esLiderCelula,
                    'match_asistentes_celula' => $esAsistenteCelula,
                    'match_ganados_semana_total' => $esGanadoSemanaTotal,
                    'match_ganados_semana_celula' => $esGanadoSemanaTotal && strpos($tipoReunionNorm, 'celula') !== false,
                    'match_ganados_semana_domingo' => $esGanadoSemanaTotal && strpos($tipoReunionNorm, 'domingo') !== false,
                    'match_escalera_uv' => !empty($checklist['Consolidar'][0]),
                    'match_escalera_encuentro' => !empty($checklist['Consolidar'][1]),
                    'match_escalera_destino_n1' => !empty($checklist['Discipular'][2]),
                    'match_escalera_destino_n2' => !empty($checklist['Enviar'][0]),
                    'match_escalera_destino_n3' => !empty($checklist['Enviar'][1]),
                    'match_convencion_enero' => $convencionNorm === 'enero',
                    'match_convencion_mujeres' => $convencionNorm === 'mujeres',
                    'match_convencion_jovenes' => $convencionNorm === 'jovenes',
                    'match_convencion_hombres' => $convencionNorm === 'hombres',
                    'match_convencion_total' => $convencionNorm !== ''
                ];
            }

            $sections[] = [
                'id_ministerio' => $idMinisterio,
                'label' => (string)($ministerio['Nombre_Ministerio'] ?? 'Ministerio sin nombre'),
                'descripcion' => (string)($ministerio['Descripcion'] ?? ''),
                'rows' => $rows,
                'total_personas' => count($rows),
                'metricas' => $metricasMinisterio[$idMinisterio] ?? null
            ];
        }

        $this->view('ministerios/lista', [
            'ministerios' => $ministerios,
            'sections' => $sections,
            'fecha_referencia' => $fechaReferencia,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'meta_guardada' => ($_GET['meta_guardada'] ?? '') === '1'
        ]);
    }

    private function usuarioPuedeEditarMinisterio($idMinisterio) {
        $idMinisterio = (int)$idMinisterio;
        if ($idMinisterio <= 0) {
            return false;
        }

        if (AuthController::esAdministrador()) {
            return true;
        }

        if (!AuthController::tienePermiso('ministerios', 'editar')) {
            return false;
        }

        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        $ministeriosVisibles = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);
        $idsPermitidos = array_map(static function($row) {
            return (int)($row['Id_Ministerio'] ?? 0);
        }, $ministeriosVisibles);

        if (!in_array($idMinisterio, $idsPermitidos, true)) {
            return false;
        }

        // No admin: solo su propio ministerio.
        $idMinisterioUsuario = (int)(DataIsolation::getUsuarioMinisterioId() ?? 0);
        return $idMinisterioUsuario > 0 && $idMinisterioUsuario === $idMinisterio;
    }

    public function actualizarMeta() {
        $esAdmin = AuthController::esAdministrador();
        $puedeEditar = AuthController::tienePermiso('ministerios', 'editar');
        if (!$esAdmin && !$puedeEditar) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('ministerios');
            return;
        }

        $idMinisterio = (int)($_POST['id_ministerio'] ?? 0);
        $metaGanados = max(0, (int)($_POST['meta_ganados'] ?? 0));

        if ($idMinisterio <= 0) {
            $this->redirect('ministerios');
            return;
        }

        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        $ministeriosVisibles = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);
        $idsPermitidos = array_map(static function($row) {
            return (int)($row['Id_Ministerio'] ?? 0);
        }, $ministeriosVisibles);

        if (!in_array($idMinisterio, $idsPermitidos, true)) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        // No administradores: solo pueden configurar la meta de su propio ministerio.
        $idMinisterioUsuario = (int)(DataIsolation::getUsuarioMinisterioId() ?? 0);
        if (!$esAdmin && ($idMinisterioUsuario <= 0 || $idMinisterio !== $idMinisterioUsuario)) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $this->ministerioModel->setMetaGanados($idMinisterio, $metaGanados);
        $this->redirect('ministerios&meta_guardada=1');
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('ministerios', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre_Ministerio' => $_POST['nombre_ministerio'],
                'Descripcion' => $_POST['descripcion']
            ];
            
            $this->ministerioModel->create($data);
            $this->redirect('ministerios');
        } else {
            $this->view('ministerios/formulario');
        }
    }

    public function exportarExcel() {
        if (!AuthController::tienePermiso('ministerios', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        $ministerios = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);

        $rows = [];
        foreach ($ministerios as $ministerio) {
            $rows[] = [
                (string)($ministerio['Nombre_Ministerio'] ?? ''),
                (string)($ministerio['Descripcion'] ?? ''),
                (string)($ministerio['Total_Miembros'] ?? 0)
            ];
        }

        $this->exportCsv(
            'ministerios_' . date('Ymd_His'),
            ['Ministerio', 'Descripcion', 'Total Miembros'],
            $rows
        );
    }

    public function editar() {
        // Verificar permiso de editar
        if (!AuthController::tienePermiso('ministerios', 'editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('ministerios');
        }

        if (!$this->usuarioPuedeEditarMinisterio($id)) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre_Ministerio' => $_POST['nombre_ministerio'],
                'Descripcion' => $_POST['descripcion']
            ];
            
            $this->ministerioModel->update($id, $data);
            $this->ministerioModel->setMetasDetalle($id, [
                'meta_ganados_s1' => $_POST['meta_ganados_s1'] ?? 0,
                'meta_ganados_s2' => $_POST['meta_ganados_s2'] ?? 0,
                'meta_uv_s1' => $_POST['meta_uv_s1'] ?? 0,
                'meta_uv_s2' => $_POST['meta_uv_s2'] ?? 0,
                'meta_encuentro_s1' => $_POST['meta_encuentro_s1'] ?? 0,
                'meta_encuentro_s2' => $_POST['meta_encuentro_s2'] ?? 0,
                'meta_n1_s1' => $_POST['meta_n1_s1'] ?? 0,
                'meta_n1_s2' => $_POST['meta_n1_s2'] ?? 0,
                'meta_n2_s1' => $_POST['meta_n2_s1'] ?? 0,
                'meta_n2_s2' => $_POST['meta_n2_s2'] ?? 0,
                'meta_n3_s1' => $_POST['meta_n3_s1'] ?? 0,
                'meta_n3_s2' => $_POST['meta_n3_s2'] ?? 0
            ]);
            $this->redirect('ministerios');
        } else {
            $data = [
                'ministerio' => $this->ministerioModel->getById($id),
                'metas' => $this->ministerioModel->getMetaDetalleByMinisterioId($id)
            ];
            $this->view('ministerios/formulario', $data);
        }
    }

    public function eliminar() {
        // Verificar permiso de eliminar
        if (!AuthController::tienePermiso('ministerios', 'eliminar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $this->ministerioModel->delete($id);
        }
        
        $this->redirect('ministerios');
    }
}
