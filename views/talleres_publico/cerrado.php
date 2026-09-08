<?php
$titulo = trim((string)($formulario['Titulo'] ?? 'Formulario'));
$mensaje = trim((string)($mensaje ?? ''));
if ($mensaje === '') {
    $mensaje = 'Este formulario ya no está recibiendo inscripciones. Gracias por su interés.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo !== '' ? $titulo : 'Formulario no disponible', ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f8fafc; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
        .box { max-width:520px; background:#fff; border-radius:12px; padding:32px; text-align:center; box-shadow:0 8px 30px rgba(0,0,0,.08); }
        .box h1 { font-size:1.35rem; color:#1e3a8a; margin:0 0 12px; line-height:1.35; }
        .box p { margin:0; color:#334155; font-size:1.02rem; line-height:1.55; white-space:pre-wrap; }
        .marca { font-size:0.78rem; letter-spacing:.08em; text-transform:uppercase; color:#64748b; margin-bottom:10px; }
    </style>
</head>
<body>
<div class="box">
    <div class="marca">MCI Madrid Colombia</div>
    <h1><?= htmlspecialchars($titulo !== '' ? $titulo : 'Formulario', ENT_QUOTES, 'UTF-8') ?></h1>
    <p><?= nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8')) ?></p>
</div>
</body>
</html>
