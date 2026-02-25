<?php include VIEWS . '/layout/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center module-header-card">
        <h2 class="page-title">
            <i class="bi bi-whatsapp"></i> Nueva Campaña WhatsApp
        </h2>
        <a href="?url=nehemias/whatsapp-campanas" class="btn btn-secondary btn-action">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <?php if (!empty($error)): ?>
    <div class="alert alert-danger" style="margin-top: 15px;">
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <div class="card mt-3">
        <div class="card-body">
            <form method="POST" action="?url=nehemias/whatsapp-campanas/crear">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Nombre de campaña *</label>
                        <input type="text" name="nombre" class="form-control" required
                               value="<?= htmlspecialchars($post_data['nombre'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold">Fecha y hora programada *</label>
                        <input type="datetime-local" name="fecha_programada" class="form-control" required
                               value="<?= htmlspecialchars($post_data['fecha_programada'] ?? '') ?>">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">Objetivo</label>
                        <input type="text" name="objetivo" class="form-control"
                               value="<?= htmlspecialchars($post_data['objetivo'] ?? '') ?>"
                               placeholder="Ej: Convocar reunión del sábado">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Tipo de mensaje</label>
                        <?php $tipoMensaje = $post_data['tipo_mensaje'] ?? 'texto'; ?>
                        <select name="tipo_mensaje" class="form-select">
                            <option value="texto" <?= $tipoMensaje === 'texto' ? 'selected' : '' ?>>Texto</option>
                            <option value="imagen" <?= $tipoMensaje === 'imagen' ? 'selected' : '' ?>>Imagen</option>
                            <option value="video" <?= $tipoMensaje === 'video' ? 'selected' : '' ?>>Video</option>
                            <option value="documento" <?= $tipoMensaje === 'documento' ? 'selected' : '' ?>>Documento</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold">Media URL (opcional)</label>
                        <input type="url" name="media_url" class="form-control"
                               value="<?= htmlspecialchars($post_data['media_url'] ?? '') ?>"
                               placeholder="https://...">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label fw-bold">Mensaje *</label>
                        <textarea name="cuerpo" class="form-control" rows="5" required
                                  placeholder="Puedes usar variables en futuras versiones, ej: {{nombres}}."><?= htmlspecialchars($post_data['cuerpo'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Límite por lote</label>
                        <input type="number" name="limite_lote" min="10" max="1000" class="form-control"
                               value="<?= htmlspecialchars($post_data['limite_lote'] ?? '100') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Pausa entre lotes (segundos)</label>
                        <input type="number" name="pausa_segundos" min="1" max="120" class="form-control"
                               value="<?= htmlspecialchars($post_data['pausa_segundos'] ?? '5') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Ministerio (Líder)</label>
                        <?php $liderSeleccionado = $post_data['lider'] ?? ''; ?>
                        <select name="lider" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach (($ministeriosNehemias ?? []) as $ministerio): ?>
                                <option value="<?= htmlspecialchars($ministerio) ?>" <?= $liderSeleccionado === $ministerio ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ministerio) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold">Filtro líder Nehemías (contiene)</label>
                        <input type="text" name="lider_nehemias" class="form-control"
                               value="<?= htmlspecialchars($post_data['lider_nehemias'] ?? '') ?>"
                               placeholder="Ej: Juan">
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="consentimiento_whatsapp" value="1" id="consentimiento_whatsapp"
                                   <?= isset($post_data) ? (!empty($post_data['consentimiento_whatsapp']) ? 'checked' : '') : 'checked' ?>>
                            <label class="form-check-label" for="consentimiento_whatsapp">
                                Requerir consentimiento WhatsApp
                            </label>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-save"></i> Guardar campaña
                        </button>
                        <a href="?url=nehemias/whatsapp-campanas" class="btn btn-secondary">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
