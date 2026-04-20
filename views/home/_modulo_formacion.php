<?php include VIEWS . '/layout/header.php'; ?>

<?php
$configModulo = $config_modulo ?? [];
$tituloModulo = (string)($configModulo['titulo'] ?? 'Modulo');
$rutaBase = (string)($configModulo['ruta_base'] ?? 'home');
$rutaExportar = (string)($configModulo['ruta_exportar'] ?? 'home');

$reportePendientes = $reporte_pendientes ?? ['total' => 0, 'rows' => []];
$filtroMinisterio = (string)($filtro_ministerio ?? '');
$filtroLider = (string)($filtro_lider ?? '');
$filtroBuscar = (string)($filtro_buscar ?? '');
$filtroGenero = (string)($filtro_genero ?? 'todos');
$inscripcionesPublicas = $inscripciones_publicas ?? [];
$programaReporte = (string)($programa_reporte ?? '');
$programaReporteLabel = (string)($programa_reporte_label ?? 'Programa');
$programasOpciones = $programas_opciones ?? [];
$tarjetasResumen = $tarjetas_resumen ?? [];

$ministerioLabelSeleccionado = 'Todos';
if ($filtroMinisterio !== '') {
    foreach (($ministerios_disponibles ?? []) as $ministerioItem) {
        if ((string)($ministerioItem['Id_Ministerio'] ?? '') === $filtroMinisterio) {
            $ministerioLabelSeleccionado = (string)($ministerioItem['Nombre_Ministerio'] ?? 'Todos');
            break;
        }
    }
}

$liderLabelSeleccionado = 'Todos';
if ($filtroLider !== '') {
    foreach (($lideres_disponibles ?? []) as $liderItem) {
        if ((string)($liderItem['Id_Persona'] ?? '') === $filtroLider) {
            $liderLabelSeleccionado = (string)($liderItem['Nombre_Completo'] ?? 'Todos');
            break;
        }
    }
}

$generoLabelSeleccionado = 'Todos';
$mapaGeneroLabel = [
    'todos' => 'Todos',
    'hombres' => 'Hombres',
    'mujeres' => 'Mujeres',
    'joven_hombre' => 'Joven Hombre',
    'joven_mujer' => 'Joven Mujer',
];
if (isset($mapaGeneroLabel[$filtroGenero])) {
    $generoLabelSeleccionado = $mapaGeneroLabel[$filtroGenero];
}

$filtrosActivos = 0;
if ($filtroBuscar !== '') { $filtrosActivos++; }
if ($filtroMinisterio !== '') { $filtrosActivos++; }
if ($filtroLider !== '') { $filtrosActivos++; }
if ($filtroGenero !== '' && $filtroGenero !== 'todos') { $filtrosActivos++; }
if ($programaReporte !== '') { $filtrosActivos++; }

$exportUrl = PUBLIC_URL . '?url=' . $rutaExportar . '&' . http_build_query([
    'ministerio' => $filtroMinisterio,
    'lider' => $filtroLider,
    'buscar' => $filtroBuscar,
    'genero' => $filtroGenero,
    'insc_programa' => $programaReporte,
]);
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;"><?= htmlspecialchars($tituloModulo) ?></h2>
        <small style="color:#637087;">Programa actual: <strong><?= htmlspecialchars($programaReporteLabel) ?></strong>.</small>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= htmlspecialchars($exportUrl) ?>" class="btn btn-secondary">Exportar CSV</a>
        <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/registro-publico" class="btn btn-secondary" target="_blank" rel="noopener">Formulario publico</a>
        <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/asistencia-publica" class="btn btn-secondary" target="_blank" rel="noopener">Asistencia publica</a>
        <a href="<?= PUBLIC_URL ?>?url=home" class="btn btn-primary">Volver al panel</a>
    </div>
</div>

<div class="card report-card" style="margin-bottom:12px; padding:10px 14px; background:#f6fbff; border-color:#d9e6f5;">
    <div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap;">
        <div style="font-weight:600;color:#244a74;">
            Filtros activos: <?= (int)$filtrosActivos ?>
        </div>
        <small style="color:#4f6480;">Los filtros se mantienen hasta presionar Limpiar.</small>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
        <span class="filter-chip">Programa: <?= htmlspecialchars($programaReporteLabel) ?></span>
        <span class="filter-chip">Ministerio: <?= htmlspecialchars($ministerioLabelSeleccionado) ?></span>
        <span class="filter-chip">Lider: <?= htmlspecialchars($liderLabelSeleccionado) ?></span>
        <span class="filter-chip">Genero: <?= htmlspecialchars($generoLabelSeleccionado) ?></span>
        <span class="filter-chip">Nombre: <?= $filtroBuscar !== '' ? htmlspecialchars($filtroBuscar) : 'Todos' ?></span>
    </div>
</div>

<div class="card report-card" style="margin-bottom:18px; padding:14px;">
    <form method="GET" action="<?= PUBLIC_URL ?>" class="filters-inline" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
        <input type="hidden" name="url" value="<?= htmlspecialchars($rutaBase) ?>">

        <div class="form-group" style="margin:0; min-width:280px;">
            <label for="filtro_buscar">Buscar por nombre</label>
            <input type="text" id="filtro_buscar" name="buscar" class="form-control" placeholder="Nombre o apellido" value="<?= htmlspecialchars($filtroBuscar) ?>">
        </div>

        <div class="form-group" style="margin:0; min-width:220px;">
            <label for="filtro_ministerio">Ministerio</label>
            <select id="filtro_ministerio" name="ministerio" class="form-control">
                <option value="">Todos</option>
                <?php foreach (($ministerios_disponibles ?? []) as $ministerio): ?>
                    <option value="<?= (int)($ministerio['Id_Ministerio'] ?? 0) ?>" <?= $filtroMinisterio === (string)($ministerio['Id_Ministerio'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)($ministerio['Nombre_Ministerio'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin:0; min-width:220px;">
            <label for="filtro_lider">Lider</label>
            <select id="filtro_lider" name="lider" class="form-control">
                <option value="">Todos</option>
                <?php foreach (($lideres_disponibles ?? []) as $lider): ?>
                    <option value="<?= (int)($lider['Id_Persona'] ?? 0) ?>" <?= $filtroLider === (string)($lider['Id_Persona'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)($lider['Nombre_Completo'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin:0; min-width:180px;">
            <label for="filtro_genero">Genero</label>
            <select id="filtro_genero" name="genero" class="form-control">
                <option value="todos" <?= $filtroGenero === 'todos' || $filtroGenero === '' ? 'selected' : '' ?>>Todos</option>
                <option value="hombres" <?= $filtroGenero === 'hombres' ? 'selected' : '' ?>>Hombres</option>
                <option value="mujeres" <?= $filtroGenero === 'mujeres' ? 'selected' : '' ?>>Mujeres</option>
                <option value="joven_hombre" <?= $filtroGenero === 'joven_hombre' ? 'selected' : '' ?>>Joven Hombre</option>
                <option value="joven_mujer" <?= $filtroGenero === 'joven_mujer' ? 'selected' : '' ?>>Joven Mujer</option>
            </select>
        </div>

        <div class="form-group" style="margin:0; min-width:260px;">
            <label for="insc_programa">Programa</label>
            <select id="insc_programa" name="insc_programa" class="form-control">
                <?php foreach ($programasOpciones as $key => $label): ?>
                    <option value="<?= htmlspecialchars((string)$key) ?>" <?= $programaReporte === (string)$key ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filters-actions" style="display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">Aplicar</button>
            <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaBase) ?>" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<?php if (!empty($tarjetasResumen)): ?>
<div class="dashboard-grid" style="grid-template-columns: repeat(4, minmax(0, 320px)); margin:18px 0;">
    <?php foreach ($tarjetasResumen as $tarjeta): ?>
    <div class="dashboard-card" style="border-left-color:#1e4a89;">
        <h3><?= htmlspecialchars((string)($tarjeta['label'] ?? 'Programa')) ?></h3>
        <div class="value" style="color:#1e4a89;"><?= (int)($tarjeta['total'] ?? 0) ?></div>
        <small style="color:#637087;">Registros en este programa.</small>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card report-card" style="padding:14px; margin-bottom:14px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <h3 style="margin:0;">Detalle Pendientes: <?= htmlspecialchars($programaReporteLabel) ?></h3>
        <small style="color:#637087;">Total registros: <?= (int)($reportePendientes['total'] ?? 0) ?></small>
    </div>

    <div class="table-container">
        <table class="data-table uv-table-ordenada">
            <thead>
                <tr>
                    <th>Persona</th>
                    <th>Lider</th>
                    <th style="width:180px;">Se inscribe</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reportePendientes['rows'])): ?>
                    <?php foreach (($reportePendientes['rows'] ?? []) as $row): ?>
                        <tr>
                            <td class="col-nowrap col-nombre"><?= htmlspecialchars((string)($row['nombre'] ?? '')) ?></td>
                            <td class="col-nowrap col-lider"><?= htmlspecialchars((string)($row['lider'] ?? '')) ?></td>
                            <td class="text-center">
                                <div class="uv-checklist-options" data-persona="<?= (int)($row['id_persona'] ?? 0) ?>" data-programa="<?= htmlspecialchars($programaReporte) ?>">
                                    <button type="button" class="uv-check-option js-uv-estado-option" data-value="1" aria-label="Marcar Si">
                                        <span class="uv-box"></span>
                                        <span>Si</span>
                                    </button>
                                    <button type="button" class="uv-check-option js-uv-estado-option" data-value="0" aria-label="Marcar No">
                                        <span class="uv-box"></span>
                                        <span>No</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center">No hay personas pendientes para <?= htmlspecialchars($programaReporteLabel) ?> con estos filtros.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card report-card" style="padding:14px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:10px;">
        <h3 style="margin:0;">Registros del formulario publico: <?= htmlspecialchars($programaReporteLabel) ?></h3>
        <small style="color:#637087;">Mostrando <?= (int)count($inscripcionesPublicas) ?> registros recientes (max. 300)</small>
    </div>

    <div class="table-container">
        <table class="data-table insc-table-ordenada">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Nombre</th>
                    <th>Cedula</th>
                    <th>Telefono</th>
                    <th>Lider</th>
                    <th>Asistencias</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($inscripcionesPublicas)): ?>
                    <?php foreach ($inscripcionesPublicas as $ins): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($ins['Fecha_Registro'] ?? '')) ?></td>
                            <td class="col-nowrap col-nombre"><?= htmlspecialchars((string)($ins['Nombre'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($ins['Cedula'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($ins['Telefono'] ?? '')) ?></td>
                            <td class="col-nowrap col-lider"><?= htmlspecialchars((string)($ins['Lider'] ?? '')) ?></td>
                            <?php $numeroAsistencias = ((string)($ins['Asistio_Clase'] ?? '') === '1') ? 1 : 0; ?>
                            <td><strong><?= $numeroAsistencias ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No hay registros de inscripciones para los filtros seleccionados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    const endpoint = <?= json_encode(PUBLIC_URL . '?url=home/escuelas-formacion/actualizar-estado') ?>;
    const programaSelect = document.getElementById('insc_programa');
    if (programaSelect && programaSelect.form) {
        programaSelect.addEventListener('change', function () {
            programaSelect.form.submit();
        });
    }

    document.querySelectorAll('.js-uv-estado-option').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const contenedor = btn.closest('.uv-checklist-options');
            if (!contenedor || btn.disabled) {
                return;
            }

            const personaId = String(contenedor.dataset.persona || '0');
            const programa = String(contenedor.dataset.programa || 'universidad_vida');
            const valorSeleccionado = String(btn.dataset.value || '');
            if (personaId === '0') {
                return;
            }

            contenedor.querySelectorAll('.js-uv-estado-option').forEach((opcion) => {
                opcion.disabled = true;
            });
            contenedor.classList.add('is-loading');

            try {
                const formData = new FormData();
                formData.append('id_persona', personaId);
                formData.append('programa', programa);
                formData.append('va', valorSeleccionado);

                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (!response.ok || !data.ok) {
                    throw new Error((data && data.error) || 'No se pudo guardar');
                }

                btn.classList.add('is-selected');
                contenedor.classList.remove('is-loading');
                contenedor.classList.add('is-done');

                setTimeout(() => {
                    window.location.reload();
                }, 220);
            } catch (error) {
                contenedor.classList.remove('is-loading');
                contenedor.querySelectorAll('.js-uv-estado-option').forEach((opcion) => {
                    opcion.disabled = false;
                });
                alert(error.message || 'No se pudo guardar el estado');
            }
        });
    });

}());
</script>

<style>
.filter-chip {
    display:inline-flex;
    align-items:center;
    border:1px solid #c8d7ea;
    background:#ffffff;
    color:#2a4a73;
    border-radius:999px;
    padding:4px 10px;
    font-size:12px;
    font-weight:600;
}

.uv-checklist-options {
    display:inline-flex;
    align-items:center;
    gap:8px;
}

.uv-checklist-options.is-loading {
    opacity:.7;
}

.uv-check-option {
    display:inline-flex;
    align-items:center;
    gap:6px;
    border:1px solid #cfd9ea;
    background:#f8fbff;
    color:#2a466a;
    border-radius:8px;
    padding:6px 10px;
    font-weight:600;
    cursor:pointer;
}

.uv-check-option:hover {
    background:#edf4ff;
    border-color:#a6bfdf;
}

.uv-check-option.is-selected {
    background:#e8f2ff;
    border-color:#4f7fbe;
    color:#1d4f92;
}

.uv-box {
    width:14px;
    height:14px;
    border:1px solid #7394bf;
    border-radius:3px;
    display:inline-block;
    background:#fff;
}

.uv-check-option.is-selected .uv-box {
    background:#2f65b5;
    border-color:#2f65b5;
    box-shadow: inset 0 0 0 2px #fff;
}

.uv-table-ordenada,
.insc-table-ordenada {
    table-layout: auto;
    width: max-content;
    min-width: 100%;
}

.uv-table-ordenada th,
.uv-table-ordenada td,
.insc-table-ordenada th,
.insc-table-ordenada td {
    vertical-align: middle;
    padding-top: 9px !important;
    padding-bottom: 9px !important;
}

.col-nowrap {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.uv-table-ordenada .col-nombre { min-width: 260px; }
.uv-table-ordenada .col-lider { min-width: 210px; }

.insc-table-ordenada .col-nombre { min-width: 240px; }
.insc-table-ordenada .col-lider { min-width: 190px; }
</style>

<?php include VIEWS . '/layout/footer.php'; ?>