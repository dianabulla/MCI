<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Personas</h2>
    <?php if (AuthController::tienePermiso('personas', 'crear')): ?>
    <a href="<?= PUBLIC_URL ?>?url=personas/crear" class="btn btn-primary">+ Nueva Persona</a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body">
        <!-- Búsqueda rápida -->
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="busqueda_rapida" style="font-size: 14px; margin-bottom: 5px;">
                <i class="bi bi-search"></i> Búsqueda Rápida (nombre, cédula o teléfono)
            </label>
            <input type="text" 
                   id="busqueda_rapida" 
                   class="form-control" 
                   placeholder="Escribe para buscar por nombre, apellido, cédula o teléfono..."
                   autocomplete="off">
        </div>

        <!-- Filtros adicionales -->
        <form method="GET" action="<?= PUBLIC_URL ?>" style="display: flex; gap: 15px; align-items: end;">
            <input type="hidden" name="url" value="personas">
            
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label for="filtro_ministerio" style="font-size: 14px; margin-bottom: 5px;">Ministerio</label>
                <select id="filtro_ministerio" name="ministerio" class="form-control">
                    <option value="">Todos los ministerios</option>
                    <option value="0" <?= (isset($_GET['ministerio']) && $_GET['ministerio'] === '0') ? 'selected' : '' ?>>
                        Sin ministerio
                    </option>
                    <?php if (!empty($ministerios)): ?>
                        <?php foreach ($ministerios as $ministerio): ?>
                            <option value="<?= $ministerio['Id_Ministerio'] ?>" 
                                    <?= (isset($_GET['ministerio']) && $_GET['ministerio'] == $ministerio['Id_Ministerio']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label for="filtro_lider" style="font-size: 14px; margin-bottom: 5px;">Líder</label>
                <select id="filtro_lider" name="lider" class="form-control">
                    <option value="">Todos los líderes</option>
                    <option value="0" <?= (isset($_GET['lider']) && $_GET['lider'] === '0') ? 'selected' : '' ?>>
                        Sin líder
                    </option>
                    <?php if (!empty($lideres)): ?>
                        <?php foreach ($lideres as $lider): ?>
                            <option value="<?= $lider['Id_Persona'] ?>" 
                                    <?= (isset($_GET['lider']) && $_GET['lider'] == $lider['Id_Persona']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lider['Nombre'] . ' ' . $lider['Apellido']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <?php if (!empty($_GET['ministerio']) || !empty($_GET['lider'])): ?>
                <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($_GET['ministerio']) || !empty($_GET['lider'])): ?>
<div class="alert alert-info" style="margin-bottom: 20px;">
    <i class="bi bi-info-circle"></i> 
    Mostrando resultados filtrados. Total: <strong><?= count($personas) ?></strong> persona(s)
</div>
<?php endif; ?>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre Completo</th>
                <th>Cédula</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Célula</th>
                <th>Rol</th>
                <th>Ministerio</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($personas)): ?>
                <?php foreach ($personas as $persona): ?>
                    <tr>
                        <td><?= htmlspecialchars($persona['Nombre'] . ' ' . $persona['Apellido']) ?></td>
                        <td><?= htmlspecialchars($persona['Numero_Documento'] ?? '') ?></td>
                        <td><?= htmlspecialchars($persona['Telefono'] ?? '') ?></td>
                        <td><?= htmlspecialchars($persona['Email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($persona['Nombre_Celula'] ?? 'Sin célula') ?></td>
                        <td><?= htmlspecialchars($persona['Nombre_Rol'] ?? 'Sin rol') ?></td>
                        <td><?= htmlspecialchars($persona['Nombre_Ministerio'] ?? 'Sin ministerio') ?></td>
                        <td>
                            <?php 
                            $estado = $persona['Estado_Cuenta'] ?? 'Activo';
                            $badgeClass = $estado == 'Activo' ? 'badge-success' : ($estado == 'Inactivo' ? 'badge-secondary' : 'badge-danger');
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($estado) ?></span>
                        </td>
                        <td>
                            <?php if (AuthController::tienePermiso('personas', 'ver')): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= $persona['Id_Persona'] ?>" class="btn btn-sm btn-info">Ver</a>
                            <?php endif; ?>
                            <?php if (AuthController::tienePermiso('personas', 'editar')): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= $persona['Id_Persona'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <?php endif; ?>
                            <?php if (AuthController::tienePermiso('personas', 'eliminar')): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/eliminar&id=<?= $persona['Id_Persona'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta persona?')">Eliminar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">No hay personas registradas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
// Búsqueda en tiempo real
const busquedaInput = document.getElementById('busqueda_rapida');
const tabla = document.querySelector('.data-table tbody');
const filas = tabla.querySelectorAll('tr');

// Guardar el contenido original
const filasArray = Array.from(filas);

busquedaInput.addEventListener('input', function(e) {
    const textoBusqueda = e.target.value.toLowerCase().trim();
    
    if (textoBusqueda === '') {
        // Mostrar todas las filas
        filasArray.forEach(fila => {
            fila.style.display = '';
        });
        return;
    }
    
    // Filtrar filas
    filasArray.forEach(fila => {
        const celdas = fila.querySelectorAll('td');
        if (celdas.length === 0) return; // Saltar fila de "No hay personas"
        
        const nombre = celdas[0].textContent.toLowerCase();
        const cedula = celdas[1].textContent.toLowerCase();
        const telefono = celdas[2].textContent.toLowerCase();
        const email = celdas[3].textContent.toLowerCase();
        
        // Buscar en nombre, cédula, teléfono y email
        const coincide = nombre.includes(textoBusqueda) || 
                        cedula.includes(textoBusqueda) || 
                        telefono.includes(textoBusqueda) ||
                        email.includes(textoBusqueda);
        
        fila.style.display = coincide ? '' : 'none';
    });
});
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
