-- Activa cuentas que quedaron con Estado_Cuenta nulo o vacío
-- Ejecutar una sola vez en producción si aplica

UPDATE persona
SET Estado_Cuenta = 'Activo'
WHERE Estado_Cuenta IS NULL
   OR TRIM(Estado_Cuenta) = '';

-- Verificación
SELECT Estado_Cuenta, COUNT(*) AS total
FROM persona
GROUP BY Estado_Cuenta;
