<?php include VIEWS . '/layout/header.php'; ?>

<style>
    .card {
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .page-header {
        background: #ffffff;
        border-radius: 16px;
        padding: 16px 20px;
        box-shadow: 0 6px 20px rgba(11, 58, 138, 0.12);
        border: 1px solid #eef1f6;
    }
    .page-title {
        margin: 0;
        font-weight: 700;
        color: #0078D4;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .page-title i {
        background: #0078D4;
        color: #fff;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .table th {
        background: #0078D4;
        color: white;
        font-weight: 600;
        padding: 8px 10px;
        border: none;
        white-space: normal;
        font-size: 12px;
    }
    .table td {
        padding: 8px 10px;
        vertical-align: top;
        white-space: normal;
        word-break: break-word;
        font-size: 12px;
    }
    .table {
        table-layout: fixed;
        border-radius: 12px;
        overflow: hidden;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        transition: background-color 0.2s;
    }
    .btn-sm {
        padding: 6px 12px;
        border-radius: 16px;
        font-weight: 600;
        font-size: 12px;
    }
    .btn-action {
        border-radius: 999px;
        padding: 6px 14px;
        font-weight: 700;
        letter-spacing: 0.2px;
        box-shadow: 0 6px 16px rgba(25, 135, 84, 0.2);
    }
    .btn-edit {
        background: #0078D4;
        border-color: #0078D4;
        color: #fff;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 12px;
        line-height: 1;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 64px;
    }
    .btn-edit:hover {
        background: #005BA1;
        border-color: #005BA1;
        color: #fff;
    }
    .filter-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #dee2e6;
    }
    .filter-title {
        color: #0078D4;
        font-weight: 700;
        font-size: 16px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .filter-title i {
        font-size: 18px;
    }
    .form-check-label {
        font-size: 13px;
        color: #495057;
    }
    .btn-filter {
        background: #0078D4;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        font-weight: 600;
    }
    .btn-filter:hover {
        background: #005BA1;
        color: white;
    }
    .btn-clear {
        background: #6c757d;
        color: white;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        font-weight: 600;
    }
    .btn-clear:hover {
        background: #5a6268;
        color: white;
    }
    .badge-filter {
        background: #0078D4;
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        margin-left: 8px;
    }
    @media (max-width: 768px) {
        .table thead {
            display: none;
        }
        .table tbody tr {
            display: block;
            margin-bottom: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
        }
        .table tbody td {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        .table tbody td::before {
            content: attr(data-label);
            font-weight: 700;
            color: #0b3a8a;
            min-width: 120px;
        }
        .table tbody td:last-child {
            border-bottom: none;
        }
        .table-responsive {
            overflow: visible;
        }
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4 page-header">
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
                        <option value="">Todos</option>
                        <?php foreach ($ministeriosNehemias as $ministerio): ?>
                            <option value="<?= htmlspecialchars($ministerio) ?>" <?= $filtros['lider'] === $ministerio ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ministerio) ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="__otros__" <?= $filtros['lider'] === '__otros__' ? 'selected' : '' ?>>Otros</option>
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
            <div class="table-responsive">
                <table class="table table-hover">
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
                                        <a class="btn btn-sm btn-edit" href="?url=nehemias/editar&id=<?= $registro['Id_Nehemias'] ?>">
                                            Editar
                                        </a>
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
