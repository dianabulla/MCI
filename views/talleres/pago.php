<?php include VIEWS . '/layout/header.php'; ?>

<?php
$idFormulario = (int)($formulario['Id_Formulario'] ?? 0);
$idRespuesta = (int)($respuesta['Id_Respuesta'] ?? 0);
$tituloForm = (string)($formulario['Titulo'] ?? 'Taller');
$nombre = trim((string)($nombre_inscrito ?? ''));
$documento = trim((string)($documento_inscrito ?? ''));
$totalPagado = (float)($total_pagado ?? 0);
$pagos = is_array($pagos ?? null) ? $pagos : [];
$mensaje = trim((string)($mensaje ?? ''));
$tipoMensaje = trim((string)($tipo_mensaje ?? ''));
$referenciaPago = trim((string)($referencia_pago ?? ''));
$ticketDatos = is_array($ticket_datos ?? null) ? $ticket_datos : null;
$pagoExitoso = $tipoMensaje === 'ok' && $referenciaPago !== '';
$usuarioNombre = trim((string)($usuario_nombre ?? ''));
$urlPagoBase = PUBLIC_URL . '?url=talleres/pago&id=' . $idFormulario . '&id_respuesta=' . $idRespuesta;
?>

<style>
.taller-pago-card {
    max-width: 640px;
    margin: 0 auto;
    background: #fff;
    border: 1px solid #dbeafe;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
}
.taller-pago-resumen {
    background: #f8fafc;
    border-radius: 8px;
    padding: 14px 16px;
    margin-bottom: 20px;
}
.taller-pago-historial {
    margin-top: 24px;
}
.taller-pago-historial table {
    width: 100%;
    font-size: 0.9rem;
}
.taller-pago-success {
    max-width: 640px;
    margin: 0 auto 20px;
    padding: 18px 20px;
    border: 1px solid #b7d7d4;
    border-radius: 14px;
    background: linear-gradient(180deg, #f7fcfb 0%, #eef8f6 100%);
}
.taller-pago-success h3 {
    margin: 0 0 8px;
    color: #0a6e6a;
    font-size: 1.1rem;
}
.taller-pago-ref {
    margin: 8px 0 12px;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: 3px;
    font-family: monospace;
    color: #0a6e6a;
}
.taller-pago-success-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 12px;
}
.taller-btn-wa {
    background: #25d366;
    border-color: #25d366;
    color: #fff;
}
.taller-btn-wa:hover {
    background: #1ebe57;
    border-color: #1ebe57;
    color: #fff;
}
.taller-pago-nota-ref {
    font-size: 0.88rem;
    color: #64748b;
    margin-bottom: 12px;
}
.ticket-pago-export {
    width: 400px;
    max-width: 100%;
    margin: 0 auto;
    background: #fff;
    border: 2px solid #0a6e6a;
    border-radius: 16px;
    padding: 22px 20px;
    box-sizing: border-box;
    color: #1e293b;
}
.ticket-pago-export__brand {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #0a6e6a;
    margin-bottom: 6px;
}
.ticket-pago-export__title {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 14px;
}
.ticket-pago-export__row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 13px;
    margin-bottom: 8px;
    line-height: 1.35;
}
.ticket-pago-export__row span {
    color: #64748b;
    flex-shrink: 0;
}
.ticket-pago-export__row strong {
    text-align: right;
    font-weight: 700;
}
.ticket-pago-export__sep {
    height: 1px;
    background: #dbeafe;
    margin: 12px 0;
}
.ticket-pago-export__ref-label {
    font-size: 12px;
    color: #64748b;
    margin-top: 8px;
}
.ticket-pago-export__ref {
    font-family: "Courier New", monospace;
    font-size: 32px;
    font-weight: 800;
    letter-spacing: 4px;
    color: #0a6e6a;
    margin: 4px 0 12px;
}
.ticket-pago-export__foot {
    font-size: 11px;
    color: #94a3b8;
    text-align: center;
    border-top: 1px dashed #cbd5e1;
    padding-top: 10px;
}
.ticket-pago-visible-wrap {
    max-width: 400px;
    margin: 12px auto 0;
}
.ticket-preview-wrap {
    text-align: center;
    margin: 14px 0 4px;
}
#ticket-pago-preview {
    display: none;
    max-width: 100%;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.12);
}
.taller-ticket-hint {
    font-size: 0.82rem;
    color: #64748b;
    margin: 10px 0 0;
}
</style>

<div class="page-header">
    <h2>Registrar pago</h2>
    <p class="text-muted" style="margin:4px 0 0;"><?= htmlspecialchars($tituloForm, ENT_QUOTES, 'UTF-8') ?></p>
    <div style="margin-top:10px;">
        <a href="<?= PUBLIC_URL ?>?url=talleres/respuestas&id=<?= $idFormulario ?>" class="btn btn-secondary btn-sm">← Volver a inscripciones</a>
    </div>
</div>

<?php if ($pagoExitoso): ?>
<div class="taller-pago-success">
    <h3>✓ Pago registrado correctamente</h3>
    <p class="taller-pago-nota-ref" style="margin:0;">Número de ticket / referencia de pago (generado automáticamente):</p>
    <div class="taller-pago-ref"><?= htmlspecialchars($referenciaPago, ENT_QUOTES, 'UTF-8') ?></div>

    <?php if ($ticketDatos !== null): ?>
    <div class="ticket-pago-visible-wrap">
        <?php $ticket = $ticketDatos; include VIEWS . '/talleres/_ticket_pago_card.php'; ?>
    </div>
    <div class="ticket-preview-wrap">
        <img id="ticket-pago-preview" alt="Vista previa del ticket de pago">
    </div>
    <p class="taller-pago-nota-ref">Comparte la imagen del ticket con quien quieras (no va ligada al teléfono del acudiente).</p>
    <div class="taller-pago-success-actions">
        <button type="button" class="btn btn-primary" id="btn-ticket-descargar">Descargar imagen</button>
        <button type="button" class="btn taller-btn-wa" id="btn-ticket-whatsapp">Compartir por WhatsApp</button>
        <a class="btn btn-outline-secondary" href="<?= PUBLIC_URL ?>?url=talleres/ticket-pago" target="_blank" rel="noopener">Ver ticket completo</a>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($urlPagoBase, ENT_QUOTES, 'UTF-8') ?>">Registrar otro pago</a>
    </div>
    <p class="taller-ticket-hint">En el celular eliges el contacto y se envía la imagen. En PC se descarga y abres WhatsApp para adjuntarla.</p>
    <?php else: ?>
    <p class="taller-pago-nota-ref">Guarda este código como comprobante.</p>
    <div class="taller-pago-success-actions">
        <a class="btn btn-primary" href="<?= PUBLIC_URL ?>?url=talleres/ticket-pago" target="_blank" rel="noopener">Ver ticket</a>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($urlPagoBase, ENT_QUOTES, 'UTF-8') ?>">Registrar otro pago</a>
    </div>
    <?php endif; ?>
</div>
<?php elseif ($mensaje !== ''): ?>
<div class="alert alert-<?= $tipoMensaje === 'ok' ? 'success' : 'danger' ?>"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!$pagoExitoso): ?>
<div class="taller-pago-card">
    <div class="taller-pago-resumen">
        <strong><?= htmlspecialchars($nombre !== '' ? $nombre : 'Inscripción #' . $idRespuesta, ENT_QUOTES, 'UTF-8') ?></strong>
        <?php if ($documento !== ''): ?>
        <div class="text-muted" style="font-size:0.9rem;margin-top:4px;">Documento: <?= htmlspecialchars($documento, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div style="margin-top:8px;font-size:0.95rem;">
            Total pagado: <strong>$<?= number_format($totalPagado, 0, ',', '.') ?></strong>
        </div>
    </div>

    <p class="taller-pago-nota-ref">La referencia de pago se generará automáticamente al guardar.</p>

    <form method="POST" action="<?= PUBLIC_URL ?>?url=talleres/guardar-pago">
        <input type="hidden" name="id_formulario" value="<?= $idFormulario ?>">
        <input type="hidden" name="id_respuesta" value="<?= $idRespuesta ?>">

        <div class="mb-3">
            <label class="form-label">Método de pago</label>
            <select name="metodo_pago" class="form-select" required>
                <option value="Efectivo">Efectivo</option>
                <option value="Transferencia">Transferencia</option>
                <option value="Nequi">Nequi</option>
                <option value="Daviplata">Daviplata</option>
                <option value="Tarjeta">Tarjeta</option>
                <option value="Otro">Otro</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Tipo</label>
            <select name="tipo_pago" class="form-select">
                <option value="completo">Pago completo</option>
                <option value="abono">Abono</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Valor</label>
            <input type="text" name="valor_pago" class="form-control" placeholder="Ej.: 50000" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Recibido por</label>
            <input type="text" name="recibido_por" class="form-control" placeholder="Nombre de quien recibe"
                   value="<?= htmlspecialchars($usuarioNombre, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <button type="submit" class="btn btn-primary w-100">Guardar pago</button>
    </form>
</div>
<?php else: ?>
<div class="taller-pago-card">
    <div class="taller-pago-resumen">
        <strong><?= htmlspecialchars($nombre !== '' ? $nombre : 'Inscripción #' . $idRespuesta, ENT_QUOTES, 'UTF-8') ?></strong>
        <div style="margin-top:8px;font-size:0.95rem;">
            Total pagado: <strong>$<?= number_format($totalPagado, 0, ',', '.') ?></strong>
        </div>
    </div>
    <p class="text-muted" style="margin:0;">El pago quedó guardado. Usa los botones de arriba para descargar o compartir el ticket.</p>
</div>
<?php endif; ?>

<?php if ($pagos !== []): ?>
<div class="taller-pago-card taller-pago-historial" style="margin-top:16px;">
    <h5 style="margin-bottom:12px;">Historial de pagos</h5>
    <table class="table table-sm">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Referencia</th>
                <th>Método</th>
                <th>Tipo</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagos as $pago): ?>
            <tr>
                <td><?= htmlspecialchars(substr((string)($pago['Fecha_Registro'] ?? ''), 0, 16), ENT_QUOTES, 'UTF-8') ?></td>
                <td style="font-family:monospace;"><?= htmlspecialchars((string)($pago['Referencia_Pago'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($pago['Metodo_Pago'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($pago['Tipo_Pago'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td>$<?= number_format((float)($pago['Valor_Pago'] ?? 0), 0, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if ($pagoExitoso && $ticketDatos !== null): ?>
<?php $ticket = $ticketDatos; include VIEWS . '/talleres/_ticket_pago_scripts.php'; ?>
<?php endif; ?>

<?php include VIEWS . '/layout/footer.php'; ?>
