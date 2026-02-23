-- Elimina duplicados exactos (modo estricto) en nehemias por:
-- 1) Nombres
-- 2) Apellidos
-- 3) Numero_Cedula
--
-- Modo estricto:
-- - Distingue mayúsculas/minúsculas y tildes usando BINARY.
-- - Mantiene el registro con Id_Nehemias más bajo.
-- - Solo considera filas con los 3 campos no vacíos.

START TRANSACTION;

-- 1) Ver grupos duplicados estrictos
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
GROUP BY BINARY TRIM(Nombres), BINARY TRIM(Apellidos), BINARY TRIM(Numero_Cedula)
HAVING COUNT(*) > 1
ORDER BY cantidad DESC;

-- 2) Guardar IDs a eliminar en temporal
DROP TEMPORARY TABLE IF EXISTS tmp_ids_duplicados_nehemias_estricto;
CREATE TEMPORARY TABLE tmp_ids_duplicados_nehemias_estricto (
    Id_Nehemias INT PRIMARY KEY
);

INSERT INTO tmp_ids_duplicados_nehemias_estricto (Id_Nehemias)
SELECT n1.Id_Nehemias
FROM nehemias n1
JOIN nehemias n2
  ON n1.Id_Nehemias > n2.Id_Nehemias
 AND BINARY TRIM(n1.Nombres) = BINARY TRIM(n2.Nombres)
 AND BINARY TRIM(n1.Apellidos) = BINARY TRIM(n2.Apellidos)
 AND BINARY TRIM(n1.Numero_Cedula) = BINARY TRIM(n2.Numero_Cedula)
WHERE TRIM(COALESCE(n1.Nombres, '')) <> ''
  AND TRIM(COALESCE(n1.Apellidos, '')) <> ''
  AND TRIM(COALESCE(n1.Numero_Cedula, '')) <> ''
  AND TRIM(n1.Numero_Cedula) NOT IN ('1', '01');

-- 3) Confirmar cuántos se eliminarán
SELECT COUNT(*) AS registros_a_eliminar
FROM tmp_ids_duplicados_nehemias_estricto;

-- 4) Eliminar duplicados estrictos
DELETE n
FROM nehemias n
JOIN tmp_ids_duplicados_nehemias_estricto t ON t.Id_Nehemias = n.Id_Nehemias;

-- 5) Resultado
SELECT ROW_COUNT() AS registros_eliminados;

COMMIT;

-- Para prueba sin borrar:
-- comenta DELETE y COMMIT, y deja hasta el SELECT de registros_a_eliminar.
