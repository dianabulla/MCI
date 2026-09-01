<?php include VIEWS . '/layout/header.php'; ?>

<?php
require_once APP . '/Models/TallerServicioSocial.php';
$cita = is_array($cita ?? null) ? $cita : [];
$estados = is_array($estados ?? null) ? $estados : TallerServicioSocial::ESTADOS;
$historiaClinica = is_array($historia_clinica ?? null) ? $historia_clinica : [];
$citasPaciente = is_array($citas_paciente ?? null) ? $citas_paciente : [];
$documentosRemision = is_array($documentos_remision ?? null) ? $documentos_remision : [];
$horariosSabado = is_array($horarios_sabado ?? null) ? $horarios_sabado : TallerServicioSocial::catalogoHorariosSabado();
$proximosSabados = is_array($proximos_sabados ?? null) ? $proximos_sabados : TallerServicioSocial::proximosSabados(8);
$tiposCita = is_array($tipos_cita ?? null) ? $tipos_cita : TallerServicioSocial::TIPOS_CITA;
$puedeGestionar = !empty($puede_gestionar);
$flashOk = (string)($flash_ok ?? '');
$flashError = (string)($flash_error ?? '');
$id = (int)($cita['Id_Cita'] ?? 0);
$estado = TallerServicioSocial::normalizarEstado((string)($cita['Estado'] ?? 'pendiente'));
$nombre = trim((string)($cita['Nombre'] ?? '') . ' ' . (string)($cita['Apellido'] ?? ''));
$tipoDoc = (string)($cita['Tipo_Documento'] ?? '');
$documento = (string)($cita['Documento'] ?? '');
$horaActual = TallerServicioSocial::normalizarHora((string)($cita['Hora_Preferida'] ?? ''));
$tel = trim((string)($cita['Telefono'] ?? ''));
$email = trim((string)($cita['Email'] ?? ''));
$eps = trim((string)($cita['Nombre_Eps'] ?? ''));
$necesidad = trim((string)($cita['Necesidad_Principal'] ?? ''));
$obsSolicitante = trim((string)($cita['Observaciones'] ?? ''));
$nCitas = count($citasPaciente) + 1;
$nHistoria = count($historiaClinica);
?>

<style>
.ss-d { display:flex; flex-direction:column; gap:16px; }
.ss-d-head {
    display:flex; justify-content:space-between; align-items:flex-start;
    gap:12px; flex-wrap:wrap; padding-bottom:12px; border-bottom:1px solid #e2e9f8;
}
.ss-d-head h2 { margin:0; }
.ss-d-head p { margin:4px 0 0; color:#64748b; }
.ss-d-actions { display:flex; gap:8px; flex-wrap:wrap; }
.ss-btn {
    display:inline-flex; align-items:center; gap:6px; padding:8px 14px;
    border-radius:10px; font-size:0.84rem; font-weight:700; text-decoration:none; border:1px solid #e2e8f0;
}
.ss-btn-ghost { background:#f8fafc; color:#334155 !important; }
.ss-btn-danger { background:#fff; color:#b91c1c !important; border-color:#fecaca; }
.ss-btn-main { background:#1e4a89; color:#fff !important; border-color:#1e4a89; width:100%; justify-content:center; }
.ss-btn-ok { background:#047857; color:#fff !important; border-color:#047857; width:100%; justify-content:center; }
.ss-step { background:#fff; border:1px solid #dbe7f3; border-radius:14px; padding:16px 18px; box-shadow:0 1px 4px rgba(15,23,42,.05); }
.ss-step-h { display:flex; gap:12px; align-items:flex-start; margin-bottom:14px; }
.ss-num {
    width:28px; height:28px; border-radius:8px; background:#1e4a89; color:#fff;
    font-weight:800; font-size:0.85rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;
}
.ss-step-h strong { display:block; font-size:1rem; color:#0f172a; }
.ss-step-h span { display:block; font-size:0.8rem; color:#64748b; margin-top:2px; }
.ss-grid-2 { display:grid; grid-template-columns: 1.15fr 0.85fr; gap:16px; align-items:start; }
@media (max-width: 900px) { .ss-grid-2 { grid-template-columns: 1fr; } }
.ss-kv { display:grid; grid-template-columns: 1fr 1fr; gap:10px 16px; margin:0; }
@media (max-width: 600px) { .ss-kv { grid-template-columns: 1fr; } }
.ss-kv div { min-width:0; }
.ss-kv dt { font-size:0.7rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; margin:0 0 2px; }
.ss-kv dd { margin:0; font-size:0.92rem; color:#0f172a; }
.ss-need { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px; white-space:pre-wrap; margin:0; }
.ss-badge { display:inline-block; padding:3px 8px; border-radius:999px; font-size:12px; font-weight:700; }
.ss-badge-pendiente { background:#fef3c7; color:#92400e; }
.ss-badge-confirmada { background:#dbeafe; color:#1e40af; }
.ss-badge-atendida { background:#d1fae5; color:#065f46; }
.ss-badge-cancelada { background:#fee2e2; color:#991b1b; }
.ss-badge-no_asistio { background:#e2e8f0; color:#475569; }
.ss-form .form-group { margin-bottom:12px; }
.ss-form .form-group:last-of-type { margin-bottom:14px; }
.ss-hc-entry { border-left:3px solid #1e4a89; padding:12px 14px; margin-bottom:10px; background:#f8fafc; border-radius:0 10px 10px 0; }
.ss-hc-entry h4 { margin:0 0 8px; font-size:0.9rem; color:#1e4a89; }
.ss-hc-field { margin-bottom:8px; }
.ss-hc-field strong { display:block; font-size:12px; color:#64748b; margin-bottom:2px; }
.ss-hc-field p { margin:0; white-space:pre-wrap; }
.ss-mini { width:100%; border-collapse:collapse; }
.ss-mini th, .ss-mini td { padding:8px 6px; font-size:0.8rem; text-align:left; border-bottom:1px solid #eef2f7; }
.ss-mini th { color:#64748b; font-size:0.68rem; text-transform:uppercase; }
.ss-docs { list-style:none; padding:0; margin:8px 0 0; display:flex; flex-wrap:wrap; gap:8px; }
</style>

<div class="ss-d">
    <div class="ss-d-head">
        <div>
            <h2><?= htmlspecialchars($nombre !== '' ? $nombre : ('Cita #' . $id), ENT_QUOTES, 'UTF-8') ?></h2>
            <p>
                Cita #<?= $id ?>
                · <span class="ss-badge ss-badge-<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(TallerServicioSocial::etiquetaEstado($estado), ENT_QUOTES, 'UTF-8') ?></span>
                · <?= htmlspecialchars(TallerServicioSocial::etiquetaTipo((string)($cita['Tipo_Cita'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
            </p>
        </div>
        <div class="ss-d-actions">
            <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social" class="ss-btn ss-btn-ghost">
                <i class="bi bi-arrow-left"></i> Listado
            </a>
            <?php if ($puedeGestionar): ?>
            <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social/eliminar&id=<?= $id ?>"
               class="ss-btn ss-btn-danger"
               onclick="return confirm('¿Eliminar esta cita? Esta acción no se puede deshacer.');">
                <i class="bi bi-trash"></i> Eliminar
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($flashOk !== ''): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if ($flashError !== ''): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="ss-grid-2">
        <div style="display:flex;flex-direction:column;gap:16px;">
            <section class="ss-step">
                <div class="ss-step-h">
                    <span class="ss-num">1</span>
                    <div>
                        <strong>Quién solicita</strong>
                        <span>Datos de la persona. No se crea en Personas; solo vive en Servicio Social.</span>
                    </div>
                </div>
                <dl class="ss-kv">
                    <div>
                        <dt>Nombre</dt>
                        <dd><?= htmlspecialchars($nombre !== '' ? $nombre : '—', ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Documento</dt>
                        <dd>
                            <?php if ($documento !== ''): ?>
                                <?= htmlspecialchars(TallerServicioSocial::etiquetaTipoDocumento($tipoDoc), ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars($documento, ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt>Teléfono</dt>
                        <dd><?= htmlspecialchars($tel !== '' ? $tel : '—', ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Correo</dt>
                        <dd><?= htmlspecialchars($email !== '' ? $email : '—', ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>EPS</dt>
                        <dd><?= htmlspecialchars($eps !== '' ? $eps : '—', ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>En este módulo</dt>
                        <dd><?= (int)$nCitas ?> cita(s) · <?= (int)$nHistoria ?> nota(s) clínicas</dd>
                    </div>
                </dl>
            </section>

            <section class="ss-step">
                <div class="ss-step-h">
                    <span class="ss-num">2</span>
                    <div>
                        <strong>Qué pidió</strong>
                        <span>Lo que la persona escribió en el formulario público. Solo lectura.</span>
                    </div>
                </div>
                <dl class="ss-kv" style="margin-bottom:12px;">
                    <div>
                        <dt>Tipo de cita</dt>
                        <dd><?= htmlspecialchars(TallerServicioSocial::etiquetaTipo((string)($cita['Tipo_Cita'] ?? '')), ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                    <div>
                        <dt>Día y hora que pidió</dt>
                        <dd>
                            <?= htmlspecialchars((string)($cita['Fecha_Preferida'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($cita['Hora_Preferida'])): ?>
                                · <?= htmlspecialchars(TallerServicioSocial::etiquetaHora((string)$cita['Hora_Preferida']), ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt>Remitido por</dt>
                        <dd>
                            <?= htmlspecialchars(TallerServicioSocial::etiquetaRemitido((string)($cita['Remitido_Por'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($cita['Remitido_Detalle'])): ?>
                                <small class="text-muted">(<?= htmlspecialchars((string)$cita['Remitido_Detalle'], ENT_QUOTES, 'UTF-8') ?>)</small>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt>Enviado</dt>
                        <dd><?= htmlspecialchars((string)($cita['Fecha_Creacion'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                    </div>
                </dl>
                <dt style="font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin:0 0 6px;">Necesidad principal</dt>
                <p class="ss-need"><?= htmlspecialchars($necesidad !== '' ? $necesidad : 'Sin descripción', ENT_QUOTES, 'UTF-8') ?></p>
                <?php if ($obsSolicitante !== ''): ?>
                <dt style="font-size:0.7rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin:12px 0 6px;">Observaciones de la persona</dt>
                <p class="ss-need"><?= htmlspecialchars($obsSolicitante, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if ($documentosRemision !== []): ?>
                <ul class="ss-docs">
                    <?php foreach ($documentosRemision as $doc): ?>
                    <li>
                        <a href="<?= htmlspecialchars((string)($doc['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="ss-btn ss-btn-ghost">
                            <i class="bi bi-paperclip"></i>
                            <?= htmlspecialchars((string)($doc['nombre'] ?? 'Documento'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <section class="ss-step">
        <div class="ss-step-h">
            <span class="ss-num">3</span>
            <div>
                <strong>Atender y dejar registro</strong>
                <span>Estado, notas del equipo y evaluación. La atención queda con el sábado y la hora ya agendados de esta cita.</span>
            </div>
        </div>
        <?php if ($puedeGestionar): ?>
        <div class="ss-grid-2">
            <form class="ss-form" method="POST" action="<?= PUBLIC_URL ?>?url=talleres/servicio-social/actualizar">
                <input type="hidden" name="id_cita" value="<?= $id ?>">
                <p style="margin:0 0 10px;font-size:0.78rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;">Esta cita</p>
                <div class="form-group">
                    <label for="estado">Estado</label>
                    <select id="estado" name="estado" class="form-control" required>
                        <?php foreach ($estados as $k => $label): ?>
                        <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $estado === $k ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="notas_internas">Notas del equipo</label>
                    <textarea id="notas_internas" name="notas_internas" class="form-control" rows="3"
                              placeholder="Llamadas, acuerdos…"><?= htmlspecialchars((string)($cita['Notas_Internas'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <p style="margin:16px 0 10px;font-size:0.78rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;">Evaluación de esta atención</p>
                <div class="form-group">
                    <label for="motivo_consulta">Motivo de consulta</label>
                    <textarea id="motivo_consulta" name="motivo_consulta" class="form-control" rows="2"><?= htmlspecialchars($necesidad, ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="diagnostico">Evaluación / diagnóstico</label>
                    <textarea id="diagnostico" name="diagnostico" class="form-control" rows="3" placeholder="Qué se observó"></textarea>
                </div>
                <div class="form-group">
                    <label for="formula">Plan / fórmula</label>
                    <textarea id="formula" name="formula" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label for="recomendaciones">Recomendaciones</label>
                    <textarea id="recomendaciones" name="recomendaciones" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label for="observaciones_hc">Notas clínicas extra</label>
                    <textarea id="observaciones_hc" name="observaciones_hc" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="ss-btn ss-btn-main">Guardar atención</button>
            </form>
            <div>
                <p style="margin:0 0 10px;font-size:0.78rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;">Historial</p>
                <?php if ($citasPaciente === [] && $historiaClinica === []): ?>
                <p class="text-muted" style="margin:0;">Aún no hay otras citas ni notas clínicas.</p>
                <?php else: ?>
                    <?php if ($citasPaciente !== []): ?>
                    <p style="margin:0 0 8px;font-size:0.8rem;color:#64748b;">Citas de esta persona</p>
                    <table class="ss-mini">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($citasPaciente as $otra): ?>
                            <?php
                            $oid = (int)($otra['Id_Cita'] ?? 0);
                            $estadoOtra = TallerServicioSocial::normalizarEstado((string)($otra['Estado'] ?? ''));
                            ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($otra['Fecha_Preferida'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars(TallerServicioSocial::etiquetaTipo((string)($otra['Tipo_Cita'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="ss-badge ss-badge-<?= htmlspecialchars($estadoOtra, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(TallerServicioSocial::etiquetaEstado($estadoOtra), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td>
                                    <?php if ($oid > 0 && $oid !== $id): ?>
                                    <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social/ver&id=<?= $oid ?>">Abrir</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                    <p style="margin:<?= $citasPaciente !== [] ? '14px' : '0' ?> 0 8px;font-size:0.8rem;color:#64748b;">Notas clínicas</p>
                    <?php if ($historiaClinica === []): ?>
                    <p class="text-muted" style="margin:0;">Aún no hay notas de evaluación.</p>
                    <?php else: ?>
                        <?php foreach ($historiaClinica as $entrada): ?>
                        <?php
                        $idCitaEntrada = (int)($entrada['Id_Cita'] ?? 0);
                        $fechaHc = (string)($entrada['Fecha_Atencion'] ?? '');
                        ?>
                        <div class="ss-hc-entry">
                            <h4>
                                <?= $fechaHc !== '' ? htmlspecialchars($fechaHc, ENT_QUOTES, 'UTF-8') : 'Sin fecha' ?>
                                <?php if ($idCitaEntrada === $id): ?>
                                <span class="ss-badge ss-badge-confirmada">esta cita</span>
                                <?php elseif ($idCitaEntrada > 0): ?>
                                · cita #<?= $idCitaEntrada ?>
                                <?php endif; ?>
                            </h4>
                            <?php foreach (['Motivo_Consulta' => 'Motivo', 'Diagnostico' => 'Evaluación', 'Formula' => 'Plan', 'Recomendaciones' => 'Recomendaciones', 'Observaciones' => 'Notas'] as $campo => $rotulo): ?>
                                <?php if (!empty($entrada[$campo])): ?>
                                <div class="ss-hc-field">
                                    <strong><?= $rotulo ?></strong>
                                    <p><?= htmlspecialchars((string)$entrada[$campo], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <p class="text-muted" style="margin:0;">Solo consulta. No puede cambiar el estado ni registrar atención.</p>
        <?php endif; ?>
    </section>

    <?php if ($puedeGestionar): ?>
    <section class="ss-step">
        <div class="ss-step-h">
            <span class="ss-num">4</span>
            <div>
                <strong>Agendar nueva cita</strong>
                <span>Si hace falta un seguimiento, elija otro sábado y hora. Se crea una cita nueva para esta misma persona.</span>
            </div>
        </div>
        <form class="ss-form" method="POST" action="<?= PUBLIC_URL ?>?url=talleres/servicio-social/agendar-nueva" style="max-width:480px;">
            <input type="hidden" name="id_cita" value="<?= $id ?>">
            <div class="form-group">
                <label for="nueva_fecha">Sábado</label>
                <select id="nueva_fecha" name="fecha_preferida" class="form-control" required>
                    <option value="">Elija sábado…</option>
                    <?php foreach ($proximosSabados as $sab): ?>
                    <option value="<?= htmlspecialchars((string)($sab['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string)($sab['label'] ?? $sab['fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="nueva_hora">Hora</label>
                <select id="nueva_hora" name="hora_preferida" class="form-control" required>
                    <option value="">Elija hora…</option>
                    <?php foreach ($horariosSabado as $hk => $hl): ?>
                    <option value="<?= htmlspecialchars($hk, ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($hl, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="nueva_tipo">Tipo de cita</label>
                <select id="nueva_tipo" name="tipo_cita" class="form-control" required>
                    <?php foreach ($tiposCita as $tk => $tl): ?>
                    <option value="<?= htmlspecialchars($tk, ENT_QUOTES, 'UTF-8') ?>" <?= ((string)($cita['Tipo_Cita'] ?? '') === $tk) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tl, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="nueva_need">Motivo / seguimiento</label>
                <textarea id="nueva_need" name="necesidad_principal" class="form-control" rows="2" placeholder="Ej. Control, siguiente sesión…">Seguimiento</textarea>
            </div>
            <button type="submit" class="ss-btn ss-btn-ok" style="width:auto;">
                <i class="bi bi-calendar-plus"></i> Agendar nueva cita
            </button>
        </form>
    </section>
    <?php endif; ?>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
