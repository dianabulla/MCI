<?php
include VIEWS . '/layout/header.php';
require_once APP . '/Helpers/GestionSistemaAccess.php';
$puedeGestionarPermisosRol = GestionSistemaAccess::puedeVerMatrizPermisos();
$admin_nav_active = 'roles';
include VIEWS . '/partials/admin_nav.php';
$rolesFlashError = (string)($_SESSION['roles_flash_error'] ?? '');
unset($_SESSION['roles_flash_error']);
?>
<?php if ($rolesFlashError !== ''): ?>
<div class="alert alert-danger" role="alert" style="margin-bottom:14px;"><?= htmlspecialchars($rolesFlashError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="page-header roles-page-header">
    <h2>Roles</h2>
    <?php $puedeCrearRoles = AuthController::esAdministrador() || AuthController::puede('roles:crear'); ?>
    <?php $puedeEditarRoles = AuthController::esAdministrador() || AuthController::puede('roles:editar'); ?>
    <?php $puedeEliminarRoles = AuthController::esAdministrador() || AuthController::puede('roles:eliminar'); ?>
    <?php $puedeGestionarRoles = $puedeEditarRoles || $puedeEliminarRoles; ?>
    <div class="roles-page-actions">
        <?php if (AuthController::esAdministrador()): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=herramientas/corregir-rol-celulas" class="btn btn-secondary">Corregir rol Células</a>
        <?php endif; ?>
        <?php if ($puedeCrearRoles): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=roles/crear" class="btn btn-primary">+ Nuevo Rol</a>
        <?php endif; ?>
    </div>
</div>

<div class="form-container roles-filtros">
    <div class="roles-filtros-grid">
        <div class="form-group" style="margin-bottom:0;">
            <label for="filtro_rol_existente">Filtrar por rol existente</label>
            <select id="filtro_rol_existente" class="form-control">
                <option value="">Todos los roles</option>
                <?php foreach (($roles ?? []) as $rolFiltro): ?>
                    <option value="<?= (int)($rolFiltro['Id_Rol'] ?? 0) ?>">
                        <?= htmlspecialchars((string)($rolFiltro['Nombre_Rol'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label for="filtro_rol_texto">Buscar</label>
            <input
                type="text"
                id="filtro_rol_texto"
                class="form-control"
                placeholder="Nombre o descripción..."
                autocomplete="off"
            >
        </div>
        <div class="form-group roles-filtros-acciones">
            <button type="button" id="btn_limpiar_filtro_roles" class="btn btn-secondary">Limpiar</button>
        </div>
    </div>
</div>

<div class="table-container roles-table-wrap">
    <table class="data-table roles-data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Total Personas</th>
                <?php if ($puedeGestionarRoles || $puedeGestionarPermisosRol): ?><th>Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($roles)): ?>
                <?php foreach ($roles as $rol): ?>
                    <?php
                        $textoBusquedaRol = trim((string)($rol['Nombre_Rol'] ?? '') . ' ' . (string)($rol['Descripcion'] ?? ''));
                    ?>
                    <tr data-roles-row="1" data-rol-id="<?= (int)($rol['Id_Rol'] ?? 0) ?>" data-search="<?= htmlspecialchars($textoBusquedaRol, ENT_QUOTES, 'UTF-8') ?>">
                        <td data-label="Nombre"><?= htmlspecialchars($rol['Nombre_Rol']) ?></td>
                        <td data-label="Descripción"><?= htmlspecialchars($rol['Descripcion']) ?></td>
                        <td data-label="Total personas"><?= (int)($rol['Total_Personas'] ?? 0) ?></td>
                        <?php if ($puedeGestionarRoles || $puedeGestionarPermisosRol): ?>
                        <td data-label="Acciones">
                            <div class="roles-row-actions">
                            <?php if ($puedeGestionarPermisosRol): ?>
                            <a href="<?= PUBLIC_URL ?>index.php?url=permisos&amp;rol=<?= (int)($rol['Id_Rol'] ?? 0) ?>" class="btn btn-sm btn-primary" title="Configurar permisos de este rol">Permisos</a>
                            <?php endif; ?>
                            <?php if ($puedeEditarRoles): ?>
                            <a href="<?= PUBLIC_URL ?>index.php?url=roles/editar&id=<?= $rol['Id_Rol'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <?php endif; ?>
                            <?php if ($puedeEliminarRoles): ?>
                            <a href="<?= PUBLIC_URL ?>index.php?url=roles/eliminar&id=<?= $rol['Id_Rol'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este rol?')">Eliminar</a>
                            <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr data-roles-empty="1">
                    <td colspan="4" class="text-center">No hay roles registrados</td>
                </tr>
            <?php endif; ?>
                <tr data-roles-empty-search="1" style="display:none;">
                    <td colspan="4" class="text-center">No hay roles para ese filtro.</td>
                </tr>
        </tbody>
    </table>
</div>

<style>
.roles-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.roles-page-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.roles-row-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.roles-filtros {
    margin-bottom: 16px;
}

.roles-filtros-grid {
    display: grid;
    grid-template-columns: minmax(180px, 1fr) minmax(180px, 1.2fr) auto;
    gap: 12px;
    align-items: end;
}

.roles-filtros-acciones {
    margin-bottom: 0;
}

@media (max-width: 800px) {
    .roles-page-header {
        align-items: stretch;
    }

    .roles-page-actions,
    .roles-page-actions .btn {
        width: 100%;
    }

    .roles-page-actions .btn {
        justify-content: center;
        padding: 12px 14px;
    }

    .roles-row-actions {
        width: 100%;
        flex-direction: column;
    }

    .roles-row-actions .btn {
        width: 100%;
        justify-content: center;
        padding: 10px 12px;
        white-space: normal;
    }

    .roles-data-table td[data-label="Acciones"] {
        align-items: stretch;
        flex-direction: column;
    }

    .roles-filtros-grid {
        grid-template-columns: 1fr;
    }

    .roles-filtros-acciones .btn {
        width: 100%;
        padding: 12px 14px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectRol = document.getElementById('filtro_rol_existente');
    const inputTexto = document.getElementById('filtro_rol_texto');
    const btnLimpiar = document.getElementById('btn_limpiar_filtro_roles');
    const filas = Array.from(document.querySelectorAll('tr[data-roles-row="1"]'));
    const vacioBusqueda = document.querySelector('tr[data-roles-empty-search="1"]');

    const normalizar = function (texto) {
        return String(texto || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    };

    const filtrar = function () {
        const idRol = String((selectRol && selectRol.value) || '').trim();
        const termino = normalizar(inputTexto ? inputTexto.value : '');
        let visibles = 0;

        filas.forEach(function (fila) {
            const coincideRol = idRol === '' || String(fila.getAttribute('data-rol-id') || '') === idRol;
            const textoFila = normalizar(fila.getAttribute('data-search') || '');
            const coincideTexto = termino === '' || textoFila.indexOf(termino) !== -1;
            const visible = coincideRol && coincideTexto;
            fila.style.display = visible ? '' : 'none';
            if (visible) {
                visibles++;
            }
        });

        if (vacioBusqueda) {
            vacioBusqueda.style.display = (filas.length > 0 && visibles === 0) ? '' : 'none';
        }
    };

    if (selectRol) {
        selectRol.addEventListener('change', filtrar);
    }
    if (inputTexto) {
        inputTexto.addEventListener('input', filtrar);
    }
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function () {
            if (selectRol) {
                selectRol.value = '';
            }
            if (inputTexto) {
                inputTexto.value = '';
            }
            filtrar();
        });
    }
});
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
