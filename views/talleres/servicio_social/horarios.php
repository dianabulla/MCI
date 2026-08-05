<?php include VIEWS . '/layout/header.php'; ?>

<?php
require_once APP . '/Models/TallerServicioSocial.php';

$fecha = (string)($fecha ?? '');
$proximosSabados = is_array($proximos_sabados ?? null) ? $proximos_sabados : TallerServicioSocial::proximosSabados(16);
$horariosSabado = is_array($horarios_sabado ?? null) ? $horarios_sabado : TallerServicioSocial::HORARIOS_SABADO;
$horasHabilitadas = is_array($horas_habilitadas ?? null) ? $horas_habilitadas : array_keys($horariosSabado);
$config = is_array($config ?? null) ? $config : null;
$flashOk = (string)($flash_ok ?? '');
$flashError = (string)($flash_error ?? '');
$tieneConfig = $config !== null;
?>

<style>
.ss-horarios-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 18px;
    margin-bottom: 16px;
}
.ss-horas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 10px;
    margin: 14px 0;
}
.ss-hora-item {
    display: flex;
    align-items: center;
    gap: 8px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
    background: #f8fafc;
}
.ss-hora-item input { width: auto; margin: 0; }
.ss-hora-item.disabled {
    opacity: .55;
    background: #f1f5f9;
}
</style>

<div class="page-header">
    <h2>Horarios por sábado</h2>
    <p class="text-muted" style="margin:4px 0 0;">
        Configura qué horas estarán disponibles cada sábado. Desmarca las que quieras bloquear
        (por ejemplo, si solo atienden de 2:00 a 5:00 p.m., deja marcadas solo esas horas).
    </p>
    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    </div>
</div>

<?php if ($flashOk !== ''): ?>
<div class="alert alert-success"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($flashError !== ''): ?>
<div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="ss-horarios-card">
    <form method="GET" action="<?= PUBLIC_URL ?>" style="margin-bottom:16px;">
        <input type="hidden" name="url" value="talleres/servicio-social/horarios">
        <div class="form-group" style="max-width:420px;">
            <label for="fecha_sel">Seleccionar sábado</label>
            <select id="fecha_sel" name="fecha" class="form-control" onchange="this.form.submit()">
                <?php foreach ($proximosSabados as $sab): ?>
                <?php $f = (string)($sab['fecha'] ?? ''); ?>
                <option value="<?= htmlspecialchars($f, ENT_QUOTES, 'UTF-8') ?>" <?= $fecha === $f ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)($sab['label'] ?? $f), ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if ($fecha !== ''): ?>
    <p style="margin:0 0 8px;">
        <strong><?= htmlspecialchars(TallerServicioSocial::etiquetaSabado($fecha), ENT_QUOTES, 'UTF-8') ?></strong>
        <?php if ($tieneConfig): ?>
            <span class="badge bg-info text-dark" style="margin-left:6px;">Configuración personalizada</span>
        <?php else: ?>
            <span class="badge bg-secondary" style="margin-left:6px;">Todos los horarios habilitados</span>
        <?php endif; ?>
    </p>

    <form method="POST" action="<?= PUBLIC_URL ?>?url=talleres/servicio-social/horarios/guardar">
        <input type="hidden" name="fecha" value="<?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?>">

        <div class="ss-horas-grid">
            <?php foreach ($horariosSabado as $hk => $hl): ?>
            <?php $checked = in_array($hk, $horasHabilitadas, true); ?>
            <label class="ss-hora-item">
                <input type="checkbox" name="horas[]" value="<?= htmlspecialchars($hk, ENT_QUOTES, 'UTF-8') ?>" <?= $checked ? 'checked' : '' ?>>
                <span><?= htmlspecialchars($hl, ENT_QUOTES, 'UTF-8') ?></span>
            </label>
            <?php endforeach; ?>
        </div>

        <div class="form-group">
            <label for="notas">Nota interna (opcional)</label>
            <input id="notas" type="text" name="notas" class="form-control" maxlength="255"
                   placeholder="Ej. Solo atención vespertina por evento en la iglesia"
                   value="<?= htmlspecialchars((string)($config['Notas'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Guardar horarios
            </button>
            <?php if ($tieneConfig): ?>
            <button type="submit" name="restaurar_todos" value="1" class="btn btn-outline-secondary"
                    onclick="return confirm('¿Restaurar todos los horarios para este sábado?');">
                Restaurar todos los horarios
            </button>
            <?php endif; ?>
        </div>
    </form>
    <?php endif; ?>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
