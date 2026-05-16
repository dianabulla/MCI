<?php include VIEWS . '/layout/header.php'; ?>

<?php
$returnUrl = $return_url ?? null;
$returnUrlParam = $returnUrl ? '&return_url=' . urlencode((string)$returnUrl) : '';

$sections = is_array($sections ?? null) ? $sections : [];
$ministeriosOrdenados = array_values($sections);

$puedeCrearMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'crear');
$puedeEditarMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'editar');
$puedeEliminarMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'eliminar');
$urlEquipoPrincipal = PUBLIC_URL . '?url=discipular/ministerios/equipo-principal';
$urlLideresCelula = PUBLIC_URL . '?url=discipular/ministerios/lideres-celula';
$urlNuevoMinisterio = PUBLIC_URL . '?url=discipular/ministerios/crear' . $returnUrlParam;
$fechaReferenciaVista = (string)($fecha_referencia ?? date('Y-m-d'));
?>

<div class="page-header" style="align-items:flex-start; gap:16px;">
    <div>
        <h2>Discipular · Ministerios (redes)</h2>
        <p style="margin:6px 0 0; font-size:0.9rem; color:#5c6f8b; max-width:52rem; line-height:1.4;">
            Cada ministerio corresponde a una <strong>red</strong> de la visión (hombres, mujeres, jóvenes, etc.). Desde aquí accedes al equipo del 12, metas y líderes de célula.
        </p>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:flex-start;">
        <?php if (!empty($returnUrl)): ?>
        <a href="<?= htmlspecialchars((string)$returnUrl) ?>" class="btn btn-secondary">← Volver a reportes</a>
        <?php endif; ?>
        <a href="<?= $urlEquipoPrincipal ?>" class="btn btn-secondary">Equipo Principal</a>
        <a href="<?= $urlLideresCelula ?>" class="btn btn-secondary">Líderes de Célula</a>
        <?php if ($puedeCrearMinisterio): ?>
        <a href="<?= $urlNuevoMinisterio ?>" class="btn btn-primary">+ Nuevo Ministerio</a>
        <?php endif; ?>
    </div>
</div>

<?php include VIEWS . '/discipular/partials/jerarquia_g12_panel.php'; ?>

<?php if (!empty($ministeriosOrdenados)): ?>
<div class="table-container" style="margin-top:16px;">
    <table class="data-table ministerios-lista-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Ministerio</th>
                <th>Personas</th>
                <th>Líderes</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ministeriosOrdenados as $idx => $section): ?>
                <?php
                $ministerioNombre = (string)($section['label'] ?? 'Ministerio sin nombre');
                $rowsMinisterio = is_array($section['rows'] ?? null) ? $section['rows'] : [];
                $totalPersonas = (int)($section['total_personas'] ?? count($rowsMinisterio));
                $totalLideres = count(array_filter($rowsMinisterio, static function ($row) {
                    return !empty($row['match_lideres_12']) || !empty($row['match_lideres_celula']);
                }));
                $idMinisterio = (int)($section['id_ministerio'] ?? 0);
                $descripcion = trim((string)($section['descripcion'] ?? ''));
                $urlEquipoPrincipalPorMinisterio = $urlEquipoPrincipal . '&id_ministerio=' . $idMinisterio;
                ?>
                <tr>
                    <td><?= (int)($idx + 1) ?></td>
                    <td title="<?= htmlspecialchars($descripcion !== '' ? $descripcion : 'Sin descripción registrada') ?>"><strong><?= htmlspecialchars($ministerioNombre) ?></strong></td>
                    <td><?= $totalPersonas ?></td>
                    <td>
                        <span class="badge badge-info"><?= $totalLideres ?></span>
                    </td>
                    <td>
                        <div class="ministerios-acciones-tabla">
                            <a href="<?= htmlspecialchars($urlEquipoPrincipalPorMinisterio) ?>" class="btn btn-sm ministerios-action-btn ministerios-action-btn--detail" title="Ver detalle" aria-label="Ver detalle">
                                <i class="bi bi-eye-fill" aria-hidden="true"></i>
                            </a>
                            <?php if ($puedeEditarMinisterio): ?>
                            <a href="<?= PUBLIC_URL ?>?url=discipular/ministerios/editar&id=<?= $idMinisterio ?><?= $returnUrlParam ?>#metas" class="btn btn-sm ministerios-action-btn ministerios-action-btn--metas" title="Metas" aria-label="Metas">
                                <i class="bi bi-flag-fill" aria-hidden="true"></i>
                            </a>
                            <a href="<?= PUBLIC_URL ?>?url=discipular/ministerios/editar&id=<?= $idMinisterio ?><?= $returnUrlParam ?>" class="btn btn-sm ministerios-action-btn ministerios-action-btn--edit" title="Editar" aria-label="Editar">
                                <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($puedeEliminarMinisterio): ?>
                            <a href="<?= PUBLIC_URL ?>?url=discipular/ministerios/eliminar&id=<?= $idMinisterio ?><?= $returnUrlParam ?>" class="btn btn-sm ministerios-action-btn ministerios-action-btn--delete" title="Eliminar" aria-label="Eliminar" onclick="return confirm('¿Eliminar este ministerio?')">
                                <i class="bi bi-trash-fill" aria-hidden="true"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php else: ?>
<div class="table-container">
    <p class="text-center" style="padding: 20px; color: #666; margin: 0;">No hay ministerios registrados</p>
</div>
<?php endif; ?>

<style>
.ministerios-lista-table th,
.ministerios-lista-table td {
    vertical-align: middle;
    font-size: 12px;
    padding: 6px 8px;
    line-height: 1.2;
}

.ministerios-lista-table {
    table-layout: fixed;
    width: 100%;
}

.ministerios-lista-table th:nth-child(1),
.ministerios-lista-table td:nth-child(1) {
    width: 44px;
}

.ministerios-lista-table th:nth-child(3),
.ministerios-lista-table td:nth-child(3),
.ministerios-lista-table th:nth-child(4),
.ministerios-lista-table td:nth-child(4) {
    width: 84px;
    text-align: center;
}

.ministerios-lista-table th:nth-child(5),
.ministerios-lista-table td:nth-child(5) {
    width: 150px;
}

.ministerios-lista-table td:nth-child(2) {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ministerios-acciones-tabla {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    align-items: center;
}

.ministerios-action-btn {
    border: 1px solid #d6e1f0;
    background: #f6f9ff;
    color: #2f4f7a;
    font-weight: 600;
    width: 28px;
    height: 28px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.ministerios-action-btn--detail {
    background: #edf3ff;
    border-color: #cdddf8;
    color: #1e5db8;
}

.ministerios-action-btn--metas {
    background: #eefbf1;
    border-color: #bfe8c9;
    color: #1f6b3d;
}

.ministerios-action-btn--edit {
    background: #fff8e8;
    border-color: #f1ddb1;
    color: #8a6400;
}

.ministerios-action-btn--delete {
    background: #fff0f1;
    border-color: #f0d0d4;
    color: #9b2e3a;
}

@media (max-width: 900px) {
    .ministerios-lista-table td,
    .ministerios-lista-table th {
        font-size: 11px;
        padding: 5px 6px;
    }

    .ministerios-lista-table th:nth-child(5),
    .ministerios-lista-table td:nth-child(5) {
        width: 132px;
    }
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>