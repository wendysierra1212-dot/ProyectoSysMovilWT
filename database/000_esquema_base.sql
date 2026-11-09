-- Esquema base limpio de SYSMOVILWT. No incluye registros ni credenciales.
-- Para una base nueva, ejecutar antes que las migraciones 001 a 005.
CREATE DATABASE IF NOT EXISTS sysmovilwt_v2
  CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE sysmovilwt_v2;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `roles` (
  `id_rol` int NOT NULL AUTO_INCREMENT,
  `nombre_rol` varchar(50) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `empleados` (
  `id_empleado` int NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `cargo` varchar(50) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `estado` enum('Activo','Inactivo') DEFAULT 'Activo',
  PRIMARY KEY (`id_empleado`),
  UNIQUE KEY `cedula` (`cedula`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `clientes` (
  `id_cliente` int NOT NULL AUTO_INCREMENT,
  `cedula` varchar(20) DEFAULT NULL,
  `nombres` varchar(100) NOT NULL,
  `apellidos` varchar(100) NOT NULL,
  `telefono` varchar(20) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `fecha_registro` date DEFAULT (curdate()),
  PRIMARY KEY (`id_cliente`),
  UNIQUE KEY `cedula` (`cedula`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `categorias` (
  `id_categoria` int NOT NULL AUTO_INCREMENT,
  `nombre_categoria` varchar(100) NOT NULL,
  `descripcion` varchar(200) DEFAULT NULL,
  `estado` enum('Activa','Inactiva') DEFAULT 'Activa',
  PRIMARY KEY (`id_categoria`),
  UNIQUE KEY `nombre_categoria` (`nombre_categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `marcas` (
  `id_marca` int NOT NULL AUTO_INCREMENT,
  `nombre_marca` varchar(50) NOT NULL,
  `estado` enum('Activa','Inactiva') DEFAULT 'Activa',
  PRIMARY KEY (`id_marca`),
  UNIQUE KEY `nombre_marca` (`nombre_marca`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `proveedores` (
  `id_proveedor` int NOT NULL AUTO_INCREMENT,
  `nit` varchar(20) DEFAULT NULL,
  `nombre_proveedor` varchar(100) NOT NULL,
  `contacto` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` varchar(150) DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `estado` enum('Activo','Inactivo') DEFAULT 'Activo',
  PRIMARY KEY (`id_proveedor`),
  UNIQUE KEY `nit` (`nit`),
  UNIQUE KEY `correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `productos` (
  `id_producto` int NOT NULL AUTO_INCREMENT,
  `codigo_producto` varchar(30) NOT NULL,
  `tipo_producto` enum('Equipo','Accesorio','Repuesto') NOT NULL,
  `nombre_producto` varchar(100) NOT NULL,
  `id_marca` int DEFAULT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modelo` varchar(50) DEFAULT NULL,
  `imei` varchar(20) DEFAULT NULL,
  `descripcion` varchar(250) DEFAULT NULL,
  `precio_compra` decimal(10,2) NOT NULL,
  `precio_venta` decimal(10,2) NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `stock_minimo` int DEFAULT '5',
  `garantia_meses` int DEFAULT '0',
  `condicion` enum('Nuevo','Usado','Exhibición') DEFAULT 'Nuevo',
  `id_categoria` int NOT NULL,
  `id_proveedor` int NOT NULL,
  PRIMARY KEY (`id_producto`),
  UNIQUE KEY `codigo_producto` (`codigo_producto`),
  UNIQUE KEY `imei` (`imei`),
  UNIQUE KEY `imei_2` (`imei`),
  KEY `id_categoria` (`id_categoria`),
  KEY `id_proveedor` (`id_proveedor`),
  KEY `fk_producto_marca` (`id_marca`),
  CONSTRAINT `fk_producto_marca` FOREIGN KEY (`id_marca`) REFERENCES `marcas` (`id_marca`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id_categoria`),
  CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `compras` (
  `id_compra` int NOT NULL AUTO_INCREMENT,
  `numero_factura_compra` varchar(50) DEFAULT NULL,
  `fecha_compra` date NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `observaciones` varchar(250) DEFAULT NULL,
  `id_proveedor` int NOT NULL,
  `id_empleado` int NOT NULL,
  PRIMARY KEY (`id_compra`),
  UNIQUE KEY `numero_factura_compra` (`numero_factura_compra`),
  KEY `id_proveedor` (`id_proveedor`),
  KEY `id_empleado` (`id_empleado`),
  CONSTRAINT `compras_ibfk_1` FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores` (`id_proveedor`),
  CONSTRAINT `compras_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `ventas` (
  `id_venta` int NOT NULL AUTO_INCREMENT,
  `numero_factura` varchar(30) NOT NULL,
  `fecha_venta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_cliente` int DEFAULT NULL,
  `id_empleado` int NOT NULL,
  `metodo_pago` enum('Efectivo','Tarjeta','Transferencia') NOT NULL DEFAULT 'Efectivo',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `iva` decimal(12,2) NOT NULL DEFAULT '0.00',
  `descuento` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `estado` enum('Completada','Anulada') NOT NULL DEFAULT 'Completada',
  PRIMARY KEY (`id_venta`),
  UNIQUE KEY `uq_ventas_numero_factura` (`numero_factura`),
  KEY `fk_ventas_cliente` (`id_cliente`),
  KEY `fk_ventas_empleado` (`id_empleado`),
  CONSTRAINT `fk_ventas_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_ventas_empleado` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `detalle_compra` (
  `id_detalle_compra` int NOT NULL AUTO_INCREMENT,
  `id_compra` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_detalle_compra`),
  KEY `id_compra` (`id_compra`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `detalle_compra_ibfk_1` FOREIGN KEY (`id_compra`) REFERENCES `compras` (`id_compra`),
  CONSTRAINT `detalle_compra_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `detalle_venta` (
  `id_detalle_venta` int NOT NULL AUTO_INCREMENT,
  `id_venta` int NOT NULL,
  `id_producto` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(12,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id_detalle_venta`),
  KEY `fk_detalle_venta_venta` (`id_venta`),
  KEY `fk_detalle_venta_producto` (`id_producto`),
  CONSTRAINT `fk_detalle_venta_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id_producto`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_venta_venta` FOREIGN KEY (`id_venta`) REFERENCES `ventas` (`id_venta`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_detalle_venta_cantidad` CHECK ((`cantidad` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `servicio_tecnico` (
  `id_servicio` int NOT NULL AUTO_INCREMENT,
  `descripcion` varchar(200) DEFAULT NULL,
  `fecha_ingreso` date DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `id_cliente` int DEFAULT NULL,
  `id_empleado` int DEFAULT NULL,
  PRIMARY KEY (`id_servicio`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_empleado` (`id_empleado`),
  CONSTRAINT `servicio_tecnico_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id_cliente`),
  CONSTRAINT `servicio_tecnico_ibfk_2` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre_usuario` varchar(50) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `estado` enum('Activo','Inactivo') DEFAULT 'Activo',
  `ultimo_acceso` datetime DEFAULT NULL,
  `id_empleado` int NOT NULL,
  `id_rol` int NOT NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  KEY `id_empleado` (`id_empleado`),
  KEY `id_rol` (`id_rol`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_empleado`) REFERENCES `empleados` (`id_empleado`),
  CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
