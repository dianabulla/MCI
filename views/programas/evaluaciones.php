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
            <i class="bi bi-arrow-left-short"></i> Volver a Programas
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
<div class="card report-card" style="padding:0; margin-bottom:20px; overflow:hidden; box-shadow:0 2px 8px rgba(31,79,147,0.1);">
    <!-- Header -->
    <div style="background:linear-gradient(135deg, #1f4f93 0%, #2d5fa3 100%); padding:18px 20px; color:white;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h2 style="margin:0;font-size:22px;font-weight:600;">Crear evaluación</h2>
                <small style="opacity:0.9;">Completa los detalles y agrega preguntas</small>
            </div>
            <div id="estadoGuardado" style="font-size:13px;color:#10b981;font-weight:bold;display:none;background:rgba(255,255,255,0.15);padding:8px 12px;border-radius:6px;">
                <span id="textoEstado">✓ Guardado automático</span>
            </div>
        </div>
    </div>

    <form id="formCrearEvaluacion" method="POST" action="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>" style="padding:20px;">
        <input type="hidden" name="accion" value="crear_evaluacion">
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
                    <input type="number" name="nivel" class="form-control" min="1" max="10" placeholder="1" required style="border:1px solid #d1d5db;border-radius:6px;padding:10px 12px;">
                </div>
                <div>
                    <label style="font-weight:600;color:#1f4f93;font-size:13px;display:block;margin-bottom:6px;">Módulo *</label>
                    <input type="number" name="modulo_numero" class="form-control" min="1" max="10" placeholder="1" required style="border:1px solid #d1d5db;border-radius:6px;padding:10px 12px;">
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
        <div style="margin-bottom:24px;">
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
        <div style="margin-bottom:24px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid #e5ebf7;">
                <h4 style="color:#1f4f93;font-size:14px;font-weight:600;margin:0;">Preguntas de opción múltiple</h4>
                <span id="contadorPreguntasUI" style="background:#1f4f93;color:white;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;">0 preguntas</span>
            </div>
            <div id="contenedorPreguntas" style="display:flex;flex-direction:column;gap:14px;"></div>
            <button type="button" class="btn btn-secondary" onclick="agregarPregunta()" style="margin-top:14px;padding:10px 16px;border-radius:6px;border:1px dashed #d1d5db;background:white;color:#1f4f93;font-weight:600;">
                <i class="bi bi-plus-circle"></i> Agregar pregunta
            </button>
        </div>

        <!-- Sección: Acciones -->
        <div style="display:flex;gap:10px;padding-top:14px;border-top:1px solid #e5ebf7;">
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
        let contadorPreguntas = 0;

        function actualizarContador() {
            const cantidad = document.querySelectorAll('[data-pregunta-id]').length;
            contadorUI.textContent = cantidad + ' ' + (cantidad === 1 ? 'pregunta' : 'preguntas');
        }

        function obtenerDatos() {
            const preguntas = [];
            document.querySelectorAll('[data-pregunta-id]').forEach(pregEl => {
                const enunciado = pregEl.querySelector('[name="pregunta_enunciado[]"]')?.value || '';
                const tipoInput = pregEl.querySelector('[name="pregunta_tipo[]"]')?.value || 'cerrada';
                const opcionesInputs = pregEl.querySelectorAll('[name="pregunta_opciones[]"]');
                const correctaInput = pregEl.querySelector('[name="pregunta_correcta[]"]');
                
                const opciones = [];
                const opcionesMap = {};
                opcionesInputs.forEach((optEl, idx) => {
                    const valor = optEl.value.trim();
                    if (valor !== '') {
                        const clave = String.fromCharCode(65 + idx); // A, B, C, D
                        opciones.push({ clave, opcion: valor });
                        opcionesMap[clave] = valor;
                    }
                });

                if (enunciado.trim() !== '') {
                    preguntas.push({
                        tipo: tipoInput,
                        enunciado: enunciado,
                        opciones: opciones,
                        respuesta_correcta: correctaInput?.value || '',
                        descripcion_extra: pregEl.querySelector('[name="pregunta_descripcion[]"]')?.value || ''
                    });
                }
            });
            return preguntas;
        }

        function guardarAutomaticamente() {
            clearTimeout(timerAutoSave);
            
            const formData = new FormData(formEl);
            formData.set('preguntas', JSON.stringify(obtenerDatos()));
            formData.set('auto_save', '1');

            mostrarEstado('Guardando...', '#f59e0b');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(resp => resp.text())
            .then(respText => {
                if (respText.includes('error:')) {
                    mostrarEstado('✗ Error al guardar', '#ef4444');
                    setTimeout(() => ocultarEstado(), 3000);
                } else {
                    mostrarEstado('✓ Guardado automático', '#10b981');
                    setTimeout(() => ocultarEstado(), 2000);
                }
            })
            .catch(err => {
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

            // Agregar listeners para auto-save
            preguntaDiv.querySelectorAll('input, textarea').forEach(inputEl => {
                inputEl.addEventListener('change', () => {
                    clearTimeout(timerAutoSave);
                    timerAutoSave = setTimeout(guardarAutomaticamente, 1500);
                });
            });
        };

        // Listeners para auto-save en campos principales
        ['titulo', 'descripcion', 'nivel', 'modulo_numero', 'leccion', 'puntaje_minimo'].forEach(fieldName => {
            const field = formEl.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.addEventListener('change', () => {
                    clearTimeout(timerAutoSave);
                    timerAutoSave = setTimeout(guardarAutomaticamente, 1500);
                });
            }
        });

        // Agregar primeros campos si no hay preguntas
        if (contenedorEl.children.length === 0) {
            agregarPregunta();
        }
    })();
    </script>
</div>
<?php endif; ?>

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

<?php
$resumenTodosResultados = (array)($resumen_todos_resultados ?? []);
?>

<?php if ($puedeGestionarEval && empty($evaluacionActiva) && !empty($resumenTodosResultados)): ?>
<div class="card report-card" style="padding:14px;margin-bottom:16px;">
    <h3 style="margin:0 0 10px 0;">Últimos intentos · Nivel <?= (int)$filtroNivelContexto ?> / Módulo <?= (int)$filtroModuloContexto ?></h3>
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
                    <th>Puntaje</th>
                    <th>Resultado</th>
                    <th>Detalle</th>
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
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($fechaFormato) ?></td>
                        <td><?= htmlspecialchars($nombrePersona) ?></td>
                        <td><?= htmlspecialchars((string)($resultado['Titulo'] ?? '')) ?></td>
                        <td><?= (int)($resultado['Nivel'] ?? 0) ?></td>
                        <td><?= (int)($resultado['Modulo_Numero'] ?? 0) ?></td>
                        <td><?= (int)($resultado['Intento_Numero'] ?? 0) ?></td>
                        <td><?= number_format($puntaje, 1, ',', '.') ?>%</td>
                        <td><span style="font-weight:bold;color:<?= $aprobado ? '#065f46' : '#b91c1c' ?>"><?= $resultadoText ?></span></td>
                        <td>
                            <?php if ($idEvalRes > 0 && $idResultadoRes > 0): ?>
                                <a class="btn btn-secondary btn-sm" href="<?= PUBLIC_URL ?>?url=programas/evaluaciones<?= $contextoQuery ?>&evaluacion=<?= $idEvalRes ?>&resultado=<?= $idResultadoRes ?>">Ver</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php elseif ($puedeGestionarEval && empty($evaluacionActiva) && empty($resumenTodosResultados)): ?>
<div class="card report-card" style="padding:14px;margin-bottom:16px;">
    <?php if ($filtroNivelContexto > 0 && $filtroModuloContexto > 0): ?>
        <small style="color:#637087;">No hay presentaciones registradas para Nivel <?= (int)$filtroNivelContexto ?> · Módulo <?= (int)$filtroModuloContexto ?> (último intento por evaluación).</small>
    <?php else: ?>
        <small style="color:#637087;">Abre <strong>Evaluaciones</strong> desde el material del nivel y módulo correspondiente para ver el resumen filtrado.</small>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($puedeGestionarEval && !empty($historialEvaluacion) && !empty($evaluacionActiva)): ?>
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
    const form = document.getElementById('formCrearEvaluacion');
    const estadoDiv = document.getElementById('estadoGuardado');
    const textoEstado = document.getElementById('textoEstado');
    
    if (!form) {
        return;
    }

    let timerGuardado = null;
    let guardandoAhora = false;

    function mostrarEstado(texto, color = '#10b981', duracion = 3000) {
        estadoDiv.style.display = 'block';
        estadoDiv.style.color = color;
        textoEstado.textContent = texto;
        
        if (duracion > 0) {
            clearTimeout(timerGuardado);
            timerGuardado = setTimeout(function() {
                estadoDiv.style.display = 'none';
            }, duracion);
        }
    }

    function guardarAutomaticamente() {
        if (guardandoAhora) {
            return;
        }

        guardandoAhora = true;
        mostrarEstado('⏳ Guardando...', '#f59e0b', 0);

        const formData = new FormData(form);
        formData.set('accion', 'crear_evaluacion');
        formData.set('auto_save', '1');

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            guardandoAhora = false;
            if (data.includes('exitosamente') || data.includes('success')) {
                mostrarEstado('✓ Guardado automático', '#10b981', 2000);
            } else if (data.includes('error') || data.includes('Error')) {
                mostrarEstado('✗ Error al guardar', '#ef4444', 3000);
                console.error('Error al guardar:', data);
            } else {
                mostrarEstado('✓ Guardado automático', '#10b981', 2000);
            }
        })
        .catch(error => {
            guardandoAhora = false;
            mostrarEstado('✗ Error de conexión', '#ef4444', 3000);
            console.error('Error:', error);
        });
    }

    form.querySelectorAll('input[type="text"], input[type="number"], input[type="date"], textarea, select').forEach(function(campo) {
        campo.addEventListener('change', function() {
            clearTimeout(timerGuardado);
            timerGuardado = setTimeout(guardarAutomaticamente, 2000);
        });

        if (campo.tagName === 'INPUT' && campo.type === 'text') {
            campo.addEventListener('input', function() {
                clearTimeout(timerGuardado);
                timerGuardado = setTimeout(guardarAutomaticamente, 3000);
            });
        }
    });

    const btnAgregarPregunta = document.getElementById('btnAgregarPregunta');
    const contenedorPreguntas = document.getElementById('contenedorPreguntas');
    
    if (btnAgregarPregunta && contenedorPreguntas) {
        const observer = new MutationObserver(function() {
            clearTimeout(timerGuardado);
            timerGuardado = setTimeout(guardarAutomaticamente, 1500);
            
            contenedorPreguntas.querySelectorAll('input, select').forEach(function(campo) {
                if (!campo.__autoSaveListener) {
                    campo.__autoSaveListener = true;
                    campo.addEventListener('change', function() {
                        clearTimeout(timerGuardado);
                        timerGuardado = setTimeout(guardarAutomaticamente, 2000);
                    });
                    
                    if (campo.tagName === 'INPUT' && campo.type === 'text') {
                        campo.addEventListener('input', function() {
                            clearTimeout(timerGuardado);
                            timerGuardado = setTimeout(guardarAutomaticamente, 3000);
                        });
                    }
                }
            });
        });

        observer.observe(contenedorPreguntas, {
            childList: true,
            subtree: true,
            characterData: true
        });
    }
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