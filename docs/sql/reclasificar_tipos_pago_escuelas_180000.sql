-- Reclasificación histórica de Tipo_Pago (Escuelas de Formación)
-- Regla final (mejorada):
--   A) Si el ACUMULADO por persona+programa >= 180000 => completo
--      (esto corrige casos de varios abonos que ya completaron el total).
--   B) Para los demás:
--      - Valor_Pago < 180000  => abono
--      - Valor_Pago >= 180000 => completo
--
-- IMPORTANTE:
-- 1) Ejecuta primero el bloque de VISTA PREVIA.
-- 2) Luego ejecuta el bloque de ACTUALIZACIÓN.
-- 3) Este script asume MySQL/MariaDB.

SET @UMBRAL := 180000;

-- Llaves de persona+programa que YA COMPLETARON por acumulado
DROP TEMPORARY TABLE IF EXISTS tmp_escuelas_pagados_completos;

CREATE TEMPORARY TABLE tmp_escuelas_pagados_completos AS
SELECT
    COALESCE(NULLIF(TRIM(Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(Id_Persona, 0))) AS ClavePersona,
    COALESCE(NULLIF(TRIM(Programa), ''), 'SIN-PROGRAMA') AS ClavePrograma,
    SUM(COALESCE(Valor_Pago, 0)) AS TotalAcumulado
FROM escuela_formacion_pago_movimiento
WHERE COALESCE(Valor_Pago, 0) > 0
GROUP BY
    COALESCE(NULLIF(TRIM(Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(Id_Persona, 0))),
    COALESCE(NULLIF(TRIM(Programa), ''), 'SIN-PROGRAMA')
HAVING SUM(COALESCE(Valor_Pago, 0)) >= @UMBRAL;

/* =========================================================
   1) VISTA PREVIA (NO MODIFICA DATOS)
   ========================================================= */

-- Resumen en tabla de movimientos
SELECT
    'escuela_formacion_pago_movimiento' AS Tabla,
    COUNT(*) AS RegistrosEvaluados,
    SUM(
        CASE
            WHEN m.Valor_Pago > 0
                 AND k.ClavePersona IS NOT NULL
                 AND COALESCE(LOWER(TRIM(m.Tipo_Pago)), '') <> 'completo'
            THEN 1 ELSE 0
        END
    ) AS CambiarACompletoPorAcumulado,
    SUM(
        CASE
            WHEN m.Valor_Pago > 0
                 AND k.ClavePersona IS NULL
                 AND m.Valor_Pago < @UMBRAL
                 AND COALESCE(LOWER(TRIM(m.Tipo_Pago)), '') <> 'abono'
            THEN 1 ELSE 0
        END
    ) AS CambiarAAbono,
    SUM(
        CASE
            WHEN m.Valor_Pago >= @UMBRAL
                 AND k.ClavePersona IS NULL
                 AND COALESCE(LOWER(TRIM(m.Tipo_Pago)), '') <> 'completo'
            THEN 1 ELSE 0
        END
    ) AS CambiarACompletoPorValor
FROM escuela_formacion_pago_movimiento m
LEFT JOIN tmp_escuelas_pagados_completos k
    ON k.ClavePersona = COALESCE(NULLIF(TRIM(m.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(m.Id_Persona, 0)))
   AND k.ClavePrograma = COALESCE(NULLIF(TRIM(m.Programa), ''), 'SIN-PROGRAMA')
WHERE Valor_Pago > 0;

-- Resumen en tabla de inscripción (snapshot)
SELECT
    'escuela_formacion_inscripcion' AS Tabla,
    COUNT(*) AS RegistrosEvaluados,
    SUM(
        CASE
            WHEN i.Valor_Pago > 0
                 AND k.ClavePersona IS NOT NULL
                 AND COALESCE(LOWER(TRIM(i.Tipo_Pago)), '') <> 'completo'
            THEN 1 ELSE 0
        END
    ) AS CambiarACompletoPorAcumulado,
    SUM(
        CASE
            WHEN i.Valor_Pago > 0
                 AND k.ClavePersona IS NULL
                 AND i.Valor_Pago < @UMBRAL
                 AND COALESCE(LOWER(TRIM(i.Tipo_Pago)), '') <> 'abono'
            THEN 1 ELSE 0
        END
    ) AS CambiarAAbono,
    SUM(
        CASE
            WHEN i.Valor_Pago >= @UMBRAL
                 AND k.ClavePersona IS NULL
                 AND COALESCE(LOWER(TRIM(i.Tipo_Pago)), '') <> 'completo'
            THEN 1 ELSE 0
        END
    ) AS CambiarACompletoPorValor
FROM escuela_formacion_inscripcion i
LEFT JOIN tmp_escuelas_pagados_completos k
    ON k.ClavePersona = COALESCE(NULLIF(TRIM(i.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(i.Id_Persona, 0)))
   AND k.ClavePrograma = COALESCE(NULLIF(TRIM(i.Programa), ''), 'SIN-PROGRAMA')
WHERE Valor_Pago > 0;

-- Cuántas personas+programa ya completaron por acumulado
SELECT COUNT(*) AS PersonasProgramasCompletadosPorAcumulado
FROM tmp_escuelas_pagados_completos;

-- Muestra ejemplos que cambiarán (movimientos)
SELECT
    m.Id_Pago,
    m.Cedula,
    m.Nombre,
    m.Programa,
    m.Valor_Pago,
    m.Tipo_Pago AS TipoPagoActual,
    CASE
        WHEN k.ClavePersona IS NOT NULL THEN 'completo'
        WHEN m.Valor_Pago < @UMBRAL THEN 'abono'
        ELSE 'completo'
    END AS TipoPagoNuevo,
    CASE
        WHEN k.ClavePersona IS NOT NULL THEN 'acumulado'
        WHEN m.Valor_Pago < @UMBRAL THEN 'valor'
        ELSE 'valor'
    END AS Motivo
FROM escuela_formacion_pago_movimiento m
LEFT JOIN tmp_escuelas_pagados_completos k
    ON k.ClavePersona = COALESCE(NULLIF(TRIM(m.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(m.Id_Persona, 0)))
   AND k.ClavePrograma = COALESCE(NULLIF(TRIM(m.Programa), ''), 'SIN-PROGRAMA')
WHERE m.Valor_Pago > 0
  AND (
      (k.ClavePersona IS NOT NULL AND COALESCE(LOWER(TRIM(m.Tipo_Pago)), '') <> 'completo')
      OR
      (k.ClavePersona IS NULL AND m.Valor_Pago < @UMBRAL AND COALESCE(LOWER(TRIM(m.Tipo_Pago)), '') <> 'abono')
      OR
      (k.ClavePersona IS NULL AND m.Valor_Pago >= @UMBRAL AND COALESCE(LOWER(TRIM(m.Tipo_Pago)), '') <> 'completo')
  )
ORDER BY m.Valor_Pago ASC
LIMIT 20;

-- Muestra ejemplos que cambiarán (inscripción)
SELECT
    i.Id_Inscripcion,
    i.Cedula,
    i.Nombre,
    i.Programa,
    i.Valor_Pago,
    i.Tipo_Pago AS TipoPagoActual,
    CASE
        WHEN k.ClavePersona IS NOT NULL THEN 'completo'
        WHEN i.Valor_Pago < @UMBRAL THEN 'abono'
        ELSE 'completo'
    END AS TipoPagoNuevo,
    CASE
        WHEN k.ClavePersona IS NOT NULL THEN 'acumulado'
        WHEN i.Valor_Pago < @UMBRAL THEN 'valor'
        ELSE 'valor'
    END AS Motivo
FROM escuela_formacion_inscripcion i
LEFT JOIN tmp_escuelas_pagados_completos k
    ON k.ClavePersona = COALESCE(NULLIF(TRIM(i.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(i.Id_Persona, 0)))
   AND k.ClavePrograma = COALESCE(NULLIF(TRIM(i.Programa), ''), 'SIN-PROGRAMA')
WHERE i.Valor_Pago > 0
  AND (
      (k.ClavePersona IS NOT NULL AND COALESCE(LOWER(TRIM(i.Tipo_Pago)), '') <> 'completo')
      OR
      (k.ClavePersona IS NULL AND i.Valor_Pago < @UMBRAL AND COALESCE(LOWER(TRIM(i.Tipo_Pago)), '') <> 'abono')
      OR
      (k.ClavePersona IS NULL AND i.Valor_Pago >= @UMBRAL AND COALESCE(LOWER(TRIM(i.Tipo_Pago)), '') <> 'completo')
  )
ORDER BY i.Valor_Pago ASC
LIMIT 20;


/* =========================================================
   2) ACTUALIZACIÓN (MODIFICA DATOS)
   ========================================================= */

START TRANSACTION;

-- Paso 1: Forzar a COMPLETO los que ya completaron por ACUMULADO
UPDATE escuela_formacion_pago_movimiento m
INNER JOIN tmp_escuelas_pagados_completos k
        ON k.ClavePersona = COALESCE(NULLIF(TRIM(m.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(m.Id_Persona, 0)))
     AND k.ClavePrograma = COALESCE(NULLIF(TRIM(m.Programa), ''), 'SIN-PROGRAMA')
SET m.Tipo_Pago = 'completo'
WHERE m.Valor_Pago > 0
    AND COALESCE(LOWER(TRIM(m.Tipo_Pago)), '') <> 'completo';

SELECT ROW_COUNT() AS FilasActualizadas_Movimientos_PorAcumulado;

UPDATE escuela_formacion_inscripcion i
INNER JOIN tmp_escuelas_pagados_completos k
        ON k.ClavePersona = COALESCE(NULLIF(TRIM(i.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(i.Id_Persona, 0)))
     AND k.ClavePrograma = COALESCE(NULLIF(TRIM(i.Programa), ''), 'SIN-PROGRAMA')
SET i.Tipo_Pago = 'completo'
WHERE i.Valor_Pago > 0
    AND COALESCE(LOWER(TRIM(i.Tipo_Pago)), '') <> 'completo';

SELECT ROW_COUNT() AS FilasActualizadas_Inscripcion_PorAcumulado;

-- Paso 2: Regla por valor individual para los demás
UPDATE escuela_formacion_pago_movimiento m
LEFT JOIN tmp_escuelas_pagados_completos k
        ON k.ClavePersona = COALESCE(NULLIF(TRIM(m.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(m.Id_Persona, 0)))
     AND k.ClavePrograma = COALESCE(NULLIF(TRIM(m.Programa), ''), 'SIN-PROGRAMA')
SET m.Tipo_Pago = CASE
        WHEN m.Valor_Pago > 0 AND m.Valor_Pago < @UMBRAL THEN 'abono'
        WHEN m.Valor_Pago >= @UMBRAL THEN 'completo'
        ELSE m.Tipo_Pago
END
WHERE k.ClavePersona IS NULL
    AND m.Valor_Pago > 0
    AND (
            (m.Valor_Pago < @UMBRAL AND COALESCE(LOWER(TRIM(m.Tipo_Pago)), '') <> 'abono')
            OR
            (m.Valor_Pago >= @UMBRAL AND COALESCE(LOWER(TRIM(m.Tipo_Pago)), '') <> 'completo')
    );

SELECT ROW_COUNT() AS FilasActualizadas_Movimientos_PorValor;

UPDATE escuela_formacion_inscripcion i
LEFT JOIN tmp_escuelas_pagados_completos k
        ON k.ClavePersona = COALESCE(NULLIF(TRIM(i.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(i.Id_Persona, 0)))
     AND k.ClavePrograma = COALESCE(NULLIF(TRIM(i.Programa), ''), 'SIN-PROGRAMA')
SET i.Tipo_Pago = CASE
        WHEN i.Valor_Pago > 0 AND i.Valor_Pago < @UMBRAL THEN 'abono'
        WHEN i.Valor_Pago >= @UMBRAL THEN 'completo'
        ELSE i.Tipo_Pago
END
WHERE k.ClavePersona IS NULL
    AND i.Valor_Pago > 0
    AND (
            (i.Valor_Pago < @UMBRAL AND COALESCE(LOWER(TRIM(i.Tipo_Pago)), '') <> 'abono')
            OR
            (i.Valor_Pago >= @UMBRAL AND COALESCE(LOWER(TRIM(i.Tipo_Pago)), '') <> 'completo')
    );

SELECT ROW_COUNT() AS FilasActualizadas_Inscripcion_PorValor;

-- Si todo está bien:
COMMIT;

-- Si quieres cancelar antes de confirmar, usa:
-- ROLLBACK;
