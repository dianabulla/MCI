<?php
/**
 * Controlador Nehemias (Formulario publico)
 */

require_once APP . '/Models/Nehemias.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/Seremos1200.php';
require_once APP . '/Controllers/AuthController.php';

class NehemiasController extends BaseController {
    private $nehemiasModel;
    private $ministerioModel;
    private $seremos1200Model;

    public function __construct() {
        $this->nehemiasModel = new Nehemias();
        $this->ministerioModel = new Ministerio();
        $this->seremos1200Model = new Seremos1200();

        try {
            $this->seremos1200Model->ensureTableExists();
        } catch (Exception $e) {
            error_log('No se pudo verificar/crear tabla seremos_1200: ' . $e->getMessage());
        }
    }

    public function index() {
        $this->formulario();
    }

    /**
     * Mostrar formulario publico
     */
    public function formulario() {
        $ministerios = $this->ministerioModel->getAll();

        $data = [
            'ministerios' => $ministerios,
            'mensaje' => $_GET['mensaje'] ?? null,
            'tipo_mensaje' => $_GET['tipo'] ?? null,
            'registro_exitoso' => isset($_GET['exito']) && $_GET['exito'] === '1'
        ];

        $this->view('nehemias/formulario', $data);
    }

    /**
     * Procesar el registro
     */
    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . '?url=nehemias/formulario');
            exit;
        }

        $errores = [];
        $nombres = trim($_POST['nombres'] ?? '');
        $apellidos = trim($_POST['apellidos'] ?? '');
        $cedula = trim($_POST['numero_cedula'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $lider = trim($_POST['lider'] ?? '');
        $liderNehemias = trim($_POST['lider_nehemias'] ?? '');
        $acepta = isset($_POST['acepta']) && $_POST['acepta'] === '1';

        if ($nombres === '') {
            $errores[] = 'Los nombres son requeridos';
        }
        if ($apellidos === '') {
            $errores[] = 'Los apellidos son requeridos';
        }
        if ($cedula === '') {
            $errores[] = 'El numero de cedula es requerido';
        }
        if ($telefono === '') {
            $errores[] = 'El telefono es requerido';
        }
        if ($lider === '') {
            $errores[] = 'El lider es requerido';
        }
        if ($liderNehemias === '') {
            $errores[] = 'El lider Nehemias es requerido';
        }
        if (!$acepta) {
            $errores[] = 'Debe aceptar la autorizacion de tratamiento de datos';
        }

        if (!empty($errores)) {
            $mensaje = urlencode(implode('. ', $errores));
            header('Location: ' . PUBLIC_URL . '?url=nehemias/formulario&mensaje=' . $mensaje . '&tipo=error');
            exit;
        }

        $data = [
            'Nombres' => $nombres,
            'Apellidos' => $apellidos,
            'Numero_Cedula' => $cedula,
            'Telefono' => $telefono,
            'Lider' => $lider,
            'Lider_Nehemias' => $liderNehemias,
            'Acepta' => $acepta ? 1 : 0,
            'Fecha_Registro' => date('Y-m-d H:i:s')
        ];

        try {
            $this->nehemiasModel->create($data);
            $mensaje = urlencode('Registro enviado correctamente. Gracias por tu apoyo.');
            header('Location: ' . PUBLIC_URL . '?url=nehemias/formulario&mensaje=' . $mensaje . '&tipo=success&exito=1');
            exit;
        } catch (Exception $e) {
            $mensaje = urlencode('No se pudo guardar el registro. Intenta de nuevo.');
            header('Location: ' . PUBLIC_URL . '?url=nehemias/formulario&mensaje=' . $mensaje . '&tipo=error');
            exit;
        }
    }

    /**
     * Listado administrativo
     */
    public function lista() {
        if (!AuthController::esAdministrador()) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        $ministeriosNehemias = array_map(
            fn($row) => $row['Lider'],
            $this->nehemiasModel->getMinisteriosDistinct()
        );

        // Obtener filtros de la URL
        $filtros = [
            'busqueda' => $_GET['busqueda'] ?? '',
            'lider_nehemias' => $_GET['lider_nehemias'] ?? '',
            'lider' => $_GET['lider'] ?? '',
            'lider_lista' => $ministeriosNehemias,
            'puesto_vacio' => $_GET['puesto_vacio'] ?? '',
            'puesto_lleno' => $_GET['puesto_lleno'] ?? '',
            'mesa_vacia' => $_GET['mesa_vacia'] ?? '',
            'mesa_llena' => $_GET['mesa_llena'] ?? '',
            'cedula_vacia' => $_GET['cedula_vacia'] ?? '',
            'subido_link_vacio' => $_GET['subido_link_vacio'] ?? '',
            'subido_link_lleno' => $_GET['subido_link_lleno'] ?? '',
            'bogota_subio_vacio' => $_GET['bogota_subio_vacio'] ?? '',
            'bogota_subio_lleno' => $_GET['bogota_subio_lleno'] ?? '',
            'acepta' => $_GET['acepta'] ?? ''
        ];

        // Verificar si hay filtros activos
        $hayFiltros = false;
        foreach ($filtros as $clave => $filtro) {
            if ($clave === 'lider_lista') {
                continue;
            }
            if (!empty($filtro)) {
                $hayFiltros = true;
                break;
            }
        }

        // Obtener registros con o sin filtros
        if ($hayFiltros) {
            $registros = $this->nehemiasModel->getAllWithFilters($filtros);
        } else {
            $registros = $this->nehemiasModel->getAllOrdered();
        }

        $this->view('nehemias/lista', [
            'registros' => $registros,
            'filtros' => $filtros,
            'ministeriosNehemias' => $ministeriosNehemias
        ]);
    }

    /**
     * Reportes Nehemias
     */
    public function reportes() {
        if (!AuthController::esAdministrador()) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        $normalize = function ($value) {
            $value = trim((string)$value);
            if ($value === '') {
                return '';
            }

            $value = str_replace(
                ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'],
                ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N'],
                $value
            );
            $value = preg_replace('/\s+/', ' ', $value);

            if (function_exists('mb_strtoupper')) {
                return mb_strtoupper($value, 'UTF-8');
            }
            return strtoupper($value);
        };

        $metaConfig = [
            ['label' => 'HUGO Y NANCY', 'meta_nehemias' => 40, 'meta_votantes' => 600, 'aliases' => []],
            ['label' => 'FABIAN Y ELIZABETH', 'meta_nehemias' => null, 'meta_votantes' => 600, 'aliases' => []],
            ['label' => 'MICHAEL Y SARA', 'meta_nehemias' => 41, 'meta_votantes' => 615, 'aliases' => []],
            ['label' => 'DISNEY Y ANGELICA', 'meta_nehemias' => 25, 'meta_votantes' => 375, 'aliases' => []],
            ['label' => 'FABIAN Y ANDREA', 'meta_nehemias' => 15, 'meta_votantes' => 225, 'aliases' => []],
            ['label' => 'ALEJANDRO y MADELINE', 'meta_nehemias' => 20, 'meta_votantes' => 300, 'aliases' => ['MADELINE Y ALEJANDRO', 'ALEJANDRO Y MADELINE']],
            ['label' => 'JEFERSON Y MONICA', 'meta_nehemias' => 20, 'meta_votantes' => 300, 'aliases' => []],
            ['label' => 'FERNANDO Y LEIDY', 'meta_nehemias' => 10, 'meta_votantes' => 150, 'aliases' => []],
            ['label' => 'JULIA BARON', 'meta_nehemias' => 25, 'meta_votantes' => 375, 'aliases' => []],
        ];

        $aliasToCanonical = [];
        $fixedOrder = [];
        foreach ($metaConfig as $cfg) {
            $fixedOrder[] = $cfg['label'];
            $aliasToCanonical[$normalize($cfg['label'])] = $cfg['label'];
            foreach ($cfg['aliases'] as $alias) {
                $aliasToCanonical[$normalize($alias)] = $cfg['label'];
            }
        }

        $conteos = $this->nehemiasModel->getVotantesPorLider();
        $totalVotantesGeneral = 0;
        foreach ($conteos as $row) {
            $totalVotantesGeneral += (int)($row['total'] ?? 0);
        }

        $ministerios = [];
        $unknownCanonical = [];
        foreach ($conteos as $row) {
            $ministerioRaw = trim((string)($row['Lider'] ?? ''));
            $liderNehemias = trim((string)($row['Lider_Nehemias'] ?? ''));
            $total = (int)($row['total'] ?? 0);

            if ($ministerioRaw === '') {
                $ministerio = 'Sin ministerio';
            } else {
                $ministerioNorm = $normalize($ministerioRaw);
                if (isset($aliasToCanonical[$ministerioNorm])) {
                    $ministerio = $aliasToCanonical[$ministerioNorm];
                } else {
                    if (!isset($unknownCanonical[$ministerioNorm])) {
                        $unknownCanonical[$ministerioNorm] = $ministerioRaw;
                    }
                    $ministerio = $unknownCanonical[$ministerioNorm];
                }
            }

            if ($liderNehemias === '') {
                $liderNehemias = 'Sin líder nehemias';
            }

            if (!isset($ministerios[$ministerio])) {
                $ministerios[$ministerio] = [];
            }

            if (!isset($ministerios[$ministerio][$liderNehemias])) {
                $ministerios[$ministerio][$liderNehemias] = 0;
            }

            $ministerios[$ministerio][$liderNehemias] += $total;
        }

        $summaryRows = [];
        $sections = [];
        $summaryTotals = [
            'meta_nehemias' => 0,
            'actual_nehemias' => 0,
            'faltantes_nehemias' => 0,
            'meta_votantes' => 0,
            'actual_votantes' => 0,
            'faltantes_votantes' => 0,
            'porcentaje_nehemias' => null,
            'porcentaje_votantes' => null
        ];

        // Resumen con metas fijas
        foreach ($metaConfig as $cfg) {
            $ministerio = $cfg['label'];
            $lideresData = $ministerios[$ministerio] ?? [];

            $actualVotantes = 0;
            foreach ($lideresData as $cantidad) {
                $actualVotantes += (int)$cantidad;
            }

            $metaNehemias = $cfg['meta_nehemias'];
            $actualNehemias = null;
            $faltantesNehemias = null;
            $porcentajeNehemias = null;

            if ($metaNehemias !== null) {
                $actualNehemias = 0;
                foreach ($lideresData as $liderNombre => $cantidad) {
                    if (strcasecmp((string)$liderNombre, 'Sin líder nehemias') === 0) {
                        continue;
                    }
                    $actualNehemias++;
                }
                $faltantesNehemias = max((int)$metaNehemias - (int)$actualNehemias, 0);
                $porcentajeNehemias = (int)$metaNehemias > 0 ? round(((int)$actualNehemias / (int)$metaNehemias) * 100) : null;
            }

            $metaVotantes = (int)$cfg['meta_votantes'];
            $faltantesVotantes = max($metaVotantes - $actualVotantes, 0);
            $porcentajeVotantes = $metaVotantes > 0 ? round(($actualVotantes / $metaVotantes) * 100) : null;

            $summaryRows[] = [
                'label' => $ministerio,
                'meta_nehemias' => $metaNehemias,
                'actual_nehemias' => $actualNehemias,
                'faltantes_nehemias' => $faltantesNehemias,
                'porcentaje_nehemias' => $porcentajeNehemias,
                'meta_votantes' => $metaVotantes,
                'actual_votantes' => $actualVotantes,
                'faltantes_votantes' => $faltantesVotantes,
                'porcentaje_votantes' => $porcentajeVotantes
            ];

            if ($metaNehemias !== null) {
                $summaryTotals['meta_nehemias'] += (int)$metaNehemias;
                $summaryTotals['actual_nehemias'] += (int)$actualNehemias;
                $summaryTotals['faltantes_nehemias'] += (int)$faltantesNehemias;
            }
            $summaryTotals['meta_votantes'] += $metaVotantes;
            $summaryTotals['actual_votantes'] += $actualVotantes;
            $summaryTotals['faltantes_votantes'] += $faltantesVotantes;
        }

        $summaryTotals['porcentaje_nehemias'] = $summaryTotals['meta_nehemias'] > 0
            ? round(($summaryTotals['actual_nehemias'] / $summaryTotals['meta_nehemias']) * 100)
            : null;
        $summaryTotals['porcentaje_votantes'] = $summaryTotals['meta_votantes'] > 0
            ? round(($summaryTotals['actual_votantes'] / $summaryTotals['meta_votantes']) * 100)
            : null;

        // Secciones: primero ministerios de la tabla fija, luego otros y al final "Sin ministerio"
        $orderedSectionLabels = [];
        foreach ($fixedOrder as $fixedLabel) {
            if (isset($ministerios[$fixedLabel])) {
                $orderedSectionLabels[] = $fixedLabel;
            }
        }

        $otros = array_filter(array_keys($ministerios), function ($label) use ($fixedOrder) {
            return !in_array($label, $fixedOrder, true) && strcasecmp($label, 'Sin ministerio') !== 0;
        });
        usort($otros, 'strnatcasecmp');
        foreach ($otros as $otro) {
            $orderedSectionLabels[] = $otro;
        }
        if (isset($ministerios['Sin ministerio'])) {
            $orderedSectionLabels[] = 'Sin ministerio';
        }

        foreach ($orderedSectionLabels as $ministerio) {
            $lideresData = $ministerios[$ministerio] ?? [];
            uksort($lideresData, function ($a, $b) {
                $aIsSin = strcasecmp($a, 'Sin líder nehemias') === 0;
                $bIsSin = strcasecmp($b, 'Sin líder nehemias') === 0;

                if ($aIsSin && !$bIsSin) {
                    return 1;
                }
                if (!$aIsSin && $bIsSin) {
                    return -1;
                }

                return strnatcasecmp($a, $b);
            });

            $rows = [];
            $nro = 1;
            $totalVotantes = 0;

            foreach ($lideresData as $liderNombre => $cantidad) {
                $cantidad = (int)$cantidad;
                $totalVotantes += $cantidad;
                $rows[] = [
                    'nro' => $nro++,
                    'lider' => $liderNombre,
                    'votantes' => $cantidad
                ];
            }

            $totalLideres = count($lideresData);

            $sections[] = [
                'label' => $ministerio,
                'rows' => $rows,
                'total_votantes' => $totalVotantes
            ];
        }

        $puestoMesaRaw = $this->nehemiasModel->getConteoPorPuestoMesa();
        $puestoMesaSectionsMap = [];
        foreach ($puestoMesaRaw as $row) {
            $puesto = (string)($row['puesto'] ?? 'Sin puesto');
            $mesa = (string)($row['mesa'] ?? 'Sin mesa');
            $total = (int)($row['total'] ?? 0);

            if (!isset($puestoMesaSectionsMap[$puesto])) {
                $puestoMesaSectionsMap[$puesto] = [
                    'puesto' => $puesto,
                    'total' => 0,
                    'mesas' => []
                ];
            }

            $puestoMesaSectionsMap[$puesto]['total'] += $total;
            $puestoMesaSectionsMap[$puesto]['mesas'][] = [
                'mesa' => $mesa,
                'total' => $total
            ];
        }

        $puestoMesaSections = array_values($puestoMesaSectionsMap);
        usort($puestoMesaSections, function ($a, $b) {
            return $b['total'] <=> $a['total'];
        });

        $puestoChartRaw = $this->nehemiasModel->getConteoPorPuesto();
        $puestoChartRaw = array_slice($puestoChartRaw, 0, 12);
        $puestoChartLabels = array_map(function ($row) {
            return (string)($row['puesto'] ?? 'Sin puesto');
        }, $puestoChartRaw);
        $puestoChartValues = array_map(function ($row) {
            return (int)($row['total'] ?? 0);
        }, $puestoChartRaw);

        $totalRegistrosUbicacion = 0;
        foreach ($puestoChartRaw as $row) {
            $totalRegistrosUbicacion += (int)($row['total'] ?? 0);
        }

        $this->view('nehemias/reportes', [
            'summaryRows' => $summaryRows,
            'summaryTotals' => $summaryTotals,
            'sections' => $sections,
            'puestoMesaSections' => $puestoMesaSections,
            'puestoChartLabels' => $puestoChartLabels,
            'puestoChartValues' => $puestoChartValues,
            'totalRegistrosUbicacion' => $totalRegistrosUbicacion,
            'totalVotantesGeneral' => $totalVotantesGeneral
        ]);
    }

    /**
     * Exportar registros a Excel (CSV)
     */
    public function exportarExcel() {
        if (!AuthController::esAdministrador()) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        $registros = $this->nehemiasModel->getAllOrdered();
        $nombreArchivo = 'Nehemias_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'Nombres',
            'Apellidos',
            'Numero de Cedula',
            'Telefono',
            'Lider',
            'Lider Nehemias',
            'Subido link de Nehemias',
            'En Bogota se le subio',
            'Puesto de votacion',
            'Mesa de votacion',
            'Acepta',
            'Fecha de Registro'
        ], ';');

        foreach ($registros as $registro) {
            fputcsv($output, [
                $registro['Nombres'],
                $registro['Apellidos'],
                $registro['Numero_Cedula'],
                $registro['Telefono'],
                $registro['Lider'],
                $registro['Lider_Nehemias'],
                $registro['Subido_Link'] ?? '',
                $registro['En_Bogota_Subio'] ?? '',
                $registro['Puesto_Votacion'] ?? '',
                $registro['Mesa_Votacion'] ?? '',
                $registro['Acepta'] ? 'Si' : 'No',
                $registro['Fecha_Registro']
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Editar registro (admin)
     */
    public function editar() {
        if (!AuthController::esAdministrador()) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: ' . PUBLIC_URL . '?url=nehemias/lista');
            exit;
        }

        $registro = $this->nehemiasModel->getById($id);
        if (!$registro) {
            header('Location: ' . PUBLIC_URL . '?url=nehemias/lista');
            exit;
        }

        $this->view('nehemias/editar', [
            'registro' => $registro
        ]);
    }

    /**
     * Actualizar registro (admin)
     */
    public function actualizar() {
        if (!AuthController::esAdministrador()) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . '?url=nehemias/lista');
            exit;
        }

        $id = $_POST['id'] ?? null;
        if (!$id) {
            header('Location: ' . PUBLIC_URL . '?url=nehemias/lista');
            exit;
        }

        $data = [
            'Subido_Link' => trim($_POST['subido_link'] ?? ''),
            'En_Bogota_Subio' => trim($_POST['en_bogota_subio'] ?? ''),
            'Puesto_Votacion' => trim($_POST['puesto_votacion'] ?? ''),
            'Mesa_Votacion' => trim($_POST['mesa_votacion'] ?? '')
        ];

        $this->nehemiasModel->update($id, $data);
        header('Location: ' . PUBLIC_URL . '?url=nehemias/lista');
        exit;
    }

    /**
     * Importador por carga de archivo (admin)
     */
    public function importar() {
        if (!AuthController::esAdministrador()) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        require ROOT . '/importar_nehemias.php';
        exit;
    }

    /**
     * Importador directo desde datos_nehemias.csv (admin)
     */
    public function importarDirecto() {
        if (!AuthController::esAdministrador()) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        require ROOT . '/importar_nehemias_directo.php';
        exit;
    }

    /**
     * Reparar registros mal importados (admin)
     */
    public function repararImportacion() {
        if (!AuthController::esAdministrador()) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        require ROOT . '/reparar_nehemias_importacion.php';
        exit;
    }

    /**
     * Pantalla de administración Seremos 1200
     */
    public function seremos1200() {
        if (!AuthController::esAdministrador()) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        $registros = $this->seremos1200Model->getAllOrdered();

        $this->view('nehemias/seremos1200', [
            'registros' => $registros,
            'mensaje' => $_GET['mensaje'] ?? null,
            'tipo' => $_GET['tipo'] ?? 'info'
        ]);
    }

    /**
     * Importar base Seremos 1200 desde archivo Excel/CSV
     */
    public function importarSeremos1200() {
        if (!AuthController::esAdministrador()) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['archivo'])) {
            header('Location: ' . PUBLIC_URL . '?url=nehemias/seremos1200&mensaje=' . urlencode('Debe seleccionar un archivo para importar') . '&tipo=error');
            exit;
        }

        $archivoTmp = $_FILES['archivo']['tmp_name'] ?? '';
        $archivoNombre = $_FILES['archivo']['name'] ?? '';
        $archivoError = isset($_FILES['archivo']['error']) ? (int)$_FILES['archivo']['error'] : UPLOAD_ERR_OK;

        if ($archivoError !== UPLOAD_ERR_OK) {
            $mensajeError = 'Error al subir el archivo';

            switch ($archivoError) {
                case UPLOAD_ERR_INI_SIZE:
                    $mensajeError = 'El archivo supera el límite permitido por el servidor (upload_max_filesize)';
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $mensajeError = 'El archivo supera el tamaño máximo permitido por el formulario';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $mensajeError = 'El archivo se subió parcialmente. Intente nuevamente';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $mensajeError = 'Debe seleccionar un archivo para importar';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $mensajeError = 'Falta la carpeta temporal de PHP para cargas';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $mensajeError = 'No se pudo escribir el archivo en disco';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $mensajeError = 'Una extensión de PHP bloqueó la carga del archivo';
                    break;
            }

            header('Location: ' . PUBLIC_URL . '?url=nehemias/seremos1200&mensaje=' . urlencode($mensajeError) . '&tipo=error');
            exit;
        }

        if ($archivoTmp === '' || !is_uploaded_file($archivoTmp)) {
            header('Location: ' . PUBLIC_URL . '?url=nehemias/seremos1200&mensaje=' . urlencode('Archivo inválido') . '&tipo=error');
            exit;
        }

        try {
            $filas = $this->extraerFilasArchivoSpreadsheet($archivoTmp, $archivoNombre);
            if (count($filas) === 0) {
                header('Location: ' . PUBLIC_URL . '?url=nehemias/seremos1200&mensaje=' . urlencode('No se encontraron filas para importar') . '&tipo=error');
                exit;
            }

            $headers = [];
            $indiceHeader = $this->detectarIndiceEncabezadoSeremos1200($filas);

            if ($indiceHeader !== null) {
                $headers = $filas[$indiceHeader];
                $filas = array_slice($filas, $indiceHeader + 1);
            } else {
                header('Location: ' . PUBLIC_URL . '?url=nehemias/seremos1200&mensaje=' . urlencode('No se pudo detectar la fila de encabezados del archivo. Asegure columnas como: NOMBRES Y APELLIDOS, CEDULA, TELEFONO, LIDER DE 12, PUNTO DE VOTACION, MESA.') . '&tipo=error');
                exit;
            }

            $insertados = 0;
            $omitidos = 0;
            $duplicados = 0;
            $cedulasArchivo = [];

            foreach ($filas as $fila) {
                $registro = $this->mapearFilaSeremos1200($headers, $fila);

                if (
                    $registro['Nombres'] === ''
                    && $registro['Apellidos'] === ''
                    && $registro['Numero_Cedula'] === ''
                    && $registro['Telefono'] === ''
                ) {
                    $omitidos++;
                    continue;
                }

                $cedulaNormalizada = $this->normalizarCedula($registro['Numero_Cedula'] ?? '');
                if ($cedulaNormalizada !== '') {
                    if (isset($cedulasArchivo[$cedulaNormalizada])) {
                        $duplicados++;
                        continue;
                    }

                    if ($this->seremos1200Model->existeCedula($cedulaNormalizada)) {
                        $duplicados++;
                        continue;
                    }

                    $cedulasArchivo[$cedulaNormalizada] = true;
                }

                $this->seremos1200Model->create($registro);
                $insertados++;
            }

            $mensaje = sprintf('Importación completada. Insertados: %d. Omitidos vacíos: %d. Omitidos duplicados: %d.', $insertados, $omitidos, $duplicados);
            if ($insertados === 0 && $omitidos > 0) {
                $mensaje .= ' Verifique que la hoja tenga columnas como: Nombres y Apellidos, Cédula, Teléfono, Líder de 12, Punto de votación, Mesa.';
            }
            header('Location: ' . PUBLIC_URL . '?url=nehemias/seremos1200&mensaje=' . urlencode($mensaje) . '&tipo=success');
            exit;
        } catch (Exception $e) {
            $mensaje = 'Error importando archivo: ' . $e->getMessage();
            header('Location: ' . PUBLIC_URL . '?url=nehemias/seremos1200&mensaje=' . urlencode($mensaje) . '&tipo=error');
            exit;
        }
    }

    /**
     * Marcar decisión Sí/No y migrar a nehemias cuando acepta
     */
    public function decisionSeremos1200() {
        if (!AuthController::esAdministrador()) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . '?url=nehemias/seremos1200');
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $acepta = isset($_POST['acepta']) && (string)$_POST['acepta'] === '1' ? 1 : 0;

        if ($id <= 0) {
            header('Location: ' . PUBLIC_URL . '?url=nehemias/seremos1200&mensaje=' . urlencode('Registro inválido') . '&tipo=error');
            exit;
        }

        $registro = $this->seremos1200Model->getById($id);
        if (!$registro) {
            header('Location: ' . PUBLIC_URL . '?url=nehemias/seremos1200&mensaje=' . urlencode('No se encontró el registro') . '&tipo=error');
            exit;
        }

        if ($acepta === 1) {
            if ((int)($registro['Fue_Migrado_Nehemias'] ?? 0) === 1) {
                $this->seremos1200Model->marcarDecision($id, 1, 1, $registro['Nehemias_Id'] ?? null);
                header('Location: ' . PUBLIC_URL . '?url=nehemias/seremos1200&mensaje=' . urlencode('Este registro ya había sido migrado a Nehemias') . '&tipo=info');
                exit;
            }

            $nehemiasId = $this->nehemiasModel->create([
                'Nombres' => trim((string)($registro['Nombres'] ?? '')),
                'Apellidos' => trim((string)($registro['Apellidos'] ?? '')),
                'Numero_Cedula' => trim((string)($registro['Numero_Cedula'] ?? '')),
                'Telefono' => trim((string)($registro['Telefono'] ?? '')),
                'Lider' => trim((string)($registro['Lider'] ?? '')),
                'Lider_Nehemias' => trim((string)($registro['Lider_Nehemias'] ?? '')),
                'Subido_Link' => trim((string)($registro['Subido_Link'] ?? '')),
                'En_Bogota_Subio' => trim((string)($registro['En_Bogota_Subio'] ?? '')),
                'Puesto_Votacion' => trim((string)($registro['Puesto_Votacion'] ?? '')),
                'Mesa_Votacion' => trim((string)($registro['Mesa_Votacion'] ?? '')),
                'Acepta' => 1,
                'Fecha_Registro' => date('Y-m-d H:i:s')
            ]);

            $this->seremos1200Model->marcarDecision($id, 1, 1, (int)$nehemiasId);

            header('Location: ' . PUBLIC_URL . '?url=nehemias/seremos1200&mensaje=' . urlencode('Registro aceptado y enviado a Nehemias correctamente') . '&tipo=success');
            exit;
        }

        $this->seremos1200Model->marcarDecision($id, 0, null, null);
        header('Location: ' . PUBLIC_URL . '?url=nehemias/seremos1200&mensaje=' . urlencode('Registro marcado como No acepta') . '&tipo=info');
        exit;
    }

    private function extraerFilasArchivoSpreadsheet($archivoTmp, $archivoNombre) {
        $extension = strtolower(pathinfo((string)$archivoNombre, PATHINFO_EXTENSION));

        if ($extension === 'xlsx') {
            return $this->leerFilasXlsx($archivoTmp);
        }

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->leerFilasCsv($archivoTmp);
        }

        throw new Exception('Formato no soportado. Use .xlsx, .csv o .txt');
    }

    private function leerFilasCsv($archivoTmp) {
        $filas = [];
        $handle = fopen($archivoTmp, 'r');
        if ($handle === false) {
            throw new Exception('No se pudo abrir el archivo CSV');
        }

        $primeraLinea = fgets($handle);
        rewind($handle);

        $delimitador = ',';
        $conteoComa = substr_count((string)$primeraLinea, ',');
        $conteoPuntoComa = substr_count((string)$primeraLinea, ';');
        $conteoTab = substr_count((string)$primeraLinea, "\t");

        if ($conteoTab >= $conteoComa && $conteoTab >= $conteoPuntoComa) {
            $delimitador = "\t";
        } elseif ($conteoPuntoComa > $conteoComa) {
            $delimitador = ';';
        }

        while (($data = fgetcsv($handle, 20000, $delimitador)) !== false) {
            if (!is_array($data)) {
                continue;
            }

            if (isset($data[0])) {
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$data[0]);
            }

            $filas[] = array_map(static function ($v) {
                return trim((string)$v);
            }, $data);
        }

        fclose($handle);
        return $filas;
    }

    private function leerFilasXlsx($archivoTmp) {
        if (!class_exists('ZipArchive')) {
            throw new Exception('La extensión ZipArchive no está disponible en PHP');
        }

        $zip = new ZipArchive();
        if ($zip->open($archivoTmp) !== true) {
            throw new Exception('No se pudo abrir el archivo XLSX');
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $sx = @simplexml_load_string($sharedXml);
            if ($sx !== false) {
                $items = $sx->xpath('//*[local-name()="si"]');
                if ($items !== false) {
                    foreach ($items as $si) {
                        $text = '';
                        $textNodes = $si->xpath('.//*[local-name()="t"]');
                        if ($textNodes !== false) {
                            foreach ($textNodes as $tn) {
                                $text .= (string)$tn;
                            }
                        }
                        $sharedStrings[] = trim($text);
                    }
                }
            }
        }

        $sheetPaths = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (strpos((string)$name, 'xl/worksheets/') === 0 && substr((string)$name, -4) === '.xml') {
                $sheetPaths[] = (string)$name;
            }
        }

        if (empty($sheetPaths)) {
            $zip->close();
            throw new Exception('No se encontró ninguna hoja en el archivo XLSX');
        }

        sort($sheetPaths);

        $mejorFilas = [];
        $mejorPuntaje = -1;

        foreach ($sheetPaths as $sheetPath) {
            $sheetXml = $zip->getFromName($sheetPath);
            if ($sheetXml === false) {
                continue;
            }

            $filasHoja = $this->parsearFilasSheetXml($sheetXml, $sharedStrings);
            $puntaje = $this->puntuarFilasSeremos1200($filasHoja);

            if ($puntaje > $mejorPuntaje) {
                $mejorPuntaje = $puntaje;
                $mejorFilas = $filasHoja;
            }
        }

        $zip->close();

        return $mejorFilas;
    }

    private function parsearFilasSheetXml($sheetXml, $sharedStrings) {
        $sx = @simplexml_load_string($sheetXml);
        if ($sx === false) {
            return [];
        }

        $filas = [];
        $rows = $sx->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]');
        if ($rows === false) {
            return [];
        }

        foreach ($rows as $row) {
            $linea = [];
            $cells = $row->xpath('./*[local-name()="c"]');
            if ($cells === false) {
                continue;
            }

            foreach ($cells as $cell) {
                $cellRef = (string)$cell['r'];
                $cellType = (string)$cell['t'];

                $colLetters = preg_replace('/\d+/', '', $cellRef);
                $colIndex = $this->excelColToIndex($colLetters);

                $value = '';
                if ($cellType === 's') {
                    $vNode = $cell->xpath('./*[local-name()="v"]');
                    $idx = isset($vNode[0]) ? (int)$vNode[0] : 0;
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($cellType === 'inlineStr') {
                    $textNodes = $cell->xpath('.//*[local-name()="is"]//*[local-name()="t"]');
                    if ($textNodes !== false && !empty($textNodes)) {
                        $inlineText = '';
                        foreach ($textNodes as $tn) {
                            $inlineText .= (string)$tn;
                        }
                        $value = $inlineText;
                    }
                } elseif ($cellType === 'str') {
                    $vNode = $cell->xpath('./*[local-name()="v"]');
                    $value = isset($vNode[0]) ? (string)$vNode[0] : '';
                } else {
                    $vNode = $cell->xpath('./*[local-name()="v"]');
                    $value = isset($vNode[0]) ? (string)$vNode[0] : '';
                }

                $linea[$colIndex] = trim($value);
            }

            if (!empty($linea)) {
                ksort($linea);
                $maxIndex = max(array_keys($linea));
                $rowCompleta = [];
                for ($i = 0; $i <= $maxIndex; $i++) {
                    $rowCompleta[] = $linea[$i] ?? '';
                }
                $filas[] = $rowCompleta;
            }
        }

        return $filas;
    }

    private function puntuarFilasSeremos1200($filas) {
        if (empty($filas)) {
            return 0;
        }

        $indiceHeader = $this->detectarIndiceEncabezadoSeremos1200($filas);
        $puntaje = 0;

        if ($indiceHeader !== null) {
            $puntaje += 50;
            $headers = (array)($filas[$indiceHeader] ?? []);
            $headersNorm = array_map(function ($h) {
                return $this->normalizarEncabezado($h);
            }, $headers);

            $tokens = ['nombre', 'apellido', 'cedula', 'telefono', 'lider', 'votacion', 'mesa'];
            foreach ($headersNorm as $hn) {
                foreach ($tokens as $token) {
                    if ($hn !== '' && strpos($hn, $token) !== false) {
                        $puntaje += 5;
                        break;
                    }
                }
            }
        }

        $filasNoVacias = 0;
        $limite = min(count((array)$filas), 30);
        for ($i = 0; $i < $limite; $i++) {
            $fila = (array)$filas[$i];
            $noVacios = 0;
            foreach ($fila as $valor) {
                if (trim((string)$valor) !== '') {
                    $noVacios++;
                }
            }
            if ($noVacios >= 2) {
                $filasNoVacias++;
            }
        }

        $puntaje += $filasNoVacias;
        return $puntaje;
    }

    private function excelColToIndex($letters) {
        $letters = strtoupper((string)$letters);
        $len = strlen($letters);
        $index = 0;

        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }

        return max($index - 1, 0);
    }

    private function normalizarEncabezado($valor) {
        $valor = trim((string)$valor);
        $valor = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'],
            ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N'],
            $valor
        );
        $valor = strtolower($valor);
        $valor = str_replace(['_', '-', '.', '/', '\\'], ' ', $valor);
        $valor = preg_replace('/[^a-z0-9\s]/', ' ', $valor);
        $valor = preg_replace('/\s+/', ' ', $valor);
        return trim((string)$valor);
    }

    private function normalizarCedula($valor) {
        $valor = strtolower(trim((string)$valor));
        $valor = preg_replace('/[^a-z0-9]/', '', $valor);
        return trim((string)$valor);
    }

    private function obtenerValorPorAlias($row, $headersMap, $aliases, $fallbackIndex = null) {
        $headerKeys = array_keys($headersMap);

        foreach ($aliases as $alias) {
            $key = $this->normalizarEncabezado($alias);
            if (isset($headersMap[$key])) {
                $idx = $headersMap[$key];
                return trim((string)($row[$idx] ?? ''));
            }

            $tokens = array_values(array_filter(explode(' ', $key), static function ($token) {
                return strlen((string)$token) >= 3;
            }));

            if (!empty($tokens)) {
                foreach ($headerKeys as $headerKey) {
                    $matchAll = true;
                    foreach ($tokens as $token) {
                        if (strpos((string)$headerKey, (string)$token) === false) {
                            $matchAll = false;
                            break;
                        }
                    }

                    if ($matchAll) {
                        $idx = $headersMap[$headerKey];
                        return trim((string)($row[$idx] ?? ''));
                    }
                }
            }
        }

        if ($fallbackIndex !== null) {
            return trim((string)($row[$fallbackIndex] ?? ''));
        }

        return '';
    }

    private function mapearFilaSeremos1200($headers, $row) {
        $headersMap = [];
        foreach ((array)$headers as $idx => $header) {
            $headerKey = $this->normalizarEncabezado($header);
            if ($headerKey !== '') {
                $headersMap[$headerKey] = (int)$idx;
            }
        }

        $usarFallbackPosicional = empty($headersMap);

        $nombreCompleto = $this->obtenerValorPorAlias(
            $row,
            $headersMap,
            ['nombres y apellidos', 'apellidos y nombres', 'nombre completo', 'nombre y apellido'],
            $usarFallbackPosicional ? 0 : null
        );

        $nombres = $this->obtenerValorPorAlias(
            $row,
            $headersMap,
            ['nombres', 'nombre', 'primer nombre', 'nombres completos'],
            $usarFallbackPosicional ? 0 : null
        );

        $apellidos = $this->obtenerValorPorAlias(
            $row,
            $headersMap,
            ['apellidos', 'apellido', 'primer apellido', 'apellidos completos'],
            $usarFallbackPosicional ? 1 : null
        );

        if ($nombreCompleto !== '' && ($nombres === '' || $apellidos === '')) {
            $partes = array_values(array_filter(explode(' ', preg_replace('/\s+/', ' ', trim($nombreCompleto)))));
            if (!empty($partes)) {
                if (count($partes) === 1) {
                    if ($nombres === '') {
                        $nombres = $partes[0];
                    }
                } else {
                    $mitad = (int)ceil(count($partes) / 2);
                    $nombresInferidos = trim(implode(' ', array_slice($partes, 0, $mitad)));
                    $apellidosInferidos = trim(implode(' ', array_slice($partes, $mitad)));

                    if ($nombres === '') {
                        $nombres = $nombresInferidos;
                    }
                    if ($apellidos === '') {
                        $apellidos = $apellidosInferidos;
                    }
                }
            }
        }

        return [
            'Nombres' => $nombres,
            'Apellidos' => $apellidos,
            'Numero_Cedula' => $this->obtenerValorPorAlias($row, $headersMap, ['numero de cedula', 'cedula', 'numero cedula', 'numero documento', 'documento', 'identificacion', 'doc'], $usarFallbackPosicional ? 2 : null),
            'Telefono' => $this->obtenerValorPorAlias($row, $headersMap, ['telefono', 'celular', 'movil', 'whatsapp', 'numero telefono'], $usarFallbackPosicional ? 3 : null),
            'Lider_Nehemias' => $this->obtenerValorPorAlias($row, $headersMap, ['lider nehemias', 'lider de nehemias', 'lider nehemia', 'lider_n', 'lider ne'], $usarFallbackPosicional ? 4 : null),
            'Lider' => $this->obtenerValorPorAlias($row, $headersMap, ['lider', 'lider de 12', 'ministerio', 'coordinador', 'pastor'], $usarFallbackPosicional ? 5 : null),
            'Subido_Link' => $this->obtenerValorPorAlias($row, $headersMap, ['subido link de nehemias', 'subido link', 'subido_link', 'subido a link', 'link subido', 'evidencia link'], $usarFallbackPosicional ? 6 : null),
            'En_Bogota_Subio' => $this->obtenerValorPorAlias($row, $headersMap, ['en bogota se le subio', 'en bogota', 'en bogota subio', 'bogota subio', 'cargado en bogota'], $usarFallbackPosicional ? 7 : null),
            'Puesto_Votacion' => $this->obtenerValorPorAlias($row, $headersMap, ['punto de votacion', 'puesto de votacion', 'puesto votacion', 'puesto_votacion', 'puesto', 'lugar votacion'], $usarFallbackPosicional ? 8 : null),
            'Mesa_Votacion' => $this->obtenerValorPorAlias($row, $headersMap, ['mesa de votacion', 'mesa votacion', 'mesa_votacion', 'mesa'], $usarFallbackPosicional ? 9 : null),
            'Decision_Acepta' => null,
            'Fue_Migrado_Nehemias' => 0,
            'Nehemias_Id' => null,
            'Fecha_Decision' => null,
            'Fecha_Registro' => date('Y-m-d H:i:s')
        ];
    }

    private function detectarIndiceEncabezadoSeremos1200($filas) {
        $limite = min(120, count((array)$filas));
        $mejorIndice = null;
        $mejorPuntaje = 0;

        $tokensEsperados = [
            'nombre',
            'apellido',
            'cedula',
            'documento',
            'telefono',
            'celular',
            'lider',
            'ministerio',
            'votacion',
            'puesto',
            'punto',
            'mesa'
        ];

        for ($i = 0; $i < $limite; $i++) {
            $fila = (array)($filas[$i] ?? []);
            $puntaje = 0;
            $textoCeldas = 0;

            foreach ($fila as $celda) {
                $valor = $this->normalizarEncabezado($celda);
                if ($valor === '') {
                    continue;
                }

                if (!preg_match('/^[0-9]+$/', (string)$valor)) {
                    $textoCeldas++;
                }

                foreach ($tokensEsperados as $token) {
                    if (strpos($valor, $token) !== false) {
                        $puntaje++;
                        break;
                    }
                }
            }

            if ($textoCeldas < 2) {
                continue;
            }

            if ($puntaje > $mejorPuntaje) {
                $mejorPuntaje = $puntaje;
                $mejorIndice = $i;
            }
        }

        if ($mejorPuntaje >= 2) {
            return $mejorIndice;
        }

        return null;
    }
}
