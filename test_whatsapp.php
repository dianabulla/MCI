<?php
/**
 * Prueba directa de WhatsApp Cloud API (hello_world)
 *
 * URL de ejemplo:
 * http://localhost/mcimadrid/test_whatsapp.php?token=TU_TOKEN&to=573001112233
 *
 * Opcionales:
 * - phone_id=1061529400372298
 * - template=hello_world
 * - lang=en_US
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=UTF-8');

$token = trim((string)($_GET['token'] ?? 'EAAhNJ1hCbaYBQZCmqWuBLBcsvBI7BOFm5LwQMFPy8xxxGHRDZAZAHp69B20wZCOiP1qqR6FBrg9Aq4rUNwxQnIF02Jc37vzZC4IoKsCAvgYB0eThKMYhqGKq0ldmjVlnKAgV78xILrJiZACDaabQKJZCeCioK7of3m6fC8ZBm0HNYAkA8ExB4tqw5z0ESYWr3TvGR0Ypa2B1eq67F493GoJAHD11lUqfuIzGPqiDq8ZC0leaMhdHJGXKob2mTIj1zatsZCbvlzpvZBDxfN8XARZC52tpGQZDZD'));
$phoneId = trim((string)($_GET['phone_id'] ?? '1061529400372298'));
$numeroDestino = trim((string)($_GET['to'] ?? ''));
$template = trim((string)($_GET['template'] ?? 'hello_world'));
$lang = trim((string)($_GET['lang'] ?? 'en_US'));
$bodyParam1 = trim((string)($_GET['body_param1'] ?? ''));

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function onlyDigits(string $value): string {
    return preg_replace('/\D+/', '', $value) ?: '';
}

$numeroDestino = onlyDigits($numeroDestino);
$phoneId = onlyDigits($phoneId);

if ($token === '' || $phoneId === '' || $numeroDestino === '') {
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Test WhatsApp</title>
        <style>
            body{font-family:Arial,Helvetica,sans-serif;padding:22px;background:#f7f9fc;color:#1f2a44}
            .box{background:#fff;border:1px solid #dbe4f0;border-radius:10px;padding:16px;margin-bottom:14px}
            .warn{color:#7a5a00}
            code{background:#eef3fb;padding:2px 6px;border-radius:6px}
        </style>
    </head>
    <body>
        <h2>Test WhatsApp Cloud API</h2>
        <div class="box warn">
            <p><strong>Faltan parámetros obligatorios.</strong></p>
            <p>Usa esta URL:</p>
            <p><code>http://localhost/mcimadrid/test_whatsapp.php?token=TU_TOKEN&to=573001112233</code></p>
            <p>Opcionales: <code>&phone_id=1061529400372298&template=hello_world&lang=en_US</code></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$url = "https://graph.facebook.com/v18.0/{$phoneId}/messages";

$payload = [
    'messaging_product' => 'whatsapp',
    'to' => $numeroDestino,
    'type' => 'template',
    'template' => [
        'name' => $template,
        'language' => ['code' => $lang]
    ]
];

if ($bodyParam1 !== '') {
    $payload['template']['components'] = [[
        'type' => 'body',
        'parameters' => [[
            'type' => 'text',
            'text' => $bodyParam1
        ]]
    ]];
}

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
$curlError = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado Test WhatsApp</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;padding:22px;background:#f7f9fc;color:#1f2a44}
        .box{background:#fff;border:1px solid #dbe4f0;border-radius:10px;padding:16px;margin-bottom:14px}
        .ok{color:#167c3f}
        .err{color:#b42318}
        pre{white-space:pre-wrap;background:#f4f6fb;border:1px solid #e1e8f5;padding:10px;border-radius:8px}
        .meta{font-size:13px;color:#4d5f85}
    </style>
</head>
<body>
    <h2>Resultado Test WhatsApp</h2>

    <div class="box meta">
        <div><strong>Phone ID:</strong> <?= h($phoneId) ?></div>
        <div><strong>Destino:</strong> <?= h($numeroDestino) ?></div>
        <div><strong>Template:</strong> <?= h($template) ?></div>
        <div><strong>Idioma:</strong> <?= h($lang) ?></div>
        <div><strong>Parámetro {{1}}:</strong> <?= h($bodyParam1 !== '' ? $bodyParam1 : '(no enviado)') ?></div>
        <div><strong>HTTP:</strong> <?= (int)$httpCode ?></div>
    </div>

    <?php if ($curlError !== ''): ?>
        <div class="box err">❌ Error cURL: <?= h($curlError) ?></div>
    <?php elseif ($httpCode >= 200 && $httpCode < 300): ?>
        <div class="box ok">✅ Envío aceptado por Meta.</div>
    <?php else: ?>
        <div class="box err">❌ Meta devolvió error. Revisa la respuesta abajo.</div>
    <?php endif; ?>

    <div class="box">
        <h3>Respuesta de Meta</h3>
        <pre><?= h((string)$response) ?></pre>
    </div>
</body>
</html>
