<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transmisiones en Vivo - MCI Madrid Colombia</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #eef3fb;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: #ffffff;
            padding: 30px;
            border-radius: 18px;
            border: 1px solid #dce5f7;
            box-shadow: 0 8px 18px rgba(21, 46, 94, 0.12);
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            color: #3a61ab;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        .header p {
            color: #4f5f82;
            font-size: 1.1em;
        }

        /* Transmisión en Vivo */
        .transmision-en-vivo {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 8px 18px rgba(22, 49, 99, 0.12);
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid #dce6fb;
            border-top: 4px solid #4b73bb;
        }

        .transmision-en-vivo-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #d7e4fb;
        }

        .indicator-en-vivo {
            width: 20px;
            height: 20px;
            background: #ff5b65;
            border-radius: 50%;
            animation: pulse 1s infinite;
            box-shadow: 0 0 0 6px rgba(255, 91, 101, 0.2);
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }

        .transmision-en-vivo-header h2 {
            color: #c73b54;
            font-size: 1.8em;
            margin: 0;
        }

        .video-container {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        .transmision-info {
            background: #f4f8ff;
            padding: 20px;
            border-radius: 14px;
            margin-top: 20px;
            border: 1px solid #dce7fa;
        }

        .transmision-info h3 {
            color: #2f4674;
            margin-bottom: 10px;
        }

        .transmision-info p {
            color: #58688b;
            line-height: 1.6;
            margin: 8px 0;
        }

        .transmision-descripcion {
            margin-top: 15px;
            line-height: 1.8;
        }

        .transmision-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        /* Transmisiones Próximas */
        .seccion {
            margin-bottom: 40px;
            background: #ffffff;
            border: 1px solid #dce6fb;
            border-top: 4px solid #4b73bb;
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 8px 18px rgba(21, 46, 94, 0.1);
        }

        .seccion-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            color: #2f4f87;
            font-size: 1.5em;
            font-weight: 600;
            text-shadow: none;
        }

        .seccion-header i {
            font-size: 1.8em;
            color: #3f66b1;
        }

        .transmisiones-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .transmision-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #dbe6f8;
            box-shadow: 0 8px 20px rgba(20, 47, 97, 0.16);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .transmision-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 28px rgba(20, 47, 97, 0.25);
        }

        .transmision-card-header {
            padding: 20px;
            background: linear-gradient(135deg, #3f66b1 0%, #5a5fc4 100%);
            color: white;
        }

        .transmision-card-header h3 {
            margin: 0;
            font-size: 1.2em;
            word-break: break-word;
        }

        .transmision-card-body {
            padding: 20px;
        }

        .transmision-card-body p {
            margin: 10px 0;
            color: #5a6b8f;
            font-size: 0.95em;
        }

        .transmision-fecha {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #3f66b1;
            font-weight: 600;
        }

        .transmision-hora {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #5a6b8f;
        }

        .btn-ver {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #3f66b1 0%, #5a5fc4 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 15px;
            border: none;
            cursor: pointer;
        }

        .btn-ver:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(63, 102, 177, 0.35);
        }

        .btn-share {
            background: linear-gradient(135deg, #2fa56f 0%, #1f7f54 100%);
            flex: 1;
            text-align: center;
            justify-content: center;
        }

        .btn-share:hover {
            box-shadow: 0 8px 18px rgba(47, 165, 111, 0.35);
        }

        .btn-full {
            width: 100%;
            text-align: center;
            justify-content: center;
        }

        .empty-message {
            background: #ffffff;
            padding: 40px;
            border-radius: 14px;
            border: 1px solid #dbe6f8;
            text-align: center;
            color: #56688d;
        }

        .empty-message i {
            font-size: 3em;
            color: #b7c6e6;
            display: block;
            margin-bottom: 20px;
        }

        .empty-note {
            margin-top: 20px;
            font-size: 0.9em;
            color: #7a88a8;
        }

        .estado-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .badge-proximamente {
            background: #fff0d2;
            color: #8a5a11;
        }

        .badge-finalizada {
            background: #dcf5e7;
            color: #1e6a47;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8em;
            }

            .transmisiones-grid {
                grid-template-columns: 1fr;
            }

            .transmision-en-vivo {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📺 Transmisiones en Vivo</h1>
            <p>Iglesia MCI Madrid - Colombia</p>
        </div>

        <!-- Transmisión en Vivo -->
        <?php if ($transmisionEnVivo): ?>
        <div class="transmision-en-vivo">
            <div class="transmision-en-vivo-header">
                <div class="indicator-en-vivo"></div>
                <h2>EN VIVO AHORA</h2>
            </div>

            <div class="video-container">
                <?php
                // Extraer ID del video de YouTube
                $urlYouTube = $transmisionEnVivo['URL_YouTube'];
                $videoId = '';

                if (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $urlYouTube, $matches)) {
                    $videoId = $matches[1];
                } elseif (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $urlYouTube, $matches)) {
                    $videoId = $matches[1];
                } elseif (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $urlYouTube, $matches)) {
                    $videoId = $matches[1];
                }

                if ($videoId):
                ?>
                <iframe src="https://www.youtube.com/embed/<?= $videoId ?>" allowfullscreen="" loading="lazy"></iframe>
                <?php endif; ?>
            </div>

            <div class="transmision-info">
                <h3><?= htmlspecialchars($transmisionEnVivo['Nombre']) ?></h3>
                <p class="transmision-fecha">
                    <i class="bi bi-calendar-event"></i>
                    <?= date('d \d\e F \d\e Y', strtotime($transmisionEnVivo['Fecha_Transmision'])) ?>
                </p>
                <?php if ($transmisionEnVivo['Hora_Transmision']): ?>
                <p class="transmision-hora">
                    <i class="bi bi-clock"></i>
                    <?= date('H:i', strtotime($transmisionEnVivo['Hora_Transmision'])) ?>
                </p>
                <?php endif; ?>
                <?php if ($transmisionEnVivo['Descripcion']): ?>
                <p class="transmision-descripcion">
                    <?= nl2br(htmlspecialchars($transmisionEnVivo['Descripcion'])) ?>
                </p>
                <?php endif; ?>
                <div class="transmision-actions">
                    <a href="<?= htmlspecialchars($transmisionEnVivo['URL_YouTube']) ?>" target="_blank" class="btn-ver">
                        <i class="bi bi-youtube"></i> Ver en YouTube
                    </a>
                    <button onclick="compartirLink()" class="btn-ver btn-share">
                        <i class="bi bi-share"></i> Compartir
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Transmisiones Próximas -->
        <?php if (!empty($transmisionesProximas)): ?>
        <div class="seccion">
            <div class="seccion-header">
                <i class="bi bi-hourglass-split"></i>
                Próximas Transmisiones
            </div>

            <div class="transmisiones-grid">
                <?php foreach ($transmisionesProximas as $trans): ?>
                <div class="transmision-card">
                    <div class="transmision-card-header">
                        <h3><?= htmlspecialchars($trans['Nombre']) ?></h3>
                    </div>
                    <div class="transmision-card-body">
                        <span class="estado-badge badge-proximamente">⏱️ Próximamente</span>
                        <p class="transmision-fecha">
                            <i class="bi bi-calendar-event"></i>
                            <?= date('d/m/Y', strtotime($trans['Fecha_Transmision'])) ?>
                        </p>
                        <?php if ($trans['Hora_Transmision']): ?>
                        <p class="transmision-hora">
                            <i class="bi bi-clock"></i>
                            <?= date('H:i', strtotime($trans['Hora_Transmision'])) ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($trans['Descripcion']): ?>
                        <p><?= substr(htmlspecialchars($trans['Descripcion']), 0, 100) ?>...</p>
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars($trans['URL_YouTube']) ?>" target="_blank" class="btn-ver btn-full">
                            <i class="bi bi-youtube"></i> Ver en YouTube
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Transmisiones Finalizadas -->
        <?php if (!empty($transmisionesFinalizadas)): ?>
        <div class="seccion">
            <div class="seccion-header">
                <i class="bi bi-check-circle"></i>
                Transmisiones Finalizadas
            </div>

            <div class="transmisiones-grid">
                <?php foreach ($transmisionesFinalizadas as $trans): ?>
                <div class="transmision-card">
                    <div class="transmision-card-header">
                        <h3><?= htmlspecialchars($trans['Nombre']) ?></h3>
                    </div>
                    <div class="transmision-card-body">
                        <span class="estado-badge badge-finalizada">✓ Finalizada</span>
                        <p class="transmision-fecha">
                            <i class="bi bi-calendar-event"></i>
                            <?= date('d/m/Y', strtotime($trans['Fecha_Transmision'])) ?>
                        </p>
                        <?php if ($trans['Hora_Transmision']): ?>
                        <p class="transmision-hora">
                            <i class="bi bi-clock"></i>
                            <?= date('H:i', strtotime($trans['Hora_Transmision'])) ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($trans['Descripcion']): ?>
                        <p><?= substr(htmlspecialchars($trans['Descripcion']), 0, 100) ?>...</p>
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars($trans['URL_YouTube']) ?>" target="_blank" class="btn-ver btn-full">
                            <i class="bi bi-youtube"></i> Ver Grabación
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Mensaje vacío si no hay transmisiones -->
        <?php if (empty($transmisionEnVivo) && empty($transmisionesProximas) && empty($transmisionesFinalizadas)): ?>
        <div class="empty-message">
            <i class="bi bi-calendar-x"></i>
            <h2>Sin Transmisiones</h2>
            <p>No hay transmisiones disponibles en este momento.</p>
            <p class="empty-note">Por favor, intenta más tarde.</p>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function compartirLink() {
            // Obtener la URL actual
            const urlActual = window.location.href;
            
            // Copiar al portapapeles
            navigator.clipboard.writeText(urlActual).then(() => {
                alert('✅ Link copiado al portapapeles:\n\n' + urlActual + '\n\nPuedes compartirlo con otros para que vean la transmisión en vivo');
            }).catch(err => {
                // Fallback si no funciona clipboard API
                const textarea = document.createElement('textarea');
                textarea.value = urlActual;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('✅ Link copiado al portapapeles:\n\n' + urlActual + '\n\nPuedes compartirlo con otros para que vean la transmisión en vivo');
            });
        }
    </script>
</body>
</html>
