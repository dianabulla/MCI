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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive nehemias-table-wrap">
                <table class="table table-hover table-no-card nehemias-table nehemias-table-main">
                    <thead>
                        <tr>
                            <th>Nombres</th>
                            <th>Apellidos</th>
                            <th>Cedula</th>
                            <th>Telefono</th>
                            <th>Lider</th>
                            <th>Lider Nehemias</th>
                            <th>Subido link</th>
                            <th>En Bogota se le subio</th>
                            <th>Puesto de votacion</th>
                            <th>Mesa de votacion</th>
                            <th>Acepta</th>
                            <th>Fecha Registro</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($registros)): ?>
                            <?php foreach ($registros as $registro): ?>
                                <tr>
                                    <td data-label="Nombres"><?= htmlspecialchars($registro['Nombres']) ?></td>
                                    <td data-label="Apellidos"><?= htmlspecialchars($registro['Apellidos']) ?></td>
                                    <td data-label="Cedula"><?= htmlspecialchars($registro['Numero_Cedula']) ?></td>
                                    <td data-label="Telefono"><?= htmlspecialchars($registro['Telefono']) ?></td>
                                    <td data-label="Lider"><?= htmlspecialchars($registro['Lider']) ?></td>
                                    <td data-label="Lider Nehemias"><?= htmlspecialchars($registro['Lider_Nehemias']) ?></td>
                                    <td data-label="Subido link"><?= htmlspecialchars($registro['Subido_Link'] ?? '') ?></td>
                                    <td data-label="En Bogota se le subio"><?= htmlspecialchars($registro['En_Bogota_Subio'] ?? '') ?></td>
                                    <td data-label="Puesto de votacion"><?= htmlspecialchars($registro['Puesto_Votacion'] ?? '') ?></td>
                                    <td data-label="Mesa de votacion"><?= htmlspecialchars($registro['Mesa_Votacion'] ?? '') ?></td>
                                    <td data-label="Acepta"><?= $registro['Acepta'] ? 'Si' : 'No' ?></td>
                                    <td data-label="Fecha Registro"><?= htmlspecialchars($registro['Fecha_Registro']) ?></td>
                                    <td data-label="Acciones">
                                        <?php
                                            $telefonoRaw = (string)($registro['Telefono'] ?? '');
                                            $telefonoDigits = preg_replace('/\D+/', '', $telefonoRaw);
                                            $telefonoWhatsapp = '';

                                            if (preg_match('/^3\d{9}$/', $telefonoDigits)) {
                                                $telefonoWhatsapp = '57' . $telefonoDigits;
                                            } elseif (preg_match('/^57(3\d{9})$/', $telefonoDigits)) {
                                                $telefonoWhatsapp = $telefonoDigits;
                                            } elseif (strlen($telefonoDigits) >= 10 && strlen($telefonoDigits) <= 15) {
                                                $telefonoWhatsapp = $telefonoDigits;
                                            }

                                            $mensajeWhatsapp = urlencode('Hola ' . (string)($registro['Nombres'] ?? '') . ', te escribimos desde MCI Madrid.');
                                        ?>
                                        <a class="btn btn-sm btn-edit" href="?url=nehemias/editar&id=<?= $registro['Id_Nehemias'] ?>">
                                            Editar
                                        </a>
                                        <?php if ($telefonoWhatsapp !== ''): ?>
                                            <a class="btn btn-sm btn-success" target="_blank" rel="noopener"
                                               href="https://wa.me/<?= htmlspecialchars($telefonoWhatsapp) ?>?text=<?= $mensajeWhatsapp ?>">
                                                WhatsApp
                                            </a>
                                        <?php endif; ?>
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

<?php include VIEWS . '/layout/footer.php'; ?>
