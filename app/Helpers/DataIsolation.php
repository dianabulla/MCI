<?php
/**
 * DataIsolation - Manejo centralizado del aislamiento de datos por rol
 */

class DataIsolation {
    const ROL_ADMINISTRADOR = 6;
    const ROL_LIDER_CELULA = 3;
    const ROL_LIDER_12 = 8;

    /**
     * Obtener el ID del usuario actual
     */
    public static function getUsuarioId() {
        if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_id'] === '') {
            return null;
        }
        return (int) $_SESSION['usuario_id'];
    }

    /**
     * Obtener el rol del usuario actual
     */
    public static function getUsuarioRol() {
        if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] === '') {
            return null;
        }
        return (int) $_SESSION['usuario_rol'];
    }

    /**
     * Obtener nombre de rol del usuario actual
     */
    public static function getUsuarioRolNombre() {
        $rolNombre = strtolower(trim((string) ($_SESSION['usuario_rol_nombre'] ?? '')));
        return strtr($rolNombre, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n'
        ]);
    }

    /**
     * Obtener el ministerio del usuario actual
     */
    public static function getUsuarioMinisterioId() {
        if (isset($_SESSION['usuario_ministerio']) && $_SESSION['usuario_ministerio'] !== '' && $_SESSION['usuario_ministerio'] !== null) {
            return (int) $_SESSION['usuario_ministerio'];
        }

        $usuarioId = self::getUsuarioId();
        if (!$usuarioId) {
            return null;
        }

        global $pdo;
        if (!isset($pdo)) {
            return null;
        }

        $stmt = $pdo->prepare("SELECT Id_Ministerio FROM persona WHERE Id_Persona = ?");
        $stmt->execute([$usuarioId]);
        $result = $stmt->fetch();

        if (!empty($result['Id_Ministerio'])) {
            return (int) $result['Id_Ministerio'];
        }

        return null;
    }

    /**
     * Obtener la célula del usuario actual
     */
    public static function getUsuarioCelulaId() {
        $usuarioId = self::getUsuarioId();
        if (!$usuarioId) {
            return null;
        }

        global $pdo;
        if (!isset($pdo)) {
            return null;
        }

        $stmt = $pdo->prepare("SELECT Id_Celula FROM persona WHERE Id_Persona = ?");
        $stmt->execute([$usuarioId]);
        $result = $stmt->fetch();

        if (!empty($result['Id_Celula'])) {
            return (int) $result['Id_Celula'];
        }

        return null;
    }

    /**
     * Verificar si el usuario es administrador
     */
    public static function esAdmin() {
        if (self::getUsuarioRol() === self::ROL_ADMINISTRADOR) {
            return true;
        }

        $rolNombre = self::getUsuarioRolNombre();
        return strpos($rolNombre, 'admin') !== false;
    }

    /**
     * Verificar si el usuario es líder de célula
     */
    public static function esLiderCelula() {
        if (self::getUsuarioRol() === self::ROL_LIDER_CELULA) {
            return true;
        }

        $rolNombre = self::getUsuarioRolNombre();
        // Aceptar variantes de nombre: "lider de celula", "lider celula", etc.
        return (strpos($rolNombre, 'lider de celula') !== false)
            || (strpos($rolNombre, 'lider celula') !== false)
            || (strpos($rolNombre, 'lider') !== false && strpos($rolNombre, 'celula') !== false);
    }

    /**
     * Verificar si el usuario es líder de 12
     */
    public static function esLider12() {
        if (self::getUsuarioRol() === self::ROL_LIDER_12) {
            return true;
        }

        $rolNombre = self::getUsuarioRolNombre();
        // Aceptar variantes de nombre: "lider de 12", "lider 12".
        return (strpos($rolNombre, 'lider de 12') !== false)
            || (strpos($rolNombre, 'lider 12') !== false)
            || (strpos($rolNombre, 'lider') !== false && strpos($rolNombre, '12') !== false);
    }

    /**
     * Verificar si el usuario es pastor
     */
    public static function esPastor() {
        $rolNombre = self::getUsuarioRolNombre();
        return strpos($rolNombre, 'pastor') !== false;
    }

    /**
     * Verificar si el usuario pertenece al rol funcional "Celulas"
     * (distinto a "lider de celula").
     */
    public static function esRolCelulas() {
        $rolNombre = self::getUsuarioRolNombre();
        return $rolNombre === 'celulas';
    }

    /**
     * Verificar si el usuario es del rol Ganar
     */
    public static function esGanar() {
        $rolNombre = self::getUsuarioRolNombre();
        return strpos($rolNombre, 'ganar') !== false;
    }

    /**
     * Roles con acceso total a la información.
     *
     * Retorna true para:
     *  - Admin y Ganar (hardcoded por definición ministerial)
     *  - Pastor (ve toda la información del sistema)
     *  - Cualquier otro rol que tenga Ver=1 en los THREE módulos clave
     *    (configurable desde el módulo de Permisos por el administrador)
     */
    public static function tieneAccesoTotal() {
        if (self::esAdmin() || self::esGanar() || self::esPastor() || self::esRolCelulas()) {
            return true;
        }

        // Nunca elevar a acceso total los roles jerarquicos que deben ir aislados.
        if (self::esRolJerarquicoRestringido()) {
            return false;
        }

        // Rol configurado como "acceso total" desde el módulo de Permisos:
        // si tiene Ver=1 en los módulos clave → se trata como acceso total.
        $modulosClave = ['personas', 'celulas', 'ministerios'];
        $permisos = $_SESSION['permisos'] ?? [];
        if (empty($permisos)) {
            return false;
        }
        foreach ($modulosClave as $modulo) {
            if (empty($permisos[$modulo]['ver'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verificar si el usuario es asistente
     */
    public static function esAsistente() {
        $rolNombre = self::getUsuarioRolNombre();
        return strpos($rolNombre, 'asistente') !== false;
    }

    /**
     * Roles que SIEMPRE deben respetar aislamiento por jerarquia.
     */
    private static function esRolJerarquicoRestringido() {
        return self::esLiderCelula() || self::esLider12() || self::esAsistente();
    }

    /**
     * Roles con visibilidad por ministerio
     */
    public static function usaVisibilidadPorMinisterio() {
        return self::esAsistente();
    }

    /**
     * Condición por anclaje de líder/pastor para personas
     */
    private static function generarCondicionAnclajePersonas($aliasPersona = 'p') {
        $usuarioId = self::getUsuarioId();
        if (!$usuarioId) {
            return '1=0';
        }

        $idMinisterio = self::getUsuarioMinisterioId();

        $aliasPersona = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$aliasPersona);
        if ($aliasPersona === '') {
            $aliasPersona = 'p';
        }

        if (self::esLiderCelula()) {
            return "($aliasPersona.Id_Lider = $usuarioId OR $aliasPersona.Id_Persona = $usuarioId)";
        }

        if (self::esLider12()) {
            $condicion = "(
                $aliasPersona.Id_Persona = $usuarioId
                OR $aliasPersona.Id_Lider = $usuarioId
                OR $aliasPersona.Id_Lider IN (
                    SELECT DISTINCT c.Id_Lider
                    FROM celula c
                    WHERE c.Id_Lider_Inmediato = $usuarioId
                )
            )";

            if ($idMinisterio) {
                $condicion = "($condicion OR $aliasPersona.Id_Ministerio = $idMinisterio)";
            }

            return $condicion;
        }

        if (self::esPastor()) {
            return "(
                $aliasPersona.Id_Persona = $usuarioId
                OR $aliasPersona.Id_Lider = $usuarioId
                OR $aliasPersona.Id_Lider IN (
                    SELECT DISTINCT c.Id_Lider
                    FROM celula c
                    WHERE c.Id_Lider_Inmediato = $usuarioId
                )
            )";
        }

        return '1=0';
    }

    /**
     * Obtener ministerios visibles por cobertura de liderazgo.
     *
     * Incluye el ministerio propio del usuario (si existe) y los ministerios
     * de líderes de células bajo su cobertura (líder inmediato).
     */
    private static function getMinisterioIdsCoberturaLiderazgo() {
        $ids = [];

        $idMinisterioPropio = self::getUsuarioMinisterioId();
        if (!empty($idMinisterioPropio)) {
            $ids[] = (int)$idMinisterioPropio;
        }

        $usuarioId = self::getUsuarioId();
        if (!$usuarioId) {
            return array_values(array_unique(array_filter($ids)));
        }

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return array_values(array_unique(array_filter($ids)));
        }

        $sql = "SELECT DISTINCT l.Id_Ministerio
                FROM celula c
                INNER JOIN persona l ON c.Id_Lider = l.Id_Persona
                WHERE l.Id_Ministerio IS NOT NULL
                AND (c.Id_Lider_Inmediato = ? OR c.Id_Lider = ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$usuarioId, $usuarioId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $id = isset($row['Id_Ministerio']) ? (int)$row['Id_Ministerio'] : 0;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * Generar cláusula WHERE para personas según el rol
     * Si es administrador: sin restricciones
     * Si es líder de célula: ver solo personas de su célula
     * Si es líder de 12: ver solo personas que lidera
     */
    public static function generarFiltroPersonas() {
        $idMinisterio = self::getUsuarioMinisterioId();

        if (self::tieneAccesoTotal()) {
            return "1=1";
        }

        if (self::esLiderCelula() || self::esLider12() || self::esPastor()) {
            return self::generarCondicionAnclajePersonas('p');
        }

        if (self::esAsistente()) {
            if ($idMinisterio) {
                return "p.Id_Ministerio = $idMinisterio";
            }
            return "1=0";
        }

        // Fallback para roles personalizados:
        // si tiene permiso explícito de ver personas, permitir listado completo.
        if (isset($_SESSION['permisos']['personas'])
            && !empty($_SESSION['permisos']['personas']['ver'])) {
            return "1=1";
        }

        // Otros roles: sin acceso (restringir)
        return "1=0";
    }

    /**
     * Filtro especializado para el apartado "Pendiente por consolidar".
     *
     * Mantiene el anclaje habitual por líder/pastor, pero permite además
     * visualizar personas del mismo ministerio que aún no tienen líder asignado.
     */
    public static function generarFiltroPersonasPendienteConsolidar() {
        $idMinisterio = self::getUsuarioMinisterioId();

        if (self::tieneAccesoTotal()) {
            return "1=1";
        }

        if (self::esLiderCelula() || self::esLider12() || self::esPastor()) {
            $condicionAnclaje = self::generarCondicionAnclajePersonas('p');

            $ministerioIds = self::getMinisterioIdsCoberturaLiderazgo();

            if (!empty($ministerioIds)) {
                $ministerioIdsSql = implode(',', array_map('intval', $ministerioIds));
                return "($condicionAnclaje OR (p.Id_Ministerio IN ($ministerioIdsSql) AND p.Id_Lider IS NULL))";
            }

            if ($idMinisterio) {
                return "($condicionAnclaje OR (p.Id_Ministerio = $idMinisterio AND p.Id_Lider IS NULL))";
            }

            return $condicionAnclaje;
        }

        if (self::esAsistente()) {
            if ($idMinisterio) {
                return "p.Id_Ministerio = $idMinisterio";
            }
            return "1=0";
        }

        if (isset($_SESSION['permisos']['personas'])
            && !empty($_SESSION['permisos']['personas']['ver'])) {
            return "1=1";
        }

        return "1=0";
    }

    /**
     * Generar cláusula WHERE para células según el rol
     */
    public static function generarFiltroCelulas() {
        $usuarioId = self::getUsuarioId();
        $idMinisterio = self::getUsuarioMinisterioId();

        if (self::tieneAccesoTotal()) {
            return "1=1";
        }

        if (self::esLiderCelula()) {
            if ($usuarioId) {
                return "c.Id_Lider = $usuarioId";
            }
            return "1=0";
        }

        if (self::esLider12()) {
            if ($usuarioId) {
                $ministerioIds = self::getMinisterioIdsCoberturaLiderazgo();

                if (!empty($ministerioIds)) {
                    $ministerioIdsSql = implode(',', array_map('intval', $ministerioIds));
                    return "(
                        c.Id_Lider = $usuarioId
                        OR c.Id_Lider_Inmediato = $usuarioId
                        OR c.Id_Lider IN (
                            SELECT Id_Persona
                            FROM persona
                            WHERE Id_Ministerio IN ($ministerioIdsSql)
                        )
                    )";
                }

                return "(c.Id_Lider = $usuarioId OR c.Id_Lider_Inmediato = $usuarioId)";
            }
            return "1=0";
        }

        if (self::esPastor()) {
            if ($usuarioId) {
                return "(c.Id_Lider_Inmediato = $usuarioId OR c.Id_Lider = $usuarioId)";
            }
            return "1=0";
        }

        if (self::esAsistente()) {
            if ($idMinisterio) {
                return "c.Id_Lider IN (SELECT Id_Persona FROM persona WHERE Id_Ministerio = $idMinisterio)";
            }
            return "1=0";
        }

        // Fallback para roles personalizados:
        // si tiene permiso explícito de ver células, permitir listado completo.
        if (isset($_SESSION['permisos']['celulas'])
            && !empty($_SESSION['permisos']['celulas']['ver'])) {
            return "1=1";
        }

        return "1=0";
    }

    /**
     * Generar cláusula WHERE para asistencias según el rol
     */
    public static function generarFiltroAsistencias() {
        $idMinisterio = self::getUsuarioMinisterioId();

        if (self::tieneAccesoTotal()) {
            return "1=1";
        }

        if (self::esLiderCelula() || self::esLider12() || self::esPastor()) {
            $condicionPersonas = self::generarCondicionAnclajePersonas('p');
            return "a.Id_Persona IN (SELECT p.Id_Persona FROM persona p WHERE $condicionPersonas)";
        }

        if (self::esAsistente()) {
            if ($idMinisterio) {
                return "a.Id_Persona IN (SELECT Id_Persona FROM persona WHERE Id_Ministerio = $idMinisterio)";
            }
            return "1=0";
        }

        return "1=0";
    }

    /**
     * Generar cláusula WHERE para eventos según el rol
     */
    public static function generarFiltroEventos() {
        // Todos pueden ver eventos
        return "1=1";
    }

    /**
     * Generar cláusula WHERE para ministerios según el rol
     */
    public static function generarFiltroMinisterios() {
        $usuarioId = self::getUsuarioId();
        $idMinisterio = self::getUsuarioMinisterioId();

        if (self::tieneAccesoTotal()) {
            return "1=1";
        }

        if (self::esLiderCelula() || self::esLider12() || self::esAsistente()) {
            if ($idMinisterio) {
                return "m.Id_Ministerio = $idMinisterio";
            }
            return "1=0";
        }

        if (self::esPastor()) {
            if (!$usuarioId) {
                return "1=0";
            }

            return "m.Id_Ministerio IN (
                SELECT DISTINCT p.Id_Ministerio
                FROM persona p
                WHERE p.Id_Ministerio IS NOT NULL
                AND (
                    p.Id_Lider = $usuarioId
                    OR p.Id_Lider IN (
                        SELECT DISTINCT c.Id_Lider
                        FROM celula c
                        WHERE c.Id_Lider_Inmediato = $usuarioId
                    )
                    OR p.Id_Persona = $usuarioId
                )
            )";
        }

        return "1=0";
    }

    /**
     * Generar cláusula WHERE para peticiones según el rol
     */
    public static function generarFiltroPeticiones() {
        $idMinisterio = self::getUsuarioMinisterioId();

        if (self::tieneAccesoTotal()) {
            return "1=1";
        }

        if (self::esLiderCelula() || self::esLider12() || self::esPastor()) {
            $condicionPersonas = self::generarCondicionAnclajePersonas('p');
            return "pet.Id_Persona IN (SELECT p.Id_Persona FROM persona p WHERE $condicionPersonas)";
        }

        if (self::esAsistente()) {
            if ($idMinisterio) {
                return "pet.Id_Persona IN (SELECT Id_Persona FROM persona WHERE Id_Ministerio = $idMinisterio)";
            }
            return "1=0";
        }

        return "1=0";
    }
}
?>
