<?php

/**
 * Valor a partir del cual un pago de encuentro de Universidad de la Vida
 * cuenta como pago total, por año y semestre.
 * Si el ciclo no tiene un valor guardado, se usa 180000 (el histórico).
 */
class UmbralPagoCompletoUv
{
    public const POR_DEFECTO = 180000;

    private static $tablaLista = false;

    public static function valor(int $anio, int $semestre): int
    {
        self::ensureTabla();
        $anio = self::anioValido($anio);
        $semestre = $semestre === 2 ? 2 : 1;

        $stmt = self::pdo()->prepare(
            'SELECT valor FROM uv_pago_completo_ciclo WHERE anio = ? AND semestre = ? LIMIT 1'
        );
        $stmt->execute([$anio, $semestre]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($fila)) {
            return self::POR_DEFECTO;
        }

        $valor = (int)($fila['valor'] ?? 0);
        return $valor > 0 ? $valor : self::POR_DEFECTO;
    }

    public static function valorParaFecha(string $fecha): int
    {
        $fecha = substr(trim($fecha), 0, 10);
        if (!preg_match('/^(\d{4})-(\d{2})-\d{2}$/', $fecha, $partes)) {
            return self::POR_DEFECTO;
        }

        $mes = (int)$partes[2];
        if ($mes < 1 || $mes > 12) {
            return self::POR_DEFECTO;
        }

        return self::valor((int)$partes[1], $mes <= 6 ? 1 : 2);
    }

    public static function guardar(int $anio, int $semestre, int $valor): void
    {
        self::ensureTabla();
        $anio = self::anioValido($anio);
        $semestre = $semestre === 2 ? 2 : 1;
        $pdo = self::pdo();

        if ($valor === self::POR_DEFECTO) {
            $stmt = $pdo->prepare('DELETE FROM uv_pago_completo_ciclo WHERE anio = ? AND semestre = ?');
            $stmt->execute([$anio, $semestre]);
            return;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO uv_pago_completo_ciclo (anio, semestre, valor)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
        );
        $stmt->execute([$anio, $semestre, $valor]);
    }

    /**
     * Ciclos con un precio distinto de 180000. Sirve para no marcar como
     * completos, con la regla vieja, a quienes pertenecen a esos ciclos.
     *
     * @return list<array{inicio:string,fin:string}>
     */
    public static function rangosDistintosDelDefecto(): array
    {
        self::ensureTabla();
        $stmt = self::pdo()->query(
            'SELECT anio, semestre FROM uv_pago_completo_ciclo WHERE valor <> ' . (int)self::POR_DEFECTO
        );
        $rangos = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $anio = (int)($fila['anio'] ?? 0);
            $semestre = (int)($fila['semestre'] ?? 0);
            if ($anio < 2020 || ($semestre !== 1 && $semestre !== 2)) {
                continue;
            }
            if ($semestre === 1) {
                $rangos[] = [
                    'inicio' => sprintf('%04d-01-01', $anio),
                    'fin' => sprintf('%04d-06-30', $anio),
                ];
                continue;
            }
            $rangos[] = [
                'inicio' => sprintf('%04d-07-01', $anio),
                'fin' => sprintf('%04d-12-31', $anio),
            ];
        }

        return $rangos;
    }

    private static function anioValido(int $anio): int
    {
        $max = (int)date('Y') + 2;
        if ($anio < 2020 || $anio > $max) {
            return (int)date('Y');
        }

        return $anio;
    }

    private static function ensureTabla(): void
    {
        if (self::$tablaLista) {
            return;
        }

        self::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS uv_pago_completo_ciclo (
                anio SMALLINT NOT NULL,
                semestre TINYINT NOT NULL,
                valor INT UNSIGNED NOT NULL,
                actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (anio, semestre)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        self::$tablaLista = true;
    }

    private static function pdo(): PDO
    {
        global $pdo;
        if ($pdo instanceof PDO) {
            return $pdo;
        }

        throw new RuntimeException('No hay conexión para el valor de pago completo por ciclo.');
    }
}
