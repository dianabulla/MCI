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
    public function getAllWithPersonAndRole($filtroRol, $idCelula = null) {
        $sql = "SELECT pet.*, 
                CONCAT(p.Nombre, ' ', p.Apellido) as Nombre_Completo,
                p.Id_Celula,
                c.Nombre_Celula
                FROM {$this->table} pet
                LEFT JOIN persona p ON pet.Id_Persona = p.Id_Persona
                LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                WHERE $filtroRol
                ORDER BY pet.Fecha_Peticion DESC";

        $params = [];
        if ($idCelula !== null && $idCelula !== '') {
            if ((string)$idCelula === '0') {
                $sql = "SELECT pet.*, 
                        CONCAT(p.Nombre, ' ', p.Apellido) as Nombre_Completo,
                        p.Id_Celula,
                        c.Nombre_Celula
                        FROM {$this->table} pet
                        LEFT JOIN persona p ON pet.Id_Persona = p.Id_Persona
                        LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                        WHERE $filtroRol AND p.Id_Celula IS NULL
                        ORDER BY pet.Fecha_Peticion DESC";
            } else {
                $sql = "SELECT pet.*, 
                        CONCAT(p.Nombre, ' ', p.Apellido) as Nombre_Completo,
                        p.Id_Celula,
                        c.Nombre_Celula
                        FROM {$this->table} pet
                        LEFT JOIN persona p ON pet.Id_Persona = p.Id_Persona
                        LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
                        WHERE $filtroRol AND p.Id_Celula = ?
                        ORDER BY pet.Fecha_Peticion DESC";
                $params[] = $idCelula;
            }
        }

        return $this->query($sql, $params);
    }
}
