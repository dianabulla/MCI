<?php
/** @var array<string, mixed> $navProgramas */
$navProgramas = is_array($navProgramas ?? null) ? $navProgramas : [];
if (empty($navProgramas['mostrar'])) {
    return;
}
$modoHub = ($navProgramas['modo'] ?? '') === 'hub';
$lineas = (array)($navProgramas['lineas'] ?? []);
$secciones = (array)($navProgramas['secciones'] ?? []);
$breadcrumb = (array)($navProgramas['breadcrumb'] ?? []);
$lineaActiva = (string)($navProgramas['linea_activa'] ?? '');
?>
<nav class="programas-nav" aria-label="Navegación de programas">
    <div class="programas-nav__top">
        <a href="<?= htmlspecialchars((string)($navProgramas['atras_url'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>" class="programas-nav__back">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            <?= htmlspecialchars((string)($navProgramas['atras_etiqueta'] ?? 'Atrás'), ENT_QUOTES, 'UTF-8') ?>
        </a>
        <ol class="programas-nav__crumb">
            <?php foreach ($breadcrumb as $i => $crumb):
                $label = (string)($crumb['label'] ?? '');
                $href = trim((string)($crumb['href'] ?? ''));
                $activo = !empty($crumb['activo']);
            ?>
            <li>
                <?php if ($href !== '' && !$activo): ?>
                    <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
                <?php else: ?>
                    <span <?= $activo ? 'aria-current="page"' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </div>

    <?php if (count($lineas) > 0): ?>
    <div class="programas-nav__lineas" role="tablist" aria-label="Línea de programa">
        <?php foreach ($lineas as $linea):
            $clave = (string)($linea['clave'] ?? '');
            $activa = !empty($linea['activa']) || ($modoHub && $lineaActiva === $clave);
        ?>
        <a href="<?= htmlspecialchars((string)($linea['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
           class="programas-nav__linea <?= $activa ? 'is-active' : '' ?>"
           style="--linea-color: <?= htmlspecialchars((string)($linea['color'] ?? '#1e4a89'), ENT_QUOTES, 'UTF-8') ?>;"
           role="tab"
           <?= $activa ? 'aria-selected="true"' : 'aria-selected="false"' ?>>
            <span class="programas-nav__linea-icon" style="background: <?= htmlspecialchars((string)($linea['gradiente'] ?? ''), ENT_QUOTES, 'UTF-8') ?>;">
                <i class="<?= htmlspecialchars((string)($linea['icono'] ?? 'bi bi-grid'), ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
            </span>
            <span class="programas-nav__linea-text">
                <strong><?= htmlspecialchars((string)($linea['titulo_corto'] ?? $linea['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                <small><?= htmlspecialchars((string)($linea['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!$modoHub && count($secciones) > 0): ?>
    <div class="programas-nav__secciones" role="tablist" aria-label="Sección">
        <?php foreach ($secciones as $sec): ?>
        <a href="<?= htmlspecialchars((string)($sec['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
           class="programas-nav__chip <?= !empty($sec['activa']) ? 'is-active' : '' ?>"
           <?= !empty($sec['activa']) ? 'aria-current="page"' : '' ?>
           <?= !empty($sec['externa']) ? 'target="_blank" rel="noopener"' : '' ?>>
            <?= htmlspecialchars((string)($sec['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($sec['externa'])): ?><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</nav>

<style>
.programas-nav {
    position: sticky;
    top: 0;
    z-index: 850;
    margin: 0 0 18px;
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid #dbe7f3;
    background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.08);
}
.programas-nav__top {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}
.programas-nav__back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 999px;
    background: #eef4ff;
    color: #1e3a5f;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    border: 1px solid #cfe0f5;
    white-space: nowrap;
}
.programas-nav__back:hover { background: #e0ecff; color: #0f2744; }
.programas-nav__crumb {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: 0.8rem;
    color: #64748b;
}
.programas-nav__crumb li { display: inline-flex; align-items: center; gap: 6px; }
.programas-nav__crumb li + li::before {
    content: "/";
    color: #94a3b8;
    font-weight: 400;
}
.programas-nav__crumb a { color: #2563eb; text-decoration: none; font-weight: 600; }
.programas-nav__crumb a:hover { text-decoration: underline; }
.programas-nav__crumb span[aria-current="page"] { color: #0f172a; font-weight: 700; }
.programas-nav__lineas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 8px;
    margin-bottom: 10px;
}
.programas-nav__linea {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    text-decoration: none;
    color: inherit;
    transition: border-color .15s, box-shadow .15s, transform .15s;
}
.programas-nav__linea:hover {
    border-color: var(--linea-color, #1e4a89);
    box-shadow: 0 6px 16px rgba(30, 74, 137, 0.12);
    transform: translateY(-1px);
}
.programas-nav__linea.is-active {
    border-color: var(--linea-color, #1e4a89);
    box-shadow: inset 0 0 0 1px var(--linea-color, #1e4a89);
    background: linear-gradient(180deg, #fff 0%, #f0f6ff 100%);
}
.programas-nav__linea-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    flex-shrink: 0;
    font-size: 1.1rem;
}
.programas-nav__linea-text { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.programas-nav__linea-text strong { font-size: 0.88rem; color: #0f172a; line-height: 1.2; }
.programas-nav__linea-text small { font-size: 0.72rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.programas-nav__secciones {
    display: flex;
    gap: 8px;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 2px;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
}
.programas-nav__chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 14px;
    border-radius: 999px;
    border: 1px solid #cfe0f5;
    background: #f8fafc;
    color: #334155;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    white-space: nowrap;
    flex-shrink: 0;
}
.programas-nav__chip:hover { background: #eef4ff; border-color: #93c5fd; color: #1e3a8a; }
.programas-nav__chip.is-active {
    background: linear-gradient(135deg, #1e4a89 0%, #3f73be 100%);
    border-color: transparent;
    color: #fff;
    box-shadow: 0 4px 12px rgba(30, 74, 137, 0.35);
}
.programas-nav__chip.is-active .bi { color: #fff; }
@media (max-width: 640px) {
    .programas-nav { padding: 10px; border-radius: 12px; }
    .programas-nav__lineas { grid-template-columns: 1fr; }
    .programas-nav__linea-text small { display: none; }
}
</style>
