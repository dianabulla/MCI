<?php
/**
 * Controlador Petición
 */

namespace App\Controllers;

use App\Models\Peticion;
use App\Models\Persona;

class PeticionController extends BaseController {
    private Peticion $model;
    private Persona $personaModel;

    public function __construct() {
        parent::__construct();
        $this->model = new Peticion();
        $this->personaModel = new Persona();
    }

    /**
     * Lista todas las peticiones
     */
    public function index() {
        $this->log('Lista de peticiones');
        
        $estado = $this->get('estado') ?? 'Pendiente';
        $page = (int)($this->get('page') ?? 1);
        
        $peticiones = $this->model->getPorEstado($estado);
        $estadisticas = $this->model->getEstadisticas();

        $this->assignArray([
            'title' => 'Peticiones',
            'peticiones' => $peticiones,
            'estado' => $estado,
            'estadisticas' => $estadisticas
        ]);

        $this->render('peticiones/lista');
    }

    /**
     * Formulario para crear petición
     */
    public function create() {
        $personas = $this->personaModel->all();
        
        $this->assignArray([
            'title' => 'Nueva Petición',
            'personas' => $personas
        ]);

        $this->render('peticiones/formulario');
    }

    /**
     * Almacena una nueva petición
     */
    public function store() {
        $errors = $this->validate(['descripcion_peticion', 'id_persona_solicitante']);

        if (!empty($errors)) {
            $this->assign('errors', $errors);
            $this->create();
            return;
        }

        $data = $this->post();
        $data['estado'] = 'Pendiente';
        
        $id = $this->model->create($data);
        
        $this->log("Petición creada: ID {$id}");
        $this->notify('Petición registrada exitosamente', 'success');
        $this->redirect('peticiones');
    }

    /**
     * Formulario para editar petición
     */
    public function edit() {
        $id = (int)$this->get('id');
        $peticion = $this->model->find($id);

        if (!$peticion) {
            $this->notify('Petición no encontrada', 'error');
            $this->redirect('peticiones');
            return;
        }

        $personas = $this->personaModel->all();

        $this->assignArray([
            'title' => 'Editar Petición',
            'peticion' => $peticion,
            'personas' => $personas
        ]);

        $this->render('peticiones/formulario');
    }

    /**
     * Actualiza una petición
     */
    public function update() {
        $id = (int)$this->post('id');
        
        $errors = $this->validate(['descripcion_peticion', 'id_persona_solicitante']);

        if (!empty($errors)) {
            $this->assign('errors', $errors);
            $this->edit();
            return;
        }

        $this->model->update($id, $this->post());
        
        $this->log("Petición actualizada: ID {$id}");
        $this->notify('Petición actualizada exitosamente', 'success');
        $this->redirect('peticiones');
    }

    /**
     * Visualiza detalles de una petición
     */
    public function show() {
        $id = (int)$this->get('id');
        $peticion = $this->model->find($id);

        if (!$peticion) {
            $this->notify('Petición no encontrada', 'error');
            $this->redirect('peticiones');
            return;
        }

        $solicitante = $this->personaModel->find($peticion['id_persona_solicitante']);

        $this->assignArray([
            'title' => 'Detalle de Petición',
            'peticion' => $peticion,
            'solicitante' => $solicitante
        ]);

        $this->render('peticiones/detalle');
    }

    /**
     * Elimina una petición
     */
    public function delete() {
        $id = (int)$this->get('id');
        
        $this->model->delete($id);
        
        $this->log("Petición eliminada: ID {$id}");
        $this->notify('Petición eliminada exitosamente', 'success');
        $this->redirect('peticiones');
    }
}
