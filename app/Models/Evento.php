<?php
/**
 * Modelo Evento
 */

require_once APP . '/Models/BaseModel.php';

class Evento extends BaseModel {
    protected $table = 'evento';
    protected $primaryKey = 'Id_Evento';

    public function __construct() {
        parent::__construct();
        $this->asegurarColumnasMultimedia();
    }

    /**
     * Obtener eventos próximos
     */
    public function getUpcoming() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Fecha_Evento >= CURDATE()
                ORDER BY Fecha_Evento ASC, Hora_Evento ASC";
        return $this->query($sql);
    }

    /**
     * Obtener eventos próximos con aislamiento de rol
     */
    public function getUpcomingWithRole($filtroRol) {
        $sql = "SELECT * FROM {$this->table}
                WHERE Fecha_Evento >= CURDATE()
                AND $filtroRol
                ORDER BY Fecha_Evento ASC, Hora_Evento ASC";
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
                ORDER BY Fecha_Evento ASC, Hora_Evento ASC";
        return $this->query($sql, [$fechaInicio, $fechaFin]);
    }

    /**
     * Obtener eventos con aislamiento de rol
     */
    public function getAllWithRole($filtroRol) {
        $sql = "SELECT * FROM {$this->table}
                WHERE $filtroRol
                ORDER BY Fecha_Evento DESC";
        return $this->query($sql);
    }

    private function asegurarColumnasMultimedia() {
        $columnasEsperadas = [
            'Imagen_Evento' => "ALTER TABLE {$this->table} ADD COLUMN Imagen_Evento VARCHAR(255) NULL AFTER Lugar_Evento",
            'Video_Evento' => "ALTER TABLE {$this->table} ADD COLUMN Video_Evento VARCHAR(255) NULL AFTER Imagen_Evento"
        ];

        foreach ($columnasEsperadas as $columna => $sqlAlter) {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute([$columna]);
            $existe = $stmt->fetch();

            if (!$existe) {
                $this->db->exec($sqlAlter);
            }
        }
    }
}
