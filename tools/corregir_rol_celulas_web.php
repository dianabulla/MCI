<?php
/**
 * 1) Quita Células de user_roles si ya hay otro rol (discípulo, líder, etc.).
 * 2) Quien dirige célula y sigue con Células en la ficha → Líder de célula.
 * 3) El resto con rol Células en la ficha → Discípulo.
 *
 * Uso:
 *   https://TU-DOMINIO/tools/corregir_rol_celulas_web.php
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$config = $root . '/app/Config/config.php';
if (is_file($config)) {
    require_once $config;
}
require_once $root . '/conexion.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo 'No hay conexión a la base de datos.';
    exit;
}

function celulas_web_norm(string $nombre): string {
    $n = mb_strtolower(trim($nombre), 'UTF-8');
    return strtr($n, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
    ]);
}

function celulas_web_es_admin_celulas(string $nombre): bool {
    $n = celulas_web_norm($nombre);
    return $n === 'celulas' || $n === 'celula';
}

function celulas_web_es_lider_celula(string $nombre): bool {
    $n = celulas_web_norm($nombre);
    return strpos($n, 'lider') !== false && strpos($n, 'celula') !== false
        && strpos($n, '12') === false && strpos($n, '144') === false;
}

function celulas_web_es_discipulo(string $nombre): bool {
    $n = celulas_web_norm($nombre);
    return strpos($n, 'discipul') !== false || strpos($n, 'disipul') !== false;
}

$idCelulas = 0;
$nombreCelulas = '';
$idLiderCelula = 0;
$nombreLiderCelula = '';
$idDiscipulo = 0;
$nombreDiscipulo = '';

$roles = $pdo->query('SELECT Id_Rol, Nombre_Rol FROM rol ORDER BY Id_Rol ASC')->fetchAll(PDO::FETCH_ASSOC);
foreach ($roles as $rol) {
    $id = (int)($rol['Id_Rol'] ?? 0);
    $nombre = trim((string)($rol['Nombre_Rol'] ?? ''));
    if ($id <= 0) {
        continue;
    }
    if ($idLiderCelula <= 0 && celulas_web_es_lider_celula($nombre)) {
        $idLiderCelula = $id;
        $nombreLiderCelula = $nombre;
    }
    if ($idDiscipulo <= 0 && celulas_web_es_discipulo($nombre)) {
        $idDiscipulo = $id;
        $nombreDiscipulo = $nombre;
    }
    if ($idCelulas <= 0 && celulas_web_es_admin_celulas($nombre)) {
        $idCelulas = $id;
        $nombreCelulas = $nombre;
    }
}

$error = '';
$ok = '';
$preview = [];

$tieneUserRoles = (int)$pdo->query(
    "SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'user_roles'"
)->fetchColumn() > 0;

if ($idCelulas <= 0) {
    $error = 'No se encontró el rol administrativo Células.';
} elseif ($idDiscipulo <= 0) {
    $error = 'No existe el rol Discípulo. Créalo en Roles y recarga.';
} else {
    $sqlPreview = "SELECT
            p.Id_Persona,
            p.Nombre,
            p.Apellido,
            COALESCE(rf.Nombre_Rol, '') AS Rol_Ficha,
            CASE WHEN c.Id_Lider IS NULL THEN 0 ELSE 1 END AS Es_Lider_Celula
        FROM persona p
        LEFT JOIN rol rf ON rf.Id_Rol = p.Id_Rol
        LEFT JOIN (SELECT DISTINCT Id_Lider FROM celula WHERE Id_Lider IS NOT NULL AND Id_Lider > 0) c
            ON c.Id_Lider = p.Id_Persona
        WHERE p.Id_Rol = ?
        ORDER BY p.Apellido, p.Nombre
        LIMIT 500";
    $stmtPrev = $pdo->prepare($sqlPreview);
    $stmtPrev->execute([$idCelulas]);
    $preview = (array)$stmtPrev->fetchAll(PDO::FETCH_ASSOC);

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($_POST['aplicar'])) {
        $pdo->beginTransaction();
        try {
            $quitadosUserRoles = 0;
            $fichasLider = 0;
            $fichasDiscipulo = 0;

            if ($tieneUserRoles) {
                $delDobles = $pdo->prepare(
                    'DELETE FROM user_roles
                     WHERE Id_Rol = ?
                       AND Id_Persona IN (
                           SELECT Id_Persona FROM (
                               SELECT Id_Persona
                               FROM user_roles
                               GROUP BY Id_Persona
                               HAVING COUNT(*) > 1
                           ) t
                       )'
                );
                $delDobles->execute([$idCelulas]);
                $quitadosUserRoles += (int)$delDobles->rowCount();

                $delSiFichaMinisterial = $pdo->prepare(
                    "DELETE urc FROM user_roles urc
                     INNER JOIN persona p ON p.Id_Persona = urc.Id_Persona
                     WHERE urc.Id_Rol = ?
                       AND p.Id_Rol > 0
                       AND p.Id_Rol <> ?"
                );
                $delSiFichaMinisterial->execute([$idCelulas, $idCelulas]);
                $quitadosUserRoles += (int)$delSiFichaMinisterial->rowCount();
            }

            if ($idLiderCelula > 0) {
                $updLider = $pdo->prepare(
                    "UPDATE persona p
                     INNER JOIN celula c ON c.Id_Lider = p.Id_Persona
                     SET p.Id_Rol = ?
                     WHERE p.Id_Rol = ?"
                );
                $updLider->execute([$idLiderCelula, $idCelulas]);
                $fichasLider = (int)$updLider->rowCount();
            }

            $updDisc = $pdo->prepare(
                'UPDATE persona
                 SET Id_Rol = ?
                 WHERE Id_Rol = ?'
            );
            $updDisc->execute([$idDiscipulo, $idCelulas]);
            $fichasDiscipulo = (int)$updDisc->rowCount();

            if ($tieneUserRoles) {
                $pdo->prepare(
                    'DELETE FROM user_roles
                     WHERE Id_Rol = ?
                       AND Id_Persona IN (
                           SELECT Id_Persona FROM (
                               SELECT ur.Id_Persona
                               FROM user_roles ur
                               INNER JOIN user_roles ur2
                                 ON ur2.Id_Persona = ur.Id_Persona AND ur2.Id_Rol = ?
                               WHERE ur.Id_Rol = ?
                           ) t
                       )'
                )->execute([$idCelulas, $idDiscipulo, $idCelulas]);

                $pdo->prepare(
                    'UPDATE user_roles SET Id_Rol = ? WHERE Id_Rol = ?'
                )->execute([$idDiscipulo, $idCelulas]);
            }

            $pdo->commit();
            $ok = 'Listo. Fichas a discípulo: ' . $fichasDiscipulo
                . '. Fichas a líder de célula (sí dirigen célula): ' . $fichasLider
                . '. Células quitado de roles dobles: ' . $quitadosUserRoles . '.';

            $stmtPrev->execute([$idCelulas]);
            $preview = (array)$stmtPrev->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'No se pudo aplicar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Células → Discípulo</title>
    <style>
        body { font-family: Segoe UI, sans-serif; background: #f1f5f9; margin: 0; padding: 24px; color: #0f172a; }
        .box { max-width: 960px; margin: 0 auto; background: #fff; border-radius: 10px; padding: 28px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        h1 { font-size: 1.3rem; margin: 0 0 12px; }
        .ok { background: #dcfce7; color: #14532d; padding: 12px 14px; border-radius: 8px; margin-bottom: 16px; }
        .err { background: #fee2e2; color: #991b1b; padding: 12px 14px; border-radius: 8px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; margin: 16px 0; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; }
        button { background: #1d4ed8; color: #fff; border: 0; padding: 12px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; }
        small { color: #64748b; }
    </style>
</head>
<body>
<div class="box">
    <h1>Quien tiene Células pasa a Discípulo</h1>
    <p>Las fichas que aún tienen rol <strong>Células</strong> y <strong>no</strong> dirigen una célula se cambian a <strong><?= htmlspecialchars($nombreDiscipulo !== '' ? $nombreDiscipulo : 'Discípulo', ENT_QUOTES, 'UTF-8') ?></strong>.</p>
    <p>Si alguien dirige una célula, esa ficha pasa a líder de célula (no a discípulo).</p>
    <p><small>Células: <?= $idCelulas > 0 ? '#' . (int)$idCelulas . ' ' . htmlspecialchars($nombreCelulas, ENT_QUOTES, 'UTF-8') : '—' ?>
        · Discípulo: <?= $idDiscipulo > 0 ? '#' . (int)$idDiscipulo . ' ' . htmlspecialchars($nombreDiscipulo, ENT_QUOTES, 'UTF-8') : '—' ?>
        · Líder de célula: <?= $idLiderCelula > 0 ? htmlspecialchars($nombreLiderCelula, ENT_QUOTES, 'UTF-8') : '—' ?></small></p>

    <?php if ($ok !== ''): ?><div class="ok"><?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <?php if ($error === ''): ?>
        <p>Fichas con rol Células ahora: <strong><?= count($preview) ?></strong><?= count($preview) >= 500 ? ' (se muestran 500)' : '' ?></p>
        <?php if ($preview !== []): ?>
        <table>
            <thead>
                <tr>
                    <th>Persona</th>
                    <th>Rol actual</th>
                    <th>Pasará a</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($preview as $fila): ?>
                <tr>
                    <td><?= htmlspecialchars(trim((string)$fila['Nombre'] . ' ' . (string)$fila['Apellido']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$fila['Rol_Ficha'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= !empty($fila['Es_Lider_Celula'])
                        ? htmlspecialchars($nombreLiderCelula, ENT_QUOTES, 'UTF-8')
                        : htmlspecialchars($nombreDiscipulo, ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <form method="post" onsubmit="return confirm('Las fichas con Células que no dirigen célula pasarán a discípulo. ¿Continuar?');">
            <button type="submit" name="aplicar" value="1">Aplicar corrección masiva</button>
        </form>
        <?php else: ?>
            <p><strong>No queda ninguna ficha con rol Células.</strong></p>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
