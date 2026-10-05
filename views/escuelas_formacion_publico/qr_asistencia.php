<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Asistencia Universidad de la Vida</title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: #eef6f5;
            color: #203432;
            min-height: 100vh;
            padding: 24px 12px;
        }
        .card {
            max-width: 480px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #d1e6e3;
            border-radius: 16px;
            padding: 24px 20px;
            text-align: center;
            box-shadow: 0 14px 30px rgba(15, 77, 74, .12);
        }
        h1 { margin: 0 0 8px; font-size: 26px; }
        p { margin: 0 0 16px; color: #4e5f5d; }
        img {
            width: 320px;
            max-width: 100%;
            height: auto;
            border: 1px solid #dbe8e6;
            border-radius: 12px;
            padding: 10px;
            background: #fff;
        }
        .url {
            width: 100%;
            margin-top: 14px;
            border: 1px solid #cfe3e0;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 14px;
        }
        .actions { margin-top: 16px; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
        a, button {
            background: #0a6e6a;
            color: #fff;
            text-decoration: none;
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            cursor: pointer;
        }
        .secondary { background: #2f4f4c; }
        @media print {
            .actions { display: none; }
            body { background: #fff; }
        }
    </style>
</head>
<body>
<div class="card">
    <h1>Asistencia Universidad de la Vida</h1>
    <p>Escanea este QR para consultar por cédula y marcar la clase correspondiente.</p>
    <img src="<?= htmlspecialchars((string)($qr_url ?? ''), ENT_QUOTES, 'UTF-8') ?>" alt="QR asistencia Universidad de la Vida">
    <input class="url" type="text" readonly value="<?= htmlspecialchars((string)($url_formulario ?? ''), ENT_QUOTES, 'UTF-8') ?>" onclick="this.select()">
    <div class="actions">
        <a href="<?= htmlspecialchars((string)($url_formulario ?? ''), ENT_QUOTES, 'UTF-8') ?>">Abrir formulario</a>
        <button type="button" class="secondary" onclick="window.print()">Imprimir QR</button>
    </div>
</div>
</body>
</html>
