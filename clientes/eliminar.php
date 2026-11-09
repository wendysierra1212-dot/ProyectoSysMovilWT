<?php

require_once "../config/conexion.php";
require_once "../config/autenticacion.php";
exigirPermiso($conn, 'clientes', 'eliminar');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header('Allow: POST');
    exit('Esta acción solo acepta solicitudes POST.');
}
exigirCsrfSysmovil();

if (!isset($_POST["id"])) {
    die("No se recibió el ID del cliente.");
}

$id_cliente = intval($_POST["id"]);

$sql = "DELETE FROM clientes WHERE id_cliente = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $id_cliente);

if ($stmt->execute()) {

    header("Location: index.php");
    exit;

} else {

    error_log('SYSMOVILWT endpoint clientes/eliminar: ' . $stmt->error);
    http_response_code(500);
    echo 'No fue posible eliminar el cliente. Inténtalo de nuevo.';

}

$stmt->close();

?>
