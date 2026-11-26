<?php
$celugrama = is_array($celugrama ?? null) ? $celugrama : [];
$trimestre = is_array($celugrama['trimestre'] ?? null) ? $celugrama['trimestre'] : [];
$resumenCg = is_array($celugrama['resumen'] ?? null) ? $celugrama['resumen'] : [];
$semanasCg = is_array($celugrama['semanas'] ?? null) ? $celugrama['semanas'] : [];
$miembrosCg = is_array($celugrama['miembros'] ?? null) ? $celugrama['miembros'] : [];
$serieCg = is_array($celugrama['serie_grafica'] ?? null) ? $celugrama['serie_grafica'] : [];
$indicadoresCg = is_array($celugrama['indicadores_mes'] ?? null) ? $celugrama['indicadores_mes'] : [];
$opcionesTrimestre = is_array($celugrama['opciones_trimestre'] ?? null) ? $celugrama['opciones_trimestre'] : [];
$maxGrafica = max(1, (int)($celugrama['max_grafica'] ?? 1));
$idCelulaCg = (int)($celula['Id_Celula'] ?? 0);
$claveTrimestre = (string)($trimestre['clave'] ?? '');
$mensajeCelugrama = trim((string)($mensajeCelugrama ?? ''));
$fechaAperturaVisibleCg = isset($fechaAperturaVisible) ? (string)$fechaAperturaVisible : 'No especificada';
$puedeSeguimientoCg = !empty($celugrama['puede_seguimiento']);
$puedeGuardarSeguimientoCg = !empty($celugrama['puede_guardar_seguimiento']);
$puedeMigrarCg = !empty($celugrama['puede_migrar']);
$puedeEliminarPersonaCg = !empty($celugrama['puede_eliminar_persona']);
$puedeEscaleraCg = !empty($celugrama['puede_escalera']);
$puedeEditarPersonaCg = !empty($celugrama['puede_editar_persona']);
$returnUrlCelulaCg = isset($returnUrlCelula)
    ? (string)$returnUrlCelula
    : (PUBLIC_URL . 'index.php?url=celulas/detalle&id=' . $idCelulaCg);
$puedeNoSeDisponeCg = !empty($celugrama['puede_eliminar_miembro']);
$mostrarAccionesCg = $puedeSeguimientoCg || $puedeMigrarCg || $puedeEliminarPersonaCg || $puedeEscaleraCg || $puedeEditarPersonaCg || $puedeNoSeDisponeCg;
?>

<section class="detail-section celugrama" id="celugrama">
    <div class="celugrama-ficha">
        <div class="celugrama-ficha-item">
            <span class="celugrama-ficha-label">Apertura</span>
            <strong><?= htmlspecialchars($fechaAperturaVisibleCg) ?></strong>
        </div>
        <div class="celugrama-ficha-item">
            <span class="celugrama-ficha-label">Dirección</span>
            <strong><?= htmlspecialchars((string)($celula['Direccion_Celula'] ?? 'No especificada')) ?></strong>
        </div>
        <div class="celugrama-ficha-item">
            <span class="celugrama-ficha-label">Día / hora</span>
            <strong><?= htmlspecialchars(trim(((string)($celula['Dia_Reunion'] ?? '—')) . ' · ' . ((string)($celula['Hora_Reunion'] ?? '—')))) ?></strong>
        </div>
        <div class="celugrama-ficha-item">
            <span class="celugrama-ficha-label">Estado</span>
            <strong><?= htmlspecialchars((string)($celula['Estado_Celula'] ?? 'Activa')) ?></strong>
        </div>
        <div class="celugrama-ficha-item">
            <span class="celugrama-ficha-label">Ministerio</span>
            <strong><?= htmlspecialchars((string)($celula['Nombre_Ministerio'] ?? 'No especificado')) ?></strong>
        </div>
        <div class="celugrama-ficha-item">
            <span class="celugrama-ficha-label">Líder</span>
            <strong><?= htmlspecialchars((string)($celula['Nombre_Lider'] ?? 'No especificado')) ?></strong>
        </div>
        <div class="celugrama-ficha-item">
            <span class="celugrama-ficha-label">Pastor</span>
            <strong><?= htmlspecialchars(trim((string)($celula['Nombre_Pastor'] ?? $celula['Pastor_Principal'] ?? '')) ?: 'No especificado') ?></strong>
        </div>
        <div class="celugrama-ficha-item">
            <span class="celugrama-ficha-label">Líder de 12</span>
            <strong><?= htmlspecialchars((string)($celula['Nombre_Lider_Inmediato'] ?? 'No especificado')) ?></strong>
        </div>
        <div class="celugrama-ficha-item">
            <span class="celugrama-ficha-label">Anfitrión</span>
            <strong><?= htmlspecialchars((string)($celula['Nombre_Anfitrion'] ?? 'No especificado')) ?></strong>
        </div>
    </div>

    <div class="celugrama-head">
        <div>
            <h3>Resumen del trimestre</h3>
            <p class="celugrama-sub">Solo movimientos de este trimestre: asistencia reportada, ganados y formación con fecha.</p>
        </div>
        <form method="GET" class="celugrama-trimestre-form" action="<?= PUBLIC_URL ?>index.php">
            <input type="hidden" name="url" value="celulas/detalle">
            <input type="hidden" name="id" value="<?= $idCelulaCg ?>">
            <label for="celugrama_trimestre">Trimestre</label>
            <select id="celugrama_trimestre" name="trimestre" class="form-control" onchange="this.form.submit()">
                <?php foreach ($opcionesTrimestre as $op): ?>
                    <option value="<?= htmlspecialchars((string)$op['clave']) ?>" <?= $claveTrimestre === (string)$op['clave'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$op['etiqueta']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($mensajeCelugrama === 'ok'): ?>
        <div class="alert alert-success celugrama-alert">Celugrama actualizado.</div>
    <?php endif; ?>

    <div class="celugrama-resumen">
        <div class="celugrama-kpi" title="Suma de asistentes de las semanas reportadas ÷ cantidad de semanas reportadas">
            <span class="celugrama-kpi-label">Promedio / semana</span>
            <strong class="celugrama-kpi-value"><?= htmlspecialchars((string)($resumenCg['promedio_asistencia'] ?? 0)) ?></strong>
        </div>
        <div class="celugrama-kpi">
            <span class="celugrama-kpi-label">Semanas reportadas</span>
            <strong class="celugrama-kpi-value"><?= (int)($resumenCg['semanas_reportadas'] ?? 0) ?>/<?= (int)($resumenCg['semanas_totales'] ?? 0) ?></strong>
        </div>
        <div class="celugrama-kpi">
            <span class="celugrama-kpi-label">Invitados célula</span>
            <strong class="celugrama-kpi-value"><?= (int)($resumenCg['invitados_celula'] ?? 0) ?></strong>
        </div>
        <div class="celugrama-kpi">
            <span class="celugrama-kpi-label">Invitados a iglesia</span>
            <strong class="celugrama-kpi-value"><?= (int)($resumenCg['invitados_coliseo'] ?? 0) ?></strong>
        </div>
        <div class="celugrama-kpi">
            <span class="celugrama-kpi-label">Encuentro</span>
            <strong class="celugrama-kpi-value"><?= (int)($resumenCg['con_encuentro'] ?? 0) ?></strong>
        </div>
        <div class="celugrama-kpi">
            <span class="celugrama-kpi-label">Pos-encuentro / UV</span>
            <strong class="celugrama-kpi-value"><?= (int)($resumenCg['con_pos_encuentro'] ?? 0) ?></strong>
        </div>
        <div class="celugrama-kpi">
            <span class="celugrama-kpi-label">Cap. Destino</span>
            <strong class="celugrama-kpi-value"><?= (int)($resumenCg['con_cap'] ?? 0) ?></strong>
        </div>
        <div class="celugrama-kpi">
            <span class="celugrama-kpi-label">No realizadas</span>
            <strong class="celugrama-kpi-value"><?= (int)($resumenCg['celulas_no_realizadas'] ?? 0) ?></strong>
        </div>
    </div>

    <div class="celugrama-grafica-wrap">
        <div class="celugrama-grafica-title">Asistencia por semana</div>
        <div class="celugrama-grafica" role="img" aria-label="Gráfica de asistencia semanal del trimestre">
            <?php foreach ($serieCg as $punto): ?>
                <?php
                $totalPunto = (int)($punto['total'] ?? 0);
                $altura = $maxGrafica > 0 ? max(4, (int)round(($totalPunto / $maxGrafica) * 100)) : 4;
                if (empty($punto['reportada']) && $totalPunto === 0) {
                    $altura = 4;
                }
                ?>
                <div class="celugrama-barra <?= !empty($punto['reportada']) ? 'is-reportada' : 'is-vacia' ?>" title="<?= htmlspecialchars((string)$punto['etiqueta'] . ': ' . $totalPunto . (!empty($punto['reportada']) ? '' : ' · sin reporte')) ?>">
                    <span class="celugrama-barra-valor"><?= $totalPunto ?></span>
                    <span class="celugrama-barra-fill" style="height: <?= $altura ?>%;"></span>
                    <span class="celugrama-barra-label"><?= htmlspecialchars((string)$punto['etiqueta']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="celugrama-form">
            <div class="celugrama-bloque-titulo-row">
                <div>
                    <h4 class="celugrama-block-title">Miembros, asistencia y crecimiento</h4>
                    <p class="celugrama-hint">Integrantes de la célula con indicador, proceso y total de asistencias del trimestre. El crecimiento sale de formación.</p>
                </div>
                <?php if (!empty($puedeReportarCelula)): ?>
                <button type="button" class="btn btn-sm celulas-action-btn celulas-action-btn--report" id="btnAbrirReporteCelugrama2">
                    <i class="bi bi-check2-square" aria-hidden="true"></i>
                    <span>Reportar célula</span>
                </button>
                <?php endif; ?>
            </div>

            <?php if ($miembrosCg === []): ?>
                <p class="celugrama-empty">No hay miembros en esta célula.</p>
            <?php else: ?>
                <div class="celugrama-matriz-wrap">
                    <table class="celugrama-matriz table-no-card">
                        <thead>
                            <tr>
                                <th class="celugrama-sticky">Integrante</th>
                                <th class="celugrama-col-ind">Indicador</th>
                                <th class="celugrama-col-proc">Proceso</th>
                                <th class="celugrama-col-num">Asistencias</th>
                                <th class="celugrama-col-flag">Enc.</th>
                                <th class="celugrama-col-flag">Pos.</th>
                                <th class="celugrama-col-flag">Bautizo</th>
                                <th class="celugrama-col-flag">Cap. Destino</th>
                                <?php if ($mostrarAccionesCg): ?>
                                <th class="celugrama-col-acciones">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($miembrosCg as $fila): ?>
                                <?php
                                $indicadorFila = (string)($fila['indicador'] ?? 'Sin asistencia');
                                $claseInd = $indicadorFila === 'Frecuente'
                                    ? 'badge-success'
                                    : ($indicadorFila === 'Regular' ? 'badge-warning' : 'badge-secondary');
                                $idPersFila = (int)($fila['id_persona'] ?? 0);
                                $nombreFila = (string)($fila['nombre'] ?? 'Sin nombre');
                                $telFila = (string)($fila['telefono'] ?? '');
                                ?>
                                <tr data-id-persona="<?= $idPersFila ?>">
                                    <td class="celugrama-sticky">
                                        <div class="celugrama-nombre"><?= htmlspecialchars($nombreFila) ?></div>
                                        <div class="celugrama-meta"><?= htmlspecialchars($telFila) ?> · Doc. <?= htmlspecialchars((string)$fila['documento']) ?></div>
                                    </td>
                                    <td class="celugrama-col-ind">
                                        <span class="badge <?= $claseInd ?>"><?= htmlspecialchars($indicadorFila) ?></span>
                                    </td>
                                    <td class="celugrama-col-proc">
                                        <span class="celugrama-proceso js-cg-proceso" title="<?= htmlspecialchars((string)($fila['proceso'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string)($fila['proceso'] ?? 'Sin proceso')) ?>
                                        </span>
                                    </td>
                                    <td class="celugrama-col-num"><strong><?= (int)$fila['total_trimestre'] ?></strong></td>
                                    <td class="celugrama-col-flag js-cg-enc"><?= !empty($fila['encuentro']) ? 'Sí' : '—' ?></td>
                                    <td class="celugrama-col-flag js-cg-pos"><?= !empty($fila['pos_encuentro']) ? 'Sí' : '—' ?></td>
                                    <td class="celugrama-col-flag js-cg-bau"><?= !empty($fila['bautismo']) ? 'Sí' : '—' ?></td>
                                    <td class="celugrama-col-flag js-cg-cap"><?= htmlspecialchars((string)(
                                        !empty($fila['cap_destino']) ? ($fila['nivel_cap_texto'] ?? 'Sí') : '—'
                                    )) ?></td>
                                    <?php if ($mostrarAccionesCg): ?>
                                    <td class="celugrama-col-acciones">
                                        <div class="cg-acciones">
                                            <?php if ($puedeSeguimientoCg): ?>
                                            <button
                                                type="button"
                                                class="action-seguimiento-btn js-seguimiento-btn<?= !empty($fila['en_seguimiento']) ? ' is-active' : '' ?>"
                                                data-persona-id="<?= $idPersFila ?>"
                                                data-persona-nombre="<?= htmlspecialchars($nombreFila, ENT_QUOTES, 'UTF-8') ?>"
                                                data-persona-telefono="<?= htmlspecialchars($telFila, ENT_QUOTES, 'UTF-8') ?>"
                                                data-puede-editar="<?= $puedeGuardarSeguimientoCg ? '1' : '0' ?>"
                                                title="En seguimiento"
                                                aria-label="En seguimiento"
                                            >
                                                <i class="bi bi-signpost-2" aria-hidden="true"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($puedeEscaleraCg): ?>
                                            <button
                                                type="button"
                                                class="cg-acc-btn cg-acc-btn--esc js-cg-abrir-escalera"
                                                data-persona-id="<?= $idPersFila ?>"
                                                data-persona-nombre="<?= htmlspecialchars($nombreFila, ENT_QUOTES, 'UTF-8') ?>"
                                                title="Escalera rápida"
                                                aria-label="Escalera rápida"
                                            >
                                                <svg class="cg-icon-escalera" viewBox="0 0 16 16" width="15" height="15" aria-hidden="true" focusable="false">
                                                    <path fill="currentColor" d="M3.25 1.25h1.4v13.5h-1.4V1.25zm8.1 0h1.4v13.5h-1.4V1.25zM4.65 3.4h6.7v1.15h-6.7V3.4zm0 2.85h6.7v1.15h-6.7V6.25zm0 2.85h6.7v1.15h-6.7V9.1zm0 2.85h6.7v1.15h-6.7v-1.15z"/>
                                                </svg>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($puedeEditarPersonaCg): ?>
                                            <a
                                                class="cg-acc-btn cg-acc-btn--edit"
                                                href="<?= PUBLIC_URL ?>index.php?url=personas/editar&id=<?= $idPersFila ?>&return_url=<?= urlencode($returnUrlCelulaCg) ?>"
                                                title="Editar ficha"
                                                aria-label="Editar ficha"
                                            >
                                                <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($puedeMigrarCg): ?>
                                            <button
                                                type="button"
                                                class="cg-acc-btn cg-acc-btn--mig js-cg-migrar-celula"
                                                data-id-persona="<?= $idPersFila ?>"
                                                data-nombre="<?= htmlspecialchars($nombreFila, ENT_QUOTES, 'UTF-8') ?>"
                                                title="Migrar a otra célula"
                                                aria-label="Migrar a otra célula"
                                            >
                                                <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($puedeNoSeDisponeCg): ?>
                                            <button
                                                type="button"
                                                class="cg-acc-btn cg-acc-btn--nd js-cg-no-disponible"
                                                data-id-persona="<?= $idPersFila ?>"
                                                data-nombre="<?= htmlspecialchars($nombreFila, ENT_QUOTES, 'UTF-8') ?>"
                                                title="No se dispone"
                                                aria-label="No se dispone"
                                            >
                                                <i class="bi bi-person-x" aria-hidden="true"></i>
                                            </button>
                                            <?php endif; ?>
                                            <?php if ($puedeEliminarPersonaCg): ?>
                                            <button
                                                type="button"
                                                class="cg-acc-btn cg-acc-btn--del js-cg-eliminar-persona"
                                                data-id-persona="<?= $idPersFila ?>"
                                                data-nombre="<?= htmlspecialchars($nombreFila, ENT_QUOTES, 'UTF-8') ?>"
                                                title="Eliminar persona"
                                                aria-label="Eliminar persona"
                                            >
                                                <i class="bi bi-trash-fill" aria-hidden="true"></i>
                                            </button>
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

            <h4 class="celugrama-block-title">Indicadores del mes</h4>
            <p class="celugrama-hint">Se calculan solos: promedio y no realizadas (reportes), invitados (ganados del mes), encuentro y pos-encuentro (fecha real de formación).</p>
            <div class="table-container">
                <table class="data-table celugrama-indicadores">
                    <thead>
                        <tr>
                            <th>Mes</th>
                            <th>Promedio</th>
                            <th>Inv. célula</th>
                            <th>Inv. iglesia</th>
                            <th>No realizadas</th>
                            <th>Encuentro</th>
                            <th>Pos-encuentro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($indicadoresCg as $ind): ?>
                            <tr>
                                <td data-label="Mes"><?= htmlspecialchars((string)$ind['nombre']) ?></td>
                                <td data-label="Promedio"><?= htmlspecialchars((string)$ind['promedio']) ?></td>
                                <td data-label="Inv. célula"><?= (int)$ind['invitados_celula'] ?></td>
                                <td data-label="Inv. iglesia"><?= (int)$ind['invitados_coliseo'] ?></td>
                                <td data-label="No realizadas"><?= (int)$ind['celulas_no_realizadas'] ?></td>
                                <td data-label="Encuentro"><?= (int)$ind['enviados_encuentro'] ?></td>
                                <td data-label="Pos-encuentro"><?= (int)$ind['graduados_pos_encuentro'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    </div>
</section>

<style>
.detail-container--celugrama,
.detail-container--celugrama .celugrama {
    max-width: none;
    width: 100%;
}
.celugrama-ficha {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 10px;
    margin-bottom: 16px;
    padding: 12px;
    border: 1px solid #dbe5f3;
    border-radius: 12px;
    background: #f8fbff;
}
.celugrama-ficha-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}
.celugrama-ficha-label {
    font-size: 0.75em;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}
.celugrama-ficha-item strong {
    color: #1e3a5f;
    font-size: 0.92em;
    line-height: 1.3;
    word-break: break-word;
}
.celugrama-bloque-titulo-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 8px;
}
.celugrama-proceso {
    display: inline-block;
    max-width: 140px;
    white-space: normal;
    line-height: 1.2;
    color: #1e3a5f;
    font-weight: 600;
    font-size: 0.8rem;
}
.celugrama-matriz .celugrama-col-ind {
    min-width: 90px;
}
.celugrama-matriz .celugrama-col-proc {
    min-width: 120px;
    text-align: left;
}
.celugrama-head {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    align-items: flex-end;
    margin-bottom: 14px;
}
.celugrama-head h3 { margin: 0 0 4px; }
.celugrama-sub {
    margin: 0;
    color: #5b6b86;
    font-size: 0.92em;
}
.celugrama-trimestre-form {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 180px;
}
.celugrama-trimestre-form label {
    font-size: 0.85em;
    color: #5b6b86;
    margin: 0;
}
.celugrama-alert { margin-bottom: 12px; }
.celugrama-resumen {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px;
    margin-bottom: 16px;
}
.celugrama-kpi {
    background: #f5f8fd;
    border: 1px solid #dbe5f3;
    border-radius: 10px;
    padding: 10px 12px;
}
.celugrama-kpi-label {
    display: block;
    font-size: 0.78em;
    color: #637087;
    margin-bottom: 4px;
}
.celugrama-kpi-value {
    font-size: 1.25rem;
    color: #1e3a5f;
}
.celugrama-grafica-wrap {
    margin-bottom: 14px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px;
    background: #fff;
}
.celugrama-grafica-title {
    font-weight: 700;
    color: #1e3a5f;
    margin-bottom: 10px;
    font-size: 0.95em;
}
.celugrama-grafica {
    display: flex;
    align-items: flex-end;
    gap: 6px;
    min-height: 140px;
    overflow-x: auto;
    padding-bottom: 4px;
}
.celugrama-barra {
    flex: 1 0 28px;
    max-width: 42px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    min-height: 120px;
}
.celugrama-barra-fill {
    width: 100%;
    border-radius: 6px 6px 2px 2px;
    background: #3b82f6;
    min-height: 4px;
}
.celugrama-barra.is-vacia .celugrama-barra-fill {
    background: #e2e8f0;
}
.celugrama-barra-valor {
    font-size: 0.72em;
    color: #334155;
    margin-bottom: 4px;
    font-weight: 700;
}
.celugrama-barra-label {
    font-size: 0.68em;
    color: #64748b;
    margin-top: 6px;
    white-space: nowrap;
}
.celugrama-block-title {
    margin: 16px 0 6px;
    color: #1e3a5f;
    font-size: 1rem;
}
.celugrama-hint {
    margin: 0 0 10px;
    color: #5b6b86;
    font-size: 0.9em;
}
.celugrama-empty { color: #64748b; padding: 8px 0; }
.celugrama-matriz-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
}
.celugrama-matriz {
    width: max-content;
    min-width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: auto;
    margin: 0;
    background: #fff;
}
.celugrama-matriz thead,
.celugrama-matriz tbody,
.celugrama-matriz tr {
    display: table-row-group;
}
.celugrama-matriz thead { display: table-header-group; }
.celugrama-matriz tr { display: table-row; }
.celugrama-matriz th,
.celugrama-matriz td {
    display: table-cell;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    padding: 8px 6px;
    border-bottom: 1px solid #eef2f7;
    font-size: 0.82rem;
    box-sizing: border-box;
}
.celugrama-matriz th {
    background: #f1f5f9;
    color: #334155;
    font-weight: 700;
    position: sticky;
    top: 0;
    z-index: 2;
}
.celugrama-matriz th::before,
.celugrama-matriz td::before {
    content: none !important;
    display: none !important;
}
.celugrama-matriz .celugrama-semana,
.celugrama-matriz .celugrama-dot-cell {
    min-width: 42px;
    width: 42px;
    max-width: 42px;
    padding-left: 4px;
    padding-right: 4px;
}
.celugrama-matriz .celugrama-col-num,
.celugrama-matriz .celugrama-col-flag {
    min-width: 44px;
    padding-left: 8px;
    padding-right: 8px;
}
.celugrama-matriz .celugrama-sticky {
    text-align: left;
    position: sticky;
    left: 0;
    z-index: 3;
    min-width: 180px;
    max-width: 220px;
    background: #fff;
    box-shadow: 4px 0 8px rgba(15, 23, 42, 0.04);
}
.celugrama-matriz thead .celugrama-sticky {
    background: #f1f5f9;
    z-index: 4;
}
.celugrama-matriz tbody tr:hover .celugrama-sticky {
    background: #f8fafc;
}
.celugrama-nombre {
    font-weight: 600;
    color: #1e3a5f;
    white-space: normal;
    line-height: 1.25;
}
.celugrama-meta { font-size: 0.8em; color: #64748b; }
.celugrama-dot-cell {
    text-align: center;
}
.celugrama-dot {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 1.5px solid #94a3b8;
    background: #fff;
    vertical-align: middle;
    box-sizing: border-box;
}
.celugrama-dot.is-on {
    background: #16a34a;
    border-color: #15803d;
}
.celugrama-tag {
    display: inline-block;
    margin-left: 6px;
    font-size: 0.72em;
    color: #92400e;
    background: #fef3c7;
    border-radius: 999px;
    padding: 1px 6px;
}
.celugrama-num { max-width: 90px; }
.celugrama-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 14px;
}
.celugrama-col-acciones {
    min-width: 140px;
    text-align: left !important;
}
.cg-acciones {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    flex-wrap: wrap;
}
.cg-acc-btn {
    width: 30px;
    height: 30px;
    padding: 0;
    border-radius: 8px;
    border: 1px solid #d6e1f0;
    background: #f8fafc;
    color: #334155;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    text-decoration: none;
    line-height: 1;
}
.cg-acc-btn:hover { background: #eef4fb; color: #1e3a5f; }
.cg-acc-btn--esc { border-color: #bfdbfe; color: #1d4ed8; background: #eff6ff; }
.cg-acc-btn--esc:hover { background: #dbeafe; }
.cg-acc-btn--esc .cg-icon-escalera { display: block; }
.cg-acc-btn--edit { border-color: #f1ddb1; color: #8a6400; background: #fff8e8; }
.cg-acc-btn--edit:hover { background: #fff1d8; }
.cg-acc-btn--nd { border-color: #f5d0b5; color: #c2410c; background: #fff7ed; }
.cg-acc-btn--nd:hover { background: #ffedd5; }
.cg-acc-btn--mig { border-color: #99f6e4; color: #0f766e; background: #ecfdf5; }
.cg-acc-btn--mig:hover { background: #ccfbf1; color: #115e59; }
.cg-acc-btn--del { border-color: #fecaca; color: #b91c1c; background: #fff1f2; }
.cg-acc-btn--del:hover { background: #fee2e2; }
.cg-acciones .action-seguimiento-btn {
    width: 30px;
    height: 30px;
    padding: 0;
    justify-content: center;
}
.cg-acciones .action-seguimiento-btn span { display: none; }

.cg-escalera-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1350;
}
.cg-escalera-modal.is-open { display: flex; }
.cg-escalera-modal__overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
}
.cg-escalera-modal__dialog {
    position: relative;
    width: min(520px, calc(100vw - 24px));
    max-height: calc(100vh - 32px);
    overflow: auto;
    background: #fff;
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 24px 54px rgba(15, 23, 42, 0.28);
}
.cg-escalera-modal__head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
}
.cg-escalera-modal__head h3 { margin: 0; color: #1e3a5f; font-size: 1.05rem; }
.cg-escalera-modal__close {
    border: 0;
    background: transparent;
    font-size: 1.6rem;
    line-height: 1;
    color: #64748b;
    cursor: pointer;
}
.cg-escalera-modal__sub { margin: 0 0 12px; color: #64748b; }
.cg-escalera-panel[hidden] { display: none !important; }

.cg-escalera-wrap .escalera-inline-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px;
    background: #f8fafc;
}
.cg-escalera-wrap .escalera-inline-steps {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}
.cg-escalera-wrap .escalera-step-btn {
    border: 1px solid #dbe5f3;
    border-radius: 999px;
    background: #fff;
    color: #1e3a5f;
    padding: 6px 10px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 700;
    font-size: 0.78rem;
}
.cg-escalera-wrap .escalera-step-btn.active {
    background: #eff6ff;
    border-color: #93c5fd;
}
.cg-escalera-wrap .escalera-step-icon {
    width: 20px;
    height: 20px;
    border-radius: 999px;
    border: 2px solid currentColor;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.cg-escalera-wrap .etapa-ganar .escalera-step-icon { color: #d3a700; border-color: #f2c300; }
.cg-escalera-wrap .etapa-consolidar .escalera-step-icon { color: #22a83e; border-color: #36c24f; }
.cg-escalera-wrap .etapa-discipular .escalera-step-icon { color: #1f77ca; border-color: #2b8eea; }
.cg-escalera-wrap .etapa-enviar .escalera-step-icon { color: #be123c; border-color: #d90d46; }
.cg-escalera-wrap .step-initial { font-size: 11px; font-weight: 900; }
.cg-escalera-wrap .escalera-inline-panel {
    display: grid;
    gap: 6px;
    padding-top: 4px;
}
.cg-escalera-wrap .escalera-inline-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    font-size: 0.92em;
    color: #334155;
}
.cg-escalera-wrap .escalera-inline-card.is-saving { opacity: 0.65; pointer-events: none; }
.cg-escalera-wrap .escalera-inline-card.is-bloqueado .escalera-inline-item:not(:has(input[data-indice="5"])) {
    opacity: 0.55;
}
</style>
