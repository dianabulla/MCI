<?php include VIEWS . '/layout/header.php'; ?>

<style>
    .card {
        border-radius: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        padding: 15px;
        border: none;
        white-space: nowrap;
    }
    .table td {
        padding: 15px;
        vertical-align: middle;
    }
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        transform: scale(1.01);
        transition: all 0.2s;
    }
    .badge {
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .stat-card {
        border-radius: 15px;
        padding: 20px;
        text-align: center;
        color: white;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .stat-card h5 {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 10px;
        opacity: 0.9;
    }
    .stat-card h2 {
        font-size: 42px;
        font-weight: bold;
        margin: 0;
    }
    .btn-sm {
        padding: 8px 20px;
        border-radius: 20px;
        font-weight: 600;
    }
    .table-success {
        background-color: #d4edda !important;
    }
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-gift-fill"></i> Gestión de Entrega de Obsequios Navideños</h2>
        <div>
            <a href="?url=entrega_obsequio/exportarPDF<?= $filtroMinisterio ? '&ministerio=' . $filtroMinisterio : '' ?>" 
               class="btn btn-danger me-2" target="_blank">
                <i class="bi bi-file-pdf-fill"></i> Exportar PDF
            </a>
            <a href="?url=entrega_obsequio/exportarExcel<?= $filtroMinisterio ? '&ministerio=' . $filtroMinisterio : '' ?>" 
               class="btn btn-success">
                <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
            </a>
        </div>
    </div>

    <?php if (isset($mensaje)): ?>
        <div class="alert alert-<?= $tipo_mensaje === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
            <?= htmlspecialchars($mensaje) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="url" value="entrega_obsequio">
                <div class="col-md-4">
                    <label class="form-label">Filtrar por Ministerio:</label>
                    <select name="ministerio" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos los ministerios</option>
                        <?php foreach ($ministerios as $ministerio): ?>
                            <option value="<?= $ministerio['Id_Ministerio'] ?>" 
                                    <?= $filtroMinisterio == $ministerio['Id_Ministerio'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <?php if ($filtroMinisterio): ?>
                        <a href="?url=entrega_obsequio" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar Filtro
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5><i class="bi bi-people-fill"></i> Total Registrados</h5>
                <h2><?= count($ninos) ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                <h5><i class="bi bi-check-circle-fill"></i> Entregados</h5>
                <h2><?= count(array_filter($ninos, fn($n) => $n['Estado_Entrega'] === 'Entregado')) ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <h5><i class="bi bi-clock-fill"></i> Pendientes</h5>
                <h2><?= count(array_filter($ninos, fn($n) => $n['Estado_Entrega'] === 'Pendiente')) ?></h2>
            </div>
        </div>
    </div>

    <!-- Tabla de niños -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nombre del Niño(a)</th>
                            <th>Edad</th>
                            <th>Acudiente</th>
                            <th>Teléfono</th>
                            <th>Barrio</th>
                            <th>Ministerio</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ninos)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                    <p class="text-muted mt-2">No hay niños registrados</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ninos as $nino): ?>
                                <tr id="row-<?= $nino['Id_Registro'] ?>" 
                                    class="<?= $nino['Estado_Entrega'] === 'Entregado' ? 'table-success' : '' ?>">
                                    <td><strong><?= htmlspecialchars($nino['Nombre_Apellidos']) ?></strong></td>
                                    <td><?= $nino['Edad'] ?> años</td>
                                    <td><?= htmlspecialchars($nino['Nombre_Acudiente']) ?></td>
                                    <td><?= htmlspecialchars($nino['Telefono_Acudiente']) ?></td>
                                    <td><?= htmlspecialchars($nino['Barrio']) ?></td>
                                    <td><?= htmlspecialchars($nino['Nombre_Ministerio'] ?? 'Sin ministerio') ?></td>
                                    <td>
                                        <span id="badge-<?= $nino['Id_Registro'] ?>" 
                                              class="badge <?= $nino['Estado_Entrega'] === 'Entregado' ? 'bg-success' : 'bg-warning' ?>">
                                            <?= $nino['Estado_Entrega'] ?>
                                        </span>
                                        <?php if ($nino['Estado_Entrega'] === 'Entregado' && $nino['Fecha_Entrega']): ?>
                                            <br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($nino['Fecha_Entrega'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($nino['Estado_Entrega'] === 'Pendiente'): ?>
                                            <button onclick="marcarEntregado(<?= $nino['Id_Registro'] ?>, '<?= htmlspecialchars($nino['Nombre_Apellidos']) ?>')" 
                                                    class="btn btn-sm btn-success" id="btn-<?= $nino['Id_Registro'] ?>">
                                                <i class="bi bi-check-circle"></i> Entregar
                                            </button>
                                        <?php else: ?>
                                            <span class="text-success"><i class="bi bi-check-circle-fill"></i> Entregado</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function marcarEntregado(idRegistro, nombreNino) {
    if (confirm('¿Está seguro de entregar el obsequio a ' + nombreNino + '?')) {
        const btn = document.getElementById('btn-' + idRegistro);
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';
        
        fetch('?url=entrega_obsequio/marcarEntregado', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id_registro=' + idRegistro
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la fila
                const row = document.getElementById('row-' + idRegistro);
                row.classList.add('table-success');
                
                // Actualizar badge
                const badge = document.getElementById('badge-' + idRegistro);
                badge.className = 'badge bg-success';
                badge.textContent = 'Entregado';
                badge.innerHTML += '<br><small class="text-muted">' + new Date().toLocaleString('es-CO') + '</small>';
                
                // Actualizar botón
                btn.outerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Entregado</span>';
                
                // Actualizar contadores
                location.reload();
            } else {
                alert('Error: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Entregar';
            }
        })
        .catch(error => {
            alert('Error al procesar la solicitud');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i> Entregar';
        });
    }
}
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
