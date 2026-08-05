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

    function dibujarTicketEnCanvas(data) {
        const width = 400;
        const height = 520;
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
        ctx.strokeStyle = '#0a6e6a';
        ctx.lineWidth = 2;
        if (typeof ctx.roundRect === 'function') {
            ctx.beginPath();
            ctx.roundRect(8, 8, width - 16, height - 16, 14);
            ctx.stroke();
        } else {
            ctx.strokeRect(8, 8, width - 16, height - 16);
        }

        let y = 36;
        ctx.fillStyle = '#0a6e6a';
        ctx.font = 'bold 11px Arial, sans-serif';
        ctx.fillText('MCI MADRID COLOMBIA', 24, y);
        y += 22;
        ctx.fillStyle = '#0f172a';
        ctx.font = 'bold 20px Arial, sans-serif';
        ctx.fillText('Ticket de pago', 24, y);
        y += 28;

        if (data.formulario) {
            dibujarLinea(ctx, 'Formulario', data.formulario, y, width);
            y += 22;
        }
        dibujarLinea(ctx, 'Fecha', data.fecha || '', y, width);
        y += 22;
        dibujarLinea(ctx, 'Niño(a)', data.nombre, y, width);
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
        y += 28;

        ctx.fillStyle = '#64748b';
        ctx.font = '12px Arial, sans-serif';
        ctx.fillText('Referencia', 24, y);
        y += 8;
        ctx.fillStyle = '#0a6e6a';
        ctx.font = 'bold 32px "Courier New", monospace';
        ctx.fillText(data.referencia_pago || '—', 24, y + 28);
        y += 48;

        ctx.strokeStyle = '#cbd5e1';
        ctx.setLineDash([4, 4]);
        ctx.beginPath();
        ctx.moveTo(24, y);
        ctx.lineTo(width - 24, y);
        ctx.stroke();
        ctx.setLineDash([]);
        y += 16;
        ctx.fillStyle = '#94a3b8';
        ctx.font = '11px Arial, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText('Comprobante de pago — Presentación de niños', width / 2, y);
        ctx.textAlign = 'left';

        return canvas;
    }

    async function capturarConHtml2canvas(node) {
        const wrap = document.createElement('div');
        wrap.setAttribute('aria-hidden', 'true');
        wrap.style.cssText = 'position:fixed;left:0;top:0;z-index:-1;width:420px;background:#fff;pointer-events:none;';

        const clone = node.cloneNode(true);
        clone.style.margin = '0';
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

    async function compartirTicketWhatsapp() {
        const blob = await generarTicketPng();
        const referencia = getReferencia();
        const filename = 'ticket-' + referencia + '.png';
        const file = new File([blob], filename, { type: 'image/png' });
        const shareData = {
            files: [file],
            title: 'Ticket de pago MCI Madrid',
            text: 'Ticket de pago — Referencia: ' + referencia,
        };

        if (navigator.share && navigator.canShare && navigator.canShare(shareData)) {
            try {
                await navigator.share(shareData);
                return;
            } catch (err) {
                if (err && err.name === 'AbortError') {
                    return;
                }
            }
        }

        descargarBlob(blob, filename);
        alert('Se descargó la imagen del ticket.\n\n1. Abre WhatsApp\n2. Elige el contacto\n3. Adjunta la imagen descargada');
        window.open('https://wa.me/', '_blank', 'noopener,noreferrer');
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
        iniciar: iniciar,
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }
})(window);
