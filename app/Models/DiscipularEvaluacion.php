<?php

require_once APP . '/Models/BaseModel.php';

class DiscipularEvaluacion extends BaseModel {
    public function __construct() {
        parent::__construct();
        $this->crearTablasSiNoExisten();
    }

    private function crearTablasSiNoExisten(): void {
        $sqlEvaluaciones = "
            CREATE TABLE IF NOT EXISTS discipular_evaluaciones (
                Id_Evaluacion INT AUTO_INCREMENT PRIMARY KEY,
                Titulo VARCHAR(180) NOT NULL,
                Descripcion TEXT NULL,
                Nivel INT NOT NULL,
                Modulo_Numero INT NOT NULL,
                Leccion VARCHAR(120) NULL,
                Puntaje_Minimo DECIMAL(5,2) NOT NULL DEFAULT 60.00,
                Preguntas_JSON LONGTEXT NOT NULL,
                Activa TINYINT(1) NOT NULL DEFAULT 1,
                Fecha_Habilitacion_Inicio DATE NULL,
                Fecha_Habilitacion_Fin DATE NULL,
                Creado_Por INT NULL,
                Fecha_Creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                Fecha_Actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_nivel_modulo (Nivel, Modulo_Numero),
                INDEX idx_activa (Activa)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $sqlResultados = "
            CREATE TABLE IF NOT EXISTS discipular_evaluacion_resultados (
                Id_Resultado INT AUTO_INCREMENT PRIMARY KEY,
                Id_Evaluacion INT NOT NULL,
                Id_Persona INT NOT NULL,
                Intento_Numero INT NOT NULL,
                Respuestas_JSON LONGTEXT NOT NULL,
                Puntaje DECIMAL(5,2) NOT NULL,
                Total_Preguntas INT NOT NULL,
                Correctas INT NOT NULL,
                Aprobado TINYINT(1) NOT NULL DEFAULT 0,
                Fecha_Presentacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_eval_persona (Id_Evaluacion, Id_Persona),
                INDEX idx_persona (Id_Persona),
                CONSTRAINT fk_eval_result_eval FOREIGN KEY (Id_Evaluacion) REFERENCES discipular_evaluaciones(Id_Evaluacion) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        $this->db->exec($sqlEvaluaciones);
        $this->db->exec($sqlResultados);

        // Compatibilidad con instalaciones existentes que ya tenían la tabla creada.
        $this->asegurarColumnaEvaluaciones('Fecha_Habilitacion_Inicio', 'DATE NULL');
        $this->asegurarColumnaEvaluaciones('Fecha_Habilitacion_Fin', 'DATE NULL');
        $this->asegurarColumnaEvaluaciones('Leccion', 'VARCHAR(120) NULL AFTER Modulo_Numero');
    }

    private function asegurarColumnaEvaluaciones(string $columna, string $definicion): void {
        try {
            $this->db->exec("ALTER TABLE discipular_evaluaciones ADD COLUMN {$columna} {$definicion}");
        } catch (Throwable $e) {
            $mensaje = strtolower((string)$e->getMessage());
            if (strpos($mensaje, 'duplicate') === false && strpos($mensaje, 'exists') === false) {
                throw $e;
            }
        }
    }

    public function crearEvaluacion(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO discipular_evaluaciones
            (Titulo, Descripcion, Nivel, Modulo_Numero, Leccion, Puntaje_Minimo, Preguntas_JSON, Activa, Fecha_Habilitacion_Inicio, Fecha_Habilitacion_Fin, Creado_Por)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)");

        $stmt->execute([
            $data['titulo'],
            $data['descripcion'],
            (int)$data['nivel'],
            (int)$data['modulo_numero'],
            $data['leccion'] ?? null,
            (float)$data['puntaje_minimo'],
            $data['preguntas_json'],
            $data['fecha_habilitacion_inicio'] ?? null,
            $data['fecha_habilitacion_fin'] ?? null,
            $data['creado_por']
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function listarEvaluaciones(): array {
        $stmt = $this->db->query("SELECT * FROM discipular_evaluaciones ORDER BY Nivel ASC, Modulo_Numero ASC, Fecha_Creacion DESC");
        return (array)$stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarEvaluacionesActivas(): array {
        $stmt = $this->db->query("SELECT * FROM discipular_evaluaciones WHERE Activa = 1 ORDER BY Nivel ASC, Modulo_Numero ASC, Fecha_Creacion DESC");
        return (array)$stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerEvaluacion(int $idEvaluacion): ?array {
        $stmt = $this->db->prepare("SELECT * FROM discipular_evaluaciones WHERE Id_Evaluacion = ? LIMIT 1");
        $stmt->execute([$idEvaluacion]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function desactivarEvaluacion(int $idEvaluacion): bool {
        $stmt = $this->db->prepare("UPDATE discipular_evaluaciones SET Activa = 0 WHERE Id_Evaluacion = ?");
        return (bool)$stmt->execute([$idEvaluacion]);
    }

    public function actualizarFechasHabilitacion(int $idEvaluacion, ?string $fechaInicio, ?string $fechaFin): bool {
        $stmt = $this->db->prepare("UPDATE discipular_evaluaciones
            SET Fecha_Habilitacion_Inicio = ?, Fecha_Habilitacion_Fin = ?
            WHERE Id_Evaluacion = ?");

        return (bool)$stmt->execute([
            $fechaInicio !== '' ? $fechaInicio : null,
            $fechaFin !== '' ? $fechaFin : null,
            $idEvaluacion,
        ]);
    }

    public function siguienteIntento(int $idEvaluacion, int $idPersona): int {
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(Intento_Numero), 0) AS max_intento
            FROM discipular_evaluacion_resultados
            WHERE Id_Evaluacion = ? AND Id_Persona = ?");
        $stmt->execute([$idEvaluacion, $idPersona]);
        $max = (int)($stmt->fetchColumn() ?: 0);
        return $max + 1;
    }

    public function contarIntentosPersonaEvaluacion(int $idEvaluacion, int $idPersona): int {
        $stmt = $this->db->prepare("SELECT COUNT(*)
            FROM discipular_evaluacion_resultados
            WHERE Id_Evaluacion = ? AND Id_Persona = ?");
        $stmt->execute([$idEvaluacion, $idPersona]);
        return (int)($stmt->fetchColumn() ?: 0);
    }

    public function contarIntentosPorEvaluacionesPersona(array $idsEvaluacion, int $idPersona): array {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0 || empty($idsEvaluacion)) {
            return [];
        }

        $ids = array_values(array_unique(array_map('intval', $idsEvaluacion)));
        $ids = array_values(array_filter($ids, static function($id) {
            return $id > 0;
        }));

        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT Id_Evaluacion, COUNT(*) AS total
                FROM discipular_evaluacion_resultados
                WHERE Id_Persona = ? AND Id_Evaluacion IN ({$placeholders})
                GROUP BY Id_Evaluacion";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge([$idPersona], $ids));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $resultado = [];
        foreach ($rows as $row) {
            $resultado[(int)($row['Id_Evaluacion'] ?? 0)] = (int)($row['total'] ?? 0);
        }

        return $resultado;
    }

    public function guardarResultado(array $data): int {
        $stmt = $this->db->prepare("INSERT INTO discipular_evaluacion_resultados
            (Id_Evaluacion, Id_Persona, Intento_Numero, Respuestas_JSON, Puntaje, Total_Preguntas, Correctas, Aprobado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            (int)$data['id_evaluacion'],
            (int)$data['id_persona'],
            (int)$data['intento_numero'],
            $data['respuestas_json'],
            (float)$data['puntaje'],
            (int)$data['total_preguntas'],
            (int)$data['correctas'],
            !empty($data['aprobado']) ? 1 : 0
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function listarResultadosPorPersona(int $idPersona): array {
        $stmt = $this->db->prepare("SELECT r.*, e.Titulo, e.Nivel, e.Modulo_Numero
            FROM discipular_evaluacion_resultados r
            INNER JOIN discipular_evaluaciones e ON e.Id_Evaluacion = r.Id_Evaluacion
            WHERE r.Id_Persona = ?
            ORDER BY r.Fecha_Presentacion DESC");
        $stmt->execute([$idPersona]);
        return (array)$stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarResultadosPorEvaluacion(int $idEvaluacion): array {
        $stmt = $this->db->prepare("SELECT r.*, p.Nombre, p.Apellido
            FROM discipular_evaluacion_resultados r
            LEFT JOIN persona p ON p.Id_Persona = r.Id_Persona
            WHERE r.Id_Evaluacion = ?
            ORDER BY r.Fecha_Presentacion DESC");
        $stmt->execute([$idEvaluacion]);
        return (array)$stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarResumenPorNivelCapacitacionDestino(): array {
        $sql = "SELECT
                    e.Nivel,
                    e.Modulo_Numero,
                    e.Titulo,
                    r.Id_Persona,
                    p.Nombre,
                    p.Apellido,
                    r.Puntaje,
                    r.Total_Preguntas,
                    r.Correctas,
                    r.Aprobado,
                    r.Fecha_Presentacion
                FROM discipular_evaluacion_resultados r
                INNER JOIN discipular_evaluaciones e ON e.Id_Evaluacion = r.Id_Evaluacion
                INNER JOIN (
                    SELECT
                        e2.Nivel,
                        r2.Id_Persona,
                        MAX(r2.Id_Resultado) AS Id_Resultado_Ultimo
                    FROM discipular_evaluacion_resultados r2
                    INNER JOIN discipular_evaluaciones e2 ON e2.Id_Evaluacion = r2.Id_Evaluacion
                    GROUP BY e2.Nivel, r2.Id_Persona
                ) ult ON ult.Id_Resultado_Ultimo = r.Id_Resultado
                     AND ult.Nivel = e.Nivel
                     AND ult.Id_Persona = r.Id_Persona
                INNER JOIN (
                    SELECT DISTINCT Id_Persona
                    FROM escuela_formacion_inscripcion
                    WHERE Programa IN (
                        'capacitacion_destino',
                        'capacitacion_destino_nivel_1',
                        'capacitacion_destino_nivel_2',
                        'capacitacion_destino_nivel_3'
                    )
                      AND Id_Persona IS NOT NULL
                ) insc ON insc.Id_Persona = r.Id_Persona
                LEFT JOIN persona p ON p.Id_Persona = r.Id_Persona
                ORDER BY e.Nivel ASC, r.Aprobado DESC, p.Nombre ASC, p.Apellido ASC";

        $stmt = $this->db->query($sql);
        return (array)$stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarLeccionesMaterialCapacitacionDestino(): array {
        $sql = "SELECT
                    CAST(Nivel AS UNSIGNED) AS Nivel,
                    CAST(Modulo_Numero AS UNSIGNED) AS Modulo_Numero,
                    COALESCE(NULLIF(TRIM(Leccion), ''), 'Sin lección') AS Leccion
                FROM material_hub_tema
                WHERE Modulo IN ('capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3')
                  AND Nivel IS NOT NULL
                  AND Modulo_Numero IS NOT NULL
                GROUP BY Nivel, Modulo_Numero, Leccion
                ORDER BY Nivel ASC, Modulo_Numero ASC, Leccion ASC";

        try {
            $stmt = $this->db->query($sql);
            return (array)$stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            // Si aún no existe material_hub_tema o su esquema, no bloquear Evaluaciones.
            return [];
        }
    }
}
