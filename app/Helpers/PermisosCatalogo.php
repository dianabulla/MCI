<?php
/**
 * Catálogo de acciones avanzadas por módulo (además de ver/crear/editar/eliminar).
 * El administrador las activa en la pantalla de Permisos; el código consulta con
 * AuthController::tienePermiso($modulo, $claveAccion).
 */
class PermisosCatalogo {

    /**
     * @return array<string, array<string, array{label:string, descripcion:string}>>
     */
    public static function accionesPorModulo(): array {
        return [
            'personas' => [
                'exportar_excel' => [
                    'label' => 'Exportar Excel',
                    'descripcion' => 'Descargar exportaciones de listados (Ganar, etc.) sin necesidad de permiso completo de editar.',
                ],
                'gestionar_cuenta_acceso' => [
                    'label' => 'Gestionar cuenta de acceso',
                    'descripcion' => 'Crear o editar usuario/contraseña de acceso al sistema para personas (cuando aplique en formularios).',
                ],
            ],
            'celulas' => [
                'exportar_datos' => [
                    'label' => 'Exportar datos',
                    'descripcion' => 'Permitir exportaciones desde el módulo de células.',
                ],
            ],
            'programas' => [
                'coordinacion_total' => [
                    'label' => 'Coordinación total (solo ámbito programas)',
                    'descripcion' => 'Privilegios amplios tipo administrador solo en Programas, Personas vinculadas (editar/gestionar inscritos), escuelas de formación, materiales UV/Cap. Destino y pagos/abonos de escuela. No abre el resto del sistema (células, ministerios, reportes generales, etc.). Marcar solo en roles de coordinación de formación.',
                ],
                'ver_universidad_vida' => [
                    'label' => 'Programas: Universidad de la Vida',
                    'descripcion' => 'Acceso al consolidado y vistas de Universidad de la Vida dentro de Programas.',
                ],
                'ver_capacitacion_destino' => [
                    'label' => 'Programas: Capacitación Destino',
                    'descripcion' => 'Acceso al consolidado y vistas de Capacitación Destino dentro de Programas.',
                ],
                'exportar_consolidado' => [
                    'label' => 'Exportar consolidado programas',
                    'descripcion' => 'Exportar planillas del consolidado de programas de formación.',
                ],
            ],
            'escuelas_formacion' => [
                'ver_matriz_completa' => [
                    'label' => 'Ver matriz completa',
                    'descripcion' => 'Ver todas las columnas y fechas en la matriz de escuelas.',
                ],
            ],
            'reportes' => [
                'exportar' => [
                    'label' => 'Exportar reportes',
                    'descripcion' => 'Exportar tablas y datasets de reportes a Excel/CSV.',
                ],
                'ver_dashboard_auditoria' => [
                    'label' => 'Dashboards sensibles',
                    'descripcion' => 'Acceso a dashboards o vistas agregadas restringidas.',
                ],
            ],
            'ministerios' => [
                'editar_metas' => [
                    'label' => 'Editar metas ministeriales',
                    'descripcion' => 'Modificar metas y configuración numérica del ministerio.',
                ],
            ],
            'eventos' => [
                'gestionar_contenido_publico' => [
                    'label' => 'Contenido y QR público',
                    'descripcion' => 'Gestionar enlaces públicos, códigos y material difundido del módulo de eventos.',
                ],
            ],
            'peticiones' => [
                'moderar' => [
                    'label' => 'Moderar peticiones',
                    'descripcion' => 'Ocultar, destacar o gestionar peticiones de terceros.',
                ],
            ],
            'discipular_evaluaciones' => [
                'calificar_terceros' => [
                    'label' => 'Calificar a otros',
                    'descripcion' => 'Registrar resultados de evaluaciones en nombre de otros usuarios (rol formativo).',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{label:string, descripcion:string}>
     */
    public static function accionesParaModulo(string $modulo): array {
        $modulo = trim($modulo);
        $todas = self::accionesPorModulo();
        return $todas[$modulo] ?? [];
    }

    public static function esAccionValida(string $modulo, string $clave): bool {
        $clave = strtolower(trim($clave));
        if ($clave === '' || $modulo === '') {
            return false;
        }
        return isset(self::accionesPorModulo()[$modulo][$clave]);
    }

    /**
     * @param mixed $filaPermiso Fila de la tabla permisos
     * @return array<string, int>
     */
    public static function mapaDesdeFila(array $filaPermiso): array {
        $raw = $filaPermiso['Acciones_Extra'] ?? null;
        if ($raw === null || $raw === '') {
            return [];
        }
        if (is_array($raw)) {
            $decoded = $raw;
        } else {
            $decoded = json_decode((string)$raw, true);
        }
        if (!is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $k => $v) {
            $k = strtolower(trim((string)$k));
            if ($k === '') {
                continue;
            }
            $out[$k] = !empty($v) ? 1 : 0;
        }
        return $out;
    }

    /**
     * @param array<string, int> $mapa
     */
    public static function jsonDesdeMapa(array $mapa): string {
        $clean = [];
        foreach ($mapa as $k => $v) {
            $k = strtolower(trim((string)$k));
            if ($k === '') {
                continue;
            }
            $clean[$k] = !empty($v) ? 1 : 0;
        }
        ksort($clean, SORT_STRING);
        $json = json_encode($clean, JSON_UNESCAPED_UNICODE);
        return $json !== false ? $json : '{}';
    }

    /**
     * Roles cuyos permisos no se editan desde esta pantalla (solo lectura / bloqueado).
     * Importante: NO usar substr "admin" suelto: palabras como "Administrativo" activaban
     * falsos positivos y bloqueaban roles de UV u otros.
     */
    public static function esRolProtegidoPermisos(int $idRol, string $nombreRol = ''): bool {
        if ($idRol === 6) {
            return true;
        }
        $nombreRol = trim($nombreRol);
        if ($nombreRol === '') {
            return false;
        }
        $norm = mb_strtolower($nombreRol, 'UTF-8');
        if ($norm === 'admin') {
            return true;
        }
        return (bool)preg_match('/\badministrador\b/u', $norm);
    }
}
