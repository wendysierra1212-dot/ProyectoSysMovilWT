<?php

require_once "../config/conexion.php";
require_once "../config/autenticacion.php";
require_once "../config/actividad.php";
exigirPermiso($conn, 'usuarios', 'ver');

/* =========================================================
   MODULO DE USUARIOS
   Mantiene la estructura del proyecto: un solo PHP para
   consulta, creación, edición y eliminación.
   ========================================================= */

$mensaje = "";
$tipoMensaje = "";
$usuarioEditar = null;
$modoLectura = false;

function limpiar($dato)
{
    return htmlspecialchars($dato ?? "", ENT_QUOTES, "UTF-8");
}

/* =========================================================
   CREAR / EDITAR
   ========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";
    exigirPermiso($conn, 'usuarios', match ($accion) { 'crear' => 'crear', 'editar', 'cambiar_estado' => 'editar', default => 'ver' });

    /* ACCIÓN: CAMBIAR EL ESTADO DEL USUARIO */
    if ($accion === "cambiar_estado") {
        $idEstado = (int)($_POST["id_usuario"] ?? 0);
        try {
            if ($idEstado <= 0) throw new Exception("Identificador inválido.");
            $stmt = $conexion->prepare("UPDATE usuarios SET estado = IF(estado = 'Activo', 'Inactivo', 'Activo') WHERE id_usuario = ?");
            $stmt->bind_param("i", $idEstado);
            $stmt->execute();
            $actualizado = $stmt->affected_rows;
            $stmt->close();
            if ($actualizado) registrarActividadSistema($conn, "Usuarios", "Cambio de estado", "Estado del usuario #" . $idEstado . " actualizado.");
            header("Location: usuarios.php?mensaje=" . ($actualizado ? "estado_actualizado" : "error_estado"));
        } catch (Exception $e) {
            header("Location: usuarios.php?mensaje=error_estado");
        }
        exit;
    }

    $id_usuario = (int)($_POST["id_usuario"] ?? 0);
    $id_empleado = (int)($_POST["id_empleado"] ?? 0);

    $cedula = trim($_POST["cedula"] ?? "");
    $nombres = trim($_POST["nombres"] ?? "");
    $apellidos = trim($_POST["apellidos"] ?? "");
    $cargo = trim($_POST["cargo"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");
    $fecha_ingreso = trim($_POST["fecha_ingreso"] ?? "");
    $nombre_usuario = trim($_POST["nombre_usuario"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";
    $estado = $_POST["estado"] ?? "Activo";
    $id_rol = (int)($_POST["id_rol"] ?? 0);

    $telefono = ($telefono === "") ? null : $telefono;
    $correo = ($correo === "") ? null : $correo;
    $direccion = ($direccion === "") ? null : $direccion;
    $fecha_ingreso = ($fecha_ingreso === "") ? null : $fecha_ingreso;

    if (
        $cedula === "" ||
        $nombres === "" ||
        $apellidos === "" ||
        $cargo === "" ||
        $nombre_usuario === "" ||
        $id_rol <= 0
    ) {
        $mensaje = "Complete los campos obligatorios del usuario.";
        $tipoMensaje = "error";
    } elseif ($accion === "crear" && strlen($contrasena) < 6) {
        $mensaje = "La contraseña debe tener mínimo 6 caracteres.";
        $tipoMensaje = "error";
    } else {

        try {
            $conexion->begin_transaction();

            if ($accion === "crear") {

                /* Verificar usuario/cédula antes de insertar */
                $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE nombre_usuario = ?");
                $stmt->bind_param("s", $nombre_usuario);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    throw new Exception("El nombre de usuario ya está registrado.");
                }
                $stmt->close();

                $stmt = $conexion->prepare("SELECT id_empleado FROM empleados WHERE cedula = ?");
                $stmt->bind_param("s", $cedula);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    throw new Exception("La cédula ya está registrada en un empleado.");
                }
                $stmt->close();

                $sqlEmpleado = "INSERT INTO empleados
                                (cedula, nombres, apellidos, cargo, telefono, correo, direccion, fecha_ingreso, estado)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Activo')";

                $stmt = $conexion->prepare($sqlEmpleado);
                $stmt->bind_param(
                    "ssssssss",
                    $cedula,
                    $nombres,
                    $apellidos,
                    $cargo,
                    $telefono,
                    $correo,
                    $direccion,
                    $fecha_ingreso
                );
                $stmt->execute();
                $nuevoEmpleado = $conexion->insert_id;
                $stmt->close();

                $hash = password_hash($contrasena, PASSWORD_DEFAULT);

                $sqlUsuario = "INSERT INTO usuarios
                               (nombre_usuario, contrasena, estado, id_empleado, id_rol)
                               VALUES (?, ?, ?, ?, ?)";

                $stmt = $conexion->prepare($sqlUsuario);
                $stmt->bind_param(
                    "sssii",
                    $nombre_usuario,
                    $hash,
                    $estado,
                    $nuevoEmpleado,
                    $id_rol
                );
                $stmt->execute();
                $stmt->close();

                $conexion->commit();
                registrarActividadSistema($conn, "Usuarios", "Registro", "Usuario " . $nombre_usuario . " registrado.");
                header("Location: usuarios.php?mensaje=creado");
                exit;
            }

            elseif ($accion === "editar" && $id_usuario > 0 && $id_empleado > 0) {

                /* Verificar nombre de usuario en otro registro */
                $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE nombre_usuario = ? AND id_usuario <> ?");
                $stmt->bind_param("si", $nombre_usuario, $id_usuario);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    throw new Exception("El nombre de usuario ya está registrado por otro usuario.");
                }
                $stmt->close();

                $sqlEmpleado = "UPDATE empleados SET
                                    cedula = ?,
                                    nombres = ?,
                                    apellidos = ?,
                                    cargo = ?,
                                    telefono = ?,
                                    correo = ?,
                                    direccion = ?,
                                    fecha_ingreso = ?
                                WHERE id_empleado = ?";

                $stmt = $conexion->prepare($sqlEmpleado);
                $stmt->bind_param(
                    "ssssssssi",
                    $cedula,
                    $nombres,
                    $apellidos,
                    $cargo,
                    $telefono,
                    $correo,
                    $direccion,
                    $fecha_ingreso,
                    $id_empleado
                );
                $stmt->execute();
                $stmt->close();

                if ($contrasena !== "") {
                    if (strlen($contrasena) < 6) {
                        throw new Exception("La nueva contraseña debe tener mínimo 6 caracteres.");
                    }

                    $hash = password_hash($contrasena, PASSWORD_DEFAULT);

                    $stmt = $conexion->prepare("UPDATE usuarios SET nombre_usuario = ?, contrasena = ?, estado = ?, id_rol = ? WHERE id_usuario = ?");
                    $stmt->bind_param("sssii", $nombre_usuario, $hash, $estado, $id_rol, $id_usuario);
                } else {
                    $stmt = $conexion->prepare("UPDATE usuarios SET nombre_usuario = ?, estado = ?, id_rol = ? WHERE id_usuario = ?");
                    $stmt->bind_param("ssii", $nombre_usuario, $estado, $id_rol, $id_usuario);
                }

                $stmt->execute();
                $stmt->close();

                $conexion->commit();
                registrarActividadSistema($conn, "Usuarios", "Actualización", "Usuario " . $nombre_usuario . " actualizado.");
                header("Location: usuarios.php?mensaje=editado");
                exit;
            }
            else {
                throw new Exception("Los datos del usuario no son válidos.");
            }

        } catch (Exception $e) {
            $conexion->rollback();
            $mensaje = "No fue posible guardar el usuario: " . $e->getMessage();
            $tipoMensaje = "error";
        }
    }
}

/* =========================================================
   ELIMINAR USUARIO
   ========================================================= */

if (isset($_GET["eliminar"])) {
    exigirPermiso($conn, 'usuarios', 'eliminar');

    $idEliminar = (int)$_GET["eliminar"];

    if ($idEliminar > 0) {
        try {
            $conexion->begin_transaction();

            $stmt = $conexion->prepare("SELECT id_empleado FROM usuarios WHERE id_usuario = ?");
            $stmt->bind_param("i", $idEliminar);
            $stmt->execute();
            $stmt->bind_result($empleadoEliminar);

            if (!$stmt->fetch()) {
                $stmt->close();
                throw new Exception("El usuario no existe.");
            }
            $stmt->close();

            $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
            $stmt->bind_param("i", $idEliminar);
            $stmt->execute();
            $stmt->close();

            if ($empleadoEliminar > 0) {
                $stmt = $conexion->prepare("DELETE FROM empleados WHERE id_empleado = ?");
                $stmt->bind_param("i", $empleadoEliminar);
                $stmt->execute();
                $stmt->close();
            }

            $conexion->commit();
            registrarActividadSistema($conn, "Usuarios", "Eliminación", "Usuario #" . $idEliminar . " eliminado.");
            header("Location: usuarios.php?mensaje=eliminado");
            exit;

        } catch (Exception $e) {
            $conexion->rollback();
            $mensaje = "No fue posible eliminar el usuario: " . $e->getMessage();
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
            $mensaje = "Usuario registrado correctamente.";
            $tipoMensaje = "success";
            break;
        case "editado":
            $mensaje = "Usuario actualizado correctamente.";
            $tipoMensaje = "success";
            break;
        case "eliminado":
            $mensaje = "Usuario eliminado correctamente.";
            $tipoMensaje = "success";
            break;
        case "estado_actualizado":
            $mensaje = "Estado del usuario actualizado correctamente.";
            $tipoMensaje = "success";
            break;
        case "error_estado":
            $mensaje = "No se pudo actualizar el estado del usuario.";
            $tipoMensaje = "error";
            break;
    }
}

/* =========================================================
   USUARIO A EDITAR
   ========================================================= */

if (isset($_GET["editar"]) || isset($_GET["ver"])) {
    $modoLectura = isset($_GET["ver"]);
    $idEditar = (int)($_GET["ver"] ?? $_GET["editar"]);

    if ($idEditar > 0) {
        $stmt = $conexion->prepare("SELECT
                u.id_usuario,
                u.nombre_usuario,
                u.estado,
                u.id_empleado,
                u.id_rol,
                e.cedula,
                e.nombres,
                e.apellidos,
                e.cargo,
                e.telefono,
                e.correo,
                e.direccion,
                e.fecha_ingreso
            FROM usuarios u
            INNER JOIN empleados e ON e.id_empleado = u.id_empleado
            WHERE u.id_usuario = ?");

        $stmt->bind_param("i", $idEditar);
        $stmt->execute();
        $resultadoEditar = $stmt->get_result();
        $usuarioEditar = $resultadoEditar->fetch_assoc();
        $stmt->close();

        if (!$usuarioEditar) {
            $mensaje = "No se encontró el usuario solicitado.";
            $tipoMensaje = "error";
        }
    }
}

/* =========================================================
   ROLES
   ========================================================= */

$roles = [];
$resultadoRoles = $conexion->query("SELECT id_rol, nombre_rol, descripcion FROM roles ORDER BY id_rol ASC");

if ($resultadoRoles) {
    while ($rol = $resultadoRoles->fetch_assoc()) {
        $roles[] = $rol;
    }
}

/* =========================================================
   LISTADO DE USUARIOS
   ========================================================= */

$sqlUsuarios = "SELECT
                    u.id_usuario,
                    u.nombre_usuario,
                    u.estado,
                    u.ultimo_acceso,
                    u.id_empleado,
                    u.id_rol,
                    e.cedula,
                    e.nombres,
                    e.apellidos,
                    e.cargo,
                    r.nombre_rol
                FROM usuarios u
                INNER JOIN empleados e ON e.id_empleado = u.id_empleado
                INNER JOIN roles r ON r.id_rol = u.id_rol
                ORDER BY u.id_usuario DESC";

$resultado = $conexion->query($sqlUsuarios);

/* =========================================================
   ESTADÍSTICAS
   ========================================================= */

$totalUsuarios = 0;
$usuariosActivos = 0;
$usuariosInactivos = 0;
$totalRoles = count($roles);

$resultadoStats = $conexion->query("SELECT
                                        COUNT(*) AS total,
                                        SUM(estado = 'Activo') AS activos,
                                        SUM(estado = 'Inactivo') AS inactivos
                                    FROM usuarios");

if ($resultadoStats) {
    $stats = $resultadoStats->fetch_assoc();
    $totalUsuarios = (int)($stats["total"] ?? 0);
    $usuariosActivos = (int)($stats["activos"] ?? 0);
    $usuariosInactivos = (int)($stats["inactivos"] ?? 0);
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
        Usuarios | SYSMOVILWT
    </title>


    <!--=========================================
    =              GOOGLE FONTS
    ==========================================-->

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


    <!--=========================================
    =              FONT AWESOME
    ==========================================-->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">


    <!--=========================================
    =              CSS
    ==========================================-->

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
        href="../assets/css/usuarios.css">

    <link rel="stylesheet" href="../assets/css/acciones-compartidas.css">
</head>


<body>


<!--=========================================
=              SIDEBAR
==========================================-->

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

                <a href="dashboard.php">

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

                <a href="ventas.php">

                    <i class="fa-solid fa-cart-shopping"></i>

                    <span>
                        Ventas
                    </span>

                </a>

            </li>


            <li>

                <a href="compras.php">

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


            <li class="active">

                <a href="usuarios.php">

                    <i class="fa-solid fa-user-shield"></i>

                    <span>
                        Usuarios
                    </span>

                </a>

            </li>


            <li>

                <a href="serviciotecnico.php">

                    <i class="fa-solid fa-screwdriver-wrench"></i>

                    <span>
                        Servicio Técnico
                    </span>

                </a>

            </li>


            <li>

                <a href="reportes.php">

                    <i class="fa-solid fa-chart-column"></i>

                    <span>
                        Reportes
                    </span>

                </a>

            </li>


            <li>

                <a href="configuracion.php">

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
        id="btnLogout">

        <i class="fa-solid fa-right-from-bracket"></i>

        <span>
            Cerrar sesión
        </span>

    </button>

</aside>



<!--=========================================
=              TOPBAR
==========================================-->

<header class="topbar">

    <div class="topbar-left">

        <button class="menu-toggle">

            <i class="fa-solid fa-bars"></i>

        </button>


        <div class="topbar-title">

            <h1>
                Usuarios
            </h1>

            <span>
                Gestión de usuarios del sistema
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

                <strong id="topbarUser">
                    Leonardo
                </strong>

                <span id="topbarRole">
                    Administrador
                </span>

            </div>

        </div>

    </div>

</header>



<!--=========================================
=              CONTENIDO
==========================================-->

<main class="dashboard-main">

<?php if ($mensaje !== ""): ?>
<div style="margin:20px 0;padding:14px 18px;border-radius:10px;font-weight:600;background:<?= $tipoMensaje === "success" ? "#dcfce7" : "#fee2e2" ?>;color:<?= $tipoMensaje === "success" ? "#166534" : "#991b1b" ?>;border:1px solid <?= $tipoMensaje === "success" ? "#86efac" : "#fecaca" ?>;">
    <i class="fa-solid <?= $tipoMensaje === "success" ? "fa-circle-check" : "fa-circle-exclamation" ?>"></i>
    <?= limpiar($mensaje) ?>
</div>
<?php endif; ?>

    <section class="crud-container">


<!--=========================================
=              CABECERA CRUD
==========================================-->

<section class="crud-header">

    <div class="crud-title">

        <h2>

            <i class="fa-solid fa-user-shield"></i>

            Gestión de Usuarios

        </h2>


        <p>

            Administra las cuentas de acceso,
            empleados, roles y estados del sistema SYSMOVILWT.

        </p>

    </div>


    <button
        type="button"
        id="btnNuevoUsuario"
        class="btn-new">

        <i class="fa-solid fa-user-plus"></i>

        Nuevo Usuario

    </button>

</section>



<!--=========================================
=              TARJETAS
==========================================-->

<section class="crud-stats">


    <div class="crud-card">

        <div class="crud-card-icon bg-red">

            <i class="fa-solid fa-users"></i>

        </div>


        <div class="crud-card-info">

            <span>
                Usuarios Registrados
            </span>

            <strong id="totalUsuarios">
                <?= $totalUsuarios ?>
            </strong>

        </div>

    </div>



    <div class="crud-card">

        <div class="crud-card-icon bg-green">

            <i class="fa-solid fa-user-check"></i>

        </div>


        <div class="crud-card-info">

            <span>
                Usuarios Activos
            </span>

            <strong id="usuariosActivos">
                <?= $usuariosActivos ?>
            </strong>

        </div>

    </div>



    <div class="crud-card">

        <div class="crud-card-icon bg-orange">

            <i class="fa-solid fa-user-slash"></i>

        </div>


        <div class="crud-card-info">

            <span>
                Usuarios Inactivos
            </span>

            <strong id="usuariosInactivos">
                <?= $usuariosInactivos ?>
            </strong>

        </div>

    </div>



    <div class="crud-card">

        <div class="crud-card-icon bg-blue">

            <i class="fa-solid fa-shield-halved"></i>

        </div>


        <div class="crud-card-info">

            <span>
                Roles Disponibles
            </span>

            <strong id="totalRoles">
                <?= $totalRoles ?>
            </strong>

        </div>

    </div>

</section>



<!--=========================================
=              BARRA DE HERRAMIENTAS
==========================================-->

<section class="crud-toolbar">


    <div class="crud-search">

        <i class="fa-solid fa-magnifying-glass"></i>

        <input
            type="text"
            id="searchUser"
            placeholder="Buscar por usuario, empleado o rol...">

    </div>


    <select id="filterEstado">

        <option value="">
            Todos los estados
        </option>

        <option value="Activo">
            Activo
        </option>

        <option value="Inactivo">
            Inactivo
        </option>

    </select>


    <select id="filterRol">

        <option value="">
            Todos los roles
        </option>

    </select>


    <button
        id="btnResetFilters"
        class="btn-reset"
        type="button"
        title="Limpiar filtros">

        <i class="fa-solid fa-rotate-right"></i>

    </button>

</section>



<!--=========================================
=              TABLA DE USUARIOS
==========================================-->

<section class="crud-table-card">

    <table class="crud-table">

        <thead>

            <tr>

                <th>
                    Usuario
                </th>

                <th>
                    Empleado
                </th>

                <th>
                    Rol
                </th>

                <th>
                    Estado
                </th>

                <th>
                    Último acceso
                </th>

                <th>
                    Acciones
                </th>

            </tr>

        </thead>


        <tbody id="usersTableBody">

<?php if ($resultado && $resultado->num_rows > 0): ?>

<?php while ($usuarioFila = $resultado->fetch_assoc()): ?>

<tr class="user-row"
    data-search="<?= limpiar($usuarioFila["nombre_usuario"] . " " . $usuarioFila["nombres"] . " " . $usuarioFila["apellidos"] . " " . $usuarioFila["nombre_rol"]) ?>"
    data-estado="<?= limpiar($usuarioFila["estado"]) ?>"
    data-rol="<?= limpiar($usuarioFila["nombre_rol"]) ?>">

    <td>
        <strong><?= limpiar($usuarioFila["nombre_usuario"]) ?></strong>
    </td>

    <td>
        <?= limpiar($usuarioFila["nombres"] . " " . $usuarioFila["apellidos"]) ?>
    </td>

    <td>
        <?= limpiar($usuarioFila["nombre_rol"]) ?>
    </td>

    <td>
        <span class="status-badge <?= strtolower($usuarioFila["estado"]) === "activo" ? "active" : "inactive" ?>">
            <?= limpiar($usuarioFila["estado"]) ?>
        </span>
    </td>

    <td>
        <?= $usuarioFila["ultimo_acceso"] ? limpiar($usuarioFila["ultimo_acceso"]) : "Nunca" ?>
    </td>

    <td>
        <div class="user-actions" style="display:flex; gap:8px;">
            <a class="btn-view-user" href="usuarios.php?ver=<?= (int)$usuarioFila["id_usuario"] ?>" title="Ver usuario" aria-label="Ver usuario"
               style="width:38px;height:38px;display:flex;align-items:center;justify-content:center;border-radius:9px;text-decoration:none;color:white;background:#2563eb;">
                <i class="fa-solid fa-eye"></i>
            </a>
            <a href="usuarios.php?editar=<?= (int)$usuarioFila["id_usuario"] ?>"
               class="btn-edit-user"
               title="Editar usuario"
               style="width:38px;height:38px;display:flex;align-items:center;justify-content:center;border-radius:9px;text-decoration:none;color:white;background:#f59e0b;">
                <i class="fa-solid fa-pen"></i>
            </a>

            <form method="POST" action="usuarios.php" style="display:inline-flex;">
                <input type="hidden" name="accion" value="cambiar_estado">
                <input type="hidden" name="id_usuario" value="<?= (int)$usuarioFila["id_usuario"] ?>">
                <button type="submit" class="btn-toggle-user" title="<?= $usuarioFila["estado"] === "Activo" ? "Desactivar usuario" : "Activar usuario" ?>"
                        aria-label="<?= $usuarioFila["estado"] === "Activo" ? "Desactivar usuario" : "Activar usuario" ?>"
                        onclick="return confirm('¿Desea <?= $usuarioFila["estado"] === "Activo" ? "desactivar" : "activar" ?> este usuario?');"
                        style="width:38px;height:38px;display:flex;align-items:center;justify-content:center;border:0;border-radius:9px;color:white;background:<?= $usuarioFila["estado"] === "Activo" ? "#64748b" : "#16a34a" ?>;cursor:pointer;">
                    <i class="fa-solid <?= $usuarioFila["estado"] === "Activo" ? "fa-user-slash" : "fa-user-check" ?>"></i>
                </button>
            </form>

            <a class="btn-delete-user" href="usuarios.php?eliminar=<?= (int)$usuarioFila["id_usuario"] ?>"
               title="Eliminar usuario"
               onclick="return confirm('¿Está seguro de eliminar este usuario y su empleado asociado? Esta acción no se puede deshacer.');"
               style="width:38px;height:38px;display:flex;align-items:center;justify-content:center;border-radius:9px;text-decoration:none;color:white;background:#dc2626;">
                <i class="fa-solid fa-trash"></i>
            </a>
        </div>
    </td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr class="users-empty">
    <td colspan="6">
        <div class="empty-state">
            <i class="fa-solid fa-user-shield"></i>
            <h3>No hay usuarios registrados</h3>
            <p>Haz clic en <strong>Nuevo Usuario</strong> para comenzar.</p>
        </div>
    </td>
</tr>

<?php endif; ?>

</tbody>

    </table>

</section>



<!--=========================================
=              MODAL USUARIO
==========================================-->

<div
    id="userModal"
    class="crud-modal"
    data-readonly="<?= $modoLectura ? "true" : "false" ?>">


    <div class="crud-modal-content">


<!--=========================================
=              CABECERA MODAL
==========================================-->

<div class="crud-modal-header">

    <h2>

        <i class="fa-solid <?= $usuarioEditar ? "fa-user-pen" : "fa-user-plus" ?>"></i>

        <?= $usuarioEditar ? ($modoLectura ? "Ver Usuario" : "Editar Usuario") : "Nuevo Usuario" ?>

    </h2>


    <button
        id="btnCloseModal"
        class="crud-close">

        <i class="fa-solid fa-xmark"></i>

    </button>

</div>



<form
    id="userForm"
    class="crud-form"
    method="POST"
    action="usuarios.php">

    <input type="hidden" name="accion" id="accionUsuario" value="<?= $usuarioEditar ? "editar" : "crear" ?>">
    <input type="hidden" name="id_usuario" id="idUsuario" value="<?= (int)($usuarioEditar["id_usuario"] ?? 0) ?>">
    <input type="hidden" name="id_empleado" id="idEmpleado" value="<?= (int)($usuarioEditar["id_empleado"] ?? 0) ?>">



<!--=========================================
=              INFORMACIÓN PERSONAL
==========================================-->

<div class="crud-section">

    <h3>

        <i class="fa-solid fa-id-card"></i>

        Información Personal

    </h3>


    <div class="crud-grid">


        <!-- NOMBRES -->

        <div class="crud-group">

            <label for="nombresEmpleado">

                Nombres *

            </label>

            <input
                type="text"
                id="nombresEmpleado"
                value="<?= limpiar($usuarioEditar["nombres"] ?? "") ?>"
                name="nombres"
                maxlength="100"
                placeholder="Ingrese los nombres"
                required>

        </div>


        <!-- APELLIDOS -->

        <div class="crud-group">

            <label for="apellidosEmpleado">

                Apellidos *

            </label>

            <input
                type="text"
                id="apellidosEmpleado"
                value="<?= limpiar($usuarioEditar["apellidos"] ?? "") ?>"
                name="apellidos"
                maxlength="100"
                placeholder="Ingrese los apellidos"
                required>

        </div>


        <!-- CÉDULA -->

        <div class="crud-group">

            <label for="cedulaEmpleado">

                Cédula *

            </label>

            <input
                type="text"
                id="cedulaEmpleado"
                value="<?= limpiar($usuarioEditar["cedula"] ?? "") ?>"
                name="cedula"
                maxlength="20"
                placeholder="Ingrese el número de cédula"
                required>

        </div>


        <!-- TELÉFONO -->

        <div class="crud-group">

            <label for="telefonoEmpleado">

                Teléfono

            </label>

            <input
                type="tel"
                id="telefonoEmpleado"
                value="<?= limpiar($usuarioEditar["telefono"] ?? "") ?>"
                name="telefono"
                maxlength="20"
                placeholder="Ingrese el teléfono">

        </div>


        <!-- CORREO -->

        <div class="crud-group">

            <label for="correoEmpleado">

                Correo

            </label>

            <input
                type="email"
                id="correoEmpleado"
                value="<?= limpiar($usuarioEditar["correo"] ?? "") ?>"
                name="correo"
                maxlength="100"
                placeholder="correo@ejemplo.com">

        </div>


        <!-- DIRECCIÓN -->

        <div class="crud-group">

            <label for="direccionEmpleado">

                Dirección

            </label>

            <input
                type="text"
                id="direccionEmpleado"
                value="<?= limpiar($usuarioEditar["direccion"] ?? "") ?>"
                name="direccion"
                maxlength="150"
                placeholder="Ingrese la dirección">

        </div>


        <!-- CARGO -->

        <div class="crud-group">

            <label for="cargoEmpleado">

                Cargo *

            </label>

            <input
                type="text"
                id="cargoEmpleado"
                value="<?= limpiar($usuarioEditar["cargo"] ?? "") ?>"
                name="cargo"
                maxlength="50"
                placeholder="Ej: Vendedor, Técnico..."
                required>

        </div>


        <!-- FECHA INGRESO -->

        <div class="crud-group">

            <label for="fechaIngresoEmpleado">

                Fecha de Ingreso

            </label>

            <input
                type="date"
                id="fechaIngresoEmpleado"
                value="<?= limpiar($usuarioEditar["fecha_ingreso"] ?? "") ?>"
                name="fecha_ingreso">

        </div>

    </div>

</div>



<!--=========================================
=              INFORMACIÓN DE ACCESO
==========================================-->

<div class="crud-section">

    <h3>

        <i class="fa-solid fa-lock"></i>

        Información de Acceso

    </h3>


    <div class="crud-grid">


        <!-- USUARIO -->

        <div class="crud-group">

            <label for="nombreUsuario">

                Nombre de Usuario *

            </label>

            <input
                type="text"
                id="nombreUsuario"
                value="<?= limpiar($usuarioEditar["nombre_usuario"] ?? "") ?>"
                name="nombre_usuario"
                maxlength="50"
                autocomplete="username"
                placeholder="Ej: leonardo"
                required>

        </div>


        <!-- ESTADO -->

        <div class="crud-group">

            <label for="estadoUsuario">

                Estado *

            </label>

            <select
                id="estadoUsuario"
                name="estado"
                required>

                <option value="Activo" <?= (($usuarioEditar["estado"] ?? "Activo") === "Activo") ? "selected" : "" ?>>
                    Activo
                </option>

                <option value="Inactivo" <?= (($usuarioEditar["estado"] ?? "") === "Inactivo") ? "selected" : "" ?>>
                    Inactivo
                </option>

            </select>

        </div>


        <!-- CONTRASEÑA -->

        <div class="crud-group">

            <label for="contrasena">

                Contraseña *

            </label>

            <input
                type="password"
                id="contrasena"
                name="contrasena"
                <?= $usuarioEditar ? "" : "required" ?>
                minlength="6"
                autocomplete="new-password"
                placeholder="Ingrese una contraseña"
                required>

        </div>


        <!-- CONFIRMAR CONTRASEÑA -->

        <div class="crud-group">

            <label for="confirmarContrasena">

                Confirmar Contraseña *

            </label>

            <input
                type="password"
                id="confirmarContrasena"
                <?= $usuarioEditar ? "" : "required" ?>
                minlength="6"
                autocomplete="new-password"
                placeholder="Repita la contraseña"
                required>

        </div>

    </div>

</div>



<!--=========================================
=              ROL Y ACCESO
==========================================-->

<div class="crud-section">

    <h3>

        <i class="fa-solid fa-shield-halved"></i>

        Rol y Acceso

    </h3>


    <div class="crud-grid">


        <!-- ROL -->

        <div class="crud-group">

            <label for="rolUsuario">

                Rol *

            </label>

            <select
                id="rolUsuario"
                name="id_rol"
                required>

                <option value="">Seleccione un rol...</option>

                <?php foreach ($roles as $rol): ?>
                    <option
                        value="<?= (int)$rol["id_rol"] ?>"
                        <?= ($usuarioEditar && (int)$usuarioEditar["id_rol"] === (int)$rol["id_rol"]) ? "selected" : "" ?>>
                        <?= limpiar($rol["nombre_rol"]) ?>
                    </option>
                <?php endforeach; ?>

            </select>

        </div>


        <!-- DESCRIPCIÓN -->

        <div class="crud-group">

            <label for="descripcionRol">

                Descripción del Rol

            </label>

            <input
                type="text"
                id="descripcionRol"
                placeholder="Se mostrará automáticamente"
                readonly>

        </div>

    </div>


    <div class="user-access-info">

        <div class="user-access-icon">

            <i class="fa-solid fa-shield-halved"></i>

        </div>


        <div class="user-access-text">

            <strong>
                Acceso del usuario
            </strong>

            <span id="accessDescription">

                Los permisos serán determinados
                por el rol seleccionado.

            </span>

        </div>

    </div>

</div>



<!--=========================================
=              INFORMACIÓN ADICIONAL
==========================================-->

<div class="crud-section">

    <h3>

        <i class="fa-solid fa-note-sticky"></i>

        Información Adicional

    </h3>


    <div class="form-checkbox">

        <input
            type="checkbox"
            id="forzarCambioContrasena">

        <label for="forzarCambioContrasena">

            Solicitar cambio de contraseña
            en el próximo inicio de sesión.

        </label>

    </div>


    <div class="crud-group">

        <label for="observacionesUsuario">

            Observaciones

        </label>

        <textarea
            id="observacionesUsuario"
            rows="4"
            placeholder="Ingrese observaciones adicionales del usuario..."></textarea>

    </div>

</div>



<!--=========================================
=              BOTONES
==========================================-->

<div class="crud-actions">

    <button
        type="button"
        class="btn-cancel"
        id="btnCancelarUsuario">

        Cancelar

    </button>


    <button
        type="submit"
        class="btn-save">

        <?= $modoLectura ? "Cerrar" : ($usuarioEditar ? "Actualizar Usuario" : "Guardar Usuario") ?>

    </button>

</div>


</form>

</div>

</div>


    </section>

</main>



<!--=========================================
=              JAVASCRIPT
==========================================-->

<script src="../assets/js/utils.js"></script>

<script>

/* =====================================================
   MODULO USUARIOS - INTERFAZ PHP
   ===================================================== */
document.addEventListener("DOMContentLoaded", function () {
    if (typeof cargarFecha === "function") cargarFecha();
    if (typeof inicializarCerrarSesion === "function") inicializarCerrarSesion();

    const modal = document.getElementById("userModal");
    const form = document.getElementById("userForm");
    const btnNuevo = document.getElementById("btnNuevoUsuario");
    const btnCerrar = document.getElementById("btnCloseModal");
    const btnCancelar = document.getElementById("btnCancelarUsuario");
    const search = document.getElementById("searchUser");
    const filterEstado = document.getElementById("filterEstado");
    const filterRol = document.getElementById("filterRol");
    const btnReset = document.getElementById("btnResetFilters");

    if (modal?.dataset.readonly === "true") {
        form.querySelectorAll("input:not([type=hidden]), select, textarea").forEach(campo => {
            if (campo.type === "password") {
                campo.closest(".crud-group")?.remove();
            } else if (campo.tagName === "SELECT") {
                campo.disabled = true;
            } else if (campo.type === "checkbox" || campo.type === "radio") {
                campo.disabled = true;
            } else {
                campo.readOnly = true;
            }
        });
        form.querySelector(".btn-save")?.addEventListener("click", e => e.preventDefault());
        const guardar = form.querySelector(".btn-save");
        if (guardar) {
            guardar.type = "button";
            guardar.addEventListener("click", cerrarModal);
            guardar.classList.add("btn-cancel");
            guardar.classList.remove("btn-save");
        }
        form.addEventListener("submit", e => e.preventDefault());
        form.querySelector("#btnCancelarUsuario").textContent = "Cerrar";
    }

    function abrirModal() {
        if (!modal) return;
        modal.classList.add("active");
        modal.setAttribute("aria-hidden", "false");
    }

    function cerrarModal() {
        if (!modal) return;
        if (modal.dataset.readonly === "true") {
            window.location.href = "usuarios.php";
            return;
        }
        modal.classList.remove("active");
        modal.setAttribute("aria-hidden", "true");
    }

    function prepararNuevo() {
        form.reset();
        document.getElementById("accionUsuario").value = "crear";
        document.getElementById("idUsuario").value = "";
        document.getElementById("idEmpleado").value = "";
        modal.querySelector(".crud-modal-header h2").innerHTML = '<i class="fa-solid fa-user-plus"></i> Nuevo Usuario';
        document.querySelector(".btn-save").textContent = "Guardar Usuario";
        document.getElementById("estadoUsuario").value = "Activo";
        abrirModal();
    }

    if (btnNuevo) btnNuevo.addEventListener("click", prepararNuevo);
    if (btnCerrar) btnCerrar.addEventListener("click", cerrarModal);
    if (btnCancelar) btnCancelar.addEventListener("click", cerrarModal);
    if (modal) modal.addEventListener("click", e => { if (e.target === modal) cerrarModal(); });
    document.addEventListener("keydown", e => { if (e.key === "Escape") cerrarModal(); });

    const pass = document.getElementById("contrasena");
    const confirmPass = document.getElementById("confirmarContrasena");
    if (form) form.addEventListener("submit", function (e) {
        if (pass.value !== confirmPass.value) {
            e.preventDefault();
            alert("Las contraseñas no coinciden.");
            confirmPass.focus();
        }
    });

    function filtrar() {
        const q = (search.value || "").toLowerCase();
        const estado = filterEstado.value.toLowerCase();
        const rol = filterRol.value.toLowerCase();
        document.querySelectorAll("#usersTableBody tr.user-row").forEach(row => {
            const texto = row.dataset.search.toLowerCase();
            const est = row.dataset.estado.toLowerCase();
            const r = row.dataset.rol.toLowerCase();
            row.style.display = (texto.includes(q) && (!estado || est === estado) && (!rol || r === rol)) ? "" : "none";
        });
    }
    if (search) search.addEventListener("input", filtrar);
    if (filterEstado) filterEstado.addEventListener("change", filtrar);
    if (filterRol) filterRol.addEventListener("change", filtrar);
    if (btnReset) btnReset.addEventListener("click", function(){ search.value=""; filterEstado.value=""; filterRol.value=""; filtrar(); });

<?php if ($usuarioEditar): ?>
    abrirModal();
<?php endif; ?>
});

</script>


</body>

</html>

