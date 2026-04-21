<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asistencia Escuelas de Formación - MCI Madrid</title>
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

        .header h1 {
            margin: 0;
            font-size: 29px;
            color: #1d2f2d;
        }

        .header p {
            margin: 8px 0 0;
            color: #4f6462;
        }

        .body {
            padding: 22px;
        }

        .alert {
            border-radius: 10px;
            padding: 11px 13px;
            margin: 0 0 14px;
            border: 1px solid transparent;
            font-size: 14px;
        }

        .alert.success {
            background: var(--ok-bg);
            border-color: #cae7cf;
            color: var(--ok-tx);
        }

        .alert.error {
            background: var(--err-bg);
            border-color: #f0d0d0;
            color: var(--err-tx);
        }

        .help {
            margin: 0 0 14px;
            font-size: 13px;
            color: #59706d;
            background: #f5fbfa;
            border: 1px dashed #cde3df;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            color: #213432;
            font-size: 14px;
        }

        input, select {
            border: 1px solid #cedfdd;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 15px;
            color: #2f4442;
            outline: none;
            background: #fff;
        }

        input:focus, select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(15, 106, 102, .14);
        }

        .readonly {
            background: #f7fbfb;
            color: #516362;
        }

        .status {
            display: none;
            margin-top: 8px;
            border-radius: 8px;
            padding: 10px 12px;
            border: 1px solid transparent;
            font-size: 13px;
        }

        .status.active { display: block; }
        .status.info { background: #eef7ff; border-color: #c8dff8; color: #245384; }
        .status.warn { background: #fff7e9; border-color: #f4db9b; color: #845500; }
        .status.error { background: #fff2f2; border-color: #f1cccc; color: #8b3a3a; }

        .actions {
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            border: none;
            border-radius: 10px;
            padding: 12px 18px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            color: #fff;
            background: linear-gradient(135deg, var(--primary) 0%, #13908a 100%);
            box-shadow: 0 10px 20px rgba(15, 106, 102, .18);
        }

        .btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .btn-secondary {
            color: #2a5a56;
            background: #eef7f6;
            border: 1px solid #c8dfdc;
            box-shadow: none;
        }

        .btn-secondary:hover {
            background: #e4f1ef;
        }

        .toast {
            position: fixed;
            left: 50%;
            bottom: 22px;
            transform: translateX(-50%) translateY(10px);
            background: #1f4f4c;
            color: #fff;
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.22);
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease, transform .2s ease;
            z-index: 1200;
        }

        .toast.active {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        @media (max-width: 720px) {
            .grid { grid-template-columns: 1fr; }
            .actions { justify-content: stretch; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Asistencia a clase</h1>
        <p>Escuelas de Formación - Registro público sin código.</p>
    </div>

    <div class="body">
        <?php if (!empty($mensaje)): ?>
            <div class="alert <?= ($tipo_mensaje ?? '') === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars((string)$mensaje) ?>
            </div>
        <?php endif; ?>

        <p class="help">Escribe teléfono o cédula. Si la persona ya está inscrita, se llenarán los datos automáticamente para registrar asistencia.</p>

        <form method="POST" action="<?= PUBLIC_URL ?>?url=escuelas_formacion/asistencia-publica/guardar" id="form-asistencia-publica" autocomplete="off">
            <div class="grid">
                <div class="field">
                    <label for="telefono">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" required inputmode="numeric" pattern="[0-9]{4,}" minlength="4" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="<?= htmlspecialchars((string)($old['telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: 3001234567">
                </div>

                <div class="field">
                    <label for="cedula">Cédula</label>
                    <input type="text" id="cedula" name="cedula" required inputmode="numeric" pattern="[0-9]{4,}" minlength="4" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="<?= htmlspecialchars((string)($old['cedula'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: 12345678">
                </div>

                <div class="field full">
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" class="readonly" readonly>
                </div>

                <div class="field">
                    <label for="genero">Género</label>
                    <input type="text" id="genero" class="readonly" readonly>
                </div>

                <div class="field">
                    <label for="ministerio">Ministerio</label>
                    <input type="text" id="ministerio" class="readonly" readonly>
                </div>

                <div class="field full">
                    <label for="id_inscripcion">Programa inscrito</label>
                    <select id="id_inscripcion" name="id_inscripcion" required>
                        <option value="">Busca primero por teléfono o cédula...</option>
                    </select>
                </div>
            </div>

            <div id="estado-busqueda" class="status"></div>

            <div class="actions">
                <button type="button" class="btn btn-secondary" id="btn-limpiar-form">Limpiar formulario</button>
                <button type="submit" class="btn" id="btn-guardar" disabled>Registrar asistencia</button>
            </div>
        </form>
    </div>
</div>

<div id="toast-feedback" class="toast" aria-live="polite"></div>

<script>
(function () {
    const endpointBuscar = <?= json_encode(PUBLIC_URL . '?url=escuelas_formacion/asistencia-publica/buscar') ?>;

    const telefonoInput = document.getElementById('telefono');
    const cedulaInput = document.getElementById('cedula');
    const nombreInput = document.getElementById('nombre');
    const generoInput = document.getElementById('genero');
    const ministerioInput = document.getElementById('ministerio');
    const inscripcionSelect = document.getElementById('id_inscripcion');
    const form = document.getElementById('form-asistencia-publica');
    const estadoBusqueda = document.getElementById('estado-busqueda');
    const btnGuardar = document.getElementById('btn-guardar');
    const btnLimpiarForm = document.getElementById('btn-limpiar-form');
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
        estadoBusqueda.classList.add('active');
        if (tipo === 'warn') {
            estadoBusqueda.classList.add('warn');
        } else if (tipo === 'error') {
            estadoBusqueda.classList.add('error');
        } else {
            estadoBusqueda.classList.add('info');
        }
    }

    function mostrarToast(mensaje) {
        if (!toastFeedback) {
            return;
        }

        if (toastTimer) {
            clearTimeout(toastTimer);
        }

        toastFeedback.textContent = String(mensaje || 'Listo');
        toastFeedback.classList.add('active');
        toastTimer = setTimeout(function () {
            toastFeedback.classList.remove('active');
        }, 1500);
    }

    function limpiarDatos() {
        nombreInput.value = '';
        generoInput.value = '';
        ministerioInput.value = '';
        inscripcionSelect.innerHTML = '<option value="">Busca primero por teléfono o cédula...</option>';
        btnGuardar.disabled = true;
    }

    function poblarDatos(data) {
        const persona = data.persona || {};
        const programas = Array.isArray(data.programas) ? data.programas : [];

        const asignarSiFalta = function(input, valor) {
            if (!input) {
                return;
            }

            const actual = String(input.value || '').trim();
            const nuevoValor = String(valor || '').trim();
            if (actual === '' && nuevoValor !== '') {
                input.value = nuevoValor;
            }
        };

        asignarSiFalta(telefonoInput, persona.telefono || '');
        asignarSiFalta(cedulaInput, persona.cedula || '');
        asignarSiFalta(nombreInput, persona.nombre || '');
        asignarSiFalta(generoInput, persona.genero || '');
        asignarSiFalta(ministerioInput, persona.ministerio || '');

        inscripcionSelect.innerHTML = '';
        if (programas.length === 0) {
            inscripcionSelect.innerHTML = '<option value="">No hay programas disponibles</option>';
            btnGuardar.disabled = true;
            return;
        }

        programas.forEach(function (item) {
            const option = document.createElement('option');
            option.value = String(item.id_inscripcion || '');
            const yaAsistio = String(item.asistio_clase || '') === '1';
            option.textContent = String(item.programa_label || item.programa || 'Programa') + (yaAsistio ? ' (ya registrada)' : '');
            inscripcionSelect.appendChild(option);
        });

        btnGuardar.disabled = false;
    }

    function buscar() {
        const telefono = String(telefonoInput.value || '').trim();
        const cedula = String(cedulaInput.value || '').trim();

        if (telefono === '' && cedula === '') {
            limpiarDatos();
            setEstado('warn', 'Ingresa teléfono o cédula para buscar.');
            return;
        }

        fetch(endpointBuscar + '&telefono=' + encodeURIComponent(telefono) + '&cedula=' + encodeURIComponent(cedula), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || data.encontrado !== true) {
                    limpiarDatos();
                    setEstado('warn', (data && data.mensaje) ? data.mensaje : 'No encontramos coincidencias.');
                    return;
                }

                poblarDatos(data);
                setEstado('info', data.mensaje || 'Datos encontrados. Selecciona programa y registra asistencia.');
            })
            .catch(function () {
                limpiarDatos();
                setEstado('error', 'No se pudo realizar la búsqueda en este momento.');
            });
    }

    function programarBusqueda() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(buscar, 300);
    }

    function esSoloDigitos(valor) {
        return /^\d+$/.test(String(valor || '').trim());
    }

    cedulaInput.addEventListener('input', function () {
        cedulaInput.value = String(cedulaInput.value || '').replace(/\D+/g, '');
    });

    telefonoInput.addEventListener('input', function () {
        telefonoInput.value = String(telefonoInput.value || '').replace(/\D+/g, '');
    });

    telefonoInput.addEventListener('input', programarBusqueda);
    cedulaInput.addEventListener('input', programarBusqueda);
    telefonoInput.addEventListener('blur', buscar);
    cedulaInput.addEventListener('blur', buscar);

    form.addEventListener('submit', function (event) {
        const telefono = String(telefonoInput.value || '').trim();
        const cedula = String(cedulaInput.value || '').trim();

        if (!telefono) {
            event.preventDefault();
            alert('El telefono es obligatorio.');
            telefonoInput.focus();
            return;
        }

        if (!cedula) {
            event.preventDefault();
            alert('La cedula es obligatoria.');
            cedulaInput.focus();
            return;
        }

        if (!esSoloDigitos(telefono)) {
            event.preventDefault();
            alert('El telefono solo puede contener numeros.');
            telefonoInput.focus();
            return;
        }

        if (!esSoloDigitos(cedula)) {
            event.preventDefault();
            alert('La cedula solo puede contener numeros.');
            cedulaInput.focus();
            return;
        }

        if (telefono.length < 4) {
            event.preventDefault();
            alert('El telefono debe tener al menos 4 numeros.');
            telefonoInput.focus();
            return;
        }

        if (cedula.length < 4) {
            event.preventDefault();
            alert('La cedula debe tener al menos 4 numeros.');
            cedulaInput.focus();
            return;
        }
    });

    if (btnLimpiarForm) {
        btnLimpiarForm.addEventListener('click', function () {
            if (debounceTimer) {
                clearTimeout(debounceTimer);
                debounceTimer = null;
            }

            form.reset();
            telefonoInput.value = '';
            cedulaInput.value = '';
            limpiarDatos();
            setEstado('', '');
            telefonoInput.focus();
            mostrarToast('Formulario limpiado');
        });
    }

    if ((telefonoInput.value || '').trim() !== '' || (cedulaInput.value || '').trim() !== '') {
        buscar();
    }
})();
</script>
</body>
</html>
