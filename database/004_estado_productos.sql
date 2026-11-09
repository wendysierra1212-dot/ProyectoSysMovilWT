-- Agrega el estado operativo del producto sin borrar registros históricos.
-- Ejecutar sobre sysmovilwt_v2 desde MySQL Workbench.
USE sysmovilwt_v2;

SET @columna_estado_existe := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'productos'
      AND COLUMN_NAME = 'estado'
);

SET @sql_estado_producto := IF(
    @columna_estado_existe = 0,
    'ALTER TABLE productos ADD COLUMN estado ENUM(''Activo'', ''Inactivo'') NOT NULL DEFAULT ''Activo'' AFTER stock_minimo',
    'SELECT ''La columna estado ya existe en productos'' AS resultado'
);

PREPARE stmt_estado_producto FROM @sql_estado_producto;
EXECUTE stmt_estado_producto;
DEALLOCATE PREPARE stmt_estado_producto;

SELECT estado, COUNT(*) AS cantidad
FROM productos
GROUP BY estado
ORDER BY estado;
