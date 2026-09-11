<?php

require_once "../config/conexion.php";

if (!isset($_GET["id"])) {
    die("No se recibió el ID del cliente.");
}

$id_cliente = intval($_GET["id"]);

/* Consultar el cliente */
$sql = "SELECT * FROM clientes WHERE id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    die("Cliente no encontrado.");
}

$cliente = $resultado->fetch_assoc();

$stmt->close();


/* Actualizar el cliente */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $cedula = $_POST["cedula"];
    $nombres = $_POST["nombres"];
    $apellidos = $_POST["apellidos"];
    $telefono = $_POST["telefono"];
    $correo = $_POST["correo"];
    $direccion = $_POST["direccion"];
    $ciudad = $_POST["ciudad"];

    $sql = "UPDATE clientes SET
                cedula = ?,
                nombres = ?,
                apellidos = ?,
                telefono = ?,
                correo = ?,
                direccion = ?,
                ciudad = ?
            WHERE id_cliente = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sssssssi",
        $cedula,
        $nombres,
        $apellidos,
        $telefono,
        $correo,
        $direccion,
        $ciudad,
        $id_cliente
    );

    if ($stmt->execute()) {

        header("Location: index.php");
        exit;

    } else {

        echo "Error al actualizar el cliente: " . $stmt->error;

    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Cliente | SYSMOVILWT</title>
</head>

<body>

    <h1>Editar cliente</h1>

    <form method="POST">

        <label>Cédula:</label><br>
        <input
            type="text"
            name="cedula"
            value="<?= htmlspecialchars($cliente['cedula'] ?? '') ?>">
        <br><br>

        <label>Nombres:</label><br>
        <input
            type="text"
            name="nombres"
            value="<?= htmlspecialchars($cliente['nombres']) ?>"
            required>
        <br><br>

        <label>Apellidos:</label><br>
        <input
            type="text"
            name="apellidos"
            value="<?= htmlspecialchars($cliente['apellidos']) ?>"
            required>
        <br><br>

        <label>Teléfono:</label><br>
        <input
            type="text"
            name="telefono"
            value="<?= htmlspecialchars($cliente['telefono']) ?>"
            required>
        <br><br>

        <label>Correo:</label><br>
        <input
            type="email"
            name="correo"
            value="<?= htmlspecialchars($cliente['correo'] ?? '') ?>">
        <br><br>

        <label>Dirección:</label><br>
        <input
            type="text"
            name="direccion"
            value="<?= htmlspecialchars($cliente['direccion'] ?? '') ?>">
        <br><br>

        <label>Ciudad:</label><br>
        <input
            type="text"
            name="ciudad"
            value="<?= htmlspecialchars($cliente['ciudad'] ?? '') ?>">
        <br><br>

        <button type="submit">Guardar cambios</button>

    </form>

</body>

</html>
