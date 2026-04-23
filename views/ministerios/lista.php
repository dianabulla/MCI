<?php include VIEWS . '/layout/header.php'; ?>

<?php
$returnUrl = $return_url ?? null;
$returnUrlParam = $returnUrl ? '&return_url=' . urlencode((string)$returnUrl) : '';

$sections = is_array($sections ?? null) ? $sections : [];
$ministeriosOrdenados = array_values($sections);

$slugMinisterio = static function ($texto) {
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower((string)$texto));
    $slug = trim((string)$slug, '-');
    return $slug !== '' ? $slug : 'sin-ministerio';
};

$puedeCrearMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'crear');
$puedeEditarMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'editar');
$puedeEliminarMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'eliminar');
$urlEquipoPrincipal = PUBLIC_URL . '?url=ministerios/equipo-principal';
$urlLideresCelula = PUBLIC_URL . '?url=ministerios/lideres-celula';
?>

<div class="page-header">
    <h2>Ministerios</h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <?php if (!empty($returnUrl)): ?>
        <a href="<?= htmlspecialchars((string)$returnUrl) ?>" class="btn btn-secondary">← Volver a reportes</a>
        <?php endif; ?>
        <a href="<?= $urlEquipoPrincipal ?>" class="btn btn-secondary">Equipo Principal</a>
        <a href="<?= $urlLideresCelula ?>" class="btn btn-secondary">Líderes de Célula</a>
        <?php if ($puedeCrearMinisterio): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=ministerios/crear<?= $returnUrlParam ?>" class="btn btn-primary">+ Nuevo Ministerio</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($ministeriosOrdenados)): ?>
<div class="dashboard-grid ministerios-summary-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 320px)); margin:18px 0;">
    <?php foreach ($ministeriosOrdenados as $section): ?>
        <?php
        $ministerioNombre = (string)($section['label'] ?? 'Ministerio sin nombre');
        $rowsMinisterio = is_array($section['rows'] ?? null) ? $section['rows'] : [];
        $totalLideres = count(array_filter($rowsMinisterio, static function ($row) {
            return !empty($row['match_lideres_12']) || !empty($row['match_lideres_celula']);
        }));
        $idMinisterio = (int)($section['id_ministerio'] ?? 0);
        $descripcion = trim((string)($section['descripcion'] ?? ''));
        $urlLideresCelulaPorMinisterio = $urlLideresCelula . '&id_ministerio=' . $idMinisterio;
        ?>
        <button
            type="button"
            class="dashboard-card ministerios-summary-card"
            data-equipo-url="<?= htmlspecialchars($urlLideresCelulaPorMinisterio) ?>"
            style="border-left-color:#17a2b8; text-align:left; cursor:pointer;"
        >
            <h3 class="ministerios-summary-title"><?= htmlspecialchars($ministerioNombre) ?></h3>
            <div class="value ministerios-summary-value" style="color:#17a2b8;"><?= $totalLideres ?></div>
            <small class="ministerios-summary-subtitle">Lideres del ministerio</small>
            <?php if ($descripcion !== ''): ?>
                <small class="ministerios-card-description"><?= htmlspecialchars($descripcion) ?></small>
            <?php else: ?>
                <small class="ministerios-card-description">Sin descripción registrada</small>
            <?php endif; ?>

            <div class="ministerios-card-footer">
                <span class="ministerios-card-cta">Clic para ver lideres de celula</span>
                <?php if ($puedeEditarMinisterio || $puedeEliminarMinisterio): ?>
                <div class="ministerios-actions-row">
                    <?php if ($puedeEditarMinisterio): ?>
                    <a href="<?= PUBLIC_URL ?>?url=ministerios/editar&id=<?= $idMinisterio ?><?= $returnUrlParam ?>#metas" class="btn btn-sm ministerios-action-btn ministerios-action-btn--text ministerios-action-btn--metas" title="Metas" aria-label="Metas">Metas</a>
                    <a href="<?= PUBLIC_URL ?>?url=ministerios/editar&id=<?= $idMinisterio ?><?= $returnUrlParam ?>" class="btn btn-sm ministerios-action-btn ministerios-action-btn--icon ministerios-action-btn--edit" title="Editar" aria-label="Editar">
                        <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($puedeEliminarMinisterio): ?>
                    <a href="<?= PUBLIC_URL ?>?url=ministerios/eliminar&id=<?= $idMinisterio ?><?= $returnUrlParam ?>" class="btn btn-sm ministerios-action-btn ministerios-action-btn--icon ministerios-action-btn--delete" title="Eliminar" aria-label="Eliminar" onclick="return confirm('¿Eliminar este ministerio?')">
                        <i class="bi bi-trash-fill" aria-hidden="true"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </button>
    <?php endforeach; ?>
</div>

<div class="card" style="margin-bottom:18px;">
    <div class="card-body" style="padding:14px 16px; color:#5b6d88;">
        Selecciona una tarjeta para abrir el Equipo Principal de ese ministerio y ver sus líderes de 12 separados por hombres y mujeres.
    </div>
</div>

<?php else: ?>
    <div class="table-container">
        <p class="text-center" style="padding: 20px; color: #666; margin: 0;">No hay ministerios registrados</p>
    </div>
<?php endif; ?>

<style>
.ministerios-summary-grid {
    gap: 14px;
}

.ministerios-summary-card {
    appearance: none;
    border-top: 0;
    border-right: 0;
    border-bottom: 0;
    width: 100%;
    border-radius: 16px;
    border-left-width: 3px;
    border-left-style: solid;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    box-shadow: 0 6px 16px rgba(15, 35, 61, 0.06);
    transition: transform 0.18s ease, box-shadow 0.18s ease, outline 0.18s ease;
}

.ministerios-summary-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(15, 35, 61, 0.08);
}

.ministerios-summary-card:focus-visible {
    outline: 3px solid rgba(37, 99, 235, 0.22);
    outline-offset: 2px;
}

.ministerios-summary-title {
    margin-bottom: 6px;
    color: #5b6d8f;
    font-weight: 700;
}

.ministerios-summary-value {
    font-size: 42px;
    line-height: 1;
    margin-bottom: 4px;
}

.ministerios-summary-subtitle {
    display: block;
    color: #6d7d97;
    font-size: 13px;
    font-weight: 600;
}

.ministerios-card-description {
    display: block;
    color: #60708a;
    min-height: 28px;
    margin-top: 8px;
}

.ministerios-actions-row {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.ministerios-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: 14px;
}

.ministerios-card-cta {
    color: #0f5fca;
    font-weight: 700;
    font-size: 12px;
}

.ministerios-action-btn {
    border: 1px solid #d6e1f0;
    background: #f6f9ff;
    color: #2f4f7a;
    font-weight: 600;
}

.ministerios-action-btn:hover {
    background: #edf3fc;
    color: #274368;
}

.ministerios-action-btn--icon {
    min-width: 30px;
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.ministerios-action-btn--text {
    height: 30px;
    padding: 0 10px;
    font-size: 12px;
}

.ministerios-action-btn--metas {
    background: #eefbf1;
    border-color: #bfe8c9;
    color: #1f6b3d;
}

.ministerios-action-btn--metas:hover {
    background: #e2f6e8;
    color: #16522f;
}

.ministerios-action-btn--edit {
    background: #fff8e8;
    border-color: #f1ddb1;
    color: #8a6400;
}

.ministerios-action-btn--edit:hover {
    background: #fff1d8;
    color: #7a5600;
}

.ministerios-action-btn--delete {
    background: #fff0f1;
    border-color: #f0d0d4;
    color: #9b2e3a;
}

.ministerios-action-btn--delete:hover {
    background: #ffe4e7;
    color: #8a2631;
}

@media (max-width: 800px) {
    .ministerios-summary-grid {
        grid-template-columns: 1fr !important;
    }

    .ministerios-card-footer {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>

<script>
(function() {
    const tarjetas = Array.from(document.querySelectorAll('.ministerios-summary-card'));
    if (!tarjetas.length) {
        return;
    }

    tarjetas.forEach(function(tarjeta) {
        tarjeta.addEventListener('click', function() {
            const destino = String(tarjeta.dataset.equipoUrl || '');
            if (destino !== '') {
                window.location.href = destino;
            }
        });
    });

    const acciones = Array.from(document.querySelectorAll('.ministerios-actions-row a'));
    acciones.forEach(function(link) {
        link.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    });
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
