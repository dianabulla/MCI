<?php
$urlNotasSeguimiento = function_exists('public_app_url')
    ? public_app_url('personas/notasSeguimiento')
    : (rtrim((string)PUBLIC_URL, '/') . '/index.php?url=personas/notasSeguimiento');
$urlGuardarSeguimiento = function_exists('public_app_url')
    ? public_app_url('personas/guardarNotaSeguimiento')
    : (rtrim((string)PUBLIC_URL, '/') . '/index.php?url=personas/guardarNotaSeguimiento');
?>
<style>
.action-seguimiento-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    height: 27px;
    padding: 0 9px;
    border-radius: 8px;
    border: 1px solid #99f6e4;
    background: #ecfdf5;
    color: #0f766e;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
    cursor: pointer;
    text-decoration: none;
}
.action-seguimiento-btn i { font-size: 13px; }
.action-seguimiento-btn:hover { background: #ccfbf1; color: #115e59; }
.action-seguimiento-btn.is-active {
    background: #0f766e;
    border-color: #0f766e;
    color: #fff;
}
.ganar-table td.action-col { min-width: 210px; }
.seguimiento-modal-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 1200;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.seguimiento-modal-backdrop.is-open { display: flex; }
.seguimiento-modal {
    width: min(480px, 96vw);
    max-height: 86vh;
    overflow: auto;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 18px 50px rgba(15, 23, 42, 0.25);
}
.seguimiento-modal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 14px 16px 10px;
    border-bottom: 1px solid #e2e8f0;
}
.seguimiento-modal-head h3 { margin: 0; font-size: 1.02rem; color: #0f172a; }
.seguimiento-modal-close {
    border: 0;
    background: transparent;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    color: #64748b;
}
.seguimiento-modal-body { padding: 14px 16px 16px; }
.seguimiento-modal-meta { margin: 0 0 10px; color: #64748b; font-size: 0.86rem; }
.seguimiento-notas { display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px; max-height: 240px; overflow: auto; }
.seguimiento-nota {
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px 10px;
    background: #f8fafc;
}
.seguimiento-nota small { display: block; color: #64748b; margin-bottom: 4px; }
.seguimiento-nota p { margin: 0; color: #0f172a; white-space: pre-wrap; }
.seguimiento-vacio { color: #64748b; font-size: 0.9rem; margin: 0 0 12px; }
.seguimiento-modal-body textarea {
    width: 100%;
    min-height: 88px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px 10px;
    font: inherit;
    resize: vertical;
    box-sizing: border-box;
}
.seguimiento-modal-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 10px; }
.seguimiento-status { min-height: 18px; font-size: 0.82rem; margin-top: 6px; }
.seguimiento-status.error { color: #b42318; }
.seguimiento-status.ok { color: #157347; }
</style>

<div class="seguimiento-modal-backdrop" id="seguimientoModalBackdrop" aria-hidden="true">
    <div class="seguimiento-modal" role="dialog" aria-modal="true" aria-labelledby="seguimientoModalTitle">
        <div class="seguimiento-modal-head">
            <h3 id="seguimientoModalTitle">En seguimiento</h3>
            <button type="button" class="seguimiento-modal-close" id="seguimientoModalClose" aria-label="Cerrar">&times;</button>
        </div>
        <div class="seguimiento-modal-body">
            <p class="seguimiento-modal-meta" id="seguimientoModalMeta"></p>
            <div id="seguimientoNotas"></div>
            <form id="seguimientoForm" hidden>
                <label for="seguimientoNotaTexto">Nueva nota</label>
                <textarea id="seguimientoNotaTexto" maxlength="1000" placeholder="Escribe el seguimiento (llamada, visita, WhatsApp...)"></textarea>
                <div class="seguimiento-modal-actions">
                    <button type="submit" class="btn btn-primary" id="seguimientoGuardarBtn">Guardar nota</button>
                </div>
            </form>
            <p class="seguimiento-status" id="seguimientoStatus"></p>
        </div>
    </div>
</div>

<script>
(function () {
    const backdrop = document.getElementById('seguimientoModalBackdrop');
    if (!backdrop) return;
    const titleEl = document.getElementById('seguimientoModalTitle');
    const metaEl = document.getElementById('seguimientoModalMeta');
    const notasEl = document.getElementById('seguimientoNotas');
    const formEl = document.getElementById('seguimientoForm');
    const textoEl = document.getElementById('seguimientoNotaTexto');
    const statusEl = document.getElementById('seguimientoStatus');
    const closeEl = document.getElementById('seguimientoModalClose');
    const urlNotas = <?= json_encode($urlNotasSeguimiento, JSON_UNESCAPED_UNICODE) ?>;
    const urlGuardar = <?= json_encode($urlGuardarSeguimiento, JSON_UNESCAPED_UNICODE) ?>;
    let personaIdActiva = 0;
    let botonActivo = null;

    function esc(valor) {
        const div = document.createElement('div');
        div.textContent = valor == null ? '' : String(valor);
        return div.innerHTML;
    }

    function setStatus(msg, esError) {
        statusEl.textContent = msg || '';
        statusEl.classList.toggle('error', !!esError);
        statusEl.classList.toggle('ok', !esError && !!msg);
    }

    function pintarNotas(notas) {
        const lista = Array.isArray(notas) ? notas : [];
        if (!lista.length) {
            notasEl.innerHTML = '<p class="seguimiento-vacio">Aún no hay notas de seguimiento.</p>';
            return;
        }
        notasEl.innerHTML = '<div class="seguimiento-notas">' + lista.slice().reverse().map(function (nota) {
            const quien = [nota.usuario, nota.fecha].filter(Boolean).join(' · ');
            return '<div class="seguimiento-nota"><small>' + esc(quien) + '</small><p>' + esc(nota.texto || '') + '</p></div>';
        }).join('') + '</div>';
    }

    function cerrar() {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
        personaIdActiva = 0;
        botonActivo = null;
        if (textoEl) textoEl.value = '';
        setStatus('', false);
    }

    function abrir(btn) {
        personaIdActiva = parseInt(btn.getAttribute('data-persona-id') || '0', 10) || 0;
        botonActivo = btn;
        const nombre = btn.getAttribute('data-persona-nombre') || 'Persona';
        const telefono = btn.getAttribute('data-persona-telefono') || '';
        const puedeEditar = btn.getAttribute('data-puede-editar') === '1';
        titleEl.textContent = 'En seguimiento';
        metaEl.textContent = nombre + (telefono ? ' · ' + telefono : '');
        formEl.hidden = !puedeEditar;
        pintarNotas([]);
        setStatus('Cargando...', false);
        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
        fetch(urlNotas + (urlNotas.indexOf('?') >= 0 ? '&' : '?') + 'id=' + encodeURIComponent(String(personaIdActiva)), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'No se pudo cargar');
                }
                pintarNotas(data.notas || []);
                setStatus('', false);
                if (botonActivo) {
                    botonActivo.classList.toggle('is-active', !!data.en_seguimiento);
                }
            })
            .catch(function (err) {
                setStatus(err.message || 'No se pudo cargar el seguimiento', true);
            });
    }

    function bindSeguimientoBtn(btn) {
        if (!btn || btn.getAttribute('data-seg-bound') === '1') {
            return;
        }
        btn.setAttribute('data-seg-bound', '1');
        btn.addEventListener('click', function (ev) {
            ev.preventDefault();
            ev.stopPropagation();
            abrir(btn);
        });
    }

    window.inicializarBotonesSeguimiento = function (root) {
        (root || document).querySelectorAll('.js-seguimiento-btn').forEach(bindSeguimientoBtn);
    };
    window.inicializarBotonesSeguimiento(document);
    if (closeEl) closeEl.addEventListener('click', cerrar);
    backdrop.addEventListener('click', function (ev) {
        if (ev.target === backdrop) cerrar();
    });
    formEl.addEventListener('submit', function (ev) {
        ev.preventDefault();
        const nota = (textoEl.value || '').trim();
        if (!nota || personaIdActiva <= 0) {
            setStatus('Escribe una nota de seguimiento', true);
            return;
        }
        setStatus('Guardando...', false);
        fetch(urlGuardar, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'id_persona=' + encodeURIComponent(String(personaIdActiva)) + '&nota=' + encodeURIComponent(nota)
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data || !data.success) {
                    throw new Error((data && data.message) ? data.message : 'No se pudo guardar');
                }
                pintarNotas(data.notas || []);
                textoEl.value = '';
                setStatus('Seguimiento guardado', false);
                if (botonActivo) botonActivo.classList.add('is-active');
                if (document.getElementById('listaPorConectar') || document.querySelector('.ganar-table')) {
                    window.location.reload();
                }
            })
            .catch(function (err) {
                setStatus(err.message || 'No se pudo guardar', true);
            });
    });
})();
</script>
