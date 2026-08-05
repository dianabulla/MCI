<?php
/** @var array<string, mixed>|null $ticket */
$ticketJson = is_array($ticket ?? null) ? $ticket : null;
$urlHtml2canvas = function_exists('asset_url')
    ? asset_url('js/vendor/html2canvas.min.js')
    : (rtrim(ASSETS_URL, '/') . '/js/vendor/html2canvas.min.js?v=' . date('Ymd'));
$urlTicketJs = function_exists('asset_url')
    ? asset_url('js/taller_ticket_pago.js')
    : (rtrim(ASSETS_URL, '/') . '/js/taller_ticket_pago.js?v=' . date('Ymd'));
?>
<?php if ($ticketJson !== null): ?>
<script type="application/json" id="ticket-pago-data"><?= htmlspecialchars(
    json_encode($ticketJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
    ENT_QUOTES,
    'UTF-8'
) ?></script>
<?php endif; ?>
<script src="<?= htmlspecialchars($urlHtml2canvas, ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($urlTicketJs, ENT_QUOTES, 'UTF-8') ?>"></script>
