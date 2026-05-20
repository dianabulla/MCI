-- =============================================================================
-- Unificar evaluaciones REPETIDAS (mismo Nivel + Modulo + Leccion)
--
-- • Solo grupos con 2+ evaluaciones; las únicas NO se tocan.
-- • Gana la que tiene MÁS notas (discipular_evaluacion_resultados).
-- • Todas las notas de las copias pasan a esa evaluación.
-- • Se borran las copias; la ganadora queda Activa = 1.
--
-- BACKUP antes. MySQL 8+. Ejecutar PASO 1 y 2, luego 3→4→5→6→7 en orden.
-- =============================================================================

-- -----------------------------------------------------------------------------
-- PASO 1 — Grupos repetidos (solo lectura)
-- -----------------------------------------------------------------------------
SELECT
    e.Nivel,
    e.Modulo_Numero,
    COALESCE(e.Leccion, '') AS Leccion,
    COUNT(*) AS copias,
    SUM(e.Activa = 1) AS activas,
    GROUP_CONCAT(e.Id_Evaluacion ORDER BY e.Id_Evaluacion) AS ids
FROM discipular_evaluaciones e
GROUP BY e.Nivel, e.Modulo_Numero, COALESCE(e.Leccion, '')
HAVING COUNT(*) > 1
ORDER BY e.Nivel, e.Modulo_Numero, Leccion;

-- -----------------------------------------------------------------------------
-- PASO 2 — Vista previa: ganadora (más notas) y copias a eliminar
-- -----------------------------------------------------------------------------
WITH ranked AS (
    SELECT
        e.Id_Evaluacion,
        e.Titulo,
        e.Nivel,
        e.Modulo_Numero,
        COALESCE(e.Leccion, '') AS Leccion_norm,
        e.Activa,
        COUNT(r.Id_Resultado) AS notas,
        ROW_NUMBER() OVER (
            PARTITION BY e.Nivel, e.Modulo_Numero, COALESCE(e.Leccion, '')
            ORDER BY COUNT(r.Id_Resultado) DESC, e.Id_Evaluacion DESC
        ) AS rn,
        COUNT(*) OVER (
            PARTITION BY e.Nivel, e.Modulo_Numero, COALESCE(e.Leccion, '')
        ) AS copias_en_grupo
    FROM discipular_evaluaciones e
    LEFT JOIN discipular_evaluacion_resultados r ON r.Id_Evaluacion = e.Id_Evaluacion
    GROUP BY e.Id_Evaluacion, e.Titulo, e.Nivel, e.Modulo_Numero, e.Leccion, e.Activa
)
SELECT
    Id_Evaluacion,
    Titulo,
    Nivel,
    Modulo_Numero,
    Leccion_norm AS Leccion,
    Activa,
    notas,
    copias_en_grupo,
    IF(rn = 1, 'CONSERVAR (recibe todas las notas)', 'eliminar') AS accion
FROM ranked
WHERE copias_en_grupo > 1
ORDER BY Nivel, Modulo_Numero, Leccion_norm, rn;

-- -----------------------------------------------------------------------------
-- PASO 2b — Cuántas notas se van a mover (solo lectura)
-- -----------------------------------------------------------------------------
WITH ranked AS (
    SELECT
        e.Id_Evaluacion,
        e.Nivel,
        e.Modulo_Numero,
        COALESCE(e.Leccion, '') AS Leccion_norm,
        ROW_NUMBER() OVER (
            PARTITION BY e.Nivel, e.Modulo_Numero, COALESCE(e.Leccion, '')
            ORDER BY COUNT(r.Id_Resultado) DESC, e.Id_Evaluacion DESC
        ) AS rn,
        COUNT(*) OVER (
            PARTITION BY e.Nivel, e.Modulo_Numero, COALESCE(e.Leccion, '')
        ) AS copias_en_grupo
    FROM discipular_evaluaciones e
    LEFT JOIN discipular_evaluacion_resultados r ON r.Id_Evaluacion = e.Id_Evaluacion
    GROUP BY e.Id_Evaluacion, e.Nivel, e.Modulo_Numero, e.Leccion
),
mapa AS (
    SELECT
        p.Id_Evaluacion AS id_copia,
        g.Id_Evaluacion AS id_ganadora,
        p.Leccion_norm,
        p.Nivel,
        p.Modulo_Numero
    FROM ranked p
    INNER JOIN ranked g
        ON g.Nivel = p.Nivel
       AND g.Modulo_Numero = p.Modulo_Numero
       AND g.Leccion_norm = p.Leccion_norm
       AND g.rn = 1
    WHERE p.copias_en_grupo > 1 AND p.rn > 1
)
SELECT COUNT(*) AS filas_notas_a_mover
FROM discipular_evaluacion_resultados res
INNER JOIN mapa m ON m.id_copia = res.Id_Evaluacion;

-- =============================================================================
-- A partir de aquí: descomentar y ejecutar UNO POR UNO (3 → 4 → 5 → 6)
-- =============================================================================

-- -----------------------------------------------------------------------------
-- PASO 3 — Unificar notas: copias → evaluación con más notas del grupo
-- -----------------------------------------------------------------------------
/*
UPDATE discipular_evaluacion_resultados res
INNER JOIN (
    SELECT p.Id_Evaluacion AS id_copia, g.Id_Evaluacion AS id_ganadora
    FROM (
        SELECT
            e.Id_Evaluacion,
            e.Nivel,
            e.Modulo_Numero,
            COALESCE(e.Leccion, '') AS Leccion_norm,
            ROW_NUMBER() OVER (
                PARTITION BY e.Nivel, e.Modulo_Numero, COALESCE(e.Leccion, '')
                ORDER BY COUNT(r.Id_Resultado) DESC, e.Id_Evaluacion DESC
            ) AS rn,
            COUNT(*) OVER (
                PARTITION BY e.Nivel, e.Modulo_Numero, COALESCE(e.Leccion, '')
            ) AS copias_en_grupo
        FROM discipular_evaluaciones e
        LEFT JOIN discipular_evaluacion_resultados r ON r.Id_Evaluacion = e.Id_Evaluacion
        GROUP BY e.Id_Evaluacion, e.Nivel, e.Modulo_Numero, e.Leccion
    ) p
    INNER JOIN (
        SELECT
            e.Id_Evaluacion,
            e.Nivel,
            e.Modulo_Numero,
            COALESCE(e.Leccion, '') AS Leccion_norm,
            ROW_NUMBER() OVER (
                PARTITION BY e.Nivel, e.Modulo_Numero, COALESCE(e.Leccion, '')
                ORDER BY COUNT(r.Id_Resultado) DESC, e.Id_Evaluacion DESC
            ) AS rn
        FROM discipular_evaluaciones e
        LEFT JOIN discipular_evaluacion_resultados r ON r.Id_Evaluacion = e.Id_Evaluacion
        GROUP BY e.Id_Evaluacion, e.Nivel, e.Modulo_Numero, e.Leccion
    ) g
        ON g.Nivel = p.Nivel
       AND g.Modulo_Numero = p.Modulo_Numero
       AND g.Leccion_norm = p.Leccion_norm
       AND g.rn = 1
    WHERE p.copias_en_grupo > 1 AND p.rn > 1
) mapa ON mapa.id_copia = res.Id_Evaluacion
SET res.Id_Evaluacion = mapa.id_ganadora;
*/

-- -----------------------------------------------------------------------------
-- PASO 4 — Borrar intentos en curso de las copias
-- -----------------------------------------------------------------------------
/*
DELETE ia
FROM discipular_evaluacion_intento_activo ia
INNER JOIN (
    SELECT Id_Evaluacion
    FROM (
        SELECT
            e.Id_Evaluacion,
            ROW_NUMBER() OVER (
                PARTITION BY e.Nivel, e.Modulo_Numero, COALESCE(e.Leccion, '')
                ORDER BY COUNT(r.Id_Resultado) DESC, e.Id_Evaluacion DESC
            ) AS rn,
            COUNT(*) OVER (
                PARTITION BY e.Nivel, e.Modulo_Numero, COALESCE(e.Leccion, '')
            ) AS copias_en_grupo
        FROM discipular_evaluaciones e
        LEFT JOIN discipular_evaluacion_resultados r ON r.Id_Evaluacion = e.Id_Evaluacion
        GROUP BY e.Id_Evaluacion, e.Nivel, e.Modulo_Numero, e.Leccion
    ) x
    WHERE copias_en_grupo > 1 AND rn > 1
) dup ON dup.Id_Evaluacion = ia.Id_Evaluacion;
*/

-- -----------------------------------------------------------------------------
-- PASO 5 — Eliminar las copias (la ganadora del grupo se queda)
-- -----------------------------------------------------------------------------
/*
DELETE e
FROM discipular_evaluaciones e
INNER JOIN (
    SELECT Id_Evaluacion
    FROM (
        SELECT
            e.Id_Evaluacion,
            ROW_NUMBER() OVER (
                PARTITION BY e.Nivel, e.Modulo_Numero, COALESCE(e.Leccion, '')
                ORDER BY COUNT(r.Id_Resultado) DESC, e.Id_Evaluacion DESC
            ) AS rn,
            COUNT(*) OVER (
                PARTITION BY e.Nivel, e.Modulo_Numero, COALESCE(e.Leccion, '')
            ) AS copias_en_grupo
        FROM discipular_evaluaciones e
        LEFT JOIN discipular_evaluacion_resultados r ON r.Id_Evaluacion = e.Id_Evaluacion
        GROUP BY e.Id_Evaluacion, e.Nivel, e.Modulo_Numero, e.Leccion
    ) x
    WHERE copias_en_grupo > 1 AND rn > 1
) dup ON dup.Id_Evaluacion = e.Id_Evaluacion;
*/

-- -----------------------------------------------------------------------------
-- PASO 6 — Activar la evaluación que quedó en cada grupo repetido
-- -----------------------------------------------------------------------------
/*
UPDATE discipular_evaluaciones e
INNER JOIN (
    SELECT Id_Evaluacion
    FROM (
        SELECT
            ev.Id_Evaluacion,
            ROW_NUMBER() OVER (
                PARTITION BY ev.Nivel, ev.Modulo_Numero, COALESCE(ev.Leccion, '')
                ORDER BY COUNT(r.Id_Resultado) DESC, ev.Id_Evaluacion DESC
            ) AS rn,
            COUNT(*) OVER (
                PARTITION BY ev.Nivel, ev.Modulo_Numero, COALESCE(ev.Leccion, '')
            ) AS copias_en_grupo
        FROM discipular_evaluaciones ev
        LEFT JOIN discipular_evaluacion_resultados r ON r.Id_Evaluacion = ev.Id_Evaluacion
        GROUP BY ev.Id_Evaluacion, ev.Nivel, ev.Modulo_Numero, ev.Leccion
    ) x
    WHERE copias_en_grupo > 1 AND rn = 1
) keep ON keep.Id_Evaluacion = e.Id_Evaluacion
SET e.Activa = 1;
*/

-- -----------------------------------------------------------------------------
-- PASO 7 — Comprobar (debe salir vacío)
-- -----------------------------------------------------------------------------
SELECT
    Nivel, Modulo_Numero, COALESCE(Leccion, '') AS Leccion, COUNT(*) AS copias
FROM discipular_evaluaciones
GROUP BY Nivel, Modulo_Numero, COALESCE(Leccion, '')
HAVING COUNT(*) > 1;
