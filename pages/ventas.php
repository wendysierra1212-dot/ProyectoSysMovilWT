<?php
/**
 * SYSMOVILWT - Módulo de ventas conectado a MySQL.
 * Ubicación esperada: /pages/ventas.php
 */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/conexion.php';
$conn->set_charset('utf8mb4');

function h($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function cop($value): string { return '$' . number_format((float)$value, 0, ',', '.'); }
function redirectVentas(string $message, string $type = 'success'): void {
    header('Location: ventas.php?mensaje=' . urlencode($message) . '&tipo=' . urlencode($type));
    exit;
}

$mensaje = trim((string)($_GET['mensaje'] ?? ''));
$tipoMensaje = (($_GET['tipo'] ?? 'success') === 'error') ? 'error' : 'success';
$ventaConfirmada = null;

/* Registrar una venta: los precios, stock y totales se verifican en el servidor. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'registrar_venta') {
    $idEmpleado = (int)($_POST['id_empleado'] ?? 0);
    $idCliente = (int)($_POST['id_cliente'] ?? 0);
    $metodoPago = (string)($_POST['metodo_pago'] ?? 'Efectivo');
    $descuento = round((float)($_POST['descuento'] ?? 0), 2);
    $ids = $_POST['id_producto'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];
    $metodosPermitidos = ['Efectivo', 'Tarjeta', 'Transferencia'];

    try {
        if ($idEmpleado <= 0) throw new RuntimeException('Selecciona el empleado que realiza la venta.');
        if (!in_array($metodoPago, $metodosPermitidos, true)) throw new RuntimeException('El método de pago seleccionado no es válido.');
        if ($descuento < 0) throw new RuntimeException('El descuento no puede ser negativo.');
        if (!is_array($ids) || !is_array($cantidades) || count($ids) === 0 || count($ids) !== count($cantidades)) {
            throw new RuntimeException('Agrega al menos un producto al carrito.');
        }

        $carrito = [];
        foreach ($ids as $index => $rawId) {
            $pid = (int)$rawId;
            $cantidad = (int)($cantidades[$index] ?? 0);
            if ($pid <= 0 || $cantidad <= 0) throw new RuntimeException('Hay productos o cantidades inválidas en el carrito.');
            $carrito[$pid] = ($carrito[$pid] ?? 0) + $cantidad;
        }

        $conn->begin_transaction();

        $stmt = $conn->prepare("SELECT id_empleado FROM empleados WHERE id_empleado=? AND estado='Activo' FOR UPDATE");
        $stmt->bind_param('i', $idEmpleado); $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) throw new RuntimeException('El empleado no existe o está inactivo.');
        $stmt->close();

        if ($idCliente > 0) {
            $stmt = $conn->prepare('SELECT id_cliente FROM clientes WHERE id_cliente=? FOR UPDATE');
            $stmt->bind_param('i', $idCliente); $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) throw new RuntimeException('El cliente seleccionado no existe.');
            $stmt->close();
        } else { $idCliente = 0; }

        $lineas = []; $subtotal = 0.0;
        $stmtProducto = $conn->prepare('SELECT id_producto, codigo_producto, nombre_producto, marca, precio_venta, stock FROM productos WHERE id_producto=? FOR UPDATE');
        foreach ($carrito as $pid => $cantidad) {
            $stmtProducto->bind_param('i', $pid); $stmtProducto->execute();
            $producto = $stmtProducto->get_result()->fetch_assoc();
            if (!$producto) throw new RuntimeException("El producto #$pid ya no existe.");
            if ((int)$producto['stock'] < $cantidad) {
                throw new RuntimeException('Stock insuficiente para ' . $producto['nombre_producto'] . '. Disponible: ' . (int)$producto['stock'] . '.');
            }
            $precio = round((float)$producto['precio_venta'], 2);
            $lineaSubtotal = round($precio * $cantidad, 2);
            $subtotal = round($subtotal + $lineaSubtotal, 2);
            $lineas[] = ['id_producto' => $pid, 'cantidad' => $cantidad, 'precio_unitario' => $precio, 'subtotal' => $lineaSubtotal, 'nombre' => $producto['nombre_producto']];
        }
        $stmtProducto->close();

        if ($descuento > $subtotal) throw new RuntimeException('El descuento no puede ser superior al subtotal de la venta.');
        $iva = 0.00; // No calcular IVA automáticamente mientras WT SERVIPHONE figure como no responsable.
        $total = round($subtotal - $descuento, 2);
        $numeroFactura = 'V-' . date('YmdHis') . '-' . random_int(100, 999);

        $stmt = $conn->prepare('INSERT INTO ventas (numero_factura, fecha_venta, id_cliente, id_empleado, metodo_pago, subtotal, iva, descuento, total, estado) VALUES (?, NOW(), NULLIF(?,0), ?, ?, ?, ?, ?, ?, \'Completada\')');
        $stmt->bind_param('siisdddd', $numeroFactura, $idCliente, $idEmpleado, $metodoPago, $subtotal, $iva, $descuento, $total);
        $stmt->execute(); $idVenta = (int)$conn->insert_id; $stmt->close();

        $stmtDetalle = $conn->prepare('INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)');
        $stmtStock = $conn->prepare('UPDATE productos SET stock = stock - ? WHERE id_producto = ? AND stock >= ?');
        foreach ($lineas as $linea) {
            $pid = $linea['id_producto']; $cantidad = $linea['cantidad']; $precio = $linea['precio_unitario']; $lineaSubtotal = $linea['subtotal'];
            $stmtDetalle->bind_param('iiidd', $idVenta, $pid, $cantidad, $precio, $lineaSubtotal); $stmtDetalle->execute();
            $stmtStock->bind_param('iii', $cantidad, $pid, $cantidad); $stmtStock->execute();
            if ($stmtStock->affected_rows !== 1) throw new RuntimeException('No fue posible actualizar el inventario. La venta fue cancelada.');
        }
        $stmtDetalle->close(); $stmtStock->close();
        $conn->commit();
        redirectVentas('Venta ' . $numeroFactura . ' registrada correctamente. Total: ' . cop($total));
    } catch (Throwable $e) {
        try { $conn->rollback(); } catch (Throwable $ignored) {}
        redirectVentas('No se pudo registrar la venta: ' . $e->getMessage(), 'error');
    }
}

/* Datos para la interfaz */
$productos = [];
$res = $conn->query("SELECT p.id_producto, p.codigo_producto, p.nombre_producto, p.marca, p.modelo, p.tipo_producto, p.precio_venta, p.stock, p.stock_minimo, p.condicion, c.nombre_categoria FROM productos p LEFT JOIN categorias c ON c.id_categoria=p.id_categoria ORDER BY p.nombre_producto");
while ($row = $res->fetch_assoc()) $productos[] = $row;
$clientes = $conn->query('SELECT id_cliente, nombres, apellidos, cedula, telefono FROM clientes ORDER BY nombres, apellidos')->fetch_all(MYSQLI_ASSOC);
$empleados = $conn->query("SELECT id_empleado, nombres, apellidos, cargo FROM empleados WHERE estado='Activo' ORDER BY nombres, apellidos")->fetch_all(MYSQLI_ASSOC);
$historial = $conn->query("SELECT v.id_venta, v.numero_factura, v.fecha_venta, v.metodo_pago, v.subtotal, v.iva, v.descuento, v.total, v.estado, CONCAT(e.nombres,' ',e.apellidos) AS empleado, CASE WHEN c.id_cliente IS NULL THEN 'Cliente general' ELSE CONCAT(c.nombres,' ',c.apellidos) END AS cliente, COUNT(d.id_detalle_venta) AS lineas FROM ventas v INNER JOIN empleados e ON e.id_empleado=v.id_empleado LEFT JOIN clientes c ON c.id_cliente=v.id_cliente LEFT JOIN detalle_venta d ON d.id_venta=v.id_venta GROUP BY v.id_venta ORDER BY v.id_venta DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);
$totalDia = 0.0; $ventasHoy = 0;
$resDia = $conn->query("SELECT COUNT(*) AS cantidad, COALESCE(SUM(total),0) AS total FROM ventas WHERE estado='Completada' AND DATE(fecha_venta)=CURDATE()")->fetch_assoc();
$ventasHoy = (int)$resDia['cantidad']; $totalDia = (float)$resDia['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ventas | SYSMOVILWT</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/styles.css"><link rel="stylesheet" href="../assets/css/components.css"><link rel="stylesheet" href="../assets/css/dashboard.css">
<style>
:root{--sale-red:#c62828;--sale-dark:#172033;--sale-bg:#f4f6fa;--sale-border:#e6e9f0;--sale-muted:#687386}
/* Alinear el contenido de ventas al lado de la barra lateral fija */
main.main-content.sales-page{margin-left:280px;width:calc(100% - 280px);min-width:0;padding:26px;overflow-x:hidden}
main.main-content.sales-page .sales-wrap{width:100%;max-width:1500px;margin:0 auto;min-width:0}
main.main-content.sales-page .sales-grid{grid-template-columns:minmax(0,1.1fr) minmax(340px,.9fr)}
main.main-content.sales-page .panel,main.main-content.sales-page .panel-body,main.main-content.sales-page .panel-head{min-width:0}
@media(max-width:1050px){main.main-content.sales-page{padding:20px}main.main-content.sales-page .sales-grid{grid-template-columns:minmax(0,1fr)}}
@media(max-width:768px){main.main-content.sales-page{margin-left:0;width:100%;padding:14px}}
*{box-sizing:border-box}.sales-page{padding:26px;background:var(--sale-bg);min-height:100vh;color:#202939;font-family:Poppins, sans-serif}.sales-wrap{max-width:1500px;margin:auto}.sales-top{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:22px}.sales-title h1{font-size:26px;margin:0 0 5px;font-weight:700}.sales-title p{margin:0;color:var(--sale-muted);font-size:13px}.sales-stats{display:flex;gap:12px;flex-wrap:wrap}.stat-card{background:#fff;border:1px solid var(--sale-border);border-radius:14px;padding:14px 18px;min-width:160px;box-shadow:0 4px 14px #1b29400a}.stat-card span{display:block;color:var(--sale-muted);font-size:12px}.stat-card strong{font-size:20px}.sales-alert{padding:13px 16px;border-radius:10px;margin-bottom:18px;font-size:13px}.sales-alert.success{background:#e9f8ef;color:#176b3a;border:1px solid #bce8cc}.sales-alert.error{background:#fff0f0;color:#a61d24;border:1px solid #f3c4c6}.sales-grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(360px,.85fr);gap:20px;align-items:start}.panel{background:#fff;border:1px solid var(--sale-border);border-radius:15px;overflow:hidden;box-shadow:0 5px 20px #1b29400a}.panel-head{padding:18px 20px;border-bottom:1px solid var(--sale-border);display:flex;justify-content:space-between;align-items:center;gap:12px}.panel-head h2{font-size:16px;margin:0;font-weight:650}.panel-head h2 i{color:var(--sale-red);margin-right:8px}.panel-body{padding:18px 20px}.searchbox{position:relative;margin-bottom:16px}.searchbox i{position:absolute;left:14px;top:14px;color:#8a94a6}.searchbox input{width:100%;padding:12px 14px 12px 40px;border:1px solid #dfe4ed;border-radius:9px;font:inherit;font-size:13px;outline:none}.searchbox input:focus,select:focus,input:focus{border-color:#c62828;box-shadow:0 0 0 3px #c6282814}.product-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;max-height:540px;overflow:auto;padding:1px 3px 4px}.product-card{border:1px solid var(--sale-border);border-radius:12px;padding:14px;display:flex;flex-direction:column;gap:8px;min-width:0}.product-card h3{font-size:13px;margin:0;line-height:1.45}.product-meta{color:var(--sale-muted);font-size:11px}.product-price{font-size:18px;font-weight:700;color:var(--sale-dark)}.product-foot{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:auto}.stock{font-size:11px;border-radius:20px;padding:4px 8px;background:#e8f8ef;color:#187343}.stock.low{background:#fff5dc;color:#8b5a00}.stock.out{background:#ffebee;color:#b4232c}.btn{border:0;border-radius:8px;padding:10px 13px;font:600 12px Poppins,sans-serif;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:7px;text-decoration:none}.btn-red{background:var(--sale-red);color:#fff}.btn-red:hover{background:#a91f1f}.btn-light{background:#f0f2f6;color:#364152}.btn:disabled{opacity:.45;cursor:not-allowed}.cart-items{display:flex;flex-direction:column;gap:10px;max-height:360px;overflow:auto}.cart-row{border:1px solid var(--sale-border);border-radius:10px;padding:12px;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px}.cart-row h4{font-size:12px;margin:0 0 4px}.cart-row small{font-size:11px;color:var(--sale-muted)}.qty-controls{display:flex;align-items:center;gap:8px;margin-top:9px}.qty-controls input{width:64px;padding:6px;border:1px solid #dfe4ed;border-radius:6px;text-align:center}.remove-item{border:0;background:transparent;color:#c62828;cursor:pointer;padding:4px}.cart-price{text-align:right;font-size:13px;font-weight:700;white-space:nowrap}.empty{padding:28px 10px;text-align:center;color:var(--sale-muted);font-size:13px}.empty i{display:block;font-size:28px;margin-bottom:8px;color:#adb5c4}.sale-fields{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:15px}.field label{display:block;font-size:11px;font-weight:600;color:#596579;margin-bottom:6px}.field select,.field input{width:100%;padding:10px;border:1px solid #dfe4ed;border-radius:8px;background:#fff;font:12px Poppins,sans-serif;min-width:0}.summary{border-top:1px solid var(--sale-border);margin-top:16px;padding-top:14px;display:flex;flex-direction:column;gap:9px}.summary-line{display:flex;justify-content:space-between;gap:10px;font-size:12px;color:#536074}.summary-line strong{color:#202939}.summary-line.total{border-top:1px dashed #d9deea;padding-top:12px;margin-top:3px;font-size:17px;font-weight:700}.summary-line.total strong{color:var(--sale-red);font-size:21px}.sale-actions{display:flex;gap:10px;margin-top:18px}.sale-actions .btn{flex:1;padding:12px 10px}.history-panel{margin-top:22px}.table-wrap{overflow-x:auto}.sales-table{width:100%;border-collapse:collapse;min-width:850px;font-size:12px}.sales-table th,.sales-table td{padding:12px 14px;text-align:left;border-bottom:1px solid #edf0f5}.sales-table th{font-size:11px;color:#697589;background:#fafbfc}.sales-table tr:last-child td{border-bottom:0}.pill{display:inline-block;border-radius:20px;padding:4px 8px;background:#e8f8ef;color:#176b3a;font-size:10px}.muted{color:var(--sale-muted)}.empty-history{padding:24px;text-align:center;color:var(--sale-muted);font-size:13px}
@media(max-width:1050px){.sales-grid{grid-template-columns:1fr}.product-grid{max-height:none}}@media(max-width:600px){.sales-page{padding:14px}.product-grid{grid-template-columns:1fr}.sale-fields{grid-template-columns:1fr}.panel-body{padding:14px}.panel-head{padding:14px}.sales-title h1{font-size:22px}.sales-stats{width:100%}.stat-card{flex:1;min-width:130px}.sale-actions{flex-direction:column}}
@media print{.sidebar,.sales-top,.sales-products-panel,.history-panel,.sales-alert,.sale-actions,.panel-head .no-print{display:none!important}.sales-page{padding:0;background:#fff}.sales-grid{display:block}.sales-cart-panel{border:0;box-shadow:none}.panel-body{padding:0}.cart-items{max-height:none;overflow:visible}.sales-cart-panel .panel-head{border:0}.sales-cart-panel .panel-head:after{content:'Factura de venta';font-size:20px;font-weight:700}.sales-cart-panel .panel-head h2{display:none}.sales-stats{display:none}}

/* Ajustes visuales para Ventas */
main.main-content.sales-page {
    margin-left: 280px;
    width: calc(100% - 280px);
    min-width: 0;
    padding: 24px;
    overflow-x: hidden;
}

main.main-content.sales-page .sales-wrap {
    width: 100%;
    max-width: 1500px;
    margin: 0 auto;
}

main.main-content.sales-page .sales-grid {
    grid-template-columns: minmax(0, 1.04fr) minmax(380px, 0.96fr);
    gap: 18px;
    align-items: start;
}

main.main-content.sales-page .panel,
main.main-content.sales-page .panel-body,
main.main-content.sales-page .panel-head {
    min-width: 0;
}

main.main-content.sales-page .product-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 10px;
    max-height: 500px;
    align-content: start;
}

main.main-content.sales-page .product-card {
    min-height: 172px;
    padding: 13px;
    gap: 7px;
}

main.main-content.sales-page .sales-cart-panel .panel-body {
    padding: 16px 18px;
}

main.main-content.sales-page .cart-items {
    max-height: 300px;
    gap: 8px;
}

main.main-content.sales-page .history-panel {
    margin-top: 18px;
}

main.main-content.sales-page .table-wrap {
    max-height: 440px;
    overflow: auto;
}

main.main-content.sales-page .sales-table {
    min-width: 850px;
    font-size: 11.5px;
}

main.main-content.sales-page .sales-table th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #f7f8fb;
    white-space: nowrap;
}

main.main-content.sales-page .sales-table th,
main.main-content.sales-page .sales-table td {
    padding: 11px 12px;
    vertical-align: middle;
}

main.main-content.sales-page .sales-table tbody tr:nth-child(even) {
    background: #fbfcfe;
}

@media (max-width: 1050px) {
    main.main-content.sales-page {
        padding: 18px;
    }

    main.main-content.sales-page .sales-grid {
        grid-template-columns: minmax(0, 1fr);
    }
}

@media (max-width: 768px) {
    main.main-content.sales-page {
        margin-left: 0;
        width: 100%;
        padding: 12px;
    }

    main.main-content.sales-page .product-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 480px) {
    main.main-content.sales-page .product-grid {
        grid-template-columns: minmax(0, 1fr);
    }

    main.main-content.sales-page .sale-fields {
        grid-template-columns: minmax(0, 1fr);
    }
}

</style>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-logo"><img src="../assets/img/logo/logowt.png" alt="Logo WT SERVIPHONE" class="logo-sidebar"><div class="logo-text"><h2>SYSMOVILWT</h2><span>Sistema de Gestión Empresarial</span></div></div>
  <nav class="sidebar-menu"><ul>
    <li><a href="dashboard.html"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
    <li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i><span>Inventario</span></a></li>
    <li class="active"><a href="ventas.php"><i class="fa-solid fa-cart-shopping"></i><span>Ventas</span></a></li>
    <li><a href="compras.php"><i class="fa-solid fa-bag-shopping"></i><span>Compras</span></a></li>
    <li><a href="clientes.html"><i class="fa-solid fa-users"></i><span>Clientes</span></a></li>
    <li><a href="proveedores.html"><i class="fa-solid fa-truck"></i><span>Proveedores</span></a></li>
    <li><a href="usuarios.html"><i class="fa-solid fa-user-shield"></i><span>Usuarios</span></a></li>
    <li><a href="serviciotecnico.html"><i class="fa-solid fa-screwdriver-wrench"></i><span>Servicio Técnico</span></a></li>
    <li><a href="reportes.html"><i class="fa-solid fa-chart-column"></i><span>Reportes</span></a></li>
    <li><a href="configuracion.html"><i class="fa-solid fa-gear"></i><span>Configuración</span></a></li>
  </ul></nav>
</aside>
<main class="main-content sales-page"><div class="sales-wrap">
  <header class="sales-top"><div class="sales-title"><h1><i class="fa-solid fa-cart-shopping" style="color:#c62828"></i> Punto de venta</h1><p>Registra ventas, administra el carrito y controla el inventario.</p></div><div class="sales-stats"><div class="stat-card"><span>Ventas completadas hoy</span><strong><?= $ventasHoy ?></strong></div><div class="stat-card"><span>Ingresos de hoy</span><strong><?= h(cop($totalDia)) ?></strong></div></div></header>
  <?php if ($mensaje !== ''): ?><div class="sales-alert <?= h($tipoMensaje) ?>"><i class="fa-solid <?= $tipoMensaje === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i> <?= h($mensaje) ?></div><?php endif; ?>
  <form method="post" id="saleForm" autocomplete="off"><input type="hidden" name="accion" value="registrar_venta">
  <div class="sales-grid">
    <section class="panel sales-products-panel"><div class="panel-head"><h2><i class="fa-solid fa-mobile-screen-button"></i> Productos disponibles</h2><span class="muted" style="font-size:11px"><?= count($productos) ?> productos</span></div><div class="panel-body">
      <div class="searchbox"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="productSearch" placeholder="Buscar por nombre, marca, modelo o código..."></div>
      <div class="product-grid" id="productGrid">
      <?php foreach ($productos as $p): $stock=(int)$p['stock']; $low=$stock>0 && $stock<=(int)($p['stock_minimo'] ?? 5); ?>
        <article class="product-card" data-search="<?= h(strtolower($p['nombre_producto'].' '.$p['marca'].' '.$p['modelo'].' '.$p['codigo_producto'].' '.$p['tipo_producto'].' '.$p['nombre_categoria'])) ?>">
          <div class="product-meta"><?= h($p['tipo_producto']) ?><?= $p['condicion'] ? ' · '.h($p['condicion']) : '' ?></div>
          <h3><?= h($p['nombre_producto']) ?></h3><div class="product-meta"><?= h(trim(($p['marca'] ?? '').' '.($p['modelo'] ?? ''))) ?> · Código: <?= h($p['codigo_producto']) ?></div>
          <div class="product-price"><?= h(cop($p['precio_venta'])) ?></div>
          <div class="product-foot"><span class="stock <?= $stock<=0?'out':($low?'low':'') ?>"><?= $stock<=0?'Agotado':($low?'Stock bajo':'Stock: '.$stock) ?></span><button type="button" class="btn btn-red add-product" data-id="<?= (int)$p['id_producto'] ?>" data-name="<?= h($p['nombre_producto']) ?>" data-price="<?= h($p['precio_venta']) ?>" data-stock="<?= $stock ?>" <?= $stock<=0?'disabled':'' ?>><i class="fa-solid fa-plus"></i> Agregar</button></div>
        </article>
      <?php endforeach; ?>
      <?php if (!$productos): ?><div class="empty"><i class="fa-solid fa-box-open"></i>No hay productos registrados en el inventario.</div><?php endif; ?>
      </div><div class="empty" id="noSearchResults" style="display:none">No se encontraron productos con esa búsqueda.</div>
    </div></section>
    <section class="panel sales-cart-panel"><div class="panel-head"><h2><i class="fa-solid fa-receipt"></i> Nueva venta</h2><span class="muted" style="font-size:11px"><?= h(date('d/m/Y H:i')) ?></span></div><div class="panel-body">
      <div class="sale-fields"><div class="field"><label for="id_empleado">Empleado responsable *</label><select name="id_empleado" id="id_empleado" required><option value="">Selecciona empleado</option><?php foreach ($empleados as $e): ?><option value="<?= (int)$e['id_empleado'] ?>"><?= h($e['nombres'].' '.$e['apellidos'].' · '.$e['cargo']) ?></option><?php endforeach; ?></select></div><div class="field"><label for="id_cliente">Cliente (opcional)</label><select name="id_cliente" id="id_cliente"><option value="0">Cliente general</option><?php foreach ($clientes as $c): ?><option value="<?= (int)$c['id_cliente'] ?>"><?= h($c['nombres'].' '.$c['apellidos'].($c['cedula']?' · '.$c['cedula']:'')) ?></option><?php endforeach; ?></select></div></div>
      <div class="field" style="margin-bottom:14px"><label>Método de pago *</label><select name="metodo_pago" id="metodo_pago" required><option value="Efectivo">Efectivo</option><option value="Tarjeta">Tarjeta</option><option value="Transferencia">Transferencia</option></select></div>
      <div class="cart-items" id="cartItems"><div class="empty" id="emptyCart"><i class="fa-solid fa-cart-shopping"></i>El carrito está vacío.<br>Agrega productos desde el panel izquierdo.</div></div>
      <div class="summary"><div class="summary-line"><span>Subtotal</span><strong id="subtotalText">$0</strong></div><div class="summary-line"><label for="descuento" style="color:#536074">Descuento (COP)</label><input id="descuento" name="descuento" type="number" min="0" step="0.01" value="0" style="width:125px;text-align:right;padding:6px;border:1px solid #dfe4ed;border-radius:7px"></div><div class="summary-line"><span>IVA (no calculado)</span><strong id="ivaText">$0</strong></div><div class="summary-line total"><span>Total</span><strong id="totalText">$0</strong></div></div>
      <div class="sale-actions"><button type="button" class="btn btn-light" id="clearCart"><i class="fa-solid fa-trash"></i> Vaciar carrito</button><button type="submit" class="btn btn-red" id="finishSale" disabled><i class="fa-solid fa-check"></i> Finalizar venta</button></div>
      <p class="muted" style="font-size:10px;margin:12px 0 0">El inventario se descuenta únicamente cuando la venta se registra correctamente.</p>
    </div></section>
  </div></form>
  <section class="panel history-panel"><div class="panel-head"><h2><i class="fa-solid fa-clock-rotate-left"></i> Ventas recientes</h2><span class="muted" style="font-size:11px">Últimas 50 ventas</span></div><div class="table-wrap"><table class="sales-table">
    
<thead>
    <tr>
        <th>Factura</th>
        <th>Fecha</th>
        <th>Cliente</th>
        <th>Empleado</th>
        <th>Pago</th>
        <th>Productos</th>
        <th>Total</th>
        <th>Estado</th>
        <th>Acciones</th>
    </tr>
</thead>
<tbody>
  <?php foreach ($historial as $v): ?><tr><td><strong><?= h($v['numero_factura']) ?></strong></td><td><?= h(date('d/m/Y H:i', strtotime($v['fecha_venta']))) ?></td><td><?= h($v['cliente']) ?></td><td><?= h($v['empleado']) ?></td><td><?= h($v['metodo_pago']) ?></td><td><?= (int)$v['lineas'] ?></td><td><strong><?= h(cop($v['total'])) ?></strong></td><td><span class="pill"><?= h($v['estado']) ?></span></td>

<td>
    <a
        href="factura.php?id=<?= (int)$v['id_venta'] ?>"
        class="btn btn-light"
        target="_blank"
        rel="noopener"
        title="Consultar e imprimir factura">
        <i class="fa-solid fa-eye"></i> Ver factura
    </a>
</td>

</tr><?php endforeach; ?>
  <?php if (!$historial): ?><tr><td colspan="9" class="empty-history">Todavía no hay ventas registradas.</td></tr><?php endif; ?>
  </tbody></table></div></section>
</div></main>
<script>
(() => {
  const cart = new Map();
  const fmt = n => '$' + Number(n || 0).toLocaleString('es-CO', {maximumFractionDigits: 0});
  const escapeHtml = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const cartItems = document.getElementById('cartItems');
  const finish = document.getElementById('finishSale');
  const discount = document.getElementById('descuento');
  function totals() {
    let subtotal = 0; cart.forEach(p => subtotal += p.price * p.qty);
    let d = Math.max(0, Number(discount.value) || 0); d = Math.min(d, subtotal);
    const iva = 0; // No sumar IVA automáticamente.
    document.getElementById('subtotalText').textContent = fmt(subtotal);
    document.getElementById('ivaText').textContent = fmt(iva);
    document.getElementById('totalText').textContent = fmt(subtotal - d);
    finish.disabled = cart.size === 0;
  }
  function renderCart() {
    cartItems.innerHTML = '';
    if (!cart.size) cartItems.innerHTML = '<div class="empty"><i class="fa-solid fa-cart-shopping"></i>El carrito está vacío.<br>Agrega productos desde el panel izquierdo.</div>';
    cart.forEach(p => {
      const row = document.createElement('div'); row.className = 'cart-row';
      row.innerHTML = `<div><h4>${escapeHtml(p.name)}</h4><small>${fmt(p.price)} por unidad · Stock disponible: ${p.stock}</small><div class="qty-controls"><label style="font-size:11px" for="qty-${p.id}">Cantidad</label><input id="qty-${p.id}" type="number" min="1" max="${p.stock}" value="${p.qty}" data-qty="${p.id}"><button type="button" class="remove-item" data-remove="${p.id}" aria-label="Eliminar producto"><i class="fa-solid fa-trash"></i></button></div></div><div class="cart-price">${fmt(p.price*p.qty)}</div>`;
      cartItems.appendChild(row);
    });
    totals();
  }
  document.querySelectorAll('.add-product').forEach(btn => btn.addEventListener('click', () => {
    const id = Number(btn.dataset.id), stock = Number(btn.dataset.stock), current = cart.get(id);
    if (current && current.qty >= stock) { alert('No puedes agregar más unidades que las disponibles.'); return; }
    if (current) current.qty++;
    else cart.set(id, {id, name:btn.dataset.name, price:Number(btn.dataset.price), stock, qty:1});
    renderCart();
  }));
  cartItems.addEventListener('change', e => {
    if (!e.target.matches('[data-qty]')) return;
    const id = Number(e.target.dataset.qty), p = cart.get(id), qty = Number(e.target.value);
    if (!p) return;
    if (!Number.isInteger(qty) || qty < 1 || qty > p.stock) { alert('La cantidad debe ser entre 1 y el stock disponible ('+p.stock+').'); e.target.value = p.qty; return; }
    p.qty = qty; renderCart();
  });
  cartItems.addEventListener('click', e => { const b=e.target.closest('[data-remove]'); if(b){cart.delete(Number(b.dataset.remove));renderCart();} });
  document.getElementById('clearCart').addEventListener('click', () => { if (!cart.size || confirm('¿Deseas vaciar el carrito?')) { cart.clear(); renderCart(); } });
  discount.addEventListener('input', totals);
  document.getElementById('productSearch').addEventListener('input', e => {
    const q = e.target.value.toLowerCase().trim(); let shown=0;
    document.querySelectorAll('.product-card').forEach(card => { const match=card.dataset.search.includes(q); card.style.display=match?'':'none'; if(match)shown++; });
    document.getElementById('noSearchResults').style.display = shown ? 'none' : '';
  });
  document.getElementById('saleForm').addEventListener('submit', e => {
    if (!cart.size) { e.preventDefault(); alert('Agrega al menos un producto antes de finalizar.'); return; }
    const emp = document.getElementById('id_empleado'); if (!emp.value) { e.preventDefault(); alert('Selecciona el empleado responsable.'); emp.focus(); return; }
    const subtotal = [...cart.values()].reduce((s,p)=>s+p.price*p.qty,0); const d=Number(discount.value)||0;
    if (d<0 || d>subtotal) { e.preventDefault(); alert('El descuento debe estar entre $0 y el subtotal.'); discount.focus(); return; }
    cart.forEach(p => {
      const idInput=document.createElement('input'); idInput.type='hidden'; idInput.name='id_producto[]'; idInput.value=p.id; e.target.appendChild(idInput);
      const qtyInput=document.createElement('input'); qtyInput.type='hidden'; qtyInput.name='cantidad[]'; qtyInput.value=p.qty; e.target.appendChild(qtyInput);
    });
    finish.disabled=true; finish.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Registrando...';
  });
  renderCart();
})();
</script>
</body></html>
