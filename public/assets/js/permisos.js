(function () {
    const cfgGlobal = window.PERMISOS_CONFIG || {};
    const soloLectura = !!cfgGlobal.soloLectura;

    function getEndpoint() {
        const cfg = window.PERMISOS_CONFIG || {};
        return cfg.endpoint || 'index.php?url=permisos/actualizar';
    }

    function getEndpointLimpieza() {
        const cfg = window.PERMISOS_CONFIG || {};
        return cfg.endpointLimpieza || 'index.php?url=permisos/limpiar-obsoletos';
    }

    async function parseJsonResponse(response) {
        const txt = await response.text();
        try {
            return JSON.parse(txt);
        } catch (e) {
            const snippet = txt.trim().substring(0, 80).replace(/\s+/g, ' ');
            throw new Error(
                'El servidor no devolvió JSON (¿sesión expirada o URL incorrecta?). ' + snippet
            );
        }
    }

    let toastTimer = null;
    let grupoAbierto = '';

    function showToast(msg, tipo) {
        const el = document.getElementById('perm-toast');
        const msgEl = document.getElementById('perm-toast-msg');
        if (!el || !msgEl) return;
        msgEl.textContent = msg;
        el.classList.toggle('error', tipo === 'error');
        el.style.display = 'flex';
        el.style.opacity = '1';
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            el.style.opacity = '0';
            setTimeout(() => {
                el.style.display = 'none';
                el.style.opacity = '1';
            }, 300);
        }, 2500);
    }

    function getPanelActivo() {
        return document.querySelector('.perm-panel.active');
    }

    function getIdRolActivo() {
        const sel = document.getElementById('perm-rol-select');
        return sel ? String(sel.value) : '';
    }

    function actualizarBadgeSelect() {
        const idRol = getIdRolActivo();
        const panel = document.getElementById('rol-' + idRol);
        if (!panel) return;
        const total = panel.querySelectorAll('.permiso-check[data-campo="puede_ver"]').length;
        const activos = panel.querySelectorAll('.permiso-check[data-campo="puede_ver"]:checked').length;
        const sel = document.getElementById('perm-rol-select');
        if (sel && sel.selectedOptions[0]) {
            const nombre = sel.selectedOptions[0].dataset.nombre || sel.selectedOptions[0].textContent;
            sel.selectedOptions[0].textContent = nombre.trim() + ' (' + activos + '/' + total + ' con acceso)';
        }
    }

    function syncPillVisual(cb) {
        const pill = cb.closest('.perm-pill, .perm-adv-chip');
        if (pill) {
            pill.classList.toggle('is-on', cb.checked);
        }
        const card = cb.closest('.perm-submodule') || cb.closest('.perm-card');
        if (card) {
            const any = card.querySelectorAll('.permiso-check:checked').length > 0;
            card.classList.toggle('perm-submodule--on', any);
            card.classList.toggle('perm-card--on', any);
            refrescarCandados(card);
        }
    }

    function syncPermisoCheckboxes(rol, modulo, campo, checked) {
        const esc = (s) => String(s).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
        const selector = `.permiso-check[data-rol="${esc(rol)}"][data-modulo="${esc(modulo)}"][data-campo="${esc(campo)}"]`;
        document.querySelectorAll(selector).forEach((cbSync) => {
            cbSync.checked = !!checked;
            syncPillVisual(cbSync);
        });
    }

    function guardarPermiso(cb, onError) {
        if (soloLectura) {
            return Promise.resolve();
        }
        const { rol, modulo, campo } = cb.dataset;
        const valor = cb.checked ? 1 : 0;

        return fetch(getEndpoint(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: `id_rol=${encodeURIComponent(rol)}&modulo=${encodeURIComponent(modulo)}&campo=${encodeURIComponent(campo)}&valor=${encodeURIComponent(valor)}`,
        })
            .then((r) => parseJsonResponse(r))
            .then((data) => {
                if (!data.success) throw new Error(data.error || 'Error desconocido');
                syncPermisoCheckboxes(rol, modulo, campo, cb.checked);
                actualizarBadgeSelect();
                filtrarVista();
                showToast('Guardado');
            })
            .catch((err) => {
                cb.checked = !cb.checked;
                syncPillVisual(cb);
                showToast('Error: ' + err.message, 'error');
                if (onError) onError();
            });
    }

    function verDelModulo(card, modulo) {
        if (!card) return null;
        return card.querySelector('.permiso-check[data-campo="puede_ver"][data-modulo="' + modulo + '"]:not([data-requiere-ver]):not([data-parte])');
    }

    function aplicarReglaVer(cb) {
        const card = cb.closest('.perm-submodule') || cb.closest('.perm-card');
        const tocados = [];
        if (!card) return tocados;

        const moduloPadre = cb.dataset.padre || cb.dataset.modulo;
        const ver = verDelModulo(card, moduloPadre);
        if (!ver || ver === cb) {
            if (cb.dataset.campo === 'puede_ver' && !cb.dataset.padre && !cb.checked) {
                card.querySelectorAll('.permiso-check[data-requiere-ver="1"]').forEach((otro) => {
                    const mismo = !otro.dataset.padre || otro.dataset.padre === cb.dataset.modulo;
                    if (mismo && otro.checked) {
                        otro.checked = false;
                        tocados.push(otro);
                    }
                });
            }
            return tocados;
        }

        if (cb.dataset.requiereVer === '1' && cb.checked && !ver.checked) {
            ver.checked = true;
            tocados.push(ver);
        }
        return tocados;
    }

    function aplicarPresetEnChecks(cbs, preset) {
        cbs.forEach((cb) => {
            const campo = cb.dataset.campo || '';
            if (preset === 'activar' || preset === 'full') {
                cb.checked = true;
            } else if (preset === 'desactivar' || preset === 'none') {
                cb.checked = false;
            } else if (preset === 'solo_ver') {
                cb.checked = campo === 'puede_ver';
            }
        });
    }

    function guardarListaChecks(lista) {
        const promesas = lista.map((cb) => {
            const { rol, modulo, campo } = cb.dataset;
            const valor = cb.checked ? 1 : 0;
            return fetch(getEndpoint(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                body: `id_rol=${encodeURIComponent(rol)}&modulo=${encodeURIComponent(modulo)}&campo=${encodeURIComponent(campo)}&valor=${encodeURIComponent(valor)}`,
            })
                .then((r) => parseJsonResponse(r))
                .then((data) => {
                    if (!data || !data.success) throw new Error(data?.error || 'Error al guardar');
                });
        });

        return Promise.all(promesas).then(() => {
            lista.forEach((cb) => {
                syncPermisoCheckboxes(cb.dataset.rol, cb.dataset.modulo, cb.dataset.campo, cb.checked);
                syncPillVisual(cb);
            });
            actualizarBadgeSelect();
            filtrarVista();
            showToast('Permisos actualizados');
        });
    }

    function ejecutarMasivo(accion, scopeElement) {
        if (soloLectura) {
            return;
        }
        const panel = getPanelActivo();
        if (!panel || panel.dataset.rolProtegido === '1') return;

        const labels = { activar: 'Activar este menú', desactivar: 'Quitar este menú', solo_ver: 'Dejar este menú en solo lectura' };
        if (!confirm('¿' + (labels[accion] || accion) + ' en esta selección?')) return;

        const root = scopeElement || panel;
        const cbs = root.querySelectorAll('.permiso-check');
        const lista = [];
        cbs.forEach((cb) => {
            aplicarPresetEnChecks([cb], accion);
            lista.push(cb);
        });

        cbs.forEach((cb) => { cb.disabled = true; });
        guardarListaChecks(lista)
            .catch((err) => showToast('Error: ' + err.message, 'error'))
            .finally(() => cbs.forEach((cb) => { cb.disabled = false; }));
    }

    document.querySelectorAll('.permiso-check').forEach((cb) => {
        syncPillVisual(cb);
        if (soloLectura) {
            cb.disabled = true;
        }
        cb.addEventListener('change', function () {
            if (soloLectura) {
                return;
            }
            const extra = aplicarReglaVer(this);
            syncPillVisual(this);
            extra.forEach((otro) => syncPillVisual(otro));
            const lista = [this].concat(extra);
            if (lista.length === 1) {
                guardarPermiso(this);
                return;
            }
            lista.forEach((otro) => { otro.disabled = true; });
            guardarListaChecks(lista)
                .catch((err) => showToast('Error: ' + err.message, 'error'))
                .finally(() => lista.forEach((otro) => { otro.disabled = false; }));
        });
    });

    const rolSelect = document.getElementById('perm-rol-select');
    const bulkBar = document.getElementById('perm-toolbar-bulk');
    const protegidoBadge = document.getElementById('perm-rol-badge');

    function cambiarRol(idRol) {
        document.querySelectorAll('.perm-panel').forEach((p) => p.classList.remove('active'));
        const panel = document.getElementById('rol-' + idRol);
        if (panel) panel.classList.add('active');

        const protegido = panel && panel.dataset.rolProtegido === '1';
        if (bulkBar) bulkBar.hidden = !!protegido;
        if (protegidoBadge) protegidoBadge.hidden = !protegido;

        document.querySelectorAll('.btn-perm-all').forEach((btn) => {
            btn.dataset.rol = idRol;
        });

        construirIndice();
        filtrarVista();
        actualizarBadgeSelect();
    }

    if (rolSelect) {
        rolSelect.addEventListener('change', function () {
            cambiarRol(this.value);
        });
        cambiarRol(rolSelect.value);
    }

    document.querySelectorAll('.btn-perm-all').forEach((btn) => {
        btn.addEventListener('click', function () {
            const panel = getPanelActivo();
            let scope = panel;
            if (panel && !panel.classList.contains('perm-panel--buscando')) {
                scope = panel.querySelector('.perm-family.is-open') || panel;
            }
            ejecutarMasivo(this.dataset.accion, scope);
        });
    });

    document.querySelectorAll('.perm-grupo-btn').forEach((btn) => {
        btn.addEventListener('click', function () {
            const scope = this.closest('.perm-family') || this.closest('.perm-section');
            if (scope) ejecutarMasivo(this.dataset.accion, scope);
        });
    });

    document.querySelectorAll('.perm-card__quick-btn').forEach((btn) => {
        btn.addEventListener('click', function () {
            const card = this.closest('.perm-submodule') || this.closest('.perm-card');
            if (!card) return;
            const cbs = card.querySelectorAll('.permiso-check');
            const lista = [];
            cbs.forEach((cb) => {
                aplicarPresetEnChecks([cb], this.dataset.preset);
                lista.push(cb);
            });
            cbs.forEach((cb) => { cb.disabled = true; });
            guardarListaChecks(lista)
                .catch((err) => showToast('Error: ' + err.message, 'error'))
                .finally(() => cbs.forEach((cb) => { cb.disabled = false; }));
        });
    });

    function escaparGrupo(nombre) {
        if (window.CSS && typeof CSS.escape === 'function') {
            return CSS.escape(nombre);
        }
        return String(nombre).replace(/"/g, '\\"');
    }

    function refrescarCandados(card) {
        if (!card) return;
        const ver = verDelModulo(card, card.dataset.moduleKey || '');
        const abierto = !ver || ver.checked;
        card.querySelectorAll('[data-requiere-ver="1"]').forEach((cb) => {
            const host = cb.closest('.perm-pill, .perm-adv-chip');
            if (host) host.classList.toggle('is-locked', !abierto);
        });
    }

    function construirIndice() {
        const nav = document.getElementById('perm-indice');
        const panel = getPanelActivo();
        if (!nav || !panel) return;
        const familias = panel.querySelectorAll('.perm-family');
        nav.innerHTML = '';
        familias.forEach((fam) => {
            const nombre = fam.dataset.grupo || '';
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'perm-indice__btn';
            btn.dataset.grupo = nombre;
            btn.append(document.createTextNode(nombre + ' '));
            const count = document.createElement('span');
            count.className = 'perm-indice__count';
            count.textContent = '0';
            btn.append(count);
            btn.addEventListener('click', function () {
                abrirGrupo(nombre);
            });
            nav.appendChild(btn);
        });
        const existe = grupoAbierto && panel.querySelector('.perm-family[data-grupo="' + escaparGrupo(grupoAbierto) + '"]');
        if (!existe) {
            const primeraActiva = Array.from(familias).find((fam) => fam.querySelector('.perm-submodule--on'));
            grupoAbierto = (primeraActiva || familias[0]) ? (primeraActiva || familias[0]).dataset.grupo || '' : '';
        }
    }

    function abrirGrupo(nombre) {
        grupoAbierto = nombre;
        const input = document.getElementById('perm-search');
        if (input) input.value = '';
        const panel = getPanelActivo();
        if (panel) panel.classList.remove('perm-panel--buscando');
        filtrarVista();
    }

    function aplicarGrupoAbierto() {
        const panel = getPanelActivo();
        if (!panel || panel.classList.contains('perm-panel--buscando')) return;
        panel.querySelectorAll('.perm-family').forEach((fam) => {
            fam.hidden = false;
            fam.classList.toggle('is-open', fam.dataset.grupo === grupoAbierto);
        });
    }

    function actualizarIndice() {
        const panel = getPanelActivo();
        if (!panel) return;
        document.querySelectorAll('.perm-indice__btn').forEach((btn) => {
            const fam = panel.querySelector('.perm-family[data-grupo="' + escaparGrupo(btn.dataset.grupo || '') + '"]');
            const n = fam ? fam.querySelectorAll('.perm-submodule--on').length : 0;
            const count = btn.querySelector('.perm-indice__count');
            if (count) count.textContent = String(n);
            btn.classList.toggle('is-empty', n === 0);
            btn.classList.toggle('is-current', btn.dataset.grupo === grupoAbierto && !panel.classList.contains('perm-panel--buscando'));
        });
    }

    function filtrarVista() {
        const panel = getPanelActivo();
        const input = document.getElementById('perm-search');
        const solo = document.getElementById('perm-solo-activo');
        if (!panel) return;

        const term = String(input ? input.value : '').toLowerCase().trim();
        const soloActivo = !solo || solo.checked;
        const shell = document.querySelector('.perm-shell');
        if (shell) shell.classList.toggle('perm-shell--solo', soloActivo);
        const buscando = term !== '';
        panel.classList.toggle('perm-panel--buscando', buscando);

        let visibles = 0;
        panel.querySelectorAll('.perm-submodule').forEach((card) => {
            const textoOk = term === '' || String(card.dataset.search || '').includes(term);
            const activo = card.querySelector('.permiso-check:checked') !== null;
            card.classList.toggle('perm-submodule--on', activo);
            card.hidden = !textoOk || (soloActivo && !activo);
            if (!card.hidden) visibles++;
        });

        if (buscando) {
            panel.querySelectorAll('.perm-family').forEach((fam) => {
                const hay = fam.querySelector('.perm-submodule:not([hidden])');
                fam.hidden = !hay;
                fam.classList.toggle('is-open', !!hay);
            });
        } else {
            aplicarGrupoAbierto();
        }

        const emptyMsg = panel.querySelector('.perm-empty-search');
        if (emptyMsg) emptyMsg.hidden = !buscando || visibles > 0;
        actualizarIndice();
    }

    function filtrarBusqueda() {
        filtrarVista();
    }

    const searchInput = document.getElementById('perm-search');
    if (searchInput) {
        searchInput.addEventListener('input', filtrarVista);
    }

    const soloActivoInput = document.getElementById('perm-solo-activo');
    if (soloActivoInput) {
        soloActivoInput.addEventListener('change', filtrarVista);
    }

    const btnLimpiar = document.getElementById('btnLimpiarModulosObsoletos');
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function () {
            if (!confirm('¿Eliminar módulos obsoletos de la tabla permisos?')) return;
            btnLimpiar.disabled = true;
            fetch(getEndpointLimpieza(), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                body: 'confirm=1',
            })
                .then((r) => parseJsonResponse(r))
                .then((data) => {
                    if (!data.success) throw new Error(data.error || 'No se pudo limpiar');
                    showToast('Limpieza: ' + String(data.deleted_rows || 0) + ' filas');
                    setTimeout(() => window.location.reload(), 500);
                })
                .catch((err) => {
                    btnLimpiar.disabled = false;
                    showToast('Error: ' + err.message, 'error');
                });
        });
    }
})();
