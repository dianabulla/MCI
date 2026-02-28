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
                        <label class="form-label fw-bold">Modo de envío</label>
                        <?php $modoEnvio = $post_data['modo_envio'] ?? 'libre'; ?>
                        <select name="modo_envio" id="modo_envio" class="form-select">
                            <option value="libre" <?= $modoEnvio === 'libre' ? 'selected' : '' ?>>Mensaje libre (no plantilla)</option>
                            <option value="template" <?= $modoEnvio === 'template' ? 'selected' : '' ?>>Template Meta / 360dialog</option>
                        </select>
                    </div>

                    <div class="col-md-4" data-bloque="modo-libre">
                        <label class="form-label fw-bold">Tipo de mensaje</label>
                        <?php $tipoMensaje = $post_data['tipo_mensaje'] ?? 'texto'; ?>
                        <select name="tipo_mensaje" class="form-select">
                            <option value="texto" <?= $tipoMensaje === 'texto' ? 'selected' : '' ?>>Texto</option>
                            <option value="imagen" <?= $tipoMensaje === 'imagen' ? 'selected' : '' ?>>Imagen</option>
                            <option value="video" <?= $tipoMensaje === 'video' ? 'selected' : '' ?>>Video</option>
                            <option value="documento" <?= $tipoMensaje === 'documento' ? 'selected' : '' ?>>Documento</option>
                        </select>
                    </div>

                    <div class="col-md-4" data-bloque="modo-template">
                        <label class="form-label fw-bold">Template name *</label>
                        <input type="text" name="template_nombre" id="template_nombre" class="form-control"
                               value="<?= htmlspecialchars($post_data['template_nombre'] ?? '') ?>"
                               placeholder="ej: bienvenida_nehemias">
                    </div>

                    <div class="col-md-4" data-bloque="modo-template">
                        <label class="form-label fw-bold">Idioma template *</label>
                        <input type="text" name="template_idioma" id="template_idioma" class="form-control"
                               value="<?= htmlspecialchars($post_data['template_idioma'] ?? 'es') ?>"
                               placeholder="es o es_CO">
                    </div>

                    <div class="col-md-8" data-bloque="modo-libre">
                        <label class="form-label fw-bold">Media URL (opcional)</label>
                        <input type="url" name="media_url" class="form-control"
                               value="<?= htmlspecialchars($post_data['media_url'] ?? '') ?>"
                               placeholder="https://...">
                    </div>

                    <div class="col-md-12" data-bloque="modo-libre">
                        <label class="form-label fw-bold">Mensaje *</label>
                        <textarea name="cuerpo" id="cuerpo_mensaje" class="form-control" rows="5"
                                  placeholder="Puedes usar variables en futuras versiones, ej: {{nombres}}."><?= htmlspecialchars($post_data['cuerpo'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-12" data-bloque="modo-template">
                        <label class="form-label fw-bold">Parámetros del BODY (uno por línea, en orden)</label>
                        <textarea name="template_parametros" class="form-control" rows="5"
                                  placeholder="{{nombres}}&#10;{{lider_nehemias}}&#10;sábado 7:00 PM"><?= htmlspecialchars($post_data['template_parametros'] ?? '') ?></textarea>
                        <small class="text-muted">Usa el orden exacto de variables aprobado en Meta. Ejemplos válidos: {{nombres}}, {{apellidos}}, {{lider}}, {{lider_nehemias}}.</small>
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
                        <label class="form-label fw-bold">Ministerios (Líder) - múltiple</label>
                        <?php $lideresSeleccionados = array_map('strval', $post_data['lideres'] ?? []); ?>
                        <select name="lideres[]" id="filtro_lideres" class="form-select" multiple size="5">
                            <?php foreach (($ministeriosNehemias ?? []) as $ministerio): ?>
                                <option value="<?= htmlspecialchars($ministerio) ?>" <?= in_array((string)$ministerio, $lideresSeleccionados, true) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ministerio) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Mantén Ctrl para seleccionar más de uno (ej: Michle y Sara).</small>
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

                    <div class="col-md-12 mt-3">
                        <hr>
                        <h5 class="mb-2">Destinatarios (Nehemías)</h5>
                        <p class="text-muted" style="margin-bottom: 8px;">
                            Se muestran hasta 500 registros elegibles según filtros. Selecciona exactamente a quiénes se enviará.
                        </p>

                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <input type="text" id="buscar_destinatario" class="form-control" style="max-width: 360px;" placeholder="Buscar por nombre, cédula o líder...">
                            <button type="button" class="btn btn-outline-primary" id="marcar_todos_visibles">Marcar visibles</button>
                            <button type="button" class="btn btn-outline-secondary" id="desmarcar_todos_visibles">Desmarcar visibles</button>
                        </div>

                        <div id="contador_destinatarios" class="text-muted mb-2">Mostrando 0 de 0 | Seleccionados 0</div>

                        <?php $idsSeleccionadosMapa = array_fill_keys(array_map('intval', $ids_seleccionados ?? []), true); ?>
                        <div class="table-responsive" style="max-height: 360px; border: 1px solid #e6e6e6; border-radius: 8px;">
                            <table class="table table-sm table-hover mb-0" id="tabla_destinatarios">
                                <thead style="position: sticky; top: 0; background: #fff; z-index: 1;">
                                    <tr>
                                        <th style="width: 70px;">Sel.</th>
                                        <th>Nombre</th>
                                        <th style="width: 140px;">Cédula</th>
                                        <th style="width: 150px;">Teléfono</th>
                                        <th style="width: 180px;">Líder</th>
                                        <th style="width: 180px;">Líder Nehemías</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($destinatarios_disponibles ?? [])): ?>
                                        <?php foreach ($destinatarios_disponibles as $destinatario): ?>
                                            <?php $idN = (int)($destinatario['Id_Nehemias'] ?? 0); ?>
                                            <?php
                                                $nombreCompleto = trim((string)($destinatario['Nombres'] ?? '') . ' ' . (string)($destinatario['Apellidos'] ?? ''));
                                                $textoBusqueda = strtolower(
                                                    $nombreCompleto . ' ' .
                                                    (string)($destinatario['Numero_Cedula'] ?? '') . ' ' .
                                                    (string)($destinatario['Lider'] ?? '') . ' ' .
                                                    (string)($destinatario['Lider_Nehemias'] ?? '')
                                                );
                                            ?>
                                            <tr data-search="<?= htmlspecialchars($textoBusqueda) ?>" data-lider="<?= htmlspecialchars(strtolower((string)($destinatario['Lider'] ?? ''))) ?>">
                                                <td>
                                                    <input type="checkbox" name="ids_nehemias[]" value="<?= $idN ?>"
                                                           <?= !empty($idsSeleccionadosMapa[$idN]) ? 'checked' : '' ?>>
                                                </td>
                                                <td><?= htmlspecialchars($nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre') ?></td>
                                                <td><?= htmlspecialchars((string)($destinatario['Numero_Cedula'] ?? '')) ?></td>
                                                <td><?= htmlspecialchars((string)($destinatario['Telefono_Normalizado'] ?? '')) ?></td>
                                                <td><?= htmlspecialchars((string)($destinatario['Lider'] ?? '')) ?></td>
                                                <td><?= htmlspecialchars((string)($destinatario['Lider_Nehemias'] ?? '')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No hay destinatarios elegibles con los filtros actuales</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        const selectModo = document.getElementById('modo_envio');
        const templateNombre = document.getElementById('template_nombre');
        const templateIdioma = document.getElementById('template_idioma');
        const cuerpoMensaje = document.getElementById('cuerpo_mensaje');
        const filtroLideres = document.getElementById('filtro_lideres');
        const buscarDestinatario = document.getElementById('buscar_destinatario');
        const tablaDestinatarios = document.getElementById('tabla_destinatarios');
        const contadorDestinatarios = document.getElementById('contador_destinatarios');
        const marcarTodos = document.getElementById('marcar_todos_visibles');
        const desmarcarTodos = document.getElementById('desmarcar_todos_visibles');
        const bloquesLibre = Array.from(document.querySelectorAll('[data-bloque="modo-libre"]'));
        const bloquesTemplate = Array.from(document.querySelectorAll('[data-bloque="modo-template"]'));

        function actualizarContadorDestinatarios() {
            if (!tablaDestinatarios || !contadorDestinatarios) {
                return;
            }

            const filas = Array.from(tablaDestinatarios.querySelectorAll('tbody tr[data-search]'));
            const total = filas.length;

            let visibles = 0;
            let seleccionadosVisibles = 0;
            let seleccionadosTotales = 0;

            filas.forEach(function (fila) {
                const visible = fila.style.display !== 'none';
                if (visible) {
                    visibles++;
                }

                const check = fila.querySelector('input[type="checkbox"]');
                if (check && check.checked) {
                    seleccionadosTotales++;
                    if (visible) {
                        seleccionadosVisibles++;
                    }
                }
            });

            contadorDestinatarios.textContent = 'Mostrando ' + visibles + ' de ' + total + ' | Seleccionados visibles ' + seleccionadosVisibles + ' | Seleccionados totales ' + seleccionadosTotales;
        }

        function actualizarModo() {
            const modo = selectModo ? selectModo.value : 'libre';
            const esTemplate = modo === 'template';

            bloquesLibre.forEach(function (bloque) {
                bloque.style.display = esTemplate ? 'none' : '';
            });

            bloquesTemplate.forEach(function (bloque) {
                bloque.style.display = esTemplate ? '' : 'none';
            });

            if (templateNombre) {
                templateNombre.required = esTemplate;
            }
            if (templateIdioma) {
                templateIdioma.required = esTemplate;
            }
            if (cuerpoMensaje) {
                cuerpoMensaje.required = !esTemplate;
            }
        }

        if (selectModo) {
            selectModo.addEventListener('change', actualizarModo);
        }

        function filtrarDestinatarios() {
            if (!buscarDestinatario || !tablaDestinatarios) {
                return;
            }
            const termino = buscarDestinatario.value.trim().toLowerCase();
            const lideresActivos = filtroLideres
                ? Array.from(filtroLideres.selectedOptions).map(function (opt) { return opt.value.toLowerCase(); })
                : [];
            const filas = Array.from(tablaDestinatarios.querySelectorAll('tbody tr[data-search]'));
            filas.forEach(function (fila) {
                const texto = fila.getAttribute('data-search') || '';
                const liderFila = (fila.getAttribute('data-lider') || '').toLowerCase();
                const pasaBusqueda = texto.indexOf(termino) !== -1;
                const pasaLider = lideresActivos.length === 0 || lideresActivos.indexOf(liderFila) !== -1;
                fila.style.display = (pasaBusqueda && pasaLider) ? '' : 'none';
            });

            actualizarContadorDestinatarios();
        }

        function seleccionarVisibles(seleccionado) {
            if (!tablaDestinatarios) {
                return;
            }
            const filas = Array.from(tablaDestinatarios.querySelectorAll('tbody tr[data-search]'));
            filas.forEach(function (fila) {
                if (fila.style.display === 'none') {
                    return;
                }
                const check = fila.querySelector('input[type="checkbox"]');
                if (check) {
                    check.checked = seleccionado;
                }
            });

            actualizarContadorDestinatarios();
        }

        if (buscarDestinatario) {
            buscarDestinatario.addEventListener('input', filtrarDestinatarios);
        }
        if (filtroLideres) {
            filtroLideres.addEventListener('change', filtrarDestinatarios);
        }
        if (marcarTodos) {
            marcarTodos.addEventListener('click', function () { seleccionarVisibles(true); });
        }
        if (desmarcarTodos) {
            desmarcarTodos.addEventListener('click', function () { seleccionarVisibles(false); });
        }
        if (tablaDestinatarios) {
            tablaDestinatarios.addEventListener('change', function (event) {
                const target = event.target;
                if (target && target.matches('input[type="checkbox"]')) {
                    actualizarContadorDestinatarios();
                }
            });
        }

        actualizarModo();
        filtrarDestinatarios();
        actualizarContadorDestinatarios();
    })();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
