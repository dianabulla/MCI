-- Reclasificación puntual de Tipo_Pago (Escuelas de Formación)
-- OBJETIVO: solo pasar a 'completo' los casos que YA completaron 180000 por acumulado.
--
-- NO hace reclasificación a 'abono'.
--
-- Regla:
--   Si SUM(Valor_Pago) por persona+programa >= 180000 => Tipo_Pago = 'completo'
--
-- Recomendado para producción cuando hubo varios abonos que completaron el total.

SET @UMBRAL := 180000;

-- Construir claves persona+programa que ya completaron por acumulado
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

-- Cuántas llaves persona+programa completaron por acumulado
SELECT COUNT(*) AS PersonasProgramasCompletadosPorAcumulado
FROM tmp_escuelas_pagados_completos;

-- Cuántos registros se corregirían en movimientos
SELECT
    COUNT(*) AS FilasACorregir_Movimientos
FROM escuela_formacion_pago_movimiento m
INNER JOIN tmp_escuelas_pagados_completos k
    ON k.ClavePersona = COALESCE(NULLIF(TRIM(m.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(m.Id_Persona, 0)))
   AND k.ClavePrograma = COALESCE(NULLIF(TRIM(m.Programa), ''), 'SIN-PROGRAMA')
WHERE m.Valor_Pago > 0
  AND COALESCE(LOWER(TRIM(m.Tipo_Pago)), '') <> 'completo';

-- Cuántos registros se corregirían en inscripción
SELECT
    COUNT(*) AS FilasACorregir_Inscripcion
FROM escuela_formacion_inscripcion i
INNER JOIN tmp_escuelas_pagados_completos k
    ON k.ClavePersona = COALESCE(NULLIF(TRIM(i.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(i.Id_Persona, 0)))
   AND k.ClavePrograma = COALESCE(NULLIF(TRIM(i.Programa), ''), 'SIN-PROGRAMA')
WHERE i.Valor_Pago > 0
  AND COALESCE(LOWER(TRIM(i.Tipo_Pago)), '') <> 'completo';

-- Ejemplos de movimientos a corregir
SELECT
    m.Id_Pago,
    m.Cedula,
    m.Nombre,
    m.Programa,
    m.Valor_Pago,
    m.Tipo_Pago AS TipoPagoActual,
    'completo' AS TipoPagoNuevo,
    k.TotalAcumulado
FROM escuela_formacion_pago_movimiento m
INNER JOIN tmp_escuelas_pagados_completos k
    ON k.ClavePersona = COALESCE(NULLIF(TRIM(m.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(m.Id_Persona, 0)))
   AND k.ClavePrograma = COALESCE(NULLIF(TRIM(m.Programa), ''), 'SIN-PROGRAMA')
WHERE m.Valor_Pago > 0
  AND COALESCE(LOWER(TRIM(m.Tipo_Pago)), '') <> 'completo'
ORDER BY k.TotalAcumulado DESC, m.Cedula ASC
LIMIT 30;

/* =========================================================
   2) ACTUALIZACIÓN (MODIFICA DATOS)
   ========================================================= */

START TRANSACTION;

UPDATE escuela_formacion_pago_movimiento m
INNER JOIN tmp_escuelas_pagados_completos k
    ON k.ClavePersona = COALESCE(NULLIF(TRIM(m.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(m.Id_Persona, 0)))
   AND k.ClavePrograma = COALESCE(NULLIF(TRIM(m.Programa), ''), 'SIN-PROGRAMA')
SET m.Tipo_Pago = 'completo'
WHERE m.Valor_Pago > 0
  AND COALESCE(LOWER(TRIM(m.Tipo_Pago)), '') <> 'completo';

SELECT ROW_COUNT() AS FilasActualizadas_Movimientos;

UPDATE escuela_formacion_inscripcion i
INNER JOIN tmp_escuelas_pagados_completos k
    ON k.ClavePersona = COALESCE(NULLIF(TRIM(i.Cedula), ''), CONCAT('SIN-CEDULA-', COALESCE(i.Id_Persona, 0)))
   AND k.ClavePrograma = COALESCE(NULLIF(TRIM(i.Programa), ''), 'SIN-PROGRAMA')
SET i.Tipo_Pago = 'completo'
WHERE i.Valor_Pago > 0
  AND COALESCE(LOWER(TRIM(i.Tipo_Pago)), '') <> 'completo';

SELECT ROW_COUNT() AS FilasActualizadas_Inscripcion;

COMMIT;

-- Si quieres cancelar antes de confirmar, reemplaza COMMIT por ROLLBACK.
