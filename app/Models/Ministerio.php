<?php
/**
 * Modelo Ministerio
 */

require_once APP . '/Models/BaseModel.php';

class Ministerio extends BaseModel {
    protected $table = 'ministerio';
    protected $primaryKey = 'Id_Ministerio';

    /**
     * Obtener ministerio por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $result = $this->query($sql, [$id]);
        return $result ? $result[0] : null;
    }

    /**
     * Obtener ministerios con contador de miembros
     */
    public function getAllWithMemberCount() {
        $sql = "SELECT m.*, 
                COUNT(p.Id_Persona) as Total_Miembros
                FROM {$this->table} m
                LEFT JOIN persona p ON m.Id_Ministerio = p.Id_Ministerio
                GROUP BY m.Id_Ministerio
                ORDER BY m.Nombre_Ministerio";
        return $this->query($sql);
    }

    /**
     * Obtener ministerios con contador de miembros y aislamiento de rol
     */
    public function getAllWithMemberCountAndRole($filtroRol) {
        $sql = "SELECT m.*, 
                COUNT(p.Id_Persona) as Total_Miembros
                FROM {$this->table} m
                LEFT JOIN persona p ON m.Id_Ministerio = p.Id_Ministerio
                WHERE $filtroRol
                GROUP BY m.Id_Ministerio
                ORDER BY m.Nombre_Ministerio";
        return $this->query($sql);
    }
}
