<?php include VIEWS . '/layout/header.php'; ?>

<?php
$returnUrl = $return_url ?? null;
$volverUrl = $returnUrl ?: (PUBLIC_URL . 'index.php?url=ministerios');
$metas = $metas ?? [];
$metaAnual = (int)($metas['meta_anual'] ?? 0);
$metaMensual = (int)($metas['meta_mensual'] ?? 0);
$metaSemanal = (int)($metas['meta_semanal'] ?? 0);
$anioMeta = (int)($metas['anio_meta'] ?? date('Y'));
$fechaMeta = sprintf('%04d-01-01', $anioMeta > 0 ? $anioMeta : (int)date('Y'));
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

.metas-auto-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 10px;
    margin-top: 10px;
}

.metas-auto-help {
    margin: 8px 0 0;
    font-size: 12px;
    color: #58708f;
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

                <div class="metas-col" style="margin-top:10px;">
                    <h4 style="margin-bottom:6px;">Configuración automática (por ministerio)</h4>
                    <small style="color:#60708a;">Ingresa la meta anual y selecciona el año en calendario. El sistema calcula meta mensual y semanal automáticamente.</small>

                    <div class="metas-auto-grid">
                        <div>
                            <label for="meta_anual">Meta anual (ganados)</label>
                            <input type="number" min="0" step="1" id="meta_anual" name="meta_anual" class="form-control" value="<?= $metaAnual ?>">
                        </div>
                        <div>
                            <label for="meta_anio_fecha">Calendario (año meta)</label>
                            <input type="date" id="meta_anio_fecha" name="meta_anio_fecha" class="form-control" value="<?= htmlspecialchars($fechaMeta, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" id="anio_meta" name="anio_meta" value="<?= (int)$anioMeta ?>">
                        </div>
                        <div>
                            <label for="meta_mensual">Meta mensual</label>
                            <input type="number" min="0" step="1" id="meta_mensual" name="meta_mensual" class="form-control" value="<?= $metaMensual ?>" readonly>
                        </div>
                        <div>
                            <label for="meta_semanal">Meta semanal</label>
                            <input type="number" min="0" step="1" id="meta_semanal" name="meta_semanal" class="form-control" value="<?= $metaSemanal ?>" readonly>
                        </div>
                    </div>
                    <p class="metas-auto-help">La meta semestral de ganados se distribuye automáticamente desde la meta anual según el calendario del año elegido.</p>
                </div>

                <div class="metas-grid">
                    <div class="metas-col">
                        <h4>Semestre 1 (Enero - Junio)</h4>

                        <div class="metas-row">
                            <label for="meta_ganados_s1">Meta almas ganadas</label>
                            <input type="number" min="0" step="1" id="meta_ganados_s1" name="meta_ganados_s1" class="form-control" value="<?= $metaGanadosS1 ?>" readonly>
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
                            <input type="number" min="0" step="1" id="meta_ganados_s2" name="meta_ganados_s2" class="form-control" value="<?= $metaGanadosS2 ?>" readonly>
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

<script>
(function() {
    var inputAnual = document.getElementById('meta_anual');
    var inputFecha = document.getElementById('meta_anio_fecha');
    var inputAnio = document.getElementById('anio_meta');
    var inputMensual = document.getElementById('meta_mensual');
    var inputSemanal = document.getElementById('meta_semanal');
    var inputS1 = document.getElementById('meta_ganados_s1');
    var inputS2 = document.getElementById('meta_ganados_s2');

    if (!inputAnual || !inputFecha || !inputAnio || !inputMensual || !inputSemanal || !inputS1 || !inputS2) {
        return;
    }

    function getAnioSeleccionado() {
        var fecha = String(inputFecha.value || '');
        var match = fecha.match(/^(\d{4})-\d{2}-\d{2}$/);
        if (match) {
            return parseInt(match[1], 10);
        }
        return new Date().getFullYear();
    }

    function diasEnAnio(anio) {
        var inicio = new Date(anio, 0, 1);
        var fin = new Date(anio, 11, 31);
        var diff = fin.getTime() - inicio.getTime();
        return Math.floor(diff / 86400000) + 1;
    }

    function recalcularMetas() {
        var anual = Math.max(0, parseInt(inputAnual.value || '0', 10) || 0);
        var anio = getAnioSeleccionado();
        inputAnio.value = String(anio);

        var dias = diasEnAnio(anio);
        var semanas = Math.ceil(dias / 7);

        var mensual = anual <= 0 ? 0 : Math.round(anual / 12);
        var semanal = anual <= 0 ? 0 : Math.ceil(anual / Math.max(1, semanas));

        var diasS1 = Math.floor((new Date(anio, 5, 30).getTime() - new Date(anio, 0, 1).getTime()) / 86400000) + 1;
        var metaS1 = anual <= 0 ? 0 : Math.round(anual * (diasS1 / dias));
        var metaS2 = anual <= 0 ? 0 : Math.max(0, anual - metaS1);

        inputMensual.value = String(mensual);
        inputSemanal.value = String(semanal);
        inputS1.value = String(metaS1);
        inputS2.value = String(metaS2);
    }

    inputAnual.addEventListener('input', recalcularMetas);
    inputFecha.addEventListener('change', recalcularMetas);
    recalcularMetas();
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
