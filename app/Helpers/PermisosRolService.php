<?php

require_once APP . '/Config/Database.php';
require_once APP . '/Helpers/PermisosModulos.php';
require_once APP . '/Helpers/PermisosCatalogo.php';

/**
 * Inicialización y estado de la matriz de permisos por rol.
 */
class PermisosRolService {

    public static function rolTieneMatrizPersistida(int $idRol): bool {
        $idRol = (int)$idRol;
        if ($idRol <= 0) {
            return false;
        }

        try {
            $pdo = self::pdo();
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM permisos WHERE Id_Rol = ?');
            $stmt->execute([$idRol]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Crea filas en permisos (todo en 0) para todos los módulos activos.
     */
    public static function inicializarMatrizDenegada(int $idRol): int {
        $idRol = (int)$idRol;
        if ($idRol <= 0 || self::rolTieneMatrizPersistida($idRol)) {
            return 0;
        }

        $modulos = PermisosModulos::modulosActivos();
        if (empty($modulos)) {
            return 0;
        }

        $insertados = 0;
        try {
            $pdo = self::pdo();
            $sql = 'INSERT INTO permisos (Id_Rol, Modulo, Puede_Ver, Puede_Crear, Puede_Editar, Puede_Eliminar)
                    VALUES (?, ?, 0, 0, 0, 0)';
            $stmt = $pdo->prepare($sql);

            foreach ($modulos as $modulo) {
                $modulo = trim((string)$modulo);
                if ($modulo === '') {
                    continue;
                }
                try {
                    $stmt->execute([$idRol, $modulo]);
                    $insertados++;
                } catch (Throwable $e) {
                    // Ignorar duplicados u otros módulos ya insertados en carrera.
                    if (stripos($e->getMessage(), 'Duplicate') === false) {
                        throw $e;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('PermisosRolService::inicializarMatrizDenegada: ' . $e->getMessage());
        }

        return $insertados;
    }

    /**
     * Roles ministeriales históricos que aún pueden operar sin filas en permisos.
     */
    public static function esRolLegacyMinisterial(int $idRol, string $nombreRol = ''): bool {
        $nombreRol = mb_strtolower(trim($nombreRol), 'UTF-8');
        if ($nombreRol === 'ganar' || $nombreRol === 'celulas' || $nombreRol === 'células') {
            return true;
        }
        if ($nombreRol !== '' && strpos($nombreRol, 'ganar') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Indica si la sesión debe aplicar matriz estricta (denegar lo no concedido).
     */
    public static function permisosConfiguradosParaSesion(int $idRol, array $permisosNormalizados, string $nombreRol = ''): bool {
        $idRol = (int)$idRol;
        if ($idRol <= 0) {
            return false;
        }

        if (PermisosCatalogo::esRolAdministradorGlobal($idRol, $nombreRol)) {
            return !empty($permisosNormalizados);
        }

        if (!empty($permisosNormalizados) || self::rolTieneMatrizPersistida($idRol)) {
            return true;
        }

        return !self::esRolLegacyMinisterial($idRol, $nombreRol);
    }

    /**
     * Repara roles existentes sin filas en permisos (excepto legacy y admin).
     *
     * @param array<int, array<string, mixed>> $roles
     */
    public static function repararRolesSinMatriz(array $roles): void {
        foreach ($roles as $rol) {
            $idRol = (int)($rol['Id_Rol'] ?? 0);
            if ($idRol <= 0) {
                continue;
            }

            $nombre = trim((string)($rol['Nombre_Rol'] ?? ''));
            if (PermisosCatalogo::esRolProtegidoPermisos($idRol, $nombre)) {
                continue;
            }
            if (self::esRolLegacyMinisterial($idRol, $nombre)) {
                continue;
            }
            if (self::rolTieneMatrizPersistida($idRol)) {
                continue;
            }

            self::inicializarMatrizDenegada($idRol);
        }
    }

    private static function pdo(): PDO {
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

        throw new RuntimeException('No se encontró conexión PDO para permisos.');
    }
}
