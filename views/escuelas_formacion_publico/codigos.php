<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Codigos QR Escuelas de Formacion - MCI Madrid</title>
    <style>
        :root {
            --primary: #0a6e6a;
            --primary-soft: #e8f6f4;
            --text-main: #2f3b3a;
            --text-title: #1e2d2b;
            --border: #d1e6e3;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #f1f8f7 0%, #e6f1ef 100%);
            color: var(--text-main);
            min-height: 100vh;
            padding: 20px 12px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 14px 30px rgba(15, 77, 74, 0.15);
        }

        .header {
            padding: 24px 22px;
            background: linear-gradient(135deg, var(--primary-soft) 0%, #ffffff 100%);
            border-bottom: 1px solid var(--border);
        }

        .eyebrow {
            margin: 0 0 8px;
            color: var(--primary);
            font-weight: 700;
            letter-spacing: 0.3px;
            font-size: 14px;
        }

        h1 {
            margin: 0;
            color: var(--text-title);
            font-size: 30px;
            line-height: 1.2;
        }

        .sub {
            margin: 10px 0 0;
            color: var(--text-main);
            font-size: 15px;
        }

        .body {
            padding: 22px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
        }

        .card {
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px;
            background: #ffffff;
        }

        .card h2 {
            margin: 0 0 8px;
            font-size: 20px;
            color: var(--text-title);
        }

        .card p {
            margin: 0 0 12px;
            color: #4e5f5d;
            font-size: 14px;
        }

        .url {
            width: 100%;
            border: 1px solid #cfe3e0;
            border-radius: 8px;
            padding: 9px 10px;
            font-size: 14px;
            color: #203432;
        }

        .qr-wrap {
            margin-top: 12px;
            text-align: center;
        }

        .qr-wrap img {
            width: 260px;
            max-width: 100%;
            height: auto;
            border: 1px solid #dbe8e6;
            border-radius: 10px;
            padding: 8px;
            background: #fff;
        }

        .actions {
            margin-top: 18px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            background: var(--primary);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
        }

        .btn.secondary {
            background: #2f4f4c;
        }

        .btn.download {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #1a5276;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            margin-top: 10px;
            border: none;
            cursor: pointer;
        }

        .btn.download:hover {
            background: #154360;
        }

        .btn.download svg {
            flex-shrink: 0;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <p class="eyebrow">Escuelas de Formacion</p>
        <h1>Codigos QR Publicos</h1>
        <p class="sub">Comparte estos QR para abrir directamente Registro y Asistencia desde el celular.</p>
    </div>

    <div class="body">
        <div class="grid">
            <div class="card">
                <h2>Formulario de Registro</h2>
                <p>Inscripcion publica de personas en Escuelas de Formacion.</p>
                <?php $qrRegistro = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . rawurlencode((string)$url_registro); ?>
                <input class="url" type="text" readonly value="<?= htmlspecialchars((string)$url_registro, ENT_QUOTES, 'UTF-8') ?>" onclick="this.select()">
                <div class="qr-wrap">
                    <img id="qr-img-registro" src="<?= htmlspecialchars($qrRegistro, ENT_QUOTES, 'UTF-8') ?>" alt="QR formulario registro escuelas">
                </div>
                <div style="text-align:center;">
                    <a class="btn download js-descargar-qr"
                       href="<?= htmlspecialchars($qrRegistro . '&format=png', ENT_QUOTES, 'UTF-8') ?>"
                       download="qr_registro_escuelas.png"
                       data-filename="qr_registro_escuelas.png">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                        Descargar QR
                    </a>
                </div>
            </div>

            <div class="card">
                <h2>Formulario de Asistencia</h2>
                <p>Registro publico de asistencia para Escuelas de Formacion.</p>
                <?php $qrAsistencia = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . rawurlencode((string)$url_asistencia); ?>
                <input class="url" type="text" readonly value="<?= htmlspecialchars((string)$url_asistencia, ENT_QUOTES, 'UTF-8') ?>" onclick="this.select()">
                <div class="qr-wrap">
                    <img id="qr-img-asistencia" src="<?= htmlspecialchars($qrAsistencia, ENT_QUOTES, 'UTF-8') ?>" alt="QR formulario asistencia escuelas">
                </div>
                <div style="text-align:center;">
                    <a class="btn download js-descargar-qr"
                       href="<?= htmlspecialchars($qrAsistencia . '&format=png', ENT_QUOTES, 'UTF-8') ?>"
                       download="qr_asistencia_escuelas.png"
                       data-filename="qr_asistencia_escuelas.png">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/></svg>
                        Descargar QR
                    </a>
                </div>
            </div>
        </div>

        <div class="actions">
            <a class="btn" href="<?= PUBLIC_URL ?>?url=escuelas_formacion/registro-publico" target="_blank" rel="noopener">Abrir Registro</a>
            <a class="btn secondary" href="<?= PUBLIC_URL ?>?url=escuelas_formacion/asistencia-publica" target="_blank" rel="noopener">Abrir Asistencia</a>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.js-descargar-qr').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        var url = this.getAttribute('href');
        var filename = this.getAttribute('data-filename') || 'qr.png';

        // Descarga via fetch+blob para forzar el dialogo save-as aunque sea cross-origin
        e.preventDefault();
        fetch(url)
            .then(function(res) { return res.blob(); })
            .then(function(blob) {
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(function() { URL.revokeObjectURL(a.href); }, 5000);
            })
            .catch(function() {
                // Fallback: abrir en nueva ventana si fetch falla por CORS
                window.open(url, '_blank');
            });
    });
});
</script>
</body>
</html>
