<?php
$urlDetallePersonaBase = rtrim((string)PUBLIC_URL, '/') . '?url=personas/detalle&id=';
$puedeVerPersonaSegModal = AuthController::esAdministrador() || AuthController::puede('personas:ver') || AuthController::puede('celulas:ver');
$puedeUsarSeguimientoModal = AuthController::puedeUsarSeguimientoPersonaListado();
$puedeEditarSeguimientoModal = AuthController::puedeGuardarSeguimientoPersonaListado();
?>
<div id="modal-lista-seguimiento" class="seguimiento-lista-backdrop" hidden>
    <div class="seguimiento-lista-modal" role="dialog" aria-modal="true" aria-labelledby="modal-lista-seguimiento-titulo">
        <div class="seguimiento-lista-head">
            <h3 id="modal-lista-seguimiento-titulo">En seguimiento</h3>
            <button type="button" class="seguimiento-lista-close js-cerrar-lista-seguimiento" aria-label="Cerrar">&times;</button>
        </div>
        <div class="seguimiento-lista-body" id="modal-lista-seguimiento-cuerpo"></div>
    </div>
</div>
<style>
.celulas-action-btn--seguimiento {
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    background: #ecfdf5;
    border-color: #99f6e4;
    color: #0f766e;
    font-size: 0.75rem;
    font-weight: 700;
}
.celulas-action-btn--seguimiento:hover {
    background: #ccfbf1;
    color: #115e59;
}
.celulas-seguimiento-badge {
    display: inline-flex;
    min-width: 18px;
    height: 18px;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: #0f766e;
    color: #fff;
    font-size: 10px;
    padding: 0 5px;
    line-height: 1;
}
.seguimiento-lista-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 1180;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.seguimiento-lista-backdrop.is-open {
    display: flex;
}
.seguimiento-lista-modal {
    width: min(720px, 96vw);
    max-height: 86vh;
    overflow: auto;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 18px 50px rgba(15, 23, 42, 0.25);
}
.seguimiento-lista-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 14px 16px 10px;
    border-bottom: 1px solid #e2e8f0;
    position: sticky;
    top: 0;
    background: #fff;
}
.seguimiento-lista-head h3 { margin: 0; font-size: 1.02rem; color: #0f172a; }
.seguimiento-lista-close {
    border: 0;
    background: transparent;
    font-size: 22px;
    line-height: 1;
    cursor: pointer;
    color: #64748b;
}
.seguimiento-lista-body { padding: 12px 16px 16px; }
.seguimiento-lista-vacio { margin: 8px 0; color: #64748b; }
.seguimiento-lista-table { width: 100%; border-collapse: collapse; }
.seguimiento-lista-table th,
.seguimiento-lista-table td {
    padding: 8px 10px;
    border-bottom: 1px solid #eef2f7;
    text-align: left;
    font-size: 0.875rem;
}
</style>
<script>
(function () {
    const backdrop = document.getElementById('modal-lista-seguimiento');
    const tituloEl = document.getElementById('modal-lista-seguimiento-titulo');
    const cuerpoEl = document.getElementById('modal-lista-seguimiento-cuerpo');
    if (!backdrop || !tituloEl || !cuerpoEl) {
        return;
    }

    const urlDetalle = <?= json_encode($urlDetallePersonaBase, JSON_UNESCAPED_UNICODE) ?>;
    const puedeVer = <?= !empty($puedeVerPersonaSegModal) ? 'true' : 'false' ?>;
    const puedeUsarSeg = <?= !empty($puedeUsarSeguimientoModal) ? 'true' : 'false' ?>;
    const puedeEditarSeg = <?= !empty($puedeEditarSeguimientoModal) ? 'true' : 'false' ?>;

    function esc(texto) {
        return String(texto == null ? '' : texto)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function cerrar() {
        backdrop.classList.remove('is-open');
        backdrop.hidden = true;
    }

    function abrir(titulo, personas, mostrarCelula) {
        tituloEl.textContent = titulo || 'En seguimiento';
        const lista = Array.isArray(personas) ? personas : [];
        if (!lista.length) {
            cuerpoEl.innerHTML = '<p class="seguimiento-lista-vacio">No hay personas en seguimiento en esta célula.</p>';
        } else {
            let html = '<div class="table-container" style="margin:0;"><table class="seguimiento-lista-table"><thead><tr>';
            html += '<th>Nombre</th><th>Teléfono</th>';
            if (mostrarCelula) {
                html += '<th>Célula</th>';
            }
            html += '<th>Líder</th>';
            if (puedeVer || puedeUsarSeg) {
                html += '<th>Acción</th>';
            }
            html += '</tr></thead><tbody>';
            lista.forEach(function (persona) {
                const id = parseInt(persona.id || persona.Id_Persona || 0, 10) || 0;
                const nombre = persona.nombre || '';
                const telefono = persona.telefono || '';
                html += '<tr><td><strong>' + esc(nombre) + '</strong></td><td>' + esc(telefono) + '</td>';
                if (mostrarCelula) {
                    html += '<td>' + esc(persona.celula || '') + '</td>';
                }
                html += '<td>' + esc(persona.lider || '') + '</td>';
                if (puedeVer || puedeUsarSeg) {
                    html += '<td><div class="celulas-actions-row">';
                    if (puedeUsarSeg && id > 0) {
                        html += '<button type="button" class="action-seguimiento-btn js-seguimiento-btn is-active"'
                            + ' data-persona-id="' + id + '"'
                            + ' data-persona-nombre="' + esc(nombre) + '"'
                            + ' data-persona-telefono="' + esc(telefono) + '"'
                            + ' data-puede-editar="' + (puedeEditarSeg ? '1' : '0') + '"'
                            + ' title="En seguimiento"><i class="bi bi-signpost-2" aria-hidden="true"></i><span>Notas</span></button>';
                    }
                    if (puedeVer && id > 0) {
                        html += '<a class="btn btn-sm celulas-action-btn celulas-action-btn--icon" href="' + urlDetalle + id + '" title="Ver persona"><i class="bi bi-eye" aria-hidden="true"></i></a>';
                    }
                    html += '</div></td>';
                }
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            cuerpoEl.innerHTML = html;
            if (typeof window.inicializarBotonesSeguimiento === 'function') {
                window.inicializarBotonesSeguimiento(cuerpoEl);
            }
        }
        backdrop.hidden = false;
        backdrop.classList.add('is-open');
    }

    document.querySelectorAll('.js-ver-seguimiento-celula').forEach(function (btn) {
        btn.addEventListener('click', function () {
            let personas = [];
            try {
                personas = JSON.parse(btn.getAttribute('data-personas') || '[]');
            } catch (e) {
                personas = [];
            }
            abrir(
                btn.getAttribute('data-titulo') || 'En seguimiento',
                personas,
                btn.getAttribute('data-mostrar-celula') === '1'
            );
        });
    });

    backdrop.querySelectorAll('.js-cerrar-lista-seguimiento').forEach(function (btn) {
        btn.addEventListener('click', cerrar);
    });
    backdrop.addEventListener('click', function (ev) {
        if (ev.target === backdrop) {
            cerrar();
        }
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && backdrop.classList.contains('is-open')) {
            cerrar();
        }
    });
})();
</script>
