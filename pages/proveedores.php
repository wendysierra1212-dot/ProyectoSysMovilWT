<?php
require_once "../config/conexion.php";

$mensaje = "";
$tipoMensaje = "";

function limpiar($valor) {
    return htmlspecialchars($valor ?? "", ENT_QUOTES, "UTF-8");
}

/* =========================
   CREAR / EDITAR PROVEEDOR
========================= */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "";
    $id = isset($_POST["id_proveedor"]) ? (int)$_POST["id_proveedor"] : 0;

    $nit = trim($_POST["nit"] ?? "");
    $nombre = trim($_POST["nombre_proveedor"] ?? "");
    $contacto = trim($_POST["contacto"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");
    $ciudad = trim($_POST["ciudad"] ?? "");
    $estado = $_POST["estado"] ?? "Activo";

    $nit = ($nit === "") ? null : $nit;
    $contacto = ($contacto === "") ? null : $contacto;
    $correo = ($correo === "") ? null : $correo;
    $direccion = ($direccion === "") ? null : $direccion;
    $ciudad = ($ciudad === "") ? null : $ciudad;

    if ($nombre === "" || $telefono === "") {
        $mensaje = "El nombre del proveedor y el teléfono son obligatorios.";
        $tipoMensaje = "error";
    } elseif (!in_array($estado, ["Activo", "Inactivo"], true)) {
        $mensaje = "El estado seleccionado no es válido.";
        $tipoMensaje = "error";
    } else {
        if ($accion === "crear") {
            $sql = "INSERT INTO proveedores
                    (nit, nombre_proveedor, contacto, telefono, correo, direccion, ciudad, estado)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ? )";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param(
                    "ssssssss",
                    $nit,
                    $nombre,
                    $contacto,
                    $telefono,
                    $correo,
                    $direccion,
                    $ciudad,
                    $estado
                );

                if ($stmt->execute()) {
                    header("Location: proveedores.php?mensaje=Proveedor registrado correctamente&tipo=success");
                    exit;
                }

                if ($stmt->errno == 1062) {
                    $mensaje = "No se pudo registrar: el NIT o correo ya existe.";
                } else {
                    $mensaje = "Error al registrar el proveedor: " . $stmt->error;
                }
                $tipoMensaje = "error";
                $stmt->close();
            } else {
                $mensaje = "Error al preparar el registro: " . $conn->error;
                $tipoMensaje = "error";
            }
        }

        if ($accion === "editar" && $id > 0) {
            $sql = "UPDATE proveedores SET
                        nit = ?,
                        nombre_proveedor = ?,
                        contacto = ?,
                        telefono = ?,
                        correo = ?,
                        direccion = ?,
                        ciudad = ?,
                        estado = ?
                    WHERE id_proveedor = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param(
                    "ssssssssi",
                    $nit,
                    $nombre,
                    $contacto,
                    $telefono,
                    $correo,
                    $direccion,
                    $ciudad,
                    $estado,
                    $id
                );

                if ($stmt->execute()) {
                    header("Location: proveedores.php?mensaje=Proveedor actualizado correctamente&tipo=success");
                    exit;
                }

                if ($stmt->errno == 1062) {
                    $mensaje = "No se pudo actualizar: el NIT o correo ya existe.";
                } else {
                    $mensaje = "Error al actualizar el proveedor: " . $stmt->error;
                }
                $tipoMensaje = "error";
                $stmt->close();
            } else {
                $mensaje = "Error al preparar la actualización: " . $conn->error;
                $tipoMensaje = "error";
            }
        }
    }
}

/* =========================
   CAMBIAR ESTADO
========================= */
if (isset($_GET["cambiar_estado"])) {
    $id = (int)$_GET["cambiar_estado"];

    $stmt = $conn->prepare("UPDATE proveedores
                            SET estado = CASE
                                WHEN estado = 'Activo' THEN 'Inactivo'
                                ELSE 'Activo'
                            END
                            WHERE id_proveedor = ?");

    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: proveedores.php?mensaje=Estado del proveedor actualizado&tipo=success");
            exit;
        }
        $mensaje = "No fue posible cambiar el estado del proveedor.";
        $tipoMensaje = "error";
        $stmt->close();
    }
}

/* =========================
   ELIMINAR
========================= */
if (isset($_GET["eliminar"])) {
    $id = (int)$_GET["eliminar"];

    $stmt = $conn->prepare("DELETE FROM proveedores WHERE id_proveedor = ?");

    if ($stmt) {
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header("Location: proveedores.php?mensaje=Proveedor eliminado correctamente&tipo=success");
            exit;
        }

        if ($stmt->errno == 1451) {
            $mensaje = "No se puede eliminar este proveedor porque está relacionado con productos u otros registros del sistema. Puedes cambiarlo a Inactivo.";
        } else {
            $mensaje = "Error al eliminar el proveedor: " . $stmt->error;
        }
        $tipoMensaje = "error";
        $stmt->close();
    }
}

/* =========================
   MENSAJES REDIRECCIONADOS
========================= */
if (isset($_GET["mensaje"])) {
    $mensaje = $_GET["mensaje"];
    $tipoMensaje = $_GET["tipo"] ?? "success";
}

/* =========================
   CONSULTAS
========================= */
$resultado = $conn->query("SELECT
    id_proveedor,
    nit,
    nombre_proveedor,
    contacto,
    telefono,
    correo,
    direccion,
    ciudad,
    estado
FROM proveedores
ORDER BY id_proveedor DESC");

$proveedores = [];
if ($resultado) {
    while ($fila = $resultado->fetch_assoc()) {
        $proveedores[] = $fila;
    }
}

$totalProveedores = count($proveedores);
$proveedoresActivos = 0;
$proveedoresInactivos = 0;

foreach ($proveedores as $proveedor) {
    if ($proveedor["estado"] === "Activo") {
        $proveedoresActivos++;
    } else {
        $proveedoresInactivos++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Proveedores | SYSMOVILWT</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<link rel="stylesheet" href="../assets/css/styles.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/crud.css">

<style>
/* =========================================================
   TABLA DE PROVEEDORES - DISTRIBUCIÓN FINAL
   ========================================================= */
.crud-table{
    width:100%;
    table-layout:fixed;
}

.crud-table th,
.crud-table td{
    box-sizing:border-box;
    vertical-align:middle;
}

/* Distribución: 100% exacto */
.crud-table th:nth-child(1),
.crud-table td:nth-child(1){width:17%;}

.crud-table th:nth-child(2),
.crud-table td:nth-child(2){width:13%;}

.crud-table th:nth-child(3),
.crud-table td:nth-child(3){width:20%;}

.crud-table th:nth-child(4),
.crud-table td:nth-child(4){width:11%;}

.crud-table th:nth-child(5),
.crud-table td:nth-child(5){width:12%;}

.crud-table th:nth-child(6),
.crud-table td:nth-child(6){width:8%;}

.crud-table th:nth-child(7),
.crud-table td:nth-child(7){width:19%;}

/* Empresa: permite varias líneas, pero no corta palabras innecesariamente */
.crud-table td:nth-child(1){
    white-space:normal;
    overflow-wrap:break-word;
    word-break:normal;
}

/* NIT, ciudad, teléfono y estado: mostrar completos */
.crud-table td:nth-child(2),
.crud-table td:nth-child(4),
.crud-table td:nth-child(5),
.crud-table td:nth-child(6){
    white-space:nowrap;
    overflow:visible;
    text-overflow:clip;
}

/* Contacto: nombre y correo pueden acomodarse en varias líneas */
.crud-table td:nth-child(3){
    white-space:normal;
    overflow-wrap:break-word;
    word-break:normal;
}

.crud-table td:nth-child(3) small{
    display:block;
    line-height:1.35;
    overflow-wrap:anywhere;
    word-break:break-word;
}

/* Acciones: los cuatro botones siempre dentro de su columna */
.crud-table th:last-child,
.crud-table td:last-child{
    padding-left:6px;
    padding-right:6px;
    white-space:nowrap;
    overflow:visible;
}

.provider-actions{
    display:flex;
    align-items:center;
    justify-content:flex-start;
    gap:5px;
    flex-wrap:nowrap;
    white-space:nowrap;
}

.provider-actions a{
    width:32px;
    height:32px;
    min-width:32px;
    flex:0 0 32px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:8px;
    text-decoration:none;
    color:#fff;
    transition:.2s;
}

.provider-actions a:hover{
    transform:translateY(-2px);
}

.btn-view-provider{background:#2563eb;}
.btn-edit-provider{background:#f59e0b;}
.btn-state-provider{background:#dc2626;}
.btn-delete-provider{background:#64748b;}

.provider-detail{padding:28px;}
.provider-detail-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:15px;}
.provider-detail-item{background:#f8fafc;padding:15px;border-radius:10px;}
.provider-detail-item span{display:block;font-size:12px;color:#64748b;margin-bottom:4px;}
.provider-detail-item strong{color:#172554;word-break:break-word;}

@media(max-width:900px){
    .crud-table{min-width:1000px;}
    .crud-table-card{overflow-x:auto;}
}

@media(max-width:700px){
    .provider-detail-grid{grid-template-columns:1fr;}
}
</style>
</head>

<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <img src="../assets/img/logo/logowt.png" alt="Logo WT SERVIPHONE" class="logo-sidebar">
        <div class="logo-text">
            <h2>SYSMOVILWT</h2>
            <span>Sistema de Gestión Empresarial</span>
        </div>
    </div>

    <nav class="sidebar-menu">
        <ul>
            <li><a href="dashboard.html"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
            <li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i><span>Inventario</span></a></li>
            <li><a href="ventas.html"><i class="fa-solid fa-cart-shopping"></i><span>Ventas</span></a></li>
            <li><a href="compras.html"><i class="fa-solid fa-bag-shopping"></i><span>Compras</span></a></li>
            <li><a href="clientes.php"><i class="fa-solid fa-users"></i><span>Clientes</span></a></li>
            <li class="active"><a href="proveedores.php"><i class="fa-solid fa-truck"></i><span>Proveedores</span></a></li>
            <li><a href="usuarios.html"><i class="fa-solid fa-user-shield"></i><span>Usuarios</span></a></li>
            <li><a href="serviciotecnico.html"><i class="fa-solid fa-screwdriver-wrench"></i><span>Servicio Técnico</span></a></li>
            <li><a href="reportes.html"><i class="fa-solid fa-chart-column"></i><span>Reportes</span></a></li>
            <li><a href="configuracion.html"><i class="fa-solid fa-gear"></i><span>Configuración</span></a></li>
        </ul>
    </nav>

    <button class="logout-button" id="btnLogout">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span>Cerrar sesión</span>
    </button>
</aside>

<header class="topbar">
    <div class="topbar-left">
        <button class="menu-toggle"><i class="fa-solid fa-bars"></i></button>
        <div class="topbar-title">
            <h1>Proveedores</h1>
            <span>Gestión de proveedores</span>
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
            <div class="profile-avatar">WT</div>
            <div class="profile-info">
                <strong>Leonardo</strong>
                <span>Administrador</span>
            </div>
        </div>
    </div>
</header>

<main class="dashboard-main">

<?php if ($mensaje !== ""): ?>
<div class="crud-message <?= $tipoMensaje === "success" ? "success" : "error" ?>"
     style="margin:20px 0;padding:14px 18px;border-radius:10px;font-weight:600;
     background:<?= $tipoMensaje === "success" ? "#dcfce7" : "#fee2e2" ?>;
     color:<?= $tipoMensaje === "success" ? "#166534" : "#991b1b" ?>;
     border:1px solid <?= $tipoMensaje === "success" ? "#86efac" : "#fecaca" ?>;">
    <i class="fa-solid <?= $tipoMensaje === "success" ? "fa-circle-check" : "fa-circle-exclamation" ?>"></i>
    <?= limpiar($mensaje) ?>
</div>
<?php endif; ?>

<section class="crud-container">

<section class="crud-header">
    <div class="crud-title">
        <h2><i class="fa-solid fa-truck"></i> Gestión de Proveedores</h2>
        <p>Administra la información de los proveedores registrados en WT SERVIPHONE.</p>
    </div>

    <button id="btnNuevoProveedor" class="btn-new" type="button">
        <i class="fa-solid fa-truck-medical"></i>
        Nuevo Proveedor
    </button>
</section>

<section class="crud-stats">

    <div class="crud-card">
        <div class="crud-card-icon bg-red"><i class="fa-solid fa-truck"></i></div>
        <div class="crud-card-info">
            <span>Proveedores Registrados</span>
            <strong><?= $totalProveedores ?></strong>
        </div>
    </div>

    <div class="crud-card">
        <div class="crud-card-icon bg-green"><i class="fa-solid fa-circle-check"></i></div>
        <div class="crud-card-info">
            <span>Proveedores Activos</span>
            <strong><?= $proveedoresActivos ?></strong>
        </div>
    </div>

    <div class="crud-card">
        <div class="crud-card-icon bg-orange"><i class="fa-solid fa-circle-xmark"></i></div>
        <div class="crud-card-info">
            <span>Proveedores Inactivos</span>
            <strong><?= $proveedoresInactivos ?></strong>
        </div>
    </div>

    <div class="crud-card">
        <div class="crud-card-icon bg-blue"><i class="fa-solid fa-sack-dollar"></i></div>
        <div class="crud-card-info">
            <span>Compras Registradas</span>
            <strong>0</strong>
        </div>
    </div>

</section>

<section class="crud-toolbar">

    <div class="crud-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchProveedor"
               placeholder="Buscar por empresa, NIT, contacto, teléfono o correo...">
    </div>

    <select id="filterEstado">
        <option value="">Todos los estados</option>
        <option value="Activo">Activo</option>
        <option value="Inactivo">Inactivo</option>
    </select>

    <button id="btnResetFilters" class="btn-reset" title="Limpiar filtros" type="button">
        <i class="fa-solid fa-rotate-right"></i>
    </button>

</section>

<section class="crud-table-card">
<table class="crud-table">
<colgroup>
    <col style="width:18%">
    <col style="width:11%">
    <col style="width:20%">
    <col style="width:12%">
    <col style="width:12%">
    <col style="width:9%">
    <col style="width:18%">
</colgroup>
<thead>
<tr>
    <th>Empresa</th>
    <th>NIT</th>
    <th>Contacto</th>
    <th>Ciudad</th>
    <th>Teléfono</th>
    <th>Estado</th>
    <th>Acciones</th>
</tr>
</thead>

<tbody id="suppliersTableBody">

<?php if (!empty($proveedores)): ?>

<?php foreach ($proveedores as $proveedor): ?>

<tr>

<td>
    <strong><?= limpiar($proveedor["nombre_proveedor"]) ?></strong><br>
    <small>Proveedor</small>
</td>

<td><?= !empty($proveedor["nit"]) ? limpiar($proveedor["nit"]) : "—" ?></td>

<td>
    <strong><?= !empty($proveedor["contacto"]) ? limpiar($proveedor["contacto"]) : "—" ?></strong>
    <?php if (!empty($proveedor["correo"])): ?>
        <br><small><?= limpiar($proveedor["correo"]) ?></small>
    <?php endif; ?>
</td>

<td><?= !empty($proveedor["ciudad"]) ? limpiar($proveedor["ciudad"]) : "—" ?></td>

<td><?= limpiar($proveedor["telefono"]) ?></td>

<td>
    <span class="status-badge <?= $proveedor["estado"] === "Activo" ? "active" : "inactive" ?>">
        <?= limpiar($proveedor["estado"]) ?>
    </span>
</td>

<td>
<div class="provider-actions">

<a href="#" class="btn-view-provider" title="Ver proveedor"
   data-nombre="<?= limpiar($proveedor["nombre_proveedor"]) ?>"
   data-nit="<?= limpiar($proveedor["nit"]) ?>"
   data-contacto="<?= limpiar($proveedor["contacto"]) ?>"
   data-telefono="<?= limpiar($proveedor["telefono"]) ?>"
   data-correo="<?= limpiar($proveedor["correo"]) ?>"
   data-direccion="<?= limpiar($proveedor["direccion"]) ?>"
   data-ciudad="<?= limpiar($proveedor["ciudad"]) ?>"
   data-estado="<?= limpiar($proveedor["estado"]) ?>">
   <i class="fa-solid fa-eye"></i>
</a>

<a href="#" class="btn-edit-provider" title="Editar proveedor"
   data-id="<?= $proveedor["id_proveedor"] ?>"
   data-nit="<?= limpiar($proveedor["nit"]) ?>"
   data-nombre="<?= limpiar($proveedor["nombre_proveedor"]) ?>"
   data-contacto="<?= limpiar($proveedor["contacto"]) ?>"
   data-telefono="<?= limpiar($proveedor["telefono"]) ?>"
   data-correo="<?= limpiar($proveedor["correo"]) ?>"
   data-direccion="<?= limpiar($proveedor["direccion"]) ?>"
   data-ciudad="<?= limpiar($proveedor["ciudad"]) ?>"
   data-estado="<?= limpiar($proveedor["estado"]) ?>">
   <i class="fa-solid fa-pen"></i>
</a>

<a href="proveedores.php?cambiar_estado=<?= $proveedor["id_proveedor"] ?>"
   class="btn-state-provider" title="Cambiar estado"
   onclick="return confirm('¿Desea <?= $proveedor["estado"] === "Activo" ? "inactivar" : "activar" ?> este proveedor?');">
   <i class="fa-solid fa-power-off"></i>
</a>

<a href="proveedores.php?eliminar=<?= $proveedor["id_proveedor"] ?>"
   class="btn-delete-provider" title="Eliminar proveedor"
   onclick="return confirm('¿Está seguro de eliminar este proveedor? Esta acción no se puede deshacer.');">
   <i class="fa-solid fa-trash"></i>
</a>

</div>
</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr class="providers-empty">
<td colspan="7">
<div class="empty-state">
    <i class="fa-solid fa-truck"></i>
    <h3>No hay proveedores registrados</h3>
    <p>Haz clic en <strong>Nuevo Proveedor</strong> para comenzar.</p>
</div>
</td>
</tr>

<?php endif; ?>

</tbody>
</table>
</section>

</section>
</main>

<!-- MODAL CREAR / EDITAR -->
<div id="supplierModal" class="crud-modal">

<div class="crud-modal-content">

<div class="crud-modal-header">
<h2 id="supplierModalTitle">
    <i class="fa-solid fa-truck-medical"></i> Nuevo Proveedor
</h2>

<button id="btnCloseModal" class="crud-close" type="button">
    <i class="fa-solid fa-xmark"></i>
</button>
</div>

<form id="supplierForm" class="crud-form" method="POST" action="proveedores.php">

<input type="hidden" id="accionProveedor" name="accion" value="crear">
<input type="hidden" id="idProveedor" name="id_proveedor" value="">

<div class="crud-section">
<h3>Información del Proveedor</h3>

<div class="crud-grid">

<div class="crud-group">
<label for="nombre_proveedor">Empresa *</label>
<input type="text" id="nombre_proveedor" name="nombre_proveedor" maxlength="100" required>
</div>

<div class="crud-group">
<label for="nit">NIT</label>
<input type="text" id="nit" name="nit" maxlength="20">
</div>

<div class="crud-group">
<label for="contacto">Persona de contacto</label>
<input type="text" id="contacto" name="contacto" maxlength="100">
</div>

<div class="crud-group">
<label for="telefono">Teléfono *</label>
<input type="text" id="telefono" name="telefono" maxlength="20" required>
</div>

</div>
</div>

<div class="crud-section">
<h3>Información de Contacto</h3>

<div class="crud-grid">

<div class="crud-group">
<label for="correo">Correo Electrónico</label>
<input type="email" id="correo" name="correo" maxlength="100">
</div>

<div class="crud-group">
<label for="ciudad">Ciudad</label>
<input type="text" id="ciudad" name="ciudad" maxlength="100">
</div>

<div class="crud-group" style="grid-column:1 / -1;">
<label for="direccion">Dirección</label>
<input type="text" id="direccion" name="direccion" maxlength="150">
</div>

<div class="crud-group">
<label for="estado">Estado</label>
<select id="estado" name="estado">
    <option value="Activo">Activo</option>
    <option value="Inactivo">Inactivo</option>
</select>
</div>

</div>
</div>

<div class="crud-actions">
<button type="button" class="btn-cancel" id="btnCancelarProveedor">Cancelar</button>
<button type="submit" class="btn-save">Guardar Proveedor</button>
</div>

</form>
</div>
</div>

<!-- MODAL VER -->
<div id="viewProviderModal" class="crud-modal">

<div class="crud-modal-content">

<div class="crud-modal-header">
<h2><i class="fa-solid fa-truck"></i> Información del Proveedor</h2>

<button id="btnCloseViewModal" class="crud-close" type="button">
    <i class="fa-solid fa-xmark"></i>
</button>
</div>

<div class="provider-detail">

<div class="provider-detail-grid">

<div class="provider-detail-item"><span>Empresa</span><strong id="viewNombre">—</strong></div>
<div class="provider-detail-item"><span>NIT</span><strong id="viewNit">—</strong></div>
<div class="provider-detail-item"><span>Contacto</span><strong id="viewContacto">—</strong></div>
<div class="provider-detail-item"><span>Teléfono</span><strong id="viewTelefono">—</strong></div>
<div class="provider-detail-item"><span>Correo</span><strong id="viewCorreo">—</strong></div>
<div class="provider-detail-item"><span>Ciudad</span><strong id="viewCiudad">—</strong></div>
<div class="provider-detail-item"><span>Dirección</span><strong id="viewDireccion">—</strong></div>
<div class="provider-detail-item"><span>Estado</span><strong id="viewEstado">—</strong></div>

</div>
</div>
</div>
</div>

<script src="../assets/js/utils.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    if (typeof cargarFecha === "function") cargarFecha();
    if (typeof inicializarCerrarSesion === "function") inicializarCerrarSesion();

    const btnNuevo = document.getElementById("btnNuevoProveedor");
    const modal = document.getElementById("supplierModal");
    const btnCerrar = document.getElementById("btnCloseModal");
    const btnCancelar = document.getElementById("btnCancelarProveedor");
    const formulario = document.getElementById("supplierForm");
    const buscador = document.getElementById("searchProveedor");
    const filtroEstado = document.getElementById("filterEstado");
    const btnReset = document.getElementById("btnResetFilters");

    function abrirNuevo() {
        formulario.reset();
        document.getElementById("accionProveedor").value = "crear";
        document.getElementById("idProveedor").value = "";
        document.getElementById("estado").value = "Activo";

        document.getElementById("supplierModalTitle").innerHTML =
            '<i class="fa-solid fa-truck-medical"></i> Nuevo Proveedor';

        formulario.querySelector(".btn-save").innerHTML = "Guardar Proveedor";
        modal.style.display = "flex";
    }

    function cerrarModal() {
        modal.style.display = "none";
        formulario.reset();
        document.getElementById("accionProveedor").value = "crear";
        document.getElementById("idProveedor").value = "";

        document.getElementById("supplierModalTitle").innerHTML =
            '<i class="fa-solid fa-truck-medical"></i> Nuevo Proveedor';

        formulario.querySelector(".btn-save").innerHTML = "Guardar Proveedor";
    }

    if (btnNuevo) btnNuevo.addEventListener("click", abrirNuevo);
    if (btnCerrar) btnCerrar.addEventListener("click", cerrarModal);
    if (btnCancelar) btnCancelar.addEventListener("click", cerrarModal);

    if (modal) {
        modal.addEventListener("click", function (e) {
            if (e.target === modal) cerrarModal();
        });
    }

    document.querySelectorAll(".btn-edit-provider").forEach(function (boton) {
        boton.addEventListener("click", function (e) {
            e.preventDefault();

            document.getElementById("idProveedor").value = boton.dataset.id || "";
            document.getElementById("nit").value = boton.dataset.nit || "";
            document.getElementById("nombre_proveedor").value = boton.dataset.nombre || "";
            document.getElementById("contacto").value = boton.dataset.contacto || "";
            document.getElementById("telefono").value = boton.dataset.telefono || "";
            document.getElementById("correo").value = boton.dataset.correo || "";
            document.getElementById("direccion").value = boton.dataset.direccion || "";
            document.getElementById("ciudad").value = boton.dataset.ciudad || "";
            document.getElementById("estado").value = boton.dataset.estado || "Activo";

            document.getElementById("accionProveedor").value = "editar";

            document.getElementById("supplierModalTitle").innerHTML =
                '<i class="fa-solid fa-truck"></i> Editar Proveedor';

            formulario.querySelector(".btn-save").innerHTML = "Guardar Cambios";
            modal.style.display = "flex";
        });
    });

    const modalVer = document.getElementById("viewProviderModal");
    const btnCerrarVer = document.getElementById("btnCloseViewModal");

    document.querySelectorAll(".btn-view-provider").forEach(function (boton) {
        boton.addEventListener("click", function (e) {
            e.preventDefault();

            document.getElementById("viewNombre").textContent = boton.dataset.nombre || "—";
            document.getElementById("viewNit").textContent = boton.dataset.nit || "—";
            document.getElementById("viewContacto").textContent = boton.dataset.contacto || "—";
            document.getElementById("viewTelefono").textContent = boton.dataset.telefono || "—";
            document.getElementById("viewCorreo").textContent = boton.dataset.correo || "—";
            document.getElementById("viewCiudad").textContent = boton.dataset.ciudad || "—";
            document.getElementById("viewDireccion").textContent = boton.dataset.direccion || "—";
            document.getElementById("viewEstado").textContent = boton.dataset.estado || "—";

            modalVer.style.display = "flex";
        });
    });

    if (btnCerrarVer) {
        btnCerrarVer.addEventListener("click", function () {
            modalVer.style.display = "none";
        });
    }

    if (modalVer) {
        modalVer.addEventListener("click", function (e) {
            if (e.target === modalVer) modalVer.style.display = "none";
        });
    }

    function filtrarTabla() {
        const texto = buscador.value.trim().toLowerCase();
        const estado = filtroEstado.value.toLowerCase();

        document.querySelectorAll("#suppliersTableBody tr").forEach(function (fila) {

            if (fila.classList.contains("providers-empty")) return;

            const contenido = fila.textContent.toLowerCase();

            const coincideTexto = texto === "" || contenido.includes(texto);
            const coincideEstado = estado === "" || contenido.includes(estado);

            fila.style.display = coincideTexto && coincideEstado ? "" : "none";
        });
    }

    if (buscador) buscador.addEventListener("input", filtrarTabla);
    if (filtroEstado) filtroEstado.addEventListener("change", filtrarTabla);

    if (btnReset) {
        btnReset.addEventListener("click", function () {
            buscador.value = "";
            filtroEstado.value = "";
            filtrarTabla();
        });
    }
});
</script>

</body>
</html>
