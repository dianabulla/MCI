<?php include VIEWS . '/layout/header.php'; ?>

<?php if ((string)($_GET['guardado'] ?? '') === '1'): ?>
<div class="alert alert-success" style="margin-bottom: 16px;">
    <i class="bi bi-check-circle"></i> Célula guardada correctamente.
</div>
<?php endif; ?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
    <h2 style="margin:0;">Células</h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <?php if (AuthController::esAdministrador() || AuthController::puede('reportes:ver')): ?>
            <a href="<?= PUBLIC_URL ?>?url=reportes&tipo=celulas" class="btn btn-secondary">Reporte de Células</a>
        <?php endif; ?>
        <?php if (AuthController::puede('celulas:crear')): ?>
            <a href="<?= PUBLIC_URL ?>?url=celulas/crear" class="btn btn-primary">+ Nueva célula</a>
        <?php endif; ?>
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

        <?php if (!empty($puede_marcar_entrego_sobre)): ?>
        <div class="form-group" style="margin-bottom: 0;">
            <label for="semana_sobre">Semana del sobre (lun. a dom.)</label>
            <input type="date" id="semana_sobre" name="semana_sobre" class="form-control"
                   value="<?= htmlspecialchars((string)($semana_sobre_referencia ?? $semana_inicio_sobre ?? '')) ?>"
                   max="<?= htmlspecialchars(date('Y-m-d')) ?>">
            <small style="color:#637087; display:block; margin-top:4px;">
                <?= !empty($semana_inicio_sobre) && !empty($semana_fin_sobre)
                    ? (date('d/m/Y', strtotime((string)$semana_inicio_sobre)) . ' – ' . date('d/m/Y', strtotime((string)$semana_fin_sobre)))
                    : '' ?>
                <?= !empty($es_semana_vencida_sobre) ? ' · Semana vencida' : '' ?>
            </small>
        </div>
        <?php endif; ?>

        <div class="form-group" style="margin-bottom: 0; display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= PUBLIC_URL ?>?url=celulas" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<?php if (!empty($puedeVerMaterialCelulasEnEnviar)): ?>
<div class="enviar-submodules-cards" style="margin: 28px 0; padding: 0;">
    <div class="enviar-cards-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px;">
        <?php if ($puedeVerMaterialCelulasEnEnviar): ?>
        <a href="<?= PUBLIC_URL ?>?url=celulas/materiales" class="enviar-card-link" style="text-decoration: none; transition: transform 0.2s ease, box-shadow 0.2s ease;">
            <div class="enviar-card" style="
                background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
                border: 2px solid #7c3aed;
                border-radius: 12px;
                padding: 24px;
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                cursor: pointer;
                transition: all 0.2s ease;
            ">
                <div>
                    <div class="enviar-card-icon" style="
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        width: 50px;
                        height: 50px;
                        background: #7c3aed;
                        color: white;
                        border-radius: 10px;
                        margin-bottom: 16px;
                        font-size: 24px;
                    ">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </div>
                    <h3 style="margin: 0 0 8px 0; color: #1f2937; font-size: 18px; font-weight: 700;">Material Células</h3>
                    <p style="margin: 0; color: #6b7280; font-size: 14px; line-height: 1.5;">
                        Accede a los materiales en PDF para las células. Gestiona y distribuye recursos educativos.
                    </p>
                </div>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(124, 58, 237, 0.2);">
                    <span style="color: #7c3aed; font-weight: 600; font-size: 13px;">Ver materiales →</span>
                </div>
            </div>
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
$sections = is_array($sections ?? null) ? $sections : [];
$puedeMarcarEntregoSobre = !empty($puede_marcar_entrego_sobre);
$semanaInicioSobre = (string)($semana_inicio_sobre ?? '');
$semanaFinSobre = (string)($semana_fin_sobre ?? '');
$fechaReporteCelula = $semanaFinSobre !== '' ? $semanaFinSobre : $semanaInicioSobre;
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
$seguimientoPorMinisterio = is_array($seguimientoPorMinisterio ?? null) ? $seguimientoPorMinisterio : [];
foreach ($seguimientoPorMinisterio as $ministerioSeguimiento => $_personasMinSeg) {
    if (!isset($ministerioGrupos[$ministerioSeguimiento])) {
        $ministerioGrupos[$ministerioSeguimiento] = [];
    }
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
        $totalSeguimientoMinisterio = count($seguimientoPorMinisterio[$ministerioNombre] ?? []);
        ?>
        <button
            type="button"
            class="dashboard-card celulas-summary-card <?= $ministerioActivoInicial === (string)$ministerioNombre ? 'is-active' : '' ?>"
            data-target-ministerio="<?= htmlspecialchars($ministerioKey) ?>"
            style="border-left-color:#17a2b8; text-align:left; cursor:pointer;"
        >
            <h3><?= htmlspecialchars($ministerioNombre) ?></h3>
            <div class="value" style="color:#17a2b8;"><?= count($celulasMinisterio) ?></div>
            <small style="color:#637087;">Miembros: <?= number_format($totalPersonasMinisterio) ?> · En seguimiento: <?= (int)$totalSeguimientoMinisterio ?> · Clic para ver detalle</small>
        </button>
    <?php endforeach; ?>
</div>

<?php foreach ($ministerioGrupos as $ministerioNombre => $celulasMinisterio): ?>
    <?php $ministerioKey = $slugMinisterio($ministerioNombre); ?>
    <div id="tabla-ministerio-<?= htmlspecialchars($ministerioKey) ?>" class="card celulas-detalle-card" <?= $ministerioActivoInicial === (string)$ministerioNombre ? '' : 'hidden' ?> style="margin-bottom: 18px;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
            <h3 style="margin:0;">Ministerio: <?= htmlspecialchars($ministerioNombre) ?></h3>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <small style="color:#637087;">Células: <?= count($celulasMinisterio) ?></small>
                <?php
                $personasSegMin = $seguimientoPorMinisterio[$ministerioNombre] ?? [];
                $payloadSegMin = [];
                foreach ($personasSegMin as $pSegMin) {
                    $payloadSegMin[] = [
                        'id' => (int)($pSegMin['Id_Persona'] ?? 0),
                        'nombre' => (string)($pSegMin['nombre'] ?? ''),
                        'telefono' => (string)($pSegMin['telefono'] ?? ''),
                        'celula' => (string)($pSegMin['celula'] ?? ''),
                        'lider' => (string)($pSegMin['lider'] ?? ''),
                    ];
                }
                ?>
                <button
                    type="button"
                    class="btn btn-sm celulas-action-btn celulas-action-btn--seguimiento js-ver-seguimiento-celula"
                    data-titulo="<?= htmlspecialchars('En seguimiento · ' . $ministerioNombre, ENT_QUOTES, 'UTF-8') ?>"
                    data-mostrar-celula="1"
                    data-personas="<?= htmlspecialchars(json_encode($payloadSegMin, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>"
                    title="Ver personas en seguimiento del ministerio"
                >
                    <i class="bi bi-signpost-2" aria-hidden="true"></i>
                    <span>En seguimiento</span>
                    <span class="celulas-seguimiento-badge"><?= count($payloadSegMin) ?></span>
                </button>
            </div>
        </div>

        <div class="table-container">
            <table class="data-table celulas-data-table">
                <thead>
                    <tr>
                        <th>Célula</th>
                        <th style="width:120px;">Miembros</th>
                        <?php if ($puedeMarcarEntregoSobre): ?>
                            <th style="width:168px;">Sobre entregado</th>
                        <?php endif; ?>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($celulasMinisterio as $section): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars((string)$section['label']) ?></strong>
                                <?php if (strtolower(trim((string)($section['estado'] ?? ''))) === 'cerrada'): ?>
                                    <span class="celulas-estado-cerrada">Cerrada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="celulas-count-badge" title="Personas activas asignadas a esta célula, aunque no asistan. El líder no entra en este número.">
                                    <?= (int)($section['total_personas'] ?? 0) ?>
                                </span>
                            </td>
                            <?php if ($puedeMarcarEntregoSobre): ?>
                            <td>
                                <?php
                                $estadoSobreCel = $section['entrego_sobre'] ?? null;
                                $marcadoSi = ($estadoSobreCel === 1 || $estadoSobreCel === true || $estadoSobreCel === '1');
                                $marcadoNo = ($estadoSobreCel === 0 || $estadoSobreCel === false || $estadoSobreCel === '0');
                                ?>
                                <div class="entrego-sobre-row entrego-sobre-row--table" data-id-celula="<?= (int)$section['id_celula'] ?>" data-semana-inicio="<?= htmlspecialchars($semanaInicioSobre) ?>">
                                    <label class="entrego-sobre-x-label is-si <?= $marcadoSi ? 'is-active' : '' ?>">
                                        <span>Sí</span>
                                        <span class="entrego-sobre-box" aria-hidden="true"><?= $marcadoSi ? 'X' : '' ?></span>
                                        <input type="checkbox" class="entrego-sobre-x" value="1" <?= $marcadoSi ? 'checked' : '' ?>>
                                    </label>
                                    <label class="entrego-sobre-x-label is-no <?= $marcadoNo ? 'is-active' : '' ?>">
                                        <span>No</span>
                                        <span class="entrego-sobre-box" aria-hidden="true"><?= $marcadoNo ? 'X' : '' ?></span>
                                        <input type="checkbox" class="entrego-sobre-x" value="0" <?= $marcadoNo ? 'checked' : '' ?>>
                                    </label>
                                    <span class="entrego-sobre-status" aria-live="polite"></span>
                                </div>
                            </td>
                            <?php endif; ?>
                            <td>
                                <div class="celulas-actions-row">
                                    <a href="<?= PUBLIC_URL ?>?url=celulas/detalle&id=<?= (int)$section['id_celula'] ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--icon" title="Ver" aria-label="Ver">
                                        <i class="bi bi-eye-fill" aria-hidden="true"></i>
                                    </a>
                                    <?php if (AuthController::puede('asistencias:crear')): ?>
                                        <a href="<?= PUBLIC_URL ?>?url=asistencias/registrar&celula=<?= (int)$section['id_celula'] ?><?= $fechaReporteCelula !== '' ? '&fecha=' . urlencode($fechaReporteCelula) : '' ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--report">Reportar célula</a>
                                    <?php endif; ?>
                                    <?php if (AuthController::puede('celulas:editar')): ?>
                                    <a href="<?= PUBLIC_URL ?>?url=celulas/editar&id=<?= (int)$section['id_celula'] ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--icon celulas-action-btn--edit" title="Editar" aria-label="Editar">
                                        <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (AuthController::puede('celulas:eliminar')): ?>
                                    <a href="<?= PUBLIC_URL ?>?url=celulas/eliminar&id=<?= (int)$section['id_celula'] ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--icon celulas-action-btn--delete" title="Eliminar" aria-label="Eliminar" onclick="return confirm('¿Eliminar esta célula?')">
                                        <i class="bi bi-trash-fill" aria-hidden="true"></i>
                                    </a>
                                    <?php endif; ?>
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
.celulas-estado-cerrada {
    display: inline-block;
    margin-left: 8px;
    padding: 2px 8px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #475569;
    font-size: 0.75rem;
    font-weight: 700;
    vertical-align: middle;
}
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

.celulas-detalle-card h3 {
    font-size: 1.05rem;
}

.celulas-detalle-card > div:first-child small {
    font-size: 0.8125rem;
}

.celulas-data-table th,
.celulas-data-table td {
    padding: 7px 10px;
    font-size: 0.875rem;
    line-height: 1.35;
    vertical-align: middle;
}

.celulas-data-table thead th {
    font-size: 0.8125rem;
}

.celulas-count-badge {
    display: inline-flex;
    min-width: 36px;
    height: 26px;
    align-items: center;
    justify-content: center;
    padding: 0 9px;
    border-radius: 999px;
    background: #e8f1ff;
    border: 1px solid #c9dcfb;
    color: #1f4f8a;
    font-size: 0.8125rem;
    font-weight: 700;
}

.celulas-data-table th:nth-child(2),
.celulas-data-table td:nth-child(2) {
    white-space: nowrap;
}

.celulas-data-table .btn {
    padding: 5px 9px;
    font-size: 0.75rem;
    line-height: 1.25;
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
    min-width: 32px;
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8125rem;
}

.celulas-action-btn--seguimiento {
    min-width: 32px;
    width: auto;
    height: 32px;
    padding: 0 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    background: #ecfdf5;
    border-color: #99f6e4;
    color: #0f766e;
}

.celulas-action-btn--seguimiento:hover {
    background: #ccfbf1;
    color: #115e59;
}

.celulas-seguimiento-badge {
    display: inline-flex;
    min-width: 18px;
    height: 18px;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: #0f766e;
    color: #fff;
    font-size: 10px;
    padding: 0 5px;
    line-height: 1;
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
    background: #fff1f1;
    border-color: #f0c9c9;
    color: #9b2e3a;
}

.entrego-sobre-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.entrego-sobre-x-label {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
    font-size: 0.8rem;
    color: #475569;
    cursor: pointer;
    user-select: none;
}

.entrego-sobre-x-label input {
    position: absolute;
    opacity: 0;
    width: 1px;
    height: 1px;
}

.entrego-sobre-box {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border: 2px solid #64748b;
    border-radius: 4px;
    background: #fff;
    color: #0f172a;
    font-size: 14px;
    font-weight: 800;
    line-height: 1;
}

.entrego-sobre-x-label.is-si.is-active { color: #166534; }
.entrego-sobre-x-label.is-si.is-active .entrego-sobre-box {
    border-color: #16a34a;
    background: #f0fdf4;
    color: #15803d;
}

.entrego-sobre-x-label.is-no.is-active { color: #991b1b; }
.entrego-sobre-x-label.is-no.is-active .entrego-sobre-box {
    border-color: #dc2626;
    background: #fef2f2;
    color: #b91c1c;
}

.entrego-sobre-status {
    font-size: 11px;
    color: #64748b;
    min-height: 14px;
}

.entrego-sobre-status.success { color: #1f7a44; }
.entrego-sobre-status.error { color: #b42318; }

.celulas-action-btn--delete:hover {
    background: #ffe4e7;
    color: #8a2631;
}

/* Estilos para tarjetas de Material Células y Asistencias */
.enviar-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 18px;
}

.enviar-card-link {
    text-decoration: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.enviar-card-link:hover .enviar-card {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.12);
}

.enviar-card {
    background: linear-gradient(135deg, #f5f3ff 0%, #f0f9ff 100%);
    border-radius: 12px;
    padding: 24px;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
}

.enviar-card h3 {
    color: #1f2937;
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 8px 0;
}

.enviar-card p {
    margin: 0;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.5;
}

.enviar-card-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    border-radius: 10px;
    margin-bottom: 16px;
    font-size: 24px;
    transition: transform 0.2s ease;
}

.enviar-card-link:hover .enviar-card-icon {
    transform: scale(1.1);
}

@media (max-width: 800px) {
    .celulas-summary-grid {
        grid-template-columns: 1fr !important;
    }

    .enviar-cards-grid {
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

    (function() {
        const checks = Array.from(document.querySelectorAll('.entrego-sobre-x'));
        checks.forEach(function(check) {
            check.addEventListener('change', function() {
                const row = check.closest('.entrego-sobre-row');
                if (!row) return;
                const idCelula = String(row.dataset.idCelula || '');
                const semanaInicio = String(row.dataset.semanaInicio || '');
                const checksFila = Array.from(row.querySelectorAll('.entrego-sobre-x'));
                const status = row.querySelector('.entrego-sobre-status');
                const previo = checksFila.map(function(item) {
                    return item === check ? !item.checked : item.checked;
                });

                if (check.checked) {
                    checksFila.forEach(function(otro) {
                        if (otro !== check) {
                            otro.checked = false;
                        }
                    });
                }

                const marcado = checksFila.find(function(item) { return item.checked; });
                if (!marcado) {
                    check.checked = true;
                }
                const entregoSobre = (marcado || check).value === '1' ? '1' : '0';

                function pintar() {
                    row.querySelectorAll('.entrego-sobre-x-label').forEach(function(opt) {
                        const input = opt.querySelector('.entrego-sobre-x');
                        const box = opt.querySelector('.entrego-sobre-box');
                        const activo = !!(input && input.checked);
                        opt.classList.toggle('is-active', activo);
                        if (box) box.textContent = activo ? 'X' : '';
                    });
                }
                pintar();

                checksFila.forEach(function(item) { item.disabled = true; });
                if (status) {
                    status.textContent = 'Guardando...';
                    status.classList.remove('success', 'error');
                }

                fetch(<?= json_encode(public_app_url('asistencias/actualizarEntregoSobre'), JSON_UNESCAPED_UNICODE) ?>, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: 'id_celula=' + encodeURIComponent(idCelula)
                        + '&semana_inicio=' + encodeURIComponent(semanaInicio)
                        + '&entrego_sobre=' + encodeURIComponent(entregoSobre)
                })
                .then(function(response) { return response.text(); })
                .then(function(text) {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        throw new Error('Respuesta invalida');
                    }
                })
                .then(function(data) {
                    checksFila.forEach(function(item) { item.disabled = false; });
                    if (!data || !data.success) {
                        throw new Error((data && data.error) ? data.error : 'No se pudo guardar');
                    }
                    if (status) {
                        status.textContent = 'Guardado';
                        status.classList.remove('error');
                        status.classList.add('success');
                        setTimeout(function() {
                            status.textContent = '';
                            status.classList.remove('success');
                        }, 1400);
                    }
                })
                .catch(function() {
                    checksFila.forEach(function(item, idx) {
                        item.disabled = false;
                        item.checked = !!previo[idx];
                    });
                    pintar();
                    if (status) {
                        status.textContent = 'Error';
                        status.classList.remove('success');
                        status.classList.add('error');
                    }
                });
            });
        });
    })();
</script>

<?php include VIEWS . '/personas/partials/modal_seguimiento.php'; ?>
<?php include VIEWS . '/celulas/partials/modal_lista_seguimiento.php'; ?>
<?php include VIEWS . '/layout/footer.php'; ?>
