/**
 * Filtros client-side para Reportes → Células (tablas de seguimiento, estado y resumen por red).
 * Se carga aparte del script inline para que un fallo en gráficos no bloquee los filtros.
 */
(function () {
    'use strict';

    const bodySeguimiento = document.getElementById('tablaSeguimientoCelulasBody');
    if (!bodySeguimiento) {
        return;
    }

    const normFiltro = (valor) => String(valor || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();

    const normRedFiltro = (valor) => {
        const base = normFiltro(valor);
        if (base === 'jovenes' || base === 'j??venes' || base === 'j?venes') {
            return 'jovenes';
        }
        return base;
    };

    const establecerFilaVisible = (fila, visible) => {
        if (!fila) {
            return;
        }
        fila.classList.toggle('is-report-row-hidden', !visible);
        fila.hidden = !visible;
        if (visible) {
            fila.style.removeProperty('display');
        } else {
            fila.style.display = 'none';
        }
    };

    const filtroMinisterioSeguimiento = document.getElementById('reporteCelulasFiltroMinisterioSeguimiento');
    const filtroRedSeguimiento = document.getElementById('reporteCelulasFiltroRedSeguimiento');
    const filtroColorSeguimiento = document.getElementById('reporteCelulasFiltroColorSeguimiento');
    const filaEmptyFiltroSeguimiento = bodySeguimiento.querySelector('tr[data-row-type="empty-filtro"]');
    const filtroMinisterioResumenRed = document.getElementById('reporteCelulasFiltroMinisterioResumenRed');
    const filtroLiderResumenRed = document.getElementById('reporteCelulasFiltroLiderResumenRed');
    const filtroRedResumenRed = document.getElementById('reporteCelulasFiltroRedResumenRed');
    const bodyResumenRed = document.getElementById('tablaResumenLideresPorRedBody');
    const filtroMinisterioEstado = document.getElementById('reporteCelulasFiltroMinisterioEstado');
    const filtroRedEstado = document.getElementById('reporteCelulasFiltroRedEstado');
    const filtroEstadoReporte = document.getElementById('reporteCelulasFiltroEstadoReporte');
    const bodyEstado = document.getElementById('tablaEstadoSemanalCelulasBody');
    const estadoConteoSi = document.getElementById('estadoSemanalConteoSi');
    const estadoConteoNo = document.getElementById('estadoSemanalConteoNo');

    const datosLideresRed = (typeof window.tablaLideresPorRedTipoData !== 'undefined' && Array.isArray(window.tablaLideresPorRedTipoData))
        ? window.tablaLideresPorRedTipoData
        : [];

    const escaparHtml = (valor) => String(valor || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    const normalizarNombreRedUi = (valor) => {
        const txt = String(valor || '').trim();
        if (txt === '') {
            return 'Sin red';
        }
        const lower = txt.toLowerCase();
        if (lower === 'j??venes' || lower === 'j?venes' || lower === 'jã³venes' || lower === 'jóvenes') {
            return 'Jóvenes';
        }
        return txt;
    };

    const cumpleFiltroColorSeguimiento = (fila) => {
        const semaforo = String(fila.dataset.semaforo || 'verde').trim();
        const semanas = parseInt(fila.dataset.semanas || '0', 10) || 0;
        const filtro = String((filtroColorSeguimiento && filtroColorSeguimiento.value) || 'seguimiento').trim();

        switch (filtro) {
            case 'rojo':
                return semaforo === 'rojo';
            case 'naranja':
                return semaforo === 'naranja';
            case 'verde':
                return semaforo === 'verde';
            case 'todos':
                return true;
            case 'seguimiento':
            default:
                return semanas >= 2;
        }
    };

    const aplicarFiltrosSeguimiento = () => {
        const ministerioSeleccionado = normFiltro((filtroMinisterioSeguimiento && filtroMinisterioSeguimiento.value) || '');
        const redSeleccionada = normRedFiltro((filtroRedSeguimiento && filtroRedSeguimiento.value) || '');

        let visibles = 0;
        bodySeguimiento.querySelectorAll('tr[data-row-type="dato"]').forEach((fila) => {
            const ministerioFila = normFiltro(fila.dataset.ministerio || '');
            const redFila = normRedFiltro(fila.dataset.red || '');
            const visible = (ministerioSeleccionado === '' || ministerioFila === ministerioSeleccionado)
                && (redSeleccionada === '' || redFila === redSeleccionada)
                && cumpleFiltroColorSeguimiento(fila);
            establecerFilaVisible(fila, visible);
            if (visible) {
                visibles += 1;
            }
        });

        if (filaEmptyFiltroSeguimiento) {
            const mostrarEmpty = visibles === 0
                && bodySeguimiento.querySelectorAll('tr[data-row-type="dato"]').length > 0;
            establecerFilaVisible(filaEmptyFiltroSeguimiento, mostrarEmpty);
        }
    };

    const aplicarFiltrosEstado = () => {
        if (!bodyEstado) {
            return;
        }

        const ministerioSeleccionado = normFiltro((filtroMinisterioEstado && filtroMinisterioEstado.value) || '');
        const redSeleccionada = normRedFiltro((filtroRedEstado && filtroRedEstado.value) || '');
        const estadoSeleccionado = String((filtroEstadoReporte && filtroEstadoReporte.value) || 'todos').trim();

        let conteoSi = 0;
        let conteoNo = 0;

        bodyEstado.querySelectorAll('tr[data-row-type="dato"]').forEach((fila) => {
            const ministerioFila = normFiltro(fila.dataset.ministerio || '');
            const redFila = normRedFiltro(fila.dataset.red || '');
            const reportoFila = String(fila.dataset.reporto || '').trim();
            const visible = (ministerioSeleccionado === '' || ministerioFila === ministerioSeleccionado)
                && (redSeleccionada === '' || redFila === redSeleccionada)
                && (estadoSeleccionado === 'todos' || reportoFila === estadoSeleccionado);
            establecerFilaVisible(fila, visible);

            if (visible) {
                if (reportoFila === 'si') {
                    conteoSi += 1;
                } else if (reportoFila === 'no') {
                    conteoNo += 1;
                }
            }
        });

        bodyEstado.querySelectorAll('tr[data-row-type="grupo"]').forEach((filaGrupo) => {
            const grupo = String(filaGrupo.dataset.group || '');
            const totalGrupo = grupo === 'si' ? conteoSi : conteoNo;
            establecerFilaVisible(filaGrupo, totalGrupo > 0);
            if (totalGrupo > 0) {
                filaGrupo.innerHTML = '<td colspan="5">' + (grupo === 'si' ? 'Sí reportaron' : 'No reportaron') + ' (' + totalGrupo + ')</td>';
            }
        });

        if (estadoConteoSi) {
            estadoConteoSi.textContent = String(conteoSi);
        }
        if (estadoConteoNo) {
            estadoConteoNo.textContent = String(conteoNo);
        }
    };

    const aplicarFiltrosResumenRed = () => {
        if (!bodyResumenRed) {
            return;
        }

        const ministerioSeleccionado = normFiltro((filtroMinisterioResumenRed && filtroMinisterioResumenRed.value) || '');
        const liderSeleccionado = normFiltro((filtroLiderResumenRed && filtroLiderResumenRed.value) || '');
        const redSeleccionada = normRedFiltro((filtroRedResumenRed && filtroRedResumenRed.value) || '');

        const rowsFiltradas = datosLideresRed.filter((item) => {
            const ministerio = normFiltro(item.ministerio || 'Sin ministerio') || 'sin ministerio';
            const lider = normFiltro(item.lider || 'Sin líder') || 'sin lider';
            const red = normRedFiltro(item.red || 'Sin red') || 'sin red';

            return (ministerioSeleccionado === '' || ministerio === ministerioSeleccionado)
                && (liderSeleccionado === '' || lider === liderSeleccionado)
                && (redSeleccionada === '' || red === redSeleccionada);
        });

        const resumenPorRed = {};
        rowsFiltradas.forEach((item) => {
            const red = String(item.red || 'Sin red').trim() || 'Sin red';
            const lider = String(item.lider || 'Sin líder').trim() || 'Sin líder';

            if (!resumenPorRed[red]) {
                resumenPorRed[red] = {
                    red: red,
                    total_celulas: 0,
                    jovenes: {},
                    kids: {},
                    sin_clasificar: {}
                };
            }

            const cantJovenes = (parseInt(item.celulas_jovenes || 0, 10) || 0) + (parseInt(item.celulas_rocas || 0, 10) || 0);
            const cantKids = parseInt(item.celulas_kids || 0, 10) || 0;
            const cantSinClasificar = parseInt(item.celulas_sin_clasificar || 0, 10) || 0;
            const total = parseInt(item.total_celulas || 0, 10) || 0;

            resumenPorRed[red].total_celulas += total;

            if (cantJovenes > 0) {
                resumenPorRed[red].jovenes[lider] = (resumenPorRed[red].jovenes[lider] || 0) + cantJovenes;
            }
            if (cantKids > 0) {
                resumenPorRed[red].kids[lider] = (resumenPorRed[red].kids[lider] || 0) + cantKids;
            }
            if (cantSinClasificar > 0) {
                resumenPorRed[red].sin_clasificar[lider] = (resumenPorRed[red].sin_clasificar[lider] || 0) + cantSinClasificar;
            }
        });

        const redKeys = Object.keys(resumenPorRed).sort((a, b) => normalizarNombreRedUi(a).localeCompare(normalizarNombreRedUi(b)));

        if (!redKeys.length) {
            bodyResumenRed.innerHTML = '<tr><td colspan="3" class="text-center">Sin datos en el resumen por red para estos filtros.</td></tr>';
            return;
        }

        const ordenarLista = (obj) => Object.keys(obj)
            .map((lider) => ({ lider: lider, cantidad: parseInt(obj[lider] || 0, 10) || 0 }))
            .sort((a, b) => {
                const cmp = b.cantidad - a.cantidad;
                return cmp !== 0 ? cmp : a.lider.localeCompare(b.lider);
            });

        bodyResumenRed.innerHTML = redKeys.map((redKey) => {
            const fila = resumenPorRed[redKey];
            const listaJovenes = ordenarLista(fila.jovenes);
            const listaKids = ordenarLista(fila.kids);
            const listaSinClasificar = ordenarLista(fila.sin_clasificar);

            const renderGrupo = (titulo, items) => {
                if (!items.length) {
                    return '';
                }
                const lis = items.map((it) => '<li>' + escaparHtml(it.lider) + ' <strong>(' + it.cantidad + ')</strong></li>').join('');
                return '<div class="report-red-group"><span class="report-red-group__title">' + escaparHtml(titulo) + '</span><ul class="report-inline-list">' + lis + '</ul></div>';
            };

            const gruposHtml = [
                renderGrupo('Jóvenes (incluye rocas)', listaJovenes),
                renderGrupo('Kids', listaKids),
                renderGrupo('Sin clasificar', listaSinClasificar)
            ].join('');

            return '<tr>'
                + '<td><strong>' + escaparHtml(normalizarNombreRedUi(fila.red)) + '</strong></td>'
                + '<td>' + (gruposHtml !== '' ? '<div class="report-red-groups">' + gruposHtml + '</div>' : '<span style="color:#6b7280;">Sin líderes</span>') + '</td>'
                + '<td><strong>' + parseInt(fila.total_celulas || 0, 10) + '</strong></td>'
                + '</tr>';
        }).join('');
    };

    window.aplicarFiltrosTablasCelulas = function () {
        aplicarFiltrosSeguimiento();
        aplicarFiltrosEstado();
        aplicarFiltrosResumenRed();
    };

    const contenedor = document.getElementById('reportesVisualContainer');
    if (contenedor) {
        contenedor.addEventListener('change', (evento) => {
            const objetivo = evento.target;
            if (!objetivo || String(objetivo.tagName || '').toUpperCase() !== 'SELECT') {
                return;
            }
            const id = String(objetivo.id || '');
            if (id === 'reporteCelulasFiltroMinisterioSeguimiento' || id === 'reporteCelulasFiltroRedSeguimiento' || id === 'reporteCelulasFiltroColorSeguimiento') {
                aplicarFiltrosSeguimiento();
            } else if (id === 'reporteCelulasFiltroMinisterioEstado' || id === 'reporteCelulasFiltroRedEstado' || id === 'reporteCelulasFiltroEstadoReporte') {
                aplicarFiltrosEstado();
            } else if (id === 'reporteCelulasFiltroMinisterioResumenRed' || id === 'reporteCelulasFiltroLiderResumenRed' || id === 'reporteCelulasFiltroRedResumenRed') {
                aplicarFiltrosResumenRed();
            }
        });
    }

    window.aplicarFiltrosTablasCelulas();

    if (typeof window.instalarBotonesDescargaTablas === 'function') {
        window.instalarBotonesDescargaTablas();
    }
})();
