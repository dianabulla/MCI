-- Paso 2: copiar login de persona a usuario_acceso
-- Sirve igual con 31 cuentas (local) o cientos (producción).
-- NO borra persona.Usuario / persona.Contrasena.
-- Preferible: botón en Cuentas → "Copiar cuentas a usuario_acceso"
--   (el PHP hashea claves en texto plano; este SQL copia el hash tal cual).
--
-- 1) Vista previa (cuántas faltan):

SELECT
    p.Id_Persona,
    p.Usuario,
    TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre,
    p.Id_Rol,
    r.Nombre_Rol
FROM persona p
LEFT JOIN rol r ON r.Id_Rol = p.Id_Rol
LEFT JOIN usuario_acceso ua ON ua.Id_Persona = p.Id_Persona
WHERE p.Usuario IS NOT NULL
  AND TRIM(p.Usuario) <> ''
  AND p.Contrasena IS NOT NULL
  AND TRIM(p.Contrasena) <> ''
  AND ua.Id_Usuario_Acceso IS NULL
ORDER BY p.Usuario;

-- 2) Copiar (omite si el usuario ya existe o la persona ya tiene cuenta):

INSERT INTO usuario_acceso (
    Usuario,
    Contrasena,
    Nombre_Mostrar,
    Id_Rol,
    Id_Ministerio,
    Id_Persona,
    Estado_Cuenta,
    Ultimo_Acceso
)
SELECT
    p.Usuario,
    p.Contrasena,
    TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))),
    p.Id_Rol,
    p.Id_Ministerio,
    p.Id_Persona,
    COALESCE(NULLIF(p.Estado_Cuenta, ''), 'Activo'),
    p.Ultimo_Acceso
FROM persona p
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

-- 3) Comprobar:

SELECT
    (SELECT COUNT(*) FROM persona WHERE Usuario IS NOT NULL AND TRIM(Usuario) <> '' AND Contrasena IS NOT NULL AND TRIM(Contrasena) <> '') AS login_en_persona,
    (SELECT COUNT(*) FROM usuario_acceso WHERE Id_Persona IS NOT NULL) AS cuentas_vinculadas;

-- 4) NO ejecutes esto hasta probar login en producción:
-- UPDATE persona p
-- INNER JOIN usuario_acceso ua ON ua.Id_Persona = p.Id_Persona AND LOWER(TRIM(ua.Usuario)) = LOWER(TRIM(p.Usuario))
-- SET p.Usuario = NULL, p.Contrasena = NULL;
