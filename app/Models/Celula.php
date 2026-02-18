<?php
/**
 * Modelo Celula
 */

require_once APP . '/Models/BaseModel.php';

class Celula extends BaseModel {
    protected $table = 'celula';
    protected $primaryKey = 'Id_Celula';

    /**
     * Obtener célula por ID con nombre del líder
     */
    public function getById($id) {
        $sql = "SELECT c.*, 
                CONCAT(p.Nombre, ' ', p.Apellido) as Nombre_Lider,
                CONCAT(li.Nombre, ' ', li.Apellido) as Nombre_Lider_Inmediato,
                CONCAT(an.Nombre, ' ', an.Apellido) as Nombre_Anfitrion
                FROM {$this->table} c
                LEFT JOIN persona p ON c.Id_Lider = p.Id_Persona
                LEFT JOIN persona li ON c.Id_Lider_Inmediato = li.Id_Persona
                LEFT JOIN persona an ON c.Id_Anfitrion = an.Id_Persona
                WHERE c.{$this->primaryKey} = ?";
        $result = $this->query($sql, [$id]);
        return $result[0] ?? null;
    }

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
     * Obtener células con contador de miembros filtradas por ministerio del líder
     */
    public function getByMinisterioWithMemberCount($idMinisterio) {
        $sql = "SELECT c.*, 
                COUNT(CASE WHEN p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL THEN 1 END) as Total_Miembros,
                CONCAT(l.Nombre, ' ', l.Apellido) as Nombre_Lider
                FROM {$this->table} c
                LEFT JOIN persona p ON c.Id_Celula = p.Id_Celula
                LEFT JOIN persona l ON c.Id_Lider = l.Id_Persona
                WHERE l.Id_Ministerio = ?
                GROUP BY c.Id_Celula
                ORDER BY c.Nombre_Celula";
        return $this->query($sql, [$idMinisterio]);
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

    /**
     * Obtener células con contador de miembros y aislamiento de rol
     */
    public function getAllWithMemberCountAndRole($filtroRol) {
        $sql = "SELECT c.*, 
                COUNT(CASE WHEN p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL THEN 1 END) as Total_Miembros,
                CONCAT(l.Nombre, ' ', l.Apellido) as Nombre_Lider
                FROM {$this->table} c
                LEFT JOIN persona p ON c.Id_Celula = p.Id_Celula
                LEFT JOIN persona l ON c.Id_Lider = l.Id_Persona
                WHERE $filtroRol
                GROUP BY c.Id_Celula
                ORDER BY c.Nombre_Celula";
        return $this->query($sql);
    }
}
