<?php
/**
 * Modelo UserRole
 * Manejo de roles múltiples por persona (tabla intermedia user_roles).
 */

require_once APP . '/Models/BaseModel.php';

class UserRole extends BaseModel {
    protected $table = 'user_roles';
    protected $primaryKey = 'Id_User_Role';

    public function existeTabla(): bool {
        try {
            $stmt = $this->db->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$this->table]);
            return (bool)$stmt->fetch();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function asegurarTabla(): bool {
        if ($this->existeTabla()) {
            return true;
        }

        $sql = "CREATE TABLE IF NOT EXISTS user_roles (
            Id_User_Role INT AUTO_INCREMENT PRIMARY KEY,
            Id_Persona INT NOT NULL,
            Id_Rol INT NOT NULL,
            Activo TINYINT(1) NOT NULL DEFAULT 1,
            Creado_En DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            Actualizado_En DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_roles_persona_rol (Id_Persona, Id_Rol),
            KEY idx_user_roles_persona (Id_Persona),
            KEY idx_user_roles_rol (Id_Rol)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->db->exec($sql);
            return true;
        } catch (Throwable $e) {
            error_log('No se pudo crear tabla user_roles: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deja el rol principal en user_roles y solo conserva segundos roles permitidos:
     * Maestro (asignado explícitamente) y Discípulo (solo si está inscrito en Cap. Destino).
     */
    public function establecerRolUnico(int $idPersona, int $idRolPrincipal): bool {
        if ($idPersona <= 0 || $idRolPrincipal <= 0) {
            return false;
        }

        if (!$this->asegurarTabla()) {
            return false;
        }

        $idRolMaestro = $this->buscarRolPorAlias('maestro');
        $idRolDiscipulo = $this->buscarRolPorAlias('discipulo');
        $conservarMaestroSecundario = false;

        if ($idRolMaestro > 0 && $idRolPrincipal !== $idRolMaestro) {
            foreach ($this->listarRolesPersona($idPersona) as $rol) {
                if ((int)($rol['Id_Rol'] ?? 0) === $idRolMaestro) {
                    $conservarMaestroSecundario = true;
                    break;
                }
            }
        }

        $conservarDiscipuloSecundario = $this->debeTenerDiscipuloSecundario($idPersona, $idRolPrincipal, $idRolDiscipulo);

        if (!$this->execute('DELETE FROM user_roles WHERE Id_Persona = ?', [$idPersona])) {
            return false;
        }

        if (!$this->execute(
            'INSERT INTO user_roles (Id_Persona, Id_Rol, Activo) VALUES (?, ?, 1)',
            [$idPersona, $idRolPrincipal]
        )) {
            return false;
        }

        if ($conservarMaestroSecundario && $idRolMaestro > 0) {
            $this->execute(
                'INSERT INTO user_roles (Id_Persona, Id_Rol, Activo) VALUES (?, ?, 1)',
                [$idPersona, $idRolMaestro]
            );
        }

        if ($conservarDiscipuloSecundario && $idRolDiscipulo > 0) {
            $this->execute(
                'INSERT INTO user_roles (Id_Persona, Id_Rol, Activo) VALUES (?, ?, 1)',
                [$idPersona, $idRolDiscipulo]
            );
        }

        return true;
    }

    public function sincronizarRolPrincipal(int $idPersona, int $idRol): bool {
        return $this->establecerRolUnico($idPersona, $idRol);
    }

    public function asignarRol(int $idPersona, int $idRol): bool {
        if ($idPersona <= 0 || $idRol <= 0) {
            return false;
        }

        if (!$this->asegurarTabla()) {
            return false;
        }

        $idRolMaestro = $this->buscarRolPorAlias('maestro');
        if ($idRolMaestro > 0 && $idRol === $idRolMaestro) {
            return $this->asignarMaestroSecundario($idPersona, $idRolMaestro);
        }

        $idRolDiscipulo = $this->buscarRolPorAlias('discipulo');
        if ($idRolDiscipulo > 0 && $idRol === $idRolDiscipulo) {
            require_once APP . '/Models/Persona.php';
            $persona = (new Persona())->getById($idPersona);
            $idRolPrincipal = (int)($persona['Id_Rol'] ?? 0);

            if ($this->debeTenerDiscipuloSecundario($idPersona, $idRolPrincipal, $idRolDiscipulo)) {
                return $this->asignarDiscipuloSecundario($idPersona, $idRolPrincipal);
            }

            return $this->establecerRolUnico($idPersona, $idRol);
        }

        return $this->establecerRolUnico($idPersona, $idRol);
    }

    public function asignarDiscipuloSecundario(int $idPersona, int $idRolPrincipal = 0): bool {
        if ($idPersona <= 0 || !$this->asegurarTabla()) {
            return false;
        }

        $idRolDiscipulo = $this->buscarRolPorAlias('discipulo');
        if ($idRolDiscipulo <= 0) {
            return false;
        }

        if ($idRolPrincipal <= 0) {
            require_once APP . '/Models/Persona.php';
            $persona = (new Persona())->getById($idPersona);
            $idRolPrincipal = (int)($persona['Id_Rol'] ?? 0);
        }

        if (!$this->debeTenerDiscipuloSecundario($idPersona, $idRolPrincipal, $idRolDiscipulo)) {
            return false;
        }

        $sql = "INSERT INTO user_roles (Id_Persona, Id_Rol, Activo)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE Activo = VALUES(Activo), Actualizado_En = CURRENT_TIMESTAMP";

        return $this->execute($sql, [$idPersona, $idRolDiscipulo]);
    }

    public function asignarMaestroSecundario(int $idPersona, int $idRolMaestro): bool {
        if ($idPersona <= 0 || $idRolMaestro <= 0 || !$this->asegurarTabla()) {
            return false;
        }

        $sql = "INSERT INTO user_roles (Id_Persona, Id_Rol, Activo)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE Activo = VALUES(Activo), Actualizado_En = CURRENT_TIMESTAMP";

        return $this->execute($sql, [$idPersona, $idRolMaestro]);
    }

    public function quitarRol(int $idPersona, int $idRol): bool {
        if ($idPersona <= 0 || $idRol <= 0) {
            return false;
        }

        if (!$this->asegurarTabla()) {
            return false;
        }

        $sql = "DELETE FROM user_roles WHERE Id_Persona = ? AND Id_Rol = ?";
        return $this->execute($sql, [$idPersona, $idRol]);
    }

    public function listarRolesPersona(int $idPersona): array {
        if ($idPersona <= 0 || !$this->asegurarTabla()) {
            return [];
        }

        $sql = "SELECT ur.Id_Rol, r.Nombre_Rol
                FROM user_roles ur
                INNER JOIN rol r ON r.Id_Rol = ur.Id_Rol
                WHERE ur.Id_Persona = ?
                  AND ur.Activo = 1
                ORDER BY ur.Id_Rol ASC";

        return $this->query($sql, [$idPersona]);
    }

    public function listarRolesPorPersonas(array $idsPersona): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $idsPersona), static function($id) {
            return $id > 0;
        })));

        if (empty($ids) || !$this->asegurarTabla()) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT ur.Id_Persona, ur.Id_Rol, r.Nombre_Rol
                FROM user_roles ur
                INNER JOIN rol r ON r.Id_Rol = ur.Id_Rol
                WHERE ur.Activo = 1
                  AND ur.Id_Persona IN ({$placeholders})
                ORDER BY ur.Id_Persona ASC, ur.Id_Rol ASC";

        $rows = $this->query($sql, $ids);
        $map = [];
        foreach ($rows as $row) {
            $idPersona = (int)($row['Id_Persona'] ?? 0);
            if ($idPersona <= 0) {
                continue;
            }
            if (!isset($map[$idPersona])) {
                $map[$idPersona] = [];
            }
            $map[$idPersona][] = [
                'Id_Rol' => (int)($row['Id_Rol'] ?? 0),
                'Nombre_Rol' => (string)($row['Nombre_Rol'] ?? ''),
            ];
        }

        return $map;
    }

    public function buscarRolPorAlias(string $alias): int {
        $alias = $this->normalizarTexto($alias);
        if ($alias === '') {
            return 0;
        }

        $rows = $this->query("SELECT Id_Rol, Nombre_Rol FROM rol ORDER BY Id_Rol ASC");
        foreach ($rows as $row) {
            $idRol = (int)($row['Id_Rol'] ?? 0);
            $nombre = $this->normalizarTexto((string)($row['Nombre_Rol'] ?? ''));
            if ($idRol <= 0 || $nombre === '') {
                continue;
            }

            if ($alias === 'discipulo') {
                if (strpos($nombre, 'discipul') !== false || strpos($nombre, 'disipul') !== false || strpos($nombre, 'discipl') !== false || strpos($nombre, 'disipl') !== false) {
                    return $idRol;
                }
            }

            if ($alias === 'maestro') {
                if (strpos($nombre, 'maestro') !== false || strpos($nombre, 'teacher') !== false) {
                    return $idRol;
                }
            }
        }

        return 0;
    }

    private function debeTenerDiscipuloSecundario(int $idPersona, int $idRolPrincipal, int $idRolDiscipulo): bool {
        if ($idPersona <= 0 || $idRolDiscipulo <= 0 || $idRolPrincipal <= 0) {
            return false;
        }

        if ($idRolPrincipal === $idRolDiscipulo) {
            return false;
        }

        require_once APP . '/Helpers/AccesoDiscipuloCapDestino.php';
        return AccesoDiscipuloCapDestino::personaInscrita($idPersona);
    }

    private function normalizarTexto(string $texto): string {
        $texto = strtolower(trim($texto));
        return strtr($texto, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ]);
    }
}
