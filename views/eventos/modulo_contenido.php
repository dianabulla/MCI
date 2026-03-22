<?php include VIEWS . '/layout/header.php'; ?>

<?php
$modulo = $modulo ?? [];
$items = $items ?? [];
$itemEditar = $itemEditar ?? null;
$puedeCrear = AuthController::esAdministrador() || AuthController::tienePermiso('eventos', 'crear');
$puedeEditar = AuthController::esAdministrador() || AuthController::tienePermiso('eventos', 'editar');
$puedeEliminar = AuthController::esAdministrador() || AuthController::tienePermiso('eventos', 'eliminar');

$estadoActivoEditar = !isset($itemEditar['Estado_Activo']) || (int)$itemEditar['Estado_Activo'] === 1;
$fechaPublicacionDesde = htmlspecialchars((string)($itemEditar['Fecha_Publicacion_Desde'] ?? ''));
$fechaPublicacionHasta = htmlspecialchars((string)($itemEditar['Fecha_Publicacion_Hasta'] ?? ''));
?>

<div class="page-header">
    <h2><?= htmlspecialchars((string)($modulo['titulo'] ?? 'Mini módulo')) ?></h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>index.php?url=eventos" class="btn btn-secondary">Volver a eventos</a>
    </div>
</div>

<div class="card" style="padding:14px; margin-bottom:14px;">
    <div style="display:flex; flex-wrap:wrap; gap:8px;">
        <a href="<?= PUBLIC_URL ?>index.php?url=eventos/universidad-vida" class="btn <?= (($modulo['tipo'] ?? '') === 'universidad_vida') ? 'btn-primary' : 'btn-secondary' ?>">Universidad de la vida</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=eventos/capacitacion-destino" class="btn <?= (($modulo['tipo'] ?? '') === 'capacitacion_destino') ? 'btn-primary' : 'btn-secondary' ?>">Capacitación destino</a>
    </div>
</div>

<div class="card" style="padding:14px; margin-bottom:14px;">
    <h3 style="margin-top:0;">QR público del módulo</h3>
    <p style="margin-bottom:8px;">Comparte este QR para que el público vea la información de este módulo.</p>
    <div style="display:flex; flex-wrap:wrap; gap:16px; align-items:center;">
        <img src="<?= htmlspecialchars((string)($qrUrl ?? '')) ?>" alt="QR módulo" style="width:180px; height:180px; border:1px solid #ddd; border-radius:8px;">
        <div>
            <a href="<?= htmlspecialchars((string)($urlPublica ?? '#')) ?>" target="_blank" class="btn btn-primary" style="margin-bottom:8px;">Abrir página pública</a>
            <a href="https://wa.me/?text=<?= urlencode('Información: ' . ((string)($urlPublica ?? ''))) ?>" target="_blank" class="btn btn-success" style="margin-bottom:8px;">Enviar por WhatsApp</a>
            <div style="word-break:break-all;"><?= htmlspecialchars((string)($urlPublica ?? '')) ?></div>
        </div>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger" style="margin-bottom:14px;">
        <?= htmlspecialchars((string)$error) ?>
    </div>
<?php endif; ?>

<?php if ($puedeCrear || ($puedeEditar && !empty($itemEditar))): ?>
<div class="card" style="padding:14px; margin-bottom:14px;">
    <h3 style="margin-top:0;"><?= !empty($itemEditar) ? 'Editar contenido' : 'Nuevo contenido' ?></h3>
    <form method="POST" action="<?= PUBLIC_URL ?>index.php?url=eventos/modulo/guardar" enctype="multipart/form-data">
        <input type="hidden" name="tipo_modulo" value="<?= htmlspecialchars((string)($modulo['tipo'] ?? '')) ?>">
        <input type="hidden" name="id_contenido" value="<?= (int)($itemEditar['Id_Contenido'] ?? 0) ?>">

        <div class="form-group">
            <label for="titulo">Título</label>
            <input type="text" id="titulo" name="titulo" class="form-control" value="<?= htmlspecialchars((string)($itemEditar['Titulo'] ?? '')) ?>" required>
        </div>

        <div class="form-group">
            <label for="parrafo">Párrafo</label>
            <textarea id="parrafo" name="parrafo" class="form-control" rows="5" required><?= htmlspecialchars((string)($itemEditar['Parrafo'] ?? '')) ?></textarea>
        </div>

        <div class="form-group">
            <label for="orden">Orden</label>
            <input type="number" min="0" step="1" id="orden" name="orden" class="form-control" value="<?= (int)($itemEditar['Orden'] ?? 0) ?>">
        </div>

        <div class="form-group">
            <label style="display:flex; align-items:center; gap:8px; margin-bottom:0;">
                <input type="checkbox" name="estado_activo" value="1" <?= $estadoActivoEditar ? 'checked' : '' ?>>
                Publicar este contenido (activo)
            </label>
        </div>

        <div class="form-group">
            <label for="fecha_publicacion_desde">Publicar desde (opcional)</label>
            <input type="date" id="fecha_publicacion_desde" name="fecha_publicacion_desde" class="form-control" value="<?= $fechaPublicacionDesde ?>">
        </div>

        <div class="form-group">
            <label for="fecha_publicacion_hasta">Publicar hasta (opcional)</label>
            <input type="date" id="fecha_publicacion_hasta" name="fecha_publicacion_hasta" class="form-control" value="<?= $fechaPublicacionHasta ?>">
        </div>

        <div class="form-group">
            <label for="imagen_modulo">Imagen (opcional)</label>
            <input type="file" id="imagen_modulo" name="imagen_modulo" class="form-control" accept="image/*">
            <small style="display:block; margin-top:6px; color:#666;">Máximo recomendado: 50MB.</small>
            <?php if (!empty($itemEditar['Imagen'])): ?>
                <div style="margin-top:8px;">
                    <img src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode((string)$itemEditar['Imagen']) ?>" alt="Imagen" style="max-width:220px; border-radius:8px;">
                </div>
                <label style="display:flex; align-items:center; gap:8px; margin-top:8px;">
                    <input type="checkbox" name="eliminar_imagen" value="1"> Eliminar imagen actual
                </label>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="video_modulo">Video (opcional)</label>
            <input type="file" id="video_modulo" name="video_modulo" class="form-control" accept="video/mp4,video/webm,video/quicktime,video/x-m4v">
            <small style="display:block; margin-top:6px; color:#666;">Máximo recomendado: 500MB.</small>
            <?php if (!empty($itemEditar['Video'])): ?>
                <div style="margin-top:8px;">
                    <video controls style="max-width:320px; border-radius:8px;">
                        <source src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode((string)$itemEditar['Video']) ?>">
                        Tu navegador no soporta video.
                    </video>
                </div>
                <label style="display:flex; align-items:center; gap:8px; margin-top:8px;">
                    <input type="checkbox" name="eliminar_video" value="1"> Eliminar video actual
                </label>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <?php if (!empty($itemEditar)): ?>
                <a href="<?= PUBLIC_URL ?>index.php?url=<?= htmlspecialchars((string)($modulo['route_privada'] ?? 'eventos')) ?>" class="btn btn-secondary">Cancelar edición</a>
            <?php endif; ?>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Título</th>
                <th>Párrafo</th>
                <th>Imagen</th>
                <th>Video</th>
                <th>Orden</th>
                <th>Estado</th>
                <th>Publicación</th>
                <th>Fecha</th>
                <?php if ($puedeCrear || $puedeEditar || $puedeEliminar): ?><th>Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $i => $item): ?>
                    <tr>
                        <td><?= (int)$i + 1 ?></td>
                        <td><?= htmlspecialchars((string)($item['Titulo'] ?? '')) ?></td>
                        <td><?= nl2br(htmlspecialchars((string)($item['Parrafo'] ?? ''))) ?></td>
                        <td>
                            <?php if (!empty($item['Imagen'])): ?>
                                <img src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode((string)$item['Imagen']) ?>" alt="Imagen" style="width:90px; height:60px; object-fit:cover; border-radius:8px; border:1px solid #d9e2ef;">
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($item['Video'])): ?>
                                <video controls preload="metadata" style="width:120px; border-radius:8px; border:1px solid #d9e2ef;">
                                    <source src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode((string)$item['Video']) ?>">
                                </video>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= (int)($item['Orden'] ?? 0) ?></td>
                        <td>
                            <?php if ((int)($item['Estado_Activo'] ?? 1) === 1): ?>
                                <span class="meta-pill" style="background:#e8f8ee; color:#1d7a45;">Activo</span>
                            <?php else: ?>
                                <span class="meta-pill" style="background:#ffe9e9; color:#9a1f1f;">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($item['Fecha_Publicacion_Desde']) || !empty($item['Fecha_Publicacion_Hasta'])): ?>
                                <?= htmlspecialchars((string)($item['Fecha_Publicacion_Desde'] ?? '')) ?>
                                <?= !empty($item['Fecha_Publicacion_Hasta']) ? ' a ' . htmlspecialchars((string)$item['Fecha_Publicacion_Hasta']) : '' ?>
                            <?php else: ?>
                                Siempre
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars((string)($item['Fecha_Creacion'] ?? '')) ?></td>
                        <?php if ($puedeCrear || $puedeEditar || $puedeEliminar): ?>
                        <td>
                            <?php if ($puedeCrear): ?>
                                <a href="<?= PUBLIC_URL ?>index.php?url=eventos/modulo/duplicar&tipo=<?= htmlspecialchars((string)($modulo['tipo'] ?? '')) ?>&id=<?= (int)($item['Id_Contenido'] ?? 0) ?>" class="btn btn-sm btn-secondary">Duplicar</a>
                            <?php endif; ?>
                            <?php if ($puedeEditar): ?>
                                <a href="<?= PUBLIC_URL ?>index.php?url=<?= htmlspecialchars((string)($modulo['route_privada'] ?? 'eventos')) ?>&editar=<?= (int)($item['Id_Contenido'] ?? 0) ?>" class="btn btn-sm btn-warning">Editar</a>
                            <?php endif; ?>
                            <?php if ($puedeEliminar): ?>
                                <a href="<?= PUBLIC_URL ?>index.php?url=eventos/modulo/eliminar&tipo=<?= htmlspecialchars((string)($modulo['tipo'] ?? '')) ?>&id=<?= (int)($item['Id_Contenido'] ?? 0) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este contenido?')">Eliminar</a>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= ($puedeCrear || $puedeEditar || $puedeEliminar) ? 10 : 9 ?>" class="text-center">Aún no hay contenido en este módulo.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
