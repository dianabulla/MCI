<?php
/**
 * Historial de salidas de personas de una célula
 * (no se dispone, transferencia, eliminación).
 */

require_once APP . '/Models/BaseModel.php';

class PersonaSalidaCelula extends BaseModel {
    protected $table = 'persona_salida_celula';
    protected $primaryKey = 'Id_Salida';

    public const MOTIVO_NO_SE_DISPONE = 'no_se_dispone';
    public const MOTIVO_TRANSFERIDA = 'transferida';
    public const MOTIVO_ELIMINADA = 'eliminada';

    public function __construct() {
        parent::__construct();
        $this->ensureTable();
    }

    private function ensureTable(): void {
        try {
            $this->execute(
                "CREATE TABLE IF NOT EXISTS {$this->table} (
                    Id_Salida INT NOT NULL AUTO_INCREMENT,
                    Id_Persona INT NOT NULL,
                    Id_Celula INT NOT NULL,
                    Fecha_Retiro DATE NOT NULL,
                    Motivo VARCHAR(40) NOT NULL,
                    Observacion TEXT NULL,
                    Id_Celula_Destino INT NULL,
                    Creado_En DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (Id_Salida),
                    KEY idx_salida_celula (Id_Celula, Fecha_Retiro),
                    KEY idx_salida_persona (Id_Persona)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (Throwable $e) {
            error_log('No se pudo crear tabla persona_salida_celula: ' . $e->getMessage());
        }
    }

    public function registrar(
        int $idPersona,
        int $idCelula,
        string $motivo,
        string $observacion = '',
        ?int $idCelulaDestino = null,
        ?string $fechaRetiro = null
    ): bool {
        $idPersona = (int)$idPersona;
        $idCelula = (int)$idCelula;
        if ($idPersona <= 0 || $idCelula <= 0) {
            return false;
        }

        $motivo = strtolower(trim($motivo));
        $motivosOk = [
            self::MOTIVO_NO_SE_DISPONE,
            self::MOTIVO_TRANSFERIDA,
            self::MOTIVO_ELIMINADA,
        ];
        if (!in_array($motivo, $motivosOk, true)) {
            $motivo = self::MOTIVO_ELIMINADA;
        }

        $fecha = substr(trim((string)($fechaRetiro ?: date('Y-m-d'))), 0, 10);
        if ($fecha === '' || $fecha === '0000-00-00' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }

        $observacion = trim($observacion);
        $idDestino = ($idCelulaDestino !== null && (int)$idCelulaDestino > 0)
            ? (int)$idCelulaDestino
            : null;

        try {
            return (bool)$this->execute(
                "INSERT INTO {$this->table}
                    (Id_Persona, Id_Celula, Fecha_Retiro, Motivo, Observacion, Id_Celula_Destino, Creado_En)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [
                    $idPersona,
                    $idCelula,
                    $fecha,
                    $motivo,
                    $observacion !== '' ? $observacion : null,
                    $idDestino,
                ]
            );
        } catch (Throwable $e) {
            error_log('No se pudo registrar salida de célula: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listarPorCelula(int $idCelula): array {
        $idCelula = (int)$idCelula;
        if ($idCelula <= 0) {
            return [];
        }

        try {
            $filas = $this->query(
                "SELECT s.Id_Salida, s.Id_Persona, s.Id_Celula, s.Fecha_Retiro, s.Motivo, s.Observacion,
                        s.Id_Celula_Destino, s.Creado_En,
                        p.Nombre, p.Apellido, p.Numero_Documento, p.Telefono, p.Fecha_Registro,
                        cdest.Nombre_Celula AS Nombre_Celula_Destino
                 FROM {$this->table} s
                 INNER JOIN persona p ON p.Id_Persona = s.Id_Persona
                 LEFT JOIN celula cdest ON cdest.Id_Celula = s.Id_Celula_Destino
                 WHERE s.Id_Celula = ?
                 ORDER BY s.Fecha_Retiro DESC, s.Id_Salida DESC",
                [$idCelula]
            );
            return is_array($filas) ? $filas : [];
        } catch (Throwable $e) {
            error_log('No se pudo listar salidas de célula: ' . $e->getMessage());
            return [];
        }
    }

    public static function etiquetaMotivo(string $motivo): string {
        $motivo = strtolower(trim($motivo));
        if ($motivo === self::MOTIVO_NO_SE_DISPONE) {
            return 'No se dispuso';
        }
        if ($motivo === self::MOTIVO_TRANSFERIDA) {
            return 'Transferida a otra celula';
        }
        if ($motivo === self::MOTIVO_ELIMINADA) {
            return 'Eliminada de la celula';
        }
        return 'Retiro';
    }

    public static function armarObservacionVisible(array $salida): string {
        $motivo = self::etiquetaMotivo((string)($salida['Motivo'] ?? ''));
        $obs = trim((string)($salida['Observacion'] ?? ''));
        $destino = trim((string)($salida['Nombre_Celula_Destino'] ?? ''));

        if ((string)($salida['Motivo'] ?? '') === self::MOTIVO_TRANSFERIDA) {
            $base = $destino !== ''
                ? ('Transferida a: ' . $destino)
                : $motivo;
            return $obs !== '' ? ($base . ' | ' . $obs) : $base;
        }

        if ($obs === '') {
            return $motivo;
        }
        return $motivo . ': ' . $obs;
    }
}
