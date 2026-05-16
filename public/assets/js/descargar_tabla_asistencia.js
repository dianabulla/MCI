// Script para descargar una tabla HTML como imagen usando html2canvas
// Requiere que html2canvas esté incluido en la página

document.addEventListener('DOMContentLoaded', function () {
    const btns = document.querySelectorAll('.btn-descargar-imagen-tabla');
    btns.forEach(btn => {
        btn.addEventListener('click', function () {
            const tablaId = btn.getAttribute('data-tabla-id');
            const tabla = document.getElementById(tablaId);
            if (!tabla) return;
            btn.disabled = true;
            btn.textContent = 'Generando imagen...';
            html2canvas(tabla, {
                backgroundColor: '#fff',
                scale: 2
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = tablaId + '_asistencias.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
                btn.disabled = false;
                btn.textContent = 'Descargar tabla como imagen';
            }).catch(() => {
                alert('No se pudo generar la imagen.');
                btn.disabled = false;
                btn.textContent = 'Descargar tabla como imagen';
            });
        });
    });
});
