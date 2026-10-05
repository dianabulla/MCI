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
        header('Location: ' . public_app_url($url));
        exit;
    }

    /**
     * Devolver JSON
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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

    protected function validarNombreYApellidoPersona($nombre, $apellido): array {
        $nombre = $this->normalizarCampoNombrePersona($nombre);
        $apellido = $this->normalizarCampoNombrePersona($apellido);
        $errores = [];

        if ($nombre === '') {
            $errores[] = 'El nombre es requerido.';
        } elseif (!$this->esTextoNombrePersonaValido($nombre)) {
            $errores[] = 'El nombre solo puede tener letras (sin números ni símbolos).';
        } elseif ($this->esMarcadorFalsoNombrePersona($nombre)) {
            $errores[] = 'Escriba un nombre real. No use valores como X, N/A o prueba.';
        } elseif ($this->contarPalabrasSignificativasNombrePersona($nombre) > 3) {
            $errores[] = 'En Nombre escriba solo el o los nombres. Los apellidos van en el campo Apellidos.';
        }

        if ($apellido === '') {
            $errores[] = 'Los apellidos son requeridos.';
        } elseif (!$this->esTextoNombrePersonaValido($apellido)) {
            $errores[] = 'Los apellidos solo pueden tener letras (sin números ni símbolos).';
        } elseif ($this->esMarcadorFalsoNombrePersona($apellido)) {
            $errores[] = 'Escriba los apellidos reales. No use X, N/A, un punto ni un valor de relleno.';
        } elseif ($this->contarPalabrasSignificativasNombrePersona($apellido) < 1) {
            $errores[] = 'Escriba al menos un apellido real (mínimo 2 letras).';
        }

        if (empty($errores) && $this->normalizarClaveNombrePersona($nombre) === $this->normalizarClaveNombrePersona($apellido)) {
            $errores[] = 'Nombre y apellidos no pueden ser iguales. Separe nombres y apellidos en cada campo.';
        }

        return $errores;
    }

    protected function primerErrorNombreYApellidoPersona($nombre, $apellido): ?string {
        $errores = $this->validarNombreYApellidoPersona($nombre, $apellido);
        if (empty($errores)) {
            return null;
        }

        return (string)$errores[0];
    }

    private function normalizarCampoNombrePersona($texto): string {
        $texto = preg_replace('/\s+/u', ' ', trim((string)$texto));
        return $texto === null ? '' : $texto;
    }

    private function esTextoNombrePersonaValido($texto): bool {
        return (bool)preg_match('/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\'’\-\s]+$/u', (string)$texto);
    }

    private function esParticulaNombrePersona($palabra): bool {
        $clave = $this->normalizarClaveNombrePersona($palabra);
        return in_array($clave, ['de', 'del', 'la', 'las', 'los', 'y', 'da', 'do', 'dos', 'das', 'van', 'von', 'san', 'santa', 'di', 'el', 'e'], true);
    }

    private function contarPalabrasSignificativasNombrePersona($texto): int {
        $palabras = preg_split('/\s+/u', $this->normalizarCampoNombrePersona($texto), -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($palabras)) {
            return 0;
        }

        $total = 0;
        foreach ($palabras as $palabra) {
            if ($this->esParticulaNombrePersona($palabra)) {
                continue;
            }
            $letras = preg_replace('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/u', '', (string)$palabra);
            if ($letras !== null && mb_strlen($letras) >= 2) {
                $total++;
            }
        }

        return $total;
    }

    private function esMarcadorFalsoNombrePersona($texto): bool {
        $clave = $this->normalizarClaveNombrePersona($texto);
        if ($clave === '') {
            return true;
        }

        $prohibidos = [
            'x', 'xx', 'xxx', 'xxxx', 'xxxxx',
            'n', 'na', 'nd', 'nn', 'sn', 'nna', 'nde',
            'no', 'si', 'ok', 'asd', 'qwe', 'qwer', 'abc', 'xyz',
            'aaa', 'bbb', 'ccc', 'nnn',
            'test', 'prueba', 'temp', 'fake', 'none', 'null',
            'nombre', 'nombres', 'apellido', 'apellidos',
            'noaplica', 'sinapellido', 'desconocido', 'ninguno', 'ninguna',
            'pendiente', 'completar', 'tbd', 'naa'
        ];
        if (in_array($clave, $prohibidos, true)) {
            return true;
        }

        if (preg_match('/^(x+|n+|a+|z+)$/u', $clave)) {
            return true;
        }

        $letras = preg_replace('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/u', '', (string)$texto);
        if ($letras === null || $letras === '') {
            return true;
        }

        $longitud = mb_strlen($letras);
        if ($longitud < 2) {
            return true;
        }

        return false;
    }

    private function normalizarClaveNombrePersona($texto): string {
        $texto = $this->normalizarCampoNombrePersona($texto);
        $texto = mb_strtolower($texto);
        $texto = strtr($texto, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
            '’' => '',
            "'" => ''
        ]);
        $texto = preg_replace('/[^a-z]/u', '', $texto);
        return $texto === null ? '' : $texto;
    }
}
