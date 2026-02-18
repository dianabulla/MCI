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
        return $_SESSION['usuario_id'] ?? null;
    }

    /**
     * Obtener el rol del usuario actual
     */
    public static function getUsuarioRol() {
        return $_SESSION['usuario_rol'] ?? null;
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
        return self::getUsuarioRol() === self::ROL_LIDER_CELULA;
    }

    /**
     * Verificar si el usuario es líder de 12
     */
    public static function esLider12() {
        return self::getUsuarioRol() === self::ROL_LIDER_12;
    }

    /**
     * Generar cláusula WHERE para personas según el rol
     * Si es administrador: sin restricciones
     * Si es líder de célula: ver solo personas de su célula
     * Si es líder de 12: ver solo personas que lidera
     */
    public static function generarFiltroPersonas() {
        $usuarioId = self::getUsuarioId();
        $rol = self::getUsuarioRol();

        if (self::esAdmin()) {
            return "1=1"; // Sin restricciones para administrador
        }

        if (self::esLiderCelula()) {
            // Obtener la célula del líder
            global $pdo;
            $stmt = $pdo->prepare("SELECT Id_Celula FROM persona WHERE Id_Persona = ?");
            $stmt->execute([$usuarioId]);
            $result = $stmt->fetch();
            $idCelula = $result['Id_Celula'] ?? null;

            if ($idCelula) {
                return "p.Id_Celula = $idCelula";
            }
            return "1=0"; // No tiene célula asignada
        }

        if (self::esLider12()) {
            // Ver solo personas que lidera directamente
            return "p.Id_Lider = $usuarioId";
        }

        // Otros roles: sin acceso (restringir)
        return "1=0";
    }

    /**
     * Generar cláusula WHERE para células según el rol
     */
    public static function generarFiltroCelulas() {
        $usuarioId = self::getUsuarioId();
        $rol = self::getUsuarioRol();

        if (self::esAdmin()) {
            return "1=1";
        }

        if (self::esLiderCelula()) {
            // Ver solo su célula
            global $pdo;
            $stmt = $pdo->prepare("SELECT Id_Celula FROM persona WHERE Id_Persona = ?");
            $stmt->execute([$usuarioId]);
            $result = $stmt->fetch();
            $idCelula = $result['Id_Celula'] ?? null;

            if ($idCelula) {
                return "c.Id_Celula = $idCelula";
            }
            return "1=0";
        }

        if (self::esLider12()) {
            // Ver células donde es líder
            return "c.Id_Lider = $usuarioId";
        }

        return "1=0";
    }

    /**
     * Generar cláusula WHERE para asistencias según el rol
     */
    public static function generarFiltroAsistencias() {
        $usuarioId = self::getUsuarioId();
        $rol = self::getUsuarioRol();

        if (self::esAdmin()) {
            return "1=1";
        }

        if (self::esLiderCelula()) {
            // Ver asistencias de su célula
            global $pdo;
            $stmt = $pdo->prepare("SELECT Id_Celula FROM persona WHERE Id_Persona = ?");
            $stmt->execute([$usuarioId]);
            $result = $stmt->fetch();
            $idCelula = $result['Id_Celula'] ?? null;

            if ($idCelula) {
                return "a.Id_Celula = $idCelula";
            }
            return "1=0";
        }

        if (self::esLider12()) {
            // Ver asistencias de sus personas
            return "a.Id_Persona IN (SELECT Id_Persona FROM persona WHERE Id_Lider = $usuarioId)";
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
        $rol = self::getUsuarioRol();

        if (self::esAdmin()) {
            return "1=1";
        }

        if (self::esLiderCelula() || self::esLider12()) {
            // Ver todos pero gestionar solo los suyos
            return "1=1";
        }

        return "1=0";
    }

    /**
     * Generar cláusula WHERE para peticiones según el rol
     */
    public static function generarFiltroPeticiones() {
        $usuarioId = self::getUsuarioId();
        $rol = self::getUsuarioRol();

        if (self::esAdmin()) {
            return "1=1";
        }

        if (self::esLiderCelula()) {
            // Ver peticiones de personas de su célula
            global $pdo;
            $stmt = $pdo->prepare("SELECT Id_Celula FROM persona WHERE Id_Persona = ?");
            $stmt->execute([$usuarioId]);
            $result = $stmt->fetch();
            $idCelula = $result['Id_Celula'] ?? null;

            if ($idCelula) {
                return "pe.Id_Persona IN (SELECT Id_Persona FROM persona WHERE Id_Celula = $idCelula)";
            }
            return "1=0";
        }

        if (self::esLider12()) {
            // Ver peticiones de sus personas
            return "pe.Id_Persona IN (SELECT Id_Persona FROM persona WHERE Id_Lider = $usuarioId)";
        }

        return "1=0";
    }
}
?>
