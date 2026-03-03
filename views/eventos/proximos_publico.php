<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Próximos Eventos</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }
        .container {
            max-width: 980px;
            margin: 0 auto;
            padding: 24px 16px 40px;
        }
        h1 {
            margin: 0 0 8px;
        }
        .sub {
            margin: 0 0 20px;
            color: #4b5563;
        }
        .evento {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 14px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }
        .titulo {
            margin: 0 0 10px;
            font-size: 1.2rem;
        }
        .meta {
            margin: 0 0 8px;
            color: #374151;
            line-height: 1.45;
        }
        .descripcion {
            margin: 10px 0 0;
            line-height: 1.5;
            white-space: pre-line;
        }
        .media {
            margin-top: 12px;
            display: grid;
            gap: 10px;
        }
        .media img, .media video {
            max-width: 100%;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .empty {
            background: #fff;
            border: 1px dashed #d1d5db;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Próximos Eventos</h1>
        <p class="sub">Aquí encontrarás la información actualizada de los eventos programados.</p>

        <?php if (empty($eventos)): ?>
            <div class="empty">No hay próximos eventos por ahora.</div>
        <?php else: ?>
            <?php foreach ($eventos as $evento): ?>
                <article class="evento">
                    <h2 class="titulo"><?= htmlspecialchars($evento['Nombre_Evento'] ?? '') ?></h2>
                    <p class="meta">
                        <strong>Fecha:</strong> <?= htmlspecialchars($evento['Fecha_Evento'] ?? '') ?><br>
                        <strong>Hora:</strong> <?= htmlspecialchars($evento['Hora_Evento'] ?? '') ?><br>
                        <strong>Lugar:</strong> <?= htmlspecialchars($evento['Lugar_Evento'] ?? '') ?>
                    </p>
                    <p class="descripcion"><?= nl2br(htmlspecialchars($evento['Descripcion_Evento'] ?? '')) ?></p>

                    <?php if (!empty($evento['Imagen_Evento']) || !empty($evento['Video_Evento'])): ?>
                        <div class="media">
                            <?php if (!empty($evento['Imagen_Evento'])): ?>
                                <img src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode($evento['Imagen_Evento']) ?>" alt="Imagen del evento">
                            <?php endif; ?>

                            <?php if (!empty($evento['Video_Evento'])): ?>
                                <video controls>
                                    <source src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode($evento['Video_Evento']) ?>">
                                    Tu navegador no soporta video HTML5.
                                </video>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
