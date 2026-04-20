<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>QR Registro Teens</h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>index.php?url=teen/registro-menores" class="btn btn-secondary">Volver a Teens</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=teen/consulta-codigo" target="_blank" class="btn btn-outline-secondary">Abrir consulta publica</a>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="text-align:center;">
        <h3 style="margin-top:0;">Escanea para registrar un nino</h3>
        <p style="color:#666; margin-bottom:18px;">Este QR abre el formulario publico para registro de menores en Teens.</p>

        <?php $qrRegistro = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . rawurlencode((string)$url_registro); ?>
        <img src="<?= htmlspecialchars($qrRegistro, ENT_QUOTES, 'UTF-8') ?>" alt="QR registro teens" style="max-width:320px; width:100%; height:auto; border:1px solid #ddd; border-radius:10px; padding:8px; background:#fff;">

        <div style="margin-top:15px; max-width:780px; margin-left:auto; margin-right:auto;">
            <label style="display:block; text-align:left; margin-bottom:4px; font-weight:600;">URL de registro</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars((string)$url_registro, ENT_QUOTES, 'UTF-8') ?>" readonly onclick="this.select()">
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h3 style="margin-top:0;">Consulta por codigo</h3>
        <p style="color:#666;">Comparte tambien este enlace para que puedan verificar a que nino pertenece un codigo.</p>

        <div style="max-width:780px; margin-bottom:15px;">
            <label style="display:block; margin-bottom:4px; font-weight:600;">URL de consulta</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars((string)$url_consulta, ENT_QUOTES, 'UTF-8') ?>" readonly onclick="this.select()">
        </div>

        <?php $qrConsulta = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode((string)$url_consulta); ?>
        <img src="<?= htmlspecialchars($qrConsulta, ENT_QUOTES, 'UTF-8') ?>" alt="QR consulta codigo" style="max-width:260px; width:100%; height:auto; border:1px solid #ddd; border-radius:10px; padding:8px; background:#fff;">
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
