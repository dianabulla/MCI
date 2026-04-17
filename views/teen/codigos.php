<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Códigos teens-kids</h2>
    <div class="page-actions personas-mobile-stack" style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>index.php?url=teen" class="btn btn-nav-pill">Material Teens</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=teen/registro-menores" class="btn btn-nav-pill">Registro teens-kids</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=teen/codigos" class="btn btn-nav-pill active">Códigos</a>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <h3 style="margin-top:0;">Formulario público</h3>
        <p style="color:#666; margin-bottom:12px;">Comparte este código para registrar teens-kids.</p>
        <?php $qrRegistro = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . rawurlencode((string)$url_registro); ?>
        <input type="text" class="form-control" readonly value="<?= htmlspecialchars((string)$url_registro, ENT_QUOTES, 'UTF-8') ?>" onclick="this.select()">
        <div style="margin-top:12px;">
            <img src="<?= htmlspecialchars($qrRegistro, ENT_QUOTES, 'UTF-8') ?>" alt="QR registro teens-kids" style="max-width:240px; width:100%; height:auto; border:1px solid #ddd; border-radius:10px; padding:8px; background:#fff;">
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h3 style="margin-top:0;">Consulta por código</h3>
        <p style="color:#666; margin-bottom:12px;">Comparte este enlace para consultar registros por código.</p>
        <?php $qrConsulta = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . rawurlencode((string)$url_consulta); ?>
        <input type="text" class="form-control" readonly value="<?= htmlspecialchars((string)$url_consulta, ENT_QUOTES, 'UTF-8') ?>" onclick="this.select()">
        <div style="margin-top:12px;">
            <img src="<?= htmlspecialchars($qrConsulta, ENT_QUOTES, 'UTF-8') ?>" alt="QR consulta teens-kids" style="max-width:240px; width:100%; height:auto; border:1px solid #ddd; border-radius:10px; padding:8px; background:#fff;">
        </div>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
