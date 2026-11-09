-- Datos ficticios para practicar Inventario y consultar alertas de stock.
-- Ejecutar solo en una base de práctica después de las migraciones 001 a 005.
-- No agrega cuentas de usuario ni datos personales reales.
USE sysmovilwt_v2;

INSERT INTO categorias (nombre_categoria, descripcion, estado)
SELECT 'Demo - Accesorios', 'Categoría ficticia para practicar el sistema.', 'Activa'
WHERE NOT EXISTS (SELECT 1 FROM categorias WHERE nombre_categoria = 'Demo - Accesorios');

INSERT INTO marcas (nombre_marca, estado)
SELECT 'Demo WT', 'Activa'
WHERE NOT EXISTS (SELECT 1 FROM marcas WHERE nombre_marca = 'Demo WT');

INSERT INTO proveedores (nit, nombre_proveedor, telefono, correo, estado)
SELECT 'DEMO-PRV-001', 'Proveedor ficticio de demostración', '0000000000', NULL, 'Activo'
WHERE NOT EXISTS (SELECT 1 FROM proveedores WHERE nit = 'DEMO-PRV-001');

INSERT INTO clientes (cedula, nombres, apellidos, telefono, correo, direccion, ciudad)
SELECT 'DEMO-CLI-001', 'Cliente', 'Demostración', '0000000000', NULL, NULL, NULL
WHERE NOT EXISTS (SELECT 1 FROM clientes WHERE cedula = 'DEMO-CLI-001');

INSERT INTO productos (
    codigo_producto, tipo_producto, nombre_producto, id_marca, marca, modelo,
    descripcion, precio_compra, precio_venta, stock, stock_minimo, garantia_meses,
    condicion, id_categoria, id_proveedor
)
SELECT 'DEMO-001', 'Accesorio', 'Producto ficticio para pruebas', m.id_marca,
       m.nombre_marca, 'Modelo demo', 'Registro de ejemplo; no representa un producto real.',
       10000, 15000, 2, 3, 0, 'Nuevo', c.id_categoria, pr.id_proveedor
FROM marcas m CROSS JOIN categorias c CROSS JOIN proveedores pr
WHERE m.nombre_marca = 'Demo WT'
  AND c.nombre_categoria = 'Demo - Accesorios'
  AND pr.nit = 'DEMO-PRV-001'
  AND NOT EXISTS (SELECT 1 FROM productos WHERE codigo_producto = 'DEMO-001');

SELECT codigo_producto, nombre_producto, stock, stock_minimo, estado
FROM productos WHERE codigo_producto = 'DEMO-001';
