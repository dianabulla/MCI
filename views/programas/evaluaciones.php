<?php include VIEWS . '/layout/header.php'; ?>

<?php
$mensajeFlash = (string)($mensaje ?? '');
$tipoFlash = (string)($tipo ?? '');
$evaluacionesLista = (array)($evaluaciones ?? []);
$evaluacionActiva = $evaluacion_seleccionada ?? null;
$historialUsuario = (array)($resultados_usuario ?? []);
$historialEvaluacion = (array)($resultados_evaluacion ?? []);
$puedeGestionarEval = !empty($puede_gestionar);
$puedeEditarEval = !empty($puede_editar);
$puedeEliminarEval = !empty($puede_eliminar);
$evaluacionEdicion = $evaluacion_edicion ?? null;
$formularioRepost = is_array($formulario_repost ?? null) ? $formulario_repost : null;
$modoEdicionEval = !empty($evaluacionEdicion) || !empty($formularioRepost['modo_edicion']);
$idEdicionFormulario = $modoEdicionEval
    ? (int)($evaluacionEdicion['Id_Evaluacion'] ?? $formularioRepost['id_evaluacion_edicion'] ?? $formularioRepost['id_evaluacion'] ?? 0)
    : 0;
$puedeConfigurarFechasEval = !empty($puede_configurar_fechas);
$presentacionOk = !empty($presentacion_ok);
$confirmarPresentarEval = "¿Seguro que quieres presentar esta evaluación?\n\n"
    . "Después de ingresar debes presentarla: el tiempo de 20 minutos empieza a correr en cuanto abras la evaluación.\n"
    . "Si no estás listo, cancela y no ingreses todavía.";
$esDiscipuloRol = !empty($es_discipulo);
$esVistaDiscipuloSimplificada = class_exists('AuthController') && AuthController::esVistaDiscipuloSimplificada();
$avisoAccesoDiscipulo = trim((string)($aviso_acceso_discipulo ?? ''));
$estadoIntento = (array)($estado_intento ?? []);
$clasesLinks = (array)($clases_links ?? []);
$accesosDirectosDiscipulo = (array)($accesos_directos_discipulo ?? []);
$tareasPorModuloDiscipulo = (array)($tareas_por_modulo_discipulo ?? []);
$intentosPorEvaluacion = (array)($intentos_por_evaluacion ?? []);
$maxIntentos = (int)($max_intentos ?? 2);
$confirmarReactivarIntentos = '¿Reactivar los intentos de esta persona en esta evaluación? Se borrarán todos los intentos registrados y podrá volver a presentar (máximo ' . $maxIntentos . ' intentos).';
$resultadoDetalle = $resultado_detalle ?? null;
$resumenCapacitacionPorNivel = (array)($resumen_capacitacion_por_nivel ?? []);
$filtroNivelContexto = (int)($filtro_nivel_contexto ?? 0);
$filtroModuloContexto = (int)($filtro_modulo_contexto ?? 0);
$filtroLeccionContexto = (string)($filtro_leccion_contexto ?? 'Sin lección');
$contextoDesdeMaterial = !empty($contexto_desde_material);
$leccionesPorNivelModulo = (array)($lecciones_por_nivel_modulo ?? []);
$urlClaseUnicaDiscipulo = '';
foreach ($accesosDirectosDiscipulo as $accesoTmpClase) {
    $urlTmpClase = trim((string)($accesoTmpClase['url_clase'] ?? ''));
    if ($urlTmpClase !== '') {
        $urlClaseUnicaDiscipulo = $urlTmpClase;
        break;
    }
}
$contextoQuery = '';
$contextoHiddenHtml = '';
if ($contextoDesdeMaterial && $filtroNivelContexto > 0 && $filtroModuloContexto > 0) {
    $contextoQuery = '&from_material=1&nivel=' . $filtroNivelContexto . '&modulo=' . $filtroModuloContexto . '&leccion=' . urlencode($filtroLeccionContexto);
    $contextoHiddenHtml = '<input type="hidden" name="from_material" value="1">'
        . '<input type="hidden" name="filtro_nivel_contexto" value="' . $filtroNivelContexto . '">'
        . '<input type="hidden" name="filtro_modulo_contexto" value="' . $filtroModuloContexto . '">'
        . '<input type="hidden" name="filtro_leccion_contexto" value="' . htmlspecialchars($filtroLeccionContexto, ENT_QUOTES, 'UTF-8') . '">';
}

$leccionesIniciales = [];
if ($filtroNivelContexto > 0 && $filtroModuloContexto > 0) {
    $leccionesIniciales = (array)($leccionesPorNivelModulo[$filtroNivelContexto][$filtroModuloContexto] ?? []);
}
if (empty($leccionesIniciales)) {
    $leccionesIniciales = ['Sin lección'];
}

$grupos = [];
foreach ($evaluacionesLista as $evaluacionTmp) {
    $nivelTmp = (int)($evaluacionTmp['Nivel'] ?? 0);
    $moduloTmp = (int)($evaluacionTmp['Modulo_Numero'] ?? 0);
    
    // Si hay un filtro de módulo, solo incluir evaluaciones de ese módulo
    if ($filtroNivelContexto > 0 && $filtroModuloContexto > 0) {
        if ($nivelTmp !== $filtroNivelContexto || $moduloTmp !== $filtroModuloContexto) {
            continue;
        }
    }
    
    $claveGrupo = 'N' . $nivelTmp . 'M' . $moduloTmp;
    if (!isset($grupos[$claveGrupo])) {
        $grupos[$claveGrupo] = [
            'nivel' => $nivelTmp,
            'modulo' => $moduloTmp,
            'items' => [],
        ];
    }
    $grupos[$claveGrupo]['items'][] = $evaluacionTmp;
}
usort($grupos, static function($a, $b) {
    $cmpNivel = ((int)$a['nivel']) <=> ((int)$b['nivel']);
    if ($cmpNivel !== 0) {
        return $cmpNivel;
    }
    return ((int)$a['modulo']) <=> ((int)$b['modulo']);
});

$evaluacionesOcultasSinFechas = (array)($evaluaciones_ocultas_sin_fechas ?? []);
$totalEvaluacionesFueraVigencia = (int)($total_evaluaciones_fuera_vigencia ?? 0);
$evaluacionesModuloTodas = (array)($evaluaciones_modulo_todas ?? $evaluacionesLista);
$gruposHistoricoEval = [];
foreach ($evaluacionesModuloTodas as $evaluacionHistTmp) {
    $nivelHistTmp = (int)($evaluacionHistTmp['Nivel'] ?? 0);
    $moduloHistTmp = (int)($evaluacionHistTmp['Modulo_Numero'] ?? 0);
    if ($filtroNivelContexto > 0 && $filtroModuloContexto > 0) {
        if ($nivelHistTmp !== $filtroNivelContexto || $moduloHistTmp !== $filtroModuloContexto) {
            continue;
        }
    }
    $claveGrupoHist = 'N' . $nivelHistTmp . 'M' . $moduloHistTmp;
    if (!isset($gruposHistoricoEval[$claveGrupoHist])) {
        $gruposHistoricoEval[$claveGrupoHist] = [
            'nivel' => $nivelHistTmp,
            'modulo' => $moduloHistTmp,
            'items' => [],
        ];
    }
    $gruposHistoricoEval[$claveGrupoHist]['items'][] = $evaluacionHistTmp;
}
$gruposHistoricoEval = array_values($gruposHistoricoEval);

$esMaestroCapEval = class_exists('AuthController') && AuthController::puedeGestionarCapDestinoComoMaestro();
$discipuloConModuloSeleccionado = $esDiscipuloRol && $filtroNivelContexto > 0 && $filtroModuloContexto > 0;
$mostrarNavCapDestino = ($filtroNivelContexto > 0 && $filtroModuloContexto > 0)
    && ($contextoDesdeMaterial || $esMaestroCapEval || $discipuloConModuloSeleccionado);
$urlVolverNivelCap = $filtroNivelContexto > 0
    ? PUBLIC_URL . '?url=home/material/capacitacion-destino&cap_nivel=' . $filtroNivelContexto
    : PUBLIC_URL . '?url=home/material/capacitacion-destino';
$urlVolverModuloCap = ($filtroNivelContexto > 0 && $filtroModuloContexto > 0)
    ? PUBLIC_URL . '?url=home/material/capacitacion-destino&cap_nivel=' . $filtroNivelContexto . '&cap_modulo=' . $filtroModuloContexto
    : $urlVolverNivelCap;
$urlTareasModuloCap = ($filtroNivelContexto > 0 && $filtroModuloContexto > 0)
    ? $urlVolverModuloCap . '&cap_seccion=tareas'
    : '';
$urlInicioCap = PUBLIC_URL . '?url=home/material/capacitacion-destino';
$mostrarSelectorModulosDiscipulo = $esDiscipuloRol && !$discipuloConModuloSeleccionado;
$bloquearClasificacionEval = $mostrarNavCapDestino;
$vistaHistorico = !empty($vista_historico) || (int)($_GET['historico'] ?? 0) === 1;
$urlEvaluacionesPrincipal = PUBLIC_URL . '?url=programas/evaluaciones' . $contextoQuery;
$urlHistoricoPresentaciones = $urlEvaluacionesPrincipal . '&historico=1';
$vistaGestionPrincipal = $puedeGestionarEval && !$vistaHistorico && !$esVistaDiscipuloSimplificada;
$vistaGestionHistorico = $puedeGestionarEval && $vistaHistorico && !$esVistaDiscipuloSimplificada;
?>

<style>
    .disc-eval-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .disc-eval-card {
        border: 1px solid #dbe3f0;
        border-radius: 10px;
        padding: 10px;
        background: #fff;
    }

    .disc-card-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .disc-tareas-wrap {
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px dashed #dbe3f0;
    }

    .disc-tareas-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 8px;
    }

    .disc-tarea-item {
        border: 1px solid #e5ebf7;
        border-radius: 8px;
        padding: 8px;
        background: #fff;
    }

    .disc-tarea-item.is-hidden {
        display: none;
    }

    @media (max-width: 900px) {
        .disc-eval-grid {
            grid-template-columns: 1fr;
        }
    }

    .disc-nav-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }

    .btn-disc-nav {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 0.84rem;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid transparent;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }

    .btn-disc-nav--back {
        background: #fff;
        color: #1e40af;
        border-color: #bfdbfe;
    }

    .btn-disc-nav--back:hover {
        background: #eff6ff;
        color: #1d4ed8;
    }

    .btn-disc-nav--home {
        background: #1f4f93;
        color: #fff;
        border-color: #1f4f93;
    }

    .btn-disc-nav--home:hover {
        background: #1a4380;
        color: #fff;
    }
</style>

<?php
$urlMaterialCapDestino = PUBLIC_URL . '?url=home/material/capacitacion-destino';
$urlInicioDiscipulo = $urlMaterialCapDestino;
$urlAtrasDiscipulo = $urlMaterialCapDestino;
if (!empty($evaluacionActiva) || !empty($resultadoDetalle)) {
    $urlAtrasDiscipulo = PUBLIC_URL . '?url=programas/evaluaciones' . $contextoQuery;
} elseif ($discipuloConModuloSeleccionado || ($contextoDesdeMaterial && $filtroNivelContexto > 0 && $filtroModuloContexto > 0)) {
    $urlAtrasDiscipulo = $urlVolverModuloCap;
}
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;"><?= ($filtroModuloContexto > 0 && ($discipuloConModuloSeleccionado || $mostrarNavCapDestino)) ? 'Evaluaciones módulo ' . (int)$filtroModuloContexto : 'Discipular - Evaluaciones' ?></h2>
        <small style="color:#637087;">
            <?php if ($vistaGestionHistorico): ?>
                Historial de evaluaciones presentadas
            <?php else: ?>
                Solo preguntas cerradas. Se aprueba con 80%.
            <?php endif; ?>
        </small>
        <?php if ($esVistaDiscipuloSimplificada): ?>
            <p style="margin:8px 0 0 0;color:#637087;max-width:720px;">
                Al pulsar <strong>Responder</strong> entrarás a la evaluación en esta misma página. El tiempo de 20 minutos empieza al entrar; si no estás listo, no la abras todavía.
            </p>
        <?php endif; ?>
    </div>
    <div class="header-actions">
        <?php if ($esVistaDiscipuloSimplificada && $discipuloConModuloSeleccionado): ?>
            <div class="disc-nav-actions" data-tour="disc-nav-modulo">
                <a class="btn-disc-nav btn-disc-nav--back" href="<?= htmlspecialchars($urlVolverModuloCap, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al módulo
                </a>
                <a class="btn-disc-nav btn-disc-nav--back" href="<?= htmlspecialchars($urlVolverNivelCap, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i> Módulos
                </a>
                <?php if ($urlTareasModuloCap !== ''): ?>
                    <a class="btn-disc-nav btn-disc-nav--back" href="<?= htmlspecialchars($urlTareasModuloCap, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-journal-text" aria-hidden="true"></i> Tareas
                    </a>
                <?php endif; ?>
                <a class="btn-disc-nav btn-disc-nav--home" href="<?= htmlspecialchars($urlInicioDiscipulo, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-house-door" aria-hidden="true"></i> Inicio
                </a>
            </div>
        <?php elseif ($esVistaDiscipuloSimplificada): ?>
            <div class="disc-nav-actions">
                <a class="btn-disc-nav btn-disc-nav--back" href="<?= htmlspecialchars($urlAtrasDiscipulo, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Atrás
                </a>
                <a class="btn-disc-nav btn-disc-nav--home" href="<?= htmlspecialchars($urlInicioDiscipulo, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-house-door" aria-hidden="true"></i> Inicio
                </a>
            </div>
        <?php elseif ($mostrarNavCapDestino): ?>
            <div class="disc-nav-actions" data-tour="maestro-nav-eval">
                <?php if ($vistaGestionHistorico): ?>
                    <a class="btn-disc-nav btn-disc-nav--back" href="<?= htmlspecialchars($urlEvaluacionesPrincipal, ENT_QUOTES, 'UTF-8') ?>">
                        <i class="bi bi-journal-check" aria-hidden="true"></i> Volver a evaluaciones
                    </a>
                <?php elseif ($vistaGestionPrincipal): ?>
                    <a class="btn-disc-nav btn-disc-nav--back" href="<?= htmlspecialchars($urlHistoricoPresentaciones, ENT_QUOTES, 'UTF-8') ?>" data-tour="maestro-historial-eval">
                        <i class="bi bi-clock-history" aria-hidden="true"></i> Historial presentadas
                    </a>
                <?php endif; ?>
                <a class="btn-disc-nav btn-disc-nav--back" href="<?= htmlspecialchars($urlVolverModuloCap, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al módulo
                </a>
                <a class="btn-disc-nav btn-disc-nav--back" href="<?= htmlspecialchars($urlVolverNivelCap, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i> Módulos
                </a>
                <a class="btn-disc-nav btn-disc-nav--home" href="<?= htmlspecialchars($urlInicioCap, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-house-door" aria-hidden="true"></i> Inicio
                </a>
            </div>
        <?php else: ?>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a class="btn btn-secondary btn-sm" href="<?= PUBLIC_URL ?>?url=programas">
                    <i class="bi bi-arrow-left-short"></i> Volver a Programas
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($vistaGestionHistorico && $filtroNivelContexto > 0 && $filtroModuloContexto > 0): ?>
    <div class="alert alert-info" style="margin:12px 0;">
        Historial de presentaciones · Nivel <?= (int)$filtroNivelContexto ?> · Módulo <?= (int)$filtroModuloContexto ?>
    </div>
<?php elseif ($vistaGestionPrincipal && $filtroNivelContexto > 0 && $filtroModuloContexto > 0): ?>
    <div class="alert alert-info" style="margin:12px 0;">
        Nivel <?= (int)$filtroNivelContexto ?> · Módulo <?= (int)$filtroModuloContexto ?> — crea evaluaciones y gestiona las de este módulo.
    </div>
<?php elseif ($filtroNivelContexto > 0 && $filtroModuloContexto > 0 && !$esVistaDiscipuloSimplificada): ?>
    <div class="alert alert-info" style="margin:12px 0;">
        Contexto carpeta activo: Nivel <?= $filtroNivelContexto ?> / Modulo <?= $filtroModuloContexto ?> / Lección <?= htmlspecialchars($filtroLeccionContexto) ?>.
        Las evaluaciones mostradas y nuevas se manejan en este modulo.
    </div>
<?php endif; ?>

<?php if ($mensajeFlash !== ''): ?>
    <div class="alert alert-<?= $tipoFlash === 'success' ? 'success' : 'danger' ?>" style="margin:12px 0;">
        <?= htmlspecialchars($mensajeFlash) ?>
    </div>
<?php endif; ?>

<?php if ($presentacionOk): ?>
<script>
(function() {
    if (window.opener && !window.opener.closed) {
        try { window.opener.location.reload(); } catch (e) {}
        window.close();
    }
})();
</script>
<?php endif; ?>

<?php if ($avisoAccesoDiscipulo !== ''): ?>
    <div class="alert alert-warning" style="margin:12px 0;">
        <?= htmlspecialchars($avisoAccesoDiscipulo) ?>
    </div>
<?php endif; ?>

<?php if ($esDiscipuloRol && !$esVistaDiscipuloSimplificada): ?>
    <div class="alert alert-warning" style="margin:12px 0;">
        Al pulsar <strong>Responder</strong> entrarás a la evaluación en esta misma página. El tiempo de 20 minutos empieza al entrar; si no estás listo, no la abras todavía.
    </div>
<?php endif; ?>

<?php if (!empty($resultadoDetalle) && ($vistaHistorico || !$puedeGestionarEval)): ?>
    <?php
    $detalleRespuestas = json_decode((string)($resultadoDetalle['Respuestas_JSON'] ?? '[]'), true);
    if (!is_array($detalleRespuestas)) {
        $detalleRespuestas = [];
    }
    ?>
    <div class="card report-card" style="padding:14px;margin-bottom:14px;border:1px solid #dbeafe;background:#f8fbff;">
        <h3 style="margin:0 0 8px 0;">Detalle del intento</h3>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <small><strong>Evaluación:</strong> <?= htmlspecialchars((string)($resultadoDetalle['Titulo'] ?? '')) ?></small>
            <small><strong>Nivel:</strong> <?= (int)($resultadoDetalle['Nivel'] ?? 0) ?></small>
            <small><strong>Módulo:</strong> <?= (int)($resultadoDetalle['Modulo_Numero'] ?? 0) ?></small>
            <small><strong>Intento:</strong> <?= (int)($resultadoDetalle['Intento_Numero'] ?? 0) ?></small>
            <small><strong>Puntaje:</strong> <?= (float)($resultadoDetalle['Puntaje'] ?? 0) ?>%</small>
            <small><strong>Resultado:</strong> <?= !empty($resultadoDetalle['Aprobado']) ? 'Aprobado' : 'Reprobado' ?></small>
        </div>

        <?php if (!empty($detalleRespuestas)): ?>
            <div style="margin-top:10px;display:flex;flex-direction:column;gap:8px;">
                <?php foreach ($detalleRespuestas as $idxDetalle => $respuestaDetalle): ?>
                    <?php
                    $esCorrectaDetalle = !empty($respuestaDetalle['es_correcta']);
                    $respondidaDetalle = !empty($respuestaDetalle['respondida']);
                    $textoRespuestaDetalle = trim((string)($respuestaDetalle['texto_respuesta'] ?? ''));
                    $claveRespuestaDetalle = trim((string)($respuestaDetalle['respuesta'] ?? ''));
                    $claveCorrectaDetalle = trim((string)($respuestaDetalle['correcta_esperada'] ?? ''));
                    ?>
                    <div style="border:1px solid #e6e8ee;border-radius:10px;padding:10px;background:#fff;">
                        <div><strong><?= ($idxDetalle + 1) ?>. <?= htmlspecialchars((string)($respuestaDetalle['pregunta'] ?? 'Pregunta')) ?></strong></div>
                        <div style="margin-top:4px;"><small><strong>Tu respuesta:</strong>
                            <?php if ($respondidaDetalle): ?>
                                <?= htmlspecialchars(($claveRespuestaDetalle !== '' ? strtoupper($claveRespuestaDetalle) . '. ' : '') . $textoRespuestaDetalle) ?>
                            <?php else: ?>
                                Sin responder
                            <?php endif; ?>
                        </small></div>
                        <div><small><strong>Respuesta correcta:</strong> <?= htmlspecialchars($claveCorrectaDetalle !== '' ? strtoupper($claveCorrectaDetalle) : 'No definida') ?></small></div>
                        <div>
                            <small style="font-weight:700;color:<?= $esCorrectaDetalle ? '#166534' : '#b91c1c' ?>;">
                                <?= $esCorrectaDetalle ? 'Correcta' : 'Incorrecta' ?>
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="margin-top:10px;"><small style="color:#637087;">Este intento no tiene respuestas registradas.</small></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($vistaGestionPrincipal && $totalEvaluacionesFueraVigencia > 0): ?>
    <div class="alert alert-secondary" style="margin:12px 0;">
        <?= (int)$totalEvaluacionesFueraVigencia ?> evaluación(es) de este módulo no se muestran aquí porque ya vencieron o aún no inician.
        Consúltalas en <a href="<?= htmlspecialchars($urlHistoricoPresentaciones, ENT_QUOTES, 'UTF-8') ?>"><strong>Historial presentadas</strong></a>.
    </div>
<?php endif; ?>

<?php if ($vistaGestionPrincipal && !empty($evaluacionesOcultasSinFechas)): ?>
    <div class="alert alert-danger" style="margin:12px 0;">
        <div><strong>Evaluaciones sin fechas completas:</strong> no aparecen en el listado hasta definir inicio y fin.</div>
        <ul style="margin:8px 0 0 18px;padding:0;">
            <?php foreach ($evaluacionesOcultasSinFechas as $evaluacionOculta): ?>
                <li>
                    <?= htmlspecialchars((string)($evaluacionOculta['Titulo'] ?? 'Evaluación')) ?>
                    (Nivel <?= (int)($evaluacionOculta['Nivel'] ?? 0) ?>,
                    Módulo <?= (int)($evaluacionOculta['Modulo_Numero'] ?? 0) ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!$vistaHistorico && $mostrarSelectorModulosDiscipulo && !empty($accesosDirectosDiscipulo)): ?>
<div class="card report-card" style="padding:14px; margin-bottom:14px;">
    <h3 style="margin:0 0 4px 0;">Selecciona un módulo</h3>
    <small style="color:#637087;">Elige primero el módulo activo de hoy en Capacitación Destino.</small>
    <div style="margin-top:10px;">
        <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($urlMaterialCapDestino, ENT_QUOTES, 'UTF-8') ?>">Ir a Cap. Destino</a>
    </div>
</div>
<?php elseif (!$vistaHistorico && !$esDiscipuloRol && !$puedeGestionarEval && !empty($clasesLinks)): ?>
<div class="card report-card" style="padding:14px; margin-bottom:14px;">
    <h3 style="margin:0 0 8px 0;">Mis clases</h3>
    <small style="color:#637087;">Accesos directos de clases para tus niveles inscritos.</small>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
        <?php foreach ($clasesLinks as $claseLink): ?>
            <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars((string)($claseLink['url'] ?? '#')) ?>">
                <?= htmlspecialchars((string)($claseLink['label'] ?? 'Conectarme a clase')) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php elseif (!$vistaHistorico && $esDiscipuloRol && $mostrarSelectorModulosDiscipulo && $avisoAccesoDiscipulo === '' && empty($accesosDirectosDiscipulo)): ?>
    <div class="card report-card" style="padding:14px; margin-bottom:14px;">
        <small style="color:#637087;">No hay módulos disponibles en este momento. Si crees que es un error, contacta a tu líder.</small>
    </div>
<?php endif; ?>

<?php if ($vistaGestionPrincipal): ?>
<div class="card report-card" style="padding:0; margin-bottom:20px; overflow:hidden; box-shadow:0 2px 8px rgba(31,79,147,0.1);" data-tour="maestro-crear-eval-form">
    <!-- Header -->
    <div style="background:linear-gradient(135deg, #1f4f93 0%, #2d5fa3 100%); padding:18px 20px; color:white;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h2 style="margin:0;font-size:22px;font-weight:600;"><?= $modoEdicionEval ? 'Editar evaluación' : 'Crear evaluación' ?></h2>
                <small style="opacity:0.9;"><?= $modoEdicionEval ? 'Modifica los detalles y las preguntas' : 'Completa los detalles y agrega preguntas' ?> · Se guarda automáticamente y también en borrador local si sales del sistema.</small>
            </div>
            <div id="estadoGuardado" style="font-size:13px;color:#10b981;font-weight:bold;display:none;background:rgba(255,255,255,0.15);padding:8px 12px;border-radius:6px;">
                <span id="textoEstado">✓ Guardado automático</span>
            </div>
        </div>
    </div>

    <div id="bannerBorradorLocal" style="display:none;margin:0 20px 12px;padding:12px 14px;background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;">
        <span style="font-size:13px;color:#92400e;">Tienes un borrador guardado en este navegador (por si saliste sin terminar).</span>
        <div style="display:flex;gap:8px;">
            <button type="button" class="btn btn-primary btn-sm" id="btnRestaurarBorrador">Restaurar borrador</button>
            <button type="button" class="btn btn-secondary btn-sm" id="btnDescartarBorrador">Descartar</button>
        </div>
    </div>

    <form id="formCrearEvaluacion" method="POST" action="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>" style="padding:20px;">
        <input type="hidden" name="accion" value="crear_evaluacion">
        <input type="hidden" name="id_evaluacion" id="id_evaluacion_borrador" value="<?= $idEdicionFormulario > 0 ? $idEdicionFormulario : (int)($formularioRepost['id_evaluacion'] ?? 0) ?>">
        <?php if ($modoEdicionEval): ?>
        <input type="hidden" name="modo_edicion" value="1">
        <input type="hidden" name="id_evaluacion_edicion" id="id_evaluacion_edicion" value="<?= $idEdicionFormulario ?>">
        <?php endif; ?>
        <?= $contextoHiddenHtml ?>
        
        <!-- Sección: Información básica -->
        <div style="margin-bottom:24px;">
            <h4 style="color:#1f4f93;font-size:14px;font-weight:600;margin:0 0 14px 0;padding-bottom:8px;border-bottom:2px solid #e5ebf7;">Información básica</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div>
                    <label style="font-weight:600;color:#1f4f93;font-size:13px;display:block;margin-bottom:6px;">Título de evaluación *</label>
                    <input type="text" name="titulo" class="form-control" placeholder="Ej: Evaluación Nivel 1 Módulo 1" required style="border:1px solid #d1d5db;border-radius:6px;padding:10px 12px;">
                </div>
                <div>
                    <label style="font-weight:600;color:#1f4f93;font-size:13px;display:block;margin-bottom:6px;">Descripción</label>
                    <input type="text" name="descripcion" class="form-control" placeholder="Descripción breve" style="border:1px solid #d1d5db;border-radius:6px;padding:10px 12px;">
                </div>
            </div>
        </div>

        <!-- Sección: Clasificación -->
        <div style="margin-bottom:24px;">
            <h4 style="color:#1f4f93;font-size:14px;font-weight:600;margin:0 0 14px 0;padding-bottom:8px;border-bottom:2px solid #e5ebf7;">Clasificación</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;">
                <div>
                    <label style="font-weight:600;color:#1f4f93;font-size:13px;display:block;margin-bottom:6px;">Nivel *</label>
                    <?php if ($bloquearClasificacionEval): ?>
                        <input type="hidden" name="nivel" value="<?= (int)$filtroNivelContexto ?>">
                        <input type="text" class="form-control" value="<?= (int)$filtroNivelContexto ?>" readonly style="border:1px solid #d1d5db;border-radius:6px;padding:10px 12px;background:#f8fafc;">
                    <?php else: ?>
                        <input type="number" name="nivel" class="form-control" min="1" max="10" placeholder="1" required style="border:1px solid #d1d5db;border-radius:6px;padding:10px 12px;">
                    <?php endif; ?>
                </div>
                <div>
                    <label style="font-weight:600;color:#1f4f93;font-size:13px;display:block;margin-bottom:6px;">Módulo *</label>
                    <?php if ($bloquearClasificacionEval): ?>
                        <input type="hidden" name="modulo_numero" value="<?= (int)$filtroModuloContexto ?>">
                        <input type="text" class="form-control" value="<?= (int)$filtroModuloContexto ?>" readonly style="border:1px solid #d1d5db;border-radius:6px;padding:10px 12px;background:#f8fafc;">
                    <?php else: ?>
                        <input type="number" name="modulo_numero" class="form-control" min="1" max="10" placeholder="1" required style="border:1px solid #d1d5db;border-radius:6px;padding:10px 12px;">
                    <?php endif; ?>
                </div>
                <div>
                    <label style="font-weight:600;color:#1f4f93;font-size:13px;display:block;margin-bottom:6px;">Lección</label>
                    <select name="leccion" class="form-control" style="border:1px solid #d1d5db;border-radius:6px;padding:10px 12px;">
                        <option value="">Sin lección</option>
                        <?php foreach ($leccionesIniciales as $leccionOpt): ?>
                            <option value="<?= htmlspecialchars($leccionOpt) ?>"><?= htmlspecialchars($leccionOpt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-weight:600;color:#1f4f93;font-size:13px;display:block;margin-bottom:6px;">Puntaje mínimo %</label>
                    <input type="number" name="puntaje_minimo" class="form-control" min="0" max="100" value="80" style="border:1px solid #d1d5db;border-radius:6px;padding:10px 12px;">
                </div>
            </div>
        </div>

        <?php if ($puedeConfigurarFechasEval): ?>
        <!-- Sección: Fechas de habilitación -->
        <div style="margin-bottom:24px;" data-tour="maestro-eval-fechas">
            <h4 style="color:#1f4f93;font-size:14px;font-weight:600;margin:0 0 14px 0;padding-bottom:8px;border-bottom:2px solid #e5ebf7;">Disponibilidad (opcional)</h4>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;background:#f8fbff;padding:14px;border-radius:8px;border:1px solid #e5ebf7;">
                <div>
                    <label style="font-weight:600;color:#1f4f93;font-size:13px;display:block;margin-bottom:6px;">Fecha de inicio</label>
                    <input type="date" name="fecha_habilitacion_inicio" class="form-control" style="border:1px solid #d1d5db;border-radius:6px;padding:10px 12px;">
                </div>
                <div>
                    <label style="font-weight:600;color:#1f4f93;font-size:13px;display:block;margin-bottom:6px;">Fecha de fin</label>
                    <input type="date" name="fecha_habilitacion_fin" class="form-control" style="border:1px solid #d1d5db;border-radius:6px;padding:10px 12px;">
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sección: Preguntas -->
        <div style="margin-bottom:24px;" data-tour="maestro-eval-preguntas">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid #e5ebf7;">
                <h4 style="color:#1f4f93;font-size:14px;font-weight:600;margin:0;">Preguntas de opción múltiple</h4>
                <span id="contadorPreguntasUI" style="background:#1f4f93;color:white;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;">0 preguntas</span>
            </div>
            <div id="contenedorPreguntas" style="display:flex;flex-direction:column;gap:14px;"></div>
            <input type="hidden" name="preguntas" id="preguntas_json_hidden" value="">
            <button type="button" class="btn btn-secondary" onclick="agregarPregunta()" style="margin-top:14px;padding:10px 16px;border-radius:6px;border:1px dashed #d1d5db;background:white;color:#1f4f93;font-weight:600;">
                <i class="bi bi-plus-circle"></i> Agregar pregunta
            </button>
        </div>

        <!-- Sección: Acciones -->
        <div style="display:flex;gap:10px;padding-top:14px;border-top:1px solid #e5ebf7;" data-tour="maestro-eval-guardar">
            <button type="submit" class="btn btn-primary" style="padding:11px 20px;border-radius:6px;font-weight:600;background:#1f4f93;border:none;">
                <i class="bi bi-save"></i> Guardar evaluación
            </button>
            <button type="button" class="btn btn-secondary" onclick="window.location='<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>';" style="padding:11px 20px;border-radius:6px;font-weight:600;background:white;color:#1f4f93;border:1px solid #d1d5db;">
                Cancelar
            </button>
        </div>
    </form>

    <script>
    (function() {
        const formEl = document.getElementById('formCrearEvaluacion');
        const contenedorEl = document.getElementById('contenedorPreguntas');
        const estadoEl = document.getElementById('estadoGuardado');
        const textoEstadoEl = document.getElementById('textoEstado');
        const contadorUI = document.getElementById('contadorPreguntasUI');
        let timerAutoSave = null;
        let timerBorradorLocal = null;
        let guardandoAhora = false;
        let enviandoFormulario = false;
        let contadorPreguntas = 0;
        const inputIdEvaluacion = document.getElementById('id_evaluacion_borrador');
        const inputIdEdicion = document.getElementById('id_evaluacion_edicion');
        const bannerBorrador = document.getElementById('bannerBorradorLocal');
        const btnRestaurarBorrador = document.getElementById('btnRestaurarBorrador');
        const btnDescartarBorrador = document.getElementById('btnDescartarBorrador');
        const borradorCfg = {
            usuarioId: <?= (int)($_SESSION['usuario_id'] ?? 0) ?>,
            contexto: 'n<?= (int)$filtroNivelContexto ?>_m<?= (int)$filtroModuloContexto ?>',
            modoEdicion: <?= $modoEdicionEval ? 'true' : 'false' ?>,
            idEdicion: <?= $idEdicionFormulario ?>
        };

        function claveBorradorLocal() {
            const idActual = parseInt(inputIdEvaluacion?.value || borradorCfg.idEdicion || '0', 10);
            const sufijo = borradorCfg.modoEdicion && idActual > 0
                ? ('edit_' + idActual)
                : ('nuevo_' + borradorCfg.contexto);
            return 'mcimadrid_eval_cap_' + borradorCfg.usuarioId + '_' + sufijo;
        }

        function serializarFormularioBorrador() {
            const leccionField = formEl.querySelector('[name="leccion"]');
            return {
                id_evaluacion: parseInt(inputIdEvaluacion?.value || '0', 10) || 0,
                titulo: (formEl.querySelector('[name="titulo"]')?.value || '').trim(),
                descripcion: (formEl.querySelector('[name="descripcion"]')?.value || '').trim(),
                nivel: formEl.querySelector('[name="nivel"]')?.value || '',
                modulo_numero: formEl.querySelector('[name="modulo_numero"]')?.value || '',
                leccion: leccionField ? leccionField.value : '',
                puntaje_minimo: formEl.querySelector('[name="puntaje_minimo"]')?.value || '',
                fecha_habilitacion_inicio: formEl.querySelector('[name="fecha_habilitacion_inicio"]')?.value || '',
                fecha_habilitacion_fin: formEl.querySelector('[name="fecha_habilitacion_fin"]')?.value || '',
                preguntas: obtenerDatos(),
                updated_at: Date.now()
            };
        }

        function setBannerBorradorVisible(visible) {
            if (!bannerBorrador) {
                return;
            }
            bannerBorrador.style.display = visible ? 'flex' : 'none';
        }

        function borradorTieneContenidoUtil(data) {
            if (!data || typeof data !== 'object') {
                return false;
            }
            if ((data.titulo || '').trim() !== '' || (data.descripcion || '').trim() !== '') {
                return true;
            }
            const preguntas = Array.isArray(data.preguntas) ? data.preguntas : [];
            return preguntas.some(function(pregunta) {
                if (!pregunta || typeof pregunta !== 'object') {
                    return false;
                }
                const enunciado = (pregunta.enunciado || '').trim();
                const opciones = normalizarOpcionesPregunta(pregunta);
                const tieneOpcion = Object.values(opciones).some(function(v) { return v !== ''; });
                const correcta = (pregunta.respuesta_correcta || '').trim();
                return enunciado !== '' || tieneOpcion || correcta !== '';
            });
        }

        function normalizarPreguntasParaComparar(preguntas) {
            const lista = Array.isArray(preguntas) ? preguntas : [];
            return lista.map(function(pregunta) {
                const opciones = normalizarOpcionesPregunta(pregunta || {});
                return {
                    enunciado: (pregunta?.enunciado || '').trim(),
                    opciones: opciones,
                    respuesta_correcta: (pregunta?.respuesta_correcta || '').toString().trim().toUpperCase()
                };
            }).filter(function(p) {
                const tieneOpcion = Object.values(p.opciones).some(function(v) { return v !== ''; });
                return p.enunciado !== '' || tieneOpcion || p.respuesta_correcta !== '';
            });
        }

        function borradorDifiereDelFormulario(data) {
            if (!data || !formEl) {
                return false;
            }
            const actual = serializarFormularioBorrador();
            const campos = ['titulo', 'descripcion', 'nivel', 'modulo_numero', 'leccion', 'puntaje_minimo', 'fecha_habilitacion_inicio', 'fecha_habilitacion_fin'];
            for (let i = 0; i < campos.length; i++) {
                const nombre = campos[i];
                if (String(actual[nombre] ?? '').trim() !== String(data[nombre] ?? '').trim()) {
                    return true;
                }
            }
            const fpActual = JSON.stringify(normalizarPreguntasParaComparar(actual.preguntas));
            const fpBorrador = JSON.stringify(normalizarPreguntasParaComparar(data.preguntas));
            return fpActual !== fpBorrador;
        }

        function guardarBorradorLocal() {
            try {
                const data = serializarFormularioBorrador();
                const key = claveBorradorLocal();
                if (!borradorTieneContenidoUtil(data)) {
                    localStorage.removeItem(key);
                    setBannerBorradorVisible(false);
                    return;
                }
                localStorage.setItem(key, JSON.stringify(data));
                evaluarBannerBorrador();
            } catch (e) {
                console.warn('No se pudo guardar borrador local', e);
            }
        }

        function programarBorradorLocal(delay) {
            clearTimeout(timerBorradorLocal);
            timerBorradorLocal = setTimeout(function() {
                sincronizarCampoPreguntasJson();
                guardarBorradorLocal();
            }, delay || 600);
        }

        function leerBorradorLocal() {
            try {
                const raw = localStorage.getItem(claveBorradorLocal());
                if (!raw) {
                    return null;
                }
                const data = JSON.parse(raw);
                return data && typeof data === 'object' ? data : null;
            } catch (e) {
                return null;
            }
        }

        function descartarBorradorLocal() {
            try {
                localStorage.removeItem(claveBorradorLocal());
            } catch (e) {}
            setBannerBorradorVisible(false);
        }

        function restaurarBorradorDesdeObjeto(data, conservarBorradorLocal) {
            if (!data || !formEl) {
                return;
            }
            const mantenerLocal = conservarBorradorLocal === true;
            if (inputIdEvaluacion && parseInt(data.id_evaluacion || '0', 10) > 0) {
                inputIdEvaluacion.value = String(data.id_evaluacion);
            }
            const setVal = function(name, value) {
                const field = formEl.querySelector('[name="' + name + '"]');
                if (field) {
                    field.value = value ?? '';
                }
            };
            setVal('titulo', data.titulo || '');
            setVal('descripcion', data.descripcion || '');
            setVal('nivel', data.nivel || '');
            setVal('modulo_numero', data.modulo_numero || '');
            setVal('puntaje_minimo', data.puntaje_minimo || 80);
            setVal('fecha_habilitacion_inicio', data.fecha_habilitacion_inicio || '');
            setVal('fecha_habilitacion_fin', data.fecha_habilitacion_fin || '');
            const leccionField = formEl.querySelector('[name="leccion"]');
            if (leccionField) {
                leccionField.value = data.leccion || '';
            }
            const preguntas = Array.isArray(data.preguntas) ? data.preguntas : [];
            contenedorEl.innerHTML = '';
            contadorPreguntas = 0;
            if (preguntas.length === 0) {
                agregarPregunta();
            } else {
                preguntas.forEach(function(pregunta) {
                    agregarPregunta();
                    const bloques = contenedorEl.querySelectorAll('[data-pregunta-id]');
                    const ultimo = bloques[bloques.length - 1];
                    if (ultimo) {
                        cargarPreguntaEnBloque(ultimo, pregunta || {});
                    }
                });
            }
            actualizarContador();
            sincronizarCampoPreguntasJson();
            if (!mantenerLocal) {
                descartarBorradorLocal();
            }
        }

        function evaluarBannerBorrador() {
            if (!bannerBorrador) {
                return;
            }
            const data = leerBorradorLocal();
            if (!data || !borradorTieneContenidoUtil(data)) {
                setBannerBorradorVisible(false);
                return;
            }
            const idBorrador = parseInt(data.id_evaluacion || '0', 10);
            const idActual = parseInt(inputIdEvaluacion?.value || '0', 10);
            if (borradorCfg.modoEdicion) {
                const idEsperado = borradorCfg.idEdicion || idActual;
                if (idBorrador > 0 && idBorrador !== idEsperado) {
                    setBannerBorradorVisible(false);
                    return;
                }
            } else if (idActual > 0 && idBorrador > 0 && idActual !== idBorrador) {
                setBannerBorradorVisible(false);
                return;
            }
            if (!borradorDifiereDelFormulario(data)) {
                setBannerBorradorVisible(false);
                return;
            }
            setBannerBorradorVisible(true);
        }

        function puedeAutoguardar() {
            const titulo = (formEl.querySelector('[name="titulo"]')?.value || '').trim();
            const nivel = parseInt(formEl.querySelector('[name="nivel"]')?.value || '0', 10);
            const modulo = parseInt(formEl.querySelector('[name="modulo_numero"]')?.value || '0', 10);
            return titulo !== '' && nivel > 0 && modulo > 0;
        }

        function aplicarIdEvaluacionDesdeRespuesta(respText) {
            const match = String(respText || '').match(/\|id:(\d+)/);
            if (!match || !inputIdEvaluacion) {
                return;
            }
            inputIdEvaluacion.value = match[1];
        }

        function actualizarContador() {
            const cantidad = document.querySelectorAll('[data-pregunta-id]').length;
            contadorUI.textContent = cantidad + ' ' + (cantidad === 1 ? 'pregunta' : 'preguntas');
        }

        const inputPreguntasJson = document.getElementById('preguntas_json_hidden');

        function normalizarOpcionesPregunta(pregunta) {
            const out = { a: '', b: '', c: '', d: '' };
            if (!pregunta || typeof pregunta !== 'object') {
                return out;
            }
            ['a', 'b', 'c', 'd'].forEach(function(letra) {
                const directa = pregunta['opcion_' + letra] ?? pregunta['opcion_' + letra.toUpperCase()];
                if (directa != null && String(directa).trim() !== '') {
                    out[letra] = String(directa).trim();
                }
            });
            const raw = pregunta.opciones;
            if (Array.isArray(raw)) {
                raw.forEach(function(item, idx) {
                    if (!item || typeof item !== 'object') {
                        return;
                    }
                    let letra = String(item.clave || '').toLowerCase();
                    if (['a', 'b', 'c', 'd'].indexOf(letra) < 0) {
                        letra = ['a', 'b', 'c', 'd'][idx] || '';
                    }
                    if (letra) {
                        out[letra] = String(item.opcion || item.texto || '').trim();
                    }
                });
            } else if (raw && typeof raw === 'object') {
                ['a', 'b', 'c', 'd'].forEach(function(letra) {
                    const valor = raw[letra] ?? raw[letra.toUpperCase()];
                    if (valor != null && String(valor).trim() !== '') {
                        out[letra] = String(valor).trim();
                    }
                });
            }
            return out;
        }

        function sincronizarCampoPreguntasJson() {
            if (!inputPreguntasJson) {
                return;
            }
            inputPreguntasJson.value = JSON.stringify(obtenerDatos());
        }

        function obtenerDatos() {
            const preguntas = [];
            document.querySelectorAll('[data-pregunta-id]').forEach(pregEl => {
                const enunciado = pregEl.querySelector('[name="pregunta_enunciado[]"]')?.value || '';
                const tipoInput = pregEl.querySelector('[name="pregunta_tipo[]"]')?.value || 'cerrada';
                const opcionesInputs = pregEl.querySelectorAll('[name="pregunta_opciones[]"]');
                const correctaInput = pregEl.querySelector('[name="pregunta_correcta[]"]');
                const opciones = { a: '', b: '', c: '', d: '' };
                opcionesInputs.forEach(function(optEl, idx) {
                    const letra = ['a', 'b', 'c', 'd'][idx];
                    if (letra) {
                        opciones[letra] = (optEl.value || '').trim();
                    }
                });
                const tieneContenido = enunciado.trim() !== ''
                    || Object.values(opciones).some(function(v) { return v !== ''; })
                    || (correctaInput?.value || '').trim() !== '';
                if (!tieneContenido) {
                    return;
                }
                preguntas.push({
                    tipo: tipoInput,
                    enunciado: enunciado,
                    opciones: opciones,
                    respuesta_correcta: (correctaInput?.value || '').trim().toUpperCase(),
                    descripcion_extra: pregEl.querySelector('[name="pregunta_descripcion[]"]')?.value || ''
                });
            });
            return preguntas;
        }

        function guardarAutomaticamente() {
            if (guardandoAhora || enviandoFormulario || !puedeAutoguardar()) {
                return;
            }

            guardandoAhora = true;
            const formData = new FormData(formEl);
            formData.set('preguntas', JSON.stringify(obtenerDatos()));
            formData.set('auto_save', '1');
            if (inputIdEvaluacion && parseInt(inputIdEvaluacion.value || '0', 10) > 0) {
                formData.set('id_evaluacion', inputIdEvaluacion.value);
            }

            mostrarEstado('Guardando...', '#f59e0b');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(resp => resp.text())
            .then(respText => {
                guardandoAhora = false;
                if (respText.includes('error:')) {
                    mostrarEstado('✗ Error al guardar', '#ef4444');
                    setTimeout(() => ocultarEstado(), 3000);
                } else {
                    aplicarIdEvaluacionDesdeRespuesta(respText);
                    guardarBorradorLocal();
                    mostrarEstado('✓ Guardado automático', '#10b981');
                    setTimeout(() => ocultarEstado(), 2000);
                }
            })
            .catch(err => {
                guardandoAhora = false;
                console.error(err);
                mostrarEstado('✗ Error de conexión', '#ef4444');
                setTimeout(() => ocultarEstado(), 3000);
            });
        }

        function mostrarEstado(texto, color) {
            textoEstadoEl.textContent = texto;
            estadoEl.style.color = color;
            estadoEl.style.display = 'block';
        }

        function ocultarEstado() {
            estadoEl.style.display = 'none';
        }

        window.agregarPregunta = function() {
            contadorPreguntas++;
            const preguntaDiv = document.createElement('div');
            preguntaDiv.setAttribute('data-pregunta-id', contadorPreguntas);
            preguntaDiv.style.cssText = 'border:1px solid #e5ebf7;border-radius:8px;padding:16px;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,0.05);';
            preguntaDiv.innerHTML = `
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid #e5ebf7;">
                    <strong style="color:#1f4f93;font-size:15px;">Pregunta ${contadorPreguntas}</strong>
                    <button type="button" onclick="this.closest('[data-pregunta-id]').remove(); document.getElementById('contadorPreguntasUI').textContent = document.querySelectorAll('[data-pregunta-id]').length + ' preguntas';" class="btn btn-sm" style="background:#fee2e2;color:#dc2626;border:none;border-radius:4px;padding:4px 8px;cursor:pointer;font-weight:600;font-size:12px;">✕ Eliminar</button>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="font-size:13px;color:#1f4f93;font-weight:600;display:block;margin-bottom:6px;">Enunciado *</label>
                    <textarea name="pregunta_enunciado[]" class="form-control" placeholder="Escribe la pregunta claramente..." style="border:1px solid #d1d5db;border-radius:6px;padding:10px;font-size:14px;"></textarea>
                </div>
                <div style="margin-bottom:12px;">
                    <label style="font-size:13px;color:#1f4f93;font-weight:600;display:block;margin-bottom:6px;">Opciones de respuesta</label>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div>
                            <small style="color:#637087;display:block;margin-bottom:4px;font-weight:500;">Opción A</small>
                            <input type="text" name="pregunta_opciones[]" class="form-control" placeholder="Primera opción" style="border:1px solid #d1d5db;border-radius:6px;padding:9px;font-size:13px;">
                        </div>
                        <div>
                            <small style="color:#637087;display:block;margin-bottom:4px;font-weight:500;">Opción B</small>
                            <input type="text" name="pregunta_opciones[]" class="form-control" placeholder="Segunda opción" style="border:1px solid #d1d5db;border-radius:6px;padding:9px;font-size:13px;">
                        </div>
                        <div>
                            <small style="color:#637087;display:block;margin-bottom:4px;font-weight:500;">Opción C</small>
                            <input type="text" name="pregunta_opciones[]" class="form-control" placeholder="Tercera opción" style="border:1px solid #d1d5db;border-radius:6px;padding:9px;font-size:13px;">
                        </div>
                        <div>
                            <small style="color:#637087;display:block;margin-bottom:4px;font-weight:500;">Opción D</small>
                            <input type="text" name="pregunta_opciones[]" class="form-control" placeholder="Cuarta opción" style="border:1px solid #d1d5db;border-radius:6px;padding:9px;font-size:13px;">
                        </div>
                    </div>
                </div>
                <div>
                    <label style="font-size:13px;color:#1f4f93;font-weight:600;display:block;margin-bottom:6px;">Respuesta correcta (A, B, C ó D) *</label>
                    <input type="text" name="pregunta_correcta[]" class="form-control" placeholder="A" maxlength="1" style="border:1px solid #d1d5db;border-radius:6px;padding:9px;font-size:14px;width:70px;text-transform:uppercase;">
                </div>
            `;
            contenedorEl.appendChild(preguntaDiv);
            actualizarContador();

            // Agregar listeners para auto-save y borrador local
            preguntaDiv.querySelectorAll('input, textarea').forEach(inputEl => {
                const onCampoChange = () => {
                    sincronizarCampoPreguntasJson();
                    programarBorradorLocal(500);
                    clearTimeout(timerAutoSave);
                    timerAutoSave = setTimeout(guardarAutomaticamente, 1500);
                };
                inputEl.addEventListener('change', onCampoChange);
                inputEl.addEventListener('input', onCampoChange);
            });
        };

        function enlazarAutoSaveCampo(field) {
            if (!field) {
                return;
            }
            const onCampoChange = () => {
                programarBorradorLocal(500);
                clearTimeout(timerAutoSave);
                timerAutoSave = setTimeout(guardarAutomaticamente, 1500);
            };
            field.addEventListener('change', onCampoChange);
            field.addEventListener('input', onCampoChange);
        }

        // Listeners para auto-save en campos principales
        ['titulo', 'descripcion', 'nivel', 'modulo_numero', 'leccion', 'puntaje_minimo', 'fecha_habilitacion_inicio', 'fecha_habilitacion_fin'].forEach(fieldName => {
            enlazarAutoSaveCampo(formEl.querySelector(`[name="${fieldName}"]`));
        });

        function cargarPreguntaEnBloque(preguntaDiv, pregunta) {
            const enunciado = preguntaDiv.querySelector('[name="pregunta_enunciado[]"]');
            if (enunciado) {
                enunciado.value = pregunta.enunciado || '';
            }
            const opcionesInputs = preguntaDiv.querySelectorAll('[name="pregunta_opciones[]"]');
            const opcionesNorm = normalizarOpcionesPregunta(pregunta);
            ['a', 'b', 'c', 'd'].forEach(function(letra, idx) {
                if (!opcionesInputs[idx]) {
                    return;
                }
                opcionesInputs[idx].value = opcionesNorm[letra] || '';
            });
            const correcta = preguntaDiv.querySelector('[name="pregunta_correcta[]"]');
            if (correcta) {
                correcta.value = (pregunta.respuesta_correcta || '').toString().toUpperCase();
            }
        }

        function cargarEvaluacionEnFormulario(ev) {
            if (!ev || !formEl) {
                return;
            }
            if (inputIdEvaluacion) {
                inputIdEvaluacion.value = String(ev.Id_Evaluacion || '0');
            }
            const setVal = function(name, value) {
                const field = formEl.querySelector('[name="' + name + '"]');
                if (field) {
                    field.value = value ?? '';
                }
            };
            setVal('titulo', ev.Titulo || '');
            setVal('descripcion', ev.Descripcion || '');
            setVal('nivel', ev.Nivel || '');
            setVal('modulo_numero', ev.Modulo_Numero || '');
            setVal('puntaje_minimo', ev.Puntaje_Minimo || 80);
            setVal('fecha_habilitacion_inicio', ev.Fecha_Habilitacion_Inicio || '');
            setVal('fecha_habilitacion_fin', ev.Fecha_Habilitacion_Fin || '');
            const leccionField = formEl.querySelector('[name="leccion"]');
            if (leccionField) {
                leccionField.value = ev.Leccion || '';
            }
            let preguntas = [];
            try {
                preguntas = JSON.parse(ev.Preguntas_JSON || '[]');
            } catch (e) {
                preguntas = [];
            }
            if (!Array.isArray(preguntas)) {
                preguntas = [];
            }
            contenedorEl.innerHTML = '';
            contadorPreguntas = 0;
            if (preguntas.length === 0) {
                agregarPregunta();
            } else {
                preguntas.forEach(function(pregunta) {
                    agregarPregunta();
                    const bloques = contenedorEl.querySelectorAll('[data-pregunta-id]');
                    const ultimo = bloques[bloques.length - 1];
                    if (ultimo) {
                        cargarPreguntaEnBloque(ultimo, pregunta || {});
                    }
                });
            }
            actualizarContador();
            formEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        const evaluacionEdicionInicial = <?= json_encode($evaluacionEdicion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null' ?>;
        const formularioRepostInicial = <?= json_encode($formularioRepost, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null' ?>;
        if (formularioRepostInicial) {
            restaurarBorradorDesdeObjeto(formularioRepostInicial, true);
            if (formEl) {
                formEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            evaluarBannerBorrador();
        } else if (evaluacionEdicionInicial) {
            cargarEvaluacionEnFormulario(evaluacionEdicionInicial);
            evaluarBannerBorrador();
        } else if (contenedorEl.children.length === 0) {
            agregarPregunta();
            evaluarBannerBorrador();
        } else {
            evaluarBannerBorrador();
        }

        if (btnRestaurarBorrador) {
            btnRestaurarBorrador.addEventListener('click', function() {
                const data = leerBorradorLocal();
                if (data) {
                    restaurarBorradorDesdeObjeto(data);
                }
            });
        }
        if (btnDescartarBorrador) {
            btnDescartarBorrador.addEventListener('click', descartarBorradorLocal);
        }

        window.addEventListener('beforeunload', function() {
            guardarBorradorLocal();
        });
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                guardarBorradorLocal();
                if (puedeAutoguardar() && !guardandoAhora && !enviandoFormulario) {
                    guardarAutomaticamente();
                }
            }
        });

        formEl.addEventListener('submit', function(event) {
            if (enviandoFormulario) {
                event.preventDefault();
                return;
            }
            enviandoFormulario = true;

            if (borradorCfg.modoEdicion && inputIdEdicion && inputIdEvaluacion) {
                const idEd = parseInt(inputIdEdicion.value || '0', 10);
                if (idEd > 0) {
                    inputIdEvaluacion.value = String(idEd);
                }
            }

            sincronizarCampoPreguntasJson();
        });

        if (<?= json_encode($tipoFlash === 'success') ?>) {
            descartarBorradorLocal();
        }
    })();
    </script>
</div>
<?php endif; ?>

<?php
$mostrarListadoEvaluacionesDiscipulo = $esDiscipuloRol
    ? $contextoDesdeMaterial
    : true;
?>
<?php if ($mostrarListadoEvaluacionesDiscipulo && !$vistaHistorico): ?>
<?php $ocultarEncabezadoListadoDiscipulo = $discipuloConModuloSeleccionado && $esVistaDiscipuloSimplificada; ?>
<?php if (!$ocultarEncabezadoListadoDiscipulo): ?>
<div class="card report-card" style="padding:14px;margin-bottom:14px;">
    <h3 style="margin:0 0 6px 0;color:#1e4a89;">
        <?php if ($puedeGestionarEval && $filtroNivelContexto > 0 && $filtroModuloContexto > 0): ?>
            Evaluaciones vigentes del módulo <?= (int)$filtroModuloContexto ?>
        <?php else: ?>
            Evaluaciones disponibles
        <?php endif; ?>
    </h3>
    <small style="color:#637087;">Solo evaluaciones activas hoy según su fecha de inicio y fin.</small>
</div>
<?php endif; ?>
<div class="dashboard-grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr));margin-bottom:14px;" data-tour="<?= $esDiscipuloRol ? 'lista-evaluaciones-discipulo' : 'maestro-lista-eval-vigentes' ?>">
    <?php foreach ($grupos as $grupo): ?>
        <div class="card report-card" style="padding:12px;">
            <?php if (!$ocultarEncabezadoListadoDiscipulo): ?>
            <h3 style="margin:0 0 8px 0;">Nivel <?= (int)$grupo['nivel'] ?> - Módulo <?= (int)$grupo['modulo'] ?></h3>
            <?php endif; ?>
            <?php foreach ($grupo['items'] as $ev): ?>
                <?php $idEv = (int)($ev['Id_Evaluacion'] ?? 0); ?>
                <?php
                $intentosUsados = (int)($intentosPorEvaluacion[$idEv] ?? 0);
                $intentosRestantes = max(0, $maxIntentos - $intentosUsados);
                $intentosAgotados = $esDiscipuloRol && $intentosRestantes <= 0;
                $yaPresentada = $esDiscipuloRol && $intentosUsados > 0;
                $textoAccionResponder = $intentosUsados > 0 ? 'Responder de nuevo' : 'Responder';
                $urlPresentarEval = PUBLIC_URL . '?url=programas/evaluaciones/presentar&evaluacion=' . $idEv;
                if ($contextoQuery !== '') {
                    $urlPresentarEval .= $contextoQuery;
                }
                $urlEditarEval = PUBLIC_URL . '?url=programas/evaluaciones' . $contextoQuery . '&editar=' . $idEv;
                $urlNotasEval = PUBLIC_URL . '?url=programas/evaluaciones' . $contextoQuery . '&historico=1&evaluacion=' . $idEv;

                $preguntasEvalTmp = json_decode((string)($ev['Preguntas_JSON'] ?? '[]'), true);
                if (!is_array($preguntasEvalTmp)) {
                    $preguntasEvalTmp = [];
                }
                $tieneAbiertasTmp = false;
                $tieneCerradasTmp = false;
                foreach ($preguntasEvalTmp as $preguntaTmp) {
                    $tipoTmp = strtolower(trim((string)($preguntaTmp['tipo'] ?? 'cerrada')));
                    if ($tipoTmp === 'abierta') {
                        $tieneAbiertasTmp = true;
                    } else {
                        $tieneCerradasTmp = true;
                    }
                }

                $modoEtiquetaEval = 'Solo cerradas';
                if ($tieneAbiertasTmp && $tieneCerradasTmp) {
                    $modoEtiquetaEval = 'Mixta';
                } elseif ($tieneAbiertasTmp) {
                    $modoEtiquetaEval = 'Solo abiertas';
                }
                ?>
                <div style="border:1px solid #e6e8ee;border-radius:10px;padding:10px;margin-bottom:8px;">
                    <div style="display:flex;justify-content:space-between;gap:8px;align-items:flex-start;">
                        <div>
                            <strong><?= htmlspecialchars((string)($ev['Titulo'] ?? 'Evaluación')) ?></strong>
                            <div><small style="color:#637087;">Mínimo: <?= max(80, (float)($ev['Puntaje_Minimo'] ?? 80)) ?>%</small></div>
                            <div><small style="color:#637087;">Lección: <?= htmlspecialchars((string)($ev['Leccion'] ?? 'Sin lección')) ?></small></div>
                            <div><small style="color:#637087;">Tipo: <?= htmlspecialchars($modoEtiquetaEval) ?></small></div>
                            <?php
                            $fechaIniEv = trim((string)($ev['Fecha_Habilitacion_Inicio'] ?? ''));
                            $fechaFinEv = trim((string)($ev['Fecha_Habilitacion_Fin'] ?? ''));
                            ?>
                            <div><small style="color:#637087;">Ventana: <?= $fechaIniEv !== '' ? htmlspecialchars($fechaIniEv) : 'sin inicio' ?> a <?= $fechaFinEv !== '' ? htmlspecialchars($fechaFinEv) : 'sin fin' ?></small></div>
                            <?php if ($esDiscipuloRol): ?>
                                <div><small style="color:#637087;">Intentos: <?= $intentosUsados ?>/<?= $maxIntentos ?></small></div>
                                <?php if ($yaPresentada): ?>
                                    <div><small style="color:#065f46;">Ya presentada. Puedes reintentar si aún tienes cupo.</small></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <?php if ((int)($ev['Activa'] ?? 0) === 1): ?>
                            <span class="badge" style="background:#d1fae5;color:#065f46;">Activa</span>
                        <?php else: ?>
                            <span class="badge" style="background:#fee2e2;color:#7f1d1d;">Inactiva</span>
                        <?php endif; ?>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                        <?php if ($esDiscipuloRol): ?>
                            <?php if (!$intentosAgotados): ?>
                                <button
                                    type="button"
                                    class="btn btn-primary btn-sm js-abrir-evaluacion"
                                    data-url="<?= htmlspecialchars($urlPresentarEval, ENT_QUOTES, 'UTF-8') ?>"
                                    data-confirm="<?= htmlspecialchars($confirmarPresentarEval, ENT_QUOTES, 'UTF-8') ?>"
                                ><?= htmlspecialchars($textoAccionResponder) ?></button>
                            <?php else: ?>
                                <span class="badge" style="background:#fee2e2;color:#7f1d1d;padding:8px 10px;">Intentos agotados</span>
                            <?php endif; ?>
                        <?php elseif ($puedeGestionarEval): ?>
                            <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($urlEditarEval, ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                            <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars($urlNotasEval, ENT_QUOTES, 'UTF-8') ?>">Notas</a>
                            <?php if ((int)($ev['Activa'] ?? 0) !== 1 && ($puedeEditarEval || $puedeEliminarEval)): ?>
                                <form method="POST" action="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>" style="margin:0;">
                                    <input type="hidden" name="accion" value="activar_evaluacion">
                                    <input type="hidden" name="id_evaluacion" value="<?= $idEv ?>">
                                    <?= $contextoHiddenHtml ?>
                                    <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('¿Activar esta evaluación?');">Activar</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($puedeEliminarEval): ?>
                                <form method="POST" action="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>" style="margin:0;">
                                    <input type="hidden" name="accion" value="eliminar_evaluacion">
                                    <input type="hidden" name="id_evaluacion" value="<?= $idEv ?>">
                                    <?= $contextoHiddenHtml ?>
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta evaluación de forma permanente? También se borrarán las notas de los alumnos vinculadas.');">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($puedeConfigurarFechasEval): ?>
                        <form method="POST" action="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>" style="margin-top:8px;display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:8px;align-items:end;">
                            <input type="hidden" name="accion" value="configurar_fechas">
                            <input type="hidden" name="id_evaluacion" value="<?= $idEv ?>">
                            <?= $contextoHiddenHtml ?>
                            <div>
                                <label style="font-size:12px;color:#637087;">Desde</label>
                                <input type="date" class="form-control" name="fecha_habilitacion_inicio" value="<?= htmlspecialchars($fechaIniEv) ?>">
                            </div>
                            <div>
                                <label style="font-size:12px;color:#637087;">Hasta</label>
                                <input type="date" class="form-control" name="fecha_habilitacion_fin" value="<?= htmlspecialchars($fechaFinEv) ?>">
                            </div>
                            <div>
                                <button type="submit" class="btn btn-secondary btn-sm">Guardar fechas</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <?php if (empty($grupos)): ?>
        <div class="card report-card" style="padding:14px;">
            <?php if ($esDiscipuloRol): ?>
                <small style="color:#637087;">No hay evaluaciones activas con fechas vigentes para hoy en este módulo. Entra desde la tarjeta de tu nivel cuando tu líder las habilite.</small>
            <?php else: ?>
                <small style="color:#637087;">No hay evaluaciones vigentes para hoy en este módulo. Si creaste evaluaciones con fechas pasadas, revisa el historial de presentaciones.</small>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (false && !empty($evaluacionActiva)): ?>
    <?php
    $preguntasEvaluacion = json_decode((string)($evaluacionActiva['Preguntas_JSON'] ?? '[]'), true);
    if (!is_array($preguntasEvaluacion)) {
        $preguntasEvaluacion = [];
    }
    ?>
    <div class="card report-card" style="padding:14px;margin-bottom:14px;">
        <h3 style="margin:0 0 8px 0;">Resolver: <?= htmlspecialchars((string)($evaluacionActiva['Titulo'] ?? 'Evaluación')) ?></h3>
        <small style="color:#637087;">Nivel <?= (int)($evaluacionActiva['Nivel'] ?? 0) ?>, Módulo <?= (int)($evaluacionActiva['Modulo_Numero'] ?? 0) ?>.</small>
        <div><small style="color:#637087;">Lección: <?= htmlspecialchars((string)($evaluacionActiva['Leccion'] ?? 'Sin lección')) ?></small></div>
        <?php if (!empty($evaluacionActiva['Descripcion'])): ?>
            <p style="margin:10px 0 0 0;"><?= nl2br(htmlspecialchars((string)$evaluacionActiva['Descripcion'])) ?></p>
        <?php endif; ?>

        <?php if (!$puedeGestionarEval): ?>
            <div style="margin-top:10px;padding:10px;border:1px solid #dfe5ef;border-radius:10px;background:#f8fafc;">
                <div><strong>Intentos:</strong> <?= (int)($estadoIntento['intentos_realizados'] ?? 0) ?>/<?= (int)($estadoIntento['max_intentos'] ?? 2) ?></div>
                <div><strong>Tiempo máximo:</strong> 20 minutos</div>
                <div><strong>Tiempo restante:</strong>
                    <?php if (!empty($estadoIntento['intento_iniciado'])): ?>
                    <span id="evalTimerDisplay" data-segundos="<?= (int)($estadoIntento['tiempo_restante'] ?? 0) ?>">--:--</span>
                    <?php else: ?>
                    <span>20:00 (al iniciar)</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ((int)($evaluacionActiva['Activa'] ?? 0) !== 1 && !$puedeGestionarEval): ?>
            <div class="alert alert-danger" style="margin-top:10px;">Esta evaluación está inactiva.</div>
        <?php elseif (!$puedeGestionarEval && empty($estadoIntento['puede_responder'])): ?>
            <div class="alert alert-danger" style="margin-top:10px;">Ya agotaste el máximo de 2 intentos para esta evaluación.</div>
        <?php elseif (!$puedeGestionarEval && empty($estadoIntento['intento_iniciado'])): ?>
            <div class="alert alert-warning" style="margin-top:12px;margin-bottom:0;">
                Cuando estés listo, inicia el intento. El cronómetro de <strong>20 minutos</strong> empieza solo al pulsar el botón (no al abrir esta página).
            </div>
            <div style="margin-top:12px;">
                <a
                    class="btn btn-primary btn-lg"
                    href="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>&evaluacion=<?= (int)($evaluacionActiva['Id_Evaluacion'] ?? 0) ?>&iniciar=1"
                >
                    Iniciar evaluación (20 minutos)
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>" style="margin-top:12px;display:flex;flex-direction:column;gap:12px;">
                <input type="hidden" name="accion" value="presentar_evaluacion">
                <input type="hidden" name="id_evaluacion" value="<?= (int)($evaluacionActiva['Id_Evaluacion'] ?? 0) ?>">
                <input type="hidden" name="tiempo_inicio" value="<?= (int)($estadoIntento['tiempo_inicio'] ?? 0) ?>">
                <?= $contextoHiddenHtml ?>

                <div class="alert alert-info" style="margin:0;">
                    La evaluación es solo de preguntas cerradas. Puedes enviar aunque dejes preguntas sin responder.
                </div>

                <?php foreach ($preguntasEvaluacion as $idx => $pregunta): ?>
                    <div style="border:1px solid #e6e8ee;border-radius:10px;padding:10px;">
                        <strong><?= ($idx + 1) ?>. <?= htmlspecialchars((string)($pregunta['enunciado'] ?? '')) ?></strong>
                        <div style="margin-top:8px;display:flex;flex-direction:column;gap:6px;">
                            <?php foreach ((array)($pregunta['opciones'] ?? []) as $claveOpcion => $textoOpcion): ?>
                                <label style="display:flex;gap:8px;align-items:flex-start;">
                                    <input type="radio" name="respuesta[<?= (int)$idx ?>]" value="<?= htmlspecialchars((string)$claveOpcion) ?>">
                                    <span><strong><?= strtoupper((string)$claveOpcion) ?>.</strong> <?= htmlspecialchars((string)$textoOpcion) ?></span>
                                </label>
                            <?php endforeach; ?>
                            <?php if (empty((array)($pregunta['opciones'] ?? []))): ?>
                                <small style="color:#b91c1c;">Esta pregunta no tiene opciones válidas.</small>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div>
                    <button type="submit" class="btn btn-primary" id="btnEnviarEvaluacion">Enviar evaluación</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (!$puedeGestionarEval || $vistaHistorico): ?>
<div class="card report-card" style="padding:14px;margin-bottom:16px;">
    <h3 style="margin:0 0 10px 0;"><?= $esDiscipuloRol ? 'Mi historial de intentos' : 'Mis notas' ?></h3>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Evaluación</th>
                    <th>Nivel</th>
                    <th>Módulo</th>
                    <th>Intento</th>
                    <th>Puntaje</th>
                    <th>Resultado</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($historialUsuario)): ?>
                    <?php foreach ($historialUsuario as $resultado): ?>
                        <?php
                        $idEvalHist = (int)($resultado['Id_Evaluacion'] ?? 0);
                        $idResultadoHist = (int)($resultado['Id_Resultado'] ?? 0);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($resultado['Fecha_Presentacion'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($resultado['Titulo'] ?? '')) ?></td>
                            <td><?= (int)($resultado['Nivel'] ?? 0) ?></td>
                            <td><?= (int)($resultado['Modulo_Numero'] ?? 0) ?></td>
                            <td><?= (int)($resultado['Intento_Numero'] ?? 0) ?></td>
                            <td><?= (float)($resultado['Puntaje'] ?? 0) ?>%</td>
                            <td>
                                <?php if (!empty($resultado['Aprobado'])): ?>
                                    <span style="color:#166534;font-weight:600;">Aprobado</span>
                                <?php else: ?>
                                    <span style="color:#b91c1c;font-weight:600;">Reprobado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="btn btn-secondary btn-sm" href="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>&historico=1&evaluacion=<?= $idEvalHist ?>&resultado=<?= $idResultadoHist ?>">Ver detalle</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">Sin intentos registrados todavía.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php
$resumenTodosResultados = (array)($resumen_todos_resultados ?? []);
?>

<?php if ($vistaGestionHistorico): ?>
<div class="card report-card" style="padding:14px;margin-bottom:16px;">
    <h3 style="margin:0 0 6px 0;color:#1e4a89;">Evaluaciones creadas del módulo <?= (int)$filtroModuloContexto ?></h3>
    <small style="color:#637087;display:block;margin-bottom:12px;">Todas las evaluaciones de este módulo (vigentes, vencidas y próximas). Desde aquí puedes ver notas o editar.</small>
    <?php if (!empty($gruposHistoricoEval)): ?>
        <?php foreach ($gruposHistoricoEval as $grupoHist): ?>
            <?php foreach ($grupoHist['items'] as $evHist): ?>
                <?php
                    $idEvHist = (int)($evHist['Id_Evaluacion'] ?? 0);
                    $fechaIniHist = trim((string)($evHist['Fecha_Habilitacion_Inicio'] ?? ''));
                    $fechaFinHist = trim((string)($evHist['Fecha_Habilitacion_Fin'] ?? ''));
                    $hoyHist = date('Y-m-d');
                    $estadoHist = 'Vigente';
                    $estadoHistColor = '#065f46';
                    $estadoHistBg = '#d1fae5';
                    if ($fechaIniHist === '' || $fechaFinHist === '') {
                        $estadoHist = 'Sin fechas';
                        $estadoHistColor = '#92400e';
                        $estadoHistBg = '#fef3c7';
                    } elseif ($hoyHist < $fechaIniHist) {
                        $estadoHist = 'Próxima';
                        $estadoHistColor = '#1e40af';
                        $estadoHistBg = '#dbeafe';
                    } elseif ($hoyHist > $fechaFinHist) {
                        $estadoHist = 'Vencida';
                        $estadoHistColor = '#7f1d1d';
                        $estadoHistBg = '#fee2e2';
                    }
                    $urlEditarHist = PUBLIC_URL . '?url=programas/evaluaciones' . $contextoQuery . '&editar=' . $idEvHist;
                    $urlNotasHist = PUBLIC_URL . '?url=programas/evaluaciones' . $contextoQuery . '&historico=1&evaluacion=' . $idEvHist;
                ?>
                <div style="border:1px solid #e6e8ee;border-radius:10px;padding:12px;margin-bottom:10px;background:#fff;">
                    <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">
                        <div>
                            <strong><?= htmlspecialchars((string)($evHist['Titulo'] ?? 'Evaluación')) ?></strong>
                            <div><small style="color:#637087;">Lección: <?= htmlspecialchars((string)($evHist['Leccion'] ?? 'Sin lección')) ?></small></div>
                            <div><small style="color:#637087;">Ventana: <?= $fechaIniHist !== '' ? htmlspecialchars($fechaIniHist) : '—' ?> a <?= $fechaFinHist !== '' ? htmlspecialchars($fechaFinHist) : '—' ?></small></div>
                        </div>
                        <span class="badge" style="background:<?= $estadoHistBg ?>;color:<?= $estadoHistColor ?>;"><?= htmlspecialchars($estadoHist) ?></span>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
                        <a class="btn btn-primary btn-sm" href="<?= htmlspecialchars($urlEditarHist, ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                        <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars($urlNotasHist, ENT_QUOTES, 'UTF-8') ?>">Notas</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <small style="color:#637087;">No hay evaluaciones registradas en este módulo.</small>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($vistaGestionHistorico && !empty($resumenTodosResultados)): ?>
<div class="card report-card" style="padding:14px;margin-bottom:16px;">
    <h3 style="margin:0 0 10px 0;">Presentaciones de estudiantes · Nivel <?= (int)$filtroNivelContexto ?> / Módulo <?= (int)$filtroModuloContexto ?></h3>
    <small style="color:#637087;display:block;margin-bottom:10px;">Solo se muestra el último intento por persona y por evaluación en esta carpeta de material.</small>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Persona</th>
                    <th>Evaluación</th>
                    <th>Nivel</th>
                    <th>Módulo</th>
                    <th>Intento</th>
                    <th>Usados</th>
                    <th>Puntaje</th>
                    <th>Resultado</th>
                    <th>Detalle</th>
                    <?php if ($puedeEditarEval): ?>
                        <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resumenTodosResultados as $resultado): ?>
                    <?php
                    $fechaResultado = trim((string)($resultado['Fecha_Presentacion'] ?? ''));
                    $fechaFormato = '';
                    if ($fechaResultado !== '') {
                        try {
                            $dt = new DateTime($fechaResultado);
                            $fechaFormato = $dt->format('d/m/Y H:i');
                        } catch (Exception $e) {
                            $fechaFormato = $fechaResultado;
                        }
                    }
                    $nombrePersona = trim((string)($resultado['Nombre'] ?? '') . ' ' . (string)($resultado['Apellido'] ?? ''));
                    $puntaje = (float)($resultado['Puntaje'] ?? 0);
                    $aprobado = !empty($resultado['Aprobado']);
                    $resultadoText = $aprobado ? 'Aprobado' : 'Reprobado';
                    $idEvalRes = (int)($resultado['Id_Evaluacion'] ?? 0);
                    $idResultadoRes = (int)($resultado['Id_Resultado'] ?? 0);
                    $idPersonaRes = (int)($resultado['Id_Persona'] ?? 0);
                    $intentosUsadosRes = (int)($resultado['Total_Intentos_Registrados'] ?? 0);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($fechaFormato) ?></td>
                        <td><?= htmlspecialchars($nombrePersona) ?></td>
                        <td><?= htmlspecialchars((string)($resultado['Titulo'] ?? '')) ?></td>
                        <td><?= (int)($resultado['Nivel'] ?? 0) ?></td>
                        <td><?= (int)($resultado['Modulo_Numero'] ?? 0) ?></td>
                        <td><?= (int)($resultado['Intento_Numero'] ?? 0) ?></td>
                        <td><?= $intentosUsadosRes ?>/<?= $maxIntentos ?></td>
                        <td><?= number_format($puntaje, 1, ',', '.') ?>%</td>
                        <td><span style="font-weight:bold;color:<?= $aprobado ? '#065f46' : '#b91c1c' ?>"><?= $resultadoText ?></span></td>
                        <td>
                            <?php if ($idEvalRes > 0 && $idResultadoRes > 0): ?>
                                <a class="btn btn-secondary btn-sm" href="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>&historico=1&evaluacion=<?= $idEvalRes ?>&resultado=<?= $idResultadoRes ?>">Ver</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <?php if ($puedeEditarEval): ?>
                            <td>
                                <?php if ($idEvalRes > 0 && $idPersonaRes > 0): ?>
                                    <form method="POST" action="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>&historico=1" style="display:inline;margin:0;" onsubmit="return confirm(<?= json_encode($confirmarReactivarIntentos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>);">
                                        <input type="hidden" name="accion" value="reactivar_intentos">
                                        <input type="hidden" name="vista_historico" value="1">
                                        <input type="hidden" name="id_evaluacion" value="<?= $idEvalRes ?>">
                                        <input type="hidden" name="id_persona" value="<?= $idPersonaRes ?>">
                                        <?= $contextoHiddenHtml ?>
                                        <button type="submit" class="btn btn-warning btn-sm" title="Borrar intentos y permitir presentar de nuevo">Reactivar intentos</button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php elseif ($vistaGestionHistorico && empty($resumenTodosResultados) && empty($historialEvaluacion)): ?>
<div class="card report-card" style="padding:14px;margin-bottom:16px;">
    <?php if ($filtroNivelContexto > 0 && $filtroModuloContexto > 0): ?>
        <small style="color:#637087;">Aún no hay presentaciones de estudiantes en este módulo. Arriba puedes ver las evaluaciones creadas.</small>
    <?php else: ?>
        <small style="color:#637087;">Abre <strong>Evaluaciones</strong> desde el material del nivel y módulo correspondiente para ver el resumen filtrado.</small>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($vistaGestionHistorico && !empty($historialEvaluacion) && !empty($evaluacionActiva)): ?>
<div class="card report-card" style="padding:14px;margin-bottom:16px;">
    <h3 style="margin:0 0 10px 0;">Resultados de la evaluación seleccionada</h3>
    <small style="color:#637087;display:block;margin-bottom:10px;">Último intento por persona en esta evaluación.</small>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Persona</th>
                    <th>Intento</th>
                    <th>Usados</th>
                    <th>Correctas</th>
                    <th>Total</th>
                    <th>Puntaje</th>
                    <th>Resultado</th>
                    <th>Detalle</th>
                    <?php if ($puedeEditarEval): ?>
                        <th>Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historialEvaluacion as $resultadoAdmin): ?>
                    <?php
                    $idResultadoAdmin = (int)($resultadoAdmin['Id_Resultado'] ?? 0);
                    $idEvalAdmin = (int)($resultadoAdmin['Id_Evaluacion'] ?? 0);
                    $idPersonaAdmin = (int)($resultadoAdmin['Id_Persona'] ?? 0);
                    $intentosUsadosAdmin = (int)($resultadoAdmin['Total_Intentos_Registrados'] ?? 0);
                    $nombrePersonaAdmin = trim((string)($resultadoAdmin['Nombre'] ?? '') . ' ' . (string)($resultadoAdmin['Apellido'] ?? ''));
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($resultadoAdmin['Fecha_Presentacion'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($nombrePersonaAdmin) ?></td>
                        <td><?= (int)($resultadoAdmin['Intento_Numero'] ?? 0) ?></td>
                        <td><?= $intentosUsadosAdmin ?>/<?= $maxIntentos ?></td>
                        <td><?= (int)($resultadoAdmin['Correctas'] ?? 0) ?></td>
                        <td><?= (int)($resultadoAdmin['Total_Preguntas'] ?? 0) ?></td>
                        <td><?= (float)($resultadoAdmin['Puntaje'] ?? 0) ?>%</td>
                        <td><?= !empty($resultadoAdmin['Aprobado']) ? 'Aprobado' : 'Reprobado' ?></td>
                        <td>
                            <a class="btn btn-secondary btn-sm" href="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>&historico=1&evaluacion=<?= $idEvalAdmin ?>&resultado=<?= $idResultadoAdmin ?>">Ver detalle</a>
                        </td>
                        <?php if ($puedeEditarEval): ?>
                            <td>
                                <?php if ($idEvalAdmin > 0 && $idPersonaAdmin > 0): ?>
                                    <form method="POST" action="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>&historico=1&evaluacion=<?= $idEvalAdmin ?>" style="display:inline;margin:0;" onsubmit="return confirm(<?= json_encode($confirmarReactivarIntentos, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>);">
                                        <input type="hidden" name="accion" value="reactivar_intentos">
                                        <input type="hidden" name="vista_historico" value="1">
                                        <input type="hidden" name="id_evaluacion" value="<?= $idEvalAdmin ?>">
                                        <input type="hidden" name="id_persona" value="<?= $idPersonaAdmin ?>">
                                        <?= $contextoHiddenHtml ?>
                                        <button type="submit" class="btn btn-warning btn-sm" title="Borrar intentos y permitir presentar de nuevo">Reactivar intentos</button>
                                    </form>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
(function() {
    document.querySelectorAll('.js-abrir-evaluacion').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var url = btn.getAttribute('data-url') || '';
            if (!url) {
                return;
            }
            var mensaje = btn.getAttribute('data-confirm') || '';
            if (mensaje !== '' && !window.confirm(mensaje)) {
                return;
            }
            window.location.href = url;
        });
    });
})();

(function() {
    document.querySelectorAll('.js-disc-toggle-tareas').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = btn.getAttribute('data-target');
            var baseLabel = btn.getAttribute('data-label') || 'tareas';
            if (!targetId) {
                return;
            }

            var panel = document.getElementById(targetId);
            if (!panel) {
                return;
            }

            var visible = !panel.classList.contains('is-hidden');
            panel.classList.toggle('is-hidden', visible);
            btn.textContent = visible ? ('Ver ' + baseLabel) : ('Ocultar ' + baseLabel);
        });
    });

    const contenedor = document.getElementById('contenedorPreguntas');
    const btnAgregar = document.getElementById('btnAgregarPregunta');
    const selectorNivel = document.querySelector('select[name="nivel"]');
    const selectorModulo = document.querySelector('select[name="modulo_numero"]');
    const selectorLeccion = document.getElementById('leccionEvaluacionSelect');
    const leccionesMap = <?= json_encode($leccionesPorNivelModulo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const leccionContextoFija = <?= json_encode($filtroLeccionContexto, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let primeraCargaLecciones = true;
    if (!contenedor || !btnAgregar) {
        return;
    }

    function actualizarLeccionesDisponibles() {
        if (!selectorLeccion || !selectorNivel || !selectorModulo) {
            return;
        }

        const nivel = String(selectorNivel.value || '');
        const modulo = String(selectorModulo.value || '');
        const mapaNivel = (leccionesMap && leccionesMap[nivel]) ? leccionesMap[nivel] : {};
        const lista = (mapaNivel && mapaNivel[modulo]) ? mapaNivel[modulo] : ['Sin lección'];

        selectorLeccion.innerHTML = '';
        lista.forEach(function(leccion) {
            const option = document.createElement('option');
            option.value = String(leccion);
            option.textContent = String(leccion);
            selectorLeccion.appendChild(option);
        });

        if (primeraCargaLecciones && leccionContextoFija && lista.indexOf(leccionContextoFija) >= 0) {
            selectorLeccion.value = leccionContextoFija;
        }

        primeraCargaLecciones = false;
    }

    if (selectorNivel && selectorModulo && selectorLeccion) {
        selectorNivel.addEventListener('change', actualizarLeccionesDisponibles);
        selectorModulo.addEventListener('change', actualizarLeccionesDisponibles);
        actualizarLeccionesDisponibles();
    }

    let indice = 0;

    function crearBloquePregunta(idx) {
        const wrapper = document.createElement('div');
        wrapper.style.border = '1px solid #e6e8ee';
        wrapper.style.borderRadius = '10px';
        wrapper.style.padding = '10px';

        wrapper.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                <strong>Pregunta ${idx + 1}</strong>
                <button type="button" class="btn btn-danger btn-sm js-eliminar-pregunta">Eliminar</button>
            </div>
            <div style="margin-top:8px;display:flex;flex-direction:column;gap:8px;">
                <input type="hidden" name="preguntas[${idx}][tipo]" value="cerrada">
                <small style="color:#637087;">Tipo: cerrada</small>
                <input type="text" class="form-control" name="preguntas[${idx}][enunciado]" placeholder="Enunciado de la pregunta" required>
                <div class="js-opciones-cerradas" style="display:flex;flex-direction:column;gap:8px;">
                    <input type="text" class="form-control" name="preguntas[${idx}][opcion_a]" placeholder="Opción A">
                    <input type="text" class="form-control" name="preguntas[${idx}][opcion_b]" placeholder="Opción B">
                    <input type="text" class="form-control" name="preguntas[${idx}][opcion_c]" placeholder="Opción C">
                    <input type="text" class="form-control" name="preguntas[${idx}][opcion_d]" placeholder="Opción D">
                    <select class="form-control" name="preguntas[${idx}][respuesta_correcta]">
                        <option value="a">Respuesta correcta: A</option>
                        <option value="b">Respuesta correcta: B</option>
                        <option value="c">Respuesta correcta: C</option>
                        <option value="d">Respuesta correcta: D</option>
                    </select>
                    <small style="color:#637087;">Para pregunta cerrada se requieren mínimo 2 opciones.</small>
                </div>
            </div>
        `;

        const btnEliminar = wrapper.querySelector('.js-eliminar-pregunta');
        const contenedorOpciones = wrapper.querySelector('.js-opciones-cerradas');
        if (contenedorOpciones) {
            contenedorOpciones.style.display = 'flex';
        }

        btnEliminar.addEventListener('click', function() {
            wrapper.remove();
        });

        return wrapper;
    }

    btnAgregar.addEventListener('click', function() {
        contenedor.appendChild(crearBloquePregunta(indice));
        indice += 1;
    });

    contenedor.appendChild(crearBloquePregunta(indice));
    indice += 1;
})();

(function() {
    const timerEl = document.getElementById('evalTimerDisplay');
    const btnEnviar = document.getElementById('btnEnviarEvaluacion');
    if (!timerEl || !btnEnviar) {
        return;
    }

    let segundos = parseInt(timerEl.getAttribute('data-segundos') || '0', 10);
    if (Number.isNaN(segundos) || segundos < 0) {
        segundos = 0;
    }
    timerEl.setAttribute('data-segundos', String(segundos));

    function formatearTiempo(totalSegundos) {
        const minutos = Math.floor(totalSegundos / 60);
        const segs = totalSegundos % 60;
        return String(minutos).padStart(2, '0') + ':' + String(segs).padStart(2, '0');
    }

    function render() {
        timerEl.textContent = formatearTiempo(segundos);
        if (segundos <= 0) {
            btnEnviar.disabled = true;
            btnEnviar.textContent = 'Tiempo agotado';
        }
    }

    render();
    if (segundos <= 0) {
        return;
    }

    const intervalId = setInterval(function() {
        segundos -= 1;
        render();
        if (segundos <= 0) {
            clearInterval(intervalId);
        }
    }, 1000);
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>