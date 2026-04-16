<?php

require_once APP . '/Models/BaseModel.php';

class EscuelaFormacionInscripcion extends BaseModel {
    protected $table = 'escuela_formacion_inscripcion';
    protected $primaryKey = 'Id_Inscripcion';

    public function __construct() {
        parent::__construct();
        $this->ensureTableExists();
    }

    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            Id_Inscripcion INT AUTO_INCREMENT PRIMARY KEY,
            Id_Persona INT NULL,
            Nombre VARCHAR(160) NOT NULL,
            Genero VARCHAR(40) NULL,
            Telefono VARCHAR(40) NULL,
            Cedula VARCHAR(50) NULL,
            Lider VARCHAR(160) NULL,
            Id_Ministerio INT NULL,
            Nombre_Ministerio VARCHAR(160) NULL,
            Programa VARCHAR(40) NOT NULL,
            Fuente VARCHAR(80) NOT NULL DEFAULT 'Formulario público',
            Asistio_Clase TINYINT(1) NULL,
            Fecha_Asistencia_Clase DATETIME NULL,
            Fecha_Registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_programa (Programa),
            KEY idx_persona (Id_Persona),
            KEY idx_telefono (Telefono),
            KEY idx_cedula (Cedula)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->db->exec($sql);

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Genero']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Genero VARCHAR(40) NULL AFTER Nombre");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Genero en escuela_formacion_inscripcion: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Asistio_Clase']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Asistio_Clase TINYINT(1) NULL AFTER Fuente");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Asistio_Clase en escuela_formacion_inscripcion: ' . $e->getMessage());
        }

        try {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
            $stmt->execute(['Fecha_Asistencia_Clase']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Fecha_Asistencia_Clase DATETIME NULL AFTER Asistio_Clase");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Fecha_Asistencia_Clase en escuela_formacion_inscripcion: ' . $e->getMessage());
        }
    }

    public function actualizarAsistenciaClase($idInscripcion, $asistio) {
        $idInscripcion = (int)$idInscripcion;
        if ($idInscripcion <= 0) {
            return false;
        }

        if ($asistio === null) {
            return $this->execute(
                "UPDATE {$this->table}
                 SET Asistio_Clase = NULL,
                     Fecha_Asistencia_Clase = NULL
                 WHERE Id_Inscripcion = ?",
                [$idInscripcion]
            );
        }

        return $this->execute(
            "UPDATE {$this->table}
             SET Asistio_Clase = ?,
                 Fecha_Asistencia_Clase = NOW()
             WHERE Id_Inscripcion = ?",
            [$asistio ? 1 : 0, $idInscripcion]
        );
    }

    public function existeInscripcionPersonaPrograma($idPersona, $programa) {
        $idPersona = (int)$idPersona;
        $programa = trim((string)$programa);
        if ($idPersona <= 0 || $programa === '') {
            return false;
        }

        $rows = $this->query(
            "SELECT Id_Inscripcion FROM {$this->table} WHERE Id_Persona = ? AND Programa = ? LIMIT 1",
            [$idPersona, $programa]
        );

        return !empty($rows);
    }

    public function crearDesdePersonaSiNoExiste($idPersona, $programa, $fuente = 'Escuelas de formacion (asistencia)') {
        $idPersona = (int)$idPersona;
        $programa = trim((string)$programa);

        if ($idPersona <= 0 || $programa === '') {
            return false;
        }

        if ($this->existeInscripcionPersonaPrograma($idPersona, $programa)) {
            return true;
        }

        $rows = $this->query(
            "SELECT
                p.Id_Persona,
                TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre,
                p.Genero,
                p.Telefono,
                p.Numero_Documento,
                p.Id_Ministerio,
                m.Nombre_Ministerio,
                TRIM(CONCAT(COALESCE(l.Nombre, ''), ' ', COALESCE(l.Apellido, ''))) AS Nombre_Lider
             FROM persona p
             LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
             LEFT JOIN persona l ON p.Id_Lider = l.Id_Persona
             WHERE p.Id_Persona = ?
             LIMIT 1",
            [$idPersona]
        );

        if (empty($rows)) {
            return false;
        }

        $persona = $rows[0];
        $data = [
            'Id_Persona' => $idPersona,
            'Nombre' => (string)($persona['Nombre'] ?? ''),
            'Genero' => trim((string)($persona['Genero'] ?? '')) !== '' ? (string)$persona['Genero'] : null,
            'Telefono' => trim((string)($persona['Telefono'] ?? '')) !== '' ? (string)$persona['Telefono'] : null,
            'Cedula' => trim((string)($persona['Numero_Documento'] ?? '')) !== '' ? (string)$persona['Numero_Documento'] : null,
            'Lider' => trim((string)($persona['Nombre_Lider'] ?? '')) !== '' ? (string)$persona['Nombre_Lider'] : null,
            'Id_Ministerio' => (int)($persona['Id_Ministerio'] ?? 0) > 0 ? (int)$persona['Id_Ministerio'] : null,
            'Nombre_Ministerio' => trim((string)($persona['Nombre_Ministerio'] ?? '')) !== '' ? (string)$persona['Nombre_Ministerio'] : null,
            'Programa' => $programa,
            'Fuente' => $fuente
        ];

        $this->create($data);
        return true;
    }

    public function getListado($programa = '', $buscar = '', $limit = 300) {
        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = 300;
        }
        if ($limit > 1000) {
            $limit = 1000;
        }

        $programa = trim((string)$programa);
        $buscar = trim((string)$buscar);

        $where = [];
        $params = [];

        if (in_array($programa, ['universidad_vida', 'capacitacion_destino'], true)) {
            $where[] = 's.Programa = ?';
            $params[] = $programa;
        }

        if ($buscar !== '') {
            $where[] = '(s.Nombre LIKE ? OR s.Genero LIKE ? OR s.Telefono LIKE ? OR s.Cedula LIKE ? OR s.Lider LIKE ? OR s.Nombre_Ministerio LIKE ?)';
            $like = '%' . $buscar . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "SELECT
                    s.*,
                    CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, '')) AS Nombre_Persona_Actual
                FROM {$this->table} s
                LEFT JOIN persona p ON s.Id_Persona = p.Id_Persona";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= " ORDER BY s.Fecha_Registro DESC, s.Id_Inscripcion DESC LIMIT {$limit}";

        return $this->query($sql, $params);
    }

    public function getResumenProgramas() {
        $sql = "SELECT Programa, COUNT(*) AS Total
                FROM {$this->table}
                GROUP BY Programa";

        $rows = $this->query($sql);
        $resumen = [
            'total' => 0,
            'universidad_vida' => 0,
            'capacitacion_destino' => 0,
            'otros' => 0,
        ];

        foreach ($rows as $row) {
            $programa = (string)($row['Programa'] ?? '');
            $total = (int)($row['Total'] ?? 0);
            $resumen['total'] += $total;

            if ($programa === 'universidad_vida' || $programa === 'capacitacion_destino') {
                $resumen[$programa] = $total;
            } else {
                $resumen['otros'] += $total;
            }
        }

        return $resumen;
    }
}
