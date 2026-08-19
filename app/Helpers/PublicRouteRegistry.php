<?php
/**
 * Rutas que deben abrir sin autenticación (formularios públicos, eventos, etc.).
 */
class PublicRouteRegistry {
    /**
     * @return list<string>
     */
    public static function rutasPublicas(): array {
        return [
            'auth/login',
            'auth/cambiar-cuenta',
            'registro_obsequio',
            'registro_obsequio/guardar',
            'registro_personas',
            'registro_personas/guardar',
            'teen/registro-publico',
            'teen/guardar-menor-publico',
            'teen/consulta-codigo',
            'teen/buscar-menor-publico-telefono',
            'teen/buscar-menor-publico-documento',
            'escuelas_formacion/codigos',
            'escuelas_formacion/registro-publico/universidad-vida',
            'escuelas_formacion/registro-publico/capacitacion-destino',
            'escuelas_formacion/registro-publico/buscar-persona',
            'escuelas_formacion/registro-publico/buscar-lideres',
            'escuelas_formacion/registro-publico/validar-abono',
            'escuelas_formacion/registro-publico/guardar',
            'escuelas_formacion/registro-publico/subir-documentos',
            'escuelas_formacion/registro-publico/ticket',
            'escuelas_formacion/asistencia-publica',
            'escuelas_formacion/asistencia-publica/buscar',
            'escuelas_formacion/asistencia-publica/guardar',
            'peticiones_publica',
            'peticiones_publica/guardar',
            'talleres_publico',
            'talleres_publico/guardar',
            'talleres_publico/buscar-persona',
            'talleres_publico/qr',
            'talleres_publico/servicio-social',
            'talleres_publico/servicio-social/guardar',
            'talleres_publico/servicio-social/buscar-persona',
            'talleres_publico/servicio-social/disponibilidad',
            'stream/live',
            'stream/gallery',
            'eventos/proximos',
            'eventos/compartir',
            'eventos/universidad-vida/publico',
            'eventos/capacitacion-destino/publico',
            'eventos/otros/publico',
            'transmisiones-publico',
            'nehemias',
            'nehemias/formulario',
            'nehemias/guardar',
            'nehemias/testigos-electorales/formulario',
            'nehemias/testigos-electorales/guardar',
            'nehemias/whatsapp/webhook',
        ];
    }

    public static function normalizarUrlSolicitada(string $url): string {
        $url = str_replace('\\', '/', trim($url));
        $url = trim($url, '/');

        if ($url === '') {
            return 'home';
        }

        if (($pos = strpos($url, '?')) !== false) {
            $url = substr($url, 0, $pos);
        }
        if (($pos = strpos($url, '&')) !== false) {
            $url = substr($url, 0, $pos);
        }

        $url = preg_replace('#^(?:public/)?index\.php/?#', '', $url) ?? $url;
        $url = preg_replace('#^public/#', '', $url) ?? $url;

        return trim($url, '/') ?: 'home';
    }

    public static function esRutaPublica(string $url): bool {
        $url = self::normalizarUrlSolicitada($url);

        if (in_array($url, self::rutasPublicas(), true)) {
            return true;
        }

        if ($url === 'eventos/proximos' || $url === 'eventos/compartir') {
            return true;
        }

        if (preg_match('#^eventos/[a-z0-9_-]+/publico$#i', $url)) {
            return true;
        }

        return false;
    }

    /**
     * Si alguien abre la ruta privada de eventos sin sesión, redirigir a la vista pública equivalente.
     */
    public static function rutaPublicaEventosParaPrivada(string $url): ?string {
        $url = self::normalizarUrlSolicitada($url);

        $mapa = [
            'eventos' => 'eventos/proximos',
            'eventos/universidad-vida' => 'eventos/universidad-vida/publico',
            'eventos/capacitacion-destino' => 'eventos/capacitacion-destino/publico',
            'eventos/otros' => 'eventos/otros/publico',
        ];

        return $mapa[$url] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function rutasEventosPublicosFallback(): array {
        return [
            'eventos/proximos' => 'EventoController@proximosPublico',
            'eventos/compartir' => 'EventoController@compartirPublico',
            'eventos/universidad-vida/publico' => 'EventoController@universidadVidaPublico',
            'eventos/capacitacion-destino/publico' => 'EventoController@capacitacionDestinoPublico',
            'eventos/otros/publico' => 'EventoController@otrosPublico',
        ];
    }
}
