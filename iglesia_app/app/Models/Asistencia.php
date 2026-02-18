<?php
/**
 * Modelo Asistencia
 */

namespace App\Models;

class Asistencia extends BaseModel {
    protected string $table = 'ASISTENCIA_CELULA';
    protected array $fillable = [
        'id_persona',
        'id_celula',
        'fecha_asistencia',
        'es_asistente',
        'observacion'
    ];

    /**
     * Obtiene todas las asistencias de una persona
     * 
     * @param int $idPersona
     * @return array
     */
    public function getPorPersona(int $idPersona): array {
        $sql = "SELECT ac.*, c.nombre_celula FROM ASISTENCIA_CELULA ac
                INNER JOIN CELULA c ON ac.id_celula = c.id_celula
                WHERE ac.id_persona = ? AND ac.activo = 1
                ORDER BY ac.fecha_asistencia DESC";
        
        return $this->db->fetchAll($sql, [$idPersona]);
    }

    /**
     * Obtiene todas las asistencias de una célula
     * 
     * @param int $idCelula
     * @return array
     */
    public function getPorCelula(int $idCelula): array {
        $sql = "SELECT ac.*, p.nombre, p.apellido FROM ASISTENCIA_CELULA ac
                INNER JOIN PERSONA p ON ac.id_persona = p.id_persona
                WHERE ac.id_celula = ? AND ac.activo = 1
                ORDER BY ac.fecha_asistencia DESC";
        
        return $this->db->fetchAll($sql, [$idCelula]);
    }

    /**
     * Obtiene asistencias en una fecha específica
     * 
     * @param string $fecha
     * @return array
     */
    public function getPorFecha(string $fecha): array {
        $sql = "SELECT ac.*, p.nombre, p.apellido, c.nombre_celula FROM ASISTENCIA_CELULA ac
                INNER JOIN PERSONA p ON ac.id_persona = p.id_persona
                INNER JOIN CELULA c ON ac.id_celula = c.id_celula
                WHERE ac.fecha_asistencia = ? AND ac.activo = 1
                ORDER BY c.nombre_celula";
        
        return $this->db->fetchAll($sql, [$fecha]);
    }

    /**
     * Obtiene asistencias en un rango de fechas
     * 
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return array
     */
    public function getPorRangoFechas(string $fechaInicio, string $fechaFin): array {
        $sql = "SELECT ac.*, p.nombre, p.apellido, c.nombre_celula FROM ASISTENCIA_CELULA ac
                INNER JOIN PERSONA p ON ac.id_persona = p.id_persona
                INNER JOIN CELULA c ON ac.id_celula = c.id_celula
                WHERE ac.fecha_asistencia BETWEEN ? AND ? AND ac.activo = 1
                ORDER BY ac.fecha_asistencia DESC";
        
        return $this->db->fetchAll($sql, [$fechaInicio, $fechaFin]);
    }

    /**
     * Obtiene el porcentaje de asistencia de una persona
     * 
     * @param int $idPersona
     * @return float
     */
    public function getPorcentajeAsistencia(int $idPersona): float {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN es_asistente = 1 THEN 1 ELSE 0 END) as asistencias
                FROM ASISTENCIA_CELULA
                WHERE id_persona = ? AND activo = 1";
        
        $result = $this->db->fetchOne($sql, [$idPersona]);
        
        if ($result['total'] == 0) {
            return 0;
        }

        return round(($result['asistencias'] / $result['total']) * 100, 2);
    }

    /**
     * Registra asistencia
     * 
     * @param int $idPersona
     * @param int $idCelula
     * @param string $fecha
     * @param bool $asistio
     * @return bool
     */
    public function registrar(int $idPersona, int $idCelula, string $fecha, bool $asistio = true): bool {
        $sql = "INSERT INTO ASISTENCIA_CELULA 
                (id_persona, id_celula, fecha_asistencia, es_asistente, activo, fecha_creacion, fecha_modificacion)
                VALUES (?, ?, ?, ?, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                es_asistente = ?, fecha_modificacion = NOW()";
        
        $asistioInt = $asistio ? 1 : 0;
        
        return $this->db->query($sql, [$idPersona, $idCelula, $fecha, $asistioInt, $asistioInt])->rowCount() > 0;
    }

    /**
     * Obtiene resumen de asistencias por persona en una fecha
     * 
     * @param string $fecha
     * @return array
     */
    public function getResumenPorPersona(string $fecha): array {
        $sql = "SELECT 
                p.id_persona,
                p.nombre,
                p.apellido,
                COUNT(*) as total_celulas,
                SUM(CASE WHEN ac.es_asistente = 1 THEN 1 ELSE 0 END) as asistencias
                FROM PERSONA p
                LEFT JOIN ASISTENCIA_CELULA ac ON p.id_persona = ac.id_persona AND ac.fecha_asistencia = ?
                WHERE p.activo = 1
                GROUP BY p.id_persona
                ORDER BY p.nombre";
        
        return $this->db->fetchAll($sql, [$fecha]);
    }
}
