<?php
/**
 * Modelo Nehemias
 */

require_once APP . '/Models/BaseModel.php';

class Nehemias extends BaseModel {
    protected $table = 'nehemias';
    protected $primaryKey = 'Id_Nehemias';

    /**
     * Obtener registros ordenados por fecha
     */
    public function getAllOrdered() {
        $sql = "SELECT * FROM {$this->table} ORDER BY Fecha_Registro DESC";
        return $this->query($sql);
    }

    /**
     * Obtener registros con filtros
     */
    public function getAllWithFilters($filtros = []) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        // Filtro por búsqueda general (nombre, apellido, cédula)
        if (!empty($filtros['busqueda'])) {
            $sql .= " AND (Nombres LIKE ? OR Apellidos LIKE ? OR Numero_Cedula LIKE ?)";
            $termino = '%' . $filtros['busqueda'] . '%';
            $params[] = $termino;
            $params[] = $termino;
            $params[] = $termino;
        }

        // Filtro por líder Nehemías
        if (!empty($filtros['lider_nehemias'])) {
            $sql .= " AND Lider_Nehemias LIKE ?";
            $params[] = '%' . $filtros['lider_nehemias'] . '%';
        }

        // Filtro por líder
        if (!empty($filtros['lider'])) {
            if ($filtros['lider'] === '__otros__' && !empty($filtros['lider_lista'])) {
                $placeholders = implode(', ', array_fill(0, count($filtros['lider_lista']), '?'));
                $sql .= " AND (Lider IS NULL OR Lider = '' OR Lider NOT IN ($placeholders))";
                foreach ($filtros['lider_lista'] as $ministerio) {
                    $params[] = $ministerio;
                }
            } else {
                $sql .= " AND Lider = ?";
                $params[] = $filtros['lider'];
            }
        }

        // Filtro por puesto de votación vacío
        if (isset($filtros['puesto_vacio']) && $filtros['puesto_vacio'] === '1') {
            $sql .= " AND (Puesto_Votacion IS NULL OR Puesto_Votacion = '')";
        }

        // Filtro por puesto de votación con valor
        if (isset($filtros['puesto_lleno']) && $filtros['puesto_lleno'] === '1') {
            $sql .= " AND Puesto_Votacion IS NOT NULL AND Puesto_Votacion != ''";
        }

        // Filtro por mesa de votación vacía
        if (isset($filtros['mesa_vacia']) && $filtros['mesa_vacia'] === '1') {
            $sql .= " AND (Mesa_Votacion IS NULL OR Mesa_Votacion = '')";
        }

        // Filtro por mesa de votación con valor
        if (isset($filtros['mesa_llena']) && $filtros['mesa_llena'] === '1') {
            $sql .= " AND Mesa_Votacion IS NOT NULL AND Mesa_Votacion != ''";
        }

        // Filtro por cédula vacía
        if (isset($filtros['cedula_vacia']) && $filtros['cedula_vacia'] === '1') {
            $sql .= " AND (Numero_Cedula IS NULL OR Numero_Cedula = '')";
        }

        // Filtro por acepta
        if (isset($filtros['acepta']) && $filtros['acepta'] !== '') {
            $sql .= " AND Acepta = ?";
            $params[] = $filtros['acepta'];
        }

        $sql .= " ORDER BY Fecha_Registro DESC";

        // Ejecutar consulta preparada con PDO
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        }

        return $this->query($sql);
    }

    /**
     * Obtener conteo de votantes por líder y líder Nehemias
     */
    public function getVotantesPorLider() {
        $sql = "SELECT Lider, Lider_Nehemias, COUNT(*) AS total
                FROM {$this->table}
                GROUP BY Lider, Lider_Nehemias";

        return $this->query($sql);
    }

    /**
     * Obtener conteo de votantes por ministerio (campo Lider)
     */
    public function getVotantesPorMinisterio() {
        $sql = "SELECT Lider, COUNT(*) AS total
                FROM {$this->table}
                GROUP BY Lider";

        return $this->query($sql);
    }

    /**
     * Obtener lista de ministerios (lider) distintos
     */
    public function getMinisteriosDistinct() {
        $sql = "SELECT DISTINCT Lider
                FROM {$this->table}
                WHERE Lider IS NOT NULL AND Lider != ''
                ORDER BY Lider ASC";

        return $this->query($sql);
    }
}
