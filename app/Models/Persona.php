<?php
/**
 * Modelo Persona
 */

require_once APP . '/Models/BaseModel.php';

class Persona extends BaseModel {
    protected $table = 'persona';
    protected $primaryKey = 'Id_Persona';
    private $columnasCache = [];

    public function tieneColumna($columna) {
        $columna = trim((string)$columna);
        if ($columna === '') {
            return false;
        }

        if (array_key_exists($columna, $this->columnasCache)) {
            return $this->columnasCache[$columna];
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute([$columna]);
            $existe = (bool)$stmt->fetch();
            $this->columnasCache[$columna] = $existe;
            return $existe;
        } catch (Exception $e) {
            error_log('Error verificando columna en persona: ' . $e->getMessage());
            $this->columnasCache[$columna] = false;
            return false;
        }
    }

    public function ensureProcesoColumnExists() {
        if ($this->tieneColumna('Proceso')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Proceso ENUM('Ganar','Consolidar','Discipular','Enviar') NULL AFTER Tipo_Reunion";
            $this->db->exec($sql);
            $this->columnasCache['Proceso'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Proceso en persona: ' . $e->getMessage());
            $this->columnasCache['Proceso'] = false;
            return false;
        }
    }

    public function ensureOrigenGanarColumnExists() {
        if ($this->tieneColumna('Origen_Ganar')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Origen_Ganar ENUM('Domingo','Celula') NULL AFTER Proceso";
            $this->db->exec($sql);
            $this->columnasCache['Origen_Ganar'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Origen_Ganar en persona: ' . $e->getMessage());
            $this->columnasCache['Origen_Ganar'] = false;
            return false;
        }
    }

    public function ensureConvencionColumnExists() {
        if ($this->tieneColumna('Convencion')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Convencion ENUM('Convencion Enero','Convencion Mujeres','Convencion Jovenes','Convencion Hombres') NULL AFTER Tipo_Reunion";
            $this->db->exec($sql);
            $this->columnasCache['Convencion'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Convencion en persona: ' . $e->getMessage());
            $this->columnasCache['Convencion'] = false;
            return false;
        }
    }

    public function ensureEscaleraChecklistColumnExists() {
        if ($this->tieneColumna('Escalera_Checklist')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Escalera_Checklist TEXT NULL AFTER Proceso";
            $this->db->exec($sql);
            $this->columnasCache['Escalera_Checklist'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Escalera_Checklist en persona: ' . $e->getMessage());
            $this->columnasCache['Escalera_Checklist'] = false;
            return false;
        }
    }

    public function ensureFechaAsignacionLiderColumnExists() {
        if ($this->tieneColumna('Fecha_Asignacion_Lider')) {
            return true;
        }

        try {
            $sql = "ALTER TABLE {$this->table} ADD COLUMN Fecha_Asignacion_Lider DATETIME NULL AFTER Id_Lider";
            $this->db->exec($sql);
            $this->columnasCache['Fecha_Asignacion_Lider'] = true;
            return true;
        } catch (Exception $e) {
            error_log('No se pudo crear columna Fecha_Asignacion_Lider en persona: ' . $e->getMessage());
            $this->columnasCache['Fecha_Asignacion_Lider'] = false;
            return false;
        }
    }

    public function puedeEditarEscaleraPorRol($idPersona, $filtroRol) {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0) {
            return false;
        }

        $sql = "SELECT 1
                FROM persona p
                WHERE p.Id_Persona = ?
                AND {$filtroRol}
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idPersona]);
        return (bool)$stmt->fetchColumn();
    }

    public function updateEscaleraChecklistYProceso($idPersona, $checklistJson, $proceso = null) {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0) {
            return false;
        }

        if ($this->tieneColumna('Proceso')) {
            $sql = "UPDATE {$this->table} SET Escalera_Checklist = ?, Proceso = ? WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$checklistJson, $proceso, $idPersona]);
        }

        $sql = "UPDATE {$this->table} SET Escalera_Checklist = ? WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$checklistJson, $idPersona]);
    }

    /**
     * Obtener personas con líder y ministerio asignados que superaron el límite
     * de horas para registrar el primer contacto.
     */
    public function getCandidatosReasignacionPrimerContacto($horasLimite = 48) {
        $horasLimite = max(1, (int)$horasLimite);

        // Regla de seguridad: sin Fecha_Asignacion_Lider no se ejecuta
        // la reasignación automática para evitar efectos retroactivos.
        if (!$this->tieneColumna('Fecha_Asignacion_Lider')) {
            return [];
        }

        $campoTiempoControl = "p.Fecha_Asignacion_Lider";

        $sql = "SELECT p.Id_Persona, p.Id_Lider, p.Id_Ministerio, p.Fecha_Registro, p.Fecha_Asignacion_Lider, p.Escalera_Checklist, p.Proceso, p.Estado_Cuenta
            FROM {$this->table} p
            WHERE p.Id_Lider IS NOT NULL
              AND p.Id_Ministerio IS NOT NULL
              AND {$campoTiempoControl} IS NOT NULL
              AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
              AND (p.Proceso = 'Ganar' OR p.Proceso IS NULL OR p.Proceso = '')
              AND TIMESTAMPDIFF(HOUR, {$campoTiempoControl}, NOW()) >= ?
            ORDER BY {$campoTiempoControl} ASC, p.Id_Persona ASC";

        return $this->query($sql, [$horasLimite]);
    }

    /**
     * Quitar asignación de líder/ministerio y marcar persona como reasignada.
     */
    public function aplicarReasignacionAutomatica($idPersona, $checklistJson, $proceso = 'Ganar') {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0) {
            return false;
        }

        $camposUpdate = [
            'Id_Lider = NULL',
            'Id_Ministerio = NULL',
            'Escalera_Checklist = ?'
        ];

        if ($this->tieneColumna('Fecha_Asignacion_Lider')) {
            $camposUpdate[] = 'Fecha_Asignacion_Lider = NULL';
        }

        if ($this->tieneColumna('Proceso')) {
            $camposConProceso = $camposUpdate;
            $camposConProceso[] = 'Proceso = ?';

            $sql = "UPDATE {$this->table}
                    SET " . implode(",\n                        ", $camposConProceso) . "
                    WHERE {$this->primaryKey} = ?
                      AND Id_Lider IS NOT NULL
                      AND Id_Ministerio IS NOT NULL";

            return $this->execute($sql, [$checklistJson, $proceso, $idPersona]);
        }

        $sql = "UPDATE {$this->table}
                SET " . implode(",\n                    ", $camposUpdate) . "
                WHERE {$this->primaryKey} = ?
                  AND Id_Lider IS NOT NULL
                  AND Id_Ministerio IS NOT NULL";

        return $this->execute($sql, [$checklistJson, $idPersona]);
    }

    /**
     * Obtener persona por ID con relaciones
     */
    public function getById($id) {
        $sql = "SELECT p.*, 
                c.Nombre_Celula, 
                r.Nombre_Rol, 
                m.Nombre_Ministerio,
                CONCAT(lid.Nombre, ' ', lid.Apellido) AS Nombre_Lider
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                WHERE p.{$this->primaryKey} = ?";
        $result = $this->query($sql, [$id]);
        return $result[0] ?? null;
    }

    /**
     * Obtener todas las personas con sus relaciones
     */
    public function getAllWithRelations() {
        $sql = "SELECT p.*, 
                c.Nombre_Celula, 
                r.Nombre_Rol, 
                m.Nombre_Ministerio,
                CONCAT(lid.Nombre, ' ', lid.Apellido) AS Nombre_Lider
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC";
        return $this->query($sql);
    }

    /**
     * Obtener personas con filtros
     */
    public function getWithFilters($idMinisterio = null, $idLider = null) {
        $sql = "SELECT p.*, 
                c.Nombre_Celula, 
                r.Nombre_Rol, 
                m.Nombre_Ministerio,
                CONCAT(lid.Nombre, ' ', lid.Apellido) AS Nombre_Lider
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                WHERE 1=1";
        
        $params = [];
        
        if ($idMinisterio !== null && $idMinisterio !== '') {
            if ($idMinisterio == '0') {
                // Filtrar por personas SIN ministerio
                $sql .= " AND p.Id_Ministerio IS NULL";
            } else {
                // Filtrar por ministerio específico
                $sql .= " AND p.Id_Ministerio = ?";
                $params[] = $idMinisterio;
            }
        }
        
        if ($idLider !== null && $idLider !== '') {
            if ($idLider == '0') {
                // Filtrar por personas SIN líder
                $sql .= " AND p.Id_Lider IS NULL";
            } else {
                // Filtrar por líder específico
                $sql .= " AND p.Id_Lider = ?";
                $params[] = $idLider;
            }
        }
        
        $sql .= " ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC";
        
        return $this->query($sql, $params);
    }

    /**
     * Buscar personas por nombre o apellido
     */
    public function search($term) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Nombre LIKE ? OR Apellido LIKE ?
                ORDER BY Apellido, Nombre";
        $searchTerm = "%$term%";
        return $this->query($sql, [$searchTerm, $searchTerm]);
    }

    /**
     * Obtener personas por célula
     */
    public function getByCelula($idCelula) {
        $sql = "SELECT * FROM {$this->table} WHERE Id_Celula = ? ORDER BY Apellido, Nombre";
        return $this->query($sql, [$idCelula]);
    }

    /**
     * Obtener solo personas activas (para reportes/gráficos/células)
     */
    public function getAllActivos() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL
                ORDER BY Apellido, Nombre";
        return $this->query($sql);
    }

    /**
     * Obtener personas activas con aislamiento de rol
     */
    public function getAllActivosWithRole($filtroRol) {
        $sql = "SELECT p.*
                FROM persona p
                WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND $filtroRol
                ORDER BY p.Apellido, p.Nombre";
        return $this->query($sql);
    }

    /**
     * Obtener personas por ministerio
     */
    public function getByMinisterio($idMinisterio) {
        $sql = "SELECT * FROM {$this->table} WHERE Id_Ministerio = ? ORDER BY Apellido, Nombre";
        return $this->query($sql, [$idMinisterio]);
    }

    /**
     * Obtener personas por rol
     */
    public function getByRol($idRol) {
        $sql = "SELECT * FROM {$this->table} WHERE Id_Rol = ? ORDER BY Apellido, Nombre";
        return $this->query($sql, [$idRol]);
    }

    /**
     * Obtener personas con rol Líder de Célula y Líder de 12
     */
    public function getLideresYPastores() {
        $sql = "SELECT * FROM {$this->table} WHERE Id_Rol IN (3, 8) ORDER BY Apellido, Nombre";
        return $this->query($sql);
    }

    /**
     * Obtener lideres por ministerio (roles 3 y 8)
     */
    public function getLideresByMinisterio($idMinisterio) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Id_Ministerio = ? AND Id_Rol IN (3, 8)
                ORDER BY Apellido, Nombre";
        return $this->query($sql, [$idMinisterio]);
    }

    /**
     * Obtener personas con rol Líder de 12
     */
    public function getLideres12() {
        $sql = "SELECT * FROM {$this->table} WHERE Id_Rol = 8 ORDER BY Apellido, Nombre";
        return $this->query($sql);
    }

    /**
     * Obtener estadísticas de almas ganadas por ministerio
     * Agrupa por ministerio y género en un rango de fechas
     */
    public function getAlmasGanadasPorMinisterio($fechaInicio, $fechaFin) {
        $sql = "SELECT 
                    COALESCE(m.Nombre_Ministerio, 'Sin Ministerio') as Nombre_Ministerio,
                    m.Id_Ministerio,
                    COUNT(*) as Total,
                    SUM(CASE WHEN p.Genero = 'Hombre' THEN 1 ELSE 0 END) as Hombres,
                    SUM(CASE WHEN p.Genero = 'Mujer' THEN 1 ELSE 0 END) as Mujeres,
                    SUM(CASE WHEN p.Genero = 'Joven Hombre' THEN 1 ELSE 0 END) as Jovenes_Hombres,
                    SUM(CASE WHEN p.Genero = 'Joven Mujer' THEN 1 ELSE 0 END) as Jovenes_Mujeres
                FROM persona p
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                GROUP BY m.Id_Ministerio, m.Nombre_Ministerio
                HAVING Total > 0
                ORDER BY m.Nombre_Ministerio";
        return $this->query($sql, [$fechaInicio, $fechaFin]);
    }

    /**
     * Autenticar usuario
     */
    public function autenticar($usuario, $contrasena) {
        $sql = "SELECT p.*, r.Nombre_Rol, p.Id_Ministerio 
                FROM persona p 
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol 
                WHERE p.Usuario = ?";
        $result = $this->query($sql, [$usuario]);

        if (!empty($result)) {
            $user = $result[0];

            $hashAlmacenado = $user['Contrasena'] ?? '';
            if ($hashAlmacenado === '') {
                return null;
            }

            // 1) Validación estándar con hash
            if (password_verify($contrasena, $hashAlmacenado)) {
                return $user;
            }

            // 2) Compatibilidad temporal con contraseñas antiguas en texto plano
            if (hash_equals((string) $hashAlmacenado, (string) $contrasena)) {
                $this->setUsuario($user['Id_Persona'], $user['Usuario'], $contrasena);
                return $user;
            }
        }

        return null;
    }

    /**
     * Actualizar último acceso
     */
    public function actualizarUltimoAcceso($idPersona) {
        try {
            $sql = "UPDATE persona SET Ultimo_Acceso = NOW() WHERE Id_Persona = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$idPersona]);
        } catch (Exception $e) {
            error_log("Error actualizando último acceso: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener permisos por rol
     */
    public function getPermisosPorRol($idRol) {
        $sql = "SELECT * FROM permisos WHERE Id_Rol = ?";
        return $this->query($sql, [$idRol]);
    }

    /**
     * Crear o actualizar usuario
     */
    public function setUsuario($idPersona, $usuario, $contrasena = null) {
        if ($contrasena) {
            // Hash de la contraseña
            $hash = password_hash($contrasena, PASSWORD_BCRYPT);
            $sql = "UPDATE persona SET Usuario = ?, Contrasena = ? WHERE Id_Persona = ?";
            return $this->execute($sql, [$usuario, $hash, $idPersona]);
        } else {
            $sql = "UPDATE persona SET Usuario = ? WHERE Id_Persona = ?";
            return $this->execute($sql, [$usuario, $idPersona]);
        }
    }

    /**
     * Cambiar estado de cuenta
     */
    public function cambiarEstado($idPersona, $estado) {
        $sql = "UPDATE persona SET Estado_Cuenta = ? WHERE Id_Persona = ?";
        return $this->execute($sql, [$estado, $idPersona]);
    }

    /**
     * Obtener todas las personas con aislamiento de rol
     */
    public function getAllWithRole($filtroRol, $soloGanar = false, $estadoCuenta = null, $idCelula = null, $proceso = null, $origen = null) {
        $sql = "SELECT p.*, 
                c.Nombre_Celula, 
                r.Nombre_Rol, 
                m.Nombre_Ministerio,
                CONCAT(lid.Nombre, ' ', lid.Apellido) AS Nombre_Lider
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                WHERE $filtroRol";

        if ($soloGanar === true) {
            $sql .= " AND (p.Id_Ministerio IS NULL OR p.Id_Lider IS NULL OR p.Id_Celula IS NULL)";
        } elseif ($soloGanar === false) {
            $sql .= " AND p.Id_Ministerio IS NOT NULL AND p.Id_Lider IS NOT NULL AND p.Id_Celula IS NOT NULL";
        }

        $params = [];

        if ($idCelula !== null && $idCelula !== '') {
            if ((string)$idCelula === '0') {
                $sql .= " AND p.Id_Celula IS NULL";
            } else {
                $sql .= " AND p.Id_Celula = ?";
                $params[] = $idCelula;
            }
        }

        if ($estadoCuenta !== null && $estadoCuenta !== '') {
            if ($estadoCuenta === 'Activo') {
                $sql .= " AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)";
            } else {
                $sql .= " AND p.Estado_Cuenta = ?";
                $params[] = $estadoCuenta;
            }
        }

        if ($proceso !== null && $proceso !== '') {
            $sql .= " AND p.Proceso = ?";
            $params[] = $proceso;
        }

        if ($origen !== null && $origen !== '') {
            $tipoReunionExpr = "LOWER(TRIM(COALESCE(p.Tipo_Reunion, '')))";
            $invitadoExpr = "TRIM(COALESCE(p.Invitado_Por, ''))";

            if ($origen === 'celula') {
                $sql .= " AND {$tipoReunionExpr} LIKE '%celula%'";
            } elseif ($origen === 'domingo') {
                // Ganados en iglesia: llegaron domingo y fueron invitados por alguien.
                $sql .= " AND {$tipoReunionExpr} LIKE '%domingo%' AND {$invitadoExpr} <> ''";
            } elseif ($origen === 'asignados') {
                // Asignados: llegaron domingo y no registran invitador.
                $sql .= " AND {$tipoReunionExpr} LIKE '%domingo%' AND {$invitadoExpr} = ''";
            }
        }

        $sql .= "
                ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC";
        return $this->query($sql, $params);
    }

    /**
     * Obtener personas con filtros y aislamiento de rol
     */
    public function getWithFiltersAndRole($filtroRol, $idMinisterio = null, $idLider = null, $soloGanar = false, $estadoCuenta = null, $idCelula = null, $proceso = null, $origen = null) {
        $sql = "SELECT p.*, 
                c.Nombre_Celula, 
                r.Nombre_Rol, 
                m.Nombre_Ministerio,
                CONCAT(lid.Nombre, ' ', lid.Apellido) AS Nombre_Lider
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
                WHERE $filtroRol";

        if ($soloGanar === true) {
            $sql .= " AND (p.Id_Ministerio IS NULL OR p.Id_Lider IS NULL OR p.Id_Celula IS NULL)";
        } elseif ($soloGanar === false) {
            $sql .= " AND p.Id_Ministerio IS NOT NULL AND p.Id_Lider IS NOT NULL AND p.Id_Celula IS NOT NULL";
        }
        
        $params = [];
        
        if ($idMinisterio !== null && $idMinisterio !== '') {
            if ($idMinisterio == '0') {
                $sql .= " AND p.Id_Ministerio IS NULL";
            } else {
                $sql .= " AND p.Id_Ministerio = ?";
                $params[] = $idMinisterio;
            }
        }
        
        if ($idLider !== null && $idLider !== '') {
            if ($idLider == '0') {
                $sql .= " AND p.Id_Lider IS NULL";
            } else {
                $sql .= " AND p.Id_Lider = ?";
                $params[] = $idLider;
            }
        }

        if ($idCelula !== null && $idCelula !== '') {
            if ((string)$idCelula === '0') {
                $sql .= " AND p.Id_Celula IS NULL";
            } else {
                $sql .= " AND p.Id_Celula = ?";
                $params[] = $idCelula;
            }
        }

        if ($estadoCuenta !== null && $estadoCuenta !== '') {
            if ($estadoCuenta === 'Activo') {
                $sql .= " AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)";
            } else {
                $sql .= " AND p.Estado_Cuenta = ?";
                $params[] = $estadoCuenta;
            }
        }

        if ($proceso !== null && $proceso !== '') {
            $sql .= " AND p.Proceso = ?";
            $params[] = $proceso;
        }

        if ($origen !== null && $origen !== '') {
            $tipoReunionExpr = "LOWER(TRIM(COALESCE(p.Tipo_Reunion, '')))";
            $invitadoExpr = "TRIM(COALESCE(p.Invitado_Por, ''))";

            if ($origen === 'celula') {
                $sql .= " AND {$tipoReunionExpr} LIKE '%celula%'";
            } elseif ($origen === 'domingo') {
                // Ganados en iglesia: llegaron domingo y fueron invitados por alguien.
                $sql .= " AND {$tipoReunionExpr} LIKE '%domingo%' AND {$invitadoExpr} <> ''";
            } elseif ($origen === 'asignados') {
                // Asignados: llegaron domingo y no registran invitador.
                $sql .= " AND {$tipoReunionExpr} LIKE '%domingo%' AND {$invitadoExpr} = ''";
            }
        }

        $sql .= " ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC";
        
        return $this->query($sql, $params);
    }

    /**
     * Obtener almas ganadas por ministerio con aislamiento de rol
     */
    public function getAlmasGanadasPorMinisterioWithRole($fechaInicio, $fechaFin, $filtroRol, $idMinisterio = '', $idLider = '') {
        $sql = "SELECT 
                    COALESCE(m.Nombre_Ministerio, 'Sin Ministerio') as Nombre_Ministerio,
                    m.Id_Ministerio,
                    COUNT(*) as Total,
                    SUM(CASE WHEN p.Genero = 'Hombre' THEN 1 ELSE 0 END) as Hombres,
                    SUM(CASE WHEN p.Genero = 'Mujer' THEN 1 ELSE 0 END) as Mujeres,
                    SUM(CASE WHEN p.Genero = 'Joven Hombre' THEN 1 ELSE 0 END) as Jovenes_Hombres,
                    SUM(CASE WHEN p.Genero = 'Joven Mujer' THEN 1 ELSE 0 END) as Jovenes_Mujeres
                FROM persona p
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND $filtroRol";

        $params = [$fechaInicio, $fechaFin];

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $sql .= " AND p.Id_Ministerio = ?";
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $sql .= " AND p.Id_Lider = ?";
            $params[] = (int)$idLider;
        }

        $sql .= "
                GROUP BY m.Id_Ministerio, m.Nombre_Ministerio
                HAVING Total > 0
                ORDER BY m.Nombre_Ministerio";
        return $this->query($sql, $params);
    }

    /**
     * Resumen de etapas del proceso de ganar por período.
     */
    public function getResumenProcesoGanarWithRole($fechaInicio, $fechaFin, $filtroRol, $idCelula = '', $idMinisterio = '', $idLider = '') {
        if (!$this->tieneColumna('Proceso')) {
            return [
                'Ganar' => 0,
                'Consolidar' => 0,
                'Discipular' => 0,
                'Enviar' => 0,
                'Sin_Proceso' => 0,
                'Total' => 0
            ];
        }

        $sql = "SELECT
                    SUM(CASE WHEN p.Proceso = 'Ganar' THEN 1 ELSE 0 END) AS Ganar,
                    SUM(CASE WHEN p.Proceso = 'Consolidar' THEN 1 ELSE 0 END) AS Consolidar,
                    SUM(CASE WHEN p.Proceso = 'Discipular' THEN 1 ELSE 0 END) AS Discipular,
                    SUM(CASE WHEN p.Proceso = 'Enviar' THEN 1 ELSE 0 END) AS Enviar,
                    SUM(CASE WHEN p.Proceso IS NULL OR p.Proceso = '' THEN 1 ELSE 0 END) AS Sin_Proceso,
                    COUNT(*) AS Total
                FROM persona p
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND $filtroRol";

        $params = [$fechaInicio, $fechaFin];

        if ($idCelula !== null && $idCelula !== '') {
            if ((string)$idCelula === '0') {
                $sql .= " AND p.Id_Celula IS NULL";
            } else {
                $sql .= " AND p.Id_Celula = ?";
                $params[] = (int)$idCelula;
            }
        }

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $sql .= " AND p.Id_Ministerio = ?";
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $sql .= " AND p.Id_Lider = ?";
            $params[] = (int)$idLider;
        }

        $rows = $this->query($sql, $params);
        $row = $rows[0] ?? [];

        return [
            'Ganar' => (int)($row['Ganar'] ?? 0),
            'Consolidar' => (int)($row['Consolidar'] ?? 0),
            'Discipular' => (int)($row['Discipular'] ?? 0),
            'Enviar' => (int)($row['Enviar'] ?? 0),
            'Sin_Proceso' => (int)($row['Sin_Proceso'] ?? 0),
            'Total' => (int)($row['Total'] ?? 0)
        ];
    }

    public function getResumenGanadosOrigenWithRole($fechaInicio, $fechaFin, $filtroRol, $idMinisterio = '', $idLider = '') {
        $tipoReunionExpr = "LOWER(TRIM(COALESCE(p.Tipo_Reunion, '')))";
        $invitadoExpr = "TRIM(COALESCE(p.Invitado_Por, ''))";

        $sql = "SELECT
                    SUM(CASE WHEN {$tipoReunionExpr} LIKE '%celula%' THEN 1 ELSE 0 END) AS Ganados_Celula,
                    SUM(CASE WHEN {$tipoReunionExpr} LIKE '%domingo%' THEN 1 ELSE 0 END) AS Ganados_Domingo,
                    SUM(CASE WHEN {$tipoReunionExpr} LIKE '%domingo%' AND {$invitadoExpr} = '' THEN 1 ELSE 0 END) AS Asignados,
                    COUNT(*) AS Total
                FROM persona p
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND $filtroRol";

        $params = [$fechaInicio, $fechaFin];

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $sql .= " AND p.Id_Ministerio = ?";
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $sql .= " AND p.Id_Lider = ?";
            $params[] = (int)$idLider;
        }

        $rows = $this->query($sql, $params);
        $row = $rows[0] ?? [];

        return [
            'Ganados_Celula' => (int)($row['Ganados_Celula'] ?? 0),
            'Ganados_Domingo' => (int)($row['Ganados_Domingo'] ?? 0),
            'Asignados' => (int)($row['Asignados'] ?? 0),
            'Total' => (int)($row['Total'] ?? 0)
        ];
    }

    /**
     * Resumen por ministerio para el reporte de fin de semana anterior.
     * Ganados: domingo con invitador.
     * Asignados: domingo sin invitador.
     * Por verificar: domingo sin líder asignado.
     */
    public function getResumenGanadosFinSemanaAnteriorPorMinisterioWithRole($fechaInicio, $fechaFin, $filtroRol, $idMinisterio = '', $idLider = '') {
        $tipoReunionExpr = "LOWER(TRIM(COALESCE(p.Tipo_Reunion, '')))";
        $invitadoExpr = "TRIM(COALESCE(p.Invitado_Por, ''))";

        $sql = "SELECT
                    COALESCE(m.Nombre_Ministerio, 'Sin ministerio') AS Nombre_Ministerio,
                    SUM(CASE WHEN {$tipoReunionExpr} LIKE '%domingo%' AND {$invitadoExpr} <> '' THEN 1 ELSE 0 END) AS Ganados,
                    SUM(CASE WHEN {$tipoReunionExpr} LIKE '%domingo%' AND {$invitadoExpr} = '' THEN 1 ELSE 0 END) AS Asignados,
                    SUM(CASE WHEN {$tipoReunionExpr} LIKE '%domingo%' AND (p.Id_Lider IS NULL OR p.Id_Lider = 0) THEN 1 ELSE 0 END) AS Por_Verificar,
                    SUM(CASE WHEN {$tipoReunionExpr} LIKE '%domingo%' THEN 1 ELSE 0 END) AS Total_Domingo
                FROM persona p
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND (p.Proceso = 'Ganar' OR p.Proceso IS NULL OR p.Proceso = '')
                AND $filtroRol";

        $params = [$fechaInicio, $fechaFin];

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $sql .= " AND p.Id_Ministerio = ?";
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $sql .= " AND p.Id_Lider = ?";
            $params[] = (int)$idLider;
        }

        $sql .= "
                GROUP BY m.Id_Ministerio, m.Nombre_Ministerio
                HAVING Total_Domingo > 0
                ORDER BY m.Nombre_Ministerio";

        $rows = $this->query($sql, $params);

        $resultadoRows = [];
        $totales = [
            'ganados' => 0,
            'asignados' => 0,
            'por_verificar' => 0,
            'total_domingo' => 0
        ];

        foreach ($rows as $row) {
            $item = [
                'ministerio' => (string)($row['Nombre_Ministerio'] ?? 'Sin ministerio'),
                'ganados' => (int)($row['Ganados'] ?? 0),
                'asignados' => (int)($row['Asignados'] ?? 0),
                'por_verificar' => (int)($row['Por_Verificar'] ?? 0),
                'total_domingo' => (int)($row['Total_Domingo'] ?? 0)
            ];

            $resultadoRows[] = $item;
            $totales['ganados'] += $item['ganados'];
            $totales['asignados'] += $item['asignados'];
            $totales['por_verificar'] += $item['por_verificar'];
            $totales['total_domingo'] += $item['total_domingo'];
        }

        return [
            'rows' => $resultadoRows,
            'totales' => $totales
        ];
    }

    public function getAlmasGanadasPorEdadesWithRole($fechaInicio, $fechaFin, $filtroRol, $idMinisterio = '', $idLider = '') {
        $sql = "SELECT
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) BETWEEN 3 AND 8 THEN 1 ELSE 0 END) AS Kids,
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) BETWEEN 9 AND 12 THEN 1 ELSE 0 END) AS Teens,
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) BETWEEN 13 AND 17 THEN 1 ELSE 0 END) AS Rocas,
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) BETWEEN 18 AND 30 THEN 1 ELSE 0 END) AS Jovenes,
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) BETWEEN 31 AND 59 THEN 1 ELSE 0 END) AS Adultos,
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) >= 61 THEN 1 ELSE 0 END) AS Adultos_Mayores,
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) = 60 THEN 1 ELSE 0 END) AS Adultos_Mayores_60,
                    SUM(CASE WHEN COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) IS NULL OR COALESCE(p.Edad, TIMESTAMPDIFF(YEAR, p.Fecha_Nacimiento, CURDATE())) < 3 THEN 1 ELSE 0 END) AS Sin_Dato
                FROM persona p
                WHERE DATE(p.Fecha_Registro) BETWEEN ? AND ?
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND $filtroRol";

        $params = [$fechaInicio, $fechaFin];

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $sql .= " AND p.Id_Ministerio = ?";
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $sql .= " AND p.Id_Lider = ?";
            $params[] = (int)$idLider;
        }

        $rows = $this->query($sql, $params);
        $row = $rows[0] ?? [];

        return [
            'Kids' => (int)($row['Kids'] ?? 0),
            'Teens' => (int)($row['Teens'] ?? 0),
            'Rocas' => (int)($row['Rocas'] ?? 0),
            'Jovenes' => (int)($row['Jovenes'] ?? 0),
            'Adultos' => (int)($row['Adultos'] ?? 0),
            'Adultos_Mayores' => (int)($row['Adultos_Mayores'] ?? 0) + (int)($row['Adultos_Mayores_60'] ?? 0),
            'Sin_Dato' => (int)($row['Sin_Dato'] ?? 0)
        ];
    }

    /**
     * Obtener miembros activos agrupables por múltiples células
     */
    public function getActivosByCelulaIds(array $celulaIds) {
        $celulaIds = array_values(array_filter(array_map('intval', $celulaIds), function ($id) {
            return $id > 0;
        }));

        if (empty($celulaIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($celulaIds), '?'));

        $sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Numero_Documento, p.Telefono, p.Id_Celula
                FROM persona p
                WHERE p.Id_Celula IN ($placeholders)
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                ORDER BY p.Id_Celula, p.Apellido, p.Nombre";

        return $this->query($sql, $celulaIds);
    }

    /**
     * Obtener miembros activos agrupables por múltiples ministerios
     */
    public function getActivosByMinisterioIds(array $ministerioIds, $idRol = null) {
        $ministerioIds = array_values(array_filter(array_map('intval', $ministerioIds), function ($id) {
            return $id > 0;
        }));

        if (empty($ministerioIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ministerioIds), '?'));

        $sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Numero_Documento, p.Telefono, p.Id_Ministerio,
                   p.Id_Rol, p.Tipo_Reunion, p.Fecha_Registro, p.Proceso, p.Escalera_Checklist, p.Convencion,
                   c.Nombre_Celula,
                   r.Nombre_Rol
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
            LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                WHERE p.Id_Ministerio IN ($placeholders)
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)";

        $params = $ministerioIds;
        $idRol = $idRol !== null ? (int)$idRol : null;
        if ($idRol !== null && $idRol > 0) {
            $sql .= " AND p.Id_Rol = ?";
            $params[] = $idRol;
        }

        $sql .= "
                ORDER BY p.Id_Ministerio, p.Apellido, p.Nombre";

        return $this->query($sql, $params);
    }

    /**
     * Total de líderes de célula visibles según aislamiento.
     */
    public function getTotalLideresCelulaWithRole($filtroRol) {
        $sql = "SELECT COUNT(*) AS Total
                FROM persona p
                WHERE (
                    EXISTS (
                        SELECT 1 FROM celula c
                        WHERE c.Id_Lider = p.Id_Persona
                    )
                    OR EXISTS (
                        SELECT 1 FROM persona p2
                        WHERE p2.Id_Lider = p.Id_Persona
                          AND (p2.Estado_Cuenta = 'Activo' OR p2.Estado_Cuenta IS NULL)
                    )
                )
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND $filtroRol";

        $rows = $this->query($sql);
        return (int)($rows[0]['Total'] ?? 0);
    }

    /**
     * Resumen de líderes de célula con actividad y cantidad de personas.
     */
    public function getResumenLideresCelulaWithRole($filtroRol) {
        $sql = "SELECT
                    p.Id_Persona,
                    p.Nombre,
                    p.Apellido,
                    p.Genero,
                    p.Id_Ministerio,
                    p.Ultimo_Acceso,
                    m.Nombre_Ministerio,
                    CASE WHEN cel.Id_Persona IS NULL THEN 0 ELSE 1 END AS Es_Lider_Celula,
                    CASE WHEN l12.Id_Persona IS NULL THEN 0 ELSE 1 END AS Es_Lider_12,
                    CASE
                        WHEN cel.Id_Persona IS NOT NULL AND l12.Id_Persona IS NOT NULL THEN 'Ambos'
                        WHEN cel.Id_Persona IS NOT NULL THEN 'Líder de célula'
                        WHEN l12.Id_Persona IS NOT NULL THEN 'Líder de 12'
                        ELSE 'Sin clasificación'
                    END AS Tipo_Liderazgo,
                    COALESCE(per.Total_Personas, 0) AS Total_Personas,
                    rep.Ultimo_Reporte_Celula
                FROM persona p
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN (
                    SELECT DISTINCT Id_Lider AS Id_Persona
                    FROM celula
                    WHERE Id_Lider IS NOT NULL
                ) cel ON cel.Id_Persona = p.Id_Persona
                LEFT JOIN (
                    SELECT DISTINCT Id_Lider AS Id_Persona
                    FROM persona
                    WHERE Id_Lider IS NOT NULL
                      AND (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL)
                ) l12 ON l12.Id_Persona = p.Id_Persona
                LEFT JOIN (
                    SELECT Id_Lider, COUNT(*) AS Total_Personas
                    FROM persona
                    WHERE Id_Lider IS NOT NULL
                    AND (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL)
                    GROUP BY Id_Lider
                ) per ON per.Id_Lider = p.Id_Persona
                LEFT JOIN (
                    SELECT c.Id_Lider, MAX(a.Fecha_Asistencia) AS Ultimo_Reporte_Celula
                    FROM asistencia_celula a
                    INNER JOIN celula c ON c.Id_Celula = a.Id_Celula
                    GROUP BY c.Id_Lider
                ) rep ON rep.Id_Lider = p.Id_Persona
                WHERE (cel.Id_Persona IS NOT NULL OR l12.Id_Persona IS NOT NULL)
                AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                AND $filtroRol
                ORDER BY p.Apellido, p.Nombre";

        return $this->query($sql);
    }
}
