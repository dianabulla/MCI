<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($titulo ?? 'Error', ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { font-family: Segoe UI, sans-serif; padding: 24px; max-width: 720px; margin: 0 auto; }
        .box { background: #fef2f2; border: 1px solid #fecaca; padding: 16px; border-radius: 8px; color: #991b1b; }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($titulo ?? 'Error', ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="box"><p><?= nl2br(htmlspecialchars($mensaje ?? '', ENT_QUOTES, 'UTF-8')) ?></p></div>
    <p><a href="<?= htmlspecialchars(PUBLIC_URL . '?url=herramientas/diagnostico-documento', ENT_QUOTES, 'UTF-8') ?>">Reintentar</a></p>
</body>
</html>
