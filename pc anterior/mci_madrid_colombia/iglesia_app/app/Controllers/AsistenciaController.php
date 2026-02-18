<?php
/**
 * Controlador Asistencia
 */

namespace App\Controllers;

use App\Models\Asistencia;
use App\Models\Celula;
use App\Models\Persona;

class AsistenciaController extends BaseController {
    private Asistencia $model;
    private Celula $celulaModel;
    private Persona $personaModel;

    public function __construct() {
        parent::__construct();
        $this->model = new Asistencia();
        $this->celulaModel = new Celula();
        $this->personaModel = new Persona();
    }

    /**
     * Lista todas las asistencias
     */
    public function index() {
        $this->log('Lista de asistencias');
        
        $fecha = $this->get('fecha') ?? date('Y-m-d');
        $page = (int)($this->get('page') ?? 1);
        
        // Obtener asistencias de la fecha
        $asistencias = $this->model->getPorFecha($fecha);

        $this->assignArray([
            'title' => 'Asistencias',
            'asistencias' => $asistencias,
            'fecha' => $fecha
        ]);

        $this->render('asistencias/lista');
    }

    /**
     * Formulario para registrar asistencia
     */
    public function create() {
        $celulas = $this->celulaModel->all();
        $personas = $this->personaModel->all();
        
        $this->assignArray([
            'title' => 'Registrar Asistencia',
            'celulas' => $celulas,
            'personas' => $personas
        ]);

        $this->render('asistencias/formulario');
    }

    /**
     * Almacena un registro de asistencia
     */
    public function store() {
        $errors = $this->validate(['id_persona', 'id_celula', 'fecha_asistencia']);

        if (!empty($errors)) {
            $this->assign('errors', $errors);
            $this->create();
            return;
        }

        $asistio = (bool)($this->post('es_asistente') ?? true);

        $this->model->registrar(
            (int)$this->post('id_persona'),
            (int)$this->post('id_celula'),
            $this->post('fecha_asistencia'),
            $asistio
        );
        
        $this->log("Asistencia registrada");
        $this->notify('Asistencia registrada exitosamente', 'success');
        $this->redirect('asistencias?fecha=' . $this->post('fecha_asistencia'));
    }

    /**
     * Formulario para editar asistencia
     */
    public function edit() {
        // Obtener parámetros
        $idPersona = (int)$this->get('id_persona');
        $idCelula = (int)$this->get('id_celula');
        $fecha = $this->get('fecha');

        // Obtener datos
        $persona = $this->personaModel->find($idPersona);
        $celula = $this->celulaModel->find($idCelula);
        $celulas = $this->celulaModel->all();
        $personas = $this->personaModel->all();

        if (!$persona || !$celula) {
            $this->notify('Registro no encontrado', 'error');
            $this->redirect('asistencias');
            return;
        }

        $this->assignArray([
            'title' => 'Editar Asistencia',
            'persona' => $persona,
            'celula' => $celula,
            'fecha' => $fecha,
            'celulas' => $celulas,
            'personas' => $personas
        ]);

        $this->render('asistencias/formulario');
    }

    /**
     * Actualiza una asistencia
     */
    public function update() {
        $idPersona = (int)$this->post('id_persona');
        $idCelula = (int)$this->post('id_celula');
        $fecha = $this->post('fecha_asistencia');
        $asistio = (bool)($this->post('es_asistente') ?? true);

        $this->model->registrar($idPersona, $idCelula, $fecha, $asistio);
        
        $this->log("Asistencia actualizada");
        $this->notify('Asistencia actualizada exitosamente', 'success');
        $this->redirect('asistencias?fecha=' . $fecha);
    }

    /**
     * Elimina un registro de asistencia
     */
    public function delete() {
        $idPersona = (int)$this->get('id_persona');
        $idCelula = (int)$this->get('id_celula');
        $fecha = $this->get('fecha');

        // PDO no tiene delete nativo para clave compuesta en este contexto
        // Se marca como inactivo
        $this->model->update(
            ['activo' => false],
            "id_persona = ? AND id_celula = ? AND fecha_asistencia = ?",
            [$idPersona, $idCelula, $fecha]
        );
        
        $this->log("Asistencia eliminada");
        $this->notify('Asistencia eliminada exitosamente', 'success');
        $this->redirect('asistencias');
    }
}
