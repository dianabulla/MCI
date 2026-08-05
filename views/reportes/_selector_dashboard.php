<?php
/** @var string $dashboardSelectorActivo */
/** @var list<array{id:string,label:string,href:string,activa:bool}> $dashboardSelectorOpciones */
$dashboardSelectorOpciones = is_array($dashboardSelectorOpciones ?? null) ? $dashboardSelectorOpciones : [];
if ($dashboardSelectorOpciones === []) {
    return;
}
?>
<div class="dash-selector-wrap">
    <label for="dash-selector-modulo" class="dash-selector-label">Dashboard:</label>
    <select id="dash-selector-modulo" class="dash-selector-select" aria-label="Elegir dashboard">
        <?php foreach ($dashboardSelectorOpciones as $op): ?>
            <option value="<?= htmlspecialchars((string)($op['href'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                <?= !empty($op['activa']) ? 'selected' : '' ?>>
                <?= htmlspecialchars((string)($op['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
<style>
.dash-selector-wrap { display:flex; align-items:center; gap:8px; flex-wrap:nowrap; }
.dash-selector-label { font-size:.84rem; color:#475569; white-space:nowrap; margin:0; font-weight:600; }
.dash-selector-select {
    min-width:200px;
    max-width:280px;
    padding:6px 10px;
    border-radius:8px;
    border:1px solid #cbd5e1;
    font-size:.9rem;
    background:#fff;
    color:#0f172a;
    cursor:pointer;
}
.dash-selector-select:focus { outline:2px solid #93c5fd; border-color:#3b82f6; }
@media (max-width:640px) {
    .dash-selector-select { min-width:160px; max-width:100%; }
}
</style>
<script>
(function() {
    const sel = document.getElementById('dash-selector-modulo');
    if (!sel) return;
    sel.addEventListener('change', function() {
        const url = String(sel.value || '').trim();
        if (url !== '') {
            window.location.href = url;
        }
    });
})();
</script>
