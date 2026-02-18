<?php
/**
 * Modelo Persona
 */

namespace App\Models;

class Persona extends BaseModel {
    protected string $table = 'PERSONA';
    protected array $fillable = [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'fecha_nacimiento',
        'id_lider_mentor',
        'observacion'
    ];

    /**
     * Obtiene el nombre completo de la persona
     * 
     * @param array $persona
     * @return string
     */
    public function getNombreCompleto(array $persona): string {
        return "{$persona['nombre']} {$persona['apellido']}";
    }

    /**
     * Obtiene los roles de una persona
     * 
     * @param int $idPersona
     * @return array
     */
    public function getRoles(int $idPersona): array {
        $sql = "SELECT r.* FROM ROL r
                INNER JOIN PERSONA_ROL pr ON r.id_rol = pr.id_rol
                WHERE pr.id_persona = ? AND pr.activo = 1";
        
        return $this->db->fetchAll($sql, [$idPersona]);
    }

    /**
     * Obtiene los ministerios de una persona
     * 
     * @param int $idPersona
     * @return array
     */
    public function getMinisterios(int $idPersona): array {
        $sql = "SELECT m.* FROM MINISTERIO m
                INNER JOIN PERSONA_MINISTERIO pm ON m.id_ministerio = pm.id_ministerio
                WHERE pm.id_persona = ? AND pm.activo = 1";
        
        return $this->db->fetchAll($sql, [$idPersona]);
    }

    /**
     * Obtiene las células en las que participa una persona
     * 
     * @param int $idPersona
     * @return array
     */
    public function getCelulas(int $idPersona): array {
        $sql = "SELECT c.* FROM CELULA c
                INNER JOIN ASISTENCIA_CELULA ac ON c.id_celula = ac.id_celula
                WHERE ac.id_persona = ? AND ac.activo = 1
                GROUP BY c.id_celula";
        
        return $this->db->fetchAll($sql, [$idPersona]);
    }

    /**
     * Obtiene el mentor/líder de una persona
     * 
     * @param int $idPersona
     * @return array|null
     */
    public function getLider(int $idPersona): ?array {
        $persona = $this->find($idPersona);
        
        if (!$persona || !$persona['id_lider_mentor']) {
            return null;
        }

        return $this->find($persona['id_lider_mentor']);
    }

    /**
     * Obtiene todos los discípulos de una persona
     * 
     * @param int $idPersona
     * @return array
     */
    public function getDiscipulos(int $idPersona): array {
        $sql = "SELECT * FROM PERSONA WHERE id_lider_mentor = ? AND activo = 1";
        return $this->db->fetchAll($sql, [$idPersona]);
    }

    /**
     * Asigna un rol a una persona
     * 
     * @param int $idPersona
     * @param int $idRol
     * @return bool
     */
    public function asignarRol(int $idPersona, int $idRol): bool {
        $sql = "INSERT INTO PERSONA_ROL (id_persona, id_rol, activo, fecha_creacion, fecha_modificacion)
                VALUES (?, ?, 1, NOW(), NOW())
                ON DUPLICATE KEY UPDATE activo = 1, fecha_modificacion = NOW()";
        
        return $this->db->query($sql, [$idPersona, $idRol])->rowCount() > 0;
    }

    /**
     * Desasigna un rol a una persona
     * 
     * @param int $idPersona
     * @param int $idRol
     * @return bool
     */
    public function desasignarRol(int $idPersona, int $idRol): bool {
        return $this->db->delete(
            'PERSONA_ROL',
            'id_persona = ? AND id_rol = ?',
            [$idPersona, $idRol]
        ) > 0;
    }

    /**
     * Busca personas por nombre
     * 
     * @param string $nombre
     * @return array
     */
    public function buscarPorNombre(string $nombre): array {
        $sql = "SELECT * FROM PERSONA WHERE (nombre LIKE ? OR apellido LIKE ?) AND activo = 1";
        return $this->db->fetchAll($sql, ["%{$nombre}%", "%{$nombre}%"]);
    }
}
