<?php
/**
 * Controlador Celula
 */

require_once APP . '/Models/Celula.php';
require_once APP . '/Models/Persona.php';

class CelulaController extends BaseController {
    private $celulaModel;
    private $personaModel;

    public function __construct() {
        $this->celulaModel = new Celula();
        $this->personaModel = new Persona();
    }

    public function index() {
        $celulas = $this->celulaModel->getAllWithMemberCount();
        $this->view('celulas/lista', ['celulas' => $celulas]);
    }

    public function crear() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre_Celula' => $_POST['nombre_celula'],
                'Direccion_Celula' => $_POST['direccion_celula'],
                'Dia_Reunion' => $_POST['dia_reunion'],
                'Hora_Reunion' => $_POST['hora_reunion'],
                'Id_Lider' => $_POST['id_lider'] ?: null
            ];
            
            $this->celulaModel->create($data);
            $this->redirect('celulas');
        } else {
            $data = [
                'personas' => $this->personaModel->getLideresYPastores()
            ];
            $this->view('celulas/formulario', $data);
        }
    }

    public function editar() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('celulas');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre_Celula' => $_POST['nombre_celula'],
                'Direccion_Celula' => $_POST['direccion_celula'],
                'Dia_Reunion' => $_POST['dia_reunion'],
                'Hora_Reunion' => $_POST['hora_reunion'],
                'Id_Lider' => $_POST['id_lider'] ?: null
            ];
            
            $this->celulaModel->update($id, $data);
            $this->redirect('celulas');
        } else {
            $data = [
                'celula' => $this->celulaModel->getById($id),
                'personas' => $this->personaModel->getLideresYPastores()
            ];
            $this->view('celulas/formulario', $data);
        }
    }

    public function detalle() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('celulas');
        }

        $celula = $this->celulaModel->getWithMembers($id);
        $this->view('celulas/detalle', ['celula' => $celula]);
    }

    public function eliminar() {
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $this->celulaModel->delete($id);
        }
        
        $this->redirect('celulas');
    }
}
