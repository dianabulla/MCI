<?php
/**
 * Provisiona usuario de acceso (rol discípulo, usuario/contraseña = cédula)
 * al inscribirse en Capacitación Destino (cualquier nivel).
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/UserRole.php';

class AccesoDiscipuloCapDestino {
    public static function esProgramaCapacitacionDestino(string $programa): bool {
        $programa = strtolower(trim($programa));
        if ($programa === 'capacitacion_destino') {
            return true;
        }

        return in_array($programa, [
            'capacitacion_destino_nivel_1',
            'capacitacion_destino_nivel_2',
            'capacitacion_destino_nivel_3',
        ], true);
    }

    public static function normalizarDocumento(string $valor): string {
        $valor = trim($valor);
        if ($valor === '') {
            return '';
        }

        $valor = preg_replace('/\s+/', '', $valor);
        return function_exists('mb_strtoupper') ? mb_strtoupper($valor, 'UTF-8') : strtoupper($valor);
    }

    /**
     * Crea o completa acceso discípulo para una persona inscrita en Cap. Destino.
     */
    public static function provisionar(int $idPersona, string $programa, string $cedula = ''): bool {
        $idPersona = (int)$idPersona;
        $programa = trim($programa);

        if ($idPersona <= 0 || !self::esProgramaCapacitacionDestino($programa)) {
            return false;
        }

        $personaModel = new Persona();
        $persona = $personaModel->getById($idPersona);
        if (empty($persona)) {
            return false;
        }

        $cedula = self::normalizarDocumento($cedula);
        if ($cedula === '') {
            $cedula = self::normalizarDocumento((string)($persona['Numero_Documento'] ?? ''));
        }

        if ($cedula === '') {
            return false;
        }

        if (trim((string)($persona['Numero_Documento'] ?? '')) === '') {
            $personaModel->update($idPersona, ['Numero_Documento' => $cedula]);
        }

        $userRoleModel = new UserRole();
        $userRoleModel->asegurarTabla();

        $idRolDiscipulo = $userRoleModel->buscarRolPorAlias('discipulo');
        if ($idRolDiscipulo <= 0) {
            return false;
        }

        $idRolActual = (int)($persona['Id_Rol'] ?? 0);
        $jerarquia = $personaModel->getJerarquiaByRol($idRolActual);
        $esLiderazgo = in_array($jerarquia, ['pastor', 'lider_12', 'lider_144', 'lider_celula', 'administrativo'], true);

        if (!$esLiderazgo) {
            if ($idRolActual !== $idRolDiscipulo) {
                $personaModel->update($idPersona, ['Id_Rol' => $idRolDiscipulo]);
                $personaModel->ajustarEscaleraPorRol($idPersona, $idRolDiscipulo);
                $idRolActual = $idRolDiscipulo;
            }
        }

        if ($idRolActual > 0) {
            $userRoleModel->sincronizarRolPrincipal($idPersona, $idRolActual);
        }

        $userRoleModel->asignarRol($idPersona, $idRolDiscipulo);

        $usuarioActual = trim((string)($persona['Usuario'] ?? ''));
        if ($usuarioActual === '') {
            $personaModel->setUsuario($idPersona, $cedula, $cedula);
        }

        $estadoCuenta = trim((string)($persona['Estado_Cuenta'] ?? ''));
        if ($estadoCuenta === '' || strtolower($estadoCuenta) !== 'activo') {
            $personaModel->update($idPersona, ['Estado_Cuenta' => 'Activo']);
        }

        return true;
    }

    /**
     * Tras crear o mover una inscripción: resuelve persona y provisiona acceso.
     */
    public static function provisionarDesdeInscripcion(array $inscripcion): bool {
        $programa = trim((string)($inscripcion['Programa'] ?? ''));
        if (!self::esProgramaCapacitacionDestino($programa)) {
            return false;
        }

        $idPersona = (int)($inscripcion['Id_Persona'] ?? 0);
        $cedula = self::normalizarDocumento((string)($inscripcion['Cedula'] ?? ''));

        if ($idPersona <= 0) {
            require_once APP . '/Models/EscuelaFormacionInscripcion.php';
            $inscripcionModel = new EscuelaFormacionInscripcion();
            $idPersona = $inscripcionModel->resolverIdPersonaDesdeInscripcion($inscripcion);
        }

        if ($idPersona <= 0) {
            return false;
        }

        return self::provisionar($idPersona, $programa, $cedula);
    }
}
