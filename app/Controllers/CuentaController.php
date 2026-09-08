<?php
/**
 * Modulo de cuentas de acceso.
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/UsuarioAcceso.php';
require_once APP . '/Models/Rol.php';
require_once APP . '/Models/UserRole.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Helpers/GestionSistemaAccess.php';
require_once APP . '/Helpers/PermisoGuard.php';
require_once APP . '/Helpers/PermisosCatalogo.php';

class CuentaController extends BaseController {
    private $personaModel;
    private $usuarioAccesoModel;
    private $rolModel;
    private $userRoleModel;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->personaModel->ensureIdRolSecundarioColumnExists();
        $this->usuarioAccesoModel = new UsuarioAcceso();
        $this->rolModel = new Rol();
        $this->userRoleModel = new UserRole();
    }

    public function index() {
        GestionSistemaAccess::denegarSiNoPuedeVerCuentas();

        $cuentasPersona = $this->personaModel->getPersonasConUsuario();
        $cuentasAcceso = $this->usuarioAccesoModel->getAllWithRelations();
        $cuentasAccesoVinculadas = [];
        $cuentasAdministrativas = [];
        $idsPersonaConAcceso = [];

        foreach ($cuentasAcceso as $cuenta) {
            $idPersonaAcceso = (int)($cuenta['Id_Persona'] ?? 0);
            if ($idPersonaAcceso > 0) {
                $cuentasAccesoVinculadas[] = $cuenta;
                $idsPersonaConAcceso[$idPersonaAcceso] = true;
            } else {
                $cuentasAdministrativas[] = $cuenta;
            }
        }

        $cuentasPersona = array_values(array_filter($cuentasPersona, static function ($cuentaPersona) use ($idsPersonaConAcceso) {
            $idPersona = (int)($cuentaPersona['Id_Persona'] ?? 0);
            return $idPersona <= 0 || empty($idsPersonaConAcceso[$idPersona]);
        }));

        $pendientesMigracion = $this->usuarioAccesoModel->listarCuentasLegacyPendientes();
        $legacyAunEnPersona = $this->usuarioAccesoModel->contarLoginLegacyAunEnPersona();

        $idsPersona = [];
        foreach ($cuentasPersona as $cuentaPersona) {
            $idPersona = (int)($cuentaPersona['Id_Persona'] ?? 0);
            if ($idPersona > 0) {
                $idsPersona[] = $idPersona;
            }
        }
        foreach ($cuentasAccesoVinculadas as $cuentaAccesoVinculada) {
            $idPersona = (int)($cuentaAccesoVinculada['Id_Persona'] ?? 0);
            if ($idPersona > 0) {
                $idsPersona[] = $idPersona;
            }
        }

        $rolesPorPersona = $this->userRoleModel->listarRolesPorPersonas($idsPersona);
        $idRolMaestro = $this->userRoleModel->buscarRolPorAlias('maestro');

        $this->view('cuentas/lista', [
            'cuentas_persona' => $cuentasPersona,
            'cuentas_acceso_vinculadas' => $cuentasAccesoVinculadas,
            'cuentas_administrativas' => $cuentasAdministrativas,
            'tabla_usuario_acceso_disponible' => $this->usuarioAccesoModel->existeTabla(),
            'roles_por_persona' => $rolesPorPersona,
            'id_rol_maestro' => $idRolMaestro,
            'pendientes_migracion' => $pendientesMigracion,
            'legacy_aun_en_persona' => $legacyAunEnPersona,
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'tipo' => (string)($_GET['tipo'] ?? ''),
        ]);
    }

    public function asignarSegundoRol() {
        PermisoGuard::exigir('cuentas:editar');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('cuentas');
            return;
        }

        $idPersona = (int)($_POST['id_persona'] ?? 0);
        $accion = trim((string)($_POST['accion_segundo_rol'] ?? ''));
        if ($idPersona <= 0 || !in_array($accion, ['asignar_maestro', 'quitar_maestro'], true)) {
            $this->redirect('cuentas&tipo=error&mensaje=' . urlencode('Solicitud inválida para segundo rol.'));
            return;
        }

        $persona = $this->personaModel->getById($idPersona);
        if (empty($persona)) {
            $this->redirect('cuentas&tipo=error&mensaje=' . urlencode('No se encontró la persona seleccionada.'));
            return;
        }

        $idRolPrincipal = (int)($persona['Id_Rol'] ?? 0);
        if ($idRolPrincipal > 0) {
            $this->userRoleModel->sincronizarRolPrincipal($idPersona, $idRolPrincipal);
        }

        $idRolMaestro = $this->userRoleModel->buscarRolPorAlias('maestro');
        if ($idRolMaestro <= 0) {
            $this->redirect('cuentas&tipo=error&mensaje=' . urlencode('No se encontró el rol Maestro en la tabla de roles.'));
            return;
        }

        if ($accion === 'asignar_maestro') {
            $this->userRoleModel->asignarMaestroSecundario($idPersona, $idRolMaestro);
            if ($this->personaModel->tieneColumna('Id_Rol_Secundario')) {
                $this->personaModel->update($idPersona, ['Id_Rol_Secundario' => $idRolMaestro]);
            }
            $this->redirect('cuentas&tipo=success&mensaje=' . urlencode('Segundo rol Maestro asignado correctamente.'));
            return;
        }

        if ($idRolPrincipal === $idRolMaestro) {
            $this->redirect('cuentas&tipo=error&mensaje=' . urlencode('No se puede quitar Maestro porque es el rol principal de esta cuenta.'));
            return;
        }

        $this->userRoleModel->quitarRol($idPersona, $idRolMaestro);
        if ($this->personaModel->tieneColumna('Id_Rol_Secundario')) {
            $this->personaModel->update($idPersona, ['Id_Rol_Secundario' => null]);
        }
        $this->redirect('cuentas&tipo=success&mensaje=' . urlencode('Segundo rol Maestro removido correctamente.'));
    }

    public function migrarLegacy() {
        PermisoGuard::exigir('cuentas:editar');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('cuentas');
            return;
        }

        $accion = trim((string)($_POST['accion_migracion'] ?? 'copiar'));
        require_once APP . '/Helpers/Auditoria.php';

        if ($accion === 'desactivar') {
            $resultado = $this->usuarioAccesoModel->desactivarLoginLegacyYaMigrado();
            $limpiadas = (int)($resultado['limpiadas'] ?? 0);
            Auditoria::ok('cuentas', 'desactivar_login_legacy', [
                'entidad' => 'usuario_acceso',
                'mensaje' => 'Se quitó usuario/clave de persona en ' . $limpiadas . ' ficha(s) ya migradas.',
            ]);
            $this->redirect('cuentas&tipo=success&mensaje=' . urlencode(
                'Login viejo desactivado en ' . $limpiadas . ' persona(s). Siguen entrando por usuario_acceso con la misma clave.'
            ));
            return;
        }

        $resultado = $this->usuarioAccesoModel->migrarCuentasLegacyDesdePersona();
        $copiadas = (int)($resultado['copiadas'] ?? 0);
        $yaOk = (int)($resultado['ya_ok'] ?? 0);
        $omitidas = (array)($resultado['omitidas'] ?? []);
        $mensaje = 'Migración: ' . $copiadas . ' copiada(s), ' . $yaOk . ' ya estaban.';
        if (!empty($omitidas)) {
            $mensaje .= ' Omitidas: ' . implode(' | ', array_slice($omitidas, 0, 8));
            if (count($omitidas) > 8) {
                $mensaje .= ' (+' . (count($omitidas) - 8) . ' más)';
            }
        }

        Auditoria::ok('cuentas', 'migrar_legacy', [
            'entidad' => 'usuario_acceso',
            'mensaje' => $mensaje,
        ]);

        $tipo = empty($omitidas) || $copiadas > 0 ? 'success' : 'error';
        if ($copiadas === 0 && $yaOk === 0 && !empty($omitidas)) {
            $tipo = 'error';
        }
        $this->redirect('cuentas&tipo=' . $tipo . '&mensaje=' . urlencode($mensaje));
    }

    public function crear() {
        PermisoGuard::exigir('cuentas:crear');

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
                    } elseif ($this->usuarioAccesoModel->existeUsuario($usuario)) {
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
                            $idPersonaNueva = (int)$persona['Id_Persona'];
                            $this->usuarioAccesoModel->create($data);
                            $this->sincronizarRolMinisterialPersona($idPersonaNueva, $idRol);
                            $success = 'Cuenta ministerial creada correctamente para ' . trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? '')) . '.';
                        } else {
                            $data['Nombre_Mostrar'] = $nombreMostrar;
                            $data['Id_Ministerio'] = null;
                            $data['Id_Persona'] = null;
                            $this->usuarioAccesoModel->create($data);
                            $success = 'Usuario administrativo creado correctamente.';
                        }

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
        PermisoGuard::exigir('cuentas:editar');

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
            $accesoPorPersona = $this->usuarioAccesoModel->getByPersonaId($id);
            if (!empty($accesoPorPersona)) {
                $this->redirect('cuentas/editar&tipo=acceso&id=' . (int)$accesoPorPersona['Id_Usuario_Acceso']);
                return;
            }
            $this->redirect('cuentas');
            return;
        }

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

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = trim((string)($_POST['usuario'] ?? ''));
            $contrasena = trim((string)($_POST['contrasena'] ?? ''));
            $idRol = (int)($_POST['id_rol'] ?? 0);
            $estadoCuenta = trim((string)($_POST['estado_cuenta'] ?? 'Activo')) ?: 'Activo';

            if ($usuario === '' || $idRol <= 0) {
                $error = 'Usuario y rol son obligatorios.';
            } elseif ($contrasena !== '' && strlen($contrasena) < 6) {
                $error = 'La contraseña debe tener mínimo 6 caracteres.';
            } elseif ($this->usuarioAccesoModel->existeUsuario($usuario, $id)) {
                $error = 'Ese usuario ya existe en el sistema.';
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
                    $this->sincronizarRolMinisterialPersona($idPersonaVinculada, $idRol);
                    $persona = $this->personaModel->getById($idPersonaVinculada);
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

    private function sincronizarRolMinisterialPersona(int $idPersona, int $idRol): void {
        if ($idPersona <= 0 || $idRol <= 0) {
            return;
        }

        $filaRol = $this->rolModel->getById($idRol);
        $nombreRol = trim((string)($filaRol['Nombre_Rol'] ?? ''));
        if (PermisosCatalogo::esRolAdministrativoEspecial($idRol, $nombreRol)) {
            return;
        }

        $this->personaModel->update($idPersona, ['Id_Rol' => $idRol]);
        $this->personaModel->ajustarEscaleraPorRol($idPersona, $idRol);
        $this->userRoleModel->sincronizarRolPrincipal($idPersona, $idRol);
    }
}
