<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string)($modulo['titulo'] ?? 'Módulo de eventos')) ?></title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            background: #f4f7fb;
            color: #1f2a3d;
        }

        .wrap {
            max-width: 980px;
            margin: 0 auto;
            padding: 20px 14px 36px;
        }

        .header {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 4px 18px rgba(17, 37, 68, 0.08);
            margin-bottom: 14px;
        }

        .header h1 {
            margin: 0;
            color: #183f78;
            font-size: 28px;
        }

        .item {
            background: #ffffff;
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 4px 18px rgba(17, 37, 68, 0.08);
            margin-bottom: 14px;
        }

        .item h2 {
            margin: 0 0 8px;
            font-size: 22px;
            color: #0f376f;
        }

        .item p {
            margin: 0 0 10px;
            line-height: 1.55;
            color: #324763;
            white-space: pre-line;
        }

        .item img,
        .item video {
            width: 100%;
            max-height: 420px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #d8e2f1;
            background: #000;
        }

        .empty {
            background: #ffffff;
            border-radius: 12px;
            padding: 18px;
            text-align: center;
            color: #60708a;
            box-shadow: 0 4px 18px rgba(17, 37, 68, 0.08);
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="header">
            <h1><?= htmlspecialchars((string)($modulo['titulo'] ?? 'Información de eventos')) ?></h1>
        </div>

        <?php if (!empty($items)): ?>
            <?php foreach ($items as $item): ?>
                <article class="item">
                    <h2><?= htmlspecialchars((string)($item['Titulo'] ?? '')) ?></h2>
                    <p><?= nl2br(htmlspecialchars((string)($item['Parrafo'] ?? ''))) ?></p>

                    <?php if (!empty($item['Imagen'])): ?>
                        <img src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode((string)$item['Imagen']) ?>" alt="Imagen de <?= htmlspecialchars((string)($item['Titulo'] ?? 'contenido')) ?>">
                    <?php endif; ?>

                    <?php if (!empty($item['Video'])): ?>
                        <div style="margin-top:10px;">
                            <video controls preload="metadata">
                                <source src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode((string)$item['Video']) ?>">
                                Tu navegador no soporta video HTML5.
                            </video>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty">Aún no hay información publicada para este módulo.</div>
        <?php endif; ?>
    </div>
</body>
</html>
