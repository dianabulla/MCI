<?php
require_once APP . '/Helpers/PermisosCatalogo.php';
require_once APP . '/Helpers/PermisosModulos.php';
require_once APP . '/Helpers/PermisosUiCatalogo.php';
require_once APP . '/Helpers/GestionSistemaAccess.php';
require_once VIEWS . '/layout/header.php';

$puedeEditarMatriz = !empty($puede_editar_matriz);
$indiceRolActivo = (int)($indice_rol_activo ?? 0);

$definiciones_modulos = is_array($definiciones_modulos ?? null) ? $definiciones_modulos : PermisosModulos::definiciones();
$jerarquiaModulos = PermisosModulos::jerarquiaParaPantalla();
$modulosObsoletos = is_array($modulos_obsoletos ?? null) ? $modulos_obsoletos : [];

$rolesProtegidos = [];
foreach ($roles as $r) {
    if (PermisosCatalogo::esRolProtegidoPermisos((int)$r['Id_Rol'], (string)($r['Nombre_Rol'] ?? ''))) {
        $rolesProtegidos[] = (int)$r['Id_Rol'];
    }
}

/** Submódulos ya colocados en la jerarquía + derivados anidados en tarjetas */
$submodulosEnJerarquia = [];
foreach ($jerarquiaModulos as $familia) {
    foreach (array_keys($familia['submodulos']) as $mk) {
        $submodulosEnJerarquia[$mk] = true;
    }
}

/**
 * Módulos en BD/catálogo que deben mostrarse dentro de un grupo (no en "Otros detectados").
 *
 * @return array<string, array{titulo:string, descripcion:string, grupo:string}>
 */
$modulosHuerfanosPorGrupo = static function (array $modulos, array $jerarquia) use ($submodulosEnJerarquia): array {
    $porGrupo = [];
    foreach ($modulos as $mk => $nombre) {
        $mk = (string)$mk;
        if (isset($submodulosEnJerarquia[$mk]) || PermisosUiCatalogo::esModuloDerivado($mk)) {
            continue;
        }
        $grupo = PermisosUiCatalogo::grupoDeModulo($mk);
        if (!isset($jerarquia[$grupo])) {
            $grupo = 'Otros';
        }
        $def = PermisosModulos::definiciones()[$mk] ?? null;
        $porGrupo[$grupo][$mk] = [
            'titulo' => $def ? (string)($def['label'] ?? $mk) : (string)$nombre,
            'descripcion' => $def ? (string)($def['descripcion'] ?? '') : 'Módulo registrado en base de datos.',
        ];
    }

    return $porGrupo;
};
?>

<?php $admin_nav_active = 'permisos'; include VIEWS . '/partials/admin_nav.php'; ?>

<?php if (!$puedeEditarMatriz): ?>
<div class="alert alert-info" style="margin-bottom:14px;">
    Modo <strong>solo lectura</strong>: puede consultar la matriz de permisos. Para activar o desactivar opciones necesita el permiso <em>Editar permisos</em> en el módulo Permisos (o ser administrador global).
</div>
<?php endif; ?>

<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/permisos.css?v=20260520">

<div class="perm-shell">
    <header class="perm-hero">
        <div>
            <h2 class="perm-hero__title"><i class="bi bi-shield-check"></i> Permisos por rol</h2>
            <p class="perm-hero__lead">Configure por <strong>menú del sistema</strong> (Ganar-Consolidar, Comunidad, …), luego cada <strong>sección</strong> (Almas ganadas, Discípulos, …) y sus acciones concretas.</p>
        </div>
        <details class="perm-hero__help">
            <summary><i class="bi bi-question-circle"></i> Ayuda</summary>
            <ul>
                <li><strong>Ver / Crear / Editar / Eliminar</strong> — acceso a cada sección (mismo nombre que en la app).</li>
                <li><strong>Acciones permitidas</strong> — exportar, moderar, editar en listado (Nehemías), etc.</li>
                <li><strong>Columnas visibles</strong> — datos sensibles en tablas (p. ej. cédula en Nehemías).</li>
            </ul>
        </details>
    </header>

    <div class="perm-toolbar card">
        <div class="perm-toolbar__rol">
            <label for="perm-rol-select" class="perm-toolbar__label">Rol a configurar</label>
            <select id="perm-rol-select" class="perm-rol-select">
                <?php foreach ($roles as $i => $rol):
                    $idRolOpt = (int)($rol['Id_Rol'] ?? 0);
                    $activos = 0;
                    foreach ($modulos as $mk => $mn) {
                        if (PermisosUiCatalogo::esModuloDerivado((string)$mk)) {
                            continue;
                        }
                        if (!empty($permisos[$idRolOpt][$mk]['Puede_Ver'])) {
                            $activos++;
                        }
                    }
                ?>
                <option value="<?= $idRolOpt ?>" data-nombre="<?= htmlspecialchars((string)$rol['Nombre_Rol'], ENT_QUOTES, 'UTF-8') ?>" <?= $i === $indiceRolActivo ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$rol['Nombre_Rol']) ?> (<?= $activos ?> secciones con acceso)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="perm-toolbar__search">
            <label for="perm-search" class="perm-toolbar__label">Buscar</label>
            <input type="search" id="perm-search" class="perm-search" placeholder="Ej. almas ganadas, discípulos, nehemias…" autocomplete="off">
        </div>
        <div class="perm-toolbar__bulk" id="perm-toolbar-bulk"<?= $puedeEditarMatriz ? '' : ' hidden' ?>>
            <span class="perm-toolbar__label">Acciones rápidas</span>
            <div class="perm-bulk-btns">
                <button type="button" class="btn-perm-all btn-perm-solo-ver" data-accion="solo_ver">
                    <i class="bi bi-eye"></i> Solo ver todo
                </button>
                <button type="button" class="btn-perm-all btn-perm-activar" data-accion="activar">
                    <i class="bi bi-check-all"></i> Activar todo
                </button>
                <button type="button" class="btn-perm-all btn-perm-quitar" data-accion="desactivar">
                    <i class="bi bi-slash-circle"></i> Quitar todo
                </button>
            </div>
        </div>
        <div class="perm-toolbar__badge" id="perm-rol-badge" hidden>
            <i class="bi bi-shield-fill-check"></i> Rol protegido — acceso total
        </div>
    </div>

    <?php
    $huerfanosPorGrupo = $modulosHuerfanosPorGrupo($modulos, $jerarquiaModulos);
    ?>

    <?php foreach ($roles as $i => $rol):
        $idRol = (int)($rol['Id_Rol'] ?? 0);
        $esRolProtegido = in_array($idRol, $rolesProtegidos, true);
    ?>
    <section class="perm-panel<?= $i === $indiceRolActivo ? ' active' : '' ?>" id="rol-<?= $idRol ?>" data-rol-protegido="<?= $esRolProtegido ? '1' : '0' ?>">

        <?php foreach ($jerarquiaModulos as $grupoNombre => $familia):
            $subs = $familia['submodulos'];
            if (!empty($huerfanosPorGrupo[$grupoNombre])) {
                foreach ($huerfanosPorGrupo[$grupoNombre] as $mk => $info) {
                    $subs[$mk] = $info;
                }
                unset($huerfanosPorGrupo[$grupoNombre]);
            }
            if (empty($subs)) {
                continue;
            }
        ?>
        <div class="perm-family card" data-grupo="<?= htmlspecialchars($grupoNombre, ENT_QUOTES, 'UTF-8') ?>">
            <header class="perm-family__head">
                <h3 class="perm-family__title"><?= htmlspecialchars($grupoNombre) ?></h3>
                <p class="perm-family__lead">Submódulos y permisos del menú «<?= htmlspecialchars($grupoNombre) ?>».</p>
                <?php if (!$esRolProtegido && $puedeEditarMatriz): ?>
                <div class="perm-section__actions">
                    <button type="button" class="perm-grupo-btn" data-accion="solo_ver">Solo ver grupo</button>
                    <button type="button" class="perm-grupo-btn" data-accion="activar">Activar grupo</button>
                </div>
                <?php endif; ?>
            </header>
            <div class="perm-family__grid">
                <?php foreach ($subs as $mk => $sub):
                    $subTitulo = (string)($sub['titulo'] ?? $mk);
                    $subDesc = (string)($sub['descripcion'] ?? '');
                    include VIEWS . '/permisos/partials/submodulo_card.php';
                endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (!empty($huerfanosPorGrupo)): ?>
        <?php foreach ($huerfanosPorGrupo as $grupoNombre => $subs): ?>
        <div class="perm-family card" data-grupo="<?= htmlspecialchars($grupoNombre, ENT_QUOTES, 'UTF-8') ?>">
            <header class="perm-family__head">
                <h3 class="perm-family__title"><?= htmlspecialchars($grupoNombre) ?></h3>
                <p class="perm-family__lead">Módulos adicionales detectados en base de datos.</p>
            </header>
            <div class="perm-family__grid">
                <?php foreach ($subs as $mk => $sub):
                    $subTitulo = (string)($sub['titulo'] ?? $mk);
                    $subDesc = (string)($sub['descripcion'] ?? '');
                    include VIEWS . '/permisos/partials/submodulo_card.php';
                endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <p class="perm-empty-search" id="perm-empty-<?= $idRol ?>" hidden>No hay módulos que coincidan con la búsqueda.</p>
    </section>
    <?php endforeach; ?>

    <?php if (!empty($modulosObsoletos) && $puedeEditarMatriz): ?>
    <details class="perm-maint card">
        <summary>Mantenimiento: módulos obsoletos en base de datos</summary>
        <p>Estos módulos ya no existen en el catálogo activo: <code><?= htmlspecialchars(implode(', ', array_map('strval', $modulosObsoletos))) ?></code></p>
        <button type="button" id="btnLimpiarModulosObsoletos" class="btn btn-sm btn-warning">Limpiar módulos obsoletos</button>
    </details>
    <?php endif; ?>
</div>

<div id="perm-toast" class="perm-toast" role="status" aria-live="polite">
    <i class="bi bi-check-circle"></i> <span id="perm-toast-msg">Permiso actualizado</span>
</div>

<script>
window.PERMISOS_CONFIG = {
    endpoint: <?= json_encode(rtrim(PUBLIC_URL, '/') . '/index.php?url=permisos/actualizar') ?>,
    endpointLimpieza: <?= json_encode(rtrim(PUBLIC_URL, '/') . '/index.php?url=permisos/limpiar-obsoletos') ?>,
    soloLectura: <?= $puedeEditarMatriz ? 'false' : 'true' ?>
};
</script>
<script src="<?= ASSETS_URL ?>/js/permisos.js?v=20260520"></script>

<?php require_once VIEWS . '/layout/footer.php'; ?>
