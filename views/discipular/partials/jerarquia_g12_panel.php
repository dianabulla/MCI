<?php
/**
 * Panel de referencia: jerarquía visión G12 ↔ pantallas del sistema.
 * Incluir desde vistas bajo discipular/ministerios.
 */
$urlListaMinisterios = PUBLIC_URL . '?url=discipular/ministerios';
$urlEquipo = PUBLIC_URL . '?url=discipular/ministerios/equipo-principal';
$urlLideresCelula = PUBLIC_URL . '?url=discipular/ministerios/lideres-celula';
$urlProgramas = PUBLIC_URL . '?url=programas';
?>
<details class="dj12-panel">
    <summary class="dj12-panel-summary">
        <span class="dj12-panel-summary-icon" aria-hidden="true"><i class="bi bi-diagram-3"></i></span>
        <span class="dj12-panel-summary-text">Jerarquía G12 y cómo se ve en esta app</span>
        <span class="dj12-panel-caret"><i class="bi bi-chevron-down"></i></span>
    </summary>
    <div class="dj12-panel-body">
        <p class="dj12-panel-lead">
            La visión se organiza por mentoreo y redes (no solo por cargos tradicionales). Abajo, cada nivel con su equivalente en MCI Madrid.
        </p>
        <ol class="dj12-panel-list">
            <li>
                <strong>Pastores principales / cabeza pastoral.</strong> Dirigen el primer anillo y modelan la visión.
                <span class="dj12-panel-app">En la app:</span> pareja pastoral o líderes configurados en <a href="<?= htmlspecialchars($urlEquipo) ?>">Equipo principal</a> (por ministerio o cobertura general).
            </li>
            <li>
                <strong>Grupo del 12 (primer anillo).</strong> Equipo directo bajo la cabeza pastoral; máxima responsabilidad de dirección en la red.
                <span class="dj12-panel-app">En la app:</span> líderes de 12 del ministerio; casillas 1–12 y cupos en la misma pantalla de <a href="<?= htmlspecialchars($urlEquipo) ?>">Equipo principal</a>.
            </li>
            <li>
                <strong>Redes ministeriales.</strong> Hombres, mujeres, jóvenes, niños, etc.; cada red con su propia cadena de 12.
                <span class="dj12-panel-app">En la app:</span> cada fila en <a href="<?= htmlspecialchars($urlListaMinisterios) ?>">Ministerios</a> es una red. Usa el filtro de ministerio en Equipo principal para trabajar una red a la vez.
            </li>
            <li>
                <strong>Líderes de célula.</strong> Unidad base en hogar; evangelización y cuidado; reportan a su líder de 12.
                <span class="dj12-panel-app">En la app:</span> <a href="<?= htmlspecialchars($urlLideresCelula) ?>">Líderes de célula</a> y la pestaña &laquo;Líderes célula&raquo; dentro de Equipo principal.
            </li>
            <li>
                <strong>Anillo 144 (cuando aplica).</strong> Líderes de célula bajo cada líder de 12.
                <span class="dj12-panel-app">En la app:</span> pestaña &laquo;Líderes 144&raquo; en Equipo principal.
            </li>
            <li>
                <strong>Discípulos en formación.</strong> Objetivo: no permanecer pasivos; avanzar hacia liderar.
                <span class="dj12-panel-app">En la app:</span> personas con rol discípulo/miembro; proceso <strong>Ganar → Consolidar → Discipular → Enviar</strong> en fichas y en <a href="<?= htmlspecialchars($urlProgramas) ?>">Programas</a> (escuela / escalera).
            </li>
        </ol>
    </div>
</details>
<style>
.dj12-panel {
    margin: 0 0 16px;
    border: 1px solid #c9d7ee;
    border-radius: 14px;
    background: linear-gradient(180deg, #fbfdff 0%, #f3f6fb 100%);
    box-shadow: 0 1px 0 rgba(255,255,255,0.9) inset;
}
.dj12-panel-summary {
    list-style: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    font-weight: 700;
    font-size: 0.92rem;
    color: #1f365f;
}
.dj12-panel-summary::-webkit-details-marker { display: none; }
.dj12-panel-summary-icon {
    width: 32px;
    height: 32px;
    border-radius: 10px;
    background: #e8f0fc;
    border: 1px solid #c5d7ee;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #2f5aa8;
    flex-shrink: 0;
}
.dj12-panel-summary-text { flex: 1; text-align: left; }
.dj12-panel-caret {
    color: #3d5b8c;
    font-size: 0.85rem;
    transition: transform 0.2s ease;
}
.dj12-panel[open] .dj12-panel-caret { transform: rotate(180deg); }
.dj12-panel-body {
    padding: 0 14px 14px 14px;
    border-top: 1px solid #e2eaf6;
}
.dj12-panel-lead {
    margin: 12px 0 10px;
    font-size: 0.86rem;
    color: #4d5f7a;
    line-height: 1.45;
}
.dj12-panel-list {
    margin: 0;
    padding-left: 1.25rem;
    font-size: 0.86rem;
    color: #2c3f67;
    line-height: 1.5;
}
.dj12-panel-list li { margin-bottom: 10px; }
.dj12-panel-list li:last-child { margin-bottom: 0; }
.dj12-panel-app {
    display: block;
    margin-top: 4px;
    font-size: 0.82rem;
    color: #5a6b86;
}
.dj12-panel-body a {
    color: #1e5db8;
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.dj12-panel-body a:hover { color: #143d7a; }
</style>
