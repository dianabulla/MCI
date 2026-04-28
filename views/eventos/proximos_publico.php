<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Próximos Eventos</title>

    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

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
            font-weight: bold;
            text-transform: uppercase;
        }

        .meta {
            margin: 0 0 8px;
            color: #374151;
            line-height: 1.45;
            font-size: 14px;
        }

        .descripcion {
            margin: 10px 0 0;
            line-height: 1.5;
            white-space: pre-line;
            font-size: 14px;
        }

        .media {
            margin-top: 12px;
            display: grid;
            gap: 10px;
        }

        .media img,
        .media video {
            width: 100%;
            max-width: 100%;
            max-height: 520px;
            object-fit: contain;
            background: #0f172a;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            display: block;
        }

        .acciones {
            margin-top: 14px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            appearance: none;
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-share {
            background: #16a34a;
            color: #fff;
        }

        .btn-download {
            background: #2563eb;
            color: #fff;
        }

        .btn-video {
            background: #7c3aed;
            color: #fff;
        }

        .btn-copy {
            background: #e5e7eb;
            color: #111827;
        }

        .empty {
            background: #fff;
            border: 1px dashed #d1d5db;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            color: #6b7280;
        }

        /* Tarjeta usada para convertir a imagen */
        .share-card {
            width: 1080px;
            max-width: 1080px;
            background: linear-gradient(180deg, #ffffff 0%, #f7fafc 100%);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
            border: 1px solid #dbe4ee;
        }

        .share-card-header {
            padding: 28px 28px 18px;
            background: linear-gradient(135deg, #eef7ff 0%, #f9fbff 100%);
            border-bottom: 1px solid #e5e7eb;
        }

        .share-card-badge {
            display: inline-block;
            font-size: 13px;
            font-weight: bold;
            color: #1d4ed8;
            background: #dbeafe;
            padding: 6px 10px;
            border-radius: 999px;
            margin-bottom: 12px;
            letter-spacing: 0.3px;
        }

        .share-card-title {
            margin: 0 0 14px;
            font-size: 34px;
            line-height: 1.15;
            color: #111827;
            text-transform: uppercase;
        }

        .share-card-meta {
            font-size: 18px;
            color: #374151;
            line-height: 1.7;
        }

        .share-card-meta strong {
            color: #111827;
        }

        .share-card-body {
            padding: 22px 28px 28px;
        }

        .share-card-description {
            margin: 0 0 18px;
            font-size: 22px;
            line-height: 1.6;
            color: #1f2937;
            white-space: pre-line;
        }

        .share-card-image-wrap {
            margin-top: 14px;
            border-radius: 20px;
            overflow: hidden;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            aspect-ratio: 16 / 9;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .share-card-image {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: contain;
        }

        .share-card-footer {
            padding: 16px 28px 24px;
            font-size: 16px;
            color: #6b7280;
        }

        /* Contenedor oculto para render */
        .render-zone {
            position: fixed;
            left: -99999px;
            top: 0;
            width: 1120px;
            padding: 20px;
            background: #ffffff;
            z-index: -1;
        }

        @media (max-width: 640px) {
            .acciones {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
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
            <?php foreach ($eventos as $index => $evento): ?>
                <?php
                    $eventoId = (int)($evento['Id_Evento'] ?? ($index + 1));
                    $nombreEvento = (string)($evento['Nombre_Evento'] ?? '');
                    $fechaEvento = (string)($evento['Fecha_Evento'] ?? '');
                    $horaEvento = (string)($evento['Hora_Evento'] ?? '');
                    $lugarEvento = (string)($evento['Lugar_Evento'] ?? '');
                    $descripcionEvento = (string)($evento['Descripcion_Evento'] ?? '');
                    $imagenEvento = (string)($evento['Imagen_Evento'] ?? '');
                    $videoEvento = (string)($evento['Video_Evento'] ?? '');
                    $imagenSrc = $imagenEvento !== ''
                        ? rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode($imagenEvento)
                        : '';
                    $videoSrc = $videoEvento !== ''
                        ? rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode($videoEvento)
                        : '';
                ?>

                <article class="evento">
                    <h2 class="titulo"><?= htmlspecialchars($nombreEvento) ?></h2>

                    <p class="meta">
                        <strong>Fecha:</strong> <?= htmlspecialchars($fechaEvento) ?><br>
                        <strong>Hora:</strong> <?= htmlspecialchars($horaEvento) ?><br>
                        <strong>Lugar:</strong> <?= htmlspecialchars($lugarEvento) ?>
                    </p>

                    <p class="descripcion"><?= nl2br(htmlspecialchars($descripcionEvento)) ?></p>

                    <?php if ($imagenEvento !== '' || $videoEvento !== ''): ?>
                        <div class="media">
                            <?php if ($imagenEvento !== ''): ?>
                                <img
                                    src="<?= $imagenSrc ?>"
                                    alt="Imagen del evento"
                                    loading="lazy"
                                    crossorigin="anonymous"
                                >
                            <?php endif; ?>

                            <?php if ($videoEvento !== ''): ?>
                                <video controls preload="metadata">
                                    <source src="<?= $videoSrc ?>">
                                    Tu navegador no soporta video HTML5.
                                </video>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="acciones">
                        <?php if ($imagenEvento !== ''): ?>
                            <a class="btn btn-download" href="<?= $imagenSrc ?>" download="evento-<?= $eventoId ?>-imagen" target="_blank" rel="noopener">
                                Descargar imagen
                            </a>

                            <button
                                type="button"
                                class="btn btn-share"
                                onclick="compartirArchivo('<?= htmlspecialchars($imagenSrc, ENT_QUOTES, 'UTF-8') ?>', 'evento-<?= $eventoId ?>-imagen.jpg', 'image/jpeg', 'imagen')">
                                Compartir imagen
                            </button>
                        <?php endif; ?>

                        <?php if ($videoEvento !== ''): ?>
                            <a class="btn btn-download" href="<?= $videoSrc ?>" download="evento-<?= $eventoId ?>-video" target="_blank" rel="noopener">
                                Descargar video
                            </a>

                            <button
                                type="button"
                                class="btn btn-video"
                                onclick="compartirArchivo('<?= htmlspecialchars($videoSrc, ENT_QUOTES, 'UTF-8') ?>', 'evento-<?= $eventoId ?>-video.mp4', 'video/mp4', 'video')">
                                Compartir video
                            </button>
                        <?php endif; ?>

                        <button type="button" class="btn btn-share" onclick="compartirImagenEvento(<?= $eventoId ?>)">
                            Compartir tarjeta
                        </button>

                        <button type="button" class="btn btn-copy" onclick="descargarImagenEvento(<?= $eventoId ?>)">
                            Descargar tarjeta
                        </button>
                    </div>
                </article>

                <!-- Tarjeta oculta para convertir a imagen -->
                <div class="render-zone">
                    <div class="share-card" id="share-card-<?= $eventoId ?>">
                        <div class="share-card-header">
                            <div class="share-card-badge">Próximo evento</div>
                            <h2 class="share-card-title"><?= htmlspecialchars($nombreEvento) ?></h2>
                            <div class="share-card-meta">
                                <strong>Fecha:</strong> <?= htmlspecialchars($fechaEvento) ?><br>
                                <strong>Hora:</strong> <?= htmlspecialchars($horaEvento) ?><br>
                                <strong>Lugar:</strong> <?= htmlspecialchars($lugarEvento) ?>
                            </div>
                        </div>

                        <div class="share-card-body">
                            <p class="share-card-description"><?= nl2br(htmlspecialchars($descripcionEvento)) ?></p>

                            <?php if ($imagenEvento !== ''): ?>
                                <div class="share-card-image-wrap">
                                    <img
                                        class="share-card-image"
                                        src="<?= $imagenSrc ?>"
                                        alt="Imagen del evento"
                                        crossorigin="anonymous"
                                    >
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="share-card-footer">
                            Comparte este evento
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        async function generarBlobEvento(eventoId) {
            const card = document.getElementById('share-card-' + eventoId);
            if (!card) {
                throw new Error('No se encontró la tarjeta del evento.');
            }

            const imagenes = card.querySelectorAll('img');
            await Promise.all(Array.from(imagenes).map(esperarImagen));

            const canvas = await html2canvas(card, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff'
            });

            return await new Promise((resolve, reject) => {
                canvas.toBlob(function(blob) {
                    if (blob) {
                        resolve(blob);
                    } else {
                        reject(new Error('No se pudo generar la imagen.'));
                    }
                }, 'image/png');
            });
        }

        function esperarImagen(img) {
            return new Promise((resolve) => {
                if (img.complete) {
                    resolve();
                    return;
                }

                img.onload = () => resolve();
                img.onerror = () => resolve();
            });
        }

        async function descargarImagenEvento(eventoId) {
            try {
                const blob = await generarBlobEvento(eventoId);
                const url = URL.createObjectURL(blob);
                const enlace = document.createElement('a');
                enlace.href = url;
                enlace.download = 'evento-' + eventoId + '.png';
                document.body.appendChild(enlace);
                enlace.click();
                document.body.removeChild(enlace);
                URL.revokeObjectURL(url);
            } catch (error) {
                alert('No se pudo descargar la imagen del evento.');
                console.error(error);
            }
        }

        async function compartirImagenEvento(eventoId) {
            try {
                const blob = await generarBlobEvento(eventoId);
                const archivo = new File([blob], 'evento-' + eventoId + '.png', { type: 'image/png' });

                if (navigator.canShare && navigator.canShare({ files: [archivo] }) && navigator.share) {
                    await navigator.share({
                        files: [archivo],
                        title: 'Evento',
                        text: 'Te comparto este evento'
                    });
                    return;
                }

                await descargarImagenEvento(eventoId);
                alert('Tu navegador no permite compartir la imagen directamente. Se descargó para que la envíes.');
            } catch (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }

                alert('No se pudo compartir la imagen del evento.');
                console.error(error);
            }
        }

        async function compartirArchivo(url, nombreArchivo, mimeType, tipo) {
            try {
                const response = await fetch(url, { credentials: 'same-origin' });
                if (!response.ok) {
                    throw new Error('No se pudo descargar el archivo para compartir.');
                }

                const blob = await response.blob();
                const typeFinal = blob.type || mimeType || 'application/octet-stream';
                const archivo = new File([blob], nombreArchivo, { type: typeFinal });

                if (navigator.canShare && navigator.canShare({ files: [archivo] }) && navigator.share) {
                    await navigator.share({
                        files: [archivo],
                        title: 'Evento',
                        text: 'Te comparto este ' + (tipo || 'archivo')
                    });
                    return;
                }

                await descargarArchivo(blob, nombreArchivo);
                alert('Tu navegador no permite compartir ' + (tipo || 'el archivo') + ' directamente. Se descargó para que lo envíes.');
            } catch (error) {
                if (error && error.name === 'AbortError') {
                    return;
                }
                alert('No se pudo compartir ' + (tipo || 'el archivo') + '.');
                console.error(error);
            }
        }

        async function descargarArchivo(blob, nombreArchivo) {
            const url = URL.createObjectURL(blob);
            const enlace = document.createElement('a');
            enlace.href = url;
            enlace.download = nombreArchivo;
            document.body.appendChild(enlace);
            enlace.click();
            document.body.removeChild(enlace);
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>

