<?php

require_once "../config/conexion.php";

/* =========================================================
   PROCESAMIENTO DEL CRUD
   ========================================================= */

$mensaje = "";
$tipoMensaje = "";


/* =========================================================
   CREAR / EDITAR CLIENTE
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";

    $cedula = trim($_POST["cedula"] ?? "");
    $nombres = trim($_POST["nombres"] ?? "");
    $apellidos = trim($_POST["apellidos"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");
    $ciudad = trim($_POST["ciudad"] ?? "");

    /* Los campos opcionales vacíos se guardan como NULL.
       Esto evita conflictos con UNIQUE en correo. */
    $correo = ($correo === "") ? null : $correo;
    $direccion = ($direccion === "") ? null : $direccion;
    $ciudad = ($ciudad === "") ? null : $ciudad;


    /* =====================================================
       VALIDACIÓN BÁSICA
       ===================================================== */

    if (
        $cedula === "" ||
        $nombres === "" ||
        $apellidos === "" ||
        $telefono === ""
    ) {

        $mensaje = "Por favor complete los campos obligatorios.";
        $tipoMensaje = "error";

    } else {

        /* =================================================
           CREAR
           ================================================= */

        if ($accion === "crear") {

            $sqlInsertar = "INSERT INTO clientes
                            (
                                cedula,
                                nombres,
                                apellidos,
                                telefono,
                                correo,
                                direccion,
                                ciudad
                            )
                            VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sqlInsertar);

            if (!$stmt) {

                $mensaje = "Error al preparar el registro: " . $conn->error;
                $tipoMensaje = "error";

            } else {

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

                    $stmt->close();

                    header("Location: clientes.php?mensaje=creado");
                    exit;

                } else {

                    if ($stmt->errno == 1062) {
                        $mensaje = "No fue posible registrar el cliente porque la cédula o el correo ya están registrados.";
                    } else {
                        $mensaje = "No fue posible registrar el cliente: " . $stmt->error;
                    }

                    $tipoMensaje = "error";
                    $stmt->close();
                }
            }
        }


        /* =================================================
           EDITAR
           ================================================= */

        elseif ($accion === "editar") {

            $id_cliente = isset($_POST["id_cliente"])
                ? (int) $_POST["id_cliente"]
                : 0;

            if ($id_cliente <= 0) {

                $mensaje = "Identificador de cliente no válido.";
                $tipoMensaje = "error";

            } else {

                $sqlActualizar = "UPDATE clientes
                                  SET
                                      cedula = ?,
                                      nombres = ?,
                                      apellidos = ?,
                                      telefono = ?,
                                      correo = ?,
                                      direccion = ?,
                                      ciudad = ?
                                  WHERE id_cliente = ?";

                $stmt = $conn->prepare($sqlActualizar);

                if (!$stmt) {

                    $mensaje = "Error al preparar la actualización: " . $conn->error;
                    $tipoMensaje = "error";

                } else {

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

                        $stmt->close();

                        header("Location: clientes.php?mensaje=editado");
                        exit;

                    } else {

                        if ($stmt->errno == 1062) {
                            $mensaje = "No fue posible actualizar el cliente porque la cédula o el correo ya están registrados en otro cliente.";
                        } else {
                            $mensaje = "No fue posible actualizar el cliente: " . $stmt->error;
                        }

                        $tipoMensaje = "error";
                        $stmt->close();
                    }
                }
            }
        }
    }
}


/* =========================================================
   ELIMINAR CLIENTE
   ========================================================= */

if (isset($_GET["eliminar"])) {

    $idEliminar = (int) $_GET["eliminar"];

    if ($idEliminar > 0) {

        $sqlEliminar = "DELETE FROM clientes WHERE id_cliente = ?";

        $stmt = $conn->prepare($sqlEliminar);

        if ($stmt) {

            $stmt->bind_param("i", $idEliminar);

            if ($stmt->execute()) {

                $stmt->close();

                header("Location: clientes.php?mensaje=eliminado");
                exit;

            } else {

                $mensaje = "No fue posible eliminar el cliente: " . $stmt->error;
                $tipoMensaje = "error";

                $stmt->close();
            }

        } else {

            $mensaje = "No fue posible preparar la eliminación: " . $conn->error;
            $tipoMensaje = "error";
        }
    }
}


/* =========================================================
   MENSAJES
   ========================================================= */

if (isset($_GET["mensaje"])) {

    switch ($_GET["mensaje"]) {

        case "creado":
            $mensaje = "Cliente registrado correctamente.";
            $tipoMensaje = "success";
            break;

        case "editado":
            $mensaje = "Cliente actualizado correctamente.";
            $tipoMensaje = "success";
            break;

        case "eliminado":
            $mensaje = "Cliente eliminado correctamente.";
            $tipoMensaje = "success";
            break;
    }
}


/* =========================================================
   CONSULTA DE CLIENTES
   ========================================================= */

$sql = "SELECT
            id_cliente,
            cedula,
            nombres,
            apellidos,
            telefono,
            correo,
            direccion,
            ciudad,
            fecha_registro
        FROM clientes
        ORDER BY id_cliente DESC";

$resultado = $conn->query($sql);


/* =========================================================
   ESTADÍSTICAS
   ========================================================= */

$totalClientes = 0;

if ($resultado) {
    $totalClientes = $resultado->num_rows;
}


/* =========================================================
   FUNCIÓN PARA MOSTRAR DATOS
   ========================================================= */

function limpiar($dato)
{
    return htmlspecialchars(
        $dato ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Clientes | SYSMOVILWT
    </title>


    <!-- GOOGLE FONTS -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">


    <!-- CSS DEL SISTEMA -->

    <link
        rel="stylesheet"
        href="../assets/css/styles.css">

    <link
        rel="stylesheet"
        href="../assets/css/components.css">

    <link
        rel="stylesheet"
        href="../assets/css/dashboard.css">

    <link
        rel="stylesheet"
        href="../assets/css/crud.css">

    <link
        rel="stylesheet"
        href="../assets/css/clientes.css">


    <style>

        /* =================================================
           ACCIONES DE LA TABLA
           ================================================= */

        .client-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .client-actions a {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            text-decoration: none;
            color: white;
            transition: 0.2s;
        }

        .client-actions a:hover {
            transform: translateY(-2px);
        }

        .btn-view-client {
            background: #2563eb;
        }

        .btn-edit-client {
            background: #f59e0b;
        }

        .btn-delete-client {
            background: #dc2626;
        }


        /* =================================================
           MODAL
           ================================================= */

        .crud-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.65);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .crud-modal-content {
            background: #ffffff;
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        }

        .crud-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px 28px;
            border-bottom: 1px solid #e5e7eb;
        }

        .crud-modal-header h2 {
            margin: 0;
            color: #172554;
            font-size: 21px;
        }

        .crud-close {
            border: none;
            background: transparent;
            font-size: 24px;
            cursor: pointer;
            color: #64748b;
        }

        .crud-form {
            padding: 25px 28px;
        }

        .crud-section {
            margin-bottom: 25px;
        }

        .crud-section h3 {
            margin-bottom: 18px;
            color: #172554;
            font-size: 16px;
        }

        .crud-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .crud-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .crud-group label {
            font-weight: 600;
            color: #334155;
            font-size: 13px;
        }

        .crud-group input,
        .crud-group select,
        .crud-group textarea {
            border: 1px solid #d1d5db;
            border-radius: 9px;
            padding: 11px 13px;
            font-family: "Poppins", sans-serif;
            outline: none;
        }

        .crud-group input:focus,
        .crud-group select:focus,
        .crud-group textarea:focus {
            border-color: #2563eb;
        }

        .crud-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .btn-cancel,
        .btn-save {
            border: none;
            padding: 12px 22px;
            border-radius: 9px;
            cursor: pointer;
            font-family: "Poppins", sans-serif;
            font-weight: 600;
        }

        .btn-cancel {
            background: #e5e7eb;
            color: #334155;
        }

        .btn-save {
            background: #dc2626;
            color: white;
        }

        .client-detail {
            padding: 28px;
        }

        .client-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .client-detail-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 10px;
        }

        .client-detail-item span {
            display: block;
            font-size: 12px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .client-detail-item strong {
            color: #172554;
        }

        @media (max-width: 700px) {

            .crud-grid,
            .client-detail-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     SIDEBAR
     ===================================================== -->

<aside class="sidebar">

    <div class="sidebar-logo">

        <img
            src="../assets/img/logo/logowt.png"
            alt="Logo WT SERVIPHONE"
            class="logo-sidebar">

        <div class="logo-text">

            <h2>
                SYSMOVILWT
            </h2>

            <span>
                Sistema de Gestión Empresarial
            </span>

        </div>

    </div>


    <nav class="sidebar-menu">

        <ul>

            <li>
                <a href="dashboard.html">
                    <i class="fa-solid fa-house"></i>
                    <span>Dashboard</span>
                </a>
            </li>


            <li>
                <a href="inventario.php">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span>Inventario</span>
                </a>
            </li>


            <li>
                <a href="ventas.html">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span>Ventas</span>
                </a>
            </li>


            <li>
                <a href="compras.html">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <span>Compras</span>
                </a>
            </li>


            <li class="active">
                <a href="clientes.php">
                    <i class="fa-solid fa-users"></i>
                    <span>Clientes</span>
                </a>
            </li>


            <li>
                <a href="proveedores.html">
                    <i class="fa-solid fa-truck"></i>
                    <span>Proveedores</span>
                </a>
            </li>


            <li>
                <a href="usuarios.html">
                    <i class="fa-solid fa-user-shield"></i>
                    <span>Usuarios</span>
                </a>
            </li>


            <li>
                <a href="serviciotecnico.html">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                    <span>Servicio Técnico</span>
                </a>
            </li>


            <li>
                <a href="reportes.html">
                    <i class="fa-solid fa-chart-column"></i>
                    <span>Reportes</span>
                </a>
            </li>


            <li>
                <a href="configuracion.html">
                    <i class="fa-solid fa-gear"></i>
                    <span>Configuración</span>
                </a>
            </li>

        </ul>

    </nav>


    <button
        class="logout-button"
        id="btnLogout">

        <i class="fa-solid fa-right-from-bracket"></i>

        <span>
            Cerrar sesión
        </span>

    </button>

</aside>



<!-- =====================================================
     TOPBAR
     ===================================================== -->

<header class="topbar">

    <div class="topbar-left">

        <button class="menu-toggle">

            <i class="fa-solid fa-bars"></i>

        </button>


        <div class="topbar-title">

            <h1>
                Clientes
            </h1>

            <span>
                Gestión de clientes
            </span>

        </div>

    </div>


    <div class="topbar-right">

        <div class="topbar-date">

            <i class="fa-regular fa-calendar"></i>

            <span id="currentDate"></span>

        </div>


        <button class="notification-button">

            <i class="fa-regular fa-bell"></i>

            <span class="notification-dot"></span>

        </button>


        <div class="topbar-profile">

            <div class="profile-avatar">
                WT
            </div>

            <div class="profile-info">

                <strong>
                    Leonardo
                </strong>

                <span>
                    Administrador
                </span>

            </div>

        </div>

    </div>

</header>



<!-- =====================================================
     CONTENIDO
     ===================================================== -->

<main class="dashboard-main">

    <?php if ($mensaje !== ""): ?>

        <div
            class="crud-message <?= $tipoMensaje === "success" ? "success" : "error" ?>"
            style="
                margin: 20px 0;
                padding: 14px 18px;
                border-radius: 10px;
                font-weight: 600;
                background: <?= $tipoMensaje === "success" ? "#dcfce7" : "#fee2e2" ?>;
                color: <?= $tipoMensaje === "success" ? "#166534" : "#991b1b" ?>;
                border: 1px solid <?= $tipoMensaje === "success" ? "#86efac" : "#fecaca" ?>;
            "
        >
            <i class="fa-solid <?= $tipoMensaje === "success" ? "fa-circle-check" : "fa-circle-exclamation" ?>"></i>
            <?= limpiar($mensaje) ?>
        </div>

    <?php endif; ?>

    <section class="crud-container">


        <!-- CABECERA -->

        <section class="crud-header">

            <div class="crud-title">

                <h2>

                    <i class="fa-solid fa-users"></i>

                    Gestión de Clientes

                </h2>

                <p>
                    Administra la información de los clientes
                    registrados en WT SERVIPHONE.
                </p>

            </div>


            <button
                id="btnNuevoCliente"
                class="btn-new">

                <i class="fa-solid fa-user-plus"></i>

                Nuevo Cliente

            </button>

        </section>



        <!-- =================================================
             TARJETAS
             ================================================= -->

        <section class="crud-stats">


            <div class="crud-card">

                <div class="crud-card-icon bg-red">

                    <i class="fa-solid fa-users"></i>

                </div>

                <div class="crud-card-info">

                    <span>
                        Clientes Registrados
                    </span>

                    <strong>
                        <?= $totalClientes ?>
                    </strong>

                </div>

            </div>


            <div class="crud-card">

                <div class="crud-card-icon bg-orange">

                    <i class="fa-solid fa-crown"></i>

                </div>

                <div class="crud-card-info">

                    <span>
                        Clientes VIP
                    </span>

                    <strong>
                        0
                    </strong>

                </div>

            </div>


            <div class="crud-card">

                <div class="crud-card-icon bg-green">

                    <i class="fa-solid fa-user-check"></i>

                </div>

                <div class="crud-card-info">

                    <span>
                        Clientes Activos
                    </span>

                    <strong>
                        <?= $totalClientes ?>
                    </strong>

                </div>

            </div>


            <div class="crud-card">

                <div class="crud-card-icon bg-blue">

                    <i class="fa-solid fa-sack-dollar"></i>

                </div>

                <div class="crud-card-info">

                    <span>
                        Compras Registradas
                    </span>

                    <strong>
                        0
                    </strong>

                </div>

            </div>


        </section>



        <!-- =================================================
             BARRA DE HERRAMIENTAS
             ================================================= -->

        <section class="crud-toolbar">


            <div class="crud-search">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    id="searchClient"
                    placeholder="Buscar por nombre, documento, teléfono o correo...">

            </div>


            <select id="filterEstado">

                <option value="">
                    Todos los estados
                </option>

                <option value="Activo">
                    Activo
                </option>

            </select>


            <select id="filterTipoCliente">

                <option value="">
                    Todos los clientes
                </option>

                <option value="Normal">
                    Normal
                </option>

            </select>


            <button
                id="btnResetFilters"
                class="btn-reset"
                title="Limpiar filtros">

                <i class="fa-solid fa-rotate-right"></i>

            </button>


        </section>



        <!-- =================================================
             TABLA
             ================================================= -->

        <section class="crud-table-card">

            <table class="crud-table">

                <thead>

                    <tr>

                        <th>
                            Cliente
                        </th>

                        <th>
                            Documento
                        </th>

                        <th>
                            Contacto
                        </th>

                        <th>
                            Ciudad
                        </th>

                        <th>
                            Compras
                        </th>

                        <th>
                            Estado
                        </th>

                        <th>
                            Acciones
                        </th>

                    </tr>

                </thead>


                <tbody id="clientsTableBody">


                <?php if ($resultado && $resultado->num_rows > 0): ?>


                    <?php while ($cliente = $resultado->fetch_assoc()): ?>


                        <tr>

                            <!-- CLIENTE -->

                            <td>

                                <strong>
                                    <?= limpiar(
                                        $cliente["nombres"] . " " . $cliente["apellidos"]
                                    ) ?>
                                </strong>

                                <br>

                                <small>
                                    Normal
                                </small>

                            </td>


                            <!-- DOCUMENTO -->

                            <td>

                                <span>
                                    CC
                                </span>

                                <br>

                                <strong>
                                    <?= limpiar($cliente["cedula"]) ?>
                                </strong>

                            </td>


                            <!-- CONTACTO -->

                            <td>

                                <strong>
                                    <?= limpiar($cliente["telefono"]) ?>
                                </strong>

                                <?php if (!empty($cliente["correo"])): ?>

                                    <br>

                                    <small>
                                        <?= limpiar($cliente["correo"]) ?>
                                    </small>

                                <?php endif; ?>

                            </td>


                            <!-- CIUDAD -->

                            <td>

                                <?= !empty($cliente["ciudad"])
                                    ? limpiar($cliente["ciudad"])
                                    : "—"
                                ?>

                            </td>


                            <!-- COMPRAS -->

                            <td>
                                0
                            </td>


                            <!-- ESTADO -->

                            <td>

                                <span class="status-badge active">
                                    Activo
                                </span>

                            </td>


                            <!-- ACCIONES -->

                            <td>

                                <div class="client-actions">


                                    <!-- VER -->

                                    <a
                                        href="#"
                                        class="btn-view-client"
                                        title="Ver cliente"

                                        data-nombre="<?= limpiar(
                                            $cliente["nombres"] . " " . $cliente["apellidos"]
                                        ) ?>"

                                        data-cedula="<?= limpiar(
                                            $cliente["cedula"]
                                        ) ?>"

                                        data-telefono="<?= limpiar(
                                            $cliente["telefono"]
                                        ) ?>"

                                        data-correo="<?= limpiar(
                                            $cliente["correo"]
                                        ) ?>"

                                        data-direccion="<?= limpiar(
                                            $cliente["direccion"]
                                        ) ?>"

                                        data-ciudad="<?= limpiar(
                                            $cliente["ciudad"]
                                        ) ?>"

                                        data-fecha="<?= limpiar(
                                            $cliente["fecha_registro"]
                                        ) ?>">

                                        <i class="fa-solid fa-eye"></i>

                                    </a>



                                    <!-- EDITAR -->

                                    <a
                                        href="#"
                                        class="btn-edit-client"
                                        title="Editar cliente"

                                        data-id="<?= $cliente["id_cliente"] ?>"

                                        data-cedula="<?= limpiar($cliente["cedula"]) ?>"

                                        data-nombres="<?= limpiar($cliente["nombres"]) ?>"

                                        data-apellidos="<?= limpiar($cliente["apellidos"]) ?>"

                                        data-telefono="<?= limpiar($cliente["telefono"]) ?>"

                                        data-correo="<?= limpiar($cliente["correo"]) ?>"

                                        data-direccion="<?= limpiar($cliente["direccion"]) ?>"

                                        data-ciudad="<?= limpiar($cliente["ciudad"]) ?>">

                                        <i class="fa-solid fa-pen"></i>

                                    </a>



                                    <!-- ELIMINAR -->

                                    <a
                                        href="clientes.php?eliminar=<?= $cliente["id_cliente"] ?>"
                                        class="btn-delete-client"
                                        title="Eliminar cliente"

                                        onclick="return confirm('¿Está seguro de eliminar este cliente? Esta acción no se puede deshacer.');">

                                        <i class="fa-solid fa-trash"></i>

                                    </a>


                                </div>

                            </td>

                        </tr>


                    <?php endwhile; ?>


                <?php else: ?>


                    <tr class="clients-empty">

                        <td colspan="7">

                            <div class="empty-state">

                                <i class="fa-solid fa-user-group"></i>

                                <h3>
                                    No hay clientes registrados
                                </h3>

                                <p>
                                    Haz clic en
                                    <strong>
                                        Nuevo Cliente
                                    </strong>
                                    para comenzar.
                                </p>

                            </div>

                        </td>

                    </tr>


                <?php endif; ?>


                </tbody>

            </table>

        </section>


    </section>

</main>



<!-- =====================================================
     MODAL NUEVO CLIENTE
     ===================================================== -->

<div
    id="clientModal"
    class="crud-modal">


    <div class="crud-modal-content">


        <div class="crud-modal-header">

            <h2>

                <i class="fa-solid fa-user-plus"></i>

                Nuevo Cliente

            </h2>


            <button
                id="btnCloseModal"
                class="crud-close"
                type="button">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>



        <form
            id="clientForm"
            class="crud-form"
            method="POST"
            action="clientes.php">

            <input
                type="hidden"
                id="accionCliente"
                name="accion"
                value="crear">

            <input
                type="hidden"
                id="idCliente"
                name="id_cliente"
                value="">


            <!-- =================================================
                 INFORMACIÓN PERSONAL
                 ================================================= -->

            <div class="crud-section">

                <h3>
                    Información Personal
                </h3>


                <div class="crud-grid">


                    <div class="crud-group">

                        <label for="cedula">
                            Número de Documento *
                        </label>

                        <input
                            type="text"
                            id="cedula"
                            name="cedula"
                            maxlength="20"
                            required>

                    </div>


                    <div class="crud-group">

                        <label for="nombres">
                            Nombres *
                        </label>

                        <input
                            type="text"
                            id="nombres"
                            name="nombres"
                            maxlength="100"
                            required>

                    </div>


                    <div class="crud-group">

                        <label for="apellidos">
                            Apellidos *
                        </label>

                        <input
                            type="text"
                            id="apellidos"
                            name="apellidos"
                            maxlength="100"
                            required>

                    </div>


                    <div class="crud-group">

                        <label for="telefono">
                            Teléfono *
                        </label>

                        <input
                            type="text"
                            id="telefono"
                            name="telefono"
                            maxlength="20"
                            required>

                    </div>


                </div>

            </div>



            <!-- =================================================
                 INFORMACIÓN DE CONTACTO
                 ================================================= -->

            <div class="crud-section">

                <h3>
                    Información de Contacto
                </h3>


                <div class="crud-grid">


                    <div class="crud-group">

                        <label for="correo">
                            Correo Electrónico
                        </label>

                        <input
                            type="email"
                            id="correo"
                            name="correo"
                            maxlength="100">

                    </div>


                    <div class="crud-group">

                        <label for="ciudad">
                            Ciudad
                        </label>

                        <input
                            type="text"
                            id="ciudad"
                            name="ciudad"
                            maxlength="100">

                    </div>


                    <div
                        class="crud-group"
                        style="grid-column: 1 / -1;">

                        <label for="direccion">
                            Dirección
                        </label>

                        <input
                            type="text"
                            id="direccion"
                            name="direccion"
                            maxlength="150">

                    </div>


                </div>

            </div>



            <!-- BOTONES -->

            <div class="crud-actions">


                <button
                    type="button"
                    class="btn-cancel"
                    id="btnCancelarCliente">

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn-save">

                    Guardar Cliente

                </button>


            </div>


        </form>


    </div>

</div>



<!-- =====================================================
     MODAL VER CLIENTE
     ===================================================== -->

<div
    id="viewClientModal"
    class="crud-modal">


    <div class="crud-modal-content">


        <div class="crud-modal-header">

            <h2>

                <i class="fa-solid fa-user"></i>

                Información del Cliente

            </h2>


            <button
                id="btnCloseViewModal"
                class="crud-close"
                type="button">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>


        <div class="client-detail">


            <div class="client-detail-grid">


                <div class="client-detail-item">

                    <span>
                        Cliente
                    </span>

                    <strong id="viewNombre">
                        —
                    </strong>

                </div>


                <div class="client-detail-item">

                    <span>
                        Documento
                    </span>

                    <strong id="viewCedula">
                        —
                    </strong>

                </div>


                <div class="client-detail-item">

                    <span>
                        Teléfono
                    </span>

                    <strong id="viewTelefono">
                        —
                    </strong>

                </div>


                <div class="client-detail-item">

                    <span>
                        Correo
                    </span>

                    <strong id="viewCorreo">
                        —
                    </strong>

                </div>


                <div class="client-detail-item">

                    <span>
                        Dirección
                    </span>

                    <strong id="viewDireccion">
                        —
                    </strong>

                </div>


                <div class="client-detail-item">

                    <span>
                        Ciudad
                    </span>

                    <strong id="viewCiudad">
                        —
                    </strong>

                </div>


                <div class="client-detail-item">

                    <span>
                        Fecha de registro
                    </span>

                    <strong id="viewFecha">
                        —
                    </strong>

                </div>


                <div class="client-detail-item">

                    <span>
                        Estado
                    </span>

                    <strong>
                        Activo
                    </strong>

                </div>


            </div>


        </div>

    </div>

</div>



<!-- =====================================================
     JAVASCRIPT
     ===================================================== -->

<script src="../assets/js/utils.js"></script>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /* =============================================
           FECHA
           ============================================= */

        if (
            typeof cargarFecha ===
            "function"
        ) {

            cargarFecha();

        }


        /* =============================================
           CERRAR SESIÓN
           ============================================= */

        if (
            typeof inicializarCerrarSesion ===
            "function"
        ) {

            inicializarCerrarSesion();

        }


        /* =============================================
           ELEMENTOS
           ============================================= */

        const btnNuevo =
            document.getElementById(
                "btnNuevoCliente"
            );

        const modal =
            document.getElementById(
                "clientModal"
            );

        const btnCerrar =
            document.getElementById(
                "btnCloseModal"
            );

        const btnCancelar =
            document.getElementById(
                "btnCancelarCliente"
            );

        const formulario =
            document.getElementById(
                "clientForm"
            );

        const buscador =
            document.getElementById(
                "searchClient"
            );

        const filtroEstado =
            document.getElementById(
                "filterEstado"
            );

        const filtroTipo =
            document.getElementById(
                "filterTipoCliente"
            );

        const btnReset =
            document.getElementById(
                "btnResetFilters"
            );


        /* =============================================
           NUEVO CLIENTE
           ============================================= */

        if (btnNuevo) {

            btnNuevo.addEventListener(
                "click",
                function () {

                    formulario.reset();

                    document.getElementById("accionCliente").value =
                        "crear";

                    document.getElementById("idCliente").value =
                        "";

                    formulario.action =
                        "clientes.php";

                    document.querySelector(
                        "#clientModal .crud-modal-header h2"
                    ).innerHTML =
                        '<i class="fa-solid fa-user-plus"></i> Nuevo Cliente';

                    formulario.querySelector(
                        ".btn-save"
                    ).innerHTML =
                        "Guardar Cliente";

                    modal.style.display =
                        "flex";

                }
            );

        }


        /* =============================================
           CERRAR MODAL
           ============================================= */

        function cerrarModal() {

            modal.style.display =
                "none";

            formulario.reset();

            document.getElementById("accionCliente").value =
                "crear";

            document.getElementById("idCliente").value =
                "";

            formulario.action =
                "clientes.php";

            document.querySelector(
                "#clientModal .crud-modal-header h2"
            ).innerHTML =
                '<i class="fa-solid fa-user-plus"></i> Nuevo Cliente';

            formulario.querySelector(
                ".btn-save"
            ).innerHTML =
                "Guardar Cliente";

        }


        if (btnCerrar) {

            btnCerrar.addEventListener(
                "click",
                cerrarModal
            );

        }


        if (btnCancelar) {

            btnCancelar.addEventListener(
                "click",
                cerrarModal
            );

        }


        /* =============================================
           CERRAR AL HACER CLIC AFUERA
           ============================================= */

        if (modal) {

            modal.addEventListener(
                "click",
                function (e) {

                    if (
                        e.target === modal
                    ) {

                        cerrarModal();

                    }

                }
            );

        }


        /* =============================================
           EDITAR CLIENTE
           ============================================= */

        const botonesEditar =
            document.querySelectorAll(
                ".btn-edit-client"
            );

        botonesEditar.forEach(
            function (boton) {

                boton.addEventListener(
                    "click",
                    function (e) {

                        e.preventDefault();

                        const id =
                            boton.dataset.id || "";

                        const cedula =
                            boton.dataset.cedula || "";

                        const nombres =
                            boton.dataset.nombres || "";

                        const apellidos =
                            boton.dataset.apellidos || "";

                        const telefono =
                            boton.dataset.telefono || "";

                        const correo =
                            boton.dataset.correo || "";

                        const direccion =
                            boton.dataset.direccion || "";

                        const ciudad =
                            boton.dataset.ciudad || "";


                        /* =====================================
                           CARGAR DATOS EN EL FORMULARIO
                           ===================================== */

                        document.getElementById("idCliente").value =
                            id;

                        document.getElementById("cedula").value =
                            cedula;

                        document.getElementById("nombres").value =
                            nombres;

                        document.getElementById("apellidos").value =
                            apellidos;

                        document.getElementById("telefono").value =
                            telefono;

                        document.getElementById("correo").value =
                            correo;

                        document.getElementById("direccion").value =
                            direccion;

                        document.getElementById("ciudad").value =
                            ciudad;


                        /* =====================================
                           CONFIGURAR MODO EDICIÓN
                           ===================================== */

                        document.getElementById("accionCliente").value =
                            "editar";

                        formulario.action =
                            "clientes.php";


                        /* =====================================
                           CAMBIAR TÍTULO
                           ===================================== */

                        document.querySelector(
                            "#clientModal .crud-modal-header h2"
                        ).innerHTML =
                            '<i class="fa-solid fa-user-pen"></i> Editar Cliente';


                        /* =====================================
                           CAMBIAR BOTÓN
                           ===================================== */

                        formulario.querySelector(
                            ".btn-save"
                        ).innerHTML =
                            "Guardar Cambios";


                        /* =====================================
                           ABRIR MODAL
                           ===================================== */

                        modal.style.display =
                            "flex";

                    }
                );

            }
        );


        /* =============================================
           VER CLIENTE
           ============================================= */

        const botonesVer =
            document.querySelectorAll(
                ".btn-view-client"
            );

        const modalVer =
            document.getElementById(
                "viewClientModal"
            );

        const btnCerrarVer =
            document.getElementById(
                "btnCloseViewModal"
            );


        botonesVer.forEach(
            function (boton) {

                boton.addEventListener(
                    "click",
                    function (e) {

                        e.preventDefault();


                        document.getElementById(
                            "viewNombre"
                        ).textContent =
                            boton.dataset.nombre ||
                            "—";


                        document.getElementById(
                            "viewCedula"
                        ).textContent =
                            boton.dataset.cedula ||
                            "—";


                        document.getElementById(
                            "viewTelefono"
                        ).textContent =
                            boton.dataset.telefono ||
                            "—";


                        document.getElementById(
                            "viewCorreo"
                        ).textContent =
                            boton.dataset.correo ||
                            "—";


                        document.getElementById(
                            "viewDireccion"
                        ).textContent =
                            boton.dataset.direccion ||
                            "—";


                        document.getElementById(
                            "viewCiudad"
                        ).textContent =
                            boton.dataset.ciudad ||
                            "—";


                        document.getElementById(
                            "viewFecha"
                        ).textContent =
                            boton.dataset.fecha ||
                            "—";


                        modalVer.style.display =
                            "flex";

                    }
                );

            }
        );


        if (btnCerrarVer) {

            btnCerrarVer.addEventListener(
                "click",
                function () {

                    modalVer.style.display =
                        "none";

                }
            );

        }


        if (modalVer) {

            modalVer.addEventListener(
                "click",
                function (e) {

                    if (
                        e.target === modalVer
                    ) {

                        modalVer.style.display =
                            "none";

                    }

                }
            );

        }


        /* =============================================
           BUSCADOR
           ============================================= */

        function filtrarTabla() {

            const texto =
                buscador.value
                    .trim()
                    .toLowerCase();


            const filas =
                document.querySelectorAll(
                    "#clientsTableBody tr"
                );


            filas.forEach(
                function (fila) {

                    if (
                        fila.classList.contains(
                            "clients-empty"
                        )
                    ) {

                        return;

                    }


                    const contenido =
                        fila.textContent
                            .toLowerCase();


                    const coincideTexto =
                        texto === "" ||
                        contenido.includes(
                            texto
                        );


                    const coincideEstado =
                        filtroEstado.value === "" ||
                        contenido.includes(
                            filtroEstado.value
                                .toLowerCase()
                        );


                    const coincideTipo =
                        filtroTipo.value === "" ||
                        contenido.includes(
                            filtroTipo.value
                                .toLowerCase()
                        );


                    if (
                        coincideTexto &&
                        coincideEstado &&
                        coincideTipo
                    ) {

                        fila.style.display =
                            "";

                    } else {

                        fila.style.display =
                            "none";

                    }

                }
            );

        }


        if (buscador) {

            buscador.addEventListener(
                "input",
                filtrarTabla
            );

        }


        if (filtroEstado) {

            filtroEstado.addEventListener(
                "change",
                filtrarTabla
            );

        }


        if (filtroTipo) {

            filtroTipo.addEventListener(
                "change",
                filtrarTabla
            );

        }


        if (btnReset) {

            btnReset.addEventListener(
                "click",
                function () {

                    buscador.value = "";

                    filtroEstado.selectedIndex =
                        0;

                    filtroTipo.selectedIndex =
                        0;

                    filtrarTabla();

                }
            );

        }


    }
);

</script>


</body>

</html>