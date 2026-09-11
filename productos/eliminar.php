<?php

require_once "../config/conexion.php";

if (!isset($_GET["id"])) {
    die("No se recibió el ID del producto.");
}

$id_producto = intval($_GET["id"]);


$sql = "DELETE FROM productos WHERE id_producto = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conn->error);
}

$stmt->bind_param("i", $id_producto);


if ($stmt->execute()) {

    header("Location: ../pages/inventario.php");
    exit;

} else {

    echo "Error al eliminar el producto: " . $stmt->error;

}

$stmt->close();

?>
