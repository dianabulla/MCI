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
            Edad TINYINT UNSIGNED NULL,
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
            $stmt->execute(['Edad']);
            $col = $stmt->fetch();
            if (empty($col)) {
                $this->db->exec("ALTER TABLE {$this->table} ADD COLUMN Edad TINYINT UNSIGNED NULL AFTER Genero");
            }
        } catch (Exception $e) {
            error_log('No se pudo asegurar columna Edad en escuela_formacion_inscripcion: ' . $e->getMessage());
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

    private function normalizarSoloDigitos($valor) {
        return preg_replace('/\D+/', '', (string)$valor);
    }

    public function getByIdInscripcion($idInscripcion) {
        $idInscripcion = (int)$idInscripcion;
        if ($idInscripcion <= 0) {
            return null;
        }

        $rows = $this->query(
            "SELECT * FROM {$this->table} WHERE Id_Inscripcion = ? LIMIT 1",
            [$idInscripcion]
        );

        return $rows[0] ?? null;
    }

    public function buscarInscripcionesPorTelefonoOCedula($telefono = '', $cedula = '', $limit = 20) {
        $telefono = $this->normalizarSoloDigitos($telefono);
        $cedula = $this->normalizarSoloDigitos($cedula);
        $limit = max(1, min(50, (int)$limit));

        if ($telefono === '' && $cedula === '') {
            return [];
        }

        $telefonoExpr = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(s.Telefono, ''), ' ', ''), '-', ''), '(', ''), ')', ''), '+', '')";
        $cedulaExpr = "REPLACE(REPLACE(REPLACE(COALESCE(s.Cedula, ''), ' ', ''), '-', ''), '.', '')";

        $where = [];
        $params = [];

        if ($telefono !== '') {
            $where[] = "{$telefonoExpr} = ?";
            $params[] = $telefono;
        }

        if ($cedula !== '') {
            $where[] = "{$cedulaExpr} = ?";
            $params[] = $cedula;
        }

        $sql = "SELECT s.*
                FROM {$this->table} s
                WHERE " . implode(' OR ', $where) . "
                ORDER BY s.Fecha_Registro DESC, s.Id_Inscripcion DESC
                LIMIT {$limit}";

        return $this->query($sql, $params);
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

    public function getIdInscripcionPersonaPrograma($idPersona, $programa) {
        $idPersona = (int)$idPersona;
        $programa = trim((string)$programa);
        if ($idPersona <= 0 || $programa === '') {
            return 0;
        }

        $rows = $this->query(
            "SELECT Id_Inscripcion FROM {$this->table} WHERE Id_Persona = ? AND Programa = ? ORDER BY Id_Inscripcion ASC LIMIT 1",
            [$idPersona, $programa]
        );

        return !empty($rows) ? (int)($rows[0]['Id_Inscripcion'] ?? 0) : 0;
    }

    public function crearDesdePersonaSiNoExiste($idPersona, $programa, $fuente = 'Escuelas de formacion (asistencia)') {
        $idPersona = (int)$idPersona;
        $programa = trim((string)$programa);

        if ($idPersona <= 0 || $programa === '') {
            return false;
        }

        if ($this->getIdInscripcionPersonaPrograma($idPersona, $programa) > 0) {
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
            'Edad' => null,
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

    public function getListado($programa = '', $buscar = '', $limit = 300, $genero = 'todos', $idMinisterio = null, $idLider = null) {
        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = 300;
        }
        if ($limit > 1000) {
            $limit = 1000;
        }

        $programa = trim((string)$programa);
        $buscar = trim((string)$buscar);
        $genero = strtolower(trim((string)$genero));
        $idMinisterio = (int)$idMinisterio;
        $idLider = (int)$idLider;

        $where = [];
        $params = [];

        if (in_array($programa, ['universidad_vida', 'encuentro', 'bautismo', 'capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            if ($programa === 'capacitacion_destino') {
                $where[] = "s.Programa IN ('capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3')";
            } else {
                $where[] = 's.Programa = ?';
                $params[] = $programa;
            }
        }

        if ($genero === 'mujeres') {
            $where[] = "(LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%mujer%' OR LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%femen%' OR LOWER(TRIM(COALESCE(s.Genero, ''))) = 'f')";
        } elseif ($genero === 'hombres') {
            $where[] = "(LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%hombre%' OR LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%mascul%' OR LOWER(TRIM(COALESCE(s.Genero, ''))) = 'm')";
        } elseif ($genero === 'joven_hombre') {
            $where[] = "(LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%joven%' AND (LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%hombre%' OR LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%mascul%'))";
        } elseif ($genero === 'joven_mujer') {
            $where[] = "(LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%joven%' AND (LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%mujer%' OR LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%femen%'))";
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

        if ($idMinisterio > 0) {
            $where[] = '(s.Id_Ministerio = ? OR p.Id_Ministerio = ?)';
            $params[] = $idMinisterio;
            $params[] = $idMinisterio;
        }

        if ($idLider > 0) {
            $where[] = 'p.Id_Lider = ?';
            $params[] = $idLider;
        }

        $sql = "SELECT
                    s.*,
                    TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre_Persona_Actual,
                    p.Genero AS Genero_Persona_Actual,
                    TRIM(CONCAT(COALESCE(lp.Nombre, ''), ' ', COALESCE(lp.Apellido, ''))) AS Lider_Persona_Actual,
                    mp.Nombre_Ministerio AS Nombre_Ministerio_Persona_Actual
                FROM {$this->table} s
                LEFT JOIN persona p ON s.Id_Persona = p.Id_Persona
                LEFT JOIN persona lp ON p.Id_Lider = lp.Id_Persona
                LEFT JOIN ministerio mp ON p.Id_Ministerio = mp.Id_Ministerio";

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
            'encuentro' => 0,
            'bautismo' => 0,
            'capacitacion_destino' => 0,
            'capacitacion_destino_nivel_1' => 0,
            'capacitacion_destino_nivel_2' => 0,
            'capacitacion_destino_nivel_3' => 0,
            'otros' => 0,
        ];

        foreach ($rows as $row) {
            $programa = (string)($row['Programa'] ?? '');
            $total = (int)($row['Total'] ?? 0);
            $resumen['total'] += $total;

            if (in_array($programa, ['universidad_vida', 'encuentro', 'bautismo', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
                $resumen[$programa] = $total;
            } elseif ($programa === 'capacitacion_destino') {
                // Compatibilidad con registros anteriores sin nivel explícito.
                $resumen['capacitacion_destino_nivel_1'] += $total;
            } else {
                $resumen['otros'] += $total;
            }
        }

        $resumen['capacitacion_destino'] =
            (int)$resumen['capacitacion_destino_nivel_1'] +
            (int)$resumen['capacitacion_destino_nivel_2'] +
            (int)$resumen['capacitacion_destino_nivel_3'];

        return $resumen;
    }

    public function getTotalConsolidarUnico($filtroPersonas = '1=1') {
        $filtroPersonas = trim((string)$filtroPersonas);
        if ($filtroPersonas === '') {
            $filtroPersonas = '1=1';
        }

        $sql = "SELECT COUNT(*) AS Total
                FROM {$this->table} s
                LEFT JOIN persona p ON s.Id_Persona = p.Id_Persona
                                WHERE s.Programa = 'universidad_vida'
                                    AND (
                                        LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%hombre%'
                                        OR LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%mascul%'
                                        OR LOWER(TRIM(COALESCE(s.Genero, ''))) IN ('m', 'masc', 'male', 'h')
                                        OR LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%mujer%'
                                        OR LOWER(TRIM(COALESCE(s.Genero, ''))) LIKE '%femen%'
                                        OR LOWER(TRIM(COALESCE(s.Genero, ''))) IN ('f', 'fem', 'female')
                                    )
                  AND ({$filtroPersonas})";

        $rows = $this->query($sql);
        return (int)($rows[0]['Total'] ?? 0);
    }

    public function getResumenUvPorMinisterioGenero($idMinisterio = null, $idLider = null) {
        $idMinisterio = (int)$idMinisterio;
        $idLider = (int)$idLider;

        $where = ["s.Programa = 'universidad_vida'"];
        $params = [];

        if ($idMinisterio > 0) {
            $where[] = '(s.Id_Ministerio = ? OR p.Id_Ministerio = ?)';
            $params[] = $idMinisterio;
            $params[] = $idMinisterio;
        }

        if ($idLider > 0) {
            $where[] = 'p.Id_Lider = ?';
            $params[] = $idLider;
        }

        $ministerioExpr = "COALESCE(NULLIF(TRIM(s.Nombre_Ministerio), ''), NULLIF(TRIM(ms.Nombre_Ministerio), ''), NULLIF(TRIM(mp.Nombre_Ministerio), ''), 'Sin ministerio')";
        $generoExpr = "LOWER(TRIM(COALESCE(
                            NULLIF(CONVERT(s.Genero USING utf8mb4) COLLATE utf8mb4_general_ci, ''),
                            NULLIF(CONVERT(p.Genero USING utf8mb4) COLLATE utf8mb4_general_ci, ''),
                            ''
                        )))";

        $esHombre = "({$generoExpr} LIKE '%hombre%' OR {$generoExpr} LIKE '%mascul%' OR {$generoExpr} IN ('m', 'masc', 'male', 'h'))";
        $esMujer = "({$generoExpr} LIKE '%mujer%' OR {$generoExpr} LIKE '%femen%' OR {$generoExpr} IN ('f', 'fem', 'female'))";

        $sql = "SELECT
                    {$ministerioExpr} AS Ministerio,
                    COUNT(*) AS Inscritos_Total,
                    SUM(CASE WHEN {$esHombre} THEN 1 ELSE 0 END) AS Inscritos_Hombres,
                    SUM(CASE WHEN {$esMujer} THEN 1 ELSE 0 END) AS Inscritos_Mujeres,
                    SUM(CASE WHEN NOT ({$esHombre} OR {$esMujer}) THEN 1 ELSE 0 END) AS Inscritos_Sin_Genero,
                    SUM(CASE WHEN s.Asistio_Clase = 1 THEN 1 ELSE 0 END) AS Asistieron_Total,
                    SUM(CASE WHEN s.Asistio_Clase = 1 AND {$esHombre} THEN 1 ELSE 0 END) AS Asistieron_Hombres,
                    SUM(CASE WHEN s.Asistio_Clase = 1 AND {$esMujer} THEN 1 ELSE 0 END) AS Asistieron_Mujeres,
                    SUM(CASE WHEN s.Asistio_Clase = 1 AND NOT ({$esHombre} OR {$esMujer}) THEN 1 ELSE 0 END) AS Asistieron_Sin_Genero
                FROM {$this->table} s
                LEFT JOIN persona p ON s.Id_Persona = p.Id_Persona
                LEFT JOIN ministerio ms ON s.Id_Ministerio = ms.Id_Ministerio
                LEFT JOIN ministerio mp ON p.Id_Ministerio = mp.Id_Ministerio
                WHERE " . implode(' AND ', $where) . "
                GROUP BY {$ministerioExpr}
                ORDER BY {$ministerioExpr} ASC";

        return $this->query($sql, $params);
    }
}
