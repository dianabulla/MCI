-- =============================================
-- Script para insertar datos de prueba
-- MCI Madrid Colombia
-- =============================================

USE u694856656_mci;

-- =============================================
-- 1. INSERTAR 7 LÍDERES
-- =============================================

-- Obtener el último ID antes de insertar
SET @ultimo_id = (SELECT COALESCE(MAX(Id_Persona), 0) FROM persona);

INSERT INTO persona (Nombre, Apellido, Tipo_Documento, Numero_Documento, Fecha_Nacimiento, Edad, Genero, Telefono, Email, Usuario, Contrasena, Estado_Cuenta, Hora_Llamada, Direccion, Barrio, Id_Rol, Id_Ministerio, Fecha_Registro, Fecha_Registro_Unix) VALUES
('Carlos', 'Martínez', 'Cedula de Ciudadania', '1234567890', '1985-03-15', 40, 'Hombre', '3201234567', 'carlos.martinez@iglesia.com', 'carlos.lider', '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi', 'Activo', 'Cualquier Hora', 'Calle 10 #20-30', 'Centro', 2, 6, '2024-01-15 09:00:00', 1705305600),
('María', 'González', 'Cedula de Ciudadania', '2345678901', '1988-07-22', 37, 'Mujer', '3102345678', 'maria.gonzalez@iglesia.com', 'maria.lider', '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi', 'Activo', 'Tarde', 'Carrera 15 #8-25', 'San Antonio', 2, 7, '2024-02-10 10:30:00', 1707562200),
('Juan', 'Rodríguez', 'Cedula de Ciudadania', '3456789012', '1982-11-08', 43, 'Hombre', '3153456789', 'juan.rodriguez@iglesia.com', 'juan.lider', '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi', 'Activo', 'Noche', 'Avenida 30 #45-60', 'Las Flores', 2, 8, '2024-03-05 14:00:00', 1709643600),
('Ana', 'Sánchez', 'Cedula de Ciudadania', '4567890123', '1990-05-18', 35, 'Mujer', '3204567890', 'ana.sanchez@iglesia.com', 'ana.lider', '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi', 'Activo', 'Ma??ana', 'Calle 25 #12-18', 'La Esperanza', 2, 9, '2024-04-12 08:15:00', 1712912100),
('Pedro', 'López', 'Cedula de Ciudadania', '5678901234', '1987-09-25', 38, 'Hombre', '3155678901', 'pedro.lopez@iglesia.com', 'pedro.lider', '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi', 'Activo', 'Medio Dia', 'Transversal 8 #22-35', 'Villa Nueva', 2, 10, '2024-05-08 11:45:00', 1715165100),
('Laura', 'Ramírez', 'Cedula de Ciudadania', '6789012345', '1992-12-30', 33, 'Mujer', '3206789012', 'laura.ramirez@iglesia.com', 'laura.lider', '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi', 'Activo', 'Tarde', 'Diagonal 20 #15-40', 'El Paraíso', 2, 11, '2024-06-15 15:20:00', 1718465200),
('Miguel', 'Torres', 'Cedula de Ciudadania', '7890123456', '1984-04-12', 41, 'Hombre', '3157890123', 'miguel.torres@iglesia.com', 'miguel.lider', '$2y$10$KNdBvEWdHiWn7LYR1ag7h.ZbMbKd05virFg5ibEX9cN4eKRfdaqDi', 'Activo', 'Noche', 'Carrera 5 #30-22', 'Los Pinos', 2, 12, '2024-07-20 16:00:00', 1721491200);

-- Establecer variables con los IDs de los líderes recién insertados
SET @carlos_id = @ultimo_id + 1;
SET @maria_id = @ultimo_id + 2;
SET @juan_id = @ultimo_id + 3;
SET @ana_id = @ultimo_id + 4;
SET @pedro_id = @ultimo_id + 5;
SET @laura_id = @ultimo_id + 6;
SET @miguel_id = @ultimo_id + 7;

-- =============================================
-- 2. INSERTAR 100 MIEMBROS
-- =============================================

-- Miembros asignados a diferentes líderes y ministerios
INSERT INTO persona (Nombre, Apellido, Tipo_Documento, Numero_Documento, Fecha_Nacimiento, Edad, Genero, Telefono, Email, Hora_Llamada, Direccion, Barrio, Invitado_Por, Id_Lider, Id_Rol, Id_Ministerio, Fecha_Registro, Fecha_Registro_Unix) VALUES
-- Asignados a Carlos (líder 22)
('Sofia', 'Hernández', 'Cedula de Ciudadania', '8901234567', '1995-02-14', 30, 'Mujer', '3208901234', 'sofia.hernandez@email.com', 'Cualquier Hora', 'Calle 12 #5-10', 'Centro', 12, 22, 3, 6, '2024-08-01 09:00:00', 1722499200),
('Andrés', 'Castro', 'Cedula de Ciudadania', '9012345678', '1998-06-20', 27, 'Joven Hombre', '3159012345', 'andres.castro@email.com', 'Tarde', 'Carrera 8 #15-20', 'San Antonio', 22, 22, 3, 6, '2024-08-05 10:30:00', 1722844200),
('Valentina', 'Vargas', 'Cedula de Ciudadania', '0123456789', '2000-09-10', 25, 'Joven Mujer', '3200123456', 'valentina.vargas@email.com', 'Ma??ana', 'Avenida 5 #8-12', 'Centro', 22, 22, 3, 7, '2024-08-10 11:00:00', 1723284000),
('Santiago', 'Morales', 'Cedula de Ciudadania', '1112223334', '1993-11-05', 32, 'Hombre', '3211112233', 'santiago.morales@email.com', 'Noche', 'Calle 20 #10-15', 'Las Flores', 22, 22, 3, 6, '2024-08-15 14:30:00', 1723732200),
('Camila', 'Jiménez', 'Cedula de Ciudadania', '2223334445', '1997-03-28', 28, 'Mujer', '3152223344', 'camila.jimenez@email.com', 'Medio Dia', 'Transversal 3 #12-8', 'San Antonio', 22, 22, 3, 7, '2024-08-20 15:00:00', 1724162400),
('Felipe', 'Ruiz', 'Cedula de Ciudadania', '3334445556', '1999-07-15', 26, 'Joven Hombre', '3203334445', 'felipe.ruiz@email.com', 'Tarde', 'Diagonal 8 #5-20', 'Centro', 22, 22, 3, 8, '2024-08-25 16:00:00', 1724598000),
('Isabella', 'Mendoza', 'Cedula de Ciudadania', '4445556667', '1996-12-22', 29, 'Mujer', '3154445556', 'isabella.mendoza@email.com', 'Cualquier Hora', 'Carrera 12 #18-25', 'Villa Nueva', 22, 22, 3, 6, '2024-09-01 09:30:00', 1725181800),
('Nicolás', 'Rojas', 'Cedula de Ciudadania', '5556667778', '1994-05-08', 31, 'Hombre', '3205556667', 'nicolas.rojas@email.com', 'Ma??ana', 'Calle 15 #22-30', 'Las Flores', 22, 22, 3, 7, '2024-09-05 10:00:00', 1725526800),
('Mariana', 'Acosta', 'Cedula de Ciudadania', '6667778889', '2001-08-30', 24, 'Joven Mujer', '3156667778', 'mariana.acosta@email.com', 'Tarde', 'Avenida 10 #5-15', 'Centro', 22, 22, 3, 8, '2024-09-10 11:30:00', 1725967800),
('Mateo', 'Silva', 'Cedula de Ciudadania', '7778889990', '1992-04-18', 33, 'Hombre', '3207778889', 'mateo.silva@email.com', 'Noche', 'Transversal 5 #8-12', 'San Antonio', 22, 22, 3, 6, '2024-09-15 14:00:00', 1726408800),
('Luciana', 'Ortiz', 'Cedula de Ciudadania', '8889990001', '1998-10-25', 27, 'Mujer', '3158889990', 'luciana.ortiz@email.com', 'Medio Dia', 'Calle 8 #15-20', 'Villa Nueva', 22, 22, 3, 7, '2024-09-20 15:30:00', 1726845000),
('Gabriel', 'Medina', 'Cedula de Ciudadania', '9990001112', '1995-01-12', 30, 'Hombre', '3209990001', 'gabriel.medina@email.com', 'Cualquier Hora', 'Carrera 20 #10-18', 'Las Flores', 22, 22, 3, 8, '2024-09-25 16:00:00', 1727280000),
('Valeria', 'Guerrero', 'Cedula de Ciudadania', '0001112223', '1999-06-07', 26, 'Joven Mujer', '3150001112', 'valeria.guerrero@email.com', 'Tarde', 'Diagonal 12 #8-15', 'Centro', 22, 22, 3, 9, '2024-10-01 09:00:00', 1727776800),
('Daniel', 'Ríos', 'Cedula de Ciudadania', '1112223335', '1991-09-14', 34, 'Hombre', '3201112223', 'daniel.rios@email.com', 'Ma??ana', 'Avenida 8 #12-20', 'San Antonio', 22, 22, 3, 6, '2024-10-05 10:30:00', 1728122200),

-- Asignados a María (líder 23)
('Gabriela', 'Vega', 'Cedula de Ciudadania', '2223334446', '1997-11-28', 28, 'Mujer', '3152223335', 'gabriela.vega@email.com', 'Noche', 'Calle 25 #5-10', 'Villa Nueva', 12, 23, 3, 7, '2024-10-10 11:00:00', 1728558000),
('Sebastián', 'Navarro', 'Cedula de Ciudadania', '3334445557', '1996-03-15', 29, 'Hombre', '3203334446', 'sebastian.navarro@email.com', 'Tarde', 'Transversal 10 #15-22', 'Las Flores', 23, 23, 3, 8, '2024-10-15 14:30:00', 1729005000),
('Catalina', 'Salazar', 'Cedula de Ciudadania', '4445556668', '2000-07-22', 25, 'Joven Mujer', '3154445557', 'catalina.salazar@email.com', 'Medio Dia', 'Carrera 5 #8-12', 'Centro', 23, 23, 3, 7, '2024-10-20 15:00:00', 1729440000),
('Alejandro', 'Cortés', 'Cedula de Ciudadania', '5556667779', '1994-12-05', 31, 'Hombre', '3205556668', 'alejandro.cortes@email.com', 'Cualquier Hora', 'Diagonal 15 #10-18', 'San Antonio', 23, 23, 3, 9, '2024-10-25 16:00:00', 1729875600),
('Daniela', 'Molina', 'Cedula de Ciudadania', '6667778890', '1998-04-18', 27, 'Mujer', '3156667779', 'daniela.molina@email.com', 'Ma??ana', 'Avenida 12 #5-15', 'Villa Nueva', 23, 23, 3, 6, '2024-11-01 09:30:00', 1730458200),
('Julián', 'Aguilar', 'Cedula de Ciudadania', '7778889991', '1993-08-30', 32, 'Hombre', '3207778890', 'julian.aguilar@email.com', 'Tarde', 'Calle 18 #12-20', 'Las Flores', 23, 23, 3, 7, '2024-11-05 10:00:00', 1730803200),
('Carolina', 'Reyes', 'Cedula de Ciudadania', '8889990002', '1999-01-12', 26, 'Joven Mujer', '3158889991', 'carolina.reyes@email.com', 'Noche', 'Transversal 8 #8-15', 'Centro', 23, 23, 3, 8, '2024-11-10 11:30:00', 1731244200),
('Oscar', 'Peña', 'Cedula de Ciudadania', '9990001113', '1992-05-25', 33, 'Hombre', '3209990002', 'oscar.pena@email.com', 'Medio Dia', 'Carrera 15 #10-18', 'San Antonio', 23, 23, 3, 7, '2024-11-15 14:00:00', 1731679200),
('Paula', 'Arce', 'Cedula de Ciudadania', '0001112224', '1996-09-08', 29, 'Mujer', '3150001113', 'paula.arce@email.com', 'Cualquier Hora', 'Diagonal 20 #5-12', 'Villa Nueva', 23, 23, 3, 9, '2024-11-20 15:30:00', 1732120200),
('Esteban', 'Blanco', 'Cedula de Ciudadania', '1112223336', '1995-02-20', 30, 'Hombre', '3201112224', 'esteban.blanco@email.com', 'Tarde', 'Avenida 5 #8-15', 'Las Flores', 23, 23, 3, 6, '2024-11-25 16:00:00', 1732554000),
('Natalia', 'Carrillo', 'Cedula de Ciudadania', '2223334447', '1998-06-14', 27, 'Mujer', '3152223336', 'natalia.carrillo@email.com', 'Ma??ana', 'Calle 10 #12-18', 'Centro', 23, 23, 3, 7, '2024-12-01 09:00:00', 1733047200),
('Rodrigo', 'Pardo', 'Cedula de Ciudadania', '3334445558', '1991-10-28', 34, 'Hombre', '3203334447', 'rodrigo.pardo@email.com', 'Noche', 'Transversal 12 #8-12', 'San Antonio', 23, 23, 3, 8, '2024-12-05 10:30:00', 1733392200),
('Andrea', 'Gómez', 'Cedula de Ciudadania', '4445556669', '1997-03-12', 28, 'Mujer', '3154445558', 'andrea.gomez@email.com', 'Tarde', 'Carrera 8 #15-20', 'Villa Nueva', 23, 23, 3, 7, '2024-12-10 11:00:00', 1733828400),

-- Asignados a Juan (líder 24)
('Ricardo', 'Luna', 'Cedula de Ciudadania', '5556667780', '1994-07-25', 31, 'Hombre', '3205556669', 'ricardo.luna@email.com', 'Medio Dia', 'Diagonal 5 #10-15', 'Las Flores', 12, 24, 3, 9, '2024-12-13 14:30:00', 1734096600),
('Diana', 'Cárdenas', 'Cedula de Ciudadania', '6667778891', '1999-11-08', 26, 'Joven Mujer', '3156667780', 'diana.cardenas@email.com', 'Cualquier Hora', 'Avenida 15 #5-10', 'Centro', 24, 24, 3, 6, '2024-12-13 15:00:00', 1734098400),
('Javier', 'Suárez', 'Cedula de Ciudadania', '7778889992', '1996-02-18', 29, 'Hombre', '3207778891', 'javier.suarez@email.com', 'Ma??ana', 'Calle 22 #8-12', 'San Antonio', 24, 24, 3, 7, '2024-12-13 15:30:00', 1734100200),
('Claudia', 'Parra', 'Cedula de Ciudadania', '8889990003', '1993-06-30', 32, 'Mujer', '3158889992', 'claudia.parra@email.com', 'Tarde', 'Transversal 6 #12-18', 'Villa Nueva', 24, 24, 3, 8, '2024-12-13 16:00:00', 1734102000),
('Eduardo', 'Villalobos', 'Cedula de Ciudadania', '9990001114', '1998-10-15', 27, 'Hombre', '3209990003', 'eduardo.villalobos@email.com', 'Noche', 'Carrera 10 #5-15', 'Las Flores', 24, 24, 3, 7, '2024-12-13 16:30:00', 1734103800),
('Fernanda', 'Cruz', 'Cedula de Ciudadania', '0001112225', '2000-01-22', 25, 'Joven Mujer', '3150001114', 'fernanda.cruz@email.com', 'Medio Dia', 'Diagonal 8 #10-12', 'Centro', 24, 24, 3, 9, '2024-12-13 17:00:00', 1734105600),
('Héctor', 'Romero', 'Cedula de Ciudadania', '1112223337', '1992-05-08', 33, 'Hombre', '3201112225', 'hector.romero@email.com', 'Cualquier Hora', 'Avenida 20 #8-15', 'San Antonio', 24, 24, 3, 6, '2024-12-13 17:30:00', 1734107400),
('Paola', 'Rincón', 'Cedula de Ciudadania', '2223334448', '1995-08-20', 30, 'Mujer', '3152223337', 'paola.rincon@email.com', 'Tarde', 'Calle 12 #15-20', 'Villa Nueva', 24, 24, 3, 7, '2024-12-13 18:00:00', 1734109200),
('Iván', 'Sandoval', 'Cedula de Ciudadania', '3334445559', '1997-12-05', 28, 'Hombre', '3203334448', 'ivan.sandoval@email.com', 'Ma??ana', 'Transversal 15 #5-10', 'Las Flores', 24, 24, 3, 8, '2025-01-05 09:00:00', 1736067600),
('Adriana', 'Zamora', 'Cedula de Ciudadania', '4445556670', '1999-04-18', 26, 'Mujer', '3154445559', 'adriana.zamora@email.com', 'Noche', 'Carrera 18 #10-15', 'Centro', 24, 24, 3, 7, '2025-01-10 10:30:00', 1736504400),
('Luis', 'Valencia', 'Cedula de Ciudadania', '5556667781', '1991-08-30', 34, 'Hombre', '3205556670', 'luis.valencia@email.com', 'Tarde', 'Diagonal 10 #8-12', 'San Antonio', 24, 24, 3, 9, '2025-01-15 11:00:00', 1736941200),
('Mónica', 'Estrada', 'Cedula de Ciudadania', '6667778892', '1996-01-12', 29, 'Mujer', '3156667781', 'monica.estrada@email.com', 'Medio Dia', 'Avenida 8 #5-10', 'Villa Nueva', 24, 24, 3, 6, '2025-01-20 14:30:00', 1737383400),
('Fabián', 'Montes', 'Cedula de Ciudadania', '7778889993', '1994-05-25', 31, 'Hombre', '3207778892', 'fabian.montes@email.com', 'Cualquier Hora', 'Calle 5 #12-18', 'Las Flores', 24, 24, 3, 7, '2025-01-25 15:00:00', 1737818400),
('Lorena', 'Vera', 'Cedula de Ciudadania', '8889990004', '1998-09-08', 27, 'Mujer', '3158889993', 'lorena.vera@email.com', 'Tarde', 'Transversal 20 #10-15', 'Centro', 24, 24, 3, 8, '2025-02-01 16:00:00', 1738432800),

-- Asignados a Ana (líder 25)
('Roberto', 'Maldonado', 'Cedula de Ciudadania', '9990001115', '1993-02-14', 32, 'Hombre', '3209990004', 'roberto.maldonado@email.com', 'Ma??ana', 'Carrera 12 #8-12', 'San Antonio', 12, 25, 3, 7, '2025-02-05 09:30:00', 1738777800),
('Beatriz', 'Ochoa', 'Cedula de Ciudadania', '0001112226', '1997-06-28', 28, 'Mujer', '3150001115', 'beatriz.ochoa@email.com', 'Noche', 'Diagonal 12 #5-10', 'Villa Nueva', 25, 25, 3, 9, '2025-02-10 10:00:00', 1739177400),
('Cristian', 'Figueroa', 'Cedula de Ciudadania', '1112223338', '1995-10-12', 30, 'Hombre', '3201112226', 'cristian.figueroa@email.com', 'Tarde', 'Avenida 10 #12-18', 'Las Flores', 25, 25, 3, 6, '2025-02-15 11:30:00', 1739614200),
('Elena', 'Nieto', 'Cedula de Ciudadania', '2223334449', '1999-03-25', 26, 'Joven Mujer', '3152223338', 'elena.nieto@email.com', 'Medio Dia', 'Calle 15 #10-15', 'Centro', 25, 25, 3, 7, '2025-02-20 14:00:00', 1740051600),
('Francisco', 'Lara', 'Cedula de Ciudadania', '3334445560', '1992-07-08', 33, 'Hombre', '3203334449', 'francisco.lara@email.com', 'Cualquier Hora', 'Transversal 8 #8-12', 'San Antonio', 25, 25, 3, 8, '2025-02-25 15:30:00', 1740488400),
('Gloria', 'Barrera', 'Cedula de Ciudadania', '4445556671', '1996-11-20', 29, 'Mujer', '3154445560', 'gloria.barrera@email.com', 'Tarde', 'Carrera 5 #5-10', 'Villa Nueva', 25, 25, 3, 7, '2025-03-01 16:00:00', 1740841200),
('Hernán', 'Cabrera', 'Cedula de Ciudadania', '5556667782', '1998-04-05', 27, 'Hombre', '3205556671', 'hernan.cabrera@email.com', 'Ma??ana', 'Diagonal 5 #12-18', 'Las Flores', 25, 25, 3, 9, '2025-03-05 09:00:00', 1741244400),
('Inés', 'Guzmán', 'Cedula de Ciudadania', '6667778893', '2000-08-18', 25, 'Joven Mujer', '3156667782', 'ines.guzman@email.com', 'Noche', 'Avenida 12 #10-15', 'Centro', 25, 25, 3, 6, '2025-03-10 10:30:00', 1741681200),
('Jorge', 'Duarte', 'Cedula de Ciudadania', '7778889994', '1994-12-30', 31, 'Hombre', '3207778893', 'jorge.duarte@email.com', 'Tarde', 'Calle 8 #8-12', 'San Antonio', 25, 25, 3, 7, '2025-03-15 11:00:00', 1742118000),
('Karina', 'Contreras', 'Cedula de Ciudadania', '8889990005', '1997-05-15', 28, 'Mujer', '3158889994', 'karina.contreras@email.com', 'Medio Dia', 'Transversal 10 #5-10', 'Villa Nueva', 25, 25, 3, 8, '2025-03-20 14:30:00', 1742559000),
('Leonardo', 'Téllez', 'Cedula de Ciudadania', '9990001116', '1993-09-28', 32, 'Hombre', '3209990005', 'leonardo.tellez@email.com', 'Cualquier Hora', 'Carrera 15 #12-18', 'Las Flores', 25, 25, 3, 7, '2025-03-25 15:00:00', 1742994000),
('Marcela', 'Orozco', 'Cedula de Ciudadania', '0001112227', '1996-02-12', 29, 'Mujer', '3150001116', 'marcela.orozco@email.com', 'Tarde', 'Diagonal 15 #10-15', 'Centro', 25, 25, 3, 9, '2025-04-01 16:00:00', 1743609600),
('Nelson', 'Porras', 'Cedula de Ciudadania', '1112223339', '1999-06-25', 26, 'Hombre', '3201112227', 'nelson.porras@email.com', 'Ma??ana', 'Avenida 5 #8-12', 'San Antonio', 25, 25, 3, 6, '2025-04-05 09:30:00', 1744012200),
('Olga', 'Murillo', 'Cedula de Ciudadania', '2223334450', '1991-10-08', 34, 'Mujer', '3152223339', 'olga.murillo@email.com', 'Noche', 'Calle 20 #5-10', 'Villa Nueva', 25, 25, 3, 7, '2025-04-10 10:00:00', 1744407600),

-- Asignados a Pedro (líder 26)
('Pablo', 'Quintero', 'Cedula de Ciudadania', '3334445561', '1995-03-20', 30, 'Hombre', '3203334450', 'pablo.quintero@email.com', 'Tarde', 'Transversal 12 #12-18', 'Las Flores', 12, 26, 3, 8, '2025-04-15 11:30:00', 1744848600),
('Raquel', 'Palacios', 'Cedula de Ciudadania', '4445556672', '1998-07-05', 27, 'Mujer', '3154445561', 'raquel.palacios@email.com', 'Medio Dia', 'Carrera 8 #10-15', 'Centro', 26, 26, 3, 7, '2025-04-20 14:00:00', 1745283600),
('Sergio', 'Herrera', 'Cedula de Ciudadania', '5556667783', '1992-11-18', 33, 'Hombre', '3205556672', 'sergio.herrera@email.com', 'Cualquier Hora', 'Diagonal 8 #8-12', 'San Antonio', 26, 26, 3, 9, '2025-04-25 15:30:00', 1745722200),
('Teresa', 'Chávez', 'Cedula de Ciudadania', '6667778894', '1997-04-30', 28, 'Mujer', '3156667783', 'teresa.chavez@email.com', 'Tarde', 'Avenida 15 #5-10', 'Villa Nueva', 26, 26, 3, 6, '2025-05-01 16:00:00', 1746115200),
('Ulises', 'Serrano', 'Cedula de Ciudadania', '7778889995', '1994-08-15', 31, 'Hombre', '3207778894', 'ulises.serrano@email.com', 'Ma??ana', 'Calle 10 #12-18', 'Las Flores', 26, 26, 3, 7, '2025-05-05 09:00:00', 1746457200),
('Verónica', 'Pacheco', 'Cedula de Ciudadania', '8889990006', '1999-12-28', 26, 'Joven Mujer', '3158889995', 'veronica.pacheco@email.com', 'Noche', 'Transversal 5 #10-15', 'Centro', 26, 26, 3, 8, '2025-05-10 10:30:00', 1746893400),
('William', 'Arias', 'Cedula de Ciudadania', '9990001117', '1993-05-12', 32, 'Hombre', '3209990006', 'william.arias@email.com', 'Tarde', 'Carrera 12 #8-12', 'San Antonio', 26, 26, 3, 7, '2025-05-15 11:00:00', 1747329600),
('Ximena', 'Galindo', 'Cedula de Ciudadania', '0001112228', '1996-09-25', 29, 'Mujer', '3150001117', 'ximena.galindo@email.com', 'Medio Dia', 'Diagonal 10 #5-10', 'Villa Nueva', 26, 26, 3, 9, '2025-05-20 14:30:00', 1747770600),
('Yesid', 'Bautista', 'Cedula de Ciudadania', '1112223340', '1998-02-08', 27, 'Hombre', '3201112228', 'yesid.bautista@email.com', 'Cualquier Hora', 'Avenida 8 #12-18', 'Las Flores', 26, 26, 3, 6, '2025-05-25 15:00:00', 1748203200),
('Zulma', 'Riveros', 'Cedula de Ciudadania', '2223334451', '1991-06-20', 34, 'Mujer', '3152223340', 'zulma.riveros@email.com', 'Tarde', 'Calle 15 #10-15', 'Centro', 26, 26, 3, 7, '2025-06-01 16:00:00', 1748797200),
('Alberto', 'Cardona', 'Cedula de Ciudadania', '3334445562', '1995-10-05', 30, 'Hombre', '3203334451', 'alberto.cardona@email.com', 'Ma??ana', 'Transversal 15 #8-12', 'San Antonio', 26, 26, 3, 8, '2025-06-05 09:30:00', 1749141000),
('Blanca', 'Ibáñez', 'Cedula de Ciudadania', '4445556673', '1997-03-18', 28, 'Mujer', '3154445562', 'blanca.ibanez@email.com', 'Noche', 'Carrera 5 #5-10', 'Villa Nueva', 26, 26, 3, 7, '2025-06-10 10:00:00', 1749536400),
('Carlos', 'Escobar', 'Cedula de Ciudadania', '5556667784', '1999-07-30', 26, 'Joven Hombre', '3205556673', 'carlos.escobar2@email.com', 'Tarde', 'Diagonal 5 #12-18', 'Las Flores', 26, 26, 3, 9, '2025-06-15 11:30:00', 1749977400),
('Dora', 'León', 'Cedula de Ciudadania', '6667778895', '1992-12-15', 33, 'Mujer', '3156667784', 'dora.leon@email.com', 'Medio Dia', 'Avenida 10 #10-15', 'Centro', 26, 26, 3, 6, '2025-06-20 14:00:00', 1750412400),

-- Asignados a Laura (líder 27)
('Emilio', 'Marín', 'Cedula de Ciudadania', '7778889996', '1996-04-28', 29, 'Hombre', '3207778895', 'emilio.marin@email.com', 'Cualquier Hora', 'Calle 12 #8-12', 'San Antonio', 12, 27, 3, 7, '2025-06-25 15:30:00', 1750853400),
('Fátima', 'Becerra', 'Cedula de Ciudadania', '8889990007', '1994-08-12', 31, 'Mujer', '3158889996', 'fatima.becerra@email.com', 'Tarde', 'Transversal 8 #5-10', 'Villa Nueva', 27, 27, 3, 8, '2025-07-01 16:00:00', 1751392800),
('Germán', 'Forero', 'Cedula de Ciudadania', '9990001118', '1998-12-25', 27, 'Hombre', '3209990007', 'german.forero@email.com', 'Ma??ana', 'Carrera 15 #12-18', 'Las Flores', 27, 27, 3, 7, '2025-07-05 09:00:00', 1751734800),
('Helena', 'Delgado', 'Cedula de Ciudadania', '0001112229', '1993-05-08', 32, 'Mujer', '3150001118', 'helena.delgado@email.com', 'Noche', 'Diagonal 12 #10-15', 'Centro', 27, 27, 3, 9, '2025-07-10 10:30:00', 1752171000),
('Ignacio', 'Caicedo', 'Cedula de Ciudadania', '1112223341', '1997-09-20', 28, 'Hombre', '3201112229', 'ignacio.caicedo@email.com', 'Tarde', 'Avenida 5 #8-12', 'San Antonio', 27, 27, 3, 6, '2025-07-15 11:00:00', 1752606000),
('Julia', 'Trujillo', 'Cedula de Ciudadania', '2223334452', '1995-02-05', 30, 'Mujer', '3152223341', 'julia.trujillo@email.com', 'Medio Dia', 'Calle 18 #5-10', 'Villa Nueva', 27, 27, 3, 7, '2025-07-20 14:30:00', 1753047000),
('Kevin', 'Bonilla', 'Cedula de Ciudadania', '3334445563', '1999-06-18', 26, 'Joven Hombre', '3203334452', 'kevin.bonilla@email.com', 'Cualquier Hora', 'Transversal 10 #12-18', 'Las Flores', 27, 27, 3, 8, '2025-07-25 15:00:00', 1753480800),
('Lidia', 'Ospina', 'Cedula de Ciudadania', '4445556674', '1991-10-30', 34, 'Mujer', '3154445563', 'lidia.ospina@email.com', 'Tarde', 'Carrera 8 #10-15', 'Centro', 27, 27, 3, 7, '2025-08-01 16:00:00', 1754074800),
('Mario', 'Campos', 'Cedula de Ciudadania', '5556667785', '1996-03-15', 29, 'Hombre', '3205556674', 'mario.campos2@email.com', 'Ma??ana', 'Diagonal 15 #8-12', 'San Antonio', 27, 27, 3, 9, '2025-08-05 09:30:00', 1754418600),
('Nora', 'Bustos', 'Cedula de Ciudadania', '6667778896', '1998-07-28', 27, 'Mujer', '3156667785', 'nora.bustos@email.com', 'Noche', 'Avenida 12 #5-10', 'Villa Nueva', 27, 27, 3, 6, '2025-08-10 10:00:00', 1754814000),
('Óscar', 'Giraldo', 'Cedula de Ciudadania', '7778889997', '1992-12-12', 33, 'Hombre', '3207778896', 'oscar.giraldo@email.com', 'Tarde', 'Calle 5 #12-18', 'Las Flores', 27, 27, 3, 7, '2025-08-15 11:30:00', 1755254400),
('Pilar', 'Navas', 'Cedula de Ciudadania', '8889990008', '1995-04-25', 30, 'Mujer', '3158889997', 'pilar.navas@email.com', 'Medio Dia', 'Transversal 20 #10-15', 'Centro', 27, 27, 3, 8, '2025-08-20 14:00:00', 1755691200),
('Quintín', 'Betancur', 'Cedula de Ciudadania', '9990001119', '1997-08-08', 28, 'Hombre', '3209990008', 'quintin.betancur@email.com', 'Cualquier Hora', 'Carrera 10 #8-12', 'San Antonio', 27, 27, 3, 7, '2025-08-25 15:30:00', 1756132200),
('Rosa', 'Mejía', 'Cedula de Ciudadania', '0001112230', '1999-12-20', 26, 'Joven Mujer', '3150001119', 'rosa.mejia@email.com', 'Tarde', 'Diagonal 8 #5-10', 'Villa Nueva', 27, 27, 3, 9, '2025-09-01 16:00:00', 1756746000),

-- Asignados a Miguel (líder 28)
('Samuel', 'Pineda', 'Cedula de Ciudadania', '1112223342', '1993-05-05', 32, 'Hombre', '3201112230', 'samuel.pineda@email.com', 'Ma??ana', 'Avenida 15 #12-18', 'Las Flores', 12, 28, 3, 6, '2025-09-05 09:00:00', 1757088000),
('Tatiana', 'Uribe', 'Cedula de Ciudadania', '2223334453', '1996-09-18', 29, 'Mujer', '3152223342', 'tatiana.uribe@email.com', 'Noche', 'Calle 10 #10-15', 'Centro', 28, 28, 3, 7, '2025-09-10 10:30:00', 1757524200),
('Uriel', 'Fonseca', 'Cedula de Ciudadania', '3334445564', '1998-02-28', 27, 'Hombre', '3203334453', 'uriel.fonseca@email.com', 'Tarde', 'Transversal 5 #8-12', 'San Antonio', 28, 28, 3, 8, '2025-09-15 11:00:00', 1757960400),
('Victoria', 'Ayala', 'Cedula de Ciudadania', '4445556675', '1991-07-12', 34, 'Mujer', '3154445564', 'victoria.ayala@email.com', 'Medio Dia', 'Carrera 12 #5-10', 'Villa Nueva', 28, 28, 3, 7, '2025-09-20 14:30:00', 1758401400),
('Walter', 'Gamboa', 'Cedula de Ciudadania', '5556667786', '1995-11-25', 30, 'Hombre', '3205556675', 'walter.gamboa@email.com', 'Cualquier Hora', 'Diagonal 10 #12-18', 'Las Flores', 28, 28, 3, 9, '2025-09-25 15:00:00', 1758835200),
('Yolanda', 'Tovar', 'Cedula de Ciudadania', '6667778897', '1997-04-08', 28, 'Mujer', '3156667786', 'yolanda.tovar@email.com', 'Tarde', 'Avenida 8 #10-15', 'Centro', 28, 28, 3, 6, '2025-10-01 16:00:00', 1759428000),
('Zacarías', 'Hurtado', 'Cedula de Ciudadania', '7778889998', '1999-08-20', 26, 'Joven Hombre', '3207778897', 'zacarias.hurtado@email.com', 'Ma??ana', 'Calle 15 #8-12', 'San Antonio', 28, 28, 3, 7, '2025-10-05 09:30:00', 1759771800),
('Amanda', 'Valencia', 'Cedula de Ciudadania', '8889990009', '1992-12-05', 33, 'Mujer', '3158889998', 'amanda.valencia2@email.com', 'Noche', 'Transversal 12 #5-10', 'Villa Nueva', 28, 28, 3, 8, '2025-10-10 10:00:00', 1760167200),
('Bruno', 'Gutiérrez', 'Cedula de Ciudadania', '9990001120', '1996-05-18', 29, 'Hombre', '3209990009', 'bruno.gutierrez@email.com', 'Tarde', 'Carrera 5 #12-18', 'Las Flores', 28, 28, 3, 7, '2025-10-15 11:30:00', 1760608200),
('Cecilia', 'Montoya', 'Cedula de Ciudadania', '0001112231', '1994-09-30', 31, 'Mujer', '3150001120', 'cecilia.montoya@email.com', 'Medio Dia', 'Diagonal 5 #10-15', 'Centro', 28, 28, 3, 9, '2025-10-20 14:00:00', 1761043200),
('David', 'Franco', 'Cedula de Ciudadania', '1112223343', '1998-02-15', 27, 'Hombre', '3201112231', 'david.franco@email.com', 'Cualquier Hora', 'Avenida 10 #8-12', 'San Antonio', 28, 28, 3, 6, '2025-10-25 15:30:00', 1761484200),
('Emma', 'Caro', 'Cedula de Ciudadania', '2223334454', '1993-06-28', 32, 'Mujer', '3152223343', 'emma.caro@email.com', 'Tarde', 'Calle 20 #5-10', 'Villa Nueva', 28, 28, 3, 7, '2025-11-01 16:00:00', 1762078200),
('Felipe', 'Bernal', 'Cedula de Ciudadania', '3334445565', '1997-10-12', 28, 'Hombre', '3203334454', 'felipe.bernal@email.com', 'Ma??ana', 'Transversal 15 #12-18', 'Las Flores', 28, 28, 3, 8, '2025-11-05 09:00:00', 1762420200),
('Graciela', 'Solano', 'Cedula de Ciudadania', '4445556676', '1995-03-25', 30, 'Mujer', '3154445565', 'graciela.solano@email.com', 'Noche', 'Carrera 8 #10-15', 'Centro', 28, 28, 3, 7, '2025-11-10 10:30:00', 1762856400);

-- Verificar resultados
SELECT COUNT(*) AS Total_Lideres FROM persona WHERE Id_Rol = 2;
SELECT COUNT(*) AS Total_Miembros FROM persona WHERE Id_Rol = 3;
SELECT 
    CONCAT(l.Nombre, ' ', l.Apellido) AS Lider,
    COUNT(m.Id_Persona) AS Cantidad_Miembros
FROM persona l
LEFT JOIN persona m ON m.Id_Lider = l.Id_Persona
WHERE l.Id_Rol = 2
GROUP BY l.Id_Persona, l.Nombre, l.Apellido
ORDER BY l.Nombre;
