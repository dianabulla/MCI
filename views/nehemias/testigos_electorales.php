<?php include VIEWS . '/layout/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center module-header-card">
        <h2 class="page-title">
            <i class="bi bi-person-badge-fill"></i> Nehemias - Testigos Electorales
        </h2>
        <div class="d-flex gap-2">
            <a href="?url=nehemias/testigos-electorales/formulario" class="btn btn-info" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-link-45deg"></i> Abrir formulario público
            </a>
            <a href="?url=nehemias/lista" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Nehemias
            </a>
        </div>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= htmlspecialchars($tipo === 'error' ? 'danger' : ($tipo === 'success' ? 'success' : 'info')) ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php
        $resumenTipos = $resumenTipos ?? ['total' => 0, 'CAMARA' => 0, 'SENADO' => 0];
        $tipoFiltro = strtoupper(trim((string)($tipoFiltro ?? '')));
    ?>

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div class="d-flex flex-wrap gap-2">
                <a href="?url=nehemias/testigos-electorales" class="btn <?= $tipoFiltro === '' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    Todos votos (<?= (int)($resumenTipos['total'] ?? 0) ?>)
                </a>
                <a href="?url=nehemias/testigos-electorales&tipo_votacion=CAMARA" class="btn <?= $tipoFiltro === 'CAMARA' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    Cámara votos (<?= (int)($resumenTipos['CAMARA'] ?? 0) ?>)
                </a>
                <a href="?url=nehemias/testigos-electorales&tipo_votacion=SENADO" class="btn <?= $tipoFiltro === 'SENADO' ? 'btn-primary' : 'btn-outline-primary' ?>">
                    Senado votos (<?= (int)($resumenTipos['SENADO'] ?? 0) ?>)
                </a>
            </div>
            <div class="small text-muted">
                Mostrando: <strong><?= $tipoFiltro !== '' ? htmlspecialchars($tipoFiltro) : 'TODOS' ?></strong>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive nehemias-table-wrap">
                <table class="table table-hover table-no-card nehemias-table nehemias-table-secondary">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Testigo</th>
                            <th>Puesto de votación</th>
                            <th>Mesa de votación</th>
                            <th>Observaciones</th>
                            <th>Votos Cámara</th>
                            <th>Foto Cámara</th>
                            <th>Votos Senado</th>
                            <th>Foto Senado</th>
                            <th>Fecha registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($registros)): ?>
                            <?php foreach ($registros as $registro): ?>
                                <tr>
                                    <td data-label="ID">
                                        <?php
                                            $idCamara = isset($registro['Id_Camara']) ? (int)$registro['Id_Camara'] : 0;
                                            $idSenado = isset($registro['Id_Senado']) ? (int)$registro['Id_Senado'] : 0;
                                        ?>
                                        <?php if ($idCamara > 0 && $idSenado > 0): ?>
                                            <?= $idCamara . '/' . $idSenado ?>
                                        <?php elseif ($idCamara > 0): ?>
                                            <?= $idCamara ?>
                                        <?php elseif ($idSenado > 0): ?>
                                            <?= $idSenado ?>
                                        <?php else: ?>
                                            0
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Testigo"><?= htmlspecialchars((string)($registro['Testigo_Nombre'] ?? '')) ?></td>
                                    <td data-label="Puesto de votación"><?= htmlspecialchars((string)($registro['Puesto_Votacion'] ?? '')) ?></td>
                                    <td data-label="Mesa de votación"><?= htmlspecialchars((string)($registro['Mesa_Votacion'] ?? '')) ?></td>
                                    <td data-label="Observaciones"><?= htmlspecialchars((string)($registro['Observaciones'] ?? '')) ?></td>
                                    <td data-label="Votos Cámara"><?= $registro['Votos_Camara'] !== null ? (int)$registro['Votos_Camara'] : '-' ?></td>
                                    <td data-label="Foto Cámara">
                                        <?php if (!empty($registro['Foto_Camara'])): ?>
                                            <a href="<?= rtrim(PUBLIC_URL, '/') . '/uploads/testigos_electorales/' . rawurlencode((string)$registro['Foto_Camara']) ?>" target="_blank" rel="noopener noreferrer">Ver foto</a>
                                        <?php else: ?>
                                            <span class="text-muted">Sin foto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Votos Senado"><?= $registro['Votos_Senado'] !== null ? (int)$registro['Votos_Senado'] : '-' ?></td>
                                    <td data-label="Foto Senado">
                                        <?php if (!empty($registro['Foto_Senado'])): ?>
                                            <a href="<?= rtrim(PUBLIC_URL, '/') . '/uploads/testigos_electorales/' . rawurlencode((string)$registro['Foto_Senado']) ?>" target="_blank" rel="noopener noreferrer">Ver foto</a>
                                        <?php else: ?>
                                            <span class="text-muted">Sin foto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Fecha registro"><?= htmlspecialchars((string)($registro['Fecha_Registro'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center">No hay registros de testigos electorales.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
