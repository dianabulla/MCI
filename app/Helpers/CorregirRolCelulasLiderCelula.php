<?php
/**
 * Quita el rol administrativo Células de fichas ministeriales.
 * Orden: líder de 12 → líder de 144 → líder de célula.
 * El resto (sin red de liderazgo) se deja como Células (staff).
 */
require_once APP . '/Helpers/PermisosCatalogo.php';

class CorregirRolCelulasLiderCelula {

    /**
     * @return array{
     *   id_celulas:int, nombre_celulas:string,
     *   id_lider_celula:int, nombre_lider_celula:string,
     *   id_lider_12:int, nombre_lider_12:string,
     *   id_lider_144:int, nombre_lider_144:string
     * }
     */
    public static function resolverRoles(PDO $pdo): array {
        $out = [
            'id_celulas' => 0,
            'nombre_celulas' => '',
            'id_lider_celula' => 0,
            'nombre_lider_celula' => '',
            'id_lider_12' => 0,
            'nombre_lider_12' => '',
            'id_lider_144' => 0,
            'nombre_lider_144' => '',
        ];

        $stmt = $pdo->query('SELECT Id_Rol, Nombre_Rol FROM rol ORDER BY Id_Rol ASC');
        $roles = $stmt ? (array)$stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($roles as $rol) {
            $id = (int)($rol['Id_Rol'] ?? 0);
            $nombre = trim((string)($rol['Nombre_Rol'] ?? ''));
            if ($id <= 0 || $nombre === '') {
                continue;
            }

            if ($out['id_lider_144'] <= 0 && PermisosCatalogo::esNombreRolLider144($nombre)) {
                $out['id_lider_144'] = $id;
                $out['nombre_lider_144'] = $nombre;
                continue;
            }
            if ($out['id_lider_12'] <= 0 && PermisosCatalogo::esNombreRolLider12($nombre)) {
                $out['id_lider_12'] = $id;
                $out['nombre_lider_12'] = $nombre;
                continue;
            }
            if ($out['id_lider_celula'] <= 0 && PermisosCatalogo::esNombreRolLiderCelula($nombre)) {
                $out['id_lider_celula'] = $id;
                $out['nombre_lider_celula'] = $nombre;
                continue;
            }

            if ($out['id_celulas'] <= 0 && PermisosCatalogo::esRolAdministrativoEspecial($id, $nombre)) {
                $n = PermisosCatalogo::nombreRolSinAcentos($nombre);
                if ($n === 'celulas' || $n === 'celula') {
                    $out['id_celulas'] = $id;
                    $out['nombre_celulas'] = $nombre;
                }
            }
        }

        return $out;
    }

    /**
     * @return array{
     *   lider_12: array<int, array<string, mixed>>,
     *   lider_144: array<int, array<string, mixed>>,
     *   lider_celula: array<int, array<string, mixed>>,
     *   conservar: array<int, array<string, mixed>>,
     *   conservar_total: int
     * }
     */
    public static function armarPlan(PDO $pdo): array {
        $roles = self::resolverRoles($pdo);
        $idCelulas = (int)$roles['id_celulas'];
        $vacio = [
            'lider_12' => [],
            'lider_144' => [],
            'lider_celula' => [],
            'conservar' => [],
            'conservar_total' => 0,
        ];
        if ($idCelulas <= 0) {
            return $vacio;
        }

        $ids12Reales = self::idsLideres12Reales($pdo, (int)$roles['id_lider_12']);

        $sql = "SELECT
                    p.Id_Persona,
                    p.Nombre,
                    p.Apellido,
                    p.Numero_Documento,
                    p.Id_Lider,
                    CASE WHEN cel.Id_Persona IS NULL THEN 0 ELSE 1 END AS Es_Lider_Celula,
                    CASE WHEN inm.Id_Persona IS NULL THEN 0 ELSE 1 END AS Es_Lider_Inmediato,
                    GROUP_CONCAT(DISTINCT celnom.Nombre_Celula ORDER BY celnom.Nombre_Celula SEPARATOR ', ') AS Nombres_Celula
                FROM persona p
                LEFT JOIN (
                    SELECT DISTINCT Id_Lider AS Id_Persona FROM celula WHERE Id_Lider IS NOT NULL AND Id_Lider > 0
                ) cel ON cel.Id_Persona = p.Id_Persona
                LEFT JOIN (
                    SELECT DISTINCT Id_Lider_Inmediato AS Id_Persona
                    FROM celula
                    WHERE Id_Lider_Inmediato IS NOT NULL AND Id_Lider_Inmediato > 0
                ) inm ON inm.Id_Persona = p.Id_Persona
                LEFT JOIN celula celnom ON celnom.Id_Lider = p.Id_Persona
                WHERE p.Id_Rol = ?
                GROUP BY p.Id_Persona, p.Nombre, p.Apellido, p.Numero_Documento, p.Id_Lider, cel.Id_Persona, inm.Id_Persona
                ORDER BY p.Apellido, p.Nombre";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$idCelulas]);
        $filas = (array)$stmt->fetchAll(PDO::FETCH_ASSOC);

        $plan = $vacio;
        foreach ($filas as $fila) {
            $idPersona = (int)($fila['Id_Persona'] ?? 0);
            $idSuperior = (int)($fila['Id_Lider'] ?? 0);
            $esCelula = (int)($fila['Es_Lider_Celula'] ?? 0) === 1;
            $esInmediato = (int)($fila['Es_Lider_Inmediato'] ?? 0) === 1;
            $superiorEs12 = $idSuperior > 0 && isset($ids12Reales[$idSuperior]);

            if ($esInmediato) {
                $fila['Rol_Destino'] = 'lider_12';
                $plan['lider_12'][] = $fila;
                continue;
            }
            if ($esCelula && $superiorEs12) {
                $fila['Rol_Destino'] = 'lider_144';
                $plan['lider_144'][] = $fila;
                continue;
            }
            if ($esCelula) {
                $fila['Rol_Destino'] = 'lider_celula';
                $plan['lider_celula'][] = $fila;
                continue;
            }
            if ($superiorEs12) {
                $fila['Rol_Destino'] = 'lider_144';
                $plan['lider_144'][] = $fila;
                continue;
            }

            $plan['conservar'][] = $fila;
        }

        $plan['conservar_total'] = count($plan['conservar']);
        $plan['conservar'] = array_slice($plan['conservar'], 0, 200);

        return $plan;
    }

    /** @deprecated usar armarPlan */
    public static function listarACorregir(PDO $pdo, int $idRolCelulas): array {
        $plan = self::armarPlan($pdo);
        return $plan['lider_celula'];
    }

    /** @deprecated usar armarPlan */
    public static function listarConservarAdmin(PDO $pdo, int $idRolCelulas): array {
        return self::armarPlan($pdo)['conservar'];
    }

    public static function contarConservarAdmin(PDO $pdo, int $idRolCelulas): int {
        return (int)self::armarPlan($pdo)['conservar_total'];
    }

    /**
     * @return array{ok:bool, message:string, personas:int, user_roles:int, cuentas:int}
     */
    public static function aplicar(PDO $pdo): array {
        $roles = self::resolverRoles($pdo);
        $idCelulas = (int)$roles['id_celulas'];

        if ($idCelulas <= 0) {
            return self::resultado(false, 'No se encontró el rol administrativo Células.');
        }
        if ((int)$roles['id_lider_celula'] <= 0) {
            return self::resultado(false, 'No existe el rol Líder de célula. Créalo en Roles y vuelve a intentar.');
        }

        $plan = self::armarPlan($pdo);
        $cambios = [];

        foreach ($plan['lider_12'] as $fila) {
            $idDestino = (int)$roles['id_lider_12'];
            if ($idDestino <= 0) {
                return self::resultado(false, 'No existe el rol Líder de 12. Créalo en Roles y vuelve a intentar.');
            }
            $cambios[] = [(int)($fila['Id_Persona'] ?? 0), $idDestino];
        }
        foreach ($plan['lider_144'] as $fila) {
            $idDestino = (int)$roles['id_lider_144'];
            if ($idDestino <= 0) {
                return self::resultado(
                    false,
                    'Hay fichas que deben ser Líder de 144, pero ese rol no existe. Créalo en Roles (nombre con “144”) y vuelve a intentar.'
                );
            }
            $cambios[] = [(int)($fila['Id_Persona'] ?? 0), $idDestino];
        }
        foreach ($plan['lider_celula'] as $fila) {
            $cambios[] = [(int)($fila['Id_Persona'] ?? 0), (int)$roles['id_lider_celula']];
        }

        $cambios = array_values(array_filter($cambios, static function (array $par): bool {
            return $par[0] > 0 && $par[1] > 0;
        }));

        if ($cambios === []) {
            return self::resultado(true, 'No hay fichas ministeriales con rol Células para corregir.');
        }

        require_once APP . '/Models/Persona.php';
        require_once APP . '/Models/UserRole.php';
        $personaModel = new Persona();
        $userRoleModel = new UserRole();
        $tieneUserRoles = self::tablaExiste($pdo, 'user_roles');
        $tieneUsuarioAcceso = self::tablaExiste($pdo, 'usuario_acceso');

        $personas = 0;
        $userRoles = 0;
        $cuentas = 0;

        $pdo->beginTransaction();
        try {
            foreach ($cambios as [$idPersona, $idDestino]) {
                $personaModel->update($idPersona, ['Id_Rol' => $idDestino]);
                $personaModel->ajustarEscaleraPorRol($idPersona, $idDestino);
                $personas++;

                if ($tieneUserRoles) {
                    $userRoles += self::reemplazarRolEnUserRoles($pdo, $idPersona, $idCelulas, $idDestino);
                }
                if ($tieneUsuarioAcceso) {
                    $stmtUa = $pdo->prepare(
                        'UPDATE usuario_acceso SET Id_Rol = ? WHERE Id_Persona = ? AND Id_Rol = ?'
                    );
                    $stmtUa->execute([$idDestino, $idPersona, $idCelulas]);
                    $cuentas += (int)$stmtUa->rowCount();
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return self::resultado(false, 'No se pudo aplicar el cambio: ' . $e->getMessage());
        }

        if ($tieneUserRoles) {
            foreach ($cambios as [$idPersona, $idDestino]) {
                $userRoleModel->sincronizarRolPrincipal($idPersona, $idDestino);
            }
        }

        $n12 = count($plan['lider_12']);
        $n144 = count($plan['lider_144']);
        $nCel = count($plan['lider_celula']);

        return [
            'ok' => true,
            'message' => 'Corregidas ' . $personas . ' ficha(s): '
                . $n12 . ' a líder de 12, '
                . $n144 . ' a líder de 144, '
                . $nCel . ' a líder de célula.',
            'personas' => $personas,
            'user_roles' => $userRoles,
            'cuentas' => $cuentas,
        ];
    }

    /**
     * @return array<int, int>
     */
    private static function idsLideres12Reales(PDO $pdo, int $idRolLider12): array {
        $ids = [];

        $stmt = $pdo->query(
            'SELECT DISTINCT Id_Lider_Inmediato AS Id_Persona
             FROM celula
             WHERE Id_Lider_Inmediato IS NOT NULL AND Id_Lider_Inmediato > 0'
        );
        foreach ((array)($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : []) as $row) {
            $id = (int)($row['Id_Persona'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        if ($idRolLider12 > 0) {
            $stmt = $pdo->prepare('SELECT Id_Persona FROM persona WHERE Id_Rol = ?');
            $stmt->execute([$idRolLider12]);
            foreach ((array)$stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $id = (int)($row['Id_Persona'] ?? 0);
                if ($id > 0) {
                    $ids[$id] = $id;
                }
            }
        }

        return $ids;
    }

    /**
     * @return array{ok:bool, message:string, personas:int, user_roles:int, cuentas:int}
     */
    private static function resultado(bool $ok, string $message): array {
        return [
            'ok' => $ok,
            'message' => $message,
            'personas' => 0,
            'user_roles' => 0,
            'cuentas' => 0,
        ];
    }

    private static function tablaExiste(PDO $pdo, string $tabla): bool {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $stmt->execute([$tabla]);
        return (int)$stmt->fetchColumn();
    }

    private static function reemplazarRolEnUserRoles(PDO $pdo, int $idPersona, int $idCelulas, int $idDestino): int {
        $stmt = $pdo->prepare('SELECT Id_Rol FROM user_roles WHERE Id_Persona = ?');
        $stmt->execute([$idPersona]);
        $ids = [];
        foreach ((array)$stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $ids[] = (int)($row['Id_Rol'] ?? 0);
        }

        $tieneCelulas = in_array($idCelulas, $ids, true);
        $tieneDestino = in_array($idDestino, $ids, true);
        if (!$tieneCelulas) {
            return 0;
        }

        if ($tieneDestino) {
            $del = $pdo->prepare('DELETE FROM user_roles WHERE Id_Persona = ? AND Id_Rol = ?');
            $del->execute([$idPersona, $idCelulas]);
            return (int)$del->rowCount();
        }

        $upd = $pdo->prepare('UPDATE user_roles SET Id_Rol = ? WHERE Id_Persona = ? AND Id_Rol = ?');
        $upd->execute([$idDestino, $idPersona, $idCelulas]);
        return (int)$upd->rowCount();
    }
}
