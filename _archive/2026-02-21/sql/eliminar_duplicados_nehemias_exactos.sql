-- Elimina duplicados exactos en nehemias por:
-- 1) Nombres
-- 2) Apellidos
-- 3) Numero_Cedula
-- Mantiene el registro con Id_Nehemias más bajo.

START TRANSACTION;

-- 1) Ver cuántos grupos duplicados existen (solo con los 3 campos llenos)
SELECT 
    TRIM(Nombres) AS Nombres,
    TRIM(Apellidos) AS Apellidos,
    TRIM(Numero_Cedula) AS Numero_Cedula,
    COUNT(*) AS cantidad
FROM nehemias
WHERE TRIM(COALESCE(Nombres, '')) <> ''
  AND TRIM(COALESCE(Apellidos, '')) <> ''
  AND TRIM(COALESCE(Numero_Cedula, '')) <> ''
  AND TRIM(Numero_Cedula) NOT IN ('1', '01')
GROUP BY TRIM(Nombres), TRIM(Apellidos), TRIM(Numero_Cedula)
HAVING COUNT(*) > 1
ORDER BY cantidad DESC;

-- 2) Guardar IDs a eliminar en tabla temporal
DROP TEMPORARY TABLE IF EXISTS tmp_ids_duplicados_nehemias;
CREATE TEMPORARY TABLE tmp_ids_duplicados_nehemias (
    Id_Nehemias INT PRIMARY KEY
);

INSERT INTO tmp_ids_duplicados_nehemias (Id_Nehemias)
SELECT DISTINCT n1.Id_Nehemias
FROM nehemias n1
JOIN nehemias n2
  ON n1.Id_Nehemias > n2.Id_Nehemias
 AND TRIM(n1.Nombres) = TRIM(n2.Nombres)
 AND TRIM(n1.Apellidos) = TRIM(n2.Apellidos)
 AND TRIM(n1.Numero_Cedula) = TRIM(n2.Numero_Cedula)
WHERE TRIM(COALESCE(n1.Nombres, '')) <> ''
  AND TRIM(COALESCE(n1.Apellidos, '')) <> ''
  AND TRIM(COALESCE(n1.Numero_Cedula, '')) <> ''
  AND TRIM(n1.Numero_Cedula) NOT IN ('1', '01');

-- 3) Ver cuántos registros se van a borrar
SELECT COUNT(*) AS registros_a_eliminar
FROM tmp_ids_duplicados_nehemias;

-- 4) Eliminar duplicados
DELETE n
FROM nehemias n
JOIN tmp_ids_duplicados_nehemias t ON t.Id_Nehemias = n.Id_Nehemias;

-- 5) Resumen final
SELECT ROW_COUNT() AS registros_eliminados;

COMMIT;

-- Si deseas probar primero sin borrar, comenta el DELETE y el COMMIT,
-- y ejecuta solo hasta el SELECT de "registros_a_eliminar".
