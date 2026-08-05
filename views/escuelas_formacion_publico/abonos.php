<?php include VIEWS . '/layout/header.php'; ?>
<?php
$programaActual = (string)($programa_actual ?? 'universidad_vida');
$programaLabel = (string)($programa_label ?? 'Abonos - Universidad de la Vida');
$abonoRuta = (string)($abono_ruta ?? 'escuelas_formacion/abonos/universidad-vida');
$urlVolverPagos = (string)($url_volver_pagos ?? (PUBLIC_URL . '?url=escuelas_formacion/pagos&programa=universidad_vida'));
$abonoAuth = is_array($abono_auth ?? null) ? $abono_auth : ['autorizado' => false, 'nombre' => ''];
$abonoAutorizado = !empty($abonoAuth['autorizado']);
$abonoNombre = (string)($abonoAuth['nombre'] ?? '');
$inscripcionActiva = is_array($inscripcion_activa ?? null) ? $inscripcion_activa : null;
$old = is_array($old ?? null) ? $old : [];
$cedulaBuscada = (string)($old['cedula'] ?? '');
$telefonoBuscado = (string)($old['telefono'] ?? '');
$referenciaPago = trim((string)($referencia_pago ?? ''));
$tipoMensaje = (string)($tipo_mensaje ?? '');
$abonoExitoso = $tipoMensaje === 'success' && $referenciaPago !== '';
$ticketWhatsappUrl = trim((string)($ticket_whatsapp_url ?? ''));
?>

<style>
.abonos-shell { max-width: 980px; margin: 0 auto; padding: 12px; }
.abonos-card { background: #fff; border: 1px solid #dbe7f3; border-radius: 14px; box-shadow: 0 1px 4px rgba(15,23,42,.08); padding: 16px; }
.abonos-head { display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start; margin-bottom:14px; }
.abonos-head h2 { margin:0; color:#1e3a5f; }
.abonos-muted { color:#64748b; font-size:.85rem; }
.abonos-grid { display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:12px; }
.abonos-field { display:flex; flex-direction:column; gap:6px; }
.abonos-field label { font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#475569; }
.abonos-field input, .abonos-field select { padding:10px 12px; border:1px solid #cbd5e1; border-radius:10px; font-size:.95rem; }
.abonos-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; }
.abonos-persona { margin-top:12px; border:1px solid #cfe0f5; background:linear-gradient(180deg,#fbfdff 0%,#f1f7ff 100%); border-radius:12px; padding:12px 14px; }
.abonos-persona strong { display:block; margin-bottom:6px; color:#1e3a5f; }
.abonos-empty { padding:14px; border:1px dashed #cbd5e1; border-radius:12px; background:#f8fafc; color:#475569; }
.abonos-badge { display:inline-flex; align-items:center; border-radius:999px; padding:4px 10px; font-size:.74rem; font-weight:700; background:#dcfce7; color:#166534; }
.abonos-success { margin-bottom:16px; padding:16px 18px; border:1px solid #b7d7d4; border-radius:14px; background:linear-gradient(180deg,#f7fcfb 0%,#eef8f6 100%); }
.abonos-success h3 { margin:0 0 8px; color:#0a6e6a; font-size:1.1rem; }
.abonos-ref { margin:8px 0 12px; font-size:28px; font-weight:800; letter-spacing:3px; font-family:monospace; color:#0a6e6a; }
.abonos-success-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; }
.abonos-btn-wa { background:#25d366; border-color:#25d366; color:#fff; }
.abonos-btn-wa:hover { background:#1ebe57; border-color:#1ebe57; color:#fff; }
@media (max-width: 720px) { .abonos-grid { grid-template-columns: 1fr; } }
</style>

<div class="abonos-shell">
    <div class="abonos-head">
        <div>
            <p class="abonos-muted" style="margin:0 0 4px; text-transform:uppercase; letter-spacing:.04em;">Escuelas de Formación</p>
            <h2>💳 <?= htmlspecialchars($programaLabel) ?></h2>
            <div class="abonos-muted">Sesión autorizada por: <strong><?= htmlspecialchars($abonoNombre !== '' ? $abonoNombre : 'USUARIO AUTORIZADO') ?></strong></div>
        </div>
        <div>
            <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($urlVolverPagos, ENT_QUOTES, 'UTF-8') ?>">Volver a pagos</a>
        </div>
    </div>

    <?php if ($abonoExitoso): ?>
        <div class="abonos-success">
            <h3>✓ Abono registrado correctamente</h3>
            <p class="abonos-muted" style="margin:0;">Número de ticket / referencia de pago:</p>
            <div class="abonos-ref"><?= htmlspecialchars($referenciaPago) ?></div>
            <p class="abonos-muted" style="margin:0 0 4px;">Guarda este código como comprobante. Puedes imprimir el ticket o compartirlo por WhatsApp.</p>
            <div class="abonos-success-actions">
                <a class="btn btn-primary" href="<?= PUBLIC_URL ?>?url=escuelas_formacion/registro-publico/ticket" target="_blank" rel="noopener">Ver / imprimir ticket</a>
                <?php if ($ticketWhatsappUrl !== ''): ?>
                    <a class="btn abonos-btn-wa" href="<?= htmlspecialchars($ticketWhatsappUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Compartir por WhatsApp</a>
                <?php endif; ?>
                <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(PUBLIC_URL . '?url=' . $abonoRuta . ($programaActual !== 'universidad_vida' ? '&programa=' . rawurlencode($programaActual) : ''), ENT_QUOTES, 'UTF-8') ?>">Registrar otro abono</a>
            </div>
        </div>
    <?php elseif (!empty($mensaje)): ?>
        <div class="alert <?= $tipoMensaje === 'success' ? 'alert-success' : 'alert-warning' ?>">
            <?= htmlspecialchars((string)$mensaje) ?>
        </div>
    <?php endif; ?>

    <div class="abonos-card">
        <form method="GET" action="<?= PUBLIC_URL ?>" class="abonos-grid" style="margin-bottom:14px; align-items:end;">
            <input type="hidden" name="url" value="<?= htmlspecialchars($abonoRuta, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($programaActual !== 'universidad_vida'): ?>
                <input type="hidden" name="programa" value="<?= htmlspecialchars($programaActual, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            <div class="abonos-field">
                <label for="cedula_buscar">Buscar por cédula</label>
                <input type="text" id="cedula_buscar" name="cedula" value="<?= htmlspecialchars($cedulaBuscada) ?>" inputmode="numeric" autocomplete="off" placeholder="Ingresa la cédula">
            </div>
            <div class="abonos-field">
                <label for="telefono_buscar">Teléfono</label>
                <input type="text" id="telefono_buscar" name="telefono" value="<?= htmlspecialchars($telefonoBuscado) ?>" inputmode="numeric" autocomplete="off" placeholder="Opcional">
            </div>
            <div class="abonos-actions" style="grid-column:1 / -1; margin-top:0;">
                <button type="submit" class="btn btn-primary">Buscar inscripción</button>
            </div>
        </form>

        <?php if (!$inscripcionActiva): ?>
            <div class="abonos-empty">
                No hay una inscripción cargada todavía. Busca por cédula para registrar un abono.
            </div>
        <?php elseif ($abonoExitoso): ?>
            <div class="abonos-empty" style="border-style:solid; background:#f7fcfb;">
                El abono quedó guardado. Usa los botones de arriba para ver el ticket o compartirlo por WhatsApp.
            </div>
        <?php else: ?>
            <?php
                $nombrePersona = trim((string)($inscripcionActiva['Nombre'] ?? ''));
                $cedulaPersona = trim((string)($inscripcionActiva['Cedula'] ?? ''));
                $telefonoPersona = trim((string)($inscripcionActiva['Telefono'] ?? ''));
                $programaPersona = trim((string)($inscripcionActiva['Programa'] ?? 'universidad_vida'));
                $idInscripcion = (int)($inscripcionActiva['Id_Inscripcion'] ?? 0);
            ?>
            <div class="abonos-persona">
                <strong>Inscripción seleccionada</strong>
                <div class="abonos-muted">Nombre: <?= htmlspecialchars($nombrePersona ?: 'Sin nombre') ?></div>
                <div class="abonos-muted">Cédula: <?= htmlspecialchars($cedulaPersona ?: 'Sin cédula') ?></div>
                <div class="abonos-muted">Teléfono: <?= htmlspecialchars($telefonoPersona ?: 'Sin teléfono') ?></div>
                <div class="abonos-muted">Programa: <?= htmlspecialchars($programaPersona) ?></div>
                <div style="margin-top:8px;">
                    <span class="abonos-badge">Lista para registrar abono</span>
                </div>
            </div>

            <form method="POST" action="<?= htmlspecialchars(PUBLIC_URL . '?url=' . $abonoRuta . '/guardar', ENT_QUOTES, 'UTF-8') ?>" style="margin-top:16px;">
                <input type="hidden" name="accion" value="abono">
                <input type="hidden" name="programa" value="<?= htmlspecialchars($programaPersona !== '' ? $programaPersona : $programaActual, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id_inscripcion_asistencia" value="<?= $idInscripcion > 0 ? (int)$idInscripcion : '' ?>">
                <input type="hidden" name="cedula" value="<?= htmlspecialchars($cedulaPersona ?: $cedulaBuscada) ?>">
                <input type="hidden" name="telefono" value="<?= htmlspecialchars($telefonoPersona ?: $telefonoBuscado) ?>">
                <input type="hidden" name="tipo_documento" value="Cedula de Ciudadania">

                <div class="abonos-grid">
                    <div class="abonos-field">
                        <label>Nombre</label>
                        <input type="text" value="<?= htmlspecialchars($nombrePersona ?: '') ?>" readonly>
                    </div>
                    <div class="abonos-field">
                        <label>Recibido por</label>
                        <input type="text" value="<?= htmlspecialchars($abonoNombre) ?>" readonly>
                    </div>
                    <div class="abonos-field">
                        <label>Método de pago</label>
                        <select name="metodo_pago" required>
                            <option value="">Seleccione...</option>
                            <option value="efectivo">Efectivo</option>
                        </select>
                    </div>
                    <div class="abonos-field">
                        <label>Tipo de pago</label>
                        <select name="tipo_pago" required>
                            <option value="abono" selected>Abono</option>
                            <option value="completo">Pago total</option>
                        </select>
                    </div>
                    <div class="abonos-field">
                        <label>Valor pagado</label>
                        <input type="number" name="valor_pago" min="1" step="1" required placeholder="Ej: 25000">
                    </div>
                    <div class="abonos-field">
                        <label>Entregó libro</label>
                        <select name="entrego_libro">
                            <option value="0" selected>No</option>
                            <option value="1">Sí</option>
                        </select>
                    </div>
                </div>

                <div class="abonos-actions">
                    <button type="submit" class="btn btn-primary">Registrar abono</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>