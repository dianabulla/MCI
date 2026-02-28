<?php
/**
 * Envío masivo WhatsApp desde tabla nehemias
 *
 * Uso local (XAMPP):
 * http://localhost/mcimadrid/tools/enviar_whatsapp_nehemias.php
 *
 * Parámetros opcionales por URL:
 * - dry_run=1 (default)  -> no envía, solo simula
 * - dry_run=0            -> intenta enviar (solo usar cuando plantilla esté aprobada)
 * - limit=50             -> tamaño de lote
 * - offset=0             -> desplazamiento para paginar
 * - test_phone=573001112233 -> fuerza envío/simulación a un solo número (prueba)
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../conexion.php';

// ==============================
// 1) CONFIGURACIÓN (AJUSTAR)
// ==============================
$token = getenv('WHATSAPP_TOKEN') ?: '';            // Recomendado: variable de entorno
$phoneId = getenv('WHATSAPP_PHONE_ID') ?: '';       // Phone Number ID de Meta
$templateName = getenv('WHATSAPP_TEMPLATE') ?: 'info_actualizacion_01';
$templateLang = getenv('WHATSAPP_TEMPLATE_LANG') ?: 'es_CO';

// Seguridad operativa: por defecto NO envía
$dryRun = isset($_GET['dry_run']) ? ((int)$_GET['dry_run'] === 1) : true;
$limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 50;
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$testPhone = trim((string)($_GET['test_phone'] ?? ''));

// ==============================
// 2) HELPERS
// ==============================
function normalizePhoneCO(string $raw): ?string {
    $digits = preg_replace('/\D+/', '', $raw);
    if ($digits === '') {
        return null;
    }

    // Casos comunes Colombia
    if (preg_match('/^3\d{9}$/', $digits)) {
        return '57' . $digits;
    }

    if (preg_match('/^57(3\d{9})$/', $digits)) {
        return $digits;
    }

    // Si viene con 00 internacional
    if (preg_match('/^0057(3\d{9})$/', $digits)) {
        return '57' . substr($digits, 4);
    }

    // Ya internacional de otro país o formato no esperado
    if (strlen($digits) >= 10 && strlen($digits) <= 15) {
        return $digits;
    }

    return null;
}

function sendTemplateMessage(string $token, string $phoneId, string $templateName, string $templateLang, string $to, string $nombre): array {
    $url = "https://graph.facebook.com/v18.0/{$phoneId}/messages";

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'template',
        'template' => [
            'name' => $templateName,
            'language' => ['code' => $templateLang],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $nombre
                        ]
                    ]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$token}",
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    $statusCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $statusCode,
        'response' => $response,
        'curl_error' => $curlErr,
        'payload' => $payload
    ];
}

function logLine(string $line): void {
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $file = $dir . '/whatsapp_nehemias_' . date('Ymd') . '.log';
    @file_put_contents($file, '[' . date('Y-m-d H:i:s') . "] {$line}" . PHP_EOL, FILE_APPEND);
}

// ==============================
// 3) LECTURA DE CONTACTOS
// ==============================
$sql = "
    SELECT
        Id_Nehemias,
        Nombres,
        Apellidos,
        Telefono,
        Acepta
    FROM nehemias
    WHERE TRIM(COALESCE(Telefono, '')) <> ''
      AND (Acepta = 1 OR Acepta = '1')
";

$sql .= "
    ORDER BY Id_Nehemias ASC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$contactos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = count($contactos);
$enviados = 0;
$errores = 0;
$omitidos = 0;

$self = htmlspecialchars($_SERVER['PHP_SELF'] ?? 'enviar_whatsapp_nehemias.php', ENT_QUOTES, 'UTF-8');
$nextOffset = $offset + $limit;
$prevOffset = max(0, $offset - $limit);
$queryTestPhone = $testPhone !== '' ? '&test_phone=' . urlencode($testPhone) : '';
$nextUrl = $self . '?dry_run=' . ($dryRun ? '1' : '0') . '&limit=' . (int)$limit . '&offset=' . (int)$nextOffset . $queryTestPhone;
$prevUrl = $self . '?dry_run=' . ($dryRun ? '1' : '0') . '&limit=' . (int)$limit . '&offset=' . (int)$prevOffset . $queryTestPhone;
$dryRunUrl = $self . '?dry_run=1&limit=' . (int)$limit . '&offset=' . (int)$offset . $queryTestPhone;
$singleTestUrl = $self . '?dry_run=1&limit=1&offset=0&test_phone=573001112233';

header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Envío WhatsApp Nehemías</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;padding:20px;background:#f7f9fc;color:#1f2a44}
        .box{background:#fff;border:1px solid #dbe4f0;border-radius:10px;padding:16px;margin-bottom:14px}
        .ok{color:#167c3f}
        .err{color:#b42318}
        .warn{color:#7a5a00}
        pre{white-space:pre-wrap;background:#f4f6fb;border:1px solid #e1e8f5;padding:10px;border-radius:8px}
        .meta{font-size:13px;color:#4d5f85}
        .help{background:#eef6ff;border:1px solid #cfe4ff;color:#113a6b}
        .btn{display:inline-block;padding:8px 12px;border-radius:8px;text-decoration:none;border:1px solid #c7d8f4;background:#fff;color:#1f2a44;font-weight:600;margin-right:8px;margin-bottom:8px}
        .btn-primary{background:#1f4d8f;color:#fff;border-color:#1f4d8f}
        .btn:hover{filter:brightness(0.97)}
    </style>
</head>
<body>
    <h2>Envío WhatsApp - Nehemías</h2>

    <div class="box warn">
        <strong>Importante:</strong> este archivo es una <strong>herramienta técnica de prueba</strong>.
        El módulo oficial del sistema es <strong>Campañas WhatsApp</strong>.
        <div style="margin-top:10px;">
            <a class="btn btn-primary" href="<?= htmlspecialchars(PUBLIC_URL) ?>?url=nehemias/whatsapp-campanas">Ir al módulo oficial</a>
        </div>
    </div>

    <div class="box help">
        <strong>Qué significa lo que viste:</strong>
        <ul>
            <li><strong>SIMULADO</strong>: validó el contacto, pero <strong>no envió</strong> mensaje.</li>
            <li><strong>OMITIDO</strong>: el teléfono es inválido o incompleto.</li>
            <li>Tu resultado actual confirma que el script está funcionando en modo prueba.</li>
        </ul>
        <a class="btn btn-primary" href="<?= $nextUrl ?>">Procesar siguiente lote</a>
        <a class="btn" href="<?= $prevUrl ?>">Volver al lote anterior</a>
        <a class="btn" href="<?= $dryRunUrl ?>">Repetir este lote (prueba)</a>
        <a class="btn" href="<?= $singleTestUrl ?>">Probar con 1 número</a>
    </div>

    <div class="box meta">
        <div><strong>Modo:</strong> <?= $dryRun ? 'DRY RUN (simulación, no envía)' : 'ENVÍO REAL' ?></div>
        <div><strong>Lote:</strong> limit=<?= (int)$limit ?>, offset=<?= (int)$offset ?></div>
        <div><strong>Template:</strong> <?= htmlspecialchars($templateName) ?> (<?= htmlspecialchars($templateLang) ?>)</div>
        <div><strong>Registros cargados:</strong> <?= (int)$total ?></div>
        <?php if ($testPhone !== ''): ?>
            <div><strong>Test phone override:</strong> <?= htmlspecialchars($testPhone) ?></div>
        <?php endif; ?>
    </div>

<?php
if ($total === 0) {
    echo '<div class="box warn">No hay contactos en este lote.</div>';
    exit;
}

if (!$dryRun && ($token === '' || $phoneId === '')) {
    echo '<div class="box err">Falta configuración para envío real: WHATSAPP_TOKEN o WHATSAPP_PHONE_ID vacío.</div>';
    exit;
}

foreach ($contactos as $c) {
    $nombre = trim((string)($c['Nombres'] ?? ''));
    if ($nombre === '') {
        $nombre = 'Amigo(a)';
    }

    $telefonoDB = (string)($c['Telefono'] ?? '');
    $to = $testPhone !== '' ? $testPhone : $telefonoDB;
    $to = normalizePhoneCO($to);

    if (!$to) {
        $omitidos++;
        $msg = "OMITIDO Id={$c['Id_Nehemias']} telefono_invalido=" . $telefonoDB;
        echo '<div class="box warn">⚠️ ' . htmlspecialchars($msg) . '</div>';
        logLine($msg);
        continue;
    }

    if ($dryRun) {
        $enviados++;
        $msg = "SIMULADO Id={$c['Id_Nehemias']} to={$to} nombre={$nombre}";
        echo '<div class="box ok">✅ ' . htmlspecialchars($msg) . '</div>';
        logLine($msg);
        continue;
    }

    $result = sendTemplateMessage($token, $phoneId, $templateName, $templateLang, $to, $nombre);

    if ($result['status'] >= 200 && $result['status'] < 300) {
        $enviados++;
        $msg = "ENVIADO Id={$c['Id_Nehemias']} to={$to} status={$result['status']}";
        echo '<div class="box ok">✅ ' . htmlspecialchars($msg) . '</div>';
        logLine($msg);
    } else {
        $errores++;
        $msg = "ERROR Id={$c['Id_Nehemias']} to={$to} status={$result['status']}";
        echo '<div class="box err">❌ ' . htmlspecialchars($msg) . '<pre>' . htmlspecialchars((string)$result['response']) . '</pre></div>';
        logLine($msg . ' response=' . (string)$result['response']);
    }

    // pequeña pausa para evitar picos
    usleep(200000); // 0.2s
}
?>

    <div class="box">
        <h3>Resumen</h3>
        <p>Procesados: <strong><?= (int)$total ?></strong></p>
        <p class="ok">Enviados/simulados: <strong><?= (int)$enviados ?></strong></p>
        <p class="warn">Omitidos: <strong><?= (int)$omitidos ?></strong></p>
        <p class="err">Errores: <strong><?= (int)$errores ?></strong></p>
    </div>

    <div class="box meta">
        <p><strong>Siguiente lote sugerido:</strong></p>
        <pre><?= htmlspecialchars($nextUrl) ?></pre>
    </div>
</body>
</html>
