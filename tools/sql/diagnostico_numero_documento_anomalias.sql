-- =============================================================================
-- Diagnóstico: Numero_Documento anómalo en tabla persona
-- Uso: ejecutar en PRODUCCIÓN (solo lectura). Ajustar USE si aplica.
--
-- Alternativa en navegador (admin logueado):
--   /tools/diagnostico_numero_documento_web.php
--   Exportar: ?export=excel&tipo=todos|vacio|5_digitos|telefono
--
-- Casos:
--   1) Documento vacío (NULL o solo espacios)
--   2) Solo 5 dígitos (tras quitar separadores)
--   3) Parece teléfono (10 dígitos móvil CO, o coincide con campo Telefono)
-- =============================================================================

-- USE nombre_bd_produccion;

-- Normalización reutilizable (solo dígitos del documento)
-- doc_digits = REPLACE(...) en subconsultas

-- ── RESUMEN (conteos) ───────────────────────────────────────────────────────
SELECT 'documento_vacio' AS tipo, COUNT(*) AS total
FROM persona p
WHERE p.Numero_Documento IS NULL OR TRIM(p.Numero_Documento) = ''

UNION ALL

SELECT 'documento_solo_5_digitos' AS tipo, COUNT(*) AS total
FROM persona p
WHERE TRIM(COALESCE(p.Numero_Documento, '')) <> ''
  AND LENGTH(
        REGEXP_REPLACE(TRIM(p.Numero_Documento), '[^0-9]', '')
      ) = 5
  AND REGEXP_REPLACE(TRIM(p.Numero_Documento), '[^0-9]', '') REGEXP '^[0-9]{5}$'

UNION ALL

SELECT 'documento_parece_telefono' AS tipo, COUNT(*) AS total
FROM persona p
WHERE TRIM(COALESCE(p.Numero_Documento, '')) <> ''
  AND (
        -- Móvil Colombia: 10 dígitos, empieza en 3
        REGEXP_REPLACE(TRIM(p.Numero_Documento), '[^0-9]', '') REGEXP '^3[0-9]{9}$'
        -- Documento numérico igual al teléfono registrado
        OR (
            REGEXP_REPLACE(TRIM(COALESCE(p.Telefono, '')), '[^0-9]', '') <> ''
            AND REGEXP_REPLACE(TRIM(p.Numero_Documento), '[^0-9]', '')
                = REGEXP_REPLACE(TRIM(p.Telefono), '[^0-9]', '')
        )
        -- Con prefijo +57 / 57 delante del móvil (12 o 13 dígitos)
        OR REGEXP_REPLACE(TRIM(p.Numero_Documento), '[^0-9]', '') REGEXP '^(57)?3[0-9]{9}$'
      );


-- ── 1) DOCUMENTO VACÍO ──────────────────────────────────────────────────────
SELECT
    'documento_vacio' AS tipo_anomalia,
    p.Id_Persona,
    TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS nombre_completo,
    p.Tipo_Documento,
    p.Numero_Documento,
    p.Telefono,
    p.Estado_Cuenta,
    p.Es_Antiguo,
    p.Id_Celula,
    p.Id_Ministerio,
    TRIM(CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, ''))) AS nombre_lider,
    COALESCE(m.Nombre_Ministerio, '') AS nombre_ministerio,
    p.Proceso,
    p.Fecha_Registro
FROM persona p
LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona
LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
WHERE p.Numero_Documento IS NULL OR TRIM(p.Numero_Documento) = ''
ORDER BY p.Id_Persona;


-- ── 2) SOLO 5 DÍGITOS ───────────────────────────────────────────────────────
SELECT
    'documento_solo_5_digitos' AS tipo_anomalia,
    p.Id_Persona,
    TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS nombre_completo,
    p.Tipo_Documento,
    p.Numero_Documento AS numero_documento_original,
    REGEXP_REPLACE(TRIM(p.Numero_Documento), '[^0-9]', '') AS solo_digitos,
    p.Telefono,
    p.Estado_Cuenta,
    p.Es_Antiguo,
    p.Id_Celula,
    p.Id_Ministerio,
    p.Proceso,
    p.Fecha_Registro
FROM persona p
WHERE TRIM(COALESCE(p.Numero_Documento, '')) <> ''
  AND REGEXP_REPLACE(TRIM(p.Numero_Documento), '[^0-9]', '') REGEXP '^[0-9]{5}$'
ORDER BY solo_digitos, p.Id_Persona;


-- ── 3) PARECE TELÉFONO EN Numero_Documento ──────────────────────────────────
SELECT
    'documento_parece_telefono' AS tipo_anomalia,
    p.Id_Persona,
    TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS nombre_completo,
    p.Tipo_Documento,
    p.Numero_Documento AS numero_documento_original,
    REGEXP_REPLACE(TRIM(p.Numero_Documento), '[^0-9]', '') AS doc_solo_digitos,
    p.Telefono,
    REGEXP_REPLACE(TRIM(COALESCE(p.Telefono, '')), '[^0-9]', '') AS telefono_solo_digitos,
    CASE
        WHEN REGEXP_REPLACE(TRIM(p.Numero_Documento), '[^0-9]', '') REGEXP '^3[0-9]{9}$'
            THEN 'movil_10_digitos_3xx'
        WHEN REGEXP_REPLACE(TRIM(COALESCE(p.Telefono, '')), '[^0-9]', '') <> ''
             AND REGEXP_REPLACE(TRIM(p.Numero_Documento), '[^0-9]', '')
                 = REGEXP_REPLACE(TRIM(p.Telefono), '[^0-9]', '')
            THEN 'igual_a_campo_telefono'
        WHEN REGEXP_REPLACE(TRIM(p.Numero_Documento), '[^0-9]', '') REGEXP '^(57)?3[0-9]{9}$'
            THEN 'movil_con_prefijo_57'
        ELSE 'otro'
    END AS motivo,
    p.Estado_Cuenta,
    p.Es_Antiguo,
    p.Id_Celula,
    p.Id_Ministerio,
    p.Proceso,
    p.Fecha_Registro
FROM persona p
WHERE TRIM(COALESCE(p.Numero_Documento, '')) <> ''
  AND (
        REGEXP_REPLACE(TRIM(p.Numero_Documento), '[^0-9]', '') REGEXP '^3[0-9]{9}$'
        OR (
            REGEXP_REPLACE(TRIM(COALESCE(p.Telefono, '')), '[^0-9]', '') <> ''
            AND REGEXP_REPLACE(TRIM(p.Numero_Documento), '[^0-9]', '')
                = REGEXP_REPLACE(TRIM(p.Telefono), '[^0-9]', '')
        )
        OR REGEXP_REPLACE(TRIM(p.Numero_Documento), '[^0-9]', '') REGEXP '^(57)?3[0-9]{9}$'
      )
ORDER BY motivo, p.Id_Persona;


-- ── LISTA UNIFICADA (export / depuración) ───────────────────────────────────
SELECT *
FROM (
    SELECT
        p.Id_Persona,
        TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS nombre_completo,
        p.Numero_Documento,
        p.Telefono,
        p.Estado_Cuenta,
        p.Id_Celula,
        GROUP_CONCAT(DISTINCT t.tipo ORDER BY t.tipo SEPARATOR ', ') AS tipos_anomalia
    FROM persona p
    INNER JOIN (
        SELECT Id_Persona, 'documento_vacio' AS tipo
        FROM persona
        WHERE Numero_Documento IS NULL OR TRIM(Numero_Documento) = ''

        UNION

        SELECT Id_Persona, 'documento_solo_5_digitos' AS tipo
        FROM persona
        WHERE TRIM(COALESCE(Numero_Documento, '')) <> ''
          AND REGEXP_REPLACE(TRIM(Numero_Documento), '[^0-9]', '') REGEXP '^[0-9]{5}$'

        UNION

        SELECT Id_Persona, 'documento_parece_telefono' AS tipo
        FROM persona
        WHERE TRIM(COALESCE(Numero_Documento, '')) <> ''
          AND (
                REGEXP_REPLACE(TRIM(Numero_Documento), '[^0-9]', '') REGEXP '^3[0-9]{9}$'
                OR (
                    REGEXP_REPLACE(TRIM(COALESCE(Telefono, '')), '[^0-9]', '') <> ''
                    AND REGEXP_REPLACE(TRIM(Numero_Documento), '[^0-9]', '')
                        = REGEXP_REPLACE(TRIM(Telefono), '[^0-9]', '')
                )
                OR REGEXP_REPLACE(TRIM(Numero_Documento), '[^0-9]', '') REGEXP '^(57)?3[0-9]{9}$'
              )
    ) t ON t.Id_Persona = p.Id_Persona
    GROUP BY p.Id_Persona, nombre_completo, p.Numero_Documento, p.Telefono, p.Estado_Cuenta, p.Id_Celula
) u
ORDER BY u.Id_Persona;
