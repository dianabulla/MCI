<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Material para Células (PDF)</h2>
    <a href="<?= PUBLIC_URL ?>?url=celulas" class="btn btn-secondary">Volver a Células</a>
</div>

<?php if (!empty($mensaje ?? '')): ?>
    <?php $esSuccess = ($tipo ?? '') === 'success'; ?>
    <div class="alert alert-<?= $esSuccess ? 'success' : 'danger' ?>" style="margin-bottom: 16px;">
        <?= htmlspecialchars((string)$mensaje) ?>
    </div>
<?php endif; ?>

<div class="form-container" style="margin-bottom: 20px;">
    <h3 style="margin-top:0;">Subir nuevo material (PDF)</h3>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="material_pdf">Archivo PDF</label>
            <input type="file" id="material_pdf" name="material_pdf" class="form-control" accept="application/pdf,.pdf" required>
            <small style="display:block; margin-top:8px; color:#666;">Tamaño máximo: 20MB.</small>
        </div>
        <button type="submit" class="btn btn-primary">Subir PDF</button>
    </form>
</div>

<div class="table-container">
    <h3 style="margin-top:0;">Materiales disponibles</h3>

    <?php if (!empty($materiales ?? [])): ?>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th style="width:140px;">Tamaño (KB)</th>
                    <th style="width:190px;">Fecha</th>
                    <th style="width:200px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materiales as $material): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$material['nombre_archivo']) ?></td>
                        <td><?= number_format((float)$material['peso_kb'], 2) ?></td>
                        <td>
                            <?php
                            $ts = (int)($material['fecha_modificacion'] ?? 0);
                            echo $ts > 0 ? date('Y-m-d H:i', $ts) : '—';
                            ?>
                        </td>
                        <td style="display:flex; gap:8px; flex-wrap:wrap;">
                            <a href="<?= htmlspecialchars((string)$material['url']) ?>" target="_blank" class="btn btn-sm btn-success">Ver PDF</a>
                            <form method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este PDF?');" style="margin:0;">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="archivo" value="<?= htmlspecialchars((string)$material['nombre_archivo'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="margin:0; color:#666;">Aún no hay materiales PDF cargados.</p>
    <?php endif; ?>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
