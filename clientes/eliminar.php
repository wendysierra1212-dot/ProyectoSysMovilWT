<?php

require_once "../config/conexion.php";

if (!isset($_GET["id"])) {
    die("No se recibió el ID del cliente.");
}

$id_cliente = intval($_GET["id"]);

$sql = "DELETE FROM clientes WHERE id_cliente = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $id_cliente);

if ($stmt->execute()) {

    header("Location: index.php");
    exit;

} else {

    echo "Error al eliminar el cliente: " . $stmt->error;

}

$stmt->close();

?>
