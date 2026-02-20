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
            'eventosProximos' => $eventoModel->getUpcomingWithRole($filtroEventos)
        ];
        
        $this->view('home/dashboard', $data);
    }
}
