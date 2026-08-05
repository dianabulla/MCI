<?php
$titulo = (string)($formulario['Titulo'] ?? 'Formulario');
$descripcion = trim((string)($formulario['Descripcion'] ?? ''));
$urlAbsoluta = (string)($url_absoluta ?? '');
$urlFormulario = (string)($url_formulario ?? '');
$slugArchivo = preg_replace('/[^a-z0-9_-]+/i', '-', (string)($formulario['Slug'] ?? 'formulario'));

// Mismo servicio que views/teen/qr_registro.php — funciona sin JavaScript externo.
$qrImagenUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&margin=12&format=png&data=' . rawurlencode($urlAbsoluta);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR — <?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: system-ui, -apple-system, sans-serif;
            background: #f0f4ff;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 16px 48px rgba(15, 23, 42, 0.12);
            max-width: 420px;
            width: 100%;
            padding: 32px 28px;
            text-align: center;
        }
        .logo-text {
            font-size: 0.8rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 8px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 1.35rem;
            color: #1e40af;
            line-height: 1.3;
        }
        .sub {
            font-size: 0.92rem;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.45;
        }
        #qr-canvas-wrap {
            display: inline-block;
            padding: 14px;
            background: #fff;
            border: 2px solid #dbeafe;
            border-radius: 12px;
            margin-bottom: 16px;
        }
        #qr-imagen {
            display: block;
            width: 260px;
            height: 260px;
            object-fit: contain;
        }
        .instruccion {
            font-size: 1rem;
            font-weight: 600;
            color: #1e3a8a;
            margin-bottom: 12px;
        }
        .url-box {
            font-size: 0.78rem;
            color: #475569;
            word-break: break-all;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 20px;
            text-align: left;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        .btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: #2563eb;
            color: #fff;
        }
        .btn-outline {
            background: #fff;
            color: #2563eb;
            border: 1px solid #93c5fd;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .card { box-shadow: none; max-width: 100%; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo-text">MCI Madrid</div>
    <h1><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h1>
    <?php if ($descripcion !== ''): ?>
    <p class="sub"><?= htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
    <p class="sub">Formulario de inscripción y diagnóstico</p>
    <?php endif; ?>

    <div id="qr-canvas-wrap">
        <?php if ($urlAbsoluta !== ''): ?>
        <img id="qr-imagen"
             src="<?= htmlspecialchars($qrImagenUrl, ENT_QUOTES, 'UTF-8') ?>"
             width="260" height="260"
             alt="Código QR — <?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?>">
        <?php else: ?>
        <p style="color:#b91c1c;font-size:0.9rem;margin:0;">No hay URL para generar el QR.</p>
        <?php endif; ?>
    </div>

    <p class="instruccion">Escanee el código QR para inscribirse</p>

    <div class="url-box"><?= htmlspecialchars($urlAbsoluta, ENT_QUOTES, 'UTF-8') ?></div>

    <div class="actions">
        <a href="<?= htmlspecialchars($urlFormulario, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Abrir formulario</a>
        <button type="button" class="btn btn-outline" onclick="window.print()">Imprimir QR</button>
        <?php if ($urlAbsoluta !== ''): ?>
        <a href="<?= htmlspecialchars($qrImagenUrl, ENT_QUOTES, 'UTF-8') ?>"
           class="btn btn-outline"
           download="qr-<?= htmlspecialchars($slugArchivo, ENT_QUOTES, 'UTF-8') ?>.png"
           id="btn-descargar-qr">Descargar QR</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
