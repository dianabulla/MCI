<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Células</h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=personas/crear&return_to=celulas&return_url=<?= urlencode(PUBLIC_URL . '?url=celulas') ?>" class="btn btn-primary">+ Nueva Persona</a>
        <a href="<?= PUBLIC_URL ?>?url=celulas/exportarExcel<?= !empty($_GET['ministerio']) ? '&ministerio=' . urlencode((string)$_GET['ministerio']) : '' ?><?= !empty($_GET['lider']) ? '&lider=' . urlencode((string)$_GET['lider']) : '' ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
        <?php if (AuthController::tienePermiso('materiales_celulas', 'ver')): ?>
            <a href="<?= PUBLIC_URL ?>?url=celulas/materiales" class="btn btn-secondary">Material Células (PDF)</a>
        <?php endif; ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=celulas/crear" class="btn btn-primary">+ Nueva Célula</a>
    </div>
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
$sections = $sections ?? [];
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
?>

<?php if (!empty($ministerioGrupos)): ?>
<div class="ministerio-cards-grid">
    <?php foreach ($ministerioGrupos as $ministerioNombre => $celulasMinisterio): ?>
        <?php
        $totalPersonasMinisterio = 0;
        foreach ($celulasMinisterio as $celulaMinisterio) {
            $totalPersonasMinisterio += (int)($celulaMinisterio['total_personas'] ?? 0);
        }
        ?>
        <div class="dashboard-card ministerio-card" data-ministerio-card="1">
            <button type="button" class="ministerio-card-header" data-ministerio-toggle="1">
                <div>
                    <h3 style="margin:0;"><?= htmlspecialchars($ministerioNombre) ?></h3>
                    <div class="value" style="font-size: 18px; color:#17a2b8;"><?= count($celulasMinisterio) ?> célula(s)</div>
                </div>
                <div style="text-align:right;">
                    <span class="meta-pill">Personas: <?= number_format($totalPersonasMinisterio) ?></span>
                    <span class="ministerio-arrow" aria-hidden="true">▾</span>
                </div>
            </button>

            <div class="ministerio-celulas" data-ministerio-body="1" hidden>
                <div class="ministerio-celulas-grid">
                    <?php foreach ($celulasMinisterio as $section): ?>
                        <div class="dashboard-card" style="border-left-color:#28a745;">
                            <h3><?= htmlspecialchars((string)$section['label']) ?></h3>
                            <div class="section-meta" style="margin-bottom:10px;">
                                <span class="meta-pill">Líder: <?= htmlspecialchars((string)$section['lider']) ?></span>
                                <span class="meta-pill">Anfitrión: <?= htmlspecialchars((string)$section['anfitrion']) ?></span>
                                <span class="meta-pill">Personas: <?= number_format((int)$section['total_personas']) ?></span>
                            </div>

                            <div class="section-meta" style="margin-bottom:12px;">
                                <?php if (!empty($section['direccion'])): ?>
                                    <span class="meta-pill">Dirección: <?= htmlspecialchars((string)$section['direccion']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($section['dia'])): ?>
                                    <span class="meta-pill">Día: <?= htmlspecialchars((string)$section['dia']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($section['hora'])): ?>
                                    <span class="meta-pill">Hora: <?= htmlspecialchars((string)$section['hora']) ?></span>
                                <?php endif; ?>
                            </div>

                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                <a href="<?= PUBLIC_URL ?>?url=celulas/detalle&id=<?= (int)$section['id_celula'] ?>" class="btn btn-sm btn-secondary">Ver personas</a>
                                <a href="<?= PUBLIC_URL ?>?url=personas/crear&return_to=celulas&return_url=<?= urlencode(PUBLIC_URL . '?url=celulas') ?>&celula=<?= (int)$section['id_celula'] ?>" class="btn btn-sm btn-primary">+ Nueva persona</a>
                                <?php if (AuthController::tienePermiso('asistencias', 'crear')): ?>
                                    <a href="<?= PUBLIC_URL ?>?url=asistencias/registrar&celula=<?= (int)$section['id_celula'] ?>" class="btn btn-sm btn-success">Asistencias</a>
                                <?php endif; ?>
                                <a href="<?= PUBLIC_URL ?>?url=celulas/editar&id=<?= (int)$section['id_celula'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                <a href="<?= PUBLIC_URL ?>?url=celulas/eliminar&id=<?= (int)$section['id_celula'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta célula?')">Eliminar</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
    <div class="table-container">
        <p class="text-center" style="padding: 20px; color: #666; margin: 0;">No hay células registradas</p>
    </div>
<?php endif; ?>

<style>
.ministerio-cards-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 14px;
}

.ministerio-card {
    border-left-color: #17a2b8;
}

.ministerio-card-header {
    width: 100%;
    border: 0;
    background: transparent;
    padding: 0;
    text-align: left;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    cursor: pointer;
}

.ministerio-arrow {
    display: inline-block;
    margin-left: 8px;
    transition: transform .2s ease;
}

.ministerio-card.is-open .ministerio-arrow {
    transform: rotate(180deg);
}

.ministerio-celulas {
    margin-top: 12px;
}

.ministerio-celulas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 12px;
}

@media (max-width: 800px) {
    .ministerio-card-header {
        align-items: flex-start;
    }

    .ministerio-celulas-grid {
        grid-template-columns: 1fr;
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
        const cards = document.querySelectorAll('[data-ministerio-card="1"]');
        if (!cards.length) {
            return;
        }

        cards.forEach(function(card) {
            const button = card.querySelector('[data-ministerio-toggle="1"]');
            const body = card.querySelector('[data-ministerio-body="1"]');
            if (!button || !body) {
                return;
            }

            button.addEventListener('click', function() {
                const isHidden = body.hasAttribute('hidden');
                if (isHidden) {
                    body.removeAttribute('hidden');
                    card.classList.add('is-open');
                } else {
                    body.setAttribute('hidden', 'hidden');
                    card.classList.remove('is-open');
                }
            });
        });
    })();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
