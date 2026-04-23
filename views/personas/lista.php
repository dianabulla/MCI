<?php include VIEWS . '/layout/header.php'; ?>
<?php
$puedeVerPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'ver');
$puedeEditarPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'editar');
$puedeEliminarPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'eliminar');
$puedeCrearPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'crear');
$puedeExportarPersonas = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'editar');
$puedeGestionPlantillas = AuthController::esAdministrador()
    || AuthController::tienePermiso('personas_plantillas_whatsapp', 'ver');
$puedeVerFormularioPublico = AuthController::esAdministrador()
    || AuthController::tienePermiso('personas_formulario_publico', 'ver');
$mostrarAcciones = $puedeVerPersona || $puedeEditarPersona || $puedeEliminarPersona;
$mostrarEscaleraRapida = true;
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <h2 style="margin:0;">Discipulos</h2>
    <div class="personas-header-actions">
        <div class="personas-action-group personas-action-group-nav">
            <a href="<?= PUBLIC_URL ?>?url=personas" class="personas-action-pill is-active" aria-current="page">Discipulos</a>
            <a href="<?= PUBLIC_URL ?>?url=personas/ganar" class="personas-action-pill">Almas ganadas</a>
        </div>
        <div class="personas-action-group">
            <?php if ($puedeVerFormularioPublico): ?>
            <a href="<?= PUBLIC_URL ?>?url=registro_personas" class="personas-action-pill" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right"></i> Formulario público
            </a>
            <?php endif; ?>
            <?php if ($puedeExportarPersonas): ?>
            <a href="<?= PUBLIC_URL ?>?url=personas/exportarExcel<?= !empty($_GET['ministerio']) ? '&ministerio=' . urlencode((string)$_GET['ministerio']) : '' ?><?= !empty($_GET['lider']) ? '&lider=' . urlencode((string)$_GET['lider']) : '' ?><?= !empty($_GET['buscar']) ? '&buscar=' . urlencode((string)$_GET['buscar']) : '' ?>" class="personas-action-pill">
                <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
            </a>
            <?php endif; ?>
            <?php if ($puedeGestionPlantillas): ?>
            <a href="<?= PUBLIC_URL ?>?url=personas/plantillas-whatsapp" class="personas-action-pill">
                <i class="bi bi-chat-dots"></i> Plantilla mensaje what
            </a>
            <?php endif; ?>
            <?php if ($puedeCrearPersona): ?>
            <a href="<?= PUBLIC_URL ?>?url=personas/crear" class="personas-action-pill">+ Nuevo Discipulo</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-body">
        <?php
        $filtroPerfilListado = '';
        $filtroMinisterioListado = (string)($filtroMinisterioActual ?? ($_GET['ministerio'] ?? ''));
        $filtroLiderListado = (string)($filtroLiderActual ?? ($_GET['lider'] ?? ''));
        $lideresFiltradosFormulario = array_values(array_filter(($lideres ?? []), static function($lider) use ($filtroMinisterioListado) {
            if ($filtroMinisterioListado === '') {
                return true;
            }

            $idMinisterioLider = isset($lider['Id_Ministerio']) ? trim((string)$lider['Id_Ministerio']) : '';
            if ($filtroMinisterioListado === '0') {
                return $idMinisterioLider === '' || $idMinisterioLider === '0';
            }

            return $idMinisterioLider === $filtroMinisterioListado;
        }));
        $queryBasePersonas = [
            'url' => 'personas',
            'ministerio' => $filtroMinisterioListado,
            'lider' => $filtroLiderListado,
            'buscar' => (string)($filtroNombreActual ?? ($_GET['buscar'] ?? ''))
        ];
        $buildPersonasUrl = static function(array $extra = []) use ($queryBasePersonas) {
            $params = array_merge($queryBasePersonas, $extra);
            $params = array_filter($params, static function($value) {
                return $value !== null && $value !== '';
            });
            return PUBLIC_URL . '?' . http_build_query($params);
        };
        $returnUrlPersonas = $buildPersonasUrl();
        ?>
        <form method="GET" action="<?= PUBLIC_URL ?>" class="filters-inline" id="filtro_perfil_form">
            <input type="hidden" name="url" value="personas">
            <div class="form-group">
                <label for="filtro_ministerio" style="font-size: 14px; margin-bottom: 5px;">Filtro por ministerio</label>
                <select id="filtro_ministerio" name="ministerio" class="form-control">
                    <option value="" <?= $filtroMinisterioListado === '' ? 'selected' : '' ?>>Todos</option>
                    <option value="0" <?= $filtroMinisterioListado === '0' ? 'selected' : '' ?>>Sin ministerio</option>
                    <?php foreach (($ministerios ?? []) as $ministerio): ?>
                        <?php $idMinisterio = (string)($ministerio['Id_Ministerio'] ?? ''); ?>
                        <option value="<?= htmlspecialchars($idMinisterio) ?>" <?= $filtroMinisterioListado === $idMinisterio ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($ministerio['Nombre_Ministerio'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="filtro_lider" style="font-size: 14px; margin-bottom: 5px;">Filtro por líder</label>
                <select id="filtro_lider" name="lider" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($lideresFiltradosFormulario as $lider): ?>
                        <?php $idLider = (string)($lider['Id_Persona'] ?? ''); ?>
                        <option value="<?= htmlspecialchars($idLider) ?>" data-ministerio="<?= htmlspecialchars((string)($lider['Id_Ministerio'] ?? '')) ?>" <?= $filtroLiderListado === $idLider ? 'selected' : '' ?>>
                            <?= htmlspecialchars(trim((string)($lider['Nombre'] ?? '') . ' ' . (string)($lider['Apellido'] ?? ''))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="min-width: 240px;">
                <label for="filtro_nombre" style="font-size: 14px; margin-bottom: 5px;">Buscar por nombre</label>
                <input
                    type="text"
                    id="filtro_nombre"
                    name="buscar"
                    class="form-control"
                    placeholder="Ej: Juan Perez"
                    list="sugerencias_nombres_personas"
                    autocomplete="off"
                    value="<?= htmlspecialchars((string)($filtroNombreActual ?? ($_GET['buscar'] ?? ''))) ?>"
                >
                <datalist id="sugerencias_nombres_personas">
                    <?php foreach (($sugerenciasNombre ?? []) as $sugerenciaNombre): ?>
                        <option value="<?= htmlspecialchars((string)$sugerenciaNombre) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="filters-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Buscar
                </button>
                <?php if (!empty($_GET['buscar']) || !empty($_GET['ministerio']) || !empty($_GET['lider'])): ?>
                <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php
$normalizarRolPerfil = static function($rolNombre) {
    $rol = strtolower(trim((string)$rolNombre));
    return strtr($rol, [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n'
    ]);
};

$resolverCategoriaPerfil = static function(array $persona) use ($normalizarRolPerfil) {
    $rolNormalizado = $normalizarRolPerfil($persona['Nombre_Rol'] ?? '');
    $idRol = (int)($persona['Id_Rol'] ?? 0);
    $idMinisterio = (int)($persona['Id_Ministerio'] ?? 0);
    $idCelula = (int)($persona['Id_Celula'] ?? 0);
    $idLider = (int)($persona['Id_Lider'] ?? 0);
    $tieneAsignacionCompleta = ($idMinisterio > 0 && $idCelula > 0 && $idLider > 0);

    if (strpos($rolNormalizado, 'pastor') !== false) {
        return 'pastores';
    }

    if (
        $idRol === 8
        || strpos($rolNormalizado, 'lider de 12') !== false
        || strpos($rolNormalizado, 'lider 12') !== false
        || strpos($rolNormalizado, 'lideres de 12') !== false
    ) {
        return 'lideres_12';
    }

    if (
        strpos($rolNormalizado, 'lider de celula') !== false
        || strpos($rolNormalizado, 'lider celula') !== false
    ) {
        return 'lideres_celula';
    }

    $esRolDiscipular = (strpos($rolNormalizado, 'discipul') !== false || strpos($rolNormalizado, 'disipul') !== false);
    if ($esRolDiscipular && $tieneAsignacionCompleta) {
        return 'discipulos';
    }

    return 'otros_ocultos';
};

$esPendienteUbicacionRed = static function(array $persona) {
    $idMinisterio = (int)($persona['Id_Ministerio'] ?? 0);
    $idLider = (int)($persona['Id_Lider'] ?? 0);
    $idCelula = (int)($persona['Id_Celula'] ?? 0);
    $esAntiguo = (int)($persona['Es_Antiguo'] ?? 1) === 1;
    $rolNormalizado = strtolower(trim((string)($persona['Nombre_Rol'] ?? '')));
    $rolNormalizado = strtr($rolNormalizado, [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n'
    ]);
    $esRolDiscipular = (strpos($rolNormalizado, 'discipul') !== false || strpos($rolNormalizado, 'disipul') !== false);

    return $esAntiguo && $esRolDiscipular && ($idMinisterio <= 0 || $idLider <= 0 || $idCelula <= 0);
};

$conteoPorPerfil = [
    'pastores' => ['id' => 'pastores', 'label' => 'Pastores', 'total' => 0, 'color' => '#7c3aed', 'meta' => 'Discipulos'],
    'lideres_12' => ['id' => 'lideres_12', 'label' => 'Lideres de 12', 'total' => 0, 'color' => '#0ea5a4', 'meta' => 'Discipulos'],
    'lideres_celula' => ['id' => 'lideres_celula', 'label' => 'Lideres de celula', 'total' => 0, 'color' => '#f59e0b', 'meta' => 'Discipulos'],
    'discipulos' => ['id' => 'discipulos', 'label' => 'Discipulos', 'total' => 0, 'color' => '#10b981', 'meta' => 'Discipulos'],
    'pendientes_ubicacion' => ['id' => 'pendientes_ubicacion', 'label' => 'Por conectar a celula', 'total' => 0, 'color' => '#2563eb', 'meta' => 'Asignacion ministerial']
];

foreach (($personas ?? []) as $personaTmp) {
    $categoriaTmp = $resolverCategoriaPerfil((array)$personaTmp);
    if (isset($conteoPorPerfil[$categoriaTmp])) {
        $conteoPorPerfil[$categoriaTmp]['total']++;
    }

    if ($esPendienteUbicacionRed((array)$personaTmp)) {
        $conteoPorPerfil['pendientes_ubicacion']['total']++;
    }
}

$perfilActivoVisual = '';
?>

<div class="dashboard-grid personas-perfiles-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin:0 0 16px; gap:10px;">
    <?php foreach ($conteoPorPerfil as $resumenPerfil): ?>
    <button
        type="button"
        class="dashboard-card personas-perfil-card js-perfil-card <?= $perfilActivoVisual === (string)$resumenPerfil['id'] ? 'is-active' : '' ?>"
        data-perfil-id="<?= htmlspecialchars((string)$resumenPerfil['id']) ?>"
        style="border-left-color:<?= htmlspecialchars((string)$resumenPerfil['color']) ?>; text-align:left; cursor:pointer;"
    >
        <h3><?= htmlspecialchars((string)$resumenPerfil['label']) ?></h3>
        <div class="value" style="color:<?= htmlspecialchars((string)$resumenPerfil['color']) ?>;"><?= (int)$resumenPerfil['total'] ?></div>
        <small style="color:#637087;"><?= htmlspecialchars((string)($resumenPerfil['meta'] ?? 'Discipulos')) ?></small>
    </button>
    <?php endforeach; ?>
</div>

<div id="discipulosHintSeleccion" class="card" style="margin-bottom: 14px; <?= $perfilActivoVisual !== '' ? 'display:none;' : '' ?>">
    <div class="card-body" style="padding: 12px 16px; color:#5b6d88;">
        Selecciona una tarjeta para ver los discipulos de esa clasificacion.
    </div>
</div>

<div id="discipulosTableWrap" class="table-container" <?= $perfilActivoVisual !== '' ? '' : 'hidden' ?>>
    <table class="data-table ganar-table mobile-persona-accordion">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Ministerio</th>
                <th>Líder</th>
                <?php if ($mostrarEscaleraRapida): ?>
                <th class="escalera-inline-col">Escalera rápida</th>
                <?php endif; ?>
                <?php if ($mostrarAcciones): ?><th class="action-col">Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($personas)): ?>
                <?php foreach ($personas as $persona): ?>
                    <?php
                    $checklistEscalera = [];
                    $checklistRawPersona = (string)($persona['Escalera_Checklist'] ?? '');
                    if ($checklistRawPersona !== '') {
                        $tmpChecklist = json_decode($checklistRawPersona, true);
                        if (is_array($tmpChecklist)) {
                            $checklistEscalera = $tmpChecklist;
                        }
                    }
                    if (!isset($checklistEscalera['Ganar']) || !is_array($checklistEscalera['Ganar'])) {
                        $checklistEscalera['Ganar'] = [false, false, false, false, false, false];
                    }
                    if (!isset($checklistEscalera['Consolidar']) || !is_array($checklistEscalera['Consolidar'])) {
                        $checklistEscalera['Consolidar'] = [false, false, false];
                    }
                    if (!isset($checklistEscalera['Discipular']) || !is_array($checklistEscalera['Discipular'])) {
                        $checklistEscalera['Discipular'] = [false, false, false];
                    }
                    if (!isset($checklistEscalera['Enviar']) || !is_array($checklistEscalera['Enviar'])) {
                        $checklistEscalera['Enviar'] = [false, false, false];
                    }
                    for ($i = 0; $i <= 5; $i++) {
                        $checklistEscalera['Ganar'][$i] = !empty($checklistEscalera['Ganar'][$i]);
                    }
                    for ($i = 0; $i <= 2; $i++) {
                        $checklistEscalera['Consolidar'][$i] = !empty($checklistEscalera['Consolidar'][$i]);
                        $checklistEscalera['Discipular'][$i] = !empty($checklistEscalera['Discipular'][$i]);
                        $checklistEscalera['Enviar'][$i] = !empty($checklistEscalera['Enviar'][$i]);
                    }
                    $checklistEscaleraJson = htmlspecialchars((string)json_encode($checklistEscalera, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                    $rowPerfilId = $resolverCategoriaPerfil((array)$persona);
                    $esFilaDiscipulo = ($rowPerfilId === 'discipulos');
                    $filaPendienteUbicacion = $esPendienteUbicacionRed((array)$persona);
                    $mostrarEscaleraFila = ($esFilaDiscipulo || $filaPendienteUbicacion);
                    $puedeEditarEscaleraInline = !empty($puedeEditarPersona) && $mostrarEscaleraFila;
                    ?>
                    <tr class="js-discipulo-row" data-perfil-id="<?= htmlspecialchars((string)$rowPerfilId) ?>" data-pendiente-ubicacion="<?= $filaPendienteUbicacion ? '1' : '0' ?>">
                        <td>
                            <span class="ganar-cell-truncate persona-nombre-cell" title="<?= htmlspecialchars($persona['Nombre'] . ' ' . $persona['Apellido']) ?>">
                                <?= htmlspecialchars($persona['Nombre'] . ' ' . $persona['Apellido']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($persona['Nombre_Ministerio'] ?? 'Sin ministerio') ?></td>
                        <td><?= htmlspecialchars(trim((string)($persona['Nombre_Lider'] ?? '')) ?: 'Sin líder') ?></td>
                        <?php if ($mostrarEscaleraRapida): ?>
                        <td class="escalera-inline-col">
                            <?php if ($mostrarEscaleraFila): ?>
                            <div
                                class="escalera-inline-card js-inline-escalera"
                                data-persona-id="<?= (int)($persona['Id_Persona'] ?? 0) ?>"
                                data-checklist="<?= $checklistEscaleraJson ?>"
                                data-proceso="<?= htmlspecialchars((string)($persona['Proceso'] ?? 'Ganar'), ENT_QUOTES, 'UTF-8') ?>"
                                data-puede-editar="<?= $puedeEditarEscaleraInline ? '1' : '0' ?>"
                            >
                                <div class="escalera-inline-steps">
                                    <button type="button" class="escalera-step-btn etapa-ganar js-inline-step-btn" data-etapa="Ganar" title="Ganar" aria-label="Ganar">
                                        <span class="escalera-step-icon" aria-hidden="true"><span class="step-initial">G</span></span>
                                    </button>
                                    <button type="button" class="escalera-step-btn etapa-consolidar js-inline-step-btn" data-etapa="Consolidar" title="Consolidar" aria-label="Consolidar">
                                        <span class="escalera-step-icon" aria-hidden="true"><span class="step-initial">C</span></span>
                                    </button>
                                    <button type="button" class="escalera-step-btn etapa-discipular js-inline-step-btn" data-etapa="Discipular" title="Discipular" aria-label="Discipular">
                                        <span class="escalera-step-icon" aria-hidden="true"><span class="step-initial">D</span></span>
                                    </button>
                                    <button type="button" class="escalera-step-btn etapa-enviar js-inline-step-btn" data-etapa="Enviar" title="Enviar" aria-label="Enviar">
                                        <span class="escalera-step-icon" aria-hidden="true"><span class="step-initial">E</span></span>
                                    </button>
                                </div>

                                <div class="escalera-inline-panel js-inline-stage-panel" data-etapa="Ganar" hidden>
                                    <label class="escalera-inline-item"><input type="checkbox" class="js-inline-escalera-check" data-etapa="Ganar" data-indice="0" <?= !empty($checklistEscalera['Ganar'][0]) ? 'checked' : '' ?> <?= $puedeEditarEscaleraInline ? '' : 'disabled' ?>><span>Primer contacto</span></label>
                                    <label class="escalera-inline-item"><input type="checkbox" class="js-inline-escalera-check" data-etapa="Ganar" data-indice="2" <?= !empty($checklistEscalera['Ganar'][2]) ? 'checked' : '' ?> <?= $puedeEditarEscaleraInline ? '' : 'disabled' ?>><span>Fonovisita</span></label>
                                    <label class="escalera-inline-item"><input type="checkbox" class="js-inline-escalera-check" data-etapa="Ganar" data-indice="3" <?= !empty($checklistEscalera['Ganar'][3]) ? 'checked' : '' ?> <?= $puedeEditarEscaleraInline ? '' : 'disabled' ?>><span>Visita</span></label>
                                    <label class="escalera-inline-item"><input type="checkbox" class="js-inline-escalera-check" data-etapa="Ganar" data-indice="5" <?= !empty($checklistEscalera['Ganar'][5]) ? 'checked' : '' ?> <?= $puedeEditarEscaleraInline ? '' : 'disabled' ?>><span>No se dispone</span></label>
                                </div>

                                <div class="escalera-inline-panel js-inline-stage-panel" data-etapa="Consolidar" hidden>
                                    <label class="escalera-inline-item"><input type="checkbox" class="js-inline-escalera-check" data-etapa="Consolidar" data-indice="0" <?= !empty($checklistEscalera['Consolidar'][0]) ? 'checked' : '' ?> <?= $puedeEditarEscaleraInline ? '' : 'disabled' ?>><span>Universidad de la vida</span></label>
                                    <label class="escalera-inline-item"><input type="checkbox" class="js-inline-escalera-check" data-etapa="Consolidar" data-indice="1" <?= !empty($checklistEscalera['Consolidar'][1]) ? 'checked' : '' ?> <?= $puedeEditarEscaleraInline ? '' : 'disabled' ?>><span>Encuentro</span></label>
                                    <label class="escalera-inline-item"><input type="checkbox" class="js-inline-escalera-check" data-etapa="Consolidar" data-indice="2" <?= !empty($checklistEscalera['Consolidar'][2]) ? 'checked' : '' ?> <?= $puedeEditarEscaleraInline ? '' : 'disabled' ?>><span>Bautismo</span></label>
                                </div>

                                <div class="escalera-inline-panel js-inline-stage-panel" data-etapa="Discipular" hidden>
                                    <label class="escalera-inline-item"><input type="checkbox" class="js-inline-escalera-check" data-etapa="Discipular" data-indice="0" <?= !empty($checklistEscalera['Discipular'][0]) ? 'checked' : '' ?> <?= $puedeEditarEscaleraInline ? '' : 'disabled' ?>><span>Destino nivel 1</span></label>
                                    <label class="escalera-inline-item"><input type="checkbox" class="js-inline-escalera-check" data-etapa="Discipular" data-indice="1" <?= !empty($checklistEscalera['Discipular'][1]) ? 'checked' : '' ?> <?= $puedeEditarEscaleraInline ? '' : 'disabled' ?>><span>Destino nivel 2</span></label>
                                    <label class="escalera-inline-item"><input type="checkbox" class="js-inline-escalera-check" data-etapa="Discipular" data-indice="2" <?= !empty($checklistEscalera['Discipular'][2]) ? 'checked' : '' ?> <?= $puedeEditarEscaleraInline ? '' : 'disabled' ?>><span>Destino nivel 3</span></label>
                                </div>

                                <div class="escalera-inline-panel js-inline-stage-panel" data-etapa="Enviar" hidden>
                                    <label class="escalera-inline-item"><input type="checkbox" class="js-inline-escalera-check" data-etapa="Enviar" data-indice="2" <?= !empty($checklistEscalera['Enviar'][2]) ? 'checked' : '' ?> <?= $puedeEditarEscaleraInline ? '' : 'disabled' ?>><span>Célula</span></label>
                                </div>
                            </div>
                            <?php else: ?>
                            <span style="display:inline-block;padding:4px 8px;border-radius:999px;background:#f2f4f7;color:#5f6b7a;font-size:12px;font-weight:600;">
                                Automática por rol
                            </span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <?php if ($mostrarAcciones): ?>
                        <td class="action-col">
                            <div class="action-buttons action-buttons-compact">
                                <?php if ($puedeVerPersona): ?>
                                <a href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= $persona['Id_Persona'] ?>&return_url=<?= urlencode($returnUrlPersonas) ?>" class="action-icon-btn action-icon-info" title="Ver" aria-label="Ver">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button
                                    type="button"
                                    class="action-icon-btn action-icon-huella js-trazabilidad-btn"
                                    title="Ver huella de creación"
                                    aria-label="Ver huella de creación"
                                    data-persona-nombre="<?= htmlspecialchars(trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                    data-persona-fecha="<?= htmlspecialchars((string)($persona['Fecha_Registro'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-persona-creador="<?= htmlspecialchars(trim((string)($persona['Nombre_Creador'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                    data-persona-usuario-creador="<?= htmlspecialchars(trim((string)($persona['Usuario_Creador'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                    data-persona-creador-id="<?= (int)($persona['Creado_Por'] ?? 0) ?>"
                                    data-persona-canal="<?= htmlspecialchars((string)($persona['Canal_Creacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                >
                                    <i class="bi bi-fingerprint"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($puedeEditarPersona): ?>
                                <a href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= $persona['Id_Persona'] ?>&return_url=<?= urlencode($returnUrlPersonas) ?>" class="action-icon-btn action-icon-warning" title="Editar" aria-label="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($puedeEliminarPersona): ?>
                                <a href="<?= PUBLIC_URL ?>?url=personas/eliminar&id=<?= $persona['Id_Persona'] ?>&return_url=<?= urlencode($returnUrlPersonas) ?>" class="action-icon-btn action-icon-danger" title="Eliminar" aria-label="Eliminar" onclick="return confirm('¿Eliminar esta persona?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <?php $columnasBase = 3 + ($mostrarEscaleraRapida ? 1 : 0) + ($mostrarAcciones ? 1 : 0); ?>
                    <td colspan="<?= (string)$columnasBase ?>" class="text-center">No hay discipulos registrados</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.persona-nombre-cell {
    font-weight: 700;
}

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

.personas-perfil-card {
    appearance: none;
    border-top: 0;
    border-right: 0;
    border-bottom: 0;
    border-left-width: 3px;
    border-left-style: solid;
    transition: transform 0.16s ease, box-shadow 0.16s ease;
}

.personas-perfil-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(15, 35, 61, 0.08);
}

.personas-perfil-card.is-active {
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12), 0 10px 22px rgba(15, 35, 61, 0.08);
}

.action-buttons-compact {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-wrap: nowrap;
}

.ganar-table th.action-col,
.ganar-table td.action-col {
    white-space: nowrap;
    min-width: 120px;
    vertical-align: top;
}

.ganar-table th.escalera-inline-col,
.ganar-table td.escalera-inline-col {
    min-width: 150px;
    vertical-align: top;
    text-align: center;
}

.ganar-table .action-buttons.action-buttons-compact {
    flex-wrap: nowrap !important;
    justify-content: center;
}

.ganar-table .action-buttons.action-buttons-compact .action-icon-btn {
    flex: 0 0 auto;
}

.action-icon-btn {
    width: 27px;
    height: 27px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    border: 1px solid transparent;
    font-size: 13px;
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

.action-icon-danger {
    background: #ffe8ea;
    color: #c82333;
    border-color: #ffc1c7;
}

.action-icon-huella {
    background: #f3f4f6;
    color: #1f2937;
    border-color: #d1d5db;
    cursor: pointer;
}

.action-icon-huella i {
    font-size: 15px;
}

.escalera-inline-card {
    width: 100%;
    border: 0;
    border-radius: 0;
    background: transparent;
    padding: 0;
    box-sizing: border-box;
}

.escalera-inline-steps {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    width: auto;
}

.escalera-step-btn {
    border: 0;
    border-radius: 999px;
    background: transparent;
    color: #2d4c79;
    width: 24px;
    height: 24px;
    min-height: 24px;
    padding: 0;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.12s ease;
}

.escalera-step-btn.active {
    background: rgba(15, 23, 42, 0.06);
    box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.08);
}

.escalera-step-icon {
    width: 18px;
    height: 18px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    flex: 0 0 auto;
    border: 2px solid transparent;
    background: #fff;
}

.step-initial {
    font-size: 11px;
    font-weight: 900;
    line-height: 1;
    letter-spacing: 0.2px;
}

.escalera-step-name {
    display: none;
}

.escalera-step-btn.etapa-ganar .escalera-step-icon {
    border-color: #f2c300;
    color: #d3a700;
}

.escalera-step-btn.etapa-consolidar .escalera-step-icon {
    border-color: #36c24f;
    color: #22a83e;
}

.escalera-step-btn.etapa-discipular .escalera-step-icon {
    border-color: #2b8eea;
    color: #1f77ca;
}

.escalera-step-btn.etapa-enviar .escalera-step-icon {
    border-color: #d90d46;
    color: #bf0a3d;
}

.escalera-step-btn.active .escalera-step-icon {
    background: #f9fbff;
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.18);
}

.escalera-inline-panel {
    margin-top: 8px;
    border: 1px solid #dbe3f4;
    border-radius: 8px;
    background: #f8fbff;
    padding: 8px;
    display: grid;
    gap: 6px;
}

.escalera-inline-panel[hidden] {
    display: none !important;
}

.escalera-inline-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: #2f4a74;
}

.escalera-inline-item input {
    width: 14px;
    height: 14px;
}

.escalera-inline-card.is-saving {
    opacity: 0.7;
    pointer-events: none;
}

.escalera-inline-card.is-bloqueado .escalera-inline-item {
    opacity: 0.7;
}

.trazabilidad-grid {
    display: grid;
    gap: 12px;
}

.trazabilidad-item {
    border: 1px solid #dbe3f4;
    border-radius: 10px;
    background: #f8fbff;
    padding: 12px;
}

.trazabilidad-label {
    display: block;
    margin-bottom: 4px;
    font-size: 12px;
    font-weight: 700;
    color: #5b6b84;
}

.escalera-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 14px;
}

.escalera-modal-backdrop.show {
    display: flex;
}

.escalera-modal {
    width: min(1120px, 96vw);
    max-height: 90vh;
    background: #fff;
    border-radius: 12px;
    border: 1px solid #dbe3f4;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.22);
    overflow: hidden;
}

.escalera-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 12px 14px;
    background: #f4f8ff;
    border-bottom: 1px solid #dbe3f4;
}

.escalera-modal-title {
    margin: 0;
    font-size: 18px;
    color: #1f365f;
}

.escalera-modal-close {
    border: 0;
    background: transparent;
    font-size: 20px;
    line-height: 1;
    color: #6b7a90;
    cursor: pointer;
}

.escalera-modal-body {
    padding: 16px;
    overflow: auto;
}

.escalera-level-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #eef4ff;
    color: #244a84;
    border: 1px solid #cfe0ff;
    border-radius: 999px;
    padding: 6px 10px;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 10px;
}

.escalera-modal-matrix-wrap {
    border: 1px solid #dbe3f4;
    border-radius: 10px;
    overflow: auto;
}

.escalera-modal-matrix {
    width: 100%;
    min-width: 940px;
    border-collapse: collapse;
    table-layout: fixed;
}

.escalera-modal-matrix th,
.escalera-modal-matrix td {
    border: 1px solid #e4ebf7;
    padding: 10px 8px;
    vertical-align: middle;
}

.escalera-modal-matrix th {
    text-align: center;
    font-size: 18px;
    font-weight: 800;
}

.escalera-modal-stage-ganar {
    background: #fff7dd;
    color: #8a6500;
}

.escalera-modal-stage-consolidar {
    background: #eaf9ee;
    color: #176636;
}

.escalera-modal-stage-discipular {
    background: #ecf5ff;
    color: #1e65b6;
}

.escalera-modal-stage-enviar {
    background: #ffeaf2;
    color: #a30f43;
}

.escalera-modal-sub-label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #4b5f7f;
    margin-bottom: 4px;
}

.escalera-check-label {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    color: #425573;
    justify-content: center;
}

.escalera-check-label input {
    width: 14px;
    height: 14px;
}

.escalera-check-item.done .escalera-check-label {
    color: #1e7f39;
    font-weight: 700;
}

.escalera-check-label.disabled {
    opacity: 0.65;
}

.escalera-check-icon {
    font-size: 13px;
    color: #1e7f39;
    margin-right: 2px;
}

.escalera-empty-cell {
    background: #f8fafc;
}

.no-disponible-panel {
    margin-top: 12px;
    border: 1px solid #f7d4a0;
    background: #fff8ed;
    border-radius: 10px;
    padding: 10px;
}

.no-disponible-panel label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    color: #9a6700;
    margin-bottom: 6px;
}

.no-disponible-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 6px;
}

.no-disponible-header label {
    margin-bottom: 0;
}

.no-disponible-panel textarea {
    width: 100%;
    min-height: 90px;
    resize: vertical;
    border: 1px solid #dfc7a0;
    border-radius: 8px;
    padding: 8px;
    font-size: 13px;
}

.no-disponible-actions {
    margin-top: 8px;
    display: flex;
    justify-content: flex-end;
}

.escalera-modal-footer-actions {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #e4ebf7;
    display: flex;
    justify-content: flex-end;
    position: sticky;
    bottom: 0;
    background: #fff;
}

.no-disponible-save-btn {
    border: 1px solid #c38a1d;
    background: #d89a22;
    color: #fff;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
}

.no-disponible-save-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

.escalera-status-msg {
    margin-top: 10px;
    font-size: 12px;
    color: #5b6b84;
    min-height: 18px;
}

.escalera-status-msg.error {
    color: #b42318;
}

.escalera-status-msg.success {
    color: #1e7f39;
}

@media (max-width: 800px) {
    .personas-header-actions {
        width: 100%;
        justify-content: flex-start;
    }

    .personas-action-group {
        max-width: 100%;
        overflow-x: auto;
    }
}
</style>

<div class="escalera-modal-backdrop" id="escaleraModalBackdrop" aria-hidden="true">
    <div class="escalera-modal" role="dialog" aria-modal="true" aria-labelledby="escaleraModalTitle">
        <div class="escalera-modal-header">
            <h3 class="escalera-modal-title" id="escaleraModalTitle">Escalera del Éxito</h3>
            <button type="button" class="escalera-modal-close" id="escaleraModalClose" aria-label="Cerrar">&times;</button>
        </div>
        <div class="escalera-modal-body" id="escaleraModalBody"></div>
    </div>
</div>

<div class="escalera-modal-backdrop" id="trazabilidadModalBackdrop" aria-hidden="true">
    <div class="escalera-modal" role="dialog" aria-modal="true" aria-labelledby="trazabilidadModalTitle" style="width:min(520px, 96vw);">
        <div class="escalera-modal-header">
            <h3 class="escalera-modal-title" id="trazabilidadModalTitle">Huella de registro</h3>
            <button type="button" class="escalera-modal-close" id="trazabilidadModalClose" aria-label="Cerrar">&times;</button>
        </div>
        <div class="escalera-modal-body" id="trazabilidadModalBody"></div>
    </div>
</div>

<script>
const btnCopiarUrlRegistroPersonas = document.getElementById('btnCopiarUrlRegistroPersonas');

if (btnCopiarUrlRegistroPersonas) {
    const baseRutaRegistroPersonas = String(btnCopiarUrlRegistroPersonas.getAttribute('data-url') || '').trim();
    const urlRegistroPersonas = new URL(baseRutaRegistroPersonas, window.location.origin).toString();
    const textoOriginalBtnRegistro = btnCopiarUrlRegistroPersonas.innerHTML;

    const mostrarFeedbackBtnRegistro = function(texto) {
        btnCopiarUrlRegistroPersonas.innerHTML = texto;
        setTimeout(function() {
            btnCopiarUrlRegistroPersonas.innerHTML = textoOriginalBtnRegistro;
        }, 1800);
    };

    btnCopiarUrlRegistroPersonas.addEventListener('click', async function() {
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(urlRegistroPersonas);
            } else {
                const inputTemporal = document.createElement('input');
                inputTemporal.value = urlRegistroPersonas;
                document.body.appendChild(inputTemporal);
                inputTemporal.select();
                document.execCommand('copy');
                document.body.removeChild(inputTemporal);
            }

            mostrarFeedbackBtnRegistro('<i class="bi bi-check2"></i> URL copiada');
        } catch (e) {
            mostrarFeedbackBtnRegistro('<i class="bi bi-x-circle"></i> No se pudo copiar');
        }
    });
}

const filtroMinisterio = document.getElementById('filtro_ministerio');
const filtroNombre = document.getElementById('filtro_nombre');
const filtroPerfilForm = document.getElementById('filtro_perfil_form');

if (filtroMinisterio && filtroPerfilForm) {
    filtroMinisterio.addEventListener('change', function() {
        if (typeof filtroPerfilForm.requestSubmit === 'function') {
            filtroPerfilForm.requestSubmit();
            return;
        }

        filtroPerfilForm.submit();
    });
}

if (filtroNombre && filtroPerfilForm) {
    filtroNombre.addEventListener('change', function() {
        if (typeof filtroPerfilForm.requestSubmit === 'function') {
            filtroPerfilForm.requestSubmit();
            return;
        }

        filtroPerfilForm.submit();
    });
}

(function() {
    const etapas = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
    const subprocesos = {
        Ganar: ['Primer contacto', 'Asignacion a lideres y ministerio', 'Fonovisita', 'Visita', 'Asignacion a una celula', 'No se dispone'],
        Consolidar: ['Universidad de la vida', 'Encuentro', 'Bautismo'],
        Discipular: ['Capacitacion destino nivel 1 (modulos 1 y 2)', 'Capacitacion destino nivel 2 (modulos 3 y 4)', 'Capacitacion destino nivel 3 (modulos 5 y 6)'],
        Enviar: ['Celula']
    };
    const indicesChecklist = {
        Ganar: [0, 1, 2, 3, 4, 5],
        Consolidar: [0, 1, 2],
        Discipular: [0, 1, 2],
        Enviar: [2]
    };

    const backdrop = document.getElementById('escaleraModalBackdrop');
    const closeBtn = document.getElementById('escaleraModalClose');
    const body = document.getElementById('escaleraModalBody');
    const title = document.getElementById('escaleraModalTitle');
    const endpoint = '<?= PUBLIC_URL ?>?url=personas/actualizarChecklistEscalera';

    const modalState = {
        personaId: 0,
        personaNombre: '',
        proceso: 'Ganar',
        checklist: null,
        meta: {
            no_disponible_observacion: ''
        },
        asignadoALider: false,
        botonOrigen: null,
        guardando: false
    };

    function construirChecklistDesdeProceso(proceso) {
        const resultado = {};
        const indiceActual = etapas.indexOf(proceso);
        etapas.forEach((etapa, idx) => {
            const totalSubprocesos = (subprocesos[etapa] || []).length;
            resultado[etapa] = Array(totalSubprocesos).fill(false);
            if (idx < indiceActual) {
                const limiteCompletado = Math.min(3, totalSubprocesos);
                for (let i = 0; i < limiteCompletado; i++) {
                    resultado[etapa][i] = true;
                }
            }
            if (idx === indiceActual) {
                resultado[etapa][0] = true;
            }
        });
        if (indiceActual < 0) {
            resultado.Ganar = [false, false, false, false, false, false];
        }
        return resultado;
    }

    function combinarChecklist(base, persistido) {
        const combinado = JSON.parse(JSON.stringify(base));
        if (!persistido || typeof persistido !== 'object') {
            return combinado;
        }

        etapas.forEach(etapa => {
            if (!Array.isArray(persistido[etapa])) {
                return;
            }
            for (let i = 0; i < (subprocesos[etapa] || []).length; i++) {
                const indiceReal = (indicesChecklist[etapa] && typeof indicesChecklist[etapa][i] !== 'undefined') ? indicesChecklist[etapa][i] : i;
                if (typeof persistido[etapa][indiceReal] !== 'undefined') {
                    combinado[etapa][indiceReal] = !!persistido[etapa][indiceReal];
                }
            }
        });

        return combinado;
    }

    function extraerMetaChecklist(persistido) {
        const metaBase = {
            no_disponible_observacion: ''
        };

        if (!persistido || typeof persistido !== 'object' || !persistido._meta || typeof persistido._meta !== 'object') {
            return metaBase;
        }

        metaBase.no_disponible_observacion = (persistido._meta.no_disponible_observacion || '').toString().trim();
        return metaBase;
    }

    function abrirModal(personaId, nombre, proceso, checklistRaw, botonOrigen, asignadoALider) {
        let checklistPersistido = null;
        if (checklistRaw) {
            try {
                checklistPersistido = JSON.parse(checklistRaw);
            } catch (e) {
                checklistPersistido = null;
            }
        }

        modalState.personaId = Number(personaId || 0);
        modalState.personaNombre = nombre || 'Persona';
        modalState.proceso = etapas.includes(proceso) ? proceso : 'Ganar';
        modalState.checklist = combinarChecklist(construirChecklistDesdeProceso(modalState.proceso), checklistPersistido);
        modalState.meta = extraerMetaChecklist(checklistPersistido);
        modalState.asignadoALider = !!asignadoALider;
        if (modalState.checklist.Ganar && modalState.checklist.Ganar.length > 0) {
            modalState.checklist.Ganar[1] = modalState.asignadoALider;
        }
        modalState.botonOrigen = botonOrigen || null;
        modalState.guardando = false;

        renderModal();
        backdrop.classList.add('show');
        backdrop.setAttribute('aria-hidden', 'false');
    }

    function renderModal(mensaje, esError) {
        const procesoActual = etapas.includes(modalState.proceso) ? modalState.proceso : 'Ganar';
        const indiceProceso = etapas.indexOf(procesoActual);
        const noDisponibleMarcado = !!(modalState.checklist.Ganar && modalState.checklist.Ganar[5]);

        title.textContent = 'Escalera del Exito - ' + modalState.personaNombre;
        let html = '<div class="escalera-level-pill">Nivel actual: <strong>' + escapeHtml(procesoActual) + '</strong></div>';
        html += '<div class="escalera-modal-matrix-wrap">';
        html += '<table class="escalera-modal-matrix"><thead><tr>';
        html += '<th class="escalera-modal-stage-ganar">Ganar</th>';
        html += '<th class="escalera-modal-stage-consolidar">Consolidar</th>';
        html += '<th class="escalera-modal-stage-discipular">Discipular</th>';
        html += '<th class="escalera-modal-stage-enviar">Enviar</th>';
        html += '</tr></thead><tbody>';

        const totalFilas = Math.max(...etapas.map(etapa => (subprocesos[etapa] || []).length));
        for (let i = 0; i < totalFilas; i++) {
            html += '<tr>';

            etapas.forEach((etapa, etapaIndex) => {
                const nombreSub = (subprocesos[etapa] && subprocesos[etapa][i]) ? subprocesos[etapa][i] : '';
                if (!nombreSub) {
                    html += '<td class="escalera-empty-cell"></td>';
                    return;
                }

                const indiceReal = (indicesChecklist[etapa] && typeof indicesChecklist[etapa][i] !== 'undefined') ? indicesChecklist[etapa][i] : i;
                const done = !!(modalState.checklist[etapa] && modalState.checklist[etapa][indiceReal]);
                let editable = etapaIndex === indiceProceso;
                if (etapa === 'Ganar' && indiceReal === 1) {
                    editable = false;
                }
                if (etapa === 'Ganar' && indiceReal === 4) {
                    editable = false;
                }
                if (noDisponibleMarcado && !(etapa === 'Ganar' && indiceReal === 5)) {
                    editable = false;
                }
                html += '<td class="escalera-check-item ' + (done ? 'done' : '') + '">';
                html += '<span class="escalera-modal-sub-label">' + escapeHtml(nombreSub) + '</span>';
                html += '<label class="escalera-check-label ' + (editable ? '' : 'disabled') + '">';
                html += '<input type="checkbox" class="js-escalera-check" data-etapa="' + escapeHtml(etapa) + '" data-indice="' + indiceReal + '" ' + (done ? 'checked' : '') + ' ' + (editable && !modalState.guardando ? '' : 'disabled') + '>';
                html += '<span>' + (done ? '<span class="escalera-check-icon">&#10003;</span>Completado' : 'Pendiente') + '</span>';
                html += '</label>';
                html += '</td>';
            });

            html += '</tr>';
        }

        html += '</tbody></table></div>';

        if (noDisponibleMarcado) {
            html += '<div class="no-disponible-panel">';
            html += '<div class="no-disponible-header">';
            html += '<label for="js-no-disponible-observacion">Observacion de No se dispone</label>';
            html += '<button type="button" class="no-disponible-save-btn js-guardar-no-disponible" ' + (modalState.guardando ? 'disabled' : '') + '>Guardar observacion</button>';
            html += '</div>';
            html += '<textarea id="js-no-disponible-observacion" ' + (modalState.guardando ? 'disabled' : '') + ' placeholder="Describe por que no se logro concretar esta persona...">' + escapeHtml(modalState.meta.no_disponible_observacion || '') + '</textarea>';
            html += '<div class="no-disponible-actions">';
            html += '<button type="button" class="no-disponible-save-btn js-guardar-no-disponible" ' + (modalState.guardando ? 'disabled' : '') + '>Guardar observacion</button>';
            html += '</div></div>';
            html += '<div class="escalera-modal-footer-actions">';
            html += '<button type="button" class="no-disponible-save-btn js-guardar-no-disponible" ' + (modalState.guardando ? 'disabled' : '') + '>Guardar observacion</button>';
            html += '</div>';
        }

        html += '<div class="escalera-status-msg ' + (mensaje ? (esError ? 'error' : 'success') : '') + '">' + (mensaje ? escapeHtml(mensaje) : '') + '</div>';
        body.innerHTML = html;
        enlazarBotonesGuardarNoDisponible();
    }

    function guardarNoDisponibleDesdeUI() {
        if (modalState.guardando) {
            alert('Ya se esta guardando la informacion.');
            return;
        }

        const textarea = document.getElementById('js-no-disponible-observacion');
        const observacion = textarea ? textarea.value.trim() : '';
        if (observacion === '') {
            alert('Debes escribir una observacion para guardar.');
            renderModal('La observacion es obligatoria para No se dispone', true);
            return;
        }

        modalState.meta.no_disponible_observacion = observacion;
        guardarChecklist('Ganar', 5, true, observacion, {
            cerrarModalExito: true,
            recargarDespues: true,
            mensajeExito: 'Se guardo la informacion con exito'
        });
    }

    function enlazarBotonesGuardarNoDisponible() {
        body.querySelectorAll('.js-guardar-no-disponible').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                guardarNoDisponibleDesdeUI();
            });
        });
    }

    async function guardarChecklist(etapa, indice, marcado, observacionNoDisponible, opciones) {
        if (!modalState.personaId || modalState.guardando) {
            if (modalState.guardando) {
                renderModal('Guardando informacion, espera un momento...');
            }
            return;
        }

        const opts = Object.assign({
            cerrarModalExito: false,
            recargarDespues: false,
            mensajeExito: 'Checklist actualizado'
        }, opciones || {});

        modalState.guardando = true;
        renderModal('Guardando cambios...');

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    id_persona: modalState.personaId,
                    etapa: etapa,
                    indice: indice,
                    marcado: marcado ? 1 : 0,
                    observacion_no_disponible: (etapa === 'Ganar' && indice === 5) ? (observacionNoDisponible || '') : ''
                })
            });

            const contentType = (response.headers.get('content-type') || '').toLowerCase();
            if (contentType.indexOf('application/json') === -1) {
                const raw = await response.text();
                throw new Error('Respuesta invalida del servidor: ' + raw.substring(0, 120));
            }

            const data = await response.json();
            if (!response.ok || !data || !data.success) {
                throw new Error((data && data.message) ? data.message : 'No se pudo guardar el checklist');
            }

            if (data.checklist && typeof data.checklist === 'object') {
                modalState.checklist = combinarChecklist(modalState.checklist, data.checklist);
                modalState.meta = extraerMetaChecklist(data.checklist);
            }
            if (data.proceso && etapas.includes(data.proceso)) {
                modalState.proceso = data.proceso;
            }

            if (modalState.checklist.Ganar && modalState.checklist.Ganar.length > 0) {
                modalState.checklist.Ganar[1] = modalState.asignadoALider;
            }

            if (modalState.botonOrigen) {
                modalState.botonOrigen.setAttribute('data-persona-proceso', modalState.proceso);
                modalState.botonOrigen.setAttribute('data-persona-checklist', JSON.stringify(Object.assign({}, modalState.checklist, { _meta: modalState.meta })));
            }

            modalState.guardando = false;

            if (opts.cerrarModalExito) {
                alert(opts.mensajeExito);
                cerrarModal();
                if (opts.recargarDespues) {
                    window.location.reload();
                }
                return;
            }

            renderModal(opts.mensajeExito);
        } catch (error) {
            modalState.guardando = false;
            if (opts.cerrarModalExito) {
                alert('No se pudo guardar: ' + (error.message || 'Error inesperado'));
            }
            renderModal(error.message || 'Error al guardar', true);
        }
    }

    function cerrarModal() {
        backdrop.classList.remove('show');
        backdrop.setAttribute('aria-hidden', 'true');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    document.querySelectorAll('.js-escalera-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            abrirModal(
                this.getAttribute('data-persona-id') || '0',
                this.getAttribute('data-persona-nombre') || '',
                this.getAttribute('data-persona-proceso') || '',
                this.getAttribute('data-persona-checklist') || '',
                this,
                this.getAttribute('data-persona-asignado') === '1'
            );
        });
    });

    if (body) {
        body.addEventListener('change', function(e) {
            const target = e.target;
            if (!target || !target.classList.contains('js-escalera-check')) {
                return;
            }

            const etapa = target.getAttribute('data-etapa') || '';
            const indice = parseInt(target.getAttribute('data-indice') || '-1', 10);
            if (!etapa || indice < 0) {
                target.checked = !target.checked;
                return;
            }

            if (etapa === 'Ganar' && indice === 5 && target.checked) {
                modalState.checklist.Ganar[5] = true;
                renderModal('Escribe la observacion y guardala para marcar No se dispone');
                return;
            }

            if (etapa === 'Ganar' && indice === 5 && !target.checked) {
                modalState.meta.no_disponible_observacion = '';
                guardarChecklist(etapa, indice, false, '');
                return;
            }

            guardarChecklist(etapa, indice, !!target.checked);
        });

        body.addEventListener('keydown', function(e) {
            const target = e.target;
            if (!target || target.id !== 'js-no-disponible-observacion') {
                return;
            }

            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                guardarNoDisponibleDesdeUI();
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', cerrarModal);
    }

    if (backdrop) {
        backdrop.addEventListener('click', function(e) {
            if (e.target === backdrop) {
                cerrarModal();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && backdrop && backdrop.classList.contains('show')) {
            cerrarModal();
        }
    });
})();
</script>

<script>
(function() {
    const cards = Array.from(document.querySelectorAll('.js-perfil-card'));
    const rows = Array.from(document.querySelectorAll('.js-discipulo-row'));
    const tableWrap = document.getElementById('discipulosTableWrap');
    const hint = document.getElementById('discipulosHintSeleccion');

    if (!cards.length || !rows.length) {
        return;
    }

    function aplicarFiltro(perfilId) {
        const objetivo = String(perfilId || '').trim();

        cards.forEach(function(card) {
            card.classList.toggle('is-active', String(card.dataset.perfilId || '') === objetivo);
        });

        if (objetivo === '') {
            rows.forEach(function(row) {
                row.style.display = 'none';
            });
            if (tableWrap) {
                tableWrap.setAttribute('hidden', 'hidden');
            }
            if (hint) {
                hint.style.display = '';
            }
            return;
        }

        if (tableWrap) {
            tableWrap.removeAttribute('hidden');
        }
        if (hint) {
            hint.style.display = 'none';
        }

        rows.forEach(function(row) {
            const rowPerfil = String(row.dataset.perfilId || 'discipulos');
            const esPendienteUbicacion = String(row.dataset.pendienteUbicacion || '0') === '1';
            const mostrar = objetivo === 'pendientes_ubicacion'
                ? esPendienteUbicacion
                : rowPerfil === objetivo;
            row.style.display = mostrar ? '' : 'none';
        });
    }

    cards.forEach(function(card) {
        card.addEventListener('click', function() {
            aplicarFiltro(String(card.dataset.perfilId || ''));
        });
    });

    const cardInicial = cards.find(function(card) {
        return card.classList.contains('is-active');
    });
    const params = new URLSearchParams(window.location.search || '');
    const panelSolicitado = String(params.get('panel') || '').trim();
    const cardSolicitada = cards.find(function(card) {
        return String(card.dataset.perfilId || '') === panelSolicitado;
    });
    const perfilInicial = cardSolicitada
        ? panelSolicitado
        : (cardInicial ? String(cardInicial.dataset.perfilId || '') : '');
    aplicarFiltro(perfilInicial);
})();
</script>

<script>
(function() {
    const backdrop = document.getElementById('trazabilidadModalBackdrop');
    const closeBtn = document.getElementById('trazabilidadModalClose');
    const body = document.getElementById('trazabilidadModalBody');
    const title = document.getElementById('trazabilidadModalTitle');

    if (!backdrop || !closeBtn || !body || !title) {
        return;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    function formatearFecha(fechaRaw) {
        const valor = String(fechaRaw || '').trim();
        if (!valor) {
            return 'No disponible';
        }

        const fecha = new Date(valor.replace(' ', 'T'));
        if (Number.isNaN(fecha.getTime())) {
            return valor;
        }

        return fecha.toLocaleString('es-CO', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function cerrarModal() {
        backdrop.classList.remove('show');
        backdrop.setAttribute('aria-hidden', 'true');
    }

    function abrirModal(btn) {
        const nombre = btn.getAttribute('data-persona-nombre') || 'Persona';
        const fecha = btn.getAttribute('data-persona-fecha') || '';
        const creador = String(btn.getAttribute('data-persona-creador') || '').trim();
        const usuarioCreador = String(btn.getAttribute('data-persona-usuario-creador') || '').trim();
        const creadorId = String(btn.getAttribute('data-persona-creador-id') || '').trim();
        const canal = String(btn.getAttribute('data-persona-canal') || '').trim();
        let creadorVisible = 'Registro anterior (sin trazabilidad)';
        if (usuarioCreador !== '' && creador !== '') {
            creadorVisible = usuarioCreador + ' · ' + creador;
        } else if (usuarioCreador !== '') {
            creadorVisible = usuarioCreador;
        } else if (creador !== '') {
            creadorVisible = creador;
        } else if (creadorId !== '' && creadorId !== '0') {
            creadorVisible = 'Usuario ID #' + creadorId;
        } else if (canal !== '') {
            creadorVisible = canal;
        }
        const canalVisible = canal !== '' ? canal : 'Registro anterior';

        title.textContent = 'Huella de registro - ' + nombre;
        body.innerHTML = ''
            + '<div class="trazabilidad-grid">'
            + '  <div class="trazabilidad-item"><span class="trazabilidad-label">Fecha de creación</span><strong>' + escapeHtml(formatearFecha(fecha)) + '</strong></div>'
            + '  <div class="trazabilidad-item"><span class="trazabilidad-label">Creada por</span><strong>' + escapeHtml(creadorVisible) + '</strong></div>'
            + '  <div class="trazabilidad-item"><span class="trazabilidad-label">Canal de registro</span><strong>' + escapeHtml(canalVisible) + '</strong></div>'
            + '</div>';

        backdrop.classList.add('show');
        backdrop.setAttribute('aria-hidden', 'false');
    }

    document.querySelectorAll('.js-trazabilidad-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            abrirModal(this);
        });
    });

    closeBtn.addEventListener('click', cerrarModal);
    backdrop.addEventListener('click', function(e) {
        if (e.target === backdrop) {
            cerrarModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && backdrop.classList.contains('show')) {
            cerrarModal();
        }
    });
})();
</script>

<script>
(function() {
    const ministerioSelect = document.getElementById('filtro_ministerio');
    const liderSelect = document.getElementById('filtro_lider');
    if (!ministerioSelect || !liderSelect) {
        return;
    }

    const lideresDisponibles = [
        <?php foreach (($lideres ?? []) as $index => $lider): ?>
        {
            id: '<?= htmlspecialchars((string)($lider['Id_Persona'] ?? ''), ENT_QUOTES) ?>',
            nombre: '<?= htmlspecialchars(trim((string)($lider['Nombre'] ?? '') . ' ' . (string)($lider['Apellido'] ?? '')), ENT_QUOTES) ?>',
            ministerio: '<?= htmlspecialchars(trim((string)($lider['Id_Ministerio'] ?? '')), ENT_QUOTES) ?>'
        }<?= $index < count(($lideres ?? [])) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
    ];

    function refrescarFiltroLider() {
        const ministerioSeleccionado = String(ministerioSelect.value || '').trim();
        const liderSeleccionado = String(liderSelect.value || '');
        const lideresFiltrados = lideresDisponibles.filter(function(lider) {
            const ministerioLider = String(lider.ministerio || '').trim();
            if (ministerioSeleccionado === '') {
                return true;
            }
            if (ministerioSeleccionado === '0') {
                return ministerioLider === '' || ministerioLider === '0';
            }
            return ministerioLider === ministerioSeleccionado;
        });

        liderSelect.innerHTML = '';
        const optionTodos = document.createElement('option');
        optionTodos.value = '';
        optionTodos.textContent = 'Todos';
        liderSelect.appendChild(optionTodos);

        lideresFiltrados.forEach(function(lider) {
            const option = document.createElement('option');
            option.value = lider.id;
            option.textContent = lider.nombre;
            option.setAttribute('data-ministerio', lider.ministerio || '');
            liderSelect.appendChild(option);
        });

        const existeSeleccion = Array.from(liderSelect.options).some(function(option) {
            return option.value === liderSeleccionado;
        });
        liderSelect.value = existeSeleccion ? liderSeleccionado : '';
    }

    ministerioSelect.addEventListener('change', refrescarFiltroLider);
    refrescarFiltroLider();
})();
</script>

<script>
(function() {
    const endpointChecklist = '<?= PUBLIC_URL ?>?url=personas/actualizarChecklistEscalera';
    const etapas = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];

    function parseChecklist(raw) {
        if (!raw) {
            return {
                Ganar: [false, false, false, false, false, false],
                Consolidar: [false, false, false],
                Discipular: [false, false, false],
                Enviar: [false, false, false],
                _meta: {}
            };
        }

        try {
            const parsed = JSON.parse(raw);
            if (!parsed || typeof parsed !== 'object') {
                throw new Error('Checklist inválido');
            }

            if (!Array.isArray(parsed.Ganar)) parsed.Ganar = [false, false, false, false, false, false];
            if (!Array.isArray(parsed.Consolidar)) parsed.Consolidar = [false, false, false];
            if (!Array.isArray(parsed.Discipular)) parsed.Discipular = [false, false, false];
            if (!Array.isArray(parsed.Enviar)) parsed.Enviar = [false, false, false];

            for (let i = 0; i <= 5; i++) parsed.Ganar[i] = !!parsed.Ganar[i];
            for (let i = 0; i <= 2; i++) {
                parsed.Consolidar[i] = !!parsed.Consolidar[i];
                parsed.Discipular[i] = !!parsed.Discipular[i];
                parsed.Enviar[i] = !!parsed.Enviar[i];
            }

            if (!parsed._meta || typeof parsed._meta !== 'object') {
                parsed._meta = {};
            }

            return parsed;
        } catch (e) {
            return {
                Ganar: [false, false, false, false, false, false],
                Consolidar: [false, false, false],
                Discipular: [false, false, false],
                Enviar: [false, false, false],
                _meta: {}
            };
        }
    }

    function aplicarChecksEnVista(container, checklist) {
        container.querySelectorAll('.js-inline-escalera-check').forEach(function(check) {
            const etapa = check.getAttribute('data-etapa') || '';
            const indice = parseInt(check.getAttribute('data-indice') || '-1', 10);
            if (!etapa || indice < 0 || !Array.isArray(checklist[etapa])) {
                return;
            }
            check.checked = !!checklist[etapa][indice];
        });
    }

    function aplicarBloqueoNoDisponible(container, checklist, puedeEditar) {
        const noDisponible = !!(checklist.Ganar && checklist.Ganar[5]);
        container.querySelectorAll('.js-inline-escalera-check').forEach(function(check) {
            const etapa = check.getAttribute('data-etapa') || 'Ganar';
            const indice = parseInt(check.getAttribute('data-indice') || '-1', 10);
            let disabled = !puedeEditar;
            if (!disabled && noDisponible && !(etapa === 'Ganar' && indice === 5)) {
                disabled = true;
            }
            check.disabled = disabled;
        });
        container.classList.toggle('is-bloqueado', noDisponible);
    }

    async function guardarChecklistInline(container, etapa, indice, marcado) {
        const idPersona = parseInt(container.getAttribute('data-persona-id') || '0', 10);
        if (!idPersona) {
            return false;
        }

        const checklist = parseChecklist(container.getAttribute('data-checklist') || '');
        let observacionNoDisponible = '';

        if (etapa === 'Ganar' && indice === 5 && marcado) {
            const observacionActual = String((checklist._meta && checklist._meta.no_disponible_observacion) || '');
            const ingresada = window.prompt('Escribe una observación para marcar "No se dispone":', observacionActual);
            if (ingresada === null) {
                return false;
            }
            const limpia = String(ingresada || '').trim();
            if (limpia === '') {
                alert('La observación es obligatoria para "No se dispone".');
                return false;
            }
            observacionNoDisponible = limpia;
        }

        if (etapa === 'Ganar' && indice === 5 && !marcado && checklist._meta) {
            checklist._meta.no_disponible_observacion = '';
        }

        container.classList.add('is-saving');
        try {
            const response = await fetch(endpointChecklist, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    id_persona: idPersona,
                    etapa: etapa,
                    indice: indice,
                    marcado: marcado ? 1 : 0,
                    observacion_no_disponible: (etapa === 'Ganar' && indice === 5) ? observacionNoDisponible : ''
                })
            });

            const data = await response.json();
            if (!response.ok || !data || !data.success) {
                throw new Error((data && data.message) ? data.message : 'No se pudo guardar.');
            }

            const checklistServidor = parseChecklist(JSON.stringify(data.checklist || {}));
            container.setAttribute('data-checklist', JSON.stringify(checklistServidor));
            aplicarChecksEnVista(container, checklistServidor);
            return true;
        } catch (error) {
            alert(error.message || 'Error al guardar checklist.');
            return false;
        } finally {
            container.classList.remove('is-saving');
        }
    }

    document.querySelectorAll('.js-inline-escalera').forEach(function(container) {
        const checklist = parseChecklist(container.getAttribute('data-checklist') || '');
        const puedeEditar = container.getAttribute('data-puede-editar') === '1';
        container.setAttribute('data-checklist', JSON.stringify(checklist));

        aplicarChecksEnVista(container, checklist);
        aplicarBloqueoNoDisponible(container, checklist, puedeEditar);

        let etapaAbierta = null;
        function mostrarPanel(etapaObjetivo) {
            container.querySelectorAll('.js-inline-stage-panel').forEach(function(panel) {
                if (etapaObjetivo && panel.getAttribute('data-etapa') === etapaObjetivo) {
                    panel.removeAttribute('hidden');
                } else {
                    panel.setAttribute('hidden', 'hidden');
                }
            });

            container.querySelectorAll('.js-inline-step-btn').forEach(function(btn) {
                btn.classList.toggle('active', !!etapaObjetivo && btn.getAttribute('data-etapa') === etapaObjetivo);
            });

            etapaAbierta = etapaObjetivo || null;
        }

        mostrarPanel(null);

        container.querySelectorAll('.js-inline-step-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const etapa = btn.getAttribute('data-etapa') || 'Ganar';
                if (etapaAbierta === etapa) {
                    mostrarPanel(null);
                    return;
                }
                mostrarPanel(etapa);
            });
        });

        container.querySelectorAll('.js-inline-escalera-check').forEach(function(check) {
            check.addEventListener('change', async function() {
                const etapa = String(check.getAttribute('data-etapa') || 'Ganar');
                const indice = parseInt(check.getAttribute('data-indice') || '-1', 10);
                if (!etapas.includes(etapa) || indice < 0) {
                    return;
                }

                const nuevoValor = !!check.checked;
                const guardado = await guardarChecklistInline(container, etapa, indice, nuevoValor);
                if (!guardado) {
                    check.checked = !nuevoValor;
                    return;
                }

                const checklistActualizado = parseChecklist(container.getAttribute('data-checklist') || '');
                aplicarBloqueoNoDisponible(container, checklistActualizado, puedeEditar);
            });
        });
    });
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>

