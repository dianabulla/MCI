<?php
/**
 * Modelo NinoNavidad
 */

require_once APP . '/Models/BaseModel.php';

class NinoNavidad extends BaseModel {
    protected $table = 'ninos_navidad';
    protected $primaryKey = 'Id_Registro';

    /**
     * Obtener todos los registros con información del ministerio
     */
    public function getAllWithMinisterio() {
        $sql = "SELECT n.*, m.Nombre_Ministerio
                FROM {$this->table} n
                LEFT JOIN ministerio m ON n.Id_Ministerio = m.Id_Ministerio
                ORDER BY n.Nombre_Apellidos ASC";
        return $this->query($sql);
    }

    /**
     * Obtener registros filtrados por ministerio
     */
    public function getAllByMinisterio($idMinisterio) {
        $sql = "SELECT n.*, m.Nombre_Ministerio 
                FROM {$this->table} n
                LEFT JOIN ministerio m ON n.Id_Ministerio = m.Id_Ministerio
                WHERE n.Id_Ministerio = ?
                ORDER BY n.Nombre_Apellidos ASC";
        return $this->query($sql, [$idMinisterio]);
    }

    /**
     * Marcar obsequio como entregado
     */
    public function marcarComoEntregado($idRegistro) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET Estado_Entrega = 'Entregado', 
                        Fecha_Entrega = NOW() 
                    WHERE Id_Registro = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$idRegistro]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Obsequio marcado como entregado exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al actualizar el estado'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calcular edad en años
     */
    public function calcularEdad($fechaNacimiento) {
        $fecha = new DateTime($fechaNacimiento);
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha);
        return $edad->y;
    }

    /**
     * Validar que el niño sea menor de 11 años
     */
    public function validarEdad($fechaNacimiento) {
        $edad = $this->calcularEdad($fechaNacimiento);
        return $edad < 11;
    }

    /**
     * Registrar niño para obsequio
     */
    public function registrarNino($data) {
        try {
            // Calcular edad
            $data['Edad'] = $this->calcularEdad($data['Fecha_Nacimiento']);
            
            // Validar edad
            if ($data['Edad'] >= 11) {
                return [
                    'success' => false,
                    'message' => 'El obsequio solo aplica para niños menores de 11 años'
                ];
            }

            // Insertar registro
            $id = $this->create($data);

            if ($id) {
                return [
                    'success' => true,
                    'message' => 'Registro exitoso. ¡El niño ha sido inscrito para recibir su obsequio!',
                    'id' => $id
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al registrar. Por favor intente nuevamente.'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estadísticas
     */
    public function getEstadisticas() {
        $sql = "SELECT 
                    COUNT(*) as total_registros,
                    COUNT(CASE WHEN Edad <= 5 THEN 1 END) as ninos_0_5,
                    COUNT(CASE WHEN Edad BETWEEN 6 AND 10 THEN 1 END) as ninos_6_10,
                    m.Nombre_Ministerio,
                    COUNT(n.Id_Registro) as registros_por_ministerio
                FROM {$this->table} n
                LEFT JOIN ministerio m ON n.Id_Ministerio = m.Id_Ministerio
                GROUP BY m.Id_Ministerio, m.Nombre_Ministerio
                WITH ROLLUP";
        return $this->query($sql);
    }
}
