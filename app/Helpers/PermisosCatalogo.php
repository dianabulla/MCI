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
                    'descripcion' => 'Privilegios amplios en Programas (consolidados, pagos, dashboards, formularios). No abre el resto del sistema.',
                ],
                'ver_universidad_vida' => [
                    'label' => 'Ver consolidado Universidad de la Vida',
                    'descripcion' => 'Listado e inscritos del programa Universidad de la Vida.',
                ],
                'ver_capacitacion_destino' => [
                    'label' => 'Ver consolidado Capacitación Destino',
                    'descripcion' => 'Listado e inscritos de Capacitación Destino.',
                ],
                'dashboard_universidad_vida' => [
                    'label' => 'Dashboard Universidad de la Vida',
                    'descripcion' => 'Tablero estadístico UV (solo datos de su alcance).',
                ],
                'dashboard_capacitacion_destino' => [
                    'label' => 'Dashboard Capacitación Destino',
                    'descripcion' => 'Tablero estadístico Cap. Destino (solo datos de su alcance).',
                ],
                'gestionar_pagos_universidad_vida' => [
                    'label' => 'Gestionar pagos Universidad de la Vida',
                    'descripcion' => 'Pantalla de pagos y abonos UV.',
                ],
                'gestionar_pagos_capacitacion_destino' => [
                    'label' => 'Gestionar pagos Capacitación Destino',
                    'descripcion' => 'Pantalla de pagos Cap. Destino.',
                ],
                'formulario_universidad_vida' => [
                    'label' => 'Formulario público Universidad de la Vida',
                    'descripcion' => 'Enlace al formulario de inscripción UV.',
                ],
                'formulario_capacitacion_destino' => [
                    'label' => 'Formulario público Capacitación Destino',
                    'descripcion' => 'Enlace al formulario de inscripción Cap. Destino.',
                ],
                'asistencias_universidad_vida' => [
                    'label' => 'Asistencias Universidad de la Vida',
                    'descripcion' => 'Vista de asistencias del programa UV.',
                ],
                'asistencias_capacitacion_destino' => [
                    'label' => 'Asistencias Capacitación Destino',
                    'descripcion' => 'Matriz de asistencias por nivel de Capacitación Destino.',
                ],
                'exportar_consolidado' => [
                    'label' => 'Exportar consolidado programas',
                    'descripcion' => 'Descargar planillas del consolidado.',
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
            'discipular_evaluaciones' => [
                'calificar_terceros' => [
                    'label' => 'Calificar a otros',
                    'descripcion' => 'Registrar resultados de evaluaciones en nombre de otros usuarios (rol formativo).',
                ],
            ],
            'asistencias' => [
                'exportar_excel' => [
                    'label' => 'Exportar asistencias',
                    'descripcion' => 'Descargar planillas de asistencias a Excel.',
                ],
            ],
            'teen' => [
                'exportar_datos' => [
                    'label' => 'Exportar datos teens',
                    'descripcion' => 'Exportar listados del módulo Teens.',
                ],
            ],
            'nehemias' => [
                'importar_masivo' => [
                    'label' => 'Importación masiva',
                    'descripcion' => 'Importar archivos y reparaciones masivas en Nehemias.',
                ],
            ],
            'transmisiones' => [
                'exportar_excel' => [
                    'label' => 'Exportar transmisiones',
                    'descripcion' => 'Exportar listado de transmisiones.',
                ],
            ],
            'talleres' => [
                'ver_respuestas' => [
                    'label' => 'Ver respuestas de inscritos',
                    'descripcion' => 'Consultar el detalle de cada inscripción y la lista de respuestas.',
                ],
                'ver_graficas' => [
                    'label' => 'Ver gráficas del cuestionario',
                    'descripcion' => 'Acceder a la pestaña de gráficas estadísticas del taller.',
                ],
                'exportar_excel' => [
                    'label' => 'Exportar respuestas a Excel',
                    'descripcion' => 'Descargar las respuestas del formulario en Excel.',
                ],
                'gestionar_enlace' => [
                    'label' => 'Abrir enlace público y QR',
                    'descripcion' => 'Abrir el formulario público y generar o imprimir el código QR de inscripción.',
                ],
            ],
            'entrega_obsequio' => [
                'exportar_pdf' => [
                    'label' => 'Exportar PDF obsequios',
                    'descripcion' => 'Generar PDF de entregas de obsequios.',
                ],
                'exportar_excel' => [
                    'label' => 'Exportar Excel obsequios',
                    'descripcion' => 'Exportar listado de obsequios a Excel.',
                ],
            ],
            'roles' => [
                'exportar_excel' => [
                    'label' => 'Exportar roles',
                    'descripcion' => 'Exportar matriz de roles a Excel.',
                ],
            ],
            'material' => [
                'gestionar_subida' => [
                    'label' => 'Gestionar subida global',
                    'descripcion' => 'Subir o reemplazar archivos en el centro de material.',
                ],
            ],
            'materiales_celulas' => [
                'exportar_datos' => [
                    'label' => 'Exportar material células',
                    'descripcion' => 'Exportar métricas o listados del material de células.',
                ],
            ],
            'escuelas_formacion' => [
                'exportar_inscritos' => [
                    'label' => 'Exportar inscritos',
                    'descripcion' => 'Exportar listados de inscritos y asistencias de escuelas.',
                ],
                'gestionar_pagos' => [
                    'label' => 'Gestionar pagos escuela',
                    'descripcion' => 'Consolidar y enviar pagos de formación.',
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

    /** Id habitual de Administrador en catálogo persona (no confundir con Pastores = 6). */
    public const ID_ROL_ADMINISTRADOR_PERSONA = 1;

    /** Id usado en semillas antiguas de permisos (sistema_autenticacion.sql). */
    public const ID_ROL_ADMINISTRADOR_LEGACY = 6;

    private static function normalizarNombreRol(string $nombreRol): string {
        return mb_strtolower(trim($nombreRol), 'UTF-8');
    }

    public static function nombreRolSinAcentos(string $nombreRol): string {
        $norm = self::normalizarNombreRol($nombreRol);
        return strtr($norm, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
    }

    /** Líder de 12 (no 144). */
    public static function esNombreRolLider12(string $nombreRol): bool {
        $n = self::nombreRolSinAcentos($nombreRol);
        if (strpos($n, 'lider') === false || strpos($n, '144') !== false) {
            return false;
        }
        return strpos($n, '12') !== false;
    }

    /** Líder de 144. */
    public static function esNombreRolLider144(string $nombreRol): bool {
        $n = self::nombreRolSinAcentos($nombreRol);
        return strpos($n, 'lider') !== false && strpos($n, '144') !== false;
    }

    /** Líder de célula (no el rol administrativo "Células"). */
    public static function esNombreRolLiderCelula(string $nombreRol): bool {
        $n = self::nombreRolSinAcentos($nombreRol);
        if (strpos($n, 'lider') === false) {
            return false;
        }
        return strpos($n, 'celula') !== false;
    }

    public static function esNombreRolDiscipulo(string $nombreRol): bool {
        $n = self::nombreRolSinAcentos($nombreRol);
        return strpos($n, 'discipul') !== false
            || strpos($n, 'disipul') !== false
            || strpos($n, 'discipl') !== false
            || strpos($n, 'disipl') !== false;
    }

    /**
     * Rol ministerial por defecto al crear una persona.
     *
     * @param array<int, array<string, mixed>> $roles
     */
    public static function resolverIdRolDiscipuloDesdeCatalogo(array $roles): int {
        foreach ($roles as $rol) {
            $id = (int)($rol['Id_Rol'] ?? 0);
            $nombre = trim((string)($rol['Nombre_Rol'] ?? ''));
            if ($id > 0 && self::esNombreRolDiscipulo($nombre)) {
                return $id;
            }
        }
        return 0;
    }

    /**
     * Cuentas administrativas especiales (Ganar, Células, Administrador).
     * No son jerarquía ministerial: pastor → 12 → 144 → célula → discípulo.
     */
    public static function esRolAdministrativoEspecial(int $idRol, string $nombreRol): bool {
        if (self::esRolAdministradorGlobal($idRol, $nombreRol)) {
            return true;
        }
        if (self::esNombreRolLiderCelula($nombreRol) || self::esNombreRolLider144($nombreRol) || self::esNombreRolLider12($nombreRol)) {
            return false;
        }
        if (self::esNombreRolPastoral($nombreRol)) {
            return false;
        }

        $n = self::nombreRolSinAcentos($nombreRol);
        if ($n === '') {
            return false;
        }
        if ($n === 'celulas' || $n === 'celula') {
            return true;
        }
        if ($n === 'ganar' || strpos($n, 'ganar') === 0) {
            return true;
        }

        return false;
    }

    /** Maestro / Teacher (Capacitación Destino y material). */
    public static function esNombreRolMaestro(string $nombreRol): bool {
        $norm = self::normalizarNombreRol($nombreRol);
        if ($norm === '') {
            return false;
        }

        return strpos($norm, 'maestro') !== false
            || strpos($norm, 'teacher') !== false
            || strpos($norm, 'docente') !== false
            || strpos($norm, 'profesor') !== false;
    }

    /** Pastor / Pastores / Pastora — nunca administrador global. */
    public static function esNombreRolPastoral(string $nombreRol): bool {
        $norm = self::normalizarNombreRol($nombreRol);
        if ($norm === '') {
            return false;
        }

        return (bool)preg_match('/\bpastor(a|es)?\b/u', $norm);
    }

    /** Administrador global del sistema: solo título exacto (no subcadenas en roles personalizados). */
    public static function esNombreRolAdministrador(string $nombreRol): bool {
        $norm = self::normalizarNombreRol($nombreRol);
        if ($norm === '') {
            return false;
        }

        return in_array($norm, ['admin', 'administrador', 'administrator', 'administrador del sistema'], true);
    }

    /**
     * Rol administrador global (acceso total, matriz de permisos bloqueada).
     * Se reconoce por el nombre (Administrador / Admin). El id 6 en este
     * catálogo es Pastores, no administrador.
     */
    public static function esRolAdministradorGlobal(int $idRol, string $nombreRol = ''): bool {
        if (self::esNombreRolPastoral($nombreRol)) {
            return false;
        }

        if (self::esNombreRolAdministrador($nombreRol)) {
            return true;
        }

        $norm = self::normalizarNombreRol($nombreRol);
        if ($norm !== '') {
            return false;
        }

        return $idRol === self::ID_ROL_ADMINISTRADOR_PERSONA;
    }

    /**
     * Roles cuyos permisos no se editan desde la pantalla de permisos.
     */
    public static function esRolProtegidoPermisos(int $idRol, string $nombreRol = ''): bool {
        return self::esRolAdministradorGlobal($idRol, $nombreRol);
    }
}
