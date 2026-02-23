<?php
/**
 * Controlador para gestionar entrega de obsequios
 * Requiere autenticación
 */

require_once APP . '/Models/NinoNavidad.php';
require_once APP . '/Models/Ministerio.php';

class EntregaObsequioController extends BaseController {
    private $ninoModel;
    private $ministerioModel;

    public function __construct() {
        $this->ninoModel = new NinoNavidad();
        $this->ministerioModel = new Ministerio();
    }

    /**
     * Listar niños registrados
     */
    public function index() {
        // Obtener filtro de ministerio si existe
        $filtroMinisterio = $_GET['ministerio'] ?? '';
        
        // Obtener lista de ministerios para el filtro
        $ministerios = $this->ministerioModel->getAll();
        
        // Obtener niños con filtro opcional
        if (!empty($filtroMinisterio)) {
            $ninos = $this->ninoModel->getAllByMinisterio($filtroMinisterio);
        } else {
            $ninos = $this->ninoModel->getAllWithMinisterio();
        }
        
        $data = [
            'ninos' => $ninos,
            'ministerios' => $ministerios,
            'filtroMinisterio' => $filtroMinisterio,
            'mensaje' => $_SESSION['mensaje'] ?? null,
            'tipo_mensaje' => $_SESSION['tipo_mensaje'] ?? null
        ];
        
        // Limpiar mensajes
        unset($_SESSION['mensaje']);
        unset($_SESSION['tipo_mensaje']);
        
        $this->view('entrega_obsequio/lista', $data);
    }

    /**
     * Marcar obsequio como entregado (AJAX)
     */
    public function marcarEntregado() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            exit;
        }
        
        $idRegistro = $_POST['id_registro'] ?? null;
        
        if (!$idRegistro) {
            echo json_encode(['success' => false, 'message' => 'ID de registro no proporcionado']);
            exit;
        }
        
        $resultado = $this->ninoModel->marcarComoEntregado($idRegistro);
        echo json_encode($resultado);
        exit;
    }

    /**
     * Exportar lista de niños a Excel
     */
    public function exportarExcel() {
        // Obtener filtro de ministerio si existe
        $filtroMinisterio = $_GET['ministerio'] ?? '';
        
        // Obtener niños con filtro opcional
        if (!empty($filtroMinisterio)) {
            $ninos = $this->ninoModel->getAllByMinisterio($filtroMinisterio);
            $ministerio = $this->ministerioModel->getById($filtroMinisterio);
            $nombreFiltro = $ministerio ? '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $ministerio['Nombre_Ministerio']) : '';
        } else {
            $ninos = $this->ninoModel->getAllWithMinisterio();
            $nombreFiltro = '';
        }
        
        // Nombre del archivo
        $nombreArchivo = 'Obsequios_Navidenos' . $nombreFiltro . '_' . date('Y-m-d_His') . '.csv';
        
        // Configurar headers para descarga
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Abrir salida
        $output = fopen('php://output', 'w');
        
        // BOM para UTF-8 (para que Excel lo reconozca correctamente)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Encabezados
        fputcsv($output, [
            'Nombre del Niño(a)',
            'Edad',
            'Fecha de Nacimiento',
            'Acudiente',
            'Teléfono',
            'Barrio',
            'Ministerio',
            'Estado',
            'Fecha de Entrega',
            'Fecha de Registro'
        ], ';');
        
        // Datos
        foreach ($ninos as $nino) {
            fputcsv($output, [
                $nino['Nombre_Apellidos'],
                $nino['Edad'] . ' años',
                date('d/m/Y', strtotime($nino['Fecha_Nacimiento'])),
                $nino['Nombre_Acudiente'],
                $nino['Telefono_Acudiente'],
                $nino['Barrio'],
                $nino['Nombre_Ministerio'] ?? 'Sin ministerio',
                $nino['Estado_Entrega'],
                $nino['Fecha_Entrega'] ? date('d/m/Y H:i', strtotime($nino['Fecha_Entrega'])) : 'No entregado',
                date('d/m/Y H:i', strtotime($nino['Fecha_Registro']))
            ], ';');
        }
        
        fclose($output);
        exit;
    }

    /**
     * Exportar lista de niños a PDF
     */
    public function exportarPDF() {
        // Obtener filtro de ministerio si existe
        $filtroMinisterio = $_GET['ministerio'] ?? '';
        
        // Obtener niños con filtro opcional
        if (!empty($filtroMinisterio)) {
            $ninos = $this->ninoModel->getAllByMinisterio($filtroMinisterio);
            $ministerio = $this->ministerioModel->getById($filtroMinisterio);
            $tituloFiltro = $ministerio ? ' - ' . $ministerio['Nombre_Ministerio'] : '';
        } else {
            $ninos = $this->ninoModel->getAllWithMinisterio();
            $tituloFiltro = '';
        }
        
        // Calcular estadísticas
        $totalRegistrados = count($ninos);
        $totalEntregados = count(array_filter($ninos, fn($n) => $n['Estado_Entrega'] === 'Entregado'));
        $totalPendientes = $totalRegistrados - $totalEntregados;
        
        // Configurar headers para PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Obsequios_Navidenos_' . date('Y-m-d_His') . '.pdf"');
        
        // Crear PDF usando librería FPDF o generación manual
        // Como no tenemos FPDF instalado, generamos HTML que el navegador puede imprimir como PDF
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Reporte de Obsequios Navideños</title>
            <style>
                @page { 
                    size: A4 landscape;
                    margin: 10mm;
                }
                body {
                    font-family: Arial, sans-serif;
                    font-size: 10pt;
                    margin: 0;
                    padding: 10px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 3px solid #c31432;
                    padding-bottom: 10px;
                }
                .header h1 {
                    color: #c31432;
                    margin: 0;
                    font-size: 20pt;
                }
                .header p {
                    margin: 5px 0;
                    color: #666;
                }
                .estadisticas {
                    display: flex;
                    justify-content: space-around;
                    margin-bottom: 20px;
                    background: #f5f5f5;
                    padding: 10px;
                    border-radius: 5px;
                }
                .stat-box {
                    text-align: center;
                }
                .stat-box .numero {
                    font-size: 24pt;
                    font-weight: bold;
                    color: #c31432;
                }
                .stat-box .label {
                    font-size: 9pt;
                    color: #666;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                    font-size: 9pt;
                }
                thead {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                }
                th {
                    padding: 8px 5px;
                    text-align: left;
                    font-weight: 600;
                    border: 1px solid #fff;
                }
                td {
                    padding: 6px 5px;
                    border: 1px solid #ddd;
                }
                tbody tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .entregado {
                    background-color: #d4edda !important;
                }
                .badge {
                    padding: 3px 8px;
                    border-radius: 10px;
                    font-size: 8pt;
                    font-weight: bold;
                }
                .badge-success {
                    background: #28a745;
                    color: white;
                }
                .badge-warning {
                    background: #ffc107;
                    color: #333;
                }
                .footer {
                    margin-top: 20px;
                    text-align: center;
                    font-size: 8pt;
                    color: #666;
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
                @media print {
                    body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
                    .no-print { display: none; }
                }
                .print-btn {
                    position: fixed;
                    top: 10px;
                    right: 10px;
                    padding: 10px 20px;
                    background: #c31432;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    cursor: pointer;
                    font-weight: bold;
                    z-index: 1000;
                }
            </style>
        </head>
        <body>
            <button class="print-btn no-print" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
            
            <div class="header">
                <h1>🎁 REPORTE DE OBSEQUIOS NAVIDEÑOS</h1>
                <p><strong>MCI Madrid Colombia - Navidad 2025</strong><?= $tituloFiltro ?></p>
                <p>Generado el: <?= date('d/m/Y H:i:s') ?></p>
            </div>

            <div class="estadisticas">
                <div class="stat-box">
                    <div class="numero"><?= $totalRegistrados ?></div>
                    <div class="label">Total Registrados</div>
                </div>
                <div class="stat-box">
                    <div class="numero" style="color: #28a745;"><?= $totalEntregados ?></div>
                    <div class="label">Entregados</div>
                </div>
                <div class="stat-box">
                    <div class="numero" style="color: #ffc107;"><?= $totalPendientes ?></div>
                    <div class="label">Pendientes</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 15%">Nombre del Niño(a)</th>
                        <th style="width: 5%">Edad</th>
                        <th style="width: 12%">Acudiente</th>
                        <th style="width: 10%">Teléfono</th>
                        <th style="width: 10%">Barrio</th>
                        <th style="width: 12%">Ministerio</th>
                        <th style="width: 10%">Estado</th>
                        <th style="width: 13%">Fecha Entrega</th>
                        <th style="width: 13%">Fecha Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ninos)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 20px;">No hay registros</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ninos as $nino): ?>
                            <tr class="<?= $nino['Estado_Entrega'] === 'Entregado' ? 'entregado' : '' ?>">
                                <td><strong><?= htmlspecialchars($nino['Nombre_Apellidos']) ?></strong></td>
                                <td><?= $nino['Edad'] ?></td>
                                <td><?= htmlspecialchars($nino['Nombre_Acudiente']) ?></td>
                                <td><?= htmlspecialchars($nino['Telefono_Acudiente']) ?></td>
                                <td><?= htmlspecialchars($nino['Barrio']) ?></td>
                                <td><?= htmlspecialchars($nino['Nombre_Ministerio'] ?? 'Sin ministerio') ?></td>
                                <td>
                                    <span class="badge <?= $nino['Estado_Entrega'] === 'Entregado' ? 'badge-success' : 'badge-warning' ?>">
                                        <?= $nino['Estado_Entrega'] ?>
                                    </span>
                                </td>
                                <td><?= $nino['Fecha_Entrega'] ? date('d/m/Y H:i', strtotime($nino['Fecha_Entrega'])) : '-' ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($nino['Fecha_Registro'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="footer">
                <p><strong>MCI Madrid Colombia</strong> | Sistema de Gestión de Obsequios Navideños</p>
                <p>Este reporte contiene información confidencial de la iglesia</p>
            </div>

            <script>
                // Auto-imprimir al cargar (opcional)
                // window.onload = function() { window.print(); }
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}
