<?php
/**
 * Validaciones para evitar datos basura en fichas de persona.
 */
class PersonaValidacion
{
    public static function normalizarDocumento($documento): string
    {
        $documento = strtoupper(trim((string)$documento));
        return preg_replace('/[^A-Z0-9]/', '', $documento) ?? '';
    }

    public static function normalizarTelefono($telefono): string
    {
        return preg_replace('/\D+/', '', (string)$telefono) ?? '';
    }

    /**
     * @return string|null Mensaje de error o null si es válido
     */
    public static function validarDocumento($documento, $telefono = '', $tipoDocumento = ''): ?string
    {
        $doc = self::normalizarDocumento($documento);
        $tel = self::normalizarTelefono($telefono);

        if ($doc === '') {
            return 'El número de documento es obligatorio.';
        }

        if (preg_match('/^X+$/', $doc) || preg_match('/^0+$/', $doc)) {
            return 'El número de documento no puede ser solo ceros o X. Escribe el documento real.';
        }

        if (preg_match('/^(.)\1{3,}$/', $doc)) {
            return 'El número de documento no parece válido (dígitos repetidos).';
        }

        $placeholders = [
            '1', '01', '123', '1111', '2222', '1001', '1234', '12345',
            'NOTIENE', 'NOTIENECEL', 'XXXX', 'XXX', 'OOOO', 'OOO',
            '1111111111', '333333333', '1234567890',
        ];
        if (in_array($doc, $placeholders, true)) {
            return 'El número de documento no puede ser un valor de prueba o placeholder.';
        }

        if (strlen($doc) < 5) {
            return 'El número de documento es demasiado corto. Usa el documento real (mínimo 5 caracteres).';
        }

        if (strlen($doc) > 15) {
            return 'El número de documento es demasiado largo.';
        }

        // No permitir meter el celular en la cédula
        if ($tel !== '' && $doc === $tel) {
            return 'El número de documento no puede ser igual al teléfono.';
        }

        // Celular colombiano (10 dígitos empezando en 3) o con 57
        if (preg_match('/^3\d{9}$/', $doc) || preg_match('/^573\d{9}$/', $doc)) {
            return 'El número de documento parece un teléfono. Escribe la cédula o registro civil real.';
        }

        // Tipo CC/TI: preferir solo dígitos
        $tipo = strtoupper(trim((string)$tipoDocumento));
        $tipo = strtr($tipo, ['Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U']);
        if ($tipo !== '' && !preg_match('/EXTRANJER/', $tipo) && !preg_match('/^\d+$/', $doc)) {
            return 'El número de documento debe contener solo números (sin letras ni símbolos).';
        }

        return null;
    }

    /**
     * @return string|null
     */
    public static function validarTelefono($telefono): ?string
    {
        $raw = trim((string)$telefono);
        if ($raw === '') {
            return 'El teléfono es obligatorio.';
        }

        $tel = self::normalizarTelefono($raw);
        if ($tel === '') {
            return 'El teléfono debe incluir dígitos válidos.';
        }

        if (preg_match('/^0+$/', $tel) || in_array($tel, ['1', '12', '123', '0000000', '0000000000', '300000'], true)) {
            return 'El teléfono no puede ser un valor de prueba o solo ceros.';
        }

        if (preg_match('/^(.)\1{5,}$/', $tel)) {
            return 'El teléfono no parece válido (dígitos repetidos).';
        }

        if (strlen($tel) < 7) {
            return 'El teléfono debe tener al menos 7 dígitos.';
        }

        if (strlen($tel) > 15) {
            return 'El teléfono es demasiado largo.';
        }

        return null;
    }

    /**
     * Fecha de nacimiento real: no hoy, no futuro, no más de 120 años.
     * Sí permite menores nacidos este año (p. ej. ayer).
     *
     * @return string|null
     */
    public static function validarFechaNacimiento($fecha): ?string
    {
        $fecha = trim((string)$fecha);
        if ($fecha === '' || $fecha === '0000-00-00') {
            return 'La fecha de nacimiento es obligatoria.';
        }

        $tz = new DateTimeZone('America/Bogota');
        try {
            $nac = DateTimeImmutable::createFromFormat('Y-m-d', substr($fecha, 0, 10), $tz);
            if (!$nac || $nac->format('Y-m-d') !== substr($fecha, 0, 10)) {
                return 'La fecha de nacimiento no es válida.';
            }
        } catch (Throwable $e) {
            return 'La fecha de nacimiento no es válida.';
        }

        $hoy = new DateTimeImmutable('today', $tz);
        if ($nac >= $hoy) {
            return 'La fecha de nacimiento no puede ser hoy ni una fecha futura. Usa la fecha real de nacimiento.';
        }

        $max = $hoy->modify('-120 years');
        if ($nac < $max) {
            return 'La fecha de nacimiento no puede ser mayor a 120 años.';
        }

        return null;
    }

    /**
     * Valida documento + teléfono + fecha de una vez.
     *
     * @return string|null Primer error encontrado
     */
    public static function validarIdentidadBasica($documento, $telefono, $fechaNacimiento, $tipoDocumento = ''): ?string
    {
        $errTel = self::validarTelefono($telefono);
        if ($errTel !== null) {
            return $errTel;
        }
        $errDoc = self::validarDocumento($documento, $telefono, $tipoDocumento);
        if ($errDoc !== null) {
            return $errDoc;
        }
        return self::validarFechaNacimiento($fechaNacimiento);
    }

    /** Fecha máxima permitida en inputs HTML (ayer). */
    public static function fechaNacimientoMaxHtml(): string
    {
        return (new DateTimeImmutable('yesterday', new DateTimeZone('America/Bogota')))->format('Y-m-d');
    }
}
