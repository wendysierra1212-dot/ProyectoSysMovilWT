<?php

require_once "../config/conexion.php";
require_once "../config/autenticacion.php";
require_once "../config/actividad.php";
exigirPermiso($conn, 'inventario', 'eliminar');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    header('Allow: POST');
    exit('Esta acción solo acepta solicitudes POST.');
}
exigirCsrfSysmovil();

if (!isset($_POST["id"])) {
    header("Location: ../pages/inventario.php?mensaje=error_eliminar");
    exit;
}

$id_producto = (int)$_POST["id"];
if ($id_producto <= 0) {
    header("Location: ../pages/inventario.php?mensaje=error_eliminar");
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM productos WHERE id_producto = ?");
    $stmt->bind_param("i", $id_producto);
    $stmt->execute();

    if ($stmt->affected_rows < 1) {
        $stmt->close();
        header("Location: ../pages/inventario.php?mensaje=error_eliminar");
        exit;
    }

    $stmt->close();
    registrarActividadSistema($conn, "Inventario", "Eliminación", "Producto #" . $id_producto . " eliminado.");
    header("Location: ../pages/inventario.php?mensaje=producto_eliminado");
    exit;
} catch (mysqli_sql_exception $e) {
    error_log("No se pudo eliminar el producto #{$id_producto}: " . $e->getMessage());

    if ((int)$e->getCode() === 1451) {
        header("Location: ../pages/inventario.php?mensaje=producto_relacionado");
        exit;
    }

    header("Location: ../pages/inventario.php?mensaje=error_eliminar");
    exit;
}
