<?php
/**
 * Controlador de Permisos
 */

require_once APP . '/Models/Rol.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Config/Database.php';

class PermisosController extends BaseController {
    private $rolModel;
    private $db;

    public function __construct() {
        // Verificar que sea administrador
        if (!AuthController::esAdministrador()) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }
        
        $this->rolModel = new Rol();
        $this->db = $this->obtenerConexionDb();
    }

    private function obtenerConexionDb() {
        global $pdo;
        if (isset($pdo) && $pdo instanceof PDO) {
            return $pdo;
        }

        if (class_exists('Database')) {
            return Database::getInstance()->getConnection();
        }

        if (class_exists('App\\Config\\Database')) {
            return \App\Config\Database::getInstance()->getConnection();
        }

        throw new Exception('No se encontró la clase de base de datos (Database).');
    }

    /**
     * Mostrar gestión de permisos
     */
    public function index() {
        $roles = $this->rolModel->getAll();
        $modulos = $this->getModulos();
        
        // Obtener permisos de todos los roles
        $permisos = [];
        foreach ($roles as $rol) {
            $permisos[$rol['Id_Rol']] = $this->getPermisosPorRol($rol['Id_Rol']);
        }
        
        $data = [
            'pageTitle' => 'Administración de Permisos',
            'roles' => $roles,
            'modulos' => $modulos,
            'permisos' => $permisos
        ];
        
        $this->view('permisos/index', $data);
    }

    public function exportarExcel() {
        $roles = $this->rolModel->getAll();
        $modulos = $this->getModulos();

        $permisosPorRol = [];
        foreach ($roles as $rol) {
            $permisosPorRol[(int)$rol['Id_Rol']] = $this->getPermisosPorRol($rol['Id_Rol']);
        }

        $rows = [];
        foreach ($modulos as $moduloKey => $moduloNombre) {
            foreach ($roles as $rol) {
                $idRol = (int)$rol['Id_Rol'];
                $permiso = $permisosPorRol[$idRol][$moduloKey] ?? null;

                $rows[] = [
                    (string)$moduloNombre,
                    (string)($rol['Nombre_Rol'] ?? ''),
                    !empty($permiso['Puede_Ver']) ? 'Si' : 'No',
                    !empty($permiso['Puede_Crear']) ? 'Si' : 'No',
                    !empty($permiso['Puede_Editar']) ? 'Si' : 'No',
                    !empty($permiso['Puede_Eliminar']) ? 'Si' : 'No'
                ];
            }
        }

        $this->exportCsv(
            'permisos_' . date('Ymd_His'),
            ['Modulo', 'Rol', 'Puede Ver', 'Puede Crear', 'Puede Editar', 'Puede Eliminar'],
            $rows
        );
    }

    /**
     * Actualizar permisos
     */
    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idRol = (int)$_POST['id_rol'];
            $modulo = $_POST['modulo'] ?? '';
            $campo  = $_POST['campo'] ?? '';
            $valor  = (int)$_POST['valor'];

            // Validar campo para evitar inyección SQL (whitelist)
            $campoDb = $this->getCampoDb($campo);
            if ($campoDb === null) {
                echo json_encode(['success' => false, 'error' => 'Campo no válido']);
                return;
            }

            try {
                // Verificar si existe el permiso
                $sql = "SELECT Id_Permiso FROM permisos WHERE Id_Rol = ? AND Modulo = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$idRol, $modulo]);
                $permiso = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($permiso) {
                    // Actualizar
                    $sql = "UPDATE permisos SET $campoDb = ? WHERE Id_Permiso = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$valor, $permiso['Id_Permiso']]);
                } else {
                    // Crear nuevo permiso con todo en 0
                    $sql = "INSERT INTO permisos (Id_Rol, Modulo, Puede_Ver, Puede_Crear, Puede_Editar, Puede_Eliminar) 
                            VALUES (?, ?, 0, 0, 0, 0)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$idRol, $modulo]);

                    // Actualizar el campo específico
                    $sql = "UPDATE permisos SET $campoDb = ? WHERE Id_Rol = ? AND Modulo = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$valor, $idRol, $modulo]);
                }

                // Si se actualiza el rol del usuario actual, reflejar en sesión inmediatamente.
                $rolSesion = (int)($_SESSION['usuario_rol'] ?? 0);
                if ($rolSesion > 0 && $rolSesion === $idRol) {
                    $moduloSesion = trim((string)$modulo);
                    if ($moduloSesion !== '') {
                        if (!isset($_SESSION['permisos'][$moduloSesion]) || !is_array($_SESSION['permisos'][$moduloSesion])) {
                            $_SESSION['permisos'][$moduloSesion] = [
                                'ver' => 0,
                                'crear' => 0,
                                'editar' => 0,
                                'eliminar' => 0,
                            ];
                        }

                        $accionSesion = str_replace('puede_', '', strtolower((string)$campo));
                        if (in_array($accionSesion, ['ver', 'crear', 'editar', 'eliminar'], true)) {
                            $_SESSION['permisos'][$moduloSesion][$accionSesion] = $valor ? 1 : 0;
                        }

                        $_SESSION['permisos_configurados'] = !empty($_SESSION['permisos']);
                        $_SESSION['permisos_last_sync'] = time();
                    }
                }

                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Obtener módulos disponibles
     */
    private function getModulos() {
        $modulosBase = [
            'personas' => 'Personas',
            'personas_formulario_publico' => 'Personas: Ver formulario publico',
            'personas_plantillas_whatsapp' => 'Personas: Ver plantillas WhatsApp',
            'personas_ganar_asignados' => 'Personas: Ver atajo Asignados (Pendiente)',
            'personas_ganar_reasignados' => 'Personas: Ver atajo Reasignados (Pendiente)',
            'celulas' => 'Células',
            'materiales_celulas' => 'Materiales Células (PDF)',
            'ministerios' => 'Ministerios',
            'roles' => 'Roles',
            'eventos' => 'Eventos',
            'peticiones' => 'Peticiones',
            'asistencias' => 'Asistencias',
            'reportes' => 'Reportes',
            'transmisiones' => 'Transmisiones',
            'escuelas_formacion' => 'Escuelas de Formación',
            'escuelas_formacion_marcar_asistencia' => 'Escuelas: Marcar asistencia',
            'escuelas_formacion_editar_fechas' => 'Escuelas: Editar fechas de clases',
            'entrega_obsequio' => 'Entrega de Obsequios',
            'registro_obsequio' => 'Registro de Obsequios',
            'teen' => 'Material Teens',
            'nehemias' => 'Nehemias',
            'nehemias_cols_cedula' => 'Nehemias: Ver Cédula',
            'nehemias_cols_telefono' => 'Nehemias: Ver Teléfono',
            'nehemias_cols_subido_link' => 'Nehemias: Ver Link subido',
            'nehemias_cols_bogota_subio' => 'Nehemias: Ver En Bogotá se le subió',
            'nehemias_cols_puesto' => 'Nehemias: Ver Puesto',
            'nehemias_cols_mesa' => 'Nehemias: Ver Mesa',
            'nehemias_cols_acepta' => 'Nehemias: Ver Acepta',
            'nehemias_acciones_editar' => 'Nehemias: Botón editar',
            'nehemias_acciones_eliminar' => 'Nehemias: Botón eliminar',
            'permisos' => 'Permisos'
        ];

        $modulosDetectados = array_unique(array_merge(
            $this->getModulosDesdeBaseDatos(),
            $this->getModulosDesdeCodigo()
        ));

        sort($modulosDetectados, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($modulosDetectados as $modulo) {
            if ($modulo === '' || isset($modulosBase[$modulo])) {
                continue;
            }

            $modulosBase[$modulo] = $this->formatearNombreModulo($modulo);
        }

        return $modulosBase;
    }

    private function getModulosDesdeBaseDatos(): array {
        try {
            $sql = "SELECT DISTINCT Modulo FROM permisos WHERE Modulo IS NOT NULL AND TRIM(Modulo) <> ''";
            $stmt = $this->db->query($sql);
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

            $modulos = [];
            foreach ($rows as $row) {
                $modulo = trim((string)($row['Modulo'] ?? ''));
                if ($modulo !== '') {
                    $modulos[] = $modulo;
                }
            }

            return array_values(array_unique($modulos));
        } catch (Throwable $e) {
            return [];
        }
    }

    private function getModulosDesdeCodigo(): array {
        $modulos = [];
        $directorios = [
            APP . '/Controllers',
            APP . '/Helpers',
            APP . '/Models',
            VIEWS
        ];

        foreach ($directorios as $directorio) {
            if (!is_dir($directorio)) {
                continue;
            }

            $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio));
            foreach ($iterador as $archivo) {
                if (!$archivo->isFile() || strtolower((string)$archivo->getExtension()) !== 'php') {
                    continue;
                }

                $contenido = @file_get_contents($archivo->getPathname());
                if (!is_string($contenido) || $contenido === '') {
                    continue;
                }

                if (preg_match_all('/AuthController::tienePermiso\s*\(\s*[\'\"]([^\'\"]+)[\'\"]\s*(?:,|\))/u', $contenido, $matches)) {
                    foreach ((array)($matches[1] ?? []) as $modulo) {
                        $modulo = trim((string)$modulo);
                        if ($modulo !== '') {
                            $modulos[] = $modulo;
                        }
                    }
                }
            }
        }

        return array_values(array_unique($modulos));
    }

    private function formatearNombreModulo(string $modulo): string {
        $texto = str_replace(['_', '-'], ' ', trim($modulo));
        $texto = preg_replace('/\s+/', ' ', (string)$texto);
        $texto = ucwords(strtolower((string)$texto));

        return $texto !== '' ? $texto : $modulo;
    }

    /**
     * Obtener permisos de un rol
     */
    private function getPermisosPorRol($idRol) {
        $sql = "SELECT * FROM permisos WHERE Id_Rol = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idRol]);
        $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        foreach ($permisos as $permiso) {
            $result[$permiso['Modulo']] = $permiso;
        }
        return $result;
    }

    /**
     * Convertir nombre de campo a nombre de base de datos
     */
    private function getCampoDb($campo) {
        $map = [
            'puede_ver' => 'Puede_Ver',
            'puede_crear' => 'Puede_Crear',
            'puede_editar' => 'Puede_Editar',
            'puede_eliminar' => 'Puede_Eliminar'
        ];
        return $map[$campo] ?? null;
    }
}
