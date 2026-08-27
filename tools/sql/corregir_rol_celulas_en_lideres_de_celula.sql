-- ============================================================================
-- Corregir Células (admin) → Líder de célula, solo si dirigen una célula.
-- Sin variables @ (phpMyAdmin las pierde entre clics y el UPDATE no cambia nada).
-- Ejecutar en la base YA seleccionada (no hace falta USE).
-- ============================================================================

-- A) Cuántos hay con cada rol (para ver Células)
SELECT r.Id_Rol, r.Nombre_Rol, COUNT(p.Id_Persona) AS personas
FROM rol r
LEFT JOIN persona p ON p.Id_Rol = r.Id_Rol
GROUP BY r.Id_Rol, r.Nombre_Rol
ORDER BY personas DESC, r.Id_Rol;

-- B) Desglose del rol Células (esto explica por qué "siguen muchos")
SELECT
    SUM(CASE WHEN c.Id_Celula IS NOT NULL THEN 1 ELSE 0 END) AS con_celulas_Y_dirigen_celula,
    SUM(CASE WHEN c.Id_Celula IS NULL THEN 1 ELSE 0 END) AS con_celulas_Y_NO_dirigen_celula,
    COUNT(*) AS total_con_rol_celulas
FROM persona p
INNER JOIN rol rc ON rc.Id_Rol = p.Id_Rol
LEFT JOIN celula c ON c.Id_Lider = p.Id_Persona
WHERE rc.Nombre_Rol NOT LIKE '%ider%'
  AND rc.Nombre_Rol NOT LIKE '%Íder%'
  AND (
      rc.Nombre_Rol LIKE '%elulas%'
      OR rc.Nombre_Rol LIKE '%élulas%'
  );

-- C) Listado de los que SÍ se van a cambiar (líderes de célula con rol Células)
SELECT
    p.Id_Persona,
    p.Nombre,
    p.Apellido,
    p.Numero_Documento,
    rc.Nombre_Rol AS rol_actual,
    c.Id_Celula,
    c.Nombre_Celula
FROM persona p
INNER JOIN rol rc ON rc.Id_Rol = p.Id_Rol
INNER JOIN celula c ON c.Id_Lider = p.Id_Persona
WHERE rc.Nombre_Rol NOT LIKE '%ider%'
  AND rc.Nombre_Rol NOT LIKE '%Íder%'
  AND (
      rc.Nombre_Rol LIKE '%elulas%'
      OR rc.Nombre_Rol LIKE '%élulas%'
  )
ORDER BY p.Apellido, p.Nombre;

-- D) APLICAR: ficha de persona
UPDATE persona p
INNER JOIN (
    SELECT Id_Rol
    FROM rol
    WHERE Nombre_Rol NOT LIKE '%ider%'
      AND Nombre_Rol NOT LIKE '%Íder%'
      AND (Nombre_Rol LIKE '%elulas%' OR Nombre_Rol LIKE '%élulas%')
    ORDER BY Id_Rol
    LIMIT 1
) rc ON p.Id_Rol = rc.Id_Rol
INNER JOIN (
    SELECT Id_Rol
    FROM rol
    WHERE (Nombre_Rol LIKE '%ider%' OR Nombre_Rol LIKE '%Íder%')
      AND (Nombre_Rol LIKE '%elula%' OR Nombre_Rol LIKE '%élula%')
      AND Nombre_Rol NOT LIKE '%144%'
      AND Nombre_Rol NOT LIKE '%12%'
    ORDER BY Id_Rol
    LIMIT 1
) rl ON 1 = 1
SET p.Id_Rol = rl.Id_Rol
WHERE EXISTS (
    SELECT 1
    FROM celula c
    WHERE c.Id_Lider = p.Id_Persona
);

-- E) user_roles (si da error de tabla, ignóralo)
UPDATE user_roles ur
INNER JOIN (
    SELECT Id_Rol
    FROM rol
    WHERE Nombre_Rol NOT LIKE '%ider%'
      AND Nombre_Rol NOT LIKE '%Íder%'
      AND (Nombre_Rol LIKE '%elulas%' OR Nombre_Rol LIKE '%élulas%')
    ORDER BY Id_Rol
    LIMIT 1
) rc ON ur.Id_Rol = rc.Id_Rol
INNER JOIN (
    SELECT Id_Rol
    FROM rol
    WHERE (Nombre_Rol LIKE '%ider%' OR Nombre_Rol LIKE '%Íder%')
      AND (Nombre_Rol LIKE '%elula%' OR Nombre_Rol LIKE '%élula%')
      AND Nombre_Rol NOT LIKE '%144%'
      AND Nombre_Rol NOT LIKE '%12%'
    ORDER BY Id_Rol
    LIMIT 1
) rl ON 1 = 1
SET ur.Id_Rol = rl.Id_Rol
WHERE EXISTS (SELECT 1 FROM celula c WHERE c.Id_Lider = ur.Id_Persona)
  AND NOT EXISTS (
      SELECT 1
      FROM user_roles ur2
      WHERE ur2.Id_Persona = ur.Id_Persona
        AND ur2.Id_Rol = rl.Id_Rol
  );

DELETE ur
FROM user_roles ur
INNER JOIN (
    SELECT Id_Rol
    FROM rol
    WHERE Nombre_Rol NOT LIKE '%ider%'
      AND Nombre_Rol NOT LIKE '%Íder%'
      AND (Nombre_Rol LIKE '%elulas%' OR Nombre_Rol LIKE '%élulas%')
    ORDER BY Id_Rol
    LIMIT 1
) rc ON ur.Id_Rol = rc.Id_Rol
INNER JOIN (
    SELECT Id_Rol
    FROM rol
    WHERE (Nombre_Rol LIKE '%ider%' OR Nombre_Rol LIKE '%Íder%')
      AND (Nombre_Rol LIKE '%elula%' OR Nombre_Rol LIKE '%élula%')
      AND Nombre_Rol NOT LIKE '%144%'
      AND Nombre_Rol NOT LIKE '%12%'
    ORDER BY Id_Rol
    LIMIT 1
) rl ON 1 = 1
WHERE EXISTS (SELECT 1 FROM celula c WHERE c.Id_Lider = ur.Id_Persona)
  AND EXISTS (
      SELECT 1
      FROM user_roles ur2
      WHERE ur2.Id_Persona = ur.Id_Persona
        AND ur2.Id_Rol = rl.Id_Rol
  );

-- F) cuentas de acceso (si da error de tabla, ignóralo)
UPDATE usuario_acceso ua
INNER JOIN (
    SELECT Id_Rol
    FROM rol
    WHERE Nombre_Rol NOT LIKE '%ider%'
      AND Nombre_Rol NOT LIKE '%Íder%'
      AND (Nombre_Rol LIKE '%elulas%' OR Nombre_Rol LIKE '%élulas%')
    ORDER BY Id_Rol
    LIMIT 1
) rc ON ua.Id_Rol = rc.Id_Rol
INNER JOIN (
    SELECT Id_Rol
    FROM rol
    WHERE (Nombre_Rol LIKE '%ider%' OR Nombre_Rol LIKE '%Íder%')
      AND (Nombre_Rol LIKE '%elula%' OR Nombre_Rol LIKE '%élula%')
      AND Nombre_Rol NOT LIKE '%144%'
      AND Nombre_Rol NOT LIKE '%12%'
    ORDER BY Id_Rol
    LIMIT 1
) rl ON 1 = 1
SET ua.Id_Rol = rl.Id_Rol
WHERE ua.Id_Persona IS NOT NULL
  AND ua.Id_Persona > 0
  AND EXISTS (SELECT 1 FROM celula c WHERE c.Id_Lider = ua.Id_Persona);

-- G) Comprobar: líderes de célula que SIGAN con Células (debe salir 0 filas)
SELECT p.Id_Persona, p.Nombre, p.Apellido, r.Nombre_Rol, c.Nombre_Celula
FROM persona p
INNER JOIN rol r ON r.Id_Rol = p.Id_Rol
INNER JOIN celula c ON c.Id_Lider = p.Id_Persona
WHERE r.Nombre_Rol NOT LIKE '%ider%'
  AND r.Nombre_Rol NOT LIKE '%Íder%'
  AND (r.Nombre_Rol LIKE '%elulas%' OR r.Nombre_Rol LIKE '%élulas%');
