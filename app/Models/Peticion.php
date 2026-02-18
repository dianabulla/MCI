<?php
/**
 * Modelo Peticion
 */

require_once APP . '/Models/BaseModel.php';

class Peticion extends BaseModel {
    protected $table = 'peticion';
    protected $primaryKey = 'Id_Peticion';

    /**
     * Obtener peticiones con información de la persona
     */
    public function getAllWithPerson() {
        $sql = "SELECT pet.*, 
                CONCAT(p.Nombre, ' ', p.Apellido) as Nombre_Completo
                FROM {$this->table} pet
                LEFT JOIN persona p ON pet.Id_Persona = p.Id_Persona
                ORDER BY pet.Fecha_Peticion DESC";
        return $this->query($sql);
    }

    /**
     * Obtener peticiones por persona
     */
    public function getByPersona($idPersona) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Id_Persona = ?
                ORDER BY Fecha_Peticion DESC";
        return $this->query($sql, [$idPersona]);
    }

    /**
     * Obtener peticiones activas (no respondidas)
     */
    public function getActive() {
        $sql = "SELECT pet.*, 
                CONCAT(p.Nombre, ' ', p.Apellido) as Nombre_Completo
                FROM {$this->table} pet
                LEFT JOIN persona p ON pet.Id_Persona = p.Id_Persona
                WHERE pet.Estado_Peticion = 'Pendiente'
                ORDER BY pet.Fecha_Peticion DESC";
        return $this->query($sql);
    }

    /**
     * Obtener peticiones con información de persona y aislamiento de rol
     */
    public function getAllWithPersonAndRole($filtroRol) {
        $sql = "SELECT pet.*, 
                CONCAT(p.Nombre, ' ', p.Apellido) as Nombre_Completo
                FROM {$this->table} pet
                LEFT JOIN persona p ON pet.Id_Persona = p.Id_Persona
                WHERE $filtroRol
                ORDER BY pet.Fecha_Peticion DESC";
        return $this->query($sql);
    }
}
