<?php include VIEWS . '/layout/header.php'; ?>

<?php
$totalModulos = (int)($total_modulos ?? 0);
$totalArchivos = (int)($total_archivos_registrados ?? 0);
$totalOk = (int)($total_archivos_ok ?? 0);
$totalFaltan = (int)($total_archivos_faltantes ?? 0);
$pdfsEnCarpeta = (int)($pdfs_en_carpeta ?? 0);
$puedeSubir = !empty($puede_subir);
$puedeEditar = !empty($puede_editar);
$puedeEliminar = !empty($puede_eliminar);
$soloVerMaterial = !empty($solo_ver_material);
$anioSeleccionado = (int)($anio_seleccionado ?? date('Y'));
$mesAbierto = (int)($mes_abierto ?? (int)date('n'));
$calendario = $calendario ?? [];
$materialesSinClasificar = $materiales_sin_clasificar ?? [];
$semanasPorMes = (int)($semanas_por_mes ?? 5);
$urlBuscarProfesor = (string)($url_buscar_profesor ?? public_app_url('teen/buscarAcudientes'));
$gestionCompleta = !empty($gestion_completa);
$semanaAbierta = (int)($semana_abierta ?? 0);
$semanaActual = (int)($semana_actual ?? (int)ceil((int)date('j') / 7));
$mesActual = (int)($mes_actual ?? (int)date('n'));
?>

<div class="teen-material-page">
    <div class="page-header teen-material-header">
        <div>
            <h2 style="margin:0;">Material Teens</h2>
            <p class="teen-material-subtitle">
                <?= $gestionCompleta
                    ? 'Abre un mes para ver sus semanas; al elegir la semana verás el material.'
                    : 'Material disponible del mes y la semana en curso.' ?>
            </p>
        </div>
        <div class="page-actions personas-mobile-stack teen-nav-pills">
            <?php if (!$soloVerMaterial): ?>
            <a href="<?= PUBLIC_URL ?>index.php?url=teen/registro-menores" class="btn btn-nav-pill">Registro</a>
            <a href="<?= PUBLIC_URL ?>index.php?url=teen/codigos" class="btn btn-nav-pill">Códigos</a>
            <?php endif; ?>
            <a href="<?= PUBLIC_URL ?>index.php?url=teen" class="btn btn-nav-pill active">Material</a>
            <?php if (!$soloVerMaterial): ?>
            <a href="<?= PUBLIC_URL ?>index.php?url=entrega_obsequio" class="btn btn-nav-pill">Obsequios</a>
            <?php endif; ?>
            <?php if ($soloVerMaterial): ?>
            <a href="<?= PUBLIC_URL ?>?url=home/material" class="btn btn-nav-pill">Volver a Material</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($gestionCompleta): ?>
    <div class="teen-stats-row">
        <div class="teen-stat-card">
            <span class="teen-stat-label">Módulos</span>
            <strong class="teen-stat-value"><?= $totalModulos ?></strong>
        </div>
        <div class="teen-stat-card teen-stat-card--ok">
            <span class="teen-stat-label">PDF disponibles</span>
            <strong class="teen-stat-value"><?= $totalOk ?><span class="teen-stat-muted">/<?= $totalArchivos ?></span></strong>
        </div>
        <?php if ($totalFaltan > 0): ?>
        <div class="teen-stat-card teen-stat-card--warn">
            <span class="teen-stat-label">Pendientes</span>
            <strong class="teen-stat-value"><?= $totalFaltan ?></strong>
        </div>
        <?php endif; ?>
        <div class="teen-stat-card">
            <span class="teen-stat-label">En carpeta servidor</span>
            <strong class="teen-stat-value"><?= $pdfsEnCarpeta ?></strong>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= ($tipo ?? '') === 'success' ? 'success' : 'danger' ?> teen-flash" role="alert">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if ($puedeSubir): ?>
    <section class="teen-upload-section" id="formSubidaTeen">
        <div class="teen-upload-card">
            <div class="teen-upload-card__head">
                <div>
                    <h3>Subir material por semana</h3>
                    <p class="teen-upload-hint">Elige mes y semana · varios PDF a la vez (máx. 20 MB c/u)</p>
                </div>
            </div>

            <form action="<?= PUBLIC_URL ?>index.php?url=teen" method="POST" enctype="multipart/form-data" class="teen-upload-form">
                <div class="teen-upload-form__fields teen-upload-form__fields--calendar">
                    <div class="form-group">
                        <label for="anio">Año</label>
                        <select id="anio" name="anio" class="form-control" required>
                            <?php for ($y = (int)date('Y') + 1; $y >= (int)date('Y') - 2; $y--): ?>
                                <option value="<?= $y ?>" <?= $y === $anioSeleccionado ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="mes">Mes</label>
                        <select id="mes" name="mes" class="form-control" required>
                            <option value="">Selecciona…</option>
                            <?php foreach (($nombres_meses ?? []) as $numMes => $nombreMes): ?>
                                <option value="<?= (int)$numMes ?>" <?= (int)$numMes === $mesAbierto ? 'selected' : '' ?>><?= htmlspecialchars($nombreMes, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="semana_mes">Semana del mes</label>
                        <select id="semana_mes" name="semana_mes" class="form-control" required>
                            <option value="">Selecciona…</option>
                            <?php for ($s = 1; $s <= $semanasPorMes; $s++): ?>
                                <option value="<?= $s ?>">Semana <?= $s ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group teen-profesor-field">
                        <label for="profesor_busqueda">Maestro de la semana</label>
                        <input type="hidden" id="id_profesor" name="id_profesor" value="">
                        <input type="text" id="profesor_busqueda" name="profesor_busqueda" class="form-control"
                               placeholder="Buscar en personas o escribir nombre" autocomplete="off">
                        <input type="hidden" id="profesor_nombre" name="profesor_nombre" value="">
                        <div id="profesor_sugerencias" class="teen-sugerencias"></div>
                    </div>

                    <div class="form-group">
                        <label for="titulo">Título <span class="teen-optional">(opcional)</span></label>
                        <input type="text" id="titulo" name="titulo" class="form-control" maxlength="255"
                               placeholder="Si lo dejas vacío se genera automático">
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción <span class="teen-optional">(opcional)</span></label>
                        <textarea id="descripcion" name="descripcion" class="form-control" rows="1"
                                  placeholder="Notas para el equipo"></textarea>
                    </div>

                    <div class="form-group teen-upload-form__files">
                        <label for="archivo_pdf">Archivos PDF</label>
                        <input type="file" id="archivo_pdf" name="archivo_pdf[]" class="form-control"
                               accept="application/pdf" multiple required>
                    </div>
                </div>

                <div class="teen-upload-form__action">
                    <button type="submit" class="btn btn-primary teen-upload-btn">
                        <i class="bi bi-cloud-upload"></i> Publicar en la semana
                    </button>
                </div>
            </form>
        </div>

        <div class="teen-upload-card teen-upload-card--folder" style="margin-top:18px;">
            <div class="teen-upload-card__head">
                <div>
                    <h3>Subir carpeta completa del mes</h3>
                    <p class="teen-upload-hint">
                        Elige año y mes, luego selecciona la carpeta del mes (ej. <strong>agosto</strong>) con subcarpetas
                        <strong>semana-1</strong>, <strong>semana-2</strong>… y opcionalmente <strong>decoracion</strong>, cada una con sus PDF.
                    </p>
                </div>
            </div>

            <form id="formCarpetaMesTeen" class="teen-upload-form" novalidate>
                <div class="teen-upload-form__fields teen-upload-form__fields--calendar">
                    <div class="form-group">
                        <label for="anio_carpeta">Año</label>
                        <select id="anio_carpeta" name="anio" class="form-control" required>
                            <?php for ($y = (int)date('Y') + 1; $y >= (int)date('Y') - 2; $y--): ?>
                                <option value="<?= $y ?>" <?= $y === $anioSeleccionado ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="mes_carpeta">Mes</label>
                        <select id="mes_carpeta" name="mes" class="form-control" required>
                            <option value="">Selecciona…</option>
                            <?php foreach (($nombres_meses ?? []) as $numMes => $nombreMes): ?>
                                <option value="<?= (int)$numMes ?>" <?= (int)$numMes === $mesAbierto ? 'selected' : '' ?>><?= htmlspecialchars($nombreMes, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tema_mes_carpeta">Tema del mes <span class="teen-optional">(opcional)</span></label>
                        <input type="text" id="tema_mes_carpeta" name="tema_mes" class="form-control" maxlength="255"
                               placeholder="Ej. Identidad en Cristo">
                    </div>

                    <div class="form-group teen-upload-form__files teen-upload-form__files--folder">
                        <label for="carpeta_mes">Carpeta del mes</label>
                        <input type="file" id="carpeta_mes" class="form-control"
                               webkitdirectory directory multiple accept="application/pdf,.pdf">
                        <small class="teen-folder-preview" id="preview_carpeta_mes">Ninguna carpeta seleccionada</small>
                    </div>
                </div>

                <div class="teen-upload-form__action">
                    <button type="submit" class="btn btn-success teen-upload-btn" id="btnSubirCarpetaMes">
                        <i class="bi bi-folder-plus"></i> Publicar mes completo
                    </button>
                </div>
            </form>
        </div>
    </section>
    <?php endif; ?>

    <section class="teen-published-section">
        <div class="teen-panel-head teen-panel-head--calendar">
            <?php if ($gestionCompleta): ?>
                <h3>Calendario <?= (int)$anioSeleccionado ?></h3>
                <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="teen-year-form">
                    <input type="hidden" name="url" value="teen">
                    <label for="filtro_anio" class="sr-only">Año</label>
                    <select id="filtro_anio" name="anio" class="form-control form-control-sm" onchange="this.form.submit()">
                        <?php for ($y = (int)date('Y') + 1; $y >= (int)date('Y') - 2; $y--): ?>
                            <option value="<?= $y ?>" <?= $y === $anioSeleccionado ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
                <?php if ($puedeEditar && $totalFaltan > 0): ?>
                    <a href="<?= PUBLIC_URL ?>index.php?url=teen/recuperar-archivos" class="btn btn-sm btn-outline-secondary">Sincronizar archivos</a>
                <?php endif; ?>
            <?php else: ?>
                <?php
                    $temaMesActual = '';
                    if (isset($calendario[$mesActual]['tema_mes'])) {
                        $temaMesActual = trim((string)$calendario[$mesActual]['tema_mes']);
                    }
                ?>
                <h3>
                    <?= htmlspecialchars((string)($nombres_meses[$mesActual] ?? 'Mes actual'), ENT_QUOTES, 'UTF-8') ?> · Semana <?= (int)$semanaActual ?>
                    <?php if ($temaMesActual !== ''): ?>
                        <span class="teen-current-theme"> · Tema: <?= htmlspecialchars($temaMesActual, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </h3>
            <?php endif; ?>
        </div>

        <?php if (empty($calendario)): ?>
            <div class="teen-empty-state">
                <i class="bi bi-journal-x"></i>
                <p>No hay material publicado para esta semana.</p>
            </div>
        <?php endif; ?>

        <div class="teen-months-grid" id="teenMonthsGrid">
            <?php foreach ($calendario as $numMes => $mesData): ?>
                <?php
                    $nombreMes = (string)($mesData['nombre'] ?? ('Mes ' . $numMes));
                    $semanas = $mesData['semanas'] ?? [];
                    $mesExpandido = ($mesAbierto > 0 && (int)$numMes === $mesAbierto) || (!$gestionCompleta && (int)$numMes === $mesActual);
                    $conMaterial = 0;
                    foreach ($semanas as $semMat) {
                        if (!empty($semMat)) {
                            $conMaterial++;
                        }
                    }
                    $semanasLista = $gestionCompleta
                        ? range(1, $semanasPorMes)
                        : array_map('intval', array_keys($semanas));
                    $temaMes = trim((string)($mesData['tema_mes'] ?? ''));
                ?>
                <details class="teen-month-folder" data-mes="<?= (int)$numMes ?>" <?= $mesExpandido ? 'open' : '' ?>>
                    <summary class="teen-month-folder__summary">
                        <span class="teen-month-folder__icon"><i class="bi bi-folder2"></i></span>
                        <span class="teen-month-folder__title"><?= htmlspecialchars($nombreMes, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($temaMes !== ''): ?>
                            <span class="teen-month-folder__theme" title="Tema del mes"><?= htmlspecialchars($temaMes, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <span class="teen-badge teen-badge--muted"><?= $conMaterial ?>/<?= count($semanasLista) ?> sem.</span>
                        <span class="teen-month-folder__hint">Clic para ver semanas</span>
                    </summary>

                    <div class="teen-month-theme-wrap">
                        <?php if ($gestionCompleta && $puedeEditar): ?>
                        <form method="POST" action="<?= PUBLIC_URL ?>index.php?url=teen/guardar-tema-mes" class="teen-month-theme-form">
                            <input type="hidden" name="anio" value="<?= (int)$anioSeleccionado ?>">
                            <input type="hidden" name="mes" value="<?= (int)$numMes ?>">
                            <label for="tema_mes_<?= (int)$numMes ?>">Tema del mes</label>
                            <div class="teen-month-theme-form__row">
                                <input type="text"
                                       id="tema_mes_<?= (int)$numMes ?>"
                                       name="tema_mes"
                                       class="form-control form-control-sm"
                                       maxlength="255"
                                       value="<?= htmlspecialchars($temaMes, ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="Ej. La fe que mueve montañas">
                                <button type="submit" class="btn btn-xs btn-secondary">Guardar tema</button>
                            </div>
                        </form>
                        <?php elseif ($temaMes !== ''): ?>
                            <p class="teen-month-theme-readonly"><strong>Tema del mes:</strong> <?= htmlspecialchars($temaMes, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="teen-weeks-list">
                        <?php
                            $materialDecoracion = $mesData['decoracion'] ?? null;
                            $idMaterialDecoracion = (int)($materialDecoracion['id'] ?? 0);
                            $archivosDecoracion = $materialDecoracion['archivos'] ?? [];
                            $okDecoracion = (int)($materialDecoracion['archivos_ok'] ?? 0);
                            $totDecoracion = (int)($materialDecoracion['archivos_total'] ?? count($archivosDecoracion));
                        ?>
                        <details class="teen-week-folder teen-week-folder--decoracion" id="decoracion-<?= (int)$numMes ?>">
                            <summary class="teen-week-folder__summary">
                                <span class="teen-week-folder__title"><i class="bi bi-palette"></i> Decoración</span>
                                <?php if ($totDecoracion > 0): ?>
                                    <span class="teen-badge <?= $okDecoracion >= $totDecoracion ? 'teen-badge--ok' : 'teen-badge--warn' ?>"><?= $okDecoracion ?>/<?= $totDecoracion ?> PDF</span>
                                <?php else: ?>
                                    <span class="teen-badge teen-badge--muted">Sin material</span>
                                <?php endif; ?>
                            </summary>
                            <div class="teen-week-folder__body">
                                <?php if (!empty($archivosDecoracion)): ?>
                                    <ul class="teen-week-files">
                                        <?php foreach ($archivosDecoracion as $archivo): ?>
                                            <?php
                                                $nombrePdf = (string)($archivo['nombre'] ?? '');
                                                $label = basename($nombrePdf, '.pdf');
                                                if (strlen($label) > 48) { $label = substr($label, 0, 45) . '…'; }
                                                $urlVer = (string)($archivo['url'] ?? '');
                                                $existe = !empty($archivo['existe']);
                                            ?>
                                            <li class="teen-week-files__item">
                                                <span class="teen-week-files__name" title="<?= htmlspecialchars($nombrePdf, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php if ($existe): ?>
                                                    <button type="button" class="btn btn-xs btn-primary"
                                                            data-nombre="<?= htmlspecialchars($nombrePdf, ENT_QUOTES, 'UTF-8') ?>"
                                                            data-url-ver="<?= htmlspecialchars($urlVer, ENT_QUOTES, 'UTF-8') ?>"
                                                            data-url-embed="<?= htmlspecialchars((string)($archivo['url_embed'] ?? $urlVer), ENT_QUOTES, 'UTF-8') ?>"
                                                            onclick="abrirPreviewPdfTeen(this)">Ver</button>
                                                    <a href="<?= htmlspecialchars($urlVer, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-xs btn-outline-secondary">PDF</a>
                                                <?php else: ?>
                                                    <span class="teen-file-tag">Pendiente</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <?php if ($gestionCompleta && ($puedeEditar || $puedeEliminar) && $idMaterialDecoracion > 0): ?>
                                    <div class="teen-week-actions">
                                        <?php if ($puedeEditar): ?>
                                            <a href="<?= PUBLIC_URL ?>index.php?url=teen/editar&id=<?= $idMaterialDecoracion ?>" class="btn btn-xs btn-warning">Editar decoración</a>
                                        <?php endif; ?>
                                        <?php if ($puedeEliminar): ?>
                                            <a href="<?= PUBLIC_URL ?>index.php?url=teen/eliminar&id=<?= $idMaterialDecoracion ?>"
                                               class="btn btn-xs btn-danger"
                                               onclick="return confirm('¿Eliminar material de decoración de este mes?');">Eliminar</a>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="teen-week-empty">Aún no hay PDF de decoración en este mes.</p>
                                <?php endif; ?>
                            </div>
                        </details>

                        <?php foreach ($semanasLista as $s): ?>
                            <?php
                                $s = (int)$s;
                                $material = $semanas[$s] ?? null;
                                $idMaterial = (int)($material['id'] ?? 0);
                                $archivos = $material['archivos'] ?? [];
                                $profesorNombre = trim((string)($material['profesor_nombre'] ?? ''));
                                $okMod = (int)($material['archivos_ok'] ?? 0);
                                $totMod = (int)($material['archivos_total'] ?? count($archivos));
                                $semanaExpandida = ($semanaAbierta > 0 && $s === $semanaAbierta)
                                    || (!$gestionCompleta && $s === $semanaActual);
                            ?>
                            <details class="teen-week-folder" id="semana-<?= (int)$numMes ?>-<?= $s ?>" <?= $semanaExpandida ? 'open' : '' ?>>
                                <summary class="teen-week-folder__summary">
                                    <span class="teen-week-folder__title">Semana <?= $s ?></span>
                                    <?php if ($profesorNombre !== ''): ?>
                                        <span class="teen-week-maestro"><i class="bi bi-person-badge"></i> <?= htmlspecialchars($profesorNombre, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="teen-week-maestro teen-week-maestro--empty">Sin maestro</span>
                                    <?php endif; ?>
                                    <?php if ($totMod > 0): ?>
                                        <span class="teen-badge <?= $okMod >= $totMod ? 'teen-badge--ok' : 'teen-badge--warn' ?>"><?= $okMod ?>/<?= $totMod ?> PDF</span>
                                    <?php else: ?>
                                        <span class="teen-badge teen-badge--muted">Sin material</span>
                                    <?php endif; ?>
                                    <?php if ($puedeSubir && $gestionCompleta): ?>
                                        <button type="button" class="btn btn-xs btn-outline-primary teen-week-upload-btn"
                                                onclick="event.preventDefault(); event.stopPropagation(); preseleccionarSemana(<?= (int)$numMes ?>, <?= $s ?>);">Subir</button>
                                    <?php endif; ?>
                                </summary>

                                <div class="teen-week-folder__body">
                                    <?php if ($puedeEditar && $gestionCompleta): ?>
                                    <form method="POST" action="<?= PUBLIC_URL ?>index.php?url=teen/asignar-profesor" class="teen-week-prof-form">
                                        <input type="hidden" name="anio" value="<?= (int)$anioSeleccionado ?>">
                                        <input type="hidden" name="mes" value="<?= (int)$numMes ?>">
                                        <input type="hidden" name="semana_mes" value="<?= $s ?>">
                                        <?php if ($idMaterial > 0): ?>
                                            <input type="hidden" name="id_material" value="<?= $idMaterial ?>">
                                        <?php endif; ?>
                                        <input type="hidden" name="id_profesor" value="<?= (int)($material['id_profesor'] ?? 0) ?>" class="js-id-profesor-semana">
                                        <input type="text" name="profesor_busqueda" value="<?= htmlspecialchars($profesorNombre, ENT_QUOTES, 'UTF-8') ?>"
                                               class="form-control form-control-sm js-profesor-semana-input"
                                               placeholder="Asignar maestro" autocomplete="off">
                                        <input type="hidden" name="profesor_nombre" value="<?= htmlspecialchars($profesorNombre, ENT_QUOTES, 'UTF-8') ?>" class="js-profesor-nombre-semana">
                                        <button type="submit" class="btn btn-xs btn-secondary">Guardar maestro</button>
                                    </form>
                                    <?php elseif ($profesorNombre !== ''): ?>
                                        <p class="teen-week-prof-readonly"><strong>Maestro:</strong> <?= htmlspecialchars($profesorNombre, ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php endif; ?>

                                    <?php if (!empty($archivos)): ?>
                                        <ul class="teen-week-files">
                                            <?php foreach ($archivos as $archivo): ?>
                                                <?php
                                                    $nombrePdf = (string)($archivo['nombre'] ?? '');
                                                    $label = basename($nombrePdf, '.pdf');
                                                    if (strlen($label) > 48) { $label = substr($label, 0, 45) . '…'; }
                                                    $urlVer = (string)($archivo['url'] ?? '');
                                                    $existe = !empty($archivo['existe']);
                                                ?>
                                                <li class="teen-week-files__item">
                                                    <span class="teen-week-files__name" title="<?= htmlspecialchars($nombrePdf, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php if ($existe): ?>
                                                        <button type="button" class="btn btn-xs btn-primary"
                                                                data-nombre="<?= htmlspecialchars($nombrePdf, ENT_QUOTES, 'UTF-8') ?>"
                                                                data-url-ver="<?= htmlspecialchars($urlVer, ENT_QUOTES, 'UTF-8') ?>"
                                                                data-url-embed="<?= htmlspecialchars((string)($archivo['url_embed'] ?? $urlVer), ENT_QUOTES, 'UTF-8') ?>"
                                                                onclick="abrirPreviewPdfTeen(this)">Ver</button>
                                                        <a href="<?= htmlspecialchars($urlVer, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-xs btn-outline-secondary">PDF</a>
                                                    <?php else: ?>
                                                        <span class="teen-file-tag">Pendiente</span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php if ($gestionCompleta && ($puedeEditar || $puedeEliminar) && $idMaterial > 0): ?>
                                        <div class="teen-week-actions">
                                            <?php if ($puedeEditar): ?>
                                                <a href="<?= PUBLIC_URL ?>index.php?url=teen/editar&id=<?= $idMaterial ?>" class="btn btn-xs btn-warning">Editar semana</a>
                                            <?php endif; ?>
                                            <?php if ($puedeEliminar): ?>
                                                <a href="<?= PUBLIC_URL ?>index.php?url=teen/eliminar&id=<?= $idMaterial ?>" class="btn btn-xs btn-danger"
                                                   onclick="return confirm('¿Eliminar material de esta semana?');">Eliminar</a>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <p class="teen-week-empty">Aún no hay PDF en esta semana.</p>
                                    <?php endif; ?>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($materialesSinClasificar)): ?>
            <div class="teen-legacy-panel">
                <h4>Material sin clasificar (anterior)</h4>
                <div class="teen-files-panel">
                    <ul class="teen-files-stack">
                        <?php foreach ($materialesSinClasificar as $material): ?>
                            <?php
                                $archivos = $material['archivos'] ?? [];
                                $idMaterial = (int)($material['id'] ?? 0);
                                $tituloMod = (string)($material['titulo'] ?? 'Sin título');
                            ?>
                            <li class="teen-files-stack__group">
                                <div class="teen-files-stack__group-head">
                                    <div class="teen-files-stack__group-title">
                                        <strong><?= htmlspecialchars($tituloMod, ENT_QUOTES, 'UTF-8') ?></strong>
                                    </div>
                                    <div class="teen-files-stack__group-actions">
                                        <?php if ($puedeEditar): ?><a href="<?= PUBLIC_URL ?>index.php?url=teen/editar&id=<?= $idMaterial ?>" class="btn btn-xs btn-warning">Editar</a><?php endif; ?>
                                        <?php if ($puedeEliminar): ?><a href="<?= PUBLIC_URL ?>index.php?url=teen/eliminar&id=<?= $idMaterial ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar?');">Eliminar</a><?php endif; ?>
                                    </div>
                                </div>
                                <?php foreach ($archivos as $archivo): ?>
                                    <?php if (empty($archivo['existe'])) continue; ?>
                                    <div class="teen-files-stack__row">
                                        <span class="teen-files-stack__name"><?= htmlspecialchars(basename((string)($archivo['nombre'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                                        <a href="<?= htmlspecialchars((string)($archivo['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-xs btn-outline-secondary">PDF</a>
                                    </div>
                                <?php endforeach; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<div id="modalPreviewPdfTeen" class="teen-pdf-modal" style="display:none;" aria-hidden="true">
    <div class="teen-pdf-modal__backdrop" onclick="cerrarPreviewPdfTeen()"></div>
    <div class="teen-pdf-modal__panel" role="dialog" aria-modal="true" aria-labelledby="teenPdfPreviewTitle">
        <header class="teen-pdf-modal__header">
            <div>
                <h3 id="teenPdfPreviewTitle">Vista previa</h3>
                <p id="teenPdfPreviewSubtitle" class="teen-pdf-modal__subtitle"></p>
            </div>
            <div class="teen-pdf-modal__header-actions">
                <a id="teenPdfPreviewOpenTab" href="#" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Abrir en pestaña</a>
                <button type="button" class="teen-pdf-modal__close" onclick="cerrarPreviewPdfTeen()" aria-label="Cerrar">&times;</button>
            </div>
        </header>
        <div class="teen-pdf-modal__body">
            <iframe
                id="teenPdfPreviewFrame"
                title="Documento PDF"
                src="about:blank"
            ></iframe>
        </div>
    </div>
</div>

<div id="modalVistasTeen" class="modal teen-modal" style="display:none;" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="tituloModalVistasTeen">Visualizaciones</h3>
            <button type="button" class="close" onclick="cerrarModalVistasTeen()" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-body" id="contenidoModalVistasTeen">
            <p>Cargando…</p>
        </div>
    </div>
</div>

<style>
.teen-material-page {
    --teen-accent: #d1457b;
    --teen-accent-dark: #b83868;
    --teen-border: #e3eaf4;
    --teen-bg: #f8fafc;
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 0 4px 24px;
    box-sizing: border-box;
}

.teen-material-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.teen-material-subtitle {
    margin: 4px 0 0;
    color: #64748b;
    font-size: 0.95rem;
}

.teen-nav-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.teen-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}

.teen-stat-card {
    background: #fff;
    border: 1px solid var(--teen-border);
    border-radius: 12px;
    padding: 12px 16px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.teen-stat-label {
    display: block;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    margin-bottom: 4px;
}

.teen-stat-value {
    font-size: 1.5rem;
    color: #0f172a;
    line-height: 1.2;
}

.teen-stat-muted {
    font-size: 1rem;
    color: #94a3b8;
    font-weight: 500;
}

.teen-stat-card--ok .teen-stat-value { color: #15803d; }
.teen-stat-card--warn { border-color: #fcd34d; background: #fffbeb; }
.teen-stat-card--warn .teen-stat-value { color: #b45309; }

.teen-flash { margin-bottom: 16px; border-radius: 10px; }

.teen-upload-section {
    margin-bottom: 24px;
}

.teen-published-section {
    width: 100%;
}

@media (max-width: 640px) {
    .teen-files-stack__row {
        grid-template-columns: 28px 1fr;
        grid-template-rows: auto auto;
    }

    .teen-files-stack__size,
    .teen-files-stack__actions {
        grid-column: 2;
    }

    .teen-pdf-modal__panel {
        width: 100%;
        max-height: 96vh;
    }

    .teen-pdf-modal__body iframe {
        height: 65vh;
    }
}

.teen-panel-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.teen-panel-head h3 {
    margin: 0;
    font-size: 1.15rem;
    color: #1e293b;
}

.teen-files-panel {
    width: 100%;
}

.teen-files-stack {
    list-style: none;
    margin: 0;
    padding: 0;
    background: #fff;
    border: 1px solid var(--teen-border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
}

.teen-files-stack__group + .teen-files-stack__group {
    border-top: 2px solid #e2e8f0;
}

.teen-files-stack__group-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px 14px;
    padding: 8px 14px;
    background: #f1f5f9;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.84rem;
}

.teen-files-stack__group-head--warn {
    background: #fffbeb;
    border-bottom-color: #fde68a;
}

.teen-files-stack__group-title {
    flex: 1 1 200px;
    min-width: 0;
}

.teen-files-stack__group-title strong {
    display: block;
    color: #0f172a;
    font-size: 0.9rem;
    line-height: 1.3;
}

.teen-files-stack__group-desc {
    display: block;
    color: #64748b;
    font-size: 0.78rem;
    margin-top: 2px;
}

.teen-files-stack__group-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.teen-files-stack__date {
    font-size: 0.75rem;
    color: #94a3b8;
}

.teen-files-stack__group-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.teen-files-stack__row {
    display: grid;
    grid-template-columns: 32px 1fr auto auto;
    gap: 10px 14px;
    align-items: center;
    padding: 7px 14px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.86rem;
}

.teen-files-stack__row:last-child {
    border-bottom: none;
}

.teen-files-stack__row--missing {
    background: #fffbeb;
}

.teen-files-stack__row--empty {
    padding: 10px 14px;
    color: #64748b;
    font-size: 0.85rem;
}

.teen-files-stack__icon {
    color: #dc2626;
    font-size: 1.15rem;
    line-height: 1;
}

.teen-files-stack__name {
    color: #1e293b;
    font-weight: 500;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.teen-files-stack__size {
    color: #64748b;
    font-size: 0.78rem;
    white-space: nowrap;
}

.teen-files-stack__actions {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.btn.btn-xs {
    padding: 2px 8px;
    font-size: 0.72rem;
    line-height: 1.4;
    border-radius: 5px;
}

.teen-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 999px;
}

.teen-badge--ok { background: #dcfce7; color: #166534; }
.teen-badge--warn { background: #fef3c7; color: #92400e; }

.teen-meta-item {
    font-size: 0.85rem;
    color: #64748b;
}

.teen-file-list {
    list-style: none;
    margin: 0 0 14px;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.teen-file-item {
    display: grid;
    grid-template-columns: 112px 1fr;
    gap: 14px;
    align-items: start;
    padding: 12px;
    background: #ffffff;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}

.teen-file-item--missing {
    grid-template-columns: 72px 1fr;
    background: #fffbeb;
    border-color: #fde68a;
}

.teen-pdf-thumb {
    display: block;
    width: 112px;
    padding: 0;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.teen-pdf-thumb:hover {
    border-color: var(--teen-accent);
    box-shadow: 0 4px 12px rgba(209, 69, 123, 0.2);
}

.teen-pdf-thumb__frame {
    display: block;
    width: 100%;
    height: 140px;
    background: #ffffff;
    overflow: hidden;
}

.teen-pdf-thumb__frame iframe {
    width: 100%;
    height: 100%;
    border: 0;
    background: #ffffff;
    pointer-events: none;
    transform: scale(1);
}

.teen-pdf-thumb__overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: rgba(255, 255, 255, 0.82);
    color: #be185d;
    font-size: 0.82rem;
    font-weight: 600;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.teen-pdf-thumb:hover .teen-pdf-thumb__overlay {
    opacity: 1;
}

.teen-pdf-thumb--empty {
    width: 72px;
    height: 72px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fef3c7;
    color: #b45309;
    font-size: 1.5rem;
    cursor: default;
    border-color: #fde68a;
}

.teen-file-details {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.teen-file-name {
    font-size: 0.92rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.35;
    word-break: break-word;
}

.teen-file-size {
    font-size: 0.78rem;
    color: #64748b;
}

.teen-file-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 4px;
}

.teen-file-actions .btn-primary {
    background: var(--teen-accent);
    border-color: var(--teen-accent);
}

.teen-file-actions .btn-primary:hover {
    background: var(--teen-accent-dark);
    border-color: var(--teen-accent-dark);
}

.teen-file-missing {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #b45309;
    font-size: 0.88rem;
    min-width: 0;
}

.teen-file-missing span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.teen-file-tag {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #b45309;
    background: #fef3c7;
    padding: 2px 8px;
    border-radius: 6px;
    flex-shrink: 0;
}

.teen-module-card__foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
}

.teen-date {
    font-size: 0.8rem;
    color: #94a3b8;
}

.teen-module-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.teen-empty-state {
    text-align: center;
    padding: 48px 24px;
    background: #fff;
    border: 1px dashed var(--teen-border);
    border-radius: 14px;
    color: #64748b;
}

.teen-empty-state i {
    font-size: 2.5rem;
    color: #cbd5e1;
    display: block;
    margin-bottom: 12px;
}

.teen-empty-hint { font-size: 0.9rem; margin-top: 8px; }

.teen-upload-card {
    background: #fff;
    border: 1px solid var(--teen-border);
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
    width: 100%;
}

.teen-upload-card__head {
    margin-bottom: 16px;
}

.teen-upload-card h3 {
    margin: 0 0 4px;
    font-size: 1.15rem;
    color: #0f172a;
}

.teen-upload-hint {
    margin: 0;
    font-size: 0.88rem;
    color: #64748b;
    line-height: 1.45;
}

.teen-upload-form {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 16px;
}

.teen-upload-form__fields {
    flex: 1 1 520px;
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr;
    gap: 14px 16px;
    min-width: 0;
}

.teen-upload-form__files--folder {
    grid-column: 1 / -1;
}

.teen-folder-preview {
    display: block;
    margin-top: 8px;
    font-size: 0.82rem;
    color: #64748b;
    line-height: 1.4;
}

.teen-upload-card--folder {
    border-top: 1px dashed #e2e8f0;
    padding-top: 18px;
}

.teen-upload-btn.is-loading {
    opacity: 0.75;
    pointer-events: none;
}

.teen-upload-form .form-group {
    margin-bottom: 0;
}

.teen-upload-form label {
    font-weight: 600;
    font-size: 0.88rem;
    margin-bottom: 4px;
    display: block;
}

.teen-optional {
    font-weight: 400;
    color: #94a3b8;
}

.teen-upload-btn {
    white-space: nowrap;
    padding: 10px 22px;
    min-height: 42px;
    background: var(--teen-accent);
    border-color: var(--teen-accent);
}

@media (max-width: 1100px) {
    .teen-upload-form__fields {
        grid-template-columns: 1fr 1fr;
    }

    .teen-upload-form__files {
        grid-column: 1 / -1;
    }
}

@media (max-width: 768px) {
    .teen-upload-form {
        flex-direction: column;
        align-items: stretch;
    }

    .teen-upload-form__fields {
        grid-template-columns: 1fr;
    }

    .teen-upload-form__action .teen-upload-btn {
        width: 100%;
    }

}

.teen-upload-btn:hover {
    background: var(--teen-accent-dark);
    border-color: var(--teen-accent-dark);
}

.teen-pdf-modal {
    position: fixed;
    z-index: 10000;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.teen-pdf-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(241, 245, 249, 0.92);
    backdrop-filter: blur(2px);
}

.teen-pdf-modal__panel {
    position: relative;
    z-index: 1;
    width: min(1100px, 96vw);
    max-height: 92vh;
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.teen-pdf-modal__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 18px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.teen-pdf-modal__header h3 {
    margin: 0;
    font-size: 1.05rem;
    color: #0f172a;
}

.teen-pdf-modal__subtitle {
    margin: 4px 0 0;
    font-size: 0.82rem;
    color: #64748b;
    word-break: break-all;
}

.teen-pdf-modal__header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.teen-pdf-modal__close {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 8px;
    background: #e2e8f0;
    color: #334155;
    font-size: 1.4rem;
    line-height: 1;
    cursor: pointer;
}

.teen-pdf-modal__close:hover {
    background: #cbd5e1;
}

.teen-pdf-modal__body {
    flex: 1;
    min-height: 0;
    background: #ffffff;
    padding: 0;
}

.teen-pdf-modal__body iframe {
    display: block;
    width: 100%;
    height: min(78vh, 820px);
    border: 0;
    background: #ffffff;
}

.teen-modal.modal {
    position: fixed;
    z-index: 9999;
    inset: 0;
    background: rgba(241, 245, 249, 0.88);
    overflow: auto;
}

.teen-modal .modal-content {
    background: #fff;
    margin: 4% auto;
    border-radius: 12px;
    max-width: 960px;
    width: 94%;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.teen-modal .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #eee;
}

.teen-modal .modal-body {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

.teen-modal .close {
    background: none;
    border: none;
    font-size: 1.75rem;
    line-height: 1;
    cursor: pointer;
    color: #64748b;
}

.table-modal {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.table-modal th,
.table-modal td {
    border: 1px solid #e2e8f0;
    padding: 8px 10px;
    text-align: left;
}

.table-modal th {
    background: #f8fafc;
}

.teen-upload-form__fields--calendar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 12px;
}

.teen-panel-head--calendar {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.teen-year-form select {
    min-width: 100px;
}

.teen-months-grid {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.teen-month-folder {
    border: 1px solid var(--teen-border);
    border-radius: 12px;
    background: #fff;
    overflow: hidden;
}

.teen-month-folder__summary {
    list-style: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    background: #f8fafc;
    font-weight: 600;
}

.teen-month-folder__summary::-webkit-details-marker { display: none; }

.teen-month-folder__icon { color: var(--teen-accent); }

.teen-weeks-list {
    display: grid;
    gap: 10px;
    padding: 12px;
}

.teen-week-card {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px;
    background: #fff;
}

.teen-month-folder__hint {
    margin-left: auto;
    font-size: 0.78rem;
    color: #94a3b8;
    font-weight: 400;
}

.teen-week-folder {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fafbfc;
    overflow: hidden;
}

.teen-week-folder__summary {
    list-style: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    padding: 10px 12px;
    font-weight: 600;
}

.teen-week-folder__summary::-webkit-details-marker { display: none; }

.teen-week-folder__title { color: #0f172a; }

.teen-week-folder__body {
    padding: 0 12px 12px;
    border-top: 1px solid #eef2f7;
    background: #fff;
}

.teen-week-upload-btn { margin-left: auto; }

.teen-week-empty,
.teen-week-prof-readonly {
    margin: 10px 0 0;
    color: #64748b;
    font-size: 0.9rem;
}

.teen-week-card__head,
.teen-week-card {
    display: none;
}

.teen-week-maestro {
    display: block;
    font-size: 0.85rem;
    color: #475569;
    margin-top: 4px;
}

.teen-week-maestro--empty { color: #94a3b8; font-style: italic; }

.teen-month-folder__theme {
    flex: 1 1 180px;
    min-width: 0;
    font-size: 0.82rem;
    color: #7c3aed;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.teen-month-theme-wrap {
    margin: 0 0 12px;
    padding: 12px 14px;
    background: linear-gradient(135deg, #f5f3ff 0%, #faf5ff 100%);
    border: 1px solid #e9d5ff;
    border-radius: 10px;
}

.teen-month-theme-form label {
    display: block;
    margin: 0 0 6px;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #6d28d9;
}

.teen-month-theme-form__row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.teen-month-theme-form__row .form-control {
    flex: 1 1 220px;
    min-width: 0;
}

.teen-month-theme-readonly {
    margin: 0;
    font-size: 0.92rem;
    color: #5b21b6;
}

.teen-current-theme {
    font-size: 0.92rem;
    font-weight: 600;
    color: #7c3aed;
}

.teen-week-folder--decoracion {
    border-left: 3px solid #c084fc;
}

.teen-week-folder--decoracion .teen-week-folder__title {
    color: #7e22ce;
}
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 8px;
}

.teen-week-prof-form .form-control { max-width: 260px; }

.teen-week-files {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.teen-week-files__item {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.teen-week-files__name {
    flex: 1;
    min-width: 120px;
    font-size: 0.9rem;
}

.teen-week-actions { margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap; }

.teen-badge--muted { background: #f1f5f9; color: #64748b; }

.teen-legacy-panel {
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px dashed #cbd5e1;
}

.teen-sugerencias {
    position: relative;
}

.teen-sugerencias-list {
    position: absolute;
    z-index: 20;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    max-height: 200px;
    overflow-y: auto;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
}

.teen-sugerencias-list button {
    display: block;
    width: 100%;
    text-align: left;
    border: 0;
    background: #fff;
    padding: 8px 12px;
    cursor: pointer;
}

.teen-sugerencias-list button:hover { background: #f8fafc; }

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
}
</style>

<script>
function abrirPreviewPdfTeen(trigger) {
    var modal = document.getElementById('modalPreviewPdfTeen');
    var frame = document.getElementById('teenPdfPreviewFrame');
    var titulo = document.getElementById('teenPdfPreviewTitle');
    var subtitulo = document.getElementById('teenPdfPreviewSubtitle');
    var enlaceTab = document.getElementById('teenPdfPreviewOpenTab');

    if (!modal || !frame || !trigger) {
        return;
    }

    var nombre = trigger.getAttribute('data-nombre') || 'Documento PDF';
    var urlVer = trigger.getAttribute('data-url-ver') || '';
    var urlEmbed = trigger.getAttribute('data-url-embed') || urlVer;
    var src = urlVer;

    if (urlVer.indexOf('/uploads/teens/') !== -1) {
        src = urlVer + '#view=FitH&toolbar=1&navpanes=0';
    }

    titulo.textContent = 'Vista previa del material';
    subtitulo.textContent = nombre;
    enlaceTab.href = urlVer;
    frame.src = 'about:blank';
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    window.setTimeout(function() {
        frame.src = src;
    }, 50);
}

function cerrarPreviewPdfTeen() {
    var modal = document.getElementById('modalPreviewPdfTeen');
    var frame = document.getElementById('teenPdfPreviewFrame');
    if (!modal) {
        return;
    }
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if (frame) {
        frame.src = 'about:blank';
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarPreviewPdfTeen();
    }
});

function verDetalleVistas(archivo) {
    const modal = document.getElementById('modalVistasTeen');
    const contenido = document.getElementById('contenidoModalVistasTeen');
    const titulo = document.getElementById('tituloModalVistasTeen');

    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    titulo.textContent = 'Visualizaciones';
    contenido.innerHTML = '<p>Cargando…</p>';

    fetch('<?= PUBLIC_URL ?>index.php?url=teen/detalleVistas&archivo=' + encodeURIComponent(archivo))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (!data.success) {
                contenido.innerHTML = '<p class="text-danger">' + escaparHtml(data.message || 'Error al consultar') + '</p>';
                return;
            }

            var html = '<p><strong>Archivo:</strong> ' + escaparHtml(data.archivo) + '</p>';
            html += '<p><strong>Personas:</strong> ' + (data.total_personas || 0) + '</p>';

            if (!data.vistas || !data.vistas.length) {
                html += '<p style="color:#64748b;">Sin visualizaciones registradas.</p>';
            } else {
                html += '<div class="table-container"><table class="table-modal"><thead><tr>';
                html += '<th>Nombre</th><th>Teléfono</th><th>Ministerio</th><th>Vistas</th><th>Primera</th><th>Última</th>';
                html += '</tr></thead><tbody>';
                data.vistas.forEach(function(item) {
                    var nombre = ((item.Nombre || '') + ' ' + (item.Apellido || '')).trim() || '—';
                    html += '<tr><td>' + escaparHtml(nombre) + '</td>';
                    html += '<td>' + escaparHtml(item.Telefono || '') + '</td>';
                    html += '<td>' + escaparHtml(item.Nombre_Ministerio || '') + '</td>';
                    html += '<td>' + (item.total_vistas || 0) + '</td>';
                    html += '<td>' + escaparHtml(item.fecha_primera_vista || '') + '</td>';
                    html += '<td>' + escaparHtml(item.fecha_ultima_vista || '') + '</td></tr>';
                });
                html += '</tbody></table></div>';
            }
            contenido.innerHTML = html;
        })
        .catch(function() {
            contenido.innerHTML = '<p class="text-danger">No se pudo cargar la información.</p>';
        });
}

function cerrarModalVistasTeen() {
    var modal = document.getElementById('modalVistasTeen');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
}

window.addEventListener('click', function(event) {
    var modal = document.getElementById('modalVistasTeen');
    if (event.target === modal) {
        cerrarModalVistasTeen();
    }
});

function escaparHtml(texto) {
    var div = document.createElement('div');
    div.textContent = texto === null || texto === undefined ? '' : texto;
    return div.innerHTML;
}

function preseleccionarSemana(mes, semana) {
    var form = document.getElementById('formSubidaTeen');
    var mesSelect = document.getElementById('mes');
    var semanaSelect = document.getElementById('semana_mes');
    if (mesSelect) mesSelect.value = String(mes);
    if (semanaSelect) semanaSelect.value = String(semana);
    if (form) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

(function () {
    var urlBuscar = <?= json_encode($urlBuscarProfesor, JSON_UNESCAPED_UNICODE) ?>;

    function vincularAutocompletado(input) {
        if (!input || input.dataset.autocompleteReady === '1') {
            return;
        }
        input.dataset.autocompleteReady = '1';

        var contenedor = input.parentElement;
        var lista = document.createElement('div');
        lista.className = 'teen-sugerencias-list';
        lista.style.display = 'none';
        if (contenedor) {
            contenedor.appendChild(lista);
        }

        var form = input.closest('form');
        var hiddenId = form ? form.querySelector('.js-id-profesor-semana, #id_profesor') : null;
        var hiddenNombre = form ? form.querySelector('.js-profesor-nombre-semana, #profesor_nombre') : null;
        var timer = null;

        function cerrarLista() {
            lista.style.display = 'none';
            lista.innerHTML = '';
        }

        function seleccionarPersona(id, nombre) {
            input.value = nombre;
            if (hiddenId) hiddenId.value = String(id || '');
            if (hiddenNombre) hiddenNombre.value = nombre;
            cerrarLista();
        }

        input.addEventListener('input', function () {
            if (hiddenId) hiddenId.value = '';
            if (hiddenNombre) hiddenNombre.value = input.value.trim();
            var term = input.value.trim();
            if (term.length < 2 || !urlBuscar) {
                cerrarLista();
                return;
            }
            clearTimeout(timer);
            timer = setTimeout(function () {
                fetch(urlBuscar + (urlBuscar.indexOf('?') >= 0 ? '&' : '?') + 'term=' + encodeURIComponent(term))
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        lista.innerHTML = '';
                        if (!res || !res.success || !res.data || !res.data.length) {
                            cerrarLista();
                            return;
                        }
                        res.data.forEach(function (p) {
                            var btn = document.createElement('button');
                            btn.type = 'button';
                            var nombre = ((p.Nombre || '') + ' ' + (p.Apellido || '')).trim();
                            btn.textContent = nombre;
                            btn.addEventListener('click', function () {
                                seleccionarPersona(p.Id_Persona || 0, nombre);
                            });
                            lista.appendChild(btn);
                        });
                        lista.style.display = 'block';
                    })
                    .catch(cerrarLista);
            }, 250);
        });

        input.addEventListener('blur', function () {
            setTimeout(cerrarLista, 180);
        });
    }

    document.querySelectorAll('#profesor_busqueda, .js-profesor-semana-input').forEach(vincularAutocompletado);

    var monthsGrid = document.getElementById('teenMonthsGrid');
    if (monthsGrid) {
        monthsGrid.querySelectorAll('.teen-month-folder').forEach(function (mesEl) {
            mesEl.addEventListener('toggle', function () {
                if (!mesEl.open) {
                    return;
                }
                monthsGrid.querySelectorAll('.teen-month-folder').forEach(function (otro) {
                    if (otro !== mesEl) {
                        otro.open = false;
                    }
                });
            });
        });
    }

    var inputCarpetaMes = document.getElementById('carpeta_mes');
    var previewCarpetaMes = document.getElementById('preview_carpeta_mes');
    var formCarpetaMes = document.getElementById('formCarpetaMesTeen');
    var btnSubirCarpetaMes = document.getElementById('btnSubirCarpetaMes');
    var urlSubirMes = <?= json_encode(PUBLIC_URL . 'index.php?url=teen/subir-mes', JSON_UNESCAPED_UNICODE) ?>;

    function resumirCarpetaMes(files) {
        var pdfs = 0;
        var semanas = {};
        var tieneDecoracion = false;
        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            if (!/\.pdf$/i.test(f.name || '')) {
                continue;
            }
            pdfs++;
            var ruta = (f.webkitRelativePath || f.name || '').replace(/\\/g, '/');
            var partes = ruta.split('/');
            for (var j = 0; j < partes.length; j++) {
                if (/^decoraci[oó]n$/i.test(partes[j])) {
                    tieneDecoracion = true;
                    break;
                }
                if (/^semana[\s\-_]?(\d+)$/i.test(partes[j]) || /^week[\s\-_]?(\d+)$/i.test(partes[j])) {
                    semanas[partes[j].toLowerCase()] = true;
                    break;
                }
            }
        }
        var nSem = Object.keys(semanas).length;
        if (pdfs === 0) {
            return 'No hay PDF en la carpeta seleccionada.';
        }
        var partesResumen = [pdfs + ' PDF'];
        if (nSem > 0) {
            partesResumen.push(nSem + ' semana(s)');
        }
        if (tieneDecoracion) {
            partesResumen.push('decoración');
        }
        if (nSem === 0 && !tieneDecoracion) {
            partesResumen.push('revisa nombres semana-1… o decoracion');
        }
        return partesResumen.join(' · ');
    }

    if (inputCarpetaMes && previewCarpetaMes) {
        inputCarpetaMes.addEventListener('change', function () {
            previewCarpetaMes.textContent = inputCarpetaMes.files.length
                ? resumirCarpetaMes(inputCarpetaMes.files)
                : 'Ninguna carpeta seleccionada';
        });
    }

    if (formCarpetaMes) {
        formCarpetaMes.addEventListener('submit', function (ev) {
            ev.preventDefault();

            var anio = document.getElementById('anio_carpeta');
            var mes = document.getElementById('mes_carpeta');
            var temaMesInput = document.getElementById('tema_mes_carpeta');
            if (!inputCarpetaMes || !inputCarpetaMes.files.length) {
                alert('Selecciona la carpeta del mes con las subcarpetas de cada semana.');
                return;
            }
            if (!mes || !mes.value) {
                alert('Selecciona el mes al que corresponde la carpeta.');
                return;
            }

            var fd = new FormData();
            fd.append('anio', anio ? anio.value : '');
            fd.append('mes', mes.value);
            if (temaMesInput && temaMesInput.value.trim() !== '') {
                fd.append('tema_mes', temaMesInput.value.trim());
            }

            var enviados = 0;
            for (var i = 0; i < inputCarpetaMes.files.length; i++) {
                var archivo = inputCarpetaMes.files[i];
                if (!/\.pdf$/i.test(archivo.name || '')) {
                    continue;
                }
                fd.append('archivo_pdf[]', archivo);
                fd.append('ruta_relativa[]', archivo.webkitRelativePath || archivo.name);
                enviados++;
            }

            if (enviados === 0) {
                alert('La carpeta no contiene archivos PDF.');
                return;
            }

            if (btnSubirCarpetaMes) {
                btnSubirCarpetaMes.classList.add('is-loading');
                btnSubirCarpetaMes.disabled = true;
            }
            if (previewCarpetaMes) {
                previewCarpetaMes.textContent = 'Subiendo ' + enviados + ' archivo(s)…';
            }

            fetch(urlSubirMes, {
                method: 'POST',
                body: fd,
                credentials: 'same-origin',
                redirect: 'follow'
            }).then(function (resp) {
                window.location.href = resp.url;
            }).catch(function () {
                alert('No se pudo subir la carpeta. Intenta de nuevo.');
                if (btnSubirCarpetaMes) {
                    btnSubirCarpetaMes.classList.remove('is-loading');
                    btnSubirCarpetaMes.disabled = false;
                }
            });
        });
    }
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
