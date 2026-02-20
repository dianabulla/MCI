<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nehemias - Firmes por la patria</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --nehemias-blue: #0b4aa2;
            --nehemias-blue-dark: #083677;
            --nehemias-orange: #f37021;
            --nehemias-orange-dark: #d85f12;
        }
        body {
            background: var(--nehemias-blue);
            min-height: 100vh;
            padding: 30px 15px;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: #1a1a1a;
        }
        .nehemias-container {
            max-width: 780px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        .nehemias-header {
            background: var(--nehemias-orange);
            color: #fff;
            padding: 0;
            text-align: left;
        }
        .nehemias-banner {
            width: 100%;
            max-width: 100%;
            display: block;
            margin: 0 auto;
            border-bottom: 4px solid rgba(255, 255, 255, 0.3);
        }
        .nehemias-header-content {
            padding: 26px 30px 32px 30px;
        }
        .nehemias-header h1 {
            font-size: 28px;
            margin: 0 0 10px 0;
            font-weight: 700;
        }
        .nehemias-header p {
            margin: 0;
            font-size: 15px;
            opacity: 0.95;
        }
        .nehemias-body {
            padding: 30px;
        }
        .form-label {
            font-weight: 600;
        }
        .required {
            color: #c1121f;
        }
        .privacy {
            background: #f6f8fb;
            border: 1px solid #d8e0f0;
            border-radius: 12px;
            padding: 16px;
            font-size: 13px;
            line-height: 1.5;
        }
        .btn-primary {
            background: var(--nehemias-orange);
            border-color: var(--nehemias-orange);
            font-weight: 600;
        }
        .btn-primary:hover {
            background: var(--nehemias-orange-dark);
            border-color: var(--nehemias-orange-dark);
        }
        
        /* Responsive para móviles */
        @media (max-width: 576px) {
            body {
                padding: 15px 10px;
            }
            .nehemias-container {
                border-radius: 12px;
            }
            .nehemias-header h1 {
                font-size: 24px;
            }
            .nehemias-header p {
                font-size: 14px;
            }
            .nehemias-header-content {
                padding: 20px 20px 24px 20px;
            }
            .nehemias-body {
                padding: 20px;
            }
            .form-label {
                font-size: 15px;
            }
            .form-control, .form-select {
                font-size: 16px;
                padding: 12px;
            }
            .privacy {
                font-size: 12px;
                padding: 14px;
            }
            .btn-primary {
                font-size: 17px;
                padding: 14px;
                border-radius: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="nehemias-container">
        <div class="nehemias-header">
            <img class="nehemias-banner" src="<?= ASSETS_URL ?>/img/nehemias.png" alt="Nehemias">
            <div class="nehemias-header-content">
                <h1>¡Firmes por la patria!</h1>
                <p>Desde Salvemos a Colombia queremos poner en el centro las necesidades reales de los colombianos que quieren un país con oportunidades, con salud, con seguridad y sin corrupción. En este formulario puedes depositar los datos de tus 12 amigos o familiares a los cuales estás motivando para votar por Sara Castellanos y Yancly Escobar. Recuerda que el 8 de marzo debemos marcar el número 100 de Salvación Nacional en el tarjetón de Senado, y el número 104 de Salvación Nacional en el tarjetón de la Cámara por Cundinamarca.</p>
            </div>
        </div>
        <div class="nehemias-body">
            <?php if (isset($mensaje) && !empty($mensaje)): ?>
                <div class="alert alert-<?= $tipo_mensaje === 'success' ? 'success' : 'danger' ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($registro_exitoso) || !$registro_exitoso): ?>
            <form method="POST" action="?url=nehemias/guardar" id="formNehemias">
                <div class="mb-3">
                    <label class="form-label">NOMBRES <span class="required">*</span></label>
                    <input type="text" name="nombres" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">APELLIDOS <span class="required">*</span></label>
                    <input type="text" name="apellidos" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">NUMERO DE CEDULA <span class="required">*</span></label>
                    <input type="text" name="numero_cedula" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">TELEFONO <span class="required">*</span></label>
                    <input type="text" name="telefono" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">LIDER <span class="required">*</span></label>
                    <select name="lider" class="form-select" required>
                        <option value="">Seleccione un ministerio</option>
                        <?php foreach (($ministerios ?? []) as $ministerio): ?>
                            <option value="<?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?>">
                                <?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">LIDER NEHEMIAS <span class="required">*</span></label>
                    <input type="text" name="lider_nehemias" class="form-control" required>
                </div>

                <div class="privacy mb-3">
                    De conformidad con la Ley 1581 de 2012, el diligenciamiento de este formulario constituye una manifestacion inequivoca que permite concluir que la persona firmante del presente formulario otorga autorizacion previa, expresa e informada a SARA CASTELLANOS y YANCLY ESCOBAR para el tratamiento de sus datos personales aqui diligenciados con las finalidades, terminos y derechos expuestos en el Aviso de Privacidad disponible en saracastellanos.com.
                    <br><br>
                    El titular podra, en cualquier momento, solicitar que la informacion sea modificada, actualizada o retirada de las bases de datos. Para mayor informacion podra consultar nuestra Politica de Tratamiento de Datos en: <a href="https://saracastellanos.com/politica-de-tratamientos-de-datos-personales/" target="_blank" rel="noopener noreferrer">https://saracastellanos.com/politica-de-tratamientos-de-datos-personales/</a>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="acepta" value="1" id="acepta" required>
                    <label class="form-check-label" for="acepta">
                        Aceptar <span class="required">*</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-100">Enviar formulario</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
