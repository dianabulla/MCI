<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Códigos teens-kids</h2>
</div>

<div class="card teen-topbar-card" style="margin-bottom:20px;">
    <div class="card-body">
        <div class="page-actions personas-mobile-stack teen-topbar-actions">
        <a href="<?= PUBLIC_URL ?>index.php?url=teen/registro-menores" class="btn btn-nav-pill">Registro teens-kids</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=teen/codigos" class="btn btn-nav-pill active">Códigos</a>
        </div>
    </div>
</div>

<div class="teen-codigos-resumen" style="margin-bottom:20px;">
    <div class="dashboard-card teen-codigo-card teen-codigo-teen">
        <h3>Teens</h3>
        <div class="value">TNS00</div>
        <small>Prefijo fijo TNS + 2 dígitos semanales</small>
    </div>
    <div class="dashboard-card teen-codigo-card teen-codigo-kids">
        <h3>Kids</h3>
        <div class="value">KS00</div>
        <small>Prefijo fijo KS + 2 dígitos semanales</small>
    </div>
</div>

<div class="teen-codigos-grid">
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
</div>

<style>
.teen-codigos-resumen {
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap:14px;
}

.teen-codigo-card {
    border-left: 3px solid transparent;
}

.teen-codigo-card .value {
    font-size: 36px;
    font-weight: 800;
    margin: 8px 0 6px;
    line-height: 1;
}

.teen-codigo-teen {
    border-left-color:#1f66d1;
}

.teen-codigo-teen .value {
    color:#1f66d1;
}

.teen-codigo-kids {
    border-left-color:#0aa678;
}

.teen-codigo-kids .value {
    color:#0aa678;
}

.teen-codigos-grid {
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap:16px;
    align-items:start;
}

.teen-topbar-card .card-body {
    padding: 16px 18px;
}

.teen-topbar-actions {
    align-items:center;
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>
