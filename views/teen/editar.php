<?php include VIEWS . '/layout/header.php'; ?>

<?php
$anioMat = (int)($material['anio'] ?? 0);
$mesMat = (int)($material['mes'] ?? 0);
$semanaMat = (int)($material['semana_mes'] ?? 0);
$profesorNombre = trim((string)($material['profesor_nombre'] ?? ''));
$idProfesor = (int)($material['id_profesor'] ?? 0);
$urlBuscarProfesor = (string)($url_buscar_profesor ?? public_app_url('teen/buscarAcudientes'));
?>

<div class="page-header">
    <h2>Editar Material</h2>
    <div>
        <a href="<?= PUBLIC_URL ?>index.php?url=teen<?= $anioMat > 0 ? '&anio=' . $anioMat . '&mes=' . $mesMat : '' ?>" class="btn btn-sm btn-secondary">
            ← Volver
        </a>
    </div>
</div>

<div class="card" style="max-width: 720px; margin: 20px auto;">
    <div class="card-body">
        <?php if ($anioMat > 0 && $mesMat > 0 && $semanaMat > 0): ?>
            <p class="text-muted" style="margin-bottom:16px;">
                <strong><?= htmlspecialchars($material['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong><br>
                Año <?= $anioMat ?> · Mes <?= $mesMat ?> · Semana <?= $semanaMat ?>
            </p>
        <?php endif; ?>

        <form action="<?= PUBLIC_URL ?>index.php?url=teen/editar&id=<?= (int)($material['id'] ?? 0) ?>" method="POST" enctype="multipart/form-data">

            <div class="form-group" style="margin-bottom: 15px;">
                <label for="titulo"><strong>Título *</strong></label>
                <input type="text" id="titulo" name="titulo" class="form-control" required maxlength="255"
                       value="<?= htmlspecialchars($material['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label for="descripcion"><strong>Descripción</strong></label>
                <textarea id="descripcion" name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($material['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label for="profesor_busqueda"><strong>Maestro de la semana</strong></label>
                <input type="hidden" id="id_profesor" name="id_profesor" value="<?= $idProfesor ?>">
                <input type="text" id="profesor_busqueda" name="profesor_busqueda" class="form-control"
                       value="<?= htmlspecialchars($profesorNombre, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Buscar persona o escribir nombre" autocomplete="off">
                <input type="hidden" id="profesor_nombre" name="profesor_nombre" value="<?= htmlspecialchars($profesorNombre, ENT_QUOTES, 'UTF-8') ?>">
                <div id="profesor_sugerencias_editar"></div>
            </div>

            <?php if (!empty($archivosActuales)): ?>
                <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <h4 style="margin-top: 0;">Archivos actuales</h4>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($archivosActuales as $archivo): ?>
                            <?php
                                $rutaRel = str_replace('\\', '/', (string)$archivo);
                                $rutaFisica = rtrim(str_replace('\\', '/', (string)$directorioMateriales), '/') . '/' . $rutaRel;
                                $tamanio = is_file($rutaFisica) ? round(((int)@filesize($rutaFisica)) / 1024, 2) : 0;
                            ?>
                            <div style="display: flex; align-items: center; gap: 10px; padding: 8px; background: white; border: 1px solid #ddd; border-radius: 4px;">
                                <input type="checkbox" id="eliminar_<?= md5($archivo) ?>" name="eliminar_archivo[]" value="<?= htmlspecialchars($archivo, ENT_QUOTES, 'UTF-8') ?>">
                                <label for="eliminar_<?= md5($archivo) ?>" style="margin: 0; flex: 1; cursor: pointer;">
                                    <strong><?= htmlspecialchars(basename($archivo), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <br><small style="color: #666;"><?= number_format($tamanio, 2) ?> KB</small>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small style="display: block; margin-top: 10px; color: #666;">Marca los archivos que deseas eliminar</small>
                </div>
            <?php endif; ?>

            <div class="form-group" style="margin-bottom: 15px;">
                <label for="archivo_pdf"><strong>Agregar más archivos PDF</strong></label>
                <input type="file" id="archivo_pdf" name="archivo_pdf[]" class="form-control" accept="application/pdf" multiple>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-success">Guardar cambios</button>
                <a href="<?= PUBLIC_URL ?>index.php?url=teen" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var urlBuscar = <?= json_encode($urlBuscarProfesor, JSON_UNESCAPED_UNICODE) ?>;
    var input = document.getElementById('profesor_busqueda');
    var hiddenId = document.getElementById('id_profesor');
    var hiddenNombre = document.getElementById('profesor_nombre');
    var contenedor = document.getElementById('profesor_sugerencias_editar');
    if (!input || !contenedor) return;

    var lista = document.createElement('div');
    lista.className = 'teen-sugerencias-list';
    lista.style.display = 'none';
    contenedor.appendChild(lista);

    var timer = null;
    input.addEventListener('input', function () {
        if (hiddenId) hiddenId.value = '';
        if (hiddenNombre) hiddenNombre.value = input.value.trim();
        var term = input.value.trim();
        if (term.length < 2) {
            lista.style.display = 'none';
            return;
        }
        clearTimeout(timer);
        timer = setTimeout(function () {
            fetch(urlBuscar + (urlBuscar.indexOf('?') >= 0 ? '&' : '?') + 'term=' + encodeURIComponent(term))
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    lista.innerHTML = '';
                    if (!res || !res.success || !res.data) {
                        lista.style.display = 'none';
                        return;
                    }
                    res.data.forEach(function (p) {
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        var nombre = ((p.Nombre || '') + ' ' + (p.Apellido || '')).trim();
                        btn.textContent = nombre;
                        btn.style.cssText = 'display:block;width:100%;text-align:left;border:0;background:#fff;padding:8px 12px;cursor:pointer';
                        btn.addEventListener('click', function () {
                            input.value = nombre;
                            if (hiddenId) hiddenId.value = String(p.Id_Persona || '');
                            if (hiddenNombre) hiddenNombre.value = nombre;
                            lista.style.display = 'none';
                        });
                        lista.appendChild(btn);
                    });
                    lista.style.display = res.data.length ? 'block' : 'none';
                });
        }, 250);
    });
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
