<?php
/**
 * Forzar campo "Ganado en" (Tipo_Reunion) a "Migrados" para todos.
 *
 * Uso web:
 *   - Simulacion: /tools/forzar_ganado_en_migrados.php
 *   - Aplicar:    /tools/forzar_ganado_en_migrados.php?aplicar=1
 *
 * Uso CLI:
 *   - Simulacion: php tools/forzar_ganado_en_migrados.php
 *   - Aplicar:    php tools/forzar_ganado_en_migrados.php --aplicar
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
        . '<title>Forzar Ganado en Migrados</title>'
        . '<style>'
        . 'body{font-family:Arial,sans-serif;margin:20px}'
        . '.ok{color:green}.warn{color:#b36b00}.err{color:#b00020;font-weight:bold}'
        . '.box{background:#f5f5f5;border:1px solid #ddd;padding:10px;border-radius:6px;margin-bottom:10px}'
        . 'a.btn{display:inline-block;margin-top:12px;padding:8px 14px;background:#c0392b;color:#fff;text-decoration:none;border-radius:4px}'
        . '</style></head><body>';
}

function out($text, $isCli, $class = '') {
    if ($isCli) {
        echo $text . PHP_EOL;
        return;
    }

    $cls = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
    echo '<div' . $cls . '>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</div>';
}

function getTipoReunionColumn(PDO $pdo) {
    $stmt = $pdo->query("SHOW COLUMNS FROM persona LIKE 'Tipo_Reunion'");
    $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
    return $row ?: null;
}

function ensureMigradosInEnum(PDO $pdo) {
    $col = getTipoReunionColumn($pdo);
    if (!$col) {
        throw new RuntimeException('No existe la columna Tipo_Reunion en persona.');
    }

    $type = (string)($col['Type'] ?? '');
    if (stripos($type, 'enum(') !== 0) {
        return;
    }

    preg_match_all("/'([^']*)'/", $type, $matches);
    $values = $matches[1] ?? [];
    if (in_array('Migrados', $values, true)) {
        return;
    }

    $values[] = 'Migrados';
    $quoted = array_map(function ($v) {
        return "'" . str_replace("'", "\\'", (string)$v) . "'";
    }, $values);

    $nullable = strtoupper((string)($col['Null'] ?? 'YES')) === 'YES' ? 'NULL' : 'NOT NULL';
    $default = $col['Default'];
    $defaultSql = $default === null ? '' : " DEFAULT '" . str_replace("'", "\\'", (string)$default) . "'";

    $sql = "ALTER TABLE persona MODIFY COLUMN Tipo_Reunion ENUM(" . implode(',', $quoted) . ") {$nullable}{$defaultSql}";
    $pdo->exec($sql);
}

try {
    if (!$isCli) {
        echo '<h2>Forzar campo "Ganado en" a "Migrados"</h2>';
        echo '<div class="box">Modo: <strong>' . ($aplicar ? 'APLICAR' : 'SIMULACION') . '</strong></div>';
    }

    ensureMigradosInEnum($pdo);

    $total = (int)$pdo->query("SELECT COUNT(*) FROM persona")->fetchColumn();
    $yaMigrados = (int)$pdo->query("SELECT COUNT(*) FROM persona WHERE Tipo_Reunion = 'Migrados'")->fetchColumn();
    $pendientes = (int)$pdo->query("SELECT COUNT(*) FROM persona WHERE COALESCE(Tipo_Reunion, '') <> 'Migrados'")->fetchColumn();

    out('Total personas: ' . $total, $isCli);
    out('Ya en Migrados: ' . $yaMigrados, $isCli, 'ok');
    out('Por actualizar: ' . $pendientes, $isCli, 'warn');

    if (!$aplicar) {
        out('Simulacion completada. Para aplicar use ?aplicar=1 o --aplicar.', $isCli, 'ok');
        if (!$isCli) {
            echo '<a class="btn" href="?aplicar=1">Aplicar cambios</a>';
            echo '</body></html>';
        }
        exit;
    }

    $pdo->beginTransaction();
    $upd = $pdo->exec("UPDATE persona SET Tipo_Reunion = 'Migrados' WHERE COALESCE(Tipo_Reunion, '') <> 'Migrados'");
    $pdo->commit();

    out('Actualizadas: ' . (int)$upd, $isCli, 'ok');
    out('Listo: todas quedaron con Ganado en = Migrados.', $isCli, 'ok');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    out('ERROR: ' . $e->getMessage(), $isCli, 'err');
    if (!$isCli) {
        http_response_code(500);
        echo '</body></html>';
    }
}
