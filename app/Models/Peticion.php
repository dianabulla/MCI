<?php
/**
 * Modelo Peticion
 */

require_once APP . '/Models/BaseModel.php';

class Peticion extends BaseModel {
    protected $table = 'peticion';
    protected $primaryKey = 'Id_Peticion';

    public function __construct() {
        parent::__construct();
        $this->asegurarCamposContacto();
    }

    private function asegurarCamposContacto() {
        try {
            // Verificar si los campos de contacto existen, si no agregarlos
            $columnas = $this->query("SHOW COLUMNS FROM {$this->table}");
            $columnasNombres = array_column($columnas, 'Field');
            
            if (!in_array('nombre_contacto', $columnasNombres, true)) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN nombre_contacto VARCHAR(100) NULL");
            }
            
            if (!in_array('email_contacto', $columnasNombres, true)) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN email_contacto VARCHAR(150) NULL");
            }
            
            if (!in_array('telefono_contacto', $columnasNombres, true)) {
                $this->execute("ALTER TABLE {$this->table} ADD COLUMN telefono_contacto VARCHAR(20) NULL");
            }

            // Verificar si Id_Persona es nullable, si no actualizarlo
            $columnInfo = $this->query("SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = 'Id_Persona'", [$this->table]);
            if (!empty($columnInfo) && $columnInfo[0]['IS_NULLABLE'] === 'NO') {
                $this->execute("ALTER TABLE {$this->table} MODIFY COLUMN Id_Persona INT(11) NULL");
            }
        } catch (Exception $e) {
            // Silenciar errores de alteración de tabla
            error_log('Error asegurando campos de contacto en tabla ' . $this->table . ': ' . $e->getMessage());
        }
    }

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
