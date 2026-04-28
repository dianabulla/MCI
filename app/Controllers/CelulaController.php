<?php
/**
 * Controlador Celula
 */

require_once APP . '/Models/Celula.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Helpers/DataIsolation.php';

class CelulaController extends BaseController {
    private $celulaModel;
    private $personaModel;
    private $ministerioModel;

    public function __construct() {
        $this->celulaModel = new Celula();
        $this->personaModel = new Persona();
        $this->ministerioModel = new Ministerio();
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
     * Filtro de personas visibles para búsquedas AJAX.
     */
    private function getFiltroPersonasBusqueda() {
        return DataIsolation::generarFiltroPersonas();
    }

    public function index() {
        if (!AuthController::tienePermiso('celulas', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        // Generar filtro segun el rol del usuario
        $filtroCelulas = DataIsolation::generarFiltroCelulas();

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
                'total_personas' => count($rows)
            ];
        }

        $this->view('celulas/lista', [
            'celulas' => $celulas,
            'sections' => $sections,
            'ministerios_disponibles' => array_values($ministeriosDisponibles),
            'lideres_disponibles' => array_values($lideresDisponibles),
            'filtro_ministerio_actual' => (string)$filtroMinisterio,
            'filtro_lider_actual' => (string)$filtroLider
        ]);
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('celulas', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idLider = $_POST['id_lider'] ?: null;

            // Para líder de célula, forzar que la célula quede anclada al usuario logueado
            if (DataIsolation::esLiderCelula()) {
                $idLider = $_SESSION['usuario_id'] ?? $idLider;
            }

            $data = [
                'Nombre_Celula' => $_POST['nombre_celula'],
                'Direccion_Celula' => $_POST['direccion_celula'],
                'Dia_Reunion' => $_POST['dia_reunion'],
                'Hora_Reunion' => $_POST['hora_reunion'],
                'Id_Lider' => $idLider,
                'Pastor_Principal' => $_POST['pastor_principal'] ?: null,
                'Id_Lider_Inmediato' => $_POST['id_lider_inmediato'] ?: null,
                'Barrio' => $_POST['barrio'] ?: null,
                'Red' => $_POST['red'] ?: null,
                'Id_Anfitrion' => $_POST['id_anfitrion'] ?: null,
                'Telefono_Anfitrion' => $_POST['telefono_anfitrion'] ?: null
            ];
            
            $this->celulaModel->create($data);
            $this->redirect('celulas');
        } else {
            $this->view('celulas/formulario');
        }
    }

    public function editar() {
        // Verificar permiso de editar
        if (!AuthController::tienePermiso('celulas', 'editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('celulas');
        }

        if (!$this->puedeAccederCelula($id)) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre_Celula' => $_POST['nombre_celula'],
                'Direccion_Celula' => $_POST['direccion_celula'],
                'Dia_Reunion' => $_POST['dia_reunion'],
                'Hora_Reunion' => $_POST['hora_reunion'],
                'Id_Lider' => $_POST['id_lider'] ?: null,
                'Pastor_Principal' => $_POST['pastor_principal'] ?: null,
                'Id_Lider_Inmediato' => $_POST['id_lider_inmediato'] ?: null,
                'Barrio' => $_POST['barrio'] ?: null,
                'Red' => $_POST['red'] ?: null,
                'Id_Anfitrion' => $_POST['id_anfitrion'] ?: null,
                'Telefono_Anfitrion' => $_POST['telefono_anfitrion'] ?: null
            ];
            
            $this->celulaModel->update($id, $data);
            $this->redirect('celulas');
        } else {
            $data = [
                'celula' => $this->celulaModel->getById($id)
            ];
            $this->view('celulas/formulario', $data);
        }
    }

    public function detalle() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('celulas');
        }

        if (!$this->puedeAccederCelula($id)) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $celula = $this->celulaModel->getWithMembers($id);
        $this->view('celulas/detalle', ['celula' => $celula]);
    }

    public function exportarExcel() {
        if (!AuthController::tienePermiso('celulas', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
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
        if (!AuthController::tienePermiso('celulas', 'eliminar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            if (!$this->puedeAccederCelula($id)) {
                header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
                exit;
            }
            $this->celulaModel->delete($id);
        }
        
        $this->redirect('celulas');
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
        if (!AuthController::tienePermiso('materiales_celulas', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
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
                    if (!AuthController::tienePermiso('materiales_celulas', 'eliminar')) {
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
                        || AuthController::tienePermiso('materiales_celulas', 'editar')
                        || AuthController::tienePermiso('materiales_celulas', 'crear');

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

                if (!AuthController::tienePermiso('materiales_celulas', 'crear')) {
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
        if (!AuthController::tienePermiso('materiales_celulas', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
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
                    WHERE p.Id_Rol IN (3, 6, 8)
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
                    WHERE p.Id_Rol = 8
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
                    WHERE p.Id_Rol = 6
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
        if (!AuthController::tienePermiso('materiales_celulas', 'ver')) {
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
