<?php
/**
 * Modelo Celula
 */

require_once APP . '/Models/BaseModel.php';

class Celula extends BaseModel {
    protected $table = 'celula';
    protected $primaryKey = 'Id_Celula';

    public function __construct() {
        parent::__construct();
        $this->ensureCamposReporteCelulas();
        $this->ensureCampoTipoCelula();
    }

    private function columnExists($columnName) {
        $columnName = trim((string)$columnName);
        if ($columnName === '') {
            return false;
        }

        $sql = "SHOW COLUMNS FROM {$this->table} LIKE ?";
        $rows = $this->query($sql, [$columnName]);
        return !empty($rows);
    }

    private function ensureCamposReporteCelulas() {
        try {
            if (!$this->columnExists('Fecha_Apertura')) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN Fecha_Apertura DATETIME NULL DEFAULT NULL");
            }

            if (!$this->columnExists('Estado_Celula')) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN Estado_Celula ENUM('Activa','Cerrada') NOT NULL DEFAULT 'Activa'");
            }

            if (!$this->columnExists('Fecha_Cierre')) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN Fecha_Cierre DATETIME NULL DEFAULT NULL");
            }
        } catch (Throwable $e) {
            // Evita bloquear la aplicación si el motor no permite alter en este momento.
            error_log('No fue posible asegurar campos de reporte en celula: ' . $e->getMessage());
        }
    }

    private function ensureCampoTipoCelula() {
        try {
            if (!$this->columnExists('Es_Antiguo')) {
                // 0 = nueva, 1 = antigua
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN Es_Antiguo TINYINT(1) NOT NULL DEFAULT 0");
            }
        } catch (Throwable $e) {
            error_log('No fue posible asegurar campo Es_Antiguo en celula: ' . $e->getMessage());
        }
    }

    /**
     * Crear célula estableciendo metadatos de apertura cuando existen las columnas.
     */
    public function create($data) {
        if ($this->columnExists('Fecha_Apertura') && empty($data['Fecha_Apertura'])) {
            $data['Fecha_Apertura'] = date('Y-m-d H:i:s');
        }

        if ($this->columnExists('Estado_Celula') && empty($data['Estado_Celula'])) {
            $data['Estado_Celula'] = 'Activa';
        }

        return parent::create($data);
    }

    /**
     * Obtener célula por ID con nombre del líder
     */
    public function getById($id) {
        $sql = "SELECT c.*, 
                CONCAT(p.Nombre, ' ', p.Apellido) as Nombre_Lider,
                CONCAT(li.Nombre, ' ', li.Apellido) as Nombre_Lider_Inmediato,
                CONCAT(an.Nombre, ' ', an.Apellido) as Nombre_Anfitrion
                FROM {$this->table} c
                LEFT JOIN persona p ON c.Id_Lider = p.Id_Persona
                LEFT JOIN persona li ON c.Id_Lider_Inmediato = li.Id_Persona
                LEFT JOIN persona an ON c.Id_Anfitrion = an.Id_Persona
                WHERE c.{$this->primaryKey} = ?";
        $result = $this->query($sql, [$id]);
        return $result[0] ?? null;
    }

    /**
     * Obtener células con contador de miembros
     */
    public function getAllWithMemberCount() {
        $sql = "SELECT c.*, 
                COUNT(CASE WHEN p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL THEN 1 END) as Total_Miembros,
                CONCAT(l.Nombre, ' ', l.Apellido) as Nombre_Lider,
                CONCAT(an.Nombre, ' ', an.Apellido) as Nombre_Anfitrion
                FROM {$this->table} c
                LEFT JOIN persona p ON c.Id_Celula = p.Id_Celula
                LEFT JOIN persona l ON c.Id_Lider = l.Id_Persona
                LEFT JOIN persona an ON c.Id_Anfitrion = an.Id_Persona
                GROUP BY c.Id_Celula
                ORDER BY c.Nombre_Celula";
        return $this->query($sql);
    }

    /**
     * Obtener células con contador de miembros filtradas por ministerio del líder
     */
    public function getByMinisterioWithMemberCount($idMinisterio) {
        $sql = "SELECT c.*, 
                COUNT(CASE WHEN p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL THEN 1 END) as Total_Miembros,
                CONCAT(l.Nombre, ' ', l.Apellido) as Nombre_Lider,
                CONCAT(an.Nombre, ' ', an.Apellido) as Nombre_Anfitrion
                FROM {$this->table} c
                LEFT JOIN persona p ON c.Id_Celula = p.Id_Celula
                LEFT JOIN persona l ON c.Id_Lider = l.Id_Persona
                LEFT JOIN persona an ON c.Id_Anfitrion = an.Id_Persona
                WHERE l.Id_Ministerio = ?
                GROUP BY c.Id_Celula
                ORDER BY c.Nombre_Celula";
        return $this->query($sql, [$idMinisterio]);
    }

    /**
     * Obtener detalles de una célula con miembros
     */
    public function getWithMembers($id) {
        $celula = $this->getById($id);
        if ($celula) {
            $sql = "SELECT * FROM persona 
                    WHERE Id_Celula = ? 
                    AND (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL)
                    ORDER BY Apellido, Nombre";
            $celula['miembros'] = $this->query($sql, [$id]);
        }
        return $celula;
    }

    /**
     * Obtener células con contador de miembros y aislamiento de rol
     */
    public function getAllWithMemberCountAndRole($filtroRol, $idMinisterio = null, $idLider = null) {
        $sql = "SELECT c.*, 
                COUNT(CASE WHEN p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL THEN 1 END) as Total_Miembros,
                CONCAT(l.Nombre, ' ', l.Apellido) as Nombre_Lider,
                CONCAT(an.Nombre, ' ', an.Apellido) as Nombre_Anfitrion,
                l.Id_Ministerio as Id_Ministerio_Lider,
                m.Nombre_Ministerio as Nombre_Ministerio_Lider
                FROM {$this->table} c
                LEFT JOIN persona p ON c.Id_Celula = p.Id_Celula
                LEFT JOIN persona l ON c.Id_Lider = l.Id_Persona
                LEFT JOIN persona an ON c.Id_Anfitrion = an.Id_Persona
                LEFT JOIN ministerio m ON l.Id_Ministerio = m.Id_Ministerio
                WHERE $filtroRol";

        $params = [];
        $idMinisterio = $idMinisterio !== null && $idMinisterio !== '' ? (int)$idMinisterio : null;
        $idLider = $idLider !== null && $idLider !== '' ? (int)$idLider : null;

        if ($idMinisterio !== null && $idMinisterio > 0) {
            $sql .= " AND l.Id_Ministerio = ?";
            $params[] = $idMinisterio;
        }

        if ($idLider !== null && $idLider > 0) {
            $sql .= " AND c.Id_Lider = ?";
            $params[] = $idLider;
        }

        $sql .= "
                GROUP BY c.Id_Celula
                ORDER BY c.Nombre_Celula";

        return $this->query($sql, $params);
    }

    /**
     * Verifica si una célula existe dentro del alcance de un filtro de rol.
     */
    public function existsByIdWithRole($idCelula, $filtroRol) {
        $idCelula = (int)$idCelula;
        if ($idCelula <= 0) {
            return false;
        }

        $sql = "SELECT c.Id_Celula
                FROM {$this->table} c
                WHERE c.Id_Celula = ? AND ($filtroRol)
                LIMIT 1";

        $rows = $this->query($sql, [$idCelula]);
        return !empty($rows);
    }
}
