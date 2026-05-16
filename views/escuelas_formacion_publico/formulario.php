<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $documentTitle = htmlspecialchars((string)($programa_label ?? 'Inscripción Escuelas de Formación')) ?>
    <title><?= $documentTitle ?> - MCI Madrid</title>
    <style>
        :root {
            --primary: #0a6e6a;
            --primary-dark: #075552;
            --primary-soft: #e8f6f4;
            --text-main: #2f3b3a;
            --text-title: #1e2d2b;
            --border: #d1e6e3;
            --danger-bg: #fff1f1;
            --danger-text: #9c3434;
            --success-bg: #ecf8ef;
            --success-text: #1f7a3c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #f1f8f7 0%, #e6f1ef 100%);
            color: var(--text-main);
            min-height: 100vh;
            padding: 20px 12px;
        }

        .container {
            max-width: 760px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 14px 30px rgba(15, 77, 74, 0.15);
        }

        .header {
            padding: 26px 22px;
            background: linear-gradient(135deg, var(--primary-soft) 0%, #ffffff 100%);
            border-bottom: 1px solid var(--border);
        }

        .eyebrow {
            margin: 0 0 8px;
            color: var(--primary);
            font-weight: 700;
            letter-spacing: 0.3px;
            font-size: 15px;
        }

        h1 {
            margin: 0;
            color: var(--text-title);
            font-size: 30px;
            line-height: 1.2;
        }

        .sub {
            margin: 10px 0 0;
            color: var(--text-main);
            font-size: 15px;
        }

        .body {
            padding: 24px 22px;
        }

        .alert {
            border-radius: 10px;
            padding: 12px 14px;
            margin: 0 0 16px;
            border: 1px solid transparent;
            font-size: 14px;
        }

        .alert.error {
            background: var(--danger-bg);
            color: var(--danger-text);
            border-color: #f7d7d7;
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success-text);
            border-color: #cceacd;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .section {
            margin-bottom: 16px;
            border: 1px solid #dcebea;
            border-radius: 12px;
            padding: 14px;
            background: #fcfefe;
        }

        .section-title {
            margin: 0 0 10px;
            font-size: 15px;
            color: #1f3d3a;
            font-weight: 700;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            color: var(--text-title);
            font-weight: 600;
            font-size: 14px;
        }

        .req {
            color: #d45a5a;
        }

        input,
        select {
            border: 1px solid #d2e4e1;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 15px;
            color: #384a48;
            background: #fff;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        input:focus,
        select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(10, 110, 106, 0.14);
        }

        .help {
            margin: 0 0 14px;
            font-size: 13px;
            color: #667775;
            background: #f5fbfa;
            border: 1px dashed #cae4df;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .hint {
            margin-top: 10px;
            font-size: 13px;
            color: #758280;
        }

        .abono-lock-box {
            border: 1px dashed #c8dfdc;
            background: #f6fbfa;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 12px;
        }

        .abono-lock-status {
            margin-top: 8px;
            font-size: 12px;
            color: #5c6f6d;
        }

        .abono-lock-status.ok {
            color: #1f7a3c;
            font-weight: 600;
        }

        .abono-lock-status.err {
            color: #9c3434;
            font-weight: 600;
        }

        .actions {
            margin-top: 18px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            border: none;
            border-radius: 10px;
            padding: 12px 18px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            color: #fff;
            background: linear-gradient(135deg, var(--primary) 0%, #0f8b86 100%);
            box-shadow: 0 10px 20px rgba(10, 110, 106, 0.2);
        }

        .btn:hover {
            filter: brightness(0.98);
        }

        .btn:active {
            transform: translateY(1px);
        }

        .btn-secondary {
            color: #2a5a56;
            background: #eef7f6;
            border: 1px solid #c8dfdc;
            box-shadow: none;
        }

        .btn-secondary:hover {
            background: #e4f1ef;
        }

        .success-box {
            border: 1px solid #b8e2c6;
            background: #f3fbf5;
            border-radius: 12px;
            padding: 18px;
        }

        .success-actions {
            margin-top: 14px;
        }

        .loader {
            display: none;
            margin-top: 8px;
            font-size: 13px;
            color: #54706d;
        }

        .loader.active {
            display: block;
        }

        .toast {
            position: fixed;
            left: 50%;
            bottom: 22px;
            transform: translateX(-50%) translateY(10px);
            background: #1f4f4c;
            color: #fff;
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.22);
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease, transform .2s ease;
            z-index: 1200;
        }

        .toast.active {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .search-status {
            display: none;
            margin-top: 8px;
            font-size: 13px;
            border-radius: 8px;
            padding: 10px 12px;
            border: 1px solid transparent;
        }

        .search-status.active {
            display: block;
        }

        .search-status.info {
            background: #eef7ff;
            border-color: #c8dff8;
            color: #245384;
        }

        .search-status.warn {
            background: #fff7e9;
            border-color: #f4db9b;
            color: #845500;
        }

        .search-status.error {
            background: #fff2f2;
            border-color: #f1cccc;
            color: #8b3a3a;
        }

        .persona-resumen {
            display: none;
            margin-top: 12px;
            border: 1px solid #cde0dd;
            background: #f5fbfa;
            border-radius: 10px;
            padding: 10px 12px;
        }

        .persona-resumen.active {
            display: block;
        }

        .persona-resumen strong {
            color: #1f3d3a;
        }

        .autocomplete-wrap {
            position: relative;
        }

        .autocomplete-list {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            max-height: 220px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #d2e4e1;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            z-index: 20;
        }

        .autocomplete-list.active {
            display: block;
        }

        .autocomplete-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #edf5f4;
            font-size: 14px;
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .autocomplete-item:hover {
            background: #f5fbfa;
        }

        .insc-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            border: 1px solid #dcebea;
            border-radius: 10px;
            margin-bottom: 8px;
            background: #f8fdfc;
        }
        .insc-card:last-child { margin-bottom: 0; }
        .insc-info { display: flex; flex-direction: column; gap: 4px; }
        .insc-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-block;
        }
        .insc-badge.asistio { background: #d6f0de; color: #1a6c33; }
        .insc-badge.pendiente { background: #fff4e0; color: #8a6200; }
        .btn-asistencia {
            font-size: 13px;
            padding: 8px 12px;
            white-space: nowrap;
            flex-shrink: 0;
        }
        @media (max-width: 720px) {
            h1 {
                font-size: 24px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .actions {
                justify-content: stretch;
            }

            .btn {
                width: 100%;
            }

            .insc-card { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="container">
    <?php
    $programaActualFormulario = trim((string)($programa_actual ?? ''));
    $subtituloPrograma = 'Registra personas para Universidad de la Vida o Capacitación Destino.';
    if ($programaActualFormulario === 'universidad_vida') {
        $subtituloPrograma = 'Registra personas para Universidad de la Vida.';
    } elseif ($programaActualFormulario === 'capacitacion_destino') {
        $subtituloPrograma = 'Registra personas para Capacitación Destino.';
    }
    ?>
    <div class="header">
        <p class="eyebrow">Escuelas de Formación</p>
        <h1><?= htmlspecialchars((string)($programa_label ?? 'Inscripción pública')) ?></h1>
        <p class="sub"><?= htmlspecialchars($subtituloPrograma) ?></p>
        <div style="margin-top:14px; display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn btn-secondary" href="<?= PUBLIC_URL ?>?url=escuelas_formacion/codigos&programa=<?= urlencode($programaActualFormulario) ?>" target="_blank" rel="noopener">Descarga QR registro</a>
        </div>
    </div>

    <div class="body">
        <?php
        $abonoAuth = is_array($abono_auth ?? null) ? $abono_auth : ['autorizado' => false, 'nombre' => ''];
        $modoAbono = !empty($modo_abono);
        $modoPagosUrl = isset($_GET['modo']) && (string)$_GET['modo'] === 'pagos';
        $relajarValidacionTipoDocumento = $modoAbono || $modoPagosUrl;
        $abonoAutorizado = !empty($abonoAuth['autorizado']);
        $abonoNombreAuth = (string)($abonoAuth['nombre'] ?? '');
        $tipoPagoOld = (string)($old['tipo_pago'] ?? 'abono');
        if (!in_array($tipoPagoOld, ['abono', 'completo'], true)) {
            $tipoPagoOld = 'abono';
        }
        $rutaRegistroNuevo = 'escuelas_formacion/registro-publico/universidad-vida';
        $programaActualFormulario = trim((string)($programa_actual ?? ''));
        if ($programaActualFormulario === 'universidad_vida') {
            $rutaRegistroNuevo = 'escuelas_formacion/registro-publico/universidad-vida';
        } elseif ($programaActualFormulario === 'capacitacion_destino') {
            $rutaRegistroNuevo = 'escuelas_formacion/registro-publico/capacitacion-destino';
        }
        ?>

        <?php if (!empty($mensaje)): ?>
            <div class="alert <?= ($tipo_mensaje ?? '') === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars((string)$mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($registro_exitoso)): ?>
            <div class="success-box">
                <strong>Registro completado.</strong>
                <?php if (!empty($referencia_pago)): ?>
                    <p style="margin:12px 0 0;">Número de referencia de pago:</p>
                    <p style="margin:4px 0 0; font-size:22px; font-weight:800; letter-spacing:3px; font-family:monospace; color:var(--primary);"><?= htmlspecialchars((string)$referencia_pago) ?></p>
                    <p style="margin:4px 0 12px; font-size:12px; color:#667775;">Guarda este código como comprobante de pago.</p>
                <?php endif; ?>
                <div style="margin:12px 0; padding:10px 12px; border:1px dashed #b7d7d4; border-radius:10px; background:#f7fcfb;">
                    <div style="font-size:13px; color:#45615e; margin-bottom:6px;"><strong>Pago de material:</strong> queda guardado en la inscripción y visible en el ticket imprimible.</div>
                    <a class="btn" href="<?= PUBLIC_URL ?>?url=escuelas_formacion/registro-publico/ticket" style="display:inline-block; text-decoration:none;">Ver / imprimir ticket</a>
                </div>
                <div class="success-actions">
                    <a class="btn" href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaRegistroNuevo) ?>" style="display:inline-block; text-decoration:none;">Registrar otra persona</a>
                </div>
            </div>
        <?php else: ?>
            <?php
            $programaActualFormulario = trim((string)($programa_actual ?? ''));
            $programaAnterior = (string)($old['programa'] ?? '');
            $programaBaseSeleccionado = $programaActualFormulario !== '' ? $programaActualFormulario : 'universidad_vida';
            $programaNivelSeleccionado = 'capacitacion_destino_nivel_1';
            if ($programaActualFormulario === '') {
                if (in_array($programaAnterior, ['capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
                    $programaBaseSeleccionado = 'capacitacion_destino';
                    if (in_array($programaAnterior, ['capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
                        $programaNivelSeleccionado = $programaAnterior;
                    }
                }
            } else {
                if ($programaActualFormulario === 'capacitacion_destino') {
                    $programaNivelSeleccionado = (string)($old['programa_nivel'] ?? 'capacitacion_destino_nivel_1');
                }
            }
            $ocultarSelectorPrograma = $programaActualFormulario !== '';
            ?>
            <p class="help">Paso 1: busca por cédula. Si la persona ya existe, se cargarán todos sus datos y solo podrás editar los campos faltantes. Si no existe, se habilitan los datos para crearla y quedará inscrita automáticamente en <?= $programaBaseSeleccionado === 'capacitacion_destino' ? 'Capacitación Destino' : 'Universidad de la Vida' ?>.</p>

            <form method="POST" action="<?= PUBLIC_URL ?>?url=escuelas_formacion/registro-publico/guardar" id="form-escuelas" autocomplete="off">
                <?php if ($ocultarSelectorPrograma): ?>
                    <input type="hidden" id="programa" name="programa" value="<?= htmlspecialchars($programaBaseSeleccionado) ?>">
                <?php endif; ?>
                <input type="hidden" id="input-accion" name="accion" value="registro">
                <input type="hidden" id="input-id-persona" name="id_persona" value="">
                <input type="hidden" id="input-id-inscripcion" name="id_inscripcion" value="">
                <input type="hidden" id="input-id-inscripcion-asistencia" name="id_inscripcion_asistencia" value="">
                <div class="section">
                    <h3 class="section-title">1. Identificación</h3>
                    <div class="grid">
                        <div class="field">
                            <label for="tipo_documento">Tipo de documento <span class="req" id="req-tipo-doc"<?= $relajarValidacionTipoDocumento ? ' style="display:none;"' : '' ?>>*</span></label>
                            <?php
                                $tipoDocumento = trim((string)($old['tipo_documento'] ?? ''));
                                $opcionesTipoDocumento = [
                                    'Cedula de Ciudadania' => 'Cédula de Ciudadanía',
                                    'Cedula Extranjera' => 'Cédula Extranjera',
                                    'Tarjeta de Identidad' => 'Tarjeta de Identidad',
                                    'Registro Civil' => 'Registro Civil',
                                ];
                                if ($tipoDocumento === '' || !isset($opcionesTipoDocumento[$tipoDocumento])) {
                                    $tipoDocumento = 'Cedula de Ciudadania';
                                }
                                $requiredAttrTipoDocumento = $relajarValidacionTipoDocumento ? '' : 'required';
                            ?>
                            <select id="tipo_documento" name="tipo_documento" <?= $requiredAttrTipoDocumento ?>>
                                <?php foreach ($opcionesTipoDocumento as $valorTipoDoc => $etiquetaTipoDoc): ?>
                                    <option value="<?= htmlspecialchars($valorTipoDoc) ?>" <?= $tipoDocumento === $valorTipoDoc ? 'selected' : '' ?>><?= htmlspecialchars($etiquetaTipoDoc) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="field">
                            <label for="cedula">Cédula <span class="req">*</span></label>
                            <input type="text" id="cedula" name="cedula" required inputmode="numeric" pattern="[0-9]{4,}" minlength="4" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="<?= htmlspecialchars((string)($old['cedula'] ?? '')) ?>" placeholder="Ej: 12345678">
                        </div>

                        <div class="field">
                            <label for="telefono">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" inputmode="numeric" pattern="[0-9]{4,}" minlength="4" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="<?= htmlspecialchars((string)($old['telefono'] ?? '')) ?>" placeholder="Ej: 3001234567">
                        </div>
                    </div>
                    <div class="persona-resumen" id="persona-resumen-encontrada">
                        <div><strong>Persona encontrada:</strong> <span id="persona-resumen-nombre">-</span></div>
                        <div style="margin-top:4px; font-size:13px; color:#476360;">Edad: <span id="persona-resumen-edad">-</span> | Cédula: <span id="persona-resumen-cedula">-</span> | Teléfono: <span id="persona-resumen-telefono">-</span></div>
                    </div>
                </div>

                <div class="section" id="section-datos-personales">
                    <h3 class="section-title">2. Datos Personales (nuevo registro)</h3>
                    <div class="grid">
                        <div class="field full">
                            <label for="nombre">Nombre y apellidos <span class="req">*</span></label>
                            <input type="text" id="nombre" name="nombre" autocomplete="off" autocapitalize="characters" spellcheck="false" value="<?= htmlspecialchars((string)($old['nombre'] ?? '')) ?>">
                        </div>

                        <div class="field">
                            <label for="genero">Género <span class="req">*</span></label>
                            <select id="genero" name="genero">
                                <option value="">Seleccione...</option>
                                <option value="Hombre" <?= (string)($old['genero'] ?? '') === 'Hombre' ? 'selected' : '' ?>>Hombre</option>
                                <option value="Mujer" <?= (string)($old['genero'] ?? '') === 'Mujer' ? 'selected' : '' ?>>Mujer</option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="edad">Edad <span class="req">*</span></label>
                            <input type="number" id="edad" name="edad" min="7" max="120" step="1" value="<?= htmlspecialchars((string)($old['edad'] ?? '')) ?>" placeholder="Ej: 28">
                        </div>

                        <div class="field">
                            <label for="fecha_nacimiento">Fecha de nacimiento</label>
                            <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= htmlspecialchars((string)($old['fecha_nacimiento'] ?? '')) ?>">
                        </div>

                        <div class="field full">
                            <label for="direccion">Dirección</label>
                            <input type="text" id="direccion" name="direccion" autocomplete="off" value="<?= htmlspecialchars((string)($old['direccion'] ?? '')) ?>" placeholder="Ej: Calle 123 #45-67">
                        </div>
                    </div>
                </div>

                <div class="section" id="section-inscripcion">
                    <h3 class="section-title">3. Información ministerial</h3>
                    <div class="grid">
                        <div class="field full">
                            <label for="lider">Líder <span class="req">*</span></label>
                            <div class="autocomplete-wrap">
                                <input type="text" id="lider" name="lider" required autocomplete="off" autocapitalize="characters" spellcheck="false" value="<?= htmlspecialchars((string)($old['lider'] ?? '')) ?>" placeholder="Escribe para buscar líder real">
                                <input type="hidden" id="id_lider" name="id_lider" value="<?= htmlspecialchars((string)($old['id_lider'] ?? '')) ?>">
                                <div id="lista-lideres" class="autocomplete-list"></div>
                            </div>
                        </div>

                        <div class="field">
                            <label for="id_ministerio">Ministerio <span class="req">*</span></label>
                            <select id="id_ministerio" name="id_ministerio" required>
                                <option value="">Seleccione...</option>
                                <?php foreach (($ministerios ?? []) as $ministerio): ?>
                                    <option value="<?= (int)$ministerio['Id_Ministerio'] ?>" <?= (string)($old['id_ministerio'] ?? '') === (string)$ministerio['Id_Ministerio'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string)$ministerio['Nombre_Ministerio']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section" id="section-programa-nuevo">
                    <h3 class="section-title">4. Programa (nuevo registro)</h3>
                    <div class="grid">
                        <?php if ($ocultarSelectorPrograma): ?>
                            <div class="field full">
                                <label>Programa seleccionado</label>
                                <div style="padding:12px 14px; border:1px solid #d2e4e1; border-radius:10px; background:#f9fefc; color:#1f3d3a;">
                                    <?= $programaBaseSeleccionado === 'capacitacion_destino' ? 'Capacitación Destino' : 'Universidad de la Vida (Un encuentro con Jesús)' ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="field">
                                <label for="programa">Programa <span class="req">*</span></label>
                                <select id="programa" name="programa" required>
                                    <option value="universidad_vida" <?= $programaBaseSeleccionado === 'universidad_vida' ? 'selected' : '' ?>>Universidad de la Vida (Un encuentro con Jesús)</option>
                                    <option value="capacitacion_destino" <?= $programaBaseSeleccionado === 'capacitacion_destino' ? 'selected' : '' ?>>Capacitación Destino por niveles</option>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="field" id="wrap-programa-nivel" <?= $programaBaseSeleccionado === 'capacitacion_destino' ? '' : 'style="display:none;"' ?>>
                            <label for="programa_nivel">Nivel de Capacitación Destino <span class="req">*</span></label>
                            <select id="programa_nivel" name="programa_nivel" <?= $programaBaseSeleccionado === 'capacitacion_destino' ? 'required' : '' ?>>
                                <option value="capacitacion_destino_nivel_1" <?= $programaNivelSeleccionado === 'capacitacion_destino_nivel_1' ? 'selected' : '' ?>>Nivel 1 (Módulos 1 y 2)</option>
                                <option value="capacitacion_destino_nivel_2" <?= $programaNivelSeleccionado === 'capacitacion_destino_nivel_2' ? 'selected' : '' ?>>Nivel 2 (Módulos 3 y 4)</option>
                                <option value="capacitacion_destino_nivel_3" <?= $programaNivelSeleccionado === 'capacitacion_destino_nivel_3' ? 'selected' : '' ?>>Nivel 3 (Módulos 5 y 6)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section" id="section-inscripciones-existentes" style="display:none;">
                    <h3 class="section-title">4. Persona ya registrada</h3>
                    <p style="margin:0 0 10px; font-size:13px; color:#55706d;">Selecciona la inscripción existente para registrar el abono.</p>
                    <div id="lista-inscripciones-existentes"></div>
                    <p id="msg-solo-asistencia" style="display:none; margin:12px 0 0; font-size:13px; color:#7a4b00; border-top:1px solid #f0dfb8; padding-top:10px;">Persona encontrada. Usa esta inscripción para registrar pagos/abonos sin crear una inscripción nueva.</p>
                </div>

                <div class="section" id="section-pago-material">
                    <h3 class="section-title">5. Abonos</h3>

                    <div style="margin-bottom:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                        <button type="button" class="btn btn-secondary" id="btn-mostrar-acceso-abono" <?= $abonoAutorizado ? 'style="display:none;"' : '' ?>>Habilitar abonos</button>
                        <span style="font-size:12px; color:#5c6f6d;">
                            <?= $abonoAutorizado
                                ? 'Abonos habilitados por sesión activa. Puedes inscribir y registrar abono en el mismo envío.'
                                : 'Opcional: habilita abonos para registrar el pago junto con la inscripción (requiere usuario autorizado).' ?>
                        </span>
                    </div>

                    <div class="abono-lock-box" id="abono-lock-box" style="display:<?= $abonoAutorizado ? 'none' : '' ?>;">
                        <div style="font-size:13px; margin-bottom:8px; color:#45615e;"><strong>Acceso restringido:</strong> para registrar abonos debes autenticar un usuario autorizado.</div>
                        <div class="grid" style="gap:10px;">
                            <div class="field">
                                <label for="abono_usuario">Usuario</label>
                                <input type="text" id="abono_usuario" autocomplete="username" placeholder="Usuario autorizado">
                            </div>
                            <div class="field">
                                <label for="abono_contrasena">Contraseña</label>
                                <input type="password" id="abono_contrasena" autocomplete="current-password" placeholder="Contraseña">
                            </div>
                        </div>
                        <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
                            <button type="button" class="btn btn-secondary" id="btn-desbloquear-abono">Desbloquear abonos</button>
                        </div>
                        <div id="abono-lock-status" class="abono-lock-status <?= $abonoAutorizado ? 'ok' : '' ?>">
                            <?= $abonoAutorizado ? ('Abonos habilitados por: ' . htmlspecialchars($abonoNombreAuth)) : 'Abonos bloqueados.' ?>
                        </div>
                    </div>

                    <div id="abono-contenido" style="display:<?= $abonoAutorizado ? '' : 'none' ?>;">
                    <div class="grid">
                        <div class="field">
                            <label for="metodo_pago">Método de pago</label>
                            <select id="metodo_pago" name="metodo_pago" <?= $abonoAutorizado ? '' : 'disabled' ?>>
                                <option value="">Sin pago registrado</option>
                                <option value="efectivo" <?= (string)($old['metodo_pago'] ?? '') === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                            </select>
                        </div>

                        <div class="field" id="wrap-tipo-pago" style="display:none;">
                            <label for="tipo_pago">Tipo de pago <span class="req">*</span></label>
                            <select id="tipo_pago" name="tipo_pago" required <?= $abonoAutorizado ? '' : 'disabled' ?>>
                                <option value="">-- Selecciona tipo de pago --</option>
                                <option value="abono" <?= $tipoPagoOld === 'abono' ? 'selected' : '' ?>>Abono (pago parcial)</option>
                                <option value="completo" <?= $tipoPagoOld === 'completo' ? 'selected' : '' ?>>Pago total (ya completó)</option>
                            </select>
                        </div>

                        <div class="field" id="wrap-valor-pago" style="display:none;">
                            <label for="valor_pago">Valor pagado <span class="req">*</span></label>
                            <input type="text" id="valor_pago" name="valor_pago" inputmode="numeric" pattern="[0-9.,]{1,}" autocomplete="off" placeholder="Ej: 180000" value="<?= htmlspecialchars((string)($old['valor_pago'] ?? '')) ?>">
                        </div>

                        <div class="field" id="wrap-recibido-por" style="display:none;">
                            <label for="recibido_por">Quién recibió el pago <span class="req">*</span></label>
                            <input type="text" id="recibido_por" name="recibido_por" maxlength="160" placeholder="Nombre de quien recibe" value="<?= htmlspecialchars($abonoNombreAuth !== '' ? $abonoNombreAuth : (string)($old['recibido_por'] ?? '')) ?>" readonly>
                        </div>

                        <div class="field" id="wrap-entrego-libro">
                            <label for="entrego_libro">Entregó libro</label>
                            <select id="entrego_libro" name="entrego_libro" <?= $abonoAutorizado ? '' : 'disabled' ?>>
                                <option value="0" <?= (string)($old['entrego_libro'] ?? '0') !== '1' ? 'selected' : '' ?>>No</option>
                                <option value="1" <?= (string)($old['entrego_libro'] ?? '') === '1' ? 'selected' : '' ?>>Sí</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top:10px;">
                        <button type="button" class="btn btn-secondary" id="btn-compartir-abono" <?= $abonoAutorizado ? '' : 'disabled' ?>>Compartir formulario</button>
                    </div>
                    <p style="margin:8px 0 0; font-size:12px; color:#888;">El número de referencia de pago es generado automáticamente por el sistema al guardar.</p>
                    </div>
                    </div>
                </div>

                <div class="loader" id="loader-busqueda">Buscando coincidencias en Personas...</div>
                <div class="search-status" id="estado-busqueda"></div>
                <p class="hint">Por privacidad, al encontrar la persona solo se autocompleta lo mínimo necesario.</p>

                <div class="actions">
                    <button type="button" class="btn btn-secondary" id="btn-limpiar-form">Limpiar formulario</button>
                    <button type="submit" class="btn" id="btn-guardar-inscripcion">Guardar inscripción</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div id="toast-feedback" class="toast" aria-live="polite"></div>

<script>
(function() {
    const endpointBuscar = <?= json_encode(PUBLIC_URL . '?url=escuelas_formacion/registro-publico/buscar-persona') ?>;
    const endpointLideres = <?= json_encode(PUBLIC_URL . '?url=escuelas_formacion/registro-publico/buscar-lideres') ?>;
    const endpointValidarAbono = <?= json_encode(PUBLIC_URL . '?url=escuelas_formacion/registro-publico/validar-abono') ?>;
    const form = document.getElementById('form-escuelas');
    const sectionDatosPersonales = document.getElementById('section-datos-personales');
    const tipoDocumento = document.getElementById('tipo_documento');
    const nombre = document.getElementById('nombre');
    const genero = document.getElementById('genero');
    const edad = document.getElementById('edad');
    const telefono = document.getElementById('telefono');
    const cedula = document.getElementById('cedula');
    const direccion = document.getElementById('direccion');
    const fechaNacimiento = document.getElementById('fecha_nacimiento');
    const lider = document.getElementById('lider');
    const idLider = document.getElementById('id_lider');
    const listaLideres = document.getElementById('lista-lideres');
    const ministerio = document.getElementById('id_ministerio');
    const programa = document.getElementById('programa');
    const programaFijo = <?= json_encode($ocultarSelectorPrograma ? $programaBaseSeleccionado : '') ?>;
    const sectionProgramaNuevo = document.getElementById('section-programa-nuevo');
    const wrapProgramaNivel = document.getElementById('wrap-programa-nivel');
    const programaNivel = document.getElementById('programa_nivel');
    const metodoPago = document.getElementById('metodo_pago');
    const tipoPago = document.getElementById('tipo_pago');
    const wrapTipoPago = document.getElementById('wrap-tipo-pago');
    const wrapValorPago = document.getElementById('wrap-valor-pago');
    const valorPago = document.getElementById('valor_pago');
    const wrapRecibidoPor = document.getElementById('wrap-recibido-por');
    const recibidoPor = document.getElementById('recibido_por');
    const entregoLibro = document.getElementById('entrego_libro');
    const inputAccion = document.getElementById('input-accion');
    const inputIdInscripcionAsistencia = document.getElementById('input-id-inscripcion-asistencia');
    const sectionInscripcionesExistentes = document.getElementById('section-inscripciones-existentes');
    const listaInscripcionesExistentes = document.getElementById('lista-inscripciones-existentes');
    const sectionInscripcion = document.getElementById('section-inscripcion');
    const sectionPagoMaterial = document.getElementById('section-pago-material');
    const msgSoloAsistencia = document.getElementById('msg-solo-asistencia');
    const btnGuardarInscripcion = document.getElementById('btn-guardar-inscripcion');
    const btnLimpiarForm = document.getElementById('btn-limpiar-form');
    const btnCompartirAbono = document.getElementById('btn-compartir-abono');
    const btnMostrarAccesoAbono = document.getElementById('btn-mostrar-acceso-abono');
    const abonoLockBox = document.getElementById('abono-lock-box');
    const abonoContenido = document.getElementById('abono-contenido');
    const btnDesbloquearAbono = document.getElementById('btn-desbloquear-abono');
    const abonoUsuario = document.getElementById('abono_usuario');
    const abonoContrasena = document.getElementById('abono_contrasena');
    const abonoLockStatus = document.getElementById('abono-lock-status');
    const loader = document.getElementById('loader-busqueda');
    const estadoBusqueda = document.getElementById('estado-busqueda');
    const toastFeedback = document.getElementById('toast-feedback');
    const personaResumen = document.getElementById('persona-resumen-encontrada');
    const personaResumenNombre = document.getElementById('persona-resumen-nombre');
    const personaResumenEdad = document.getElementById('persona-resumen-edad');
    const personaResumenCedula = document.getElementById('persona-resumen-cedula');
    const personaResumenTelefono = document.getElementById('persona-resumen-telefono');
    const inputIdPersona = document.getElementById('input-id-persona');
    const inputIdInscripcion = document.getElementById('input-id-inscripcion');
    let personaExistente = false;
    let modoSoloAsistencia = false;
    const modoAbono = <?= !empty($modo_abono) ? 'true' : 'false' ?>;
    const relajarValidacionTipoDocumento = <?= !empty($relajarValidacionTipoDocumento) ? 'true' : 'false' ?>;
    const usuarioInternoLogueado = <?= !empty($usuario_interno_logueado) ? 'true' : 'false' ?>;
    const puedeRecibirPagosEscuelas = <?= !empty($puede_recibir_pagos_escuelas) ? 'true' : 'false' ?>;
    const prefillInicial = <?= json_encode($prefill_inicial ?? null, JSON_UNESCAPED_UNICODE) ?>;
    let abonoAutorizado = <?= !empty($abonoAutorizado) ? 'true' : 'false' ?>;
    let abonoNombreAutorizado = <?= json_encode((string)$abonoNombreAuth, JSON_UNESCAPED_UNICODE) ?>;

    let toastTimer = null;

    if (!form || !nombre || !genero || !edad || !telefono || !cedula || !lider || !idLider || !listaLideres || !ministerio || !programa || !wrapProgramaNivel || !programaNivel || !sectionDatosPersonales || !sectionProgramaNuevo) {
        return;
    }

    if (relajarValidacionTipoDocumento && tipoDocumento) {
        tipoDocumento.removeAttribute('required');
    }

    let timer = null;
    const MIN_CEDULA_BUSQUEDA = 4;
    let buscarPersonaSeq = 0;

    if (abonoLockBox) {
        abonoLockBox.dataset.visible = abonoAutorizado ? '1' : '0';
    }

    // Solo cuentas que pueden recibir pagos (administrativo puro o admin de sistema, con permisos)
    // deben tener abonos automáticos; un líder u otro usuario interno sigue igual que un visitante anónimo.
    if (usuarioInternoLogueado && puedeRecibirPagosEscuelas) {
        abonoAutorizado = true;
    }

    function actualizarAccesoAbono() {
        if (abonoLockBox) {
            // Si ya está autorizado (por sesión o login), no mostrar panel de usuario/contraseña.
            if (abonoAutorizado) {
                abonoLockBox.style.display = 'none';
            } else {
                abonoLockBox.style.display = abonoLockBox.dataset.visible === '1' ? '' : 'none';
            }
        }
        if (btnMostrarAccesoAbono) {
            btnMostrarAccesoAbono.style.display = abonoAutorizado ? 'none' : '';
        }
    }

    if (btnMostrarAccesoAbono) {
        btnMostrarAccesoAbono.addEventListener('click', function() {
            if (abonoLockBox) {
                abonoLockBox.dataset.visible = '1';
            }
            actualizarAccesoAbono();
            if (abonoUsuario) {
                abonoUsuario.focus();
            }
        });
    }

    function actualizarCamposPago() {
        if (!abonoAutorizado) {
            actualizarAccesoAbono();
            if (abonoContenido) {
                abonoContenido.style.display = 'none';
            }
            if (metodoPago) {
                metodoPago.value = '';
                metodoPago.disabled = true;
            }
            if (tipoPago) {
                tipoPago.value = '';
                tipoPago.disabled = true;
            }
            if (entregoLibro) {
                entregoLibro.value = '0';
                entregoLibro.disabled = true;
            }
            if (wrapTipoPago) wrapTipoPago.style.display = 'none';
            if (wrapValorPago) wrapValorPago.style.display = 'none';
            if (wrapRecibidoPor) wrapRecibidoPor.style.display = 'none';
            if (valorPago) valorPago.value = '';
            if (recibidoPor) recibidoPor.value = abonoNombreAutorizado || '';
            return;
        }

        actualizarAccesoAbono();
        if (abonoContenido) {
            abonoContenido.style.display = '';
        }

        if (metodoPago) {
            metodoPago.disabled = false;
        }
        if (tipoPago) {
            tipoPago.disabled = false;
        }
        if (entregoLibro) {
            entregoLibro.disabled = false;
        }

        // En página de abonos o al cargar pago sobre inscripción ya existente, mostrar siempre método/tipo/valor (igual que modoAbono).
        if (modoAbono || modoSoloAsistencia) {
            if (metodoPago && !String(metodoPago.value || '').trim()) {
                metodoPago.value = 'efectivo';
            }
            if (tipoPago && !String(tipoPago.value || '').trim()) {
                tipoPago.value = 'abono';
            }
            if (wrapTipoPago) wrapTipoPago.style.display = '';
            if (wrapValorPago) wrapValorPago.style.display = '';
            if (wrapRecibidoPor) wrapRecibidoPor.style.display = '';
            if (recibidoPor) recibidoPor.value = abonoNombreAutorizado || '';
            return;
        }

        // Persona nueva (aún no en BD): mismo paso para inscripción + abono tras desbloquear
        if (personaExistente === false && abonoAutorizado) {
            if (metodoPago && !String(metodoPago.value || '').trim()) {
                metodoPago.value = 'efectivo';
            }
            if (tipoPago && !String(tipoPago.value || '').trim()) {
                tipoPago.value = 'abono';
            }
            if (wrapTipoPago) wrapTipoPago.style.display = '';
            if (wrapValorPago) wrapValorPago.style.display = '';
            if (wrapRecibidoPor) wrapRecibidoPor.style.display = '';
            if (recibidoPor) recibidoPor.value = abonoNombreAutorizado || '';
            return;
        }

        const tienePago = !!String(metodoPago ? metodoPago.value : '').trim();
        if (!tienePago) {
            if (wrapTipoPago) wrapTipoPago.style.display = 'none';
            if (wrapValorPago) wrapValorPago.style.display = 'none';
            if (wrapRecibidoPor) wrapRecibidoPor.style.display = 'none';
            // NO limpiar tipo_pago aquí - preservar lo que el usuario seleccionó
            if (valorPago) valorPago.value = '';
            if (recibidoPor) recibidoPor.value = abonoNombreAutorizado || '';
            return;
        }
        if (wrapTipoPago) wrapTipoPago.style.display = '';
        if (wrapValorPago) wrapValorPago.style.display = '';
        if (wrapRecibidoPor) wrapRecibidoPor.style.display = '';
        if (recibidoPor) recibidoPor.value = abonoNombreAutorizado || '';
    }

    /** Datos mínimos del formulario listos (inscripción + opción de abono). */
    function registroDatosMinimosListos() {
        const c = String(cedula.value || '').trim();
        if (c.length < 4) {
            return false;
        }

        const minMinisterio = String(ministerio.value || '').trim() !== '' && String(ministerio.value) !== '0';
        const idL = parseInt(String(idLider.value || '0').trim(), 10);

        if (personaExistente) {
            return minMinisterio && idL > 0;
        }

        const nom = String(nombre.value || '').trim();
        const gen = String(genero.value || '').trim();
        const ed = parseInt(String(edad.value || '').trim(), 10);
        const tel = String(telefono.value || '').trim();

        if (!nom || !gen || !Number.isFinite(ed) || ed < 7 || ed > 120) {
            return false;
        }
        if (!tel || tel.length < 4) {
            return false;
        }
        if (idL <= 0 || !minMinisterio) {
            return false;
        }
        if (String(programa.value || '') === 'capacitacion_destino') {
            if (!String(programaNivel.value || '').trim()) {
                return false;
            }
        }
        return true;
    }

    function sincronizarAbonoTrasDatosRegistro() {
        if (modoSoloAsistencia || modoAbono) {
            return;
        }
        if (!registroDatosMinimosListos()) {
            return;
        }
        if (usuarioInternoLogueado && puedeRecibirPagosEscuelas) {
            abonoAutorizado = true;
        }
        if (abonoAutorizado) {
            actualizarCamposPago();
            return;
        }
        if (abonoLockBox) {
            abonoLockBox.dataset.visible = '1';
        }
        actualizarAccesoAbono();
    }

    function actualizarEstadoBloqueoAbono(mensaje, tipo) {
        if (!abonoLockStatus) {
            return;
        }
        abonoLockStatus.classList.remove('ok', 'err');
        if (tipo === 'ok') {
            abonoLockStatus.classList.add('ok');
        } else if (tipo === 'err') {
            abonoLockStatus.classList.add('err');
        }
        abonoLockStatus.textContent = String(mensaje || '');
    }

    function setModoSoloAsistencia(activo) {
        const bloquear = !!activo;
        modoSoloAsistencia = bloquear;

        if (msgSoloAsistencia) {
            msgSoloAsistencia.style.display = bloquear ? '' : 'none';
        }

        if (btnGuardarInscripcion) {
            btnGuardarInscripcion.style.display = '';
            btnGuardarInscripcion.textContent = bloquear ? 'Guardar abono' : 'Guardar inscripción';
        }

        if (sectionProgramaNuevo) {
            sectionProgramaNuevo.querySelectorAll('input, select, textarea, button').forEach(function(el) {
                if (el.id === 'btn-limpiar-form') {
                    return;
                }
                el.disabled = bloquear;
            });
            sectionProgramaNuevo.style.opacity = bloquear ? '0.55' : '1';
        }

        if (sectionProgramaNuevo) {
            sectionProgramaNuevo.style.display = bloquear ? 'none' : '';
        }
        if (sectionInscripcionesExistentes) {
            sectionInscripcionesExistentes.style.display = bloquear ? '' : 'none';
        }

        if (inputAccion) {
            inputAccion.value = bloquear ? 'abono' : 'registro';
        }
        if (inputIdInscripcionAsistencia && !bloquear) {
            inputIdInscripcionAsistencia.value = '';
        }

        // Refrescar bloque de pago: en modo “solo abono / inscripción existente” deben verse tipo y valor como en la vista dedicada de abonos.
        actualizarCamposPago();
    }

    function renderInscripciones(inscripciones) {
        if (!sectionInscripcionesExistentes || !listaInscripcionesExistentes) return;
        if (!inscripciones || inscripciones.length === 0) {
            sectionInscripcionesExistentes.style.display = 'none';
            listaInscripcionesExistentes.innerHTML = '';
            setModoSoloAsistencia(false);
            return;
        }
        listaInscripcionesExistentes.innerHTML = inscripciones.map(function(ins) {
            return '<div class="insc-card">' +
                '<div class="insc-info">' +
                    '<label style="display:flex;align-items:center;gap:8px;font-weight:600;">' +
                        '<input type="checkbox" class="chk-inscripcion" data-id="' + String(ins.id_inscripcion || '') + '" style="width:16px;height:16px;" ' + (inscripciones.length === 1 ? 'checked' : '') + '> Seleccionar' +
                    '</label>' +
                    '<strong>' + String(ins.programa_label || ins.programa || '') + '</strong>' +
                    '<span class="insc-badge asistio">Inscripción existente</span>' +
                '</div>' +
                '<div style="font-size:12px;color:#667775;">Programa registrado previamente.</div>' +
            '</div>';
        }).join('');
        if (inputIdInscripcionAsistencia) {
            if (inscripciones.length === 1) {
                inputIdInscripcionAsistencia.value = String(inscripciones[0].id_inscripcion || '');
                if (inputIdInscripcion) {
                    inputIdInscripcion.value = String(inscripciones[0].id_inscripcion || '');
                }
            } else {
                inputIdInscripcionAsistencia.value = '';
                if (inputIdInscripcion) {
                    inputIdInscripcion.value = '';
                }
            }
        }

        listaInscripcionesExistentes.querySelectorAll('.chk-inscripcion').forEach(function(chk) {
            chk.addEventListener('change', function() {
                const idIns = String(chk.dataset.id || '').trim();
                if (!idIns || idIns === '0') {
                    chk.checked = false;
                    return;
                }

                listaInscripcionesExistentes.querySelectorAll('.chk-inscripcion').forEach(function(other) {
                    if (other !== chk) {
                        other.checked = false;
                    }
                });

                if (inputIdInscripcionAsistencia) {
                    inputIdInscripcionAsistencia.value = chk.checked ? idIns : '';
                }
            });
        });

        setModoSoloAsistencia(true);
    }

    function toUpperCaseInput(input) {
        if (!input || typeof input.value !== 'string') {
            return;
        }
        input.value = input.value.toUpperCase();
    }

    function setLoading(active) {
        if (!loader) {
            return;
        }
        loader.classList.toggle('active', !!active);
    }

    function setEstadoBusqueda(tipo, mensaje) {
        if (!estadoBusqueda) {
            return;
        }

        estadoBusqueda.classList.remove('active', 'info', 'warn', 'error');

        if (!mensaje) {
            estadoBusqueda.textContent = '';
            return;
        }

        estadoBusqueda.textContent = String(mensaje);
        estadoBusqueda.classList.add('active');
        if (tipo === 'warn') {
            estadoBusqueda.classList.add('warn');
            return;
        }
        if (tipo === 'error') {
            estadoBusqueda.classList.add('error');
            return;
        }
        estadoBusqueda.classList.add('info');
    }

    function actualizarProgramaNivel() {
        const esDestino = String(programa.value || '') === 'capacitacion_destino';
        wrapProgramaNivel.style.display = esDestino ? '' : 'none';
        programaNivel.required = esDestino;
    }

    function mostrarToast(mensaje) {
        if (!toastFeedback) {
            return;
        }

        if (toastTimer) {
            clearTimeout(toastTimer);
        }

        toastFeedback.textContent = String(mensaje || 'Listo');
        toastFeedback.classList.add('active');
        toastTimer = setTimeout(function() {
            toastFeedback.classList.remove('active');
        }, 1500);
    }

    function mostrarSeccionDatosPersonales(mostrar) {
        sectionDatosPersonales.style.display = '';
        nombre.required = !!mostrar;
        genero.required = !!mostrar;
        edad.required = !!mostrar;
    }

    function setInputBloqueado(input, bloqueado) {
        if (!input) {
            return;
        }

        input.readOnly = !!bloqueado;
        input.setAttribute('aria-readonly', bloqueado ? 'true' : 'false');
        input.style.backgroundColor = bloqueado ? '#f4f6f8' : '';
        input.style.cursor = bloqueado ? 'not-allowed' : '';
    }

    function setSelectBloqueado(input, bloqueado) {
        if (!input) {
            return;
        }

        input.disabled = !!bloqueado;
        input.setAttribute('aria-disabled', bloqueado ? 'true' : 'false');
        input.style.backgroundColor = bloqueado ? '#f4f6f8' : '';
        input.style.cursor = bloqueado ? 'not-allowed' : '';
    }

    function actualizarModoCamposPersonaExistente(existe, persona) {
        const esExistente = !!existe;
        const data = persona && typeof persona === 'object' ? persona : {};

        if (!esExistente) {
            setSelectBloqueado(tipoDocumento, false);
            setInputBloqueado(cedula, false);
            setInputBloqueado(nombre, false);
            setSelectBloqueado(genero, false);
            setInputBloqueado(edad, false);
            setInputBloqueado(fechaNacimiento, false);
            setInputBloqueado(direccion, false);
            setInputBloqueado(telefono, false);
            setInputBloqueado(lider, false);
            setSelectBloqueado(ministerio, false);
            if (sectionPagoMaterial) sectionPagoMaterial.style.display = '';
            actualizarCamposPago();
            return;
        }

        // Persona EXISTE: Aplicar readonly solo a campos con valor, habilitar campos vacíos
        const tieneNombre = String(data.nombre || nombre.value || '').trim() !== '';
        const tieneGenero = String(data.genero || genero.value || '').trim() !== '';
        const edadValor = parseInt(String(data.edad || edad.value || '').trim(), 10);
        const tieneEdad = Number.isFinite(edadValor) && edadValor > 0;
        const tieneFechaNacimiento = String(data.fecha_nacimiento || (fechaNacimiento ? fechaNacimiento.value : '') || '').trim() !== '';
        const tieneDireccion = String(data.direccion || (direccion ? direccion.value : '') || '').trim() !== '';
        const tieneTelefono = String(data.telefono || telefono.value || '').trim() !== '';
        const tieneCedula = String(data.cedula || cedula.value || '').trim() !== '';
        const tieneTipoDocumento = String(tipoDocumento ? tipoDocumento.value : '').trim() !== '';
        const tieneLider = String(data.lider || lider.value || '').trim() !== '' || Number(data.id_lider || idLider.value || 0) > 0;
        const ministerioActual = String((data.id_ministerio || '') || (ministerio ? ministerio.value : '') || '').trim();
        const tieneMinisterio = ministerioActual !== '' && ministerioActual !== '0';

        // Bloquear campos que YA TIENEN VALOR en la BD
        // Solo el tipo de documento se bloquea si tiene valor (ya fue seleccionado)
        setSelectBloqueado(tipoDocumento, tieneTipoDocumento);
        setInputBloqueado(cedula, tieneCedula);

        setInputBloqueado(nombre, tieneNombre);
        setSelectBloqueado(genero, tieneGenero);
        setInputBloqueado(edad, tieneEdad);
        setInputBloqueado(fechaNacimiento, tieneFechaNacimiento);
        setInputBloqueado(direccion, tieneDireccion);
        setInputBloqueado(telefono, tieneTelefono);
        // Bloquear líder solo si tiene ID válido
        setInputBloqueado(lider, Number(data.id_lider || idLider.value || 0) > 0);
        // Bloquear ministerio solo si tiene valor válido
        setSelectBloqueado(ministerio, tieneMinisterio);

        if (sectionPagoMaterial) sectionPagoMaterial.style.display = '';
        actualizarCamposPago();
    }

    function calcularEdadDesdeFechaNacimiento(fechaTexto) {
        const raw = String(fechaTexto || '').trim();
        if (!raw) {
            return 0;
        }

        const fecha = new Date(raw + 'T00:00:00');
        if (Number.isNaN(fecha.getTime())) {
            return 0;
        }

        const hoy = new Date();
        let anios = hoy.getFullYear() - fecha.getFullYear();
        const mes = hoy.getMonth() - fecha.getMonth();
        if (mes < 0 || (mes === 0 && hoy.getDate() < fecha.getDate())) {
            anios--;
        }

        return anios > 0 ? anios : 0;
    }

    function sincronizarEdadConFechaNacimiento() {
        if (!fechaNacimiento || !edad) {
            return;
        }

        const anios = calcularEdadDesdeFechaNacimiento(fechaNacimiento.value);
        if (anios > 0) {
            edad.value = String(anios);
        }
        sincronizarAbonoTrasDatosRegistro();
    }

    function actualizarResumenPersona(persona, mostrar) {
        if (!personaResumen) {
            return;
        }

        const activo = !!mostrar && !!persona;
        personaResumen.classList.toggle('active', activo);
        if (!activo) {
            if (personaResumenNombre) personaResumenNombre.textContent = '-';
            if (personaResumenEdad) personaResumenEdad.textContent = '-';
            if (personaResumenCedula) personaResumenCedula.textContent = '-';
            if (personaResumenTelefono) personaResumenTelefono.textContent = '-';
            return;
        }

        if (personaResumenNombre) {
            personaResumenNombre.textContent = String(persona.nombre || '').trim() || '(sin nombre)';
        }
        if (personaResumenEdad) {
            const edadValor = parseInt(String(persona.edad || '0'), 10);
            personaResumenEdad.textContent = Number.isFinite(edadValor) && edadValor > 0 ? String(edadValor) : 'Sin dato';
        }
        if (personaResumenCedula) {
            personaResumenCedula.textContent = String(persona.cedula || '').trim() || 'Sin dato';
        }
        if (personaResumenTelefono) {
            personaResumenTelefono.textContent = String(persona.telefono || '').trim() || 'Sin dato';
        }
    }

    function aplicarPersona(persona, forzar) {
        if (!persona || typeof persona !== 'object') {
            return;
        }

        const sobrescribir = !!forzar;

        const completarSiFalta = function(input, valor) {
            if (!input) {
                return;
            }

            const actual = String(input.value || '').trim();
            const nuevo = String(valor || '').trim();
            if ((sobrescribir || actual === '') && nuevo !== '') {
                input.value = nuevo;
            }
        };

        completarSiFalta(nombre, persona.nombre || '');
        completarSiFalta(genero, persona.genero || '');
        completarSiFalta(edad, persona.edad || '');
        if (fechaNacimiento) {
            completarSiFalta(fechaNacimiento, persona.fecha_nacimiento || '');
        }
        if (direccion) {
            completarSiFalta(direccion, persona.direccion || '');
        }
        completarSiFalta(telefono, persona.telefono || '');
        completarSiFalta(cedula, persona.cedula || '');
        completarSiFalta(lider, persona.lider || '');

        // Establecer tipo_documento si viene en persona
        if ((sobrescribir || !String(tipoDocumento.value || '').trim()) && persona.tipo_documento) {
            tipoDocumento.value = String(persona.tipo_documento);
        } else if ((sobrescribir || !String(tipoDocumento.value || '').trim()) && String(persona.cedula || '').trim()) {
            // Si hay cédula pero no tipo_documento, asumir CC por defecto
            tipoDocumento.value = 'Cedula de Ciudadania';
        }

        if ((sobrescribir || !String(idLider.value || '').trim()) && persona.id_lider) {
            idLider.value = String(persona.id_lider);
        }

        if ((sobrescribir || !String(ministerio.value || '').trim()) && persona.id_ministerio) {
            ministerio.value = String(persona.id_ministerio);
        }

        if (inputIdPersona && persona.id_persona) {
            inputIdPersona.value = String(persona.id_persona);
        }
        if (inputIdInscripcion && persona.id_inscripcion) {
            inputIdInscripcion.value = String(persona.id_inscripcion);
        }

        toUpperCaseInput(nombre);
        toUpperCaseInput(lider);
    }

    function cerrarListaLideres() {
        listaLideres.classList.remove('active');
        listaLideres.innerHTML = '';
    }

    function seleccionarLider(item) {
        if (!item || !item.id_persona) {
            return;
        }

        lider.value = String(item.nombre || '');
        idLider.value = String(item.id_persona);
        toUpperCaseInput(lider);
        cerrarListaLideres();
        sincronizarAbonoTrasDatosRegistro();
    }

    async function buscarLideresReales() {
        const term = String(lider.value || '').trim();
        if (term.length < 2) {
            cerrarListaLideres();
            return;
        }

        try {
            const response = await fetch(endpointLideres + '&term=' + encodeURIComponent(term), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (!response.ok || !data || !Array.isArray(data.data) || data.data.length === 0) {
                cerrarListaLideres();
                return;
            }

            listaLideres.innerHTML = '';
            data.data.forEach(function(item) {
                const option = document.createElement('div');
                option.className = 'autocomplete-item';
                option.textContent = String(item.nombre || '') + (item.rol ? ' - ' + String(item.rol) : '');
                option.addEventListener('click', function() {
                    seleccionarLider(item);
                });
                listaLideres.appendChild(option);
            });
            listaLideres.classList.add('active');
        } catch (error) {
            cerrarListaLideres();
        }
    }

    function limpiarDatosPersonaNueva() {
        nombre.value = '';
        genero.value = '';
        edad.value = '';
        if (direccion) {
            direccion.value = '';
        }
        if (fechaNacimiento) {
            fechaNacimiento.value = '';
        }
    }

    async function buscarPersona() {
        const docRaw = String(cedula.value || '').trim();

        if (docRaw.length < MIN_CEDULA_BUSQUEDA) {
            buscarPersonaSeq++;
            const teniaPersonaCargada = personaExistente || (inputIdPersona && String(inputIdPersona.value || '').trim() !== '');
            personaExistente = false;
            actualizarModoCamposPersonaExistente(false, null);
            mostrarSeccionDatosPersonales(true);
            actualizarResumenPersona(null, false);
            renderInscripciones([]);
            setModoSoloAsistencia(false);
            setEstadoBusqueda('', '');
            if (inputIdPersona) {
                inputIdPersona.value = '';
            }
            if (inputIdInscripcion) {
                inputIdInscripcion.value = '';
            }
            if (inputIdInscripcionAsistencia) {
                inputIdInscripcionAsistencia.value = '';
            }
            if (teniaPersonaCargada) {
                limpiarDatosPersonaNueva();
            }
            return;
        }

        const seq = ++buscarPersonaSeq;
        const params = new URLSearchParams({
            cedula: docRaw,
            programa: String(programa ? programa.value : '').trim()
        });

        if (!params.get('cedula')) {
            setEstadoBusqueda('', '');
            return;
        }

        try {
            setLoading(true);
            const response = await fetch(endpointBuscar + '&' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (seq !== buscarPersonaSeq) {
                return;
            }

            if (!response.ok || !data) {
                if (seq === buscarPersonaSeq) {
                    setEstadoBusqueda('error', 'No se pudo consultar la información en este momento.');
                }
                return;
            }

            if (!data.encontrado) {
                if (seq !== buscarPersonaSeq) {
                    return;
                }
                personaExistente = false;
                actualizarModoCamposPersonaExistente(false, null);
                mostrarSeccionDatosPersonales(true);
                limpiarDatosPersonaNueva();
                actualizarResumenPersona(null, false);
                setModoSoloAsistencia(false);
                renderInscripciones([]);
                setEstadoBusqueda('warn', data.mensaje || 'No existe coincidencias para esta persona. Completa datos para crearla.');
                return;
            }

            if (seq !== buscarPersonaSeq) {
                return;
            }

            personaExistente = true;
            mostrarSeccionDatosPersonales(true);
            aplicarPersona(data.persona || null, true);
            actualizarModoCamposPersonaExistente(true, data.persona || null);
            actualizarResumenPersona(data.persona || null, true);
            renderInscripciones(data.inscripciones || []);
            if (!Array.isArray(data.inscripciones) || data.inscripciones.length === 0) {
                setModoSoloAsistencia(false);
            }

            const faltaLider = !!(data.requiere_asignacion && data.requiere_asignacion.lider);
            const faltaMinisterio = !!(data.requiere_asignacion && data.requiere_asignacion.ministerio);
            if (faltaLider || faltaMinisterio) {
                setEstadoBusqueda('warn', data.mensaje || 'La persona no tiene líder y/o ministerio asignado. Debes completarlos antes de guardar.');
            } else {
                setEstadoBusqueda('info', data.mensaje || 'Persona encontrada y campos completados.');
            }
            sincronizarAbonoTrasDatosRegistro();
        } catch (e) {
            if (seq === buscarPersonaSeq) {
                setEstadoBusqueda('error', 'Error al buscar coincidencias. Puedes continuar el registro manualmente.');
            }
        } finally {
            if (seq === buscarPersonaSeq) {
                setLoading(false);
            }
        }
    }

    function programarBusqueda() {
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(buscarPersona, 450);
    }

    [cedula].forEach(function(input) {
        input.addEventListener('input', function() {
            input.value = String(input.value || '').replace(/\D+/g, '');
            programarBusqueda();
            sincronizarAbonoTrasDatosRegistro();
        });

        input.addEventListener('blur', buscarPersona);
    });

    if (telefono) {
        telefono.addEventListener('input', function() {
            telefono.value = String(telefono.value || '').replace(/\D+/g, '');
            sincronizarAbonoTrasDatosRegistro();
        });
    }

    nombre.addEventListener('input', function() {
        toUpperCaseInput(nombre);
        sincronizarAbonoTrasDatosRegistro();
    });

    if (genero) {
        genero.addEventListener('change', sincronizarAbonoTrasDatosRegistro);
    }
    if (edad) {
        edad.addEventListener('input', sincronizarAbonoTrasDatosRegistro);
        edad.addEventListener('change', sincronizarAbonoTrasDatosRegistro);
    }

    if (fechaNacimiento) {
        fechaNacimiento.addEventListener('change', sincronizarEdadConFechaNacimiento);
        fechaNacimiento.addEventListener('input', sincronizarEdadConFechaNacimiento);
    }

    lider.addEventListener('input', function() {
        toUpperCaseInput(lider);
        idLider.value = '';
        buscarLideresReales();
        sincronizarAbonoTrasDatosRegistro();
    });

    lider.addEventListener('blur', function() {
        setTimeout(cerrarListaLideres, 180);
    });

    document.addEventListener('click', function(event) {
        if (!event.target.closest('.autocomplete-wrap')) {
            cerrarListaLideres();
        }
    });

    if (metodoPago) {
        metodoPago.addEventListener('change', function() {
            actualizarCamposPago();
        });
    }

    if (btnDesbloquearAbono) {
        btnDesbloquearAbono.addEventListener('click', async function() {
            const usuario = String(abonoUsuario ? abonoUsuario.value : '').trim();
            const contrasena = String(abonoContrasena ? abonoContrasena.value : '');

            if (!usuario || !contrasena) {
                actualizarEstadoBloqueoAbono('Debes escribir usuario y contraseña.', 'err');
                return;
            }

            btnDesbloquearAbono.disabled = true;
            btnDesbloquearAbono.textContent = 'Validando...';

            try {
                const payload = new URLSearchParams();
                payload.set('usuario', usuario);
                payload.set('contrasena', contrasena);

                const response = await fetch(endpointValidarAbono, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: payload.toString()
                });

                const data = await response.json();
                if (!response.ok || !data || !data.success) {
                    throw new Error((data && data.mensaje) ? data.mensaje : 'No fue posible desbloquear abonos.');
                }

                abonoAutorizado = true;
                abonoNombreAutorizado = String(data.nombre || '').trim();
                if (abonoLockBox) {
                    abonoLockBox.dataset.visible = '1';
                }
                if (recibidoPor) {
                    recibidoPor.value = abonoNombreAutorizado;
                }
                if (abonoContrasena) {
                    abonoContrasena.value = '';
                }
                if (btnCompartirAbono) {
                    btnCompartirAbono.disabled = false;
                }
                if (abonoContenido) {
                    abonoContenido.style.display = '';
                }

                actualizarEstadoBloqueoAbono('Abonos habilitados por: ' + abonoNombreAutorizado, 'ok');
                actualizarCamposPago();
                sincronizarAbonoTrasDatosRegistro();
                mostrarToast('Abonos desbloqueados');
            } catch (error) {
                actualizarEstadoBloqueoAbono(String(error.message || 'Credenciales inválidas.'), 'err');
            } finally {
                btnDesbloquearAbono.disabled = false;
                btnDesbloquearAbono.textContent = 'Desbloquear abonos';
            }
        });
    }

    toUpperCaseInput(nombre);
    toUpperCaseInput(lider);
    actualizarModoCamposPersonaExistente(false, null);
    mostrarSeccionDatosPersonales(false);
    setModoSoloAsistencia(false);
    actualizarProgramaNivel();

    if (modoAbono) {
        if (inputAccion) inputAccion.value = 'abono';
        if (btnGuardarInscripcion) btnGuardarInscripcion.textContent = 'Guardar abono';
    }

    actualizarCamposPago();

    if (prefillInicial && prefillInicial.encontrado) {
        personaExistente = true;
        mostrarSeccionDatosPersonales(true);
        aplicarPersona(prefillInicial.persona || null, true);
        actualizarModoCamposPersonaExistente(true, prefillInicial.persona || null);
        actualizarResumenPersona(prefillInicial.persona || null, true);
        renderInscripciones(prefillInicial.inscripciones || []);
        if (!Array.isArray(prefillInicial.inscripciones) || prefillInicial.inscripciones.length === 0) {
            setModoSoloAsistencia(false);
            if (modoAbono) {
                // Fallback: intentar recuperar inscripciones por AJAX si no vinieron en prefill.
                buscarPersona();
            }
        }
        const insPref = Array.isArray(prefillInicial.inscripciones) ? prefillInicial.inscripciones : [];
        if (insPref.length > 0) {
            setEstadoBusqueda('info', 'Persona encontrada. Puedes registrar pagos o abonos desde la inscripción existente.');
        } else {
            setEstadoBusqueda('info', 'Persona encontrada. Completa el programa para registrar una nueva inscripción.');
        }
    }

    sincronizarAbonoTrasDatosRegistro();

    if (!prefillInicial && modoAbono && String(cedula.value || '').trim() !== '') {
        // En modo abono se carga automáticamente la persona seleccionada desde el botón.
        buscarPersona();
    }

    programa.addEventListener('change', function() {
        actualizarProgramaNivel();
        sincronizarAbonoTrasDatosRegistro();
    });

    if (programaNivel) {
        programaNivel.addEventListener('change', sincronizarAbonoTrasDatosRegistro);
    }

    if (ministerio) {
        ministerio.addEventListener('change', sincronizarAbonoTrasDatosRegistro);
    }

    function normalizarValorPagoInput(valor) {
        let raw = String(valor || '').trim();
        if (!raw) {
            return 0;
        }
        raw = raw.replace(/\s+/g, '');

        const tieneComa = raw.indexOf(',') >= 0;
        const tienePunto = raw.indexOf('.') >= 0;

        if (tieneComa && tienePunto) {
            if (raw.lastIndexOf(',') > raw.lastIndexOf('.')) {
                raw = raw.replace(/\./g, '');
                raw = raw.replace(',', '.');
            } else {
                raw = raw.replace(/,/g, '');
            }
        } else if (tieneComa && !tienePunto) {
            if (/\,\d{1,2}$/.test(raw)) {
                raw = raw.replace(/\./g, '');
                raw = raw.replace(',', '.');
            } else {
                raw = raw.replace(/,/g, '');
            }
        } else if (tienePunto && !tieneComa) {
            if (!/\.\d{1,2}$/.test(raw)) {
                raw = raw.replace(/\./g, '');
            }
        }

        raw = raw.replace(/[^0-9.\-]/g, '');
        const n = Number(raw);
        return Number.isFinite(n) ? n : 0;
    }

    if (valorPago) {
        valorPago.addEventListener('input', function() {
            const limpio = String(valorPago.value || '').replace(/[^0-9.,]/g, '');
            if (limpio !== valorPago.value) {
                valorPago.value = limpio;
            }
        });
    }

    form.addEventListener('submit', function(event) {
        if (relajarValidacionTipoDocumento && tipoDocumento) {
            tipoDocumento.removeAttribute('required');
        }
        // Los <select disabled> no se envían en el POST; al bloquear persona existente el tipo queda disabled y el servidor rechazaba el guardado.
        if (tipoDocumento && tipoDocumento.disabled) {
            tipoDocumento.disabled = false;
        }

        const edadValor = parseInt(String(edad.value || '').trim(), 10);
        const telefonoValor = String(telefono.value || '').trim();
        const cedulaValor = String(cedula.value || '').trim();

        if (modoAbono && !modoSoloAsistencia) {
            const idInscripcion = String(inputIdInscripcionAsistencia ? inputIdInscripcionAsistencia.value : '').trim();
            if (!idInscripcion) {
                event.preventDefault();
                alert('Debes seleccionar una inscripción existente para registrar el abono.');
                return;
            }

            const metodo = String(metodoPago ? metodoPago.value : '').trim();
            const valor = normalizarValorPagoInput(valorPago ? valorPago.value : '');
            if (valorPago && Number.isFinite(valor) && valor > 0) {
                valorPago.value = String(Math.round(valor * 100) / 100);
            }

            if (!abonoAutorizado) {
                event.preventDefault();
                alert('Debes tener sesión autorizada para registrar abonos.');
                return;
            }
            if (!metodo) {
                event.preventDefault();
                alert('Selecciona método de pago.');
                if (metodoPago) metodoPago.focus();
                return;
            }
            if (!String(tipoPago ? tipoPago.value : '').trim()) {
                event.preventDefault();
                alert('Selecciona el tipo de pago.');
                if (tipoPago) tipoPago.focus();
                return;
            }
            if (!Number.isFinite(valor) || valor <= 0) {
                event.preventDefault();
                alert('Ingresa un valor de pago mayor a 0.');
                if (valorPago) valorPago.focus();
                return;
            }
            if (!String(recibidoPor ? recibidoPor.value : '').trim()) {
                event.preventDefault();
                alert('Debes indicar quién recibió el pago.');
                return;
            }

            if (inputAccion) inputAccion.value = 'abono';
            return;
        }

        if (modoSoloAsistencia) {
            const idInscripcion = String(inputIdInscripcionAsistencia ? inputIdInscripcionAsistencia.value : '').trim();
            if (!idInscripcion) {
                event.preventDefault();
                alert('Debes marcar con X una inscripción para continuar.');
                return;
            }

            const metodo = String(metodoPago ? metodoPago.value : '').trim();
            const valor = normalizarValorPagoInput(valorPago ? valorPago.value : '');
            const quiereAbono = !!metodo || (Number.isFinite(valor) && valor > 0);

            if (valorPago && Number.isFinite(valor) && valor > 0) {
                valorPago.value = String(Math.round(valor * 100) / 100);
            }

            if (quiereAbono && !abonoAutorizado) {
                event.preventDefault();
                alert('Debes desbloquear la sección de abonos con usuario y contraseña.');
                if (abonoUsuario) abonoUsuario.focus();
                return;
            }

            if (!quiereAbono) {
                event.preventDefault();
                alert('Debes registrar un abono.');
                return;
            }

            if (quiereAbono && !metodo) {
                event.preventDefault();
                alert('Selecciona método de pago para registrar el abono.');
                if (metodoPago) metodoPago.focus();
                return;
            }

            if (quiereAbono && !String(tipoPago ? tipoPago.value : '').trim()) {
                event.preventDefault();
                alert('Debes seleccionar el tipo de pago: ¿Abono (parcial) o Pago Total (completó)?');
                if (tipoPago) {
                    tipoPago.focus();
                    if (wrapTipoPago) wrapTipoPago.style.display = '';
                }
                return;
            }

            if (quiereAbono && (!Number.isFinite(valor) || valor <= 0)) {
                event.preventDefault();
                alert('Ingresa un valor de abono mayor a 0.');
                if (valorPago) valorPago.focus();
                return;
            }

            if (quiereAbono && !String(recibidoPor ? recibidoPor.value : '').trim()) {
                event.preventDefault();
                alert('Debes indicar quién recibió el pago.');
                if (recibidoPor) recibidoPor.focus();
                return;
            }


            if (inputAccion) {
                inputAccion.value = 'abono';
            }
            // Permitir que el formulario se envíe con acción='abono'
            return;
        }

        const metodoReg = String(metodoPago ? metodoPago.value : '').trim();
        const valorRegistro = normalizarValorPagoInput(valorPago ? valorPago.value : '');
        const quierePagoRegistro = !!metodoReg || (Number.isFinite(valorRegistro) && valorRegistro > 0);
        if (quierePagoRegistro) {
            if (valorPago && Number.isFinite(valorRegistro) && valorRegistro > 0) {
                valorPago.value = String(Math.round(valorRegistro * 100) / 100);
            }
            if (!abonoAutorizado) {
                event.preventDefault();
                alert('Para registrar pago junto con la inscripción, pulsa «Habilitar abonos» e inicia sesión con un usuario autorizado.');
                if (btnMostrarAccesoAbono) {
                    btnMostrarAccesoAbono.focus();
                } else if (abonoUsuario) {
                    abonoUsuario.focus();
                }
                return;
            }
            if (!metodoReg) {
                event.preventDefault();
                alert('Selecciona método de pago.');
                if (metodoPago) metodoPago.focus();
                return;
            }
            if (!String(tipoPago ? tipoPago.value : '').trim()) {
                event.preventDefault();
                alert('Selecciona el tipo de pago: Abono (parcial) o Pago total.');
                if (tipoPago) tipoPago.focus();
                return;
            }
            if (!Number.isFinite(valorRegistro) || valorRegistro <= 0) {
                event.preventDefault();
                alert('Ingresa un valor de pago mayor a 0.');
                if (valorPago) valorPago.focus();
                return;
            }
            if (!String(recibidoPor ? recibidoPor.value : '').trim()) {
                event.preventDefault();
                alert('Debes indicar quién recibió el pago.');
                if (recibidoPor) recibidoPor.focus();
                return;
            }
        }
        if (telefonoValor && !/^\d+$/.test(telefonoValor)) {
            event.preventDefault();
            alert('El telefono solo puede contener numeros.');
            telefono.focus();
            return;
        }

        if (telefonoValor && telefonoValor.length < 4) {
            event.preventDefault();
            alert('El telefono debe tener al menos 4 numeros.');
            telefono.focus();
            return;
        }

        if (cedulaValor && !/^\d+$/.test(cedulaValor)) {
            event.preventDefault();
            alert('La cedula solo puede contener numeros.');
            cedula.focus();
            return;
        }

        if (cedulaValor && cedulaValor.length < 4) {
            event.preventDefault();
            alert('La cedula debe tener al menos 4 numeros.');
            cedula.focus();
            return;
        }

        if (!cedulaValor) {
            event.preventDefault();
            alert('La cédula es obligatoria.');
            cedula.focus();
            return;
        }

        if (personaExistente === false) {
            if (!nombre.value.trim()) {
                event.preventDefault();
                alert('Para persona nueva, el nombre es obligatorio.');
                nombre.focus();
                return;
            }
            if (!Number.isFinite(edadValor) || edadValor < 7 || edadValor > 120) {
                event.preventDefault();
                alert('Para persona nueva, la edad debe estar entre 7 y 120 anos.');
                edad.focus();
                return;
            }
            if (!telefonoValor) {
                event.preventDefault();
                alert('Para persona nueva, el teléfono es obligatorio.');
                telefono.focus();
                return;
            }
            if (!cedulaValor) {
                event.preventDefault();
                alert('Para persona nueva, la cédula es obligatoria.');
                cedula.focus();
                return;
            }
        }
    });

    if (btnLimpiarForm) {
        btnLimpiarForm.addEventListener('click', function() {
            if (timer) {
                clearTimeout(timer);
                timer = null;
            }

            form.reset();
            nombre.value = '';
            genero.value = '';
            edad.value = '';
            telefono.value = '';
            cedula.value = '';
            if (direccion) {
                direccion.value = '';
            }
            if (fechaNacimiento) {
                fechaNacimiento.value = '';
            }
            lider.value = '';
            if (form.elements.programa) {
                form.elements.programa.value = programaFijo || 'universidad_vida';
            }
            if (form.elements.programa_nivel) {
                form.elements.programa_nivel.value = programaFijo === 'capacitacion_destino' ? 'capacitacion_destino_nivel_1' : 'capacitacion_destino_nivel_1';
            }
            if (metodoPago) {
                metodoPago.value = '';
            }
            if (tipoPago) {
                tipoPago.value = '';
            }
            if (valorPago) {
                valorPago.value = '';
            }
            if (recibidoPor) {
                recibidoPor.value = abonoNombreAutorizado || '';
            }
            if (inputAccion) {
                inputAccion.value = 'registro';
            }
            if (inputIdInscripcionAsistencia) {
                inputIdInscripcionAsistencia.value = '';
            }
            if (chkMarcarAsistencia) {
                chkMarcarAsistencia.checked = false;
            }
            ministerio.value = '';
            idLider.value = '';
            personaExistente = false;
            actualizarModoCamposPersonaExistente(false, null);
            mostrarSeccionDatosPersonales(false);
            actualizarResumenPersona(null, false);
            setModoSoloAsistencia(false);
            actualizarProgramaNivel();
            cerrarListaLideres();
            renderInscripciones([]);
            setEstadoBusqueda('', '');
            setLoading(false);
            toUpperCaseInput(nombre);
            toUpperCaseInput(lider);
            cedula.focus();
            mostrarToast('Formulario limpiado');
        });
    }

    if (btnCompartirAbono) {
        btnCompartirAbono.addEventListener('click', async function() {
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: 'Escuelas de Formación - Registro',
                        text: 'Te comparto el formulario de registro y abonos de Escuelas de Formación.',
                        url: window.location.href
                    });
                    return;
                } catch (error) {
                    // Si el usuario cancela, continuar con fallback
                }
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                try {
                    await navigator.clipboard.writeText(window.location.href);
                    mostrarToast('Enlace copiado para compartir');
                    return;
                } catch (error) {
                    // Fallback abajo
                }
            }

            window.prompt('Copia este enlace para compartir:', window.location.href);
        });
        btnCompartirAbono.disabled = !abonoAutorizado;
    }

    sincronizarEdadConFechaNacimiento();
})();
</script>
</body>
</html>
