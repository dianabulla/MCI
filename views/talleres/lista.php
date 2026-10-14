<?php include VIEWS . '/layout/header.php'; ?>

<?php
$flashOk = $_SESSION['talleres_flash_ok'] ?? '';
$flashError = $_SESSION['talleres_flash_error'] ?? '';
unset($_SESSION['talleres_flash_ok'], $_SESSION['talleres_flash_error']);
$permisosTaller = is_array($permisos_taller ?? null) ? $permisos_taller : [];
$puedeCrear = !empty($permisosTaller['crear']);
$puedeEditar = !empty($permisosTaller['editar']);
$puedeEliminar = !empty($permisosTaller['eliminar']);
$puedeVerRespuestas = !empty($permisosTaller['ver_respuestas']);
$puedeGraficas = !empty($permisosTaller['ver_graficas']);
$puedeSoloGraficas = !empty($permisosTaller['solo_graficas']);
$puedeGestionarEnlace = !empty($permisosTaller['gestionar_enlace']);
?>

<style>
.talleres-lista-head {
    display: flex !important;
    flex-direction: row;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 16px 20px;
    margin-bottom: 22px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e2e9f8;
}
.talleres-lista-title {
    flex: 1 1 220px;
    min-width: 0;
}
.talleres-lista-title h2 {
    display: block;
    margin: 0;
    width: 100%;
}
.talleres-lista-title p {
    display: block;
    margin: 6px 0 0;
    max-width: 28rem;
    line-height: 1.4;
}
.talleres-lista-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: stretch;
    flex: 0 0 auto;
}
.talleres-btn-nuevo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 12px;
    background: #1e4a89;
    color: #fff !important;
    font-weight: 700;
    font-size: 0.95rem;
    text-decoration: none;
    border: 0;
    box-shadow: 0 4px 12px rgba(30, 74, 137, 0.22);
    white-space: nowrap;
}
.talleres-btn-nuevo:hover { background: #163a6e; color: #fff !important; }
.talleres-btn-ss {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 8px 16px 8px 8px;
    border-radius: 12px;
    text-decoration: none;
    color: #9f1239 !important;
    background: linear-gradient(180deg, #fff1f2 0%, #ffe4e6 100%);
    border: 1px solid #fecdd3;
    box-shadow: 0 4px 12px rgba(190, 24, 93, 0.12);
}
.talleres-btn-ss:hover {
    background: linear-gradient(180deg, #ffe4e6 0%, #fecdd3 100%);
    color: #881337 !important;
}
.talleres-btn-ss i {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #e11d48;
    color: #fff;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.talleres-btn-ss .talleres-btn-ss-copy { display: flex; flex-direction: column; gap: 1px; }
.talleres-btn-ss strong { font-size: 0.92rem; line-height: 1.15; }
.talleres-btn-ss small { font-size: 0.72rem; font-weight: 600; color: #be123c; }
</style>

<div class="talleres-lista-head">
    <div class="talleres-lista-title">
        <h2>Talleres</h2>
        <p class="text-muted">
            <?php if ($puedeSoloGraficas): ?>
                Consulte las gráficas estadísticas de cada formulario de taller.
            <?php else: ?>
                Un formulario por tipo de actividad. Edítelo y, para la próxima fecha, abra una nueva actividad: el enlace y el QR no cambian.
            <?php endif; ?>
        </p>
    </div>
    <div class="talleres-lista-actions">
        <?php if ($puedeCrear): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres/crear" class="talleres-btn-nuevo">
            <i class="bi bi-plus-lg"></i> Nuevo formulario
        </a>
        <?php endif; ?>
        <?php if (AuthController::puedeVerServicioSocial()): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social" class="talleres-btn-ss">
            <i class="bi bi-heart-pulse"></i>
            <span class="talleres-btn-ss-copy">
                <strong>Servicio social</strong>
                <small>Citas y atención</small>
            </span>
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($flashOk !== ''): ?>
<div class="alert alert-success"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($flashError !== ''): ?>
<div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php
$titulosVistos = [];
$titulosDuplicados = [];
foreach (is_array($formularios ?? null) ? $formularios : [] as $filaDup) {
    $claveTitulo = mb_strtolower(trim((string)($filaDup['Titulo'] ?? '')), 'UTF-8');
    if ($claveTitulo === '') {
        continue;
    }
    if (isset($titulosVistos[$claveTitulo])) {
        $titulosDuplicados[$claveTitulo] = (string)($filaDup['Titulo'] ?? '');
    } else {
        $titulosVistos[$claveTitulo] = true;
    }
}
?>
<?php if ($titulosDuplicados !== []): ?>
<div class="alert alert-warning">
    Hay más de un formulario con el mismo nombre
    (<?= htmlspecialchars(implode(', ', array_values($titulosDuplicados)), ENT_QUOTES, 'UTF-8') ?>).
    Deje uno, use «Nueva actividad» para las próximas fechas y cierre o elimine el duplicado. No se unieron solos para no mezclar inscripciones.
</div>
<?php endif; ?>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Preguntas</th>
                <th>Respuestas</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($formularios)): ?>
                <?php foreach ($formularios as $f): ?>
                    <?php
                    $id = (int)($f['Id_Formulario'] ?? 0);
                    $slug = (string)($f['Slug'] ?? '');
                    $urlPublica = PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug);
                    $urlQr = PUBLIC_URL . '?url=talleres_publico/qr&slug=' . urlencode($slug);
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string)($f['Titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (trim((string)($f['Actividad_Actual'] ?? '')) !== ''): ?>
                                <br><small class="text-muted">Actividad: <?= htmlspecialchars((string)$f['Actividad_Actual'], ENT_QUOTES, 'UTF-8') ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= (int)($f['Total_Campos'] ?? 0) ?></td>
                        <td>
                            <?= (int)($f['Respuestas_Actividad'] ?? $f['Total_Respuestas'] ?? 0) ?>
                            <?php if ((int)($f['Total_Respuestas'] ?? 0) > (int)($f['Respuestas_Actividad'] ?? 0)): ?>
                                <small class="text-muted">/ <?= (int)$f['Total_Respuestas'] ?> hist.</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($f['Activo'])): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Cerrado</span>
                            <?php endif; ?>
                            <?php if ($puedeEditar): ?>
                            <form method="post" action="<?= PUBLIC_URL ?>?url=talleres/estado" style="display:inline;margin-left:6px;">
                                <input type="hidden" name="id_formulario" value="<?= $id ?>">
                                <input type="hidden" name="activo" value="<?= !empty($f['Activo']) ? '0' : '1' ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <?= !empty($f['Activo']) ? 'Cerrar' : 'Activar' ?>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <?php if ($puedeVerRespuestas): ?>
                            <a href="<?= PUBLIC_URL ?>?url=talleres/respuestas&id=<?= $id ?>" class="btn btn-sm btn-outline-primary" title="Ver respuestas">
                                <i class="bi bi-table"></i>
                            </a>
                            <?php elseif ($puedeGraficas): ?>
                            <a href="<?= PUBLIC_URL ?>?url=talleres/respuestas&id=<?= $id ?>&tab=graficas" class="btn btn-sm btn-outline-primary" title="Ver gráficas">
                                <i class="bi bi-bar-chart-line"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($puedeEditar): ?>
                            <a href="<?= PUBLIC_URL ?>?url=talleres/editar&id=<?= $id ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                            <?php if ($puedeGestionarEnlace && $slug !== ''): ?>
                            <a href="<?= htmlspecialchars($urlPublica, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-info" target="_blank" rel="noopener" title="<?= !empty($f['Activo']) ? 'Abrir formulario público' : 'Ver mensaje del enlace cerrado' ?>">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            <?php if (!empty($f['Activo'])): ?>
                            <a href="<?= htmlspecialchars($urlQr, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-success" target="_blank" rel="noopener" title="Ver e imprimir QR">
                                <i class="bi bi-qr-code"></i>
                            </a>
                            <?php endif; ?>
                            <?php endif; ?>
                            <?php if ($puedeEliminar): ?>
                            <a href="<?= PUBLIC_URL ?>?url=talleres/eliminar&id=<?= $id ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('¿Eliminar este formulario y todas sus respuestas?');"
                               title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No hay formularios creados. Use «Nuevo formulario» para el tipo de actividad (Taller de Padres, etc.).</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
