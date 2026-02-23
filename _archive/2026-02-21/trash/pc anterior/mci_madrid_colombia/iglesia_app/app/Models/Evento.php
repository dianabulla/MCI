<?php
/**
 * Modelo Evento
 */

namespace App\Models;

class Evento extends BaseModel {
    protected string $table = 'EVENTO';
    protected array $fillable = [
        'nombre_evento',
        'fecha',
        'hora',
        'lugar',
        'cupo_maximo',
        'observacion'
    ];

    /**
     * Obtiene todos los asistentes registrados a un evento
     * 
     * @param int $idEvento
     * @return array
     */
    public function getAsistentes(int $idEvento): array {
        $sql = "SELECT p.* FROM PERSONA p
                INNER JOIN REGISTRO_EVENTO re ON p.id_persona = re.id_persona
                WHERE re.id_evento = ? AND re.activo = 1 AND p.activo = 1
                ORDER BY p.nombre";
        
        return $this->db->fetchAll($sql, [$idEvento]);
    }

    /**
     * Registra una persona en un evento
     * 
     * @param int $idPersona
     * @param int $idEvento
     * @return bool
     */
    public function registrarAsistente(int $idPersona, int $idEvento): bool {
        // Verificar si no se excede el cupo
        $evento = $this->find($idEvento);
        
        if ($evento['cupo_maximo']) {
            $registrados = $this->getTotalAsistentes($idEvento);
            
            if ($registrados >= $evento['cupo_maximo']) {
                return false;
            }
        }

        $sql = "INSERT INTO REGISTRO_EVENTO 
                (id_persona, id_evento, activo, fecha_creacion, fecha_modificacion)
                VALUES (?, ?, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE activo = 1, fecha_modificacion = NOW()";
        
        return $this->db->query($sql, [$idPersona, $idEvento])->rowCount() > 0;
    }

    /**
     * Desregistra una persona de un evento
     * 
     * @param int $idPersona
     * @param int $idEvento
     * @return bool
     */
    public function desregistrarAsistente(int $idPersona, int $idEvento): bool {
        return $this->db->update(
            'REGISTRO_EVENTO',
            ['activo' => false],
            'id_persona = ? AND id_evento = ?',
            [$idPersona, $idEvento]
        ) > 0;
    }

    /**
     * Obtiene el total de asistentes registrados
     * 
     * @param int $idEvento
     * @return int
     */
    public function getTotalAsistentes(int $idEvento): int {
        $sql = "SELECT COUNT(*) as total FROM REGISTRO_EVENTO 
                WHERE id_evento = ? AND activo = 1";
        
        $result = $this->db->fetchOne($sql, [$idEvento]);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Obtiene eventos próximos
     * 
     * @param int $dias
     * @return array
     */
    public function getProximos(int $dias = 30): array {
        $sql = "SELECT * FROM EVENTO 
                WHERE fecha BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND activo = 1
                ORDER BY fecha, hora";
        
        return $this->db->fetchAll($sql, [$dias]);
    }

    /**
     * Obtiene eventos por mes
     * 
     * @param int $mes
     * @param int $anio
     * @return array
     */
    public function getPorMes(int $mes, int $anio): array {
        $sql = "SELECT * FROM EVENTO 
                WHERE MONTH(fecha) = ? AND YEAR(fecha) = ? AND activo = 1
                ORDER BY fecha, hora";
        
        return $this->db->fetchAll($sql, [$mes, $anio]);
    }

    /**
     * Busca eventos por nombre
     * 
     * @param string $nombre
     * @return array
     */
    public function buscarPorNombre(string $nombre): array {
        $sql = "SELECT * FROM EVENTO WHERE nombre_evento LIKE ? AND activo = 1";
        return $this->db->fetchAll($sql, ["%{$nombre}%"]);
    }

    /**
     * Verifica si una persona ya está registrada en un evento
     * 
     * @param int $idPersona
     * @param int $idEvento
     * @return bool
     */
    public function estaRegistrado(int $idPersona, int $idEvento): bool {
        $sql = "SELECT COUNT(*) as total FROM REGISTRO_EVENTO 
                WHERE id_persona = ? AND id_evento = ? AND activo = 1";
        
        $result = $this->db->fetchOne($sql, [$idPersona, $idEvento]);
        return $result['total'] > 0;
    }
}
