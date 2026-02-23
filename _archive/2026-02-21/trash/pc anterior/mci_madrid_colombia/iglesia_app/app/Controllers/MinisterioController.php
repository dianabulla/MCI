<?php
/**
 * Controlador Ministerio
 */

namespace App\Controllers;

use App\Models\Ministerio;
use App\Models\Persona;

class MinisterioController extends BaseController {
    private Ministerio $model;
    private Persona $personaModel;

    public function __construct() {
        parent::__construct();
        $this->model = new Ministerio();
        $this->personaModel = new Persona();
    }

    /**
     * Lista todos los ministerios
     */
    public function index() {
        $this->log('Lista de ministerios');
        
        $page = (int)($this->get('page') ?? 1);
        $data = $this->model->paginate($page, 10);

        $this->assignArray([
            'title' => 'Ministerios',
            'ministerios' => $data['items'],
            'pagination' => $data
        ]);

        $this->render('ministerios/lista');
    }

    /**
     * Formulario para crear ministerio
     */
    public function create() {
        $personas = $this->personaModel->all();
        
        $this->assignArray([
            'title' => 'Crear Ministerio',
            'personas' => $personas
        ]);

        $this->render('ministerios/formulario');
    }

    /**
     * Almacena un nuevo ministerio
     */
    public function store() {
        $errors = $this->validate(['nombre_ministerio', 'id_pastor_lider']);

        if (!empty($errors)) {
            $this->assign('errors', $errors);
            $this->create();
            return;
        }

        $id = $this->model->create($this->post());
        
        $this->log("Ministerio creado: ID {$id}");
        $this->notify('Ministerio creado exitosamente', 'success');
        $this->redirect('ministerios');
    }

    /**
     * Formulario para editar ministerio
     */
    public function edit() {
        $id = (int)$this->get('id');
        $ministerio = $this->model->find($id);

        if (!$ministerio) {
            $this->notify('Ministerio no encontrado', 'error');
            $this->redirect('ministerios');
            return;
        }

        $personas = $this->personaModel->all();

        $this->assignArray([
            'title' => 'Editar Ministerio',
            'ministerio' => $ministerio,
            'personas' => $personas
        ]);

        $this->render('ministerios/formulario');
    }

    /**
     * Actualiza un ministerio
     */
    public function update() {
        $id = (int)$this->post('id');
        
        $errors = $this->validate(['nombre_ministerio', 'id_pastor_lider']);

        if (!empty($errors)) {
            $this->assign('errors', $errors);
            $this->edit();
            return;
        }

        $this->model->update($id, $this->post());
        
        $this->log("Ministerio actualizado: ID {$id}");
        $this->notify('Ministerio actualizado exitosamente', 'success');
        $this->redirect('ministerios');
    }

    /**
     * Visualiza detalles de un ministerio
     */
    public function show() {
        $id = (int)$this->get('id');
        $ministerio = $this->model->find($id);

        if (!$ministerio) {
            $this->notify('Ministerio no encontrado', 'error');
            $this->redirect('ministerios');
            return;
        }

        $this->assignArray([
            'title' => $ministerio['nombre_ministerio'],
            'ministerio' => $ministerio,
            'lider' => $this->model->getLider($id),
            'miembros' => $this->model->getMiembros($id),
            'totalMiembros' => $this->model->getTotalMiembros($id)
        ]);

        $this->render('ministerios/detalle');
    }

    /**
     * Elimina un ministerio
     */
    public function delete() {
        $id = (int)$this->get('id');
        
        $this->model->delete($id);
        
        $this->log("Ministerio eliminado: ID {$id}");
        $this->notify('Ministerio eliminado exitosamente', 'success');
        $this->redirect('ministerios');
    }
}
