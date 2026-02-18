<?php
/**
 * Modelo Transmision - Gestión de transmisiones de YouTube
 */

require_once __DIR__ . '/BaseModel.php';

class Transmision extends BaseModel {
    protected $table = 'TRANSMISIONES_YOUTUBE';
    protected $primaryKey = 'Id_Transmision';

    /**
     * Obtener todas las transmisiones ordenadas por fecha
     */
    public function obtenerTodas($estado = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($estado) {
            $sql .= " WHERE Estado = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$estado]);
        } else {
            $sql .= " ORDER BY Fecha_Transmision DESC, Hora_Transmision DESC";
            $stmt = $this->db->query($sql);
        }
        
        return $stmt->fetchAll();
    }

    /**
     * Obtener la transmisión en vivo actual
     */
    public function obtenerEnVivo() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Estado = 'en_vivo' 
                ORDER BY Fecha_Transmision DESC 
                LIMIT 1";
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }

    /**
     * Obtener transmisiones próximas
     */
    public function obtenerProximas($limite = 5) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Estado = 'proximamente' 
                AND CONCAT(Fecha_Transmision, ' ', COALESCE(Hora_Transmision, '00:00:00')) > NOW()
                ORDER BY Fecha_Transmision ASC, Hora_Transmision ASC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtener transmisiones finalizadas
     */
    public function obtenerFinalizadas($limite = 10) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Estado = 'finalizada' 
                ORDER BY Fecha_Transmision DESC, Hora_Transmision DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Buscar transmisiones por nombre
     */
    public function buscar($termino) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Nombre LIKE ? 
                OR Descripcion LIKE ?
                ORDER BY Fecha_Transmision DESC";
        $stmt = $this->db->prepare($sql);
        $busqueda = "%$termino%";
        $stmt->execute([$busqueda, $busqueda]);
        return $stmt->fetchAll();
    }

    /**
     * Crear transmisión
     */
    public function crear($nombre, $url, $fecha, $hora, $estado, $descripcion, $idUsuario) {
        $data = [
            'Nombre' => $nombre,
            'URL_YouTube' => $url,
            'Fecha_Transmision' => $fecha,
            'Hora_Transmision' => $hora,
            'Estado' => $estado,
            'Descripcion' => $descripcion,
            'Id_Usuario_Creador' => $idUsuario
        ];
        
        return parent::create($data);
    }

    /**
     * Actualizar transmisión
     */
    public function actualizar($id, $nombre, $url, $fecha, $hora, $estado, $descripcion) {
        $data = [
            'Nombre' => $nombre,
            'URL_YouTube' => $url,
            'Fecha_Transmision' => $fecha,
            'Hora_Transmision' => $hora,
            'Estado' => $estado,
            'Descripcion' => $descripcion,
            'Fecha_Actualizacion' => date('Y-m-d H:i:s')
        ];
        
        return parent::update($id, $data);
    }

    /**
     * Cambiar estado de transmisión
     */
    public function cambiarEstado($id, $nuevoEstado) {
        $estadosValidos = ['en_vivo', 'finalizada', 'proximamente'];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            throw new Exception("Estado inválido: $nuevoEstado");
        }
        
        $data = [
            'Estado' => $nuevoEstado,
            'Fecha_Actualizacion' => date('Y-m-d H:i:s')
        ];
        
        return parent::update($id, $data);
    }

    /**
     * Eliminar transmisión
     */
    public function eliminar($id) {
        return parent::delete($id);
    }

    /**
     * Obtener una transmisión por ID
     */
    public function obtenerPorId($id) {
        return parent::getById($id);
    }

    /**
     * Contar transmisiones por estado
     */
    public function contarPorEstado($estado) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE Estado = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$estado]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
}
