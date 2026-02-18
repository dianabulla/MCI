<?php
/**
 * Controlador Evento
 */

namespace App\Controllers;

use App\Models\Evento;
use App\Models\Persona;

class EventoController extends BaseController {
    private Evento $model;
    private Persona $personaModel;

    public function __construct() {
        parent::__construct();
        $this->model = new Evento();
        $this->personaModel = new Persona();
    }

    /**
     * Lista todos los eventos
     */
    public function index() {
        $this->log('Lista de eventos');
        
        $page = (int)($this->get('page') ?? 1);
        $data = $this->model->paginate($page, 10);

        $this->assignArray([
            'title' => 'Eventos',
            'eventos' => $data['items'],
            'pagination' => $data
        ]);

        $this->render('eventos/lista');
    }

    /**
     * Formulario para crear evento
     */
    public function create() {
        $this->assign('title', 'Crear Evento');
        $this->render('eventos/formulario');
    }

    /**
     * Almacena un nuevo evento
     */
    public function store() {
        $errors = $this->validate(['nombre_evento', 'fecha']);

        if (!empty($errors)) {
            $this->assign('errors', $errors);
            $this->create();
            return;
        }

        $id = $this->model->create($this->post());
        
        $this->log("Evento creado: ID {$id}");
        $this->notify('Evento creado exitosamente', 'success');
        $this->redirect('eventos');
    }

    /**
     * Formulario para editar evento
     */
    public function edit() {
        $id = (int)$this->get('id');
        $evento = $this->model->find($id);

        if (!$evento) {
            $this->notify('Evento no encontrado', 'error');
            $this->redirect('eventos');
            return;
        }

        $this->assignArray([
            'title' => 'Editar Evento',
            'evento' => $evento
        ]);

        $this->render('eventos/formulario');
    }

    /**
     * Actualiza un evento
     */
    public function update() {
        $id = (int)$this->post('id');
        
        $errors = $this->validate(['nombre_evento', 'fecha']);

        if (!empty($errors)) {
            $this->assign('errors', $errors);
            $this->edit();
            return;
        }

        $this->model->update($id, $this->post());
        
        $this->log("Evento actualizado: ID {$id}");
        $this->notify('Evento actualizado exitosamente', 'success');
        $this->redirect('eventos');
    }

    /**
     * Visualiza detalles de un evento
     */
    public function show() {
        $id = (int)$this->get('id');
        $evento = $this->model->find($id);

        if (!$evento) {
            $this->notify('Evento no encontrado', 'error');
            $this->redirect('eventos');
            return;
        }

        $asistentes = $this->model->getAsistentes($id);
        $totalAsistentes = $this->model->getTotalAsistentes($id);

        $this->assignArray([
            'title' => $evento['nombre_evento'],
            'evento' => $evento,
            'asistentes' => $asistentes,
            'totalAsistentes' => $totalAsistentes
        ]);

        $this->render('eventos/detalle');
    }

    /**
     * Elimina un evento
     */
    public function delete() {
        $id = (int)$this->get('id');
        
        $this->model->delete($id);
        
        $this->log("Evento eliminado: ID {$id}");
        $this->notify('Evento eliminado exitosamente', 'success');
        $this->redirect('eventos');
    }
}
