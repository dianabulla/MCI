<?php
/**
 * Diagnóstico admin: permisos de edición de personas por rol (producción).
 */
require_once APP . '/Controllers/BaseController.php';
require_once APP . '/Helpers/DiagnosticoPermisosPersona.php';

class DiagnosticoPermisosPersonaController extends BaseController
{
    public function index() {
        if (!AuthController::estaAutenticado() || !AuthController::esAdministrador()) {
            $this->redirect('auth/acceso-denegado');
            return;
        }

        global $pdo;

        $idRol = (int)($_GET['id_rol'] ?? 0);
        $roles = [];
        $permisos = [];
        $evaluacion = null;
        $filasModulos = [];
        $nombreRol = '';
        $error = null;

        try {
            $roles = DiagnosticoPermisosPersona::listarRoles($pdo);
            if ($idRol <= 0 && !empty($roles)) {
                $idRol = (int)($roles[0]['Id_Rol'] ?? 0);
            }
            foreach ($roles as $rol) {
                if ((int)($rol['Id_Rol'] ?? 0) === $idRol) {
                    $nombreRol = (string)($rol['Nombre_Rol'] ?? '');
                    break;
                }
            }
            if ($idRol > 0) {
                $permisos = DiagnosticoPermisosPersona::permisosNormalizadosPorRol($pdo, $idRol);
                $evaluacion = DiagnosticoPermisosPersona::evaluarEditarPersona($permisos);
                $filasModulos = DiagnosticoPermisosPersona::filasModulosRelevantes($permisos);
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $sesionRolId = (int)($_SESSION['usuario_rol'] ?? 0);
        $sesionRolNombre = (string)($_SESSION['usuario_rol_nombre'] ?? '');

        $this->view('herramientas/diagnostico_permisos_persona', [
            'roles' => $roles,
            'id_rol' => $idRol,
            'nombre_rol' => $nombreRol,
            'permisos' => $permisos,
            'evaluacion' => $evaluacion,
            'filas_modulos' => $filasModulos,
            'error' => $error,
            'form_action' => rtrim(PUBLIC_URL, '/') . '/index.php',
            'route_diagnostico' => 'herramientas/diagnostico-permisos-persona',
            'parche_desplegado' => DiagnosticoPermisosPersona::tieneParcheEditarDiscipular(),
            'sesion_rol_id' => $sesionRolId,
            'sesion_rol_nombre' => $sesionRolNombre,
            'host' => $_SERVER['HTTP_HOST'] ?? '',
        ]);
    }
}
