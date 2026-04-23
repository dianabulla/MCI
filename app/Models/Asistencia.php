<?php
/**
 * Modelo Asistencia
 */

require_once APP . '/Models/BaseModel.php';

class Asistencia extends BaseModel {
    protected $table = 'asistencia_celula';
    protected $primaryKey = 'Id_Asistencia';

    public function ensureEntregaSobreTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS asistencia_entrega_sobre_semana (
                    Id_Entrega INT AUTO_INCREMENT PRIMARY KEY,
                    Id_Celula INT NOT NULL,
                    Semana_Inicio DATE NOT NULL,
                    Entrego_Sobre TINYINT(1) NOT NULL DEFAULT 0,
                    Actualizado_En DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_celula_semana (Id_Celula, Semana_Inicio),
                    INDEX idx_semana (Semana_Inicio)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        return $this->execute($sql);
    }

    /**
     * Obtener asistencias con información completa
     */
    public function getAllWithInfo() {
        $sql = "SELECT a.*, 
                CONCAT(p.Nombre, ' ', p.Apellido) as Nombre_Persona,
                c.Nombre_Celula
                FROM {$this->table} a
                LEFT JOIN persona p ON a.Id_Persona = p.Id_Persona
                LEFT JOIN celula c ON a.Id_Celula = c.Id_Celula
                ORDER BY a.Fecha_Asistencia DESC";
        return $this->query($sql);
    }

    /**
     * Obtener asistencias por célula
     */
    public function getByCelula($idCelula, $fecha = null) {
        if ($fecha) {
            $sql = "SELECT a.*, CONCAT(p.Nombre, ' ', p.Apellido) as Nombre_Persona
                    FROM {$this->table} a
                    LEFT JOIN persona p ON a.Id_Persona = p.Id_Persona
                    WHERE a.Id_Celula = ? AND a.Fecha_Asistencia = ?
                    ORDER BY p.Apellido, p.Nombre";
            return $this->query($sql, [$idCelula, $fecha]);
        } else {
            $sql = "SELECT a.*, CONCAT(p.Nombre, ' ', p.Apellido) as Nombre_Persona
                    FROM {$this->table} a
                    LEFT JOIN persona p ON a.Id_Persona = p.Id_Persona
                    WHERE a.Id_Celula = ?
                    ORDER BY a.Fecha_Asistencia DESC";
            return $this->query($sql, [$idCelula]);
        }
    }

    /**
     * Obtener asistencias por persona
     */
    public function getByPersona($idPersona) {
        $sql = "SELECT a.*, c.Nombre_Celula
                FROM {$this->table} a
                LEFT JOIN celula c ON a.Id_Celula = c.Id_Celula
                WHERE a.Id_Persona = ?
                ORDER BY a.Fecha_Asistencia DESC";
        return $this->query($sql, [$idPersona]);
    }

    /**
     * Registrar asistencia
     */
    public function registrarAsistencia($idPersona, $idCelula, $fecha, $asistio, $tema = null, $tipoCelula = null, $observaciones = null) {
        $data = [
            'Id_Persona' => $idPersona,
            'Id_Celula' => $idCelula,
            'Fecha_Asistencia' => $fecha,
            'Asistio' => $asistio,
            'Tema' => $tema,
            'Tipo_Celula' => $tipoCelula,
            'Observaciones' => $observaciones
        ];
        return $this->create($data);
    }

    /**
     * Obtener estadísticas de asistencia por célula en un rango de fechas
     */
    public function getAsistenciaPorCelula($fechaInicio, $fechaFin) {
        $sql = "SELECT 
                    c.Nombre_Celula,
                    c.Id_Celula,
                    CONCAT(COALESCE(l.Nombre, ''), ' ', COALESCE(l.Apellido, '')) as Nombre_Lider,
                    (SELECT COUNT(*) FROM persona WHERE Id_Celula = c.Id_Celula AND (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL)) as Total_Inscritos,
                    (SELECT COUNT(DISTINCT Fecha_Asistencia) 
                     FROM asistencia_celula 
                     WHERE Id_Celula = c.Id_Celula 
                     AND Fecha_Asistencia BETWEEN ? AND ?) as Reuniones_Realizadas,
                    (SELECT COUNT(*) FROM persona WHERE Id_Celula = c.Id_Celula AND (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL)) * 
                    (SELECT COUNT(DISTINCT Fecha_Asistencia) 
                     FROM asistencia_celula 
                     WHERE Id_Celula = c.Id_Celula 
                     AND Fecha_Asistencia BETWEEN ? AND ?) as Asistencias_Esperadas,
                    (SELECT COUNT(*) 
                     FROM asistencia_celula a
                     INNER JOIN persona p ON a.Id_Persona = p.Id_Persona
                     WHERE a.Id_Celula = c.Id_Celula 
                     AND a.Asistio = 1 
                     AND a.Fecha_Asistencia BETWEEN ? AND ?
                     AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)) as Asistencias_Reales
                FROM celula c
                LEFT JOIN persona l ON c.Id_Lider = l.Id_Persona
                ORDER BY c.Nombre_Celula";
        return $this->query($sql, [$fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin]);
    }

    /**
     * Obtener asistencias con información y aislamiento de rol
     */
    public function getAllWithInfoAndRole($filtroRol) {
        $sql = "SELECT a.*, 
                CONCAT(p.Nombre, ' ', p.Apellido) as Nombre_Persona,
                c.Nombre_Celula
                FROM {$this->table} a
                LEFT JOIN persona p ON a.Id_Persona = p.Id_Persona
                LEFT JOIN celula c ON a.Id_Celula = c.Id_Celula
                WHERE $filtroRol
                ORDER BY a.Fecha_Asistencia DESC";
        return $this->query($sql);
    }

    /**
     * Obtener asistencia por célula con aislamiento de rol
     */
    public function getAsistenciaPorCelulaWithRole($fechaInicio, $fechaFin, $filtroRol, $idMinisterio = '', $idLider = '') {
        $sql = "SELECT 
                    c.Nombre_Celula,
                    c.Id_Celula,
                    CONCAT(COALESCE(l.Nombre, ''), ' ', COALESCE(l.Apellido, '')) as Nombre_Lider,
                    (SELECT COUNT(*) FROM persona WHERE Id_Celula = c.Id_Celula AND (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL)) as Total_Inscritos,
                    (SELECT COUNT(DISTINCT Fecha_Asistencia) 
                     FROM asistencia_celula 
                     WHERE Id_Celula = c.Id_Celula 
                     AND Fecha_Asistencia BETWEEN ? AND ?) as Reuniones_Realizadas,
                    (SELECT COUNT(*) FROM persona WHERE Id_Celula = c.Id_Celula AND (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL)) * 
                    (SELECT COUNT(DISTINCT Fecha_Asistencia) 
                     FROM asistencia_celula 
                     WHERE Id_Celula = c.Id_Celula 
                     AND Fecha_Asistencia BETWEEN ? AND ?) as Asistencias_Esperadas,
                    (SELECT COUNT(*) 
                     FROM asistencia_celula a
                     INNER JOIN persona p ON a.Id_Persona = p.Id_Persona
                     WHERE a.Id_Celula = c.Id_Celula 
                     AND a.Asistio = 1 
                     AND a.Fecha_Asistencia BETWEEN ? AND ?
                     AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)) as Asistencias_Reales
                FROM celula c
                LEFT JOIN persona l ON c.Id_Lider = l.Id_Persona
                WHERE $filtroRol";

        $params = [$fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin];

        if ($idMinisterio !== null && $idMinisterio !== '' && (int)$idMinisterio > 0) {
            $sql .= " AND l.Id_Ministerio = ?";
            $params[] = (int)$idMinisterio;
        }

        if ($idLider !== null && $idLider !== '' && (int)$idLider > 0) {
            $sql .= " AND c.Id_Lider = ?";
            $params[] = (int)$idLider;
        }

        $sql .= "
                ORDER BY c.Nombre_Celula";
        return $this->query($sql, $params);
    }

    /**
     * Obtener conteo de asistencias completas (Asistio = 1) por persona.
     *
     * @param array $idsPersona
     * @return array [Id_Persona => total_asistencias]
     */
    public function getConteoAsistenciasCompletasPorPersona(array $idsPersona) {
        $idsPersona = array_values(array_unique(array_filter(array_map('intval', $idsPersona), static function($id) {
            return $id > 0;
        })));

        if (empty($idsPersona)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsPersona), '?'));
        $sql = "SELECT Id_Persona, SUM(CASE WHEN Asistio = 1 THEN 1 ELSE 0 END) AS Total
                FROM {$this->table}
                WHERE Id_Persona IN ({$placeholders})
                GROUP BY Id_Persona";

        $rows = $this->query($sql, $idsPersona);
        $resultado = [];

        foreach ($rows as $row) {
            $idPersona = (int)($row['Id_Persona'] ?? 0);
            if ($idPersona <= 0) {
                continue;
            }

            $resultado[$idPersona] = (int)($row['Total'] ?? 0);
        }

        return $resultado;
    }

    public function getUltimaFechaReportePorCelula(array $idsCelula) {
        $idsCelula = array_values(array_unique(array_filter(array_map('intval', $idsCelula), static function($id) {
            return $id > 0;
        })));

        if (empty($idsCelula)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsCelula), '?'));
        $sql = "SELECT Id_Celula, MAX(Fecha_Asistencia) AS Ultima_Fecha_Reporte
                FROM {$this->table}
                WHERE Id_Celula IN ({$placeholders})
                GROUP BY Id_Celula";

        $rows = $this->query($sql, $idsCelula);
        $resultado = [];

        foreach ($rows as $row) {
            $idCelula = (int)($row['Id_Celula'] ?? 0);
            if ($idCelula <= 0) {
                continue;
            }

            $resultado[$idCelula] = (string)($row['Ultima_Fecha_Reporte'] ?? '');
        }

        return $resultado;
    }

    public function getEstadoEntregoSobrePorCelulaSemana(array $idsCelula, $semanaInicio) {
        $idsCelula = array_values(array_unique(array_filter(array_map('intval', $idsCelula), static function($id) {
            return $id > 0;
        })));

        $semanaInicio = trim((string)$semanaInicio);
        if (empty($idsCelula) || $semanaInicio === '') {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsCelula), '?'));
        $sql = "SELECT Id_Celula, Entrego_Sobre
                FROM asistencia_entrega_sobre_semana
                WHERE Semana_Inicio = ?
                  AND Id_Celula IN ({$placeholders})";

        $rows = $this->query($sql, array_merge([$semanaInicio], $idsCelula));
        $estado = [];

        foreach ($rows as $row) {
            $idCelula = (int)($row['Id_Celula'] ?? 0);
            if ($idCelula <= 0) {
                continue;
            }
            $estado[$idCelula] = (int)($row['Entrego_Sobre'] ?? 0) === 1;
        }

        return $estado;
    }

    public function guardarEntregoSobreSemana($idCelula, $semanaInicio, $entregoSobre) {
        $idCelula = (int)$idCelula;
        $semanaInicio = trim((string)$semanaInicio);
        $entregoSobre = !empty($entregoSobre) ? 1 : 0;

        if ($idCelula <= 0 || $semanaInicio === '') {
            return false;
        }

        $sql = "INSERT INTO asistencia_entrega_sobre_semana (Id_Celula, Semana_Inicio, Entrego_Sobre)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE Entrego_Sobre = VALUES(Entrego_Sobre), Actualizado_En = NOW()";

        return $this->execute($sql, [$idCelula, $semanaInicio, $entregoSobre]);
    }
}
