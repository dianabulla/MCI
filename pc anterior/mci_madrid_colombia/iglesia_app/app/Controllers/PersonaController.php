<?php
/**
 * Controlador Persona
 */

namespace App\Controllers;

use App\Models\Persona;

class PersonaController extends BaseController {
    private Persona $model;

    public function __construct() {
        parent::__construct();
        $this->model = new Persona();
    }

    /**
     * Lista todas las personas
     */
    public function index() {
        $this->log('Lista de personas');
        
        $page = (int)($this->get('page') ?? 1);
        $data = $this->model->paginate($page, 10);

        $this->assignArray([
            'title' => 'Personas',
            'personas' => $data['items'],
            'pagination' => $data
        ]);

        $this->render('personas/lista');
    }

    /**
     * Formulario para crear persona
     */
    public function create() {
        $this->assign('title', 'Crear Persona');
        $this->render('personas/formulario');
    }

    /**
     * Almacena una nueva persona
     */
    public function store() {
        $errors = $this->validate(['nombre', 'apellido', 'email']);

        if (!empty($errors)) {
            $this->assign('errors', $errors);
            $this->render('personas/formulario');
            return;
        }

        $id = $this->model->create($this->post());
        
        $this->log("Persona creada: ID {$id}");
        $this->notify('Persona creada exitosamente', 'success');
        $this->redirect('personas');
    }

    /**
     * Formulario para editar persona
     */
    public function edit() {
        $id = (int)$this->get('id');
        $persona = $this->model->find($id);

        if (!$persona) {
            $this->notify('Persona no encontrada', 'error');
            $this->redirect('personas');
            return;
        }

        $this->assignArray([
            'title' => 'Editar Persona',
            'persona' => $persona,
            'roles' => $this->model->getRoles($id)
        ]);

        $this->render('personas/formulario');
    }

    /**
     * Actualiza una persona
     */
    public function update() {
        $id = (int)$this->post('id');
        
        $errors = $this->validate(['nombre', 'apellido', 'email']);

        if (!empty($errors)) {
            $this->assign('errors', $errors);
            $this->edit();
            return;
        }

        $this->model->update($id, $this->post());
        
        $this->log("Persona actualizada: ID {$id}");
        $this->notify('Persona actualizada exitosamente', 'success');
        $this->redirect('personas');
    }

    /**
     * Visualiza detalles de una persona
     */
    public function show() {
        $id = (int)$this->get('id');
        $persona = $this->model->find($id);

        if (!$persona) {
            $this->notify('Persona no encontrada', 'error');
            $this->redirect('personas');
            return;
        }

        $this->assignArray([
            'title' => $this->model->getNombreCompleto($persona),
            'persona' => $persona,
            'roles' => $this->model->getRoles($id),
            'ministerios' => $this->model->getMinisterios($id),
            'celulas' => $this->model->getCelulas($id),
            'discipulos' => $this->model->getDiscipulos($id),
            'lider' => $this->model->getLider($id)
        ]);

        $this->render('personas/detalle');
    }

    /**
     * Elimina una persona
     */
    public function delete() {
        $id = (int)$this->get('id');
        
        $this->model->delete($id);
        
        $this->log("Persona eliminada: ID {$id}");
        $this->notify('Persona eliminada exitosamente', 'success');
        $this->redirect('personas');
    }
}
