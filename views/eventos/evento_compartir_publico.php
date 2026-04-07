<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
        $tituloSeo = trim((string)($tituloCompartir ?? ($evento['Nombre_Evento'] ?? 'Evento')));
        $descripcionSeo = trim((string)($descripcionCompartir ?? ($evento['Descripcion_Evento'] ?? '')));
        $urlSeo = trim((string)($urlCompartir ?? ''));
        $imagenSeo = trim((string)($imagenCompartir ?? ''));
    ?>

    <title><?= htmlspecialchars($tituloSeo) ?></title>
    <meta name="description" content="<?= htmlspecialchars($descripcionSeo) ?>">

    <meta property="og:type" content="article">
    <meta property="og:title" content="<?= htmlspecialchars($tituloSeo) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($descripcionSeo) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($urlSeo) ?>">
    <?php if ($imagenSeo !== ''): ?>
        <meta property="og:image" content="<?= htmlspecialchars($imagenSeo) ?>">
    <?php endif; ?>

    <meta name="twitter:card" content="<?= $imagenSeo !== '' ? 'summary_large_image' : 'summary' ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($tituloSeo) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($descripcionSeo) ?>">
    <?php if ($imagenSeo !== ''): ?>
        <meta name="twitter:image" content="<?= htmlspecialchars($imagenSeo) ?>">
    <?php endif; ?>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px 16px 40px;
        }
        .evento {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }
        .titulo {
            margin: 0 0 12px;
            font-size: 1.5rem;
        }
        .meta {
            margin: 0 0 10px;
            color: #374151;
            line-height: 1.5;
        }
        .descripcion {
            margin: 14px 0 0;
            line-height: 1.6;
            white-space: pre-line;
        }
        .media {
            margin-top: 14px;
            display: grid;
            gap: 10px;
        }
        .media img, .media video {
            max-width: 100%;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
        }
        .acciones {
            margin-top: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 10px;
            border: 0;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        .btn-success {
            background: #16a34a;
            color: #fff;
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #111827;
        }
        .btn-primary {
            background: #2563eb;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <article class="evento">
            <h1 class="titulo"><?= htmlspecialchars($evento['Nombre_Evento'] ?? '') ?></h1>

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

            <div class="acciones">
                <a
                    class="btn btn-success"
                    href="https://wa.me/?text=<?= urlencode(($evento['Nombre_Evento'] ?? 'Evento') . ' - ' . ($urlSeo ?? '')) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    Compartir por WhatsApp
                </a>

                <button type="button" class="btn btn-secondary" id="btnCopiarEnlace">
                    Copiar enlace
                </button>

                <a class="btn btn-primary" href="<?= htmlspecialchars(rtrim(PUBLIC_URL, '/') . '/index.php?url=eventos/proximos') ?>">
                    Ver todos los próximos eventos
                </a>
            </div>
        </article>
    </div>

    <script>
        document.getElementById('btnCopiarEnlace')?.addEventListener('click', async function () {
            const url = <?= json_encode((string)$urlSeo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(url);
                } else {
                    const input = document.createElement('input');
                    input.value = url;
                    document.body.appendChild(input);
                    input.select();
                    document.execCommand('copy');
                    document.body.removeChild(input);
                }

                const txt = this.textContent;
                this.textContent = 'Enlace copiado';
                setTimeout(() => {
                    this.textContent = txt;
                }, 1800);
            } catch (err) {
                alert('No se pudo copiar el enlace.');
            }
        });
    </script>
</body>
</html>