<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Eventos</h2>
    <?php $puedeCrearEvento = AuthController::esAdministrador() || AuthController::tienePermiso('eventos', 'crear'); ?>
    <?php $puedeEditarEvento = AuthController::esAdministrador() || AuthController::tienePermiso('eventos', 'editar'); ?>
    <?php $puedeEliminarEvento = AuthController::esAdministrador() || AuthController::tienePermiso('eventos', 'eliminar'); ?>
    <?php $puedeGestionarEvento = $puedeEditarEvento || $puedeEliminarEvento; ?>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=eventos/exportarExcel" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
        <?php if ($puedeCrearEvento): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=eventos/crear" class="btn btn-primary">+ Nuevo Evento</a>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="padding:16px; margin-bottom:16px;">
    <h3 style="margin-top:0;">QR para próximos eventos</h3>
    <p style="margin-bottom:8px;">Comparte este código QR para que las personas vean la información pública de los próximos eventos.</p>
    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:center;">
        <img src="<?= htmlspecialchars($qrUrl ?? '') ?>" alt="QR próximos eventos" style="width:180px; height:180px; border:1px solid #ddd; border-radius:8px;">
        <div>
            <a href="<?= htmlspecialchars($urlEventosPublicos ?? '#') ?>" target="_blank" class="btn btn-primary" style="margin-bottom:8px;">Abrir página pública</a>
            <a href="https://wa.me/?text=<?= urlencode('Próximos eventos: ' . ($urlEventosPublicos ?? '')) ?>" target="_blank" class="btn btn-success" style="margin-bottom:8px;">Enviar por WhatsApp</a>
            <div style="word-break:break-all;"><?= htmlspecialchars($urlEventosPublicos ?? '') ?></div>
        </div>
    </div>
</div>

<div class="card" style="padding:16px; margin-bottom:16px;">
    <h3 style="margin-top:0;">Mini-módulos de formación</h3>
    <p style="margin-bottom:10px;">Administra contenidos públicos específicos con su propio QR para compartir.</p>
    <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>index.php?url=eventos/universidad-vida" class="btn btn-primary">Universidad de la vida</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=eventos/capacitacion-destino" class="btn btn-primary">Capacitación destino</a>
    </div>
</div>

<div class="table-container">
    <style>
        .evento-media-img {
            width: 96px;
            height: 64px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #d9e2ef;
            display: block;
        }

        .evento-media-video {
            width: 140px;
            max-width: 100%;
            border-radius: 8px;
            border: 1px solid #d9e2ef;
            display: block;
        }
    </style>
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Lugar</th>
                <th>Descripción</th>
                <th>Imagen</th>
                <th>Video</th>
                <?php if ($puedeGestionarEvento): ?><th>Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($eventos)): ?>
                <?php foreach ($eventos as $evento): ?>
                    <tr>
                        <td><?= htmlspecialchars($evento['Nombre_Evento']) ?></td>
                        <td><?= htmlspecialchars($evento['Fecha_Evento']) ?></td>
                        <td><?= htmlspecialchars($evento['Hora_Evento']) ?></td>
                        <td><?= htmlspecialchars($evento['Lugar_Evento']) ?></td>
                        <td><?= htmlspecialchars($evento['Descripcion_Evento']) ?></td>
                        <td>
                            <?php if (!empty($evento['Imagen_Evento'])): ?>
                                <a href="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode($evento['Imagen_Evento']) ?>" target="_blank" rel="noopener noreferrer">
                                    <img
                                        src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode($evento['Imagen_Evento']) ?>"
                                        alt="Imagen de <?= htmlspecialchars($evento['Nombre_Evento']) ?>"
                                        class="evento-media-img"
                                        loading="lazy"
                                    >
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($evento['Video_Evento'])): ?>
                                <video class="evento-media-video" controls preload="metadata">
                                    <source src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode($evento['Video_Evento']) ?>">
                                    Tu navegador no soporta video.
                                </video>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <?php if ($puedeGestionarEvento): ?>
                        <td>
                            <?php if ($puedeEditarEvento): ?>
                            <a href="<?= PUBLIC_URL ?>index.php?url=eventos/editar&id=<?= $evento['Id_Evento'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <?php endif; ?>
                            <?php if ($puedeEliminarEvento): ?>
                            <a href="<?= PUBLIC_URL ?>index.php?url=eventos/eliminar&id=<?= $evento['Id_Evento'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este evento?')">Eliminar</a>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No hay eventos registrados</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
