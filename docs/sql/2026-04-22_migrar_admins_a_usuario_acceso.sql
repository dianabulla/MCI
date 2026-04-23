-- Migracion no destructiva de administradores desde persona hacia usuario_acceso
-- Requisitos:
--   1) Haber ejecutado 2026-04-22_usuario_acceso_no_destructivo.sql
--   2) Mantener intactos persona.Usuario y persona.Contrasena
-- Objetivo:
--   Copiar administradores existentes al nuevo esquema de acceso, sin borrar nada.

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
LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
LEFT JOIN usuario_acceso ua ON ua.Usuario = p.Usuario
WHERE p.Usuario IS NOT NULL
  AND TRIM(p.Usuario) <> ''
  AND p.Contrasena IS NOT NULL
  AND TRIM(p.Contrasena) <> ''
  AND ua.Id_Usuario_Acceso IS NULL
  AND (
        p.Id_Rol = 6
        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%admin%'
      );
