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
