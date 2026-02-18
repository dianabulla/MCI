<?php
/**
 * Modelo Evento
 */

require_once APP . '/Models/BaseModel.php';

class Evento extends BaseModel {
    protected $table = 'evento';
    protected $primaryKey = 'Id_Evento';

    /**
     * Obtener eventos próximos
     */
    public function getUpcoming() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Fecha_Evento >= CURDATE()
                ORDER BY Fecha_Evento ASC";
        return $this->query($sql);
    }

    /**
     * Obtener eventos pasados
     */
    public function getPast() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Fecha_Evento < CURDATE()
                ORDER BY Fecha_Evento DESC";
        return $this->query($sql);
    }

    /**
     * Obtener eventos por rango de fechas
     */
    public function getByDateRange($fechaInicio, $fechaFin) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Fecha_Evento BETWEEN ? AND ?
                ORDER BY Fecha_Evento ASC";
        return $this->query($sql, [$fechaInicio, $fechaFin]);
    }
}
