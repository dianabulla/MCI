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

    public function sincronizarRolPrincipal(int $idPersona, int $idRol): bool {
        if ($idPersona <= 0 || $idRol <= 0) {
            return false;
        }

        if (!$this->asegurarTabla()) {
            return false;
        }

        $sql = "INSERT INTO user_roles (Id_Persona, Id_Rol, Activo)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE Activo = VALUES(Activo), Actualizado_En = CURRENT_TIMESTAMP";

        return $this->execute($sql, [$idPersona, $idRol]);
    }

    public function asignarRol(int $idPersona, int $idRol): bool {
        return $this->sincronizarRolPrincipal($idPersona, $idRol);
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
