-- Limpieza segura de registros mal importados en NEHEMIAS
-- Caso: filas con Nombres corrupto (ej: ';2;;', ';;;', ';MESA 6;;', texto de política)
-- Fechas objetivo reportadas: 2026-02-18 18:58:42 y 2026-02-18 18:50:15
--
-- USO EN PRODUCCIÓN:
-- 1) Ejecuta primero hasta el SELECT de "registros_detectados" para validar.
-- 2) Si el resultado es correcto, ejecuta el script completo.

START TRANSACTION;

-- 0) Conteo total antes
SELECT COUNT(*) AS total_antes
FROM nehemias;

-- 1) Detectar candidatos (patrones reportados)
DROP TEMPORARY TABLE IF EXISTS tmp_nehemias_malos_20260218;
CREATE TEMPORARY TABLE tmp_nehemias_malos_20260218 (
    Id_Nehemias INT PRIMARY KEY
);

INSERT INTO tmp_nehemias_malos_20260218 (Id_Nehemias)
SELECT n.Id_Nehemias
FROM nehemias n
WHERE
    -- Fechas reportadas del lote malo (ajusta o comenta si lo necesitas más amplio)
    n.Fecha_Registro IN ('2026-02-18 18:58:42', '2026-02-18 18:50:15')
    AND (
        -- Ejemplos exactos vistos
        TRIM(COALESCE(n.Nombres, '')) IN (';2;;', ';;;', ';13;;', ';6;;', ';8;;', ';MESA 6;;')

        -- Nombres iniciando con ';' y resto de campos clave vacíos
        OR (
            TRIM(COALESCE(n.Nombres, '')) LIKE ';%'
            AND TRIM(COALESCE(n.Apellidos, '')) = ''
            AND TRIM(COALESCE(n.Numero_Cedula, '')) = ''
            AND TRIM(COALESCE(n.Telefono, '')) = ''
        )

        -- Texto legal/política que no corresponde a un nombre
        OR TRIM(COALESCE(n.Nombres, '')) LIKE 'El titular podrá%'
    );

-- 2) Ver cuántos se eliminarán
SELECT COUNT(*) AS registros_detectados
FROM tmp_nehemias_malos_20260218;

-- 3) Vista previa de registros a eliminar
SELECT n.*
FROM nehemias n
JOIN tmp_nehemias_malos_20260218 t ON t.Id_Nehemias = n.Id_Nehemias
ORDER BY n.Id_Nehemias;

-- 4) Backup de seguridad solo de los registros a borrar
CREATE TABLE IF NOT EXISTS nehemias_backup_malos_20260218 AS
SELECT n.*
FROM nehemias n
JOIN tmp_nehemias_malos_20260218 t ON t.Id_Nehemias = n.Id_Nehemias;

-- 5) Eliminar
DELETE n
FROM nehemias n
JOIN tmp_nehemias_malos_20260218 t ON t.Id_Nehemias = n.Id_Nehemias;

-- 6) Resultado de eliminación
SELECT ROW_COUNT() AS registros_eliminados;

-- 7) Conteo final
SELECT COUNT(*) AS total_despues
FROM nehemias;

COMMIT;

-- Si quieres hacer una prueba SIN borrar, comenta desde el DELETE hasta el COMMIT.
-- Si algo no cuadra, usa ROLLBACK antes de COMMIT.
