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
        $sql = "SELECT * FROM {$this->table} ORDER BY Fecha_Registro DESC, Id_Nehemias DESC";
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

        // Filtro por Subido_Link vacío
        if (isset($filtros['subido_link_vacio']) && $filtros['subido_link_vacio'] === '1') {
            $sql .= " AND (Subido_Link IS NULL OR TRIM(Subido_Link) = '')";
        }

        // Filtro por Subido_Link con valor
        if (isset($filtros['subido_link_lleno']) && $filtros['subido_link_lleno'] === '1') {
            $sql .= " AND Subido_Link IS NOT NULL AND TRIM(Subido_Link) != ''";
        }

        // Filtro por En_Bogota_Subio vacío
        if (isset($filtros['bogota_subio_vacio']) && $filtros['bogota_subio_vacio'] === '1') {
            $sql .= " AND (En_Bogota_Subio IS NULL OR TRIM(En_Bogota_Subio) = '')";
        }

        // Filtro por En_Bogota_Subio con valor
        if (isset($filtros['bogota_subio_lleno']) && $filtros['bogota_subio_lleno'] === '1') {
            $sql .= " AND En_Bogota_Subio IS NOT NULL AND TRIM(En_Bogota_Subio) != ''";
        }

        // Filtro por acepta
        if (isset($filtros['acepta']) && $filtros['acepta'] !== '') {
            $sql .= " AND Acepta = ?";
            $params[] = $filtros['acepta'];
        }

        $sql .= " ORDER BY Fecha_Registro DESC, Id_Nehemias DESC";

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

    /**
     * Conteo por puesto y mesa de votación
     */
    public function getConteoPorPuestoMesa() {
        $sql = "SELECT 
                    COALESCE(NULLIF(TRIM(Puesto_Votacion), ''), 'Sin puesto') AS puesto,
                    COALESCE(NULLIF(TRIM(Mesa_Votacion), ''), 'Sin mesa') AS mesa,
                    COUNT(*) AS total
                FROM {$this->table}
                GROUP BY 
                    COALESCE(NULLIF(TRIM(Puesto_Votacion), ''), 'Sin puesto'),
                    COALESCE(NULLIF(TRIM(Mesa_Votacion), ''), 'Sin mesa')
                ORDER BY total DESC, puesto ASC, mesa ASC";

        return $this->query($sql);
    }

    /**
     * Conteo total por puesto de votación (para gráfica)
     */
    public function getConteoPorPuesto() {
        $sql = "SELECT 
                    COALESCE(NULLIF(TRIM(Puesto_Votacion), ''), 'Sin puesto') AS puesto,
                    COUNT(*) AS total
                FROM {$this->table}
                GROUP BY COALESCE(NULLIF(TRIM(Puesto_Votacion), ''), 'Sin puesto')
                ORDER BY total DESC, puesto ASC";

        return $this->query($sql);
    }
}
