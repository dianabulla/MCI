<?php
// Reclasifica Tipo_Pago historico en Escuelas de Formacion.
// Regla solicitada:
// - Valor_Pago < umbral  => abono
// - Valor_Pago >= umbral => completo
//
// Uso CLI:
//   php tools/reclasificar_tipos_pago_escuelas.php --dry-run
//   php tools/reclasificar_tipos_pago_escuelas.php --apply --umbral=180000
//
// Uso navegador (token requerido):
//   /tools/reclasificar_tipos_pago_escuelas.php?token=TU_TOKEN&mode=dry-run
//   /tools/reclasificar_tipos_pago_escuelas.php?token=TU_TOKEN&mode=apply&confirm=SI&umbral=180000

require_once __DIR__ . '/../app/Config/config.php';

$isCli = (PHP_SAPI === 'cli');
if (!$isCli) {
    header('Content-Type: text/plain; charset=UTF-8');
}

function out(string $msg, bool $isCli): void {
    if ($isCli) {
        echo $msg . PHP_EOL;
        return;
    }
    echo $msg . "\n";
}

function getCliArgValue(array $argv, string $name, ?string $default = null): ?string {
    $prefix = '--' . $name . '=';
    foreach ($argv as $arg) {
        if (strpos($arg, $prefix) === 0) {
            return substr($arg, strlen($prefix));
        }
    }
    return $default;
}

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
    out('No se pudo cargar configuración de base de datos.', $isCli);
    exit(1);
}

$apply = false;
$umbral = 180000;

if ($isCli) {
    $apply = in_array('--apply', $argv, true);
    $umbralArg = getCliArgValue($argv, 'umbral');
    if ($umbralArg !== null && is_numeric($umbralArg)) {
        $umbral = (int)$umbralArg;
    }
} else {
    $expectedToken = hash('sha256', (string)DB_HOST . '|' . (string)DB_NAME . '|' . (string)DB_USER . '|' . (string)(defined('DB_PASS') ? DB_PASS : ''));
    $token = (string)($_GET['token'] ?? '');
    $mode = strtolower(trim((string)($_GET['mode'] ?? 'dry-run')));
    $confirm = strtoupper(trim((string)($_GET['confirm'] ?? '')));
    $umbralGet = (string)($_GET['umbral'] ?? '180000');

    if ($token === '' || !hash_equals($expectedToken, $token)) {
        http_response_code(403);
        out('Acceso denegado. Token inválido o ausente.', $isCli);
        out('Modo navegador permitido:', $isCli);
        out('- dry-run: ?token=TU_TOKEN&mode=dry-run&umbral=180000', $isCli);
        out('- apply:   ?token=TU_TOKEN&mode=apply&confirm=SI&umbral=180000', $isCli);
        exit(1);
    }

    $apply = ($mode === 'apply' && $confirm === 'SI');
    if (is_numeric($umbralGet)) {
        $umbral = (int)$umbralGet;
    }
}

$umbral = max(1, $umbral);

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');

try {
    $pdo = new PDO($dsn, DB_USER, defined('DB_PASS') ? DB_PASS : '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    out('Error conectando a BD: ' . $e->getMessage(), $isCli);
    exit(1);
}

$targets = [
    [
        'table' => 'escuela_formacion_pago_movimiento',
        'id' => 'Id_Pago',
        'label' => 'Movimientos de pago',
    ],
    [
        'table' => 'escuela_formacion_inscripcion',
        'id' => 'Id_Inscripcion',
        'label' => 'Inscripción (snapshot)',
    ],
];

out('=== Reclasificación de Tipo_Pago (Escuelas) ===', $isCli);
out('Modo: ' . ($apply ? 'APLICAR' : 'SIMULACIÓN (dry-run)'), $isCli);
out('Regla: Valor_Pago < ' . $umbral . ' => abono, >= ' . $umbral . ' => completo', $isCli);
out('', $isCli);

$totalCambios = 0;
$hayTabla = false;

try {
    if ($apply) {
        $pdo->beginTransaction();
    }

    foreach ($targets as $target) {
        $table = $target['table'];
        $idCol = $target['id'];
        $label = $target['label'];

        if (!tableExists($pdo, $table)) {
            out('[SKIP] ' . $label . ' -> tabla no existe (' . $table . ')', $isCli);
            continue;
        }

        $hayTabla = true;

        $sqlConteo = "SELECT
                SUM(CASE WHEN Valor_Pago > 0 AND Valor_Pago < :umbral AND COALESCE(LOWER(TRIM(Tipo_Pago)), '') <> 'abono' THEN 1 ELSE 0 END) AS a_abono,
                SUM(CASE WHEN Valor_Pago >= :umbral AND COALESCE(LOWER(TRIM(Tipo_Pago)), '') <> 'completo' THEN 1 ELSE 0 END) AS a_completo,
                SUM(CASE WHEN Valor_Pago > 0 THEN 1 ELSE 0 END) AS evaluados
            FROM {$table}";

        $stmtConteo = $pdo->prepare($sqlConteo);
        $stmtConteo->execute(['umbral' => $umbral]);
        $conteo = $stmtConteo->fetch() ?: [];

        $aAbono = (int)($conteo['a_abono'] ?? 0);
        $aCompleto = (int)($conteo['a_completo'] ?? 0);
        $evaluados = (int)($conteo['evaluados'] ?? 0);
        $aCambiar = $aAbono + $aCompleto;

        out('[' . $label . ']', $isCli);
        out('- Registros evaluados (Valor_Pago > 0): ' . $evaluados, $isCli);
        out('- Cambiar a abono: ' . $aAbono, $isCli);
        out('- Cambiar a completo: ' . $aCompleto, $isCli);
        out('- Total por cambiar: ' . $aCambiar, $isCli);

        if ($aCambiar > 0) {
            $sqlPreview = "SELECT
                    {$idCol} AS Id_Registro,
                    COALESCE(Cedula, '') AS Cedula,
                    COALESCE(Nombre, '') AS Nombre,
                    COALESCE(Programa, '') AS Programa,
                    COALESCE(Tipo_Pago, '') AS Tipo_Pago_Actual,
                    Valor_Pago,
                    CASE WHEN Valor_Pago < :umbral THEN 'abono' ELSE 'completo' END AS Tipo_Pago_Nuevo
                FROM {$table}
                WHERE Valor_Pago > 0
                  AND (
                    (Valor_Pago < :umbral AND COALESCE(LOWER(TRIM(Tipo_Pago)), '') <> 'abono')
                    OR
                    (Valor_Pago >= :umbral AND COALESCE(LOWER(TRIM(Tipo_Pago)), '') <> 'completo')
                  )
                ORDER BY Valor_Pago ASC
                LIMIT 10";
            $stmtPreview = $pdo->prepare($sqlPreview);
            $stmtPreview->execute(['umbral' => $umbral]);
            $preview = $stmtPreview->fetchAll();

            out('- Vista previa (hasta 10):', $isCli);
            foreach ($preview as $row) {
                out(
                    '  * ID ' . (int)$row['Id_Registro'] .
                    ' | Cedula=' . (string)$row['Cedula'] .
                    ' | Valor=' . number_format((float)$row['Valor_Pago'], 0, ',', '.') .
                    ' | ' . (string)$row['Tipo_Pago_Actual'] . ' -> ' . (string)$row['Tipo_Pago_Nuevo'],
                    $isCli
                );
            }
        }

        if ($apply && $aCambiar > 0) {
            $sqlUpdate = "UPDATE {$table}
                SET Tipo_Pago = CASE
                    WHEN Valor_Pago > 0 AND Valor_Pago < :umbral THEN 'abono'
                    WHEN Valor_Pago >= :umbral THEN 'completo'
                    ELSE Tipo_Pago
                END
                WHERE Valor_Pago > 0
                  AND (
                    (Valor_Pago < :umbral AND COALESCE(LOWER(TRIM(Tipo_Pago)), '') <> 'abono')
                    OR
                    (Valor_Pago >= :umbral AND COALESCE(LOWER(TRIM(Tipo_Pago)), '') <> 'completo')
                  )";

            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute(['umbral' => $umbral]);
            $actualizados = (int)$stmtUpdate->rowCount();
            out('- Actualizados: ' . $actualizados, $isCli);
            $totalCambios += $actualizados;
        } else {
            $totalCambios += $aCambiar;
        }

        out('', $isCli);
    }

    if (!$hayTabla) {
        if ($apply && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        out('No se encontró ninguna tabla objetivo. No se aplicaron cambios.', $isCli);
        exit(1);
    }

    if ($apply) {
        $pdo->commit();
        out('✅ Corrección aplicada. Total de filas actualizadas: ' . $totalCambios, $isCli);
    } else {
        out('✅ Simulación completada. Filas que se actualizarían: ' . $totalCambios, $isCli);
        out('Para aplicar en CLI: php tools/reclasificar_tipos_pago_escuelas.php --apply --umbral=' . $umbral, $isCli);
        if (!$isCli) {
            out('Para aplicar en navegador: ?token=TU_TOKEN&mode=apply&confirm=SI&umbral=' . $umbral, $isCli);
        }
    }
} catch (Throwable $e) {
    if ($apply && isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    out('❌ Error: ' . $e->getMessage(), $isCli);
    exit(1);
}
