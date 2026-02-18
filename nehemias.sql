-- Tabla para modulo Nehemias
CREATE TABLE IF NOT EXISTS `nehemias` (
  `Id_Nehemias` int(11) NOT NULL AUTO_INCREMENT,
  `Nombres` varchar(100) NOT NULL,
  `Apellidos` varchar(100) NOT NULL,
  `Numero_Cedula` varchar(30) NOT NULL,
  `Telefono` varchar(30) NOT NULL,
  `Lider` varchar(150) NOT NULL,
  `Lider_Nehemias` varchar(150) NOT NULL,
  `Acepta` tinyint(1) NOT NULL DEFAULT 0,
  `Fecha_Registro` datetime NOT NULL,
  PRIMARY KEY (`Id_Nehemias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
