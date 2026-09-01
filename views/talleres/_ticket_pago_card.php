<?php
/** @var array<string, mixed> $ticket */
$ticket = is_array($ticket ?? null) ? $ticket : [];
$formulario = trim((string)($ticket['formulario'] ?? ''));
$tituloMci = trim((string)($ticket['titulo_mci'] ?? ''));
if ($tituloMci === '') {
    $origen = strtolower(trim((string)($ticket['origen'] ?? '')));
    if ($origen === 'taller') {
        $tituloMci = 'MCI Taller' . ($formulario !== '' ? ' ' . $formulario : '');
    } elseif ($formulario !== '') {
        $tituloMci = 'MCI ' . $formulario;
    } else {
        $tituloMci = 'MCI';
    }
}
$fecha = trim((string)($ticket['fecha'] ?? ''));
$nombre = trim((string)($ticket['nombre'] ?? ''));
$documento = trim((string)($ticket['documento'] ?? ''));
$acudiente = trim((string)($ticket['acudiente'] ?? ''));
$etiquetaNombre = trim((string)($ticket['etiqueta_nombre'] ?? ''));
if ($etiquetaNombre === '') {
    $etiquetaNombre = $acudiente !== '' ? 'Niño(a)' : 'Inscrito';
}
$pieComprobante = trim((string)($ticket['pie_comprobante'] ?? ''));
if ($pieComprobante === '') {
    $pieComprobante = 'Gracias por tu ofrenda · ' . $tituloMci;
}
$metodoPago = trim((string)($ticket['metodo_pago'] ?? ''));
$recibidoPor = trim((string)($ticket['recibido_por'] ?? ''));
$tipoPago = trim((string)($ticket['tipo_pago'] ?? ''));
$valorPago = trim((string)($ticket['valor_pago'] ?? ''));
$referencia = trim((string)($ticket['referencia_pago'] ?? ''));
$material = isset($ticket['entrego_material']) ? ((int)$ticket['entrego_material'] === 1 ? 'Sí' : 'No') : '';
?>
<style>
.ticket-pago-export {
    width: 400px;
    max-width: 100%;
    margin: 0 auto;
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 10px 28px rgba(10, 110, 106, 0.18);
    color: #1e293b;
    font-family: Arial, Helvetica, sans-serif;
    box-sizing: border-box;
}
.ticket-pago-export__header {
    background: linear-gradient(135deg, #0a6e6a 0%, #0f766e 55%, #134e4a 100%);
    color: #fff;
    padding: 18px 20px 16px;
    text-align: center;
}
.ticket-pago-export__brand {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    opacity: 0.85;
    margin-bottom: 8px;
}
.ticket-pago-export__mci {
    font-size: 22px;
    font-weight: 800;
    line-height: 1.25;
    margin: 0 0 6px;
}
.ticket-pago-export__subtitle {
    display: inline-block;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    background: rgba(255,255,255,0.16);
    border-radius: 999px;
    padding: 4px 10px;
}
.ticket-pago-export__body { padding: 16px 20px 18px; }
.ticket-pago-export__row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    font-size: 13px;
    margin-bottom: 8px;
    line-height: 1.35;
}
.ticket-pago-export__row span { color: #64748b; flex-shrink: 0; }
.ticket-pago-export__row strong { text-align: right; font-weight: 700; }
.ticket-pago-export__sep { height: 1px; background: #dbeafe; margin: 12px 0; }
.ticket-pago-export__ref-label { font-size: 12px; color: #64748b; margin-top: 4px; text-align: center; }
.ticket-pago-export__ref {
    font-family: "Courier New", monospace;
    font-size: 30px;
    font-weight: 800;
    letter-spacing: 4px;
    color: #0a6e6a;
    margin: 4px 0 12px;
    text-align: center;
}
.ticket-pago-export__foot {
    font-size: 11px;
    color: #64748b;
    text-align: center;
    border-top: 1px dashed #cbd5e1;
    padding-top: 10px;
}
</style>
<div id="ticket-pago-export" class="ticket-pago-export" aria-hidden="false">
    <div class="ticket-pago-export__header">
        <div class="ticket-pago-export__brand">MCI Madrid Colombia</div>
        <div class="ticket-pago-export__mci"><?= htmlspecialchars($tituloMci, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="ticket-pago-export__subtitle">Ticket de pago</div>
    </div>
    <div class="ticket-pago-export__body">
        <div class="ticket-pago-export__row"><span>Fecha</span><strong><?= htmlspecialchars($fecha !== '' ? $fecha : date('Y-m-d H:i'), ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="ticket-pago-export__row"><span><?= htmlspecialchars($etiquetaNombre, ENT_QUOTES, 'UTF-8') ?></span><strong><?= htmlspecialchars($nombre !== '' ? $nombre : '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="ticket-pago-export__row"><span>Documento</span><strong><?= htmlspecialchars($documento !== '' ? $documento : '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <?php if ($acudiente !== ''): ?>
        <div class="ticket-pago-export__row"><span>Acudiente</span><strong><?= htmlspecialchars($acudiente, ENT_QUOTES, 'UTF-8') ?></strong></div>
        <?php endif; ?>
        <div class="ticket-pago-export__sep"></div>
        <div class="ticket-pago-export__row"><span>Método</span><strong><?= htmlspecialchars($metodoPago !== '' ? $metodoPago : '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="ticket-pago-export__row"><span>Recibido por</span><strong><?= htmlspecialchars($recibidoPor !== '' ? $recibidoPor : '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="ticket-pago-export__row"><span>Tipo</span><strong><?= htmlspecialchars($tipoPago !== '' ? $tipoPago : '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="ticket-pago-export__row"><span>Valor</span><strong>$<?= htmlspecialchars($valorPago !== '' ? $valorPago : '0', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <?php if ($material !== ''): ?>
        <div class="ticket-pago-export__row"><span>Material</span><strong><?= htmlspecialchars($material, ENT_QUOTES, 'UTF-8') ?></strong></div>
        <?php endif; ?>
        <div class="ticket-pago-export__ref-label">Referencia</div>
        <div class="ticket-pago-export__ref"><?= htmlspecialchars($referencia !== '' ? $referencia : '—', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="ticket-pago-export__foot"><?= htmlspecialchars($pieComprobante, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
</div>
