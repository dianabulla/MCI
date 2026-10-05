<?php
$personasSeguimiento = is_array($personasSeguimiento ?? null) ? $personasSeguimiento : [];
$tituloSeguimiento = (string)($tituloSeguimiento ?? 'En seguimiento');
$mostrarCelulaColumna = !empty($mostrarCelulaColumna);
$puedeUsarSeguimiento = AuthController::puedeUsarSeguimientoPersonaListado();
$puedeVerPersonaSeg = AuthController::esAdministrador() || AuthController::puede('personas:ver') || AuthController::puede('celulas:ver');
?>
<div class="enviar-seguimiento-bloque">
    <h4 class="enviar-seguimiento-titulo">
        <i class="bi bi-signpost-2" aria-hidden="true"></i>
        <?= htmlspecialchars($tituloSeguimiento) ?>
        <span class="enviar-seguimiento-count"><?= count($personasSeguimiento) ?></span>
    </h4>
    <?php if ($personasSeguimiento === []): ?>
        <p class="enviar-seguimiento-vacio">No hay personas en seguimiento en este espacio.</p>
    <?php else: ?>
        <div class="table-container" style="margin:0;">
            <table class="data-table celulas-data-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <?php if ($mostrarCelulaColumna): ?>
                            <th>Célula</th>
                        <?php endif; ?>
                        <th>Líder</th>
                        <?php if ($puedeUsarSeguimiento || $puedeVerPersonaSeg): ?>
                            <th>Acción</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($personasSeguimiento as $personaSeg): ?>
                        <?php
                        $idPersonaSeg = (int)($personaSeg['Id_Persona'] ?? 0);
                        $nombreSeg = (string)($personaSeg['nombre'] ?? trim(((string)($personaSeg['Nombre'] ?? '')) . ' ' . ((string)($personaSeg['Apellido'] ?? ''))));
                        if ($nombreSeg === '') {
                            $nombreSeg = 'Sin nombre';
                        }
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($nombreSeg) ?></strong></td>
                            <td><?= htmlspecialchars((string)($personaSeg['telefono'] ?? '')) ?></td>
                            <?php if ($mostrarCelulaColumna): ?>
                                <td><?= htmlspecialchars((string)($personaSeg['celula'] ?? 'Sin célula')) ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars((string)($personaSeg['lider'] ?? '')) ?></td>
                            <?php if ($puedeUsarSeguimiento || $puedeVerPersonaSeg): ?>
                            <td>
                                <div class="celulas-actions-row">
                                    <?php if ($puedeUsarSeguimiento): ?>
                                    <button
                                        type="button"
                                        class="action-seguimiento-btn js-seguimiento-btn is-active"
                                        data-persona-id="<?= $idPersonaSeg ?>"
                                        data-persona-nombre="<?= htmlspecialchars($nombreSeg, ENT_QUOTES, 'UTF-8') ?>"
                                        data-persona-telefono="<?= htmlspecialchars(trim((string)($personaSeg['telefono'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                        data-puede-editar="<?= AuthController::puedeGuardarSeguimientoPersonaListado() ? '1' : '0' ?>"
                                        title="En seguimiento"
                                    >
                                        <i class="bi bi-signpost-2" aria-hidden="true"></i>
                                        <span>En seguimiento</span>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($puedeVerPersonaSeg && $idPersonaSeg > 0): ?>
                                    <a href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= $idPersonaSeg ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--icon" title="Ver persona" aria-label="Ver persona">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
