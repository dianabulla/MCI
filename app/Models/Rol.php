<?php
/**
 * Modelo Rol
 */

require_once APP . '/Models/BaseModel.php';

class Rol extends BaseModel {
    protected $table = 'rol';
    protected $primaryKey = 'Id_Rol';

    /**
     * Obtener roles con contador de personas
     */
    public function getAllWithPersonCount() {
        $sql = "SELECT r.*, 
                COUNT(p.Id_Persona) as Total_Personas
                FROM {$this->table} r
                LEFT JOIN persona p ON r.Id_Rol = p.Id_Rol
                GROUP BY r.Id_Rol
                ORDER BY r.Nombre_Rol";
        return $this->query($sql);
    }
}
