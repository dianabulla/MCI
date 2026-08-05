<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario no disponible</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f8fafc; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:20px; }
        .box { max-width:480px; background:#fff; border-radius:12px; padding:32px; text-align:center; box-shadow:0 8px 30px rgba(0,0,0,.08); }
    </style>
</head>
<body>
<div class="box">
    <h1 style="font-size:1.4rem;color:#334155;">Formulario no disponible</h1>
    <p class="text-muted">El enlace no existe, está inactivo o aún no tiene campos configurados.</p>
    <?php if (!empty($slug)): ?>
    <p class="small text-muted">Referencia: <?= htmlspecialchars((string)$slug, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
</div>
</body>
</html>
