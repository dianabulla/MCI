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
        
        $personaModel = new Persona();
        $celulaModel = new Celula();
        $ministerioModel = new Ministerio();
        $eventoModel = new Evento();
        
        $data = [
            'totalPersonas' => count($personaModel->getAllActivos()),
            'totalCelulas' => count($celulaModel->getAll()),
            'totalMinisterios' => count($ministerioModel->getAll()),
            'eventosProximos' => $eventoModel->getUpcoming()
        ];
        
        $this->view('home/dashboard', $data);
    }
}
