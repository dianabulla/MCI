<?php
/**
 * Tarjeta de un submódulo dentro de un grupo (menú).
 * Variables: $mk, $subTitulo, $subDesc, $idRol, $esRolProtegido, $permisos, $definiciones_modulos, $puedeEditarMatriz
 */
$deshabilitarEdicion = $esRolProtegido || empty($puedeEditarMatriz);
$permiso = $permisos[$idRol][$mk] ?? null;
$pVer = $permiso ? (int)$permiso['Puede_Ver'] : 0;
$pCre = $permiso ? (int)$permiso['Puede_Crear'] : 0;
$pEdi = $permiso ? (int)$permiso['Puede_Editar'] : 0;
$pEli = $permiso ? (int)$permiso['Puede_Eliminar'] : 0;
$soloVerModulo = !empty($definiciones_modulos[$mk]['solo_ver']);
$crudLabels = PermisosModulos::crudLabels($mk);
$extras = PermisosUiCatalogo::extrasDeSubmodulo($mk);
$tieneAlguno = ($pVer + $pCre + $pEdi + $pEli) > 0;
$searchText = strtolower($subTitulo . ' ' . $subDesc . ' ' . $mk);
?>
<article class="perm-submodule<?= $tieneAlguno ? ' perm-submodule--on' : '' ?>"
    data-module-key="<?= htmlspecialchars($mk, ENT_QUOTES, 'UTF-8') ?>"
    data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
    <header class="perm-submodule__head">
        <h4 class="perm-submodule__title"><?= htmlspecialchars($subTitulo) ?></h4>
        <p class="perm-submodule__desc"><?= htmlspecialchars($subDesc) ?></p>
        <?php if (!$deshabilitarEdicion): ?>
        <div class="perm-card__quick" aria-label="Atajos">
            <button type="button" class="perm-card__quick-btn" data-preset="solo_ver">Solo ver</button>
            <button type="button" class="perm-card__quick-btn" data-preset="full">Todo</button>
            <button type="button" class="perm-card__quick-btn" data-preset="none">Ninguno</button>
        </div>
        <?php endif; ?>
    </header>

    <div class="perm-pills" role="group" aria-label="Acceso al submódulo">
        <?php
        $pills = [
            ['puede_ver', $pVer, $crudLabels['puede_ver'], 'bi-eye', 'perm-pill--ver', true],
            ['puede_crear', $pCre, $crudLabels['puede_crear'], 'bi-plus-lg', 'perm-pill--crear', !$soloVerModulo],
            ['puede_editar', $pEdi, $crudLabels['puede_editar'], 'bi-pencil', 'perm-pill--editar', !$soloVerModulo],
            ['puede_eliminar', $pEli, $crudLabels['puede_eliminar'], 'bi-trash', 'perm-pill--eliminar', !$soloVerModulo],
        ];
        foreach ($pills as [$campo, $val, $label, $icon, $cls, $show]):
            if (!$show) {
                continue;
            }
        ?>
        <label class="perm-pill <?= $cls ?><?= $val ? ' is-on' : '' ?>">
            <input type="checkbox"
                class="permiso-check"
                data-rol="<?= $idRol ?>"
                data-modulo="<?= htmlspecialchars($mk, ENT_QUOTES, 'UTF-8') ?>"
                data-campo="<?= $campo ?>"
                <?= $val ? 'checked' : '' ?>
                <?= $deshabilitarEdicion ? 'disabled' : '' ?>>
            <i class="bi <?= $icon ?>"></i>
            <span><?= $label ?></span>
        </label>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($extras['acciones'])): ?>
    <div class="perm-card__ui-block">
        <p class="perm-card__ui-title">Acciones permitidas</p>
        <div class="perm-card__adv-list">
            <?php foreach ($extras['acciones'] as $item):
                $modSub = $item['modulo'];
                $permSub = $permisos[$idRol][$modSub] ?? null;
                $pSub = $permSub ? (int)$permSub['Puede_Ver'] : 0;
            ?>
            <label class="perm-adv-chip<?= $pSub ? ' is-on' : '' ?>">
                <input type="checkbox"
                    class="permiso-check"
                    data-rol="<?= $idRol ?>"
                    data-modulo="<?= htmlspecialchars($modSub, ENT_QUOTES, 'UTF-8') ?>"
                    data-campo="puede_ver"
                    <?= $pSub ? 'checked' : '' ?>
                    <?= $deshabilitarEdicion ? 'disabled' : '' ?>>
                <span class="perm-adv-chip__label"><?= htmlspecialchars($item['label']) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($extras['columnas'])): ?>
    <div class="perm-card__ui-block">
        <p class="perm-card__ui-title">Columnas visibles en tablas</p>
        <div class="perm-pills perm-pills--nested">
            <?php foreach ($extras['columnas'] as $item):
                $modSub = $item['modulo'];
                $permSub = $permisos[$idRol][$modSub] ?? null;
                $pSub = $permSub ? (int)$permSub['Puede_Ver'] : 0;
            ?>
            <label class="perm-pill perm-pill--ver<?= $pSub ? ' is-on' : '' ?>">
                <input type="checkbox"
                    class="permiso-check"
                    data-rol="<?= $idRol ?>"
                    data-modulo="<?= htmlspecialchars($modSub, ENT_QUOTES, 'UTF-8') ?>"
                    data-campo="puede_ver"
                    <?= $pSub ? 'checked' : '' ?>
                    <?= $deshabilitarEdicion ? 'disabled' : '' ?>>
                <i class="bi bi-layout-three-columns"></i>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</article>
