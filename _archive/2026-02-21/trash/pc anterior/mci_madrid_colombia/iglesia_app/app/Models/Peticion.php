<?php
/**
 * Modelo Petición
 */

namespace App\Models;

class Peticion extends BaseModel {
    protected string $table = 'PETICION';
    protected array $fillable = [
        'descripcion_peticion',
        'estado',
        'id_persona_solicitante',
        'observacion'
    ];

    /**
     * Obtiene todas las peticiones de una persona
     * 
     * @param int $idPersona
     * @return array
     */
    public function getPorPersona(int $idPersona): array {
        $sql = "SELECT * FROM PETICION 
                WHERE id_persona_solicitante = ? AND activo = 1
                ORDER BY fecha_creacion DESC";
        
        return $this->db->fetchAll($sql, [$idPersona]);
    }

    /**
     * Obtiene peticiones por estado
     * 
     * @param string $estado
     * @return array
     */
    public function getPorEstado(string $estado): array {
        $sql = "SELECT p.*, CONCAT(pe.nombre, ' ', pe.apellido) as nombre_solicitante
                FROM PETICION p
                INNER JOIN PERSONA pe ON p.id_persona_solicitante = pe.id_persona
                WHERE p.estado = ? AND p.activo = 1
                ORDER BY p.fecha_creacion DESC";
        
        return $this->db->fetchAll($sql, [$estado]);
    }

    /**
     * Obtiene todas las peticiones pendientes
     * 
     * @return array
     */
    public function getPendientes(): array {
        return $this->getPorEstado('Pendiente');
    }

    /**
     * Obtiene todas las peticiones en oración
     * 
     * @return array
     */
    public function getEnOracion(): array {
        return $this->getPorEstado('En Oración');
    }

    /**
     * Obtiene todas las peticiones respondidas
     * 
     * @return array
     */
    public function getRespondidas(): array {
        return $this->getPorEstado('Respondida');
    }

    /**
     * Actualiza el estado de una petición
     * 
     * @param int $idPeticion
     * @param string $nuevoEstado
     * @return bool
     */
    public function actualizarEstado(int $idPeticion, string $nuevoEstado): bool {
        $estados = ['Pendiente', 'En Oración', 'Respondida'];
        
        if (!in_array($nuevoEstado, $estados)) {
            return false;
        }

        return $this->update($idPeticion, ['estado' => $nuevoEstado]);
    }

    /**
     * Obtiene estadísticas de peticiones
     * 
     * @return array
     */
    public function getEstadisticas(): array {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'En Oración' THEN 1 ELSE 0 END) as en_oracion,
                SUM(CASE WHEN estado = 'Respondida' THEN 1 ELSE 0 END) as respondidas
                FROM PETICION
                WHERE activo = 1";
        
        return $this->db->fetchOne($sql) ?? [
            'total' => 0,
            'pendientes' => 0,
            'en_oracion' => 0,
            'respondidas' => 0
        ];
    }

    /**
     * Busca peticiones por descripción
     * 
     * @param string $termino
     * @return array
     */
    public function buscar(string $termino): array {
        $sql = "SELECT p.*, CONCAT(pe.nombre, ' ', pe.apellido) as nombre_solicitante
                FROM PETICION p
                INNER JOIN PERSONA pe ON p.id_persona_solicitante = pe.id_persona
                WHERE p.descripcion_peticion LIKE ? AND p.activo = 1
                ORDER BY p.fecha_creacion DESC";
        
        return $this->db->fetchAll($sql, ["%{$termino}%"]);
    }

    /**
     * Obtiene peticiones por rango de fechas
     * 
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return array
     */
    public function getPorRangoFechas(string $fechaInicio, string $fechaFin): array {
        $sql = "SELECT p.*, CONCAT(pe.nombre, ' ', pe.apellido) as nombre_solicitante
                FROM PETICION p
                INNER JOIN PERSONA pe ON p.id_persona_solicitante = pe.id_persona
                WHERE p.fecha_creacion BETWEEN ? AND ? AND p.activo = 1
                ORDER BY p.fecha_creacion DESC";
        
        return $this->db->fetchAll($sql, [$fechaInicio, $fechaFin]);
    }
}
