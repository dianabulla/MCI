<!DOCTYPE html>
<?php
$tipoActual = (string)($modulo['tipo'] ?? '');
?>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string)($modulo['titulo'] ?? 'Módulo de eventos')) ?></title>
    <style>
        :root {
            --green-primary: #20D4A4;
            --green-mid: #66E3C2;
            --green-soft: #A8F0DA;
            --green-deep: #17B98F;
            --surface: #FFFFFF;
            --text-main: #6F7683;
            --text-title: #626C7A;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #F6FAF8;
            color: var(--text-main);
            line-height: 1.6;
        }

        .wrap {
            margin: 0;
            padding: 0;
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: #F7F9F8;
            color: var(--text-title);
            padding: 40px 20px;
            text-align: center;
            border-bottom: 1px solid #E5EEEA;
            box-shadow: none;
        }

        .header h1 {
            margin: 0;
            font-size: 36px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: var(--text-title);
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 28px;
            }
        }

        /* Layout alternado */
        .stories-container {
            padding: 0;
        }

        .item {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            align-items: stretch;
            background: var(--surface);
            border-bottom: 1px solid #E9F3EF;
            min-height: 500px;
        }

        /* Alternar dirección */
        .item:nth-child(odd) {
            direction: ltr;
        }

        .item:nth-child(even) {
            direction: rtl;
        }

        .item:nth-child(even) > * {
            direction: ltr;
        }

        .media-wrapper {
            background: linear-gradient(135deg, #EFFAF5 0%, var(--surface) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }

        .item:nth-child(even) .media-wrapper {
            background: linear-gradient(135deg, #F3FCF8 0%, var(--surface) 100%);
        }

        .item:nth-child(3n) .media-wrapper {
            background: linear-gradient(135deg, #E9F8F1 0%, var(--surface) 100%);
        }

        .item img,
        .item video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .item-text {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: var(--surface);
        }

        .item h2 {
            margin: 0 0 16px;
            font-size: 32px;
            font-weight: 700;
            color: var(--text-title);
            line-height: 1.2;
        }

        .item:nth-child(even) h2 {
            color: var(--text-title);
        }

        .item p {
            margin: 0;
            line-height: 1.8;
            color: var(--text-main);
            font-size: 16px;
            white-space: pre-line;
        }

        .accent-line {
            width: 60px;
            height: 4px;
            background: var(--green-primary);
            margin: 0 0 20px;
            border-radius: 2px;
        }

        .item:nth-child(even) .accent-line {
            background: var(--green-mid);
        }

        /* Mobile: layout apilado */
        @media (max-width: 768px) {
            .item {
                grid-template-columns: 1fr;
                gap: 0;
                min-height: auto;
            }

            .item:nth-child(odd),
            .item:nth-child(even) {
                direction: ltr;
            }

            .item:nth-child(even) > * {
                direction: ltr;
            }

            .media-wrapper {
                min-height: 300px;
            }

            .item-text {
                padding: 24px;
                order: 2;
            }

            .media-wrapper {
                order: 1;
            }

            .item h2 {
                font-size: 24px;
            }

            .item p {
                font-size: 14px;
                line-height: 1.6;
            }
        }

        .empty {
            background: #F1FBF7;
            border-radius: 0;
            padding: 60px 20px;
            text-align: center;
            color: var(--text-title);
            box-shadow: none;
            font-size: 18px;
            border: 1px solid #DFF2EA;
        }

        /* Video con controles mejorados */
        .item video {
            background: #000;
        }

        .item video::-webkit-media-controls-panel {
            background-color: rgba(23, 185, 143, 0.9);
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="header">
            <h1><?= htmlspecialchars((string)($modulo['titulo'] ?? 'Información de eventos')) ?></h1>
        </div>

        <?php if (!empty($items)): ?>
            <div class="stories-container">
                <?php foreach ($items as $index => $item): ?>
                    <article class="item">
                        <?php if (!empty($item['Imagen']) || !empty($item['Video'])): ?>
                            <div class="media-wrapper">
                                <?php if (!empty($item['Video'])): ?>
                                    <video controls preload="metadata" style="width:100%; height:100%; object-fit:cover;">
                                        <source src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode((string)$item['Video']) ?>">
                                        Tu navegador no soporta video HTML5.
                                    </video>
                                <?php elseif (!empty($item['Imagen'])): ?>
                                    <img src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode((string)$item['Imagen']) ?>" alt="Imagen de <?= htmlspecialchars((string)($item['Titulo'] ?? 'contenido')) ?>">
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="item-text">
                            <div class="accent-line"></div>
                            <h2><?= htmlspecialchars((string)($item['Titulo'] ?? '')) ?></h2>
                            <p><?= nl2br(htmlspecialchars((string)($item['Parrafo'] ?? ''))) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty">Aún no hay información publicada para este módulo.</div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lazy load para videos
            const videos = document.querySelectorAll('video');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const video = entry.target;
                        if (!video.src && video.querySelector('source')) {
                            video.load();
                        }
                    }
                });
            }, { threshold: 0.1 });

            videos.forEach(video => {
                observer.observe(video);
            });
        });
    </script>
</body>
</html>
