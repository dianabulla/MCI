-- =============================================
-- PASO 1: Insertar solo los 7 líderes
-- MCI Madrid Colombia
-- Ejecuta este script PRIMERO
-- =============================================

USE u694856656_mci;

INSERT INTO persona (Nombre, Apellido, Tipo_Documento, Numero_Documento, Fecha_Nacimiento, Edad, Genero, Telefono, Email, Usuario, Contrasena, Estado_Cuenta, Hora_Llamada, Direccion, Barrio, Id_Rol, Id_Ministerio, Fecha_Registro, Fecha_Registro_Unix) VALUES
('Carlos', 'Martínez', 'Cedula de Ciudadania', '1234567890', '1985-03-15', 40, 'Hombre', '3201234567', 'carlos.martinez@iglesia.com', 'carlos.lider', '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi', 'Activo', 'Cualquier Hora', 'Calle 10 #20-30', 'Centro', 2, 6, '2024-01-15 09:00:00', 1705305600),
('María', 'González', 'Cedula de Ciudadania', '2345678901', '1988-07-22', 37, 'Mujer', '3102345678', 'maria.gonzalez@iglesia.com', 'maria.lider', '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi', 'Activo', 'Tarde', 'Carrera 15 #8-25', 'San Antonio', 2, 7, '2024-02-10 10:30:00', 1707562200),
('Juan', 'Rodríguez', 'Cedula de Ciudadania', '3456789012', '1982-11-08', 43, 'Hombre', '3153456789', 'juan.rodriguez@iglesia.com', 'juan.lider', '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi', 'Activo', 'Noche', 'Avenida 30 #45-60', 'Las Flores', 2, 8, '2024-03-05 14:00:00', 1709643600),
('Ana', 'Sánchez', 'Cedula de Ciudadania', '4567890123', '1990-05-18', 35, 'Mujer', '3204567890', 'ana.sanchez@iglesia.com', 'ana.lider', '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi', 'Activo', 'Ma??ana', 'Calle 25 #12-18', 'La Esperanza', 2, 9, '2024-04-12 08:15:00', 1712912100),
('Pedro', 'López', 'Cedula de Ciudadania', '5678901234', '1987-09-25', 38, 'Hombre', '3155678901', 'pedro.lopez@iglesia.com', 'pedro.lider', '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi', 'Activo', 'Medio Dia', 'Transversal 8 #22-35', 'Villa Nueva', 2, 10, '2024-05-08 11:45:00', 1715165100),
('Laura', 'Ramírez', 'Cedula de Ciudadania', '6789012345', '1992-12-30', 33, 'Mujer', '3206789012', 'laura.ramirez@iglesia.com', 'laura.lider', '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi', 'Activo', 'Tarde', 'Diagonal 20 #15-40', 'El Paraíso', 2, 11, '2024-06-15 15:20:00', 1718465200),
('Miguel', 'Torres', 'Cedula de Ciudadania', '7890123456', '1984-04-12', 41, 'Hombre', '3157890123', 'miguel.torres@iglesia.com', 'miguel.lider', '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi', 'Activo', 'Noche', 'Carrera 5 #30-22', 'Los Pinos', 2, 12, '2024-07-20 16:00:00', 1721491200);

-- Ver los IDs asignados a los líderes
SELECT Id_Persona, Nombre, Apellido, Usuario 
FROM persona 
WHERE Id_Rol = 2 
ORDER BY Id_Persona DESC 
LIMIT 7;

-- IMPORTANTE: Anota los IDs que aparecen arriba (ejemplo: 22, 23, 24, 25, 26, 27, 28)
-- Luego edita el archivo "insertar_miembros_segundo.sql" 
-- y reemplaza 22, 23, 24, 25, 26, 27, 28 con los IDs reales
