<?php include VIEWS . '/layout/header.php'; ?>
<?php
$puedeVerPersona    = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'ver');
$puedeEditarPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'editar');
$puedeEliminarPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'eliminar');
$mostrarAcciones = $puedeVerPersona || $puedeEditarPersona || $puedeEliminarPersona;
$filtroNombre = (string)($filtroNombreActual ?? '');
$personas = is_array($personas ?? null) ? $personas : [];
$totalPersonas = (int)($totalPersonas ?? count($personas));
$totalSinCelula = 0;
$totalConCelula = 0;
$totalSinLider = 0;

foreach ($personas as $personaResumen) {
    $idCelulaResumen = (int)($personaResumen['Id_Celula'] ?? 0);
    $idLiderResumen = (int)($personaResumen['Id_Lider'] ?? 0);

    if ($idCelulaResumen > 0) {
        $totalConCelula++;
    } else {
        $totalSinCelula++;
    }

    if ($idLiderResumen <= 0) {
        $totalSinLider++;
    }
}
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <h2 style="margin:0;">Universidad de la Vida</h2>
    <div class="personas-header-actions">
        <div class="personas-action-group personas-action-group-nav">
            <a href="<?= PUBLIC_URL ?>?url=personas" class="personas-action-pill">Discipulos</a>
            <a href="<?= PUBLIC_URL ?>?url=personas/ganar" class="personas-action-pill">Almas ganadas</a>
            <a href="<?= PUBLIC_URL ?>?url=personas/universidad-vida" class="personas-action-pill is-active" aria-current="page">Universidad de la Vida</a>
        </div>
        <div class="personas-action-group">
            <?php if (AuthController::tienePermiso('personas', 'crear')): ?>
            <a href="<?= PUBLIC_URL ?>?url=personas/crear" class="personas-action-pill">+ Nuevo Discipulo</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-body">
        <form method="GET" action="<?= PUBLIC_URL ?>" class="filters-inline" id="filtrosUvForm">
            <input type="hidden" name="url" value="personas/universidad-vida">
            <div class="form-group" style="min-width: 260px;">
                <label for="filtro_buscar_uv" style="font-size: 14px; margin-bottom: 5px;">Buscar por nombre</label>
                <input type="text" id="filtro_buscar_uv" name="buscar" class="form-control"
                       placeholder="Ej: Juan Perez"
                       value="<?= htmlspecialchars($filtroNombre) ?>">
            </div>
            <div class="filters-actions" style="align-self:flex-end;">
                <button type="submit" class="uv-btn uv-btn-primary">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <?php if ($filtroNombre !== ''): ?>
                <a href="<?= PUBLIC_URL ?>?url=personas/universidad-vida" class="uv-btn uv-btn-secondary">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-info" style="margin-bottom: 20px;">
    <i class="bi bi-mortarboard"></i>
    Este apartado muestra las personas registradas a través del formulario público de <strong>Escuelas de Formación</strong>.
</div>

<div class="alert alert-success" style="margin-bottom: 20px;">
    <i class="bi bi-check-circle"></i>
    Total: <strong><?= $totalPersonas ?></strong> persona(s) en Universidad de la Vida
</div>

<div class="card" style="margin-bottom: 20px;">
    <div class="card-body">
        <div class="uv-shortcuts">
            <article class="uv-shortcut-card">
                <span class="uv-shortcut-title"><i class="bi bi-people"></i> Inscritos U. de la Vida</span>
                <span class="uv-shortcut-count"><?= (int)$totalPersonas ?></span>
                <small class="uv-shortcut-help">Total de registros visibles en el listado actual</small>
            </article>
            <article class="uv-shortcut-card">
                <span class="uv-shortcut-title"><i class="bi bi-house-heart"></i> Con célula asignada</span>
                <span class="uv-shortcut-count uv-shortcut-count-success"><?= (int)$totalConCelula ?></span>
                <small class="uv-shortcut-help">Personas vinculadas a una célula</small>
            </article>
            <article class="uv-shortcut-card">
                <span class="uv-shortcut-title"><i class="bi bi-person-x"></i> Pendientes por ubicar</span>
                <span class="uv-shortcut-count uv-shortcut-count-warn"><?= (int)$totalSinCelula ?></span>
                <small class="uv-shortcut-help">Sin célula asignada · Sin líder: <?= (int)$totalSinLider ?></small>
            </article>
        </div>
    </div>
</div>

<div class="table-container">
    <table class="data-table ganar-table mobile-persona-accordion">
        <thead>
            <tr>
                <th>Nombre Completo</th>
                <th>Teléfono</th>
                <th>Célula</th>
                <th>Líder</th>
                <th>Ministerio</th>
                <th>Fecha de Registro</th>
                <?php if ($mostrarAcciones): ?><th class="action-col">Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($personas)): ?>
                <?php foreach ($personas as $persona): ?>
                    <?php
                    $fechaReg = '';
                    $fechaRaw = trim((string)($persona['Fecha_Registro'] ?? ''));
                    if ($fechaRaw !== '') {
                        try {
                            $dt = new DateTime($fechaRaw);
                            $fechaReg = $dt->format('d/m/Y');
                        } catch (Exception $e) {
                            $fechaReg = htmlspecialchars($fechaRaw);
                        }
                    }
                    ?>
                    <tr>
                        <td>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars(trim($persona['Nombre'] . ' ' . $persona['Apellido'])) ?>">
                                <?= htmlspecialchars(trim($persona['Nombre'] . ' ' . $persona['Apellido'])) ?>
                            </span>
                        </td>
                        <td>
                            <?= htmlspecialchars($persona['Telefono'] ?? '') ?>
                        </td>
                        <td>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars($persona['Nombre_Celula'] ?? 'Sin célula') ?>">
                                <?= htmlspecialchars($persona['Nombre_Celula'] ?? 'Sin célula') ?>
                            </span>
                        </td>
                        <td>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars($persona['Nombre_Lider'] ?? 'Sin líder') ?>">
                                <?= htmlspecialchars($persona['Nombre_Lider'] ?? 'Sin líder') ?>
                            </span>
                        </td>
                        <td>
                            <span class="ganar-cell-truncate" title="<?= htmlspecialchars($persona['Nombre_Ministerio'] ?? 'Sin ministerio') ?>">
                                <?= htmlspecialchars($persona['Nombre_Ministerio'] ?? 'Sin ministerio') ?>
                            </span>
                        </td>
                        <td>
                            <?= $fechaReg !== '' ? htmlspecialchars($fechaReg) : '<em style="color:#9aabb8;">Sin fecha</em>' ?>
                        </td>
                        <?php if ($mostrarAcciones): ?>
                        <td class="action-col">
                            <?php if ($puedeVerPersona): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= (int)$persona['Id_Persona'] ?>" class="btn-icon uv-icon-btn uv-icon-view" title="Ver detalle">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($puedeEditarPersona): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= (int)$persona['Id_Persona'] ?>" class="btn-icon uv-icon-btn uv-icon-edit" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($puedeEliminarPersona): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/eliminar&id=<?= (int)$persona['Id_Persona'] ?>"
                               class="btn-icon uv-icon-btn uv-icon-delete"
                               title="Eliminar"
                               onclick="return confirm('¿Seguro que deseas eliminar esta persona?')">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= $mostrarAcciones ? 7 : 6 ?>" style="text-align:center; padding:32px; color:#637087;">
                        <i class="bi bi-mortarboard" style="font-size:2rem; display:block; margin-bottom:8px;"></i>
                        <?php if ($filtroNombre !== ''): ?>
                            No se encontraron personas con el nombre "<strong><?= htmlspecialchars($filtroNombre) ?></strong>".
                        <?php else: ?>
                            Aún no hay personas registradas desde el formulario público de Escuelas de Formación.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.personas-header-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.personas-action-group {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px;
    border: 1px solid #d5e2f3;
    border-radius: 999px;
    background: #f8fbff;
}

.personas-action-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 12px;
    border: 1px solid transparent;
    border-radius: 999px;
    color: #2a4a73;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    line-height: 1;
    white-space: nowrap;
    transition: all 0.16s ease;
}

.personas-action-pill:hover {
    background: #edf4ff;
    color: #1c4478;
}

.personas-action-pill.is-active {
    background: #1f5ea8;
    border-color: #1f5ea8;
    color: #ffffff;
    box-shadow: 0 1px 3px rgba(20, 58, 101, 0.28);
}

.uv-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 14px;
    border-radius: 10px;
    border: 1px solid transparent;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    cursor: pointer;
    transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
}

.uv-btn:hover {
    transform: translateY(-1px);
}

.uv-btn-primary {
    background: linear-gradient(135deg, #1f5ea8, #2f76c8);
    color: #ffffff;
    box-shadow: 0 6px 16px rgba(31, 94, 168, 0.26);
}

.uv-btn-primary:hover {
    color: #ffffff;
}

.uv-btn-secondary {
    background: #f0f5ff;
    color: #244a7c;
    border-color: #c8d8f3;
}

.uv-shortcuts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
}

.uv-shortcut-card {
    display: grid;
    gap: 8px;
    padding: 14px;
    border: 1px solid #d8e2f1;
    border-radius: 12px;
    background: linear-gradient(180deg, #fbfdff 0%, #f3f8ff 100%);
    color: #1f3a66;
}

.uv-shortcut-title {
    font-weight: 700;
    font-size: 15px;
}

.uv-shortcut-count {
    width: fit-content;
    min-width: 42px;
    padding: 5px 10px;
    border-radius: 999px;
    background: #2f65b5;
    color: #fff;
    font-size: 16px;
    font-weight: 800;
    line-height: 1;
}

.uv-shortcut-count-success {
    background: #1f8f5f;
}

.uv-shortcut-count-warn {
    background: #b06a0a;
}

.uv-shortcut-help {
    color: #6b7a90;
    font-size: 12px;
    line-height: 1.25;
}

.uv-icon-btn {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: 1px solid transparent;
    font-size: 13px;
}

.uv-icon-view {
    background: #e6f2ff;
    color: #0d6efd;
    border-color: #b7d7ff;
}

.uv-icon-edit {
    background: #fff4dd;
    color: #9a6700;
    border-color: #ffd98a;
}

.uv-icon-delete {
    background: #ffe9ec;
    color: #c11f3a;
    border-color: #f6b5c0;
}

@media (max-width: 768px) {
    .personas-header-actions {
        width: 100%;
        justify-content: flex-start;
    }

    .personas-action-group {
        width: 100%;
        justify-content: flex-start;
        flex-wrap: wrap;
        border-radius: 12px;
    }

    .personas-action-pill {
        flex: 1 1 auto;
    }

    .filters-actions {
        width: 100%;
        display: flex;
        gap: 8px;
    }

    .uv-btn {
        flex: 1 1 auto;
    }
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>
