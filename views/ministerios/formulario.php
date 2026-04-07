<?php include VIEWS . '/layout/header.php'; ?>

<?php
$returnUrl = $return_url ?? null;
$volverUrl = $returnUrl ?: (PUBLIC_URL . 'index.php?url=ministerios');
$metas = $metas ?? [];
$metaGanadosS1 = (int)($metas['meta_ganados_s1'] ?? 0);
$metaGanadosS2 = (int)($metas['meta_ganados_s2'] ?? 0);
$metaUvS1 = (int)($metas['meta_uv_s1'] ?? 0);
$metaUvS2 = (int)($metas['meta_uv_s2'] ?? 0);
$metaEncuentroS1 = (int)($metas['meta_encuentro_s1'] ?? 0);
$metaEncuentroS2 = (int)($metas['meta_encuentro_s2'] ?? 0);
$metaN1S1 = (int)($metas['meta_n1_s1'] ?? 0);
$metaN1S2 = (int)($metas['meta_n1_s2'] ?? 0);
$metaN2S1 = (int)($metas['meta_n2_s1'] ?? 0);
$metaN2S2 = (int)($metas['meta_n2_s2'] ?? 0);
$metaN3S1 = (int)($metas['meta_n3_s1'] ?? 0);
$metaN3S2 = (int)($metas['meta_n3_s2'] ?? 0);
?>

<style>
.metas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 12px;
    margin-top: 10px;
}

.metas-col {
    border: 1px solid #d8e2f1;
    border-radius: 10px;
    background: #f8fbff;
    padding: 10px;
}

.metas-col h4 {
    margin: 0 0 8px;
    color: #21457e;
    font-size: 15px;
}

.metas-row {
    display: grid;
    grid-template-columns: 1fr 110px;
    gap: 8px;
    align-items: center;
    margin-bottom: 7px;
}

.metas-row label {
    margin: 0;
    color: #4b5f7e;
    font-size: 13px;
}
</style>

<div class="page-header">
    <h2><?= isset($ministerio) ? 'Editar' : 'Nuevo' ?> Ministerio</h2>
    <a href="<?= htmlspecialchars($volverUrl) ?>" class="btn btn-secondary">Volver</a>
</div>

<div class="form-container">
    <form method="POST">
        <?php if (!empty($returnUrl)): ?>
        <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">
        <?php endif; ?>
        <div class="form-group">
            <label for="nombre_ministerio">Nombre del Ministerio</label>
            <input type="text" id="nombre_ministerio" name="nombre_ministerio" class="form-control" 
                   value="<?= htmlspecialchars($ministerio['Nombre_Ministerio'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" class="form-control" rows="4" required><?= htmlspecialchars($ministerio['Descripcion'] ?? '') ?></textarea>
        </div>

        <?php if (!empty($ministerio['Id_Ministerio'])): ?>
        <div class="card" style="margin-bottom: 14px;">
            <div class="card-body" style="padding: 12px;">
                <h3 style="margin: 0 0 6px; color:#21457e; font-size: 17px;">Metas por semestre</h3>
                <small style="color:#5f6f88;">Configura metas de almas ganadas y de cada evento de escalera/convenciones.</small>

                <div class="metas-grid">
                    <div class="metas-col">
                        <h4>Semestre 1 (Enero - Junio)</h4>

                        <div class="metas-row">
                            <label for="meta_ganados_s1">Meta almas ganadas</label>
                            <input type="number" min="0" step="1" id="meta_ganados_s1" name="meta_ganados_s1" class="form-control" value="<?= $metaGanadosS1 ?>">
                        </div>

                        <div class="metas-row">
                            <label for="meta_uv_s1">Meta Universidad de la vida</label>
                            <input type="number" min="0" step="1" id="meta_uv_s1" name="meta_uv_s1" class="form-control" value="<?= $metaUvS1 ?>">
                        </div>

                        <div class="metas-row">
                            <label for="meta_encuentro_s1">Meta Encuentro</label>
                            <input type="number" min="0" step="1" id="meta_encuentro_s1" name="meta_encuentro_s1" class="form-control" value="<?= $metaEncuentroS1 ?>">
                        </div>

                        <div class="metas-row">
                            <label for="meta_n1_s1">Meta Convención N1</label>
                            <input type="number" min="0" step="1" id="meta_n1_s1" name="meta_n1_s1" class="form-control" value="<?= $metaN1S1 ?>">
                        </div>

                        <div class="metas-row">
                            <label for="meta_n2_s1">Meta Convención N2</label>
                            <input type="number" min="0" step="1" id="meta_n2_s1" name="meta_n2_s1" class="form-control" value="<?= $metaN2S1 ?>">
                        </div>

                        <div class="metas-row" style="margin-bottom:0;">
                            <label for="meta_n3_s1">Meta Convención N3</label>
                            <input type="number" min="0" step="1" id="meta_n3_s1" name="meta_n3_s1" class="form-control" value="<?= $metaN3S1 ?>">
                        </div>
                    </div>

                    <div class="metas-col">
                        <h4>Semestre 2 (Julio - Diciembre)</h4>

                        <div class="metas-row">
                            <label for="meta_ganados_s2">Meta almas ganadas</label>
                            <input type="number" min="0" step="1" id="meta_ganados_s2" name="meta_ganados_s2" class="form-control" value="<?= $metaGanadosS2 ?>">
                        </div>

                        <div class="metas-row">
                            <label for="meta_uv_s2">Meta Universidad de la vida</label>
                            <input type="number" min="0" step="1" id="meta_uv_s2" name="meta_uv_s2" class="form-control" value="<?= $metaUvS2 ?>">
                        </div>

                        <div class="metas-row">
                            <label for="meta_encuentro_s2">Meta Encuentro</label>
                            <input type="number" min="0" step="1" id="meta_encuentro_s2" name="meta_encuentro_s2" class="form-control" value="<?= $metaEncuentroS2 ?>">
                        </div>

                        <div class="metas-row">
                            <label for="meta_n1_s2">Meta Convención N1</label>
                            <input type="number" min="0" step="1" id="meta_n1_s2" name="meta_n1_s2" class="form-control" value="<?= $metaN1S2 ?>">
                        </div>

                        <div class="metas-row">
                            <label for="meta_n2_s2">Meta Convención N2</label>
                            <input type="number" min="0" step="1" id="meta_n2_s2" name="meta_n2_s2" class="form-control" value="<?= $metaN2S2 ?>">
                        </div>

                        <div class="metas-row" style="margin-bottom:0;">
                            <label for="meta_n3_s2">Meta Convención N3</label>
                            <input type="number" min="0" step="1" id="meta_n3_s2" name="meta_n3_s2" class="form-control" value="<?= $metaN3S2 ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=ministerios" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
