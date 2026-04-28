<?php
/**
 * Herramienta: Reasignar programa de inscripción a personas específicas.
 * Personas: ANGELA PULIDO (tel: 3208643647) y EDGAR CUELLAR (tel: 3224453348)
 * Destino: capacitacion_destino_nivel_1
 *
 * USO: Ejecutar desde navegador. Pasar ?confirmar=1 para aplicar los cambios.
 */

require_once __DIR__ . '/../conexion.php';

$confirmar  = isset($_GET['confirmar']) && $_GET['confirmar'] === '1';
$programaDestino = 'capacitacion_destino_nivel_1';

// Personas a corregir: identificadas por teléfono y nombre (búsqueda flexible)
$personas = [
    ['nombre_buscar' => 'ANGELA PULIDO',  'telefono' => '3208643647'],
    ['nombre_buscar' => 'EDGAR CUELLAR',  'telefono' => '3224453348'],
];

$resultados = [];

foreach ($personas as $p) {
    $telNorm = preg_replace('/\D/', '', $p['telefono']);

    // Buscar en escuela_formacion_inscripcion por teléfono o nombre
    $stmt = $pdo->prepare(
        "SELECT Id_Inscripcion, Id_Persona, Nombre, Telefono, Programa, Fecha_Registro
         FROM escuela_formacion_inscripcion
         WHERE REPLACE(REPLACE(Telefono,' ',''),'-','') LIKE ?
            OR UPPER(TRIM(Nombre)) LIKE ?
         ORDER BY Fecha_Registro DESC"
    );
    $stmt->execute(['%' . $telNorm . '%', '%' . strtoupper(trim($p['nombre_buscar'])) . '%']);
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $resultados[] = [
        'buscado'  => $p['nombre_buscar'] . ' / ' . $p['telefono'],
        'filas'    => $filas,
    ];
}

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reasignar programa – Escuelas de Formación</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }
        th { background: #2563eb; color: #fff; }
        tr:nth-child(even) { background: #f3f4f6; }
        .badge-actual  { background: #f59e0b; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: .85em; }
        .badge-destino { background: #16a34a; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: .85em; }
        .btn  { display:inline-block; padding:10px 22px; background:#dc2626; color:#fff; border-radius:6px; text-decoration:none; font-weight:bold; margin-top:14px; }
        .ok   { color: #16a34a; font-weight: bold; }
        .warn { color: #f59e0b; font-weight: bold; }
        .err  { color: #dc2626; font-weight: bold; }
        h2 { margin-top: 30px; }
    </style>
</head>
<body>
<h1>Reasignar programa de Escuelas de Formación</h1>
<p>Programa destino: <strong><?= htmlspecialchars($programaDestino) ?></strong></p>

<?php if ($confirmar): ?>
    <h2>⚙️ Ejecutando cambios...</h2>
    <?php foreach ($resultados as $r): ?>
        <h3>Persona: <?= htmlspecialchars($r['buscado']) ?></h3>
        <?php if (empty($r['filas'])): ?>
            <p class="warn">⚠️ No se encontraron inscripciones.</p>
        <?php else: ?>
            <?php foreach ($r['filas'] as $fila):
                $programaActual = (string)($fila['Programa'] ?? '');
                if ($programaActual === $programaDestino): ?>
                    <p class="ok">✅ Id_Inscripcion <?= (int)$fila['Id_Inscripcion'] ?> ya está en el programa correcto (<?= htmlspecialchars($programaActual) ?>). Sin cambios.</p>
                <?php else:
                    try {
                        $upd = $pdo->prepare("UPDATE escuela_formacion_inscripcion SET Programa = ? WHERE Id_Inscripcion = ?");
                        $upd->execute([$programaDestino, (int)$fila['Id_Inscripcion']]);
                        echo '<p class="ok">✅ Id_Inscripcion ' . (int)$fila['Id_Inscripcion'] . ' actualizado: <strong>' . htmlspecialchars($programaActual) . '</strong> → <strong>' . htmlspecialchars($programaDestino) . '</strong></p>';
                    } catch (Exception $e) {
                        echo '<p class="err">❌ Error al actualizar Id_Inscripcion ' . (int)$fila['Id_Inscripcion'] . ': ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                endif;
            endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>
    <hr>
    <p class="ok">Proceso finalizado. Puedes cerrar esta ventana o <a href="?">ver el resumen previo</a>.</p>

<?php else: ?>
    <h2>Vista previa – Inscripciones encontradas</h2>
    <p>Revisa que los registros de abajo sean los correctos. Si lo son, haz clic en <strong>Confirmar y aplicar cambios</strong>.</p>

    <?php foreach ($resultados as $r): ?>
        <h3>Persona buscada: <?= htmlspecialchars($r['buscado']) ?></h3>
        <?php if (empty($r['filas'])): ?>
            <p class="warn">⚠️ No se encontraron inscripciones para esta persona.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Id_Inscripcion</th>
                        <th>Id_Persona</th>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Programa actual</th>
                        <th>Programa destino</th>
                        <th>Fecha registro</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($r['filas'] as $fila): ?>
                        <?php $yaOk = ($fila['Programa'] ?? '') === $programaDestino; ?>
                        <tr>
                            <td><?= (int)$fila['Id_Inscripcion'] ?></td>
                            <td><?= $fila['Id_Persona'] !== null ? (int)$fila['Id_Persona'] : '<em>sin persona</em>' ?></td>
                            <td><?= htmlspecialchars((string)$fila['Nombre']) ?></td>
                            <td><?= htmlspecialchars((string)($fila['Telefono'] ?? '')) ?></td>
                            <td><span class="badge-actual"><?= htmlspecialchars((string)($fila['Programa'] ?? '')) ?></span></td>
                            <td><span class="badge-destino"><?= htmlspecialchars($programaDestino) ?></span></td>
                            <td><?= htmlspecialchars((string)($fila['Fecha_Registro'] ?? '')) ?></td>
                            <td><?= $yaOk ? '<span class="ok">Ya correcto</span>' : '<span class="warn">Será actualizado</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endforeach; ?>

    <a class="btn" href="?confirmar=1">✔ Confirmar y aplicar cambios</a>
<?php endif; ?>
</body>
</html>
