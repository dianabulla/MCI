-- Recuperar Id_Ministerio / Id_Celula / Id_Lider por coincidencia de
-- Numero_Documento o Telefono (cuando Id_Persona no alcanza).
--
-- Requiere:
--   - Tabla actual: persona
--   - Tabla respaldo: persona_backup_20250313
--
-- Estrategia segura:
--   1) Fase Documento: solo documentos UNICOS en respaldo.
--   2) Fase Telefono: solo telefonos UNICOS en respaldo y UNICOS en actual.
--
-- No sobreescribe datos existentes. Solo rellena campos faltantes (NULL/0).

-- ================================================================
-- 0) Verificacion rapida
-- ================================================================
SELECT
  COUNT(*) AS filas_actual
FROM persona;

SELECT
  COUNT(*) AS filas_backup
FROM persona_backup_20250313;

-- ================================================================
-- 1) Vista previa por Documento (unicos en backup)
-- ================================================================
SELECT
  COUNT(*) AS candidatos_doc
FROM persona p
INNER JOIN (
  SELECT Numero_Documento, MAX(Id_Persona) AS Id_Persona_ref
  FROM persona_backup_20250313
  WHERE Numero_Documento IS NOT NULL AND TRIM(Numero_Documento) <> ''
  GROUP BY Numero_Documento
  HAVING COUNT(*) = 1
) bdoc
  ON bdoc.Numero_Documento = p.Numero_Documento
INNER JOIN persona_backup_20250313 pb
  ON pb.Id_Persona = bdoc.Id_Persona_ref
WHERE
  (
    (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
    AND (pb.Id_Ministerio IS NOT NULL AND pb.Id_Ministerio > 0)
  )
  OR
  (
    (p.Id_Celula IS NULL OR p.Id_Celula = 0)
    AND (pb.Id_Celula IS NOT NULL AND pb.Id_Celula > 0)
  )
  OR
  (
    (p.Id_Lider IS NULL OR p.Id_Lider = 0)
    AND (pb.Id_Lider IS NOT NULL AND pb.Id_Lider > 0)
  );

-- Muestra de casos por documento (max 50)
SELECT
  p.Id_Persona,
  p.Nombre,
  p.Apellido,
  p.Numero_Documento,
  p.Id_Ministerio AS actual_min,
  pb.Id_Ministerio AS backup_min,
  p.Id_Celula AS actual_cel,
  pb.Id_Celula AS backup_cel,
  p.Id_Lider AS actual_lid,
  pb.Id_Lider AS backup_lid
FROM persona p
INNER JOIN (
  SELECT Numero_Documento, MAX(Id_Persona) AS Id_Persona_ref
  FROM persona_backup_20250313
  WHERE Numero_Documento IS NOT NULL AND TRIM(Numero_Documento) <> ''
  GROUP BY Numero_Documento
  HAVING COUNT(*) = 1
) bdoc
  ON bdoc.Numero_Documento = p.Numero_Documento
INNER JOIN persona_backup_20250313 pb
  ON pb.Id_Persona = bdoc.Id_Persona_ref
WHERE
  (
    (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
    AND (pb.Id_Ministerio IS NOT NULL AND pb.Id_Ministerio > 0)
  )
  OR
  (
    (p.Id_Celula IS NULL OR p.Id_Celula = 0)
    AND (pb.Id_Celula IS NOT NULL AND pb.Id_Celula > 0)
  )
  OR
  (
    (p.Id_Lider IS NULL OR p.Id_Lider = 0)
    AND (pb.Id_Lider IS NOT NULL AND pb.Id_Lider > 0)
  )
ORDER BY p.Id_Persona
LIMIT 50;

-- ================================================================
-- 2) Vista previa por Telefono (unico en backup y unico en actual)
-- ================================================================
SELECT
  COUNT(*) AS candidatos_tel
FROM persona p
INNER JOIN (
  SELECT
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(Telefono), ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') AS tel_norm,
    MAX(Id_Persona) AS Id_Persona_ref
  FROM persona_backup_20250313
  WHERE Telefono IS NOT NULL AND TRIM(Telefono) <> ''
  GROUP BY REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(Telefono), ' ', ''), '-', ''), '+', ''), '(', ''), ')', '')
  HAVING COUNT(*) = 1
) btel
  ON btel.tel_norm = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(p.Telefono), ' ', ''), '-', ''), '+', ''), '(', ''), ')', '')
INNER JOIN persona_backup_20250313 pb
  ON pb.Id_Persona = btel.Id_Persona_ref
INNER JOIN (
  SELECT
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(Telefono), ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') AS tel_norm
  FROM persona
  WHERE Telefono IS NOT NULL AND TRIM(Telefono) <> ''
  GROUP BY REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(Telefono), ' ', ''), '-', ''), '+', ''), '(', ''), ')', '')
  HAVING COUNT(*) = 1
) ptel_unico
  ON ptel_unico.tel_norm = btel.tel_norm
WHERE
  (
    (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
    AND (pb.Id_Ministerio IS NOT NULL AND pb.Id_Ministerio > 0)
  )
  OR
  (
    (p.Id_Celula IS NULL OR p.Id_Celula = 0)
    AND (pb.Id_Celula IS NOT NULL AND pb.Id_Celula > 0)
  )
  OR
  (
    (p.Id_Lider IS NULL OR p.Id_Lider = 0)
    AND (pb.Id_Lider IS NOT NULL AND pb.Id_Lider > 0)
  );

-- ================================================================
-- 3) Ejecutar recuperacion (transaccion)
-- ================================================================
START TRANSACTION;

-- FASE A: completar por documento unico en backup
UPDATE persona p
INNER JOIN (
  SELECT Numero_Documento, MAX(Id_Persona) AS Id_Persona_ref
  FROM persona_backup_20250313
  WHERE Numero_Documento IS NOT NULL AND TRIM(Numero_Documento) <> ''
  GROUP BY Numero_Documento
  HAVING COUNT(*) = 1
) bdoc
  ON bdoc.Numero_Documento = p.Numero_Documento
INNER JOIN persona_backup_20250313 pb
  ON pb.Id_Persona = bdoc.Id_Persona_ref
SET
  p.Id_Ministerio = CASE
    WHEN (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
         AND (pb.Id_Ministerio IS NOT NULL AND pb.Id_Ministerio > 0)
    THEN pb.Id_Ministerio
    ELSE p.Id_Ministerio
  END,
  p.Id_Celula = CASE
    WHEN (p.Id_Celula IS NULL OR p.Id_Celula = 0)
         AND (pb.Id_Celula IS NOT NULL AND pb.Id_Celula > 0)
    THEN pb.Id_Celula
    ELSE p.Id_Celula
  END,
  p.Id_Lider = CASE
    WHEN (p.Id_Lider IS NULL OR p.Id_Lider = 0)
         AND (pb.Id_Lider IS NOT NULL AND pb.Id_Lider > 0)
    THEN pb.Id_Lider
    ELSE p.Id_Lider
  END
WHERE
  (
    (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
    AND (pb.Id_Ministerio IS NOT NULL AND pb.Id_Ministerio > 0)
  )
  OR
  (
    (p.Id_Celula IS NULL OR p.Id_Celula = 0)
    AND (pb.Id_Celula IS NOT NULL AND pb.Id_Celula > 0)
  )
  OR
  (
    (p.Id_Lider IS NULL OR p.Id_Lider = 0)
    AND (pb.Id_Lider IS NOT NULL AND pb.Id_Lider > 0)
  );

SELECT ROW_COUNT() AS filas_actualizadas_por_documento;

-- FASE B: completar por telefono unico (backup y actual)
UPDATE persona p
INNER JOIN (
  SELECT
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(Telefono), ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') AS tel_norm,
    MAX(Id_Persona) AS Id_Persona_ref
  FROM persona_backup_20250313
  WHERE Telefono IS NOT NULL AND TRIM(Telefono) <> ''
  GROUP BY REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(Telefono), ' ', ''), '-', ''), '+', ''), '(', ''), ')', '')
  HAVING COUNT(*) = 1
) btel
  ON btel.tel_norm = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(p.Telefono), ' ', ''), '-', ''), '+', ''), '(', ''), ')', '')
INNER JOIN (
  SELECT
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(Telefono), ' ', ''), '-', ''), '+', ''), '(', ''), ')', '') AS tel_norm
  FROM persona
  WHERE Telefono IS NOT NULL AND TRIM(Telefono) <> ''
  GROUP BY REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(Telefono), ' ', ''), '-', ''), '+', ''), '(', ''), ')', '')
  HAVING COUNT(*) = 1
) ptel_unico
  ON ptel_unico.tel_norm = btel.tel_norm
INNER JOIN persona_backup_20250313 pb
  ON pb.Id_Persona = btel.Id_Persona_ref
SET
  p.Id_Ministerio = CASE
    WHEN (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
         AND (pb.Id_Ministerio IS NOT NULL AND pb.Id_Ministerio > 0)
    THEN pb.Id_Ministerio
    ELSE p.Id_Ministerio
  END,
  p.Id_Celula = CASE
    WHEN (p.Id_Celula IS NULL OR p.Id_Celula = 0)
         AND (pb.Id_Celula IS NOT NULL AND pb.Id_Celula > 0)
    THEN pb.Id_Celula
    ELSE p.Id_Celula
  END,
  p.Id_Lider = CASE
    WHEN (p.Id_Lider IS NULL OR p.Id_Lider = 0)
         AND (pb.Id_Lider IS NOT NULL AND pb.Id_Lider > 0)
    THEN pb.Id_Lider
    ELSE p.Id_Lider
  END
WHERE
  (
    (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
    AND (pb.Id_Ministerio IS NOT NULL AND pb.Id_Ministerio > 0)
  )
  OR
  (
    (p.Id_Celula IS NULL OR p.Id_Celula = 0)
    AND (pb.Id_Celula IS NOT NULL AND pb.Id_Celula > 0)
  )
  OR
  (
    (p.Id_Lider IS NULL OR p.Id_Lider = 0)
    AND (pb.Id_Lider IS NOT NULL AND pb.Id_Lider > 0)
  );

SELECT ROW_COUNT() AS filas_actualizadas_por_telefono;

-- Validacion final
SELECT
  SUM(CASE WHEN Id_Ministerio IS NULL OR Id_Ministerio = 0 THEN 1 ELSE 0 END) AS sin_ministerio,
  SUM(CASE WHEN Id_Celula IS NULL OR Id_Celula = 0 THEN 1 ELSE 0 END) AS sin_celula,
  SUM(CASE WHEN Id_Lider IS NULL OR Id_Lider = 0 THEN 1 ELSE 0 END) AS sin_lider
FROM persona;

COMMIT;

-- Si prefieres validar antes de aplicar:
-- reemplaza COMMIT por ROLLBACK.
