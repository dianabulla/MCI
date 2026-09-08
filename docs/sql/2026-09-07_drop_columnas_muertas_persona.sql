-- Quita de persona columnas que ya no usa la ficha ni el login.
-- NO toca Email, Edad, Convencion ni la tabla peticion.
-- Ejecutar DESPUÉS de copiar cuentas (Cuentas → Copiar, o el INSERT de abajo).
-- MariaDB 10.4+ / MySQL 8.0.29+: DROP COLUMN IF EXISTS.

-- 1) Copiar login pendiente (omite si la persona ya tiene cuenta o el usuario ya existe).
INSERT INTO usuario_acceso (
    Usuario,
    Contrasena,
    Nombre_Mostrar,
    Id_Rol,
    Id_Ministerio,
    Id_Persona,
    Estado_Cuenta,
    Ultimo_Acceso,
    Acuerdo_Confidencialidad_At,
    Acuerdo_Confidencialidad_Version
)
SELECT
    p.Usuario,
    p.Contrasena,
    TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))),
    p.Id_Rol,
    CASE WHEN m.Id_Ministerio IS NULL THEN NULL ELSE p.Id_Ministerio END,
    p.Id_Persona,
    'Activo',
    p.Ultimo_Acceso,
    p.Acuerdo_Confidencialidad_At,
    p.Acuerdo_Confidencialidad_Version
FROM persona p
LEFT JOIN ministerio m ON m.Id_Ministerio = p.Id_Ministerio
LEFT JOIN usuario_acceso ua_persona ON ua_persona.Id_Persona = p.Id_Persona
LEFT JOIN usuario_acceso ua_usuario ON ua_usuario.Usuario = p.Usuario
WHERE p.Usuario IS NOT NULL
  AND TRIM(p.Usuario) <> ''
  AND p.Contrasena IS NOT NULL
  AND TRIM(p.Contrasena) <> ''
  AND p.Id_Rol IS NOT NULL
  AND p.Id_Rol > 0
  AND ua_persona.Id_Usuario_Acceso IS NULL
  AND ua_usuario.Id_Usuario_Acceso IS NULL;

-- 2) Pasar acuerdo ya aceptado en ficha a la cuenta de acceso (no pisa si ya hay fecha en ua).
UPDATE usuario_acceso ua
INNER JOIN persona p ON p.Id_Persona = ua.Id_Persona
SET
    ua.Acuerdo_Confidencialidad_At = COALESCE(ua.Acuerdo_Confidencialidad_At, p.Acuerdo_Confidencialidad_At),
    ua.Acuerdo_Confidencialidad_Version = COALESCE(NULLIF(TRIM(ua.Acuerdo_Confidencialidad_Version), ''), p.Acuerdo_Confidencialidad_Version)
WHERE p.Acuerdo_Confidencialidad_At IS NOT NULL
  AND ua.Acuerdo_Confidencialidad_At IS NULL;

-- 3) Borrar columnas muertas.
ALTER TABLE persona
    DROP COLUMN IF EXISTS Peticion,
    DROP COLUMN IF EXISTS Hora_Llamada,
    DROP COLUMN IF EXISTS Fecha_Registro_Unix,
    DROP COLUMN IF EXISTS Usuario,
    DROP COLUMN IF EXISTS Contrasena,
    DROP COLUMN IF EXISTS Ultimo_Acceso,
    DROP COLUMN IF EXISTS Acuerdo_Confidencialidad_At,
    DROP COLUMN IF EXISTS Acuerdo_Confidencialidad_Version;
