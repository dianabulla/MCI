<?php
/**
 * Controlador Teen
 * Módulo para subir material PDF, listar, ver, eliminar y consultar visualizaciones.
 */

require_once APP . '/Models/Teen.php';
require_once APP . '/Controllers/AuthController.php';

class TeenController extends BaseController {
    private $teenModel;

    public function __construct() {
        $this->teenModel = new Teen();
    }

    /**
     * Pantalla principal del módulo.
     * GET: lista materiales
     * POST: sube nuevo PDF
     */
    public function index() {
        if (!AuthController::tienePermiso('teen', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $directorioMateriales = ROOT . '/public/uploads/teens';
        if (!is_dir($directorioMateriales)) {
            @mkdir($directorioMateriales, 0775, true);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!AuthController::tienePermiso('teen', 'crear')) {
                $this->redirect('teen&mensaje=' . urlencode('No tienes permiso para subir material.') . '&tipo=error');
                return;
            }

            try {
                $titulo = trim((string)($_POST['titulo'] ?? ''));
                $descripcion = trim((string)($_POST['descripcion'] ?? ''));

                if ($titulo === '') {
                    throw new Exception('El título es obligatorio.');
                }

                if (!isset($_FILES['archivo_pdf'])) {
                    throw new Exception('No se recibieron archivos PDF.');
                }

                $archivos = $_FILES['archivo_pdf'];
                
                // Convertir a array si es un solo archivo
                if (!is_array($archivos['name'])) {
                    $archivos = [
                        'name' => [$archivos['name']],
                        'tmp_name' => [$archivos['tmp_name']],
                        'size' => [$archivos['size']],
                        'error' => [$archivos['error']]
                    ];
                }

                $archivosSubidos = [];
                $erroresSubida = [];

                for ($i = 0; $i < count($archivos['name']); $i++) {
                    try {
                        $archivoTemp = [
                            'name' => $archivos['name'][$i],
                            'tmp_name' => $archivos['tmp_name'][$i],
                            'size' => $archivos['size'][$i],
                            'error' => $archivos['error'][$i]
                        ];

                        $archivoFinal = $this->subirPdf($archivoTemp, $directorioMateriales);
                        $archivosSubidos[] = $archivoFinal;
                    } catch (Exception $e) {
                        $erroresSubida[] = htmlspecialchars($archivos['name'][$i]) . ': ' . $e->getMessage();
                    }
                }

                if (empty($archivosSubidos)) {
                    throw new Exception('No se pudieron subir los archivos. ' . implode(', ', $erroresSubida));
                }

                // Crear UN SOLO módulo con todos los archivos
                $archivosJson = json_encode($archivosSubidos);
                
                // Debug: verificar que los datos sean válidos
                if ($archivosJson === false) {
                    throw new Exception('Error al codificar JSON: ' . json_last_error_msg());
                }
                
                if (strlen($archivosJson) > 65500) {
                    throw new Exception('El JSON es demasiado grande (' . strlen($archivosJson) . ' bytes). Límite máximo: 65500 bytes.');
                }
                
                $data = [
                    'titulo' => $titulo,
                    'descripcion' => $descripcion !== '' ? $descripcion : null,
                    'archivos_pdf' => $archivosJson
                ];

                // Debug: log de datos antes de insertar
                error_log('TeenController: Insertando ' . count($archivosSubidos) . ' archivo(s)');
                error_log('TeenController: JSON = ' . $archivosJson);
                error_log('TeenController: Data = ' . print_r($data, true));

                $id = $this->teenModel->create($data);
                
                error_log('TeenController: Insertado con ID = ' . $id);

                $mensaje = count($archivosSubidos) . ' archivo(s) agregado(s) al módulo "' . htmlspecialchars($titulo) . '" correctamente.';
                if (!empty($erroresSubida)) {
                    $mensaje .= ' Errores: ' . implode('; ', $erroresSubida);
                }

                $this->redirect('teen&mensaje=' . urlencode($mensaje) . '&tipo=success');
            } catch (Exception $e) {
                $this->redirect('teen&mensaje=' . urlencode($e->getMessage()) . '&tipo=error');
            }
            return;
        }

        $materiales = $this->teenModel->getAll();
        $vistasPorArchivo = [];

        try {
            $vistasPorArchivo = $this->obtenerConteoVistasTeen();
        } catch (Throwable $e) {
            $vistasPorArchivo = [];
        }

        foreach ($materiales as &$material) {
            $archivosJson = (string)($material['archivos_pdf'] ?? '');
            $archivos = [];
            
            if (!empty($archivosJson)) {
                $archivos = json_decode($archivosJson, true);
                if (!is_array($archivos)) {
                    $archivos = [];
                }
            }
            
            $material['archivos'] = [];
            $pesoTotal = 0;
            $vistasTotales = 0;
            $fechaUltima = 0;
            
            foreach ($archivos as $nombreArchivo) {
                $nombreArchivo = (string)$nombreArchivo;
                $ruta = $directorioMateriales . '/' . basename($nombreArchivo);
                
                $infArchivo = [
                    'nombre' => $nombreArchivo,
                    'url' => PUBLIC_URL . 'index.php?url=teen/verPdf&archivo=' . rawurlencode($nombreArchivo),
                    'peso_kb' => is_file($ruta) ? round(((int)@filesize($ruta)) / 1024, 2) : 0,
                    'fecha_mod' => is_file($ruta) ? (@filemtime($ruta) ?: 0) : 0
                ];
                
                $material['archivos'][] = $infArchivo;
                $pesoTotal += $infArchivo['peso_kb'];
                $vistasTotales += (int)($vistasPorArchivo[$nombreArchivo] ?? 0);
                $fechaUltima = max($fechaUltima, $infArchivo['fecha_mod']);
            }
            
            $material['peso_total_kb'] = $pesoTotal;
            $material['vistas_totales'] = $vistasTotales;
            $material['fecha_ultima'] = $fechaUltima;
        }
        unset($material);

        usort($materiales, static function ($a, $b) {
            return ((int)($b['fecha_ultima'] ?? 0)) <=> ((int)($a['fecha_ultima'] ?? 0));
        });

        $this->view('teen/lista', [
            'materiales' => $materiales,
            'mensaje' => $_GET['mensaje'] ?? '',
            'tipo' => $_GET['tipo'] ?? ''
        ]);
    }

    /**
     * Abrir PDF y registrar visualización.
     */
    public function verPdf() {
        if (!AuthController::tienePermiso('teen', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $archivo = basename((string)($_GET['archivo'] ?? ''));
        if ($archivo === '' || strtolower(pathinfo($archivo, PATHINFO_EXTENSION)) !== 'pdf') {
            $this->redirect('teen&mensaje=' . urlencode('Archivo inválido') . '&tipo=error');
            return;
        }

        $ruta = ROOT . '/public/uploads/teens/' . $archivo;
        if (!is_file($ruta)) {
            $this->redirect('teen&mensaje=' . urlencode('El archivo no existe') . '&tipo=error');
            return;
        }

        $idPersona = (int)($_SESSION['usuario_id'] ?? 0);

        try {
            $this->registrarVistaTeen($archivo, $idPersona);
        } catch (Throwable $e) {
            // No bloquear apertura del PDF por fallo de tracking.
        }

        header('Location: ' . PUBLIC_URL . 'uploads/teens/' . rawurlencode($archivo));
        exit;
    }

    /**
     * Eliminar módulo completamente (todos sus archivos)
     */
    public function eliminar() {
        if (!AuthController::tienePermiso('teen', 'eliminar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);

        if ($id > 0) {
            $material = $this->teenModel->getById($id);

            if ($material) {
                // Eliminar todos los archivos físicos
                $archivosJson = (string)($material['archivos_pdf'] ?? '');
                if (!empty($archivosJson)) {
                    $archivos = json_decode($archivosJson, true);
                    if (is_array($archivos)) {
                        foreach ($archivos as $nombreArchivo) {
                            $archivo = basename((string)$nombreArchivo);
                            if ($archivo !== '') {
                                $ruta = ROOT . '/public/uploads/teens/' . $archivo;
                                if (is_file($ruta)) {
                                    @unlink($ruta);
                                }
                            }
                        }
                    }
                }

                $this->teenModel->deleteTeen($id);
            }
        }

        $this->redirect('teen&mensaje=' . urlencode('Material eliminado correctamente') . '&tipo=success');
    }

    /**
     * AJAX: detalle de quiénes vieron un material.
     */
    public function detalleVistas() {
        if (!AuthController::tienePermiso('teen', 'ver')) {
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
            $this->asegurarTablaVistasTeen();

            global $pdo;
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                echo json_encode(['success' => false, 'message' => 'Error de conexión']);
                exit;
            }

            $sql = "SELECT 
                        tv.id_persona,
                        p.Nombre,
                        p.Apellido,
                        p.Telefono,
                        m.Nombre_Ministerio,
                        tv.total_vistas,
                        tv.fecha_primera_vista,
                        tv.fecha_ultima_vista
                    FROM teen_vista tv
                    LEFT JOIN persona p ON tv.id_persona = p.Id_Persona
                    LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                    WHERE tv.archivo = ?
                    ORDER BY tv.fecha_ultima_vista DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$archivo]);
            $vistas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'archivo' => htmlspecialchars($archivo),
                'total_personas' => count($vistas),
                'vistas' => $vistas
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener datos']);
        }
        exit;
    }

    /**
     * Subir y validar PDF.
     */
    private function subirPdf($archivo, $directorio) {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir archivo. Intente nuevamente.');
        }

        $nombreOriginal = trim((string)($archivo['name'] ?? 'material.pdf'));
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

        $base = pathinfo($nombreOriginal, PATHINFO_FILENAME);
        $base = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $base);
        $base = preg_replace('/\s+/', '_', (string)$base);
        $base = trim((string)$base, '_-');
        if ($base === '') {
            $base = 'material_teen';
        }

        $nombreFinal = date('Ymd_His') . '_' . substr((string)md5(uniqid((string)mt_rand(), true)), 0, 8) . '_' . $base . '.pdf';
        $destino = rtrim($directorio, '/') . '/' . $nombreFinal;

        if (!move_uploaded_file($tmp, $destino)) {
            throw new Exception('No se pudo guardar el PDF en el servidor.');
        }

        return $nombreFinal;
    }

    /**
     * Crear tabla de tracking de vistas.
     */
    private function asegurarTablaVistasTeen() {
        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS teen_vista (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    archivo VARCHAR(255) NOT NULL,
                    id_persona INT NOT NULL,
                    total_vistas INT NOT NULL DEFAULT 1,
                    fecha_primera_vista DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    fecha_ultima_vista DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_archivo_persona (archivo, id_persona),
                    KEY idx_archivo (archivo),
                    KEY idx_persona (id_persona)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);
    }

    /**
     * Mapa archivo => cantidad de personas distintas que lo vieron.
     */
    private function obtenerConteoVistasTeen() {
        $this->asegurarTablaVistasTeen();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return [];
        }

        $rows = $pdo->query("SELECT archivo, COUNT(DISTINCT id_persona) AS personas_vieron FROM teen_vista GROUP BY archivo")
                    ->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $archivo = (string)($row['archivo'] ?? '');
            if ($archivo === '') {
                continue;
            }
            $map[$archivo] = (int)($row['personas_vieron'] ?? 0);
        }

        return $map;
    }

    /**
     * Registrar visualización por usuario.
     */
    private function registrarVistaTeen($archivo, $idPersona) {
        $archivo = basename((string)$archivo);
        $idPersona = (int)$idPersona;

        if ($archivo === '' || $idPersona <= 0) {
            return;
        }

        $this->asegurarTablaVistasTeen();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return;
        }

        $sql = "INSERT INTO teen_vista (archivo, id_persona, total_vistas)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE total_vistas = total_vistas + 1, fecha_ultima_vista = NOW()";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$archivo, $idPersona]);
    }

    /**
     * Editar material: actualizar título, descripción y/o agregar archivos
     */
    public function editar() {
        if (!AuthController::tienePermiso('teen', 'editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('teen&mensaje=' . urlencode('ID inválido') . '&tipo=error');
            return;
        }

        $material = $this->teenModel->getById($id);
        if (!$material) {
            $this->redirect('teen&mensaje=' . urlencode('Material no encontrado') . '&tipo=error');
            return;
        }

        $directorioMateriales = ROOT . '/public/uploads/teens';
        if (!is_dir($directorioMateriales)) {
            @mkdir($directorioMateriales, 0775, true);
        }

        // Preparar archivos actuales
        $archivosActuales = [];
        $archivosJson = (string)($material['archivos_pdf'] ?? '');
        if (!empty($archivosJson)) {
            $archivosActuales = json_decode($archivosJson, true);
            if (!is_array($archivosActuales)) {
                $archivosActuales = [];
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $titulo = trim((string)($_POST['titulo'] ?? ''));
                $descripcion = trim((string)($_POST['descripcion'] ?? ''));
                $archivosAEliminar = isset($_POST['eliminar_archivo']) ? (array)$_POST['eliminar_archivo'] : [];

                if ($titulo === '') {
                    throw new Exception('El título es obligatorio.');
                }

                // Procesar archivos a eliminar
                $archivosActualizados = [];
                foreach ($archivosActuales as $archivoNombre) {
                    if (!in_array($archivoNombre, $archivosAEliminar, true)) {
                        $archivosActualizados[] = $archivoNombre;
                    } else {
                        // Eliminar archivo físico
                        $ruta = $directorioMateriales . '/' . basename($archivoNombre);
                        if (is_file($ruta)) {
                            @unlink($ruta);
                        }
                    }
                }

                // Procesar nuevos archivos si los hay
                $nuevosArchivos = [];
                if (isset($_FILES['archivo_pdf']) && !empty($_FILES['archivo_pdf']['name'][0])) {
                    $archivos = $_FILES['archivo_pdf'];
                    
                    // Convertir a array si es un solo archivo
                    if (!is_array($archivos['name'])) {
                        $archivos = [
                            'name' => [$archivos['name']],
                            'tmp_name' => [$archivos['tmp_name']],
                            'size' => [$archivos['size']],
                            'error' => [$archivos['error']]
                        ];
                    }

                    $erroresSubida = [];
                    for ($i = 0; $i < count($archivos['name']); $i++) {
                        try {
                            $archivoTemp = [
                                'name' => $archivos['name'][$i],
                                'tmp_name' => $archivos['tmp_name'][$i],
                                'size' => $archivos['size'][$i],
                                'error' => $archivos['error'][$i]
                            ];
                            $archivoFinal = $this->subirPdf($archivoTemp, $directorioMateriales);
                            $nuevosArchivos[] = $archivoFinal;
                        } catch (Exception $e) {
                            $erroresSubida[] = htmlspecialchars($archivos['name'][$i]) . ': ' . $e->getMessage();
                        }
                    }

                    if (!empty($erroresSubida)) {
                        throw new Exception('Errores al subir archivos: ' . implode(', ', $erroresSubida));
                    }
                }

                // Combinar archivos: mantener los existentes + agregar los nuevos
                $archivosFinales = array_merge($archivosActualizados, $nuevosArchivos);

                if (empty($archivosFinales)) {
                    throw new Exception('Debe haber al menos un archivo. Agregue archivos nuevos antes de eliminar todos.');
                }

                // Actualizar en BD
                $dataUpdate = [
                    'titulo' => $titulo,
                    'descripcion' => $descripcion !== '' ? $descripcion : null,
                    'archivos_pdf' => json_encode($archivosFinales)
                ];

                $this->teenModel->updateTeen($id, $dataUpdate);

                $mensaje = 'Material actualizado correctamente.';
                if (!empty($nuevosArchivos)) {
                    $mensaje .= ' Se agregaron ' . count($nuevosArchivos) . ' archivo(s) nuevo(s).';
                }

                $this->redirect('teen&mensaje=' . urlencode($mensaje) . '&tipo=success');
            } catch (Exception $e) {
                $this->redirect('teen&mensaje=' . urlencode($e->getMessage()) . '&tipo=error');
            }
            return;
        }

        // GET: mostrar formulario
        $this->view('teen/editar', [
            'material' => $material,
            'archivosActuales' => $archivosActuales,
            'directorioMateriales' => $directorioMateriales
        ]);
    }
}