<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
    <h2 style="margin:0;">Células</h2>
    <?php if (AuthController::tienePermiso('celulas', 'crear')): ?>
        <a href="<?= PUBLIC_URL ?>?url=celulas/crear" class="btn btn-primary">+ Nueva célula</a>
    <?php endif; ?>
</div>

<div class="form-container" style="margin-bottom: 20px;">
    <form method="GET" class="filter-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; align-items: end;">
        <input type="hidden" name="url" value="celulas">

        <div class="form-group" style="margin-bottom: 0;">
            <label for="filtro_ministerio">Filtrar por Ministerio</label>
            <select id="filtro_ministerio" name="ministerio" class="form-control">
                <option value="">Todos</option>
                <?php foreach (($ministerios_disponibles ?? []) as $ministerio): ?>
                    <option value="<?= (int)$ministerio['Id_Ministerio'] ?>" <?= ((string)$filtro_ministerio_actual === (string)$ministerio['Id_Ministerio']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label for="filtro_lider">Filtrar por Líder de Célula</label>
            <select id="filtro_lider" name="lider" class="form-control">
                <option value="">Todos</option>
                <?php foreach (($lideres_disponibles ?? []) as $lider): ?>
                    <option value="<?= (int)$lider['Id_Persona'] ?>" <?= ((string)$filtro_lider_actual === (string)$lider['Id_Persona']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lider['Nombre_Completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0; display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= PUBLIC_URL ?>?url=celulas" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<?php
$sections = is_array($sections ?? null) ? $sections : [];
$ministerioGrupos = [];
foreach ($sections as $section) {
    $ministerio = trim((string)($section['ministerio'] ?? ''));
    if ($ministerio === '') {
        $ministerio = 'Sin ministerio';
    }

    if (!isset($ministerioGrupos[$ministerio])) {
        $ministerioGrupos[$ministerio] = [];
    }
    $ministerioGrupos[$ministerio][] = $section;
}
krsort($ministerioGrupos);

$ministeriosOrdenados = array_keys($ministerioGrupos);
$ministerioActivoInicial = !empty($ministeriosOrdenados) ? (string)$ministeriosOrdenados[0] : '';
$slugMinisterio = static function ($texto) {
    $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower((string)$texto));
    $slug = trim((string)$slug, '-');
    return $slug !== '' ? $slug : 'sin-ministerio';
};
?>

<?php if (!empty($ministerioGrupos)): ?>
<div class="dashboard-grid celulas-summary-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 320px)); margin:18px 0;">
    <?php foreach ($ministerioGrupos as $ministerioNombre => $celulasMinisterio): ?>
        <?php
        $ministerioKey = $slugMinisterio($ministerioNombre);
        $totalPersonasMinisterio = 0;
        foreach ($celulasMinisterio as $celulaMinisterio) {
            $totalPersonasMinisterio += (int)($celulaMinisterio['total_personas'] ?? 0);
        }
        ?>
        <button
            type="button"
            class="dashboard-card celulas-summary-card <?= $ministerioActivoInicial === (string)$ministerioNombre ? 'is-active' : '' ?>"
            data-target-ministerio="<?= htmlspecialchars($ministerioKey) ?>"
            style="border-left-color:#17a2b8; text-align:left; cursor:pointer;"
        >
            <h3><?= htmlspecialchars($ministerioNombre) ?></h3>
            <div class="value" style="color:#17a2b8;"><?= count($celulasMinisterio) ?></div>
            <small style="color:#637087;">Personas: <?= number_format($totalPersonasMinisterio) ?> · Clic para ver detalle</small>
        </button>
    <?php endforeach; ?>
</div>

<?php foreach ($ministerioGrupos as $ministerioNombre => $celulasMinisterio): ?>
    <?php $ministerioKey = $slugMinisterio($ministerioNombre); ?>
    <div id="tabla-ministerio-<?= htmlspecialchars($ministerioKey) ?>" class="card celulas-detalle-card" <?= $ministerioActivoInicial === (string)$ministerioNombre ? '' : 'hidden' ?> style="margin-bottom: 18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
            <h3 style="margin:0;">Ministerio: <?= htmlspecialchars($ministerioNombre) ?></h3>
            <small style="color:#637087;">Células: <?= count($celulasMinisterio) ?></small>
        </div>

        <div class="table-container">
            <table class="data-table celulas-data-table">
                <thead>
                    <tr>
                        <th>Célula</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($celulasMinisterio as $section): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string)$section['label']) ?></strong></td>
                            <td>
                                <div class="celulas-actions-row">
                                    <a href="<?= PUBLIC_URL ?>?url=celulas/detalle&id=<?= (int)$section['id_celula'] ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--icon" title="Ver personas" aria-label="Ver personas">
                                        <i class="bi bi-people-fill" aria-hidden="true"></i>
                                    </a>
                                    <a href="<?= PUBLIC_URL ?>?url=personas/crear&return_to=celulas&return_url=<?= urlencode(PUBLIC_URL . '?url=celulas') ?>&celula=<?= (int)$section['id_celula'] ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--icon" title="Nueva persona" aria-label="Nueva persona">
                                        <i class="bi bi-person-plus-fill" aria-hidden="true"></i>
                                    </a>
                                    <?php if (AuthController::tienePermiso('asistencias', 'crear')): ?>
                                        <a href="<?= PUBLIC_URL ?>?url=asistencias/registrar&celula=<?= (int)$section['id_celula'] ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--report">Reportar célula</a>
                                    <?php endif; ?>
                                    <a href="<?= PUBLIC_URL ?>?url=celulas/editar&id=<?= (int)$section['id_celula'] ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--icon celulas-action-btn--edit" title="Editar" aria-label="Editar">
                                        <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                    </a>
                                    <a href="<?= PUBLIC_URL ?>?url=celulas/eliminar&id=<?= (int)$section['id_celula'] ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--icon celulas-action-btn--delete" title="Eliminar" aria-label="Eliminar" onclick="return confirm('¿Eliminar esta célula?')">
                                        <i class="bi bi-trash-fill" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endforeach; ?>
<?php else: ?>
    <div class="table-container">
        <p class="text-center" style="padding: 20px; color: #666; margin: 0;">No hay células registradas</p>
    </div>
<?php endif; ?>

<style>
.celulas-summary-grid {
    gap: 14px;
}

.celulas-summary-card {
    appearance: none;
    border-top: 0;
    border-right: 0;
    border-bottom: 0;
    width: 100%;
    transition: transform 0.18s ease, box-shadow 0.18s ease, outline 0.18s ease;
}

.celulas-summary-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(15, 35, 61, 0.08);
}

.celulas-summary-card.is-active {
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12), 0 10px 22px rgba(15, 35, 61, 0.08);
}

.celulas-summary-card:focus-visible {
    outline: 3px solid rgba(37, 99, 235, 0.22);
    outline-offset: 2px;
}

.celulas-data-table th,
.celulas-data-table td {
    padding: 6px 8px;
    font-size: 12px;
    line-height: 1.25;
    vertical-align: middle;
}

.celulas-data-table th:nth-child(2),
.celulas-data-table td:nth-child(2) {
    white-space: nowrap;
}

.celulas-data-table .btn {
    padding: 4px 8px;
    font-size: 11px;
    line-height: 1.2;
}

.celulas-actions-row {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.celulas-action-btn {
    border: 1px solid #d6e1f0;
    background: #f6f9ff;
    color: #2f4f7a;
    font-weight: 600;
}

.celulas-action-btn:hover {
    background: #edf3fc;
    color: #274368;
}

.celulas-action-btn--icon {
    min-width: 30px;
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}

.celulas-action-btn--report {
    background: #e9f8ef;
    border-color: #c6ebd4;
    color: #1f7a44;
}

.celulas-action-btn--report:hover {
    background: #def4e7;
    color: #176a39;
}

.celulas-action-btn--edit {
    background: #fff8e8;
    border-color: #f1ddb1;
    color: #8a6400;
}

.celulas-action-btn--edit:hover {
    background: #fff1d8;
    color: #7a5600;
}

.celulas-action-btn--delete {
    background: #fff0f1;
    border-color: #f0d0d4;
    color: #9b2e3a;
}

.celulas-action-btn--delete:hover {
    background: #ffe4e7;
    color: #8a2631;
}

@media (max-width: 800px) {
    .celulas-summary-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
    (function() {
        const ministerioSelect = document.getElementById('filtro_ministerio');
        const liderSelect = document.getElementById('filtro_lider');

        if (!ministerioSelect || !liderSelect) {
            return;
        }

        const lideres = <?= json_encode($lideres_disponibles ?? []) ?>;
        const liderActual = '<?= htmlspecialchars((string)($filtro_lider_actual ?? ''), ENT_QUOTES) ?>';

        function renderLideres() {
            const ministerioSeleccionado = ministerioSelect.value;
            liderSelect.innerHTML = '';

            const optionTodos = document.createElement('option');
            optionTodos.value = '';
            optionTodos.textContent = 'Todos';
            liderSelect.appendChild(optionTodos);

            const filtrados = lideres.filter(function(lider) {
                if (!ministerioSeleccionado) {
                    return true;
                }
                return String(lider.Id_Ministerio || '') === String(ministerioSeleccionado);
            });

            filtrados.forEach(function(lider) {
                const option = document.createElement('option');
                option.value = String(lider.Id_Persona);
                option.textContent = lider.Nombre_Completo;
                if (String(lider.Id_Persona) === String(liderActual)) {
                    option.selected = true;
                }
                liderSelect.appendChild(option);
            });

            const seleccionadoValido = Array.from(liderSelect.options).some(function(opt) {
                return opt.value === liderSelect.value;
            });

            if (!seleccionadoValido) {
                liderSelect.value = '';
            }
        }

        ministerioSelect.addEventListener('change', function() {
            renderLideres();
        });

        renderLideres();
    })();

    (function() {
        const tarjetas = Array.from(document.querySelectorAll('.celulas-summary-card'));
        if (!tarjetas.length) {
            return;
        }

        function activarMinisterio(target, shouldScroll) {
            tarjetas.forEach(function(tarjeta) {
                tarjeta.classList.toggle('is-active', String(tarjeta.dataset.targetMinisterio || '') === target);
            });

            const paneles = Array.from(document.querySelectorAll('.celulas-detalle-card'));
            paneles.forEach(function(panel) {
                const esObjetivo = panel.id === ('tabla-ministerio-' + target);
                if (esObjetivo) {
                    panel.removeAttribute('hidden');
                } else {
                    panel.setAttribute('hidden', 'hidden');
                }
            });

            const panelActivo = document.getElementById('tabla-ministerio-' + target);
            if (shouldScroll && panelActivo) {
                panelActivo.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        tarjetas.forEach(function(tarjeta) {
            tarjeta.addEventListener('click', function() {
                const target = String(tarjeta.dataset.targetMinisterio || '');
                if (target !== '') {
                    activarMinisterio(target, true);
                }
            });
        });
    })();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
