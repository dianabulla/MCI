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
        $this->ensureCamposFichaDiagrama();
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

            if (!$this->columnExists('Observaciones_Cierre')) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN Observaciones_Cierre TEXT NULL DEFAULT NULL");
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
     * Campos de ficha alineados al diagrama (INT, no UUID). Sin valor_sobre.
     * Entrego_sobre / reportó se quedan en asistencias por semana.
     */
    private function ensureCamposFichaDiagrama() {
        try {
            if (!$this->columnExists('Id_Ministerio')) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN Id_Ministerio INT NULL AFTER Id_Lider");
            }
            if (!$this->columnExists('Id_Pastor')) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN Id_Pastor INT NULL AFTER Pastor_Principal");
            }
        } catch (Throwable $e) {
            error_log('No fue posible asegurar Id_Ministerio/Id_Pastor en celula: ' . $e->getMessage());
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
        $joinPastor = $this->columnExists('Id_Pastor')
            ? 'LEFT JOIN persona pa ON c.Id_Pastor = pa.Id_Persona'
            : '';
        $selectPastor = $this->columnExists('Id_Pastor')
            ? "CONCAT(pa.Nombre, ' ', pa.Apellido) as Nombre_Pastor,"
            : 'NULL as Nombre_Pastor,';
        $joinMinisterio = $this->columnExists('Id_Ministerio')
            ? 'LEFT JOIN ministerio minc ON c.Id_Ministerio = minc.Id_Ministerio'
            : '';
        $selectMinisterio = $this->columnExists('Id_Ministerio')
            ? 'minc.Nombre_Ministerio as Nombre_Ministerio,'
            : 'NULL as Nombre_Ministerio,';

        $sql = "SELECT c.*, 
                CONCAT(p.Nombre, ' ', p.Apellido) as Nombre_Lider,
                CONCAT(li.Nombre, ' ', li.Apellido) as Nombre_Lider_Inmediato,
                CONCAT(an.Nombre, ' ', an.Apellido) as Nombre_Anfitrion,
                {$selectPastor}
                {$selectMinisterio}
                an.Telefono as Telefono_Persona_Anfitrion
                FROM {$this->table} c
                LEFT JOIN persona p ON c.Id_Lider = p.Id_Persona
                LEFT JOIN persona li ON c.Id_Lider_Inmediato = li.Id_Persona
                LEFT JOIN persona an ON c.Id_Anfitrion = an.Id_Persona
                {$joinPastor}
                {$joinMinisterio}
                WHERE c.{$this->primaryKey} = ?";
        $result = $this->query($sql, [$id]);
        return $result[0] ?? null;
    }

    /**
     * Obtener células con contador de miembros
     */
    public function getAllWithMemberCount() {
        $sql = "SELECT c.*, 
                COUNT(DISTINCT CASE
                    WHEN (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                     AND p.Id_Persona IS NOT NULL
                     AND (c.Id_Lider IS NULL OR p.Id_Persona <> c.Id_Lider)
                    THEN p.Id_Persona
                END) as Total_Miembros,
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
                COUNT(DISTINCT CASE
                    WHEN (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                     AND p.Id_Persona IS NOT NULL
                     AND (c.Id_Lider IS NULL OR p.Id_Persona <> c.Id_Lider)
                    THEN p.Id_Persona
                END) as Total_Miembros,
                CONCAT(l.Nombre, ' ', l.Apellido) as Nombre_Lider,
                CONCAT(an.Nombre, ' ', an.Apellido) as Nombre_Anfitrion
                FROM {$this->table} c
                LEFT JOIN persona p ON c.Id_Celula = p.Id_Celula
                LEFT JOIN persona l ON c.Id_Lider = l.Id_Persona
                LEFT JOIN persona an ON c.Id_Anfitrion = an.Id_Persona
                WHERE COALESCE(c.Id_Ministerio, l.Id_Ministerio) = ?
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
            $idLiderCelula = (int)($celula['Id_Lider'] ?? 0);
            $celula['miembros'] = array_values(array_filter(
                $this->query($sql, [$id]),
                static function ($miembro) use ($idLiderCelula) {
                    $idPersona = (int)($miembro['Id_Persona'] ?? 0);
                    return $idPersona > 0 && ($idLiderCelula <= 0 || $idPersona !== $idLiderCelula);
                }
            ));
        }
        return $celula;
    }

    /**
     * Obtener células con contador de miembros y aislamiento de rol
     */
    public function getAllWithMemberCountAndRole($filtroRol, $idMinisterio = null, $idLider = null) {
        $sql = "SELECT c.*, 
                COUNT(DISTINCT CASE
                    WHEN (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                     AND p.Id_Persona IS NOT NULL
                     AND (c.Id_Lider IS NULL OR p.Id_Persona <> c.Id_Lider)
                    THEN p.Id_Persona
                END) as Total_Miembros,
                CONCAT(l.Nombre, ' ', l.Apellido) as Nombre_Lider,
                CONCAT(an.Nombre, ' ', an.Apellido) as Nombre_Anfitrion,
                COALESCE(c.Id_Ministerio, l.Id_Ministerio) as Id_Ministerio_Lider,
                m.Nombre_Ministerio as Nombre_Ministerio_Lider
                FROM {$this->table} c
                LEFT JOIN persona p ON c.Id_Celula = p.Id_Celula
                LEFT JOIN persona l ON c.Id_Lider = l.Id_Persona
                LEFT JOIN persona an ON c.Id_Anfitrion = an.Id_Persona
                LEFT JOIN ministerio m ON m.Id_Ministerio = COALESCE(c.Id_Ministerio, l.Id_Ministerio)
                WHERE $filtroRol";

        $params = [];
        $idMinisterio = $idMinisterio !== null && $idMinisterio !== '' ? (int)$idMinisterio : null;
        $idLider = $idLider !== null && $idLider !== '' ? (int)$idLider : null;

        if ($idMinisterio !== null && $idMinisterio > 0) {
            $sql .= " AND COALESCE(c.Id_Ministerio, l.Id_Ministerio) = ?";
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
     * Miembros activos por célula (distinct, sin contar al líder de la célula).
     *
     * @return array<int, int> [Id_Celula => total]
     */
    public function getConteoMiembrosActivosPorCelulas(array $idsCelula): array {
        $idsCelula = array_values(array_unique(array_filter(array_map('intval', $idsCelula), static function($id) {
            return $id > 0;
        })));

        if (empty($idsCelula)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsCelula), '?'));
        $sql = "SELECT c.Id_Celula,
                       COUNT(DISTINCT p.Id_Persona) AS Total_Miembros
                FROM {$this->table} c
                LEFT JOIN persona p
                  ON p.Id_Celula = c.Id_Celula
                 AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                 AND p.Id_Persona <> c.Id_Lider
                WHERE c.Id_Celula IN ({$placeholders})
                GROUP BY c.Id_Celula";

        $mapa = [];
        foreach ($this->query($sql, $idsCelula) as $row) {
            $idCelula = (int)($row['Id_Celula'] ?? 0);
            if ($idCelula <= 0) {
                continue;
            }
            $mapa[$idCelula] = (int)($row['Total_Miembros'] ?? 0);
        }

        return $mapa;
    }

    /**
     * Miembros activos y personas nuevas (casilla Nuevo) por célula, sin contar al líder.
     *
     * @return array<int, array{asistentes:int, nuevas:int}>
     */
    public function getConteoAsistentesYNuevosPorCelulas(array $idsCelula, bool $contarNuevasPorEsAntiguo = true): array {
        $idsCelula = array_values(array_unique(array_filter(array_map('intval', $idsCelula), static function($id) {
            return $id > 0;
        })));

        if (empty($idsCelula)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsCelula), '?'));
        $colNuevas = $contarNuevasPorEsAntiguo
            ? 'COUNT(DISTINCT CASE WHEN COALESCE(p.Es_Antiguo, 1) = 0 THEN p.Id_Persona END)'
            : '0';

        $sql = "SELECT c.Id_Celula,
                       COUNT(DISTINCT p.Id_Persona) AS Asistentes,
                       {$colNuevas} AS Nuevas
                FROM {$this->table} c
                LEFT JOIN persona p
                  ON p.Id_Celula = c.Id_Celula
                 AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                 AND (c.Id_Lider IS NULL OR p.Id_Persona <> c.Id_Lider)
                WHERE c.Id_Celula IN ({$placeholders})
                GROUP BY c.Id_Celula";

        $mapa = [];
        foreach ($this->query($sql, $idsCelula) as $row) {
            $idCelula = (int)($row['Id_Celula'] ?? 0);
            if ($idCelula <= 0) {
                continue;
            }
            $mapa[$idCelula] = [
                'asistentes' => (int)($row['Asistentes'] ?? 0),
                'nuevas' => (int)($row['Nuevas'] ?? 0),
            ];
        }

        return $mapa;
    }

    /**
     * Miembros activos por célula (sin líder), con documento y tipo nuevo/antiguo.
     *
     * @return array<int, list<array<string, mixed>>>
     */
    public function getMiembrosActivosDetallePorCelulas(array $idsCelula): array {
        $idsCelula = array_values(array_unique(array_filter(array_map('intval', $idsCelula), static function($id) {
            return $id > 0;
        })));

        if (empty($idsCelula)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsCelula), '?'));
        $sql = "SELECT p.Id_Persona, p.Id_Celula, p.Nombre, p.Apellido, p.Telefono, p.Numero_Documento,
                       COALESCE(p.Es_Antiguo, 1) AS Es_Antiguo,
                       p.Proceso, p.Escalera_Checklist, p.Id_Rol, p.Id_Lider, p.Id_Ministerio
                FROM persona p
                INNER JOIN {$this->table} c ON c.Id_Celula = p.Id_Celula
                WHERE p.Id_Celula IN ({$placeholders})
                  AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND (c.Id_Lider IS NULL OR p.Id_Persona <> c.Id_Lider)
                ORDER BY p.Apellido, p.Nombre";

        $mapa = [];
        foreach ($this->query($sql, $idsCelula) as $row) {
            $idCelula = (int)($row['Id_Celula'] ?? 0);
            if ($idCelula <= 0) {
                continue;
            }
            $mapa[$idCelula][] = $row;
        }

        return $mapa;
    }

    /**
     * Personas activas únicas en un conjunto de células (sin duplicar entre células ni contar líderes).
     */
    public function contarMiembrosActivosUnicosEnCelulas(array $idsCelula): int {
        $idsCelula = array_values(array_unique(array_filter(array_map('intval', $idsCelula), static function($id) {
            return $id > 0;
        })));

        if (empty($idsCelula)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($idsCelula), '?'));
        $sql = "SELECT COUNT(DISTINCT p.Id_Persona) AS Total_Miembros
                FROM persona p
                INNER JOIN {$this->table} c ON c.Id_Celula = p.Id_Celula
                WHERE c.Id_Celula IN ({$placeholders})
                  AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND p.Id_Persona <> c.Id_Lider";

        $rows = $this->query($sql, $idsCelula);

        return (int)($rows[0]['Total_Miembros'] ?? 0);
    }

    /**
     * Cantidad de células (no de personas) visibles según aislamiento.
     */
    public function contarCelulas(string $filtroRol = '1=1', bool $soloActivas = true): int {
        $filtroRol = trim($filtroRol) !== '' ? $filtroRol : '1=1';
        $where = ["({$filtroRol})"];
        if ($soloActivas && $this->columnExists('Estado_Celula')) {
            $where[] = "(c.Estado_Celula = 'Activa' OR c.Estado_Celula IS NULL OR c.Estado_Celula = '')";
        }
        $sql = "SELECT COUNT(*) AS Total
                FROM {$this->table} c
                WHERE " . implode(' AND ', $where);
        $rows = $this->query($sql);
        return (int)($rows[0]['Total'] ?? 0);
    }

    /**
     * Células activas por ministerio (el de la célula o, si no tiene, el de su líder).
     *
     * @return array<int,int> [Id_Ministerio => cantidad]
     */
    public function contarActivasPorMinisterio(): array {
        $columnaMinisterio = $this->columnExists('Id_Ministerio')
            ? 'COALESCE(c.Id_Ministerio, l.Id_Ministerio)'
            : 'l.Id_Ministerio';
        $where = $this->columnExists('Estado_Celula')
            ? "WHERE (c.Estado_Celula = 'Activa' OR c.Estado_Celula IS NULL OR c.Estado_Celula = '')"
            : '';

        $sql = "SELECT {$columnaMinisterio} AS Id_Ministerio, COUNT(*) AS Total
                FROM {$this->table} c
                LEFT JOIN persona l ON l.Id_Persona = c.Id_Lider
                {$where}
                GROUP BY {$columnaMinisterio}";

        $resultado = [];
        foreach ($this->query($sql) as $fila) {
            $id = (int)($fila['Id_Ministerio'] ?? 0);
            if ($id > 0) {
                $resultado[$id] = (int)($fila['Total'] ?? 0);
            }
        }

        return $resultado;
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

    /**
     * Mapa líder de célula → líder inmediato (12 o 144).
     *
     * @return array<int, int>
     */
    public function getMapaLiderCelulaAInmediato(): array {
        $sql = "SELECT Id_Lider, Id_Lider_Inmediato
                FROM {$this->table}
                WHERE Id_Lider IS NOT NULL
                  AND Id_Lider > 0
                  AND Id_Lider_Inmediato IS NOT NULL
                  AND Id_Lider_Inmediato > 0";
        $mapa = [];
        foreach ((array)$this->query($sql) as $row) {
            $idLider = (int)($row['Id_Lider'] ?? 0);
            $idInmediato = (int)($row['Id_Lider_Inmediato'] ?? 0);
            if ($idLider > 0 && $idInmediato > 0 && $idLider !== $idInmediato) {
                $mapa[$idLider] = $idInmediato;
            }
        }

        return $mapa;
    }
}
