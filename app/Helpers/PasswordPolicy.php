<?php
/**
 * Reglas mínimas para contraseñas nuevas o cambiadas.
 * No afecta a las contraseñas que ya existen.
 */
class PasswordPolicy {
    public const LONGITUD_MINIMA = 8;

    /**
     * @return string|null mensaje de error, o null si la contraseña es aceptable
     */
    public static function validar(string $contrasena): ?string {
        if (strlen($contrasena) < self::LONGITUD_MINIMA) {
            return 'La contraseña debe tener mínimo ' . self::LONGITUD_MINIMA . ' caracteres.';
        }
        if (!preg_match('/[A-Za-z]/', $contrasena) || !preg_match('/[0-9]/', $contrasena)) {
            return 'La contraseña debe combinar letras y números.';
        }
        return null;
    }
}
