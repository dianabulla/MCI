<?php include VIEWS . '/layout/header.php'; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center module-header-card">
        <h2 class="module-title"><i class="bi bi-gift-fill"></i> Gestión de Entrega de Obsequios Navideños</h2>
        <div class="page-actions obsequio-actions">
            <a href="?url=entrega_obsequio/exportarPDF<?= $filtroMinisterio ? '&ministerio=' . $filtroMinisterio : '' ?>" 
               class="btn btn-danger me-2 btn-obsequio-pdf" target="_blank">
                <i class="bi bi-file-pdf-fill"></i> Exportar PDF
            </a>
            <a href="?url=entrega_obsequio/exportarExcel<?= $filtroMinisterio ? '&ministerio=' . $filtroMinisterio : '' ?>" 
               class="btn btn-success btn-obsequio-excel">
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
                    <select name="ministerio" class="form-control" onchange="this.form.submit()">
                        <?php if (empty($filtroMinisterioRestringido)): ?>
                            <option value="">Todos los ministerios</option>
                        <?php else: ?>
                            <option value="">Seleccione</option>
                        <?php endif; ?>
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
            <div class="stat-card obsequio-stat-card obsequio-stat-total">
                <h5><i class="bi bi-people-fill"></i> Total Registrados</h5>
                <h2><?= count($ninos) ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card obsequio-stat-card obsequio-stat-entregados">
                <h5><i class="bi bi-check-circle-fill"></i> Entregados</h5>
                <h2><?= count(array_filter($ninos, fn($n) => $n['Estado_Entrega'] === 'Entregado')) ?></h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card obsequio-stat-card obsequio-stat-pendientes">
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
                                    <td data-label="Nombre del Niño(a)"><strong><?= htmlspecialchars($nino['Nombre_Apellidos']) ?></strong></td>
                                    <td data-label="Edad"><?= $nino['Edad'] ?> años</td>
                                    <td data-label="Acudiente"><?= htmlspecialchars($nino['Nombre_Acudiente']) ?></td>
                                    <td data-label="Teléfono"><?= htmlspecialchars($nino['Telefono_Acudiente']) ?></td>
                                    <td data-label="Barrio"><?= htmlspecialchars($nino['Barrio']) ?></td>
                                    <td data-label="Ministerio"><?= htmlspecialchars($nino['Nombre_Ministerio'] ?? 'Sin ministerio') ?></td>
                                    <td data-label="Estado">
                                        <span id="badge-<?= $nino['Id_Registro'] ?>" 
                                              class="badge <?= $nino['Estado_Entrega'] === 'Entregado' ? 'bg-success' : 'bg-warning' ?>">
                                            <?= $nino['Estado_Entrega'] ?>
                                        </span>
                                        <?php if ($nino['Estado_Entrega'] === 'Entregado' && $nino['Fecha_Entrega']): ?>
                                            <br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($nino['Fecha_Entrega'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Acciones">
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
