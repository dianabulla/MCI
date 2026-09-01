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
$sabadosFiltro = is_array($sabados_filtro ?? null) ? $sabados_filtro : [];
$esPerfilSs = AuthController::esPerfilServicioSocialTalleres();

$qExport = http_build_query(array_filter([
    'estado' => $filtros['estado'] ?? '',
    'buscar' => $filtros['buscar'] ?? '',
    'sabado' => $filtros['sabado'] ?? '',
], static function ($v) {
    return $v !== null && $v !== '';
}));
$urlExport = PUBLIC_URL . '?url=talleres/servicio-social/exportar' . ($qExport !== '' ? '&' . $qExport : '');
?>

<style>
.ss-shell { display:flex; flex-direction:column; gap:16px; }
.ss-head {
    display:flex; justify-content:space-between; align-items:flex-start;
    gap:16px; flex-wrap:wrap; padding-bottom:14px; border-bottom:1px solid #e2e9f8;
}
.ss-head h2 { margin:0; }
.ss-head p { margin:6px 0 0; max-width:36rem; line-height:1.4; }
.ss-head-actions { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
.ss-btn {
    display:inline-flex; align-items:center; gap:7px;
    padding:9px 14px; border-radius:10px; font-size:0.84rem; font-weight:700;
    text-decoration:none; border:1px solid transparent; white-space:nowrap;
}
.ss-btn-ghost { background:#f8fafc; color:#334155 !important; border-color:#e2e8f0; }
.ss-btn-ghost:hover { background:#eef2f7; }
.ss-btn-main { background:#1e4a89; color:#fff !important; box-shadow:0 3px 10px rgba(30,74,137,.18); }
.ss-btn-main:hover { background:#163a6e; color:#fff !important; }
.ss-btn-soft { background:#fff7ed; color:#9a3412 !important; border-color:#fed7aa; }
.ss-btn-soft:hover { background:#ffedd5; }
.ss-btn-ok { background:#ecfdf5; color:#047857 !important; border-color:#a7f3d0; }
.ss-btn-ok:hover { background:#d1fae5; }
.ss-summary { display:flex; gap:8px; flex-wrap:wrap; }
.ss-stat {
    background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;
    padding:10px 14px; min-width:92px; flex:1 1 92px;
}
.ss-stat strong { display:block; font-size:1.2rem; font-weight:800; color:#1e3a5f; line-height:1.1; }
.ss-stat span { font-size:0.7rem; color:#64748b; text-transform:uppercase; letter-spacing:.03em; }
.ss-stat--pend { background:#fffbeb; border-color:#fde68a; }
.ss-stat--pend strong { color:#92400e; }
.ss-stat--ok { background:#ecfdf5; border-color:#a7f3d0; }
.ss-stat--ok strong { color:#166534; }
.ss-filters {
    background:#fff; border:1px solid #dbe7f3; border-radius:12px;
    padding:12px 14px; box-shadow:0 1px 4px rgba(15,23,42,.05);
}
.ss-filters form {
    display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;
}
.ss-filters label {
    display:block; font-size:0.72rem; font-weight:700; color:#475569;
    text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px;
}
.ss-filters .form-control { min-width:140px; }
.ss-filters input[type=search] { min-width:200px; }
.ss-filters .ss-filter-dates { display:flex; gap:8px; }
.ss-filters .ss-filter-dates .form-control { min-width:132px; }
.ss-card { background:#fff; border:1px solid #dbe7f3; border-radius:12px; box-shadow:0 1px 4px rgba(15,23,42,.08); overflow:hidden; }
.ss-table-wrap { overflow-x:auto; }
.ss-table { width:100%; border-collapse:collapse; }
.ss-table th {
    background:#f8fafc; color:#475569; font-size:0.68rem; text-transform:uppercase;
    letter-spacing:.04em; padding:9px 10px; border-bottom:2px solid #dbe7f3; text-align:left;
}
.ss-table td {
    padding:10px; font-size:0.82rem; color:#1e293b; border-bottom:1px solid #eef2f7; vertical-align:top;
}
.ss-table tbody tr:hover { background:#f7faff; }
.ss-table .col-acc { white-space:nowrap; width:88px; }
.ss-meta { display:block; margin-top:2px; font-size:0.75rem; color:#64748b; }
.ss-need { max-width:220px; }
.ss-badge {
    display:inline-block; padding:3px 8px; border-radius:999px;
    font-size:12px; font-weight:600;
}
.ss-badge-pendiente { background:#fef3c7; color:#92400e; }
.ss-badge-confirmada { background:#dbeafe; color:#1e40af; }
.ss-badge-atendida { background:#d1fae5; color:#065f46; }
.ss-badge-cancelada { background:#fee2e2; color:#991b1b; }
.ss-badge-no_asistio { background:#e2e8f0; color:#475569; }
@media (max-width: 720px) {
    .ss-filters .form-control, .ss-filters input[type=search] { min-width:100%; width:100%; }
    .ss-filter-dates { width:100%; }
}
</style>

<div class="ss-shell">
    <div class="ss-head">
        <div>
            <h2>Servicio Social</h2>
            <p class="text-muted">Agenda: una fila por persona, con su última cita. El historial de atenciones está dentro de cada ficha.</p>
        </div>
        <div class="ss-head-actions">
            <?php if (!$esPerfilSs): ?>
            <a href="<?= PUBLIC_URL ?>?url=talleres" class="ss-btn ss-btn-ghost">
                <i class="bi bi-arrow-left"></i> Talleres
            </a>
            <?php endif; ?>
            <?php if ($puedeGestionar): ?>
            <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social/horarios" class="ss-btn ss-btn-soft">
                <i class="bi bi-clock"></i> Horarios
            </a>
            <?php endif; ?>
            <?php if ($urlPublico !== ''): ?>
            <a href="<?= htmlspecialchars($urlPublico, ENT_QUOTES, 'UTF-8') ?>" class="ss-btn ss-btn-main" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right"></i> Formulario
            </a>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($urlExport, ENT_QUOTES, 'UTF-8') ?>" class="ss-btn ss-btn-ok">
                <i class="bi bi-download"></i> Excel
            </a>
        </div>
    </div>

    <?php if ($flashOk !== ''): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($flashError !== ''): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="ss-summary">
        <div class="ss-stat">
            <strong><?= (int)($conteos['total'] ?? 0) ?></strong>
            <span>Total</span>
        </div>
        <div class="ss-stat ss-stat--pend">
            <strong><?= (int)($conteos['pendiente'] ?? 0) ?></strong>
            <span>Pendientes</span>
        </div>
        <div class="ss-stat">
            <strong><?= (int)($conteos['confirmada'] ?? 0) ?></strong>
            <span>Confirmadas</span>
        </div>
        <div class="ss-stat ss-stat--ok">
            <strong><?= (int)($conteos['atendida'] ?? 0) ?></strong>
            <span>Atendidas</span>
        </div>
        <div class="ss-stat">
            <strong><?= (int)($conteos['cancelada'] ?? 0) ?></strong>
            <span>Canceladas</span>
        </div>
        <div class="ss-stat">
            <strong><?= (int)($conteos['no_asistio'] ?? 0) ?></strong>
            <span>No asistió</span>
        </div>
    </div>

    <div class="ss-filters">
        <form method="GET">
            <input type="hidden" name="url" value="talleres/servicio-social">
            <div>
                <label for="buscar">Buscar</label>
                <input id="buscar" type="search" name="buscar" class="form-control" value="<?= htmlspecialchars((string)($filtros['buscar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre, cédula o teléfono" autocomplete="off">
            </div>
            <div>
                <label for="sabado">Sábado (agenda)</label>
                <select id="sabado" name="sabado" class="form-control">
                    <option value="">Todas las semanas</option>
                    <?php foreach ($sabadosFiltro as $sab): ?>
                    <?php $fechaSab = (string)($sab['fecha'] ?? ''); ?>
                    <option value="<?= htmlspecialchars($fechaSab, ENT_QUOTES, 'UTF-8') ?>" <?= (($filtros['sabado'] ?? '') === $fechaSab) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)($sab['label'] ?? $fechaSab), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
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
            <div style="display:flex;gap:8px;">
                <button type="submit" class="ss-btn ss-btn-main">Buscar</button>
                <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social" class="ss-btn ss-btn-ghost">Limpiar</a>
            </div>
        </form>
    </div>

    <div class="ss-card">
        <div class="ss-table-wrap">
            <table class="ss-table">
                <thead>
                    <tr>
                        <th>Cita</th>
                        <th>Solicitante</th>
                        <th>Tipo</th>
                        <th>Necesidad</th>
                        <th>Remitido</th>
                        <th>Estado</th>
                        <th class="col-acc"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($citas === []): ?>
                    <tr>
                        <td colspan="7" class="text-muted">No hay personas en la agenda con los filtros actuales.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($citas as $cita): ?>
                            <?php
                            $id = (int)($cita['Id_Cita'] ?? 0);
                            $estado = TallerServicioSocial::normalizarEstado((string)($cita['Estado'] ?? 'pendiente'));
                            $tipo = (string)($cita['Tipo_Cita'] ?? '');
                            $rem = (string)($cita['Remitido_Por'] ?? '');
                            $nombre = trim((string)($cita['Nombre'] ?? '') . ' ' . (string)($cita['Apellido'] ?? ''));
                            $necesidad = (string)($cita['Necesidad_Principal'] ?? '');
                            $hora = trim((string)($cita['Hora_Preferida'] ?? ''));
                            $tel = trim((string)($cita['Telefono'] ?? ''));
                            ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars(substr((string)($cita['Fecha_Preferida'] ?? ''), 0, 10), ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($hora !== ''): ?>
                                        <span class="ss-meta"><?= htmlspecialchars(TallerServicioSocial::etiquetaHora($hora), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($nombre !== '' ? $nombre : 'Sin nombre', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if (!empty($cita['Documento'])): ?>
                                        <?php
                                        $tipoDoc = TallerServicioSocial::etiquetaTipoDocumento((string)($cita['Tipo_Documento'] ?? ''));
                                        $abrev = $tipoDoc !== '—' ? preg_replace('/\s.*/', '', $tipoDoc) : 'Doc';
                                        ?>
                                        <span class="ss-meta"><?= htmlspecialchars($abrev, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)$cita['Documento'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <?php if ($tel !== ''): ?>
                                        <span class="ss-meta"><?= htmlspecialchars($tel, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(TallerServicioSocial::etiquetaTipo($tipo), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div class="ss-need" title="<?= htmlspecialchars($necesidad, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($necesidad, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td>
                                    <?= htmlspecialchars(TallerServicioSocial::etiquetaRemitido($rem), ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($cita['Remitido_Detalle'])): ?>
                                        <span class="ss-meta"><?= htmlspecialchars((string)$cita['Remitido_Detalle'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="ss-badge ss-badge-<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(TallerServicioSocial::etiquetaEstado($estado), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="col-acc">
                                    <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social/ver&id=<?= $id ?>" class="btn btn-sm btn-outline-primary" title="Ver cita">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($puedeGestionar): ?>
                                    <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social/eliminar&id=<?= $id ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       title="Eliminar cita"
                                       onclick="return confirm('¿Eliminar la cita de <?= htmlspecialchars($nombre !== '' ? $nombre : ('#' . $id), ENT_QUOTES, 'UTF-8') ?>? Esta acción no se puede deshacer.');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
