<?php
/**
 * Controlador Celula
 */

require_once APP . '/Models/Celula.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/Asistencia.php';
require_once APP . '/Models/EscuelaFormacionInscripcion.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Helpers/DataIsolation.php';

class CelulaController extends BaseController {
    private $celulaModel;
    private $personaModel;
    private $ministerioModel;
    private $asistenciaModel;
    private $inscripcionFormacionModel;

    public function __construct() {
        $this->celulaModel = new Celula();
        $this->personaModel = new Persona();
        $this->ministerioModel = new Ministerio();
        $this->asistenciaModel = new Asistencia();
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
        if ($estado === 'Activa') {
            $fechaCierre = null;
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
            $miembrosCelula = $miembrosPorCelula[$idCelula] ?? [];

            $rows = [];
            $nro = 1;
            foreach ($miembrosCelula as $miembro) {
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
                'ministerio' => (string)($celula['Nombre_Ministerio_Lider'] ?? 'Sin ministerio'),
                'lider' => (string)($celula['Nombre_Lider'] ?? 'Sin líder'),
                'anfitrion' => (string)($celula['Nombre_Anfitrion'] ?? 'Sin anfitrión'),
                'direccion' => (string)($celula['Direccion_Celula'] ?? ''),
                'dia' => (string)($celula['Dia_Reunion'] ?? ''),
                'hora' => (string)($celula['Hora_Reunion'] ?? ''),
                'rows' => $rows,
                'total_personas' => count($rows),
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

        $this->view('celulas/lista', [
            'celulas' => $celulas,
            'sections' => $sections,
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
            $this->celulaModel->create($this->datosFichaDesdePost($_POST, null));
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

            $this->celulaModel->update($id, $this->datosFichaDesdePost($_POST, $celulaAntes));
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
                $miembro['Indicador_Asistencia'] = $total >= 3 ? 'Frecuente' : 'Regular';
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
            }
            unset($miembro);
        }

        $this->view('celulas/detalle', ['celula' => $celula]);
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
