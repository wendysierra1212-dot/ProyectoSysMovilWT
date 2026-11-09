<?php

require_once "../config/conexion.php";
require_once "../config/autenticacion.php";
require_once "../config/actividad.php";
exigirPermiso($conn, 'inventario', 'crear');
$csrfToken = tokenCsrfSysmovil();


/* ==================================================
   REGISTRAR PRODUCTO
   ================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    exigirCsrfSysmovil();

    /* Datos principales */

    $codigo_producto = trim($_POST["codigo_producto"] ?? "");
    $tipo_producto = $_POST["tipo_producto"] ?? "";
    $nombre_producto = trim($_POST["nombre_producto"] ?? "");


    /* Datos opcionales */

    $marca = !empty($_POST["marca"])
        ? trim($_POST["marca"])
        : null;

    $modelo = !empty($_POST["modelo"])
        ? trim($_POST["modelo"])
        : null;

    $imei = !empty($_POST["imei"])
        ? trim($_POST["imei"])
        : null;

    $descripcion = !empty($_POST["descripcion"])
        ? trim($_POST["descripcion"])
        : null;


    /* Información comercial */

    $precio_compra = !empty($_POST["precio_compra"])
        ? (float) $_POST["precio_compra"]
        : 0;

    $precio_venta = !empty($_POST["precio_venta"])
        ? (float) $_POST["precio_venta"]
        : 0;


    /* Inventario */

    $stock = isset($_POST["stock"])
        ? (int) $_POST["stock"]
        : 0;

    $stock_minimo = !empty($_POST["stock_minimo"])
        ? (int) $_POST["stock_minimo"]
        : 5;

    $garantia_meses = !empty($_POST["garantia_meses"])
        ? (int) $_POST["garantia_meses"]
        : 0;


    /*
     * El formulario profesional actualmente maneja
     * "Estado" visualmente, pero la base de datos
     * utiliza "condicion".
     *
     * Por defecto registraremos el producto como Nuevo.
     */

    $condicion = $_POST["condicion"] ?? "Nuevo";


    /* Relaciones */

    $id_categoria = isset($_POST["id_categoria"])
        ? (int) $_POST["id_categoria"]
        : 0;

    $id_proveedor = isset($_POST["id_proveedor"])
        ? (int) $_POST["id_proveedor"]
        : 0;


    /* ==================================================
       VALIDACIONES
       ================================================== */

    if (
        $codigo_producto === "" ||
        $tipo_producto === "" ||
        $nombre_producto === "" ||
        $precio_venta <= 0 ||
        $id_categoria <= 0 ||
        $id_proveedor <= 0
    ) {

        die("Error: faltan datos obligatorios para registrar el producto.");

    }


    /* ==================================================
       INSERTAR EN MYSQL
       ================================================== */

    $sql = "INSERT INTO productos
            (
                codigo_producto,
                tipo_producto,
                nombre_producto,
                marca,
                modelo,
                imei,
                descripcion,
                precio_compra,
                precio_venta,
                stock,
                stock_minimo,
                garantia_meses,
                condicion,
                id_categoria,
                id_proveedor
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";


    $stmt = $conn->prepare($sql);


    if (!$stmt) {

        error_log('SYSMOVILWT productos al preparar registro: ' . $conn->error);
        http_response_code(500);
        exit('No fue posible preparar el registro del producto. Inténtalo de nuevo.');

    }


    $stmt->bind_param(
        "sssssssddiiisii",
        $codigo_producto,
        $tipo_producto,
        $nombre_producto,
        $marca,
        $modelo,
        $imei,
        $descripcion,
        $precio_compra,
        $precio_venta,
        $stock,
        $stock_minimo,
        $garantia_meses,
        $condicion,
        $id_categoria,
        $id_proveedor
    );


    /* ==================================================
       EJECUTAR
       ================================================== */

    if ($stmt->execute()) {

        registrarActividadSistema($conn, "Inventario", "Registro", "Producto " . $nombre_producto . " registrado.");

        $stmt->close();

        /*
         * Volver directamente a la interfaz profesional
         * de Inventario.
         */

        header("Location: ../pages/inventario.php");
        exit;

    }


    /* ==================================================
       ERROR
       ================================================== */

    error_log('SYSMOVILWT endpoint productos/crear: ' . $stmt->error);
    http_response_code(500);
    echo 'No fue posible registrar el producto. Inténtalo de nuevo.';


    $stmt->close();

}

?>

