<?php

require_once "../config/conexion.php";

$sql = "SELECT * FROM clientes ORDER BY id_cliente DESC";
$resultado = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Clientes | SYSMOVILWT</title>
</head>

<body>

    <h1>Clientes registrados</h1>

    <?php if ($resultado && $resultado->num_rows > 0): ?>

        <table border="1" cellpadding="10">

            <tr>
                <th>ID</th>
                <th>Cédula</th>
                <th>Nombres</th>
                <th>Apellidos</th>
                <th>Teléfono</th>
                <th>Correo</th>
                <th>Ciudad</th>
            </tr>

            <?php while ($cliente = $resultado->fetch_assoc()): ?>

                <tr>
                    <td><?= $cliente['id_cliente'] ?></td>
                    <td><?= $cliente['cedula'] ?></td>
                    <td><?= $cliente['nombres'] ?></td>
                    <td><?= $cliente['apellidos'] ?></td>
                    <td><?= $cliente['telefono'] ?></td>
                    <td><?= $cliente['correo'] ?></td>
                    <td><?= $cliente['ciudad'] ?></td>
                </tr>

            <?php endwhile; ?>

        </table>

    <?php else: ?>

        <p>No hay clientes registrados.</p>

    <?php endif; ?>

</body>

</html>
