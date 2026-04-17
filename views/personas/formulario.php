<?php include VIEWS . '/layout/header.php'; ?>

<?php
$returnTo = $return_to ?? ($_GET['return_to'] ?? ($_POST['return_to'] ?? ''));
$returnUrl = trim((string)($return_url ?? ($_GET['return_url'] ?? ($_POST['return_url'] ?? ''))));
$returnToAsistencia = $returnTo === 'asistencia';
$returnToCelulas = $returnTo === 'celulas';
$panelEventosProcesos = strtolower(trim((string)($_GET['panel'] ?? ($_POST['panel_eventos_procesos'] ?? ''))));
$modoSoloEventosProcesos = in_array($panelEventosProcesos, ['escalera', 'convenciones'], true);
$urlVolver = $returnUrl !== ''
    ? $returnUrl
    : ($returnToAsistencia
        ? (PUBLIC_URL . '?url=asistencias/registrar' . (!empty($celula_retorno) ? '&celula=' . (int)$celula_retorno : ''))
        : ($returnToCelulas ? (PUBLIC_URL . '?url=celulas') : (PUBLIC_URL . '?url=personas')));
?>

<div class="page-header">
    <h2><?= $modoSoloEventosProcesos ? 'Eventos y procesos' : ((isset($persona) ? 'Editar' : 'Nueva') . ' Persona') ?></h2>
    <a href="<?= $urlVolver ?>" class="btn btn-secondary">Volver</a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger" style="margin-bottom: 20px;">
    <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
</div>
<?php endif; ?>

<?php if ($modoSoloEventosProcesos): ?>
<div class="alert alert-info" style="margin-bottom: 20px;">
    <i class="bi bi-bullseye"></i> Estás en la vista enfocada de <strong>Eventos y procesos</strong>. Aquí solo verás esta sección para actualizar la escalera o las convenciones.
</div>
<?php endif; ?>

<div class="form-container <?= $modoSoloEventosProcesos ? 'modo-solo-eventos' : '' ?>">
    <form method="POST" id="persona_form">
        <?php if (isset($persona['Id_Persona'])): ?>
        <input type="hidden" name="id" value="<?= (int)$persona['Id_Persona'] ?>">
        <?php endif; ?>

        <?php if ($returnToAsistencia): ?>
        <input type="hidden" name="return_to" value="asistencia">
        <input type="hidden" name="celula_retorno" value="<?= (int)($celula_retorno ?? 0) ?>">
        <?php elseif ($returnToCelulas): ?>
        <input type="hidden" name="return_to" value="celulas">
        <?php endif; ?>
        <?php if ($returnUrl !== ''): ?>
        <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl, ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>

        <!-- Sección: Información Personal -->
        <div class="form-section">
            <h3 class="section-title">📋 Información Personal</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" 
                           value="<?= htmlspecialchars($post_data['nombre'] ?? $persona['Nombre'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="apellido">Apellido *</label>
                    <input type="text" id="apellido" name="apellido" class="form-control" 
                           value="<?= htmlspecialchars($post_data['apellido'] ?? $persona['Apellido'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tipo_documento">Tipo de Documento</label>
                    <?php $tipoDocumentoSeleccionado = $post_data['tipo_documento'] ?? ($persona['Tipo_Documento'] ?? ''); ?>
                    <select id="tipo_documento" name="tipo_documento" class="form-control">
                        <option value="">Seleccionar...</option>
                        <option value="Registro Civil" <?= $tipoDocumentoSeleccionado === 'Registro Civil' ? 'selected' : '' ?>>Registro Civil</option>
                        <option value="Cedula de Ciudadania" <?= $tipoDocumentoSeleccionado === 'Cedula de Ciudadania' ? 'selected' : '' ?>>Cédula de Ciudadanía</option>
                        <option value="Cedula Extranjera" <?= $tipoDocumentoSeleccionado === 'Cedula Extranjera' ? 'selected' : '' ?>>Cédula Extranjera</option>
                        <option value="Tarjeta de Identidad" <?= $tipoDocumentoSeleccionado === 'Tarjeta de Identidad' ? 'selected' : '' ?>>Tarjeta de Identidad</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="numero_documento">Número de Documento</label>
                    <input type="text" id="numero_documento" name="numero_documento" class="form-control" 
                           value="<?= htmlspecialchars($post_data['numero_documento'] ?? $persona['Numero_Documento'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control" 
                           value="<?= htmlspecialchars($post_data['fecha_nacimiento'] ?? $persona['Fecha_Nacimiento'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="edad">Edad</label>
                    <input type="number" id="edad" name="edad" class="form-control" min="0" max="120"
                           value="<?= htmlspecialchars($persona['Edad'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="genero">Género</label>
                    <select id="genero" name="genero" class="form-control">
                        <option value="">Seleccionar...</option>
                        <option value="Hombre" <?= isset($persona) && $persona['Genero'] == 'Hombre' ? 'selected' : '' ?>>Hombre</option>
                        <option value="Mujer" <?= isset($persona) && $persona['Genero'] == 'Mujer' ? 'selected' : '' ?>>Mujer</option>
                        <option value="Joven Hombre" <?= isset($persona) && $persona['Genero'] == 'Joven Hombre' ? 'selected' : '' ?>>Joven Hombre</option>
                        <option value="Joven Mujer" <?= isset($persona) && $persona['Genero'] == 'Joven Mujer' ? 'selected' : '' ?>>Joven Mujer</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Sección: Información de Contacto -->
        <div class="form-section">
            <h3 class="section-title">📞 Información de Contacto</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" class="form-control" 
                           value="<?= htmlspecialchars($persona['Telefono'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($persona['Email'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <input type="text" id="direccion" name="direccion" class="form-control" 
                           value="<?= htmlspecialchars($persona['Direccion'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="barrio">Barrio</label>
                    <input type="text" id="barrio" name="barrio" class="form-control" 
                           value="<?= htmlspecialchars($post_data['barrio'] ?? $persona['Barrio'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Sección: Información Ministerial -->
        <div class="form-section form-section-ministerial">
            <h3 class="section-title"><?= $modoSoloEventosProcesos ? '🎯 Eventos y procesos' : '⛪ Información Ministerial' ?></h3>
            
            <div class="bloque-ministerial-general">
                <div id="alerta_pastor" class="alert alert-info" style="display:none; margin-bottom: 15px;">
                    <i class="bi bi-shield-check"></i> <strong>⭐ Esta persona es un Líder (Pastor)</strong> - Será tratada como líder en el sistema y reportará actividades de liderazgo.
                </div>
                
                <div class="form-row">
                <div class="form-group autocomplete-wrapper">
                    <label for="celula_search">Célula</label>
                    <?php
                    $celulaNombreSeleccionada = $post_data['celula_search'] ?? (isset($persona) && !empty($persona['Id_Celula']) ? ($persona['Nombre_Celula'] ?? '') : '');
                    $celulaIdSeleccionada = $post_data['id_celula'] ?? (isset($persona) && !empty($persona['Id_Celula']) ? $persona['Id_Celula'] : '');
                    ?>
                    <input type="text" id="celula_search" class="form-control"
                           placeholder="Buscar célula..."
                           value="<?= htmlspecialchars((string)$celulaNombreSeleccionada) ?>"
                           autocomplete="off">
                    <input type="hidden" id="id_celula" name="id_celula"
                           value="<?= htmlspecialchars((string)$celulaIdSeleccionada) ?>">
                    <div id="celula_autocomplete" class="autocomplete-items"></div>
                    <small class="form-text text-muted">Escriba el nombre de la célula para buscarla y selecciónela de la lista</small>
                    <small id="celula_error" class="form-text text-danger" style="display:none;">Debes seleccionar una célula válida de la lista.</small>
                </div>

                <div class="form-group">
                    <?php if (AuthController::esAdministrador()): ?>
                        <label>Rol</label>
                        <div class="form-control" style="background:#f8f9fb; color:#5f6d84;">Se asigna desde el botón "Asignar usuario"</div>
                    <?php else: ?>
                        <label>Acceso de usuario</label>
                        <div class="form-control" style="background:#f8f9fb; color:#5f6d84;">Solo el administrador puede asignar usuario y rol.</div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="id_ministerio">Ministerio</label>
                    <?php $ministerioSeleccionado = $post_data['id_ministerio'] ?? ($persona['Id_Ministerio'] ?? ''); ?>
                    <select id="id_ministerio" name="id_ministerio" class="form-control">
                        <option value="">Sin ministerio</option>
                        <?php if (!empty($ministerios)): ?>
                            <?php foreach ($ministerios as $ministerio): ?>
                                <option value="<?= $ministerio['Id_Ministerio'] ?>" 
                                        <?= (string)$ministerioSeleccionado === (string)$ministerio['Id_Ministerio'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <option value="otro" <?= (string)$ministerioSeleccionado === 'otro' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group autocomplete-wrapper">
                    <label for="lider_search">Líder Asignado</label>
                    <?php
                    $liderNombreSeleccionado = $post_data['lider_search'] ?? (isset($persona) && !empty($persona['Id_Lider']) ? ($persona['Nombre_Lider'] ?? '') : '');
                    $liderIdSeleccionado = $post_data['id_lider'] ?? (isset($persona) && !empty($persona['Id_Lider']) ? $persona['Id_Lider'] : '');
                    ?>
                    <input type="text" id="lider_search" class="form-control" 
                           placeholder="Buscar líder..."
                           value="<?= htmlspecialchars((string)$liderNombreSeleccionado) ?>"
                           autocomplete="off">
                    <input type="hidden" id="id_lider" name="id_lider" 
                           value="<?= htmlspecialchars((string)$liderIdSeleccionado) ?>">
                    <div id="lider_autocomplete" class="autocomplete-items"></div>
                    <small class="form-text text-muted">Escriba el nombre del líder y selecciónelo de la lista</small>
                    <small id="lider_error" class="form-text text-danger" style="display:none;">Debes seleccionar un líder válido de la lista.</small>
                </div>

                <div class="form-group">
                    <label for="invitado_por">Invitado Por</label>
                    <input type="text" id="invitado_por" name="invitado_por" class="form-control" 
                           value="<?= htmlspecialchars($post_data['invitado_por'] ?? ($persona['Invitado_Por'] ?? '')) ?>"
                           placeholder="Nombre de quien lo invitó">
                </div>

                <div class="form-group">
                    <label for="tipo_reunion">Ganado en</label>
                    <?php
                    $tipoReunionRaw = $post_data['tipo_reunion'] ?? ($persona['Tipo_Reunion'] ?? '');
                    $tipoReunionNormalizado = strtolower(trim((string)$tipoReunionRaw));
                    $tipoReunionNormalizado = strtr($tipoReunionNormalizado, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
                    if (in_array($tipoReunionNormalizado, ['celula'], true)) {
                        $tipoReunionSeleccionado = 'Celula';
                    } elseif (in_array($tipoReunionNormalizado, ['domingo'], true)) {
                        $tipoReunionSeleccionado = 'Domingo';
                    } elseif (in_array($tipoReunionNormalizado, ['somos uno', 'somos_uno', 'viernes'], true)) {
                        $tipoReunionSeleccionado = 'Somos Uno';
                    } elseif (in_array($tipoReunionNormalizado, ['migrados'], true)) {
                        $tipoReunionSeleccionado = 'Migrados';
                    } elseif (in_array($tipoReunionNormalizado, ['otro', 'otros', 'asignados'], true)) {
                        $tipoReunionSeleccionado = 'Otros';
                    } else {
                        $tipoReunionSeleccionado = '';
                    }
                    $ganadoEnOtroObservacion = $post_data['ganado_en_otro_observacion'] ?? ($persona['Observacion_Ganado_En'] ?? '');
                    if ($ganadoEnOtroObservacion === '' && in_array((string)$tipoReunionRaw, ['Migrados', 'Asignados'], true)) {
                        $ganadoEnOtroObservacion = (string)$tipoReunionRaw;
                    }
                    ?>
                    <select id="tipo_reunion" name="tipo_reunion" class="form-control">
                        <option value="">Seleccionar...</option>
                        <option value="Domingo" <?= $tipoReunionSeleccionado === 'Domingo' ? 'selected' : '' ?>>Domingo</option>
                        <option value="Somos Uno" <?= $tipoReunionSeleccionado === 'Somos Uno' ? 'selected' : '' ?>>Somos Uno</option>
                        <option value="Celula" <?= $tipoReunionSeleccionado === 'Celula' ? 'selected' : '' ?>>Célula</option>
                        <option value="Migrados" <?= $tipoReunionSeleccionado === 'Migrados' ? 'selected' : '' ?>>Migrados</option>
                        <option value="Otros" <?= $tipoReunionSeleccionado === 'Otros' ? 'selected' : '' ?>>Otros</option>
                    </select>
                </div>

                <div class="form-group" id="ganado_en_otro_wrap" style="display:none;">
                    <label for="ganado_en_otro_observacion">Observaciones</label>
                    <textarea id="ganado_en_otro_observacion" name="ganado_en_otro_observacion" class="form-control" rows="3" placeholder="Describe dónde fue ganado o la observación necesaria..."><?= htmlspecialchars((string)$ganadoEnOtroObservacion) ?></textarea>
                    <small class="form-text text-muted">Este campo es obligatorio cuando seleccionas Otros.</small>
                </div>

                <?php
                $tipoPersonaSeleccionado = strtolower(trim((string)($post_data['tipo_persona'] ?? '')));
                if (!in_array($tipoPersonaSeleccionado, ['nueva', 'antigua'], true)) {
                    if (isset($persona['Es_Antiguo'])) {
                        $tipoPersonaSeleccionado = ((int)$persona['Es_Antiguo'] === 1) ? 'antigua' : 'nueva';
                    } else {
                        $tipoPersonaSeleccionado = isset($persona) ? 'antigua' : 'nueva';
                    }
                }
                ?>
                <div class="form-row">
                    <?php if (AuthController::esAdministrador()): ?>
                    <div class="form-group">
                        <label for="estado_cuenta">Estado de Cuenta</label>
                        <select id="estado_cuenta" name="estado_cuenta" class="form-control">
                            <?php $estadoCuentaSeleccionado = $post_data['estado_cuenta'] ?? ($persona['Estado_Cuenta'] ?? 'Activo'); ?>
                            <option value="Activo" <?= $estadoCuentaSeleccionado == 'Activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="Inactivo" <?= $estadoCuentaSeleccionado == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            <option value="Bloqueado" <?= $estadoCuentaSeleccionado == 'Bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                        </select>
                        <small class="form-text text-muted">
                            Solo las cuentas activas pueden iniciar sesión
                        </small>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Tipo de persona</label>
                        <div class="tipo-persona-options">
                            <label class="tipo-persona-item">
                                <input type="radio" name="tipo_persona" value="antigua" <?= $tipoPersonaSeleccionado === 'antigua' ? 'checked' : '' ?>>
                                Antigua
                            </label>
                            <label class="tipo-persona-item">
                                <input type="radio" name="tipo_persona" value="nueva" <?= $tipoPersonaSeleccionado === 'nueva' ? 'checked' : '' ?>>
                                Nueva
                            </label>
                        </div>
                        <small class="form-text text-muted">Marca si la persona es antigua o nueva.</small>
                    </div>
                </div>
            </div>
            </div>

            <?php if (!empty($soportaConvencion) || !empty($soportaProceso)): ?>
            <?php
            $convencionesDisponibles = [
                'Convencion Enero' => 'Convención Enero',
                'Convencion Mujeres' => 'Convención Mujeres',
                'Convencion Jovenes' => 'Convención Jóvenes',
                'Convencion Hombres' => 'Convención Hombres'
            ];
            $subprocesosEscaleraFormulario = [
                'Ganar' => ['Primer contacto', 'Asignación a líderes y ministerio', 'Fonovisita', 'Visita', 'Asignación a una célula', 'No se dispone'],
                'Consolidar' => ['Universidad de la Vida', 'Encuentro', 'Bautismo'],
                'Discipular' => ['Capacitación Destino Nivel 1 (Módulos 1 y 2)', 'Capacitación Destino Nivel 2 (Módulos 3 y 4)', 'Capacitación Destino Nivel 3 (Módulos 5 y 6)'],
                'Enviar' => ['Célula']
            ];
            $indicesChecklistEscaleraFormulario = [
                'Ganar' => [0, 1, 2, 3, 4, 5],
                'Consolidar' => [0, 1, 2],
                'Discipular' => [0, 1, 2],
                'Enviar' => [2]
            ];
            $procesoSeleccionado = $post_data['proceso'] ?? ($persona['Proceso'] ?? 'Ganar');
            $checklistFormulario = [
                'Ganar' => [false, false, false, false, false, false],
                'Consolidar' => [false, false, false],
                'Discipular' => [false, false, false],
                'Enviar' => [false, false, false],
                '_meta' => [
                    'no_disponible_observacion' => '',
                    'convenciones' => []
                ]
            ];
            $ordenEtapasFormulario = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
            $indiceProcesoFormulario = array_search((string)$procesoSeleccionado, $ordenEtapasFormulario, true);
            if ($indiceProcesoFormulario === false) {
                $indiceProcesoFormulario = 0;
                $procesoSeleccionado = 'Ganar';
            }
            foreach ($ordenEtapasFormulario as $idxEtapa => $etapaNombreTmp) {
                if ($idxEtapa < $indiceProcesoFormulario) {
                    $limite = min(3, count($checklistFormulario[$etapaNombreTmp]));
                    for ($iTmp = 0; $iTmp < $limite; $iTmp++) {
                        $checklistFormulario[$etapaNombreTmp][$iTmp] = true;
                    }
                } elseif ($idxEtapa === $indiceProcesoFormulario && isset($checklistFormulario[$etapaNombreTmp][0])) {
                    $checklistFormulario[$etapaNombreTmp][0] = true;
                }
            }

            $checklistFormularioRaw = $post_data['escalera_checklist'] ?? ($persona['Escalera_Checklist'] ?? '');
            if (is_string($checklistFormularioRaw) && trim($checklistFormularioRaw) !== '') {
                $checklistDecodificado = json_decode($checklistFormularioRaw, true);
                if (is_array($checklistDecodificado)) {
                    foreach (['Ganar', 'Consolidar', 'Discipular', 'Enviar'] as $etapaTmp) {
                        if (!empty($checklistDecodificado[$etapaTmp]) && is_array($checklistDecodificado[$etapaTmp])) {
                            foreach ($checklistFormulario[$etapaTmp] as $indiceTmp => $valorTmp) {
                                if (array_key_exists($indiceTmp, $checklistDecodificado[$etapaTmp])) {
                                    $checklistFormulario[$etapaTmp][$indiceTmp] = !empty($checklistDecodificado[$etapaTmp][$indiceTmp]);
                                }
                            }
                        }
                    }
                    if (!empty($checklistDecodificado['_meta']) && is_array($checklistDecodificado['_meta'])) {
                        $checklistFormulario['_meta']['no_disponible_observacion'] = (string)($checklistDecodificado['_meta']['no_disponible_observacion'] ?? '');
                        $checklistFormulario['_meta']['convenciones'] = array_values(array_unique(array_filter((array)($checklistDecodificado['_meta']['convenciones'] ?? []))));
                    }
                }
            }

            $convencionesSeleccionadasFormulario = [];
            if (!empty($post_data['convenciones']) && is_array($post_data['convenciones'])) {
                $convencionesSeleccionadasFormulario = array_values(array_unique(array_filter(array_map('strval', $post_data['convenciones']))));
            } elseif (!empty($checklistFormulario['_meta']['convenciones']) && is_array($checklistFormulario['_meta']['convenciones'])) {
                $convencionesSeleccionadasFormulario = array_values(array_unique(array_filter(array_map('strval', $checklistFormulario['_meta']['convenciones']))));
            } elseif (!empty($post_data['convencion'])) {
                $convencionesSeleccionadasFormulario = [(string)$post_data['convencion']];
            } elseif (!empty($persona['Convencion'])) {
                $convencionesSeleccionadasFormulario = [(string)$persona['Convencion']];
            }
            $checklistFormulario['_meta']['convenciones'] = $convencionesSeleccionadasFormulario;

            $tieneAsignacionLiderMinisterioFormulario = !empty($liderIdSeleccionado) && !empty($ministerioSeleccionado) && (string)$ministerioSeleccionado !== 'otro';
            $checklistFormulario['Ganar'][1] = $tieneAsignacionLiderMinisterioFormulario;
            $checklistFormularioJson = json_encode($checklistFormulario, JSON_UNESCAPED_UNICODE);
            $mostrarConvencionesPrimero = !empty($soportaConvencion);
            if ($panelEventosProcesos === 'escalera' && !empty($soportaProceso)) {
                $mostrarConvencionesPrimero = false;
            } elseif ($panelEventosProcesos === 'convenciones' && !empty($soportaConvencion)) {
                $mostrarConvencionesPrimero = true;
            }
            ?>
            <div class="form-row">
                <div class="form-group form-group-wide eventos-procesos-group" id="eventos-procesos">
                    <input type="hidden" id="panel_eventos_procesos" name="panel_eventos_procesos" value="<?= $mostrarConvencionesPrimero ? 'convenciones' : 'escalera' ?>">
                    <label>Eventos y procesos</label>
                    <input type="hidden" id="escalera_checklist_payload" name="escalera_checklist" value="<?= htmlspecialchars((string)$checklistFormularioJson, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" id="proceso" name="proceso" value="<?= htmlspecialchars((string)$procesoSeleccionado) ?>">

                    <div class="eventos-procesos-card">
                        <div class="eventos-procesos-tabs">
                            <?php if (!empty($soportaConvencion)): ?>
                                <button type="button" class="eventos-procesos-tab <?= $mostrarConvencionesPrimero ? 'active' : '' ?>" data-target="panel_convenciones">Convenciones</button>
                            <?php endif; ?>
                            <?php if (!empty($soportaProceso)): ?>
                                <button type="button" class="eventos-procesos-tab <?= !$mostrarConvencionesPrimero ? 'active' : '' ?>" data-target="panel_escalera">Escalera del Éxito</button>
                            <?php endif; ?>
                            <div class="eventos-proceso-badge">Proceso actual: <strong id="proceso_actual_badge"><?= htmlspecialchars((string)$procesoSeleccionado ?: 'Ganar') ?></strong></div>
                        </div>

                        <?php if (!empty($soportaConvencion)): ?>
                        <div class="eventos-tab-panel <?= $mostrarConvencionesPrimero ? 'active' : '' ?>" id="panel_convenciones">
                            <div class="eventos-check-grid">
                                <?php foreach ($convencionesDisponibles as $valorConvencion => $labelConvencion): ?>
                                    <?php $convencionMarcada = in_array($valorConvencion, $convencionesSeleccionadasFormulario, true); ?>
                                    <label class="eventos-check-item <?= $convencionMarcada ? 'checked' : '' ?>">
                                        <input type="checkbox" class="js-convencion-check" name="convenciones[]" value="<?= htmlspecialchars($valorConvencion) ?>" <?= $convencionMarcada ? 'checked' : '' ?>>
                                        <span><?= htmlspecialchars($labelConvencion) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <small class="form-text text-muted">Marca una o varias convenciones del año asociadas a esta persona.</small>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($soportaProceso)): ?>
                        <div class="eventos-tab-panel <?= !$mostrarConvencionesPrimero ? 'active' : '' ?>" id="panel_escalera">
                            <div class="eventos-stage-grid">
                                <?php foreach ($subprocesosEscaleraFormulario as $etapaNombre => $itemsEtapa): ?>
                                    <div class="eventos-stage-card eventos-stage-<?= strtolower($etapaNombre) ?>">
                                        <div class="eventos-stage-header"><?= htmlspecialchars($etapaNombre) ?></div>
                                        <div class="eventos-stage-body">
                                            <?php foreach ($itemsEtapa as $indiceItem => $nombreItem): ?>
                                                <?php
                                                $indiceChecklistReal = (int)($indicesChecklistEscaleraFormulario[$etapaNombre][$indiceItem] ?? $indiceItem);
                                                $estaMarcado = !empty($checklistFormulario[$etapaNombre][$indiceChecklistReal]);
                                                $esAsignacionAuto = $etapaNombre === 'Ganar' && in_array($indiceChecklistReal, [1, 4], true);
                                                ?>
                                                <label class="eventos-check-item <?= $estaMarcado ? 'checked' : '' ?> <?= $esAsignacionAuto ? 'disabled is-auto' : '' ?>">
                                                    <input type="checkbox"
                                                           class="js-escalera-form-check"
                                                           data-etapa="<?= htmlspecialchars($etapaNombre) ?>"
                                                           data-indice="<?= (int)$indiceChecklistReal ?>"
                                                           <?= $estaMarcado ? 'checked' : '' ?>
                                                           <?= $esAsignacionAuto ? 'disabled' : '' ?>>
                                                    <span><?= htmlspecialchars($nombreItem) ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="eventos-no-disponible-wrap" id="evento_no_disponible_wrap" style="<?= !empty($checklistFormulario['Ganar'][5]) ? '' : 'display:none;' ?>">
                                <label for="evento_no_disponible_observacion">Observación de "No se dispone"</label>
                                <textarea id="evento_no_disponible_observacion" class="form-control" rows="3" placeholder="Escribe por qué no se logró concretar esta persona..."><?= htmlspecialchars((string)($checklistFormulario['_meta']['no_disponible_observacion'] ?? '')) ?></textarea>
                                <small class="form-text text-muted">Este campo es obligatorio cuando marcas "No se dispone".</small>
                            </div>

                            <small class="form-text text-muted">Los checks de la escalera definen automáticamente el proceso actual de la persona.</small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sección: Petición de Oración -->
        <div class="form-section">
            <h3 class="section-title">🙏 Petición de Oración</h3>
            <div class="form-group">
                <label for="peticion">Petición</label>
                <textarea id="peticion" name="peticion" class="form-control" rows="4" placeholder="Escriba aquí la petición de oración..."><?= htmlspecialchars($post_data['peticion'] ?? $persona['Peticion'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Acceso al Sistema - Solo Administradores -->
        <?php if (AuthController::esAdministrador()): ?>
        <?php
        $rolSeleccionadoAdmin = (string)($post_data['id_rol'] ?? ($persona['Id_Rol'] ?? ''));
        $asignarUsuarioAbierto = !empty($post_data['asignar_usuario_activo']) || !empty($persona['Usuario']);
        ?>
        <div class="form-section" id="acceso_sistema_section">
            <h3 class="section-title">🔐 Acceso al Sistema</h3>
            <button type="button" id="btn_asignar_usuario_toggle" class="btn btn-secondary" style="margin-bottom: 15px;">
                Asignar usuario
            </button>

            <input type="hidden" name="asignar_usuario_activo" id="asignar_usuario_activo" value="<?= $asignarUsuarioAbierto ? '1' : '0' ?>">

            <div id="asignar_usuario_panel" style="display:<?= $asignarUsuarioAbierto ? 'block' : 'none' ?>;">
            <div class="form-row">
                <div class="form-group">
                    <label for="id_rol">Rol</label>
                    <select id="id_rol" name="id_rol" class="form-control">
                        <option value="">Sin rol</option>
                        <?php if (!empty($roles)): ?>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?= $rol['Id_Rol'] ?>" <?= (string)$rolSeleccionadoAdmin === (string)$rol['Id_Rol'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($rol['Nombre_Rol']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <small class="form-text text-muted">Solo el administrador puede asignar o cambiar el rol.</small>
                </div>
            </div>

            <div id="acceso_sistema_alerta" class="alert alert-warning" style="display:none; margin-bottom: 15px;">
                El acceso al sistema no está disponible para personas con rol Asistente.
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" class="form-control" 
                           value="<?= htmlspecialchars($persona['Usuario'] ?? '') ?>"
                           placeholder="Dejar vacío si no tendrá acceso al sistema">
                    <small class="form-text text-muted">
                        Si asigna un usuario, la persona podrá iniciar sesión en el sistema
                    </small>
                </div>

                <div class="form-group">
                    <label for="contrasena">
                        Contraseña <?= isset($persona) ? '(Dejar vacío para mantener la actual)' : '' ?>
                    </label>
                    <input type="password" id="contrasena" name="contrasena" class="form-control" 
                           placeholder="<?= isset($persona) ? 'Solo llenar si desea cambiar la contraseña' : 'Contraseña para acceso' ?>">
                    <small class="form-text text-muted">
                        Mínimo 6 caracteres
                    </small>
                </div>
            </div>

            <?php if (isset($persona) && !empty($persona['Ultimo_Acceso'])): ?>
            <div class="alert alert-info" style="margin-top: 15px;">
                <i class="bi bi-clock-history"></i> 
                <strong>Último acceso:</strong> 
                <?= date('d/m/Y H:i:s', strtotime($persona['Ultimo_Acceso'])) ?>
            </div>
            <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= $urlVolver ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<style>
.form-section {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.modo-solo-eventos .form-section:not(.form-section-ministerial),
.modo-solo-eventos .bloque-ministerial-general,
.modo-solo-eventos #alerta_pastor,
.modo-solo-eventos .section-title:not(.section-title) {
    display: none;
}

.modo-solo-eventos .form-section-ministerial {
    border-color: #dbe7fb;
    box-shadow: 0 4px 14px rgba(47,95,179,0.08);
}

.modo-solo-eventos .form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.section-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #667eea;
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
}

.form-section .form-row {
    margin-bottom: 0;
}

.form-section .form-group {
    margin-bottom: 20px;
}

.form-group-wide,
.eventos-procesos-group {
    flex: 0 0 100% !important;
    width: 100% !important;
    max-width: 100% !important;
    grid-column: 1 / -1 !important;
    display: block;
    scroll-margin-top: 90px;
}

.eventos-procesos-card {
    width: 100%;
    border: 1px solid #dfe6f3;
    border-radius: 12px;
    background: linear-gradient(180deg, #f9fbff 0%, #ffffff 100%);
    overflow: hidden;
}

.eventos-procesos-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    justify-content: flex-start;
    padding: 14px;
    background: #f3f6fd;
    border-bottom: 1px solid #e2e8f5;
}

.eventos-procesos-tab {
    border: 1px solid #cfd8ee;
    background: #fff;
    color: #355fa3;
    border-radius: 999px;
    padding: 8px 16px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.eventos-procesos-tab.active {
    background: #2f5fb3;
    color: #fff;
    border-color: #2f5fb3;
}

.eventos-proceso-badge {
    margin-left: auto;
    font-size: 0.92rem;
    color: #4d5f7a;
    background: #fff;
    border: 1px solid #d8e1f1;
    border-radius: 999px;
    padding: 7px 12px;
    white-space: nowrap;
}

@media (max-width: 900px) {
    .eventos-proceso-badge {
        width: 100%;
        margin-left: 0;
        text-align: center;
    }
}

.eventos-tab-panel {
    display: none;
    padding: 16px;
}

.eventos-tab-panel.active {
    display: block;
}

.eventos-check-grid,
.eventos-stage-body {
    display: grid;
    gap: 10px;
}

.eventos-check-grid {
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
}

.eventos-stage-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(260px, 1fr));
    gap: 14px;
}

@media (max-width: 900px) {
    .eventos-stage-grid {
        grid-template-columns: 1fr;
    }
}

.eventos-stage-card {
    border: 1px solid #dfe6f3;
    border-radius: 12px;
    background: #fff;
    overflow: hidden;
}

.eventos-stage-header {
    padding: 10px 12px;
    font-weight: 700;
    color: #244f93;
    background: #eef4ff;
    border-bottom: 1px solid #dfe6f3;
}

.eventos-stage-body {
    padding: 12px;
}

.eventos-check-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border: 1px solid #dce4f2;
    border-radius: 10px;
    background: #fff;
    cursor: pointer;
    transition: all 0.15s ease;
}

.eventos-check-item:hover {
    border-color: #9bb5e3;
    background: #f8fbff;
}

.eventos-check-item.checked {
    border-color: #2f5fb3;
    background: #eef4ff;
}

.eventos-check-item.disabled {
    opacity: 0.75;
    cursor: not-allowed;
}

.eventos-check-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.eventos-no-disponible-wrap {
    margin-top: 14px;
}

/* Autocompletar */
.autocomplete-wrapper {
    position: relative;
}

.autocomplete-items {
    position: absolute;
    border: 1px solid #d4d4d4;
    border-bottom: none;
    border-top: none;
    z-index: 99;
    top: 100%;
    left: 0;
    right: 0;
    max-height: 250px;
    overflow-y: auto;
    background: white;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.autocomplete-items div {
    padding: 10px;
    cursor: pointer;
    background-color: #fff;
    border-bottom: 1px solid #d4d4d4;
}

.autocomplete-items div:hover {
    background-color: #e9e9e9;
}

.autocomplete-active {
    background-color: #667eea !important;
    color: #ffffff;
}


.input-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.15) !important;
}

.text-danger {
    color: #dc3545 !important;
}

.tipo-persona-options {
    display: flex;
    gap: 16px;
    align-items: center;
    flex-wrap: wrap;
    margin-top: 6px;
}

.tipo-persona-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}

</style>

<script>
// Lista de células disponibles
const celulasDisponibles = [
    <?php if (!empty($celulas)): ?>
        <?php foreach ($celulas as $index => $celula): ?>
            {
                id: <?= $celula['Id_Celula'] ?>,
                nombre: "<?= htmlspecialchars(preg_replace('/\s+/', ' ', trim((string)($celula['Nombre_Celula'] ?? ''))), ENT_QUOTES) ?>"
            }<?= $index < count($celulas) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
    <?php endif; ?>
];

// Lista de líderes disponibles
const lideresDisponibles = [
    <?php if (!empty($personas_lideres)): ?>
        <?php foreach ($personas_lideres as $index => $lider): ?>
            {
                id: <?= $lider['Id_Persona'] ?>,
                nombre: "<?= htmlspecialchars(preg_replace('/\s+/', ' ', trim((string)($lider['Nombre'] ?? '') . ' ' . (string)($lider['Apellido'] ?? ''))), ENT_QUOTES) ?>"
            }<?= $index < count($personas_lideres) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
    <?php endif; ?>
];

// Control de acceso al sistema según rol seleccionado
const rolSelect = document.getElementById('id_rol');
const accesoSistemaSection = document.getElementById('acceso_sistema_section');
const accesoSistemaAlerta = document.getElementById('acceso_sistema_alerta');
const asignarUsuarioBtn = document.getElementById('btn_asignar_usuario_toggle');
const asignarUsuarioPanel = document.getElementById('asignar_usuario_panel');
const asignarUsuarioActivoInput = document.getElementById('asignar_usuario_activo');
const alertaPastor = document.getElementById('alerta_pastor');
const fechaNacimientoInput = document.getElementById('fecha_nacimiento');
const edadInput = document.getElementById('edad');
const liderSearchInput = document.getElementById('lider_search');
const liderHiddenInput = document.getElementById('id_lider');
const personaForm = document.getElementById('persona_form');
const celulaError = document.getElementById('celula_error');
const liderError = document.getElementById('lider_error');
const tipoReunionSelect = document.getElementById('tipo_reunion');
const ganadoEnOtroWrap = document.getElementById('ganado_en_otro_wrap');
const ganadoEnOtroInput = document.getElementById('ganado_en_otro_observacion');
const ministerioSelect = document.getElementById('id_ministerio');
const procesoHiddenInput = document.getElementById('proceso');
const checklistPayloadInput = document.getElementById('escalera_checklist_payload');
const procesoActualBadge = document.getElementById('proceso_actual_badge');
const panelEventosProcesosInput = document.getElementById('panel_eventos_procesos');
const tabsEventosProcesos = Array.from(document.querySelectorAll('.eventos-procesos-tab'));
const panelsEventosProcesos = Array.from(document.querySelectorAll('.eventos-tab-panel'));
const noDisponibleWrap = document.getElementById('evento_no_disponible_wrap');
const noDisponibleObservacionInput = document.getElementById('evento_no_disponible_observacion');
const etapasEventosProcesos = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];

function normalizarChecklistFormularioState(payload) {
    const base = {
        Ganar: [false, false, false, false, false, false],
        Consolidar: [false, false, false],
        Discipular: [false, false, false],
        Enviar: [false, false, false],
        _meta: {
            no_disponible_observacion: '',
            convenciones: []
        }
    };

    if (!payload || typeof payload !== 'object') {
        return base;
    }

    etapasEventosProcesos.forEach(etapa => {
        if (!Array.isArray(payload[etapa])) {
            return;
        }
        for (let i = 0; i < base[etapa].length; i++) {
            if (typeof payload[etapa][i] !== 'undefined') {
                base[etapa][i] = !!payload[etapa][i];
            }
        }
    });

    if (payload._meta && typeof payload._meta === 'object') {
        base._meta.no_disponible_observacion = String(payload._meta.no_disponible_observacion || '').trim();
        base._meta.convenciones = Array.isArray(payload._meta.convenciones) ? payload._meta.convenciones.map(String) : [];
    }

    return base;
}

let checklistFormularioState = normalizarChecklistFormularioState(null);
if (checklistPayloadInput && checklistPayloadInput.value) {
    try {
        checklistFormularioState = normalizarChecklistFormularioState(JSON.parse(checklistPayloadInput.value));
    } catch (e) {
        checklistFormularioState = normalizarChecklistFormularioState(null);
    }
}

function calcularProcesoDesdeChecklistFormulario(checklist) {
    if (checklist && checklist.Ganar && checklist.Ganar[5]) {
        return 'Ganar';
    }

    let consecutivas = 0;
    for (const etapa of etapasEventosProcesos) {
        const valores = Array.isArray(checklist[etapa]) ? checklist[etapa] : [];
        const completa = etapa === 'Ganar'
            ? (!!valores[0] && !!valores[1] && !!valores[2] && !!valores[3] && !!valores[4])
            : (!!valores[0] && !!valores[1] && !!valores[2]);
        if (!completa) {
            break;
        }
        consecutivas++;
    }

    if (consecutivas === 0) {
        return 'Ganar';
    }

    if (consecutivas >= etapasEventosProcesos.length) {
        return 'Enviar';
    }

    return etapasEventosProcesos[consecutivas];
}

function actualizarTabsEventosProcesos(tabActivo) {
    tabsEventosProcesos.forEach(tab => {
        const esActivo = tab === tabActivo;
        tab.classList.toggle('active', esActivo);
    });

    const panelObjetivo = tabActivo ? tabActivo.getAttribute('data-target') : '';
    panelsEventosProcesos.forEach(panel => {
        panel.classList.toggle('active', panel.id === panelObjetivo);
    });

    if (panelEventosProcesosInput) {
        panelEventosProcesosInput.value = panelObjetivo === 'panel_escalera' ? 'escalera' : 'convenciones';
    }
}

function actualizarAsignadoALiderChecklist() {
    if (!checklistFormularioState || !Array.isArray(checklistFormularioState.Ganar)) {
        return;
    }

    const celulaHiddenField = document.getElementById('id_celula');
    const tieneLider = !!(liderHiddenInput && String(liderHiddenInput.value || '').trim() !== '' && !liderSearchInput.disabled);
    const tieneCelula = !!(celulaHiddenField && String(celulaHiddenField.value || '').trim() !== '');
    const tieneMinisterio = !!(ministerioSelect && String(ministerioSelect.value || '').trim() !== '' && String(ministerioSelect.value || '').trim() !== 'otro');

    checklistFormularioState.Ganar[1] = tieneLider && tieneMinisterio;
    checklistFormularioState.Ganar[4] = tieneCelula;
    sincronizarEventosProcesos();
}

function sincronizarEventosProcesos() {
    if (!checklistFormularioState || typeof checklistFormularioState !== 'object') {
        return;
    }

    if (!checklistFormularioState._meta || typeof checklistFormularioState._meta !== 'object') {
        checklistFormularioState._meta = { no_disponible_observacion: '', convenciones: [] };
    }

    checklistFormularioState._meta.convenciones = Array.from(document.querySelectorAll('.js-convencion-check:checked')).map(cb => cb.value);
    checklistFormularioState._meta.no_disponible_observacion = noDisponibleObservacionInput ? String(noDisponibleObservacionInput.value || '').trim() : '';

    const noDisponibleMarcado = !!(checklistFormularioState.Ganar && checklistFormularioState.Ganar[5]);
    if (noDisponibleWrap) {
        noDisponibleWrap.style.display = noDisponibleMarcado ? '' : 'none';
    }
    if (!noDisponibleMarcado && noDisponibleObservacionInput) {
        noDisponibleObservacionInput.value = '';
        checklistFormularioState._meta.no_disponible_observacion = '';
    }

    document.querySelectorAll('.js-escalera-form-check').forEach(function(checkbox) {
        const etapa = checkbox.getAttribute('data-etapa');
        const indice = Number(checkbox.getAttribute('data-indice') || -1);
        const checked = !!(checklistFormularioState[etapa] && checklistFormularioState[etapa][indice]);
        const esAsignacionAuto = etapa === 'Ganar' && (indice === 1 || indice === 4);
        const disabledByNoDisponible = noDisponibleMarcado && !(etapa === 'Ganar' && indice === 5) && !esAsignacionAuto;

        checkbox.checked = checked;
        checkbox.disabled = esAsignacionAuto || disabledByNoDisponible;

        const item = checkbox.closest('.eventos-check-item');
        if (item) {
            item.classList.toggle('checked', checked);
            item.classList.toggle('disabled', checkbox.disabled);
        }
    });

    document.querySelectorAll('.js-convencion-check').forEach(function(checkbox) {
        const item = checkbox.closest('.eventos-check-item');
        if (item) {
            item.classList.toggle('checked', checkbox.checked);
        }
    });

    const procesoCalculado = calcularProcesoDesdeChecklistFormulario(checklistFormularioState);
    if (procesoHiddenInput) {
        procesoHiddenInput.value = procesoCalculado;
    }
    if (procesoActualBadge) {
        procesoActualBadge.textContent = procesoCalculado;
    }
    if (checklistPayloadInput) {
        checklistPayloadInput.value = JSON.stringify(checklistFormularioState);
    }
}

function calcularEdadDesdeFecha(fechaTexto) {
    if (!fechaTexto) {
        return '';
    }

    const fechaNac = new Date(fechaTexto + 'T00:00:00');
    if (Number.isNaN(fechaNac.getTime())) {
        return '';
    }

    const hoy = new Date();
    let edad = hoy.getFullYear() - fechaNac.getFullYear();
    const mes = hoy.getMonth() - fechaNac.getMonth();

    if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNac.getDate())) {
        edad--;
    }

    if (edad < 0) {
        return '';
    }

    return edad;
}

function actualizarEdadAutomatica() {
    if (!fechaNacimientoInput || !edadInput) {
        return;
    }

    const edadCalculada = calcularEdadDesdeFecha(fechaNacimientoInput.value);
    edadInput.value = edadCalculada;
}

function normalizarTexto(texto) {
    if (!texto) return '';
    return texto
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function rolSeleccionadoEsAsistente() {
    if (!rolSelect || rolSelect.selectedIndex < 0) {
        return false;
    }

    const option = rolSelect.options[rolSelect.selectedIndex];
    const textoRol = normalizarTexto(option ? option.text : '');
    return textoRol.includes('asistente');
}

function rolSeleccionadoEsPastor() {
    if (!rolSelect || rolSelect.selectedIndex < 0) {
        return false;
    }

    const option = rolSelect.options[rolSelect.selectedIndex];
    const textoRol = normalizarTexto(option ? option.text : '');
    return textoRol.includes('pastor');
}
function obtenerCoincidenciaExacta(items, valor) {
    const texto = normalizarTexto(valor);
    if (!texto) {
        return null;
    }

    return items.find(item => normalizarTexto(item.nombre) === texto) || null;
}

function marcarCampoInvalido(input, errorEl, mensaje) {
    if (input) {
        input.classList.add('input-invalid');
    }
    if (errorEl) {
        if (mensaje) {
            errorEl.textContent = mensaje;
        }
        errorEl.style.display = 'block';
    }
}

function limpiarCampoInvalido(input, errorEl) {
    if (input) {
        input.classList.remove('input-invalid');
    }
    if (errorEl) {
        errorEl.style.display = 'none';
    }
}

function sincronizarSeleccionAutocomplete(input, hidden, items, errorEl, etiqueta) {
    if (!input || !hidden) {
        return true;
    }

    const valor = (input.value || '').trim();
    if (valor === '') {
        hidden.value = '';
        limpiarCampoInvalido(input, errorEl);
        if (typeof actualizarAsignadoALiderChecklist === 'function') {
            actualizarAsignadoALiderChecklist();
        }
        return true;
    }

    const coincidencia = obtenerCoincidenciaExacta(items, valor);
    if (coincidencia) {
        input.value = coincidencia.nombre;
        hidden.value = coincidencia.id;
        limpiarCampoInvalido(input, errorEl);
        if (typeof actualizarAsignadoALiderChecklist === 'function') {
            actualizarAsignadoALiderChecklist();
        }
        return true;
    }

    hidden.value = '';
    marcarCampoInvalido(input, errorEl, 'Debes seleccionar ' + etiqueta + ' válida de la lista.');
    if (typeof actualizarAsignadoALiderChecklist === 'function') {
        actualizarAsignadoALiderChecklist();
    }
    return false;
}


function actualizarAccesoSistemaPorRol() {
    if (!accesoSistemaSection || !asignarUsuarioPanel) {
        return;
    }

    const panelAbierto = asignarUsuarioPanel.style.display !== 'none';
    const esAsistente = rolSeleccionadoEsAsistente();
    const rolAsignado = rolSelect && rolSelect.value !== '';
    const camposAcceso = [
        document.getElementById('usuario'),
        document.getElementById('contrasena')
    ].filter(Boolean);

    if (rolSelect) {
        rolSelect.disabled = !panelAbierto;
    }

    if (!panelAbierto) {
        if (asignarUsuarioActivoInput) {
            asignarUsuarioActivoInput.value = '0';
        }
        if (accesoSistemaAlerta) {
            accesoSistemaAlerta.style.display = 'none';
        }
        camposAcceso.forEach(campo => {
            campo.disabled = true;
        });
        return;
    }

    if (asignarUsuarioActivoInput) {
        asignarUsuarioActivoInput.value = '1';
    }

    if (!rolAsignado || esAsistente) {
        if (accesoSistemaAlerta) {
            accesoSistemaAlerta.textContent = !rolAsignado
                ? 'Asigne un rol para habilitar el acceso al sistema.'
                : 'El acceso al sistema no está disponible para personas con rol Asistente.';
            accesoSistemaAlerta.style.display = 'block';
        }

        camposAcceso.forEach(campo => {
            campo.disabled = true;
            if (campo.id === 'usuario' || campo.id === 'contrasena') {
                campo.value = '';
            }
        });
        return;
    }

    if (accesoSistemaAlerta) {
        accesoSistemaAlerta.style.display = 'none';
    }
    camposAcceso.forEach(campo => {
        campo.disabled = false;
    });
}

function actualizarFormularioPorRolPastor() {
    const esPastor = rolSeleccionadoEsPastor();
    
    // Mostrar/ocultar indicador de Pastor
    if (alertaPastor) {
        alertaPastor.style.display = esPastor ? 'block' : 'none';
    }
    
    // Desabilitar/habilitar campo Líder Asignado
    if (liderSearchInput) {
        if (esPastor) {
            liderSearchInput.disabled = true;
            liderSearchInput.value = '';
            liderSearchInput.placeholder = 'No aplica (Esta persona es un Líder)';
        } else {
            liderSearchInput.disabled = false;
            liderSearchInput.placeholder = 'Buscar líder...';
        }
    }
    
    if (esPastor && liderHiddenInput) {
        liderHiddenInput.value = '';
        limpiarCampoInvalido(liderSearchInput, liderError);
    }

    if (!esPastor) {
        limpiarCampoInvalido(liderSearchInput, liderError);
    }

    actualizarAsignadoALiderChecklist();
}

if (rolSelect) {
    rolSelect.addEventListener('change', actualizarAccesoSistemaPorRol);
    rolSelect.addEventListener('change', actualizarFormularioPorRolPastor);
}

if (asignarUsuarioBtn && asignarUsuarioPanel) {
    asignarUsuarioBtn.addEventListener('click', function() {
        const panelAbierto = asignarUsuarioPanel.style.display !== 'none';
        asignarUsuarioPanel.style.display = panelAbierto ? 'none' : 'block';
        actualizarAccesoSistemaPorRol();
    });
}

actualizarAccesoSistemaPorRol();
actualizarFormularioPorRolPastor();

if (ministerioSelect) {
    ministerioSelect.addEventListener('change', function() {
        actualizarAsignadoALiderChecklist();
    });
}

if (tabsEventosProcesos.length) {
    tabsEventosProcesos.forEach(function(tab) {
        tab.addEventListener('click', function() {
            actualizarTabsEventosProcesos(tab);
        });
    });
}

document.querySelectorAll('.js-convencion-check').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        sincronizarEventosProcesos();
    });
});

document.querySelectorAll('.js-escalera-form-check').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const etapa = checkbox.getAttribute('data-etapa');
        const indice = Number(checkbox.getAttribute('data-indice') || -1);
        if (!etapa || indice < 0 || !Array.isArray(checklistFormularioState[etapa])) {
            return;
        }
        checklistFormularioState[etapa][indice] = !!checkbox.checked;
        sincronizarEventosProcesos();
    });
});

if (noDisponibleObservacionInput) {
    noDisponibleObservacionInput.addEventListener('input', function() {
        sincronizarEventosProcesos();
    });
}

sincronizarEventosProcesos();

if (fechaNacimientoInput && edadInput) {
    fechaNacimientoInput.addEventListener('change', actualizarEdadAutomatica);
    fechaNacimientoInput.addEventListener('input', actualizarEdadAutomatica);
    actualizarEdadAutomatica();
}

function actualizarCampoGanadoEnOtro() {
    if (!tipoReunionSelect || !ganadoEnOtroWrap || !ganadoEnOtroInput) {
        return;
    }

    const esOtros = String(tipoReunionSelect.value || '') === 'Otros';
    ganadoEnOtroWrap.style.display = esOtros ? '' : 'none';
    ganadoEnOtroInput.required = esOtros;
}

if (tipoReunionSelect) {
    tipoReunionSelect.addEventListener('change', actualizarCampoGanadoEnOtro);
    actualizarCampoGanadoEnOtro();
}

function inicializarMayusculasAutomaticas() {
    const campos = document.querySelectorAll('input[type="text"], textarea');
    campos.forEach(function(campo) {
        if (!campo || campo.id === 'usuario') {
            return;
        }

        campo.style.textTransform = 'uppercase';

        const transformar = function() {
            if (typeof campo.value === 'string') {
                campo.value = campo.value.toUpperCase();
            }
        };

        campo.addEventListener('input', transformar);
        campo.addEventListener('change', transformar);
        transformar();
    });
}

inicializarMayusculasAutomaticas();

// Autocompletar para célula
const celulaInput = document.getElementById('celula_search');
const celulaHidden = document.getElementById('id_celula');
const celulaAutocomplete = document.getElementById('celula_autocomplete');
let currentFocusCelula = -1;

celulaInput.addEventListener('input', function() {
    const value = this.value;
    closeAllLists();
    
    limpiarCampoInvalido(celulaInput, celulaError);

    if (!value) {
        celulaHidden.value = '';
        return false;
    }

    const coincidenciaExacta = obtenerCoincidenciaExacta(celulasDisponibles, value);
    if (!coincidenciaExacta || String(coincidenciaExacta.id) !== String(celulaHidden.value || '')) {
        celulaHidden.value = '';
    }
    
    currentFocusCelula = -1;
    
    const textoBusqueda = normalizarTexto(value);
    const filtrados = celulasDisponibles.filter(celula => 
        normalizarTexto(celula.nombre).includes(textoBusqueda)
    );
    
    if (filtrados.length === 0) {
        const div = document.createElement('div');
        div.innerHTML = '<em>No se encontraron células</em>';
        div.style.fontStyle = 'italic';
        div.style.color = '#999';
        celulaAutocomplete.appendChild(div);
        return;
    }
    
    filtrados.forEach(celula => {
        const div = document.createElement('div');
        const nombre = celula.nombre;
        const index = nombre.toLowerCase().indexOf(value.toLowerCase());
        
        if (index >= 0) {
            div.innerHTML = nombre.substr(0, index) + 
                          '<strong>' + nombre.substr(index, value.length) + '</strong>' + 
                          nombre.substr(index + value.length);
        } else {
            div.innerHTML = nombre;
        }
        
        div.addEventListener('click', function() {
            celulaInput.value = nombre;
            celulaHidden.value = celula.id;
            limpiarCampoInvalido(celulaInput, celulaError);
            closeAllLists();
        });
        
        celulaAutocomplete.appendChild(div);
    });
});

celulaInput.addEventListener('keydown', function(e) {
    let items = celulaAutocomplete.getElementsByTagName('div');
    
    if (e.keyCode === 40) { // Down
        currentFocusCelula++;
        addActive(items, currentFocusCelula);
    } else if (e.keyCode === 38) { // Up
        currentFocusCelula--;
        addActive(items, currentFocusCelula);
    } else if (e.keyCode === 13) { // Enter
        e.preventDefault();
        if (currentFocusCelula > -1) {
            if (items) items[currentFocusCelula].click();
        }
    }
});

celulaInput.addEventListener('blur', function() {
    setTimeout(() => {
        sincronizarSeleccionAutocomplete(celulaInput, celulaHidden, celulasDisponibles, celulaError, 'una célula');
    }, 200);
});

// Autocompletar para líder
const liderInput = document.getElementById('lider_search');
const liderHidden = document.getElementById('id_lider');
const liderAutocomplete = document.getElementById('lider_autocomplete');
let currentFocus = -1;

liderInput.addEventListener('input', function() {
    const value = this.value;
    closeAllLists();
    
    limpiarCampoInvalido(liderInput, liderError);

    if (!value) {
        liderHidden.value = '';
        return false;
    }

    const coincidenciaExacta = obtenerCoincidenciaExacta(lideresDisponibles, value);
    if (!coincidenciaExacta || String(coincidenciaExacta.id) !== String(liderHidden.value || '')) {
        liderHidden.value = '';
    }
    
    currentFocus = -1;
    
    const textoBusqueda = normalizarTexto(value);
    const filtrados = lideresDisponibles.filter(lider => 
        normalizarTexto(lider.nombre).includes(textoBusqueda)
    );
    
    if (filtrados.length === 0) {
        const div = document.createElement('div');
        div.innerHTML = '<em>No se encontraron líderes</em>';
        div.style.fontStyle = 'italic';
        div.style.color = '#999';
        liderAutocomplete.appendChild(div);
        return;
    }
    
    filtrados.forEach(lider => {
        const div = document.createElement('div');
        const nombre = lider.nombre;
        const index = nombre.toLowerCase().indexOf(value.toLowerCase());
        
        if (index >= 0) {
            div.innerHTML = nombre.substr(0, index) + 
                          '<strong>' + nombre.substr(index, value.length) + '</strong>' + 
                          nombre.substr(index + value.length);
        } else {
            div.innerHTML = nombre;
        }
        
        div.addEventListener('click', function() {
            liderInput.value = nombre;
            liderHidden.value = lider.id;
            limpiarCampoInvalido(liderInput, liderError);
            closeAllLists();
        });
        
        liderAutocomplete.appendChild(div);
    });
});

liderInput.addEventListener('keydown', function(e) {
    let items = liderAutocomplete.getElementsByTagName('div');
    
    if (e.keyCode === 40) { // Down
        currentFocus++;
        addActive(items, currentFocus);
    } else if (e.keyCode === 38) { // Up
        currentFocus--;
        addActive(items, currentFocus);
    } else if (e.keyCode === 13) { // Enter
        e.preventDefault();
        if (currentFocus > -1) {
            if (items) items[currentFocus].click();
        }
    }
});

function addActive(items, focusIndex) {
    if (!items) return false;
    removeActive(items);
    if (focusIndex >= items.length) focusIndex = 0;
    if (focusIndex < 0) focusIndex = (items.length - 1);
    items[focusIndex].classList.add('autocomplete-active');
}

function removeActive(items) {
    for (let i = 0; i < items.length; i++) {
        items[i].classList.remove('autocomplete-active');
    }
}

function closeAllLists(elmnt) {
    const items = document.getElementsByClassName('autocomplete-items');
    for (let i = 0; i < items.length; i++) {
        if (elmnt !== items[i] && elmnt !== liderInput && elmnt !== celulaInput) {
            items[i].innerHTML = '';
        }
    }
}

// Cerrar al hacer click fuera
document.addEventListener('click', function(e) {
    if (e.target !== liderInput && e.target !== celulaInput) {
        closeAllLists(e.target);
    }
});

liderInput.addEventListener('blur', function() {
    setTimeout(() => {
        if (liderInput.disabled) {
            liderHidden.value = '';
            limpiarCampoInvalido(liderInput, liderError);
            return;
        }
        sincronizarSeleccionAutocomplete(liderInput, liderHidden, lideresDisponibles, liderError, 'un líder');
    }, 200);
});

if (personaForm) {
    personaForm.addEventListener('submit', function(e) {
        sincronizarEventosProcesos();
        let valido = true;

        if (celulaInput && !sincronizarSeleccionAutocomplete(celulaInput, celulaHidden, celulasDisponibles, celulaError, 'una célula')) {
            valido = false;
        }

        if (liderInput && !liderInput.disabled && !sincronizarSeleccionAutocomplete(liderInput, liderHidden, lideresDisponibles, liderError, 'un líder')) {
            valido = false;
        }

        if (!valido) {
            e.preventDefault();
            const primerInvalido = document.querySelector('.input-invalid');
            if (primerInvalido) {
                primerInvalido.focus();
            }
        }
    });
}
</script>

<?php include VIEWS . '/layout/footer.php'; ?>


