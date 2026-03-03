<?php include VIEWS . '/layout/header.php'; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center module-header-card">
        <h2 class="page-title">
            <i class="bi bi-clipboard-data"></i> Nehemias - Registros
            <?php 
            $totalFiltros = 0;
            foreach ($filtros as $clave => $filtro) {
                if ($clave === 'lider_lista') {
                    continue;
                }
                if (!empty($filtro)) $totalFiltros++;
            }
            if ($totalFiltros > 0): ?>
                <span class="badge-filter"><?= $totalFiltros ?> filtro<?= $totalFiltros > 1 ? 's' : '' ?> activo<?= $totalFiltros > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </h2>
        <div>
            <a href="?url=nehemias/whatsapp-campanas" class="btn btn-success btn-action me-2">
                <i class="bi bi-whatsapp"></i> WhatsApp Campañas
            </a>
            <a href="?url=nehemias/seremos1200" class="btn btn-warning btn-action me-2">
                <i class="bi bi-people"></i> Seremos 1200
            </a>
            <a href="?url=nehemias/exportarExcel" class="btn btn-success btn-action">
                <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
            </a>
            <a href="?url=nehemias/reportes" class="btn btn-primary btn-action ms-2">
                <i class="bi bi-graph-up"></i> Reportes
            </a>
        </div>
    </div>

    <?php if (!empty($mensaje)): ?>
        <?php
            $alertClass = 'alert-info';
            if (($tipo ?? '') === 'success') {
                $alertClass = 'alert-success';
            } elseif (($tipo ?? '') === 'error') {
                $alertClass = 'alert-danger';
            } elseif (($tipo ?? '') === 'warning') {
                $alertClass = 'alert-warning';
            }
        ?>
        <div class="alert <?= $alertClass ?> mt-3" role="alert">
            <?= htmlspecialchars((string)$mensaje) ?>
        </div>
    <?php endif; ?>

    <!-- Formulario de Filtros -->
    <div class="filter-card">
        <div class="filter-title">
            <i class="bi bi-funnel-fill"></i> Filtros de Búsqueda
        </div>
        <form method="GET" action="<?= PUBLIC_URL ?>">
            <input type="hidden" name="url" value="nehemias/lista">
            
            <div class="row g-3">
                <!-- Búsqueda general -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Buscar por Nombre, Apellido o Cédula</label>
                    <input type="text" name="busqueda" class="form-control" 
                           placeholder="Escriba para buscar..." 
                           value="<?= htmlspecialchars($filtros['busqueda']) ?>">
                </div>

                <!-- Líder Nehemías -->
                <div class="col-md-3">
                    <label class="form-label fw-bold">Líder Nehemías</label>
                    <input type="text" name="lider_nehemias" class="form-control" 
                           placeholder="Nombre del líder Nehemías" 
                           value="<?= htmlspecialchars($filtros['lider_nehemias']) ?>">
                </div>

                <!-- Líder (Ministerio) -->
                <div class="col-md-3">
                    <label class="form-label fw-bold">Ministerio</label>
                    <select name="lider" class="form-select">
                        <?php if (empty($filtroLiderRestringido)): ?>
                            <option value="">Todos</option>
                        <?php else: ?>
                            <option value="">Seleccione</option>
                        <?php endif; ?>
                        <?php foreach ($ministeriosNehemias as $ministerio): ?>
                            <option value="<?= htmlspecialchars($ministerio) ?>" <?= $filtros['lider'] === $ministerio ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ministerio) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if (empty($filtroLiderRestringido)): ?>
                            <option value="__otros__" <?= $filtros['lider'] === '__otros__' ? 'selected' : '' ?>>Otros</option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Filtros de checkbox -->
                <div class="col-md-12">
                    <label class="form-label fw-bold">Filtros Rápidos</label>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="puesto_vacio" value="1" 
                                       id="puesto_vacio" <?= !empty($filtros['puesto_vacio']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="puesto_vacio">
                                    Puesto de Votación Vacío
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="puesto_lleno" value="1" 
                                       id="puesto_lleno" <?= !empty($filtros['puesto_lleno']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="puesto_lleno">
                                    Puesto de Votación Completo
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mesa_vacia" value="1" 
                                       id="mesa_vacia" <?= !empty($filtros['mesa_vacia']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="mesa_vacia">
                                    Mesa de Votación Vacía
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mesa_llena" value="1" 
                                       id="mesa_llena" <?= !empty($filtros['mesa_llena']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="mesa_llena">
                                    Mesa de Votación Completa
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="cedula_vacia" value="1" 
                                       id="cedula_vacia" <?= !empty($filtros['cedula_vacia']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="cedula_vacia">
                                    Cédula Vacía
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="subido_link_vacio" value="1" 
                                       id="subido_link_vacio" <?= !empty($filtros['subido_link_vacio']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="subido_link_vacio">
                                    Subido link vacío
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="subido_link_lleno" value="1" 
                                       id="subido_link_lleno" <?= !empty($filtros['subido_link_lleno']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="subido_link_lleno">
                                    Subido link completo
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="bogota_subio_vacio" value="1" 
                                       id="bogota_subio_vacio" <?= !empty($filtros['bogota_subio_vacio']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="bogota_subio_vacio">
                                    En Bogotá se le subió vacío
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="bogota_subio_lleno" value="1" 
                                       id="bogota_subio_lleno" <?= !empty($filtros['bogota_subio_lleno']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="bogota_subio_lleno">
                                    En Bogotá se le subió completo
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3 mt-2">
                            <label class="form-label mb-1" style="font-size: 12px;">Acepta</label>
                            <select name="acepta" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="1" <?= $filtros['acepta'] === '1' ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= $filtros['acepta'] === '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="col-md-12">
                    <button type="submit" class="btn btn-filter">
                        <i class="bi bi-search"></i> Aplicar Filtros
                    </button>
                    <a href="?url=nehemias/lista" class="btn btn-clear">
                        <i class="bi bi-x-circle"></i> Limpiar Filtros
                    </a>
                    <span class="ms-3 text-muted">
                        <strong><?= count($registros) ?></strong> registro<?= count($registros) !== 1 ? 's' : '' ?> encontrado<?= count($registros) !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>
        </form>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <h5 class="mb-2">Columnas visibles</h5>
            <div class="d-flex flex-wrap gap-3" id="column-toggle-wrap">
                <label><input type="checkbox" class="col-toggle" data-col="nombres" checked> Nombres</label>
                <label><input type="checkbox" class="col-toggle" data-col="apellidos" checked> Apellidos</label>
                <label><input type="checkbox" class="col-toggle" data-col="cedula" checked> Cédula</label>
                <label><input type="checkbox" class="col-toggle" data-col="telefono" checked> Teléfono</label>
                <label><input type="checkbox" class="col-toggle" data-col="lider" checked> Líder</label>
                <label><input type="checkbox" class="col-toggle" data-col="lider-nehemias" checked> Líder Nehemías</label>
                <label><input type="checkbox" class="col-toggle" data-col="subido-link" checked> Subido link</label>
                <label><input type="checkbox" class="col-toggle" data-col="bogota-subio" checked> En Bogotá se le subió</label>
                <label><input type="checkbox" class="col-toggle" data-col="puesto" checked> Puesto</label>
                <label><input type="checkbox" class="col-toggle" data-col="mesa" checked> Mesa</label>
                <label><input type="checkbox" class="col-toggle" data-col="acepta" checked> Acepta</label>
                <label><input type="checkbox" class="col-toggle" data-col="fecha" checked> Fecha</label>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive nehemias-table-wrap">
                <table class="table table-hover table-no-card nehemias-table nehemias-table-main" id="nehemias-edit-table">
                    <thead>
                        <tr>
                            <th class="col-nombres">Nombres</th>
                            <th class="col-apellidos">Apellidos</th>
                            <th class="col-cedula">Cedula</th>
                            <th class="col-telefono">Telefono</th>
                            <th class="col-lider">Lider</th>
                            <th class="col-lider-nehemias">Lider Nehemias</th>
                            <th class="col-subido-link">Subido link</th>
                            <th class="col-bogota-subio">En Bogota se le subio</th>
                            <th class="col-puesto">Puesto de votacion</th>
                            <th class="col-mesa">Mesa de votacion</th>
                            <th class="col-acepta">Acepta</th>
                            <th class="col-fecha">Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($registros)): ?>
                            <?php foreach ($registros as $registro): ?>
                                <tr>
                                    <td class="col-nombres" data-label="Nombres"><?= htmlspecialchars($registro['Nombres']) ?></td>
                                    <td class="col-apellidos" data-label="Apellidos"><?= htmlspecialchars($registro['Apellidos']) ?></td>
                                    <td class="col-cedula" data-label="Cedula"><?= htmlspecialchars($registro['Numero_Cedula']) ?></td>
                                    <td class="col-telefono" data-label="Telefono"><?= htmlspecialchars($registro['Telefono']) ?></td>
                                    <td class="col-lider" data-label="Lider"><?= htmlspecialchars($registro['Lider']) ?></td>
                                    <td class="col-lider-nehemias" data-label="Lider Nehemias"><?= htmlspecialchars($registro['Lider_Nehemias']) ?></td>
                                    <td class="col-subido-link" data-label="Subido link"><?= htmlspecialchars($registro['Subido_Link'] ?? '') ?></td>
                                    <td class="col-bogota-subio" data-label="En Bogota se le subio"><?= htmlspecialchars($registro['En_Bogota_Subio'] ?? '') ?></td>
                                    <td class="col-puesto" data-label="Puesto de votacion"><?= htmlspecialchars($registro['Puesto_Votacion'] ?? '') ?></td>
                                    <td class="col-mesa" data-label="Mesa de votacion"><?= htmlspecialchars($registro['Mesa_Votacion'] ?? '') ?></td>
                                    <td class="col-acepta" data-label="Acepta"><?= ((int)($registro['Acepta'] ?? 0) === 1) ? 'Si' : 'No' ?></td>
                                    <td class="col-fecha" data-label="Fecha Registro"><?= htmlspecialchars($registro['Fecha_Registro']) ?></td>
                                    <td data-label="Acciones">
                                        <?php
                                            $registroPayload = [
                                                'id' => (int)$registro['Id_Nehemias'],
                                                'nombres' => (string)($registro['Nombres'] ?? ''),
                                                'apellidos' => (string)($registro['Apellidos'] ?? ''),
                                                'numero_cedula' => (string)($registro['Numero_Cedula'] ?? ''),
                                                'telefono' => (string)($registro['Telefono'] ?? ''),
                                                'telefono_normalizado' => (string)($registro['Telefono_Normalizado'] ?? ''),
                                                'lider' => (string)($registro['Lider'] ?? ''),
                                                'lider_nehemias' => (string)($registro['Lider_Nehemias'] ?? ''),
                                                'subido_link' => (string)($registro['Subido_Link'] ?? ''),
                                                'en_bogota_subio' => (string)($registro['En_Bogota_Subio'] ?? ''),
                                                'puesto_votacion' => (string)($registro['Puesto_Votacion'] ?? ''),
                                                'mesa_votacion' => (string)($registro['Mesa_Votacion'] ?? ''),
                                                'acepta' => (int)($registro['Acepta'] ?? 0),
                                                'consentimiento_whatsapp' => (int)($registro['Consentimiento_Whatsapp'] ?? 0),
                                                'fecha_registro' => (string)($registro['Fecha_Registro'] ?? ''),
                                            ];
                                            $registroPayloadJson = htmlspecialchars(json_encode($registroPayload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <button type="button" class="btn btn-sm btn-edit btn-open-edit-modal" data-record="<?= $registroPayloadJson ?>">
                                            Editar
                                        </button>
                                        <form method="POST" action="?url=nehemias/eliminar" class="d-inline" onsubmit="return confirm('¿Seguro que quieres eliminar este registro?');">
                                            <input type="hidden" name="id" value="<?= (int)$registro['Id_Nehemias'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="13" class="text-center">No hay registros.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    #nehemias-edit-table {
        table-layout: auto !important;
        width: 100%;
    }

    #nehemias-edit-table th,
    #nehemias-edit-table td {
        white-space: nowrap;
    }

    #modalEditarNehemias .field-modified {
        background-color: #fffde7;
        border-color: #f4e58a;
    }

    #modalEditarNehemias {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(17, 24, 39, 0.5);
        z-index: 2000;
        padding: 16px;
    }

    #modalEditarNehemias.show {
        display: flex;
    }

    #modalEditarNehemias .custom-modal-dialog {
        width: min(1100px, 100%);
        max-height: calc(100vh - 32px);
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    #modalEditarNehemias .custom-modal-header,
    #modalEditarNehemias .custom-modal-footer {
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    #modalEditarNehemias .custom-modal-footer {
        border-bottom: 0;
        border-top: 1px solid #e5e7eb;
        justify-content: flex-end;
        gap: 8px;
    }

    #modalEditarNehemias .custom-modal-body {
        padding: 16px;
        overflow-y: auto;
        max-height: calc(100vh - 190px);
    }

    #modalEditarNehemias .custom-modal-close {
        border: 0;
        background: transparent;
        font-size: 20px;
        line-height: 1;
        cursor: pointer;
    }
</style>

<div id="modalEditarNehemias" aria-hidden="true">
    <div class="custom-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalEditarTitulo">
            <form method="POST" action="?url=nehemias/actualizar" id="formEditarNehemiasModal">
                <div class="custom-modal-header">
                    <h5 class="modal-title" id="modalEditarTitulo">Editar registro Nehemias</h5>
                    <button type="button" class="custom-modal-close" id="btnCloseModalNehemias" aria-label="Cerrar">&times;</button>
                </div>
                <div class="custom-modal-body">
                    <input type="hidden" name="id" id="edit-id">

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">ID</label>
                            <input type="text" class="form-control" id="edit-id-readonly" disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nombres</label>
                            <input type="text" class="form-control modal-editable" name="nombres" id="edit-nombres">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellidos</label>
                            <input type="text" class="form-control modal-editable" name="apellidos" id="edit-apellidos">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Numero de Cedula</label>
                            <input type="text" class="form-control modal-editable" name="numero_cedula" id="edit-numero-cedula">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefono</label>
                            <input type="text" class="form-control modal-editable" name="telefono" id="edit-telefono">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefono normalizado</label>
                            <input type="text" class="form-control" id="edit-telefono-normalizado" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Acepta</label>
                            <select class="form-select modal-editable" name="acepta" id="edit-acepta">
                                <option value="1">Si</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Consentimiento WhatsApp</label>
                            <select class="form-select modal-editable" name="consentimiento_whatsapp" id="edit-consentimiento-whatsapp">
                                <option value="1">Si</option>
                                <option value="0">No</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ministerio (Lider)</label>
                            <select class="form-select modal-editable" name="lider" id="edit-lider">
                                <option value="">Seleccione</option>
                                <?php foreach ($ministeriosNehemias as $ministerio): ?>
                                    <option value="<?= htmlspecialchars($ministerio) ?>"><?= htmlspecialchars($ministerio) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lider Nehemias</label>
                            <input type="text" class="form-control modal-editable" name="lider_nehemias" id="edit-lider-nehemias">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Subido link</label>
                            <input type="text" class="form-control modal-editable" name="subido_link" id="edit-subido-link">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">En Bogota se le subio</label>
                            <input type="text" class="form-control modal-editable" name="en_bogota_subio" id="edit-en-bogota-subio">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Puesto de votacion</label>
                            <input type="text" class="form-control modal-editable" name="puesto_votacion" id="edit-puesto-votacion">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mesa de votacion</label>
                            <input type="text" class="form-control modal-editable" name="mesa_votacion" id="edit-mesa-votacion">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fecha Registro</label>
                            <input type="text" class="form-control modal-editable" name="fecha_registro" id="edit-fecha-registro">
                        </div>
                    </div>
                </div>
                <div class="custom-modal-footer">
                    <button type="button" class="btn btn-secondary" id="btnCancelarModalNehemias">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('modalEditarNehemias');
        var btnCloseModal = document.getElementById('btnCloseModalNehemias');
        var btnCancelarModal = document.getElementById('btnCancelarModalNehemias');

        function openModal() {
            if (!modal) return;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            if (!modal) return;
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        if (btnCloseModal) {
            btnCloseModal.addEventListener('click', closeModal);
        }
        if (btnCancelarModal) {
            btnCancelarModal.addEventListener('click', closeModal);
        }
        if (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
        }

        function applyColumnVisibility(columnKey, isVisible) {
            var cells = document.querySelectorAll('.col-' + columnKey);
            cells.forEach(function (cell) {
                cell.style.display = isVisible ? '' : 'none';
            });
        }

        document.querySelectorAll('.col-toggle').forEach(function (toggle) {
            var columnKey = toggle.getAttribute('data-col') || '';
            var storageKey = 'nehemias_cols_' + columnKey;
            var saved = localStorage.getItem(storageKey);
            if (saved === '0') {
                toggle.checked = false;
            }

            applyColumnVisibility(columnKey, toggle.checked);

            toggle.addEventListener('change', function () {
                applyColumnVisibility(columnKey, toggle.checked);
                localStorage.setItem(storageKey, toggle.checked ? '1' : '0');
            });
        });

        function setInputValue(id, value) {
            var input = document.getElementById(id);
            if (!input) return;
            input.value = value == null ? '' : value;
            input.setAttribute('data-original', input.value);
            input.classList.remove('field-modified');
        }

        function ensureLiderOption(value) {
            var select = document.getElementById('edit-lider');
            if (!select) return;
            var v = value == null ? '' : String(value);
            var exists = false;
            Array.prototype.forEach.call(select.options, function (opt) {
                if (opt.value === v) {
                    exists = true;
                }
            });
            if (!exists && v !== '') {
                var option = document.createElement('option');
                option.value = v;
                option.textContent = v + ' (actual)';
                option.setAttribute('data-temp', '1');
                select.appendChild(option);
            }
            select.value = v;
            select.setAttribute('data-original', select.value);
            select.classList.remove('field-modified');
        }

        function clearTempLiderOptions() {
            var select = document.getElementById('edit-lider');
            if (!select) return;
            Array.prototype.slice.call(select.querySelectorAll('option[data-temp="1"]')).forEach(function (opt) {
                opt.remove();
            });
        }

        document.querySelectorAll('.btn-open-edit-modal').forEach(function (button) {
            button.addEventListener('click', function () {
                clearTempLiderOptions();
                var raw = button.getAttribute('data-record') || '{}';
                var record = {};
                try {
                    record = JSON.parse(raw);
                } catch (e) {
                    record = {};
                }

                setInputValue('edit-id', record.id || '');
                setInputValue('edit-id-readonly', record.id || '');
                setInputValue('edit-nombres', record.nombres || '');
                setInputValue('edit-apellidos', record.apellidos || '');
                setInputValue('edit-numero-cedula', record.numero_cedula || '');
                setInputValue('edit-telefono', record.telefono || '');
                setInputValue('edit-telefono-normalizado', record.telefono_normalizado || '');
                ensureLiderOption(record.lider || '');
                setInputValue('edit-lider-nehemias', record.lider_nehemias || '');
                setInputValue('edit-subido-link', record.subido_link || '');
                setInputValue('edit-en-bogota-subio', record.en_bogota_subio || '');
                setInputValue('edit-puesto-votacion', record.puesto_votacion || '');
                setInputValue('edit-mesa-votacion', record.mesa_votacion || '');
                setInputValue('edit-fecha-registro', record.fecha_registro || '');
                setInputValue('edit-acepta', String(record.acepta === 1 ? 1 : 0));
                setInputValue('edit-consentimiento-whatsapp', String(record.consentimiento_whatsapp === 1 ? 1 : 0));
                openModal();
            });
        });

        document.querySelectorAll('#formEditarNehemiasModal .modal-editable').forEach(function (input) {
            input.addEventListener('input', function () {
                var original = input.getAttribute('data-original') || '';
                if (input.value !== original) {
                    input.classList.add('field-modified');
                } else {
                    input.classList.remove('field-modified');
                }
            });
            input.addEventListener('change', function () {
                var original = input.getAttribute('data-original') || '';
                if (input.value !== original) {
                    input.classList.add('field-modified');
                } else {
                    input.classList.remove('field-modified');
                }
            });
        });
    })();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
