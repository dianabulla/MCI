-- Correccion de produccion: intercambiar Modulo_Numero 3 y 4
-- para Capacitacion Destino Nivel 2 en material_hub_tema.
--
-- Ejecutar en la base de datos de PRODUCCION.
-- Recomendado: correr primero en ventana de mantenimiento.

-- 1) Inspeccion previa (sin cambios)
SELECT Id_Tema, Modulo, Nivel, Modulo_Numero, Leccion, Titulo, Lote_Id, Fecha_Creacion
FROM material_hub_tema
WHERE Modulo = 'capacitacion_destino'
  AND Nivel = 2
  AND Modulo_Numero IN (3, 4)
ORDER BY Modulo_Numero, Fecha_Creacion, Id_Tema;

-- 2) Respaldo solo de filas afectadas
-- Nota: si ya existe una tabla de respaldo con este nombre, se reemplaza
-- para garantizar que el backup corresponde exactamente a esta ejecucion.
DROP TABLE IF EXISTS material_hub_tema_backup_swap_n2_mod3_mod4_20260505;
CREATE TABLE material_hub_tema_backup_swap_n2_mod3_mod4_20260505 AS
SELECT *
FROM material_hub_tema
WHERE Modulo = 'capacitacion_destino'
  AND Nivel = 2
  AND Modulo_Numero IN (3, 4);

-- 3) Intercambio atomico 3 <-> 4 (sin valor temporal)
SELECT COUNT(*) AS filas_objetivo
FROM material_hub_tema
WHERE Modulo = 'capacitacion_destino'
  AND Nivel = 2
  AND Modulo_Numero IN (3, 4);

START TRANSACTION;

UPDATE material_hub_tema
SET Modulo_Numero = CASE
    WHEN Modulo_Numero = 3 THEN 4
    WHEN Modulo_Numero = 4 THEN 3
    ELSE Modulo_Numero
END
WHERE Modulo = 'capacitacion_destino'
  AND Nivel = 2
  AND Modulo_Numero IN (3, 4);

SELECT ROW_COUNT() AS filas_actualizadas;

COMMIT;

-- 4) Verificacion posterior
SELECT Id_Tema, Modulo, Nivel, Modulo_Numero, Leccion, Titulo, Lote_Id, Fecha_Creacion
FROM material_hub_tema
WHERE Modulo = 'capacitacion_destino'
  AND Nivel = 2
  AND Modulo_Numero IN (3, 4)
ORDER BY Modulo_Numero, Fecha_Creacion, Id_Tema;
