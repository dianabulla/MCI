<?php
/**
 * Modelo Base - Clase padre para todos los modelos
 */

class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';

    public function __construct() {
        global $pdo;
        $this->db = $pdo;
    }

    /**
     * Obtener todos los registros
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }

    /**
     * Obtener un registro por ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Crear un nuevo registro
     */
    public function create($data) {
        try {
            $fields = array_keys($data);
            $values = array_values($data);
            $placeholders = array_fill(0, count($fields), '?');
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Error preparando query: " . implode(", ", $this->db->errorInfo()));
            }
            
            $result = $stmt->execute($values);
            
            if (!$result) {
                throw new Exception("Error ejecutando query: " . implode(", ", $stmt->errorInfo()));
            }
            
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Error en create(): " . $e->getMessage());
            error_log("SQL: " . ($sql ?? 'no generado'));
            error_log("Data: " . print_r($data, true));
            throw $e;
        }
    }

    /**
     * Actualizar un registro
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " 
                WHERE {$this->primaryKey} = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    /**
     * Eliminar un registro
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Ejecutar una consulta personalizada
     */
    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

// Compatibilidad: permite usar BaseModel con namespace (App\Models\BaseModel)
if (!class_exists('App\\Models\\BaseModel', false)) {
    class_alias('BaseModel', 'App\\Models\\BaseModel');
}
