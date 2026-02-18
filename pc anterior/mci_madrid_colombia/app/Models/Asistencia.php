<?php
/**
 * Modelo Asistencia
 */

require_once APP . '/Models/BaseModel.php';

class Asistencia extends BaseModel {
    protected $table = 'asistencia_celula';
    protected $primaryKey = 'Id_Asistencia';

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
    public function registrarAsistencia($idPersona, $idCelula, $fecha, $asistio) {
        $data = [
            'Id_Persona' => $idPersona,
            'Id_Celula' => $idCelula,
            'Fecha_Asistencia' => $fecha,
            'Asistio' => $asistio
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
                WHERE l.Id_Rol IS NULL OR l.Id_Rol IN (1, 2)
                ORDER BY c.Nombre_Celula";
        return $this->query($sql, [$fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin]);
    }
}

