<?php
/**
 * Modelo Ministerio
 */

namespace App\Models;

class Ministerio extends BaseModel {
    protected string $table = 'MINISTERIO';
    protected array $fillable = [
        'nombre_ministerio',
        'descripcion',
        'id_pastor_lider',
        'observacion'
    ];

    /**
     * Obtiene el pastor/líder del ministerio
     * 
     * @param int $idMinisterio
     * @return array|null
     */
    public function getLider(int $idMinisterio): ?array {
        $ministerio = $this->find($idMinisterio);
        
        if (!$ministerio) {
            return null;
        }

        $sql = "SELECT * FROM PERSONA WHERE id_persona = ? AND activo = 1";
        return $this->db->fetchOne($sql, [$ministerio['id_pastor_lider']]);
    }

    /**
     * Obtiene todos los miembros de un ministerio
     * 
     * @param int $idMinisterio
     * @return array
     */
    public function getMiembros(int $idMinisterio): array {
        $sql = "SELECT p.* FROM PERSONA p
                INNER JOIN PERSONA_MINISTERIO pm ON p.id_persona = pm.id_persona
                WHERE pm.id_ministerio = ? AND pm.activo = 1 AND p.activo = 1
                ORDER BY p.nombre";
        
        return $this->db->fetchAll($sql, [$idMinisterio]);
    }

    /**
     * Añade una persona al ministerio
     * 
     * @param int $idPersona
     * @param int $idMinisterio
     * @param string $fechaInicio
     * @return bool
     */
    public function agregarMiembro(int $idPersona, int $idMinisterio, string $fechaInicio = null): bool {
        $fechaInicio = $fechaInicio ?? date('Y-m-d');
        
        $sql = "INSERT INTO PERSONA_MINISTERIO 
                (id_persona, id_ministerio, fecha_inicio, activo, fecha_creacion, fecha_modificacion)
                VALUES (?, ?, ?, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE activo = 1, fecha_modificacion = NOW()";
        
        return $this->db->query($sql, [$idPersona, $idMinisterio, $fechaInicio])->rowCount() > 0;
    }

    /**
     * Elimina una persona del ministerio
     * 
     * @param int $idPersona
     * @param int $idMinisterio
     * @return bool
     */
    public function eliminarMiembro(int $idPersona, int $idMinisterio): bool {
        return $this->db->update(
            'PERSONA_MINISTERIO',
            ['activo' => false],
            'id_persona = ? AND id_ministerio = ?',
            [$idPersona, $idMinisterio]
        ) > 0;
    }

    /**
     * Obtiene el total de miembros en un ministerio
     * 
     * @param int $idMinisterio
     * @return int
     */
    public function getTotalMiembros(int $idMinisterio): int {
        $sql = "SELECT COUNT(*) as total FROM PERSONA_MINISTERIO 
                WHERE id_ministerio = ? AND activo = 1";
        
        $result = $this->db->fetchOne($sql, [$idMinisterio]);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Busca ministerios por nombre
     * 
     * @param string $nombre
     * @return array
     */
    public function buscarPorNombre(string $nombre): array {
        $sql = "SELECT * FROM MINISTERIO WHERE nombre_ministerio LIKE ? AND activo = 1";
        return $this->db->fetchAll($sql, ["%{$nombre}%"]);
    }
}
