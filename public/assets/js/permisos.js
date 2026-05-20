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
                showToast('Guardado');
            })
            .catch((err) => {
                cb.checked = !cb.checked;
                syncPillVisual(cb);
                showToast('Error: ' + err.message, 'error');
                if (onError) onError();
            });
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
            showToast('Permisos actualizados');
        });
    }

    function ejecutarMasivo(accion, scopeElement) {
        if (soloLectura) {
            return;
        }
        const panel = getPanelActivo();
        if (!panel || panel.dataset.rolProtegido === '1') return;

        const labels = { activar: 'Activar todo', desactivar: 'Quitar todo', solo_ver: 'Dejar solo lectura' };
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
            syncPillVisual(this);
            guardarPermiso(this);
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

        filtrarBusqueda();
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
            ejecutarMasivo(this.dataset.accion, getPanelActivo());
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

    function filtrarBusqueda() {
        const panel = getPanelActivo();
        const input = document.getElementById('perm-search');
        if (!panel || !input) return;

        const term = String(input.value || '').toLowerCase().trim();
        let visibles = 0;

        panel.querySelectorAll('.perm-submodule, .perm-card').forEach((card) => {
            const hay = term === '' || String(card.dataset.search || '').includes(term);
            card.hidden = !hay;
            if (hay) visibles++;
        });

        panel.querySelectorAll('.perm-family, .perm-section').forEach((section) => {
            const cardsVis = section.querySelectorAll('.perm-submodule:not([hidden]), .perm-card:not([hidden])');
            section.hidden = cardsVis.length === 0 && term !== '';
        });

        const emptyMsg = panel.querySelector('.perm-empty-search');
        if (emptyMsg) emptyMsg.hidden = visibles > 0 || term === '';
    }

    const searchInput = document.getElementById('perm-search');
    if (searchInput) {
        searchInput.addEventListener('input', filtrarBusqueda);
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
