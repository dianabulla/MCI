<?php require_once VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2><i class="bi bi-shield-check"></i> Administración de Permisos</h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <p style="margin:0;">Gestionar permisos de acceso por rol</p>
        <a href="<?= PUBLIC_URL ?>?url=permisos/exportarExcel" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> 
            <strong>Instrucciones:</strong> Haga clic en las casillas para otorgar o revocar permisos. Los cambios se guardan automáticamente.
        </div>

        <div class="table-responsive permissions-table-wrap">
            <table class="table table-bordered table-hover table-no-card permisos-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="perm-module-col align-middle">Módulo</th>
                        <?php foreach ($roles as $rol): ?>
                        <th colspan="4" class="text-center bg-primary text-white">
                            <?= htmlspecialchars($rol['Nombre_Rol']) ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <?php foreach ($roles as $rol): ?>
                        <th class="text-center perm-action-col"><small>Ver</small></th>
                        <th class="text-center perm-action-col"><small>Crear</small></th>
                        <th class="text-center perm-action-col"><small>Editar</small></th>
                        <th class="text-center perm-action-col"><small>Elim.</small></th>
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
