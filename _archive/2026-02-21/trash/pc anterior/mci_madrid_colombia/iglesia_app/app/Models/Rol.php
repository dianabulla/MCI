<?php
/**
 * Modelo Rol
 */

namespace App\Models;

class Rol extends BaseModel {
    protected string $table = 'ROL';
    protected array $fillable = [
        'nombre_rol',
        'observacion'
    ];

    /**
     * Obtiene todas las personas con un rol específico
     * 
     * @param int $idRol
     * @return array
     */
    public function getPersonas(int $idRol): array {
        $sql = "SELECT p.* FROM PERSONA p
                INNER JOIN PERSONA_ROL pr ON p.id_persona = pr.id_persona
                WHERE pr.id_rol = ? AND pr.activo = 1 AND p.activo = 1
                ORDER BY p.nombre";
        
        return $this->db->fetchAll($sql, [$idRol]);
    }

    /**
     * Obtiene el total de personas con un rol específico
     * 
     * @param int $idRol
     * @return int
     */
    public function getTotalPersonas(int $idRol): int {
        $sql = "SELECT COUNT(*) as total FROM PERSONA_ROL 
                WHERE id_rol = ? AND activo = 1";
        
        $result = $this->db->fetchOne($sql, [$idRol]);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Busca roles por nombre
     * 
     * @param string $nombre
     * @return array
     */
    public function buscarPorNombre(string $nombre): array {
        $sql = "SELECT * FROM ROL WHERE nombre_rol LIKE ? AND activo = 1";
        return $this->db->fetchAll($sql, ["%{$nombre}%"]);
    }

    /**
     * Verifica si un rol existe
     * 
     * @param string $nombre
     * @return bool
     */
    public function existe(string $nombre): bool {
        $sql = "SELECT COUNT(*) as total FROM ROL WHERE nombre_rol = ? AND activo = 1";
        $result = $this->db->fetchOne($sql, [$nombre]);
        return $result['total'] > 0;
    }
}
