<?php
$entregasUsuario = (array)($entregas_usuario ?? []);
$claveModuloTarea = trim((string)($clave_modulo_tarea ?? 'capacitacion_destino'));
if ($claveModuloTarea === '') {
    $claveModuloTarea = 'capacitacion_destino';
}
?>
<?php if (!empty($entregasUsuario)): ?>
    <div class="cap-entregas-usuario-wrap">
        <strong class="cap-entregas-usuario-title">Tus entregas y calificaciones</strong>
        <?php foreach ($entregasUsuario as $entrega): ?>
            <?php
                $estadoCalificacion = strtolower(trim((string)($entrega['Estado_Calificacion'] ?? 'pendiente')));
                $estaCalificada = $estadoCalificacion === 'calificada';
                $nombreArchivo = trim((string)($entrega['Nombre_Archivo'] ?? ''));
                $nombreOriginal = trim((string)($entrega['Nombre_Original'] ?? ''));
                if ($nombreOriginal === '') {
                    $nombreOriginal = $nombreArchivo !== '' ? $nombreArchivo : 'Archivo';
                }
                $urlArchivo = $nombreArchivo !== ''
                    ? (rtrim(PUBLIC_URL, '/') . '/uploads/material_hub_tareas/' . rawurlencode($claveModuloTarea) . '/' . rawurlencode($nombreArchivo))
                    : '';
                $notaEntrega = $entrega['Nota'] ?? null;
            ?>
            <div class="cap-entrega-usuario-card">
                <div class="cap-entrega-usuario-grid">
                    <div class="cap-entrega-usuario-cell">
                        <span class="cap-entrega-usuario-label">Archivo</span>
                        <?php if ($urlArchivo !== ''): ?>
                            <a href="<?= htmlspecialchars($urlArchivo, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="cap-entrega-usuario-link"><?= htmlspecialchars($nombreOriginal) ?></a>
                        <?php else: ?>
                            <span class="cap-entrega-usuario-value"><?= htmlspecialchars($nombreOriginal) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="cap-entrega-usuario-cell">
                        <span class="cap-entrega-usuario-label">Fecha de entrega</span>
                        <span class="cap-entrega-usuario-value"><?= htmlspecialchars(trim((string)($entrega['Fecha_Entrega'] ?? '')) !== '' ? (string)($entrega['Fecha_Entrega'] ?? '') : 'Sin fecha') ?></span>
                    </div>
                    <div class="cap-entrega-usuario-cell">
                        <span class="cap-entrega-usuario-label">Tu comentario</span>
                        <span class="cap-entrega-usuario-value"><?= htmlspecialchars(trim((string)($entrega['Comentario'] ?? '')) !== '' ? (string)($entrega['Comentario'] ?? '') : 'Sin comentario') ?></span>
                    </div>
                    <div class="cap-entrega-usuario-cell">
                        <span class="cap-entrega-usuario-label">Calificación</span>
                        <?php if ($estaCalificada): ?>
                            <span class="cap-entrega-usuario-value cap-entrega-usuario-calif-ok">
                                <?= htmlspecialchars('Calificada' . ($notaEntrega !== null && $notaEntrega !== '' ? (' · Nota: ' . (string)$notaEntrega) : '')) ?>
                            </span>
                            <?php if (trim((string)($entrega['Fecha_Calificacion'] ?? '')) !== ''): ?>
                                <span class="cap-entrega-usuario-value cap-entrega-usuario-calif-ok" style="display:block;margin-top:2px;">
                                    Fecha: <?= htmlspecialchars((string)($entrega['Fecha_Calificacion'] ?? '')) ?>
                                </span>
                            <?php endif; ?>
                            <?php if (trim((string)($entrega['Retroalimentacion'] ?? '')) !== ''): ?>
                                <span class="cap-entrega-usuario-value cap-entrega-usuario-calif-ok" style="display:block;margin-top:2px;">
                                    Retroalimentación: <?= htmlspecialchars((string)($entrega['Retroalimentacion'] ?? '')) ?>
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="cap-entrega-usuario-value cap-entrega-usuario-calif-pend">Pendiente de calificar por tu líder</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
