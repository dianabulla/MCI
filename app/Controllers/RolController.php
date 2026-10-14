<?php
/**
 * Controlador Rol
 */

require_once APP . '/Models/Rol.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Helpers/PermisosCatalogo.php';
require_once APP . '/Helpers/PermisosRolService.php';

class RolController extends BaseController {
    private $rolModel;

    public function __construct() {
        $this->rolModel = new Rol();
    }

    public function index() {
        if (!AuthController::puede('roles:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $roles = $this->rolModel->getAllWithPersonCount();
        $this->view('roles/lista', ['roles' => $roles]);
    }

    public function exportarExcel() {
        if (!AuthController::puede('roles:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $roles = $this->rolModel->getAllWithPersonCount();

        $rows = [];
        foreach ($roles as $rol) {
            $rows[] = [
                (string)($rol['Nombre_Rol'] ?? ''),
                (string)($rol['Descripcion'] ?? ''),
                (string)($rol['Total_Personas'] ?? 0)
            ];
        }

        $this->exportCsv(
            'roles_' . date('Ymd_His'),
            ['Rol', 'Descripcion', 'Total Personas'],
            $rows
        );
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::puede('roles:crear')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim((string)($_POST['nombre_rol'] ?? ''));
            $descripcion = trim((string)($_POST['descripcion'] ?? ''));

            $error = $this->validarNombreRol($nombre, 0);
            if ($error !== null) {
                $this->view('roles/formulario', [
                    'rol' => ['Nombre_Rol' => $nombre, 'Descripcion' => $descripcion],
                    'modo_nuevo' => true,
                    'error' => $error,
                ]);
                return;
            }

            $idNuevoRol = (int)$this->rolModel->create([
                'Nombre_Rol' => $nombre,
                'Descripcion' => $descripcion,
            ]);
            if ($idNuevoRol > 0) {
                PermisosRolService::inicializarMatrizDenegada($idNuevoRol);
            }
            $this->redirect('roles');
        } else {
            $this->view('roles/formulario');
        }
    }

    public function editar() {
        // Verificar permiso de editar
        if (!AuthController::puede('roles:editar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);

        if ($id <= 0) {
            $this->redirect('roles');
        }

        $rolActual = $this->rolModel->getById($id);
        if (!$rolActual) {
            $this->redirect('roles');
        }

        $motivo = $this->motivoRolIntocable($rolActual);
        if ($motivo !== null) {
            $this->flashError($motivo);
            $this->redirect('roles');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = trim((string)($_POST['nombre_rol'] ?? ''));
            $descripcion = trim((string)($_POST['descripcion'] ?? ''));

            $error = $this->validarNombreRol($nombre, $id);
            if ($error !== null) {
                $rolActual['Nombre_Rol'] = $nombre;
                $rolActual['Descripcion'] = $descripcion;
                $this->view('roles/formulario', ['rol' => $rolActual, 'error' => $error]);
                return;
            }

            $this->rolModel->update($id, [
                'Nombre_Rol' => $nombre,
                'Descripcion' => $descripcion,
            ]);
            $this->redirect('roles');
        } else {
            $this->view('roles/formulario', ['rol' => $rolActual]);
        }
    }

    public function eliminar() {
        // Verificar permiso de eliminar
        if (!AuthController::puede('roles:eliminar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);

        if ($id > 0) {
            $rol = $this->rolModel->getById($id);
            if ($rol) {
                $motivo = $this->motivoRolIntocable($rol);
                if ($motivo === null && $id === (int)($_SESSION['usuario_rol'] ?? 0)) {
                    $motivo = 'No puedes eliminar el rol con el que estás trabajando.';
                }
                if ($motivo === null && $this->contarUsosRol($id) > 0) {
                    $motivo = 'No se puede eliminar un rol que tiene personas o cuentas asignadas. Cámbiales el rol primero.';
                }

                if ($motivo !== null) {
                    $this->flashError($motivo);
                } else {
                    $this->rolModel->delete($id);
                }
            }
        }

        $this->redirect('roles');
    }

    /**
     * Rol que no se puede editar ni eliminar desde esta pantalla.
     * El Administrador está protegido siempre; los roles con acceso especial a los datos
     * (pastor, ganar, células, asistente) solo los toca un administrador.
     */
    private function motivoRolIntocable(array $rol): ?string {
        $nombre = (string)($rol['Nombre_Rol'] ?? '');
        $id = (int)($rol['Id_Rol'] ?? 0);

        if (PermisosCatalogo::esRolAdministradorGlobal($id, $nombre)) {
            return 'El rol Administrador está protegido y no se puede editar ni eliminar.';
        }

        if (!AuthController::esAdministrador() && $this->nombreDaAccesoEspecial($nombre)) {
            return 'Este rol da acceso especial a los datos; solo un administrador puede modificarlo.';
        }

        return null;
    }

    /**
     * @return string|null mensaje de error, o null si el nombre es válido
     */
    private function validarNombreRol(string $nombre, int $idExcluir): ?string {
        if ($nombre === '') {
            return 'El nombre del rol es obligatorio.';
        }

        if (mb_strlen($nombre, 'UTF-8') > 60) {
            return 'El nombre del rol no puede superar los 60 caracteres.';
        }

        if (PermisosCatalogo::esNombreRolAdministrador($nombre)) {
            return 'Ese nombre está reservado para el administrador del sistema.';
        }

        if (!AuthController::esAdministrador() && $this->nombreDaAccesoEspecial($nombre)) {
            return 'Ese nombre da acceso especial a los datos (contiene admin, pastor, ganar, asistente o es "Células"). Solo un administrador puede usarlo.';
        }

        $buscado = PermisosCatalogo::nombreRolSinAcentos($nombre);
        foreach ($this->rolModel->getAll() as $existente) {
            if ((int)($existente['Id_Rol'] ?? 0) === $idExcluir) {
                continue;
            }
            if (PermisosCatalogo::nombreRolSinAcentos((string)($existente['Nombre_Rol'] ?? '')) === $buscado) {
                return 'Ya existe un rol con ese nombre.';
            }
        }

        return null;
    }

    /**
     * Nombres que el sistema interpreta como acceso amplio a los datos.
     */
    private function nombreDaAccesoEspecial(string $nombre): bool {
        $n = PermisosCatalogo::nombreRolSinAcentos($nombre);
        if ($n === '') {
            return false;
        }
        if ($n === 'celulas') {
            return true;
        }
        foreach (['admin', 'pastor', 'ganar', 'asistente'] as $palabra) {
            if (strpos($n, $palabra) !== false) {
                return true;
            }
        }
        return false;
    }

    private function contarUsosRol(int $idRol): int {
        global $pdo;
        $total = 0;
        foreach (['persona', 'usuario_acceso'] as $tabla) {
            try {
                $st = $pdo->prepare("SELECT COUNT(*) FROM {$tabla} WHERE Id_Rol = ?");
                $st->execute([$idRol]);
                $total += (int)$st->fetchColumn();
            } catch (Throwable $e) {
                // La tabla puede no existir en instalaciones antiguas.
            }
        }
        return $total;
    }

    private function flashError(string $mensaje): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['roles_flash_error'] = $mensaje;
    }
}
