<?php
/**
 * Clase Base Model
 * Proporciona funcionalidad común para todos los modelos
 */

namespace App\Models;

use App\Config\Database;
use PDO;

abstract class BaseModel {
    protected string $table;
    protected array $fillable = [];
    protected array $hidden = [];
    protected Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtiene todos los registros de la tabla
     * 
     * @return array
     */
    public function all(): array {
        $sql = "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY fecha_creacion DESC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Busca un registro por ID
     * 
     * @param int $id
     * @return array|null
     */
    public function find(int $id): ?array {
        $sql = "SELECT * FROM {$this->table} WHERE id_{$this->table} = ? AND activo = 1";
        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Busca registros que coincidan con los criterios
     * 
     * @param string $field
     * @param string $value
     * @return array
     */
    public function where(string $field, string $value): array {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} = ? AND activo = 1";
        return $this->db->fetchAll($sql, [$value]);
    }

    /**
     * Crea un nuevo registro
     * 
     * @param array $data
     * @return int ID del nuevo registro
     */
    public function create(array $data): int {
        $data = $this->filterFillable($data);
        $data['fecha_creacion'] = date('Y-m-d H:i:s');
        $data['fecha_modificacion'] = date('Y-m-d H:i:s');
        
        return $this->db->insert($this->table, $data);
    }

    /**
     * Actualiza un registro
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool {
        $data = $this->filterFillable($data);
        $data['fecha_modificacion'] = date('Y-m-d H:i:s');
        
        return $this->db->update(
            $this->table,
            $data,
            "id_{$this->table} = ?",
            [$id]
        ) > 0;
    }

    /**
     * Elimina lógicamente un registro (soft delete)
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool {
        return $this->update($id, ['activo' => false]);
    }

    /**
     * Filtra los datos según el atributo fillable
     * 
     * @param array $data
     * @return array
     */
    protected function filterFillable(array $data): array {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Pagina los resultados
     * 
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function paginate(int $page = 1, int $perPage = 10): array {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY fecha_creacion DESC LIMIT ? OFFSET ?";
        $items = $this->db->fetchAll($sql, [$perPage, $offset]);
        
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE activo = 1";
        $total = $this->db->fetchOne($countSql)['total'] ?? 0;
        
        return [
            'items' => $items,
            'total' => (int)$total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Busca múltiples registros con búsqueda libre
     * 
     * @param string $field
     * @param string $searchTerm
     * @return array
     */
    public function search(string $field, string $searchTerm): array {
        $sql = "SELECT * FROM {$this->table} WHERE {$field} LIKE ? AND activo = 1";
        return $this->db->fetchAll($sql, ["%{$searchTerm}%"]);
    }

    /**
     * Obtiene el total de registros activos
     * 
     * @return int
     */
    public function count(): int {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE activo = 1";
        $result = $this->db->fetchOne($sql);
        return (int)($result['total'] ?? 0);
    }
}
