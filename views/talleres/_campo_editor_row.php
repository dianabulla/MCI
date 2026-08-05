<?php

// Fragmento reutilizable para fila de campo en el editor de talleres.

// Variables: $campo, $cIdx, $bIdx, $tiposCampo, $labelsTipo, $tipoCampo, $opcionesTxt, $columnasTxt

?>

<div class="campo-row card" style="padding:14px;margin-bottom:10px;border:1px solid #e5e7eb;background:#fafafa;">

    <input type="hidden" class="campo-bloque-idx-input" name="campo_bloque_idx[]" value="<?= (int)$bIdx ?>">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">

        <strong class="campo-numero">Pregunta <?= (int)$cIdx + 1 ?></strong>

        <button type="button" class="btn btn-outline-danger btn-sm btn-quitar-campo">Quitar</button>

    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">

        <div>

            <label>¿Qué desea preguntar? *</label>

            <input type="text" name="campo_etiqueta[]" class="form-control" required

                   value="<?= htmlspecialchars((string)($campo['Etiqueta'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

        </div>

        <div>

            <label>Tipo de respuesta</label>

            <select name="campo_tipo[]" class="form-control campo-tipo-select">

                <?php foreach ($tiposCampo as $tipo): ?>

                <option value="<?= $tipo ?>" <?= ($tipoCampo === $tipo) ? 'selected' : '' ?>>

                    <?= htmlspecialchars($labelsTipo[$tipo] ?? $tipo, ENT_QUOTES, 'UTF-8') ?>

                </option>

                <?php endforeach; ?>

            </select>

        </div>

    </div>

    <div class="campo-opciones-wrap" style="margin-top:10px;<?= in_array($tipoCampo, ['select','radio','checkbox'], true) ? '' : 'display:none;' ?>">

        <label>Opciones (una por línea)</label>

        <textarea name="campo_opciones[]" class="form-control" rows="3"><?= htmlspecialchars($opcionesTxt, ENT_QUOTES, 'UTF-8') ?></textarea>

    </div>

    <div class="campo-tabla-wrap" style="margin-top:10px;<?= $tipoCampo === 'tabla' ? '' : 'display:none;' ?>">

        <label>Columnas de la tabla (una por línea)</label>

        <textarea name="campo_tabla_columnas[]" class="form-control" rows="2"><?= htmlspecialchars($columnasTxt, ENT_QUOTES, 'UTF-8') ?></textarea>

    </div>

    <div style="margin-top:10px;">

        <label style="display:flex;align-items:center;gap:8px;font-weight:500;cursor:pointer;">

            <input type="checkbox" name="campo_requerido[<?= (int)$cIdx ?>]" class="campo-requerido-check" value="1" <?= !empty($campo['Requerido']) ? 'checked' : '' ?>>

            Obligatoria

        </label>

    </div>

</div>

