-- =============================================================================
-- Consulta de diagnóstico: Localizar personas en tabla 'persona'
-- Personas: LAURA VALENTINA POSADA PARDO (CC 1015994401)
--           Laura Valentina Aponte Contreras (CC 1073156731)
-- Fecha: 2026-04-27
-- =============================================================================

-- ── BÚSQUEDA 1: Por documento exacto ──────────────────────────────────────────
SELECT
    'Por Documento' AS Criterio_Búsqueda,
    p.Id_Persona,
    CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Completo,
    p.Numero_Documento,
    p.Telefono,
    p.Genero,
    p.Edad,
    p.Id_Lider,
    p.Id_Ministerio,
    p.Id_Celula,
    p.Proceso,
    p.Es_Antiguo,
    p.Fecha_Registro,
    p.Escalera_Checklist
FROM persona p
WHERE p.Numero_Documento IN ('1015994401', '1073156731')
ORDER BY p.Numero_Documento;


-- ── BÚSQUEDA 2: Por teléfono (normalizado) ──────────────────────────────────
SELECT
    'Por Teléfono' AS Criterio_Búsqueda,
    p.Id_Persona,
    CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Completo,
    p.Numero_Documento,
    p.Telefono,
    p.Genero,
    p.Edad,
    p.Id_Lider,
    p.Id_Ministerio,
    p.Id_Celula,
    p.Proceso,
    p.Es_Antiguo,
    p.Fecha_Registro,
    p.Escalera_Checklist
FROM persona p
WHERE REPLACE(REPLACE(REPLACE(p.Telefono, ' ', ''), '-', ''), '+', '') 
      IN (REPLACE(REPLACE(REPLACE('3144858914', ' ', ''), '-', ''), '+', ''),
          REPLACE(REPLACE(REPLACE('1015994401', ' ', ''), '-', ''), '+', ''),
          REPLACE(REPLACE(REPLACE('1073156731', ' ', ''), '-', ''), '+', ''))
ORDER BY p.Telefono;


-- ── BÚSQUEDA 3: Por nombre (búsqueda flexible) ──────────────────────────────
SELECT
    'Por Nombre Similar' AS Criterio_Búsqueda,
    p.Id_Persona,
    CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Completo,
    p.Numero_Documento,
    p.Telefono,
    p.Genero,
    p.Edad,
    p.Id_Lider,
    p.Id_Ministerio,
    p.Id_Celula,
    p.Proceso,
    p.Es_Antiguo,
    p.Fecha_Registro,
    p.Escalera_Checklist
FROM persona p
WHERE UPPER(TRIM(CONCAT(p.Nombre, ' ', p.Apellido))) LIKE '%LAURA%VALENTINA%POSADA%'
   OR UPPER(TRIM(CONCAT(p.Nombre, ' ', p.Apellido))) LIKE '%LAURA%VALENTINA%APONTE%'
   OR UPPER(TRIM(CONCAT(p.Nombre, ' ', p.Apellido))) LIKE '%XIMENA%DUSSAN%'
   OR UPPER(TRIM(p.Nombre)) LIKE '%LAURA%'
ORDER BY p.Nombre, p.Apellido;


-- ── BÚSQUEDA 4: Información completa de contexto (líder, ministerio, célula) ──
SELECT
    p.Id_Persona,
    CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Completo,
    p.Numero_Documento,
    p.Telefono,
    p.Genero,
    p.Edad,
    CONCAT(pl.Nombre, ' ', pl.Apellido) AS Nombre_Lider,
    m.Nombre_Ministerio,
    c.Nombre_Celula,
    p.Proceso,
    p.Es_Antiguo,
    p.Fecha_Registro
FROM persona p
LEFT JOIN persona pl ON p.Id_Lider = pl.Id_Persona
LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
WHERE p.Numero_Documento IN ('1015994401', '1073156731')
ORDER BY p.Numero_Documento;


-- ── BÚSQUEDA 5: Inscripciones en escuelas de formación vinculadas a esas personas
SELECT
    p.Id_Persona,
    CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Persona,
    p.Numero_Documento,
    COUNT(efi.Id_Inscripcion) AS Total_Inscripciones,
    GROUP_CONCAT(DISTINCT efi.Programa ORDER BY efi.Programa) AS Programas_Inscritos,
    GROUP_CONCAT(DISTINCT efi.Fuente ORDER BY efi.Fuente) AS Fuentes_Inscripción,
    MIN(efi.Fecha_Registro) AS Primera_Inscripción,
    MAX(efi.Fecha_Registro) AS Última_Inscripción
FROM persona p
LEFT JOIN escuela_formacion_inscripcion efi ON efi.Id_Persona = p.Id_Persona
WHERE p.Numero_Documento IN ('1015994401', '1073156731')
GROUP BY p.Id_Persona, p.Nombre, p.Apellido, p.Numero_Documento
ORDER BY p.Numero_Documento;


-- ── BÚSQUEDA 6: Estado de la escuela de formación por programa ────────────────
SELECT
    p.Id_Persona,
    CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Persona,
    p.Numero_Documento,
    efe.Programa,
    efe.Va AS Aprobó,
    efe.Fecha_Actualizacion
FROM persona p
LEFT JOIN escuela_formacion_estado efe ON efe.Id_Persona = p.Id_Persona
WHERE p.Numero_Documento IN ('1015994401', '1073156731')
ORDER BY p.Numero_Documento, efe.Programa;


-- ── BÚSQUEDA 7: Resumen general de las 2 personas ─────────────────────────────
SELECT
    p.Id_Persona,
    CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Completo,
    p.Numero_Documento,
    p.Telefono,
    p.Genero,
    p.Edad,
    p.Proceso AS Etapa_Escalera,
    p.Es_Antiguo,
    CASE 
        WHEN p.Id_Lider > 0 THEN 'Sí'
        ELSE 'No'
    END AS Tiene_Lider,
    CASE 
        WHEN p.Id_Ministerio > 0 THEN 'Sí'
        ELSE 'No'
    END AS Tiene_Ministerio,
    CASE 
        WHEN p.Id_Celula > 0 THEN 'Sí'
        ELSE 'No'
    END AS Tiene_Célula,
    p.Fecha_Registro,
    (SELECT COUNT(*) FROM escuela_formacion_inscripcion WHERE Id_Persona = p.Id_Persona) AS Inscripciones_Escuela
FROM persona p
WHERE p.Numero_Documento IN ('1015994401', '1073156731')
ORDER BY p.Numero_Documento;
