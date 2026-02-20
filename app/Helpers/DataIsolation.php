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
        return self::getUsuarioRol() === self::ROL_ADMINISTRADOR;
    }

    /**
     * Verificar si el usuario es líder de célula
     */
    public static function esLiderCelula() {
        if (self::getUsuarioRol() === self::ROL_LIDER_CELULA) {
            return true;
        }

        $rolNombre = self::getUsuarioRolNombre();
        return strpos($rolNombre, 'lider de celula') !== false;
    }

    /**
     * Verificar si el usuario es líder de 12
     */
    public static function esLider12() {
        if (self::getUsuarioRol() === self::ROL_LIDER_12) {
            return true;
        }

        $rolNombre = self::getUsuarioRolNombre();
        return strpos($rolNombre, 'lider de 12') !== false;
    }

    /**
     * Verificar si el usuario es pastor
     */
    public static function esPastor() {
        $rolNombre = self::getUsuarioRolNombre();
        return strpos($rolNombre, 'pastor') !== false;
    }

    /**
     * Verificar si el usuario es del rol Ganar
     */
    public static function esGanar() {
        $rolNombre = self::getUsuarioRolNombre();
        return strpos($rolNombre, 'ganar') !== false;
    }

    /**
     * Roles con acceso total a la información
     */
    public static function tieneAccesoTotal() {
        return self::esAdmin() || self::esPastor() || self::esGanar();
    }

    /**
     * Verificar si el usuario es asistente
     */
    public static function esAsistente() {
        $rolNombre = self::getUsuarioRolNombre();
        return strpos($rolNombre, 'asistente') !== false;
    }

    /**
     * Roles con visibilidad por ministerio
     */
    public static function usaVisibilidadPorMinisterio() {
        return self::esLider12() || self::esPastor() || self::esAsistente();
    }

    /**
     * Generar cláusula WHERE para personas según el rol
     * Si es administrador: sin restricciones
     * Si es líder de célula: ver solo personas de su célula
     * Si es líder de 12: ver solo personas que lidera
     */
    public static function generarFiltroPersonas() {
        $usuarioId = self::getUsuarioId();
        $idMinisterio = self::getUsuarioMinisterioId();
        $idCelula = self::getUsuarioCelulaId();

        if (self::tieneAccesoTotal()) {
            return "1=1"; // Sin restricciones para administrador
        }

        if (self::esLiderCelula()) {
            if ($idCelula) {
                return "p.Id_Celula = $idCelula";
            }
            return "1=0";
        }

        if (self::usaVisibilidadPorMinisterio()) {
            if ($idMinisterio) {
                return "p.Id_Ministerio = $idMinisterio";
            }
            return "1=0";
        }

        // Otros roles: sin acceso (restringir)
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

        if (self::usaVisibilidadPorMinisterio()) {
            if ($idMinisterio) {
                return "c.Id_Lider IN (SELECT Id_Persona FROM persona WHERE Id_Ministerio = $idMinisterio)";
            }
            return "1=0";
        }

        return "1=0";
    }

    /**
     * Generar cláusula WHERE para asistencias según el rol
     */
    public static function generarFiltroAsistencias() {
        $usuarioId = self::getUsuarioId();
        $idMinisterio = self::getUsuarioMinisterioId();
        $idCelula = self::getUsuarioCelulaId();

        if (self::tieneAccesoTotal()) {
            return "1=1";
        }

        if (self::esLiderCelula()) {
            if ($idCelula) {
                return "a.Id_Celula = $idCelula";
            }
            return "1=0";
        }

        if (self::usaVisibilidadPorMinisterio()) {
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
        $idMinisterio = self::getUsuarioMinisterioId();

        if (self::tieneAccesoTotal()) {
            return "1=1";
        }

        if (self::esLiderCelula() || self::usaVisibilidadPorMinisterio()) {
            if ($idMinisterio) {
                return "m.Id_Ministerio = $idMinisterio";
            }
            return "1=0";
        }

        return "1=0";
    }

    /**
     * Generar cláusula WHERE para peticiones según el rol
     */
    public static function generarFiltroPeticiones() {
        $usuarioId = self::getUsuarioId();
        $idMinisterio = self::getUsuarioMinisterioId();
        $idCelula = self::getUsuarioCelulaId();

        if (self::tieneAccesoTotal()) {
            return "1=1";
        }

        if (self::esLiderCelula()) {
            if ($idCelula) {
                return "pet.Id_Persona IN (SELECT Id_Persona FROM persona WHERE Id_Celula = $idCelula)";
            }
            return "1=0";
        }

        if (self::usaVisibilidadPorMinisterio()) {
            if ($idMinisterio) {
                return "pet.Id_Persona IN (SELECT Id_Persona FROM persona WHERE Id_Ministerio = $idMinisterio)";
            }
            return "1=0";
        }

        return "1=0";
    }
}
?>
