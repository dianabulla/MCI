<?php
include VIEWS . '/layout/header.php';

$fechaAperturaRaw = substr(trim((string)($celula['Fecha_Apertura'] ?? '')), 0, 10);
$fechaAperturaVisible = 'No especificada';
if ($fechaAperturaRaw !== '' && $fechaAperturaRaw !== '0000-00-00') {
    $tsApertura = strtotime($fechaAperturaRaw);
    $fechaAperturaVisible = $tsApertura ? date('d/m/Y', $tsApertura) : $fechaAperturaRaw;
}

$idCelulaDetalle = (int)($celula['Id_Celula'] ?? 0);
$returnUrlCelula = PUBLIC_URL . 'index.php?url=celulas/detalle&id=' . $idCelulaDetalle;
?>

<div class="page-header">
    <h2>Detalle de Célula</h2>
    <div class="celulas-actions-row">
        <?php
        $personasSegDetalle = is_array($personasEnSeguimiento ?? null) ? $personasEnSeguimiento : [];
        $payloadSegDetalle = [];
        foreach ($personasSegDetalle as $pSegDet) {
            $payloadSegDetalle[] = [
                'id' => (int)($pSegDet['Id_Persona'] ?? 0),
                'nombre' => (string)($pSegDet['nombre'] ?? ''),
                'telefono' => (string)($pSegDet['telefono'] ?? ''),
                'celula' => (string)($pSegDet['celula'] ?? ''),
                'lider' => (string)($pSegDet['lider'] ?? ''),
            ];
        }
        ?>
        <button
            type="button"
            class="btn btn-sm celulas-action-btn celulas-action-btn--seguimiento js-ver-seguimiento-celula"
            data-titulo="<?= htmlspecialchars('En seguimiento · ' . (string)($celula['Nombre_Celula'] ?? 'Célula'), ENT_QUOTES, 'UTF-8') ?>"
            data-mostrar-celula="0"
            data-personas="<?= htmlspecialchars(json_encode($payloadSegDetalle, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>"
            title="Ver personas en seguimiento"
        >
            <i class="bi bi-signpost-2" aria-hidden="true"></i>
            <span>En seguimiento</span>
            <span class="celulas-seguimiento-badge"><?= count($payloadSegDetalle) ?></span>
        </button>
        <a href="<?= PUBLIC_URL ?>index.php?url=personas/crear&return_to=celulas&return_url=<?= urlencode($returnUrlCelula) ?>&celula=<?= $idCelulaDetalle ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--icon" title="Nueva persona" aria-label="Nueva persona">
            <i class="bi bi-person-plus-fill" aria-hidden="true"></i>
        </a>
        <?php if (AuthController::puede('asistencias:crear')): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=asistencias/registrar&celula=<?= $idCelulaDetalle ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--report">Reportar célula</a>
        <?php endif; ?>
        <?php if (AuthController::puede('celulas:editar')): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=celulas/editar&id=<?= $idCelulaDetalle ?>" class="btn btn-sm celulas-action-btn celulas-action-btn--icon celulas-action-btn--edit" title="Editar" aria-label="Editar">
            <i class="bi bi-pencil-fill" aria-hidden="true"></i>
        </a>
        <?php endif; ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=celulas" class="btn btn-sm celulas-action-btn celulas-action-btn--icon" title="Volver" aria-label="Volver">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
        </a>
    </div>
</div>

<div class="detail-container">
    <div class="detail-section">
        <h3>Información de la Célula</h3>
        <div class="detail-grid">
            <div class="detail-item" style="grid-column: 1 / -1;">
                <span class="detail-label">Fecha de apertura:</span>
                <span class="detail-value" style="font-weight: 700; color: #1d4ed8;"><?= htmlspecialchars($fechaAperturaVisible) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Nombre:</span>
                <span class="detail-value"><?= htmlspecialchars($celula['Nombre_Celula']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Dirección:</span>
                <span class="detail-value"><?= htmlspecialchars($celula['Direccion_Celula'] ?? 'No especificada') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Día de Reunión:</span>
                <span class="detail-value"><?= htmlspecialchars($celula['Dia_Reunion'] ?? 'No especificado') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Hora de Reunión:</span>
                <span class="detail-value"><?= htmlspecialchars($celula['Hora_Reunion'] ?? 'No especificada') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Estado:</span>
                <span class="detail-value"><?= htmlspecialchars((string)($celula['Estado_Celula'] ?? 'Activa')) ?></span>
            </div>
            <?php
            $estadoDetalleCel = strtolower(trim((string)($celula['Estado_Celula'] ?? 'Activa')));
            $obsCierreDetalle = trim((string)($celula['Observaciones_Cierre'] ?? ''));
            if ($estadoDetalleCel === 'cerrada' && $obsCierreDetalle !== ''):
            ?>
            <div class="detail-item" style="grid-column:1 / -1;">
                <span class="detail-label">Motivo de cierre:</span>
                <span class="detail-value"><?= nl2br(htmlspecialchars($obsCierreDetalle)) ?></span>
            </div>
            <?php endif; ?>

            <div class="detail-item">
                <span class="detail-label">Ministerio:</span>
                <span class="detail-value"><?= htmlspecialchars((string)($celula['Nombre_Ministerio'] ?? 'No especificado')) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Líder:</span>
                <span class="detail-value"><?= htmlspecialchars((string)($celula['Nombre_Lider'] ?? 'No especificado')) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Pastor:</span>
                <span class="detail-value"><?= htmlspecialchars(trim((string)($celula['Nombre_Pastor'] ?? $celula['Pastor_Principal'] ?? '')) ?: 'No especificado') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Líder de 12:</span>
                <span class="detail-value"><?= htmlspecialchars((string)($celula['Nombre_Lider_Inmediato'] ?? 'No especificado')) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Anfitrión:</span>
                <span class="detail-value"><?= htmlspecialchars((string)($celula['Nombre_Anfitrion'] ?? 'No especificado')) ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h3>Miembros de la célula (<?= count($celula['miembros'] ?? []) ?>)</h3>
        <p style="margin: 0 0 12px; color: #5b6b86; font-size: 0.92em;">Sin asistencia: ninguna reunión en esta célula. Regular: 1 o 2. Frecuente: 3 o más.</p>
        
        <?php if (!empty($celula['miembros'])): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Documento</th>
                            <th>Teléfono</th>
                            <th>Indicador</th>
                            <th>Asistencias</th>
                            <th>Proceso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($celula['miembros'] as $miembro): ?>
                            <?php
                            $nombreCompleto = trim(($miembro['Nombre'] ?? '') . ' ' . ($miembro['Apellido'] ?? ''));
                            $indicador = (string)($miembro['Indicador_Asistencia'] ?? 'Sin asistencia');
                            $claseIndicador = $indicador === 'Frecuente'
                                ? 'badge-success'
                                : ($indicador === 'Regular' ? 'badge-warning' : 'badge-secondary');
                            $documento = trim((string)($miembro['Numero_Documento'] ?? ''));
                            if ($documento === '') {
                                $documento = 'N/A';
                            }
                            $proceso = trim((string)($miembro['Proceso'] ?? ''));
                            if ($proceso === '') {
                                $proceso = 'Sin proceso';
                            }
                            ?>
                            <tr>
                                <td data-label="Nombre"><?= htmlspecialchars($nombreCompleto) ?></td>
                                <td data-label="Documento"><?= htmlspecialchars($documento) ?></td>
                                <td data-label="Teléfono"><?= htmlspecialchars($miembro['Telefono'] ?? 'N/A') ?></td>
                                <td data-label="Indicador">
                                    <span class="badge <?= $claseIndicador ?>"><?= htmlspecialchars($indicador) ?></span>
                                </td>
                                <td data-label="Asistencias"><?= (int)($miembro['Total_Asistencias'] ?? 0) ?></td>
                                <td data-label="Proceso"><?= htmlspecialchars($proceso) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center" style="padding: 20px; color: #666;">No hay miembros registrados en esta célula</p>
        <?php endif; ?>
    </div>
</div>

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
    background: #e9f8ef;
    border-color: #c6ebd4;
    color: #1f7a44;
}
.celulas-action-btn--report:hover {
    background: #def4e7;
    color: #176a39;
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
.celulas-action-btn--seguimiento {
    min-width: 32px;
    width: auto;
    height: 32px;
    padding: 0 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    background: #ecfdf5;
    border-color: #99f6e4;
    color: #0f766e;
    font-weight: 700;
}
.celulas-action-btn--seguimiento:hover {
    background: #ccfbf1;
    color: #115e59;
}
.celulas-seguimiento-badge {
    display: inline-flex;
    min-width: 18px;
    height: 18px;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: #0f766e;
    color: #fff;
    font-size: 10px;
    padding: 0 5px;
    line-height: 1;
}
</style>

<?php include VIEWS . '/personas/partials/modal_seguimiento.php'; ?>
<?php include VIEWS . '/celulas/partials/modal_lista_seguimiento.php'; ?>
<?php include VIEWS . '/layout/footer.php'; ?>
