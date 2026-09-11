<?php

require_once "../config/conexion.php";


/* ==================================================
   VERIFICAR ID DEL PRODUCTO
   ================================================== */

if (!isset($_GET["id"])) {

    die("No se recibió el ID del producto.");

}

$id_producto = (int) $_GET["id"];


/* ==================================================
   CONSULTAR PRODUCTO
   ================================================== */

$sql = "SELECT * FROM productos WHERE id_producto = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die("Error al preparar la consulta: " . $conn->error);

}

$stmt->bind_param("i", $id_producto);

$stmt->execute();

$resultado = $stmt->get_result();


if ($resultado->num_rows === 0) {

    die("Producto no encontrado.");

}

$producto = $resultado->fetch_assoc();

$stmt->close();


/* ==================================================
   ACTUALIZAR PRODUCTO
   ================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    /* Datos principales */

    $codigo_producto = trim(
        $_POST["codigo_producto"] ?? ""
    );

    $tipo_producto = $_POST["tipo_producto"] ?? "";

    $nombre_producto = trim(
        $_POST["nombre_producto"] ?? ""
    );


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

    $stock_minimo = isset($_POST["stock_minimo"])
        && $_POST["stock_minimo"] !== ""
        ? (int) $_POST["stock_minimo"]
        : 5;

    $garantia_meses = isset($_POST["garantia_meses"])
        && $_POST["garantia_meses"] !== ""
        ? (int) $_POST["garantia_meses"]
        : 0;


    /*
     * Nuestro formulario profesional no envía
     * "condicion", por lo tanto conservamos
     * la condición actual del producto.
     */

    $condicion = $producto["condicion"] ?? "Nuevo";


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

        die(
            "Error: faltan datos obligatorios " .
            "para actualizar el producto."
        );

    }


    /* ==================================================
       UPDATE MYSQL
       ================================================== */

    $sql = "UPDATE productos SET

                codigo_producto = ?,
                tipo_producto = ?,
                nombre_producto = ?,
                marca = ?,
                modelo = ?,
                imei = ?,
                descripcion = ?,
                precio_compra = ?,
                precio_venta = ?,
                stock = ?,
                stock_minimo = ?,
                garantia_meses = ?,
                condicion = ?,
                id_categoria = ?,
                id_proveedor = ?

            WHERE id_producto = ?";


    $stmt = $conn->prepare($sql);


    if (!$stmt) {

        die(
            "Error al preparar la consulta: "
            . $conn->error
        );

    }


    $stmt->bind_param(

        "sssssssddiiisiii",

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
        $id_proveedor,
        $id_producto

    );


    /* ==================================================
       EJECUTAR ACTUALIZACIÓN
       ================================================== */

    if ($stmt->execute()) {

        $stmt->close();

        /*
         * Regresar a la interfaz profesional
         * de Inventario.
         */

        header(
            "Location: ../pages/inventario.php"
        );

        exit;

    }


    /* ==================================================
       ERROR
       ================================================== */

    echo "Error al actualizar el producto: "
        . htmlspecialchars($stmt->error);


    $stmt->close();

}


/* ==================================================
   CATEGORÍAS ACTIVAS
   ================================================== */

$categorias = $conn->query(

    "SELECT id_categoria, nombre_categoria
     FROM categorias
     WHERE estado = 'Activa'
     ORDER BY nombre_categoria"

);


/* ==================================================
   PROVEEDORES ACTIVOS
   ================================================== */

$proveedores = $conn->query(

    "SELECT id_proveedor, nombre_proveedor
     FROM proveedores
     WHERE estado = 'Activo'
     ORDER BY nombre_proveedor"

);

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>
        Editar Producto | SYSMOVILWT
    </title>

</head>

<body>


<h1>Editar producto</h1>


<form method="POST">


    <!-- =========================================
         CÓDIGO
         ========================================= -->

    <label>
        Código del producto:
    </label>

    <br>

    <input
        type="text"
        name="codigo_producto"
        value="<?= htmlspecialchars(
            $producto["codigo_producto"]
        ) ?>"
        required>

    <br><br>


    <!-- =========================================
         TIPO
         ========================================= -->

    <label>
        Tipo de producto:
    </label>

    <br>

    <select
        name="tipo_producto"
        required>

        <option
            value="Equipo"
            <?= $producto["tipo_producto"] === "Equipo"
                ? "selected"
                : "" ?>>
            Equipo
        </option>

        <option
            value="Accesorio"
            <?= $producto["tipo_producto"] === "Accesorio"
                ? "selected"
                : "" ?>>
            Accesorio
        </option>

        <option
            value="Repuesto"
            <?= $producto["tipo_producto"] === "Repuesto"
                ? "selected"
                : "" ?>>
            Repuesto
        </option>

    </select>

    <br><br>


    <!-- =========================================
         NOMBRE
         ========================================= -->

    <label>
        Nombre del producto:
    </label>

    <br>

    <input
        type="text"
        name="nombre_producto"
        value="<?= htmlspecialchars(
            $producto["nombre_producto"]
        ) ?>"
        required>

    <br><br>


    <!-- =========================================
         MARCA
         ========================================= -->

    <label>
        Marca:
    </label>

    <br>

    <input
        type="text"
        name="marca"
        value="<?= htmlspecialchars(
            $producto["marca"] ?? ""
        ) ?>">

    <br><br>


    <!-- =========================================
         MODELO
         ========================================= -->

    <label>
        Modelo:
    </label>

    <br>

    <input
        type="text"
        name="modelo"
        value="<?= htmlspecialchars(
            $producto["modelo"] ?? ""
        ) ?>">

    <br><br>


    <!-- =========================================
         IMEI
         ========================================= -->

    <label>
        IMEI / Serial:
    </label>

    <br>

    <input
        type="text"
        name="imei"
        value="<?= htmlspecialchars(
            $producto["imei"] ?? ""
        ) ?>">

    <br><br>


    <!-- =========================================
         DESCRIPCIÓN
         ========================================= -->

    <label>
        Descripción:
    </label>

    <br>

    <textarea
        name="descripcion"
        rows="4"><?= htmlspecialchars(
            $producto["descripcion"] ?? ""
        ) ?></textarea>

    <br><br>


    <!-- =========================================
         PRECIO COMPRA
         ========================================= -->

    <label>
        Precio de compra:
    </label>

    <br>

    <input
        type="number"
        name="precio_compra"
        step="0.01"
        min="0"
        value="<?= $producto["precio_compra"] ?>">

    <br><br>


    <!-- =========================================
         PRECIO VENTA
         ========================================= -->

    <label>
        Precio de venta:
    </label>

    <br>

    <input
        type="number"
        name="precio_venta"
        step="0.01"
        min="0"
        value="<?= $producto["precio_venta"] ?>"
        required>

    <br><br>


    <!-- =========================================
         STOCK
         ========================================= -->

    <label>
        Stock:
    </label>

    <br>

    <input
        type="number"
        name="stock"
        min="0"
        value="<?= $producto["stock"] ?>"
        required>

    <br><br>


    <!-- =========================================
         STOCK MÍNIMO
         ========================================= -->

    <label>
        Stock mínimo:
    </label>

    <br>

    <input
        type="number"
        name="stock_minimo"
        min="0"
        value="<?= $producto["stock_minimo"] ?>">

    <br><br>


    <!-- =========================================
         GARANTÍA
         ========================================= -->

    <label>
        Garantía (meses):
    </label>

    <br>

    <input
        type="number"
        name="garantia_meses"
        min="0"
        value="<?= $producto["garantia_meses"] ?>">

    <br><br>


    <!-- =========================================
         CATEGORÍA
         ========================================= -->

    <label>
        Categoría:
    </label>

    <br>

    <select
        name="id_categoria"
        required>

        <?php if ($categorias): ?>

            <?php while (
                $categoria = $categorias->fetch_assoc()
            ): ?>

                <option
                    value="<?= $categoria["id_categoria"] ?>"
                    <?= $producto["id_categoria"]
                        == $categoria["id_categoria"]
                        ? "selected"
                        : "" ?>>

                    <?= htmlspecialchars(
                        $categoria["nombre_categoria"]
                    ) ?>

                </option>

            <?php endwhile; ?>

        <?php endif; ?>

    </select>

    <br><br>


    <!-- =========================================
         PROVEEDOR
         ========================================= -->

    <label>
        Proveedor:
    </label>

    <br>

    <select
        name="id_proveedor"
        required>

        <?php if ($proveedores): ?>

            <?php while (
                $proveedor = $proveedores->fetch_assoc()
            ): ?>

                <option
                    value="<?= $proveedor["id_proveedor"] ?>"
                    <?= $producto["id_proveedor"]
                        == $proveedor["id_proveedor"]
                        ? "selected"
                        : "" ?>>

                    <?= htmlspecialchars(
                        $proveedor["nombre_proveedor"]
                    ) ?>

                </option>

            <?php endwhile; ?>

        <?php endif; ?>

    </select>

    <br><br>


    <!-- =========================================
         BOTONES
         ========================================= -->

    <button type="submit">
        Guardar cambios
    </button>


    <a href="../pages/inventario.php">
        Cancelar
    </a>


</form>


</body>

</html>
