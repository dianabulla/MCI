<?php include VIEWS . '/layout/header.php'; ?>



<?php

require_once APP . '/Models/TallerServicioSocial.php';

$cita = is_array($cita ?? null) ? $cita : [];

$estados = is_array($estados ?? null) ? $estados : TallerServicioSocial::ESTADOS;

$historiaClinica = is_array($historia_clinica ?? null) ? $historia_clinica : [];

$citasPaciente = is_array($citas_paciente ?? null) ? $citas_paciente : [];

$documentosRemision = is_array($documentos_remision ?? null) ? $documentos_remision : [];

$horariosSabado = is_array($horarios_sabado ?? null) ? $horarios_sabado : TallerServicioSocial::HORARIOS_SABADO;

$puedeGestionar = !empty($puede_gestionar);

$flashOk = (string)($flash_ok ?? '');

$flashError = (string)($flash_error ?? '');

$id = (int)($cita['Id_Cita'] ?? 0);

$estado = (string)($cita['Estado'] ?? 'pendiente');

$nombre = trim((string)($cita['Nombre'] ?? '') . ' ' . (string)($cita['Apellido'] ?? ''));

$tipoDoc = (string)($cita['Tipo_Documento'] ?? '');

$documento = (string)($cita['Documento'] ?? '');

?>



<style>

.ss-detail-grid {

    display:grid;

    grid-template-columns: minmax(0, 1.4fr) minmax(280px, 0.8fr);

    gap:16px;

    align-items:start;

}

@media (max-width: 900px) {

    .ss-detail-grid { grid-template-columns: 1fr; }

}

.ss-card {

    background:#fff;

    border:1px solid #e5e7eb;

    border-radius:12px;

    padding:18px;

    margin-bottom:16px;

}

.ss-card h3 {

    margin:0 0 12px;

    font-size:1.05rem;

}

.ss-dl {

    display:grid;

    grid-template-columns: 160px 1fr;

    gap:8px 12px;

    margin:0;

}

.ss-dl dt { color:#64748b; font-weight:600; font-size:13px; }

.ss-dl dd { margin:0; }

.ss-need-box {

    white-space:pre-wrap;

    background:#f8fafc;

    border-radius:8px;

    padding:12px;

    border:1px solid #e2e8f0;

}

.ss-badge {

    display:inline-block; padding:3px 8px; border-radius:999px;

    font-size:12px; font-weight:600;

}

.ss-badge-pendiente { background:#fef3c7; color:#92400e; }

.ss-badge-confirmada { background:#dbeafe; color:#1e40af; }

.ss-badge-atendida { background:#d1fae5; color:#065f46; }

.ss-badge-cancelada { background:#fee2e2; color:#991b1b; }

.ss-badge-no_asistio { background:#e2e8f0; color:#475569; }

.ss-hc-entry {

    border-left:3px solid #2857a0;

    padding:12px 14px;

    margin-bottom:12px;

    background:#f8fafc;

    border-radius:0 8px 8px 0;

}

.ss-hc-entry h4 {

    margin:0 0 8px;

    font-size:0.95rem;

    color:#2857a0;

}

.ss-hc-field { margin-bottom:8px; }

.ss-hc-field strong { display:block; font-size:12px; color:#64748b; margin-bottom:2px; }

.ss-hc-field p { margin:0; white-space:pre-wrap; }

.ss-doc-list { list-style:none; padding:0; margin:0; }

.ss-doc-list li { margin-bottom:8px; }

.ss-doc-list a { text-decoration:none; }

.ss-patient-banner {

    background:linear-gradient(135deg, #eef3fb, #f0fdf4);

    border:1px solid #dbe3f0;

    border-radius:12px;

    padding:14px 18px;

    margin-bottom:16px;

}

</style>



<div class="page-header">

    <h2>Cita #<?= $id ?> — Servicio Social</h2>

    <p class="text-muted" style="margin:4px 0 0;">Detalle de la solicitud, historia clínica y gestión del estado.</p>

    <div style="margin-top:12px;">

        <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social" class="btn btn-secondary btn-sm">

            <i class="bi bi-arrow-left"></i> Volver al listado

        </a>

    </div>

</div>



<?php if ($flashOk !== ''): ?>

<div class="alert alert-success"><?= htmlspecialchars($flashOk, ENT_QUOTES, 'UTF-8') ?></div>

<?php endif; ?>

<?php if ($flashError !== ''): ?>

<div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>

<?php endif; ?>



<?php if ($documento !== ''): ?>

<div class="ss-patient-banner">

    <strong><i class="bi bi-person-vcard"></i> Historia clínica del paciente</strong>

    <div style="margin-top:4px;">

        <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>

        — <?= htmlspecialchars(TallerServicioSocial::etiquetaTipoDocumento($tipoDoc), ENT_QUOTES, 'UTF-8') ?>

        <?= htmlspecialchars($documento, ENT_QUOTES, 'UTF-8') ?>

        <?php if (!empty($cita['Nombre_Eps'])): ?>

            · EPS: <?= htmlspecialchars((string)$cita['Nombre_Eps'], ENT_QUOTES, 'UTF-8') ?>

        <?php endif; ?>

        · <?= count($historiaClinica) ?> entrada(s) clínica(s)

        · <?= count($citasPaciente) + 1 ?> cita(s) registrada(s)

    </div>

</div>

<?php endif; ?>



<div class="ss-detail-grid">

    <div>

        <div class="ss-card">

            <h3>Respuesta del formulario</h3>

            <dl class="ss-dl">

                <dt>Solicitante</dt>

                <dd><strong><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></strong></dd>



                <dt>Tipo documento</dt>

                <dd><?= htmlspecialchars(TallerServicioSocial::etiquetaTipoDocumento($tipoDoc), ENT_QUOTES, 'UTF-8') ?></dd>



                <dt>Documento</dt>

                <dd><?= htmlspecialchars($documento !== '' ? $documento : '—', ENT_QUOTES, 'UTF-8') ?></dd>



                <dt>EPS</dt>

                <dd><?= htmlspecialchars((string)($cita['Nombre_Eps'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>



                <dt>Teléfono</dt>

                <dd><?= htmlspecialchars((string)($cita['Telefono'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>



                <dt>Email</dt>

                <dd><?= htmlspecialchars((string)($cita['Email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>



                <dt>Fecha preferida</dt>

                <dd>

                    <?= htmlspecialchars((string)($cita['Fecha_Preferida'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>

                    <?php if (!empty($cita['Hora_Preferida'])): ?>

                        — <?= htmlspecialchars(TallerServicioSocial::etiquetaHora((string)$cita['Hora_Preferida']), ENT_QUOTES, 'UTF-8') ?>

                    <?php endif; ?>

                </dd>



                <dt>Tipo de cita</dt>

                <dd><?= htmlspecialchars(TallerServicioSocial::etiquetaTipo((string)($cita['Tipo_Cita'] ?? '')), ENT_QUOTES, 'UTF-8') ?></dd>



                <dt>Remitido por</dt>

                <dd>

                    <?= htmlspecialchars(TallerServicioSocial::etiquetaRemitido((string)($cita['Remitido_Por'] ?? '')), ENT_QUOTES, 'UTF-8') ?>

                    <?php if (!empty($cita['Remitido_Detalle'])): ?>

                        <br><small class="text-muted"><?= htmlspecialchars((string)$cita['Remitido_Detalle'], ENT_QUOTES, 'UTF-8') ?></small>

                    <?php endif; ?>

                </dd>



                <dt>Estado</dt>

                <dd>

                    <span class="ss-badge ss-badge-<?= htmlspecialchars($estado, ENT_QUOTES, 'UTF-8') ?>">

                        <?= htmlspecialchars(TallerServicioSocial::etiquetaEstado($estado), ENT_QUOTES, 'UTF-8') ?>

                    </span>

                </dd>



                <dt>Solicitado el</dt>

                <dd><?= htmlspecialchars((string)($cita['Fecha_Creacion'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>

            </dl>



            <h3 style="margin-top:20px;">Principal necesidad</h3>

            <div class="ss-need-box"><?= htmlspecialchars((string)($cita['Necesidad_Principal'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>



            <?php if (!empty($cita['Observaciones'])): ?>

            <h3 style="margin-top:20px;">Observaciones del solicitante</h3>

            <div class="ss-need-box"><?= htmlspecialchars((string)$cita['Observaciones'], ENT_QUOTES, 'UTF-8') ?></div>

            <?php endif; ?>



            <?php if ($documentosRemision !== []): ?>

            <h3 style="margin-top:20px;">Documentos de remisión</h3>

            <ul class="ss-doc-list">

                <?php foreach ($documentosRemision as $doc): ?>

                <li>

                    <a href="<?= htmlspecialchars((string)($doc['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">

                        <i class="bi bi-paperclip"></i>

                        <?= htmlspecialchars((string)($doc['nombre'] ?? 'Documento'), ENT_QUOTES, 'UTF-8') ?>

                    </a>

                    <?php if (!empty($doc['fecha'])): ?>

                        <small class="text-muted"> — <?= htmlspecialchars((string)$doc['fecha'], ENT_QUOTES, 'UTF-8') ?></small>

                    <?php endif; ?>

                </li>

                <?php endforeach; ?>

            </ul>

            <?php endif; ?>

        </div>



        <?php if ($citasPaciente !== []): ?>

        <div class="ss-card">

            <h3>Otras citas del paciente</h3>

            <div class="table-responsive">

                <table class="data-table">

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

                        <?php $oid = (int)($otra['Id_Cita'] ?? 0); ?>

                        <tr>

                            <td><?= htmlspecialchars((string)($otra['Fecha_Preferida'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>

                            <td><?= htmlspecialchars(TallerServicioSocial::etiquetaTipo((string)($otra['Tipo_Cita'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>

                            <td>

                                <span class="ss-badge ss-badge-<?= htmlspecialchars((string)($otra['Estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

                                    <?= htmlspecialchars(TallerServicioSocial::etiquetaEstado((string)($otra['Estado'] ?? '')), ENT_QUOTES, 'UTF-8') ?>

                                </span>

                            </td>

                            <td>

                                <a href="<?= PUBLIC_URL ?>?url=talleres/servicio-social/ver&id=<?= $oid ?>" class="btn btn-sm btn-outline-primary">Ver</a>

                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <?php endif; ?>

    </div>



    <div>

        <div class="ss-card">

            <h3>Gestión interna</h3>

            <?php if ($puedeGestionar): ?>

            <form method="POST" action="<?= PUBLIC_URL ?>?url=talleres/servicio-social/actualizar">

                <input type="hidden" name="id_cita" value="<?= $id ?>">



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

                    <label for="fecha_preferida">Fecha preferida / confirmada</label>

                    <input id="fecha_preferida" type="date" name="fecha_preferida" class="form-control"

                           value="<?= htmlspecialchars((string)($cita['Fecha_Preferida'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

                </div>



                <div class="form-group">

                    <label for="hora_preferida">Hora (sábados)</label>

                    <select id="hora_preferida" name="hora_preferida" class="form-control">

                        <option value="">—</option>

                        <?php $horaActual = TallerServicioSocial::normalizarHora((string)($cita['Hora_Preferida'] ?? '')); ?>

                        <?php foreach ($horariosSabado as $hk => $hl): ?>

                        <option value="<?= htmlspecialchars($hk, ENT_QUOTES, 'UTF-8') ?>" <?= $horaActual === $hk ? 'selected' : '' ?>>

                            <?= htmlspecialchars($hl, ENT_QUOTES, 'UTF-8') ?>

                        </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <div class="form-group">

                    <label for="fecha_atencion">Fecha de atención</label>

                    <input id="fecha_atencion" type="datetime-local" name="fecha_atencion" class="form-control"

                           value="<?= !empty($cita['Fecha_Atencion']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime((string)$cita['Fecha_Atencion'])), ENT_QUOTES, 'UTF-8') : '' ?>">

                </div>



                <div class="form-group">

                    <label for="notas_internas">Notas internas</label>

                    <textarea id="notas_internas" name="notas_internas" class="form-control" rows="4"

                              placeholder="Seguimiento, acuerdos, remisiones internas…"><?= htmlspecialchars((string)($cita['Notas_Internas'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

                </div>



                <button type="submit" class="btn btn-primary" style="width:100%;">

                    Guardar cambios

                </button>

            </form>

            <?php else: ?>

                <p class="text-muted" style="margin:0;">Solo lectura. No tienes permiso para editar el estado.</p>

                <?php if (!empty($cita['Notas_Internas'])): ?>

                <div class="ss-need-box" style="margin-top:12px;"><?= htmlspecialchars((string)$cita['Notas_Internas'], ENT_QUOTES, 'UTF-8') ?></div>

                <?php endif; ?>

            <?php endif; ?>

        </div>



        <?php if ($puedeGestionar): ?>

        <div class="ss-card">

            <h3><i class="bi bi-journal-medical"></i> Nueva entrada clínica</h3>

            <p class="text-muted" style="font-size:13px;margin:-6px 0 12px;">Registra motivo, diagnóstico, fórmula y recomendaciones como en un consultorio.</p>

            <form method="POST" action="<?= PUBLIC_URL ?>?url=talleres/servicio-social/guardar-historia">

                <input type="hidden" name="id_cita" value="<?= $id ?>">



                <div class="form-group">

                    <label for="fecha_atencion_hc">Fecha de atención</label>

                    <input id="fecha_atencion_hc" type="datetime-local" name="fecha_atencion_hc" class="form-control"

                           value="<?= htmlspecialchars(date('Y-m-d\TH:i'), ENT_QUOTES, 'UTF-8') ?>">

                </div>



                <div class="form-group">

                    <label for="motivo_consulta">Motivo de consulta</label>

                    <textarea id="motivo_consulta" name="motivo_consulta" class="form-control" rows="2"

                              placeholder="Motivo por el que acude"><?= htmlspecialchars((string)($cita['Necesidad_Principal'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

                </div>



                <div class="form-group">

                    <label for="diagnostico">Diagnóstico / evaluación</label>

                    <textarea id="diagnostico" name="diagnostico" class="form-control" rows="3"

                              placeholder="Diagnóstico o evaluación psicosocial"></textarea>

                </div>



                <div class="form-group">

                    <label for="formula">Fórmula / plan de tratamiento</label>

                    <textarea id="formula" name="formula" class="form-control" rows="3"

                              placeholder="Medicamentos, terapias, remisiones, apoyos…"></textarea>

                </div>



                <div class="form-group">

                    <label for="recomendaciones">Recomendaciones</label>

                    <textarea id="recomendaciones" name="recomendaciones" class="form-control" rows="2"

                              placeholder="Indicaciones para el paciente"></textarea>

                </div>



                <div class="form-group">

                    <label for="observaciones_hc">Observaciones clínicas</label>

                    <textarea id="observaciones_hc" name="observaciones_hc" class="form-control" rows="2"

                              placeholder="Notas adicionales"></textarea>

                </div>



                <button type="submit" class="btn btn-success" style="width:100%;">

                    <i class="bi bi-save"></i> Guardar en historia clínica

                </button>

            </form>

        </div>

        <?php endif; ?>

    </div>

</div>



<div class="ss-card">

    <h3><i class="bi bi-clock-history"></i> Historia clínica</h3>

    <?php if ($historiaClinica === []): ?>

        <p class="text-muted" style="margin:0;">Aún no hay entradas clínicas para este paciente.</p>

    <?php else: ?>

        <?php foreach ($historiaClinica as $entrada): ?>

        <?php

        $idEntrada = (int)($entrada['Id_Entrada'] ?? 0);

        $idCitaEntrada = (int)($entrada['Id_Cita'] ?? 0);

        $fechaHc = (string)($entrada['Fecha_Atencion'] ?? '');

        ?>

        <div class="ss-hc-entry">

            <h4>

                <?= $fechaHc !== '' ? htmlspecialchars($fechaHc, ENT_QUOTES, 'UTF-8') : 'Sin fecha' ?>

                <?php if ($idCitaEntrada > 0): ?>

                    · Cita #<?= $idCitaEntrada ?>

                    <?php if ($idCitaEntrada === $id): ?><span class="ss-badge ss-badge-confirmada">actual</span><?php endif; ?>

                <?php endif; ?>

            </h4>



            <?php if (!empty($entrada['Motivo_Consulta'])): ?>

            <div class="ss-hc-field">

                <strong>Motivo de consulta</strong>

                <p><?= htmlspecialchars((string)$entrada['Motivo_Consulta'], ENT_QUOTES, 'UTF-8') ?></p>

            </div>

            <?php endif; ?>



            <?php if (!empty($entrada['Diagnostico'])): ?>

            <div class="ss-hc-field">

                <strong>Diagnóstico / evaluación</strong>

                <p><?= htmlspecialchars((string)$entrada['Diagnostico'], ENT_QUOTES, 'UTF-8') ?></p>

            </div>

            <?php endif; ?>



            <?php if (!empty($entrada['Formula'])): ?>

            <div class="ss-hc-field">

                <strong>Fórmula / plan de tratamiento</strong>

                <p><?= htmlspecialchars((string)$entrada['Formula'], ENT_QUOTES, 'UTF-8') ?></p>

            </div>

            <?php endif; ?>



            <?php if (!empty($entrada['Recomendaciones'])): ?>

            <div class="ss-hc-field">

                <strong>Recomendaciones</strong>

                <p><?= htmlspecialchars((string)$entrada['Recomendaciones'], ENT_QUOTES, 'UTF-8') ?></p>

            </div>

            <?php endif; ?>



            <?php if (!empty($entrada['Observaciones'])): ?>

            <div class="ss-hc-field">

                <strong>Observaciones</strong>

                <p><?= htmlspecialchars((string)$entrada['Observaciones'], ENT_QUOTES, 'UTF-8') ?></p>

            </div>

            <?php endif; ?>

        </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>



<?php include VIEWS . '/layout/footer.php'; ?>

