<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Registrar Asistencia</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=asistencias" class="btn btn-secondary">Volver</a>
</div>

<div class="form-container" style="max-width: 800px;">
    <form method="POST" id="formAsistencia">
        <div class="form-group">
            <label for="id_celula">Célula</label>
            <select id="id_celula" name="id_celula" class="form-control" required>
                <option value="">Seleccione una célula...</option>
                <?php if (!empty($celulas)): ?>
                    <?php foreach ($celulas as $celula): ?>
                        <option value="<?= $celula['Id_Celula'] ?>">
                            <?= htmlspecialchars($celula['Nombre_Celula']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="fecha">Fecha</label>
            <input type="date" id="fecha" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div id="miembros-container" style="margin-top: 30px; display: none;">
            <h3>Marcar Asistencias</h3>
            <div id="lista-miembros" style="margin-top: 20px;">
                <!-- Aquí se cargarán los miembros -->
            </div>
        </div>

        <div class="form-actions" id="botones-accion" style="display: none; margin-top: 30px;">
            <button type="submit" class="btn btn-primary">Guardar Asistencias</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=asistencias" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
// Datos de personas desde PHP
const personas = <?= json_encode($personas ?? []) ?>;

document.getElementById('id_celula').addEventListener('change', function() {
    const celulaId = parseInt(this.value);
    const miembrosContainer = document.getElementById('miembros-container');
    const listaMiembros = document.getElementById('lista-miembros');
    const botonesAccion = document.getElementById('botones-accion');
    
    if (!celulaId) {
        miembrosContainer.style.display = 'none';
        botonesAccion.style.display = 'none';
        return;
    }

    // Filtrar personas que pertenecen a esta célula
    const miembrosCelula = personas.filter(p => parseInt(p.Id_Celula) === celulaId);
    
    if (miembrosCelula.length === 0) {
        listaMiembros.innerHTML = '<div class="alert alert-warning">Esta célula no tiene miembros asignados.</div>';
        miembrosContainer.style.display = 'block';
        botonesAccion.style.display = 'none';
        return;
    }

    // Generar lista de checkboxes para cada miembro
    let html = '<div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">';
    html += '<table style="width: 100%;">';
    html += '<thead><tr><th style="text-align: left; padding: 10px;">Nombre</th><th style="text-align: center; padding: 10px;">Asistió</th></tr></thead>';
    html += '<tbody>';
    
    miembrosCelula.forEach(miembro => {
        html += '<tr style="border-bottom: 1px solid #dee2e6;">';
        html += '<td style="padding: 10px;">';
        html += '<strong>' + escapeHtml(miembro.Nombre + ' ' + miembro.Apellido) + '</strong>';
        html += '</td>';
        html += '<td style="text-align: center; padding: 10px;">';
        // Campo hidden para enviar siempre el ID (valor 0 = no asistió)
        html += '<input type="hidden" name="asistencias[' + miembro.Id_Persona + ']" value="0">';
        // Checkbox que sobrescribe con 1 si está marcado
        html += '<input type="checkbox" name="asistencias[' + miembro.Id_Persona + ']" value="1" ';
        html += 'style="width: 20px; height: 20px; cursor: pointer;">';
        html += '</td>';
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    html += '<p style="margin-top: 15px; color: #666; font-size: 0.9em;">';
    html += '<strong>Total de miembros:</strong> ' + miembrosCelula.length;
    html += '</p></div>';
    
    listaMiembros.innerHTML = html;
    miembrosContainer.style.display = 'block';
    botonesAccion.style.display = 'flex';
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
