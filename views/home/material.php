<?php include VIEWS . '/layout/header.php'; ?>

<?php
$modulosMaterial = $modulos_material ?? [];
$mensaje = (string)($mensaje ?? '');
$tipo = (string)($tipo ?? '');
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;">Material</h2>
        <small style="color:#637087;">Aqui gestionas los 4 modulos de material con todo tipo de archivos sin salir de esta pantalla.</small>
    </div>
    <a href="<?= PUBLIC_URL ?>?url=home" class="btn btn-secondary">Volver al panel</a>
</div>

<style>
    .material-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
        gap: 14px;
        margin-top: 14px;
        margin-bottom: 10px;
    }

    .material-card {
        display: block;
        text-decoration: none;
        border: 1px solid #d8e2f1;
        border-radius: 14px;
        background: linear-gradient(160deg, #ffffff 0%, #f7fbff 100%);
        padding: 16px;
        color: #1e2f48;
        box-shadow: 0 6px 18px rgba(30, 56, 98, 0.08);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .material-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(30, 56, 98, 0.16);
        border-color: #b9d0ec;
    }

    .material-card:focus-visible {
        outline: 0;
        border-color: #1f5ea8;
        box-shadow: 0 0 0 3px rgba(31, 94, 168, 0.18), 0 10px 24px rgba(30, 56, 98, 0.18);
    }

    .material-card.is-active {
        border-color: #1f5ea8;
        background: linear-gradient(180deg, #1f5ea8 0%, #1a518f 100%);
        color: #ffffff;
        box-shadow: 0 10px 24px rgba(23, 62, 110, 0.28);
    }

    .material-card.is-active .meta,
    .material-card.is-active .total,
    .material-card.is-active h3 {
        color: #ffffff;
    }

    .material-card-top {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 10px;
    }

    .material-card-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 18px;
    }

    .material-card h3 {
        margin: 0;
        font-size: 17px;
    }

    .material-card .meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        color: #4f647f;
        font-size: 13px;
    }

    .material-card .total {
        font-weight: 700;
        color: #1f4f93;
    }

    .material-tip {
        margin-top: 10px;
        color: #5a6f8d;
        font-size: 13px;
    }
</style>

<?php if ($mensaje !== ''): ?>
    <div class="alert alert-<?= $tipo === 'success' ? 'success' : 'danger' ?>" style="margin-top:14px;">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<div class="material-grid">
    <?php foreach ($modulosMaterial as $clave => $info): ?>
        <?php $meta = $info['meta'] ?? []; ?>
        <a
            href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars((string)($meta['ruta'] ?? 'home/material')) ?>"
            class="material-card"
            data-material-card="<?= htmlspecialchars((string)$clave, ENT_QUOTES, 'UTF-8') ?>"
        >
            <div class="material-card-top">
                <span class="material-card-icon" style="background: <?= htmlspecialchars((string)($meta['color'] ?? '#1e4a89')) ?>;">
                    <i class="<?= htmlspecialchars((string)($meta['icono'] ?? 'bi bi-journal-bookmark-fill')) ?>"></i>
                </span>
                <h3><?= htmlspecialchars((string)($meta['titulo'] ?? $clave)) ?></h3>
            </div>
            <div class="meta">
                <span>Entrar al módulo</span>
                <span class="total"><?= (int)($info['total_archivos'] ?? 0) ?> archivo(s)</span>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<p class="material-tip">Selecciona una tarjeta para abrir su vista separada y gestionar los archivos de ese módulo.</p>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var storageKey = 'material_card_selected';
    var cards = document.querySelectorAll('[data-material-card]');

    function activarCard(cardId) {
        cards.forEach(function(card) {
            var id = card.getAttribute('data-material-card') || '';
            card.classList.toggle('is-active', id === cardId);
        });
    }

    try {
        var selected = localStorage.getItem(storageKey) || '';
        if (selected) {
            activarCard(selected);
        }
    } catch (e) {
        // Ignorar bloqueo de storage en navegadores restringidos.
    }

    cards.forEach(function(card) {
        card.addEventListener('click', function() {
            var id = card.getAttribute('data-material-card') || '';
            activarCard(id);
            try {
                localStorage.setItem(storageKey, id);
            } catch (e) {
                // Ignorar bloqueo de storage en navegadores restringidos.
            }
        });
    });
});
</script>

<?php include VIEWS . '/layout/footer.php'; ?>