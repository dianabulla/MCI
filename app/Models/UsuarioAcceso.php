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
     * Login oficial: solo usuario_acceso.
     * Si aún hay clave en persona y no hay cuenta ligada, la copia una vez y entra por usuario_acceso.
     */
    public function autenticarUnificado(string $usuario, string $contrasena): ?array {
        $usuario = trim($usuario);
        if ($usuario === '' || $contrasena === '' || !$this->existeTabla()) {
            return null;
        }

        $userAcceso = $this->autenticar($usuario, $contrasena);
        if ($userAcceso) {
            return $userAcceso;
        }

        if ($this->existeUsuario($usuario)) {
            return null;
        }

        require_once APP . '/Models/Persona.php';
        $personaModel = new Persona();
        if (!$personaModel->tieneColumnasLoginLegacy()) {
            return null;
        }
        $persona = $personaModel->autenticar($usuario, $contrasena);
        if (empty($persona)) {
            return null;
        }

        $idPersona = (int)($persona['Id_Persona'] ?? 0);
        if ($idPersona <= 0) {
            return null;
        }

        if (!empty($this->getByPersonaId($idPersona))) {
            return null;
        }

        $idRol = (int)($persona['Id_Rol'] ?? 0);
        if (!$this->provisionarParaPersona($idPersona, $usuario, $contrasena, $idRol)) {
            return null;
        }

        if ($personaModel->tieneColumnasLoginLegacy()) {
            $personaModel->update($idPersona, [
                'Usuario' => null,
                'Contrasena' => null,
            ]);
        }

        return $this->autenticar($usuario, $contrasena);
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

    private function esHashPassword($valor): bool {
        $valor = (string)$valor;
        return (bool)preg_match('/^\$2[ayb]\$\d{2}\$/', $valor);
    }

    /**
     * Personas con login en persona que aún no tienen fila vinculada en usuario_acceso.
     */
    public function listarCuentasLegacyPendientes(): array {
        if (!$this->existeTabla()) {
            return [];
        }

        require_once APP . '/Models/Persona.php';
        if (!(new Persona())->tieneColumnasLoginLegacy()) {
            return [];
        }

        $sql = "SELECT
                    p.Id_Persona,
                    p.Usuario,
                    p.Id_Rol,
                    p.Id_Ministerio,
                    p.Estado_Cuenta,
                    p.Nombre,
                    p.Apellido,
                    p.Numero_Documento,
                    r.Nombre_Rol
                FROM persona p
                LEFT JOIN rol r ON r.Id_Rol = p.Id_Rol
                LEFT JOIN {$this->table} ua_p ON ua_p.Id_Persona = p.Id_Persona
                WHERE p.Usuario IS NOT NULL
                  AND TRIM(p.Usuario) <> ''
                  AND p.Contrasena IS NOT NULL
                  AND TRIM(p.Contrasena) <> ''
                  AND ua_p.Id_Usuario_Acceso IS NULL
                ORDER BY p.Usuario ASC, p.Id_Persona ASC";

        return $this->query($sql);
    }

    public function contarLoginLegacyAunEnPersona(): int {
        if (!$this->existeTabla()) {
            return 0;
        }

        require_once APP . '/Models/Persona.php';
        if (!(new Persona())->tieneColumna('Usuario')) {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS total
                FROM persona p
                INNER JOIN {$this->table} ua ON ua.Id_Persona = p.Id_Persona
                WHERE p.Usuario IS NOT NULL
                  AND TRIM(p.Usuario) <> ''
                  AND LOWER(TRIM(p.Usuario)) = LOWER(TRIM(ua.Usuario))";
        $rows = $this->query($sql);
        return (int)($rows[0]['total'] ?? 0);
    }

    /**
     * Copia usuario/clave de persona a usuario_acceso. No borra el login viejo.
     * Idempotente: se puede repetir en producción con 31 o 300 cuentas.
     *
     * @return array{copiadas:int, omitidas:array, ya_ok:int}
     */
    public function migrarCuentasLegacyDesdePersona(): array {
        $reporte = [
            'copiadas' => 0,
            'ya_ok' => 0,
            'omitidas' => [],
        ];

        if (!$this->existeTabla()) {
            $reporte['omitidas'][] = 'La tabla usuario_acceso no existe.';
            return $reporte;
        }

        require_once APP . '/Models/Persona.php';
        if (!(new Persona())->tieneColumnasLoginLegacy()) {
            return $reporte;
        }

        $this->ensureAcuerdoConfidencialidadColumnsExist();
        $tieneAcuerdoAt = $this->tieneColumna('Acuerdo_Confidencialidad_At');
        $tieneAcuerdoVer = $this->tieneColumna('Acuerdo_Confidencialidad_Version');

        require_once APP . '/Models/Persona.php';
        $personaModel = new Persona();
        $selectAcuerdoAt = $personaModel->tieneColumna('Acuerdo_Confidencialidad_At')
            ? 'p.Acuerdo_Confidencialidad_At'
            : 'NULL AS Acuerdo_Confidencialidad_At';
        $selectAcuerdoVer = $personaModel->tieneColumna('Acuerdo_Confidencialidad_Version')
            ? 'p.Acuerdo_Confidencialidad_Version'
            : 'NULL AS Acuerdo_Confidencialidad_Version';

        $sql = "SELECT
                    p.Id_Persona,
                    p.Usuario,
                    p.Contrasena,
                    p.Id_Rol,
                    p.Id_Ministerio,
                    p.Estado_Cuenta,
                    p.Ultimo_Acceso,
                    p.Nombre,
                    p.Apellido,
                    {$selectAcuerdoAt},
                    {$selectAcuerdoVer}
                FROM persona p
                WHERE p.Usuario IS NOT NULL
                  AND TRIM(p.Usuario) <> ''
                  AND p.Contrasena IS NOT NULL
                  AND TRIM(p.Contrasena) <> ''
                ORDER BY p.Id_Persona ASC";

        $filas = $this->query($sql);
        foreach ($filas as $fila) {
            $idPersona = (int)($fila['Id_Persona'] ?? 0);
            $usuario = trim((string)($fila['Usuario'] ?? ''));
            $etiqueta = $usuario !== '' ? $usuario : ('persona #' . $idPersona);

            if ($idPersona <= 0 || $usuario === '') {
                continue;
            }

            if (!empty($this->getByPersonaId($idPersona))) {
                $reporte['ya_ok']++;
                continue;
            }

            $idRol = (int)($fila['Id_Rol'] ?? 0);
            if ($idRol <= 0) {
                $reporte['omitidas'][] = $etiqueta . ': no tiene rol de iglesia; asígnale rol y vuelve a migrar.';
                continue;
            }

            $choque = $this->query(
                "SELECT Id_Usuario_Acceso, Id_Persona FROM {$this->table} WHERE Usuario = ? LIMIT 1",
                [$usuario]
            );
            if (!empty($choque)) {
                $idPersonaChoque = (int)($choque[0]['Id_Persona'] ?? 0);
                if ($idPersonaChoque === $idPersona) {
                    $reporte['ya_ok']++;
                    continue;
                }
                $reporte['omitidas'][] = $etiqueta . ': ese usuario ya existe en usuario_acceso'
                    . ($idPersonaChoque > 0 ? ' (otra persona #' . $idPersonaChoque . ')' : ' (cuenta administrativa)');
                continue;
            }

            $hash = (string)($fila['Contrasena'] ?? '');
            if (!$this->esHashPassword($hash)) {
                $hash = password_hash($hash, PASSWORD_BCRYPT);
            }

            $idMinisterio = (int)($fila['Id_Ministerio'] ?? 0);
            $estado = trim((string)($fila['Estado_Cuenta'] ?? 'Activo')) ?: 'Activo';
            $nombreMostrar = trim((string)($fila['Nombre'] ?? '') . ' ' . (string)($fila['Apellido'] ?? ''));
            $ultimoAcceso = $fila['Ultimo_Acceso'] ?? null;
            if ($ultimoAcceso === '') {
                $ultimoAcceso = null;
            }

            $data = [
                'Usuario' => $usuario,
                'Contrasena' => $hash,
                'Nombre_Mostrar' => $nombreMostrar !== '' ? $nombreMostrar : $usuario,
                'Id_Rol' => $idRol,
                'Id_Ministerio' => $idMinisterio > 0 ? $idMinisterio : null,
                'Id_Persona' => $idPersona,
                'Estado_Cuenta' => $estado,
                'Ultimo_Acceso' => $ultimoAcceso,
            ];
            if ($tieneAcuerdoAt) {
                $data['Acuerdo_Confidencialidad_At'] = $fila['Acuerdo_Confidencialidad_At'] ?? null;
            }
            if ($tieneAcuerdoVer) {
                $data['Acuerdo_Confidencialidad_Version'] = $fila['Acuerdo_Confidencialidad_Version'] ?? null;
            }

            try {
                $this->create($data);
                $reporte['copiadas']++;
            } catch (Throwable $e) {
                if ($idMinisterio > 0) {
                    try {
                        $data['Id_Ministerio'] = null;
                        $this->create($data);
                        $reporte['copiadas']++;
                        continue;
                    } catch (Throwable $e2) {
                        $reporte['omitidas'][] = $etiqueta . ': ' . $e2->getMessage();
                        continue;
                    }
                }
                $reporte['omitidas'][] = $etiqueta . ': ' . $e->getMessage();
            }
        }

        return $reporte;
    }

    /**
     * Quita Usuario/Contrasena de persona solo si ya hay cuenta vinculada con el mismo usuario.
     * No tocar hasta comprobar login en producción.
     *
     * @return array{limpiadas:int}
     */
    public function desactivarLoginLegacyYaMigrado(): array {
        $limpiadas = 0;
        if (!$this->existeTabla()) {
            return ['limpiadas' => 0];
        }

        require_once APP . '/Models/Persona.php';
        $personaModel = new Persona();
        if (!$personaModel->tieneColumnasLoginLegacy()) {
            return ['limpiadas' => 0];
        }

        $sql = "SELECT p.Id_Persona, p.Usuario, ua.Usuario AS Usuario_Acceso
                FROM persona p
                INNER JOIN {$this->table} ua ON ua.Id_Persona = p.Id_Persona
                WHERE p.Usuario IS NOT NULL
                  AND TRIM(p.Usuario) <> ''";
        $filas = $this->query($sql);
        foreach ($filas as $fila) {
            $usuarioPersona = trim((string)($fila['Usuario'] ?? ''));
            $usuarioAcceso = trim((string)($fila['Usuario_Acceso'] ?? ''));
            if ($usuarioPersona === '' || strcasecmp($usuarioPersona, $usuarioAcceso) !== 0) {
                continue;
            }
            require_once APP . '/Models/Persona.php';
            (new Persona())->update((int)$fila['Id_Persona'], [
                'Usuario' => null,
                'Contrasena' => null,
            ]);
            $limpiadas++;
        }

        return ['limpiadas' => $limpiadas];
    }

    /**
     * Crea cuenta ministerial si la persona aún no tiene una. No pisa clave existente.
     */
    public function provisionarParaPersona(int $idPersona, string $usuario, string $clavePlano, int $idRol = 0): bool {
        if (!$this->existeTabla() || $idPersona <= 0) {
            return false;
        }

        $usuario = trim($usuario);
        $clavePlano = trim($clavePlano);
        if ($usuario === '' || $clavePlano === '') {
            return false;
        }

        if (!empty($this->getByPersonaId($idPersona))) {
            return true;
        }

        if ($this->existeUsuario($usuario)) {
            return false;
        }

        if ($idRol <= 0) {
            require_once APP . '/Models/Persona.php';
            $persona = (new Persona())->getById($idPersona);
            $idRol = (int)($persona['Id_Rol'] ?? 0);
        }
        if ($idRol <= 0) {
            return false;
        }

        require_once APP . '/Models/Persona.php';
        $persona = (new Persona())->getById($idPersona);
        $idMinisterio = (int)($persona['Id_Ministerio'] ?? 0);
        $nombreMostrar = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));

        $data = [
            'Usuario' => $usuario,
            'Contrasena' => $this->esHashPassword($clavePlano) ? $clavePlano : password_hash($clavePlano, PASSWORD_BCRYPT),
            'Nombre_Mostrar' => $nombreMostrar !== '' ? $nombreMostrar : $usuario,
            'Id_Rol' => $idRol,
            'Id_Ministerio' => $idMinisterio > 0 ? $idMinisterio : null,
            'Id_Persona' => $idPersona,
            'Estado_Cuenta' => 'Activo',
        ];

        try {
            return (bool)$this->create($data);
        } catch (Throwable $e) {
            if ($idMinisterio > 0) {
                try {
                    $data['Id_Ministerio'] = null;
                    return (bool)$this->create($data);
                } catch (Throwable $e2) {
                    error_log('No se pudo provisionar usuario_acceso: ' . $e2->getMessage());
                    return false;
                }
            }
            error_log('No se pudo provisionar usuario_acceso: ' . $e->getMessage());
            return false;
        }
    }
}
