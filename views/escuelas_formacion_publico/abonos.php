<?php include VIEWS . '/layout/header.php'; ?>
<?php
$programaActual = 'universidad_vida';
$programaLabel = (string)($programa_label ?? 'Abonos - Universidad de la Vida');
$abonoAuth = is_array($abono_auth ?? null) ? $abono_auth : ['autorizado' => false, 'nombre' => ''];
$abonoAutorizado = !empty($abonoAuth['autorizado']);
$abonoNombre = (string)($abonoAuth['nombre'] ?? '');
$inscripcionActiva = is_array($inscripcion_activa ?? null) ? $inscripcion_activa : null;
$old = is_array($old ?? null) ? $old : [];
$cedulaBuscada = (string)($old['cedula'] ?? '');
$telefonoBuscado = (string)($old['telefono'] ?? '');
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
            <a class="btn btn-outline-secondary btn-sm" href="<?= PUBLIC_URL ?>?url=escuelas_formacion/pagos&programa=universidad_vida">Volver a pagos</a>
        </div>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert <?= ($tipo_mensaje ?? '') === 'success' ? 'alert-success' : 'alert-warning' ?>">
            <?= htmlspecialchars((string)$mensaje) ?>
        </div>
    <?php endif; ?>

    <div class="abonos-card">
        <form method="GET" action="<?= PUBLIC_URL ?>" class="abonos-grid" style="margin-bottom:14px; align-items:end;">
            <input type="hidden" name="url" value="escuelas_formacion/abonos/universidad-vida">
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
                No hay una inscripción cargada todavía. Busca por cédula para abrir directamente el formulario de abono.
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

            <form method="POST" action="<?= PUBLIC_URL ?>?url=escuelas_formacion/abonos/universidad-vida/guardar" style="margin-top:16px;">
                <input type="hidden" name="accion" value="abono">
                <input type="hidden" name="programa" value="universidad_vida">
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
                        <input type="number" name="valor_pago" min="1" step="100" required placeholder="Ej: 25000">
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