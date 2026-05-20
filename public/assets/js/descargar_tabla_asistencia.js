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
 */
(function () {
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
        return partes.length ? partes.join(' · ') : 'Sin filtros aplicados';
    }

    function crearBloqueExportacion(sourceEl, titulo, subtitulo) {
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

    document.addEventListener('DOMContentLoaded', function () {
        if (typeof html2canvas !== 'function') {
            return;
        }

        const btns = document.querySelectorAll('.btn-descargar-imagen-tabla');
        const labelDefault = 'Descargar tabla como imagen';

        btns.forEach(function (btn) {
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
                if (formFiltrosId) {
                    subtitulo = resumenFiltrosDesdeForm(formFiltrosId);
                }

                const filas = sourceEl.querySelectorAll('tbody tr');
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

                const exportContainer = crearBloqueExportacion(sourceEl, titulo, subtitulo);
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
    });
})();
