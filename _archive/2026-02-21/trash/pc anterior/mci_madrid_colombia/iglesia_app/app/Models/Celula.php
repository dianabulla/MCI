<?php
/**
 * Modelo Celula
 */

namespace App\Models;

class Celula extends BaseModel {
    protected string $table = 'CELULA';
    protected array $fillable = [
        'nombre_celula',
        'direccion',
        'dia_semana',
        'hora_inicio',
        'id_lider_celula',
        'observacion'
    ];

    /**
     * Obtiene el líder de la célula
     * 
     * @param int $idCelula
     * @return array|null
     */
    public function getLider(int $idCelula): ?array {
        $celula = $this->find($idCelula);
        
        if (!$celula) {
            return null;
        }

        $sql = "SELECT * FROM PERSONA WHERE id_persona = ? AND activo = 1";
        return $this->db->fetchOne($sql, [$celula['id_lider_celula']]);
    }

    /**
     * Obtiene todos los miembros de una célula
     * 
     * @param int $idCelula
     * @return array
     */
    public function getMiembros(int $idCelula): array {
        $sql = "SELECT DISTINCT p.* FROM PERSONA p
                INNER JOIN ASISTENCIA_CELULA ac ON p.id_persona = ac.id_persona
                WHERE ac.id_celula = ? AND ac.activo = 1 AND p.activo = 1";
        
        return $this->db->fetchAll($sql, [$idCelula]);
    }

    /**
     * Obtiene el historial de asistencia de una célula
     * 
     * @param int $idCelula
     * @param int $limit
     * @return array
     */
    public function getAsistencias(int $idCelula, int $limit = 10): array {
        $sql = "SELECT ac.*, p.nombre, p.apellido FROM ASISTENCIA_CELULA ac
                INNER JOIN PERSONA p ON ac.id_persona = p.id_persona
                WHERE ac.id_celula = ? AND ac.activo = 1
                ORDER BY ac.fecha_asistencia DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$idCelula, $limit]);
    }

    /**
     * Registra asistencia de una persona en una célula
     * 
     * @param int $idPersona
     * @param int $idCelula
     * @param string $fecha
     * @param bool $asistio
     * @return bool
     */
    public function registrarAsistencia(int $idPersona, int $idCelula, string $fecha, bool $asistio = true): bool {
        $sql = "INSERT INTO ASISTENCIA_CELULA 
                (id_persona, id_celula, fecha_asistencia, es_asistente, activo, fecha_creacion, fecha_modificacion)
                VALUES (?, ?, ?, ?, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                es_asistente = ?, fecha_modificacion = NOW()";
        
        $asistioInt = $asistio ? 1 : 0;
        
        return $this->db->query($sql, [$idPersona, $idCelula, $fecha, $asistioInt, $asistioInt])->rowCount() > 0;
    }

    /**
     * Obtiene estadísticas de asistencia
     * 
     * @param int $idCelula
     * @return array
     */
    public function getEstadisticasAsistencia(int $idCelula): array {
        $sql = "SELECT 
                COUNT(*) as total_registros,
                SUM(CASE WHEN es_asistente = 1 THEN 1 ELSE 0 END) as asistencias,
                SUM(CASE WHEN es_asistente = 0 THEN 1 ELSE 0 END) as inasistencias
                FROM ASISTENCIA_CELULA
                WHERE id_celula = ? AND activo = 1";
        
        return $this->db->fetchOne($sql, [$idCelula]) ?? [
            'total_registros' => 0,
            'asistencias' => 0,
            'inasistencias' => 0
        ];
    }

    /**
     * Obtiene células por día de la semana
     * 
     * @param string $diaSemana
     * @return array
     */
    public function porDiaSemana(string $diaSemana): array {
        $sql = "SELECT * FROM CELULA WHERE dia_semana = ? AND activo = 1 ORDER BY hora_inicio";
        return $this->db->fetchAll($sql, [$diaSemana]);
    }
}
