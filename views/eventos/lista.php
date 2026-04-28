<?php include VIEWS . '/layout/header.php'; ?>

<?php
$esAdminEventos = (bool)($esAdminEventos ?? AuthController::esAdministrador());
$puedeCrearEvento = $esAdminEventos;
$puedeEditarEvento = $esAdminEventos;
$puedeEliminarEvento = $esAdminEventos;
$puedeGestionarEvento = $puedeEditarEvento || $puedeEliminarEvento;
$eventos = $eventos ?? [];
$urlEventosPublicos = (string)($urlEventosPublicos ?? (rtrim(PUBLIC_URL, '/') . '?url=eventos/proximos'));
$qrUrlEventos = (string)($qrUrl ?? ('https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . urlencode($urlEventosPublicos)));
$modulosEventos = $modulosEventos ?? [];
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;">Eventos</h2>
        <small style="color:#637087;"><?= $esAdminEventos ? 'Los cuatro módulos comparten la misma lógica de acceso público y administración.' : 'Acceso rápido a los módulos públicos.' ?></small>
    </div>
    <div class="header-actions">
        <?php if ($esAdminEventos): ?>
        <div class="action-group action-group-nav">
            <a href="<?= PUBLIC_URL ?>?url=eventos" class="action-pill is-active" aria-current="page">Reuniones</a>
            <a href="<?= PUBLIC_URL ?>?url=eventos/universidad-vida" class="action-pill">Universidad de la Vida</a>
            <a href="<?= PUBLIC_URL ?>?url=eventos/capacitacion-destino" class="action-pill">Capacitación Destino</a>
            <a href="<?= PUBLIC_URL ?>?url=eventos/otros" class="action-pill">Otros</a>
        </div>
        <?php endif; ?>
        <div class="action-group">
            <?php if ($puedeCrearEvento): ?>
            <a href="<?= PUBLIC_URL ?>?url=eventos/crear" class="action-pill">Nuevo evento</a>
            <?php endif; ?>
            <?php if (!$esAdminEventos): ?>
            <a href="<?= htmlspecialchars($urlEventosPublicos) ?>" target="_blank" rel="noopener" class="action-pill">Abrir reuniones</a>
            <?php endif; ?>
            <a href="<?= PUBLIC_URL ?>?url=home" class="action-pill">Volver al panel</a>
        </div>
    </div>
</div>

<div class="card report-card" style="padding:16px; margin-bottom:18px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <h3 style="margin:0;"><?= $esAdminEventos ? 'Módulos de eventos' : 'Accesos públicos' ?></h3>
            <small style="color:#637087;"><?= $esAdminEventos ? 'Cada tarjeta solo muestra el acceso al módulo. El QR y la URL pública quedan dentro.' : 'Selecciona el módulo que deseas abrir.' ?></small>
        </div>
    </div>

    <div class="<?= $esAdminEventos ? 'eventos-module-grid' : 'eventos-access-grid' ?>">
        <?php foreach ($modulosEventos as $moduloItem): ?>
            <?php
            $varianteModulo = (string)($moduloItem['variant'] ?? 'reuniones');
            ?>
            <?php if ($esAdminEventos): ?>
            <article class="eventos-module-card eventos-module-card--<?= htmlspecialchars($varianteModulo) ?>">
                <div class="eventos-module-card__head eventos-module-card__head--simple">
                    <div>
                        <h4><?= htmlspecialchars((string)($moduloItem['titulo'] ?? 'Módulo')) ?></h4>
                        <p><?= htmlspecialchars((string)($moduloItem['descripcion'] ?? '')) ?></p>
                    </div>
                </div>
                <div class="eventos-module-card__actions">
                    <?php if ($esAdminEventos): ?>
                        <a href="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars((string)($moduloItem['route_privada'] ?? 'eventos')) ?>" class="btn btn-primary">Entrar al módulo</a>
                        <a href="<?= htmlspecialchars((string)($moduloItem['url_publica'] ?? '#')) ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">URL pública</a>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars((string)($moduloItem['url_publica'] ?? '#')) ?>" target="_blank" rel="noopener" class="btn btn-primary">Abrir URL pública</a>
                    <?php endif; ?>
                </div>
            </article>
            <?php else: ?>
            <article class="eventos-access-card eventos-module-card--<?= htmlspecialchars($varianteModulo) ?>">
                <h4><?= htmlspecialchars((string)($moduloItem['titulo'] ?? 'Módulo')) ?></h4>
                <a href="<?= htmlspecialchars((string)($moduloItem['url_publica'] ?? '#')) ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm">Abrir</a>
            </article>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($esAdminEventos): ?>
<div class="card report-card" style="padding:16px; margin-bottom:18px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap; margin-bottom:12px;">
        <div>
            <h3 style="margin:0;">Gestión de reuniones</h3>
            <small style="color:#637087;">Este es el módulo principal actual y conserva el listado completo de reuniones.</small>
        </div>
        <?php if ($puedeCrearEvento): ?>
        <a href="<?= PUBLIC_URL ?>?url=eventos/crear" class="btn btn-primary">Crear evento</a>
        <?php endif; ?>
    </div>

    <div class="table-container">
        <table class="data-table eventos-main-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Lugar</th>
                    <th>Descripción</th>
                    <th>Imagen</th>
                    <th>Video</th>
                    <th>Compartible</th>
                    <?php if ($puedeGestionarEvento): ?><th>Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($eventos)): ?>
                    <?php foreach ($eventos as $evento): ?>
                        <?php $esCompartible = (int)($evento['Permitir_Compartir'] ?? 1) === 1; ?>
                        <?php $descripcionEvento = trim((string)($evento['Descripcion_Evento'] ?? '')); ?>
                        <?php $descripcionLarga = mb_strlen($descripcionEvento) > 120; ?>
                        <tr>
                            <td data-label="Nombre" class="col-nowrap col-nombre"><?= htmlspecialchars((string)($evento['Nombre_Evento'] ?? '')) ?></td>
                            <td data-label="Fecha"><?= htmlspecialchars((string)($evento['Fecha_Evento'] ?? '')) ?></td>
                            <td data-label="Hora"><?= htmlspecialchars((string)($evento['Hora_Evento'] ?? '')) ?></td>
                            <td data-label="Lugar" class="col-nowrap col-lugar"><?= htmlspecialchars((string)($evento['Lugar_Evento'] ?? '')) ?></td>
                            <td data-label="Descripción" class="col-descripcion">
                                <?php if ($descripcionEvento === ''): ?>
                                    -
                                <?php elseif ($descripcionLarga): ?>
                                    <details class="evento-descripcion-detalle">
                                        <summary>Ver más</summary>
                                        <div class="evento-descripcion-preview"><?= htmlspecialchars($descripcionEvento) ?></div>
                                    </details>
                                <?php else: ?>
                                    <div class="evento-descripcion-preview"><?= htmlspecialchars($descripcionEvento) ?></div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Imagen">
                                <?php if (!empty($evento['Imagen_Evento'])): ?>
                                    <img src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode((string)$evento['Imagen_Evento']) ?>" alt="Imagen evento" class="evento-media-img">
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td data-label="Video">
                                <?php if (!empty($evento['Video_Evento'])): ?>
                                    <video class="evento-media-video" controls preload="metadata">
                                        <source src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode((string)$evento['Video_Evento']) ?>">
                                        Tu navegador no soporta video.
                                    </video>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td data-label="Compartible">
                                <?php if ($esCompartible): ?>
                                    <span class="meta-pill" style="background:#e8f8ee; color:#1d7a45;">Sí</span>
                                <?php else: ?>
                                    <span class="meta-pill" style="background:#ffe9e9; color:#9a1f1f;">No</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($puedeGestionarEvento): ?>
                            <td data-label="Acciones">
                                <div class="table-actions-inline">
                                    <?php if ($puedeEditarEvento): ?>
                                    <a href="<?= PUBLIC_URL ?>?url=eventos/editar&id=<?= (int)($evento['Id_Evento'] ?? 0) ?>" class="btn btn-sm btn-warning">Editar</a>
                                    <?php endif; ?>
                                    <?php if ($puedeEliminarEvento): ?>
                                    <a href="<?= PUBLIC_URL ?>?url=eventos/eliminar&id=<?= (int)($evento['Id_Evento'] ?? 0) ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este evento?')">Eliminar</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= $puedeGestionarEvento ? '9' : '8' ?>" class="text-center">No hay eventos principales registrados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<style>
.header-actions {
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}

.action-group {
    display:flex;
    align-items:center;
    gap:6px;
    flex-wrap:wrap;
}

.action-pill {
    display:inline-flex;
    align-items:center;
    padding:6px 14px;
    border-radius:999px;
    font-size:13px;
    font-weight:600;
    white-space:nowrap;
    text-decoration:none;
    border:1.5px solid #c7d8ef;
    background:#f0f5fc;
    color:#1f5ea8;
    transition:background .15s, border-color .15s, color .15s;
    cursor:pointer;
}

.action-pill:hover {
    background:#dce9fa;
    border-color:#9bbde0;
    color:#163f7a;
}

.action-pill.is-active {
    background:#1f5ea8;
    border-color:#1f5ea8;
    color:#ffffff;
}

.action-pill.is-active:hover {
    background:#163f7a;
    border-color:#163f7a;
}

.eventos-module-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));
    gap:14px;
}

.eventos-access-grid {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(180px, 1fr));
    gap:10px;
}

.eventos-access-card {
    border:1px solid #d8e3f1;
    border-radius:12px;
    background:#ffffff;
    padding:12px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
}

.eventos-access-card h4 {
    margin:0;
    font-size:18px;
    color:#17365f;
}

.eventos-module-card {
    border:1px solid #d8e3f1;
    border-radius:16px;
    padding:16px;
    background:linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    box-shadow:0 10px 24px rgba(31, 63, 110, 0.08);
}

.eventos-module-card__head {
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
    margin-bottom:16px;
}

.eventos-module-card__head h4 {
    margin:0 0 6px 0;
    font-size:18px;
    color:#17365f;
}

.eventos-module-card__head p {
    margin:0;
    font-size:13px;
    color:#637087;
    line-height:1.55;
}

.eventos-module-card__actions {
    display:flex;
    justify-content:flex-start;
    align-items:center;
    gap:8px;
    margin-top:auto;
}

.eventos-module-card__actions .btn {
    min-width:160px;
}

.eventos-module-card--reuniones {
    border-top:4px solid #1f5ea8;
}

.eventos-module-card--uv {
    border-top:4px solid #1e6b3c;
}

.eventos-module-card--destino {
    border-top:4px solid #b86a15;
}

.eventos-module-card--otros {
    border-top:4px solid #6a3db8;
}

.eventos-module-links {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));
    gap:12px;
}

.eventos-module-link {
    display:flex;
    flex-direction:column;
    gap:6px;
    text-decoration:none;
    border:1px solid #d6e2f1;
    border-radius:14px;
    padding:16px;
    background:linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
    box-shadow:0 6px 18px rgba(41, 73, 128, 0.08);
    transition:transform .16s ease, box-shadow .16s ease, border-color .16s ease;
}

.eventos-module-link:hover {
    transform:translateY(-1px);
    border-color:#afc7e8;
    box-shadow:0 10px 22px rgba(41, 73, 128, 0.12);
}

.eventos-module-link__title {
    font-size:18px;
    font-weight:700;
    color:#1f3f6e;
}

.eventos-module-link__text {
    color:#637087;
    line-height:1.45;
}

.eventos-module-link--uv {
    border-left:4px solid #0b7285;
}

.eventos-module-link--destino {
    border-left:4px solid #7a4e08;
}

.table-actions-inline {
    display:flex;
    gap:6px;
    align-items:center;
    justify-content:center;
    flex-wrap:wrap;
}

.evento-media-img {
    width: 96px;
    height: 64px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #d9e2ef;
    display: block;
}

.evento-media-video {
    width: 140px;
    max-width: 100%;
    border-radius: 8px;
    border: 1px solid #d9e2ef;
    display: block;
}

.eventos-main-table {
    table-layout:auto;
    width:max-content;
    min-width:100%;
}

.eventos-main-table th,
.eventos-main-table td {
    vertical-align:middle;
}

.evento-descripcion-detalle {
    max-width: 320px;
}

.evento-descripcion-detalle summary {
    cursor: pointer;
    color: #1f5ea8;
    font-weight: 700;
    margin-bottom: 6px;
    list-style: none;
}

.evento-descripcion-detalle summary::-webkit-details-marker {
    display: none;
}

.evento-descripcion-preview {
    max-width: 320px;
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
    line-height: 1.45;
}

.col-nowrap {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.eventos-main-table .col-nombre { min-width: 220px; }
.eventos-main-table .col-lugar { min-width: 160px; }
.eventos-main-table .col-descripcion {
    min-width: 220px;
    max-width: 320px;
}

@media (max-width: 720px) {
    .header-actions {
        width: 100%;
        justify-content: stretch;
    }

    .action-group {
        width: 100%;
        justify-content: flex-start;
        overflow-x: auto;
    }

    .action-pill {
        min-height: 40px;
        font-size: 14px;
    }

    .eventos-module-card__head {
        flex-direction:column;
    }

    .eventos-module-card__actions .btn {
        width:100%;
    }

    .eventos-module-links {
        grid-template-columns: 1fr;
    }

    .eventos-access-grid {
        grid-template-columns: 1fr;
    }

    .eventos-access-card {
        flex-direction:column;
        align-items:stretch;
    }

    .eventos-access-card .btn {
        width:100%;
        justify-content:center;
    }

    .eventos-module-link {
        padding: 14px;
    }

    .eventos-main-table td[data-label="Acciones"] .table-actions-inline {
        width: 100%;
        flex-direction: column;
        align-items: stretch;
    }

    .eventos-main-table td[data-label="Acciones"] .btn {
        width: 100%;
        justify-content: center;
    }

    .evento-media-video,
    .evento-media-img {
        max-width: 140px;
        margin-left: auto;
    }

    .evento-descripcion-detalle,
    .evento-descripcion-preview,
    .eventos-main-table .col-descripcion {
        max-width: 100%;
        min-width: 0;
    }
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>