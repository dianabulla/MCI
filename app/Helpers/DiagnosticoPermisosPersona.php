<?php
/**
 * Diagnóstico de permisos para editar personas (producción / admin).
 */
class DiagnosticoPermisosPersona {

    /** @var array<int, string> */
    private const MODULOS_RELEVANTES = [
        'personas' => 'Almas ganadas',
        'personas_consulta' => 'Discípulos (consulta)',
        'ministerios' => 'Discipular (ministerios)',
        'celulas' => 'Enviar (células)',
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listarRoles(PDO $pdo): array {
        $stmt = $pdo->query('SELECT Id_Rol, Nombre_Rol FROM rol ORDER BY Nombre_Rol ASC');
        return $stmt ? (array)$stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    /**
     * @return array<string, array{ver:int, crear:int, editar:int, eliminar:int}>
     */
    public static function permisosNormalizadosPorRol(PDO $pdo, int $idRol): array {
        if ($idRol <= 0) {
            return [];
        }

        require_once APP . '/Models/Persona.php';
        require_once APP . '/Helpers/PermisosCatalogo.php';

        $personaModel = new Persona();
        $filas = $personaModel->getPermisosPorRol($idRol);
        $resultado = [];

        foreach ((array)$filas as $permiso) {
            $modulo = trim((string)($permiso['Modulo'] ?? ''));
            if ($modulo === '') {
                continue;
            }

            $resultado[$modulo] = [
                'ver' => !empty($permiso['Puede_Ver']) ? 1 : 0,
                'crear' => !empty($permiso['Puede_Crear']) ? 1 : 0,
                'editar' => !empty($permiso['Puede_Editar']) ? 1 : 0,
                'eliminar' => !empty($permiso['Puede_Eliminar']) ? 1 : 0,
            ];

            foreach (PermisosCatalogo::mapaDesdeFila((array)$permiso) as $claveExtra => $valorExtra) {
                $resultado[$modulo][$claveExtra] = !empty($valorExtra) ? 1 : 0;
            }
        }

        return $resultado;
    }

    /**
     * @param array<string, array<string, int>> $permisos
     * @return array<string, mixed>
     */
    public static function evaluarEditarPersona(array $permisos): array {
        $puedeModulo = static function (string $modulo, string $accion) use ($permisos): bool {
            return !empty($permisos[$modulo][$accion]);
        };

        $personasEditar = $puedeModulo('personas', 'editar');
        $consultaEditar = $puedeModulo('personas_consulta', 'editar');
        $ministeriosEditar = $puedeModulo('ministerios', 'editar');
        $celulasEditar = $puedeModulo('celulas', 'editar');

        $puedeEditarConsulta = $personasEditar || $consultaEditar;
        $puedeEditarDiscipular = $puedeEditarConsulta || $ministeriosEditar;
        $accesoRuta = $puedeEditarDiscipular;

        $motivos = [];
        if (!$accesoRuta) {
            if ($celulasEditar && !$ministeriosEditar) {
                $motivos[] = 'Tiene «Editar células» pero eso no autoriza personas/editar.';
            }
            $motivos[] = 'Active al menos uno: Discípulos (consulta) → Editar fichas, Almas ganadas → Editar fichas, o Discipular → Editar equipos.';
        } elseif ($ministeriosEditar && !$puedeEditarConsulta) {
            $motivos[] = 'Solo «Editar equipos» (ministerios): el botón en Discipular sí; antes la ruta fallaba sin el parche puedeEditarPersonaDesdeDiscipular.';
        }

        return [
            'personas_editar' => $personasEditar,
            'personas_consulta_editar' => $consultaEditar,
            'ministerios_editar' => $ministeriosEditar,
            'celulas_editar' => $celulasEditar,
            'puede_editar_personas_consulta' => $puedeEditarConsulta,
            'puede_editar_desde_discipular' => $puedeEditarDiscipular,
            'acceso_ruta_personas_editar' => $accesoRuta,
            'boton_discipular_visible' => $puedeEditarDiscipular,
            'motivos' => $motivos,
        ];
    }

    /**
     * @param array<string, array<string, int>> $permisos
     * @return array<int, array<string, mixed>>
     */
    public static function filasModulosRelevantes(array $permisos): array {
        $filas = [];
        foreach (self::MODULOS_RELEVANTES as $modulo => $etiqueta) {
            $crud = $permisos[$modulo] ?? null;
            $filas[] = [
                'modulo' => $modulo,
                'etiqueta' => $etiqueta,
                'configurado' => is_array($crud),
                'ver' => !empty($crud['ver']),
                'crear' => !empty($crud['crear']),
                'editar' => !empty($crud['editar']),
                'eliminar' => !empty($crud['eliminar']),
            ];
        }
        return $filas;
    }

    public static function tieneParcheEditarDiscipular(): bool {
        return method_exists('AuthController', 'puedeEditarPersonaDesdeDiscipular');
    }
}
