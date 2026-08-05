<?php
$ok = !empty($ok);
$errores = is_array($errores ?? null) ? $errores : [];
$valores = is_array($valores ?? null) ? $valores : [];
$tiposCita = is_array($tipos_cita ?? null) ? $tipos_cita : [];
$tiposDocumento = is_array($tipos_documento ?? null) ? $tipos_documento : [];
$remitidoPor = is_array($remitido_por ?? null) ? $remitido_por : [];
$horariosSabado = is_array($horarios_sabado ?? null) ? $horarios_sabado : [];
$proximosSabados = is_array($proximos_sabados ?? null) ? $proximos_sabados : [];
$puedeAgendarHoy = !isset($puede_agendar_hoy) || !empty($puede_agendar_hoy);
$urlBuscarPersona = (string)($url_buscar_persona ?? '');
$urlDisponibilidad = (string)($url_disponibilidad ?? '');

$v = static function (array $valores, string $clave, string $default = ''): string {
    $x = $valores[$clave] ?? $default;
    return is_array($x) ? $default : (string)$x;
};

$hoy = date('Y-m-d');
$remitidoActual = $v($valores, 'remitido_por', 'ninguno');
$fechaSel = $v($valores, 'fecha_preferida');
if ($fechaSel === '' && $proximosSabados !== []) {
    $fechaSel = (string)($proximosSabados[0]['fecha'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicio Social — Agendar cita | MCI Madrid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --blue: #2857a0;
            --blue-2: #3a6fb8;
            --ink: #1e293b;
            --muted: #64748b;
            --line: #dbe3f0;
            --bg: #eef3fb;
            --card: #ffffff;
            --ok: #0f766e;
            --danger: #b42318;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 500px at 10% -10%, rgba(40, 87, 160, 0.18), transparent 60%),
                radial-gradient(900px 420px at 100% 0%, rgba(15, 118, 110, 0.12), transparent 55%),
                var(--bg);
            padding: 24px 16px 40px;
        }
        .wrap {
            max-width: 720px;
            margin: 0 auto;
        }
        .brand {
            text-align: center;
            margin-bottom: 18px;
        }
        .brand .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(40, 87, 160, 0.1);
            color: var(--blue);
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .brand h1 {
            margin: 0 0 6px;
            font-size: clamp(1.6rem, 3vw, 2rem);
            letter-spacing: -0.02em;
        }
        .brand p {
            margin: 0;
            color: var(--muted);
            font-size: 0.98rem;
            line-height: 1.45;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: 0 18px 40px rgba(30, 58, 95, 0.08);
            padding: 22px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }
        @media (max-width: 640px) {
            .grid-2 { grid-template-columns: 1fr; }
            .card { padding: 16px; }
        }
        .field { margin-bottom: 14px; }
        label {
            display: block;
            font-size: 0.86rem;
            font-weight: 650;
            margin-bottom: 6px;
        }
        .req { color: var(--danger); }
        input, select, textarea {
            width: 100%;
            border: 1.5px solid var(--line);
            border-radius: 10px;
            padding: 11px 12px;
            font: inherit;
            background: #fff;
            color: var(--ink);
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(40, 87, 160, 0.12);
        }
        textarea { min-height: 110px; resize: vertical; }
        .hint { font-size: 12px; color: var(--muted); margin-top: 5px; }
        .alert {
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 14px;
            font-size: 0.92rem;
        }
        .alert-danger {
            background: #fef3f2;
            color: #7a271a;
            border: 1px solid #fecdca;
        }
        .alert-ok {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            text-align: center;
        }
        .alert-ok h2 { margin: 8px 0 6px; font-size: 1.25rem; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 13px 16px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            background: linear-gradient(135deg, var(--blue), var(--blue-2));
            color: #fff;
        }
        .btn:hover { filter: brightness(1.05); }
        .section-title {
            margin: 8px 0 12px;
            font-size: 0.95rem;
            color: var(--blue);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .file-input {
            border: 1.5px dashed var(--line);
            border-radius: 10px;
            padding: 14px;
            background: #f8fafc;
        }
        .file-input input[type="file"] { padding: 0; border: 0; background: transparent; }
        .footer-note {
            text-align: center;
            color: var(--muted);
            font-size: 12px;
            margin-top: 16px;
        }
        .alert-info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .alert-warn {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .prefill-banner {
            display: none;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 14px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            font-size: 0.92rem;
        }
        .prefill-banner.visible { display: block; }
        .input-with-btn { display: flex; gap: 8px; }
        .input-with-btn input { flex: 1; }
        .btn-buscar {
            width: auto;
            padding: 11px 14px;
            white-space: nowrap;
            background: #fff;
            color: var(--blue);
            border: 1.5px solid var(--line);
        }
        .btn-buscar:hover { background: #f8fafc; filter: none; }
        .field-highlight { animation: ssFlash 1.2s ease; }
        @keyframes ssFlash {
            0% { background: #ecfdf5; }
            100% { background: #fff; }
        }
        .hora-ocupada { color: #94a3b8; }
        .btn-buscar.loading { opacity: .7; pointer-events: none; }
        .btn-buscar.loading i { animation: ssSpin .8s linear infinite; }
        @keyframes ssSpin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">
        <div class="pill"><i class="bi bi-heart-pulse"></i> MCI Madrid Colombia</div>
        <h1>Servicio Social</h1>
        <p>Agenda una cita para atención los <strong>sábados</strong>. Las solicitudes se reciben de <strong>lunes a jueves</strong>.</p>
    </div>

    <?php if ($ok): ?>
    <div class="card alert-ok">
        <div style="font-size:2rem;"><i class="bi bi-check-circle"></i></div>
        <h2>Solicitud recibida</h2>
        <p style="margin:0 0 14px;">Tu cita quedó registrada como <strong>pendiente</strong>. El equipo de Servicio Social te contactará para confirmar fecha y hora.</p>
        <a class="btn" href="<?= htmlspecialchars(public_app_url('talleres_publico/servicio-social'), ENT_QUOTES, 'UTF-8') ?>">Agendar otra cita</a>
    </div>
    <?php else: ?>

    <?php if (!$puedeAgendarHoy): ?>
    <div class="alert alert-warn">
        <strong>Formulario cerrado hoy.</strong> Las solicitudes de cita solo se reciben de lunes a jueves. Vuelve a intentarlo en un día hábil.
    </div>
    <?php endif; ?>

    <?php if ($errores !== []): ?>
    <div class="alert alert-danger">
        <strong>Revisa el formulario:</strong>
        <ul style="margin:8px 0 0; padding-left:18px;">
            <?php foreach ($errores as $err): ?>
                <li><?= htmlspecialchars((string)$err, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div id="prefill_banner" class="prefill-banner" role="status"></div>

    <form class="card" method="POST" enctype="multipart/form-data" action="<?= htmlspecialchars(public_app_url('talleres_publico/servicio-social/guardar'), ENT_QUOTES, 'UTF-8') ?>" novalidate id="form_ss"<?= !$puedeAgendarHoy ? ' style="opacity:.65;pointer-events:none;"' : '' ?>>
        <div class="section-title"><i class="bi bi-person"></i> Datos de contacto</div>
        <div class="grid-2">
            <div class="field">
                <label for="nombre">Nombre <span class="req">*</span></label>
                <input id="nombre" name="nombre" required maxlength="120" value="<?= htmlspecialchars($v($valores, 'nombre'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="field">
                <label for="apellido">Apellido</label>
                <input id="apellido" name="apellido" maxlength="120" value="<?= htmlspecialchars($v($valores, 'apellido'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <div class="grid-2">
            <div class="field">
                <label for="tipo_documento">Tipo de documento <span class="req">*</span></label>
                <select id="tipo_documento" name="tipo_documento" required>
                    <option value="">Selecciona…</option>
                    <?php foreach ($tiposDocumento as $k => $label): ?>
                    <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $v($valores, 'tipo_documento') === $k ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="documento">Número de documento / cédula <span class="req">*</span></label>
                <div class="input-with-btn">
                    <input id="documento" name="documento" required maxlength="40" inputmode="numeric"
                           value="<?= htmlspecialchars($v($valores, 'documento'), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="button" class="btn btn-buscar" id="btn_buscar_doc" title="Buscar datos">
                        <i class="bi bi-search"></i> Buscar
                    </button>
                </div>
                <div class="hint">Busca por cédula con o sin puntos. Si ya estás registrado, traeremos tus datos.</div>
            </div>
        </div>
        <div class="grid-2">
            <div class="field">
                <label for="nombre_eps">EPS (entidad de salud)</label>
                <input id="nombre_eps" name="nombre_eps" maxlength="120"
                       placeholder="Ej. Sanitas, Sura, Nueva EPS…"
                       value="<?= htmlspecialchars($v($valores, 'nombre_eps'), ENT_QUOTES, 'UTF-8') ?>">
                <div class="hint" id="hint_eps">Opcional. Obligatorio si fuiste remitido(a) por EPS.</div>
            </div>
            <div class="field">
                <label for="telefono">Teléfono <span class="req">*</span></label>
                <input id="telefono" name="telefono" type="tel" required maxlength="40" value="<?= htmlspecialchars($v($valores, 'telefono'), ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <div class="field">
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" maxlength="160" value="<?= htmlspecialchars($v($valores, 'email'), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="section-title"><i class="bi bi-calendar2-check"></i> Agendamiento</div>
        <div class="alert alert-info" style="margin-bottom:14px;">
            Las citas de atención son los <strong>sábados</strong>. Elige fecha y hora disponibles.
        </div>
        <div class="grid-2">
            <div class="field">
                <label for="fecha_preferida">Sábado de la cita <span class="req">*</span></label>
                <select id="fecha_preferida" name="fecha_preferida" required>
                    <option value="">Selecciona un sábado…</option>
                    <?php foreach ($proximosSabados as $sab): ?>
                    <?php $f = (string)($sab['fecha'] ?? ''); ?>
                    <option value="<?= htmlspecialchars($f, ENT_QUOTES, 'UTF-8') ?>" <?= $fechaSel === $f ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)($sab['label'] ?? $f), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="hint" id="hint_fecha">Las citas solo se agendan los sábados de cada mes.</div>
            </div>
            <div class="field">
                <label for="hora_preferida">Hora de la cita <span class="req">*</span></label>
                <select id="hora_preferida" name="hora_preferida" required>
                    <option value="">Primero elige un sábado…</option>
                    <?php
                    $horaSel = $v($valores, 'hora_preferida');
                    foreach ($horariosSabado as $hk => $hl):
                    ?>
                    <option value="<?= htmlspecialchars($hk, ENT_QUOTES, 'UTF-8') ?>" <?= $horaSel === $hk ? 'selected' : '' ?>>
                        <?= htmlspecialchars($hl, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="hint" id="hint_hora">Las horas ocupadas aparecen bloqueadas.</div>
            </div>
        </div>
        <div class="field">
            <label for="tipo_cita">Tipo de cita <span class="req">*</span></label>
            <select id="tipo_cita" name="tipo_cita" required>
                <option value="">Selecciona…</option>
                <?php foreach ($tiposCita as $k => $label): ?>
                <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $v($valores, 'tipo_cita') === $k ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label for="necesidad_principal">Principal necesidad <span class="req">*</span></label>
            <textarea id="necesidad_principal" name="necesidad_principal" required
                      placeholder="Describe brevemente en qué podemos ayudarte"><?= htmlspecialchars($v($valores, 'necesidad_principal'), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="section-title"><i class="bi bi-signpost-2"></i> Remisión</div>
        <div class="field">
            <label for="remitido_por">¿Fue remitido(a) por…? <span class="req">*</span></label>
            <select id="remitido_por" name="remitido_por" required>
                <?php foreach ($remitidoPor as $k => $label): ?>
                <option value="<?= htmlspecialchars($k, ENT_QUOTES, 'UTF-8') ?>" <?= $remitidoActual === $k ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
            <div class="hint">Trabajo, EPS, colegio, iglesia, entidad, etc.</div>
        </div>
        <div class="field" id="wrap_remitido_detalle">
            <label for="remitido_detalle">Detalle de remisión</label>
            <input id="remitido_detalle" name="remitido_detalle" maxlength="255"
                   placeholder="Nombre de la empresa, EPS, colegio o entidad"
                   value="<?= htmlspecialchars($v($valores, 'remitido_detalle'), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="field" id="wrap_documentos_remision" style="display:none;">
            <label for="documentos_remision">Documentos de remisión</label>
            <div class="file-input">
                <input id="documentos_remision" name="documentos_remision[]" type="file" multiple
                       accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
            </div>
            <div class="hint">Opcional. Adjunta la remisión médica u otros documentos (PDF, JPG, PNG, DOC — máx. 8 MB c/u).</div>
        </div>
        <div class="field">
            <label for="observaciones">Observaciones adicionales</label>
            <textarea id="observaciones" name="observaciones"
                      placeholder="Opcional"><?= htmlspecialchars($v($valores, 'observaciones'), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <button type="submit" class="btn" id="btn_submit"<?= !$puedeAgendarHoy ? ' disabled' : '' ?>><i class="bi bi-send"></i> Enviar solicitud de cita</button>
    </form>
    <?php endif; ?>

    <p class="footer-note">Tu solicitud queda solo en Servicio Social; no se registra en el directorio general de personas.</p>
</div>

<script>
(function () {
    var URL_BUSCAR = <?= json_encode($urlBuscarPersona, JSON_UNESCAPED_UNICODE) ?>;
    var URL_DISP = <?= json_encode($urlDisponibilidad, JSON_UNESCAPED_UNICODE) ?>;
    var HORARIOS = <?= json_encode($horariosSabado, JSON_UNESCAPED_UNICODE) ?>;
    var PROXIMOS_SABADOS = <?= json_encode($proximosSabados, JSON_UNESCAPED_UNICODE) ?>;
    var puedeAgendar = <?= $puedeAgendarHoy ? 'true' : 'false' ?>;

    function esSabado(fechaStr) {
        if (!fechaStr) return false;
        for (var i = 0; i < PROXIMOS_SABADOS.length; i++) {
            if (PROXIMOS_SABADOS[i].fecha === fechaStr) return true;
        }
        var p = fechaStr.split('-');
        var d = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        return d.getDay() === 6;
    }

    function proximoSabado(fechaStr) {
        if (PROXIMOS_SABADOS.length > 0) {
            return PROXIMOS_SABADOS[0].fecha;
        }
        var base = fechaStr ? new Date(fechaStr.split('-')[0], parseInt(fechaStr.split('-')[1], 10) - 1, parseInt(fechaStr.split('-')[2], 10))
            : new Date();
        while (base.getDay() !== 6) {
            base.setDate(base.getDate() + 1);
        }
        var y = base.getFullYear();
        var m = String(base.getMonth() + 1).padStart(2, '0');
        var d = String(base.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function setFieldValue(id, val, highlight) {
        var el = document.getElementById(id);
        if (!el || val === undefined || val === null || String(val).trim() === '') return;
        el.value = String(val);
        if (highlight) {
            el.classList.add('field-highlight');
            setTimeout(function () { el.classList.remove('field-highlight'); }, 1200);
        }
    }

    function buscarPersona() {
        if (!URL_BUSCAR) return;
        var tipo = document.getElementById('tipo_documento');
        var doc = document.getElementById('documento');
        var banner = document.getElementById('prefill_banner');
        var btnBuscar = document.getElementById('btn_buscar_doc');
        if (!doc) return;

        var tipoVal = tipo ? String(tipo.value || '').trim() : '';
        var docVal = String(doc.value || '').trim();
        if (docVal.length < 3) {
            if (banner) {
                banner.textContent = 'Escribe al menos 3 caracteres del documento.';
                banner.classList.add('visible');
            }
            return;
        }

        if (btnBuscar) btnBuscar.classList.add('loading');
        if (banner) {
            banner.textContent = 'Buscando…';
            banner.classList.add('visible');
        }

        var url = URL_BUSCAR + (URL_BUSCAR.indexOf('?') >= 0 ? '&' : '?')
            + 'documento=' + encodeURIComponent(docVal);
        if (tipoVal !== '') {
            url += '&tipo_documento=' + encodeURIComponent(tipoVal);
        }

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (btnBuscar) btnBuscar.classList.remove('loading');
                if (!banner) return;
                if (!data || !data.ok) {
                    banner.textContent = (data && data.mensaje)
                        ? data.mensaje
                        : 'No encontramos datos con ese documento. Puedes continuar llenando el formulario.';
                    banner.classList.add('visible');
                    return;
                }
                var p = data.prefill || {};
                setFieldValue('nombre', p.nombre, true);
                setFieldValue('apellido', p.apellido, true);
                setFieldValue('telefono', p.telefono, true);
                setFieldValue('email', p.email, true);
                setFieldValue('nombre_eps', p.nombre_eps, true);
                if (p.documento) doc.value = p.documento;
                if (p.tipo_documento && tipo) tipo.value = p.tipo_documento;

                var msg = data.mensaje || 'Datos cargados.';
                if ((data.citas_anteriores || 0) > 0) {
                    msg += ' Citas anteriores: ' + data.citas_anteriores + '.';
                }
                banner.textContent = msg + ' (No se modifica el directorio de personas.)';
                banner.classList.add('visible');
            })
            .catch(function () {
                if (btnBuscar) btnBuscar.classList.remove('loading');
                if (banner) {
                    banner.textContent = 'No se pudo consultar el documento. Intenta de nuevo.';
                    banner.classList.add('visible');
                }
            });
    }

    function cargarHoras(fecha, horaPreseleccionada) {
        var selHora = document.getElementById('hora_preferida');
        var hintHora = document.getElementById('hint_hora');
        if (!selHora) return;

        selHora.innerHTML = '<option value="">Cargando horarios…</option>';

        if (!fecha || !esSabado(fecha)) {
            selHora.innerHTML = '<option value="">Elige un sábado válido</option>';
            return;
        }

        if (!URL_DISP) {
            selHora.innerHTML = '<option value="">Selecciona hora…</option>';
            Object.keys(HORARIOS).forEach(function (k) {
                var opt = document.createElement('option');
                opt.value = k;
                opt.textContent = HORARIOS[k];
                if (horaPreseleccionada === k) opt.selected = true;
                selHora.appendChild(opt);
            });
            return;
        }

        var url = URL_DISP + (URL_DISP.indexOf('?') >= 0 ? '&' : '?') + 'fecha=' + encodeURIComponent(fecha);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                selHora.innerHTML = '<option value="">Selecciona hora…</option>';
                var disponibles = 0;
                (data.horas || []).forEach(function (h) {
                    var opt = document.createElement('option');
                    opt.value = h.hora;
                    var suffix = '';
                    if (h.bloqueada) suffix = ' (no disponible)';
                    else if (!h.disponible) suffix = ' (ocupada)';
                    opt.textContent = h.label + suffix;
                    if (!h.disponible) {
                        opt.disabled = true;
                    } else {
                        disponibles++;
                        if (horaPreseleccionada === h.hora) opt.selected = true;
                    }
                    selHora.appendChild(opt);
                });
                if (hintHora) {
                    hintHora.textContent = disponibles > 0
                        ? disponibles + ' horario(s) disponible(s) para ese sábado.'
                        : 'No hay cupos disponibles ese sábado. Elige otra fecha.';
                }
            })
            .catch(function () {
                selHora.innerHTML = '<option value="">Error al cargar horarios</option>';
            });
    }

    function syncFecha() {
        var fecha = document.getElementById('fecha_preferida');
        var hintFecha = document.getElementById('hint_fecha');
        if (!fecha) return;

        var val = String(fecha.value || '');
        if (val === '') return;

        if (hintFecha) {
            hintFecha.textContent = 'Las citas solo se agendan los sábados de cada mes.';
        }

        var horaSel = document.getElementById('hora_preferida');
        cargarHoras(val, horaSel ? horaSel.value : '');
    }

    var sel = document.getElementById('remitido_por');
    var wrap = document.getElementById('wrap_remitido_detalle');
    var wrapDocs = document.getElementById('wrap_documentos_remision');
    var det = document.getElementById('remitido_detalle');
    var eps = document.getElementById('nombre_eps');
    var hintEps = document.getElementById('hint_eps');

    function syncRemision() {
        if (!sel || !wrap) return;
        var v = String(sel.value || '');
        var necesita = ['trabajo', 'eps', 'colegio', 'entidad', 'otro'].indexOf(v) !== -1;
        var tieneRemision = v !== '' && v !== 'ninguno';
        wrap.style.display = necesita ? '' : 'none';
        if (wrapDocs) wrapDocs.style.display = tieneRemision ? '' : 'none';
        if (det) det.required = necesita;
        if (eps) {
            eps.required = v === 'eps';
            if (hintEps) {
                hintEps.textContent = v === 'eps'
                    ? 'Obligatorio cuando la remisión proviene de una EPS.'
                    : 'Opcional. Indica tu entidad de salud si la conoces.';
            }
        }
    }

    if (sel) {
        sel.addEventListener('change', syncRemision);
        syncRemision();
    }

    var docInput = document.getElementById('documento');
    var tipoInput = document.getElementById('tipo_documento');
    var btnBuscar = document.getElementById('btn_buscar_doc');
    if (btnBuscar) btnBuscar.addEventListener('click', buscarPersona);
    if (docInput) {
        docInput.addEventListener('blur', function () {
            if (String(docInput.value || '').trim().length >= 3) buscarPersona();
        });
        docInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarPersona();
            }
        });
    }
    if (tipoInput) {
        tipoInput.addEventListener('change', function () {
            if (docInput && String(docInput.value || '').trim().length >= 3) buscarPersona();
        });
    }

    var fechaInput = document.getElementById('fecha_preferida');
    if (fechaInput) {
        fechaInput.addEventListener('change', syncFecha);
        if (fechaInput.value) syncFecha();
    }

    var form = document.getElementById('form_ss');
    if (form && puedeAgendar) {
        form.addEventListener('submit', function (e) {
            var f = document.getElementById('fecha_preferida');
            var h = document.getElementById('hora_preferida');
            if (f && !esSabado(String(f.value || ''))) {
                e.preventDefault();
                alert('La cita debe ser un sábado.');
                return;
            }
            if (h && h.selectedOptions && h.selectedOptions[0] && h.selectedOptions[0].disabled) {
                e.preventDefault();
                alert('Ese horario ya no está disponible. Elige otra hora.');
            }
        });
    }
})();
</script>
</body>
</html>
