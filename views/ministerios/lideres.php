<?php include VIEWS . '/layout/header.php'; ?>

<?php
$idMinisterioFiltro = (int)($id_ministerio_filtro ?? 0);
$nombreMinisterioFiltro = trim((string)($nombre_ministerio_filtro ?? ''));
$hayFiltroMinisterio = $idMinisterioFiltro > 0;
?>

<div class="page-header">
    <div>
        <h2>Equipo Principal</h2>
        <?php if ($hayFiltroMinisterio): ?>
        <small style="color:#5c6f8b;">Ministerio: <?= htmlspecialchars($nombreMinisterioFiltro !== '' ? $nombreMinisterioFiltro : ('ID ' . $idMinisterioFiltro)) ?></small>
        <?php endif; ?>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <?php if ($hayFiltroMinisterio): ?>
        <a href="<?= PUBLIC_URL ?>?url=ministerios/equipo-principal" class="btn btn-secondary">Ver todos</a>
        <?php endif; ?>
        <a href="<?= PUBLIC_URL ?>?url=ministerios" class="btn btn-secondary">← Volver a Ministerios</a>
    </div>
</div>

<?php
$bloques = [
    [
        'id' => 'hombres',
        'titulo' => 'Equipo 12 - Hombres',
        'equipos' => $equipos_12_hombres ?? [],
        'vacio' => 'No hay equipos de 12 de hombres para mostrar.',
        'color' => '#0f5fca'
    ],
    [
        'id' => 'mujeres',
        'titulo' => 'Equipo 12 - Mujeres',
        'equipos' => $equipos_12_mujeres ?? [],
        'vacio' => 'No hay equipos de 12 de mujeres para mostrar.',
        'color' => '#b23c6f'
    ],
];

$bloqueInicial = !empty($bloques) ? (string)$bloques[0]['id'] : '';
?>

<div class="dashboard-grid ministerios-lideres-summary-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin:16px 0; gap:12px;">
    <?php foreach ($bloques as $bloque): ?>
    <button
        type="button"
        class="dashboard-card ministerios-lideres-summary-card js-lider-card <?= $bloqueInicial === (string)$bloque['id'] ? 'is-active' : '' ?>"
        data-target="<?= htmlspecialchars((string)$bloque['id']) ?>"
        style="border-left-color:<?= htmlspecialchars((string)$bloque['color']) ?>; text-align:left; cursor:pointer;"
    >
        <h3><?= htmlspecialchars((string)$bloque['titulo']) ?></h3>
        <div class="value" style="color:<?= htmlspecialchars((string)$bloque['color']) ?>;"><?= count($bloque['equipos'] ?? []) ?></div>
        <small style="color:#637087;">Clic para ver la tabla</small>
    </button>
    <?php endforeach; ?>
</div>

<div style="display:grid; gap:14px; margin-bottom:18px;">
    <?php foreach ($bloques as $bloque): ?>
    <div class="card js-lider-panel" data-panel="<?= htmlspecialchars((string)$bloque['id']) ?>" <?= $bloqueInicial === (string)$bloque['id'] ? '' : 'hidden' ?>>
        <div class="card-header" style="padding:12px 14px; border-bottom:1px solid #dde6f4; font-weight:700; color:#1f365f;">
            <?= htmlspecialchars((string)$bloque['titulo']) ?>
        </div>
        <div class="table-container">
            <table class="data-table ministerios-equipo-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bloque['equipos'])): ?>
                        <?php foreach ($bloque['equipos'] as $equipo): ?>
                            <?php $lider = $equipo['lider'] ?? []; ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($lider['nombre'] ?? 'Sin nombre')) ?></td>
                                <td><?= htmlspecialchars((string)($lider['telefono'] ?? 'N/A')) ?></td>
                                <td><?= htmlspecialchars((string)($lider['direccion'] ?? 'N/A')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="text-center"><?= htmlspecialchars((string)$bloque['vacio']) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<style>
.ministerios-lideres-summary-card {
    appearance: none;
    border-top: 0;
    border-right: 0;
    border-bottom: 0;
    width: 100%;
    transition: transform 0.18s ease, box-shadow 0.18s ease, outline 0.18s ease;
}

.ministerios-lideres-summary-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(15, 35, 61, 0.08);
}

.ministerios-lideres-summary-card.is-active {
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12), 0 10px 22px rgba(15, 35, 61, 0.08);
}

.ministerios-lideres-summary-card:focus-visible {
    outline: 3px solid rgba(37, 99, 235, 0.22);
    outline-offset: 2px;
}

.ministerios-equipo-empty-state {
    padding: 16px;
    border-radius: 12px;
    background: #f7f9fc;
    color: #5b6d88;
    text-align: center;
}

.ministerios-equipo-table th,
.ministerios-equipo-table td {
    padding: 8px 10px;
    font-size: 12px;
    line-height: 1.3;
    vertical-align: middle;
}

@media (max-width: 800px) {
    .ministerios-lideres-summary-grid {
        grid-template-columns: 1fr !important;
    }
}
</style>

<script>
(function() {
    const cards = Array.from(document.querySelectorAll('.js-lider-card'));
    const panels = Array.from(document.querySelectorAll('.js-lider-panel'));

    if (!cards.length || !panels.length) {
        return;
    }

    function activar(target) {
        cards.forEach(function(card) {
            card.classList.toggle('is-active', String(card.dataset.target || '') === target);
        });

        panels.forEach(function(panel) {
            if (String(panel.dataset.panel || '') === target) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', 'hidden');
            }
        });
    }

    cards.forEach(function(card) {
        card.addEventListener('click', function() {
            const target = String(card.dataset.target || '');
            if (target !== '') {
                activar(target);
            }
        });
    });
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
