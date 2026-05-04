<?php
/**
 * Herramienta de administración: Marcar células existentes como antiguas
 *
 * Comportamiento:
 * - Asegura la columna celula.Es_Antiguo (0 = nueva, 1 = antigua).
 * - Modo simulación por defecto (no escribe en BD).
 * - Solo ejecuta UPDATE real cuando se pasa ?ejecutar=1.
 *
 * Seguridad:
 * - Permite acceso local sin clave.
 * - En acceso remoto exige ?clave=...
 */

define('CLAVE_ACCESO', 'mci_admin_2026'); // Cambiar antes de usar en producción

$claveRecibida = trim((string)($_GET['clave'] ?? ''));
$ipCliente = $_SERVER['REMOTE_ADDR'] ?? '';
$ipsLocales = ['127.0.0.1', '::1'];
$esLocal = in_array($ipCliente, $ipsLocales, true);

if (!$esLocal && !hash_equals(CLAVE_ACCESO, $claveRecibida)) {
    http_response_code(403);
    exit('Acceso denegado. Incluye ?clave=TU_CLAVE en la URL.');
}

require_once __DIR__ . '/../conexion.php'; // provee $pdo

$ejecutar = (string)($_GET['ejecutar'] ?? '') === '1';
$forzarTodo = (string)($_GET['forzar_todo'] ?? '') === '1';

header('Content-Type: text/html; charset=utf-8');

function out($msg, $color = '#333') {
    echo "<p style='font-family:monospace;color:{$color};margin:4px 0'>{$msg}</p>\n";
}

function titulo($txt) {
    echo "<h2 style='font-family:sans-serif;margin-top:24px'>{$txt}</h2>\n";
}

echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Marcar células antiguas</title>';
echo '<style>body{background:#f5f5f5;padding:20px} code{background:#eee;padding:2px 6px;border-radius:4px}</style>';
echo '</head><body>';

titulo('Marcar células existentes como antiguas');

if (!$ejecutar) {
    out('⚠️ MODO SIMULACIÓN: no se harán cambios en la base de datos.', '#856404');
    out('Para ejecutar de verdad usa <code>?ejecutar=1</code> (y <code>&clave=...</code> si aplica).', '#856404');
}

try {
    $col = $pdo->query("SHOW COLUMNS FROM celula LIKE 'Es_Antiguo'")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($col)) {
        $pdo->exec("ALTER TABLE celula ADD COLUMN Es_Antiguo TINYINT(1) NOT NULL DEFAULT 0");
        out('✅ Columna Es_Antiguo creada en tabla celula.', '#155724');
    } else {
        out('ℹ️ Columna Es_Antiguo ya existe.', '#004085');
    }
} catch (Throwable $e) {
    out('❌ Error asegurando columna Es_Antiguo: ' . htmlspecialchars($e->getMessage()), '#721c24');
    echo '</body></html>';
    exit;
}

try {
    $total = (int)$pdo->query("SELECT COUNT(*) FROM celula")->fetchColumn();
    $yaAntiguas = (int)$pdo->query("SELECT COUNT(*) FROM celula WHERE Es_Antiguo = 1")->fetchColumn();
    $pendientes = (int)$pdo->query("SELECT COUNT(*) FROM celula WHERE Es_Antiguo <> 1 OR Es_Antiguo IS NULL")->fetchColumn();

    titulo('Resumen previo');
    out('Total de células: ' . $total, '#333');
    out('Ya marcadas como antiguas: ' . $yaAntiguas, '#333');
    out('Pendientes por marcar: ' . $pendientes, '#333');

    if ($pendientes <= 0) {
        out('✅ No hay células pendientes por actualizar.', '#155724');
        echo '</body></html>';
        exit;
    }

    if (!$ejecutar) {
        out('Simulación completada. No se aplicaron cambios.', '#856404');
        echo '</body></html>';
        exit;
    }

    if ($forzarTodo) {
        $stmt = $pdo->prepare("UPDATE celula SET Es_Antiguo = 1");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("UPDATE celula SET Es_Antiguo = 1 WHERE Es_Antiguo <> 1 OR Es_Antiguo IS NULL");
        $stmt->execute();
    }

    out('✅ Actualización completada. Filas afectadas: ' . (int)$stmt->rowCount(), '#155724');
    if ($forzarTodo) {
        out('ℹ️ Se usó modo forzado sobre todas las células.', '#004085');
    }
} catch (Throwable $e) {
    out('❌ Error en actualización: ' . htmlspecialchars($e->getMessage()), '#721c24');
}

echo '</body></html>';
