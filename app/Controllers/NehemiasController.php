<?php
/**
 * Controlador Nehemias (Formulario publico)
 */

require_once APP . '/Models/Nehemias.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Controllers/AuthController.php';

class NehemiasController extends BaseController {
    private $nehemiasModel;
    private $ministerioModel;

    public function __construct() {
        $this->nehemiasModel = new Nehemias();
        $this->ministerioModel = new Ministerio();
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
            $value = trim((string) $value);
            if ($value === '') {
                return '';
            }
            if (function_exists('mb_strtolower')) {
                return mb_strtolower($value, 'UTF-8');
            }
            return strtolower($value);
        };

        $conteos = $this->nehemiasModel->getVotantesPorLider();
        $conteosMap = [];
        foreach ($conteos as $row) {
            $lider = $normalize($row['Lider'] ?? '');
            $liderNehemias = $normalize($row['Lider_Nehemias'] ?? '');
            if ($lider === '' || $liderNehemias === '') {
                continue;
            }
            if (!isset($conteosMap[$lider])) {
                $conteosMap[$lider] = [];
            }
            $conteosMap[$lider][$liderNehemias] = (int) $row['total'];
        }

        $totalesMinisterio = $this->nehemiasModel->getVotantesPorMinisterio();
        $totalesMinisterioMap = [];
        foreach ($totalesMinisterio as $row) {
            $lider = $normalize($row['Lider'] ?? '');
            if ($lider === '') {
                continue;
            }
            $totalesMinisterioMap[$lider] = (int) $row['total'];
        }

        $config = [
            [
                'key' => 'HUGO Y NANCY',
                'label' => 'HUGO Y NANCY',
                'summary_label' => 'HUGO Y NANCY',
                'meta_nehemias' => 40,
                'meta_votantes' => 600,
                'show_summary' => true,
                'leaders' => [
                    'Adriana Rodríguez López',
                    'Aldaris Cardozo',
                    'Andrés Bermúdez',
                    'Angie Parra',
                    'Carmen Dayana Rojas',
                    'Dairon Gutierrez',
                    'Darin Margaret Osorio Rodríguez',
                    'Diana Sierra',
                    'Dora Mercedes Rodriguez Herrera',
                    'Edwin Roa',
                    'Elba Morales',
                    'Esperanza Bautista',
                    'Henry Mendez',
                    'Hugo Correa',
                    'Alberto Gonzales',
                    'Lina Paola ruge Jimenez',
                    'Luz Castillo',
                    'Marco Rodríguez',
                    'María Yolanda Hernández Bohórquez',
                    'Nancy Diez',
                    'Paola Andrea Pulido Prieto',
                    'Placido Ballen',
                    'Wilson Mora',
                    'Yenny Rubio',
                    'Blanca alfonso',
                    'Jennifer Vega',
                    'Maria Ines Jaramillo',
                    'Maria Sanchez'
                ]
            ],
            [
                'key' => 'FABIAN Y ELIZABETH',
                'label' => 'FABIAN Y ELIZABETH',
                'summary_label' => 'FABIAN Y ELIZABETH',
                'meta_nehemias' => null,
                'meta_votantes' => 600,
                'show_summary' => true,
                'leaders' => [
                    'Alejandro alean',
                    'Alexandra escobar',
                    'Andrea Lorena Cajamarca Alonso',
                    'Angela Pulido',
                    'Barbara Castañeda',
                    'Bibiana escobar',
                    'Clotilde Gaitán',
                    'Cristian arnoldo cruz rojas',
                    'Daniel Armando Quintero Rodelo',
                    'Dinael Montealegre lavao',
                    'Elizabeth escobar',
                    'Fabián camargo',
                    'Ferninchi Atencia',
                    'Jenifer Andrea Loaiza Cancelado',
                    'Jhon Jairo González perez',
                    'Julio cesar Romero López',
                    'Luz Deiby Cancelado Galindo',
                    'Maria Amparo Guevara Viscaino',
                    'Mariluz Buitrago Valdés',
                    'Míldred Caballero Quintero',
                    'Norma Patricia Gaitán',
                    'Nancy Tovar',
                    'Omar Yesith Ramirez Pedrozo',
                    'Oxelis Gabriela Pérez Navarro',
                    'Shirley Janine Alegria p.',
                    'Tatiana mora',
                    'Yina Jimena gomez Wilches',
                    'Yinet gonzalez',
                    'Yineth Lorena Riaño Garzon',
                    'Luz Dayris Catalán',
                    'Jorge Armando Leon',
                    'Camilo Cifuentes',
                    'Daniela Quevedo',
                    'Darling Aurora Murillo Mosquera',
                    'Doris Cantor Poveda'
                ]
            ],
            [
                'key' => 'MICHAEL Y SARA',
                'label' => 'MICHAEL Y SARA',
                'summary_label' => 'MICHAEL Y SARA',
                'meta_nehemias' => 41,
                'meta_votantes' => 615,
                'show_summary' => true,
                'leaders' => [
                    'Diana Cediel Gómez',
                    'Flor victoria peñuela castiblanco',
                    'Gloria Galindo Baron',
                    'Greicy Santiago',
                    'LEIDY JOHANA JIMENEZ',
                    'Leidy Yohana Cruz Pérez',
                    'Martha Lucía Rocha Amaya',
                    'Mary Gaitan',
                    'MICHAEL TORRES',
                    'Orlando Caro vega',
                    'Ruth Yohana rincón araque',
                    'Sindi Carolina Mendez',
                    'Yessica Triana Palacio',
                    'Luz Amparo Ortiz',
                    'Maria Adelina Reyes',
                    'Nubia Avelino',
                    'Dolly Carvajal León',
                    'Maria Mercedes Sanchez',
                    'ELIANA JIMENEZ OSPINA',
                    'Ana Yibe Sierra Rodríguez',
                    'Yira Tatiana Peñuela Bermúdez',
                    'Maria stella Guiterrez',
                    'Sara Escobar',
                    'Jaime Mendez',
                    'Luz Helena Pérez'
                ]
            ],
            [
                'key' => 'DISNEY Y ANGELICA',
                'label' => 'DISNEY Y ANGELICA',
                'summary_label' => 'DISNEY Y ANGELICA',
                'meta_nehemias' => 25,
                'meta_votantes' => 375,
                'show_summary' => true,
                'leaders' => [
                    'Disney Dias Mosquera',
                    'Elmer Gabriel Cruz Quintin',
                    'Emerita ordoñez',
                    'Jose Miguel Rebolledo',
                    'Karen juliana alfonso',
                    'Luz Adriana Gualteros Montaño',
                    'Maria Angelica Gualteros',
                    'Maria Johana Giron',
                    'Angela Omita herrera Sánchez',
                    'Dora Emilse Silva Soto',
                    'Lun Angela herrera Castro',
                    'Neyl Romero Vela',
                    'Rosa Bohorquez Moreno',
                    'Diana Carolina Madariaga',
                    'Sin lider nehemias'
                ]
            ],
            [
                'key' => 'FABIAN Y ANDREA',
                'label' => 'FABIAN Y ANDREA',
                'summary_label' => 'FABIAN Y ANDREA',
                'meta_nehemias' => 15,
                'meta_votantes' => 225,
                'show_summary' => true,
                'leaders' => [
                    'Ana Guzman',
                    'Andrea Pérez Galvez',
                    'ANDREA RANGEL BOHORQUEZ',
                    'FABIAN VILLA SOGAMOSO',
                    'YELI XIOMARA SALDAÑA'
                ]
            ],
            [
                'key' => 'MADELINE Y ALEJANDRO',
                'label' => 'MADELINE Y ALEJANDRO',
                'summary_label' => 'ALEJANDRO y MADELINE',
                'meta_nehemias' => 20,
                'meta_votantes' => 300,
                'show_summary' => true,
                'leaders' => [
                    'Paola torres',
                    'Camilo Salinas',
                    'Damaris Gomez',
                    'Eleane Nava',
                    'Ines Amaya',
                    'Jesica Marin',
                    'Levitd Sierra',
                    'Libardo Abril',
                    'Marcela Díaz',
                    'Marlen Herrera',
                    'Ricardo meza',
                    'Sergio Correa',
                    'Grace Teresa Caro Ospino',
                    'Madeline Sanchez',
                    'Alejandro Arismendi',
                    'Sofía Zapata'
                ]
            ],
            [
                'key' => 'JEFERSON Y MONICA',
                'label' => 'JEFERSON Y MONICA',
                'summary_label' => 'JEFERSON Y MONICA',
                'meta_nehemias' => 20,
                'meta_votantes' => 300,
                'show_summary' => true,
                'leaders' => [
                    'Alfredo Goyeneche',
                    'Edgar Acevedo',
                    'Ingrid Dayana López Sánchez',
                    'Jaydy Yolima Moreno Moreno',
                    'Jeferson Manuel Casso Ortiz',
                    'Jorge Andrés Cabrejo Callejas',
                    'Juan Carlos Ceron',
                    'Lady Liced Camacho Barrera',
                    'Leidy Andrea Bolivar Ayala',
                    'Liney Silva',
                    'Liseth Nathalia Vasquez Romero',
                    'Martha Lucia Segura',
                    'Mónica García Minorta',
                    'Yeimy Johana Silva Cañizales',
                    'YINET MOLINA',
                    'Andrea Bolivar',
                    'Yulieth Marcela Duarte Bulla'
                ]
            ],
            [
                'key' => 'FERNANDO Y LEIDY',
                'label' => 'FERNANDO Y LEIDY',
                'summary_label' => 'FERNANDO Y LEIDY',
                'meta_nehemias' => 10,
                'meta_votantes' => 150,
                'show_summary' => true,
                'leaders' => [
                    'Beatriz Padilla',
                    'Janine Acacio',
                    'Yamile Guayara',
                    'Fernando Castro Sierra',
                    'leidy Johanna Ortiz Pardo'
                ]
            ],
            [
                'key' => 'JULIA BARON',
                'label' => 'JULIA BARON',
                'summary_label' => 'JULIA BARON',
                'meta_nehemias' => 25,
                'meta_votantes' => 375,
                'show_summary' => true,
                'leaders' => [
                    'Angie Katherine Sarmiento Triana',
                    'Aura palacios',
                    'Claudia Lucia Contreras Ovalle',
                    'Diana Yamile Suárez tapia',
                    'Doris Marlen Montoya',
                    'Heidy Yohana Wilches Junca',
                    'Lady Marcela Rodríguez',
                    'Luz Alba varón santos',
                    'Luz stella Cardona Aguilar',
                    'Manuel Andres Wilches',
                    'María Alicia Gómez santos',
                    'María del Carmen moran Vásquez',
                    'Martha Lucia Cifuentes',
                    'Rosa Gladys Triana Gomez',
                    'Stella junca',
                    'Julia Baron'
                ]
            ],
            [
                'key' => 'MARCO Y CAMILA',
                'label' => 'MARCO Y CAMILA',
                'summary_label' => 'MARCO Y CAMILA',
                'meta_nehemias' => 0,
                'meta_votantes' => 0,
                'show_summary' => false,
                'leaders' => [
                    'Andrea Vanesa Acosta',
                    'Camila Prieto Cardona',
                    'Daniel giovanny Rojas Acosta',
                    'Laura romero',
                    'Marco Andres Carreño Ovalle',
                    'Michael grillo'
                ]
            ]
        ];

        $summaryRows = [];
        $sections = [];
        $summaryTotals = [
            'meta_nehemias' => 0,
            'actual_nehemias' => 0,
            'faltantes_nehemias' => 0,
            'meta_votantes' => 0,
            'actual_votantes' => 0,
            'faltantes_votantes' => 0
        ];

        foreach ($config as $item) {
            $ministerioKey = $normalize($item['key']);
            $totalVotantesPorLider = 0;
            $totalNehemias = 0;
            $rows = [];
            $nro = 1;

            foreach ($item['leaders'] as $leaderName) {
                $leaderKey = $normalize($leaderName);
                $count = $conteosMap[$ministerioKey][$leaderKey] ?? 0;
                if ($count > 0) {
                    $totalNehemias++;
                }
                $totalVotantesPorLider += $count;
                $rows[] = [
                    'nro' => $nro,
                    'lider' => $leaderName,
                    'votantes' => $count
                ];
                $nro++;
            }

            $totalVotantesReal = $totalesMinisterioMap[$ministerioKey] ?? $totalVotantesPorLider;
            if ($totalVotantesReal > $totalVotantesPorLider) {
                $rows[] = [
                    'nro' => $nro,
                    'lider' => 'Otros',
                    'votantes' => $totalVotantesReal - $totalVotantesPorLider
                ];
                $nro++;
            }

            $metaNehemias = $item['meta_nehemias'];
            $metaVotantes = $item['meta_votantes'];

            $mostrarNehemias = $metaNehemias !== null && (int) $metaNehemias > 0;
            $metaNehemiasValue = $mostrarNehemias ? (int) $metaNehemias : null;
            $metaVotantesValue = (int) $metaVotantes;

            $faltantesNehemias = $mostrarNehemias ? max($metaNehemiasValue - $totalNehemias, 0) : null;
            $faltantesVotantes = $metaVotantesValue > 0 ? max($metaVotantesValue - $totalVotantesReal, 0) : null;
            $porcentajeNehemias = $mostrarNehemias ? round(($totalNehemias / $metaNehemiasValue) * 100) : null;
            $porcentajeVotantes = $metaVotantesValue > 0 ? round(($totalVotantesReal / $metaVotantesValue) * 100) : null;

            $sections[] = [
                'label' => $item['label'],
                'rows' => $rows,
                'total_votantes' => $totalVotantesReal
            ];

            if (!empty($item['show_summary'])) {
                $summaryRows[] = [
                    'label' => $item['summary_label'] ?? $item['label'],
                    'meta_nehemias' => $metaNehemiasValue,
                    'actual_nehemias' => $mostrarNehemias ? $totalNehemias : null,
                    'faltantes_nehemias' => $faltantesNehemias,
                    'porcentaje_nehemias' => $porcentajeNehemias,
                    'meta_votantes' => $metaVotantesValue,
                    'actual_votantes' => $totalVotantesReal,
                    'faltantes_votantes' => $faltantesVotantes,
                    'porcentaje_votantes' => $porcentajeVotantes
                ];

                if ($mostrarNehemias) {
                    $summaryTotals['meta_nehemias'] += $metaNehemiasValue;
                    $summaryTotals['actual_nehemias'] += $totalNehemias;
                    $summaryTotals['faltantes_nehemias'] += $faltantesNehemias ?? 0;
                }
                $summaryTotals['meta_votantes'] += $metaVotantesValue;
                $summaryTotals['actual_votantes'] += $totalVotantesReal;
                $summaryTotals['faltantes_votantes'] += $faltantesVotantes ?? 0;
            }
        }

        $summaryTotals['porcentaje_nehemias'] = $summaryTotals['meta_nehemias'] > 0
            ? round(($summaryTotals['actual_nehemias'] / $summaryTotals['meta_nehemias']) * 100)
            : null;
        $summaryTotals['porcentaje_votantes'] = $summaryTotals['meta_votantes'] > 0
            ? round(($summaryTotals['actual_votantes'] / $summaryTotals['meta_votantes']) * 100)
            : null;

        $this->view('nehemias/reportes', [
            'summaryRows' => $summaryRows,
            'summaryTotals' => $summaryTotals,
            'sections' => $sections
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
}
