<?php include VIEWS . '/layout/header.php'; ?>

<?php
$mensajeFlash = (string)($mensaje ?? '');
$tipoFlash = (string)($tipo ?? '');
$evaluacionesLista = (array)($evaluaciones ?? []);
$evaluacionActiva = $evaluacion_seleccionada ?? null;
$historialUsuario = (array)($resultados_usuario ?? []);
$historialEvaluacion = (array)($resultados_evaluacion ?? []);
$puedeGestionarEval = !empty($puede_gestionar);
$puedeConfigurarFechasEval = !empty($puede_configurar_fechas);
$esDiscipuloRol = !empty($es_discipulo);
$estadoIntento = (array)($estado_intento ?? []);
$clasesLinks = (array)($clases_links ?? []);
$accesosDirectosDiscipulo = (array)($accesos_directos_discipulo ?? []);
$tareasPorModuloDiscipulo = (array)($tareas_por_modulo_discipulo ?? []);
$intentosPorEvaluacion = (array)($intentos_por_evaluacion ?? []);
$maxIntentos = (int)($max_intentos ?? 2);
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

$evaluacionesOcultasSinFechas = [];
if ($puedeGestionarEval) {
    foreach ($evaluacionesLista as $evaluacionTmpFechas) {
        $fechaInicioTmp = trim((string)($evaluacionTmpFechas['Fecha_Habilitacion_Inicio'] ?? ''));
        $fechaFinTmp = trim((string)($evaluacionTmpFechas['Fecha_Habilitacion_Fin'] ?? ''));
        if ($fechaInicioTmp === '' || $fechaFinTmp === '') {
            $evaluacionesOcultasSinFechas[] = $evaluacionTmpFechas;
        }
    }
}
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
</style>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;">Discipular - Evaluaciones</h2>
        <small style="color:#637087;">Solo preguntas cerradas. Se aprueba con 80%.</small>
    </div>
    <div class="header-actions" style="display:flex;gap:8px;flex-wrap:wrap;">
        <?php if ($contextoDesdeMaterial): ?>
            <a class="btn btn-secondary btn-sm" href="<?= PUBLIC_URL ?>?url=home/material/capacitacion-destino">
                <i class="bi bi-folder"></i> Volver a Material Capacitacion Destino
            </a>
        <?php endif; ?>
        <a class="btn btn-secondary btn-sm" href="<?= PUBLIC_URL ?>?url=programas">
            <i class="bi bi-arrow-left-short"></i> Volver a Discipular
        </a>
    </div>
</div>

<?php if ($filtroNivelContexto > 0 && $filtroModuloContexto > 0): ?>
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

<?php if (!empty($resultadoDetalle)): ?>
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

<?php if ($puedeGestionarEval && !empty($evaluacionesOcultasSinFechas)): ?>
    <div class="alert alert-danger" style="margin:12px 0;">
        <div><strong>Evaluaciones ocultas por falta de fechas:</strong> estas evaluaciones no son visibles para discípulos hasta definir inicio y fin.</div>
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

<?php if ($esDiscipuloRol && !empty($accesosDirectosDiscipulo)): ?>
<div class="card report-card" style="padding:14px; margin-bottom:14px;">
    <h3 style="margin:0 0 4px 0;">Acceso a clase</h3>
    <small style="color:#637087;">Link único de clase para hoy.</small>
    <div style="margin-top:10px;">
        <?php if ($urlClaseUnicaDiscipulo !== ''): ?>
            <a class="btn btn-sm" style="background:#10b981;color:#fff;" href="<?= htmlspecialchars($urlClaseUnicaDiscipulo, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Ir a clase</a>
        <?php else: ?>
            <button type="button" class="btn btn-sm" style="background:#94a3b8;color:#fff;" disabled title="Aún no hay link de clase configurado">Ir a clase</button>
        <?php endif; ?>
    </div>
</div>

<div class="card report-card" style="padding:14px; margin-bottom:14px;">
    <h3 style="margin:0 0 4px 0;">Módulos de evaluaciones</h3>
    <small style="color:#637087;">Modo discípulo: aquí solo ves tus evaluaciones activas de hoy.</small>
    <div class="disc-eval-grid">
        <?php foreach ($accesosDirectosDiscipulo as $accesoDirecto): ?>
            <?php
                $nivelAcceso = (int)($accesoDirecto['nivel'] ?? 0);
                $moduloAcceso = (int)($accesoDirecto['modulo'] ?? 0);
                $keyTareaAcceso = $nivelAcceso . '_' . $moduloAcceso;
                $tareasModulo = (array)($tareasPorModuloDiscipulo[$keyTareaAcceso] ?? []);
                $tareasPanelId = 'disc-tareas-' . $nivelAcceso . '-' . $moduloAcceso;
            ?>
            <div class="disc-eval-card">
                <div style="font-weight:700;color:#1f4f93;">Nivel <?= $nivelAcceso ?> · Módulo <?= $moduloAcceso ?></div>
                <div><small style="color:#637087;">Lección: <?= htmlspecialchars((string)($accesoDirecto['leccion'] ?? 'Sin lección activa')) ?></small></div>
                <div class="disc-card-actions">
                    <?php if (!empty($accesoDirecto['url_evaluacion'])): ?>
                        <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars((string)$accesoDirecto['url_evaluacion'], ENT_QUOTES, 'UTF-8') ?>">Ir a evaluación</a>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm" style="background:#94a3b8;color:#fff;" disabled title="No hay evaluación activa para este nivel/módulo">Ir a evaluación</button>
                    <?php endif; ?>
                    <a class="btn btn-sm btn-secondary" href="<?= PUBLIC_URL ?>?url=programas/tareas&nivel=<?= $nivelAcceso ?>&modulo=<?= $moduloAcceso ?>">
                        Ver tareas (<?= count($tareasModulo) ?>)
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php elseif (!$esDiscipuloRol && !$puedeGestionarEval && !empty($clasesLinks)): ?>
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
<?php endif; ?>

<?php if ($puedeGestionarEval): ?>
<div class="card report-card" style="padding:14px; margin-bottom:14px;">
    <h3 style="margin:0 0 12px 0;">Crear evaluación</h3>
    <form method="POST" action="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>">
        <input type="hidden" name="accion" value="crear_evaluacion">
        <?= $contextoHiddenHtml ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;">
            <div>
                <label>Título</label>
                <input type="text" class="form-control" name="titulo" required maxlength="180">
            </div>
            <div>
                <label>Nivel</label>
                <select class="form-control" name="nivel" required>
                    <option value="1" <?= $filtroNivelContexto === 1 ? 'selected' : '' ?>>Nivel 1</option>
                    <option value="2" <?= $filtroNivelContexto === 2 ? 'selected' : '' ?>>Nivel 2</option>
                    <option value="3" <?= $filtroNivelContexto === 3 ? 'selected' : '' ?>>Nivel 3</option>
                </select>
            </div>
            <div>
                <label>Módulo</label>
                <select class="form-control" name="modulo_numero" required>
                    <option value="1" <?= $filtroModuloContexto === 1 ? 'selected' : '' ?>>Módulo 1</option>
                    <option value="2" <?= $filtroModuloContexto === 2 ? 'selected' : '' ?>>Módulo 2</option>
                    <option value="3" <?= $filtroModuloContexto === 3 ? 'selected' : '' ?>>Módulo 3</option>
                    <option value="4" <?= $filtroModuloContexto === 4 ? 'selected' : '' ?>>Módulo 4</option>
                    <option value="5" <?= $filtroModuloContexto === 5 ? 'selected' : '' ?>>Módulo 5</option>
                    <option value="6" <?= $filtroModuloContexto === 6 ? 'selected' : '' ?>>Módulo 6</option>
                </select>
            </div>
            <div>
                <label>Lección</label>
                <select class="form-control" id="leccionEvaluacionSelect" name="leccion" required>
                    <?php foreach ($leccionesIniciales as $leccionOpt): ?>
                        <?php $leccionOptStr = (string)$leccionOpt; ?>
                        <option value="<?= htmlspecialchars($leccionOptStr, ENT_QUOTES, 'UTF-8') ?>" <?= $leccionOptStr === $filtroLeccionContexto ? 'selected' : '' ?>><?= htmlspecialchars($leccionOptStr) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Puntaje mínimo (%)</label>
                <input type="number" class="form-control" name="puntaje_minimo" min="80" max="100" step="0.01" value="80" required>
            </div>
            <div>
                <label>Tipo de respuestas</label>
                <input type="hidden" name="modo_respuestas" value="cerrada">
                <span class="form-control" style="background:#f3f4f6;">Solo cerradas</span>
                <small style="color:#637087;">Solo se permiten preguntas cerradas en Capacitación Destino.</small>
            </div>
        </div>

        <?php if ($puedeConfigurarFechasEval): ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-top:10px;">
                <div>
                    <label>Habilitar desde</label>
                    <input type="date" class="form-control" name="fecha_habilitacion_inicio">
                </div>
                <div>
                    <label>Habilitar hasta</label>
                    <input type="date" class="form-control" name="fecha_habilitacion_fin">
                </div>
            </div>
        <?php endif; ?>

        <div style="margin-top:10px;">
            <label>Descripción</label>
            <textarea class="form-control" name="descripcion" rows="2" placeholder="Instrucciones para el discípulo"></textarea>
        </div>

        <hr style="margin:14px 0;">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
            <h4 style="margin:0;">Preguntas</h4>
            <button type="button" class="btn btn-secondary btn-sm" id="btnAgregarPregunta">Agregar pregunta</button>
        </div>
        <div id="contenedorPreguntas" style="display:flex;flex-direction:column;gap:12px;margin-top:10px;"></div>

        <div style="margin-top:12px;">
            <button type="submit" class="btn btn-primary">Guardar evaluación</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if (!$esDiscipuloRol): ?>
<div class="dashboard-grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr));margin-bottom:14px;">
    <?php foreach ($grupos as $grupo): ?>
        <div class="card report-card" style="padding:12px;">
            <h3 style="margin:0 0 8px 0;">Nivel <?= (int)$grupo['nivel'] ?> - Módulo <?= (int)$grupo['modulo'] ?></h3>
            <?php foreach ($grupo['items'] as $ev): ?>
                <?php $idEv = (int)($ev['Id_Evaluacion'] ?? 0); ?>
                <?php
                $intentosUsados = (int)($intentosPorEvaluacion[$idEv] ?? 0);
                $intentosRestantes = max(0, $maxIntentos - $intentosUsados);
                $intentosAgotados = !$puedeGestionarEval && $intentosRestantes <= 0;
                $yaPresentada = !$puedeGestionarEval && $intentosUsados > 0;
                $textoAccionResponder = $puedeGestionarEval
                    ? 'Abrir'
                    : ($intentosUsados > 0 ? 'Volver a presentar' : 'Responder');

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
                            <?php if (!$puedeGestionarEval): ?>
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
                        <?php if (!$intentosAgotados): ?>
                            <a class="btn btn-info btn-sm" href="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>&evaluacion=<?= $idEv ?>"><?= htmlspecialchars($textoAccionResponder) ?></a>
                        <?php else: ?>
                            <span class="badge" style="background:#fee2e2;color:#7f1d1d;padding:8px 10px;">Intentos agotados</span>
                        <?php endif; ?>
                        <?php if ($puedeGestionarEval && (int)($ev['Activa'] ?? 0) === 1): ?>
                            <form method="POST" action="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>" style="margin:0;">
                                <input type="hidden" name="accion" value="desactivar_evaluacion">
                                <input type="hidden" name="id_evaluacion" value="<?= $idEv ?>">
                                <?= $contextoHiddenHtml ?>
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Desactivar evaluación?');">Desactivar</button>
                            </form>
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
            <small style="color:#637087;">No hay evaluaciones creadas todavía.</small>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($puedeGestionarEval): ?>
<div class="card report-card" style="padding:14px;margin-bottom:14px;">
    <h3 style="margin:0 0 8px 0;">Capacitación Destino - Presentaron por nivel</h3>
    <small style="color:#637087;">Se toma el último intento por persona en cada nivel y se separa por estado.</small>

    <?php if (!empty($resumenCapacitacionPorNivel)): ?>
        <?php foreach ($resumenCapacitacionPorNivel as $grupoNivelResumen): ?>
            <?php
            $nivelResumen = (int)($grupoNivelResumen['nivel'] ?? 0);
            $aprobadosNivel = (array)($grupoNivelResumen['aprobados'] ?? []);
            $reprobadosNivel = (array)($grupoNivelResumen['reprobados'] ?? []);
            ?>
            <div style="border:1px solid #e6e8ee;border-radius:10px;padding:10px;margin-top:10px;">
                <div style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;align-items:center;">
                    <strong>Nivel <?= $nivelResumen ?></strong>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <span class="badge" style="background:#dcfce7;color:#166534;">Aprobados: <?= count($aprobadosNivel) ?></span>
                        <span class="badge" style="background:#fee2e2;color:#991b1b;">Reprobados: <?= count($reprobadosNivel) ?></span>
                    </div>
                </div>

                <div class="dashboard-grid" style="grid-template-columns:repeat(auto-fit,minmax(320px,1fr));margin-top:10px;">
                    <div style="border:1px solid #dcfce7;border-radius:8px;padding:8px;">
                        <h4 style="margin:0 0 8px 0;color:#166534;">Aprobados</h4>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Persona</th>
                                        <th>Módulo</th>
                                        <th>Puntaje</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($aprobadosNivel)): ?>
                                        <?php foreach ($aprobadosNivel as $filaAprobado): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(trim((string)($filaAprobado['Nombre'] ?? '') . ' ' . (string)($filaAprobado['Apellido'] ?? ''))) ?></td>
                                                <td><?= (int)($filaAprobado['Modulo_Numero'] ?? 0) ?></td>
                                                <td><?= (float)($filaAprobado['Puntaje'] ?? 0) ?>%</td>
                                                <td><?= htmlspecialchars((string)($filaAprobado['Fecha_Presentacion'] ?? '')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center">Sin aprobados en este nivel.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div style="border:1px solid #fee2e2;border-radius:8px;padding:8px;">
                        <h4 style="margin:0 0 8px 0;color:#991b1b;">Reprobados</h4>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Persona</th>
                                        <th>Módulo</th>
                                        <th>Puntaje</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($reprobadosNivel)): ?>
                                        <?php foreach ($reprobadosNivel as $filaReprobado): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(trim((string)($filaReprobado['Nombre'] ?? '') . ' ' . (string)($filaReprobado['Apellido'] ?? ''))) ?></td>
                                                <td><?= (int)($filaReprobado['Modulo_Numero'] ?? 0) ?></td>
                                                <td><?= (float)($filaReprobado['Puntaje'] ?? 0) ?>%</td>
                                                <td><?= htmlspecialchars((string)($filaReprobado['Fecha_Presentacion'] ?? '')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="text-center">Sin reprobados en este nivel.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div style="margin-top:10px;">
            <small style="color:#637087;">Aún no hay resultados para personas inscritas en Capacitación Destino.</small>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($evaluacionActiva)): ?>
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
                <div><strong>Tiempo restante:</strong> <span id="evalTimerDisplay"><?= (int)($estadoIntento['tiempo_restante'] ?? 0) ?> s</span></div>
            </div>
        <?php endif; ?>

        <?php if ((int)($evaluacionActiva['Activa'] ?? 0) !== 1 && !$puedeGestionarEval): ?>
            <div class="alert alert-danger" style="margin-top:10px;">Esta evaluación está inactiva.</div>
        <?php elseif (!$puedeGestionarEval && empty($estadoIntento['puede_responder'])): ?>
            <div class="alert alert-danger" style="margin-top:10px;">Ya agotaste el máximo de 2 intentos para esta evaluación.</div>
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

<div class="card report-card" style="padding:14px;margin-bottom:16px;">
    <h3 style="margin:0 0 10px 0;"><?= $esDiscipuloRol ? 'Mi historial de intentos' : ($puedeGestionarEval ? 'Historial personal de intentos' : 'Mis notas') ?></h3>
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
                                <a class="btn btn-secondary btn-sm" href="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>&evaluacion=<?= $idEvalHist ?>&resultado=<?= $idResultadoHist ?>">Ver detalle</a>
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

<?php if ($puedeGestionarEval && !empty($historialEvaluacion) && !empty($evaluacionActiva)): ?>
<div class="card report-card" style="padding:14px;margin-bottom:16px;">
    <h3 style="margin:0 0 10px 0;">Resultados de la evaluación seleccionada</h3>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Persona</th>
                    <th>Intento</th>
                    <th>Correctas</th>
                    <th>Total</th>
                    <th>Puntaje</th>
                    <th>Resultado</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historialEvaluacion as $resultadoAdmin): ?>
                    <?php
                    $idResultadoAdmin = (int)($resultadoAdmin['Id_Resultado'] ?? 0);
                    $idEvalAdmin = (int)($resultadoAdmin['Id_Evaluacion'] ?? 0);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($resultadoAdmin['Fecha_Presentacion'] ?? '')) ?></td>
                        <td><?= htmlspecialchars(trim((string)($resultadoAdmin['Nombre'] ?? '') . ' ' . (string)($resultadoAdmin['Apellido'] ?? ''))) ?></td>
                        <td><?= (int)($resultadoAdmin['Intento_Numero'] ?? 0) ?></td>
                        <td><?= (int)($resultadoAdmin['Correctas'] ?? 0) ?></td>
                        <td><?= (int)($resultadoAdmin['Total_Preguntas'] ?? 0) ?></td>
                        <td><?= (float)($resultadoAdmin['Puntaje'] ?? 0) ?>%</td>
                        <td><?= !empty($resultadoAdmin['Aprobado']) ? 'Aprobado' : 'Reprobado' ?></td>
                        <td>
                            <a class="btn btn-secondary btn-sm" href="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>&evaluacion=<?= $idEvalAdmin ?>&resultado=<?= $idResultadoAdmin ?>">Ver detalle</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
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

    let segundos = parseInt(timerEl.textContent, 10);
    if (Number.isNaN(segundos) || segundos < 0) {
        segundos = 0;
    }

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
