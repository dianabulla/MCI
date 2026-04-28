-- =============================================================================
-- Consulta de diagnóstico: Localizar personas en escuela_formacion_inscripcion
-- Personas: LAURA VALENTINA POSADA PARDO (CC 1015994401)
--           Laura Valentina Aponte Contreras (CC 1073156731)
-- Fecha: 2026-04-27
-- =============================================================================

-- ── BÚSQUEDA 1: Por documento exacto ──────────────────────────────────────────
SELECT
    'Por Documento' AS Criterio_Búsqueda,
    efi.Id_Inscripcion,
    efi.Id_Persona,
    efi.Nombre,
    efi.Cedula,
    efi.Telefono,
    efi.Genero,
    efi.Edad,
    efi.Programa,
    efi.Fuente,
    efi.Fecha_Registro,
    efi.Asistio_Clase,
    efi.Fecha_Asistencia_Clase
FROM escuela_formacion_inscripcion efi
WHERE efi.Cedula IN ('1015994401', '1073156731')
ORDER BY efi.Cedula, efi.Fecha_Registro DESC;


-- ── BÚSQUEDA 2: Por teléfono (normalizado) ──────────────────────────────────
SELECT
    'Por Teléfono' AS Criterio_Búsqueda,
    efi.Id_Inscripcion,
    efi.Id_Persona,
    efi.Nombre,
    efi.Cedula,
    efi.Telefono,
    efi.Genero,
    efi.Edad,
    efi.Programa,
    efi.Fuente,
    efi.Fecha_Registro,
    efi.Asistio_Clase,
    efi.Fecha_Asistencia_Clase
FROM escuela_formacion_inscripcion efi
WHERE REPLACE(REPLACE(REPLACE(efi.Telefono, ' ', ''), '-', ''), '+', '') 
      LIKE CONCAT('%', REPLACE(REPLACE(REPLACE('3144858914', ' ', ''), '-', ''), '+', ''), '%')
      OR REPLACE(REPLACE(REPLACE(efi.Telefono, ' ', ''), '-', ''), '+', '') 
      IN ('1015994401', '1073156731')
ORDER BY efi.Telefono, efi.Fecha_Registro DESC;


-- ── BÚSQUEDA 3: Por nombre (búsqueda flexible) ──────────────────────────────
SELECT
    'Por Nombre Similar' AS Criterio_Búsqueda,
    efi.Id_Inscripcion,
    efi.Id_Persona,
    efi.Nombre,
    efi.Cedula,
    efi.Telefono,
    efi.Genero,
    efi.Edad,
    efi.Programa,
    efi.Fuente,
    efi.Fecha_Registro,
    efi.Asistio_Clase,
    efi.Fecha_Asistencia_Clase
FROM escuela_formacion_inscripcion efi
WHERE UPPER(TRIM(efi.Nombre)) LIKE '%LAURA%VALENTINA%'
   OR UPPER(TRIM(efi.Nombre)) LIKE '%POSADA%'
   OR UPPER(TRIM(efi.Nombre)) LIKE '%APONTE%'
   OR UPPER(TRIM(efi.Nombre)) LIKE '%XIMENA%DUSSAN%'
ORDER BY efi.Nombre, efi.Fecha_Registro DESC;


-- ── BÚSQUEDA 4: Resumen de TODOS los registros encontrados ────────────────────
SELECT
    'RESUMEN FINAL' AS Tipo,
    COUNT(DISTINCT efi.Id_Inscripcion) AS Total_Inscripciones,
    COUNT(DISTINCT efi.Id_Persona) AS Personas_Vinculadas,
    GROUP_CONCAT(DISTINCT efi.Programa ORDER BY efi.Programa) AS Programas,
    COUNT(CASE WHEN efi.Asistio_Clase = 1 THEN 1 END) AS Con_Asistencia_Marcada
FROM escuela_formacion_inscripcion efi
WHERE efi.Cedula IN ('1015994401', '1073156731')
   OR REPLACE(REPLACE(REPLACE(efi.Telefono, ' ', ''), '-', ''), '+', '') 
      IN ('1015994401', '1073156731')
   OR UPPER(TRIM(efi.Nombre)) LIKE '%LAURA%VALENTINA%'
   OR UPPER(TRIM(efi.Nombre)) LIKE '%POSADA%'
   OR UPPER(TRIM(efi.Nombre)) LIKE '%APONTE%'
   OR UPPER(TRIM(efi.Nombre)) LIKE '%XIMENA%DUSSAN%';


-- ── BÚSQUEDA 5: Verificar si están vinculadas a personas en tabla 'persona' ────
SELECT
    p.Id_Persona,
    CONCAT(p.Nombre, ' ', p.Apellido) AS Nombre_Completo,
    p.Numero_Documento,
    p.Telefono,
    COUNT(efi.Id_Inscripcion) AS Total_Inscripciones_Escuela,
    GROUP_CONCAT(DISTINCT efi.Programa) AS Programas_Inscritos
FROM persona p
LEFT JOIN escuela_formacion_inscripcion efi ON efi.Id_Persona = p.Id_Persona
WHERE p.Numero_Documento IN ('1015994401', '1073156731')
GROUP BY p.Id_Persona, p.Nombre, p.Apellido, p.Numero_Documento, p.Telefono
ORDER BY p.Numero_Documento;
