<?php
/**
 * Controlador Base - Clase padre para todos los controladores
 */

class BaseController {
    /**
     * Cargar una vista
     */
    protected function view($viewName, $data = []) {
        // Extraer datos para que estén disponibles en la vista
        extract($data);
        
        // Construir ruta a la vista
        $viewPath = VIEWS . '/' . $viewName . '.php';
        
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            die("Vista no encontrada: $viewPath");
        }
    }

    /**
     * Redirigir a otra URL
     */
    protected function redirect($url) {
        header("Location: " . PUBLIC_URL . "index.php?url=$url");
        exit;
    }

    /**
     * Devolver JSON
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Exportar datos en formato CSV compatible con Excel
     */
    protected function exportCsv($filename, array $headers, array $rows, $sortRows = true) {
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$filename);
        if ($safeFilename === '') {
            $safeFilename = 'export';
        }

        if (substr($safeFilename, -4) !== '.csv') {
            $safeFilename .= '.csv';
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            http_response_code(500);
            echo 'No se pudo generar el archivo de exportación.';
            exit;
        }

        fwrite($output, "\xEF\xBB\xBF");
        fwrite($output, "sep=;\r\n");

        $delimiter = ';';
        $enclosure = '"';
        $escape = '\\';

        $headers = array_map([$this, 'sanitizeExportValue'], $headers);

        $normalizedRows = [];
        $headerCount = count($headers);
        foreach ($rows as $row) {
            if (!is_array($row)) {
                $row = [(string)$row];
            }

            $row = array_map([$this, 'sanitizeExportValue'], array_values($row));

            if (count($row) < $headerCount) {
                $row = array_pad($row, $headerCount, '');
            } elseif (count($row) > $headerCount) {
                $row = array_slice($row, 0, $headerCount);
            }

            $normalizedRows[] = $row;
        }

        if ($sortRows) {
            usort($normalizedRows, function($a, $b) {
                $leftRaw = trim(implode(' ', array_map('strval', $a)));
                $rightRaw = trim(implode(' ', array_map('strval', $b)));
                $left = function_exists('mb_strtolower') ? mb_strtolower($leftRaw, 'UTF-8') : strtolower($leftRaw);
                $right = function_exists('mb_strtolower') ? mb_strtolower($rightRaw, 'UTF-8') : strtolower($rightRaw);
                return strnatcasecmp($left, $right);
            });
        }

        fputcsv($output, $headers, $delimiter, $enclosure, $escape);
        foreach ($normalizedRows as $row) {
            fputcsv($output, $row, $delimiter, $enclosure, $escape);
        }

        fclose($output);
        exit;
    }

    private function sanitizeExportValue($value) {
        $text = trim((string)($value ?? ''));
        $text = preg_replace('/\s+/u', ' ', $text);
        return $text;
    }
}
