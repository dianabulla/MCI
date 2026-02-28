<?php include VIEWS . '/layout/header.php'; ?>

<style>
    .ministerio-personas-table {
        min-width: 860px;
        table-layout: auto;
    }

    .ministerio-personas-table th,
    .ministerio-personas-table td {
        white-space: nowrap;
        word-break: normal;
        overflow-wrap: normal;
    }

    .ministerio-personas-table th:nth-child(2),
    .ministerio-personas-table td:nth-child(2),
    .ministerio-personas-table th:nth-child(5),
    .ministerio-personas-table td:nth-child(5) {
        white-space: normal;
        word-break: keep-all;
        overflow-wrap: break-word;
        min-width: 220px;
    }
</style>

<div class="page-header">
    <h2>Ministerios</h2>
    <?php $puedeCrearMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'crear'); ?>
    <?php $puedeEditarMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'editar'); ?>
    <?php $puedeEliminarMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'eliminar'); ?>
    <?php $puedeGestionarMinisterio = $puedeEditarMinisterio || $puedeEliminarMinisterio; ?>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=ministerios/exportarExcel" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
        <?php if ($puedeCrearMinisterio): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=ministerios/crear" class="btn btn-primary">+ Nuevo Ministerio</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($sections ?? [])): ?>
<div class="sections-grid">
    <?php foreach ($sections as $section): ?>
        <details class="section-collapse">
            <summary>
                <div class="collapse-title">
                    <i class="bi bi-bank"></i> <?= htmlspecialchars($section['label']) ?>
                </div>
                <div class="section-meta mb-0">
                    <a class="view-group-btn" href="<?= PUBLIC_URL ?>?url=personas&ministerio=<?= (int)$section['id_ministerio'] ?>" onclick="event.stopPropagation();">Ver personas</a>
                    <span class="meta-pill">Personas: <?= number_format((int)$section['total_personas']) ?></span>
                    <span class="collapse-arrow">▶</span>
                </div>
            </summary>

            <div class="collapse-content">
                <?php if (!empty($section['descripcion'])): ?>
                    <div class="section-meta">
                        <span class="meta-pill">Descripción: <?= htmlspecialchars($section['descripcion']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($puedeGestionarMinisterio): ?>
                <div class="mb-3">
                    <?php if ($puedeEditarMinisterio): ?>
                        <a href="<?= PUBLIC_URL ?>?url=ministerios/editar&id=<?= (int)$section['id_ministerio'] ?>" class="btn btn-sm btn-warning">Editar</a>
                    <?php endif; ?>
                    <?php if ($puedeEliminarMinisterio): ?>
                        <a href="<?= PUBLIC_URL ?>?url=ministerios/eliminar&id=<?= (int)$section['id_ministerio'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este ministerio?')">Eliminar</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="table-responsive ministerio-table-wrap">
                    <table class="table table-hover ministerio-detail-table ministerio-personas-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Nro</th>
                                <th>Persona</th>
                                <th style="width: 140px;">Teléfono</th>
                                <th style="width: 160px;">Documento</th>
                                <th style="width: 180px;">Célula</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($section['rows'])): ?>
                                <?php foreach ($section['rows'] as $row): ?>
                                    <tr>
                                        <td><?= (int)$row['nro'] ?></td>
                                        <td>
                                            <a class="group-link" href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= (int)$row['id_persona'] ?>">
                                                <?= htmlspecialchars($row['nombre']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($row['telefono'] !== '' ? $row['telefono'] : '—') ?></td>
                                        <td><?= htmlspecialchars($row['documento'] !== '' ? $row['documento'] : '—') ?></td>
                                        <td><?= htmlspecialchars($row['celula'] !== '' ? $row['celula'] : '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No hay líderes de célula activos registrados en este ministerio</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </details>
    <?php endforeach; ?>
</div>
<?php else: ?>
    <div class="table-container">
        <p class="text-center" style="padding: 20px; color: #666; margin: 0;">No hay ministerios registrados</p>
    </div>
<?php endif; ?>

<?php include VIEWS . '/layout/footer.php'; ?>
