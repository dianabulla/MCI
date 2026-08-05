/**
 * Descarga un bloque HTML (tabla o contenedor) como PNG usando html2canvas.
 * Requiere html2canvas en la página.
 *
 * Atributos del botón (.btn-descargar-imagen-tabla):
 * - data-tabla-id: id del elemento a capturar (tabla o .table-container)
 * - data-export-title: título en la imagen
 * - data-filename: prefijo del archivo (sin extensión)
 * - data-export-subtitle: texto fijo bajo el título (opcional)
 * - data-export-subtitle-from: id de un formulario para resumir filtros activos
 * - data-export-stats-from: id de un bloque (p. ej. contador de inscritos) a incluir en la imagen
 */
(function () {
    function filaVisible(row) {
        if (!row || String(row.nodeName || '').toUpperCase() !== 'TR') {
            return false;
        }
        if (row.hidden || row.getAttribute('aria-hidden') === 'true') {
            return false;
        }
        if (row.classList.contains('cap-inscrito-row--oculta') || row.classList.contains('is-report-row-hidden')) {
            return false;
        }
        const style = window.getComputedStyle(row);
        return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
    }

    function conservarSoloFilasVisibles(origen, clon) {
        const tablasOrigen = origen.matches('table') ? [origen] : Array.from(origen.querySelectorAll('table'));
        const tablasClon = clon.matches('table') ? [clon] : Array.from(clon.querySelectorAll('table'));

        tablasOrigen.forEach(function (tablaOrigen, idxTabla) {
            const tablaClon = tablasClon[idxTabla];
            if (!tablaClon) {
                return;
            }

            const bodiesOrigen = Array.from(tablaOrigen.querySelectorAll('tbody'));
            const bodiesClon = Array.from(tablaClon.querySelectorAll('tbody'));

            bodiesOrigen.forEach(function (tbodyOrigen, idxBody) {
                const tbodyClon = bodiesClon[idxBody];
                if (!tbodyClon) {
                    return;
                }

                const filasOrigen = Array.from(tbodyOrigen.querySelectorAll(':scope > tr'));
                const filasClon = Array.from(tbodyClon.querySelectorAll(':scope > tr'));

                filasOrigen.forEach(function (filaOrigen, idxFila) {
                    const filaClon = filasClon[idxFila];
                    if (!filaClon) {
                        return;
                    }
                    if (!filaVisible(filaOrigen)) {
                        filaClon.remove();
                    }
                });
            });
        });
    }

    function slugify(text) {
        return String(text || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'tabla';
    }

    function resumenFiltrosDesdeForm(formId) {
        const form = document.getElementById(formId);
        if (!form) {
            return '';
        }
        const partes = [];
        const ministerio = form.querySelector('[name="ministerio"]');
        const lider = form.querySelector('[name="lider"]');
        const buscar = form.querySelector('[name="buscar"]');
        if (ministerio && ministerio.value !== '') {
            const opt = ministerio.options[ministerio.selectedIndex];
            partes.push('Ministerio: ' + (opt ? opt.textContent.trim() : ministerio.value));
        }
        if (lider && lider.value !== '') {
            const opt = lider.options[lider.selectedIndex];
            partes.push('Líder: ' + (opt ? opt.textContent.trim() : lider.value));
        }
        if (buscar && String(buscar.value || '').trim() !== '') {
            partes.push('Búsqueda: ' + String(buscar.value).trim());
        }
        const genero = form.querySelector('[name="genero"]');
        if (genero && String(genero.value || '') !== '' && String(genero.value) !== 'todos') {
            const optGenero = genero.options[genero.selectedIndex];
            partes.push('Género: ' + (optGenero ? optGenero.textContent.trim() : genero.value));
        }
        const tabActivo = document.querySelector('.equipo-tab.is-active');
        if (tabActivo) {
            const tabClone = tabActivo.cloneNode(true);
            tabClone.querySelectorAll('span').forEach(function (span) {
                span.remove();
            });
            const tabLabel = String(tabClone.textContent || '').replace(/\s+/g, ' ').trim();
            if (tabLabel !== '') {
                partes.push('Pestaña: ' + tabLabel);
            }
        } else {
            const tabHidden = form.querySelector('[name="tab"]');
            if (tabHidden && String(tabHidden.value || '').trim() !== '') {
                partes.push('Pestaña: ' + String(tabHidden.value).trim());
            }
        }
        const resumenFilas = document.getElementById('resumenFiltrado');
        if (resumenFilas && String(resumenFilas.textContent || '').trim() !== '') {
            partes.push(String(resumenFilas.textContent).trim());
        }
        return partes.length ? partes.join(' · ') : 'Sin filtros aplicados';
    }

    function clonarBloqueEstadisticas(statsId) {
        const statsEl = statsId ? document.getElementById(statsId) : null;
        if (!statsEl) {
            return null;
        }

        const wrap = document.createElement('div');
        wrap.style.display = 'flex';
        wrap.style.alignItems = 'baseline';
        wrap.style.flexWrap = 'wrap';
        wrap.style.gap = '8px';
        wrap.style.margin = '0 0 14px 0';
        wrap.style.padding = '12px 14px';
        wrap.style.border = '1px solid #c8d9ef';
        wrap.style.borderRadius = '10px';
        wrap.style.background = 'linear-gradient(180deg, #f0f6ff 0%, #ffffff 100%)';

        const numEl = statsEl.querySelector('.cap-inscritos-contador-num');
        const num = document.createElement('span');
        num.style.fontSize = '32px';
        num.style.fontWeight = '800';
        num.style.lineHeight = '1';
        num.style.color = '#1e4a89';
        num.textContent = numEl ? String(numEl.textContent || '').trim() : '';

        const texto = document.createElement('span');
        texto.style.fontSize = '15px';
        texto.style.fontWeight = '600';
        texto.style.color = '#355d8b';
        const detalleEl = statsEl.querySelector('#cap-inscritos-contador-detalle, small');
        const detalleTxt = detalleEl ? String(detalleEl.textContent || '').trim() : '';
        texto.textContent = 'inscrito(s)' + (detalleTxt ? ' ' + detalleTxt : '');

        wrap.appendChild(num);
        wrap.appendChild(texto);

        return wrap;
    }

    function crearBloqueExportacion(sourceEl, titulo, subtitulo, statsId) {
        const exportContainer = document.createElement('div');
        const sourceWidth = Math.max(sourceEl.scrollWidth || 0, sourceEl.offsetWidth || 0, 720);
        const targetWidth = Math.min(1400, Math.max(720, sourceWidth + 32));

        exportContainer.style.position = 'fixed';
        exportContainer.style.left = '-10000px';
        exportContainer.style.top = '0';
        exportContainer.style.width = targetWidth + 'px';
        exportContainer.style.background = '#ffffff';
        exportContainer.style.padding = '16px';
        exportContainer.style.boxSizing = 'border-box';
        exportContainer.style.fontFamily = 'system-ui, -apple-system, Segoe UI, Roboto, sans-serif';

        if (titulo) {
            const h = document.createElement('div');
            h.style.fontSize = '20px';
            h.style.fontWeight = '700';
            h.style.color = '#1e293b';
            h.style.margin = '0 0 8px 0';
            h.textContent = titulo;
            exportContainer.appendChild(h);
        }

        const statsBlock = clonarBloqueEstadisticas(statsId);
        if (statsBlock) {
            exportContainer.appendChild(statsBlock);
        }

        if (subtitulo) {
            const sub = document.createElement('div');
            sub.style.fontSize = '13px';
            sub.style.color = '#64748b';
            sub.style.margin = '0 0 12px 0';
            sub.style.lineHeight = '1.45';
            sub.textContent = subtitulo;
            exportContainer.appendChild(sub);
        }

        const fecha = document.createElement('div');
        fecha.style.fontSize = '12px';
        fecha.style.color = '#94a3b8';
        fecha.style.margin = '0 0 12px 0';
        fecha.textContent = 'Generado: ' + new Date().toLocaleString('es-CO');
        exportContainer.appendChild(fecha);

        const cloned = sourceEl.cloneNode(true);
        cloned.style.overflow = 'visible';
        cloned.querySelectorAll('table').forEach(function (table) {
            table.style.width = '100%';
            table.style.minWidth = '0';
        });
        exportContainer.appendChild(cloned);

        return exportContainer;
    }

    function initDescargarTablaAsistencia() {
        if (typeof html2canvas !== 'function') {
            return;
        }

        const btns = document.querySelectorAll('.btn-descargar-imagen-tabla');
        const labelDefault = 'Descargar tabla como imagen';

        btns.forEach(function (btn) {
            if (btn.dataset.exportBound === '1') {
                return;
            }
            btn.dataset.exportBound = '1';

            const labelOriginal = (btn.getAttribute('data-label-default') || btn.textContent || labelDefault).trim();

            btn.addEventListener('click', async function () {
                const targetId = btn.getAttribute('data-tabla-id');
                const sourceEl = targetId ? document.getElementById(targetId) : null;
                if (!sourceEl) {
                    alert('No se encontró la tabla para exportar.');
                    return;
                }

                const titulo = btn.getAttribute('data-export-title') || 'Tabla';
                let subtitulo = btn.getAttribute('data-export-subtitle') || '';
                const formFiltrosId = btn.getAttribute('data-export-subtitle-from');
                const statsFromId = btn.getAttribute('data-export-stats-from') || '';
                if (formFiltrosId) {
                    subtitulo = resumenFiltrosDesdeForm(formFiltrosId);
                }

                const filas = Array.from(sourceEl.querySelectorAll('tbody tr')).filter(filaVisible);
                if (!filas.length) {
                    alert('No hay filas para exportar con los filtros actuales.');
                    return;
                }

                btn.disabled = true;
                const icon = btn.querySelector('i');
                if (icon) {
                    btn.innerHTML = '';
                    btn.appendChild(icon);
                    btn.appendChild(document.createTextNode(' Generando…'));
                } else {
                    btn.textContent = 'Generando imagen…';
                }

                const exportContainer = crearBloqueExportacion(sourceEl, titulo, subtitulo, statsFromId);
                const bloqueClonado = exportContainer.lastElementChild;
                if (bloqueClonado) {
                    conservarSoloFilasVisibles(sourceEl, bloqueClonado);
                }
                document.body.appendChild(exportContainer);

                try {
                    const canvas = await html2canvas(exportContainer, {
                        backgroundColor: '#ffffff',
                        scale: 2,
                        useCORS: true,
                        logging: false,
                        windowWidth: exportContainer.scrollWidth,
                        windowHeight: exportContainer.scrollHeight
                    });

                    const enlace = document.createElement('a');
                    const fecha = new Date().toISOString().slice(0, 10);
                    const prefijo = btn.getAttribute('data-filename') || slugify(titulo);
                    enlace.download = prefijo + '-' + fecha + '.png';
                    enlace.href = canvas.toDataURL('image/png');
                    enlace.click();
                } catch (err) {
                    console.error(err);
                    alert('No se pudo generar la imagen.');
                } finally {
                    document.body.removeChild(exportContainer);
                    btn.disabled = false;
                    if (icon) {
                        btn.innerHTML = '';
                        btn.appendChild(icon);
                        btn.appendChild(document.createTextNode(' ' + labelOriginal));
                    } else {
                        btn.textContent = labelOriginal;
                    }
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDescargarTablaAsistencia);
    } else {
        initDescargarTablaAsistencia();
    }
})();
