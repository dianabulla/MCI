-- =============================================
-- BASE DE DATOS: MCI Madrid Colombia
-- Sistema de Gestión Eclesiástica
-- Versión: 1.0
-- =============================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS mci CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE mci;

-- =============================================
-- TABLA: ROL
-- =============================================
CREATE TABLE IF NOT EXISTS ROL (
    Id_Rol INT PRIMARY KEY AUTO_INCREMENT,
    Nombre_Rol VARCHAR(100) NOT NULL,
    Descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA: MINISTERIO
-- =============================================
CREATE TABLE IF NOT EXISTS MINISTERIO (
    Id_Ministerio INT PRIMARY KEY AUTO_INCREMENT,
    Nombre_Ministerio VARCHAR(100) NOT NULL,
    Descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA: PERSONA
-- =============================================
CREATE TABLE IF NOT EXISTS PERSONA (
    Id_Persona INT PRIMARY KEY AUTO_INCREMENT,
    Nombre VARCHAR(100) NOT NULL,
    Apellido VARCHAR(100) NOT NULL,
    Tipo_Documento ENUM('Registro Civil', 'Cedula de Ciudadania', 'Cedula Extranjera') DEFAULT 'Cedula de Ciudadania',
    Numero_Documento VARCHAR(50),
    Fecha_Nacimiento DATE,
    Edad INT,
    Genero ENUM('Hombre', 'Mujer', 'Joven Hombre', 'Joven Mujer'),
    Telefono VARCHAR(20),
    Email VARCHAR(150),
    Hora_Llamada ENUM('Mañana', 'Medio Dia', 'Tarde', 'Noche', 'Cualquier Hora') DEFAULT 'Cualquier Hora',
    Direccion VARCHAR(255),
    Barrio VARCHAR(100),
    Peticion TEXT,
    Invitado_Por INT NULL,
    Tipo_Reunion ENUM('Domingo', 'Celula', 'Reu Jovenes', 'Reu Hombre', 'Reu Mujeres', 'Grupo Go', 'Seminario', 'Pesca', 'Semana Santa', 'Otro'),
    Id_Lider INT NULL,
    Id_Celula INT NULL,
    Id_Rol INT NULL,
    Id_Ministerio INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA: CELULA
-- =============================================
CREATE TABLE IF NOT EXISTS CELULA (
    Id_Celula INT PRIMARY KEY AUTO_INCREMENT,
    Nombre_Celula VARCHAR(100) NOT NULL,
    Direccion_Celula VARCHAR(255),
    Dia_Reunion VARCHAR(20),
    Hora_Reunion TIME,
    Id_Lider INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA: EVENTO
-- =============================================
CREATE TABLE IF NOT EXISTS EVENTO (
    Id_Evento INT PRIMARY KEY AUTO_INCREMENT,
    Nombre_Evento VARCHAR(150) NOT NULL,
    Descripcion_Evento TEXT,
    Fecha_Evento DATE NOT NULL,
    Hora_Evento TIME,
    Lugar_Evento VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA: PETICION
-- =============================================
CREATE TABLE IF NOT EXISTS PETICION (
    Id_Peticion INT PRIMARY KEY AUTO_INCREMENT,
    Id_Persona INT NOT NULL,
    Descripcion_Peticion TEXT NOT NULL,
    Fecha_Peticion DATE NOT NULL,
    Estado_Peticion ENUM('Pendiente', 'Respondida') DEFAULT 'Pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- TABLA: ASISTENCIA_CELULA
-- =============================================
CREATE TABLE IF NOT EXISTS ASISTENCIA_CELULA (
    Id_Asistencia INT PRIMARY KEY AUTO_INCREMENT,
    Id_Persona INT NOT NULL,
    Id_Celula INT NOT NULL,
    Fecha_Asistencia DATE NOT NULL,
    Asistio BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- AGREGAR CLAVES FORÁNEAS
-- =============================================

-- Persona -> Celula
ALTER TABLE PERSONA
ADD CONSTRAINT fk_persona_celula 
FOREIGN KEY (Id_Celula) REFERENCES CELULA(Id_Celula) 
ON DELETE SET NULL;

-- Persona -> Rol
ALTER TABLE PERSONA
ADD CONSTRAINT fk_persona_rol 
FOREIGN KEY (Id_Rol) REFERENCES ROL(Id_Rol) 
ON DELETE SET NULL;

-- Persona -> Ministerio
ALTER TABLE PERSONA
ADD CONSTRAINT fk_persona_ministerio 
FOREIGN KEY (Id_Ministerio) REFERENCES MINISTERIO(Id_Ministerio) 
ON DELETE SET NULL;

-- Celula -> Persona (Lider)
ALTER TABLE CELULA
ADD CONSTRAINT fk_celula_lider 
FOREIGN KEY (Id_Lider) REFERENCES PERSONA(Id_Persona) 
ON DELETE SET NULL;

-- Persona -> Persona (Invitado Por)
ALTER TABLE PERSONA
ADD CONSTRAINT fk_persona_invitado_por 
FOREIGN KEY (Invitado_Por) REFERENCES PERSONA(Id_Persona) 
ON DELETE SET NULL;

-- Persona -> Persona (Lider)
ALTER TABLE PERSONA
ADD CONSTRAINT fk_persona_lider 
FOREIGN KEY (Id_Lider) REFERENCES PERSONA(Id_Persona) 
ON DELETE SET NULL;

-- Peticion -> Persona
ALTER TABLE PETICION
ADD CONSTRAINT fk_peticion_persona 
FOREIGN KEY (Id_Persona) REFERENCES PERSONA(Id_Persona) 
ON DELETE CASCADE;

-- Asistencia -> Persona
ALTER TABLE ASISTENCIA_CELULA
ADD CONSTRAINT fk_asistencia_persona 
FOREIGN KEY (Id_Persona) REFERENCES PERSONA(Id_Persona) 
ON DELETE CASCADE;

-- Asistencia -> Celula
ALTER TABLE ASISTENCIA_CELULA
ADD CONSTRAINT fk_asistencia_celula 
FOREIGN KEY (Id_Celula) REFERENCES CELULA(Id_Celula) 
ON DELETE CASCADE;

-- =============================================
-- DATOS DE EJEMPLO (OPCIONAL)
-- =============================================

-- Roles
INSERT INTO ROL (Nombre_Rol, Descripcion) VALUES 
('Pastor', 'Pastor de la iglesia'),
('Líder', 'Líder de célula o ministerio'),
('Miembro', 'Miembro activo'),
('Visitante', 'Visitante ocasional'),
('Colaborador', 'Colaborador del ministerio');

-- Ministerios
INSERT INTO MINISTERIO (Nombre_Ministerio, Descripcion) VALUES 
('Alabanza', 'Ministerio de música y adoración'),
('Intercesión', 'Ministerio de oración'),
('Multimedia', 'Ministerio de sonido y video'),
('Ujieres', 'Ministerio de recibimiento'),
('Protocolo', 'Ministerio de protocolo y organización');

-- Células
INSERT INTO CELULA (Nombre_Celula, Direccion_Celula, Dia_Reunion, Hora_Reunion) VALUES 
('Célula Norte', 'Cra 7 # 100-50', 'Viernes', '19:00:00'),
('Célula Sur', 'Calle 1 # 15-30', 'Jueves', '19:30:00'),
('Célula Centro', 'Cra 13 # 45-20', 'Miércoles', '19:00:00');

-- Personas
INSERT INTO PERSONA (Nombre, Apellido, Tipo_Documento, Numero_Documento, Fecha_Nacimiento, Edad, Genero, Telefono, Email, Hora_Llamada, Direccion, Barrio, Peticion, Invitado_Por, Tipo_Reunion, Id_Lider, Id_Celula, Id_Rol, Id_Ministerio) VALUES 
('Juan', 'Pérez', 'Cedula de Ciudadania', '1234567890', '1985-05-15', 40, 'Hombre', '3001234567', 'juan.perez@email.com', 'Mañana', 'Calle 100 # 15-20', 'Chico Norte', 'Por mi familia', NULL, 'Domingo', NULL, 1, 1, 1),
('María', 'González', 'Cedula de Ciudadania', '9876543210', '1990-08-22', 35, 'Mujer', '3009876543', 'maria.gonzalez@email.com', 'Tarde', 'Cra 50 # 30-10', 'Usaquén', 'Por mi trabajo', 1, 'Celula', 1, 1, 2, 2),
('Carlos', 'Rodríguez', 'Cedula de Ciudadania', '5556667777', '1988-12-10', 37, 'Hombre', '3105556789', 'carlos.rodriguez@email.com', 'Noche', 'Calle 25 # 40-15', 'Suba', NULL, NULL, 'Domingo', NULL, 2, 3, 3),
('Ana', 'Martínez', 'Cedula de Ciudadania', '1112223333', '1995-03-18', 30, 'Mujer', '3201112233', 'ana.martinez@email.com', 'Cualquier Hora', 'Cra 80 # 60-25', 'Kennedy', 'Por mi salud', 2, 'Reu Jovenes', 3, 2, 3, 1),
('Pedro', 'López', 'Cedula de Ciudadania', '4445556666', '1982-07-25', 43, 'Hombre', '3154445566', 'pedro.lopez@email.com', 'Medio Dia', 'Calle 45 # 20-30', 'Engativá', NULL, 1, 'Domingo', 1, 3, 2, 4);

-- Actualizar líderes de células
UPDATE CELULA SET Id_Lider = 1 WHERE Id_Celula = 1;
UPDATE CELULA SET Id_Lider = 3 WHERE Id_Celula = 2;
UPDATE CELULA SET Id_Lider = 5 WHERE Id_Celula = 3;

-- Eventos
INSERT INTO EVENTO (Nombre_Evento, Descripcion_Evento, Fecha_Evento, Hora_Evento, Lugar_Evento) VALUES 
('Culto Dominical', 'Servicio de adoración dominical', '2025-12-14', '10:00:00', 'Templo Principal'),
('Reunión de Oración', 'Reunión semanal de intercesión', '2025-12-15', '19:00:00', 'Salón de Oración'),
('Conferencia de Jóvenes', 'Encuentro especial para jóvenes', '2025-12-20', '18:00:00', 'Auditorio');

-- Peticiones
INSERT INTO PETICION (Id_Persona, Descripcion_Peticion, Fecha_Peticion, Estado_Peticion) VALUES 
(1, 'Por sanidad de mi familia', '2025-12-10', 'Pendiente'),
(2, 'Por provisión económica', '2025-12-11', 'Pendiente'),
(3, 'Por sabiduría en decisiones importantes', '2025-12-09', 'Respondida');

-- Asistencias
INSERT INTO ASISTENCIA_CELULA (Id_Persona, Id_Celula, Fecha_Asistencia, Asistio) VALUES 
(1, 1, '2025-12-06', TRUE),
(2, 1, '2025-12-06', TRUE),
(3, 2, '2025-12-05', TRUE),
(4, 2, '2025-12-05', FALSE),
(5, 3, '2025-12-04', TRUE);

-- =============================================
-- ÍNDICES PARA MEJOR RENDIMIENTO
-- =============================================

CREATE INDEX idx_persona_apellido ON PERSONA(Apellido);
CREATE INDEX idx_persona_email ON PERSONA(Email);
CREATE INDEX idx_persona_documento ON PERSONA(Numero_Documento);
CREATE INDEX idx_celula_nombre ON CELULA(Nombre_Celula);
CREATE INDEX idx_evento_fecha ON EVENTO(Fecha_Evento);
CREATE INDEX idx_peticion_estado ON PETICION(Estado_Peticion);
CREATE INDEX idx_asistencia_fecha ON ASISTENCIA_CELULA(Fecha_Asistencia);

-- =============================================
-- FIN DEL SCRIPT
-- =============================================
