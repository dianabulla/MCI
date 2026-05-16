<?php include VIEWS . '/layout/header.php'; ?>

<?php
$cardsDashboard = [];
$esDiscipuloSoloDiscipular = AuthController::esRolDiscipuloUsuario()
    && !AuthController::esAdministrador();
$esContextoMaestro = AuthController::getActiveContext() === 'maestro';

if ($esDiscipuloSoloDiscipular) {
    $cardsDashboard[] = [
        'titulo' => 'Discipular',
        'subtitulo' => 'Formacion y crecimiento',
        'valor' => (int)($totalDiscipular ?? 0),
        'accion' => 'Ver modulo',
        'href' => PUBLIC_URL . '?url=programas/evaluaciones',
        'icono' => 'bi-journal-richtext',
        'clase' => 'discipular',
    ];
}

if (!$esDiscipuloSoloDiscipular && AuthController::puedeVerModuloPersonasGanar()) {
    $cardsDashboard[] = [
        'titulo' => 'Ganar-Consolidar',
        'subtitulo' => 'Almas nuevas y primer contacto',
        'valor' => (int)($totalPersonas ?? 0),
        'accion' => 'Ver todas',
        'href' => PUBLIC_URL . '?url=personas',
        'icono' => 'bi-person-heart',
        'clase' => 'ganar',
    ];

    $cardsDashboard[] = [
        'titulo' => 'Discipular',
        'subtitulo' => 'Formacion y crecimiento',
        'valor' => (int)($totalDiscipular ?? 0),
        'accion' => 'Ver modulo',
        'href' => PUBLIC_URL . '?url=discipular/ministerios/equipo-principal',
        'icono' => 'bi-journal-richtext',
        'clase' => 'discipular',
    ];
}

if (!$esDiscipuloSoloDiscipular
    && AuthController::puedeVerPersonasConsulta()
    && !AuthController::puedeVerModuloPersonasGanar()
    && !AuthController::debeUsarSoloVistaProgramasPersonas()) {
    $cardsDashboard[] = [
        'titulo' => 'Personas',
        'subtitulo' => 'Consulta de discipulos y listados',
        'valor' => (int)($totalPersonas ?? 0),
        'accion' => 'Abrir listado',
        'href' => PUBLIC_URL . '?url=personas',
        'icono' => 'bi-people',
        'clase' => 'personas-consulta',
    ];
}

if (!$esDiscipuloSoloDiscipular
    && !$esContextoMaestro
    && !AuthController::esAdministrador()
    && !AuthController::tienePermiso('personas', 'ver')
    && AuthController::tienePermiso('discipular_evaluaciones', 'ver')) {
    $cardsDashboard[] = [
        'titulo' => 'Discipular',
        'subtitulo' => 'Formacion y crecimiento',
        'valor' => (int)($totalDiscipular ?? 0),
        'accion' => 'Ver modulo',
        'href' => PUBLIC_URL . '?url=programas/evaluaciones',
        'icono' => 'bi-journal-richtext',
        'clase' => 'discipular',
    ];
}

if (!$esDiscipuloSoloDiscipular && (AuthController::esAdministrador() || AuthController::tienePermiso('celulas', 'ver'))) {
    $cardsDashboard[] = [
        'titulo' => 'Enviar',
        'subtitulo' => 'Celulas activas en mision',
        'valor' => (int)($totalCelulas ?? 0),
        'accion' => 'Ver todas',
        'href' => PUBLIC_URL . '?url=celulas',
        'icono' => 'bi-send-check',
        'clase' => 'enviar',
    ];
}

if (!$esDiscipuloSoloDiscipular && (AuthController::esAdministrador() || AuthController::tienePermiso('teen', 'ver'))) {
    $cardsDashboard[] = [
        'titulo' => 'Teens',
        'subtitulo' => 'Acompanamiento de nuevas generaciones',
        'valor_html' => '<i class="bi bi-balloon-heart"></i>',
        'accion' => 'Abrir registro',
        'href' => PUBLIC_URL . '?url=teen/registro-menores',
        'icono' => 'bi-balloon-heart',
        'clase' => 'teens',
    ];
}

if (!$esDiscipuloSoloDiscipular
    && !$esContextoMaestro
    && (
        AuthController::esAdministrador()
        || AuthController::tienePermiso('programas', 'ver')
        || AuthController::tienePermiso('personas_consulta', 'ver')
        || AuthController::tienePermiso('programas', 'ver_universidad_vida')
        || AuthController::tienePermiso('programas', 'ver_capacitacion_destino')
    )) {
    $cardsDashboard[] = [
        'titulo' => 'Programas',
        'subtitulo' => 'Universidad de la Vida y Capacitación Destino',
        'valor_html' => '<i class="bi bi-mortarboard"></i>',
        'accion' => 'Ver programas',
        'href' => PUBLIC_URL . '?url=programas',
        'icono' => 'bi-mortarboard',
        'clase' => 'programas',
    ];
}
?>

<div class="page-header">
    <h2>Panel de Control</h2>
</div>

<div class="dashboard-grid home-dashboard-grid">
    <?php foreach ($cardsDashboard as $card): ?>
    <a href="<?= htmlspecialchars((string)$card['href']) ?>" class="dashboard-card home-dashboard-card home-dashboard-card-link home-dashboard-card--<?= htmlspecialchars((string)$card['clase']) ?>">
        <div class="home-dashboard-head">
            <span class="home-dashboard-avatar">
                <i class="bi <?= htmlspecialchars((string)$card['icono']) ?>"></i>
            </span>
            <div>
                <h3><?= htmlspecialchars((string)$card['titulo']) ?></h3>
                <p><?= htmlspecialchars((string)$card['subtitulo']) ?></p>
            </div>
        </div>
        <div class="value">
            <?php if (isset($card['valor_html'])): ?>
                <?= $card['valor_html'] ?>
            <?php else: ?>
                <?= (int)($card['valor'] ?? 0) ?>
            <?php endif; ?>
        </div>
        <span class="home-dashboard-cta"><?= htmlspecialchars((string)$card['accion']) ?></span>
    </a>
    <?php endforeach; ?>
</div>

<?php if (!$esDiscipuloSoloDiscipular && !empty($eventosProximos) && (AuthController::esAdministrador() || AuthController::tienePermiso('eventos', 'ver'))): ?>
<div class="main-content" style="margin-top: 30px;">
    <h3>Próximos Eventos</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Evento</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Lugar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($eventosProximos as $evento): ?>
            <tr>
                <td data-label="Evento"><?= htmlspecialchars($evento['Nombre_Evento']) ?></td>
                <td data-label="Fecha"><?= date('d/m/Y', strtotime($evento['Fecha_Evento'])) ?></td>
                <td data-label="Hora"><?= $evento['Hora_Evento'] ? date('h:i A', strtotime($evento['Hora_Evento'])) : 'No especificada' ?></td>
                <td data-label="Lugar"><?= htmlspecialchars($evento['Lugar_Evento'] ?? 'No especificado') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<style>
.home-dashboard-card {
    border-top: 1px solid #d7e4f4;
    min-height: 210px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    background:
        radial-gradient(circle at top right, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0) 34%),
        linear-gradient(180deg, #ffffff 0%, #f7fbff 100%);
}

.home-dashboard-card::after {
    content: '';
    position: absolute;
    inset: auto -28px -34px auto;
    width: 104px;
    height: 104px;
    border-radius: 50%;
    background: rgba(56, 104, 181, 0.08);
}

.home-dashboard-head {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    position: relative;
    z-index: 1;
}

.home-dashboard-avatar {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 56px;
    font-size: 1.45rem;
    color: #1e4d89;
    background: linear-gradient(180deg, #ffffff 0%, #e8f1ff 100%);
    border: 1px solid #d6e4f8;
    box-shadow: 0 10px 18px rgba(45, 82, 140, 0.08);
}

.home-dashboard-head h3 {
    margin: 4px 0 4px;
    font-size: 1.18rem;
}

.home-dashboard-head p {
    margin: 0;
    color: #687d9c;
    font-size: 0.86rem;
    line-height: 1.35;
}

.home-dashboard-card .value {
    position: relative;
    z-index: 1;
    font-size: 3rem;
    line-height: 1;
    margin: 20px 0 16px;
}

.home-dashboard-card-link {
    text-decoration: none;
    color: inherit;
}

.home-dashboard-cta {
    position: relative;
    z-index: 1;
    align-self: flex-start;
    border-radius: 999px;
    border: 1px solid #c7d7ee;
    background: #f3f8ff;
    color: #2f4f79;
    font-weight: 600;
    font-size: .82rem;
    line-height: 1;
    padding-top: 8px;
    padding-bottom: 8px;
    padding-left: 14px;
    padding-right: 14px;
}

.home-dashboard-card--ganar {
    border-top-color: #d8d7ff;
}

.home-dashboard-card--ganar .value,
.home-dashboard-card--ganar .home-dashboard-avatar {
    color: #5b5ce1;
}


.home-dashboard-card--discipular {
    border-top-color: #ead6b4;
}

.home-dashboard-card--discipular .value,
.home-dashboard-card--discipular .home-dashboard-avatar {
    color: #7a4e08;
}

.home-dashboard-card--enviar {
    border-top-color: #cdebd6;
}

.home-dashboard-card--enviar .value,
.home-dashboard-card--enviar .home-dashboard-avatar {
    color: #28a745;
}

.home-dashboard-card--teens {
    border-top-color: #ffd4e7;
}

.home-dashboard-card--teens .value,
.home-dashboard-card--teens .home-dashboard-avatar {
    color: #e83e8c;
}

@media (max-width: 700px) {
    .home-dashboard-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>
