<?php

/**
 * Detecta intención del usuario a partir de texto en español (reglas, sin IA).
 */
class ChatbotIntentService {

    /**
     * @return array{intent: string, params: array<string, mixed>, confidence: string}
     */
    public function parse(string $message): array {
        $original = trim($message);
        $msg = mb_strtolower($original, 'UTF-8');
        $msg = preg_replace('/\s+/u', ' ', $msg) ?? '';

        if ($msg === '') {
            return $this->result('vacio', []);
        }

        if (preg_match('/^(hola|buenos\s+d[ií]as|buenas\s+tardes|buenas\s+noches|hey|saludos)\b/u', $msg)) {
            return $this->result('saludo', []);
        }

        if (preg_match('/^(ayuda|help|menu|menú|opciones|qu[eé]\s+puedes|comandos)\b/u', $msg)) {
            return $this->result('ayuda', []);
        }

        if ($this->matchReporteEquipoRed($msg)) {
            return $this->result('reporte_equipo_red', $this->parseParamsRed($msg));
        }

        if ($this->matchReporteMetasBajo($msg)) {
            return $this->result('reporte_metas_bajo', $this->parseParamsMetas($msg));
        }

        if ($this->matchReporteGanadosIglesia($msg)) {
            $params = $this->parsePeriodo($msg);
            $params['desglose_semanal'] = $this->quiereDesgloseSemanal($msg);
            return $this->result('reporte_ganados_iglesia', $params);
        }

        if ($this->matchReporteGanados($msg)) {
            return $this->result('reporte_ganados', $this->parsePeriodo($msg));
        }

        if ($this->matchReporteProceso($msg)) {
            return $this->result('reporte_proceso', $this->parsePeriodo($msg));
        }

        if (preg_match('/(ir\s+a|abrir|ver|mostrar)\s+(los\s+)?reportes/u', $msg)) {
            return $this->result('navegar', ['destino' => 'reportes']);
        }

        if (preg_match('/(ir\s+a|abrir|ver)\s+(las\s+)?personas/u', $msg)) {
            return $this->result('navegar', ['destino' => 'personas']);
        }

        if (preg_match('/(ir\s+a|abrir|ver)\s+(discipular|equipo\s+principal)/u', $msg)) {
            return $this->result('navegar', ['destino' => 'discipular']);
        }

        $terminoBusqueda = $this->extraerTerminoBusqueda($original, $msg);
        if ($terminoBusqueda !== '') {
            return $this->result('buscar_persona', ['termino' => $terminoBusqueda]);
        }

        if (mb_strlen($original) >= 3 && !$this->parecePreguntaGeneral($msg)) {
            return $this->result('buscar_persona', ['termino' => $original]);
        }

        return $this->result('desconocido', ['texto' => $original]);
    }

    private function matchReporteGanadosIglesia(string $msg): bool {
        if (preg_match('/(ganad|almas?).*(iglesia|domingo).*(celula|c[eé]lula|ubicad)/u', $msg)) {
            return true;
        }
        if (preg_match('/(iglesia|domingo).*(ganad|ubicad).*(celula|c[eé]lula)/u', $msg)) {
            return true;
        }
        if (preg_match('/ubicad.*celula|c[eé]lula.*ubicad/u', $msg)) {
            return true;
        }
        if (preg_match('/(cu[aá]ntos|total|reporte|resumen).*(iglesia|domingo).*(celula|c[eé]lula|ubicad)/u', $msg)) {
            return true;
        }
        return false;
    }

    private function matchReporteEquipoRed(string $msg): bool {
        if (preg_match('/(red\s+(de\s+)?(hombres|mujeres)|equipo\s+completo|12\s*\/\s*12|12\s+de\s+12)/u', $msg)
            && preg_match('/(lider|equipo|12|red|pastor|pastora)/u', $msg)) {
            return true;
        }
        if (preg_match('/(hombres|mujeres).*(equipo\s+completo|lideres?\s+de\s+12|l[ií]deres?\s+de\s+12)/u', $msg)) {
            return true;
        }
        if (preg_match('/(cu[aá]les|quienes|qui[eé]nes).*(12|equipo\s+principal|equipo\s+completo)/u', $msg)
            && preg_match('/(red|hombres|mujeres|lider)/u', $msg)) {
            return true;
        }
        return false;
    }

    private function matchReporteMetasBajo(string $msg): bool {
        if (preg_match('/(bajo\s+rendimiento|rendimiento\s+bajo|van\s+mal|van\s+retrasad)/u', $msg)) {
            return true;
        }
        if (preg_match('/(critico|cr[ií]tico|en\s+riesgo|riesgo).*(meta|ganad|ministerio|semestre|mes)/u', $msg)) {
            return true;
        }
        if (preg_match('/(meta|metas).*(critico|cr[ií]tico|riesgo|bajo|semestre|mes|semana)/u', $msg)) {
            return true;
        }
        if (preg_match('/(semestre|mes|semana).*(meta|metas|rendimiento|critico|cr[ií]tico)/u', $msg)) {
            return true;
        }
        return false;
    }

    private function matchReporteGanados(string $msg): bool {
        if (preg_match('/(reporte|informe|resumen|cu[aá]ntos|total).*(ganad|almas?\s+ganad)/u', $msg)) {
            return true;
        }
        if (preg_match('/(ganad|almas?\s+ganad).*(mes|semana|periodo|per[ií]odo|a[nñ]o|semestre)/u', $msg)) {
            return true;
        }
        return preg_match('/^(ganados|almas\s+ganadas)\b/u', $msg) === 1;
    }

    private function matchReporteProceso(string $msg): bool {
        if (preg_match('/(proceso|embudo|funnel).*(ganar|consolidar|discipular)/u', $msg)) {
            return true;
        }
        if (preg_match('/(reporte|resumen|cu[aá]ntos).*(consolidar|discipular|proceso\s+ganar)/u', $msg)) {
            return true;
        }
        return false;
    }

    private function extraerTerminoBusqueda(string $original, string $msg): string {
        $patterns = [
            '/^(buscar|busca|encontrar|encuentra|localizar|dame\s+info\s+de|info\s+de|datos\s+de)\s+(a\s+|la\s+|al\s+|el\s+)?(.+)$/u',
            '/^(quien\s+es|quién\s+es)\s+(a\s+|la\s+|el\s+)?(.+)$/u',
            '/^(persona|cedula|c[eé]dula|tel[eé]fono|telefono)\s+(.+)$/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $msg, $m)) {
                $idx = count($m) - 1;
                $term = trim((string)($m[$idx] ?? ''));
                if ($term !== '') {
                    return $term;
                }
            }
        }

        return '';
    }

    private function parecePreguntaGeneral(string $msg): bool {
        return preg_match('/^(c[oó]mo|qu[eé]\s+es|d[oó]nde|para\s+qu[eé]|por\s+qu[eé]|cu[aá]ndo)\b/u', $msg) === 1;
    }

    private function quiereDesgloseSemanal(string $msg): bool {
        return preg_match('/por\s+semanas?|desglose\s+semanal|cada\s+semana/u', $msg) === 1;
    }

    /**
     * @return array{red: string}
     */
    private function parseParamsRed(string $msg): array {
        $red = '';
        if (preg_match('/mujer|femen|pastora/u', $msg)) {
            $red = 'mujeres';
        } elseif (preg_match('/hombre|mascul|pastor(?!a)/u', $msg)) {
            $red = 'hombres';
        }
        return ['red' => $red];
    }

    /**
     * @return array{periodo_meta: string, red: string, solo_bajo: bool}
     */
    private function parseParamsMetas(string $msg): array {
        $params = $this->parseParamsRed($msg);
        $params['periodo_meta'] = 'mes';
        $params['solo_bajo'] = true;

        if (preg_match('/semestre/u', $msg)) {
            $params['periodo_meta'] = 'semestre';
        } elseif (preg_match('/semana/u', $msg)) {
            $params['periodo_meta'] = 'semana';
        } elseif (preg_match('/a[nñ]o|anual/u', $msg)) {
            $params['periodo_meta'] = 'anio';
        } elseif (preg_match('/mes/u', $msg)) {
            $params['periodo_meta'] = 'mes';
        }

        if (preg_match('/(todas?|todos?|completo|general|resumen)/u', $msg) && !preg_match('/bajo/u', $msg)) {
            $params['solo_bajo'] = false;
        }

        return $params;
    }

    /**
     * @return array{fecha_inicio: string, fecha_fin: string, etiqueta: string}
     */
    private function parsePeriodo(string $msg): array {
        $hoy = new DateTime('today');

        if (preg_match('/a[nñ]o\s+pasado|[uú]ltimo\s+a[nñ]o/u', $msg)) {
            $anio = (int)$hoy->format('Y') - 1;
            return [
                'fecha_inicio' => $anio . '-01-01',
                'fecha_fin' => $anio . '-12-31',
                'etiqueta' => 'año pasado',
            ];
        }

        if (preg_match('/este\s+a[nñ]o|a[nñ]o\s+actual/u', $msg)) {
            $anio = (int)$hoy->format('Y');
            return [
                'fecha_inicio' => $anio . '-01-01',
                'fecha_fin' => $hoy->format('Y-m-d'),
                'etiqueta' => 'este año',
            ];
        }

        if (preg_match('/(1er|primer)\s+semestre/u', $msg)) {
            $anio = (int)$hoy->format('Y');
            return [
                'fecha_inicio' => $anio . '-01-01',
                'fecha_fin' => $anio . '-06-30',
                'etiqueta' => '1er semestre ' . $anio,
            ];
        }

        if (preg_match('/(2do|segundo)\s+semestre/u', $msg)) {
            $anio = (int)$hoy->format('Y');
            return [
                'fecha_inicio' => $anio . '-07-01',
                'fecha_fin' => $anio . '-12-31',
                'etiqueta' => '2do semestre ' . $anio,
            ];
        }

        if (preg_match('/semestre/u', $msg)) {
            $mes = (int)$hoy->format('n');
            $anio = (int)$hoy->format('Y');
            if ($mes <= 6) {
                return [
                    'fecha_inicio' => $anio . '-01-01',
                    'fecha_fin' => $anio . '-06-30',
                    'etiqueta' => '1er semestre ' . $anio,
                ];
            }
            return [
                'fecha_inicio' => $anio . '-07-01',
                'fecha_fin' => $anio . '-12-31',
                'etiqueta' => '2do semestre ' . $anio,
            ];
        }

        if (preg_match('/mes\s+pasado|[uú]ltimo\s+mes/u', $msg)) {
            $inicio = (clone $hoy)->modify('first day of last month');
            $fin = (clone $hoy)->modify('last day of last month');
            return [
                'fecha_inicio' => $inicio->format('Y-m-d'),
                'fecha_fin' => $fin->format('Y-m-d'),
                'etiqueta' => 'mes pasado',
            ];
        }

        if (preg_match('/esta\s+semana|semana\s+actual/u', $msg)) {
            $diaSemana = (int)$hoy->format('w');
            $inicio = (clone $hoy)->modify('-' . $diaSemana . ' days');
            $fin = (clone $inicio)->modify('+6 days');
            return [
                'fecha_inicio' => $inicio->format('Y-m-d'),
                'fecha_fin' => $fin->format('Y-m-d'),
                'etiqueta' => 'esta semana',
            ];
        }

        if (preg_match('/hoy/u', $msg)) {
            $fecha = $hoy->format('Y-m-d');
            return [
                'fecha_inicio' => $fecha,
                'fecha_fin' => $fecha,
                'etiqueta' => 'hoy',
            ];
        }

        $inicio = (clone $hoy)->modify('first day of this month');
        return [
            'fecha_inicio' => $inicio->format('Y-m-d'),
            'fecha_fin' => $hoy->format('Y-m-d'),
            'etiqueta' => 'este mes',
        ];
    }

    /**
     * @param array<string, mixed> $params
     * @return array{intent: string, params: array<string, mixed>, confidence: string}
     */
    private function result(string $intent, array $params): array {
        return [
            'intent' => $intent,
            'params' => $params,
            'confidence' => 'rule',
        ];
    }
}
