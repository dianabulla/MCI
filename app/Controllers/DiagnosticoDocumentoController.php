<?php
/**
 * Diagnóstico de Numero_Documento (vacío, 5 dígitos, teléfono).
 */
require_once APP . '/Controllers/BaseController.php';
require_once APP . '/Helpers/DiagnosticoDocumentoQuery.php';

class DiagnosticoDocumentoController extends BaseController
{
    public function index() {
        if (!AuthController::estaAutenticado() || !AuthController::esAdministrador()) {
            $this->redirect('auth/acceso-denegado');
            return;
        }

        global $pdo;
        $tipo = DiagnosticoDocumentoQuery::normalizarTipo((string)($_GET['tipo'] ?? 'todos'));

        try {
            $resumen = DiagnosticoDocumentoQuery::obtenerResumen($pdo);
            $filas = DiagnosticoDocumentoQuery::obtenerFilas($pdo, $tipo);
            $usaRegexp = DiagnosticoDocumentoQuery::soportaRegexpReplace($pdo);
        } catch (Throwable $e) {
            $this->view('herramientas/diagnostico_documento_error', [
                'mensaje' => $e->getMessage(),
                'titulo' => 'Error al consultar la base de datos',
            ]);
            return;
        }

        $this->view('herramientas/diagnostico_documento', [
            'tipo' => $tipo,
            'resumen' => $resumen,
            'filas' => $filas,
            'usa_regexp_replace' => $usaRegexp,
            'export_url' => PUBLIC_URL . '?url=herramientas/diagnostico-documento/exportar&tipo=' . urlencode($tipo),
            'base_url' => PUBLIC_URL . '?url=herramientas/diagnostico-documento',
        ]);
    }

    public function exportar() {
        if (!AuthController::estaAutenticado() || !AuthController::esAdministrador()) {
            $this->redirect('auth/acceso-denegado');
            return;
        }

        global $pdo;
        $tipo = DiagnosticoDocumentoQuery::normalizarTipo((string)($_GET['tipo'] ?? 'todos'));

        try {
            $filas = DiagnosticoDocumentoQuery::obtenerFilas($pdo, $tipo);
        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Error al exportar: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            exit;
        }

        $etiquetas = [
            'todos' => 'todas_anomalias',
            'vacio' => 'documento_vacio',
            '5_digitos' => 'documento_5_digitos',
            'telefono' => 'documento_telefono',
        ];
        $sufijo = $etiquetas[$tipo] ?? 'listado';
        $headers = [
            'Id_Persona' => 'ID Persona',
            'nombre_completo' => 'Nombre completo',
            'Tipo_Documento' => 'Tipo documento',
            'Numero_Documento' => 'Número documento',
            'doc_solo_digitos' => 'Documento (solo dígitos)',
            'Telefono' => 'Teléfono',
            'telefono_solo_digitos' => 'Teléfono (solo dígitos)',
            'nombre_lider' => 'Líder',
            'nombre_ministerio' => 'Ministerio',
            'tipo_anomalia' => 'Tipo anomalía',
            'tipos_anomalia' => 'Tipos anomalía',
            'motivo' => 'Motivo',
            'Estado_Cuenta' => 'Estado cuenta',
            'Es_Antiguo' => 'Es antiguo',
            'Id_Celula' => 'ID Célula',
            'Id_Ministerio' => 'ID Ministerio',
            'Proceso' => 'Proceso',
            'Fecha_Registro' => 'Fecha registro',
        ];

        $columnas = empty($filas) ? ['Sin registros'] : array_keys($filas[0]);
        $headerRow = [];
        foreach ($columnas as $col) {
            $headerRow[] = $headers[$col] ?? $col;
        }

        $rows = [];
        foreach ($filas as $fila) {
            $row = [];
            foreach ($columnas as $col) {
                $row[] = (string)($fila[$col] ?? '');
            }
            $rows[] = $row;
        }

        $nombre = 'diagnostico_documento_' . $sufijo . '_' . date('Y-m-d_His') . '.csv';
        $this->exportCsv($nombre, $headerRow, $rows, false);
    }
}
