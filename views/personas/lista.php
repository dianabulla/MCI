<?php include VIEWS . '/layout/header.php'; ?>
<?php
$puedeVerPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'ver');
$puedeEditarPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'editar');
$puedeEliminarPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'eliminar');
$mostrarAcciones = $puedeVerPersona || $puedeEditarPersona || $puedeEliminarPersona;
?>

<div class="page-header">
    <h2>Personas</h2>
    <div class="page-actions personas-mobile-stack">
        <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-nav-pill active">Ubicados en células</a>
        <a href="<?= PUBLIC_URL ?>?url=personas/ganar" class="btn btn-nav-pill">Pendiente por consolidar</a>
        <a href="<?= PUBLIC_URL ?>?url=personas/exportarExcel<?= !empty($_GET['ministerio']) ? '&ministerio=' . urlencode((string)$_GET['ministerio']) : '' ?><?= !empty($_GET['lider']) ? '&lider=' . urlencode((string)$_GET['lider']) : '' ?><?= !empty($_GET['celula']) ? '&celula=' . urlencode((string)$_GET['celula']) : '' ?><?= !empty($_GET['estado']) ? '&estado=' . urlencode((string)$_GET['estado']) : '' ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
        <?php if (AuthController::tienePermiso('personas', 'crear')): ?>
        <a href="<?= PUBLIC_URL ?>?url=personas/crear" class="btn btn-primary">+ Nueva Persona</a>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body">
        <!-- Búsqueda rápida -->
        <div class="form-group" style="margin-bottom: 20px;">
            <label for="busqueda_rapida" style="font-size: 14px; margin-bottom: 5px;">
                <i class="bi bi-search"></i> Búsqueda Rápida (nombre, cédula, teléfono, célula, líder o ministerio)
            </label>
            <input type="text" 
                   id="busqueda_rapida" 
                   class="form-control" 
                   placeholder="Escribe para buscar por nombre, apellido, cédula, teléfono, célula, líder o ministerio..."
                   autocomplete="off">
        </div>

        <!-- Filtros adicionales -->
        <form method="GET" action="<?= PUBLIC_URL ?>" class="filters-inline">
            <input type="hidden" name="url" value="personas">
            
            <div class="form-group">
                <label for="filtro_ministerio" style="font-size: 14px; margin-bottom: 5px;">Ministerio</label>
                <select id="filtro_ministerio" name="ministerio" class="form-control">
                    <?php if (empty($filtroRestringido)): ?>
                        <option value="">Todos los ministerios</option>
                        <option value="0" <?= (($filtroMinisterioActual ?? ($_GET['ministerio'] ?? '')) === '0') ? 'selected' : '' ?>>
                            Sin ministerio
                        </option>
                    <?php else: ?>
                        <option value="">Seleccione</option>
                    <?php endif; ?>
                    <?php if (!empty($ministerios)): ?>
                        <?php foreach ($ministerios as $ministerio): ?>
                            <option value="<?= $ministerio['Id_Ministerio'] ?>" 
                                    <?= (($filtroMinisterioActual ?? ($_GET['ministerio'] ?? '')) == $ministerio['Id_Ministerio']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="filtro_lider" style="font-size: 14px; margin-bottom: 5px;">Líder</label>
                <select id="filtro_lider" name="lider" class="form-control">
                    <?php if (empty($filtroRestringido)): ?>
                        <option value="">Todos los líderes</option>
                        <option value="0" <?= (($filtroLiderActual ?? ($_GET['lider'] ?? '')) === '0') ? 'selected' : '' ?>>
                            Sin líder
                        </option>
                    <?php else: ?>
                        <option value="">Seleccione</option>
                    <?php endif; ?>
                    <?php if (!empty($lideres)): ?>
                        <?php foreach ($lideres as $lider): ?>
                            <option value="<?= $lider['Id_Persona'] ?>" 
                                    <?= (($filtroLiderActual ?? ($_GET['lider'] ?? '')) == $lider['Id_Persona']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lider['Nombre'] . ' ' . $lider['Apellido']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="filtro_celula" style="font-size: 14px; margin-bottom: 5px;">Célula</label>
                <select id="filtro_celula" name="celula" class="form-control">
                    <option value="">Todas las células</option>
                    <option value="0" <?= (($filtroCelulaActual ?? ($_GET['celula'] ?? '')) === '0') ? 'selected' : '' ?>>Sin célula</option>
                    <?php if (!empty($celulas)): ?>
                        <?php foreach ($celulas as $celula): ?>
                            <option value="<?= $celula['Id_Celula'] ?>"
                                    <?= (($filtroCelulaActual ?? ($_GET['celula'] ?? '')) == $celula['Id_Celula']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($celula['Nombre_Celula']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="filtro_estado" style="font-size: 14px; margin-bottom: 5px;">Estado</label>
                <select id="filtro_estado" name="estado" class="form-control">
                    <option value="">Todos los estados</option>
                    <option value="Activo" <?= (($filtroEstadoActual ?? ($_GET['estado'] ?? '')) === 'Activo') ? 'selected' : '' ?>>Activo</option>
                    <option value="Inactivo" <?= (($filtroEstadoActual ?? ($_GET['estado'] ?? '')) === 'Inactivo') ? 'selected' : '' ?>>Inactivo</option>
                    <option value="Bloqueado" <?= (($filtroEstadoActual ?? ($_GET['estado'] ?? '')) === 'Bloqueado') ? 'selected' : '' ?>>Bloqueado</option>
                </select>
            </div>

            <div class="filters-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <?php if (!empty($_GET['ministerio']) || !empty($_GET['lider']) || !empty($_GET['celula']) || !empty($_GET['estado'])): ?>
                <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($_GET['ministerio']) || !empty($_GET['lider']) || !empty($_GET['celula']) || !empty($_GET['estado'])): ?>
<div class="alert alert-info" style="margin-bottom: 20px;">
    <i class="bi bi-info-circle"></i> 
    Mostrando resultados filtrados. Total: <strong><?= count($personas) ?></strong> persona(s)
</div>
<?php endif; ?>

<div class="table-container">
    <table class="data-table ganar-table mobile-persona-accordion">
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
                <?php if ($mostrarAcciones): ?><th>Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($personas)): ?>
                <?php foreach ($personas as $persona): ?>
                    <tr>
                        <td>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars($persona['Nombre'] . ' ' . $persona['Apellido']) ?>">
                                <?= htmlspecialchars($persona['Nombre'] . ' ' . $persona['Apellido']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($persona['Numero_Documento'] ?? '') ?></td>
                        <td><?= htmlspecialchars($persona['Telefono'] ?? '') ?></td>
                        <td>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars($persona['Email'] ?? '') ?>">
                                <?= htmlspecialchars($persona['Email'] ?? '') ?>
                            </span>
                        </td>
                        <td>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars($persona['Nombre_Celula'] ?? 'Sin célula') ?>">
                                <?= htmlspecialchars($persona['Nombre_Celula'] ?? 'Sin célula') ?>
                            </span>
                        </td>
                        <td>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars($persona['Nombre_Rol'] ?? 'Sin rol') ?>">
                                <?= htmlspecialchars($persona['Nombre_Rol'] ?? 'Sin rol') ?>
                            </span>
                        </td>
                        <td>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars($persona['Nombre_Ministerio'] ?? 'Sin ministerio') ?>">
                                <?= htmlspecialchars($persona['Nombre_Ministerio'] ?? 'Sin ministerio') ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $estado = $persona['Estado_Cuenta'] ?? 'Activo';
                            $badgeClass = $estado == 'Activo' ? 'badge-success' : ($estado == 'Inactivo' ? 'badge-secondary' : 'badge-danger');
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($estado) ?></span>
                        </td>
                        <?php if ($mostrarAcciones): ?>
                        <td>
                            <div class="action-buttons">
                            <?php if ($puedeVerPersona): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= $persona['Id_Persona'] ?>" class="btn btn-sm btn-info">Ver</a>
                            <?php endif; ?>
                            <?php if ($puedeEditarPersona): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= $persona['Id_Persona'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <?php endif; ?>
                            <?php if ($puedeEliminarPersona): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/eliminar&id=<?= $persona['Id_Persona'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta persona?')">Eliminar</a>
                            <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $mostrarAcciones ? '9' : '8' ?>" class="text-center">No hay personas registradas</td>
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

const filtroMinisterio = document.getElementById('filtro_ministerio');
const filtroLider = document.getElementById('filtro_lider');
const filtroCelula = document.getElementById('filtro_celula');
const filtroRestringido = <?= !empty($filtroRestringido) ? 'true' : 'false' ?>;
let liderActual = '<?= htmlspecialchars((string)($filtroLiderActual ?? ($_GET['lider'] ?? '')), ENT_QUOTES) ?>';
let celulaActual = '<?= htmlspecialchars((string)($filtroCelulaActual ?? ($_GET['celula'] ?? '')), ENT_QUOTES) ?>';
const lideresDisponibles = <?= json_encode(array_map(function ($lider) {
    return [
        'Id_Persona' => (int)($lider['Id_Persona'] ?? 0),
        'Nombre' => trim((string)($lider['Nombre'] ?? '') . ' ' . (string)($lider['Apellido'] ?? '')),
        'Id_Ministerio' => (int)($lider['Id_Ministerio'] ?? 0)
    ];
}, $lideres ?? [])) ?>;
const celulasDisponibles = <?= json_encode(array_map(function ($celula) {
    return [
        'Id_Celula' => (int)($celula['Id_Celula'] ?? 0),
        'Nombre_Celula' => (string)($celula['Nombre_Celula'] ?? ''),
        'Id_Lider' => (int)($celula['Id_Lider'] ?? 0),
        'Id_Ministerio' => (int)($celula['Id_Ministerio'] ?? 0)
    ];
}, $celulas ?? [])) ?>;

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
        const celula = celdas[4] ? celdas[4].textContent.toLowerCase() : '';
        const lider = celdas[5] ? celdas[5].textContent.toLowerCase() : '';
        const ministerio = celdas[6] ? celdas[6].textContent.toLowerCase() : '';
        
        // Buscar en nombre, cédula, teléfono, email, célula, líder y ministerio
        const coincide = nombre.includes(textoBusqueda) || 
                        cedula.includes(textoBusqueda) || 
                        telefono.includes(textoBusqueda) ||
                email.includes(textoBusqueda) ||
                celula.includes(textoBusqueda) ||
                lider.includes(textoBusqueda) ||
                ministerio.includes(textoBusqueda);
        
        fila.style.display = coincide ? '' : 'none';
    });
});

function renderLideresDependiente() {
    if (!filtroMinisterio || !filtroLider) return;

    const ministerioSeleccionado = filtroMinisterio.value;
    const valorPrevioLider = String(filtroLider.value || '');
    filtroLider.innerHTML = '';

    const optionTodos = document.createElement('option');
    optionTodos.value = '';
    optionTodos.textContent = filtroRestringido ? 'Seleccione' : 'Todos los líderes';
    filtroLider.appendChild(optionTodos);

    if (!filtroRestringido) {
        const optionSinLider = document.createElement('option');
        optionSinLider.value = '0';
        optionSinLider.textContent = 'Sin líder';
        filtroLider.appendChild(optionSinLider);
    }

    const filtrados = lideresDisponibles.filter(function(lider) {
        return (!ministerioSeleccionado || ministerioSeleccionado === '0')
            ? true
            : String(lider.Id_Ministerio) === String(ministerioSeleccionado);
    });

    filtrados.forEach(function(lider) {
        const option = document.createElement('option');
        option.value = String(lider.Id_Persona);
        option.textContent = lider.Nombre;
        filtroLider.appendChild(option);
    });

    const valorDeseado = valorPrevioLider !== '' ? valorPrevioLider : String(liderActual || '');
    const existe = Array.from(filtroLider.options).some(function(opt) {
        return opt.value === valorDeseado;
    });
    filtroLider.value = existe ? valorDeseado : '';
}

function renderCelulasDependiente() {
    if (!filtroCelula) return;

    const ministerioSeleccionado = filtroMinisterio ? filtroMinisterio.value : '';
    const liderSeleccionado = filtroLider ? filtroLider.value : '';
    const valorPrevioCelula = String(filtroCelula.value || '');

    filtroCelula.innerHTML = '';

    const optionTodas = document.createElement('option');
    optionTodas.value = '';
    optionTodas.textContent = 'Todas las células';
    filtroCelula.appendChild(optionTodas);

    const optionSinCelula = document.createElement('option');
    optionSinCelula.value = '0';
    optionSinCelula.textContent = 'Sin célula';
    filtroCelula.appendChild(optionSinCelula);

    const filtradas = celulasDisponibles.filter(function(celula) {
        const coincideMinisterio = !ministerioSeleccionado || ministerioSeleccionado === '0'
            ? true
            : String(celula.Id_Ministerio || '') === String(ministerioSeleccionado);

        const coincideLider = !liderSeleccionado || liderSeleccionado === '0'
            ? true
            : String(celula.Id_Lider || '') === String(liderSeleccionado);

        return coincideMinisterio && coincideLider;
    });

    filtradas.forEach(function(celula) {
        const option = document.createElement('option');
        option.value = String(celula.Id_Celula);
        option.textContent = celula.Nombre_Celula;
        filtroCelula.appendChild(option);
    });

    const valorDeseado = valorPrevioCelula !== '' ? valorPrevioCelula : String(celulaActual || '');
    const existe = Array.from(filtroCelula.options).some(function(opt) {
        return opt.value === valorDeseado;
    });
    filtroCelula.value = existe ? valorDeseado : '';
}

if (filtroMinisterio && filtroLider) {
    filtroMinisterio.addEventListener('change', function() {
        renderLideresDependiente();
        renderCelulasDependiente();
    });
    filtroLider.addEventListener('change', function() {
        renderCelulasDependiente();
    }
    renderLideresDependiente();
    renderCelulasDependiente();
    liderActual = '';
    celulaActual = '';
}
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
