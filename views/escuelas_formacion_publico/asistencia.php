<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistencia Universidad de la Vida - MCI Madrid</title>
    <style>
        :root {
            --primary: #0f6a66;
            --primary-dark: #0b5451;
            --border: #d3e4e2;
            --bg: #eef6f5;
            --ok-bg: #ecf8ef;
            --ok-tx: #1f7a3c;
            --err-bg: #fff1f1;
            --err-tx: #9c3434;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: linear-gradient(180deg, #f3f9f8 0%, var(--bg) 100%);
            min-height: 100vh;
            padding: 20px 12px;
            color: #2d3c3b;
        }
        .container {
            max-width: 760px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 14px 30px rgba(15, 77, 74, .12);
        }
        .header {
            padding: 22px;
            background: linear-gradient(135deg, #e8f4f2 0%, #fff 100%);
            border-bottom: 1px solid var(--border);
        }
        .header h1 { margin: 0; font-size: 26px; color: #1d2f2d; }
        .header p { margin: 8px 0 0; color: #4f6462; }
        .body { padding: 22px; }
        .alert {
            border-radius: 10px;
            padding: 11px 13px;
            margin: 0 0 14px;
            border: 1px solid transparent;
            font-size: 14px;
        }
        .alert.success { background: var(--ok-bg); border-color: #cae7cf; color: var(--ok-tx); }
        .alert.error { background: var(--err-bg); border-color: #f0d0d0; color: var(--err-tx); }
        .help {
            margin: 0 0 14px;
            font-size: 13px;
            color: #59706d;
            background: #f5fbfa;
            border: 1px dashed #cde3df;
            border-radius: 8px;
            padding: 10px 12px;
        }
        .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
        label { font-size: 13px; font-weight: 700; color: #35504d; }
        input[type=text] {
            border: 1px solid #c9dddb;
            border-radius: 10px;
            padding: 12px 13px;
            font-size: 18px;
            letter-spacing: 0.04em;
        }
        .persona {
            display: none;
            border: 1px solid #d7e8e5;
            border-radius: 12px;
            padding: 14px;
            background: #f8fcfb;
            margin-bottom: 16px;
        }
        .persona.visible { display: block; }
        .persona h2 { margin: 0 0 6px; font-size: 22px; color: #16332f; }
        .meta { color: #4f6462; font-size: 14px; line-height: 1.5; }
        .status { display: none; margin: 0 0 14px; font-size: 14px; }
        .status.active { display: block; }
        .status.info { color: #1f6b66; }
        .status.warn { color: #9a5b12; }
        .status.error { color: #9c3434; }
        .grupos { display: none; }
        .grupos.visible { display: block; }
        .grupo { margin-bottom: 14px; }
        .grupo h3 {
            margin: 0 0 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #4f6462;
        }
        .clases {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .clase {
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #cfe3e0;
            border-radius: 10px;
            padding: 10px 12px;
            background: #fff;
            cursor: pointer;
        }
        .clase.seleccionada { border-color: #0f6a66; box-shadow: 0 0 0 2px rgba(15, 106, 102, .18); }
        .clase.asistio { background: #ecf8ef; border-color: #b7e0c0; }
        .clase input { width: 18px; height: 18px; accent-color: var(--primary); }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
        .btn {
            background: var(--primary);
            color: #fff;
            border: 0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }
        .btn:disabled { opacity: .45; cursor: not-allowed; }
        .btn-secondary { background: #2f4f4c; }
        .btn-ghost {
            background: #fff;
            color: var(--primary-dark);
            border: 1px solid #b7d4d0;
        }
        .exito-box {
            display: none;
            margin: 0 0 16px;
            padding: 16px 16px 14px;
            border-radius: 12px;
            border: 1px solid #b7e0c0;
            background: #ecf8ef;
        }
        .exito-box.visible { display: block; }
        .exito-box strong { display: block; font-size: 18px; color: #1f7a3c; margin-bottom: 6px; }
        .exito-box p { margin: 0 0 12px; color: #2d5a3a; font-size: 14px; }
        .enlace-registro {
            display: none;
            margin: 0 0 14px;
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #cfe3e0;
            background: #f5fbfa;
            font-size: 14px;
            color: #35504d;
        }
        .enlace-registro.visible { display: block; }
        .enlace-registro a { color: #0f6a66; font-weight: 700; }
        .toast {
            position: fixed;
            left: 50%;
            bottom: 18px;
            transform: translateX(-50%) translateY(12px);
            background: #16332f;
            color: #fff;
            padding: 10px 14px;
            border-radius: 10px;
            opacity: 0;
            transition: .2s;
            z-index: 20;
        }
        .toast.active { opacity: 1; transform: translateX(-50%) translateY(0); }
        @media (max-width: 720px) {
            .clases { grid-template-columns: 1fr; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Asistencia Universidad de la Vida</h1>
        <p>Consulta por cédula. Puedes marcar una o varias clases a la vez. Los días de Encuentro no se marcan en este formulario.</p>
    </div>
    <div class="body">
        <?php if (!empty($mensaje)): ?>
            <div class="alert <?= ($tipo_mensaje ?? '') === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars((string)$mensaje) ?>
            </div>
        <?php endif; ?>
        <p class="help">Escribe el número de cédula. Marca una o varias clases de pre-encuentro, refuerzo o post-encuentro y pulsa <strong>Marcar asistencia</strong>. Los días de Encuentro no se marcan aquí.</p>
        <form method="POST" action="<?= PUBLIC_URL ?>?url=escuelas_formacion/asistencia-publica/guardar" id="form-asistencia-publica" autocomplete="off">
            <div class="field">
                <label for="cedula">Número de cédula</label>
                <input type="text" id="cedula" name="cedula" required inputmode="numeric" pattern="[0-9]{4,}" minlength="4" autocomplete="off" value="<?= htmlspecialchars((string)($old['cedula'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: 12345678">
            </div>
            <div id="estado-busqueda" class="status"></div>
            <div id="enlace-registro" class="enlace-registro"></div>
            <div id="exito-asistencia" class="exito-box">
                <strong id="exito-asistencia-titulo">Asistencia guardada con éxito</strong>
                <p id="exito-asistencia-texto"></p>
                <button type="button" class="btn btn-ghost" id="btn-buscar-otra">Buscar otra persona</button>
            </div>
            <div id="ficha-persona" class="persona">
                <h2 id="nombre-persona"></h2>
                <div class="meta" id="datos-persona"></div>
            </div>
            <div id="grupos-clases" class="grupos"></div>
            <input type="hidden" name="numero_clase" id="numero_clase" value="">
            <input type="hidden" name="numeros_clase" id="numeros_clase" value="">
            <input type="hidden" name="opcion_clase" id="opcion_clase" value="">
            <div class="actions">
                <button type="button" class="btn btn-secondary" id="btn-limpiar-form">Limpiar</button>
                <button type="submit" class="btn" id="btn-guardar" disabled>Marcar asistencia</button>
            </div>
        </form>
    </div>
</div>
<div id="toast-feedback" class="toast" aria-live="polite"></div>
<script>
(function () {
    const endpointBuscar = <?= json_encode(PUBLIC_URL . '?url=escuelas_formacion/asistencia-publica/buscar') ?>;
    const endpointGuardar = <?= json_encode(PUBLIC_URL . '?url=escuelas_formacion/asistencia-publica/guardar') ?>;
    const urlRegistroUv = <?= json_encode(PUBLIC_URL . '?url=escuelas_formacion/registro-publico/universidad-vida') ?>;
    const cedulaInput = document.getElementById('cedula');
    const form = document.getElementById('form-asistencia-publica');
    const estadoBusqueda = document.getElementById('estado-busqueda');
    const enlaceRegistro = document.getElementById('enlace-registro');
    const exitoBox = document.getElementById('exito-asistencia');
    const exitoTitulo = document.getElementById('exito-asistencia-titulo');
    const exitoTexto = document.getElementById('exito-asistencia-texto');
    const btnBuscarOtra = document.getElementById('btn-buscar-otra');
    const ficha = document.getElementById('ficha-persona');
    const nombrePersona = document.getElementById('nombre-persona');
    const datosPersona = document.getElementById('datos-persona');
    const grupos = document.getElementById('grupos-clases');
    const numeroClaseInput = document.getElementById('numero_clase');
    const numerosClaseInput = document.getElementById('numeros_clase');
    const opcionClaseInput = document.getElementById('opcion_clase');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnLimpiar = document.getElementById('btn-limpiar-form');
    const toastFeedback = document.getElementById('toast-feedback');
    let debounceTimer = null;
    let toastTimer = null;

    function setEstado(tipo, mensaje) {
        estadoBusqueda.classList.remove('active', 'info', 'warn', 'error');
        if (!mensaje) {
            estadoBusqueda.textContent = '';
            return;
        }
        estadoBusqueda.textContent = String(mensaje);
        estadoBusqueda.classList.add('active', tipo || 'info');
    }

    function ocultarEnlaceRegistro() {
        if (!enlaceRegistro) return;
        enlaceRegistro.classList.remove('visible');
        enlaceRegistro.innerHTML = '';
    }

    function mostrarEnlaceRegistro(cedula, mensaje, urlApi) {
        if (!enlaceRegistro) return;
        const url = urlApi || (urlRegistroUv + (cedula ? ('&cedula=' + encodeURIComponent(cedula)) : ''));
        enlaceRegistro.innerHTML = (mensaje ? ('<div style="margin-bottom:8px;">' + mensaje + '</div>') : '')
            + 'Si no está inscrita en este ciclo, <a href="' + url + '">regístrala en el formulario de Universidad de la Vida</a>.';
        enlaceRegistro.classList.add('visible');
    }

    function ocultarExito() {
        if (exitoBox) exitoBox.classList.remove('visible');
        if (exitoTexto) exitoTexto.textContent = '';
    }

    function mostrarExito(mensaje, etiquetaClase) {
        const texto = String(mensaje || '');
        const claseTxt = String(etiquetaClase || '');
        if (exitoTitulo) {
            exitoTitulo.textContent = claseTxt
                ? ('Asistencia guardada: ' + claseTxt)
                : 'Asistencia guardada con éxito';
        }
        if (exitoTexto) {
            exitoTexto.textContent = texto;
        }
        if (exitoBox) exitoBox.classList.add('visible');
    }

    function mostrarToast(mensaje) {
        toastFeedback.textContent = String(mensaje || 'Listo');
        toastFeedback.classList.add('active');
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toastFeedback.classList.remove('active'); }, 2800);
    }

    function resetearParaOtraPersona() {
        form.reset();
        limpiarResultado();
        ocultarExito();
        ocultarEnlaceRegistro();
        setEstado('', '');
        cedulaInput.focus();
    }

    function limpiarResultado() {
        ficha.classList.remove('visible');
        grupos.classList.remove('visible');
        grupos.innerHTML = '';
        numeroClaseInput.value = '';
        if (numerosClaseInput) numerosClaseInput.value = '';
        if (opcionClaseInput) opcionClaseInput.value = '';
        btnGuardar.disabled = true;
        nombrePersona.textContent = '';
        datosPersona.textContent = '';
    }

    function actualizarSeleccion() {
        const numeros = [];
        const ids = [];
        grupos.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
            const label = input.closest('.clase');
            if (label) {
                label.classList.toggle('seleccionada', input.checked && !input.disabled);
            }
            if (!input.checked || input.disabled) {
                return;
            }
            ids.push(String(input.value || ''));
            String(input.getAttribute('data-numeros') || '').split(',').forEach(function (parte) {
                const n = Number(String(parte || '').trim());
                if (n > 0 && numeros.indexOf(n) === -1) {
                    numeros.push(n);
                }
            });
        });
        numeroClaseInput.value = numeros.length ? String(numeros[0]) : '';
        if (numerosClaseInput) numerosClaseInput.value = numeros.join(',');
        if (opcionClaseInput) opcionClaseInput.value = ids.join(',');
        btnGuardar.disabled = numeros.length === 0;
    }

    function renderClases(clases, sugerida, opciones) {
        const items = Array.isArray(opciones) && opciones.length
            ? opciones
            : (clases || []).filter(function (clase) {
                const grupo = String(clase.grupo || '');
                const numero = Number(clase.numero || 0);
                return grupo !== 'Encuentro' && numero !== 5 && numero !== 6;
            }).map(function (clase) {
                return {
                    id: String(clase.numero || ''),
                    grupo: clase.grupo || 'Clases',
                    label: clase.label || ('Clase ' + clase.numero),
                    numeros: [Number(clase.numero || 0)],
                    asistio: !!clase.asistio,
                    parcial: false
                };
            });
        const porGrupo = {};
        items.forEach(function (opcion) {
            const grupo = String(opcion.grupo || 'Clases');
            if (!porGrupo[grupo]) porGrupo[grupo] = [];
            porGrupo[grupo].push(opcion);
        });
        grupos.innerHTML = '';
        Object.keys(porGrupo).forEach(function (grupoNombre) {
            const wrap = document.createElement('div');
            wrap.className = 'grupo';
            wrap.innerHTML = '<h3>' + grupoNombre + '</h3>';
            const grid = document.createElement('div');
            grid.className = 'clases';
            porGrupo[grupoNombre].forEach(function (opcion) {
                const label = document.createElement('label');
                const yaAsistio = !!opcion.asistio;
                const extra = yaAsistio ? ' (ya asistió)' : (opcion.parcial ? ' (parcial)' : '');
                const numeros = (opcion.numeros || []).map(function (n) { return Number(n); }).filter(function (n) { return n > 0; });
                label.className = 'clase' + (yaAsistio ? ' asistio' : '');
                label.innerHTML = '<input type="checkbox" name="clase_ui[]" value="' + String(opcion.id || '') + '"'
                    + ' data-numeros="' + numeros.join(',') + '"'
                    + (yaAsistio ? ' disabled' : '') + '>'
                    + '<span>' + String(opcion.label || '') + extra + '</span>';
                const input = label.querySelector('input');
                input.addEventListener('change', actualizarSeleccion);
                grid.appendChild(label);
            });
            wrap.appendChild(grid);
            grupos.appendChild(wrap);
        });
        grupos.classList.add('visible');
        const sugeridaNum = Number(sugerida || 0);
        let marcada = grupos.querySelector('input[type="checkbox"]:not(:disabled)[data-numeros="' + sugeridaNum + '"]');
        if (!marcada) {
            marcada = grupos.querySelector('input[type="checkbox"]:not(:disabled)');
        }
        if (marcada) {
            marcada.checked = true;
        }
        actualizarSeleccion();
    }

    function buscar(opciones) {
        const mantenerExito = !!(opciones && opciones.mantenerExito);
        const cedula = String(cedulaInput.value || '').replace(/\D+/g, '');
        cedulaInput.value = cedula;
        if (cedula.length < 4) {
            limpiarResultado();
            ocultarExito();
            ocultarEnlaceRegistro();
            setEstado('warn', 'Escribe al menos 4 números de la cédula.');
            return;
        }
        if (!mantenerExito) {
            ocultarExito();
        }
        fetch(endpointBuscar + '&cedula=' + encodeURIComponent(cedula), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || data.encontrado !== true) {
                    limpiarResultado();
                    ocultarExito();
                    const mensaje = (data && data.mensaje) ? data.mensaje : 'No encontramos coincidencias.';
                    setEstado('warn', mensaje);
                    if (data && data.puede_registrar !== false && cedula.length >= 4) {
                        mostrarEnlaceRegistro(cedula, mensaje, data.url_registro || '');
                    } else {
                        ocultarEnlaceRegistro();
                    }
                    return;
                }
                ocultarEnlaceRegistro();
                const persona = data.persona || {};
                nombrePersona.textContent = persona.nombre || 'Persona encontrada';
                const partes = [];
                if (persona.cedula) partes.push('Cédula: ' + persona.cedula);
                if (persona.telefono) partes.push('Teléfono: ' + persona.telefono);
                if (persona.genero) partes.push(persona.genero);
                if (persona.ministerio) partes.push('Ministerio: ' + persona.ministerio);
                if (persona.lider) partes.push('Líder: ' + persona.lider);
                datosPersona.textContent = partes.join(' · ');
                ficha.classList.add('visible');
                renderClases(data.clases || [], data.clase_sugerida || 1, data.opciones || []);
                if (!mantenerExito) {
                    setEstado('info', data.mensaje || 'Marca una o varias clases.');
                }
            })
            .catch(function () {
                limpiarResultado();
                setEstado('error', 'No se pudo realizar la búsqueda en este momento.');
            });
    }

    cedulaInput.addEventListener('input', function () {
        cedulaInput.value = String(cedulaInput.value || '').replace(/\D+/g, '');
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(buscar, 280);
    });
    cedulaInput.addEventListener('blur', buscar);

    btnLimpiar.addEventListener('click', resetearParaOtraPersona);
    if (btnBuscarOtra) {
        btnBuscarOtra.addEventListener('click', resetearParaOtraPersona);
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const cedula = String(cedulaInput.value || '').trim();
        const numerosClase = String((numerosClaseInput && numerosClaseInput.value) || numeroClaseInput.value || '').trim();
        if (cedula.length < 4 || !numerosClase) {
            setEstado('warn', 'Busca la persona y marca al menos una clase.');
            return;
        }
        const body = new FormData();
        body.append('cedula', cedula);
        body.append('numero_clase', String(numeroClaseInput.value || '').trim());
        body.append('numeros_clase', numerosClase);
        if (opcionClaseInput && opcionClaseInput.value) {
            body.append('opcion_clase', opcionClaseInput.value);
        }
        btnGuardar.disabled = true;
        fetch(endpointGuardar, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const ok = !!(data && data.success);
                const mensaje = (data && data.mensaje)
                    ? data.mensaje
                    : (ok ? 'Asistencia guardada con éxito.' : 'No se pudo guardar la asistencia.');
                const etiqueta = (data && data.etiqueta_clase) ? data.etiqueta_clase : '';
                mostrarToast(mensaje);
                setEstado(ok ? 'info' : 'error', mensaje);
                if (ok) {
                    mostrarExito(mensaje, etiqueta);
                    buscar({ mantenerExito: true });
                } else {
                    btnGuardar.disabled = false;
                    if (/no encontramos|inscribirse de nuevo|no está inscrita/i.test(mensaje)) {
                        mostrarEnlaceRegistro(cedula, mensaje);
                    }
                }
            })
            .catch(function () {
                btnGuardar.disabled = false;
                setEstado('error', 'No se pudo guardar la asistencia.');
            });
    });

    if (String(cedulaInput.value || '').trim() !== '') {
        buscar();
    }
})();
</script>
</body>
</html>
