<?php
include VIEWS . '/layout/header.php';

$fechaAperturaRaw = substr(trim((string)($celula['Fecha_Apertura'] ?? '')), 0, 10);
$fechaAperturaVisible = 'No especificada';
if ($fechaAperturaRaw !== '' && $fechaAperturaRaw !== '0000-00-00') {
    $tsApertura = strtotime($fechaAperturaRaw);
    $fechaAperturaVisible = $tsApertura ? date('d/m/Y', $tsApertura) : $fechaAperturaRaw;
}

$idCelulaDetalle = (int)($celula['Id_Celula'] ?? 0);
$nombreCelulaDetalle = trim((string)($celula['Nombre_Celula'] ?? 'Célula'));
$returnUrlCelula = PUBLIC_URL . 'index.php?url=celulas/detalle&id=' . $idCelulaDetalle;
$fechaReporteDefault = substr(trim((string)($fechaReporteDefault ?? date('Y-m-d'))), 0, 10);
$puedeReportarCelula = !empty($puedeReportarCelula);
$abrirReporte = !empty($abrirReporte);
$mensajeReporte = trim((string)($mensajeReporte ?? ''));
?>

<div class="page-header">
    <div>
        <h2 style="margin:0;">Celugrama</h2>
        <p style="margin:4px 0 0; color:#5b6b86; font-size:0.95em;"><?= htmlspecialchars($nombreCelulaDetalle) ?></p>
    </div>
    <div class="celulas-actions-row">
        <?php if ($puedeReportarCelula): ?>
        <button type="button" class="btn btn-sm celulas-action-btn celulas-action-btn--report" id="btnAbrirReporteCelugrama" title="Reportar célula">
            <i class="bi bi-check2-square" aria-hidden="true"></i>
            <span>Reportar célula</span>
        </button>
        <?php endif; ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=personas/crear&return_to=celulas&return_url=<?= urlencode($returnUrlCelula) ?>&celula=<?= $idCelulaDetalle ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--icon" title="Nueva persona" aria-label="Nueva persona">
            <i class="bi bi-person-plus-fill" aria-hidden="true"></i>
        </a>
        <?php if (AuthController::puede('celulas:ver')): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=celulas/reporte-individual&id=<?= $idCelulaDetalle ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--excel" title="Reporte individual" aria-label="Reporte individual">
            <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>
            <span>Reporte</span>
        </a>
        <?php endif; ?>
        <?php if (AuthController::puede('celulas:editar')): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=celulas/editar&id=<?= $idCelulaDetalle ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--icon celulas-action-btn--edit" title="Editar" aria-label="Editar">
            <i class="bi bi-pencil-fill" aria-hidden="true"></i>
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($mensajeReporte === '1' || $mensajeReporte === 'ok'): ?>
<div class="alert alert-success" style="margin-bottom:14px;">Célula reportada. El Celugrama ya refleja la asistencia.</div>
<?php endif; ?>

<div class="detail-container detail-container--celugrama">
    <?php include VIEWS . '/celulas/partials/celugrama.php'; ?>
</div>

<?php if ($puedeReportarCelula): ?>
<?php
$miembrosModal = is_array($celugrama['miembros'] ?? null) ? $celugrama['miembros'] : [];
?>
<div id="modalReporteCelugrama" class="cg-modal" aria-hidden="true">
    <div class="cg-modal__overlay" data-close-reporte-celugrama="1"></div>
    <div class="cg-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tituloReporteCelugrama">
        <div class="cg-modal__head">
            <h3 id="tituloReporteCelugrama">Reportar célula</h3>
            <button type="button" class="cg-modal__close" data-close-reporte-celugrama="1" aria-label="Cerrar">&times;</button>
        </div>
        <p class="cg-modal__sub"><?= htmlspecialchars($nombreCelulaDetalle) ?></p>
        <div id="cgReporteFlash" class="cg-modal__flash" hidden></div>
        <form id="formReporteCelugrama" method="POST" action="<?= PUBLIC_URL ?>index.php?url=asistencias/registrar">
            <input type="hidden" name="id_celula" value="<?= $idCelulaDetalle ?>">
            <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrlCelula . '&reportado=1') ?>">

            <div class="cg-modal__grid">
                <div class="form-group">
                    <label for="cg_fecha">Fecha del reporte</label>
                    <input type="date" id="cg_fecha" name="fecha" class="form-control" value="<?= htmlspecialchars($fechaReporteDefault) ?>" required>
                    <small class="form-text text-muted">Cuenta la semana de lunes a domingo de esta fecha.</small>
                </div>
                <div class="form-group">
                    <label for="cg_tema">Tema</label>
                    <input type="text" id="cg_tema" name="tema" class="form-control" placeholder="Tema de la reunión" maxlength="255">
                </div>
            </div>

            <div class="form-group">
                <label class="cg-check-line" for="cg_no_se_realizo">
                    <input type="checkbox" id="cg_no_se_realizo" name="no_se_realizo" value="1">
                    <span>No se realizó</span>
                </label>
                <div id="cg_motivo_wrap" class="cg-motivo-wrap" hidden>
                    <p class="cg-motivo-title">¿Por qué no se realizó?</p>
                    <label class="cg-check-line"><input type="radio" name="motivo_no_realizo" value="convencion" disabled> <span>Convención</span></label>
                    <label class="cg-check-line"><input type="radio" name="motivo_no_realizo" value="actividades_iglesia" disabled> <span>Actividades de la iglesia</span></label>
                    <label class="cg-check-line"><input type="radio" name="motivo_no_realizo" value="otro" disabled> <span>Otro</span></label>
                    <input type="text" id="cg_motivo_otro" name="motivo_no_realizo_otro" class="form-control" placeholder="Especifique" disabled style="margin-top:8px;">
                </div>
            </div>

            <div class="form-group">
                <label for="cg_observaciones">Observaciones</label>
                <textarea id="cg_observaciones" name="observaciones" class="form-control" rows="2" placeholder="Opcional"></textarea>
            </div>

            <div class="cg-modal__miembros-head">
                <strong>Marcar asistencias</strong>
                <button type="button" class="btn btn-sm btn-secondary" id="cgMarcarTodos">Marcar todos</button>
            </div>
            <div class="cg-modal__lista">
                <?php if ($miembrosModal === []): ?>
                    <p class="cg-modal__empty">No hay miembros activos en esta célula.</p>
                <?php else: ?>
                    <?php foreach ($miembrosModal as $mModal): ?>
                        <?php $idPersModal = (int)($mModal['id_persona'] ?? 0); if ($idPersModal <= 0) continue; ?>
                        <label class="cg-miembro-row">
                            <span class="cg-miembro-info">
                                <strong><?= htmlspecialchars((string)($mModal['nombre'] ?? 'Sin nombre')) ?></strong>
                                <small><?= htmlspecialchars((string)($mModal['telefono'] ?? '')) ?></small>
                            </span>
                            <input type="hidden" name="asistencias[<?= $idPersModal ?>]" value="0">
                            <input type="checkbox" class="cg-asist-check" name="asistencias[<?= $idPersModal ?>]" value="1">
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="cg-modal__actions">
                <button type="button" class="btn btn-secondary" data-close-reporte-celugrama="1">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="cgBtnGuardarReporte" <?= $miembrosModal === [] ? 'disabled' : '' ?>>Guardar asistencias</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
.celulas-actions-row {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    align-items: center;
}
.celulas-action-btn {
    border: 1px solid #d6e1f0;
    background: #f6f9ff;
    color: #2f4f7a;
    font-weight: 600;
}
.celulas-action-btn:hover {
    background: #edf3fc;
    color: #274368;
}
.celulas-action-btn--icon {
    min-width: 32px;
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8125rem;
}
.celulas-action-btn--report {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 32px;
    padding: 0 10px;
    background: #e9f8ef;
    border-color: #c6ebd4;
    color: #1f7a44;
}
.celulas-action-btn--report:hover {
    background: #def4e7;
    color: #176a39;
}
.celulas-action-btn--excel {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 32px;
    padding: 0 8px;
    background: #eff6ff;
    border-color: #bfdbfe;
    color: #1d4ed8;
}
.celulas-action-btn--excel:hover {
    background: #dbeafe;
    color: #1e40af;
}
.celulas-action-btn--edit {
    background: #fff8e8;
    border-color: #f1ddb1;
    color: #8a6400;
}
.celulas-action-btn--edit:hover {
    background: #fff1d8;
    color: #7a5600;
}

.cg-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1300;
}
.cg-modal.is-open { display: flex; }
.cg-modal__overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
}
.cg-modal__dialog {
    position: relative;
    width: min(640px, calc(100vw - 24px));
    max-height: calc(100vh - 32px);
    overflow: auto;
    background: #fff;
    border-radius: 14px;
    padding: 16px;
    box-shadow: 0 24px 54px rgba(15, 23, 42, 0.28);
}
.cg-modal__head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
}
.cg-modal__head h3 { margin: 0; color: #1e3a5f; }
.cg-modal__close {
    border: 0;
    background: transparent;
    font-size: 1.6rem;
    line-height: 1;
    color: #64748b;
    cursor: pointer;
}
.cg-modal__sub { margin: 4px 0 12px; color: #64748b; }
.cg-modal__flash {
    margin-bottom: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    font-size: 0.92em;
}
.cg-modal__flash.is-ok { background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; }
.cg-modal__flash.is-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.cg-modal__grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
@media (max-width: 640px) {
    .cg-modal__grid { grid-template-columns: 1fr; }
}
.cg-check-line {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #334155;
    margin: 0 0 6px;
}
.cg-motivo-wrap {
    margin-top: 8px;
    padding: 10px;
    background: #f8fafc;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}
.cg-motivo-title { margin: 0 0 8px; font-weight: 700; color: #475569; font-size: 0.9em; }
.cg-modal__miembros-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    margin: 12px 0 8px;
}
.cg-modal__lista {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    max-height: 280px;
    overflow: auto;
}
.cg-miembro-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-bottom: 1px solid #eef2f7;
    margin: 0;
    cursor: pointer;
}
.cg-miembro-row:last-child { border-bottom: 0; }
.cg-miembro-info { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.cg-miembro-info strong { color: #1e3a5f; }
.cg-miembro-info small { color: #64748b; }
.cg-asist-check { width: 20px; height: 20px; cursor: pointer; flex-shrink: 0; }
.cg-modal__empty { padding: 16px; color: #64748b; margin: 0; }
.cg-modal__actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 14px;
}
</style>

<?php
$miembrosEscaleraCg = is_array($celugrama['miembros'] ?? null) ? $celugrama['miembros'] : [];
$celulasMigracion = is_array($celulasMigracion ?? null) ? $celulasMigracion : [];
$puedeEscaleraDetalle = !empty($celugrama['puede_escalera']);
$puedeMigrarDetalle = !empty($celugrama['puede_migrar']);
$puedeEliminarPersonaDetalle = !empty($celugrama['puede_eliminar_persona']);
$puedeNoSeDisponeDetalle = !empty($celugrama['puede_eliminar_miembro']);
?>
<?php if ($puedeEscaleraDetalle): ?>
<div id="modalEscaleraCelugrama" class="cg-escalera-modal" aria-hidden="true">
    <div class="cg-escalera-modal__overlay" data-close-escalera-cg="1"></div>
    <div class="cg-escalera-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tituloEscaleraCg">
        <div class="cg-escalera-modal__head">
            <h3 id="tituloEscaleraCg">Escalera rápida</h3>
            <button type="button" class="cg-escalera-modal__close" data-close-escalera-cg="1" aria-label="Cerrar">&times;</button>
        </div>
        <p class="cg-escalera-modal__sub" id="nombreEscaleraCg"></p>
        <div class="cg-escalera-wrap">
            <?php foreach ($miembrosEscaleraCg as $mEsc): ?>
                <?php
                $idEsc = (int)($mEsc['id_persona'] ?? 0);
                if ($idEsc <= 0) {
                    continue;
                }
                $escaleraPersonaId = $idEsc;
                $escaleraPuedeEditar = true;
                $escaleraIdCelula = $idCelulaDetalle;
                $escaleraIdMinisterio = (int)($mEsc['id_ministerio'] ?? 0);
                $escaleraProceso = (string)($mEsc['proceso'] ?? 'Ganar');
                $escaleraChecklistRaw = (string)($mEsc['escalera_checklist'] ?? '');
                ?>
                <div class="cg-escalera-panel" data-persona-id="<?= $idEsc ?>" hidden>
                    <?php include VIEWS . '/discipular/partials/escalera_rapida_casilla.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($puedeNoSeDisponeDetalle): ?>
<div id="modalNoDisponibleCg" class="cg-escalera-modal" aria-hidden="true">
    <div class="cg-escalera-modal__overlay" data-close-no-disponible-cg="1"></div>
    <div class="cg-escalera-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tituloNoDisponibleCg" style="max-width:420px;">
        <div class="cg-escalera-modal__head">
            <h3 id="tituloNoDisponibleCg">Marcar como No se dispone</h3>
            <button type="button" class="cg-escalera-modal__close" data-close-no-disponible-cg="1" aria-label="Cerrar">&times;</button>
        </div>
        <p class="cg-escalera-modal__sub" id="nombreNoDisponibleCg"></p>
        <label for="obsNoDisponibleCg" style="display:block;font-weight:600;margin:0 0 6px;">Observación</label>
        <textarea id="obsNoDisponibleCg" class="form-control" rows="4" placeholder="Escribe la razón o contexto..."></textarea>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
            <button type="button" class="btn btn-secondary btn-sm" data-close-no-disponible-cg="1">Cancelar</button>
            <button type="button" class="btn btn-danger btn-sm" id="btnGuardarNoDisponibleCg">Guardar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($puedeMigrarDetalle): ?>
<div id="modalMigrarCelugrama" class="cg-escalera-modal" aria-hidden="true">
    <div class="cg-escalera-modal__overlay" data-close-migrar-cg="1"></div>
    <div class="cg-escalera-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="tituloMigrarCg" style="max-width:420px;">
        <div class="cg-escalera-modal__head">
            <h3 id="tituloMigrarCg">Migrar a otra célula</h3>
            <button type="button" class="cg-escalera-modal__close" data-close-migrar-cg="1" aria-label="Cerrar">&times;</button>
        </div>
        <p class="cg-escalera-modal__sub" id="nombreMigrarCg"></p>
        <label for="selectMigrarCg" style="display:block;font-weight:600;margin:0 0 6px;">Célula del mismo ministerio</label>
        <select id="selectMigrarCg" class="form-control">
            <option value="">Selecciona una célula…</option>
            <?php foreach ($celulasMigracion as $celMig): ?>
                <option value="<?= (int)$celMig['id'] ?>"><?= htmlspecialchars((string)$celMig['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($celulasMigracion === []): ?>
            <p class="form-text text-muted" style="margin-top:8px;">No hay otras células en el mismo ministerio.</p>
        <?php endif; ?>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px;">
            <button type="button" class="btn btn-secondary btn-sm" data-close-migrar-cg="1">Cancelar</button>
            <button type="button" class="btn btn-primary btn-sm" id="btnGuardarMigrarCg" <?= $celulasMigracion === [] ? 'disabled' : '' ?>>Migrar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include VIEWS . '/personas/partials/modal_seguimiento.php'; ?>

<?php if ($puedeEliminarPersonaDetalle || $puedeEscaleraDetalle || $puedeNoSeDisponeDetalle || $puedeMigrarDetalle): ?>
<script>
(function () {
    var idCelula = <?= (int)$idCelulaDetalle ?>;
    var urlEliminarPersona = '<?= PUBLIC_URL ?>?url=personas/eliminar';
    var urlMigrar = '<?= PUBLIC_URL ?>?url=asistencias/migrarCelula';
    var urlNoDisponible = '<?= PUBLIC_URL ?>?url=asistencias/marcarNoDisponible';
    var urlChecklist = '<?= PUBLIC_URL ?>?url=personas/actualizarChecklistEscalera';
    var modalEsc = document.getElementById('modalEscaleraCelugrama');
    var nombreEsc = document.getElementById('nombreEscaleraCg');
    var modalNoDisp = document.getElementById('modalNoDisponibleCg');
    var nombreNoDisp = document.getElementById('nombreNoDisponibleCg');
    var obsNoDisp = document.getElementById('obsNoDisponibleCg');
    var btnGuardarNoDisp = document.getElementById('btnGuardarNoDisponibleCg');
    var personaNoDispId = 0;
    var botonNoDispActivo = null;
    var modalMigrar = document.getElementById('modalMigrarCelugrama');
    var nombreMigrar = document.getElementById('nombreMigrarCg');
    var selectMigrar = document.getElementById('selectMigrarCg');
    var btnGuardarMigrar = document.getElementById('btnGuardarMigrarCg');
    var personaMigrarId = 0;
    var botonMigrarActivo = null;

    function abrirEscalera(idPersona, nombre) {
        if (!modalEsc) return;
        modalEsc.querySelectorAll('.cg-escalera-panel').forEach(function (panel) {
            var match = String(panel.getAttribute('data-persona-id') || '') === String(idPersona);
            panel.hidden = !match;
        });
        if (nombreEsc) nombreEsc.textContent = nombre || '';
        modalEsc.classList.add('is-open');
        modalEsc.setAttribute('aria-hidden', 'false');
    }
    function cerrarEscalera() {
        if (!modalEsc) return;
        modalEsc.classList.remove('is-open');
        modalEsc.setAttribute('aria-hidden', 'true');
    }

    document.querySelectorAll('.js-cg-abrir-escalera').forEach(function (btn) {
        btn.addEventListener('click', function () {
            abrirEscalera(btn.getAttribute('data-persona-id'), btn.getAttribute('data-persona-nombre'));
        });
    });
    document.querySelectorAll('[data-close-escalera-cg]').forEach(function (el) {
        el.addEventListener('click', cerrarEscalera);
    });

    document.querySelectorAll('.js-cg-eliminar-persona').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idPersona = parseInt(btn.getAttribute('data-id-persona') || '0', 10);
            var nombre = String(btn.getAttribute('data-nombre') || '');
            if (!idPersona) return;
            var aviso = '¿Estás seguro de eliminar esta persona?'
                + (nombre ? ('\n\n' + nombre) : '')
                + '\n\nSe eliminará de toda la base de datos. Esta acción no se puede deshacer.';
            if (!window.confirm(aviso)) return;
            btn.disabled = true;
            var body = new URLSearchParams();
            body.append('id', String(idPersona));
            fetch(urlEliminarPersona, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body.toString(),
                credentials: 'same-origin'
            }).then(function (resp) {
                return resp.json().then(function (data) {
                    return { ok: resp.ok && !!(data && data.success), data: data || {} };
                });
            }).then(function (res) {
                if (!res.ok) {
                    throw new Error(res.data.message || 'No se pudo eliminar la persona.');
                }
                var fila = btn.closest('tr');
                if (fila) fila.remove();
            }).catch(function (err) {
                btn.disabled = false;
                window.alert(err.message || 'Error al eliminar.');
            });
        });
    });

    function abrirMigrar(idPersona, nombre, boton) {
        if (!modalMigrar) return;
        personaMigrarId = idPersona;
        botonMigrarActivo = boton || null;
        if (nombreMigrar) nombreMigrar.textContent = 'Persona: ' + (nombre || '');
        if (selectMigrar) selectMigrar.value = '';
        modalMigrar.classList.add('is-open');
        modalMigrar.setAttribute('aria-hidden', 'false');
    }
    function cerrarMigrar() {
        if (!modalMigrar) return;
        modalMigrar.classList.remove('is-open');
        modalMigrar.setAttribute('aria-hidden', 'true');
        personaMigrarId = 0;
        botonMigrarActivo = null;
    }
    document.querySelectorAll('.js-cg-migrar-celula').forEach(function (btn) {
        btn.addEventListener('click', function () {
            abrirMigrar(
                parseInt(btn.getAttribute('data-id-persona') || '0', 10),
                String(btn.getAttribute('data-nombre') || ''),
                btn
            );
        });
    });
    document.querySelectorAll('[data-close-migrar-cg]').forEach(function (el) {
        el.addEventListener('click', cerrarMigrar);
    });
    if (btnGuardarMigrar) {
        btnGuardarMigrar.addEventListener('click', function () {
            var destino = selectMigrar ? parseInt(selectMigrar.value || '0', 10) : 0;
            if (!personaMigrarId || !destino) {
                window.alert('Selecciona la célula de destino.');
                return;
            }
            btnGuardarMigrar.disabled = true;
            if (botonMigrarActivo) botonMigrarActivo.disabled = true;
            var body = new URLSearchParams();
            body.append('id_persona', String(personaMigrarId));
            body.append('id_celula', String(idCelula));
            body.append('id_celula_destino', String(destino));
            fetch(urlMigrar, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body.toString(),
                credentials: 'same-origin'
            }).then(function (resp) {
                return resp.json().then(function (data) {
                    return { ok: resp.ok && !!(data && data.success), data: data || {} };
                });
            }).then(function (res) {
                if (!res.ok) {
                    throw new Error(res.data.message || 'No se pudo migrar.');
                }
                var fila = botonMigrarActivo ? botonMigrarActivo.closest('tr') : null;
                if (fila) fila.remove();
                cerrarMigrar();
            }).catch(function (err) {
                if (botonMigrarActivo) botonMigrarActivo.disabled = false;
                window.alert(err.message || 'Error al migrar.');
            }).finally(function () {
                btnGuardarMigrar.disabled = false;
            });
        });
    }

    function abrirNoDisponible(idPersona, nombre, boton) {
        if (!modalNoDisp) return;
        personaNoDispId = idPersona;
        botonNoDispActivo = boton || null;
        if (nombreNoDisp) nombreNoDisp.textContent = 'Persona: ' + (nombre || '');
        if (obsNoDisp) obsNoDisp.value = '';
        modalNoDisp.classList.add('is-open');
        modalNoDisp.setAttribute('aria-hidden', 'false');
        if (obsNoDisp) setTimeout(function () { obsNoDisp.focus(); }, 10);
    }
    function cerrarNoDisponible() {
        if (!modalNoDisp) return;
        modalNoDisp.classList.remove('is-open');
        modalNoDisp.setAttribute('aria-hidden', 'true');
        personaNoDispId = 0;
        botonNoDispActivo = null;
    }
    document.querySelectorAll('.js-cg-no-disponible').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idPersona = parseInt(btn.getAttribute('data-id-persona') || '0', 10);
            var nombre = String(btn.getAttribute('data-nombre') || '');
            if (!idPersona) return;
            abrirNoDisponible(idPersona, nombre, btn);
        });
    });
    document.querySelectorAll('[data-close-no-disponible-cg]').forEach(function (el) {
        el.addEventListener('click', cerrarNoDisponible);
    });
    if (btnGuardarNoDisp) {
        btnGuardarNoDisp.addEventListener('click', function () {
            var observacion = String((obsNoDisp && obsNoDisp.value) || '').trim();
            if (!personaNoDispId) return;
            if (!observacion) {
                window.alert('La observación es obligatoria para "No se dispone".');
                if (obsNoDisp) obsNoDisp.focus();
                return;
            }
            btnGuardarNoDisp.disabled = true;
            if (botonNoDispActivo) botonNoDispActivo.disabled = true;
            var body = new URLSearchParams();
            body.append('id_persona', String(personaNoDispId));
            body.append('observacion', observacion);
            fetch(urlNoDisponible, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body.toString(),
                credentials: 'same-origin'
            }).then(function (resp) {
                return resp.json().then(function (data) {
                    return { ok: resp.ok && !!(data && data.success), data: data || {} };
                });
            }).then(function (res) {
                if (!res.ok) {
                    throw new Error(res.data.message || 'No se pudo marcar No se dispone.');
                }
                var fila = botonNoDispActivo ? botonNoDispActivo.closest('tr') : null;
                cerrarNoDisponible();
                if (fila) fila.remove();
            }).catch(function (err) {
                btnGuardarNoDisp.disabled = false;
                if (botonNoDispActivo) botonNoDispActivo.disabled = false;
                window.alert(err.message || 'Error al marcar No se dispone.');
            }).finally(function () {
                btnGuardarNoDisp.disabled = false;
            });
        });
    }

    // Escalera rápida (mismo endpoint que Personas / Discipular)
    function parseChecklist(raw) {
        var base = {
            Ganar: [false, false, false, false, false, false],
            Consolidar: [false, false, false],
            Discipular: [false, false, false],
            Enviar: [false, false, false],
            _meta: {}
        };
        if (!raw) return base;
        try {
            var parsed = JSON.parse(raw);
            if (!parsed || typeof parsed !== 'object') throw new Error('bad');
            ['Ganar', 'Consolidar', 'Discipular', 'Enviar'].forEach(function (etapa) {
                if (!Array.isArray(parsed[etapa])) parsed[etapa] = base[etapa].slice();
                for (var i = 0; i < base[etapa].length; i++) parsed[etapa][i] = !!parsed[etapa][i];
            });
            if (!parsed._meta || typeof parsed._meta !== 'object') parsed._meta = {};
            return parsed;
        } catch (e) {
            return base;
        }
    }

    function aplicarChecks(container, checklist) {
        container.querySelectorAll('.js-inline-escalera-check').forEach(function (check) {
            var etapa = check.getAttribute('data-etapa') || '';
            var indice = parseInt(check.getAttribute('data-indice') || '-1', 10);
            if (!etapa || indice < 0 || !Array.isArray(checklist[etapa])) return;
            check.checked = !!checklist[etapa][indice];
        });
    }

    function aplicarBloqueo(container, checklist, puedeEditar) {
        var noDisponible = !!(checklist.Ganar && checklist.Ganar[5]);
        container.querySelectorAll('.js-inline-escalera-check').forEach(function (check) {
            var etapa = check.getAttribute('data-etapa') || 'Ganar';
            var indice = parseInt(check.getAttribute('data-indice') || '-1', 10);
            var esAuto = (etapa === 'Ganar' && (indice === 1 || indice === 4)) || (etapa === 'Enviar' && indice === 2);
            var disabled = esAuto || !puedeEditar;
            if (!disabled && noDisponible && !(etapa === 'Ganar' && indice === 5)) disabled = true;
            check.disabled = disabled;
        });
        container.classList.toggle('is-bloqueado', noDisponible);
    }

    async function guardarChecklist(container, etapa, indice, marcado) {
        var idPersona = parseInt(container.getAttribute('data-persona-id') || '0', 10);
        if (!idPersona) return false;
        var checklist = parseChecklist(container.getAttribute('data-checklist') || '');
        var observacionNoDisponible = '';
        if (etapa === 'Ganar' && indice === 5 && marcado) {
            var actual = String((checklist._meta && checklist._meta.no_disponible_observacion) || '');
            var ingresada = window.prompt('Escribe una observación para marcar "No se dispone":', actual);
            if (ingresada === null) return false;
            var limpia = String(ingresada || '').trim();
            if (limpia === '') {
                alert('La observación es obligatoria para "No se dispone".');
                return false;
            }
            observacionNoDisponible = limpia;
        }
        if (etapa === 'Ganar' && indice === 5 && !marcado && checklist._meta) {
            checklist._meta.no_disponible_observacion = '';
        }
        container.classList.add('is-saving');
        try {
            var response = await fetch(urlChecklist, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    id_persona: idPersona,
                    etapa: etapa,
                    indice: indice,
                    marcado: marcado ? 1 : 0,
                    observacion_no_disponible: (etapa === 'Ganar' && indice === 5) ? observacionNoDisponible : ''
                })
            });
            var data = await response.json();
            if (!response.ok || !data || !data.success) {
                throw new Error((data && data.message) ? data.message : 'No se pudo guardar.');
            }
            var checklistServidor = parseChecklist(JSON.stringify(data.checklist || {}));
            container.setAttribute('data-checklist', JSON.stringify(checklistServidor));
            aplicarChecks(container, checklistServidor);
            // Refrescar proceso/flags de la fila sin salir del Celugrama
            var fila = document.querySelector('tr[data-id-persona="' + idPersona + '"]');
            if (fila && data.proceso) {
                var proc = fila.querySelector('.js-cg-proceso');
                if (proc) {
                    proc.textContent = String(data.proceso);
                    proc.setAttribute('title', String(data.proceso));
                }
            }
            if (fila && checklistServidor) {
                var enc = fila.querySelector('.js-cg-enc');
                var pos = fila.querySelector('.js-cg-pos');
                var bau = fila.querySelector('.js-cg-bau');
                var cap = fila.querySelector('.js-cg-cap');
                if (enc) enc.textContent = checklistServidor.Consolidar && checklistServidor.Consolidar[1] ? 'Sí' : '—';
                if (pos) pos.textContent = checklistServidor.Consolidar && checklistServidor.Consolidar[0] ? 'Sí' : '—';
                if (bau) bau.textContent = checklistServidor.Consolidar && checklistServidor.Consolidar[2] ? 'Sí' : '—';
                if (cap) {
                    var nivel = '';
                    if (checklistServidor.Discipular && checklistServidor.Discipular[2]) nivel = 'Nivel 3';
                    else if (checklistServidor.Discipular && checklistServidor.Discipular[1]) nivel = 'Nivel 2';
                    else if (checklistServidor.Discipular && checklistServidor.Discipular[0]) nivel = 'Nivel 1';
                    cap.textContent = nivel || '—';
                }
            }
            return true;
        } catch (error) {
            alert(error.message || 'Error al guardar checklist.');
            return false;
        } finally {
            container.classList.remove('is-saving');
        }
    }

    document.querySelectorAll('#modalEscaleraCelugrama .js-inline-escalera').forEach(function (container) {
        var checklist = parseChecklist(container.getAttribute('data-checklist') || '');
        var puedeEditar = container.getAttribute('data-puede-editar') === '1';
        container.setAttribute('data-checklist', JSON.stringify(checklist));
        aplicarChecks(container, checklist);
        aplicarBloqueo(container, checklist, puedeEditar);

        var etapaAbierta = null;
        function mostrarPanel(etapaObjetivo) {
            container.querySelectorAll('.js-inline-stage-panel').forEach(function (panel) {
                if (etapaObjetivo && panel.getAttribute('data-etapa') === etapaObjetivo) {
                    panel.removeAttribute('hidden');
                } else {
                    panel.setAttribute('hidden', 'hidden');
                }
            });
            container.querySelectorAll('.js-inline-step-btn').forEach(function (btn) {
                btn.classList.toggle('active', !!etapaObjetivo && btn.getAttribute('data-etapa') === etapaObjetivo);
            });
            etapaAbierta = etapaObjetivo || null;
        }
        mostrarPanel(null);

        container.querySelectorAll('.js-inline-step-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var etapa = btn.getAttribute('data-etapa') || 'Ganar';
                mostrarPanel(etapaAbierta === etapa ? null : etapa);
            });
        });

        container.querySelectorAll('.js-inline-escalera-check').forEach(function (check) {
            check.addEventListener('change', async function () {
                var etapa = String(check.getAttribute('data-etapa') || 'Ganar');
                var indice = parseInt(check.getAttribute('data-indice') || '-1', 10);
                var nuevoValor = !!check.checked;
                var guardado = await guardarChecklist(container, etapa, indice, nuevoValor);
                if (!guardado) {
                    check.checked = !nuevoValor;
                    return;
                }
                aplicarBloqueo(container, parseChecklist(container.getAttribute('data-checklist') || ''), puedeEditar);
            });
        });
    });
})();
</script>
<?php endif; ?>

<?php if ($puedeReportarCelula): ?>
<script>
(function () {
    var modal = document.getElementById('modalReporteCelugrama');
    var form = document.getElementById('formReporteCelugrama');
    var btnAbrir = document.getElementById('btnAbrirReporteCelugrama');
    var flash = document.getElementById('cgReporteFlash');
    var noRealizo = document.getElementById('cg_no_se_realizo');
    var motivoWrap = document.getElementById('cg_motivo_wrap');
    var motivoRadios = Array.prototype.slice.call(document.querySelectorAll('input[name="motivo_no_realizo"]'));
    var motivoOtro = document.getElementById('cg_motivo_otro');
    var btnGuardar = document.getElementById('cgBtnGuardarReporte');
    var btnMarcarTodos = document.getElementById('cgMarcarTodos');

    function abrir() {
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }
    function cerrar() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }
    function showFlash(ok, msg) {
        if (!flash) return;
        flash.hidden = false;
        flash.className = 'cg-modal__flash ' + (ok ? 'is-ok' : 'is-error');
        flash.textContent = msg || '';
    }
    function syncNoRealizo() {
        var on = !!(noRealizo && noRealizo.checked);
        if (motivoWrap) motivoWrap.hidden = !on;
        motivoRadios.forEach(function (r) {
            r.disabled = !on;
            if (!on) r.checked = false;
        });
        if (motivoOtro) {
            motivoOtro.disabled = !on || !motivoRadios.some(function (r) { return r.checked && r.value === 'otro'; });
            if (!on) motivoOtro.value = '';
        }
        document.querySelectorAll('.cg-asist-check').forEach(function (chk) {
            if (on) chk.checked = false;
            chk.disabled = on;
            var row = chk.closest('.cg-miembro-row');
            if (row) row.style.opacity = on ? '0.55' : '1';
        });
    }

    if (btnAbrir) btnAbrir.addEventListener('click', abrir);
    var btnAbrir2 = document.getElementById('btnAbrirReporteCelugrama2');
    if (btnAbrir2) btnAbrir2.addEventListener('click', abrir);
    document.querySelectorAll('[data-close-reporte-celugrama]').forEach(function (el) {
        el.addEventListener('click', cerrar);
    });
    <?php if ($abrirReporte && $puedeReportarCelula): ?>
    abrir();
    <?php endif; ?>
    if (noRealizo) noRealizo.addEventListener('change', syncNoRealizo);
    motivoRadios.forEach(function (r) {
        r.addEventListener('change', syncNoRealizo);
    });
    if (btnMarcarTodos) {
        btnMarcarTodos.addEventListener('click', function () {
            if (noRealizo && noRealizo.checked) return;
            document.querySelectorAll('.cg-asist-check').forEach(function (chk) {
                chk.checked = true;
            });
        });
    }

    if (form) {
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            if (btnGuardar) {
                btnGuardar.disabled = true;
                btnGuardar.textContent = 'Guardando…';
            }
            if (flash) flash.hidden = true;

            var body = new URLSearchParams(new FormData(form));
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body,
                credentials: 'same-origin'
            }).then(function (resp) {
                return resp.json().then(function (data) {
                    return { ok: resp.ok && !!(data && (data.ok || data.success)), data: data || {} };
                });
            }).then(function (res) {
                if (!res.ok) {
                    showFlash(false, res.data.message || 'No se pudo reportar la célula.');
                    if (btnGuardar) {
                        btnGuardar.disabled = false;
                        btnGuardar.textContent = 'Guardar asistencias';
                    }
                    return;
                }
                showFlash(true, res.data.message || 'Célula reportada.');
                setTimeout(function () {
                    var url = new URL(window.location.href);
                    url.searchParams.set('reportado', '1');
                    window.location.href = url.toString();
                }, 500);
            }).catch(function () {
                showFlash(false, 'Error de conexión. Intenta de nuevo.');
                if (btnGuardar) {
                    btnGuardar.disabled = false;
                    btnGuardar.textContent = 'Guardar asistencias';
                }
            });
        });
    }

    syncNoRealizo();
})();
</script>
<?php endif; ?>

<?php include VIEWS . '/layout/footer.php'; ?>
