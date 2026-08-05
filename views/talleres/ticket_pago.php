<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de pago</title>
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background: #eef4f3;
            padding: 16px;
        }
        .ticket-page {
            max-width: 480px;
            margin: 0 auto;
        }
        .ticket-page h1 {
            font-size: 1.15rem;
            color: #0a6e6a;
            margin: 0 0 12px;
        }
        .ticket-pago-export {
            width: 400px;
            max-width: 100%;
            margin: 0 auto 16px;
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
        .ticket-preview-wrap {
            text-align: center;
            margin-bottom: 16px;
        }
        #ticket-pago-preview {
            display: none;
            max-width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.12);
        }
        .ticket-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        .ticket-actions .btn {
            border: none;
            border-radius: 10px;
            padding: 11px 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #0a6e6a; color: #fff; }
        .btn-wa { background: #25d366; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .ticket-hint {
            font-size: 0.85rem;
            color: #64748b;
            text-align: center;
            margin-top: 12px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .ticket-actions, .ticket-hint, h1, #ticket-pago-preview { display: none !important; }
            #ticket-pago-export { border: 1px solid #000; }
        }
    </style>
</head>
<body>
<div class="ticket-page">
    <h1>Ticket de pago (imagen)</h1>

    <?php include VIEWS . '/talleres/_ticket_pago_card.php'; ?>

    <div class="ticket-preview-wrap">
        <img id="ticket-pago-preview" alt="Vista previa del ticket">
    </div>

    <div class="ticket-actions">
        <button type="button" class="btn btn-primary" id="btn-ticket-descargar">Descargar imagen</button>
        <button type="button" class="btn btn-wa" id="btn-ticket-whatsapp">Compartir por WhatsApp</button>
        <button type="button" class="btn btn-secondary" onclick="window.print()">Imprimir</button>
    </div>
    <p class="ticket-hint">Al compartir por WhatsApp podrás elegir cualquier contacto. En el celular se envía la imagen directamente; en PC se descarga y abres WhatsApp para adjuntarla.</p>
</div>

<?php include VIEWS . '/talleres/_ticket_pago_scripts.php'; ?>
</body>
</html>
