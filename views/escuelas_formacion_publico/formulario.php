<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripción Escuelas de Formación - MCI Madrid</title>
    <style>
        :root {
            --primary: #0a6e6a;
            --primary-dark: #075552;
            --primary-soft: #e8f6f4;
            --text-main: #2f3b3a;
            --text-title: #1e2d2b;
            --border: #d1e6e3;
            --danger-bg: #fff1f1;
            --danger-text: #9c3434;
            --success-bg: #ecf8ef;
            --success-text: #1f7a3c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #f1f8f7 0%, #e6f1ef 100%);
            color: var(--text-main);
            min-height: 100vh;
            padding: 20px 12px;
        }

        .container {
            max-width: 760px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 14px 30px rgba(15, 77, 74, 0.15);
        }

        .header {
            padding: 26px 22px;
            background: linear-gradient(135deg, var(--primary-soft) 0%, #ffffff 100%);
            border-bottom: 1px solid var(--border);
        }

        .eyebrow {
            margin: 0 0 8px;
            color: var(--primary);
            font-weight: 700;
            letter-spacing: 0.3px;
            font-size: 15px;
        }

        h1 {
            margin: 0;
            color: var(--text-title);
            font-size: 30px;
            line-height: 1.2;
        }

        .sub {
            margin: 10px 0 0;
            color: var(--text-main);
            font-size: 15px;
        }

        .body {
            padding: 24px 22px;
        }

        .alert {
            border-radius: 10px;
            padding: 12px 14px;
            margin: 0 0 16px;
            border: 1px solid transparent;
            font-size: 14px;
        }

        .alert.error {
            background: var(--danger-bg);
            color: var(--danger-text);
            border-color: #f7d7d7;
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success-text);
            border-color: #cceacd;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
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
            color: var(--text-title);
            font-weight: 600;
            font-size: 14px;
        }

        .req {
            color: #d45a5a;
        }

        input,
        select {
            border: 1px solid #d2e4e1;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 15px;
            color: #384a48;
            background: #fff;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        input:focus,
        select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(10, 110, 106, 0.14);
        }

        .help {
            margin: 0 0 14px;
            font-size: 13px;
            color: #667775;
            background: #f5fbfa;
            border: 1px dashed #cae4df;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .hint {
            margin-top: 10px;
            font-size: 13px;
            color: #758280;
        }

        .actions {
            margin-top: 18px;
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
            background: linear-gradient(135deg, var(--primary) 0%, #0f8b86 100%);
            box-shadow: 0 10px 20px rgba(10, 110, 106, 0.2);
        }

        .btn:hover {
            filter: brightness(0.98);
        }

        .btn:active {
            transform: translateY(1px);
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

        .success-box {
            border: 1px solid #b8e2c6;
            background: #f3fbf5;
            border-radius: 12px;
            padding: 18px;
        }

        .success-actions {
            margin-top: 14px;
        }

        .loader {
            display: none;
            margin-top: 8px;
            font-size: 13px;
            color: #54706d;
        }

        .loader.active {
            display: block;
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

        .search-status {
            display: none;
            margin-top: 8px;
            font-size: 13px;
            border-radius: 8px;
            padding: 10px 12px;
            border: 1px solid transparent;
        }

        .search-status.active {
            display: block;
        }

        .search-status.info {
            background: #eef7ff;
            border-color: #c8dff8;
            color: #245384;
        }

        .search-status.warn {
            background: #fff7e9;
            border-color: #f4db9b;
            color: #845500;
        }

        .search-status.error {
            background: #fff2f2;
            border-color: #f1cccc;
            color: #8b3a3a;
        }

        .autocomplete-wrap {
            position: relative;
        }

        .autocomplete-list {
            display: none;
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            max-height: 220px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #d2e4e1;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            z-index: 20;
        }

        .autocomplete-list.active {
            display: block;
        }

        .autocomplete-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #edf5f4;
            font-size: 14px;
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .autocomplete-item:hover {
            background: #f5fbfa;
        }

        @media (max-width: 720px) {
            h1 {
                font-size: 24px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .actions {
                justify-content: stretch;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <p class="eyebrow">Escuelas de Formación</p>
        <h1>Inscripción pública</h1>
        <p class="sub">Registra personas para Universidad de la Vida o Capacitación Destino.</p>
    </div>

    <div class="body">
        <?php if (!empty($mensaje)): ?>
            <div class="alert <?= ($tipo_mensaje ?? '') === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars((string)$mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($registro_exitoso)): ?>
            <div class="success-box">
                <strong>Inscripción completada.</strong>
                <div class="success-actions">
                    <a class="btn" href="<?= PUBLIC_URL ?>?url=escuelas_formacion/registro-publico" style="display:inline-block; text-decoration:none;">Registrar otra persona</a>
                </div>
            </div>
        <?php else: ?>
            <p class="help">Debes registrar cédula y teléfono. Con esos datos se buscará la persona en la plataforma para autocompletar y evitar errores.</p>

            <form method="POST" action="<?= PUBLIC_URL ?>?url=escuelas_formacion/registro-publico/guardar" id="form-escuelas" autocomplete="off">
                <div class="grid">
                    <div class="field full">
                        <label for="nombre">Nombre <span class="req">*</span></label>
                        <input type="text" id="nombre" name="nombre" required autocomplete="off" autocapitalize="characters" spellcheck="false" value="<?= htmlspecialchars((string)($old['nombre'] ?? '')) ?>">
                    </div>

                    <div class="field">
                        <label for="genero">Género <span class="req">*</span></label>
                        <select id="genero" name="genero" required>
                            <option value="">Seleccione...</option>
                            <option value="Hombre" <?= (string)($old['genero'] ?? '') === 'Hombre' ? 'selected' : '' ?>>Hombre</option>
                            <option value="Mujer" <?= (string)($old['genero'] ?? '') === 'Mujer' ? 'selected' : '' ?>>Mujer</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="edad">Edad <span class="req">*</span></label>
                        <input type="number" id="edad" name="edad" min="7" max="120" step="1" required value="<?= htmlspecialchars((string)($old['edad'] ?? '')) ?>" placeholder="Ej: 28">
                    </div>

                    <div class="field">
                        <label for="telefono">Teléfono <span class="req">*</span></label>
                        <input type="tel" id="telefono" name="telefono" required inputmode="numeric" pattern="[0-9]{4,}" minlength="4" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="<?= htmlspecialchars((string)($old['telefono'] ?? '')) ?>" placeholder="Ej: 3001234567">
                    </div>

                    <div class="field">
                        <label for="cedula">Cédula <span class="req">*</span></label>
                        <input type="text" id="cedula" name="cedula" required inputmode="numeric" pattern="[0-9]{4,}" minlength="4" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="<?= htmlspecialchars((string)($old['cedula'] ?? '')) ?>" placeholder="Ej: 12345678">
                    </div>

                    <div class="field full">
                        <label for="lider">Líder <span class="req">*</span></label>
                        <div class="autocomplete-wrap">
                            <input type="text" id="lider" name="lider" required autocomplete="off" autocapitalize="characters" spellcheck="false" value="<?= htmlspecialchars((string)($old['lider'] ?? '')) ?>" placeholder="Escribe para buscar líder real">
                            <input type="hidden" id="id_lider" name="id_lider" value="<?= htmlspecialchars((string)($old['id_lider'] ?? '')) ?>">
                            <div id="lista-lideres" class="autocomplete-list"></div>
                        </div>
                    </div>

                    <div class="field">
                        <label for="id_ministerio">Ministerio <span class="req">*</span></label>
                        <select id="id_ministerio" name="id_ministerio" required>
                            <option value="">Seleccione...</option>
                            <?php foreach (($ministerios ?? []) as $ministerio): ?>
                                <option value="<?= (int)$ministerio['Id_Ministerio'] ?>" <?= (string)($old['id_ministerio'] ?? '') === (string)$ministerio['Id_Ministerio'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$ministerio['Nombre_Ministerio']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field">
                        <label for="programa">Programa <span class="req">*</span></label>
                        <select id="programa" name="programa" required>
                            <option value="">Seleccione...</option>
                            <option value="universidad_vida" <?= (string)($old['programa'] ?? '') === 'universidad_vida' ? 'selected' : '' ?>>Universidad de la Vida</option>
                            <option value="encuentro" <?= (string)($old['programa'] ?? '') === 'encuentro' ? 'selected' : '' ?>>Encuentro</option>
                            <option value="bautismo" <?= (string)($old['programa'] ?? '') === 'bautismo' ? 'selected' : '' ?>>Bautismo</option>
                            <option value="capacitacion_destino_nivel_1" <?= (string)($old['programa'] ?? '') === 'capacitacion_destino_nivel_1' ? 'selected' : '' ?>>Capacitación Destino - Nivel 1 (Módulos 1 y 2)</option>
                            <option value="capacitacion_destino_nivel_2" <?= (string)($old['programa'] ?? '') === 'capacitacion_destino_nivel_2' ? 'selected' : '' ?>>Capacitación Destino - Nivel 2 (Módulos 3 y 4)</option>
                            <option value="capacitacion_destino_nivel_3" <?= (string)($old['programa'] ?? '') === 'capacitacion_destino_nivel_3' ? 'selected' : '' ?>>Capacitación Destino - Nivel 3 (Módulos 5 y 6)</option>
                        </select>
                    </div>
                </div>

                <div class="loader" id="loader-busqueda">Buscando coincidencias en Personas...</div>
                <div class="search-status" id="estado-busqueda"></div>
                <p class="hint">Para mayor exactitud usamos primero cédula o teléfono; si no hay coincidencia, intentamos por nombre.</p>

                <div class="actions">
                    <button type="button" class="btn btn-secondary" id="btn-limpiar-form">Limpiar formulario</button>
                    <button type="submit" class="btn">Guardar inscripción</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div id="toast-feedback" class="toast" aria-live="polite"></div>

<script>
(function() {
    const endpointBuscar = <?= json_encode(PUBLIC_URL . '?url=escuelas_formacion/registro-publico/buscar-persona') ?>;
    const endpointLideres = <?= json_encode(PUBLIC_URL . '?url=escuelas_formacion/registro-publico/buscar-lideres') ?>;
    const form = document.getElementById('form-escuelas');
    const nombre = document.getElementById('nombre');
    const genero = document.getElementById('genero');
    const edad = document.getElementById('edad');
    const telefono = document.getElementById('telefono');
    const cedula = document.getElementById('cedula');
    const lider = document.getElementById('lider');
    const idLider = document.getElementById('id_lider');
    const listaLideres = document.getElementById('lista-lideres');
    const ministerio = document.getElementById('id_ministerio');
    const btnLimpiarForm = document.getElementById('btn-limpiar-form');
    const loader = document.getElementById('loader-busqueda');
    const estadoBusqueda = document.getElementById('estado-busqueda');
    const toastFeedback = document.getElementById('toast-feedback');

    let toastTimer = null;

    if (!form || !nombre || !genero || !edad || !telefono || !cedula || !lider || !idLider || !listaLideres || !ministerio) {
        return;
    }

    let timer = null;

    function toUpperCaseInput(input) {
        if (!input || typeof input.value !== 'string') {
            return;
        }
        input.value = input.value.toUpperCase();
    }

    function setLoading(active) {
        if (!loader) {
            return;
        }
        loader.classList.toggle('active', !!active);
    }

    function setEstadoBusqueda(tipo, mensaje) {
        if (!estadoBusqueda) {
            return;
        }

        estadoBusqueda.classList.remove('active', 'info', 'warn', 'error');

        if (!mensaje) {
            estadoBusqueda.textContent = '';
            return;
        }

        estadoBusqueda.textContent = String(mensaje);
        estadoBusqueda.classList.add('active');
        if (tipo === 'warn') {
            estadoBusqueda.classList.add('warn');
            return;
        }
        if (tipo === 'error') {
            estadoBusqueda.classList.add('error');
            return;
        }
        estadoBusqueda.classList.add('info');
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
        toastTimer = setTimeout(function() {
            toastFeedback.classList.remove('active');
        }, 1500);
    }

    function aplicarPersona(persona) {
        if (!persona || typeof persona !== 'object') {
            return;
        }

        const completarSiFalta = function(input, valor) {
            if (!input) {
                return;
            }

            const actual = String(input.value || '').trim();
            const nuevo = String(valor || '').trim();
            if (actual === '' && nuevo !== '') {
                input.value = nuevo;
            }
        };

        completarSiFalta(nombre, persona.nombre || '');
        completarSiFalta(genero, persona.genero || '');
        completarSiFalta(telefono, persona.telefono || '');
        completarSiFalta(cedula, persona.cedula || '');
        completarSiFalta(lider, persona.lider || '');

        if (!String(idLider.value || '').trim() && persona.id_lider) {
            idLider.value = String(persona.id_lider);
        }

        if (!String(ministerio.value || '').trim() && persona.id_ministerio) {
            ministerio.value = String(persona.id_ministerio);
        }

        toUpperCaseInput(nombre);
        toUpperCaseInput(lider);
    }

    function cerrarListaLideres() {
        listaLideres.classList.remove('active');
        listaLideres.innerHTML = '';
    }

    function seleccionarLider(item) {
        if (!item || !item.id_persona) {
            return;
        }

        lider.value = String(item.nombre || '');
        idLider.value = String(item.id_persona);
        toUpperCaseInput(lider);
        cerrarListaLideres();
    }

    async function buscarLideresReales() {
        const term = String(lider.value || '').trim();
        if (term.length < 2) {
            cerrarListaLideres();
            return;
        }

        try {
            const response = await fetch(endpointLideres + '&term=' + encodeURIComponent(term), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (!response.ok || !data || !Array.isArray(data.data) || data.data.length === 0) {
                cerrarListaLideres();
                return;
            }

            listaLideres.innerHTML = '';
            data.data.forEach(function(item) {
                const option = document.createElement('div');
                option.className = 'autocomplete-item';
                option.textContent = String(item.nombre || '') + (item.rol ? ' - ' + String(item.rol) : '');
                option.addEventListener('click', function() {
                    seleccionarLider(item);
                });
                listaLideres.appendChild(option);
            });
            listaLideres.classList.add('active');
        } catch (error) {
            cerrarListaLideres();
        }
    }

    async function buscarPersona() {
        const params = new URLSearchParams({
            cedula: String(cedula.value || '').trim(),
            telefono: String(telefono.value || '').trim(),
            nombre: String(nombre.value || '').trim()
        });

        if (!params.get('cedula') && !params.get('telefono') && !params.get('nombre')) {
            setEstadoBusqueda('', '');
            return;
        }

        try {
            setLoading(true);
            const response = await fetch(endpointBuscar + '&' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (!response.ok || !data) {
                setEstadoBusqueda('error', 'No se pudo consultar la información en este momento.');
                return;
            }

            if (!data.encontrado) {
                setEstadoBusqueda('warn', data.mensaje || 'No existe coincidencias para esta persona. Puedes registrarla.');
                return;
            }

            aplicarPersona(data.persona || null);

            const faltaLider = !!(data.requiere_asignacion && data.requiere_asignacion.lider);
            const faltaMinisterio = !!(data.requiere_asignacion && data.requiere_asignacion.ministerio);
            if (faltaLider || faltaMinisterio) {
                setEstadoBusqueda('warn', data.mensaje || 'La persona no tiene líder y/o ministerio asignado. Debes completarlos antes de guardar.');
            } else {
                setEstadoBusqueda('info', data.mensaje || 'Persona encontrada y campos completados.');
            }
        } catch (error) {
            setEstadoBusqueda('error', 'Error al buscar coincidencias. Puedes continuar el registro manualmente.');
        } finally {
            setLoading(false);
        }
    }

    function programarBusqueda() {
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(buscarPersona, 450);
    }

    [nombre, telefono, cedula].forEach(function(input) {
        input.addEventListener('input', function() {
            if (input === telefono || input === cedula) {
                input.value = String(input.value || '').replace(/\D+/g, '');
            }
            if (input === nombre || input === lider) {
                toUpperCaseInput(input);
            }
            programarBusqueda();
        });

        input.addEventListener('blur', buscarPersona);
    });

    lider.addEventListener('input', function() {
        toUpperCaseInput(lider);
        idLider.value = '';
        buscarLideresReales();
    });

    lider.addEventListener('blur', function() {
        setTimeout(cerrarListaLideres, 180);
    });

    document.addEventListener('click', function(event) {
        if (!event.target.closest('.autocomplete-wrap')) {
            cerrarListaLideres();
        }
    });

    toUpperCaseInput(nombre);
    toUpperCaseInput(lider);

    form.addEventListener('submit', function(event) {
        const edadValor = parseInt(String(edad.value || '').trim(), 10);
        const telefonoValor = String(telefono.value || '').trim();
        const cedulaValor = String(cedula.value || '').trim();

        if (!Number.isFinite(edadValor) || edadValor < 7 || edadValor > 120) {
            event.preventDefault();
            alert('La edad es obligatoria y debe estar entre 7 y 120 anos.');
            edad.focus();
            return;
        }

        if (telefonoValor && !/^\d+$/.test(telefonoValor)) {
            event.preventDefault();
            alert('El telefono solo puede contener numeros.');
            telefono.focus();
            return;
        }

        if (telefonoValor && telefonoValor.length < 4) {
            event.preventDefault();
            alert('El telefono debe tener al menos 4 numeros.');
            telefono.focus();
            return;
        }

        if (cedulaValor && !/^\d+$/.test(cedulaValor)) {
            event.preventDefault();
            alert('La cedula solo puede contener numeros.');
            cedula.focus();
            return;
        }

        if (cedulaValor && cedulaValor.length < 4) {
            event.preventDefault();
            alert('La cedula debe tener al menos 4 numeros.');
            cedula.focus();
            return;
        }

        if (!telefonoValor || !cedulaValor) {
            event.preventDefault();
            alert('Debes registrar telefono y cedula.');
            if (!telefonoValor) {
                telefono.focus();
            } else {
                cedula.focus();
            }
        }
    });

    if (btnLimpiarForm) {
        btnLimpiarForm.addEventListener('click', function() {
            if (timer) {
                clearTimeout(timer);
                timer = null;
            }

            form.reset();
            nombre.value = '';
            genero.value = '';
            edad.value = '';
            telefono.value = '';
            cedula.value = '';
            lider.value = '';
            if (form.elements.programa) {
                form.elements.programa.value = '';
            }
            ministerio.value = '';
            idLider.value = '';
            cerrarListaLideres();
            setEstadoBusqueda('', '');
            setLoading(false);
            toUpperCaseInput(nombre);
            toUpperCaseInput(lider);
            nombre.focus();
            mostrarToast('Formulario limpiado');
        });
    }
})();
</script>
</body>
</html>
