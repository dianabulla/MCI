<?php
/**
 * Lista plantillas de WhatsApp Cloud API para verificar nombre + idioma exactos.
 *
 * Uso:
 * http://localhost/mcimadrid/test_whatsapp_templates.php?waba_id=912285834724033
 *
 * Opcional:
 * - token=... (si no se envía, usa el temporal por defecto)
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: text/html; charset=UTF-8');

$token = trim((string)($_GET['token'] ?? 'EAAhNJ1hCbaYBQZCmqWuBLBcsvBI7BOFm5LwQMFPy8xxxGHRDZAZAHp69B20wZCOiP1qqR6FBrg9Aq4rUNwxQnIF02Jc37vzZC4IoKsCAvgYB0eThKMYhqGKq0ldmjVlnKAgV78xILrJiZACDaabQKJZCeCioK7of3m6fC8ZBm0HNYAkA8ExB4tqw5z0ESYWr3TvGR0Ypa2B1eq67F493GoJAHD11lUqfuIzGPqiDq8ZC0leaMhdHJGXKob2mTIj1zatsZCbvlzpvZBDxfN8XARZC52tpGQZDZD'));
$wabaId = trim((string)($_GET['waba_id'] ?? '912285834724033'));

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function onlyDigits(string $v): string { return preg_replace('/\D+/', '', $v) ?: ''; }

$wabaId = onlyDigits($wabaId);

if ($token === '' || $wabaId === '') {
    echo '<h2>Faltan datos</h2>';
    echo '<p>Usa: http://localhost/mcimadrid/test_whatsapp_templates.php?waba_id=912285834724033</p>';
    exit;
}

$url = "https://graph.facebook.com/v22.0/{$wabaId}/message_templates?limit=200";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$token}",
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$decoded = json_decode((string)$response, true);
$items = is_array($decoded) ? ($decoded['data'] ?? []) : [];

?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Plantillas WhatsApp</title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;padding:20px;background:#f7f9fc;color:#1f2a44}
    .box{background:#fff;border:1px solid #dbe4f0;border-radius:10px;padding:14px;margin-bottom:12px}
    .ok{color:#167c3f}.err{color:#b42318}.muted{color:#4d5f85}
    table{width:100%;border-collapse:collapse}
    th,td{border-bottom:1px solid #e4ebf6;padding:8px;text-align:left;font-size:14px}
    th{background:#f4f7fd}
    pre{white-space:pre-wrap;background:#f4f6fb;border:1px solid #e1e8f5;padding:10px;border-radius:8px}
  </style>
</head>
<body>
  <h2>Plantillas WhatsApp (WABA)</h2>
  <div class="box muted">
    <div><strong>WABA ID:</strong> <?= h($wabaId) ?></div>
    <div><strong>HTTP:</strong> <?= (int)$httpCode ?></div>
    <div><strong>Total:</strong> <?= is_array($items) ? count($items) : 0 ?></div>
  </div>

  <?php if ($curlError !== ''): ?>
    <div class="box err">❌ Error cURL: <?= h($curlError) ?></div>
  <?php elseif ($httpCode < 200 || $httpCode >= 300): ?>
    <div class="box err">❌ Error consultando plantillas</div>
    <div class="box"><pre><?= h((string)$response) ?></pre></div>
  <?php else: ?>
    <div class="box ok">✅ Consulta exitosa</div>
    <div class="box">
      <table>
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Idioma</th>
            <th>Estado</th>
            <th>Categoría</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($items)): ?>
            <?php foreach ($items as $t): ?>
              <tr>
                <td><?= h((string)($t['name'] ?? '')) ?></td>
                <td><?= h((string)($t['language'] ?? '')) ?></td>
                <td><?= h((string)($t['status'] ?? '')) ?></td>
                <td><?= h((string)($t['category'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="4">No hay plantillas para mostrar</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</body>
</html>
