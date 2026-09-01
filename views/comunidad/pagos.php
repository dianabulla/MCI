<?php include VIEWS . '/layout/header.php'; ?>
<?php
$cedula = (string)($cedula ?? '');
$opciones = is_array($opciones ?? null) ? $opciones : [];
$mensaje = trim((string)($mensaje ?? ''));
$error = trim((string)($error ?? ''));
$ticket = is_array($ticket ?? null) ? $ticket : null;
$responsableDefault = trim((string)($responsable_default ?? ''));
$urlHtml2canvas = function_exists('asset_url')
    ? asset_url('js/vendor/html2canvas.min.js')
    : (rtrim(ASSETS_URL, '/') . '/js/vendor/html2canvas.min.js?v=' . date('Ymd'));
$urlTicketJs = function_exists('asset_url')
    ? asset_url('js/taller_ticket_pago.js')
    : (rtrim(ASSETS_URL, '/') . '/js/taller_ticket_pago.js?v=' . date('Ymd'));
?>

<style>
.com-pagos { max-width: 720px; margin: 0 auto; }
.com-pagos-card {
    background: #fff;
    border: 1px solid #dbeafe;
    border-radius: 14px;
    padding: 22px 22px 24px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
    margin-bottom: 18px;
}
.com-pagos-card h2 { margin: 0 0 6px; font-size: 1.25rem; color: #0f172a; }
.com-pagos-card .lead { margin: 0 0 16px; color: #64748b; font-size: 0.92rem; }
.com-pagos-buscar { display: flex; gap: 8px; flex-wrap: wrap; align-items: end; }
.com-pagos-buscar label { font-size: 0.8rem; font-weight: 600; color: #475569; display: block; margin-bottom: 6px; }
.com-pagos-buscar input { min-width: 220px; height: 42px; padding: 0 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 1.05rem; }
.com-pagos-opciones { display: flex; flex-direction: column; gap: 8px; margin: 14px 0 18px; }
.com-pagos-opcion {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 10px;
    align-items: center;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 12px;
    cursor: pointer;
    background: #f8fafc;
}
.com-pagos-opcion:has(input:checked) {
    border-color: #2563eb;
    background: #eff6ff;
}
.com-pagos-opcion strong { display: block; color: #0f172a; }
.com-pagos-opcion small { color: #64748b; }
.com-pagos-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.com-pagos-grid .full { grid-column: 1 / -1; }
.com-pagos-grid label { font-size: 0.8rem; font-weight: 600; color: #475569; display: block; margin-bottom: 6px; }
.com-pagos-grid select,
.com-pagos-grid input[type="text"],
.com-pagos-grid input[type="number"] {
    width: 100%;
    height: 40px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0 10px;
}
.com-pagos-radios { display: flex; gap: 14px; flex-wrap: wrap; }
.com-pagos-radios label { font-weight: 600; color: #0f172a; display: flex; align-items: center; gap: 6px; margin: 0; }
.com-pagos-ticket { border-color: #99f6e4; background: linear-gradient(180deg, #f7fcfb 0%, #eef8f6 100%); }
.com-pagos-ticket h3 { margin: 0 0 8px; color: #0a6e6a; }
.com-pagos-ref { font-family: monospace; font-size: 1.6rem; font-weight: 800; letter-spacing: 2px; color: #0a6e6a; margin: 8px 0 12px; }
.com-pagos-acciones { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; }
.com-pagos-ticket .ticket-pago-export { margin: 8px auto 16px; }
@media (max-width: 640px) {
    .com-pagos-grid { grid-template-columns: 1fr; }
}
</style>

<div class="page-header">
    <h2>Pagos comunidad</h2>
    <p class="text-muted" style="margin:4px 0 0;">Busque por cédula, elija el programa o taller y registre el pago. Queda en UV, Cap. Destino o talleres según la inscripción.</p>
</div>

<div class="com-pagos">
<?php if ($error !== ''): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($mensaje !== ''): ?>
<div class="alert alert-success"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (is_array($ticket) && trim((string)($ticket['referencia_pago'] ?? '')) !== ''): ?>
<div class="com-pagos-card com-pagos-ticket">
    <h3>Ticket listo</h3>
    <p class="lead" style="margin-bottom:8px;text-align:center;"><?= htmlspecialchars((string)($ticket['titulo_mci'] ?? $ticket['formulario'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
    <?php include VIEWS . '/talleres/_ticket_pago_card.php'; ?>
    <div class="com-pagos-acciones">
        <button type="button" class="btn btn-primary" id="btn-ticket-descargar">Descargar imagen</button>
        <button type="button" class="btn" id="btn-ticket-whatsapp" style="background:#25d366;border:0;color:#fff;">Enviar por WhatsApp Web</button>
    </div>
    <p id="ticket-wa-estado" class="text-muted" style="margin:10px 0 0;font-size:0.85rem;text-align:center;"></p>
</div>
<script type="application/json" id="ticket-pago-data"><?= json_encode($ticket, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>

<div class="com-pagos-card">
    <h2>1. Buscar por cédula</h2>
    <p class="lead">Al ingresar el documento aparecen las inscripciones activas de esa persona.</p>
    <form method="get" action="<?= PUBLIC_URL ?>" class="com-pagos-buscar">
        <input type="hidden" name="url" value="comunidad/pagos">
        <div>
            <label for="cedula">Número de cédula</label>
            <input type="text" id="cedula" name="cedula" inputmode="numeric" value="<?= htmlspecialchars($cedula, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. 1234567890" required>
        </div>
        <button type="submit" class="btn btn-primary" style="height:42px;">Buscar</button>
    </form>
</div>

<?php if ($cedula !== ''): ?>
<form method="post" action="<?= PUBLIC_URL ?>?url=comunidad/pagos/guardar" class="com-pagos-card">
    <input type="hidden" name="cedula" value="<?= htmlspecialchars($cedula, ENT_QUOTES, 'UTF-8') ?>">
    <h2>2. ¿A qué está inscrito?</h2>
    <?php if ($opciones === []): ?>
    <p class="lead">No hay Universidad de la Vida, Capacitación Destino ni talleres con esa cédula.</p>
    <?php else: ?>
    <p class="lead"><?= count($opciones) === 1 ? 'Una inscripción. Confirme y registre el pago.' : 'Hay más de una inscripción. Elija a cuál corresponde este pago.' ?></p>
    <div class="com-pagos-opciones">
        <?php foreach ($opciones as $i => $op): ?>
        <label class="com-pagos-opcion">
            <input type="radio" name="inscripcion" value="<?= htmlspecialchars((string)$op['clave'], ENT_QUOTES, 'UTF-8') ?>" <?= $i === 0 ? 'checked' : '' ?> required>
            <span>
                <strong><?= htmlspecialchars((string)$op['programa'], ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= htmlspecialchars((string)$op['nombre'], ENT_QUOTES, 'UTF-8') ?><?php if (!empty($op['fecha'])): ?> · <?= htmlspecialchars((string)$op['fecha'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?></small>
            </span>
            <span class="text-muted" style="font-size:0.82rem;white-space:nowrap;">Pagado $<?= number_format((float)($op['total_pagado'] ?? 0), 0, ',', '.') ?></span>
        </label>
        <?php endforeach; ?>
    </div>

    <h2>3. Registrar pago</h2>
    <div class="com-pagos-grid">
        <div>
            <label for="metodo_pago">Medio de pago</label>
            <select name="metodo_pago" id="metodo_pago" required>
                <option value="">Seleccione…</option>
                <option value="Efectivo">Efectivo</option>
                <option value="Nequi">Nequi</option>
                <option value="Daviplata">Daviplata</option>
            </select>
        </div>
        <div>
            <label>Tipo</label>
            <div class="com-pagos-radios" style="height:40px;align-items:center;">
                <label><input type="radio" name="tipo_pago" value="abono" checked> Abono</label>
                <label><input type="radio" name="tipo_pago" value="completo"> Pago total</label>
            </div>
        </div>
        <div>
            <label for="valor_pago">Valor</label>
            <input type="text" name="valor_pago" id="valor_pago" inputmode="decimal" placeholder="Ej. 180000" required>
        </div>
        <div>
            <label>¿Le entregó material?</label>
            <div class="com-pagos-radios" style="height:40px;align-items:center;">
                <label><input type="radio" name="entrego_material" value="1"> Sí</label>
                <label><input type="radio" name="entrego_material" value="0" checked> No</label>
            </div>
        </div>
        <div class="full">
            <label for="recibido_por">Responsable que recibe el pago</label>
            <input type="text" name="recibido_por" id="recibido_por" value="<?= htmlspecialchars($responsableDefault, ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
    </div>
    <div style="margin-top:16px;">
        <button type="submit" class="btn btn-primary">Guardar pago</button>
    </div>
    <?php endif; ?>
</form>
<?php endif; ?>
</div>

<?php if (is_array($ticket) && trim((string)($ticket['referencia_pago'] ?? '')) !== ''): ?>
<script src="<?= htmlspecialchars($urlHtml2canvas, ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($urlTicketJs, ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endif; ?>

<?php include VIEWS . '/layout/footer.php'; ?>
