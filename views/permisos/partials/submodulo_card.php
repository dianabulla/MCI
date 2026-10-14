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
$partesExtra = [];
$funcionesExtra = [];
foreach ($extras['acciones'] as $itemExtra) {
    $claveExtra = (string)($itemExtra['clave'] ?? '');
    $modSubExtra = (string)($itemExtra['modulo'] ?? '');
    $permSubExtra = $permisos[$idRol][$modSubExtra] ?? null;
    $itemExtra['activo'] = $permSubExtra ? (int)$permSubExtra['Puede_Ver'] : 0;
    if (PermisosCatalogo::esVistaParcial($mk, $claveExtra)) {
        $partesExtra[] = $itemExtra;
    } else {
        $funcionesExtra[] = $itemExtra;
    }
}
$columnasExtra = [];
foreach ($extras['columnas'] as $itemCol) {
    $modSubCol = (string)($itemCol['modulo'] ?? '');
    $permSubCol = $permisos[$idRol][$modSubCol] ?? null;
    $itemCol['activo'] = $permSubCol ? (int)$permSubCol['Puede_Ver'] : 0;
    $columnasExtra[] = $itemCol;
}
$tieneAlguno = ($pVer + $pCre + $pEdi + $pEli) > 0;
foreach (array_merge($partesExtra, $funcionesExtra, $columnasExtra) as $itemActivo) {
    if (!empty($itemActivo['activo'])) {
        $tieneAlguno = true;
        break;
    }
}
$searchText = strtolower($subTitulo . ' ' . $subDesc . ' ' . $mk);
foreach (array_merge($partesExtra, $funcionesExtra, $columnasExtra) as $itemBusqueda) {
    $searchText .= ' ' . strtolower((string)($itemBusqueda['label'] ?? ''));
}
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

    <div class="perm-bloque">
        <p class="perm-bloque__titulo">Acceso</p>
    <div class="perm-pills" role="group" aria-label="Acceso al submódulo">
        <?php
        $pills = [
            ['puede_ver', $pVer, $crudLabels['puede_ver'], 'bi-eye', 'perm-pill--ver', true, false],
            ['puede_crear', $pCre, $crudLabels['puede_crear'], 'bi-plus-lg', 'perm-pill--crear', !$soloVerModulo, true],
            ['puede_editar', $pEdi, $crudLabels['puede_editar'], 'bi-pencil', 'perm-pill--editar', !$soloVerModulo, true],
            ['puede_eliminar', $pEli, $crudLabels['puede_eliminar'], 'bi-trash', 'perm-pill--eliminar', !$soloVerModulo, true],
        ];
        foreach ($pills as [$campo, $val, $label, $icon, $cls, $show, $requiereVer]):
            if (!$show) {
                continue;
            }
        ?>
        <label class="perm-pill <?= $cls ?><?= $val ? ' is-on' : '' ?><?= (!$pVer && $requiereVer) ? ' is-locked' : '' ?>">
            <input type="checkbox"
                class="permiso-check"
                data-rol="<?= $idRol ?>"
                data-modulo="<?= htmlspecialchars($mk, ENT_QUOTES, 'UTF-8') ?>"
                data-campo="<?= $campo ?>"
                <?= $requiereVer ? 'data-requiere-ver="1"' : '' ?>
                <?= $val ? 'checked' : '' ?>
                <?= $deshabilitarEdicion ? 'disabled' : '' ?>>
            <i class="bi <?= $icon ?>"></i>
            <span><?= $label ?></span>
        </label>
        <?php endforeach; ?>
    </div>
    </div>

    <?php if ($partesExtra !== []): ?>
    <div class="perm-bloque perm-bloque--partes">
        <p class="perm-bloque__titulo">Partes <span>se pueden dar solas</span></p>
        <div class="perm-card__adv-list">
            <?php foreach ($partesExtra as $item):
                $modSub = $item['modulo'];
                $pSub = (int)($item['activo'] ?? 0);
            ?>
            <label class="perm-adv-chip perm-adv-chip--parte<?= $pSub ? ' is-on' : '' ?>">
                <input type="checkbox"
                    class="permiso-check"
                    data-rol="<?= $idRol ?>"
                    data-modulo="<?= htmlspecialchars($modSub, ENT_QUOTES, 'UTF-8') ?>"
                    data-campo="puede_ver"
                    data-padre="<?= htmlspecialchars($mk, ENT_QUOTES, 'UTF-8') ?>"
                    data-parte="1"
                    <?= $pSub ? 'checked' : '' ?>
                    <?= $deshabilitarEdicion ? 'disabled' : '' ?>>
                <span class="perm-adv-chip__label"><?= htmlspecialchars($item['label']) ?></span>
                <span class="perm-adv-chip__hint">No abre el resto del módulo.</span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($funcionesExtra !== [] || $columnasExtra !== []): ?>
    <div class="perm-bloque perm-bloque--funciones">
        <p class="perm-bloque__titulo">Funciones <span>solo si Ver está encendido</span></p>
        <?php if ($funcionesExtra !== []): ?>
        <div class="perm-card__adv-list">
            <?php foreach ($funcionesExtra as $item):
                $modSub = $item['modulo'];
                $pSub = (int)($item['activo'] ?? 0);
            ?>
            <label class="perm-adv-chip perm-adv-chip--funcion<?= $pSub ? ' is-on' : '' ?><?= $pVer ? '' : ' is-locked' ?>">
                <input type="checkbox"
                    class="permiso-check"
                    data-rol="<?= $idRol ?>"
                    data-modulo="<?= htmlspecialchars($modSub, ENT_QUOTES, 'UTF-8') ?>"
                    data-campo="puede_ver"
                    data-padre="<?= htmlspecialchars($mk, ENT_QUOTES, 'UTF-8') ?>"
                    data-requiere-ver="1"
                    <?= $pSub ? 'checked' : '' ?>
                    <?= $deshabilitarEdicion ? 'disabled' : '' ?>>
                <span class="perm-adv-chip__label"><?= htmlspecialchars($item['label']) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($columnasExtra !== []): ?>
        <div class="perm-pills perm-pills--nested">
            <?php foreach ($columnasExtra as $item):
                $modSub = $item['modulo'];
                $pSub = (int)($item['activo'] ?? 0);
            ?>
            <label class="perm-pill perm-pill--ver<?= $pSub ? ' is-on' : '' ?><?= $pVer ? '' : ' is-locked' ?>">
                <input type="checkbox"
                    class="permiso-check"
                    data-rol="<?= $idRol ?>"
                    data-modulo="<?= htmlspecialchars($modSub, ENT_QUOTES, 'UTF-8') ?>"
                    data-campo="puede_ver"
                    data-padre="<?= htmlspecialchars($mk, ENT_QUOTES, 'UTF-8') ?>"
                    data-requiere-ver="1"
                    <?= $pSub ? 'checked' : '' ?>
                    <?= $deshabilitarEdicion ? 'disabled' : '' ?>>
                <i class="bi bi-layout-three-columns"></i>
                <span><?= htmlspecialchars($item['label']) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</article>
