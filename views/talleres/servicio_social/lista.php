<?php include VIEWS . '/layout/header.php'; ?>

<?php
$filtros = is_array($filtros ?? null) ? $filtros : [];
$conteos = is_array($conteos ?? null) ? $conteos : [];
$tiposCita = is_array($tipos_cita ?? null) ? $tipos_cita : [];
$remitidoPor = is_array($remitido_por ?? null) ? $remitido_por : [];
$estados = is_array($estados ?? null) ? $estados : [];
$citas = is_array($citas ?? null) ? $citas : [];
$puedeGestionar = !empty($puede_gestionar);
$urlPublico = (string)($url_publico ?? '');
$flashOk = (string)($flash_ok ?? '');
$flashError = (string)($flash_error ?? '');

$qExport = http_build_query(array_filter([
    'estado' => $filtros['estado'] ?? '',
    'tipo' => $filtros['tipo'] ?? '',
    'remitido' => $filtros['remitido'] ?? '',
    'buscar' => $filtros['buscar'] ?? '',
    'desde' => $filtros['desde'] ?? '',
    'hasta' => $filtros['hasta'] ?? '',
], static function ($v) {
    return $v !== null && $v !== '';
}));
?>

<style>
.ss-stats { display:flex; flex-wrap:wrap; gap:10px; margin:12px 0 18px; }
.ss-stat {
    background:#fff; border:1px solid #e5e7eb; border-radius:10px;
    padding:10px 14px; min-width:110px;
}
.ss-stat strong { display:block; font-size:1.25rem; line-height:1.2; }
.ss-stat span { font-size:12px; color:#64748b; }
.ss-badge {
    display:inline-block; padding:3px 8px; border-radius:999px;
    font-size:12px; font-weight:600;
}
.ss-badge-pendiente { background:#fef3c7; color:#92400e; }
.ss-badge-confirmada { background:#dbeafe; color:#1e40af; }
.ss-badge-atendida { background:#d1fae5; color:#065f46; }
.ss-badge-cancelada { background:#fee2e2; color:#991b1b; }
.ss-badge-no_asistio { background:#e2e8f0; color:#475569; }
.ss-need {
    max-width:280px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
</style>

<div class="page-header">
    <h2>Servicio Social</h2>
    <p class="text-muted" style="margin:4px 0 0;">Agendamiento de citas y seguimiento de necesidades. Las solicitudes quedan solo en Servicio Social; no se crean en Personas ni aparecen como nuevas.</p>
    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
        <?php if (!AuthController::esPerfilServicioSocialTalleres()): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver a Talleres
        </a>
        <?php endif; ?>
        <?php if ($puedeGestionar): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social/horarios" class="btn btn-warning btn-sm">
            <i class="bi bi-clock"></i> Horarios por sábado
        </a>
        <?php endif; ?>
        <?php if ($urlPublico !== ''): ?>
        <a href="<?= htmlspecialchars($urlPublico, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-info btn-sm" target="_blank" rel="noopener">
            <i class="bi bi-link-45deg"></i> Formulario público
        </a>
        <?php endif; ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social/exportar<?= $qExport !== '' ? '&' . htmlspecialchars($qExport, ENT_QUOTES, 'UTF-8') : '' ?>" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
    </div>
</div>

<?php if ($flashOk !== ''): ?>
<div class="alert alert-success"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($flashError !== ''): ?>
<div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="ss-stats">
    <div class="ss-stat"><strong><?= (int)($conteos['total'] ?? 0) ?></strong><span>Total</span></div>
    <div class="ss-stat"><strong><?= (int)($conteos['pendiente'] ?? 0) ?></strong><span>Pendientes</span></div>
    <div class="ss-stat"><strong><?= (int)($conteos['confirmada'] ?? 0) ?></strong><span>Confirmadas</span></div>
    <div class="ss-stat"><strong><?= (int)($conteos['atendida'] ?? 0) ?></strong><span>Atendidas</span></div>
    <div class="ss-stat"><strong><?= (int)($conteos['cancelada'] ?? 0) ?></strong><span>Canceladas</span></div>
</div>

<div class="form-container" style="margin-bottom:16px;">
    <form method="GET" class="filter-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;align-items:end;">
        <input type="hidden" name="url" value="talleres/servicio-social">

        <div class="form-group" style="margin:0;">
            <label for="buscar">Buscar</label>
            <input id="buscar" type="search" name="buscar" class="form-control" value="<?= htmlspecialchars((string)($filtros['buscar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre, cédula, teléfono…">
        </div>

        <div class="form-group" style="margin:0;">
            <label for="estado">Estado</label>
            <select id="estado" name="estado" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($estados as $k => $label): ?>
                <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= (($filtros['estado'] ?? '') === $k) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin:0;">
            <label for="tipo">Tipo de cita</label>
            <select id="tipo" name="tipo" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($tiposCita as $k => $label): ?>
                <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= (($filtros['tipo'] ?? '') === $k) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin:0;">
            <label for="remitido">Remitido por</label>
            <select id="remitido" name="remitido" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($remitidoPor as $k => $label): ?>
                <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= (($filtros['remitido'] ?? '') === $k) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin:0;">
            <label for="desde">Desde</label>
            <input id="desde" type="date" name="desde" class="form-control" value="<?= htmlspecialchars((string)($filtros['desde'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group" style="margin:0;">
            <label for="hasta">Hasta</label>
            <input id="hasta" type="date" name="hasta" class="form-control" value="<?= htmlspecialchars((string)($filtros['hasta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="form-group" style="margin:0;display:flex;gap:8px;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Fecha pref.</th>
                <th>Solicitante</th>
                <th>Contacto</th>
                <th>Tipo de cita</th>
                <th>Necesidad</th>
                <th>Remitido por</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($citas === []): ?>
            <tr>
                <td colspan="8" class="text-muted">No hay citas con los filtros actuales.</td>
            </tr>
            <?php else: ?>
                <?php foreach ($citas as $cita): ?>
                    <?php
                    $id = (int)($cita['Id_Cita'] ?? 0);
                    $estado = (string)($cita['Estado'] ?? 'pendiente');
                    $tipo = (string)($cita['Tipo_Cita'] ?? '');
                    $rem = (string)($cita['Remitido_Por'] ?? '');
                    $nombre = trim((string)($cita['Nombre'] ?? '') . ' ' . (string)($cita['Apellido'] ?? ''));
                    $necesidad = (string)($cita['Necesidad_Principal'] ?? '');
                    $hora = trim((string)($cita['Hora_Preferida'] ?? ''));
                    ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars(substr((string)($cita['Fecha_Preferida'] ?? ''), 0, 10), ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($hora !== ''): ?>
                                <br><small class="text-muted"><?= htmlspecialchars(TallerServicioSocial::etiquetaHora($hora), ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (!empty($cita['Documento'])): ?>
                                <?php
                                $tipoDoc = TallerServicioSocial::etiquetaTipoDocumento((string)($cita['Tipo_Documento'] ?? ''));
                                $abrev = $tipoDoc !== '—' ? preg_replace('/\s.*/', '', $tipoDoc) : 'Doc';
                                ?>
                                <br><small class="text-muted"><?= htmlspecialchars($abrev, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$cita['Documento'], ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endif; ?>
                            <?php if (!empty($cita['Nombre_Eps'])): ?>
                                <br><small class="text-muted">EPS: <?= htmlspecialchars((string)$cita['Nombre_Eps'], ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars((string)($cita['Telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($cita['Email'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars((string)$cita['Email'], ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(TallerServicioSocial::etiquetaTipo($tipo), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><div class="ss-need" title="<?= htmlspecialchars($necesidad, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($necesidad, ENT_QUOTES, 'UTF-8') ?></div></td>
                        <td>
                            <?= htmlspecialchars(TallerServicioSocial::etiquetaRemitido($rem), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($cita['Remitido_Detalle'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars((string)$cita['Remitido_Detalle'], ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="ss-badge ss-badge-<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(TallerServicioSocial::etiquetaEstado($estado), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social/ver&id=<?= $id ?>" class="btn btn-sm btn-outline-primary" title="Ver respuesta">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
