<?php include VIEWS . '/layout/header.php'; ?>

<?php
$exportParams = [
    'url' => 'nehemias/seremos1200/exportarExcel'
];

if (!empty($filtros['busqueda'])) {
    $exportParams['busqueda'] = (string)$filtros['busqueda'];
}
if (($filtros['decision'] ?? '') !== '') {
    $exportParams['decision'] = (string)$filtros['decision'];
}
if (($filtros['migrado'] ?? '') !== '') {
    $exportParams['migrado'] = (string)$filtros['migrado'];
}
if (($filtros['lider'] ?? '') !== '') {
    $exportParams['lider'] = (string)$filtros['lider'];
}

$exportUrl = '?' . http_build_query($exportParams);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center module-header-card">
        <h2 class="page-title">
            <i class="bi bi-people-fill"></i> Nehemias - Seremos 1200
        </h2>
        <div class="d-flex gap-2">
            <a href="<?= htmlspecialchars($exportUrl) ?>" class="btn btn-success">
                <i class="bi bi-file-earmark-excel"></i> Exportar Excel
            </a>
            <a href="?url=nehemias/lista" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Nehemias
            </a>
        </div>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= htmlspecialchars($tipo === 'error' ? 'danger' : ($tipo === 'success' ? 'success' : 'info')) ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <div class="upload-box">
        <form method="POST" action="?url=nehemias/seremos1200/importar" enctype="multipart/form-data" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label fw-bold">Cargar archivo Excel/CSV para Seremos 1200</label>
                <input type="file" name="archivo" class="form-control" accept=".xlsx,.csv,.txt" required>
                <small class="text-muted">Columnas esperadas (acepta variantes): Nombres/Nombre, Apellidos/Apellido, Cedula/Documento, Telefono/Celular, Lider Nehemias, Lider/Ministerio, Subido Link, En Bogota, Puesto, Mesa.</small>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-upload"></i> Importar a Seremos 1200
                </button>
            </div>
        </form>
    </div>

    <div class="filter-box">
        <form method="GET" action="<?= PUBLIC_URL ?>" class="row g-2 align-items-end">
            <input type="hidden" name="url" value="nehemias/seremos1200">
            <div class="col-md-5">
                <label class="form-label mb-1 fw-bold">Búsqueda rápida</label>
                <input
                    type="text"
                    name="busqueda"
                    class="form-control"
                    value="<?= htmlspecialchars($filtros['busqueda'] ?? '') ?>"
                    placeholder="Nombre, cédula, teléfono, líder..."
                >
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1 fw-bold">Estado</label>
                <select name="decision" class="form-select">
                    <option value="" <?= (($filtros['decision'] ?? '') === '') ? 'selected' : '' ?>>Todos</option>
                    <option value="pendiente" <?= (($filtros['decision'] ?? '') === 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                    <option value="1" <?= (($filtros['decision'] ?? '') === '1') ? 'selected' : '' ?>>Sí acepta</option>
                    <option value="0" <?= (($filtros['decision'] ?? '') === '0') ? 'selected' : '' ?>>No acepta</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1 fw-bold">Líder</label>
                <select name="lider" class="form-select">
                    <?php if (empty($filtroLiderRestringido)): ?>
                        <option value="" <?= (($filtros['lider'] ?? '') === '') ? 'selected' : '' ?>>Todos</option>
                    <?php else: ?>
                        <option value="" <?= (($filtros['lider'] ?? '') === '') ? 'selected' : '' ?>>Seleccione</option>
                    <?php endif; ?>
                    <?php foreach (($lideres ?? []) as $lider): ?>
                        <option value="<?= htmlspecialchars($lider) ?>" <?= (($filtros['lider'] ?? '') === $lider) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lider) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label mb-1 fw-bold">Migrado</label>
                <select name="migrado" class="form-select">
                    <option value="" <?= (($filtros['migrado'] ?? '') === '') ? 'selected' : '' ?>>Todos</option>
                    <option value="1" <?= (($filtros['migrado'] ?? '') === '1') ? 'selected' : '' ?>>Sí</option>
                    <option value="0" <?= (($filtros['migrado'] ?? '') === '0') ? 'selected' : '' ?>>No</option>
                </select>
            </div>
            <div class="col-md-2 d-grid gap-2">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="?url=nehemias/seremos1200" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive nehemias-table-wrap">
                <table class="table table-hover table-no-card nehemias-table nehemias-table-secondary">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombres</th>
                            <th>Apellidos</th>
                            <th>Cédula</th>
                            <th>Teléfono</th>
                            <th>Líder</th>
                            <th>Líder Nehemias</th>
                            <th>Estado</th>
                            <th>Migrado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($registros)): ?>
                            <?php foreach ($registros as $registro): ?>
                                <?php
                                $decision = $registro['Decision_Acepta'];
                                $migrado = (int)($registro['Fue_Migrado_Nehemias'] ?? 0) === 1;
                                ?>
                                <tr>
                                    <td data-label="ID"><?= (int)$registro['Id_Seremos1200'] ?></td>
                                    <td data-label="Nombres"><?= htmlspecialchars($registro['Nombres'] ?? '') ?></td>
                                    <td data-label="Apellidos"><?= htmlspecialchars($registro['Apellidos'] ?? '') ?></td>
                                    <td data-label="Cédula"><?= htmlspecialchars($registro['Numero_Cedula'] ?? '') ?></td>
                                    <td data-label="Teléfono"><?= htmlspecialchars($registro['Telefono'] ?? '') ?></td>
                                    <td data-label="Líder"><?= htmlspecialchars($registro['Lider'] ?? '') ?></td>
                                    <td data-label="Líder Nehemias"><?= htmlspecialchars($registro['Lider_Nehemias'] ?? '') ?></td>
                                    <td data-label="Estado">
                                        <?php if ($decision === null): ?>
                                            <span class="status-pill status-pending">Pendiente</span>
                                        <?php elseif ((int)$decision === 1): ?>
                                            <span class="status-pill status-yes">Sí acepta</span>
                                        <?php else: ?>
                                            <span class="status-pill status-no">No acepta</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Migrado">
                                        <?= $migrado ? '<span class="status-pill status-yes">Sí</span>' : '<span class="status-pill status-pending">No</span>' ?>
                                    </td>
                                    <td data-label="Acción">
                                        <div class="d-flex gap-1">
                                            <form method="POST" action="?url=nehemias/seremos1200/decision" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= (int)$registro['Id_Seremos1200'] ?>">
                                                <input type="hidden" name="acepta" value="1">
                                                <button type="submit" class="btn btn-success btn-action" <?= $migrado ? 'disabled' : '' ?>>
                                                    Sí acepta
                                                </button>
                                            </form>
                                            <form method="POST" action="?url=nehemias/seremos1200/decision" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= (int)$registro['Id_Seremos1200'] ?>">
                                                <input type="hidden" name="acepta" value="0">
                                                <button type="submit" class="btn btn-danger btn-action">
                                                    No acepta
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">No hay registros en Seremos 1200.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
