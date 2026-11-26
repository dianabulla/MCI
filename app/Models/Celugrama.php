<?php
/**
 * Celugrama por célula: temas semanales.
 * Indicadores del mes se calculan en CelulaController (ganados, reportes, formación).
 */

require_once APP . '/Models/BaseModel.php';

class Celugrama extends BaseModel {
    protected $table = 'celugrama_indicador_mes';
    protected $primaryKey = 'Id_Indicador';
    protected $tableTemas = 'celugrama_tema_semana';

    public function __construct() {
        parent::__construct();
        $this->ensureTables();
    }

    private function ensureTables(): void {
        try {
            $this->execute(
                "CREATE TABLE IF NOT EXISTS {$this->table} (
                    Id_Indicador INT NOT NULL AUTO_INCREMENT,
                    Id_Celula INT NOT NULL,
                    Anio SMALLINT NOT NULL,
                    Mes TINYINT NOT NULL,
                    Invitados_Celula INT NOT NULL DEFAULT 0,
                    Invitados_Coliseo INT NOT NULL DEFAULT 0,
                    Celulas_No_Realizadas INT NULL DEFAULT NULL,
                    Actualizado_En DATETIME NULL DEFAULT NULL,
                    PRIMARY KEY (Id_Indicador),
                    UNIQUE KEY uq_celugrama_mes (Id_Celula, Anio, Mes),
                    KEY idx_celugrama_celula (Id_Celula)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
            $this->execute(
                "CREATE TABLE IF NOT EXISTS {$this->tableTemas} (
                    Id_Tema INT NOT NULL AUTO_INCREMENT,
                    Id_Celula INT NOT NULL,
                    Semana_Inicio DATE NOT NULL,
                    Titulo VARCHAR(255) NULL DEFAULT NULL,
                    Texto_Biblico VARCHAR(255) NULL DEFAULT NULL,
                    Actualizado_En DATETIME NULL DEFAULT NULL,
                    PRIMARY KEY (Id_Tema),
                    UNIQUE KEY uq_celugrama_tema (Id_Celula, Semana_Inicio),
                    KEY idx_celugrama_tema_celula (Id_Celula)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        } catch (Throwable $e) {
            error_log('No fue posible asegurar tablas de celugrama: ' . $e->getMessage());
        }
    }

    /**
     * @return array<string, array{titulo:string,texto:string}> clave = Y-m-d lunes
     */
    public function getTemasPorCelulaYRango(int $idCelula, string $desde, string $hasta): array {
        $idCelula = (int)$idCelula;
        $desde = substr(trim($desde), 0, 10);
        $hasta = substr(trim($hasta), 0, 10);
        if ($idCelula <= 0 || $desde === '' || $hasta === '') {
            return [];
        }

        $rows = $this->query(
            "SELECT Semana_Inicio, Titulo, Texto_Biblico
             FROM {$this->tableTemas}
             WHERE Id_Celula = ?
               AND Semana_Inicio BETWEEN ? AND ?",
            [$idCelula, $desde, $hasta]
        );

        $mapa = [];
        foreach ((array)$rows as $row) {
            $semana = substr(trim((string)($row['Semana_Inicio'] ?? '')), 0, 10);
            if ($semana === '') {
                continue;
            }
            $mapa[$semana] = [
                'titulo' => trim((string)($row['Titulo'] ?? '')),
                'texto' => trim((string)($row['Texto_Biblico'] ?? '')),
            ];
        }
        return $mapa;
    }

    public function guardarTema(int $idCelula, string $semanaInicio, string $titulo, string $textoBiblico): bool {
        $idCelula = (int)$idCelula;
        $semanaInicio = substr(trim($semanaInicio), 0, 10);
        $titulo = trim($titulo);
        $textoBiblico = trim($textoBiblico);
        if ($idCelula <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $semanaInicio)) {
            return false;
        }

        if ($titulo === '' && $textoBiblico === '') {
            return (bool)$this->execute(
                "DELETE FROM {$this->tableTemas} WHERE Id_Celula = ? AND Semana_Inicio = ?",
                [$idCelula, $semanaInicio]
            );
        }

        return (bool)$this->execute(
            "INSERT INTO {$this->tableTemas} (Id_Celula, Semana_Inicio, Titulo, Texto_Biblico, Actualizado_En)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                Titulo = VALUES(Titulo),
                Texto_Biblico = VALUES(Texto_Biblico),
                Actualizado_En = NOW()",
            [$idCelula, $semanaInicio, $titulo !== '' ? $titulo : null, $textoBiblico !== '' ? $textoBiblico : null]
        );
    }

    /**
     * @return array<string, array{invitados_celula:int,invitados_coliseo:int,celulas_no_realizadas:?int}>
     *         clave = YYYY-MM
     */
    public function getIndicadoresPorCelulaYAnios(int $idCelula, array $anios): array {
        $idCelula = (int)$idCelula;
        $anios = array_values(array_unique(array_filter(array_map('intval', $anios), static function ($a) {
            return $a >= 2000 && $a <= 2100;
        })));
        if ($idCelula <= 0 || $anios === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($anios), '?'));
        $rows = $this->query(
            "SELECT Anio, Mes, Invitados_Celula, Invitados_Coliseo, Celulas_No_Realizadas
             FROM {$this->table}
             WHERE Id_Celula = ?
               AND Anio IN ({$placeholders})",
            array_merge([$idCelula], $anios)
        );

        $mapa = [];
        foreach ((array)$rows as $row) {
            $anio = (int)($row['Anio'] ?? 0);
            $mes = (int)($row['Mes'] ?? 0);
            if ($anio <= 0 || $mes < 1 || $mes > 12) {
                continue;
            }
            $clave = sprintf('%04d-%02d', $anio, $mes);
            $noRealizadas = $row['Celulas_No_Realizadas'];
            $mapa[$clave] = [
                'invitados_celula' => (int)($row['Invitados_Celula'] ?? 0),
                'invitados_coliseo' => (int)($row['Invitados_Coliseo'] ?? 0),
                'celulas_no_realizadas' => $noRealizadas === null || $noRealizadas === '' ? null : (int)$noRealizadas,
            ];
        }
        return $mapa;
    }

    public function guardarIndicadorMes(
        int $idCelula,
        int $anio,
        int $mes,
        int $invitadosCelula,
        int $invitadosColiseo,
        ?int $celulasNoRealizadas
    ): bool {
        $idCelula = (int)$idCelula;
        $anio = (int)$anio;
        $mes = (int)$mes;
        if ($idCelula <= 0 || $anio < 2000 || $mes < 1 || $mes > 12) {
            return false;
        }

        return (bool)$this->execute(
            "INSERT INTO {$this->table}
                (Id_Celula, Anio, Mes, Invitados_Celula, Invitados_Coliseo, Celulas_No_Realizadas, Actualizado_En)
             VALUES (?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                Invitados_Celula = VALUES(Invitados_Celula),
                Invitados_Coliseo = VALUES(Invitados_Coliseo),
                Celulas_No_Realizadas = VALUES(Celulas_No_Realizadas),
                Actualizado_En = NOW()",
            [
                $idCelula,
                $anio,
                $mes,
                max(0, $invitadosCelula),
                max(0, $invitadosColiseo),
                $celulasNoRealizadas,
            ]
        );
    }
}
