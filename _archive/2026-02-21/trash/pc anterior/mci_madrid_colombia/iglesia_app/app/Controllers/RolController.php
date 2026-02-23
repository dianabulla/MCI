<?php
/**
 * Controlador Rol
 */

namespace App\Controllers;

use App\Models\Rol;

class RolController extends BaseController {
    private Rol $model;

    public function __construct() {
        parent::__construct();
        $this->model = new Rol();
    }

    /**
     * Lista todos los roles
     */
    public function index() {
        $this->log('Lista de roles');
        
        $page = (int)($this->get('page') ?? 1);
        $data = $this->model->paginate($page, 10);

        $this->assignArray([
            'title' => 'Roles',
            'roles' => $data['items'],
            'pagination' => $data
        ]);

        $this->render('roles/lista');
    }

    /**
     * Formulario para crear rol
     */
    public function create() {
        $this->assign('title', 'Crear Rol');
        $this->render('roles/formulario');
    }

    /**
     * Almacena un nuevo rol
     */
    public function store() {
        $errors = $this->validate(['nombre_rol']);

        if (!empty($errors)) {
            $this->assign('errors', $errors);
            $this->create();
            return;
        }

        if ($this->model->existe($this->post('nombre_rol'))) {
            $this->notify('El rol ya existe', 'error');
            $this->create();
            return;
        }

        $id = $this->model->create($this->post());
        
        $this->log("Rol creado: ID {$id}");
        $this->notify('Rol creado exitosamente', 'success');
        $this->redirect('roles');
    }

    /**
     * Formulario para editar rol
     */
    public function edit() {
        $id = (int)$this->get('id');
        $rol = $this->model->find($id);

        if (!$rol) {
            $this->notify('Rol no encontrado', 'error');
            $this->redirect('roles');
            return;
        }

        $this->assignArray([
            'title' => 'Editar Rol',
            'rol' => $rol
        ]);

        $this->render('roles/formulario');
    }

    /**
     * Actualiza un rol
     */
    public function update() {
        $id = (int)$this->post('id');
        
        $errors = $this->validate(['nombre_rol']);

        if (!empty($errors)) {
            $this->assign('errors', $errors);
            $this->edit();
            return;
        }

        $this->model->update($id, $this->post());
        
        $this->log("Rol actualizado: ID {$id}");
        $this->notify('Rol actualizado exitosamente', 'success');
        $this->redirect('roles');
    }

    /**
     * Visualiza detalles de un rol
     */
    public function show() {
        $id = (int)$this->get('id');
        $rol = $this->model->find($id);

        if (!$rol) {
            $this->notify('Rol no encontrado', 'error');
            $this->redirect('roles');
            return;
        }

        $this->assignArray([
            'title' => $rol['nombre_rol'],
            'rol' => $rol,
            'personas' => $this->model->getPersonas($id),
            'totalPersonas' => $this->model->getTotalPersonas($id)
        ]);

        $this->render('roles/detalle');
    }

    /**
     * Elimina un rol
     */
    public function delete() {
        $id = (int)$this->get('id');
        
        $this->model->delete($id);
        
        $this->log("Rol eliminado: ID {$id}");
        $this->notify('Rol eliminado exitosamente', 'success');
        $this->redirect('roles');
    }
}
