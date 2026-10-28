<?php
require_once "../config/conexion.php";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset("utf8mb4");

function h($v): string { return htmlspecialchars((string)($v ?? ""), ENT_QUOTES, "UTF-8"); }
function cop($v): string { return "$" . number_format((float)$v, 0, ",", "."); }
function redirectCompra($m): void { header("Location: compras.php?mensaje=" . urlencode($m)); exit; }

$mensaje = "";
$tipoMensaje = "success";

/* CREAR / EDITAR */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $accion = $_POST["accion"] ?? "crear";
    $idCompra = (int)($_POST["id_compra"] ?? 0);
    $idProveedor = (int)($_POST["id_proveedor"] ?? 0);
    $idEmpleado = (int)($_POST["id_empleado"] ?? 0);
    $factura = trim($_POST["numero_factura_compra"] ?? "");
    $fecha = trim($_POST["fecha_compra"] ?? "");
    $observaciones = trim($_POST["observaciones"] ?? "");
    $factura = $factura === "" ? null : $factura;
    $observaciones = $observaciones === "" ? null : $observaciones;
    $items = $_POST["productos"] ?? [];
    $lineas = [];

    foreach ($items as $item) {
        $pid = (int)($item["id_producto"] ?? 0);
        $cantidad = (int)($item["cantidad"] ?? 0);
        $precio = (float)($item["precio_unitario"] ?? 0);
        if ($pid === 0 && $cantidad === 0 && $precio == 0) continue;
        if ($pid <= 0 || $cantidad <= 0 || $precio < 0) {
            $mensaje = "Revisa los productos, las cantidades y los costos.";
            $tipoMensaje = "error";
            break;
        }
        $lineas[] = [
            "id_producto" => $pid,
            "cantidad" => $cantidad,
            "precio_unitario" => round($precio, 2),
            "subtotal" => round($cantidad * $precio, 2)
        ];
    }

    if ($mensaje === "" && ($idProveedor <= 0 || $idEmpleado <= 0 || $fecha === "" || !$lineas)) {
        $mensaje = "Completa la fecha, el proveedor, el empleado y agrega al menos un producto.";
        $tipoMensaje = "error";
    }

    if ($mensaje === "") {
        $total = round(array_sum(array_column($lineas, "subtotal")), 2);
        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare("SELECT id_proveedor FROM proveedores WHERE id_proveedor=? AND estado='Activo' FOR UPDATE");
            $stmt->bind_param("i", $idProveedor);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) throw new RuntimeException("El proveedor no existe o está inactivo.");
            $stmt->close();

            $stmt = $conn->prepare("SELECT id_empleado FROM empleados WHERE id_empleado=? FOR UPDATE");
            $stmt->bind_param("i", $idEmpleado);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) throw new RuntimeException("El empleado seleccionado no existe.");
            $stmt->close();

            if ($accion === "editar" && $idCompra > 0) {
                $stmt = $conn->prepare("SELECT id_compra FROM compras WHERE id_compra=? FOR UPDATE");
                $stmt->bind_param("i", $idCompra);
                $stmt->execute();
                if (!$stmt->get_result()->fetch_assoc()) throw new RuntimeException("La compra que intentas editar no existe.");
                $stmt->close();

                $stmt = $conn->prepare("SELECT id_producto, cantidad FROM detalle_compra WHERE id_compra=? FOR UPDATE");
                $stmt->bind_param("i", $idCompra);
                $stmt->execute();
                $res = $stmt->get_result();
                $anteriores = [];
                while ($r = $res->fetch_assoc()) {
                    $pid = (int)$r["id_producto"];
                    $anteriores[$pid] = ($anteriores[$pid] ?? 0) + (int)$r["cantidad"];
                }
                $stmt->close();

                foreach ($anteriores as $pid => $cantidadAnterior) {
                    $stmt = $conn->prepare("SELECT stock FROM productos WHERE id_producto=? FOR UPDATE");
                    $stmt->bind_param("i", $pid);
                    $stmt->execute();
                    $p = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    if (!$p || (int)$p["stock"] < $cantidadAnterior) {
                        throw new RuntimeException("No se puede editar: el stock del producto ID $pid no permite revertir la compra anterior. Revisa movimientos posteriores.");
                    }
                    $stmt = $conn->prepare("UPDATE productos SET stock=stock-? WHERE id_producto=?");
                    $stmt->bind_param("ii", $cantidadAnterior, $pid);
                    $stmt->execute();
                    $stmt->close();
                }

                $stmt = $conn->prepare("UPDATE compras SET numero_factura_compra=?, fecha_compra=?, total=?, observaciones=?, id_proveedor=?, id_empleado=? WHERE id_compra=?");
                $stmt->bind_param("ssdsiii", $factura, $fecha, $total, $observaciones, $idProveedor, $idEmpleado, $idCompra);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("DELETE FROM detalle_compra WHERE id_compra=?");
                $stmt->bind_param("i", $idCompra);
                $stmt->execute();
                $stmt->close();
                $idGuardado = $idCompra;
            } else {
                $stmt = $conn->prepare("INSERT INTO compras (numero_factura_compra, fecha_compra, total, observaciones, id_proveedor, id_empleado) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdsii", $factura, $fecha, $total, $observaciones, $idProveedor, $idEmpleado);
                $stmt->execute();
                $idGuardado = (int)$conn->insert_id;
                $stmt->close();
            }

            $qProducto = $conn->prepare("SELECT id_producto FROM productos WHERE id_producto=? FOR UPDATE");
            $qDetalle = $conn->prepare("INSERT INTO detalle_compra (id_compra, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $qStock = $conn->prepare("UPDATE productos SET stock=stock+?, precio_compra=? WHERE id_producto=?");

            foreach ($lineas as $l) {
                $pid = $l["id_producto"];
                $cantidad = $l["cantidad"];
                $precio = $l["precio_unitario"];
                $subtotal = $l["subtotal"];

                $qProducto->bind_param("i", $pid);
                $qProducto->execute();
                if (!$qProducto->get_result()->fetch_assoc()) throw new RuntimeException("No existe el producto ID $pid.");

                $qDetalle->bind_param("iiidd", $idGuardado, $pid, $cantidad, $precio, $subtotal);
                $qDetalle->execute();
                $qStock->bind_param("idi", $cantidad, $precio, $pid);
                $qStock->execute();
            }
            $qProducto->close();
            $qDetalle->close();
            $qStock->close();
            $conn->commit();
            redirectCompra($accion === "editar" ? "editada" : "creada");
        } catch (Throwable $e) {
            $conn->rollback();
            $mensaje = "No se pudo guardar la compra: " . $e->getMessage();
            $tipoMensaje = "error";
        }
    }
}

/* ELIMINAR Y REVERTIR STOCK */
if (isset($_GET["eliminar"])) {
    $idEliminar = (int)$_GET["eliminar"];
    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare("SELECT id_compra FROM compras WHERE id_compra=? FOR UPDATE");
        $stmt->bind_param("i", $idEliminar);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new RuntimeException("La compra no existe.");
        $stmt->close();

        $stmt = $conn->prepare("SELECT id_producto, cantidad FROM detalle_compra WHERE id_compra=?");
        $stmt->bind_param("i", $idEliminar);
        $stmt->execute();
        $res = $stmt->get_result();
        $cantidades = [];
        while ($r = $res->fetch_assoc()) {
            $pid = (int)$r["id_producto"];
            $cantidades[$pid] = ($cantidades[$pid] ?? 0) + (int)$r["cantidad"];
        }
        $stmt->close();

        foreach ($cantidades as $pid => $cantidad) {
            $stmt = $conn->prepare("SELECT stock FROM productos WHERE id_producto=? FOR UPDATE");
            $stmt->bind_param("i", $pid);
            $stmt->execute();
            $p = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$p || (int)$p["stock"] < $cantidad) {
                throw new RuntimeException("No se puede eliminar: el stock del producto ID $pid es menor que las unidades que se deben descontar.");
            }
            $stmt = $conn->prepare("UPDATE productos SET stock=stock-? WHERE id_producto=?");
            $stmt->bind_param("ii", $cantidad, $pid);
            $stmt->execute();
            $stmt->close();
        }
        $stmt = $conn->prepare("DELETE FROM detalle_compra WHERE id_compra=?");
        $stmt->bind_param("i", $idEliminar);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM compras WHERE id_compra=?");
        $stmt->bind_param("i", $idEliminar);
        $stmt->execute();
        $stmt->close();
        $conn->commit();
        redirectCompra("eliminada");
    } catch (Throwable $e) {
        $conn->rollback();
        $mensaje = "No se pudo eliminar la compra: " . $e->getMessage();
        $tipoMensaje = "error";
    }
}

/* DATOS DE APOYO */
if (isset($_GET["mensaje"])) {
    $m = [
        "creada" => "Compra registrada correctamente y existencias actualizadas.",
        "editada" => "Compra actualizada correctamente y existencias recalculadas.",
        "eliminada" => "Compra eliminada y existencias ajustadas."
    ];
    if (isset($m[$_GET["mensaje"]])) { $mensaje = $m[$_GET["mensaje"]]; $tipoMensaje = "success"; }
}
$proveedores = $conn->query("SELECT id_proveedor, nombre_proveedor FROM proveedores WHERE estado='Activo' ORDER BY nombre_proveedor")->fetch_all(MYSQLI_ASSOC);
$empleados = $conn->query("SELECT id_empleado, nombres, apellidos, cargo FROM empleados WHERE estado='Activo' ORDER BY nombres, apellidos")->fetch_all(MYSQLI_ASSOC);
$productos = $conn->query("SELECT id_producto, codigo_producto, nombre_producto, marca, modelo, precio_compra, stock FROM productos ORDER BY nombre_producto")->fetch_all(MYSQLI_ASSOC);

$buscar = trim($_GET["buscar"] ?? "");
$idProveedorFiltro = (int)($_GET["proveedor"] ?? 0);
$sql = "SELECT c.id_compra,c.numero_factura_compra,c.fecha_compra,c.total,c.observaciones,c.id_proveedor,c.id_empleado,p.nombre_proveedor,e.nombres AS empleado_nombres,e.apellidos AS empleado_apellidos,COUNT(dc.id_detalle_compra) AS lineas,COALESCE(SUM(dc.cantidad),0) AS unidades
        FROM compras c INNER JOIN proveedores p ON p.id_proveedor=c.id_proveedor INNER JOIN empleados e ON e.id_empleado=c.id_empleado LEFT JOIN detalle_compra dc ON dc.id_compra=c.id_compra WHERE 1=1";
$params = []; $types = "";
if ($buscar !== "") {
    $sql .= " AND (CAST(c.id_compra AS CHAR) LIKE ? OR c.numero_factura_compra LIKE ? OR p.nombre_proveedor LIKE ? OR e.nombres LIKE ? OR e.apellidos LIKE ?)";
    $like = "%$buscar%"; array_push($params, $like, $like, $like, $like, $like); $types .= "sssss";
}
if ($idProveedorFiltro > 0) { $sql .= " AND c.id_proveedor=?"; $params[] = $idProveedorFiltro; $types .= "i"; }
$sql .= " GROUP BY c.id_compra,c.numero_factura_compra,c.fecha_compra,c.total,c.observaciones,c.id_proveedor,c.id_empleado,p.nombre_proveedor,e.nombres,e.apellidos ORDER BY c.id_compra DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$compras = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$detalles = [];
$res = $conn->query("SELECT id_compra,id_producto,cantidad,precio_unitario,subtotal FROM detalle_compra ORDER BY id_detalle_compra");
while ($r = $res->fetch_assoc()) $detalles[(int)$r["id_compra"]][] = $r;

$totalCompras = count($compras);
$totalValor = array_sum(array_map(fn($c) => (float)$c["total"], $compras));
$hoy = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Compras | SYSMOVILWT</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/styles.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/compras.css">
<style>
.purchase-table-wrapper{width:100%;overflow-x:auto}.purchase-table{width:100%;min-width:900px}
.purchase-actions-cell{display:flex;gap:6px;white-space:nowrap}.purchase-actions-cell a,.purchase-actions-cell button{width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:6px;cursor:pointer;text-decoration:none}
.btn-view-purchase{background:#e0f2fe;color:#0369a1}.btn-edit-purchase{background:#fef3c7;color:#92400e}.btn-delete-purchase{background:#fee2e2;color:#b91c1c}
.purchase-alert{margin:16px 0;padding:12px 15px;border-radius:8px}.purchase-alert.success{background:#dcfce7;color:#166534}.purchase-alert.error{background:#fee2e2;color:#991b1b}
.purchase-modal{display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,23,42,.65);align-items:center;justify-content:center;padding:16px}.purchase-modal.show{display:flex}
.purchase-modal-content{width:100%;max-width:1000px;max-height:92vh;overflow:auto;background:#fff;border-radius:14px;box-shadow:0 20px 50px #0003}
.purchase-modal-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #e5e7eb}.purchase-modal-header h2{margin:0;color:#172554;font-size:20px}.purchase-modal-close{border:0;background:transparent;font-size:22px;cursor:pointer}
.purchase-form,.purchase-detail-view{padding:22px 24px}.purchase-section{margin-bottom:22px}.purchase-section h3{font-size:16px;color:#172554;margin:0 0 14px}
.purchase-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.purchase-form .form-group{display:flex;flex-direction:column;gap:7px}
.purchase-form label{font-size:13px;font-weight:600;color:#334155}.purchase-form input,.purchase-form select,.purchase-form textarea{width:100%;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:7px;padding:10px 12px;font:inherit}
.purchase-products{overflow-x:auto}.purchase-detail-table,.purchase-detail-view table{width:100%;min-width:650px;border-collapse:collapse}.purchase-detail-table th,.purchase-detail-table td,.purchase-detail-view th,.purchase-detail-view td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left}
.purchase-total{display:flex;justify-content:flex-end;gap:20px;padding:16px;background:#f8fafc;border-radius:8px;font-size:18px}.purchase-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px}
.purchase-actions button{padding:10px 18px;border-radius:7px;cursor:pointer;font-weight:600}.btn-cancel{border:1px solid #cbd5e1;background:#f8fafc}.btn-save{border:0;background:#dc2626;color:white}.btn-delete-row{border:0;background:#fee2e2;color:#b91c1c;border-radius:6px;padding:8px;cursor:pointer}
.purchase-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin:18px 0}.purchase-summary-card{background:white;border:1px solid #e5e7eb;border-radius:10px;padding:16px}.purchase-summary-card span{display:block;color:#64748b;font-size:13px;margin-bottom:6px}.purchase-summary-card strong{font-size:22px;color:#172554}
.purchase-filter-form{display:flex;align-items:center;gap:12px;flex-wrap:wrap}.purchase-filter-form .purchase-search{flex:1;min-width:230px}.purchase-filter-form select{min-width:190px}.purchase-table td small{color:#64748b}
@media(max-width:720px){.purchase-grid,.purchase-summary{grid-template-columns:1fr}.purchase-form{padding:16px}.purchase-modal{padding:8px}}
</style>
</head>
<body>
<aside class="sidebar">
<div class="sidebar-logo"><img src="../assets/img/logo/logowt.png" alt="Logo WT SERVIPHONE" class="logo-sidebar"><div class="logo-text"><h2>SYSMOVILWT</h2><span>Sistema de Gestión Empresarial</span></div></div>
<nav class="sidebar-menu"><ul>
<li><a href="dashboard.html"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
<li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i><span>Inventario</span></a></li>
<li><a href="ventas.html"><i class="fa-solid fa-cart-shopping"></i><span>Ventas</span></a></li>
<li class="active"><a href="compras.php"><i class="fa-solid fa-bag-shopping"></i><span>Compras</span></a></li>
<li><a href="clientes.php"><i class="fa-solid fa-users"></i><span>Clientes</span></a></li>
<li><a href="proveedores.php"><i class="fa-solid fa-truck"></i><span>Proveedores</span></a></li>
<li><a href="usuarios.html"><i class="fa-solid fa-user-shield"></i><span>Usuarios</span></a></li>
<li><a href="serviciotecnico.php"><i class="fa-solid fa-screwdriver-wrench"></i><span>Servicio Técnico</span></a></li>
<li><a href="reportes.html"><i class="fa-solid fa-chart-column"></i><span>Reportes</span></a></li>
<li><a href="configuracion.html"><i class="fa-solid fa-gear"></i><span>Configuración</span></a></li>
</ul></nav>
<button class="logout-button" id="btnLogout" type="button"><i class="fa-solid fa-right-from-bracket"></i><span>Cerrar sesión</span></button>
</aside>
<header class="topbar"><div class="topbar-left"><button class="menu-toggle" type="button"><i class="fa-solid fa-bars"></i></button><div class="topbar-title"><h1>Compras</h1><span>Administra las compras de WT SERVIPHONE</span></div></div><div class="topbar-right"><div class="topbar-date"><i class="fa-regular fa-calendar"></i><span id="currentDate"></span></div><button class="notification-button" type="button"><i class="fa-regular fa-bell"></i><span class="notification-dot"></span></button><div class="topbar-profile"><div class="profile-avatar">WT</div><div class="profile-info"><strong>Administrador</strong><span>SYSMOVILWT</span></div></div></div></header>
<main class="dashboard-main"><section class="purchase-container">
<div class="purchase-header"><div><h2><i class="fa-solid fa-bag-shopping"></i> Gestión de Compras</h2><p>Administra las compras realizadas a los proveedores de WT SERVIPHONE.</p></div><button id="btnNuevaCompra" class="btn-new" type="button"><i class="fa-solid fa-plus"></i> Nueva Compra</button></div>
<?php if ($mensaje !== ""): ?><div class="purchase-alert <?= h($tipoMensaje) ?>"><i class="fa-solid fa-circle-info"></i> <?= h($mensaje) ?></div><?php endif; ?>
<div class="purchase-summary"><div class="purchase-summary-card"><span>Compras encontradas</span><strong><?= $totalCompras ?></strong></div><div class="purchase-summary-card"><span>Valor de compras filtradas</span><strong><?= cop($totalValor) ?></strong></div><div class="purchase-summary-card"><span>Productos disponibles</span><strong><?= count($productos) ?></strong></div></div>
<form method="GET" class="purchase-toolbar purchase-filter-form"><div class="purchase-search"><i class="fa-solid fa-magnifying-glass"></i><input type="text" name="buscar" value="<?= h($buscar) ?>" placeholder="Buscar compra, factura, proveedor o empleado..."></div><select name="proveedor"><option value="">Todos los proveedores</option><?php foreach($proveedores as $p): ?><option value="<?= (int)$p["id_proveedor"] ?>" <?= $idProveedorFiltro === (int)$p["id_proveedor"] ? "selected" : "" ?>><?= h($p["nombre_proveedor"]) ?></option><?php endforeach; ?></select><button type="submit" class="btn-new"><i class="fa-solid fa-filter"></i> Filtrar</button><a href="compras.php" class="btn-reset" title="Limpiar filtros"><i class="fa-solid fa-rotate-right"></i></a></form>
<div class="purchase-table-card purchase-table-wrapper"><table class="purchase-table"><thead><tr><th>Código</th><th>Fecha</th><th>Proveedor</th><th>Empleado</th><th>Productos</th><th>Total</th><th>Acciones</th></tr></thead><tbody>
<?php if (!$compras): ?><tr class="purchase-empty"><td colspan="7"><div class="empty-state"><i class="fa-solid fa-bag-shopping"></i><h3>No hay compras registradas</h3><p>Haz clic en <strong>Nueva Compra</strong> para registrar la primera compra.</p></div></td></tr>
<?php else: foreach($compras as $c): $idc=(int)$c["id_compra"]; ?>
<tr><td><strong><?= h($c["numero_factura_compra"] ?: ("COMP-" . str_pad((string)$idc,5,"0",STR_PAD_LEFT))) ?></strong><br><small>#<?= $idc ?></small></td><td><?= h(date("d/m/Y",strtotime($c["fecha_compra"]))) ?></td><td><?= h($c["nombre_proveedor"]) ?></td><td><?= h(trim($c["empleado_nombres"]." ".$c["empleado_apellidos"])) ?></td><td><?= (int)$c["unidades"] ?> unidades<br><small><?= (int)$c["lineas"] ?> líneas</small></td><td><strong><?= cop($c["total"]) ?></strong></td><td><div class="purchase-actions-cell"><button type="button" class="btn-view-purchase" title="Ver" onclick="verCompra(<?= $idc ?>)"><i class="fa-solid fa-eye"></i></button><button type="button" class="btn-edit-purchase" title="Editar" onclick="editarCompra(<?= $idc ?>)"><i class="fa-solid fa-pen"></i></button><a class="btn-delete-purchase" title="Eliminar" href="compras.php?eliminar=<?= $idc ?>" onclick="return confirm('¿Eliminar esta compra y descontar sus unidades del stock, si es posible?')"><i class="fa-solid fa-trash"></i></a></div></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div>
</section></main>

<div class="purchase-modal" id="purchaseModal" role="dialog" aria-modal="true"><div class="purchase-modal-content"><div class="purchase-modal-header"><h2 id="purchaseModalTitle"><i class="fa-solid fa-bag-shopping"></i> Nueva Compra</h2><button type="button" id="btnClosePurchaseModal" class="purchase-modal-close"><i class="fa-solid fa-xmark"></i></button></div>
<form id="purchaseForm" class="purchase-form" method="POST"><input type="hidden" name="accion" id="accionCompra" value="crear"><input type="hidden" name="id_compra" id="idCompra">
<div class="purchase-section"><h3>Información de la Compra</h3><div class="purchase-grid">
<div class="form-group"><label for="fechaCompra">Fecha *</label><input type="date" id="fechaCompra" name="fecha_compra" value="<?= h($hoy) ?>" required></div>
<div class="form-group"><label for="proveedorCompra">Proveedor *</label><select id="proveedorCompra" name="id_proveedor" required><option value="">Seleccione...</option><?php foreach($proveedores as $p): ?><option value="<?= (int)$p["id_proveedor"] ?>"><?= h($p["nombre_proveedor"]) ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label for="empleadoCompra">Empleado responsable *</label><select id="empleadoCompra" name="id_empleado" required><option value="">Seleccione empleado...</option><?php foreach($empleados as $e): ?><option value="<?= (int)$e["id_empleado"] ?>"><?= h(trim($e["nombres"]." ".$e["apellidos"])) ?><?= !empty($e["cargo"]) ? " — ".h($e["cargo"]) : "" ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label for="numeroFactura">Número de factura</label><input type="text" id="numeroFactura" name="numero_factura_compra" maxlength="50" placeholder="Opcional"></div>
</div></div>
<div class="purchase-section"><h3>Detalle de Productos</h3><div class="purchase-products"><table class="purchase-detail-table"><thead><tr><th style="width:38%">Producto</th><th style="width:14%">Cantidad</th><th style="width:20%">Costo unitario</th><th style="width:18%">Subtotal</th><th style="width:10%">Acción</th></tr></thead><tbody id="detalleCompraBody"></tbody></table><button type="button" id="btnAgregarProductoCompra" class="btn-new"><i class="fa-solid fa-plus"></i> Agregar Producto</button></div></div>
<div class="purchase-section"><h3>Observaciones</h3><div class="form-group"><textarea id="observacionesCompra" name="observaciones" rows="3" maxlength="250" placeholder="Observaciones de la compra..."></textarea></div></div>
<div class="purchase-total"><strong>Total Compra:</strong><strong id="totalCompra">$0</strong></div><div class="purchase-actions"><button type="button" id="btnCancelarCompra" class="btn-cancel">Cancelar</button><button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> <span id="textoGuardarCompra">Guardar Compra</span></button></div>
</form></div></div>
<div class="purchase-modal" id="purchaseViewModal" role="dialog" aria-modal="true"><div class="purchase-modal-content"><div class="purchase-modal-header"><h2><i class="fa-solid fa-file-invoice"></i> Detalle de Compra</h2><button type="button" id="btnCloseViewModal" class="purchase-modal-close"><i class="fa-solid fa-xmark"></i></button></div><div class="purchase-detail-view" id="purchaseViewContent"></div></div></div>

<script>
const productosDisponibles = <?= json_encode(array_map(fn($p)=>["id"=>(int)$p["id_producto"],"codigo"=>$p["codigo_producto"],"nombre"=>$p["nombre_producto"],"marca"=>$p["marca"]??"","modelo"=>$p["modelo"]??"","precio"=>(float)$p["precio_compra"],"stock"=>(int)$p["stock"]],$productos), JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
const comprasDetalle = <?= json_encode(array_map(function($c) use ($detalles){$id=(int)$c["id_compra"];return ["id"=>$id,"factura"=>$c["numero_factura_compra"]??"","fecha"=>$c["fecha_compra"],"proveedor"=>(int)$c["id_proveedor"],"empleado"=>(int)$c["id_empleado"],"observaciones"=>$c["observaciones"]??"","detalles"=>array_map(fn($d)=>["id_producto"=>(int)$d["id_producto"],"cantidad"=>(int)$d["cantidad"],"precio_unitario"=>(float)$d["precio_unitario"]],$detalles[$id]??[])];},$compras),JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>;
const modal=document.getElementById("purchaseModal"), viewModal=document.getElementById("purchaseViewModal"), form=document.getElementById("purchaseForm"), tbody=document.getElementById("detalleCompraBody");
let filaIndex=0;
function money(n){return "$"+Number(n||0).toLocaleString("es-CO",{maximumFractionDigits:2});}
function esc(s){return String(s??"").replace(/[&<>"']/g,c=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"}[c]));}
function totalizar(){let total=0;tbody.querySelectorAll("tr").forEach(tr=>{let q=Math.max(0,Number(tr.querySelector(".cantidad-input").value||0)),p=Math.max(0,Number(tr.querySelector(".costo-input").value||0)),sub=q*p;tr.querySelector(".subtotal-text").textContent=money(sub);total+=sub;});document.getElementById("totalCompra").textContent=money(total);}
function agregarFila(d={}){let i=filaIndex++,tr=document.createElement("tr");let opts=productosDisponibles.map(p=>{let label=`${p.codigo} - ${p.nombre}${p.marca?" / "+p.marca:""}${p.modelo?" "+p.modelo:""} (stock: ${p.stock})`;return `<option value="${p.id}" data-precio="${p.precio}" ${Number(d.id_producto)===p.id?"selected":""}>${esc(label)}</option>`;}).join("");tr.innerHTML=`<td><select class="producto-select" name="productos[${i}][id_producto]" required><option value="">Seleccione producto...</option>${opts}</select></td><td><input type="number" class="cantidad-input" name="productos[${i}][cantidad]" min="1" step="1" value="${Number(d.cantidad||1)}" required></td><td><input type="number" class="costo-input" name="productos[${i}][precio_unitario]" min="0" step="0.01" value="${Number(d.precio_unitario??0)}" required></td><td><span class="subtotal-text">$0</span></td><td><button type="button" class="btn-delete-row" title="Quitar"><i class="fa-solid fa-trash"></i></button></td>`;tbody.appendChild(tr);let sel=tr.querySelector(".producto-select"),cost=tr.querySelector(".costo-input");sel.addEventListener("change",()=>{let op=sel.selectedOptions[0];if(op&&op.dataset.precio!==undefined)cost.value=op.dataset.precio;totalizar();});tr.querySelector(".cantidad-input").addEventListener("input",totalizar);cost.addEventListener("input",totalizar);tr.querySelector(".btn-delete-row").addEventListener("click",()=>{tr.remove();totalizar();});totalizar();}
function abrirNueva(){form.reset();tbody.innerHTML="";filaIndex=0;document.getElementById("accionCompra").value="crear";document.getElementById("idCompra").value="";document.getElementById("purchaseModalTitle").innerHTML='<i class="fa-solid fa-bag-shopping"></i> Nueva Compra';document.getElementById("textoGuardarCompra").textContent="Guardar Compra";document.getElementById("fechaCompra").value=new Date().toLocaleDateString("en-CA");agregarFila();modal.classList.add("show");}
function cerrar(){modal.classList.remove("show");}
function editarCompra(id){let c=comprasDetalle.find(x=>x.id===Number(id));if(!c)return;form.reset();tbody.innerHTML="";filaIndex=0;document.getElementById("accionCompra").value="editar";document.getElementById("idCompra").value=c.id;document.getElementById("fechaCompra").value=c.fecha;document.getElementById("proveedorCompra").value=c.proveedor;document.getElementById("empleadoCompra").value=c.empleado;document.getElementById("numeroFactura").value=c.factura;document.getElementById("observacionesCompra").value=c.observaciones;document.getElementById("purchaseModalTitle").innerHTML='<i class="fa-solid fa-pen"></i> Editar Compra';document.getElementById("textoGuardarCompra").textContent="Actualizar Compra";(c.detalles.length?c.detalles:[{}]).forEach(agregarFila);modal.classList.add("show");}
function verCompra(id){let c=comprasDetalle.find(x=>x.id===Number(id));if(!c)return;let rows=c.detalles.map(d=>{let p=productosDisponibles.find(x=>x.id===d.id_producto);return `<tr><td>${esc(p?.codigo||d.id_producto)}</td><td>${esc(p?.nombre||"Producto")}</td><td>${d.cantidad}</td><td>${money(d.precio_unitario)}</td><td>${money(d.cantidad*d.precio_unitario)}</td></tr>`;}).join("");let total=c.detalles.reduce((s,d)=>s+d.cantidad*d.precio_unitario,0);let prov=document.querySelector(`#proveedorCompra option[value="${c.proveedor}"]`)?.textContent||"Proveedor";document.getElementById("purchaseViewContent").innerHTML=`<p><strong>Compra:</strong> #${c.id}</p><p><strong>Fecha:</strong> ${esc(c.fecha)}</p><p><strong>Factura:</strong> ${esc(c.factura||"Sin número")}</p><p><strong>Proveedor:</strong> ${esc(prov)}</p><p><strong>Observaciones:</strong> ${esc(c.observaciones||"Sin observaciones")}</p><div style="overflow-x:auto"><table><thead><tr><th>Código</th><th>Producto</th><th>Cantidad</th><th>Unitario</th><th>Subtotal</th></tr></thead><tbody>${rows}</tbody></table></div><h3 style="text-align:right">Total: ${money(total)}</h3>`;viewModal.classList.add("show");}
document.getElementById("btnNuevaCompra").addEventListener("click",abrirNueva);
document.getElementById("btnClosePurchaseModal").addEventListener("click",cerrar);
document.getElementById("btnCancelarCompra").addEventListener("click",cerrar);
document.getElementById("btnAgregarProductoCompra").addEventListener("click",()=>agregarFila());
document.getElementById("btnCloseViewModal").addEventListener("click",()=>viewModal.classList.remove("show"));
window.addEventListener("click",e=>{if(e.target===modal)cerrar();if(e.target===viewModal)viewModal.classList.remove("show");});
form.addEventListener("submit",e=>{if(!tbody.querySelector("tr")){e.preventDefault();alert("Agrega al menos un producto.");return;}if([...tbody.querySelectorAll("tr")].some(r=>!r.querySelector(".producto-select").value||Number(r.querySelector(".cantidad-input").value)<=0||Number(r.querySelector(".costo-input").value)<0)){e.preventDefault();alert("Revisa los productos, cantidades y costos.");}});
document.getElementById("btnLogout").addEventListener("click",()=>{if(confirm("¿Deseas cerrar la sesión?"))location.href="login.html";});
document.getElementById("currentDate").textContent=new Date().toLocaleDateString("es-CO",{weekday:"long",year:"numeric",month:"long",day:"numeric"});
document.querySelector(".menu-toggle")?.addEventListener("click",()=>document.querySelector(".sidebar")?.classList.toggle("show"));
</script>
</body>
</html>
