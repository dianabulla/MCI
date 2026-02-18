<?php
/**
 * Modelo Celula
 */

require_once APP . '/Models/BaseModel.php';

class Celula extends BaseModel {
    protected $table = 'celula';
    protected $primaryKey = 'Id_Celula';

    /**
     * Obtener células con contador de miembros
     */
    public function getAllWithMemberCount() {
        $sql = "SELECT c.*, 
                COUNT(CASE WHEN p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL THEN 1 END) as Total_Miembros,
                CONCAT(l.Nombre, ' ', l.Apellido) as Nombre_Lider
                FROM {$this->table} c
                LEFT JOIN persona p ON c.Id_Celula = p.Id_Celula
                LEFT JOIN persona l ON c.Id_Lider = l.Id_Persona
                GROUP BY c.Id_Celula
                ORDER BY c.Nombre_Celula";
        return $this->query($sql);
    }

    /**
     * Obtener detalles de una célula con miembros
     */
    public function getWithMembers($id) {
        $celula = $this->getById($id);
        if ($celula) {
            $sql = "SELECT * FROM persona 
                    WHERE Id_Celula = ? 
                    AND (Estado_Cuenta = 'Activo' OR Estado_Cuenta IS NULL)
                    ORDER BY Apellido, Nombre";
            $celula['miembros'] = $this->query($sql, [$id]);
        }
        return $celula;
    }
}
