<?php include VIEWS . '/layout/header.php'; ?>

<style>
    .card {
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        margin-bottom: 20px;
    }
    .page-header {
        background: #ffffff;
        border-radius: 16px;
        padding: 16px 20px;
        box-shadow: 0 6px 20px rgba(11, 58, 138, 0.12);
        border: 1px solid #eef1f6;
        margin-bottom: 20px;
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
        white-space: nowrap;
        font-size: 12px;
    }
    .table td {
        padding: 8px 10px;
        vertical-align: middle;
        font-size: 12px;
    }
    .status-pill {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
    }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-yes { background: #d1e7dd; color: #0f5132; }
    .status-no { background: #f8d7da; color: #842029; }
    .upload-box {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 12px;
        padding: 18px;
        margin-bottom: 20px;
    }
    .btn-action {
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 700;
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center page-header">
        <h2 class="page-title">
            <i class="bi bi-people-fill"></i> Nehemias - Seremos 1200
        </h2>
        <a href="?url=nehemias/lista" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver a Nehemias
        </a>
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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
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
                                    <td><?= (int)$registro['Id_Seremos1200'] ?></td>
                                    <td><?= htmlspecialchars($registro['Nombres'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($registro['Apellidos'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($registro['Numero_Cedula'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($registro['Telefono'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($registro['Lider'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($registro['Lider_Nehemias'] ?? '') ?></td>
                                    <td>
                                        <?php if ($decision === null): ?>
                                            <span class="status-pill status-pending">Pendiente</span>
                                        <?php elseif ((int)$decision === 1): ?>
                                            <span class="status-pill status-yes">Sí acepta</span>
                                        <?php else: ?>
                                            <span class="status-pill status-no">No acepta</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $migrado ? '<span class="status-pill status-yes">Sí</span>' : '<span class="status-pill status-pending">No</span>' ?>
                                    </td>
                                    <td>
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
