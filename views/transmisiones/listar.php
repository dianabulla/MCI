<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Gestión de Transmisiones</h2>
    <a href="<?= PUBLIC_URL ?>?url=transmisiones/crear" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nueva Transmisión
    </a>
</div>

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
<div class="main-content">
    <?php if (!empty($transmisiones)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Estado</th>
                    <th>Descripción</th>
                    <th>Acciones</th>
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
                    <td>
                        <div class="action-buttons">
                            <a href="<?= PUBLIC_URL ?>?url=transmisiones/editar&id=<?= $trans['Id_Transmision'] ?>" 
                               class="btn btn-sm btn-info" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" 
                                    onclick="eliminarTransmision(<?= $trans['Id_Transmision'] ?>, '<?= htmlspecialchars($trans['Nombre']) ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                            <a href="<?= PUBLIC_URL ?>?url=transmisiones-publico" 
                               class="btn btn-sm btn-success" title="Ver en sitio web">
                                <i class="bi bi-play-circle"></i> Ver
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">
            <p>No hay transmisiones registradas. <a href="<?= PUBLIC_URL ?>?url=transmisiones/crear">Crear una nueva</a></p>
        </div>
    <?php endif; ?>
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
