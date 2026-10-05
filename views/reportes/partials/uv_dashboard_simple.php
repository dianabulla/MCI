<?php
/**
 * Dashboard UV: tabla de inscritos por red del ciclo (semestre) seleccionado.
 *
 * @var array<int, array<string, mixed>> $tablas_uv_avance_semestres
 */
$tablasUvSemestres = is_array($tablas_uv_avance_semestres ?? null) ? $tablas_uv_avance_semestres : [];
if ($tablasUvSemestres === []) {
    $tablaUvAvanceRedes = is_array($tabla_uv_avance_redes ?? null) ? $tabla_uv_avance_redes : [];
    $tablasUvSemestres = [[
        'semestre' => (int)($semestre_uv ?? 0),
        'etiqueta' => (string)($etiqueta_periodo_uv ?? 'Periodo'),
        'fecha_inicio' => (string)($fechaInicioMes ?? ''),
        'fecha_fin' => (string)($fechaFinMes ?? ''),
        'filas' => $tablaUvAvanceRedes,
    ]];
}

$anioRepUv = (int)($anio ?? date('Y'));
$urlDetalleAvanceUv = PUBLIC_URL . '?url=reportes/dashboard-escuelas-uv-detalle-avance';

$etiquetaRedUv = static function ($red) {
    if ($red === 'hombres') {
        return 'Hombres';
    }
    if ($red === 'mujeres') {
        return 'Mujeres';
    }
    if ($red === 'jovenes') {
        return 'Jóvenes';
    }
    return 'Otros';
};

$slugMinisterioUv = static function ($nombre) {
    $s = strtolower(trim(preg_replace('/\s+/u', ' ', (string)$nombre)));
    return $s === '' ? 'sin-ministerio' : $s;
};

$totalesFilasUv = static function (array $filas) {
    $tot = ['inscritos' => 0, 'asistieron' => 0, 'pasaron' => 0];
    foreach ($filas as $filaUvAv) {
        $tot['inscritos'] += (int)($filaUvAv['inscritos'] ?? 0);
        $tot['asistieron'] += (int)($filaUvAv['asistieron'] ?? 0);
        $tot['pasaron'] += (int)($filaUvAv['pasaron'] ?? 0);
    }
    return $tot;
};
?>

<p class="uv-dash-intro uv-dash-intro-compact">
    <strong><?= (int)$anioRepUv ?></strong>
    · Ciclo seleccionado. Pulsa una fila para ver las personas inscritas, a cuántas clases fueron y si culminaron los dos días de Encuentro.
</p>

<?php foreach ($tablasUvSemestres as $bloqueSem):
    $filasSem = is_array($bloqueSem['filas'] ?? null) ? $bloqueSem['filas'] : [];
    $totSem = $totalesFilasUv($filasSem);
    $numSem = (int)($bloqueSem['semestre'] ?? 0);
    $idTabla = 'tablaUvAvanceRedes-s' . $numSem;
    $etiquetaSem = trim((string)($bloqueSem['etiqueta'] ?? ('Semestre ' . $numSem)));
    $iniSem = (string)($bloqueSem['fecha_inicio'] ?? '');
    $finSem = (string)($bloqueSem['fecha_fin'] ?? '');
?>
<div class="dash-card uv-dash-table-card" style="margin-bottom:16px;" data-uv-semestre="<?= $numSem ?>">
    <h4 class="section-title"><?= htmlspecialchars($etiquetaSem) ?></h4>
    <small class="uv-dash-table-note">
        <?= htmlspecialchars($iniSem) ?> a <?= htmlspecialchars($finSem) ?>.
        <strong>Asistieron</strong> = al menos una clase UV.
        <strong>Culminaron encuentro</strong> = día 1 y día 2 (clases 5 y 6).
    </small>
    <div class="rpt-ganados-semana-filtros cap-red-filtros" role="group" aria-label="Filtrar por grupo" style="margin:10px 0 8px;">
        <button type="button" class="rpt-ganados-filtro is-active js-uv-red-filtro" data-red="todos">Todos</button>
        <button type="button" class="rpt-ganados-filtro js-uv-red-filtro" data-red="hombres">Hombres</button>
        <button type="button" class="rpt-ganados-filtro js-uv-red-filtro" data-red="mujeres">Mujeres</button>
        <button type="button" class="rpt-ganados-filtro js-uv-red-filtro" data-red="jovenes">Jóvenes</button>
    </div>
    <div class="table-wrap">
        <table class="leader-table uv-simple-table js-uv-tabla-avance" id="<?= htmlspecialchars($idTabla, ENT_QUOTES, 'UTF-8') ?>">
            <thead>
                <tr>
                    <th>Ministerio</th>
                    <th>Grupo</th>
                    <th class="uv-num">Inscritos</th>
                    <th class="uv-num">Asistieron</th>
                    <th class="uv-num">Culminaron encuentro</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($filasSem)): ?>
                    <?php foreach ($filasSem as $filaUvAv): ?>
                        <?php
                        $minNom = (string)($filaUvAv['ministerio'] ?? 'Sin ministerio');
                        $redNom = (string)($filaUvAv['red'] ?? '');
                        ?>
                        <tr class="js-uv-avance-row uv-avance-row-click"
                            data-cap-red="<?= htmlspecialchars($redNom, ENT_QUOTES, 'UTF-8') ?>"
                            data-min-slug="<?= htmlspecialchars($slugMinisterioUv($minNom), ENT_QUOTES, 'UTF-8') ?>"
                            data-semestre="<?= $numSem ?>">
                            <td><?= htmlspecialchars($minNom) ?></td>
                            <td><?= htmlspecialchars($etiquetaRedUv($redNom)) ?></td>
                            <td class="uv-num js-uv-col-ins"><?= (int)($filaUvAv['inscritos'] ?? 0) ?></td>
                            <td class="uv-num js-uv-col-as"><?= (int)($filaUvAv['asistieron'] ?? 0) ?></td>
                            <td class="uv-num js-uv-col-pa"><?= (int)($filaUvAv['pasaron'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No hay inscripciones de Universidad de la Vida en este semestre.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">TOTAL</th>
                    <th class="uv-num js-uv-tot-ins"><?= (int)$totSem['inscritos'] ?></th>
                    <th class="uv-num js-uv-tot-as"><?= (int)$totSem['asistieron'] ?></th>
                    <th class="uv-num js-uv-tot-pa"><?= (int)$totSem['pasaron'] ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endforeach; ?>

<div id="uvAvanceDetalleModal" class="uv-avance-modal" hidden aria-hidden="true">
    <div class="uv-avance-modal__overlay" data-uv-avance-cerrar="1"></div>
    <div class="uv-avance-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="uvAvanceDetalleTitulo">
        <div class="uv-avance-modal__head">
            <h3 id="uvAvanceDetalleTitulo">Personas inscritas</h3>
            <button type="button" class="uv-avance-modal__cerrar" data-uv-avance-cerrar="1" aria-label="Cerrar">×</button>
        </div>
        <p class="uv-avance-modal__sub" id="uvAvanceDetalleSub"></p>
        <p class="uv-avance-modal__estado" id="uvAvanceDetalleEstado" hidden></p>
        <div class="table-wrap">
            <table class="leader-table">
                <thead>
                    <tr>
                        <th>Persona</th>
                        <th>Cédula</th>
                        <th>Líder</th>
                        <th class="uv-num">Clases</th>
                        <th>Día 1</th>
                        <th>Día 2</th>
                        <th>Culminó encuentro</th>
                    </tr>
                </thead>
                <tbody id="uvAvanceDetalleBody"></tbody>
            </table>
        </div>
    </div>
</div>

<style>
.cap-red-filtros { display:flex; flex-wrap:wrap; gap:8px; }
.cap-red-filtros .rpt-ganados-filtro {
    border: 1px solid #cfdff5;
    background: #fff;
    color: #1f3f74;
    border-radius: 999px;
    padding: 6px 14px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
}
.cap-red-filtros .rpt-ganados-filtro.is-active {
    background: #1f3f74;
    border-color: #1f3f74;
    color: #fff;
}
.uv-avance-row-click { cursor: pointer; }
.uv-avance-row-click:hover { background: #f1f5f9; }
.uv-avance-modal {
    position: fixed;
    inset: 0;
    z-index: 5000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 80px 16px 28px;
    box-sizing: border-box;
}
.uv-avance-modal[hidden] { display: none !important; }
.uv-avance-modal__overlay {
    position: absolute; inset: 0; background: rgba(15, 23, 42, 0.5);
}
.uv-avance-modal__dialog {
    position: relative; z-index: 1;
    width: min(960px, 100%);
    max-height: min(78vh, calc(100vh - 108px));
    overflow: auto;
    background: #fff;
    border-radius: 14px;
    padding: 16px 16px 20px;
    box-shadow: 0 18px 50px rgba(15, 23, 42, 0.25);
    margin: 0;
}
.uv-avance-modal__head {
    display:flex;
    justify-content:space-between;
    gap:12px;
    align-items:flex-start;
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 2;
    padding-bottom: 8px;
}
.uv-avance-modal__head h3 { margin: 0; font-size: 1.05rem; }
.uv-avance-modal__cerrar {
    border: 0; background: #e2e8f0; border-radius: 8px;
    width: 32px; height: 32px; font-size: 20px; cursor: pointer;
}
.uv-avance-modal__sub { margin: 6px 0 12px; color: #64748b; font-size: 0.85rem; }
.uv-avance-si { color: #166534; font-weight: 700; }
.uv-avance-no { color: #94a3b8; }
</style>
<script>
(function () {
    const urlDetalle = <?= json_encode($urlDetalleAvanceUv, JSON_UNESCAPED_UNICODE) ?>;
    const anio = <?= json_encode((string)$anioRepUv) ?>;
    const filtroMinisterio = <?= json_encode((string)($filtro_ministerio ?? ''), JSON_UNESCAPED_UNICODE) ?>;
    const filtroLider = <?= json_encode((string)($filtro_lider ?? ''), JSON_UNESCAPED_UNICODE) ?>;
    const modal = document.getElementById('uvAvanceDetalleModal');
    const titulo = document.getElementById('uvAvanceDetalleTitulo');
    const sub = document.getElementById('uvAvanceDetalleSub');
    const estado = document.getElementById('uvAvanceDetalleEstado');
    const body = document.getElementById('uvAvanceDetalleBody');

    function esc(texto) {
        const d = document.createElement('div');
        d.textContent = texto == null ? '' : String(texto);
        return d.innerHTML;
    }

    document.querySelectorAll('.uv-dash-table-card').forEach(function (card) {
        const botones = card.querySelectorAll('.js-uv-red-filtro');
        const filas = card.querySelectorAll('.js-uv-avance-row');
        const totIns = card.querySelector('.js-uv-tot-ins');
        const totAs = card.querySelector('.js-uv-tot-as');
        const totPa = card.querySelector('.js-uv-tot-pa');
        function aplicarFiltroRed(red) {
            let ins = 0;
            let asis = 0;
            let pasa = 0;
            filas.forEach(function (fila) {
                const redFila = String(fila.getAttribute('data-cap-red') || '');
                const mostrar = red === 'todos' || redFila === red;
                fila.style.display = mostrar ? '' : 'none';
                if (mostrar) {
                    ins += parseInt((fila.querySelector('.js-uv-col-ins') || {}).textContent || '0', 10) || 0;
                    asis += parseInt((fila.querySelector('.js-uv-col-as') || {}).textContent || '0', 10) || 0;
                    pasa += parseInt((fila.querySelector('.js-uv-col-pa') || {}).textContent || '0', 10) || 0;
                }
            });
            if (totIns) totIns.textContent = String(ins);
            if (totAs) totAs.textContent = String(asis);
            if (totPa) totPa.textContent = String(pasa);
        }
        botones.forEach(function (boton) {
            boton.addEventListener('click', function () {
                const red = String(boton.getAttribute('data-red') || 'todos');
                botones.forEach(function (otro) {
                    otro.classList.toggle('is-active', otro === boton);
                });
                aplicarFiltroRed(red);
            });
        });
    });

    function cerrarModal() {
        if (!modal) return;
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
    }

    function marcarSiNo(ok) {
        return ok
            ? '<span class="uv-avance-si">Sí</span>'
            : '<span class="uv-avance-no">No</span>';
    }

    async function abrirDetalle(fila) {
        const ministerio = String(fila.getAttribute('data-min-slug') || '');
        const red = String(fila.getAttribute('data-cap-red') || '');
        const semestre = String(fila.getAttribute('data-semestre') || '');
        if (!ministerio || !red || (semestre !== '1' && semestre !== '2')) {
            return;
        }
        if (titulo) {
            titulo.textContent = (fila.cells[0] ? fila.cells[0].textContent : 'Ministerio')
                + ' · ' + (fila.cells[1] ? fila.cells[1].textContent : red);
        }
        if (sub) sub.textContent = 'Cargando…';
        if (estado) {
            estado.hidden = true;
            estado.textContent = '';
        }
        if (body) body.innerHTML = '';
        if (modal) {
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
        }
        const params = new URLSearchParams({
            anio: anio,
            semestre: semestre,
            ministerio: ministerio,
            red: red,
            filtro_ministerio: filtroMinisterio,
            filtro_lider: filtroLider
        });
        try {
            const res = await fetch(urlDetalle + '&' + params.toString(), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (!res.ok || !data.ok) {
                throw new Error(data.mensaje || 'No se pudo cargar el listado.');
            }
            const periodo = (data.periodo && data.periodo.etiqueta) ? data.periodo.etiqueta : '';
            if (sub) {
                sub.textContent = periodo + ' · ' + (data.totales && data.totales.listado ? data.totales.listado : 0) + ' persona(s)';
            }
            const personas = Array.isArray(data.personas) ? data.personas : [];
            if (body) {
                if (!personas.length) {
                    body.innerHTML = '<tr><td colspan="7">No hay personas inscritas en este ministerio y grupo.</td></tr>';
                    return;
                }
                body.innerHTML = personas.map(function (p) {
                    return '<tr>'
                        + '<td>' + esc(p.nombre || 'Sin nombre') + '</td>'
                        + '<td>' + esc(p.cedula || '') + '</td>'
                        + '<td>' + esc(p.lider || '') + '</td>'
                        + '<td class="uv-num">' + String(parseInt(p.clases, 10) || 0) + ' / 10</td>'
                        + '<td>' + marcarSiNo(!!p.dia1) + '</td>'
                        + '<td>' + marcarSiNo(!!p.dia2) + '</td>'
                        + '<td>' + marcarSiNo(!!p.culmino_encuentro) + '</td>'
                        + '</tr>';
                }).join('');
            }
        } catch (err) {
            if (sub) sub.textContent = '';
            if (estado) {
                estado.hidden = false;
                estado.textContent = err && err.message ? err.message : 'No se pudo cargar el detalle.';
            }
        }
    }

    document.querySelectorAll('.js-uv-avance-row').forEach(function (fila) {
        fila.addEventListener('click', function () {
            abrirDetalle(fila);
        });
    });
    document.querySelectorAll('[data-uv-avance-cerrar="1"]').forEach(function (el) {
        el.addEventListener('click', cerrarModal);
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && modal && !modal.hidden) {
            cerrarModal();
        }
    });
})();
</script>
