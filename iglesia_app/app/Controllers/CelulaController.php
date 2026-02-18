<?php
/**
 * Controlador Celula
 */

namespace App\Controllers;

use App\Models\Celula;
use App\Models\Persona;

class CelulaController extends BaseController {
    private Celula $model;
    private Persona $personaModel;

    public function __construct() {
        parent::__construct();
        $this->model = new Celula();
        $this->personaModel = new Persona();
    }

    /**
     * Lista todas las células
     */
    public function index() {
        $this->log('Lista de células');
        
        $page = (int)($this->get('page') ?? 1);
        $data = $this->model->paginate($page, 10);

        $this->assignArray([
            'title' => 'Células',
            'celulas' => $data['items'],
            'pagination' => $data
        ]);

        $this->render('celulas/lista');
    }

    /**
     * Formulario para crear célula
     */
    public function create() {
        $personas = $this->personaModel->all();
        
        $this->assignArray([
            'title' => 'Crear Célula',
            'personas' => $personas
        ]);

        $this->render('celulas/formulario');
    }

    /**
     * Almacena una nueva célula
     */
    public function store() {
        $errors = $this->validate(['nombre_celula', 'dia_semana', 'id_lider_celula']);

        if (!empty($errors)) {
            $this->assign('errors', $errors);
            $this->create();
            return;
        }

        $id = $this->model->create($this->post());
        
        $this->log("Célula creada: ID {$id}");
        $this->notify('Célula creada exitosamente', 'success');
        $this->redirect('celulas');
    }

    /**
     * Formulario para editar célula
     */
    public function edit() {
        $id = (int)$this->get('id');
        $celula = $this->model->find($id);

        if (!$celula) {
            $this->notify('Célula no encontrada', 'error');
            $this->redirect('celulas');
            return;
        }

        $personas = $this->personaModel->all();

        $this->assignArray([
            'title' => 'Editar Célula',
            'celula' => $celula,
            'personas' => $personas
        ]);

        $this->render('celulas/formulario');
    }

    /**
     * Actualiza una célula
     */
    public function update() {
        $id = (int)$this->post('id');
        
        $errors = $this->validate(['nombre_celula', 'dia_semana', 'id_lider_celula']);

        if (!empty($errors)) {
            $this->assign('errors', $errors);
            $this->edit();
            return;
        }

        $this->model->update($id, $this->post());
        
        $this->log("Célula actualizada: ID {$id}");
        $this->notify('Célula actualizada exitosamente', 'success');
        $this->redirect('celulas');
    }

    /**
     * Visualiza detalles de una célula
     */
    public function show() {
        $id = (int)$this->get('id');
        $celula = $this->model->find($id);

        if (!$celula) {
            $this->notify('Célula no encontrada', 'error');
            $this->redirect('celulas');
            return;
        }

        $this->assignArray([
            'title' => $celula['nombre_celula'],
            'celula' => $celula,
            'lider' => $this->model->getLider($id),
            'miembros' => $this->model->getMiembros($id),
            'asistencias' => $this->model->getAsistencias($id, 20),
            'estadisticas' => $this->model->getEstadisticasAsistencia($id)
        ]);

        $this->render('celulas/detalle');
    }

    /**
     * Elimina una célula
     */
    public function delete() {
        $id = (int)$this->get('id');
        
        $this->model->delete($id);
        
        $this->log("Célula eliminada: ID {$id}");
        $this->notify('Célula eliminada exitosamente', 'success');
        $this->redirect('celulas');
    }
}
