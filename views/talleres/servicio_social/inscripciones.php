<?php include VIEWS . '/layout/header.php'; ?>

<?php
require_once APP . '/Models/TallerServicioSocial.php';

$diasHabilitados = is_array($dias_habilitados ?? null) ? $dias_habilitados : TallerServicioSocial::diasInscripcionPorDefecto();
$diasHabilitados = array_map('intval', $diasHabilitados);
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
</style>

<div class="page-header">
    <h2>Días de inscripción</h2>
    <p class="text-muted" style="margin:4px 0 0;">
        Configura en qué días de la semana se habilita el formulario público de inscripción.
        Si un día no está marcado, ese día el formulario permanece cerrado.
    </p>
    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
        <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social/horarios" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-clock"></i> Horarios de agenda
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
    <p style="margin:0 0 8px;">
        <strong>Formulario público</strong>
        <?php if ($tieneConfig): ?>
            <span class="badge bg-info text-dark" style="margin-left:6px;">Configuración personalizada</span>
        <?php else: ?>
            <span class="badge bg-secondary" style="margin-left:6px;">Todos los días habilitados</span>
        <?php endif; ?>
    </p>

    <form method="POST" action="<?= PUBLIC_URL ?>?url=talleres/servicio-social/inscripciones/guardar">
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin:8px 0 4px;">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="ssMarcarTodas">Marcar todos los días</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="ssDesmarcarTodas">Desmarcar todos</button>
        </div>
        <div class="ss-horas-grid">
            <?php foreach (TallerServicioSocial::DIAS_SEMANA_INSCRIPCION as $num => $label): ?>
            <?php $checked = in_array((int)$num, $diasHabilitados, true); ?>
            <label class="ss-hora-item">
                <input type="checkbox" name="dias[]" value="<?= (int)$num ?>" <?= $checked ? 'checked' : '' ?>>
                <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
            </label>
            <?php endforeach; ?>
        </div>

        <div class="form-group">
            <label for="notas">Nota interna (opcional)</label>
            <input id="notas" type="text" name="notas" class="form-control" maxlength="255"
                   placeholder="Ej. Cerrado los miércoles por reunión de equipo"
                   value="<?= htmlspecialchars((string)($config['Notas'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Guardar días de inscripción
            </button>
        </div>
    </form>
</div>
<script>
(function() {
    var marcar = document.getElementById('ssMarcarTodas');
    var desmarcar = document.getElementById('ssDesmarcarTodas');
    if (!marcar || !desmarcar) return;
    function setAll(on) {
        document.querySelectorAll('input[name="dias[]"]').forEach(function(el) {
            el.checked = on;
        });
    }
    marcar.addEventListener('click', function() { setAll(true); });
    desmarcar.addEventListener('click', function() { setAll(false); });
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
