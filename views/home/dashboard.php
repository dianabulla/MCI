<?php include VIEWS . '/layout/header.php'; ?>

<?php
$indicadoresDashboard = [
    'ganar' => [
        'valor' => (int)($totalGanarConsolidar ?? $totalPersonas ?? 0),
        'etiqueta' => 'personas',
    ],
    'discipular' => [
        'valor' => (int)($totalDiscipular ?? 0),
        'etiqueta' => 'personas',
    ],
    'enviar' => [
        'valor' => (int)($totalPersonasCelulas ?? 0),
        'etiqueta' => 'personas en células',
    ],
    'teens' => [
        'valor' => (int)($totalTeens ?? 0),
        'etiqueta' => 'menores',
    ],
    'programas' => [
        'valor' => (int)($totalProgramas ?? 0),
        'etiqueta' => 'inscritos',
    ],
];
$esDiscipuloSoloDiscipular = AuthController::usaVistaDiscipuloCapacitacionDestino()
    && !AuthController::esAdministrador();
$esContextoMaestro = AuthController::esContextoMaestro();
$esPerfilMenuReducido = $esContextoMaestro || AuthController::esVistaDiscipuloSimplificada();

if ($esDiscipuloSoloDiscipular) {
    $cardsDashboard[] = [
        'titulo' => 'Discipular',
        'subtitulo' => 'Formacion y crecimiento',
        'accion' => 'Ver modulo',
        'href' => PUBLIC_URL . '?url=programas/evaluaciones',
        'icono' => 'bi-journal-richtext',
        'clase' => 'discipular',
    ];
}

if (!$esDiscipuloSoloDiscipular && AuthController::puedeAccederAreaGanarConsolidar()) {
    $cardsDashboard[] = [
        'titulo' => 'Ganar-Consolidar',
        'subtitulo' => AuthController::puedeVerModuloPersonasGanar()
            ? 'Almas ganadas y discípulos'
            : 'Discípulos de tu red',
        'accion' => 'Abrir',
        'href' => PUBLIC_URL . '?url=' . AuthController::urlEntradaGanarConsolidarRelativa(),
        'icono' => 'bi-person-heart',
        'clase' => 'ganar',
    ];
}

if (!$esPerfilMenuReducido
    && !$esDiscipuloSoloDiscipular
    && AuthController::puedeVerModuloPersonasGanar()
    && AuthController::puede('ministerios:ver')) {
    $cardsDashboard[] = [
        'titulo' => 'Discipular',
        'subtitulo' => 'Formacion y crecimiento',
        'accion' => 'Ver modulo',
        'href' => PUBLIC_URL . '?url=discipular/ministerios/equipo-principal',
        'icono' => 'bi-journal-richtext',
        'clase' => 'discipular',
    ];
}

if (!$esPerfilMenuReducido
    && !$esDiscipuloSoloDiscipular
    && !AuthController::esAdministrador()
    && !AuthController::puede('personas:ver')
    && AuthController::puede('discipular_evaluaciones:ver')) {
    $cardsDashboard[] = [
        'titulo' => 'Discipular',
        'subtitulo' => 'Formacion y crecimiento',
        'accion' => 'Ver modulo',
        'href' => PUBLIC_URL . '?url=programas/evaluaciones',
        'icono' => 'bi-journal-richtext',
        'clase' => 'discipular',
    ];
}

if (!$esPerfilMenuReducido && !$esDiscipuloSoloDiscipular && (AuthController::esAdministrador() || AuthController::puede('celulas:ver'))) {
    $cardsDashboard[] = [
        'titulo' => 'Enviar',
        'subtitulo' => 'Celulas activas en mision',
        'accion' => 'Ver todas',
        'href' => PUBLIC_URL . '?url=celulas',
        'icono' => 'bi-send-check',
        'clase' => 'enviar',
    ];
}

if (!$esPerfilMenuReducido && !$esDiscipuloSoloDiscipular && (AuthController::esAdministrador() || AuthController::puede('teen:ver'))) {
    $cardsDashboard[] = [
        'titulo' => 'Teens',
        'subtitulo' => 'Acompanamiento de nuevas generaciones',
        'accion' => 'Abrir registro',
        'href' => PUBLIC_URL . '?url=teen/registro-menores',
        'icono' => 'bi-balloon-heart',
        'clase' => 'teens',
    ];
}

if (!$esPerfilMenuReducido
    && !$esDiscipuloSoloDiscipular
    && (
        AuthController::esAdministrador()
        || AuthController::puede('programas:ver')
        || AuthController::puede('personas_consulta:ver')
        || AuthController::puede('programas:ver_universidad_vida')
        || AuthController::puede('programas:ver_capacitacion_destino')
    )) {
    $cardsDashboard[] = [
        'titulo' => 'Programas',
        'subtitulo' => 'Universidad de la Vida y Capacitación Destino',
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
        <?php
        $kpiCard = $indicadoresDashboard[(string)($card['clase'] ?? '')] ?? null;
        ?>
        <?php if (is_array($kpiCard)): ?>
        <div class="home-dashboard-kpi">
            <strong><?= (int)($kpiCard['valor'] ?? 0) ?></strong>
            <span><?= htmlspecialchars((string)($kpiCard['etiqueta'] ?? 'personas')) ?></span>
        </div>
        <?php endif; ?>
        <span class="home-dashboard-cta"><?= htmlspecialchars((string)$card['accion']) ?></span>
    </a>
    <?php endforeach; ?>
</div>

<?php if (!$esDiscipuloSoloDiscipular && !empty($eventosProximos) && (AuthController::esAdministrador() || AuthController::puede('eventos:ver'))): ?>
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
    min-height: 168px;
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

.home-dashboard-kpi {
    position: relative;
    z-index: 1;
    margin: 12px 0 10px;
    display: flex;
    align-items: baseline;
    gap: 8px;
}

.home-dashboard-kpi strong {
    font-size: 1.55rem;
    font-weight: 800;
    color: #1e3a5f;
    line-height: 1;
}

.home-dashboard-kpi span {
    font-size: 0.78rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .03em;
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

.home-dashboard-card--ganar .home-dashboard-avatar {
    color: #5b5ce1;
}


.home-dashboard-card--discipular {
    border-top-color: #ead6b4;
}

.home-dashboard-card--discipular .home-dashboard-avatar {
    color: #7a4e08;
}

.home-dashboard-card--enviar {
    border-top-color: #cdebd6;
}

.home-dashboard-card--enviar .home-dashboard-avatar {
    color: #28a745;
}

.home-dashboard-card--teens {
    border-top-color: #ffd4e7;
}

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
