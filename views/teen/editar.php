<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Editar Material Teens</h2>
    <div>
        <a href="<?= PUBLIC_URL ?>index.php?url=teen" class="btn btn-sm btn-secondary">
            ← Volver
        </a>
    </div>
</div>

<div class="card" style="max-width: 600px; margin: 20px auto;">
    <div class="card-body">
        <form action="<?= PUBLIC_URL ?>index.php?url=teen/editar&id=<?= (int)($material['id'] ?? 0) ?>" method="POST" enctype="multipart/form-data">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label for="titulo"><strong>Título *</strong></label>
                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    class="form-control"
                    required
                    maxlength="255"
                    value="<?= htmlspecialchars($material['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="Ej: Guía Semana 1"
                >
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label for="descripcion"><strong>Descripción</strong></label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    class="form-control"
                    rows="3"
                    placeholder="Descripción opcional del material"
                ><?= htmlspecialchars($material['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- Archivos actuales -->
            <?php if (!empty($archivosActuales)): ?>
                <div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <h4 style="margin-top: 0;">Archivos actuales</h4>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <?php foreach ($archivosActuales as $archivo): ?>
                            <?php 
                                $ruta = $directorioMateriales . '/' . basename($archivo);
                                $tamanio = is_file($ruta) ? round(((int)@filesize($ruta)) / 1024, 2) : 0;
                            ?>
                            <div style="display: flex; align-items: center; gap: 10px; padding: 8px; background: white; border: 1px solid #ddd; border-radius: 4px;">
                                <input
                                    type="checkbox"
                                    id="eliminar_<?= md5($archivo) ?>"
                                    name="eliminar_archivo[]"
                                    value="<?= htmlspecialchars($archivo) ?>"
                                >
                                <label for="eliminar_<?= md5($archivo) ?>" style="margin: 0; flex: 1; cursor: pointer;">
                                    <strong><?= htmlspecialchars(basename($archivo)) ?></strong>
                                    <br>
                                    <small style="color: #666;"><?= number_format($tamanio, 2) ?> KB</small>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <small style="display: block; margin-top: 10px; color: #666;">
                        ✓ Marca los archivos que deseas eliminar
                    </small>
                </div>
            <?php endif; ?>

            <!-- Agregar nuevos archivos -->
            <div class="form-group" style="margin-bottom: 15px;">
                <label for="archivo_pdf"><strong>Agregar más archivos PDF</strong></label>
                <input
                    type="file"
                    id="archivo_pdf"
                    name="archivo_pdf[]"
                    class="form-control"
                    accept="application/pdf"
                    multiple
                >
                <small style="display:block; margin-top:6px; color:#666;">
                    Solo se permiten archivos PDF. Tamaño máximo: 20MB por archivo.
                </small>
            </div>

            <!-- Botones -->
            <div style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="submit" class="btn btn-success">
                    💾 Guardar cambios
                </button>
                <a href="<?= PUBLIC_URL ?>index.php?url=teen" class="btn btn-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
