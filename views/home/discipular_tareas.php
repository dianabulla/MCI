<?php include VIEWS . '/layout/header.php'; ?>

<?php
$mensajeFlash = (string)($mensaje ?? '');
$tipoFlash = (string)($tipo ?? '');
$accesosDirectosDiscipulo = (array)($accesos_directos_discipulo ?? []);
$tareasPorModuloDiscipulo = (array)($tareas_por_modulo_discipulo ?? []);
$filtroNivel = (int)($filtro_nivel ?? 0);
$filtroModulo = (int)($filtro_modulo ?? 0);
$totalTarjetasTareas = count($accesosDirectosDiscipulo);
?>

<style>
    .disc-task-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(320px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .disc-task-grid.is-single {
        grid-template-columns: 1fr;
    }

    .disc-task-card {
        border: 1px solid #dbe3f0;
        border-radius: 10px;
        padding: 10px;
        background: #fff;
    }

    .disc-task-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 8px;
    }

    .disc-task-item {
        border: 1px solid #e5ebf7;
        border-radius: 8px;
        padding: 8px;
        background: #fff;
    }

    .disc-entrega-wrap {
        margin-top: 8px;
        padding: 8px;
        border: 1px solid #e5ebf7;
        border-radius: 8px;
        background: #f8fbff;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .disc-entrega-card {
        border: 1px solid #dbe3f0;
        border-radius: 8px;
        padding: 8px;
        background: #fff;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .disc-entrega-row {
        display: grid;
        grid-template-columns: minmax(220px, 1.8fr) minmax(150px, 1fr) minmax(180px, 1.2fr) minmax(240px, 1.6fr) auto;
        gap: 8px;
        align-items: stretch;
    }

    .disc-entrega-cell {
        border: 1px solid #e5ebf7;
        border-radius: 6px;
        padding: 6px 8px;
        background: #fff;
        min-width: 0;
    }

    .disc-entrega-label {
        display: block;
        font-size: 11px;
        color: #637087;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .disc-entrega-value {
        font-size: 12px;
        color: #1f4f93;
        word-break: break-word;
    }

    .disc-entrega-archivo-link {
        font-weight: 700;
        color: #1f4f93;
        text-decoration: underline;
    }

    .disc-entrega-calif-ok {
        color: #14532d;
    }

    .disc-entrega-calif-pend {
        color: #8a6d1d;
    }

    .disc-entrega-actions {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .disc-entrega-edit {
        margin: 0;
    }

    .disc-entrega-edit > summary {
        list-style: none;
        cursor: pointer;
        color: #1f4f93;
        font-weight: 600;
        border: 1px solid #c8d7ee;
        border-radius: 18px;
        padding: 3px 10px;
        background: #eef4ff;
        font-size: 12px;
    }

    .disc-entrega-edit > summary::-webkit-details-marker {
        display: none;
    }

    .disc-entrega-edit-form {
        margin-top: 8px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(220px, 1fr) auto;
        gap: 6px;
        align-items: end;
    }

    .disc-entrega-nueva {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(260px, 1fr) auto;
        gap: 6px;
        align-items: end;
        margin-top: 8px;
    }

    .disc-entrega-actions-cell {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    @media (max-width: 900px) {
        .disc-task-grid {
            grid-template-columns: 1fr;
        }

        .disc-entrega-row {
            grid-template-columns: 1fr;
        }

        .disc-entrega-edit-form,
        .disc-entrega-nueva {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;">Discipular - Tareas</h2>
        <small style="color:#637087;">Vista separada para entregar tareas de tus módulos inscritos.</small>
    </div>
    <div class="header-actions" style="display:flex;gap:8px;flex-wrap:wrap;">
        <a class="btn btn-secondary btn-sm" href="<?= PUBLIC_URL ?>?url=programas/evaluaciones">
            <i class="bi bi-arrow-left-short"></i> Volver a Evaluaciones
        </a>
        <a class="btn btn-secondary btn-sm" href="<?= PUBLIC_URL ?>?url=programas">
            <i class="bi bi-arrow-left-short"></i> Volver a Discipular
        </a>
    </div>
</div>

<?php if ($mensajeFlash !== ''): ?>
    <div class="alert alert-<?= $tipoFlash === 'success' ? 'success' : 'danger' ?>" style="margin:12px 0;">
        <?= htmlspecialchars($mensajeFlash) ?>
    </div>
<?php endif; ?>

<div class="card report-card" style="padding:14px; margin-bottom:14px;">
    <h3 style="margin:0 0 4px 0;">Módulos con tareas</h3>
    <small style="color:#637087;">Selecciona y entrega tus tareas en una vista independiente.</small>

    <?php if (!empty($accesosDirectosDiscipulo)): ?>
        <div class="disc-task-grid <?= $totalTarjetasTareas <= 1 ? 'is-single' : '' ?>">
            <?php foreach ($accesosDirectosDiscipulo as $accesoDirecto): ?>
                <?php
                    $nivelAcceso = (int)($accesoDirecto['nivel'] ?? 0);
                    $moduloAcceso = (int)($accesoDirecto['modulo'] ?? 0);
                    $keyTareaAcceso = $nivelAcceso . '_' . $moduloAcceso;
                    $tareasModulo = (array)($tareasPorModuloDiscipulo[$keyTareaAcceso] ?? []);
                ?>
                <div class="disc-task-card">
                    <div style="font-weight:700;color:#1f4f93;">Nivel <?= $nivelAcceso ?> · Módulo <?= $moduloAcceso ?></div>
                    <div><small style="color:#637087;">Lección: <?= htmlspecialchars((string)($accesoDirecto['leccion'] ?? 'Sin lección activa')) ?></small></div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
                        <a class="btn btn-sm btn-primary" href="<?= PUBLIC_URL ?>?url=programas/evaluaciones&nivel=<?= $nivelAcceso ?>&modulo=<?= $moduloAcceso ?>&from_material=1&leccion=<?= urlencode((string)($accesoDirecto['leccion'] ?? 'Sin lección')) ?>">Ir a evaluación</a>
                    </div>

                    <div style="margin-top:10px;padding-top:8px;border-top:1px dashed #dbe3f0;">
                        <strong style="font-size:13px;color:#1f4f93;">Tareas del módulo</strong>
                        <?php if (!empty($tareasModulo)): ?>
                            <div class="disc-task-list">
                                <?php foreach ($tareasModulo as $tareaDisc): ?>
                                    <div class="disc-task-item">
                                        <div style="font-weight:700;color:#234b7c;"><?= htmlspecialchars((string)($tareaDisc['Titulo'] ?? 'Tarea')) ?></div>
                                        <div><small style="color:#637087;">Límite: <?= htmlspecialchars((string)($tareaDisc['Fecha_Limite'] ?? 'Sin fecha')) ?> · Tus entregas: <?= (int)($tareaDisc['total_entregas_usuario'] ?? 0) ?></small></div>
                                        <?php if (trim((string)($tareaDisc['Descripcion'] ?? '')) !== ''): ?>
                                            <div style="margin-top:4px;"><small style="color:#637087;"><?= htmlspecialchars((string)$tareaDisc['Descripcion']) ?></small></div>
                                        <?php endif; ?>

                                        <?php $entregasUsuario = (array)($tareaDisc['entregas_usuario'] ?? []); ?>
                                        <?php if (!empty($entregasUsuario)): ?>
                                            <div class="disc-entrega-wrap">
                                                <strong style="font-size:12px;color:#1f4f93;">Tus entregas subidas</strong>
                                                <?php foreach ($entregasUsuario as $entrega): ?>
                                                    <?php
                                                        $idEntrega = (int)($entrega['Id_Entrega'] ?? 0);
                                                        $estadoCalificacion = strtolower(trim((string)($entrega['Estado_Calificacion'] ?? 'pendiente')));
                                                        $estaCalificada = $estadoCalificacion === 'calificada';
                                                        $nombreArchivo = trim((string)($entrega['Nombre_Archivo'] ?? ''));
                                                        $nombreOriginal = trim((string)($entrega['Nombre_Original'] ?? ''));
                                                        if ($nombreOriginal === '') {
                                                            $nombreOriginal = $nombreArchivo !== '' ? $nombreArchivo : 'Archivo';
                                                        }
                                                        $urlArchivo = $nombreArchivo !== ''
                                                            ? (PUBLIC_URL . '/uploads/material_hub_tareas/capacitacion_destino/' . rawurlencode($nombreArchivo))
                                                            : '';
                                                        $notaEntrega = $entrega['Nota'] ?? null;
                                                    ?>
                                                    <div class="disc-entrega-card">
                                                        <div class="disc-entrega-row">
                                                            <div class="disc-entrega-cell">
                                                                <span class="disc-entrega-label">Entrega</span>
                                                                <?php if ($urlArchivo !== ''): ?>
                                                                    <a href="<?= htmlspecialchars($urlArchivo) ?>" target="_blank" rel="noopener" class="disc-entrega-archivo-link"><?= htmlspecialchars($nombreOriginal) ?></a>
                                                                <?php else: ?>
                                                                    <span class="disc-entrega-value" style="font-weight:700;"><?= htmlspecialchars($nombreOriginal) ?></span>
                                                                <?php endif; ?>
                                                            </div>

                                                            <div class="disc-entrega-cell">
                                                                <span class="disc-entrega-label">Fecha y hora de entrega</span>
                                                                <span class="disc-entrega-value"><?= htmlspecialchars(trim((string)($entrega['Fecha_Entrega'] ?? '')) !== '' ? (string)($entrega['Fecha_Entrega'] ?? '') : 'Sin fecha') ?></span>
                                                            </div>

                                                            <div class="disc-entrega-cell">
                                                                <span class="disc-entrega-label">Comentario</span>
                                                                <span class="disc-entrega-value"><?= htmlspecialchars(trim((string)($entrega['Comentario'] ?? '')) !== '' ? (string)($entrega['Comentario'] ?? '') : 'Sin comentario') ?></span>
                                                            </div>

                                                            <div class="disc-entrega-cell">
                                                                <span class="disc-entrega-label">Calificación del profesor</span>
                                                                <?php if ($estaCalificada): ?>
                                                                    <div class="disc-entrega-value disc-entrega-calif-ok">
                                                                        <?= htmlspecialchars('Calificada' . ($notaEntrega !== null ? (' · Nota: ' . (string)$notaEntrega) : '')) ?>
                                                                    </div>
                                                                    <div class="disc-entrega-value disc-entrega-calif-ok" style="margin-top:2px;">
                                                                        <?= htmlspecialchars(trim((string)($entrega['Fecha_Calificacion'] ?? '')) !== '' ? ('Fecha: ' . (string)($entrega['Fecha_Calificacion'] ?? '')) : 'Fecha: sin registro') ?>
                                                                    </div>
                                                                    <div class="disc-entrega-value disc-entrega-calif-ok" style="margin-top:2px;">
                                                                        <?= htmlspecialchars(trim((string)($entrega['Retroalimentacion'] ?? '')) !== '' ? ('Retro: ' . (string)($entrega['Retroalimentacion'] ?? '')) : 'Retro: sin retroalimentación') ?>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div class="disc-entrega-value disc-entrega-calif-pend">Pendiente de calificar</div>
                                                                <?php endif; ?>
                                                            </div>

                                                            <div class="disc-entrega-cell disc-entrega-actions-cell">
                                                                <div class="disc-entrega-actions">
                                                                    <details class="disc-entrega-edit">
                                                                        <summary>Editar</summary>
                                                                        <form method="POST" enctype="multipart/form-data" action="<?= PUBLIC_URL ?>?url=programas/tareas&nivel=<?= $nivelAcceso ?>&modulo=<?= $moduloAcceso ?>" class="disc-entrega-edit-form">
                                                                            <input type="hidden" name="accion" value="editar_tarea_entrega">
                                                                            <input type="hidden" name="volver_tareas" value="1">
                                                                            <input type="hidden" name="id_entrega" value="<?= $idEntrega ?>">
                                                                            <input type="hidden" name="nivel" value="<?= $nivelAcceso ?>">
                                                                            <input type="hidden" name="modulo_numero" value="<?= $moduloAcceso ?>">
                                                                            <input type="text" name="comentario_entrega_editar" class="form-control" maxlength="500" value="<?= htmlspecialchars((string)($entrega['Comentario'] ?? '')) ?>" placeholder="Actualizar comentario">
                                                                            <input type="file" name="tarea_archivo_editar" class="form-control">
                                                                            <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                                                                        </form>
                                                                    </details>

                                                                    <form method="POST" action="<?= PUBLIC_URL ?>?url=programas/tareas&nivel=<?= $nivelAcceso ?>&modulo=<?= $moduloAcceso ?>" onsubmit="return confirm('¿Eliminar esta entrega? Esta acción no se puede deshacer.');" style="margin:0;">
                                                                        <input type="hidden" name="volver_tareas" value="1">
                                                                        <input type="hidden" name="id_entrega" value="<?= $idEntrega ?>">
                                                                        <input type="hidden" name="nivel" value="<?= $nivelAcceso ?>">
                                                                        <input type="hidden" name="modulo_numero" value="<?= $moduloAcceso ?>">
                                                                        <input type="hidden" name="accion" value="eliminar_tarea_entrega">
                                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <form method="POST" enctype="multipart/form-data" action="<?= PUBLIC_URL ?>?url=programas/tareas&nivel=<?= $nivelAcceso ?>&modulo=<?= $moduloAcceso ?>" class="disc-entrega-nueva">
                                            <input type="hidden" name="accion" value="subir_tarea_entrega">
                                            <input type="hidden" name="volver_tareas" value="1">
                                            <input type="hidden" name="id_tarea" value="<?= (int)($tareaDisc['Id_Tarea'] ?? 0) ?>">
                                            <input type="hidden" name="nivel" value="<?= $nivelAcceso ?>">
                                            <input type="hidden" name="modulo_numero" value="<?= $moduloAcceso ?>">
                                            <input type="text" name="comentario_entrega" class="form-control" maxlength="500" placeholder="Comentario opcional">
                                            <input type="file" name="tarea_archivos[]" class="form-control" multiple required>
                                            <button type="submit" class="btn btn-sm" style="background:#10b981;color:#fff;">Subir tarea</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="margin-top:6px;"><small style="color:#637087;">No hay tareas activas para este módulo.</small></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info" style="margin-top:10px;">No tienes módulos activos para mostrar tareas en este momento.</div>
    <?php endif; ?>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
