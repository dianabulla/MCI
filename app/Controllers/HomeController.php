<?php
/**
 * Controlador Home - Dashboard principal
 */

class HomeController extends BaseController {

    private function esGeneroMujer($genero) {
        $genero = strtolower(trim((string)$genero));
        return strpos($genero, 'mujer') !== false;
    }

    private function coincideBusquedaLider(array $lider, $buscar) {
        $buscar = strtolower(trim((string)$buscar));
        if ($buscar === '') {
            return true;
        }

        $nombre = strtolower(trim((string)($lider['Nombre'] ?? '') . ' ' . (string)($lider['Apellido'] ?? '')));
        $ministerio = strtolower(trim((string)($lider['Nombre_Ministerio'] ?? '')));

        return strpos($nombre, $buscar) !== false || strpos($ministerio, $buscar) !== false;
    }
    
    public function index() {
        require_once APP . '/Models/Persona.php';
        require_once APP . '/Models/Celula.php';
        require_once APP . '/Models/Ministerio.php';
        require_once APP . '/Models/Evento.php';
        require_once APP . '/Helpers/DataIsolation.php';
        
        $personaModel = new Persona();
        $celulaModel = new Celula();
        $ministerioModel = new Ministerio();
        $eventoModel = new Evento();

        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        $filtroEventos = DataIsolation::generarFiltroEventos();
        
        $data = [
            'totalPersonas' => count($personaModel->getAllActivosWithRole($filtroPersonas)),
            'totalCelulas' => count($celulaModel->getAllWithMemberCountAndRole($filtroCelulas)),
            'totalMinisterios' => count($ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios)),
            'totalLideresCelula' => $personaModel->getTotalLideresCelulaWithRole($filtroPersonas),
            'eventosProximos' => $eventoModel->getUpcomingWithRole($filtroEventos)
        ];
        
        $this->view('home/dashboard', $data);
    }

    public function lideresCelula() {
        require_once APP . '/Models/Persona.php';
        require_once APP . '/Helpers/DataIsolation.php';

        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('personas', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $personaModel = new Persona();
        $filtroPersonas = DataIsolation::generarFiltroPersonas();

        $lideres = $personaModel->getResumenLideresCelulaWithRole($filtroPersonas);

        $filtroGenero = strtolower(trim((string)($_GET['genero'] ?? 'todos')));
        if (!in_array($filtroGenero, ['todos', 'hombres', 'mujeres'], true)) {
            $filtroGenero = 'todos';
        }

        $filtroTipoLiderazgo = strtolower(trim((string)($_GET['tipo_liderazgo'] ?? 'todos')));
        if (!in_array($filtroTipoLiderazgo, ['todos', 'celula', 'doce', 'ambos'], true)) {
            $filtroTipoLiderazgo = 'todos';
        }

        $filtroBuscar = trim((string)($_GET['buscar'] ?? ''));
        $filtroMinisterio = trim((string)($_GET['ministerio'] ?? ''));

        $ministeriosDisponibles = [];
        foreach ($lideres as $lider) {
            $idMinisterio = (int)($lider['Id_Ministerio'] ?? 0);
            $nombreMinisterio = trim((string)($lider['Nombre_Ministerio'] ?? ''));
            if ($idMinisterio > 0 && $nombreMinisterio !== '') {
                $ministeriosDisponibles[$idMinisterio] = [
                    'id' => $idMinisterio,
                    'nombre' => $nombreMinisterio
                ];
            }
        }
        ksort($ministeriosDisponibles);

        $lideresFiltrados = array_values(array_filter($lideres, function($lider) use ($filtroGenero, $filtroTipoLiderazgo, $filtroBuscar, $filtroMinisterio) {
            $esMujer = $this->esGeneroMujer($lider['Genero'] ?? '');
            $esLiderCelula = (int)($lider['Es_Lider_Celula'] ?? 0) === 1;
            $esLider12 = (int)($lider['Es_Lider_12'] ?? 0) === 1;

            if ($filtroGenero === 'mujeres' && !$esMujer) {
                return false;
            }
            if ($filtroGenero === 'hombres' && $esMujer) {
                return false;
            }

            if ($filtroTipoLiderazgo === 'celula' && !$esLiderCelula) {
                return false;
            }
            if ($filtroTipoLiderazgo === 'doce' && !$esLider12) {
                return false;
            }
            if ($filtroTipoLiderazgo === 'ambos' && !($esLiderCelula && $esLider12)) {
                return false;
            }

            if ($filtroMinisterio !== '') {
                if ((int)($lider['Id_Ministerio'] ?? 0) !== (int)$filtroMinisterio) {
                    return false;
                }
            }

            return $this->coincideBusquedaLider($lider, $filtroBuscar);
        }));

        $lideresHombres = [];
        $lideresMujeres = [];
        foreach ($lideresFiltrados as $lider) {
            if ($this->esGeneroMujer($lider['Genero'] ?? '')) {
                $lideresMujeres[] = $lider;
            } else {
                $lideresHombres[] = $lider;
            }
        }

        $this->view('home/lideres_celula', [
            'lideres_hombres' => $lideresHombres,
            'lideres_mujeres' => $lideresMujeres,
            'total_hombres' => count($lideresHombres),
            'total_mujeres' => count($lideresMujeres),
            'total_lideres' => count($lideresFiltrados),
            'ministerios_disponibles' => array_values($ministeriosDisponibles),
            'filtro_genero' => $filtroGenero,
            'filtro_tipo_liderazgo' => $filtroTipoLiderazgo,
            'filtro_buscar' => $filtroBuscar,
            'filtro_ministerio' => $filtroMinisterio
        ]);
    }
}
