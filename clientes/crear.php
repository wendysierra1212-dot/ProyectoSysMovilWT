<?php

require_once "../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $cedula = $_POST["cedula"];
    $nombres = $_POST["nombres"];
    $apellidos = $_POST["apellidos"];
    $telefono = $_POST["telefono"];
    $correo = $_POST["correo"];
    $direccion = $_POST["direccion"];
    $ciudad = $_POST["ciudad"];

    $sql = "INSERT INTO clientes 
            (cedula, nombres, apellidos, telefono, correo, direccion, ciudad)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sssssss",
        $cedula,
        $nombres,
        $apellidos,
        $telefono,
        $correo,
        $direccion,
        $ciudad
    );

    if ($stmt->execute()) {
        header("Location: index.php");
        exit;
    } else {
        echo "Error al registrar el cliente: " . $stmt->error;
    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Nuevo Cliente | SYSMOVILWT</title>
</head>

<body>

    <h1>Registrar nuevo cliente</h1>

    <form method="POST">

        <label>Cédula:</label><br>
        <input type="text" name="cedula"><br><br>

        <label>Nombres:</label><br>
        <input type="text" name="nombres" required><br><br>

        <label>Apellidos:</label><br>
        <input type="text" name="apellidos" required><br><br>

        <label>Teléfono:</label><br>
        <input type="text" name="telefono" required><br><br>

        <label>Correo:</label><br>
        <input type="email" name="correo"><br><br>

        <label>Dirección:</label><br>
        <input type="text" name="direccion"><br><br>

        <label>Ciudad:</label><br>
        <input type="text" name="ciudad"><br><br>

        <button type="submit">Guardar cliente</button>

    </form>

</body>

</html>
