<?php
/**
 * Marcar como NUEVAS (Es_Antiguo=0) personas ganadas en:
 * - Célula
 * - Domingo / Iglesia
 * - Viernes / Somos Uno
 * - Otros
 * - Sin dato (Tipo_Reunion vacio o NULL)
 *
 * NO modifica asignaciones (Id_Lider, Id_Celula, Id_Ministerio),
 * solo ajusta Es_Antiguo.
 *
 * Uso web:
 *   Simulacion : /tools/marcar_nuevas_por_ganado_en.php
 *   Aplicar    : /tools/marcar_nuevas_por_ganado_en.php?aplicar=1
 *
 * Uso CLI:
 *   Simulacion : php tools/marcar_nuevas_por_ganado_en.php
 *   Aplicar    : php tools/marcar_nuevas_por_ganado_en.php --aplicar
 */

date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../conexion.php';

$isCli = PHP_SAPI === 'cli';
$aplicar = $isCli
    ? in_array('--aplicar', $argv ?? [], true)
    : (isset($_GET['aplicar']) && (string)$_GET['aplicar'] === '1');

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8">'
        . '<title>Marcar Nuevas por Ganado en</title>'
        . '<style>'
        . 'body{font-family:Arial,sans-serif;margin:20px;}'
        . 'h2{color:#333;}'
        . '.ok{color:green;font-weight:bold;}'
        . '.warn{color:#b26a00;font-weight:bold;}'
        . '.err{color:red;font-weight:bold;}'
        . '.box{background:#f6f8fa;border:1px solid #ddd;padding:10px;border-radius:6px;margin-bottom:12px;}'
        . 'table{border-collapse:collapse;width:100%;margin-top:8px;}'
        . 'th,td{border:1px solid #ccc;padding:6px 8px;font-size:13px;text-align:left;}'
        . 'th{background:#f0f0f0;}'
        . 'a.btn{display:inline-block;margin-top:14px;padding:8px 16px;background:#c0392b;color:#fff;text-decoration:none;border-radius:4px;font-weight:bold;}'
        . '</style>'
        . '</head><body>';
}

function out($text, $isCli, $class = '') {
    if ($isCli) {
        echo $text . PHP_EOL;
        return;
    }

    $cls = $class ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
    echo '<div' . $cls . '>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</div>';
}

function tableHasColumn(PDO $pdo, $table, $column) {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS '
        . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([(string)$table, (string)$column]);
    return (int)$stmt->fetchColumn() > 0;
}

function condicionGanadoEnSql() {
    return "(
        p.Tipo_Reunion IS NULL
        OR TRIM(p.Tipo_Reunion) = ''
        OR LOWER(TRIM(p.Tipo_Reunion)) LIKE '%celula%'
        OR LOWER(TRIM(p.Tipo_Reunion)) LIKE '%célula%'
        OR LOWER(TRIM(p.Tipo_Reunion)) LIKE '%domingo%'
        OR LOWER(TRIM(p.Tipo_Reunion)) LIKE '%iglesia%'
        OR LOWER(TRIM(p.Tipo_Reunion)) LIKE '%viernes%'
        OR LOWER(TRIM(p.Tipo_Reunion)) LIKE '%somos uno%'
        OR LOWER(TRIM(p.Tipo_Reunion)) LIKE '%somosuno%'
        OR LOWER(TRIM(p.Tipo_Reunion)) LIKE '%otro%'
    )";
}

try {
    if (!$isCli) {
        echo '<h2>Marcar personas nuevas por Ganado en</h2>';
        echo '<div class="box">Modo: <strong>' . ($aplicar ? 'APLICAR' : 'SIMULACION') . '</strong></div>';
    }

    if (!tableHasColumn($pdo, 'persona', 'Es_Antiguo')) {
        throw new RuntimeException('La columna persona.Es_Antiguo no existe en esta base de datos.');
    }

    $whereGanadoEn = condicionGanadoEnSql();

    $sqlConteo = "SELECT COUNT(*)
                  FROM persona p
                  WHERE {$whereGanadoEn}
                    AND COALESCE(p.Es_Antiguo, 1) <> 0";

    $totalObjetivo = (int)$pdo->query($sqlConteo)->fetchColumn();

    out('Personas a corregir (pasar a NUEVA): ' . $totalObjetivo, $isCli, $totalObjetivo > 0 ? 'warn' : 'ok');

    $sqlResumen = "SELECT
                    CASE
                        WHEN p.Tipo_Reunion IS NULL OR TRIM(p.Tipo_Reunion) = '' THEN 'Sin dato'
                        WHEN LOWER(TRIM(p.Tipo_Reunion)) LIKE '%celula%' OR LOWER(TRIM(p.Tipo_Reunion)) LIKE '%célula%' THEN 'Celula'
                        WHEN LOWER(TRIM(p.Tipo_Reunion)) LIKE '%domingo%' OR LOWER(TRIM(p.Tipo_Reunion)) LIKE '%iglesia%' THEN 'Domingo/Iglesia'
                        WHEN LOWER(TRIM(p.Tipo_Reunion)) LIKE '%viernes%' OR LOWER(TRIM(p.Tipo_Reunion)) LIKE '%somos uno%' OR LOWER(TRIM(p.Tipo_Reunion)) LIKE '%somosuno%' THEN 'Viernes/Somos Uno'
                        WHEN LOWER(TRIM(p.Tipo_Reunion)) LIKE '%otro%' THEN 'Otros'
                        ELSE 'Otros'
                    END AS Categoria,
                    COUNT(*) AS Total
                   FROM persona p
                   WHERE {$whereGanadoEn}
                     AND COALESCE(p.Es_Antiguo, 1) <> 0
                   GROUP BY Categoria
                   ORDER BY Total DESC";

    $resumen = $pdo->query($sqlResumen)->fetchAll(PDO::FETCH_ASSOC);

    if ($isCli) {
        out('Resumen por categoria:', $isCli);
        foreach ($resumen as $row) {
            out('- ' . (string)$row['Categoria'] . ': ' . (int)$row['Total'], $isCli);
        }
    } else {
        if (!empty($resumen)) {
            echo '<table><tr><th>Categoria</th><th>Total a corregir</th></tr>';
            foreach ($resumen as $row) {
                echo '<tr><td>' . htmlspecialchars((string)$row['Categoria'], ENT_QUOTES, 'UTF-8') . '</td><td>' . (int)$row['Total'] . '</td></tr>';
            }
            echo '</table>';
        }
    }

    if (!$aplicar) {
        out('Simulacion completada. No se realizaron cambios.', $isCli, 'ok');
        if (!$isCli && $totalObjetivo > 0) {
            echo '<a class="btn" href="?aplicar=1">Aplicar correccion ahora</a>';
        }
        if (!$isCli) {
            echo '</body></html>';
        }
        exit;
    }

    if ($totalObjetivo <= 0) {
        out('No hay personas por corregir.', $isCli, 'ok');
        if (!$isCli) {
            echo '</body></html>';
        }
        exit;
    }

    $pdo->beginTransaction();

    $sqlUpdate = "UPDATE persona p
                  SET p.Es_Antiguo = 0
                  WHERE {$whereGanadoEn}
                    AND COALESCE(p.Es_Antiguo, 1) <> 0";

    $stmt = $pdo->prepare($sqlUpdate);
    $stmt->execute();
    $actualizados = (int)$stmt->rowCount();

    $pdo->commit();

    out('Correccion aplicada con exito.', $isCli, 'ok');
    out('Registros actualizados: ' . $actualizados, $isCli, 'ok');
    out('Asignaciones de lider/celula/ministerio NO fueron modificadas.', $isCli, 'ok');

    if (!$isCli) {
        echo '</body></html>';
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    out('Error: ' . $e->getMessage(), $isCli, 'err');
    if (!$isCli) {
        echo '</body></html>';
    }
    exit(1);
}
