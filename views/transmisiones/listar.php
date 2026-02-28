<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Gestión de Transmisiones</h2>
    <?php $puedeCrearTransmision = AuthController::esAdministrador() || AuthController::tienePermiso('transmisiones', 'crear'); ?>
    <?php $puedeEditarTransmision = AuthController::esAdministrador() || AuthController::tienePermiso('transmisiones', 'editar'); ?>
    <?php $puedeEliminarTransmision = AuthController::esAdministrador() || AuthController::tienePermiso('transmisiones', 'eliminar'); ?>
    <?php $puedeGestionarTransmision = $puedeEditarTransmision || $puedeEliminarTransmision; ?>
    <div class="page-actions">
        <a href="<?= PUBLIC_URL ?>?url=transmisiones/exportarExcel" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
        <?php if ($puedeCrearTransmision): ?>
        <a href="<?= PUBLIC_URL ?>?url=transmisiones/crear" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nueva Transmisión
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <?= htmlspecialchars($_GET['success']) ?>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger" style="margin-bottom: 20px;">
        <?= htmlspecialchars($_GET['error']) ?>
    </div>
<?php endif; ?>

<!-- Estadísticas -->
<div class="dashboard-grid" style="margin-bottom: 30px;">
    <div class="dashboard-card">
        <h3>En Vivo</h3>
        <div class="value" style="color: #e74c3c;"><?= $estadisticas['en_vivo'] ?? 0 ?></div>
    </div>
    <div class="dashboard-card" style="border-left-color: #f39c12;">
        <h3>Próximamente</h3>
        <div class="value" style="color: #f39c12;"><?= $estadisticas['proximamente'] ?? 0 ?></div>
    </div>
    <div class="dashboard-card" style="border-left-color: #95a5a6;">
        <h3>Finalizadas</h3>
        <div class="value" style="color: #95a5a6;"><?= $estadisticas['finalizada'] ?? 0 ?></div>
    </div>
</div>

<!-- Lista de Transmisiones -->
<div class="card">
    <div class="card-body">
    <?php if (!empty($transmisiones)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Estado</th>
                    <th>Descripción</th>
                    <?php if ($puedeGestionarTransmision): ?><th>Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transmisiones as $trans): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($trans['Nombre']) ?></strong>
                    </td>
                    <td><?= date('d/m/Y', strtotime($trans['Fecha_Transmision'])) ?></td>
                    <td><?= $trans['Hora_Transmision'] ? date('H:i', strtotime($trans['Hora_Transmision'])) : '-' ?></td>
                    <td>
                        <?php 
                        $estadoClass = '';
                        $estadoTexto = '';
                        
                        switch($trans['Estado']) {
                            case 'en_vivo':
                                $estadoClass = 'badge-danger';
                                $estadoTexto = '🔴 En Vivo';
                                break;
                            case 'proximamente':
                                $estadoClass = 'badge-secondary';
                                $estadoTexto = '⏱️ Proximamente';
                                break;
                            case 'finalizada':
                                $estadoClass = 'badge-success';
                                $estadoTexto = '✓ Finalizada';
                                break;
                        }
                        ?>
                        <span class="badge <?= $estadoClass ?>"><?= $estadoTexto ?></span>
                    </td>
                    <td>
                        <small>
                            <?php 
                            $desc = htmlspecialchars($trans['Descripcion'] ?? '');
                            echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                            ?>
                        </small>
                    </td>
                    <?php if ($puedeGestionarTransmision): ?>
                    <td>
                        <div class="action-buttons">
                            <?php if ($puedeEditarTransmision): ?>
                            <a href="<?= PUBLIC_URL ?>?url=transmisiones/editar&id=<?= $trans['Id_Transmision'] ?>" 
                               class="btn btn-sm btn-info" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($puedeEliminarTransmision): ?>
                            <button type="button" class="btn btn-sm btn-danger" 
                                    onclick="eliminarTransmision(<?= $trans['Id_Transmision'] ?>, '<?= htmlspecialchars($trans['Nombre']) ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                            <a href="<?= PUBLIC_URL ?>?url=transmisiones-publico" 
                               class="btn btn-sm btn-success" title="Ver en sitio web">
                                <i class="bi bi-play-circle"></i> Ver
                            </a>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">
            <?php if ($puedeCrearTransmision): ?>
                <p>No hay transmisiones registradas. <a href="<?= PUBLIC_URL ?>?url=transmisiones/crear">Crear una nueva</a></p>
            <?php else: ?>
                <p>No hay transmisiones registradas.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    </div>
</div>

<style>
    .action-buttons {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    .btn-sm {
        padding: 6px 12px;
        font-size: 12px;
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border-radius: 8px;
        overflow: hidden;
    }
    .data-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .data-table th {
        padding: 15px;
        text-align: left;
        font-weight: 600;
    }
    .data-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #ddd;
    }
    .data-table tbody tr:hover {
        background: #f8f9ff;
    }
</style>

<script>
function eliminarTransmision(id, nombre) {
    if (confirm(`¿Estás seguro de que deseas eliminar la transmisión "${nombre}"?`)) {
        fetch('<?= PUBLIC_URL ?>index.php?url=transmisiones/eliminar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Transmisión eliminada exitosamente');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar la transmisión');
        });
    }
}
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
