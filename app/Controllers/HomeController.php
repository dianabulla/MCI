<?php
/**
 * Controlador Home - Dashboard principal
 */

class HomeController extends BaseController {
    
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

        $this->view('home/lideres_celula', [
            'lideres' => $lideres
        ]);
    }
}
