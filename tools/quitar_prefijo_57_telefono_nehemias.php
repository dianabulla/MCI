<?php
/**
 * Quita el prefijo +57 del campo Telefono en la tabla nehemias.
 *
 * No toca Telefono_Normalizado.
 *
 * Uso CLI:
 *   php tools/quitar_prefijo_57_telefono_nehemias.php           (solo vista previa)
 *   php tools/quitar_prefijo_57_telefono_nehemias.php --apply   (aplica cambios)
 *
 * Uso web:
 *   /tools/quitar_prefijo_57_telefono_nehemias.php
 *   /tools/quitar_prefijo_57_telefono_nehemias.php?apply=1
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../conexion.php';

$isCli = (PHP_SAPI === 'cli');
$args = $isCli ? ($_SERVER['argv'] ?? []) : [];
$apply = in_array('--apply', $args, true) || (($_GET['apply'] ?? '0') === '1');

function out($text, $isCli = true) {
    if ($isCli) {
        echo $text . PHP_EOL;
    } else {
        echo nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')) . "<br>";
    }
}

try {
    $sqlCount = "SELECT COUNT(*) AS total FROM nehemias WHERE TRIM(COALESCE(Telefono, '')) LIKE '+57%'";
    $stmtCount = $pdo->query($sqlCount);
    $totalConPrefijo = (int)($stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    out('Registros con +57 en Telefono: ' . $totalConPrefijo, $isCli);

    $sqlSample = "
        SELECT Id_Nehemias, Telefono
        FROM nehemias
        WHERE TRIM(COALESCE(Telefono, '')) LIKE '+57%'
        ORDER BY Id_Nehemias ASC
        LIMIT 10
    ";
    $sample = $pdo->query($sqlSample)->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($sample)) {
        out('Muestra (antes):', $isCli);
        foreach ($sample as $row) {
            out(' - ID ' . (int)$row['Id_Nehemias'] . ': ' . (string)$row['Telefono'], $isCli);
        }
    }

    if (!$apply) {
        out('', $isCli);
        out('Modo vista previa. No se aplicaron cambios.', $isCli);
        out('Para aplicar: php tools/quitar_prefijo_57_telefono_nehemias.php --apply', $isCli);
        exit;
    }

    $pdo->beginTransaction();

    $sqlUpdate = "
        UPDATE nehemias
        SET Telefono = CASE
            WHEN TRIM(Telefono) LIKE '+57 %' THEN TRIM(SUBSTRING(TRIM(Telefono), 5))
            WHEN TRIM(Telefono) LIKE '+57%' THEN TRIM(SUBSTRING(TRIM(Telefono), 4))
            ELSE Telefono
        END
        WHERE TRIM(COALESCE(Telefono, '')) LIKE '+57%'
    ";

    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $stmtUpdate->execute();
    $filasAfectadas = (int)$stmtUpdate->rowCount();

    $sqlCountAfter = "SELECT COUNT(*) AS total FROM nehemias WHERE TRIM(COALESCE(Telefono, '')) LIKE '+57%'";
    $stmtAfter = $pdo->query($sqlCountAfter);
    $totalDespues = (int)($stmtAfter->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $pdo->commit();

    out('', $isCli);
    out('✅ Cambios aplicados correctamente.', $isCli);
    out('Filas afectadas: ' . $filasAfectadas, $isCli);
    out('Registros con +57 después: ' . $totalDespues, $isCli);
    out('Telefono_Normalizado no fue modificado.', $isCli);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    out('❌ Error: ' . $e->getMessage(), $isCli);
    http_response_code(500);
    exit(1);
}
