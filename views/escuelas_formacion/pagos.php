<?php include VIEWS . '/layout/header.php'; ?>

<?php
$resumen = is_array($resumen ?? null) ? $resumen : [];
$detalle = is_array($detalle ?? null) ? $detalle : [];
$buscar = (string)($buscar ?? '');
$cedulaDetalle = (string)($cedula_detalle ?? '');
$programa = (string)($programa ?? 'universidad_vida');
$programaLabel = $programa === 'capacitacion_destino' ? 'Capacitación Destino' : 'Universidad de la Vida';
$bloquearSelectorPrograma = !empty($bloquear_selector_programa);
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;">Escuelas de Formación - Pagos y Abonos</h2>
        <small style="color:#637087;">Vista actual: <strong><?= htmlspecialchars($programaLabel) ?></strong></small>
    </div>
    <div>
        <a href="<?= PUBLIC_URL ?>?url=home" class="btn btn-outline-secondary btn-sm">Volver al panel</a>
    </div>
</div>

<?php if (!$bloquearSelectorPrograma): ?>
<div class="card" style="padding:14px; margin-bottom:16px;">
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/pagos&programa=universidad_vida" class="btn btn-sm <?= $programa === 'universidad_vida' ? 'btn-primary' : 'btn-outline-secondary' ?>">Universidad de la Vida</a>
        <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/pagos&programa=capacitacion_destino" class="btn btn-sm <?= $programa === 'capacitacion_destino' ? 'btn-primary' : 'btn-outline-secondary' ?>">Capacitación Destino</a>
    </div>
</div>
<?php endif; ?>

<div class="card" style="padding:16px; margin-bottom:16px;">
    <form method="GET" action="<?= PUBLIC_URL ?>" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="url" value="escuelas_formacion/pagos">
        <input type="hidden" name="programa" value="<?= htmlspecialchars($programa) ?>">
        <div>
            <label for="buscar" style="display:block; font-size:13px; color:#475569; margin-bottom:6px;">Buscar</label>
            <input type="text" id="buscar" name="buscar" value="<?= htmlspecialchars($buscar) ?>" class="form-control" placeholder="Nombre, cédula, teléfono, referencia" style="min-width:300px;">
        </div>
        <div>
            <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
            <a href="<?= PUBLIC_URL ?>?url=escuelas_formacion/pagos&programa=<?= urlencode($programa) ?>" class="btn btn-outline-secondary btn-sm">Limpiar</a>
        </div>
    </form>
</div>

<div class="card" style="padding:16px; margin-bottom:16px;">
    <h3 style="margin-top:0;">Resumen por persona</h3>
    <?php if (empty($resumen)): ?>
        <p style="margin:0; color:#64748b;">No hay pagos registrados con ese filtro.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table" style="width:100%; min-width:980px;">
                <thead>
                    <tr>
                        <th>Persona</th>
                        <th>Cédula</th>
                        <th>Teléfono</th>
                        <th>Registros</th>
                        <th>Total pagado</th>
                        <th>Total en abonos</th>
                        <th>Cantidad abonos</th>
                        <th>Último movimiento</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resumen as $row): ?>
                        <?php
                        $cedulaClave = (string)($row['Cedula_Clave'] ?? '');
                        $urlDetalle = PUBLIC_URL . '?url=escuelas_formacion/pagos&programa=' . urlencode($programa) . '&buscar=' . urlencode($buscar) . '&cedula=' . urlencode($cedulaClave);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($row['Nombre'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Cedula'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($row['Telefono'] ?? '')) ?></td>
                            <td><?= (int)($row['Registros_Pago'] ?? 0) ?></td>
                            <td>$<?= number_format((float)($row['Total_Pagado'] ?? 0), 0, ',', '.') ?></td>
                            <td>$<?= number_format((float)($row['Total_Abonos'] ?? 0), 0, ',', '.') ?></td>
                            <td><?= (int)($row['Cantidad_Abonos'] ?? 0) ?></td>
                            <td><?= htmlspecialchars((string)($row['Ultimo_Movimiento'] ?? '')) ?></td>
                            <td><a href="<?= htmlspecialchars($urlDetalle) ?>" class="btn btn-outline-secondary btn-sm">Ver</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($cedulaDetalle)): ?>
<div class="card" style="padding:16px;">
    <h3 style="margin-top:0;">Detalle de pagos</h3>
    <p style="color:#64748b;">Clave de persona: <strong><?= htmlspecialchars($cedulaDetalle) ?></strong></p>
    <?php if (empty($detalle)): ?>
        <p style="margin:0; color:#64748b;">No hay movimientos para esta persona.</p>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table" style="width:100%; min-width:900px;">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Programa</th>
                        <th>Método</th>
                        <th>Quién recibió</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Referencia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalle as $mov): ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($mov['Fecha_Registro'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($mov['Programa'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($mov['Metodo_Pago'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($mov['Recibido_Por'] ?? '')) !== '' ? htmlspecialchars((string)($mov['Recibido_Por'] ?? '')) : '-' ?></td>
                            <td><?= htmlspecialchars((string)($mov['Tipo_Pago'] ?? '')) ?></td>
                            <td>$<?= number_format((float)($mov['Valor_Pago'] ?? 0), 0, ',', '.') ?></td>
                            <td style="font-family:monospace;"><?= htmlspecialchars((string)($mov['Referencia_Pago'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php include VIEWS . '/layout/footer.php'; ?>
