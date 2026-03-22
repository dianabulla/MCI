<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Actividad de Líderes de Célula</h2>
    <a href="<?= PUBLIC_URL ?>?url=home" class="btn btn-secondary">Volver al panel</a>
</div>

<div class="alert alert-info" style="margin-bottom: 20px;">
    <i class="bi bi-info-circle"></i>
    Resumen de actividad por líder separado por género, con filtros de búsqueda y ministerio.
</div>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="padding: 14px;">
        <form method="GET" action="<?= PUBLIC_URL ?>" class="filters-inline">
            <input type="hidden" name="url" value="home/lideres-celula">

            <div class="form-group" style="min-width: 220px;">
                <label for="filtro_buscar_lider">Buscar</label>
                <input
                    type="text"
                    id="filtro_buscar_lider"
                    name="buscar"
                    class="form-control"
                    placeholder="Nombre o ministerio"
                    value="<?= htmlspecialchars((string)($filtro_buscar ?? '')) ?>"
                >
            </div>

            <div class="form-group" style="min-width: 220px;">
                <label for="filtro_ministerio_lider">Ministerio</label>
                <select id="filtro_ministerio_lider" name="ministerio" class="form-control">
                    <option value="">Todos los ministerios</option>
                    <?php foreach (($ministerios_disponibles ?? []) as $ministerio): ?>
                        <option value="<?= (int)($ministerio['id'] ?? 0) ?>" <?= ((string)($filtro_ministerio ?? '') === (string)($ministerio['id'] ?? '')) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($ministerio['nombre'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="min-width: 180px;">
                <label for="filtro_genero_lider">Género</label>
                <select id="filtro_genero_lider" name="genero" class="form-control">
                    <option value="todos" <?= (($filtro_genero ?? 'todos') === 'todos') ? 'selected' : '' ?>>Todos</option>
                    <option value="hombres" <?= (($filtro_genero ?? 'todos') === 'hombres') ? 'selected' : '' ?>>Hombres</option>
                    <option value="mujeres" <?= (($filtro_genero ?? 'todos') === 'mujeres') ? 'selected' : '' ?>>Mujeres</option>
                </select>
            </div>

            <div class="form-group" style="min-width: 220px;">
                <label for="filtro_tipo_liderazgo">Tipo de liderazgo</label>
                <select id="filtro_tipo_liderazgo" name="tipo_liderazgo" class="form-control">
                    <option value="todos" <?= (($filtro_tipo_liderazgo ?? 'todos') === 'todos') ? 'selected' : '' ?>>Todos</option>
                    <option value="celula" <?= (($filtro_tipo_liderazgo ?? 'todos') === 'celula') ? 'selected' : '' ?>>Líder de célula</option>
                    <option value="doce" <?= (($filtro_tipo_liderazgo ?? 'todos') === 'doce') ? 'selected' : '' ?>>Líder de 12</option>
                    <option value="ambos" <?= (($filtro_tipo_liderazgo ?? 'todos') === 'ambos') ? 'selected' : '' ?>>Ambos</option>
                </select>
            </div>

            <div class="filters-actions">
                <button type="submit" class="btn btn-primary">Aplicar</button>
                <a href="<?= PUBLIC_URL ?>?url=home/lideres-celula" class="btn btn-secondary">Limpiar</a>
            </div>
        </form>
    </div>
</div>

<div class="report-kpi-grid" style="margin-bottom: 16px; grid-template-columns: repeat(3, minmax(0, 1fr));">
    <?php
    $vistaInicial = in_array(($filtro_genero ?? 'todos'), ['hombres', 'mujeres'], true)
        ? (string)$filtro_genero
        : 'todos';
    $lideresTodos = array_merge($lideres_hombres ?? [], $lideres_mujeres ?? []);
    ?>

    <button type="button" class="report-kpi-card kpi-escalera js-lider-card <?= $vistaInicial === 'todos' ? 'is-active' : '' ?>" data-target="todos" aria-label="Mostrar todos los líderes">
        <div class="report-kpi-label">Total líderes</div>
        <div class="report-kpi-value"><?= (int)($total_lideres ?? 0) ?></div>
    </button>
    <button type="button" class="report-kpi-card kpi-celula js-lider-card <?= $vistaInicial === 'hombres' ? 'is-active' : '' ?>" data-target="hombres" aria-label="Mostrar líderes hombres">
        <div class="report-kpi-label">Hombres</div>
        <div class="report-kpi-value"><?= (int)($total_hombres ?? 0) ?></div>
    </button>
    <button type="button" class="report-kpi-card kpi-domingo js-lider-card <?= $vistaInicial === 'mujeres' ? 'is-active' : '' ?>" data-target="mujeres" aria-label="Mostrar líderes mujeres">
        <div class="report-kpi-label">Mujeres</div>
        <div class="report-kpi-value"><?= (int)($total_mujeres ?? 0) ?></div>
    </button>
</div>

<div class="main-content" style="display:grid; gap:16px;">
    <div class="card js-lider-panel" data-panel="todos" style="<?= $vistaInicial === 'todos' ? '' : 'display:none;' ?>">
        <div class="card-header" style="padding:12px 14px; border-bottom:1px solid #dde6f4; font-weight:700; color:#1f365f;">
            Todos los líderes
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Líder</th>
                        <th>Ministerio</th>
                        <th>Tipo liderazgo</th>
                        <th>Personas asignadas</th>
                        <th class="action-col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lideresTodos)): ?>
                        <?php foreach ($lideresTodos as $lider): ?>
                            <tr>
                                <td><?= htmlspecialchars(trim((string)($lider['Nombre'] ?? '') . ' ' . (string)($lider['Apellido'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars((string)($lider['Nombre_Ministerio'] ?? 'Sin ministerio')) ?></td>
                                <td>
                                    <span class="liderazgo-badge"><?= htmlspecialchars((string)($lider['Tipo_Liderazgo'] ?? 'Sin clasificación')) ?></span>
                                </td>
                                <td><?= (int)($lider['Total_Personas'] ?? 0) ?></td>
                                <td class="action-col">
                                    <div class="action-buttons action-buttons-compact">
                                        <a href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= (int)($lider['Id_Persona'] ?? 0) ?>" class="action-icon-btn action-icon-info" title="Ver" aria-label="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (AuthController::tienePermiso('personas', 'editar')): ?>
                                        <a href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= (int)($lider['Id_Persona'] ?? 0) ?>" class="action-icon-btn action-icon-warning" title="Editar" aria-label="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                                <td colspan="5" class="text-center">No hay líderes de célula para mostrar.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card js-lider-panel" data-panel="hombres" style="<?= $vistaInicial === 'hombres' ? '' : 'display:none;' ?>">
        <div class="card-header" style="padding:12px 14px; border-bottom:1px solid #dde6f4; font-weight:700; color:#1f365f;">
            Líderes hombres
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Líder</th>
                        <th>Ministerio</th>
                        <th>Tipo liderazgo</th>
                        <th>Personas asignadas</th>
                        <th class="action-col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lideres_hombres)): ?>
                        <?php foreach ($lideres_hombres as $lider): ?>
                            <tr>
                                <td><?= htmlspecialchars(trim((string)($lider['Nombre'] ?? '') . ' ' . (string)($lider['Apellido'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars((string)($lider['Nombre_Ministerio'] ?? 'Sin ministerio')) ?></td>
                                <td>
                                    <span class="liderazgo-badge"><?= htmlspecialchars((string)($lider['Tipo_Liderazgo'] ?? 'Sin clasificación')) ?></span>
                                </td>
                                <td><?= (int)($lider['Total_Personas'] ?? 0) ?></td>
                                <td class="action-col">
                                    <div class="action-buttons action-buttons-compact">
                                        <a href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= (int)($lider['Id_Persona'] ?? 0) ?>" class="action-icon-btn action-icon-info" title="Ver" aria-label="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (AuthController::tienePermiso('personas', 'editar')): ?>
                                        <a href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= (int)($lider['Id_Persona'] ?? 0) ?>" class="action-icon-btn action-icon-warning" title="Editar" aria-label="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Sin líderes hombres para mostrar.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card js-lider-panel" data-panel="mujeres" style="<?= $vistaInicial === 'mujeres' ? '' : 'display:none;' ?>">
        <div class="card-header" style="padding:12px 14px; border-bottom:1px solid #dde6f4; font-weight:700; color:#1f365f;">
            Líderes mujeres
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Líder</th>
                        <th>Ministerio</th>
                        <th>Tipo liderazgo</th>
                        <th>Personas asignadas</th>
                        <th class="action-col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($lideres_mujeres)): ?>
                        <?php foreach ($lideres_mujeres as $lider): ?>
                            <tr>
                                <td><?= htmlspecialchars(trim((string)($lider['Nombre'] ?? '') . ' ' . (string)($lider['Apellido'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars((string)($lider['Nombre_Ministerio'] ?? 'Sin ministerio')) ?></td>
                                <td>
                                    <span class="liderazgo-badge"><?= htmlspecialchars((string)($lider['Tipo_Liderazgo'] ?? 'Sin clasificación')) ?></span>
                                </td>
                                <td><?= (int)($lider['Total_Personas'] ?? 0) ?></td>
                                <td class="action-col">
                                    <div class="action-buttons action-buttons-compact">
                                        <a href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= (int)($lider['Id_Persona'] ?? 0) ?>" class="action-icon-btn action-icon-info" title="Ver" aria-label="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (AuthController::tienePermiso('personas', 'editar')): ?>
                                        <a href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= (int)($lider['Id_Persona'] ?? 0) ?>" class="action-icon-btn action-icon-warning" title="Editar" aria-label="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Sin líderes mujeres para mostrar.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(() => {
    const cards = Array.from(document.querySelectorAll('.js-lider-card'));
    const panels = Array.from(document.querySelectorAll('.js-lider-panel'));

    if (!cards.length || !panels.length) {
        return;
    }

    const activate = (target) => {
        cards.forEach((card) => {
            card.classList.toggle('is-active', card.dataset.target === target);
        });

        panels.forEach((panel) => {
            panel.style.display = panel.dataset.panel === target ? '' : 'none';
        });
    };

    cards.forEach((card) => {
        card.addEventListener('click', () => {
            activate(card.dataset.target || 'todos');
        });
    });
})();
</script>

<style>
.report-kpi-grid {
    display: grid;
    gap: 12px;
}

.report-kpi-card {
    border-radius: 12px;
    padding: 14px;
    color: #10233d;
    border: 1px solid #d8e2f1;
    background: #f8fbff;
    text-align: left;
    cursor: pointer;
}

.report-kpi-card.is-active {
    box-shadow: inset 0 0 0 2px rgba(31, 54, 95, 0.18);
    transform: translateY(-1px);
}

.kpi-celula { background: #eef7ff; border-color: #c7dfff; }
.kpi-domingo { background: #fff8e8; border-color: #ffe2a8; }
.kpi-escalera { background: #eefbf1; border-color: #bfe8c9; }

.report-kpi-label { font-size: .82rem; color: #475569; }
.report-kpi-value { font-size: 1.8rem; font-weight: 800; }

.liderazgo-badge {
    display: inline-flex;
    padding: 4px 9px;
    border-radius: 999px;
    border: 1px solid #cfe0ff;
    background: #eef5ff;
    color: #1f3f74;
    font-size: 12px;
    font-weight: 700;
}

.action-buttons-compact {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.action-col {
    white-space: nowrap;
    min-width: 94px;
}

.action-icon-btn {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: 1px solid transparent;
    font-size: 14px;
}

.action-icon-info {
    background: #e6f2ff;
    color: #0d6efd;
    border-color: #b7d7ff;
}

.action-icon-warning {
    background: #fff4dd;
    color: #9a6700;
    border-color: #ffd98a;
}

@media (max-width: 960px) {
    .report-kpi-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>