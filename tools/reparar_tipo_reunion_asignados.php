<?php
/**
 * Repara registros legacy donde Tipo_Reunion quedo como "Asignados".
 *
 * Regla de correccion:
 * - Si Observacion_Ganado_En sugiere origen, se usa ese valor normalizado.
 * - Si no hay pista, se usa "Domingo" por defecto.
 *
 * Uso CLI:
 *   Simulacion: php tools/reparar_tipo_reunion_asignados.php
 *   Aplicar   : php tools/reparar_tipo_reunion_asignados.php --aplicar
 */

date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../conexion.php';

$isCli = PHP_SAPI === 'cli';
$aplicar = $isCli && in_array('--aplicar', $argv ?? [], true);

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Este script se ejecuta por CLI.\n";
    exit(1);
}

function out($text) {
    echo $text . PHP_EOL;
}

function tableHasColumn(PDO $pdo, $table, $column) {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS '
        . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([(string)$table, (string)$column]);
    return (int)$stmt->fetchColumn() > 0;
}

function exprDestinoTipoReunion($tieneObservacion) {
    if (!$tieneObservacion) {
        return "'Domingo'";
    }

    return "CASE
        WHEN LOWER(TRIM(COALESCE(Observacion_Ganado_En, ''))) LIKE '%celula%'
          OR LOWER(TRIM(COALESCE(Observacion_Ganado_En, ''))) LIKE '%célula%' THEN 'Celula'
        WHEN LOWER(TRIM(COALESCE(Observacion_Ganado_En, ''))) LIKE '%somos uno%'
          OR LOWER(TRIM(COALESCE(Observacion_Ganado_En, ''))) LIKE '%somosuno%'
          OR LOWER(TRIM(COALESCE(Observacion_Ganado_En, ''))) LIKE '%viernes%' THEN 'Somos Uno'
        WHEN LOWER(TRIM(COALESCE(Observacion_Ganado_En, ''))) LIKE '%otro%' THEN 'Otros'
        WHEN LOWER(TRIM(COALESCE(Observacion_Ganado_En, ''))) LIKE '%domingo%'
          OR LOWER(TRIM(COALESCE(Observacion_Ganado_En, ''))) LIKE '%iglesia%' THEN 'Domingo'
        ELSE 'Domingo'
    END";
}

try {
    if (!tableHasColumn($pdo, 'persona', 'Tipo_Reunion')) {
        throw new RuntimeException('No existe la columna persona.Tipo_Reunion');
    }

    $tieneObservacion = tableHasColumn($pdo, 'persona', 'Observacion_Ganado_En');
    $destinoExpr = exprDestinoTipoReunion($tieneObservacion);

    $whereAsignados = "LOWER(TRIM(COALESCE(Tipo_Reunion, ''))) = 'asignados'";

    $total = (int)$pdo->query("SELECT COUNT(*) FROM persona WHERE {$whereAsignados}")->fetchColumn();
    out('Registros Tipo_Reunion=Asignados detectados: ' . $total);

    if ($total <= 0) {
        out('No hay datos por corregir.');
        exit(0);
    }

    $sqlPreview = "SELECT {$destinoExpr} AS destino, COUNT(*) AS total
                   FROM persona
                   WHERE {$whereAsignados}
                   GROUP BY destino
                   ORDER BY total DESC";
    $preview = $pdo->query($sqlPreview)->fetchAll(PDO::FETCH_ASSOC);

    out('Resumen de conversion propuesta:');
    foreach ($preview as $row) {
        out('- ' . (string)$row['destino'] . ': ' . (int)$row['total']);
    }

    if (!$aplicar) {
        out('Simulacion completada. Ejecuta con --aplicar para confirmar.');
        exit(0);
    }

    $pdo->beginTransaction();

    $sqlUpdate = "UPDATE persona
                  SET Tipo_Reunion = {$destinoExpr}
                  WHERE {$whereAsignados}";
    $stmt = $pdo->prepare($sqlUpdate);
    $stmt->execute();
    $actualizados = (int)$stmt->rowCount();

    $pdo->commit();

    out('Correccion aplicada con exito.');
    out('Registros actualizados: ' . $actualizados);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    out('Error: ' . $e->getMessage());
    exit(1);
}
