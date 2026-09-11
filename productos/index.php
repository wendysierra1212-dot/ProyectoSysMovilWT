<?php

require_once "../config/conexion.php";

$sql = "SELECT 
            p.id_producto,
            p.codigo_producto,
            p.tipo_producto,
            p.nombre_producto,
            p.marca,
            p.modelo,
            p.imei,
            p.descripcion,
            p.precio_compra,
            p.precio_venta,
            p.stock,
            p.stock_minimo,
            p.garantia_meses,
            p.condicion,
            c.nombre_categoria,
            pr.nombre_proveedor
        FROM productos p
        INNER JOIN categorias c 
            ON p.id_categoria = c.id_categoria
        INNER JOIN proveedores pr 
            ON p.id_proveedor = pr.id_proveedor
        ORDER BY p.id_producto DESC";

$resultado = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Productos | SYSMOVILWT</title>
</head>

<body>

    <h1>Productos registrados</h1>

    <a href="crear.php">Registrar nuevo producto</a>

    <br><br>

    <?php if ($resultado && $resultado->num_rows > 0): ?>

        <table border="1" cellpadding="10">

            <tr>
                <th>ID</th>
                <th>Código</th>
                <th>Tipo</th>
                <th>Producto</th>
                <th>Marca</th>
                <th>Modelo</th>
                <th>Precio venta</th>
                <th>Stock</th>
                <th>Condición</th>
                <th>Categoría</th>
                <th>Proveedor</th>
                <th>Acciones</th>
            </tr>

            <?php while ($producto = $resultado->fetch_assoc()): ?>

                <tr>

                    <td><?= $producto['id_producto'] ?></td>

                    <td><?= htmlspecialchars($producto['codigo_producto']) ?></td>

                    <td><?= htmlspecialchars($producto['tipo_producto']) ?></td>

                    <td><?= htmlspecialchars($producto['nombre_producto']) ?></td>

                    <td><?= htmlspecialchars($producto['marca'] ?? '') ?></td>

                    <td><?= htmlspecialchars($producto['modelo'] ?? '') ?></td>

                    <td>
                        $<?= number_format($producto['precio_venta'], 0, ',', '.') ?>
                    </td>

                    <td><?= $producto['stock'] ?></td>

                    <td><?= htmlspecialchars($producto['condicion'] ?? '') ?></td>

                    <td><?= htmlspecialchars($producto['nombre_categoria']) ?></td>

                    <td><?= htmlspecialchars($producto['nombre_proveedor']) ?></td>

                    <td>
                        <a href="editar.php?id=<?= $producto['id_producto'] ?>">
                            Editar
                        </a>

                        |

                        <a href="eliminar.php?id=<?= $producto['id_producto'] ?>"
                           onclick="return confirm('¿Está seguro de eliminar este producto?');">
                            Eliminar
                        </a>
                    </td>

                </tr>

            <?php endwhile; ?>

        </table>

    <?php else: ?>

        <p>No hay productos registrados.</p>

    <?php endif; ?>

</body>

</html>
