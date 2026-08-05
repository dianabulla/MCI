<?php
/** @var array<string, mixed> $ticket */
$ticket = is_array($ticket ?? null) ? $ticket : [];
$formulario = trim((string)($ticket['formulario'] ?? ''));
$fecha = trim((string)($ticket['fecha'] ?? ''));
$nombre = trim((string)($ticket['nombre'] ?? ''));
$documento = trim((string)($ticket['documento'] ?? ''));
$acudiente = trim((string)($ticket['acudiente'] ?? ''));
$metodoPago = trim((string)($ticket['metodo_pago'] ?? ''));
$recibidoPor = trim((string)($ticket['recibido_por'] ?? ''));
$tipoPago = trim((string)($ticket['tipo_pago'] ?? ''));
$valorPago = trim((string)($ticket['valor_pago'] ?? ''));
$referencia = trim((string)($ticket['referencia_pago'] ?? ''));
?>
<div id="ticket-pago-export" class="ticket-pago-export" aria-hidden="false">
    <div class="ticket-pago-export__brand">MCI Madrid Colombia</div>
    <div class="ticket-pago-export__title">Ticket de pago</div>
    <?php if ($formulario !== ''): ?>
    <div class="ticket-pago-export__row"><span>Formulario</span><strong><?= htmlspecialchars($formulario, ENT_QUOTES, 'UTF-8') ?></strong></div>
    <?php endif; ?>
    <div class="ticket-pago-export__row"><span>Fecha</span><strong><?= htmlspecialchars($fecha !== '' ? $fecha : date('Y-m-d H:i'), ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div class="ticket-pago-export__row"><span>Niño(a)</span><strong><?= htmlspecialchars($nombre !== '' ? $nombre : '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div class="ticket-pago-export__row"><span>Documento</span><strong><?= htmlspecialchars($documento !== '' ? $documento : '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
    <?php if ($acudiente !== ''): ?>
    <div class="ticket-pago-export__row"><span>Acudiente</span><strong><?= htmlspecialchars($acudiente, ENT_QUOTES, 'UTF-8') ?></strong></div>
    <?php endif; ?>
    <div class="ticket-pago-export__sep"></div>
    <div class="ticket-pago-export__row"><span>Método</span><strong><?= htmlspecialchars($metodoPago !== '' ? $metodoPago : '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div class="ticket-pago-export__row"><span>Recibido por</span><strong><?= htmlspecialchars($recibidoPor !== '' ? $recibidoPor : '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div class="ticket-pago-export__row"><span>Tipo</span><strong><?= htmlspecialchars($tipoPago !== '' ? $tipoPago : '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div class="ticket-pago-export__row"><span>Valor</span><strong>$<?= htmlspecialchars($valorPago !== '' ? $valorPago : '0', ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div class="ticket-pago-export__ref-label">Referencia</div>
    <div class="ticket-pago-export__ref"><?= htmlspecialchars($referencia !== '' ? $referencia : '—', ENT_QUOTES, 'UTF-8') ?></div>
    <div class="ticket-pago-export__foot">Comprobante de pago — Presentación de niños</div>
</div>
