<?php require_once VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2><i class="bi bi-shield-check"></i> Administración de Permisos</h2>
    <p>Gestionar permisos de acceso por rol</p>
</div>

<div class="card">
    <div class="card-body">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            <strong>Instrucciones:</strong> Haga clic en las casillas para otorgar o revocar permisos. Los cambios se guardan automáticamente.
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th rowspan="2" style="vertical-align: middle; width: 200px;">Módulo</th>
                        <?php foreach ($roles as $rol): ?>
                        <th colspan="4" class="text-center bg-primary text-white">
                            <?= htmlspecialchars($rol['Nombre_Rol']) ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($roles as $rol): ?>
                        <th class="text-center" style="width: 70px;"><small>Ver</small></th>
                        <th class="text-center" style="width: 70px;"><small>Crear</small></th>
                        <th class="text-center" style="width: 70px;"><small>Editar</small></th>
                        <th class="text-center" style="width: 70px;"><small>Eliminar</small></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modulos as $moduloKey => $moduloNombre): ?>
                    <tr>
                        <td><strong><?= $moduloNombre ?></strong></td>
                        <?php foreach ($roles as $rol): ?>
                            <?php 
                            $permiso = $permisos[$rol['Id_Rol']][$moduloKey] ?? null;
                            $puedeVer = $permiso ? $permiso['Puede_Ver'] : 0;
                            $puedeCrear = $permiso ? $permiso['Puede_Crear'] : 0;
                            $puedeEditar = $permiso ? $permiso['Puede_Editar'] : 0;
                            $puedeEliminar = $permiso ? $permiso['Puede_Eliminar'] : 0;
                            ?>
                            <td class="text-center">
                                <input type="checkbox" 
                                       class="form-check-input permiso-check" 
                                       data-rol="<?= $rol['Id_Rol'] ?>" 
                                       data-modulo="<?= $moduloKey ?>" 
                                       data-campo="puede_ver"
                                       <?= $puedeVer ? 'checked' : '' ?>>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" 
                                       class="form-check-input permiso-check" 
                                       data-rol="<?= $rol['Id_Rol'] ?>" 
                                       data-modulo="<?= $moduloKey ?>" 
                                       data-campo="puede_crear"
                                       <?= $puedeCrear ? 'checked' : '' ?>>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" 
                                       class="form-check-input permiso-check" 
                                       data-rol="<?= $rol['Id_Rol'] ?>" 
                                       data-modulo="<?= $moduloKey ?>" 
                                       data-campo="puede_editar"
                                       <?= $puedeEditar ? 'checked' : '' ?>>
                            </td>
                            <td class="text-center">
                                <input type="checkbox" 
                                       class="form-check-input permiso-check" 
                                       data-rol="<?= $rol['Id_Rol'] ?>" 
                                       data-modulo="<?= $moduloKey ?>" 
                                       data-campo="puede_eliminar"
                                       <?= $puedeEliminar ? 'checked' : '' ?>>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="mensaje-guardado" class="alert alert-success" style="display: none;">
            <i class="bi bi-check-circle"></i> Permiso actualizado correctamente
        </div>
    </div>
</div>

<style>
    .table th {
        background-color: #f8f9fa;
    }
    .bg-primary {
        background-color: #667eea !important;
    }
    .permiso-check {
        cursor: pointer;
        width: 20px;
        height: 20px;
    }
    .alert {
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .alert-info {
        background-color: #e7f3ff;
        border: 1px solid #b3d9ff;
        color: #004085;
    }
    .alert-success {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
    }
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
</style>

<script>
document.querySelectorAll('.permiso-check').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const idRol = this.dataset.rol;
        const modulo = this.dataset.modulo;
        const campo = this.dataset.campo;
        const valor = this.checked ? 1 : 0;

        // Enviar actualización vía AJAX
        fetch('<?= BASE_URL ?>/public/?url=permisos/actualizar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id_rol=${idRol}&modulo=${modulo}&campo=${campo}&valor=${valor}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                const mensaje = document.getElementById('mensaje-guardado');
                mensaje.style.display = 'block';
                setTimeout(() => {
                    mensaje.style.display = 'none';
                }, 2000);
            } else {
                alert('Error al actualizar permiso: ' + (data.error || 'Error desconocido'));
                this.checked = !this.checked; // Revertir el cambio
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
            this.checked = !this.checked; // Revertir el cambio
        });
    });
});
</script>

<?php require_once VIEWS . '/layout/footer.php'; ?>
