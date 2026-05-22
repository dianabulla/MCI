<?php
/**
 * Modelo UsuarioAcceso
 *
 * Cuentas de acceso desacopladas de persona para roles administrativos
 * u operativos que no deben obligatoriamente existir en la tabla persona.
 */

require_once APP . '/Models/BaseModel.php';

class UsuarioAcceso extends BaseModel {
    protected $table = 'usuario_acceso';
    protected $primaryKey = 'Id_Usuario_Acceso';
    private $columnasCache = [];

    public function existeTabla() {
        try {
            $stmt = $this->db->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$this->table]);
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Autenticar cuenta de acceso desacoplada.
     */
    public function autenticar($usuario, $contrasena) {
        $sql = "SELECT ua.*, r.Nombre_Rol
                FROM {$this->table} ua
                LEFT JOIN rol r ON ua.Id_Rol = r.Id_Rol
                WHERE ua.Usuario = ?
                LIMIT 1";

        $rows = $this->query($sql, [$usuario]);
        if (empty($rows)) {
            return null;
        }

        $user = $rows[0];
        $estado = strtolower(trim((string)($user['Estado_Cuenta'] ?? 'Activo')));
        if ($estado === 'inactivo' || $estado === 'bloqueado') {
            return null;
        }

        $hashAlmacenado = (string)($user['Contrasena'] ?? '');
        if ($hashAlmacenado === '') {
            return null;
        }

        if (!password_verify($contrasena, $hashAlmacenado)) {
            return null;
        }

        return $user;
    }

    /**
     * Actualizar timestamp de ultimo acceso de la cuenta.
     */
    public function actualizarUltimoAcceso($idUsuarioAcceso) {
        $idUsuarioAcceso = (int)$idUsuarioAcceso;
        if ($idUsuarioAcceso <= 0) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET Ultimo_Acceso = NOW() WHERE {$this->primaryKey} = ?";
        return $this->execute($sql, [$idUsuarioAcceso]);
    }

    public function tieneColumna($nombreColumna) {
        $nombreColumna = trim((string)$nombreColumna);
        if ($nombreColumna === '' || !$this->existeTabla()) {
            return false;
        }
        if (array_key_exists($nombreColumna, $this->columnasCache)) {
            return (bool)$this->columnasCache[$nombreColumna];
        }
        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute([$nombreColumna]);
            $this->columnasCache[$nombreColumna] = (bool)$stmt->fetch();
            return $this->columnasCache[$nombreColumna];
        } catch (Exception $e) {
            $this->columnasCache[$nombreColumna] = false;
            return false;
        }
    }

    public function ensureAcuerdoConfidencialidadColumnsExist() {
        if (!$this->existeTabla()) {
            return false;
        }
        $ok = true;
        if (!$this->tieneColumna('Acuerdo_Confidencialidad_At')) {
            try {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Acuerdo_Confidencialidad_At DATETIME NULL AFTER Ultimo_Acceso");
                $this->columnasCache['Acuerdo_Confidencialidad_At'] = true;
            } catch (Exception $e) {
                error_log('No se pudo crear Acuerdo_Confidencialidad_At en usuario_acceso: ' . $e->getMessage());
                $ok = false;
            }
        }
        if (!$this->tieneColumna('Acuerdo_Confidencialidad_Version')) {
            try {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Acuerdo_Confidencialidad_Version VARCHAR(32) NULL AFTER Acuerdo_Confidencialidad_At");
                $this->columnasCache['Acuerdo_Confidencialidad_Version'] = true;
            } catch (Exception $e) {
                error_log('No se pudo crear Acuerdo_Confidencialidad_Version en usuario_acceso: ' . $e->getMessage());
                $ok = false;
            }
        }
        return $ok;
    }

    public function registrarAcuerdoConfidencialidad(int $idUsuarioAcceso, string $version): bool {
        $idUsuarioAcceso = (int)$idUsuarioAcceso;
        if ($idUsuarioAcceso <= 0 || !$this->tieneColumna('Acuerdo_Confidencialidad_At')) {
            return false;
        }
        $data = ['Acuerdo_Confidencialidad_At' => date('Y-m-d H:i:s')];
        if ($this->tieneColumna('Acuerdo_Confidencialidad_Version')) {
            $data['Acuerdo_Confidencialidad_Version'] = $version;
        }
        return $this->update($idUsuarioAcceso, $data);
    }

    public function tieneAcuerdoConfidencialidadVigente(int $idUsuarioAcceso, string $versionActual): bool {
        $idUsuarioAcceso = (int)$idUsuarioAcceso;
        if ($idUsuarioAcceso <= 0 || !$this->tieneColumna('Acuerdo_Confidencialidad_At')) {
            return true;
        }
        $cuenta = $this->getById($idUsuarioAcceso);
        if (empty($cuenta)) {
            return false;
        }
        $fecha = trim((string)($cuenta['Acuerdo_Confidencialidad_At'] ?? ''));
        if ($fecha === '') {
            return false;
        }
        if ($this->tieneColumna('Acuerdo_Confidencialidad_Version')) {
            return trim((string)($cuenta['Acuerdo_Confidencialidad_Version'] ?? '')) === $versionActual;
        }
        return true;
    }

    public function getAllWithRelations() {
        if (!$this->existeTabla()) {
            return [];
        }

        $sql = "SELECT
                    ua.Id_Usuario_Acceso,
                    ua.Usuario,
                    ua.Nombre_Mostrar,
                    ua.Estado_Cuenta,
                    ua.Ultimo_Acceso,
                    ua.Id_Rol,
                    ua.Id_Ministerio,
                    ua.Id_Persona,
                    r.Nombre_Rol,
                    m.Nombre_Ministerio,
                    p.Numero_Documento,
                    p.Nombre,
                    p.Apellido
                FROM {$this->table} ua
                LEFT JOIN rol r ON ua.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON ua.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona p ON ua.Id_Persona = p.Id_Persona
                ORDER BY ua.Usuario ASC, ua.Id_Usuario_Acceso ASC";

        return $this->query($sql);
    }

    public function existeUsuario($usuario, $excludeId = null) {
        if (!$this->existeTabla()) {
            return false;
        }

        $usuario = trim((string)$usuario);
        if ($usuario === '') {
            return false;
        }

        $sql = "SELECT {$this->primaryKey} FROM {$this->table} WHERE Usuario = ?";
        $params = [$usuario];

        $excludeId = $excludeId !== null ? (int)$excludeId : 0;
        if ($excludeId > 0) {
            $sql .= " AND {$this->primaryKey} <> ?";
            $params[] = $excludeId;
        }

        $sql .= ' LIMIT 1';
        return !empty($this->query($sql, $params));
    }

    public function getByPersonaId($idPersona) {
        if (!$this->existeTabla()) {
            return null;
        }

        $idPersona = (int)$idPersona;
        if ($idPersona <= 0) {
            return null;
        }

        $sql = "SELECT * FROM {$this->table} WHERE Id_Persona = ? LIMIT 1";
        $rows = $this->query($sql, [$idPersona]);
        return $rows[0] ?? null;
    }
}
