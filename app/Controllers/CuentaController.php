<?php
/**
 * Modulo de cuentas de acceso.
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/UsuarioAcceso.php';
require_once APP . '/Models/Rol.php';
require_once APP . '/Controllers/AuthController.php';

class CuentaController extends BaseController {
    private $personaModel;
    private $usuarioAccesoModel;
    private $rolModel;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->usuarioAccesoModel = new UsuarioAcceso();
        $this->rolModel = new Rol();
    }

    public function index() {
        if (!AuthController::esAdministrador()) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $cuentasPersona = $this->personaModel->getPersonasConUsuario();
        $cuentasAcceso = $this->usuarioAccesoModel->getAllWithRelations();
        $cuentasAccesoVinculadas = [];
        $cuentasAdministrativas = [];

        foreach ($cuentasAcceso as $cuenta) {
            if (!empty($cuenta['Id_Persona'])) {
                $cuentasAccesoVinculadas[] = $cuenta;
            } else {
                $cuentasAdministrativas[] = $cuenta;
            }
        }

        $this->view('cuentas/lista', [
            'cuentas_persona' => $cuentasPersona,
            'cuentas_acceso_vinculadas' => $cuentasAccesoVinculadas,
            'cuentas_administrativas' => $cuentasAdministrativas,
            'tabla_usuario_acceso_disponible' => $this->usuarioAccesoModel->existeTabla(),
        ]);
    }

    public function crear() {
        if (!AuthController::esAdministrador()) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $tablaDisponible = $this->usuarioAccesoModel->existeTabla();
        $roles = $this->rolModel->getAll();
        $postData = $_POST;
        $persona = null;
        $tipoCreacion = trim((string)($_GET['tipo'] ?? $_POST['tipo_creacion'] ?? 'ministerial'));
        if (!in_array($tipoCreacion, ['ministerial', 'administrativo'], true)) {
            $tipoCreacion = 'ministerial';
        }
        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = trim((string)($_POST['accion'] ?? 'buscar'));
            $numeroDocumento = trim((string)($_POST['numero_documento'] ?? ''));

            if ($tipoCreacion === 'ministerial' && $numeroDocumento !== '') {
                $persona = $this->personaModel->getByNumeroDocumento($numeroDocumento);
            }

            if ($accion === 'crear') {
                if (!$tablaDisponible) {
                    $error = 'Debes ejecutar primero la migración SQL de usuario_acceso para crear cuentas nuevas.';
                } else {
                    $usuario = trim((string)($_POST['usuario'] ?? ''));
                    $contrasena = trim((string)($_POST['contrasena'] ?? ''));
                    $idRol = (int)($_POST['id_rol'] ?? 0);
                    $estadoCuenta = trim((string)($_POST['estado_cuenta'] ?? 'Activo')) ?: 'Activo';
                    $nombreMostrar = trim((string)($_POST['nombre_mostrar'] ?? ''));

                    if ($usuario === '' || $contrasena === '' || $idRol <= 0) {
                        $error = 'Usuario, contraseña y rol son obligatorios.';
                    } elseif (strlen($contrasena) < 6) {
                        $error = 'La contraseña debe tener mínimo 6 caracteres.';
                    } elseif ($this->personaModel->existeUsuario($usuario) || $this->usuarioAccesoModel->existeUsuario($usuario)) {
                        $error = 'Ese usuario ya existe en el sistema.';
                    } elseif ($tipoCreacion === 'ministerial' && empty($persona)) {
                        $error = 'No se encontró una persona con esa cédula.';
                    } elseif ($tipoCreacion === 'ministerial' && $this->usuarioAccesoModel->getByPersonaId((int)$persona['Id_Persona'])) {
                        $error = 'Esa persona ya tiene una cuenta ministerial en el nuevo modelo.';
                    } elseif ($tipoCreacion === 'administrativo' && $nombreMostrar === '') {
                        $error = 'El nombre para mostrar es obligatorio en los usuarios administrativos.';
                    } else {
                        $data = [
                            'Usuario' => $usuario,
                            'Contrasena' => password_hash($contrasena, PASSWORD_BCRYPT),
                            'Id_Rol' => $idRol,
                            'Estado_Cuenta' => $estadoCuenta,
                        ];

                        if ($tipoCreacion === 'ministerial') {
                            $data['Nombre_Mostrar'] = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));
                            $data['Id_Ministerio'] = !empty($persona['Id_Ministerio']) ? (int)$persona['Id_Ministerio'] : null;
                            $data['Id_Persona'] = (int)$persona['Id_Persona'];
                            $this->personaModel->update((int)$persona['Id_Persona'], ['Id_Rol' => $idRol]);
                            $this->personaModel->ajustarEscaleraPorRol((int)$persona['Id_Persona'], $idRol);
                            $success = 'Cuenta ministerial creada correctamente para ' . trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? '')) . '.';
                        } else {
                            $data['Nombre_Mostrar'] = $nombreMostrar;
                            $data['Id_Ministerio'] = null;
                            $data['Id_Persona'] = null;
                            $success = 'Usuario administrativo creado correctamente.';
                        }

                        $this->usuarioAccesoModel->create($data);
                        $postData = [];
                        $persona = null;
                    }
                }
            }
        }

        $this->view('cuentas/formulario', [
            'persona' => $persona,
            'roles' => $roles,
            'post_data' => $postData,
            'tabla_usuario_acceso_disponible' => $tablaDisponible,
            'error' => $error,
            'success' => $success,
            'modo_edicion' => false,
            'tipo_creacion' => $tipoCreacion,
        ]);
    }

    public function editar() {
        if (!AuthController::esAdministrador()) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $tablaDisponible = $this->usuarioAccesoModel->existeTabla();
        $roles = $this->rolModel->getAll();
        $tipo = trim((string)($_GET['tipo'] ?? $_POST['tipo'] ?? 'persona'));
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $error = null;
        $success = null;
        $postData = $_POST;
        $persona = null;
        $cuentaAcceso = null;

        if ($id <= 0 || !in_array($tipo, ['persona', 'acceso'], true)) {
            $this->redirect('cuentas');
            return;
        }

        if ($tipo === 'persona') {
            $persona = $this->personaModel->getById($id);
            if (!$persona || trim((string)($persona['Usuario'] ?? '')) === '') {
                $this->redirect('cuentas');
                return;
            }
        } else {
            if (!$tablaDisponible) {
                $this->redirect('cuentas');
                return;
            }
            $cuentaAcceso = $this->usuarioAccesoModel->getById($id);
            if (!$cuentaAcceso) {
                $this->redirect('cuentas');
                return;
            }
            if (!empty($cuentaAcceso['Id_Persona'])) {
                $persona = $this->personaModel->getById((int)$cuentaAcceso['Id_Persona']);
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = trim((string)($_POST['usuario'] ?? ''));
            $contrasena = trim((string)($_POST['contrasena'] ?? ''));
            $idRol = (int)($_POST['id_rol'] ?? 0);
            $estadoCuenta = trim((string)($_POST['estado_cuenta'] ?? 'Activo')) ?: 'Activo';

            if ($usuario === '' || $idRol <= 0) {
                $error = 'Usuario y rol son obligatorios.';
            } elseif ($contrasena !== '' && strlen($contrasena) < 6) {
                $error = 'La contraseña debe tener mínimo 6 caracteres.';
            } elseif (
                $this->personaModel->existeUsuario($usuario, $tipo === 'persona' ? $id : null)
                || $this->usuarioAccesoModel->existeUsuario($usuario, $tipo === 'acceso' ? $id : null)
            ) {
                $error = 'Ese usuario ya existe en el sistema.';
            } else {
                if ($tipo === 'persona') {
                    $data = [
                        'Usuario' => $usuario,
                        'Id_Rol' => $idRol,
                        'Estado_Cuenta' => $estadoCuenta,
                    ];
                    if ($contrasena !== '') {
                        $data['Contrasena'] = password_hash($contrasena, PASSWORD_BCRYPT);
                    }
                    $this->personaModel->update($id, $data);
                    $this->personaModel->ajustarEscaleraPorRol((int)$id, $idRol);
                    $persona = $this->personaModel->getById($id);
                } else {
                    $data = [
                        'Usuario' => $usuario,
                        'Id_Rol' => $idRol,
                        'Estado_Cuenta' => $estadoCuenta,
                    ];
                    if ($contrasena !== '') {
                        $data['Contrasena'] = password_hash($contrasena, PASSWORD_BCRYPT);
                    }
                    $this->usuarioAccesoModel->update($id, $data);
                    $cuentaAcceso = $this->usuarioAccesoModel->getById($id);
                    if (!empty($cuentaAcceso['Id_Persona'])) {
                        $idPersonaVinculada = (int)$cuentaAcceso['Id_Persona'];
                        $this->personaModel->update($idPersonaVinculada, ['Id_Rol' => $idRol]);
                        $this->personaModel->ajustarEscaleraPorRol($idPersonaVinculada, $idRol);
                        $persona = $this->personaModel->getById($idPersonaVinculada);
                    }
                }

                $success = 'Cuenta actualizada correctamente.';
                $postData = [];
            }
        }

        $this->view('cuentas/formulario', [
            'persona' => $persona,
            'cuenta_acceso' => $cuentaAcceso,
            'roles' => $roles,
            'post_data' => $postData,
            'tabla_usuario_acceso_disponible' => $tablaDisponible,
            'error' => $error,
            'success' => $success,
            'modo_edicion' => true,
            'tipo_cuenta' => $tipo,
            'cuenta_id' => $id,
        ]);
    }
}
