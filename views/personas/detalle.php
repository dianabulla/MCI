<?php include VIEWS . '/layout/header.php'; ?>

<?php
$returnTo = $return_to ?? null;
$editUrl = PUBLIC_URL . '?url=personas/editar&id=' . (int)$persona['Id_Persona'];
$volverUrl = PUBLIC_URL . '?url=personas';

if ($returnTo === 'celulas') {
    $editUrl .= '&return_to=celulas';
    $volverUrl = PUBLIC_URL . '?url=celulas';
} elseif ($returnTo === 'asistencia') {
    $editUrl .= '&return_to=asistencia';
}
?>

<div class="page-header">
    <h2>Detalle de Persona</h2>
    <div>
        <a href="<?= $editUrl ?>" class="btn btn-warning">Editar</a>
        <a href="<?= $volverUrl ?>" class="btn btn-secondary">Volver</a>
    </div>
</div>

<div class="detail-container">
    <div class="detail-section">
        <h3>Información Personal</h3>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Nombre Completo:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Nombre'] . ' ' . $persona['Apellido']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Tipo de Documento:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Tipo_Documento'] ?? 'No especificado') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Número de Documento:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Numero_Documento'] ?? 'No especificado') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Fecha de Nacimiento:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Fecha_Nacimiento'] ?? 'No especificado') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Edad:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Edad'] ?? 'No especificado') ?> años</span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Género:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Genero'] ?? 'No especificado') ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h3>Información de Contacto</h3>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Teléfono:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Telefono'] ?? 'No especificado') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Email'] ?? 'No especificado') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Mejor Hora para Llamar:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Hora_Llamada'] ?? 'No especificado') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Dirección:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Direccion'] ?? 'No especificado') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Barrio:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Barrio'] ?? 'No especificado') ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h3>Información Ministerial</h3>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Célula:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Nombre_Celula'] ?? 'Sin célula') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Rol:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Nombre_Rol'] ?? 'Sin rol') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Ministerio:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Nombre_Ministerio'] ?? 'Sin ministerio') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Líder:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Nombre_Lider'] ?? 'Sin líder') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Invitado Por:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Invitado_Por'] ?? 'No aplica') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Ganado en:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Tipo_Reunion'] ?? 'No especificado') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Escalera del Éxito:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Proceso'] ?? 'No especificado') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Estado de Cuenta:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Estado_Cuenta'] ?? 'Activo') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Usuario de acceso:</span>
                <span class="detail-value"><?= htmlspecialchars($persona['Usuario'] ?? 'No asignado') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Última vez que ingresó:</span>
                <span class="detail-value">
                    <?php if (!empty($persona['Ultimo_Acceso'])): ?>
                        <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$persona['Ultimo_Acceso']))) ?>
                    <?php else: ?>
                        Sin registro
                    <?php endif; ?>
                </span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Última vez que reportó célula:</span>
                <span class="detail-value">
                    <?php if (!empty($persona['Ultimo_Reporte_Celula'])): ?>
                        <?= htmlspecialchars(date('d/m/Y', strtotime((string)$persona['Ultimo_Reporte_Celula']))) ?>
                    <?php else: ?>
                        Sin reportes
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h3>Petición de Oración</h3>
        <div class="detail-full">
            <p><?= !empty($persona['Peticion']) ? nl2br(htmlspecialchars($persona['Peticion'])) : 'No especificada' ?></p>
        </div>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
