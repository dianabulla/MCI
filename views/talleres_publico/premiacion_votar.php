<?php
$anio = (int)($anio ?? (int)date('Y'));
$categorias = is_array($categorias ?? null) ? $categorias : [];
$errores = is_array($errores ?? null) ? $errores : [];
$valores = is_array($valores ?? null) ? $valores : [];
$yaVoto = !empty($ya_voto);
$gracias = !empty($gracias);
$nombre = (string)($valores['nombre'] ?? '');
$telefono = (string)($valores['telefono'] ?? '');
$votos = is_array($valores['voto'] ?? null) ? $valores['voto'] : [];

$h = static function ($valor): string {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Votación Premios MCI <?= (int)$anio ?></title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", sans-serif; background: #f4f7fb; color: #0f172a; }
        .voto-wrap { max-width: 720px; margin: 0 auto; padding: 20px 16px 48px; }
        .voto-hero { background: #1e4a89; color: #fff; border-radius: 18px; padding: 22px 20px; margin-bottom: 16px; }
        .voto-hero h1 { margin: 0 0 6px; font-size: 1.45rem; }
        .voto-hero p { margin: 0; color: #dbe7ff; }
        .voto-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px; margin-bottom: 14px; }
        .voto-card h2 { margin: 0 0 4px; font-size: 1.15rem; }
        .voto-card .voto-nota { margin: 0 0 12px; color: #64748b; font-size: 0.92rem; }
        label.voto-campo { display: block; font-weight: 700; margin-bottom: 6px; }
        input[type="text"], input[type="tel"] { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px; font-size: 1rem; }
        .voto-premio { margin: 14px 0; }
        .voto-premio h3 { margin: 0 0 8px; font-size: 1rem; }
        .voto-premio small { display: block; color: #64748b; font-weight: 500; }
        .voto-lista { max-height: 280px; overflow: auto; border: 1px solid #e2e8f0; border-radius: 12px; padding: 6px; }
        .voto-grupo { margin: 8px 8px 4px; font-size: 0.75rem; letter-spacing: 0.04em; text-transform: uppercase; color: #92400e; font-weight: 800; }
        .voto-opcion { display: flex; gap: 10px; align-items: flex-start; padding: 8px; border-radius: 8px; }
        .voto-opcion:hover { background: #f8fafc; }
        .voto-opcion input { margin-top: 3px; }
        .voto-libre { position: relative; }
        .voto-libre input { text-transform: uppercase; }
        .voto-sugerencias { position: absolute; z-index: 5; left: 0; right: 0; top: 100%; background: #fff; border: 1px solid #cbd5e1; border-radius: 10px; margin-top: 4px; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08); max-height: 220px; overflow: auto; }
        .voto-sugerencias button { display: block; width: 100%; text-align: left; background: #fff; border: 0; border-bottom: 1px solid #f1f5f9; padding: 10px 12px; font-size: 0.95rem; }
        .voto-sugerencias button:hover { background: #fffbeb; }
        .voto-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 12px; padding: 12px; margin-bottom: 12px; }
        .voto-ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; border-radius: 16px; padding: 22px; }
        .voto-enviar { width: 100%; border: 0; border-radius: 12px; background: #d97706; color: #fff; font-weight: 800; font-size: 1rem; padding: 14px; }
        .voto-enviar:hover { background: #b45309; }
    </style>
</head>
<body>
<div class="voto-wrap">
    <div class="voto-hero">
        <h1>Premios MCI <?= (int)$anio ?></h1>
        <p>Elige una persona en cada premio. El celular solo puede votar una vez. Líderes de célula no se votan aquí.</p>
    </div>

    <?php if ($gracias): ?>
        <div class="voto-ok">
            <h2>Voto registrado</h2>
            <p>Gracias. Este celular ya quedó anotado y no puede votar otra vez.</p>
        </div>
    <?php elseif ($yaVoto): ?>
        <div class="voto-error">
            <strong>Este celular ya votó.</strong>
            <p>Cada persona vota una sola vez. Si el número es correcto, el voto anterior sigue vigente.</p>
        </div>
    <?php else: ?>
        <?php foreach ($errores as $error): ?>
            <div class="voto-error"><?= $h($error) ?></div>
        <?php endforeach; ?>

        <form method="post" action="<?= $h(public_app_url('talleres_publico/premiacion/guardar')) ?>">
            <section class="voto-card">
                <h2>Tus datos</h2>
                <p class="voto-nota">El celular evita que la misma persona vote dos veces. No cambia ninguna ficha de la iglesia.</p>
                <div class="voto-premio">
                    <label class="voto-campo" for="voto-nombre">Tu nombre</label>
                    <input id="voto-nombre" name="nombre" type="text" maxlength="120" required value="<?= $h($nombre) ?>" autocomplete="name">
                </div>
                <div class="voto-premio">
                    <label class="voto-campo" for="voto-telefono">Celular</label>
                    <input id="voto-telefono" name="telefono" type="tel" inputmode="numeric" maxlength="16" required placeholder="3001234567" value="<?= $h($telefono) ?>" autocomplete="tel">
                </div>
            </section>

            <?php foreach ($categorias as $categoria): ?>
                <?php
                $premios = is_array($categoria['premios'] ?? null) ? $categoria['premios'] : [];
                $grupos = is_array($categoria['nominados'] ?? null) ? $categoria['nominados'] : [];
                ?>
                <section class="voto-card">
                    <h2><?= $h($categoria['titulo'] ?? '') ?></h2>
                    <?php if (!empty($categoria['nota'])): ?>
                        <p class="voto-nota"><?= $h($categoria['nota']) ?></p>
                    <?php endif; ?>
                    <?php foreach ($premios as $premio): ?>
                        <?php $clave = (string)($premio['clave'] ?? ''); ?>
                        <div class="voto-premio">
                            <h3>
                                <?= $h($premio['titulo'] ?? '') ?>
                                <?php if (!empty($premio['detalle'])): ?>
                                    <small><?= $h($premio['detalle']) ?></small>
                                <?php endif; ?>
                            </h3>
                            <?php if (($categoria['origen'] ?? '') === 'libre'): ?>
                                <div class="voto-libre">
                                    <input
                                        id="voto-<?= $h($clave) ?>"
                                        name="voto[<?= $h($clave) ?>]"
                                        type="text"
                                        maxlength="120"
                                        required
                                        autocomplete="off"
                                        placeholder="NOMBRE Y APELLIDO"
                                        data-sugerir="1"
                                        value="<?= $h($votos[$clave] ?? '') ?>"
                                    >
                                    <div class="voto-sugerencias" hidden></div>
                                </div>
                            </div>
                                <?php continue; ?>
                            <?php endif; ?>
                            <div class="voto-lista">
                                <?php foreach ($grupos as $grupo): ?>
                                    <?php
                                    $tituloGrupo = trim((string)($grupo['grupo'] ?? ''));
                                    $nombres = is_array($grupo['nombres'] ?? null) ? $grupo['nombres'] : [];
                                    ?>
                                    <?php if ($tituloGrupo !== ''): ?>
                                        <div class="voto-grupo"><?= $h($tituloGrupo) ?></div>
                                    <?php endif; ?>
                                    <?php foreach ($nombres as $persona): ?>
                                        <?php $persona = trim((string)$persona); if ($persona === '') { continue; } ?>
                                        <label class="voto-opcion">
                                            <input type="radio" name="voto[<?= $h($clave) ?>]" value="<?= $h($persona) ?>" required<?= (($votos[$clave] ?? '') === $persona) ? ' checked' : '' ?>>
                                            <span><?= $h($persona) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>

            <button class="voto-enviar" type="submit">Enviar mi voto</button>
        </form>
        <script>
        (function () {
            var url = <?= json_encode(public_app_url('talleres_publico/premiacion/sugerencias'), JSON_UNESCAPED_SLASHES) ?>;
            function mayuscula(input) {
                var inicio = input.selectionStart;
                var fin = input.selectionEnd;
                input.value = input.value.toLocaleUpperCase('es');
                if (inicio !== null) {
                    input.setSelectionRange(inicio, fin);
                }
            }
            document.querySelectorAll('[data-sugerir]').forEach(function (input) {
                var caja = input.parentElement.querySelector('.voto-sugerencias');
                var timer = null;
                input.addEventListener('input', function () {
                    mayuscula(input);
                    clearTimeout(timer);
                    var q = input.value.trim();
                    if (q.length < 2) {
                        caja.hidden = true;
                        caja.innerHTML = '';
                        return;
                    }
                    timer = setTimeout(function () {
                        fetch(url + '&q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } })
                            .then(function (r) { return r.json(); })
                            .then(function (data) {
                                var nombres = (data && data.nombres) || [];
                                caja.innerHTML = '';
                                if (!nombres.length) {
                                    caja.hidden = true;
                                    return;
                                }
                                nombres.forEach(function (nombre) {
                                    var boton = document.createElement('button');
                                    boton.type = 'button';
                                    boton.textContent = nombre;
                                    boton.addEventListener('click', function () {
                                        input.value = nombre;
                                        caja.hidden = true;
                                        caja.innerHTML = '';
                                    });
                                    caja.appendChild(boton);
                                });
                                caja.hidden = false;
                            })
                            .catch(function () { caja.hidden = true; });
                    }, 250);
                });
                document.addEventListener('click', function (evento) {
                    if (!input.parentElement.contains(evento.target)) {
                        caja.hidden = true;
                    }
                });
            });
        })();
        </script>
    <?php endif; ?>
</div>
</body>
</html>
