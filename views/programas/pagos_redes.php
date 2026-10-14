<?php include VIEWS . '/layout/header.php'; ?>
<?php
require_once APP . '/Helpers/ProgramasNavegacion.php';
ProgramasNavegacion::incluirPartial([
    'linea' => 'universidad_vida',
    'seccion' => 'pagos',
    'forzar' => true,
]);

$categoriasPago = is_array($categorias_pago ?? null) ? $categorias_pago : [];
$urlsRed = is_array($urls_red ?? null) ? $urls_red : [];
$filasCategoria = [
    'encuentro' => 'Encuentros',
    'libro' => 'Libros',
];
$etiquetasRed = [
    'hombres' => 'Hombres',
    'mujeres' => 'Mujeres',
    'jovenes' => 'Jóvenes',
    'teens' => 'Teens',
    'otros' => 'Sin red',
];
$formatoPesos = static function ($valor): string {
    return '$' . number_format((float)$valor, 0, ',', '.');
};
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:8px;">
    <div>
        <h2 style="margin:0;">Pagos Universidad de la Vida</h2>
        <small style="color:#637087;">Un clic en Encuentros o Libros muestra hombres, mujeres, jóvenes y teens.</small>
    </div>
</div>
<?php
$filtro_semestre_form = true;
include VIEWS . '/programas/_filtro_semestre.php';
?>

<div class="uv-pagos-cat">
    <div class="uv-pagos-tabla-wrap">
        <table class="data-table uv-pagos-tabla">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Personas</th>
                    <th>Valor</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filasCategoria as $claveCat => $tituloCat):
                    $filaCat = is_array($categoriasPago[$claveCat] ?? null) ? $categoriasPago[$claveCat] : ['personas' => 0, 'valor' => 0, 'redes' => []];
                    $redesCat = is_array($filaCat['redes'] ?? null) ? $filaCat['redes'] : [];
                ?>
                <tr class="uv-pagos-fila js-uv-pagos-cat" data-categoria="<?= htmlspecialchars($claveCat, ENT_QUOTES, 'UTF-8') ?>" role="button" tabindex="0" aria-expanded="false">
                    <td>
                        <span class="uv-pagos-flecha" aria-hidden="true">▸</span>
                        <strong><?= htmlspecialchars($tituloCat, ENT_QUOTES, 'UTF-8') ?></strong>
                    </td>
                    <td><?= (int)($filaCat['personas'] ?? 0) ?></td>
                    <td><?= htmlspecialchars($formatoPesos($filaCat['valor'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="uv-pagos-accion">Ver redes</td>
                </tr>
                <?php foreach ($etiquetasRed as $claveRed => $tituloRed):
                    $itemRed = is_array($redesCat[$claveRed] ?? null) ? $redesCat[$claveRed] : ['personas' => 0, 'valor' => 0];
                    if ($claveRed === 'otros' && (int)($itemRed['personas'] ?? 0) === 0) {
                        continue;
                    }
                    $urlRed = trim((string)($urlsRed[$claveRed] ?? ''));
                ?>
                <tr class="uv-pagos-red" data-parent="<?= htmlspecialchars($claveCat, ENT_QUOTES, 'UTF-8') ?>" hidden>
                    <td><?= htmlspecialchars($tituloRed, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)($itemRed['personas'] ?? 0) ?></td>
                    <td><?= htmlspecialchars($formatoPesos($itemRed['valor'] ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="uv-pagos-accion">
                        <?php if ($urlRed !== ''): ?>
                            <a href="<?= htmlspecialchars($urlRed, ENT_QUOTES, 'UTF-8') ?>">Ver listado</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="uv-pagos-nota">Quien pagó las dos cosas aparece en las dos filas. Un joven no se cuenta también como hombre.</p>
</div>

<style>
.uv-pagos-cat { margin-top: 16px; }
.uv-pagos-tabla-wrap {
    overflow: auto;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
}
.uv-pagos-tabla { width: 100%; margin: 0; }
.uv-pagos-fila { cursor: pointer; }
.uv-pagos-fila:hover { background: #f8fbff; }
.uv-pagos-fila.is-open { background: #f3f7fd; }
.uv-pagos-fila.is-open .uv-pagos-flecha { transform: rotate(90deg); }
.uv-pagos-flecha {
    display: inline-block;
    width: 14px;
    color: #64748b;
    transition: transform .15s ease;
}
.uv-pagos-red td { background: #f8fafc; color: #334155; }
.uv-pagos-red td:first-child { padding-left: 34px; }
.uv-pagos-accion { width: 120px; text-align: right; color: #64748b; font-size: 13px; white-space: nowrap; }
.uv-pagos-accion a { font-weight: 600; }
.uv-pagos-nota { margin: 10px 0 0; font-size: 0.86rem; color: #64748b; }
</style>
<script>
(function () {
    const filas = document.querySelectorAll('.js-uv-pagos-cat');
    const abrir = function (fila, mostrar) {
        const clave = fila.getAttribute('data-categoria') || '';
        fila.classList.toggle('is-open', mostrar);
        fila.setAttribute('aria-expanded', mostrar ? 'true' : 'false');
        const accion = fila.querySelector('.uv-pagos-accion');
        if (accion) accion.textContent = mostrar ? 'Ocultar' : 'Ver redes';
        document.querySelectorAll('.uv-pagos-red[data-parent="' + clave + '"]').forEach(function (hijo) {
            hijo.hidden = !mostrar;
        });
    };
    filas.forEach(function (fila) {
        const alternar = function () {
            const abierto = fila.classList.contains('is-open');
            filas.forEach(function (otra) { abrir(otra, false); });
            if (!abierto) abrir(fila, true);
        };
        fila.addEventListener('click', alternar);
        fila.addEventListener('keydown', function (evento) {
            if (evento.key === 'Enter' || evento.key === ' ') {
                evento.preventDefault();
                alternar();
            }
        });
    });
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
