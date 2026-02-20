<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Reportes y Estadísticas</h2>
</div>

<!-- Filtro de Fechas -->
<div class="card" style="margin-bottom: 30px;">
    <form method="GET" action="<?= PUBLIC_URL ?>index.php" style="display: flex; gap: 15px; align-items: end;">
        <input type="hidden" name="url" value="reportes">
        
        <div class="form-group" style="margin: 0;">
            <label>Fecha Inicio:</label>
            <input type="date" name="fecha_inicio" class="form-control" value="<?= $fecha_inicio ?>" required>
        </div>
        
        <div class="form-group" style="margin: 0;">
            <label>Fecha Fin:</label>
            <input type="date" name="fecha_fin" class="form-control" value="<?= $fecha_fin ?>" required>
        </div>
        
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <a href="<?= PUBLIC_URL ?>index.php?url=reportes" class="btn btn-secondary">Resetear</a>
    </form>
</div>

<!-- Gráfico de Almas Ganadas por Ministerio -->
<div class="card" style="margin-bottom: 30px;">
    <h3 style="margin-bottom: 20px; color: #0078D4;">📊 Almas Ganadas por Ministerio</h3>
    <p style="color: #666; margin-bottom: 20px;">
        Período: <strong><?= date('d/m/Y', strtotime($fecha_inicio)) ?></strong> - <strong><?= date('d/m/Y', strtotime($fecha_fin)) ?></strong>
    </p>
    <div id="chartAlmasGanadas"></div>
</div>

<!-- Tabla de Detalle de Almas Ganadas -->
<div class="card" style="margin-bottom: 30px;">
    <h4 style="margin-bottom: 15px; color: #0078D4;">Detalle por Ministerio</h4>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ministerio</th>
                    <th>Hombres</th>
                    <th>Mujeres</th>
                    <th>Jóvenes Hombres</th>
                    <th>Jóvenes Mujeres</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($almas_ganadas)): ?>
                    <?php 
                    $totalGeneral = 0;
                    $totalHombres = 0;
                    $totalMujeres = 0;
                    $totalJovenesH = 0;
                    $totalJovenesM = 0;
                    ?>
                    <?php foreach ($almas_ganadas as $ministerio): ?>
                        <?php
                        $totalHombres += $ministerio['Hombres'];
                        $totalMujeres += $ministerio['Mujeres'];
                        $totalJovenesH += $ministerio['Jovenes_Hombres'];
                        $totalJovenesM += $ministerio['Jovenes_Mujeres'];
                        $totalGeneral += $ministerio['Total'];
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($ministerio['Nombre_Ministerio'] ?? 'Sin ministerio') ?></td>
                            <td><?= $ministerio['Hombres'] ?></td>
                            <td><?= $ministerio['Mujeres'] ?></td>
                            <td><?= $ministerio['Jovenes_Hombres'] ?></td>
                            <td><?= $ministerio['Jovenes_Mujeres'] ?></td>
                            <td><strong><?= $ministerio['Total'] ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr style="background: #f0f0f0; font-weight: bold;">
                        <td>TOTAL</td>
                        <td><?= $totalHombres ?></td>
                        <td><?= $totalMujeres ?></td>
                        <td><?= $totalJovenesH ?></td>
                        <td><?= $totalJovenesM ?></td>
                        <td><?= $totalGeneral ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No hay datos en este período</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Gráfico de Asistencia a Células -->
<div class="card" style="margin-bottom: 30px;">
    <h3 style="margin-bottom: 20px; color: #0078D4;">📈 Asistencia a Células</h3>
    <p style="color: #666; margin-bottom: 20px;">
        Período: <strong><?= date('d/m/Y', strtotime($fecha_inicio)) ?></strong> - <strong><?= date('d/m/Y', strtotime($fecha_fin)) ?></strong>
    </p>
    
    <!-- Filtro por Líder -->
    <div style="margin-bottom: 20px;">
        <label style="font-weight: 600; margin-bottom: 10px; display: block; color: #0078D4;">
            🔍 Filtrar por Líder:
        </label>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button onclick="seleccionarTodosLideres()" class="btn btn-sm btn-secondary">Todos</button>
            <button onclick="limpiarLideres()" class="btn btn-sm btn-secondary">Ninguno</button>
            <div id="filtrosLideres" style="display: flex; gap: 10px; flex-wrap: wrap;"></div>
        </div>
    </div>
    
    <div id="chartAsistenciaCelulas"></div>
</div>

<!-- Tabla de Detalle de Asistencia -->
<div class="card" style="margin-bottom: 30px;">
    <h4 style="margin-bottom: 15px; color: #0078D4;">Detalle por Célula</h4>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Célula</th>
                    <th>Líder</th>
                    <th>Miembros Inscritos</th>
                    <th>Reuniones Realizadas</th>
                    <th>Asistencias Esperadas</th>
                    <th>Asistencias Reales</th>
                    <th>% Asistencia</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($asistencia_celulas)): ?>
                    <?php foreach ($asistencia_celulas as $celula): ?>
                        <?php 
                        $esperadas = $celula['Asistencias_Esperadas'];
                        $reales = $celula['Asistencias_Reales'];
                        $porcentaje = $esperadas > 0 ? round(($reales / $esperadas) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($celula['Nombre_Celula']) ?></td>
                            <td><?= htmlspecialchars(trim($celula['Nombre_Lider']) ?: 'Sin líder') ?></td>
                            <td><?= $celula['Total_Inscritos'] ?></td>
                            <td><?= $celula['Reuniones_Realizadas'] ?></td>
                            <td><?= $esperadas ?></td>
                            <td><?= $reales ?></td>
                            <td><?= $porcentaje ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay datos de asistencia en este período</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ApexCharts Library -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
// Datos para Almas Ganadas
const almasGanadasData = <?= json_encode($almas_ganadas) ?>;

const ministerios = almasGanadasData.map(m => m.Nombre_Ministerio || 'Sin ministerio');
const hombres = almasGanadasData.map(m => parseInt(m.Hombres));
const mujeres = almasGanadasData.map(m => parseInt(m.Mujeres));
const jovenesH = almasGanadasData.map(m => parseInt(m.Jovenes_Hombres));
const jovenesM = almasGanadasData.map(m => parseInt(m.Jovenes_Mujeres));

// Gráfico de Almas Ganadas - ApexCharts
const optionsAlmas = {
    series: [
        {
            name: 'Hombres',
            data: hombres
        },
        {
            name: 'Mujeres',
            data: mujeres
        },
        {
            name: 'Jóvenes Hombres',
            data: jovenesH
        },
        {
            name: 'Jóvenes Mujeres',
            data: jovenesM
        }
    ],
    chart: {
        type: 'bar',
        height: 400,
        stacked: false,
        toolbar: {
            show: true,
            tools: {
                download: true,
                zoom: true,
                zoomin: true,
                zoomout: true,
                pan: true,
                reset: true
            }
        },
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 800
        }
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '70%',
            borderRadius: 6,
            dataLabels: {
                position: 'top'
            }
        }
    },
    dataLabels: {
        enabled: false
    },
    stroke: {
        show: true,
        width: 2,
        colors: ['transparent']
    },
    xaxis: {
        categories: ministerios,
        labels: {
            style: {
                fontSize: '12px'
            }
        }
    },
    yaxis: {
        title: {
            text: 'Cantidad de Personas'
        },
        labels: {
            formatter: function(val) {
                return Math.floor(val);
            }
        }
    },
    colors: ['#0078D4', '#FF6B9D', '#4FC3F7', '#FFB6C1'],
    fill: {
        opacity: 1
    },
    tooltip: {
        y: {
            formatter: function(val) {
                return val + " personas";
            }
        },
        theme: 'light'
    },
    legend: {
        position: 'top',
        horizontalAlign: 'left',
        offsetY: 0,
        fontSize: '13px',
        markers: {
            width: 12,
            height: 12,
            radius: 3
        }
    },
    grid: {
        borderColor: '#e7e7e7',
        strokeDashArray: 4
    }
};

const chartAlmas = new ApexCharts(document.querySelector("#chartAlmasGanadas"), optionsAlmas);
chartAlmas.render();

// Datos para Asistencia a Células
const asistenciaDataFull = <?= json_encode($asistencia_celulas) ?>;
let asistenciaData = [...asistenciaDataFull];
let lideresFiltrados = new Set();

// Crear filtros de líderes
function crearFiltrosLideres() {
    const lideres = [...new Set(asistenciaDataFull.map(c => {
        const lider = c.Nombre_Lider ? c.Nombre_Lider.trim() : '';
        return lider || 'Sin líder';
    }))].sort();
    
    const contenedor = document.getElementById('filtrosLideres');
    contenedor.innerHTML = '';
    
    lideres.forEach(lider => {
        const checkbox = document.createElement('label');
        checkbox.className = 'filtro-lider';
        checkbox.innerHTML = `
            <input type="checkbox" value="${lider}" checked onchange="filtrarPorLider()">
            <span>${lider}</span>
        `;
        contenedor.appendChild(checkbox);
        lideresFiltrados.add(lider);
    });
}

function seleccionarTodosLideres() {
    document.querySelectorAll('#filtrosLideres input[type="checkbox"]').forEach(cb => {
        cb.checked = true;
        lideresFiltrados.add(cb.value);
    });
    filtrarPorLider();
}

function limpiarLideres() {
    document.querySelectorAll('#filtrosLideres input[type="checkbox"]').forEach(cb => {
        cb.checked = false;
    });
    lideresFiltrados.clear();
    filtrarPorLider();
}

function filtrarPorLider() {
    // Actualizar conjunto de líderes filtrados
    lideresFiltrados.clear();
    document.querySelectorAll('#filtrosLideres input[type="checkbox"]:checked').forEach(cb => {
        lideresFiltrados.add(cb.value);
    });
    
    // Filtrar datos
    asistenciaData = asistenciaDataFull.filter(c => {
        const lider = c.Nombre_Lider ? c.Nombre_Lider.trim() : '';
        const nombreLider = lider || 'Sin líder';
        return lideresFiltrados.has(nombreLider);
    });
    
    // Actualizar gráfico
    actualizarGraficoAsistencia();
}

const celulas = asistenciaData.map(c => c.Nombre_Celula);
const esperadas = asistenciaData.map(c => parseInt(c.Asistencias_Esperadas));
const reales = asistenciaData.map(c => parseInt(c.Asistencias_Reales));
const porcentajes = asistenciaData.map(c => {
    const esp = parseInt(c.Asistencias_Esperadas);
    const real = parseInt(c.Asistencias_Reales);
    return esp > 0 ? Math.round((real / esp) * 100) : 0;
});

// Gráfico de Asistencia - ApexCharts
const optionsAsistencia = {
    series: [
        {
            name: 'Asistencias Esperadas',
            type: 'column',
            data: esperadas
        },
        {
            name: 'Asistencias Reales',
            type: 'column',
            data: reales
        },
        {
            name: '% Asistencia',
            type: 'line',
            data: porcentajes
        }
    ],
    chart: {
        type: 'line',
        height: 400,
        toolbar: {
            show: true,
            tools: {
                download: true,
                zoom: true,
                zoomin: true,
                zoomout: true,
                pan: true,
                reset: true
            }
        },
        animations: {
            enabled: true,
            easing: 'easeinout',
            speed: 800
        }
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: '60%',
            borderRadius: 6
        }
    },
    dataLabels: {
        enabled: true,
        enabledOnSeries: [2],
        formatter: function(val) {
            return val + "%";
        },
        offsetY: -10,
        style: {
            fontSize: '12px',
            fontWeight: 'bold',
            colors: ["#28a745"]
        }
    },
    stroke: {
        width: [0, 0, 3],
        curve: 'smooth'
    },
    xaxis: {
        categories: celulas,
        labels: {
            style: {
                fontSize: '12px'
            }
        }
    },
    yaxis: [
        {
            seriesName: 'Asistencias Esperadas',
            title: {
                text: 'Cantidad de Asistencias'
            },
            labels: {
                formatter: function(val) {
                    return Math.floor(val);
                }
            }
        },
        {
            seriesName: 'Asistencias Esperadas',
            show: false
        },
        {
            seriesName: '% Asistencia',
            opposite: true,
            title: {
                text: 'Porcentaje (%)'
            },
            min: 0,
            max: 100,
            labels: {
                formatter: function(val) {
                    return Math.floor(val) + "%";
                }
            }
        }
    ],
    colors: ['#005BA1', '#0078D4', '#28a745'],
    fill: {
        opacity: [0.85, 0.85, 1],
        type: ['gradient', 'gradient', 'solid'],
        gradient: {
            shade: 'light',
            type: "vertical",
            shadeIntensity: 0.3,
            gradientToColors: ['#0078D4', '#4FC3F7', '#28a745'],
            inverseColors: false,
            opacityFrom: 0.9,
            opacityTo: 0.8,
            stops: [0, 100]
        }
    },
    tooltip: {
        shared: true,
        intersect: false,
        y: [
            {
                formatter: function(val) {
                    return val + " asistencias";
                }
            },
            {
                formatter: function(val) {
                    return val + " asistencias";
                }
            },
            {
                formatter: function(val) {
                    return val + "%";
                }
            }
        ],
        theme: 'light'
    },
    legend: {
        position: 'top',
        horizontalAlign: 'left',
        offsetY: 0,
        fontSize: '13px',
        markers: {
            width: 12,
            height: 12,
            radius: 3
        }
    },
    grid: {
        borderColor: '#e7e7e7',
        strokeDashArray: 4
    },
    markers: {
        size: [0, 0, 5],
        strokeWidth: 0,
        hover: {
            size: 7
        }
    }
};

let chartAsistencia = new ApexCharts(document.querySelector("#chartAsistenciaCelulas"), optionsAsistencia);
chartAsistencia.render();

// Función para actualizar el gráfico
function actualizarGraficoAsistencia() {
    const celulasFiltradas = asistenciaData.map(c => c.Nombre_Celula);
    const esperadasFiltradas = asistenciaData.map(c => parseInt(c.Asistencias_Esperadas));
    const realesFiltradas = asistenciaData.map(c => parseInt(c.Asistencias_Reales));
    const porcentajesFiltrados = asistenciaData.map(c => {
        const esp = parseInt(c.Asistencias_Esperadas);
        const real = parseInt(c.Asistencias_Reales);
        return esp > 0 ? Math.round((real / esp) * 100) : 0;
    });
    
    chartAsistencia.updateOptions({
        xaxis: {
            categories: celulasFiltradas
        }
    });
    
    chartAsistencia.updateSeries([
        {
            name: 'Asistencias Esperadas',
            type: 'column',
            data: esperadasFiltradas
        },
        {
            name: 'Asistencias Reales',
            type: 'column',
            data: realesFiltradas
        },
        {
            name: '% Asistencia',
            type: 'line',
            data: porcentajesFiltrados
        }
    ]);
}

// Inicializar filtros
crearFiltrosLideres();

</script>

<style>
.card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.apexcharts-tooltip {
    background: white !important;
    border: 1px solid #e3e3e3 !important;
    box-shadow: 0 3px 10px rgba(0,0,0,0.15) !important;
}

.filtro-lider {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    background: #f0f8ff;
    border: 1px solid #0078D4;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 14px;
}

.filtro-lider:hover {
    background: #e3f2fd;
    border-color: #005BA1;
}

.filtro-lider input[type="checkbox"] {
    cursor: pointer;
    width: 16px;
    height: 16px;
    accent-color: #0078D4;
}

.filtro-lider span {
    color: #333;
    font-weight: 500;
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>
