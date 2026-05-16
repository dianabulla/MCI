    public function tienePermiso($accion) {
        $usuarioId = $_SESSION['usuario_id'] ?? null;
        if (!$usuarioId) {
            return false;
        }

        $sql = "SELECT 1 FROM permisos WHERE usuario_id = :usuarioId AND accion = :accion LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':usuarioId', $usuarioId);
        $stmt->bindParam(':accion', $accion);
        $stmt->execute();

        return (bool)$stmt->fetchColumn();
    }