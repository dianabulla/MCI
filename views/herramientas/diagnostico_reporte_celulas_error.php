<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error — Diagnóstico reporte células</title>
</head>
<body style="font-family:system-ui;padding:24px;">
    <h1>No se pudo ejecutar el diagnóstico</h1>
    <p><?= htmlspecialchars((string)($mensaje ?? 'Error desconocido'), ENT_QUOTES, 'UTF-8') ?></p>
    <p><a href="<?= htmlspecialchars(PUBLIC_URL . '?url=home', ENT_QUOTES, 'UTF-8') ?>">Volver al inicio</a></p>
</body>
</html>
