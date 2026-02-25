<?php
/**
 * Modelo Persona
 */

require_once APP . '/Models/BaseModel.php';

class Persona extends BaseModel {
    protected $table = 'persona';
    protected $primaryKey = 'Id_Persona';

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
    public function getAllWithRole($filtroRol, $soloGanar = false, $estadoCuenta = null) {
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

        if ($soloGanar) {
            $sql .= " AND (p.Id_Ministerio IS NULL OR p.Id_Lider IS NULL)";
        } else {
            $sql .= " AND p.Id_Ministerio IS NOT NULL AND p.Id_Lider IS NOT NULL";
        }

        $params = [];
        if ($estadoCuenta !== null && $estadoCuenta !== '') {
            if ($estadoCuenta === 'Activo') {
                $sql .= " AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)";
            } else {
                $sql .= " AND p.Estado_Cuenta = ?";
                $params[] = $estadoCuenta;
            }
        }

        $sql .= "
                ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC";
        return $this->query($sql, $params);
    }

    /**
     * Obtener personas con filtros y aislamiento de rol
     */
    public function getWithFiltersAndRole($filtroRol, $idMinisterio = null, $idLider = null, $soloGanar = false, $estadoCuenta = null) {
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

        if ($soloGanar) {
            $sql .= " AND (p.Id_Ministerio IS NULL OR p.Id_Lider IS NULL)";
        } else {
            $sql .= " AND p.Id_Ministerio IS NOT NULL AND p.Id_Lider IS NOT NULL";
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

        if ($estadoCuenta !== null && $estadoCuenta !== '') {
            if ($estadoCuenta === 'Activo') {
                $sql .= " AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)";
            } else {
                $sql .= " AND p.Estado_Cuenta = ?";
                $params[] = $estadoCuenta;
            }
        }

        $sql .= " ORDER BY p.Fecha_Registro DESC, p.Id_Persona DESC";
        
        return $this->query($sql, $params);
    }

    /**
     * Obtener almas ganadas por ministerio con aislamiento de rol
     */
    public function getAlmasGanadasPorMinisterioWithRole($fechaInicio, $fechaFin, $filtroRol) {
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
                AND $filtroRol
                GROUP BY m.Id_Ministerio, m.Nombre_Ministerio
                HAVING Total > 0
                ORDER BY m.Nombre_Ministerio";
        return $this->query($sql, [$fechaInicio, $fechaFin]);
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
                       c.Nombre_Celula
                FROM persona p
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
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
}
