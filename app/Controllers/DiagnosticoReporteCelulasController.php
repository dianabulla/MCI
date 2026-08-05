<?php

require_once APP . '/Controllers/BaseController.php';
require_once APP . '/Helpers/DiagnosticoReporteCelulas.php';

class DiagnosticoReporteCelulasController extends BaseController {

    public function index() {
        if (!AuthController::estaAutenticado()) {
            $this->redirect('auth/acceso-denegado');
            return;
        }

        $puedeVer = AuthController::esAdministrador()
            || AuthController::puede('asistencias:ver')
            || AuthController::puede('asistencias:editar');

        if (!$puedeVer) {
            $this->redirect('auth/acceso-denegado');
            return;
        }

        $puedeVerLog = AuthController::esAdministrador();

        $semanaParam = (string)($_GET['semana'] ?? '');
        $buscar = trim((string)($_GET['buscar'] ?? ''));
        [$inicio, $fin, $semanaIso, $esSemanaAnteriorDefecto] = DiagnosticoReporteCelulas::resolverRangoSemana($semanaParam, true);
        $fechaInicio = $inicio->format('Y-m-d');
        $fechaFin = $fin->format('Y-m-d');

        try {
            $resumen = DiagnosticoReporteCelulas::obtenerResumenSemana($fechaInicio, $fechaFin);
            $celulas = DiagnosticoReporteCelulas::obtenerDetalleCelulasSemana($fechaInicio, $fechaFin);
            $registros = DiagnosticoReporteCelulas::obtenerRegistrosRecientes($fechaInicio, $fechaFin);
            $registrosSinCelula = DiagnosticoReporteCelulas::obtenerRegistrosSinCelula($fechaInicio, $fechaFin);
            $gruposConfusos = DiagnosticoReporteCelulas::obtenerGruposCelulasConfusas();
            $log = $puedeVerLog
                ? DiagnosticoReporteCelulas::leerLineasLogRelevantes($fechaInicio, $fechaFin)
                : ['archivo' => '', 'lineas' => [], 'advertencia' => 'Solo administradores pueden ver el log del servidor.'];
        } catch (Throwable $e) {
            $this->view('herramientas/diagnostico_reporte_celulas_error', [
                'mensaje' => $e->getMessage(),
            ]);
            return;
        }

        $sinReporte = array_values(array_filter($celulas, static function (array $f): bool {
            return (int)($f['Total_Registros'] ?? 0) === 0;
        }));

        $conReporte = array_values(array_filter($celulas, static function (array $f): bool {
            return (int)($f['Total_Registros'] ?? 0) > 0;
        }));

        if ($buscar !== '') {
            $sinReporte = DiagnosticoReporteCelulas::filtrarCelulasPorTexto($sinReporte, $buscar);
            $conReporte = DiagnosticoReporteCelulas::filtrarCelulasPorTexto($conReporte, $buscar);
            $gruposConfusos = array_values(array_filter($gruposConfusos, static function (array $g) use ($buscar): bool {
                $needle = trim($buscar);
                if ($needle === '') {
                    return true;
                }
                $texto = strtolower((string)($g['nombre_referencia'] ?? ''));
                if (str_contains($texto, strtolower($needle))) {
                    return true;
                }
                foreach ($g['celulas'] ?? [] as $c) {
                    $campos = [
                        (string)($c['Nombre_Celula'] ?? ''),
                        (string)($c['Nombre_Lider'] ?? ''),
                    ];
                    foreach ($campos as $campo) {
                        if (str_contains(strtolower($campo), strtolower($needle))) {
                            return true;
                        }
                    }
                }
                return false;
            }));
        }

        $baseUrl = PUBLIC_URL . '?url=herramientas/diagnostico-reporte-celulas';
        if ($semanaParam !== '') {
            $baseUrl .= '&semana=' . urlencode($semanaIso);
        }

        $this->view('herramientas/diagnostico_reporte_celulas', [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'semana_iso' => $semanaIso,
            'es_semana_anterior_defecto' => $esSemanaAnteriorDefecto && $semanaParam === '',
            'resumen' => $resumen,
            'celulas_con_reporte' => $conReporte,
            'celulas_sin_reporte' => $sinReporte,
            'registros' => $registros,
            'registros_sin_celula' => $registrosSinCelula,
            'grupos_celulas_confusas' => $gruposConfusos,
            'buscar' => $buscar,
            'log' => $log,
            'puede_ver_log' => $puedeVerLog,
            'opciones_semanas' => DiagnosticoReporteCelulas::opcionesSemanasRecientes(),
            'base_url' => $baseUrl,
            'url_asistencias' => PUBLIC_URL . '?url=asistencias&semana=' . urlencode($semanaIso),
        ]);
    }
}
