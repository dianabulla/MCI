<?php include VIEWS . '/layout/header.php'; ?>
<?php
require_once APP . '/Helpers/ProgramasNavegacion.php';
ProgramasNavegacion::incluirPartial();

$tarjetasRed = is_array($tarjetas_red ?? null) ? $tarjetas_red : [];
$conteosRed = is_array($conteos_red ?? null) ? $conteos_red : [];
$totalInscritos = (int)($conteosRed['total'] ?? 0);
$sinClasificar = (int)($conteosRed['otros'] ?? 0);
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:8px;">
    <div>
        <h2 style="margin:0;">Asistencias Universidad de la Vida</h2>
        <small style="color:#637087;">Elige una red para ver la tabla de asistencias, pagos y clases. El conteo usa la fecha de inscripción del semestre.</small>
    </div>
</div>
<?php
$filtro_semestre_form = true;
$filtro_semestre_buscar = true;
include VIEWS . '/programas/_filtro_semestre.php';
?>

<div class="dashboard-grid uv-asist-redes-grid" style="grid-template-columns:repeat(4,minmax(0,1fr));margin:18px 0;">
    <?php foreach ($tarjetasRed as $tarjeta): ?>
        <a href="<?= htmlspecialchars((string)($tarjeta['url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
           class="gender-card dashboard-card uv-asist-red-card"
           style="border-left-color:<?= htmlspecialchars((string)($tarjeta['color'] ?? '#1e4a89'), ENT_QUOTES, 'UTF-8') ?>;text-decoration:none;color:inherit;">
            <div class="gender-card-toggle">
                <span class="gender-card-title-wrap">
                    <span class="gender-avatar <?= htmlspecialchars((string)($tarjeta['avatar_class'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"><?= (string)($tarjeta['icono'] ?? '') ?></span>
                    <span><?= htmlspecialchars((string)($tarjeta['titulo'] ?? '')) ?></span>
                </span>
                <span class="gender-card-icon">Ver</span>
            </div>
            <div class="gender-card-metric">
                <div class="gender-kpi-grid">
                    <div class="gender-kpi-box" style="width:100%;">
                        <span class="kpi-label">Inscritos</span>
                        <strong class="kpi-value" style="color:<?= htmlspecialchars((string)($tarjeta['color'] ?? '#1e4a89'), ENT_QUOTES, 'UTF-8') ?>;"><?= (int)($tarjeta['total'] ?? 0) ?></strong>
                    </div>
                </div>
                <small style="display:block;margin-top:8px;color:#637087;line-height:1.35;"><?= htmlspecialchars((string)($tarjeta['subtitulo'] ?? '')) ?></small>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<p style="margin:0 0 18px;font-size:0.86rem;color:#64748b;">
    Total inscritos: <strong><?= $totalInscritos ?></strong>
    <?php if ($sinClasificar > 0): ?>
        · <?= $sinClasificar ?> sin red (faltan género, edad o segmento)
    <?php endif; ?>
</p>

<style>
.uv-asist-red-card { padding: 0; overflow: hidden; }
.uv-asist-red-card:hover { box-shadow: 0 4px 14px rgba(15, 23, 42, 0.10); }
.gender-card-toggle {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    border: 0;
    padding: 12px 14px;
    background: transparent;
    color: #204a82;
    font-size: 16px;
    font-weight: 700;
}
.gender-card-title-wrap { display: inline-flex; align-items: center; gap: 8px; }
.gender-avatar {
    display: inline-flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 999px; font-size: 18px; line-height: 1;
}
.gender-avatar-male { background: #e8f2ff; border: 1px solid #b6cff0; }
.gender-avatar-female { background: #fdeaf4; border: 1px solid #ebc4d9; }
.gender-card-icon {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 42px; height: 24px; border-radius: 999px;
    border: 1px solid #9ab7df; background: #fff; font-size: 12px; font-weight: 700; padding: 0 10px;
}
.gender-card-metric { padding: 0 14px 14px 14px; color: #5a6780; }
.gender-kpi-grid { display: grid; grid-template-columns: 1fr; gap: 8px; }
.gender-kpi-box {
    border: 1px solid #d8e4f5; border-radius: 10px; background: #f8fbff;
    padding: 10px 8px; text-align: center;
}
.kpi-label {
    display: block; font-size: 11px; font-weight: 700; color: #4e617d;
    text-transform: uppercase; letter-spacing: .2px; margin-bottom: 3px;
}
.kpi-value { display: block; font-size: 26px; line-height: 1; }
@media (max-width: 900px) {
    .uv-asist-redes-grid { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
}
@media (max-width: 560px) {
    .uv-asist-redes-grid { grid-template-columns: 1fr !important; }
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>
