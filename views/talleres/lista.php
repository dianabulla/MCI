<?php include VIEWS . '/layout/header.php'; ?>

<?php
$flashOk = $_SESSION['talleres_flash_ok'] ?? '';
$flashError = $_SESSION['talleres_flash_error'] ?? '';
unset($_SESSION['talleres_flash_ok'], $_SESSION['talleres_flash_error']);
$permisosTaller = is_array($permisos_taller ?? null) ? $permisos_taller : [];
$puedeCrear = !empty($permisosTaller['crear']);
$puedeEditar = !empty($permisosTaller['editar']);
$puedeEliminar = !empty($permisosTaller['eliminar']);
$puedeVerRespuestas = !empty($permisosTaller['ver_respuestas']);
$puedeGraficas = !empty($permisosTaller['ver_graficas']);
$puedeSoloGraficas = !empty($permisosTaller['solo_graficas']);
$puedeGestionarEnlace = !empty($permisosTaller['gestionar_enlace']);
?>

<div class="page-header">
    <h2>Talleres</h2>
    <p class="text-muted" style="margin:4px 0 0;">
        <?php if ($puedeSoloGraficas): ?>
            Consulte las gráficas estadísticas de cada formulario de taller.
        <?php else: ?>
            Cree formularios, comparta el enlace y vea las respuestas en una tabla.
        <?php endif; ?>
    </p>
    <?php if ($puedeCrear): ?>
    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=talleres/crear" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo formulario
        </a>
        <a href="<?= PUBLIC_URL ?>?url=talleres/crear-presentacion-ninos" class="btn btn-outline-primary">
            <i class="bi bi-people"></i> Presentación de niños
        </a>
        <a href="<?= PUBLIC_URL ?>?url=talleres/crear-tour-levantate" class="btn btn-outline-primary">
            <i class="bi bi-book"></i> Tour Levántate y Resplandece
        </a>
        <?php if ($puedeEditar): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres/corregir-personas-tour" class="btn btn-outline-warning">
            <i class="bi bi-wrench"></i> Corregir personas Tour
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social" class="btn btn-outline-success">
            <i class="bi bi-heart-pulse"></i> Servicio Social
        </a>
    </div>
</div>

<?php if ($flashOk !== ''): ?>
<div class="alert alert-success"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($flashError !== ''): ?>
<div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Enlace para compartir</th>
                <th>Preguntas</th>
                <th>Respuestas</th>
                <th>Estado</th>
                <th>Creado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($formularios)): ?>
                <?php foreach ($formularios as $f): ?>
                    <?php
                    $id = (int)($f['Id_Formulario'] ?? 0);
                    $slug = (string)($f['Slug'] ?? '');
                    $urlPublica = PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug);
                    $urlQr = PUBLIC_URL . '?url=talleres_publico/qr&slug=' . urlencode($slug);
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars((string)($f['Titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td>
                            <?php if ($puedeGestionarEnlace && !empty($f['Activo'])): ?>
                                <a href="<?= htmlspecialchars($urlPublica, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" title="Abrir formulario">
                                    Abrir enlace
                                </a>
                            <?php elseif (!empty($f['Activo'])): ?>
                                <span class="text-muted">Activo</span>
                            <?php else: ?>
                                <span class="text-muted">No publicado</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)($f['Total_Campos'] ?? 0) ?></td>
                        <td><?= (int)($f['Total_Respuestas'] ?? 0) ?></td>
                        <td>
                            <?php if (!empty($f['Activo'])): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(substr((string)($f['Fecha_Creacion'] ?? ''), 0, 16), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="white-space:nowrap;">
                            <?php if ($puedeVerRespuestas): ?>
                            <a href="<?= PUBLIC_URL ?>?url=talleres/respuestas&id=<?= $id ?>" class="btn btn-sm btn-outline-primary" title="Ver respuestas">
                                <i class="bi bi-table"></i>
                            </a>
                            <?php elseif ($puedeGraficas): ?>
                            <a href="<?= PUBLIC_URL ?>?url=talleres/respuestas&id=<?= $id ?>&tab=graficas" class="btn btn-sm btn-outline-primary" title="Ver gráficas">
                                <i class="bi bi-bar-chart-line"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($puedeEditar): ?>
                            <a href="<?= PUBLIC_URL ?>?url=talleres/editar&id=<?= $id ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($puedeGestionarEnlace && !empty($f['Activo'])): ?>
                            <a href="<?= htmlspecialchars($urlPublica, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-info" target="_blank" rel="noopener" title="Abrir formulario público">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            <a href="<?= htmlspecialchars($urlQr, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-success" target="_blank" rel="noopener" title="Ver e imprimir QR">
                                <i class="bi bi-qr-code"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($puedeEliminar): ?>
                            <a href="<?= PUBLIC_URL ?>?url=talleres/eliminar&id=<?= $id ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Eliminar este formulario y todas sus respuestas?');"
                               title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No hay formularios creados. Use «Nuevo formulario» para empezar.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
