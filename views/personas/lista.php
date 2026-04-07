<?php include VIEWS . '/layout/header.php'; ?>
<?php
$puedeVerPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'ver');
$puedeEditarPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'editar');
$puedeEliminarPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'eliminar');
$puedeCrearPersona = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'crear');
$puedeExportarPersonas = AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'editar');
$tienePermisoPlantillasExplicito = isset($_SESSION['permisos']['personas_plantillas_whatsapp']) && is_array($_SESSION['permisos']['personas_plantillas_whatsapp']);
$puedeGestionPlantillas = AuthController::esAdministrador()
    || ($tienePermisoPlantillasExplicito
        ? AuthController::tienePermiso('personas_plantillas_whatsapp', 'ver')
        : AuthController::tienePermiso('personas', 'editar'));
$tienePermisoFormularioPublicoExplicito = isset($_SESSION['permisos']['personas_formulario_publico']) && is_array($_SESSION['permisos']['personas_formulario_publico']);
$puedeVerFormularioPublico = AuthController::esAdministrador()
    || ($tienePermisoFormularioPublicoExplicito
        ? AuthController::tienePermiso('personas_formulario_publico', 'ver')
        : $puedeCrearPersona);
$mostrarAcciones = $puedeVerPersona || $puedeEditarPersona || $puedeEliminarPersona;
?>

<div class="page-header">
    <h2>Personas</h2>
    <div class="page-actions personas-mobile-stack">
        <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-nav-pill active">Personas</a>
        <a href="<?= PUBLIC_URL ?>?url=personas/ganar" class="btn btn-nav-pill">Pendiente por consolidar</a>
        <?php if ($puedeVerFormularioPublico): ?>
        <a href="<?= PUBLIC_URL ?>?url=registro_personas" class="btn btn-primary" target="_blank" rel="noopener">
            <i class="bi bi-box-arrow-up-right"></i> Formulario público
        </a>
        <?php endif; ?>
        <?php if ($puedeExportarPersonas): ?>
        <a href="<?= PUBLIC_URL ?>?url=personas/exportarExcel<?= !empty($_GET['perfil']) ? '&perfil=' . urlencode((string)$_GET['perfil']) : '' ?><?= !empty($_GET['ministerio']) ? '&ministerio=' . urlencode((string)$_GET['ministerio']) : '' ?><?= !empty($_GET['lider']) ? '&lider=' . urlencode((string)$_GET['lider']) : '' ?><?= !empty($_GET['buscar']) ? '&buscar=' . urlencode((string)$_GET['buscar']) : '' ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
        <?php endif; ?>
        <?php if ($puedeGestionPlantillas): ?>
        <a href="<?= PUBLIC_URL ?>?url=personas/plantillas-whatsapp" class="btn btn-secondary">
            <i class="bi bi-chat-dots"></i> Plantilla mensaje what
        </a>
        <?php endif; ?>
        <?php if ($puedeCrearPersona): ?>
        <a href="<?= PUBLIC_URL ?>?url=personas/crear" class="btn btn-primary">+ Nueva Persona</a>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-body">
        <?php
        $filtroPerfilListado = (string)($filtroPerfilActual ?? ($_GET['perfil'] ?? ''));
        $filtroMinisterioListado = (string)($filtroMinisterioActual ?? ($_GET['ministerio'] ?? ''));
        $filtroLiderListado = (string)($filtroLiderActual ?? ($_GET['lider'] ?? ''));
        $lideresFiltradosFormulario = array_values(array_filter(($lideres ?? []), static function($lider) use ($filtroMinisterioListado) {
            if ($filtroMinisterioListado === '') {
                return true;
            }

            $idMinisterioLider = isset($lider['Id_Ministerio']) ? (string)$lider['Id_Ministerio'] : '';
            if ($filtroMinisterioListado === '0') {
                return $idMinisterioLider === '' || $idMinisterioLider === '0';
            }

            return $idMinisterioLider === $filtroMinisterioListado;
        }));
        ?>
        <form method="GET" action="<?= PUBLIC_URL ?>" class="filters-inline" id="filtro_perfil_form">
            <input type="hidden" name="url" value="personas">
            <input type="hidden" name="perfil" value="<?= htmlspecialchars($filtroPerfilListado) ?>">
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
                <?php if (!empty($_GET['perfil']) || !empty($_GET['buscar']) || !empty($_GET['ministerio']) || !empty($_GET['lider'])): ?>
                <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-body" style="padding: 12px 16px;">
        <?php $perfilActivo = (string)($filtroPerfilActual ?? ($_GET['perfil'] ?? '')); ?>
        <?php $qsMinisterio = ((string)($filtroMinisterioActual ?? '') !== '') ? '&ministerio=' . urlencode((string)$filtroMinisterioActual) : ''; ?>
        <?php $qsLider = ((string)($filtroLiderActual ?? '') !== '') ? '&lider=' . urlencode((string)$filtroLiderActual) : ''; ?>
        <?php $qsBuscar = !empty($filtroNombreActual) ? '&buscar=' . urlencode((string)$filtroNombreActual) : ''; ?>
        <?php $totalResumenRoles = (int)($totalesPerfil['lideres_12'] ?? 0) + (int)($totalesPerfil['lideres_celula'] ?? 0) + (int)($totalesPerfil['asistentes'] ?? 0) + (int)($totalesPerfil['otros'] ?? 0); ?>
        <div class="resumen-roles-total">
            Total en listado actual: <strong><?= $totalResumenRoles ?></strong>
        </div>
        <div class="personas-resumen-roles">
            <a href="<?= PUBLIC_URL ?>?url=personas&perfil=lideres_12<?= $qsMinisterio ?><?= $qsLider ?><?= $qsBuscar ?>" class="resumen-role-item <?= $perfilActivo === 'lideres_12' ? 'active' : '' ?>">
                <span class="resumen-role-label">Líder de 12</span>
                <strong class="resumen-role-value"><?= (int)($totalesPerfil['lideres_12'] ?? 0) ?></strong>
            </a>
            <a href="<?= PUBLIC_URL ?>?url=personas&perfil=lideres_celula<?= $qsMinisterio ?><?= $qsLider ?><?= $qsBuscar ?>" class="resumen-role-item <?= $perfilActivo === 'lideres_celula' ? 'active' : '' ?>">
                <span class="resumen-role-label">Líder de célula</span>
                <strong class="resumen-role-value"><?= (int)($totalesPerfil['lideres_celula'] ?? 0) ?></strong>
            </a>
            <a href="<?= PUBLIC_URL ?>?url=personas&perfil=asistentes<?= $qsMinisterio ?><?= $qsLider ?><?= $qsBuscar ?>" class="resumen-role-item <?= $perfilActivo === 'asistentes' ? 'active' : '' ?>">
                <span class="resumen-role-label">Asistentes</span>
                <strong class="resumen-role-value"><?= (int)($totalesPerfil['asistentes'] ?? 0) ?></strong>
            </a>
            <a href="<?= PUBLIC_URL ?>?url=personas&perfil=otros<?= $qsMinisterio ?><?= $qsLider ?><?= $qsBuscar ?>" class="resumen-role-item <?= $perfilActivo === 'otros' ? 'active' : '' ?>">
                <span class="resumen-role-label">Otros roles</span>
                <strong class="resumen-role-value"><?= (int)($totalesPerfil['otros'] ?? 0) ?></strong>
            </a>
        </div>
    </div>
</div>

<div class="table-container">
    <table class="data-table ganar-table mobile-persona-accordion">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Ministerio</th>
                <th>Líder</th>
                <?php if ($mostrarAcciones): ?><th class="action-col">Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($personas)): ?>
                <?php foreach ($personas as $persona): ?>
                    <tr>
                        <td>
                            <span class="ganar-cell-truncate persona-nombre-cell" title="<?= htmlspecialchars($persona['Nombre'] . ' ' . $persona['Apellido']) ?>">
                                <?= htmlspecialchars($persona['Nombre'] . ' ' . $persona['Apellido']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($persona['Nombre_Ministerio'] ?? 'Sin ministerio') ?></td>
                        <td><?= htmlspecialchars(trim((string)($persona['Nombre_Lider'] ?? '')) ?: 'Sin líder') ?></td>
                        <?php if ($mostrarAcciones): ?>
                        <td class="action-col">
                            <div class="action-buttons action-buttons-compact">
                                <?php if ($puedeVerPersona): ?>
                                <a href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= $persona['Id_Persona'] ?>" class="action-icon-btn action-icon-info" title="Ver" aria-label="Ver">
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
                                <a
                                    href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= (int)($persona['Id_Persona'] ?? 0) ?>&panel=escalera#eventos-procesos"
                                    class="action-icon-btn action-icon-escalera"
                                    title="Ir a Escalera del Éxito"
                                    aria-label="Ir a Escalera del Éxito"
                                >
                                    <i class="bi bi-bar-chart-steps"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($puedeEditarPersona): ?>
                                <a href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= $persona['Id_Persona'] ?>" class="action-icon-btn action-icon-warning" title="Editar" aria-label="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                <?php if ($puedeEliminarPersona): ?>
                                <a href="<?= PUBLIC_URL ?>?url=personas/eliminar&id=<?= $persona['Id_Persona'] ?>" class="action-icon-btn action-icon-danger" title="Eliminar" aria-label="Eliminar" onclick="return confirm('¿Eliminar esta persona?')">
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
                    <td colspan="<?= $mostrarAcciones ? '4' : '3' ?>" class="text-center">No hay personas registradas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.persona-nombre-cell {
    font-weight: 700;
}

.personas-resumen-roles {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 10px;
}

.resumen-roles-total {
    margin-bottom: 10px;
    font-size: 13px;
    color: #3d4f6a;
}

.resumen-role-item {
    border: 1px solid #d8e2f1;
    border-radius: 10px;
    background: #f8fbff;
    padding: 10px 12px;
    text-decoration: none;
    transition: background .15s, border-color .15s, box-shadow .15s;
}

.resumen-role-item:hover {
    background: #eef5ff;
    border-color: #b9cdee;
}

.resumen-role-item.active {
    background: #e7f1ff;
    border-color: #4f8edc;
    box-shadow: inset 0 0 0 1px rgba(79, 142, 220, 0.25);
}

.resumen-role-label {
    display: block;
    font-size: 12px;
    color: #5b6b84;
    margin-bottom: 2px;
}

.resumen-role-value {
    font-size: 20px;
    color: #244a84;
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
    min-width: 196px;
}

.ganar-table .action-buttons.action-buttons-compact {
    flex-wrap: nowrap !important;
    justify-content: flex-start;
}

.ganar-table .action-buttons.action-buttons-compact .action-icon-btn {
    flex: 0 0 auto;
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

.action-icon-danger {
    background: #ffe8ea;
    color: #c82333;
    border-color: #ffc1c7;
}

.action-icon-escalera {
    background: #ebe9ff;
    color: #4a3cc9;
    border-color: #cfc8ff;
    cursor: pointer;
}

.action-icon-huella {
    background: #e8fff4;
    color: #14804a;
    border-color: #bfe9d1;
    cursor: pointer;
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
        Ganar: ['Asignado a lider', 'Primer contacto', 'Ubicado en celula', 'No se dispone'],
        Consolidar: ['Universidad de la vida', 'Encuentro', 'Bautismo'],
        Discipular: ['Proyeccion', 'Equipo G12', 'Capacitacion destino nivel 1'],
        Enviar: ['Capacitacion destino nivel 2', 'Capacitacion destino nivel 3', 'Celula']
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
            resultado.Ganar = [false, false, false, false];
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
                if (typeof persistido[etapa][i] !== 'undefined') {
                    combinado[etapa][i] = !!persistido[etapa][i];
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
            modalState.checklist.Ganar[0] = modalState.asignadoALider;
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
        const noDisponibleMarcado = !!(modalState.checklist.Ganar && modalState.checklist.Ganar[3]);

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

                const done = !!(modalState.checklist[etapa] && modalState.checklist[etapa][i]);
                let editable = etapaIndex === indiceProceso;
                if (etapa === 'Ganar' && i === 0) {
                    editable = false;
                }
                if (noDisponibleMarcado && !(etapa === 'Ganar' && i === 3)) {
                    editable = false;
                }
                html += '<td class="escalera-check-item ' + (done ? 'done' : '') + '">';
                html += '<span class="escalera-modal-sub-label">' + escapeHtml(nombreSub) + '</span>';
                html += '<label class="escalera-check-label ' + (editable ? '' : 'disabled') + '">';
                html += '<input type="checkbox" class="js-escalera-check" data-etapa="' + escapeHtml(etapa) + '" data-indice="' + i + '" ' + (done ? 'checked' : '') + ' ' + (editable && !modalState.guardando ? '' : 'disabled') + '>';
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
        guardarChecklist('Ganar', 3, true, observacion, {
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
                    observacion_no_disponible: (etapa === 'Ganar' && indice === 3) ? (observacionNoDisponible || '') : ''
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
                modalState.checklist.Ganar[0] = modalState.asignadoALider;
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

            if (etapa === 'Ganar' && indice === 3 && target.checked) {
                modalState.checklist.Ganar[3] = true;
                renderModal('Escribe la observacion y guardala para marcar No se dispone');
                return;
            }

            if (etapa === 'Ganar' && indice === 3 && !target.checked) {
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
            ministerio: '<?= htmlspecialchars((string)($lider['Id_Ministerio'] ?? ''), ENT_QUOTES) ?>'
        }<?= $index < count(($lideres ?? [])) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
    ];

    function refrescarFiltroLider() {
        const ministerioSeleccionado = String(ministerioSelect.value || '');
        const liderSeleccionado = String(liderSelect.value || '');
        const lideresFiltrados = lideresDisponibles.filter(function(lider) {
            if (ministerioSeleccionado === '') {
                return true;
            }
            if (ministerioSeleccionado === '0') {
                return !lider.ministerio || lider.ministerio === '0';
            }
            return String(lider.ministerio || '') === ministerioSeleccionado;
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

<?php include VIEWS . '/layout/footer.php'; ?>

