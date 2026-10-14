<?php
$pendientes = (int)($pendientes ?? 0);
$mensaje = (string)($mensaje ?? '');
$tipoMensaje = (string)($tipo_mensaje ?? '');
$action = (defined('PUBLIC_URL') ? rtrim(PUBLIC_URL, '/') . '/index.php?url=personas/sincronizar-edades' : '?url=personas/sincronizar-edades');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actualizar edades</title>
    <style>
        body { font-family: sans-serif; background: #f4f6fb; margin: 0; padding: 24px; color: #1e293b; }
        .box { max-width: 520px; margin: 40px auto; background: #fff; border: 1px solid #dbe4f0; border-radius: 12px; padding: 24px; }
        h1 { margin: 0 0 12px; font-size: 1.3rem; }
        .btn { margin-top: 14px; background: #2563eb; color: #fff; border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 700; cursor: pointer; }
        .ok { background: #e8f7ee; color: #166534; padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; }
        .err { background: #fef2f2; color: #991b1b; padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; }
    </style>
</head>
<body>
<div class="box">
    <h1>Actualizar edades</h1>
    <p>Calcula la edad con la fecha de nacimiento. Solo para administradores.</p>

    <?php if ($mensaje !== ''): ?>
        <div class="<?= $tipoMensaje === 'success' ? 'ok' : 'err' ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <p>Pendientes: <strong><?= $pendientes ?></strong></p>
    <?php if ($pendientes > 0): ?>
    <form method="POST" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('¿Actualizar <?= $pendientes ?> persona(s)?');">
        <input type="hidden" name="confirmar" value="1">
        <button class="btn" type="submit">Actualizar edades ahora</button>
    </form>
    <?php else: ?>
    <p>No hay registros pendientes.</p>
    <?php endif; ?>
</div>
</body>
</html>
