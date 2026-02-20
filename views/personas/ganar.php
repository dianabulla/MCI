<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Apartado Ganar</h2>
    <div style="display: flex; gap: 8px;">
        <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-secondary">Volver a Personas</a>
        <?php if (AuthController::tienePermiso('personas', 'crear')): ?>
        <a href="<?= PUBLIC_URL ?>?url=personas/crear" class="btn btn-primary">+ Nueva Persona</a>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-info" style="margin-bottom: 20px;">
    <i class="bi bi-info-circle"></i>
    Este apartado muestra personas nuevas (últimos <strong>30 días</strong>) para seguimiento del proceso de ganar.
</div>

<div class="card" style="margin-bottom: 20px;">
    <div class="card-body">
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

        <form method="GET" action="<?= PUBLIC_URL ?>" style="display: flex; gap: 15px; align-items: end;">
            <input type="hidden" name="url" value="personas/ganar">

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
                <a href="<?= PUBLIC_URL ?>?url=personas/ganar" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-success" style="margin-bottom: 20px;">
    <i class="bi bi-check-circle"></i>
    Total en seguimiento Ganar: <strong><?= count($personas) ?></strong> persona(s)
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre Completo</th>
                <th>Cédula</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Célula</th>
                <th>Líder</th>
                <th>Ministerio</th>
                <th>Fecha Registro</th>
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
                        <td><?= htmlspecialchars($persona['Nombre_Lider'] ?? 'Sin líder') ?></td>
                        <td><?= htmlspecialchars($persona['Nombre_Ministerio'] ?? 'Sin ministerio') ?></td>
                        <td><?= !empty($persona['Fecha_Registro']) ? date('d/m/Y H:i', strtotime($persona['Fecha_Registro'])) : '' ?></td>
                        <td>
                            <?php if (AuthController::tienePermiso('personas', 'ver')): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= $persona['Id_Persona'] ?>" class="btn btn-sm btn-info">Ver</a>
                            <?php endif; ?>
                            <?php if (AuthController::tienePermiso('personas', 'editar')): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= $persona['Id_Persona'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center">No hay personas en el apartado Ganar</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
const busquedaInput = document.getElementById('busqueda_rapida');
const tabla = document.querySelector('.data-table tbody');
const filas = tabla.querySelectorAll('tr');
const filasArray = Array.from(filas);

busquedaInput.addEventListener('input', function(e) {
    const textoBusqueda = e.target.value.toLowerCase().trim();

    if (textoBusqueda === '') {
        filasArray.forEach(fila => {
            fila.style.display = '';
        });
        return;
    }

    filasArray.forEach(fila => {
        const celdas = fila.querySelectorAll('td');
        if (celdas.length === 0) return;

        const nombre = celdas[0].textContent.toLowerCase();
        const cedula = celdas[1].textContent.toLowerCase();
        const telefono = celdas[2].textContent.toLowerCase();
        const email = celdas[3].textContent.toLowerCase();

        const coincide = nombre.includes(textoBusqueda) ||
                        cedula.includes(textoBusqueda) ||
                        telefono.includes(textoBusqueda) ||
                        email.includes(textoBusqueda);

        fila.style.display = coincide ? '' : 'none';
    });
});
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
