<?php include VIEWS . '/layout/header.php'; ?>
<?php
$anio = (int)($anio ?? (int)date('Y'));
$anios = is_array($anios ?? null) ? $anios : [$anio];
$catalogo = is_array($catalogo ?? null) ? $catalogo : [];
$rankings = is_array($rankings ?? null) ? $rankings : [];
$resultados = is_array($resultados ?? null) ? $resultados : [];
$votantes = is_array($votantes ?? null) ? $votantes : [];
$totalVotos = (int)($total_votos ?? 0);
$urlVoto = (string)($url_voto ?? '');
$flashOk = (string)($flash_ok ?? '');
$flashError = (string)($flash_error ?? '');
$errorDatos = (string)($error_datos ?? '');
$celulas = is_array($rankings['celulas'] ?? null) ? $rankings['celulas'] : [];
$pastores = is_array($rankings['pastores'] ?? null) ? $rankings['pastores'] : [];
$revelacion = is_array($rankings['revelacion'] ?? null) ? $rankings['revelacion'] : [];

$h = static function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};

$empate = static function (array $filas, array $campos): bool {
    if (!isset($filas[0], $filas[1])) {
        return false;
    }
    foreach ($campos as $campo) {
        if ((int)($filas[0][$campo] ?? 0) !== (int)($filas[1][$campo] ?? 0)) {
            return false;
        }
    }
    return true;
};
?>
<style>
.premio-head { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 16px; align-items: flex-end; margin-bottom: 18px; }
.premio-head h2 { margin: 0; }
.premio-head p { margin: 6px 0 0; max-width: 40rem; color: #64748b; }
.premio-anio { display: flex; gap: 8px; align-items: center; }
.premio-link { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; justify-content: space-between; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 14px; padding: 12px 14px; margin-bottom: 16px; }
.premio-link p { margin: 4px 0 0; color: #92400e; font-size: 0.85rem; }
.premio-link a { display: inline-block; margin-top: 6px; font-weight: 800; color: #1e4a89; word-break: break-all; }
.premio-cat { background: #fff; border: 1px solid #e2e9f8; border-radius: 16px; padding: 18px; margin-bottom: 16px; }
.premio-cat h3 { margin: 0 0 6px; }
.premio-nota { color: #64748b; margin: 0 0 14px; }
.premio-ganadores { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; margin-bottom: 14px; }
.premio-ganador { background: #f8fafc; border-radius: 12px; padding: 12px; border: 1px solid #e2e8f0; }
.premio-ganador strong { display: block; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.03em; color: #64748b; }
.premio-ganador b { display: block; margin-top: 4px; font-size: 1.05rem; }
.premio-ganador span { display: block; margin-top: 4px; color: #0369a1; font-size: 0.85rem; }
.premio-empate { color: #92400e; font-weight: 700; }
.premio-tabla { width: 100%; border-collapse: collapse; font-size: 0.92rem; }
.premio-tabla th, .premio-tabla td { text-align: left; padding: 7px 8px; border-bottom: 1px solid #eef2f7; vertical-align: top; }
.premio-tabla th { color: #64748b; font-size: 0.75rem; text-transform: uppercase; }
.premio-barra { height: 8px; background: #e2e8f0; border-radius: 99px; overflow: hidden; min-width: 80px; }
.premio-barra i { display: block; height: 100%; background: #d97706; }
.premio-premio { margin-top: 16px; }
.premio-premio h4 { margin: 0 0 8px; }
</style>

<div class="premio-head">
    <div>
        <h2>Premiación <?= (int)$anio ?></h2>
        <p>Líderes de 12 sale del sistema, una sola persona por premio. Las demás categorías se votan por el enlace.</p>
    </div>
    <form method="get" action="<?= $h(PUBLIC_URL) ?>" class="premio-anio">
        <input type="hidden" name="url" value="talleres/premiacion">
        <label for="premio-anio">Año</label>
        <select id="premio-anio" name="anio" class="form-control" onchange="this.form.submit()">
            <?php foreach ($anios as $opcion): ?>
                <option value="<?= (int)$opcion ?>"<?= (int)$opcion === $anio ? ' selected' : '' ?>><?= (int)$opcion ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<?php if ($flashOk !== ''): ?><div class="alert alert-success"><?= $h($flashOk) ?></div><?php endif; ?>
<?php if ($flashError !== ''): ?><div class="alert alert-danger"><?= $h($flashError) ?></div><?php endif; ?>
<?php if ($errorDatos !== ''): ?><div class="alert alert-warning"><?= $h($errorDatos) ?></div><?php endif; ?>

<div class="premio-link">
    <div>
        <strong>Enlace público de votación</strong>
        <p>No pide inicio de sesión y no se puede modificar.</p>
        <a id="premio-url-voto" href="<?= $h($urlVoto) ?>" target="_blank" rel="noopener"><?= $h($urlVoto) ?></a>
    </div>
    <button type="button" class="btn btn-warning" onclick="navigator.clipboard.writeText(document.getElementById('premio-url-voto').textContent)">Copiar</button>
</div>

<section class="premio-cat">
    <h3>Líderes de 12 y Célula</h3>
    <p class="premio-nota">No va en el enlace. El sistema elige un ganador por premio con los datos del año. Células, personas y asistencias no se modifican.</p>
    <?php $diag = is_array($rankings['diagnostico'] ?? null) ? $rankings['diagnostico'] : []; ?>
    <p class="premio-nota">Personas que entraron en <?= (int)$anio ?> y siguen en una célula: <?= (int)($diag['nuevos'] ?? 0) ?>. De esas, <?= (int)($diag['dos_dias'] ?? 0) ?> tienen los dos días de encuentro. Encuentro en lo que va del año: <?= (int)($diag['total_encuentro'] ?? 0) ?>, que es <?= (int)($diag['s1'] ?? 0) ?> del primer semestre + <?= (int)($diag['s2'] ?? 0) ?> del segundo. Bautismos en células: <?= (int)($diag['bautismos'] ?? 0) ?>. Inscripciones a encuentro: <?= (int)($diag['inscripciones'] ?? 0) ?>.</p>
    <?php
    $gCelula = $celulas[0] ?? null;
    $gPastor = null;
    foreach ($pastores as $candidato) {
        if ((int)($candidato['apto'] ?? 0) === 1) {
            $gPastor = $candidato;
            break;
        }
    }
    $gRevelacion = $revelacion[0] ?? null;
    $eCelula = $empate($celulas, ['nuevas', 'un_dia', 'bautismos', 'inscripciones']);
    $pastoresAptos = array_values(array_filter($pastores, static function (array $fila): bool {
        return (int)($fila['apto'] ?? 0) === 1;
    }));
    $ePastor = $empate($pastoresAptos, ['encuentro']);
    $eRevelacion = $empate($revelacion, ['crecimiento', 'asistencias']);
    ?>
    <div class="premio-ganadores">
        <div class="premio-ganador">
            <strong>Célula de mayor crecimiento</strong>
            <span>La célula que más gente nueva ganó. De esos nuevos, cuántos fueron a algún día de encuentro. También cuentan los bautismos y las inscripciones a encuentro de la célula, aunque no sean las mismas personas.</span>
            <?php if ($gCelula): ?>
                <b><?= $h($gCelula['nombre'] ?? '') ?></b>
                <span><?= (int)($gCelula['nuevas'] ?? 0) ?> nuevos · <?= (int)($gCelula['un_dia'] ?? 0) ?> fueron a un día de encuentro · <?= (int)($gCelula['bautismos'] ?? 0) ?> bautismos · <?= (int)($gCelula['inscripciones'] ?? 0) ?> inscripciones a encuentro · líder <?= $h($gCelula['lider'] ?? '') ?><?= $eCelula ? ' · empate' : '' ?></span>
            <?php else: ?>
                <b>No hay personas nuevas en células en <?= (int)$anio ?></b>
            <?php endif; ?>
        </div>
        <div class="premio-ganador">
            <strong>Pastor de corazón</strong>
            <span>El líder que más personas llevó a encuentro. Se suma el primer semestre y el segundo: el total de lo que va del año. No se mira un solo semestre ni la célula.</span>
            <?php if ($gPastor): ?>
                <b><?= $h($gPastor['nombre'] ?? '') ?></b>
                <span><?= (int)($gPastor['encuentro'] ?? 0) ?> en el año · <?= (int)($gPastor['s1'] ?? 0) ?> del primer semestre + <?= (int)($gPastor['s2'] ?? 0) ?> del segundo<?= $ePastor ? ' · empate' : '' ?></span>
            <?php else: ?>
                <b>Nadie tiene personas con asistencia a encuentro en <?= (int)$anio ?></b>
            <?php endif; ?>
        </div>
        <div class="premio-ganador">
            <strong>Líder revelación</strong>
            <span>Líder joven: apenas tiene una célula y llevó personas a encuentro. También se suma el año completo.</span>
            <?php if ($gRevelacion): ?>
                <b><?= $h($gRevelacion['nombre'] ?? '') ?></b>
                <span>Una sola célula · <?= (int)($gRevelacion['crecimiento'] ?? 0) ?> en el año · <?= (int)($gRevelacion['s1'] ?? 0) ?> del primer semestre + <?= (int)($gRevelacion['s2'] ?? 0) ?> del segundo<?= $eRevelacion ? ' · empate' : '' ?></span>
            <?php else: ?>
                <b>Ningún líder de una sola célula llevó gente a encuentro en <?= (int)$anio ?></b>
            <?php endif; ?>
        </div>
    </div>
</section>

<h3 style="margin: 28px 0 8px;">Resultados de la votación</h3>
<p class="premio-nota"><?= (int)$totalVotos ?> <?= $totalVotos === 1 ? 'persona ha votado' : 'personas han votado' ?>. Un celular cuenta una sola vez. Cada categoría va en su propio bloque.</p>

<?php foreach ($catalogo as $categoria): ?>
    <?php if (!in_array((string)($categoria['origen'] ?? ''), ['voto', 'libre'], true)) { continue; } ?>
    <section class="premio-cat">
        <h3><?= $h($categoria['titulo'] ?? '') ?></h3>
        <?php foreach ($categoria['premios'] as $premio): ?>
            <?php
            $clave = (string)($premio['clave'] ?? '');
            $lista = is_array($resultados[$clave] ?? null) ? $resultados[$clave] : [];
            $conVotos = [];
            $max = 0;
            foreach ($lista as $item) {
                $votosItem = (int)($item['votos'] ?? 0);
                if ($votosItem <= 0) {
                    continue;
                }
                $conVotos[] = $item;
                $max = max($max, $votosItem);
            }
            $empatados = 0;
            if ($max > 0) {
                foreach ($conVotos as $item) {
                    if ((int)$item['votos'] === $max) {
                        $empatados++;
                    }
                }
            }
            $primero = ($max > 0 && isset($conVotos[0])) ? (string)$conVotos[0]['nombre'] : '';
            ?>
            <div class="premio-ganador" style="margin-bottom: 12px;">
                <strong><?= $h($premio['titulo'] ?? '') ?></strong>
                <?php if ($primero !== ''): ?>
                    <b><?= $h($primero) ?></b>
                    <span><?= (int)$max ?> <?= $max === 1 ? 'voto' : 'votos' ?><?= $empatados > 1 ? ' · empate' : '' ?></span>
                <?php else: ?>
                    <b>Todavía sin votos</b>
                <?php endif; ?>
            </div>
            <?php if ($conVotos !== []): ?>
                <table class="premio-tabla">
                    <thead><tr><th>Nombre</th><th>Votos</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($conVotos as $item): ?>
                        <?php $ancho = $max > 0 ? (int)round(((int)$item['votos'] / $max) * 100) : 0; ?>
                        <tr>
                            <td><?= $h($item['nombre']) ?></td>
                            <td><?= (int)$item['votos'] ?></td>
                            <td><div class="premio-barra"><i style="width: <?= $ancho ?>%"></i></div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endforeach; ?>
    </section>
<?php endforeach; ?>

<section class="premio-cat">
    <details>
        <summary>Celulares que ya votaron (<?= count($votantes) ?>)</summary>
        <table class="premio-tabla">
            <thead><tr><th>Nombre</th><th>Celular</th><th>Fecha</th></tr></thead>
            <tbody>
            <?php foreach ($votantes as $votante): ?>
                <tr>
                    <td><?= $h($votante['nombre'] ?? '') ?></td>
                    <td><?= $h($votante['telefono'] ?? '') ?></td>
                    <td><?= $h($votante['fecha'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </details>
</section>

<p><a href="<?= $h(PUBLIC_URL) ?>?url=talleres">Volver a talleres</a></p>
<?php include VIEWS . '/layout/footer.php'; ?>
