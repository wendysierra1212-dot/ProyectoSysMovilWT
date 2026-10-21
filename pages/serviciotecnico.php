<?php

require_once "../config/conexion.php";

/* =========================================================
   VARIABLES
   ========================================================= */

$mensaje = "";
$tipoMensaje = "";


/* =========================================================
   CREAR / EDITAR SERVICIO
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";

    $idServicio = intval($_POST["id_servicio"] ?? 0);
    $idCliente = intval($_POST["id_cliente"] ?? 0);
    $idEmpleado = intval($_POST["id_empleado"] ?? 0);

    $descripcion = trim($_POST["descripcion"] ?? "");
    $estado = trim($_POST["estado"] ?? "");
    $costo = trim($_POST["costo"] ?? "");

    $fechaIngreso = $_POST["fecha_ingreso"] ?? "";
    $fechaEntrega = $_POST["fecha_entrega"] ?? "";

    if ($fechaEntrega === "") {
        $fechaEntrega = null;
    }


    /* =====================================================
       VALIDACIÓN
       ===================================================== */

    if (
        $idCliente <= 0 ||
        $idEmpleado <= 0 ||
        $descripcion === "" ||
        $estado === "" ||
        $costo === "" ||
        $fechaIngreso === ""
    ) {

        $mensaje = "Por favor complete todos los campos obligatorios.";
        $tipoMensaje = "error";

    } else {

        /* =================================================
           CREAR
           ================================================= */

        if ($accion === "crear") {

            $sql = "INSERT INTO servicio_tecnico
                    (
                        descripcion,
                        fecha_ingreso,
                        fecha_entrega,
                        estado,
                        costo,
                        id_cliente,
                        id_empleado
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {

                $mensaje = "Error al preparar el registro: " . $conn->error;
                $tipoMensaje = "error";

            } else {

                $costoNumerico = floatval($costo);

                /*
                 * s = descripcion
                 * s = fecha_ingreso
                 * s = fecha_entrega
                 * s = estado
                 * d = costo
                 * i = id_cliente
                 * i = id_empleado
                 */

                $stmt->bind_param(
                    "ssssdii",
                    $descripcion,
                    $fechaIngreso,
                    $fechaEntrega,
                    $estado,
                    $costoNumerico,
                    $idCliente,
                    $idEmpleado
                );

                if ($stmt->execute()) {

                    $stmt->close();

                    header("Location: serviciotecnico.php?mensaje=creado");
                    exit;

                } else {

                    $mensaje = "Error al registrar el servicio: " . $stmt->error;
                    $tipoMensaje = "error";

                    $stmt->close();
                }
            }


        /* =================================================
           EDITAR
           ================================================= */

        } elseif ($accion === "editar" && $idServicio > 0) {

            $sql = "UPDATE servicio_tecnico
                    SET
                        descripcion = ?,
                        fecha_ingreso = ?,
                        fecha_entrega = ?,
                        estado = ?,
                        costo = ?,
                        id_cliente = ?,
                        id_empleado = ?
                    WHERE id_servicio = ?";

            $stmt = $conn->prepare($sql);

            if (!$stmt) {

                $mensaje = "Error al preparar la actualización: " . $conn->error;
                $tipoMensaje = "error";

            } else {

                $costoNumerico = floatval($costo);

                /*
                 * s = descripcion
                 * s = fecha_ingreso
                 * s = fecha_entrega
                 * s = estado
                 * d = costo
                 * i = id_cliente
                 * i = id_empleado
                 * i = id_servicio
                 */

                $stmt->bind_param(
                    "ssssdiii",
                    $descripcion,
                    $fechaIngreso,
                    $fechaEntrega,
                    $estado,
                    $costoNumerico,
                    $idCliente,
                    $idEmpleado,
                    $idServicio
                );

                if ($stmt->execute()) {

                    $stmt->close();

                    header("Location: serviciotecnico.php?mensaje=editado");
                    exit;

                } else {

                    $mensaje = "Error al actualizar el servicio: " . $stmt->error;
                    $tipoMensaje = "error";

                    $stmt->close();
                }
            }
        }
    }
}


/* =========================================================
   CAMBIAR ESTADO
   ========================================================= */

if (isset($_GET["cambiar_estado"])) {

    $idServicio = intval($_GET["cambiar_estado"]);
    $estadoActual = $_GET["estado_actual"] ?? "";

    $estados = [
        "Recibido",
        "En Diagnóstico",
        "Pendiente Repuestos",
        "En Reparación",
        "Listo para Entrega",
        "Entregado"
    ];

    $posicion = array_search($estadoActual, $estados);

    if ($posicion === false) {

        $nuevoEstado = "Recibido";

    } elseif ($posicion >= count($estados) - 1) {

        $nuevoEstado = "Recibido";

    } else {

        $nuevoEstado = $estados[$posicion + 1];
    }


    $stmt = $conn->prepare(
        "UPDATE servicio_tecnico
         SET estado = ?
         WHERE id_servicio = ?"
    );

    if ($stmt) {

        $stmt->bind_param(
            "si",
            $nuevoEstado,
            $idServicio
        );

        $stmt->execute();
        $stmt->close();
    }

    header("Location: serviciotecnico.php?mensaje=estado");
    exit;
}


/* =========================================================
   ELIMINAR SERVICIO
   ========================================================= */

if (isset($_GET["eliminar"])) {

    $idServicio = intval($_GET["eliminar"]);

    $stmt = $conn->prepare(
        "DELETE FROM servicio_tecnico
         WHERE id_servicio = ?"
    );

    if ($stmt) {

        $stmt->bind_param(
            "i",
            $idServicio
        );

        if ($stmt->execute()) {

            $stmt->close();

            header("Location: serviciotecnico.php?mensaje=eliminado");
            exit;
        }

        $stmt->close();
    }
}


/* =========================================================
   MENSAJES
   ========================================================= */

if (isset($_GET["mensaje"])) {

    switch ($_GET["mensaje"]) {

        case "creado":

            $mensaje = "Servicio técnico registrado correctamente.";
            $tipoMensaje = "success";

            break;


        case "editado":

            $mensaje = "Servicio técnico actualizado correctamente.";
            $tipoMensaje = "success";

            break;


        case "eliminado":

            $mensaje = "Servicio técnico eliminado correctamente.";
            $tipoMensaje = "success";

            break;


        case "estado":

            $mensaje = "Estado del servicio actualizado correctamente.";
            $tipoMensaje = "success";

            break;
    }
}


/* =========================================================
   ESTADÍSTICAS
   ========================================================= */

$totalServicios = 0;
$recibidos = 0;
$diagnostico = 0;
$reparacion = 0;
$listos = 0;
$entregados = 0;
$facturacion = 0;


/* TOTAL */

$resultado = $conn->query(
    "SELECT COUNT(*) AS total
     FROM servicio_tecnico"
);

if ($resultado) {

    $fila = $resultado->fetch_assoc();

    $totalServicios = intval(
        $fila["total"]
    );
}


/* ESTADOS */

$sqlEstados = "
    SELECT

        SUM(estado = 'Recibido') AS recibidos,

        SUM(estado = 'En Diagnóstico') AS diagnostico,

        SUM(estado = 'En Reparación') AS reparacion,

        SUM(estado = 'Listo para Entrega') AS listos,

        SUM(estado = 'Entregado') AS entregados

    FROM servicio_tecnico
";

$resultado = $conn->query($sqlEstados);

if ($resultado) {

    $fila = $resultado->fetch_assoc();

    $recibidos = intval(
        $fila["recibidos"] ?? 0
    );

    $diagnostico = intval(
        $fila["diagnostico"] ?? 0
    );

    $reparacion = intval(
        $fila["reparacion"] ?? 0
    );

    $listos = intval(
        $fila["listos"] ?? 0
    );

    $entregados = intval(
        $fila["entregados"] ?? 0
    );
}


/* FACTURACIÓN */

$resultado = $conn->query(
    "SELECT COALESCE(SUM(costo), 0) AS total
     FROM servicio_tecnico"
);

if ($resultado) {

    $fila = $resultado->fetch_assoc();

    $facturacion = floatval(
        $fila["total"]
    );
}


/* =========================================================
   CLIENTES
   ========================================================= */

$clientes = [];

$resultadoClientes = $conn->query(
    "SELECT
        id_cliente,
        nombres,
        apellidos,
        cedula
     FROM clientes
     ORDER BY nombres ASC, apellidos ASC"
);

if ($resultadoClientes) {

    while (
        $cliente = $resultadoClientes->fetch_assoc()
    ) {

        $clientes[] = $cliente;
    }
}


/* =========================================================
   EMPLEADOS / TÉCNICOS ACTIVOS
   ========================================================= */

$empleados = [];

$resultadoEmpleados = $conn->query(
    "SELECT
        id_empleado,
        nombres,
        apellidos,
        cargo
     FROM empleados
     WHERE estado = 'Activo'
     ORDER BY nombres ASC, apellidos ASC"
);

if ($resultadoEmpleados) {

    while (
        $empleado = $resultadoEmpleados->fetch_assoc()
    ) {

        $empleados[] = $empleado;
    }
}


/* =========================================================
   FILTROS
   ========================================================= */

$buscar = trim(
    $_GET["buscar"] ?? ""
);

$filtroEstado = trim(
    $_GET["estado"] ?? ""
);

$filtroTecnico = intval(
    $_GET["tecnico"] ?? 0
);


/* =========================================================
   CONSULTA DE SERVICIOS
   ========================================================= */

$sqlServicios = "
    SELECT

        st.id_servicio,

        st.descripcion,

        st.fecha_ingreso,

        st.fecha_entrega,

        st.estado,

        st.costo,

        c.id_cliente,

        c.nombres AS cliente_nombres,

        c.apellidos AS cliente_apellidos,

        c.cedula,

        e.id_empleado,

        e.nombres AS tecnico_nombres,

        e.apellidos AS tecnico_apellidos,

        e.cargo

    FROM servicio_tecnico st

    INNER JOIN clientes c
        ON st.id_cliente = c.id_cliente

    INNER JOIN empleados e
        ON st.id_empleado = e.id_empleado

    WHERE 1=1
";


$params = [];
$types = "";


/* BUSCAR */

if ($buscar !== "") {

    $sqlServicios .= "
        AND (
            CAST(st.id_servicio AS CHAR) LIKE ?
            OR st.descripcion LIKE ?
            OR c.nombres LIKE ?
            OR c.apellidos LIKE ?
            OR c.cedula LIKE ?
            OR e.nombres LIKE ?
            OR e.apellidos LIKE ?
        )
    ";

    $buscarLike = "%" . $buscar . "%";

    $params[] = $buscarLike;
    $params[] = $buscarLike;
    $params[] = $buscarLike;
    $params[] = $buscarLike;
    $params[] = $buscarLike;
    $params[] = $buscarLike;
    $params[] = $buscarLike;

    $types .= "sssssss";
}


/* FILTRO ESTADO */

if ($filtroEstado !== "") {

    $sqlServicios .= "
        AND st.estado = ?
    ";

    $params[] = $filtroEstado;

    $types .= "s";
}


/* FILTRO TÉCNICO */

if ($filtroTecnico > 0) {

    $sqlServicios .= "
        AND st.id_empleado = ?
    ";

    $params[] = $filtroTecnico;

    $types .= "i";
}


$sqlServicios .= "
    ORDER BY st.id_servicio DESC
";


$stmtServicios = $conn->prepare(
    $sqlServicios
);

$servicios = [];

if ($stmtServicios) {

    if (!empty($params)) {

        $stmtServicios->bind_param(
            $types,
            ...$params
        );
    }

    $stmtServicios->execute();

    $resultadoServicios =
        $stmtServicios->get_result();

    while (
        $servicio =
            $resultadoServicios->fetch_assoc()
    ) {

        $servicios[] = $servicio;
    }

    $stmtServicios->close();
}


/* =========================================================
   FUNCIONES
   ========================================================= */

function limpiar($valor)
{
    return htmlspecialchars(
        (string)$valor,
        ENT_QUOTES,
        "UTF-8"
    );
}


function moneda($valor)
{
    return "$" . number_format(
        floatval($valor),
        0,
        ",",
        "."
    );
}


function claseEstado($estado)
{
    switch ($estado) {

        case "Recibido":
            return "estado-recibido";

        case "En Diagnóstico":
            return "estado-diagnostico";

        case "Pendiente Repuestos":
            return "estado-pendiente";

        case "En Reparación":
            return "estado-reparacion";

        case "Listo para Entrega":
            return "estado-listo";

        case "Entregado":
            return "estado-entregado";

        default:
            return "";
    }
}

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Servicio Técnico | SYSMOVILWT
    </title>


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >


    <!-- CSS DEL SISTEMA -->

    <link
        rel="stylesheet"
        href="../assets/css/styles.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/components.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/crud.css"
    >

    <link
        rel="stylesheet"
        href="../assets/css/serviciotecnico.css"
    >


    <style>

        /* =====================================================
           TABLA
           ===================================================== */

        .service-table-wrapper {

            width: 100%;

            overflow-x: auto;
        }


        .service-table {

            min-width: 950px;
        }


        /* =====================================================
           ACCIONES
           ===================================================== */

        .service-actions {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 5px;

            white-space: nowrap;
        }


        .service-actions a,
        .service-actions button {

            width: 30px;

            height: 30px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 6px;

            border: none;

            cursor: pointer;

            text-decoration: none;

            transition: 0.2s;
        }


        .service-actions a:hover,
        .service-actions button:hover {

            transform: translateY(-1px);
        }


        .btn-view-service {

            background: #e0f2fe;

            color: #0369a1;
        }


        .btn-edit-service {

            background: #fef3c7;

            color: #92400e;
        }


        .btn-state-service {

            background: #dcfce7;

            color: #166534;
        }


        .btn-delete-service {

            background: #fee2e2;

            color: #b91c1c;
        }


        /* =====================================================
           ESTADOS
           ===================================================== */

        .service-status {

            display: inline-flex;

            padding: 5px 9px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 600;

            white-space: nowrap;
        }


        .estado-recibido {

            background: #dbeafe;

            color: #1d4ed8;
        }


        .estado-diagnostico {

            background: #fef3c7;

            color: #92400e;
        }


        .estado-pendiente {

            background: #ffedd5;

            color: #c2410c;
        }


        .estado-reparacion {

            background: #ede9fe;

            color: #6d28d9;
        }


        .estado-listo {

            background: #dcfce7;

            color: #15803d;
        }


        .estado-entregado {

            background: #e2e8f0;

            color: #334155;
        }


        /* =====================================================
           ALERTAS
           ===================================================== */

        .crud-alert {

            margin: 15px 0;

            padding: 12px 15px;

            border-radius: 8px;

            font-size: 14px;
        }


        .crud-alert.success {

            background: #dcfce7;

            color: #166534;

            border: 1px solid #bbf7d0;
        }


        .crud-alert.error {

            background: #fee2e2;

            color: #991b1b;

            border: 1px solid #fecaca;
        }


        /* =====================================================
           MODAL
           ===================================================== */

        .service-modal {

            display: none;

            position: fixed;

            inset: 0;

            background: rgba(15, 23, 42, 0.65);

            align-items: center;

            justify-content: center;

            z-index: 9999;

            padding: 20px;
        }


        .service-modal-content {

            background: white;

            width: 100%;

            max-width: 850px;

            max-height: 90vh;

            overflow-y: auto;

            border-radius: 14px;

            box-shadow:
                0 20px 50px
                rgba(0, 0, 0, .25);
        }


        .service-modal-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 20px 24px;

            border-bottom:
                1px solid #e5e7eb;
        }


        .service-modal-header h2 {

            margin: 0;

            color: #172554;

            font-size: 20px;
        }


        .service-modal-close {

            border: none;

            background: transparent;

            font-size: 22px;

            cursor: pointer;

            color: #64748b;
        }


        .service-form {

            padding: 24px;
        }


        .service-form-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 18px;
        }


        .service-form-group {

            display: flex;

            flex-direction: column;

            gap: 7px;
        }


        .service-form-group.full {

            grid-column: 1 / -1;
        }


        .service-form-group label {

            font-size: 13px;

            font-weight: 600;

            color: #334155;
        }


        .service-form-group input,
        .service-form-group select,
        .service-form-group textarea {

            width: 100%;

            border:
                1px solid #cbd5e1;

            border-radius: 7px;

            padding: 10px 12px;

            font-family: inherit;

            box-sizing: border-box;

            outline: none;
        }


        .service-form-group textarea {

            min-height: 100px;

            resize: vertical;
        }


        .service-form-group input:focus,
        .service-form-group select:focus,
        .service-form-group textarea:focus {

            border-color: #dc2626;

            box-shadow:
                0 0 0 2px
                rgba(220, 38, 38, .08);
        }


        .service-modal-footer {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            padding-top: 20px;
        }


        .btn-cancel-service {

            border:
                1px solid #cbd5e1;

            background: #f8fafc;

            color: #334155;

            padding: 10px 18px;

            border-radius: 7px;

            cursor: pointer;
        }


        .btn-save-service {

            border: none;

            background: #dc2626;

            color: white;

            padding: 10px 18px;

            border-radius: 7px;

            cursor: pointer;

            font-weight: 600;
        }


        /* =====================================================
           MODAL DETALLE
           ===================================================== */

        .service-detail-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 18px;

            padding: 24px;
        }


        .service-detail-item {

            background: #f8fafc;

            padding: 14px;

            border-radius: 8px;
        }


        .service-detail-item.full {

            grid-column: 1 / -1;
        }


        .service-detail-item span {

            display: block;

            font-size: 12px;

            color: #64748b;

            margin-bottom: 5px;
        }


        .service-detail-item strong {

            color: #172554;
        }


        @media (max-width: 700px) {

            .service-form-grid,
            .service-detail-grid {

                grid-template-columns: 1fr;
            }


            .service-form-group.full,
            .service-detail-item.full {

                grid-column: auto;
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
            class="logo-sidebar"
        >


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

                    <span>
                        Dashboard
                    </span>

                </a>

            </li>


            <li>

                <a href="inventario.php">

                    <i class="fa-solid fa-boxes-stacked"></i>

                    <span>
                        Inventario
                    </span>

                </a>

            </li>


            <li>

                <a href="ventas.html">

                    <i class="fa-solid fa-cart-shopping"></i>

                    <span>
                        Ventas
                    </span>

                </a>

            </li>


            <li>

                <a href="compras.html">

                    <i class="fa-solid fa-bag-shopping"></i>

                    <span>
                        Compras
                    </span>

                </a>

            </li>


            <li>

                <a href="clientes.php">

                    <i class="fa-solid fa-users"></i>

                    <span>
                        Clientes
                    </span>

                </a>

            </li>


            <li>

                <a href="proveedores.php">

                    <i class="fa-solid fa-truck"></i>

                    <span>
                        Proveedores
                    </span>

                </a>

            </li>


            <li>

                <a href="usuarios.html">

                    <i class="fa-solid fa-user-shield"></i>

                    <span>
                        Usuarios
                    </span>

                </a>

            </li>


            <li class="active">

                <a href="serviciotecnico.php">

                    <i class="fa-solid fa-screwdriver-wrench"></i>

                    <span>
                        Servicio Técnico
                    </span>

                </a>

            </li>


            <li>

                <a href="reportes.html">

                    <i class="fa-solid fa-chart-column"></i>

                    <span>
                        Reportes
                    </span>

                </a>

            </li>


            <li>

                <a href="configuracion.html">

                    <i class="fa-solid fa-gear"></i>

                    <span>
                        Configuración
                    </span>

                </a>

            </li>


        </ul>

    </nav>


    <button
        class="logout-button"
        id="btnLogout"
        type="button"
    >

        <i class="fa-solid fa-right-from-bracket"></i>

        <span>
            Cerrar sesión
        </span>

    </button>


</aside>



<!-- =====================================================
     CONTENIDO
     ===================================================== -->

<div class="main-content">


    <!-- TOPBAR -->

    <header class="topbar">


        <div class="topbar-left">

            <h1>
                Servicio Técnico
            </h1>

            <span>
                Gestión integral de órdenes de servicio
            </span>

        </div>


        <div class="topbar-right">


            <div class="topbar-date">

                <i class="fa-regular fa-calendar"></i>

                <span id="currentDate"></span>

            </div>


            <button
                class="notification-button"
                type="button"
            >

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
         CONTENIDO PRINCIPAL
         ===================================================== -->

    <main class="dashboard-main">


        <section class="crud-container">


            <!-- CABECERA -->

            <section class="crud-header">


                <div class="crud-title">

                    <h2>

                        <i
                            class="fa-solid fa-screwdriver-wrench"
                        ></i>

                        Gestión de Servicio Técnico

                    </h2>


                    <p>

                        Administra las órdenes de servicio
                        técnico de WT SERVIPHONE.

                    </p>

                </div>


                <button
                    type="button"
                    id="btnNuevoServicio"
                    class="btn-new"
                >

                    <i class="fa-solid fa-plus"></i>

                    Nuevo Servicio

                </button>


            </section>



            <!-- =================================================
                 ESTADÍSTICAS
                 ================================================= -->

            <section class="crud-stats">


                <article class="crud-card">

                    <div class="crud-card-icon bg-red">

                        <i
                            class="fa-solid fa-screwdriver-wrench"
                        ></i>

                    </div>


                    <div class="crud-card-info">

                        <span>
                            Servicios Registrados
                        </span>

                        <strong>
                            <?= $totalServicios ?>
                        </strong>

                    </div>

                </article>



                <article class="crud-card">

                    <div class="crud-card-icon bg-blue">

                        <i class="fa-solid fa-inbox"></i>

                    </div>


                    <div class="crud-card-info">

                        <span>
                            Equipos Recibidos
                        </span>

                        <strong>
                            <?= $recibidos ?>
                        </strong>

                    </div>

                </article>



                <article class="crud-card">

                    <div class="crud-card-icon bg-orange">

                        <i
                            class="fa-solid fa-magnifying-glass"
                        ></i>

                    </div>


                    <div class="crud-card-info">

                        <span>
                            En Diagnóstico
                        </span>

                        <strong>
                            <?= $diagnostico ?>
                        </strong>

                    </div>

                </article>



                <article class="crud-card">

                    <div class="crud-card-icon bg-red">

                        <i
                            class="fa-solid fa-screwdriver"
                        ></i>

                    </div>


                    <div class="crud-card-info">

                        <span>
                            En Reparación
                        </span>

                        <strong>
                            <?= $reparacion ?>
                        </strong>

                    </div>

                </article>



                <article class="crud-card">

                    <div class="crud-card-icon bg-green">

                        <i
                            class="fa-solid fa-circle-check"
                        ></i>

                    </div>


                    <div class="crud-card-info">

                        <span>
                            Listos para Entrega
                        </span>

                        <strong>
                            <?= $listos ?>
                        </strong>

                    </div>

                </article>



                <article class="crud-card">

                    <div class="crud-card-icon bg-blue">

                        <i
                            class="fa-solid fa-sack-dollar"
                        ></i>

                    </div>


                    <div class="crud-card-info">

                        <span>
                            Facturación
                        </span>

                        <strong>
                            <?= moneda($facturacion) ?>
                        </strong>

                    </div>

                </article>


            </section>



            <!-- =================================================
                 MENSAJE
                 ================================================= -->

            <?php if ($mensaje !== ""): ?>

                <div
                    class="crud-alert <?= limpiar($tipoMensaje) ?>"
                >

                    <i
                        class="fa-solid fa-circle-info"
                    ></i>

                    <?= limpiar($mensaje) ?>

                </div>

            <?php endif; ?>



            <!-- =================================================
                 FILTROS
                 ================================================= -->

            <form
                method="GET"
                class="crud-toolbar"
            >


                <div class="crud-search">

                    <i
                        class="fa-solid fa-magnifying-glass"
                    ></i>


                    <input
                        type="text"
                        name="buscar"
                        value="<?= limpiar($buscar) ?>"
                        placeholder="Buscar por orden, cliente, técnico o descripción..."
                    >

                </div>


                <select name="estado">

                    <option value="">
                        Todos los estados
                    </option>


                    <option
                        value="Recibido"
                        <?= $filtroEstado === "Recibido" ? "selected" : "" ?>
                    >
                        Recibido
                    </option>


                    <option
                        value="En Diagnóstico"
                        <?= $filtroEstado === "En Diagnóstico" ? "selected" : "" ?>
                    >
                        En Diagnóstico
                    </option>


                    <option
                        value="Pendiente Repuestos"
                        <?= $filtroEstado === "Pendiente Repuestos" ? "selected" : "" ?>
                    >
                        Pendiente Repuestos
                    </option>


                    <option
                        value="En Reparación"
                        <?= $filtroEstado === "En Reparación" ? "selected" : "" ?>
                    >
                        En Reparación
                    </option>


                    <option
                        value="Listo para Entrega"
                        <?= $filtroEstado === "Listo para Entrega" ? "selected" : "" ?>
                    >
                        Listo para Entrega
                    </option>


                    <option
                        value="Entregado"
                        <?= $filtroEstado === "Entregado" ? "selected" : "" ?>
                    >
                        Entregado
                    </option>

                </select>


                <select name="tecnico">

                    <option value="">
                        Todos los técnicos
                    </option>


                    <?php foreach ($empleados as $empleado): ?>

                        <option
                            value="<?= $empleado["id_empleado"] ?>"
                            <?= $filtroTecnico == $empleado["id_empleado"] ? "selected" : "" ?>
                        >

                            <?= limpiar(
                                $empleado["nombres"]
                                . " "
                                . $empleado["apellidos"]
                            ) ?>

                        </option>

                    <?php endforeach; ?>

                </select>


                <button
                    type="submit"
                    class="btn-new"
                    style="padding: 9px 14px;"
                >

                    <i class="fa-solid fa-filter"></i>

                    Filtrar

                </button>


                <a
                    href="serviciotecnico.php"
                    class="btn-reset"
                    title="Limpiar filtros"
                >

                    <i
                        class="fa-solid fa-rotate-right"
                    ></i>

                </a>


            </form>



            <!-- =================================================
                 TABLA
                 ================================================= -->

            <section class="crud-table-card">


                <div class="service-table-wrapper">


                    <table class="crud-table service-table">


                        <thead>

                            <tr>

                                <th>
                                    Orden
                                </th>

                                <th>
                                    Fecha
                                </th>

                                <th>
                                    Cliente
                                </th>

                                <th>
                                    Técnico
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th>
                                    Entrega
                                </th>

                                <th>
                                    Total
                                </th>

                                <th>
                                    Acciones
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php if (!empty($servicios)): ?>


                            <?php foreach ($servicios as $servicio): ?>


                                <tr>


                                    <td>

                                        <strong>
                                            #<?= $servicio["id_servicio"] ?>
                                        </strong>

                                    </td>


                                    <td>

                                        <?= date(
                                            "d/m/Y",
                                            strtotime(
                                                $servicio["fecha_ingreso"]
                                            )
                                        ) ?>

                                    </td>


                                    <td>

                                        <strong>

                                            <?= limpiar(
                                                $servicio["cliente_nombres"]
                                                . " "
                                                . $servicio["cliente_apellidos"]
                                            ) ?>

                                        </strong>

                                        <br>

                                        <small>

                                            CC:
                                            <?= limpiar(
                                                $servicio["cedula"]
                                            ) ?>

                                        </small>

                                    </td>


                                    <td>

                                        <?= limpiar(
                                            $servicio["tecnico_nombres"]
                                            . " "
                                            . $servicio["tecnico_apellidos"]
                                        ) ?>

                                        <br>

                                        <small>

                                            <?= limpiar(
                                                $servicio["cargo"]
                                            ) ?>

                                        </small>

                                    </td>


                                    <td>

                                        <span
                                            class="service-status <?= claseEstado(
                                                $servicio["estado"]
                                            ) ?>"
                                        >

                                            <?= limpiar(
                                                $servicio["estado"]
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php if (
                                            !empty(
                                                $servicio["fecha_entrega"]
                                            )
                                        ): ?>

                                            <?= date(
                                                "d/m/Y",
                                                strtotime(
                                                    $servicio["fecha_entrega"]
                                                )
                                            ) ?>

                                        <?php else: ?>

                                            <span
                                                style="color:#94a3b8;"
                                            >
                                                Pendiente
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <strong>
                                            <?= moneda(
                                                $servicio["costo"]
                                            ) ?>
                                        </strong>

                                    </td>


                                    <td>


                                        <div class="service-actions">


                                            <!-- VER -->

                                            <button
                                                type="button"
                                                class="btn-view-service btnVerServicio"
                                                title="Ver servicio"

                                                data-id="<?= $servicio["id_servicio"] ?>"

                                                data-cliente="<?= limpiar(
                                                    $servicio["cliente_nombres"]
                                                    . " "
                                                    . $servicio["cliente_apellidos"]
                                                ) ?>"

                                                data-cedula="<?= limpiar(
                                                    $servicio["cedula"]
                                                ) ?>"

                                                data-tecnico="<?= limpiar(
                                                    $servicio["tecnico_nombres"]
                                                    . " "
                                                    . $servicio["tecnico_apellidos"]
                                                ) ?>"

                                                data-cargo="<?= limpiar(
                                                    $servicio["cargo"]
                                                ) ?>"

                                                data-descripcion="<?= limpiar(
                                                    $servicio["descripcion"]
                                                ) ?>"

                                                data-estado="<?= limpiar(
                                                    $servicio["estado"]
                                                ) ?>"

                                                data-costo="<?= moneda(
                                                    $servicio["costo"]
                                                ) ?>"

                                                data-ingreso="<?= date(
                                                    "d/m/Y",
                                                    strtotime(
                                                        $servicio["fecha_ingreso"]
                                                    )
                                                ) ?>"

                                                data-entrega="<?= !empty(
                                                    $servicio["fecha_entrega"]
                                                )
                                                    ? date(
                                                        "d/m/Y",
                                                        strtotime(
                                                            $servicio["fecha_entrega"]
                                                        )
                                                    )
                                                    : "Pendiente"
                                                ?>"
                                            >

                                                <i
                                                    class="fa-solid fa-eye"
                                                ></i>

                                            </button>



                                            <!-- EDITAR -->

                                            <button
                                                type="button"
                                                class="btn-edit-service btnEditarServicio"
                                                title="Editar"

                                                data-id="<?= $servicio["id_servicio"] ?>"

                                                data-cliente="<?= $servicio["id_cliente"] ?>"

                                                data-empleado="<?= $servicio["id_empleado"] ?>"

                                                data-descripcion="<?= limpiar(
                                                    $servicio["descripcion"]
                                                ) ?>"

                                                data-estado="<?= limpiar(
                                                    $servicio["estado"]
                                                ) ?>"

                                                data-costo="<?= $servicio["costo"] ?>"

                                                data-ingreso="<?= $servicio["fecha_ingreso"] ?>"

                                                data-entrega="<?= $servicio["fecha_entrega"] ?>"
                                            >

                                                <i
                                                    class="fa-solid fa-pen"
                                                ></i>

                                            </button>



                                            <!-- ESTADO -->

                                            <a
                                                href="serviciotecnico.php?cambiar_estado=<?= $servicio["id_servicio"] ?>&estado_actual=<?= urlencode($servicio["estado"]) ?>"
                                                class="btn-state-service"
                                                title="Cambiar estado"
                                            >

                                                <i
                                                    class="fa-solid fa-arrow-right"
                                                ></i>

                                            </a>



                                            <!-- ELIMINAR -->

                                            <a
                                                href="serviciotecnico.php?eliminar=<?= $servicio["id_servicio"] ?>"
                                                class="btn-delete-service"
                                                title="Eliminar"

                                                onclick="return confirm('¿Está seguro de eliminar esta orden de servicio? Esta acción no se puede deshacer.');"
                                            >

                                                <i
                                                    class="fa-solid fa-trash"
                                                ></i>

                                            </a>


                                        </div>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <tr>

                                <td
                                    colspan="8"
                                    style="
                                        text-align:center;
                                        padding:40px;
                                    "
                                >

                                    <div class="empty-state">

                                        <i
                                            class="fa-solid fa-screwdriver-wrench"
                                        ></i>

                                        <h3>
                                            No hay servicios registrados
                                        </h3>

                                        <p>

                                            Haz clic en

                                            <strong>
                                                Nuevo Servicio
                                            </strong>

                                            para comenzar.

                                        </p>

                                    </div>

                                </td>

                            </tr>


                        <?php endif; ?>


                        </tbody>

                    </table>


                </div>


            </section>


        </section>


    </main>


</div>



<!-- =====================================================
     MODAL CREAR / EDITAR
     ===================================================== -->

<div
    id="modalServicio"
    class="service-modal"
>


    <div class="service-modal-content">


        <div class="service-modal-header">


            <h2 id="tituloModalServicio">

                <i
                    class="fa-solid fa-screwdriver-wrench"
                ></i>

                Nuevo Servicio

            </h2>


            <button
                type="button"
                id="btnCerrarServicio"
                class="service-modal-close"
            >

                <i
                    class="fa-solid fa-xmark"
                ></i>

            </button>


        </div>



        <form
            method="POST"
            action="serviciotecnico.php"
            class="service-form"
            id="formServicio"
        >


            <input
                type="hidden"
                name="accion"
                id="accionServicio"
                value="crear"
            >


            <input
                type="hidden"
                name="id_servicio"
                id="idServicio"
                value=""
            >


            <div class="service-form-grid">


                <!-- CLIENTE -->

                <div class="service-form-group">

                    <label for="id_cliente">
                        Cliente *
                    </label>


                    <select
                        name="id_cliente"
                        id="id_cliente"
                        required
                    >

                        <option value="">
                            Seleccione un cliente
                        </option>


                        <?php foreach (
                            $clientes
                            as $cliente
                        ): ?>

                            <option
                                value="<?= $cliente["id_cliente"] ?>"
                            >

                                <?= limpiar(
                                    $cliente["nombres"]
                                    . " "
                                    . $cliente["apellidos"]
                                ) ?>

                                -
                                <?= limpiar(
                                    $cliente["cedula"]
                                ) ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </div>



                <!-- TÉCNICO -->

                <div class="service-form-group">

                    <label for="id_empleado">
                        Técnico *
                    </label>


                    <select
                        name="id_empleado"
                        id="id_empleado"
                        required
                    >

                        <option value="">
                            Seleccione un técnico
                        </option>


                        <?php foreach (
                            $empleados
                            as $empleado
                        ): ?>

                            <option
                                value="<?= $empleado["id_empleado"] ?>"
                            >

                                <?= limpiar(
                                    $empleado["nombres"]
                                    . " "
                                    . $empleado["apellidos"]
                                ) ?>

                                -
                                <?= limpiar(
                                    $empleado["cargo"]
                                ) ?>

                            </option>

                        <?php endforeach; ?>


                    </select>

                </div>



                <!-- ESTADO -->

                <div class="service-form-group">

                    <label for="estado">
                        Estado *
                    </label>


                    <select
                        name="estado"
                        id="estado"
                        required
                    >

                        <option value="Recibido">
                            Recibido
                        </option>

                        <option value="En Diagnóstico">
                            En Diagnóstico
                        </option>

                        <option value="Pendiente Repuestos">
                            Pendiente Repuestos
                        </option>

                        <option value="En Reparación">
                            En Reparación
                        </option>

                        <option value="Listo para Entrega">
                            Listo para Entrega
                        </option>

                        <option value="Entregado">
                            Entregado
                        </option>

                    </select>

                </div>



                <!-- COSTO -->

                <div class="service-form-group">

                    <label for="costo">
                        Costo *
                    </label>


                    <input
                        type="number"
                        name="costo"
                        id="costo"
                        min="0"
                        step="0.01"
                        placeholder="0"
                        required
                    >

                </div>



                <!-- FECHA INGRESO -->

                <div class="service-form-group">

                    <label for="fecha_ingreso">
                        Fecha de ingreso *
                    </label>


                    <input
                        type="date"
                        name="fecha_ingreso"
                        id="fecha_ingreso"
                        required
                    >

                </div>



                <!-- FECHA ENTREGA -->

                <div class="service-form-group">

                    <label for="fecha_entrega">
                        Fecha de entrega
                    </label>


                    <input
                        type="date"
                        name="fecha_entrega"
                        id="fecha_entrega"
                    >

                </div>



                <!-- DESCRIPCIÓN -->

                <div
                    class="service-form-group full"
                >

                    <label for="descripcion">
                        Descripción del servicio *
                    </label>


                    <textarea
                        name="descripcion"
                        id="descripcion"
                        placeholder="Describa el diagnóstico, reparación o servicio realizado..."
                        required
                    ></textarea>

                </div>


            </div>



            <div class="service-modal-footer">


                <button
                    type="button"
                    id="btnCancelarServicio"
                    class="btn-cancel-service"
                >

                    Cancelar

                </button>


                <button
                    type="submit"
                    class="btn-save-service"
                >

                    <i
                        class="fa-solid fa-floppy-disk"
                    ></i>

                    <span id="textoGuardarServicio">
                        Guardar Servicio
                    </span>

                </button>


            </div>


        </form>


    </div>


</div>



<!-- =====================================================
     MODAL VER
     ===================================================== -->

<div
    id="modalVerServicio"
    class="service-modal"
>


    <div class="service-modal-content">


        <div class="service-modal-header">


            <h2>

                <i
                    class="fa-solid fa-file-lines"
                ></i>

                Detalle del Servicio

            </h2>


            <button
                type="button"
                id="btnCerrarVerServicio"
                class="service-modal-close"
            >

                <i
                    class="fa-solid fa-xmark"
                ></i>

            </button>


        </div>



        <div class="service-detail-grid">


            <div class="service-detail-item">

                <span>
                    Orden
                </span>

                <strong id="verIdServicio">
                    -
                </strong>

            </div>


            <div class="service-detail-item">

                <span>
                    Estado
                </span>

                <strong id="verEstadoServicio">
                    -
                </strong>

            </div>


            <div class="service-detail-item">

                <span>
                    Cliente
                </span>

                <strong id="verClienteServicio">
                    -
                </strong>

            </div>


            <div class="service-detail-item">

                <span>
                    Documento
                </span>

                <strong id="verCedulaServicio">
                    -
                </strong>

            </div>


            <div class="service-detail-item">

                <span>
                    Técnico
                </span>

                <strong id="verTecnicoServicio">
                    -
                </strong>

            </div>


            <div class="service-detail-item">

                <span>
                    Cargo
                </span>

                <strong id="verCargoServicio">
                    -
                </strong>

            </div>


            <div class="service-detail-item">

                <span>
                    Fecha de ingreso
                </span>

                <strong id="verIngresoServicio">
                    -
                </strong>

            </div>


            <div class="service-detail-item">

                <span>
                    Fecha de entrega
                </span>

                <strong id="verEntregaServicio">
                    -
                </strong>

            </div>


            <div class="service-detail-item">

                <span>
                    Costo
                </span>

                <strong id="verCostoServicio">
                    -
                </strong>

            </div>


            <div class="service-detail-item full">

                <span>
                    Descripción
                </span>

                <strong id="verDescripcionServicio">
                    -
                </strong>

            </div>


        </div>


    </div>


</div>



<script>

/* =========================================================
   FECHA
   ========================================================= */

const currentDate =
    document.getElementById("currentDate");

if (currentDate) {

    const fecha = new Date();

    currentDate.textContent =
        fecha.toLocaleDateString(
            "es-CO",
            {
                weekday: "long",
                year: "numeric",
                month: "long",
                day: "numeric"
            }
        );
}


/* =========================================================
   ELEMENTOS
   ========================================================= */

const modalServicio =
    document.getElementById(
        "modalServicio"
    );

const modalVerServicio =
    document.getElementById(
        "modalVerServicio"
    );

const formServicio =
    document.getElementById(
        "formServicio"
    );

const btnNuevoServicio =
    document.getElementById(
        "btnNuevoServicio"
    );

const btnCerrarServicio =
    document.getElementById(
        "btnCerrarServicio"
    );

const btnCancelarServicio =
    document.getElementById(
        "btnCancelarServicio"
    );

const btnCerrarVerServicio =
    document.getElementById(
        "btnCerrarVerServicio"
    );


/* =========================================================
   NUEVO SERVICIO
   ========================================================= */

if (btnNuevoServicio) {

    btnNuevoServicio.addEventListener(
        "click",
        function () {

            formServicio.reset();

            document.getElementById(
                "accionServicio"
            ).value = "crear";

            document.getElementById(
                "idServicio"
            ).value = "";

            document.getElementById(
                "tituloModalServicio"
            ).innerHTML =
                '<i class="fa-solid fa-screwdriver-wrench"></i> Nuevo Servicio';

            document.getElementById(
                "textoGuardarServicio"
            ).textContent =
                "Guardar Servicio";


            const hoy =
                new Date()
                .toISOString()
                .split("T")[0];


            document.getElementById(
                "fecha_ingreso"
            ).value = hoy;


            modalServicio.style.display =
                "flex";
        }
    );

}


/* =========================================================
   EDITAR
   ========================================================= */

document.querySelectorAll(
    ".btnEditarServicio"
).forEach(
    function (boton) {

        boton.addEventListener(
            "click",
            function () {

                formServicio.reset();


                document.getElementById(
                    "accionServicio"
                ).value = "editar";


                document.getElementById(
                    "idServicio"
                ).value =
                    this.dataset.id;


                document.getElementById(
                    "id_cliente"
                ).value =
                    this.dataset.cliente;


                document.getElementById(
                    "id_empleado"
                ).value =
                    this.dataset.empleado;


                document.getElementById(
                    "descripcion"
                ).value =
                    this.dataset.descripcion;


                document.getElementById(
                    "estado"
                ).value =
                    this.dataset.estado;


                document.getElementById(
                    "costo"
                ).value =
                    this.dataset.costo;


                document.getElementById(
                    "fecha_ingreso"
                ).value =
                    this.dataset.ingreso;


                document.getElementById(
                    "fecha_entrega"
                ).value =
                    this.dataset.entrega;


                document.getElementById(
                    "tituloModalServicio"
                ).innerHTML =
                    '<i class="fa-solid fa-pen"></i> Editar Servicio';


                document.getElementById(
                    "textoGuardarServicio"
                ).textContent =
                    "Actualizar Servicio";


                modalServicio.style.display =
                    "flex";

            }
        );

    }
);


/* =========================================================
   CERRAR MODAL
   ========================================================= */

function cerrarModalServicio() {

    modalServicio.style.display =
        "none";

    formServicio.reset();
}


if (btnCerrarServicio) {

    btnCerrarServicio.addEventListener(
        "click",
        cerrarModalServicio
    );
}


if (btnCancelarServicio) {

    btnCancelarServicio.addEventListener(
        "click",
        cerrarModalServicio
    );
}


/* =========================================================
   VER SERVICIO
   ========================================================= */

document.querySelectorAll(
    ".btnVerServicio"
).forEach(
    function (boton) {

        boton.addEventListener(
            "click",
            function () {

                document.getElementById(
                    "verIdServicio"
                ).textContent =
                    "#" + this.dataset.id;


                document.getElementById(
                    "verClienteServicio"
                ).textContent =
                    this.dataset.cliente;


                document.getElementById(
                    "verCedulaServicio"
                ).textContent =
                    this.dataset.cedula;


                document.getElementById(
                    "verTecnicoServicio"
                ).textContent =
                    this.dataset.tecnico;


                document.getElementById(
                    "verCargoServicio"
                ).textContent =
                    this.dataset.cargo;


                document.getElementById(
                    "verDescripcionServicio"
                ).textContent =
                    this.dataset.descripcion;


                document.getElementById(
                    "verEstadoServicio"
                ).textContent =
                    this.dataset.estado;


                document.getElementById(
                    "verCostoServicio"
                ).textContent =
                    this.dataset.costo;


                document.getElementById(
                    "verIngresoServicio"
                ).textContent =
                    this.dataset.ingreso;


                document.getElementById(
                    "verEntregaServicio"
                ).textContent =
                    this.dataset.entrega;


                modalVerServicio.style.display =
                    "flex";

            }
        );

    }
);


/* =========================================================
   CERRAR MODAL VER
   ========================================================= */

if (btnCerrarVerServicio) {

    btnCerrarVerServicio.addEventListener(
        "click",
        function () {

            modalVerServicio.style.display =
                "none";

        }
    );
}


/* =========================================================
   CERRAR AL HACER CLICK FUERA
   ========================================================= */

window.addEventListener(
    "click",
    function (event) {

        if (
            event.target ===
            modalServicio
        ) {

            cerrarModalServicio();

        }


        if (
            event.target ===
            modalVerServicio
        ) {

            modalVerServicio.style.display =
                "none";

        }

    }
);


/* =========================================================
   CERRAR SESIÓN
   ========================================================= */

const btnLogout =
    document.getElementById(
        "btnLogout"
    );

if (btnLogout) {

    btnLogout.addEventListener(
        "click",
        function () {

            if (
                confirm(
                    "¿Desea cerrar la sesión?"
                )
            ) {

                window.location.href =
                    "login.html";

            }

        }
    );

}

</script>


</body>

</html>