(function (global) {
    'use strict';

    function getExportNode() {
        return document.getElementById('ticket-pago-export');
    }

    function leerTicketData() {
        const el = document.getElementById('ticket-pago-data');
        if (!el) {
            return null;
        }
        try {
            return JSON.parse(el.textContent || '');
        } catch (e) {
            return null;
        }
    }

    function getReferencia() {
        const data = leerTicketData();
        if (data && data.referencia_pago) {
            return String(data.referencia_pago).replace(/[^\w-]+/g, '_');
        }
        const node = getExportNode();
        if (!node) {
            return 'ticket';
        }
        const ref = node.querySelector('.ticket-pago-export__ref');
        const text = ref ? String(ref.textContent || '').trim() : '';
        return text !== '' ? text.replace(/[^\w-]+/g, '_') : 'ticket';
    }

    function canvasToBlob(canvas) {
        return new Promise(function (resolve, reject) {
            if (canvas.toBlob) {
                canvas.toBlob(function (blob) {
                    if (blob) {
                        resolve(blob);
                        return;
                    }
                    reject(new Error('No se pudo crear la imagen del ticket.'));
                }, 'image/png', 1);
                return;
            }
            try {
                const dataUrl = canvas.toDataURL('image/png');
                const parts = dataUrl.split(',');
                const bin = atob(parts[1]);
                const arr = new Uint8Array(bin.length);
                for (let i = 0; i < bin.length; i++) {
                    arr[i] = bin.charCodeAt(i);
                }
                resolve(new Blob([arr], { type: 'image/png' }));
            } catch (err) {
                reject(err);
            }
        });
    }

    function dibujarLinea(ctx, label, value, y, width) {
        ctx.fillStyle = '#64748b';
        ctx.font = '13px Arial, sans-serif';
        ctx.fillText(label, 24, y);
        ctx.fillStyle = '#1e293b';
        ctx.font = 'bold 13px Arial, sans-serif';
        const val = value || '—';
        const textWidth = ctx.measureText(val).width;
        ctx.fillText(val, width - 24 - textWidth, y);
    }

    function tituloMciDeData(data) {
        if (data && data.titulo_mci) {
            return String(data.titulo_mci).trim();
        }
        const formulario = data && data.formulario ? String(data.formulario).trim() : '';
        const origen = data && data.origen ? String(data.origen).toLowerCase() : '';
        if (origen === 'taller') {
            return formulario ? ('MCI Taller ' + formulario) : 'MCI Taller';
        }
        return formulario ? ('MCI ' + formulario) : 'MCI';
    }

    function telefonoWhatsappDeData(data) {
        if (!data) {
            return '';
        }
        let digits = String(data.telefono || '').replace(/\D+/g, '');
        if (digits.length === 10 && digits.charAt(0) === '3') {
            digits = '57' + digits;
        }
        if (digits.length >= 11 && digits.length <= 15) {
            return digits;
        }
        return '';
    }

    function dibujarTicketEnCanvas(data) {
        const width = 400;
        const height = 580;
        const scale = 2;
        const canvas = document.createElement('canvas');
        canvas.width = width * scale;
        canvas.height = height * scale;
        const ctx = canvas.getContext('2d');
        if (!ctx) {
            throw new Error('No se pudo preparar el lienzo para el ticket.');
        }
        ctx.scale(scale, scale);

        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, width, height);
        if (typeof ctx.roundRect === 'function') {
            ctx.beginPath();
            ctx.roundRect(0, 0, width, height, 18);
            ctx.clip();
        }

        const headerH = 118;
        const grad = ctx.createLinearGradient(0, 0, width, headerH);
        grad.addColorStop(0, '#0a6e6a');
        grad.addColorStop(0.55, '#0f766e');
        grad.addColorStop(1, '#134e4a');
        ctx.fillStyle = grad;
        ctx.fillRect(0, 0, width, headerH);

        ctx.fillStyle = 'rgba(255,255,255,0.85)';
        ctx.font = 'bold 11px Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('MCI MADRID COLOMBIA', width / 2, 28);

        const titulo = tituloMciDeData(data);
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 20px Arial, sans-serif';
        const palabras = titulo.split(' ');
        let linea = '';
        let yTitulo = 56;
        palabras.forEach(function (palabra, idx) {
            const prueba = linea ? (linea + ' ' + palabra) : palabra;
            if (ctx.measureText(prueba).width > width - 40 && linea) {
                ctx.fillText(linea, width / 2, yTitulo);
                linea = palabra;
                yTitulo += 24;
            } else {
                linea = prueba;
            }
            if (idx === palabras.length - 1) {
                ctx.fillText(linea, width / 2, yTitulo);
            }
        });
        ctx.font = 'bold 11px Arial, sans-serif';
        ctx.fillText('TICKET DE PAGO', width / 2, headerH - 16);
        ctx.textAlign = 'left';

        let y = headerH + 28;
        dibujarLinea(ctx, 'Fecha', data.fecha || '', y, width);
        y += 22;
        dibujarLinea(ctx, data.etiqueta_nombre || 'Inscrito', data.nombre, y, width);
        y += 22;
        dibujarLinea(ctx, 'Documento', data.documento, y, width);
        y += 22;
        if (data.acudiente) {
            dibujarLinea(ctx, 'Acudiente', data.acudiente, y, width);
            y += 22;
        }

        y += 6;
        ctx.strokeStyle = '#dbeafe';
        ctx.beginPath();
        ctx.moveTo(24, y);
        ctx.lineTo(width - 24, y);
        ctx.stroke();
        y += 18;

        dibujarLinea(ctx, 'Método', data.metodo_pago, y, width);
        y += 22;
        dibujarLinea(ctx, 'Recibido por', data.recibido_por, y, width);
        y += 22;
        dibujarLinea(ctx, 'Tipo', data.tipo_pago, y, width);
        y += 22;
        dibujarLinea(ctx, 'Valor', '$' + (data.valor_pago || '0'), y, width);
        y += 22;
        if (data.entrego_material === 1 || data.entrego_material === '1' || data.entrego_material === true) {
            dibujarLinea(ctx, 'Material', 'Sí', y, width);
            y += 22;
        } else if (data.entrego_material === 0 || data.entrego_material === '0') {
            dibujarLinea(ctx, 'Material', 'No', y, width);
            y += 22;
        }

        y += 8;
        ctx.fillStyle = '#64748b';
        ctx.font = '12px Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('Referencia', width / 2, y);
        ctx.fillStyle = '#0a6e6a';
        ctx.font = 'bold 32px "Courier New", monospace';
        ctx.fillText(data.referencia_pago || '—', width / 2, y + 36);
        ctx.textAlign = 'left';
        y += 56;

        ctx.strokeStyle = '#cbd5e1';
        ctx.setLineDash([4, 4]);
        ctx.beginPath();
        ctx.moveTo(24, y);
        ctx.lineTo(width - 24, y);
        ctx.stroke();
        ctx.setLineDash([]);
        y += 16;
        ctx.fillStyle = '#64748b';
        ctx.font = '11px Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(data.pie_comprobante || ('Gracias por tu ofrenda · ' + titulo), width / 2, y);
        ctx.textAlign = 'left';

        return canvas;
    }

    async function capturarConHtml2canvas(node) {
        const wrap = document.createElement('div');
        wrap.setAttribute('aria-hidden', 'true');
        wrap.style.cssText = 'position:fixed;left:0;top:0;z-index:-1;width:420px;background:#fff;pointer-events:none;';

        const clone = node.cloneNode(true);
        clone.style.margin = '0';
        clone.style.position = 'static';
        clone.style.left = 'auto';
        clone.style.top = 'auto';
        clone.id = 'ticket-pago-export-clone';
        wrap.appendChild(clone);
        document.body.appendChild(wrap);

        try {
            const canvas = await html2canvas(clone, {
                backgroundColor: '#ffffff',
                scale: Math.min(2, window.devicePixelRatio || 2),
                useCORS: true,
                logging: false,
                width: clone.offsetWidth || 400,
                height: clone.offsetHeight || 480,
            });
            return canvasToBlob(canvas);
        } finally {
            document.body.removeChild(wrap);
        }
    }

    async function generarTicketPng() {
        const node = getExportNode();
        if (node && typeof html2canvas === 'function') {
            try {
                return await capturarConHtml2canvas(node);
            } catch (e) {
                console.warn('html2canvas falló, usando dibujo alternativo:', e);
            }
        }

        const data = leerTicketData();
        if (data) {
            const canvas = dibujarTicketEnCanvas(data);
            return canvasToBlob(canvas);
        }

        if (typeof html2canvas !== 'function') {
            throw new Error('No se pudo cargar el generador de imagen. Recargue la página.');
        }
        throw new Error('No se encontró el ticket para exportar.');
    }

    function setTicketData(data) {
        let el = document.getElementById('ticket-pago-data');
        if (!el) {
            el = document.createElement('script');
            el.type = 'application/json';
            el.id = 'ticket-pago-data';
            document.body.appendChild(el);
        }
        el.textContent = JSON.stringify(data || {});
    }

    function descargarBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const enlace = document.createElement('a');
        enlace.href = url;
        enlace.download = filename;
        enlace.style.display = 'none';
        document.body.appendChild(enlace);
        enlace.click();
        setTimeout(function () {
            document.body.removeChild(enlace);
            URL.revokeObjectURL(url);
        }, 500);
    }

    async function descargarTicketImagen() {
        const blob = await generarTicketPng();
        descargarBlob(blob, 'ticket-' + getReferencia() + '.png');
        return blob;
    }

    async function copiarImagenPortapapeles(blob) {
        if (!navigator.clipboard || !window.ClipboardItem) {
            return false;
        }
        try {
            await navigator.clipboard.write([new ClipboardItem({ 'image/png': blob })]);
            return true;
        } catch (e) {
            return false;
        }
    }

    function abrirWhatsappWeb(data, texto) {
        const phone = telefonoWhatsappDeData(data);
        const encoded = encodeURIComponent(texto);
        const url = phone
            ? ('https://web.whatsapp.com/send?phone=' + phone + '&text=' + encoded)
            : 'https://web.whatsapp.com/';
        window.open(url, '_blank', 'noopener,noreferrer');
    }

    async function compartirTicketWhatsapp() {
        const blob = await generarTicketPng();
        const data = leerTicketData() || {};
        const referencia = getReferencia();
        const filename = 'ticket-' + referencia + '.png';
        const titulo = tituloMciDeData(data);
        const texto = titulo + ' — Ticket de pago. Referencia: ' + (data.referencia_pago || referencia);

        const copiado = await copiarImagenPortapapeles(blob);
        descargarBlob(blob, filename);
        abrirWhatsappWeb(data, texto);

        const estado = document.getElementById('ticket-wa-estado');
        const aviso = copiado
            ? 'Se abrió WhatsApp Web. La imagen quedó copiada: péguela en el chat con Ctrl+V (o adjunte el archivo descargado).'
            : 'Se abrió WhatsApp Web. Adjunte en el chat la imagen del ticket que se acaba de descargar.';
        if (estado) {
            estado.textContent = aviso;
        } else {
            alert(aviso);
        }
    }

    async function mostrarVistaPrevia() {
        const preview = document.getElementById('ticket-pago-preview');
        if (!preview) {
            return;
        }
        try {
            const blob = await generarTicketPng();
            const url = URL.createObjectURL(blob);
            preview.src = url;
            preview.style.display = 'block';
            preview.onload = function () {
                URL.revokeObjectURL(url);
            };
        } catch (e) {
            console.warn('Vista previa ticket:', e);
        }
    }

    function enlazarBotones() {
        const btnDescargar = document.getElementById('btn-ticket-descargar');
        const btnWhatsapp = document.getElementById('btn-ticket-whatsapp');

        if (btnDescargar && btnDescargar.dataset.ticketBound !== '1') {
            btnDescargar.dataset.ticketBound = '1';
            btnDescargar.addEventListener('click', function () {
                btnDescargar.disabled = true;
                descargarTicketImagen()
                    .catch(function (err) { alert(err.message || 'Error al descargar'); })
                    .finally(function () { btnDescargar.disabled = false; });
            });
        }

        if (btnWhatsapp && btnWhatsapp.dataset.ticketBound !== '1') {
            btnWhatsapp.dataset.ticketBound = '1';
            btnWhatsapp.addEventListener('click', function () {
                btnWhatsapp.disabled = true;
                compartirTicketWhatsapp()
                    .catch(function (err) { alert(err.message || 'Error al compartir'); })
                    .finally(function () { btnWhatsapp.disabled = false; });
            });
        }

        if (btnDescargar || btnWhatsapp) {
            mostrarVistaPrevia();
        }
    }

    function iniciar() {
        enlazarBotones();
    }

    global.TallerTicketPago = {
        generarTicketPng: generarTicketPng,
        descargarTicketImagen: descargarTicketImagen,
        compartirTicketWhatsapp: compartirTicketWhatsapp,
        setTicketData: setTicketData,
        mostrarVistaPrevia: mostrarVistaPrevia,
        iniciar: iniciar,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})(window);
