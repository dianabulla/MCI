<?php
/**
 * Render del menú lateral desde $_SESSION['sidebar_menu'].
 * Variables esperadas: $sidebarMenu (array), $currentUrl (string), $isActive (callable).
 */
if (!function_exists('sidebar_nav_item_activo')) {
    function sidebar_nav_item_activo(array $item, string $currentUrl, callable $isActive): bool {
        if (!empty($item['active_exact']) && is_array($item['active_exact'])) {
            foreach ($item['active_exact'] as $exact) {
                if ($currentUrl === (string)$exact) {
                    return true;
                }
            }
        }

        $exclude = (array)($item['active_exclude_prefixes'] ?? []);
        foreach ($exclude as $prefijoExcl) {
            $prefijoExcl = (string)$prefijoExcl;
            if ($prefijoExcl !== '' && ($currentUrl === $prefijoExcl || strpos($currentUrl, $prefijoExcl . '/') === 0)) {
                return false;
            }
        }

        $prefixExtra = (array)($item['active_prefix_extra'] ?? []);
        foreach ($prefixExtra as $prefijo) {
            if ($currentUrl === (string)$prefijo || strpos($currentUrl, (string)$prefijo . '/') === 0) {
                return true;
            }
        }

        $prefixes = (array)($item['active_prefixes'] ?? [(string)($item['ruta'] ?? '')]);
        return $isActive($prefixes);
    }
}

$sidebarMenu = (array)($sidebarMenu ?? ($_SESSION['sidebar_menu'] ?? []));
foreach ($sidebarMenu as $itemNav):
    if (!is_array($itemNav)) {
        continue;
    }
    if (
        class_exists('AuthController')
        && AuthController::esContextoMaestro()
        && !AuthController::esAdministrador()
        && (string)($itemNav['id'] ?? '') === 'evaluaciones_maestro'
    ) {
        continue;
    }
    $rutaNav = trim((string)($itemNav['ruta'] ?? ''));
    if ($rutaNav === '') {
        continue;
    }
    $activoNav = sidebar_nav_item_activo($itemNav, (string)$currentUrl, $isActive);
    $iconoNav = trim((string)($itemNav['icon'] ?? 'bi-circle'));
    $labelNav = (string)($itemNav['label'] ?? 'Enlace');
    $idNav = (string)($itemNav['id'] ?? '');
    $dataTourNav = '';
    if (in_array($idNav, ['material_cap_destino', 'capacitacion_destino'], true)) {
        $dataTourNav = 'sidebar-cap-destino';
    }
?>
    <a class="sidebar-link <?= $activoNav ? 'active' : '' ?>" href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($rutaNav, ENT_QUOTES, 'UTF-8') ?>"<?= $dataTourNav !== '' ? ' data-tour="' . htmlspecialchars($dataTourNav, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
        <span class="sidebar-link-icon"><i class="bi <?= htmlspecialchars($iconoNav, ENT_QUOTES, 'UTF-8') ?>"></i></span>
        <span class="sidebar-link-text"><?= htmlspecialchars($labelNav) ?></span>
    </a>
<?php endforeach; ?>
