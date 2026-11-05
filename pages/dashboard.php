<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/conexion.php';

/* =========================================================
   ACCIÓN: PREPARAR FUNCIONES DE PRESENTACIÓN
   ========================================================= */
function h(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function moneda(mixed $value): string
{
    return '$ ' . number_format((float)$value, 0, ',', '.');
}

/* =========================================================
   ACCIÓN: CONSULTAR INDICADORES DEL NEGOCIO
   ========================================================= */
$resumen = [
    'productos' => (int)$conn->query('SELECT COUNT(*) FROM productos')->fetch_row()[0],
    'ventas_hoy' => 0,
    'clientes' => (int)$conn->query('SELECT COUNT(*) FROM clientes')->fetch_row()[0],
    'ingresos_mes' => 0.0,
];

$stmt = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total), 0) FROM ventas WHERE estado = 'Completada' AND fecha_venta >= CURDATE() AND fecha_venta < DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
$stmt->execute();
$ventasHoy = $stmt->get_result()->fetch_row();
$resumen['ventas_hoy'] = (int)($ventasHoy[0] ?? 0);
$stmt->close();

$stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) FROM ventas WHERE estado = 'Completada' AND fecha_venta >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND fecha_venta < DATE_ADD(CURDATE(), INTERVAL 1 DAY)");
$stmt->execute();
$resumen['ingresos_mes'] = (float)$stmt->get_result()->fetch_row()[0];
$stmt->close();

/* =========================================================
   ACCIÓN: CONSULTAR PRODUCTOS CON STOCK BAJO
   ========================================================= */
$productosBajos = $conn->query(
    'SELECT id_producto, codigo_producto, nombre_producto, stock, stock_minimo
     FROM productos
     WHERE stock <= stock_minimo
     ORDER BY stock ASC, nombre_producto ASC
     LIMIT 8'
)->fetch_all(MYSQLI_ASSOC);

/* =========================================================
   ACCIÓN: CONSULTAR ACTIVIDAD RECIENTE
   ========================================================= */
$actividad = $conn->query(
    "SELECT v.fecha_venta AS fecha, 'Venta' AS tipo,
            CONCAT('Factura ', v.numero_factura) AS descripcion,
            v.total AS valor
     FROM ventas v
     WHERE v.estado = 'Completada'
     ORDER BY v.fecha_venta DESC
     LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | SYSMOVILWT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .dashboard-panels{display:grid;grid-template-columns:1.3fr 1fr;gap:24px;margin-top:24px}
        .dashboard-panel{background:#fff;border-radius:16px;padding:22px;box-shadow:0 8px 24px #15213b12}
        .dashboard-panel h3{margin:0 0 16px;color:#162b4d}
        .dashboard-table{width:100%;border-collapse:collapse}
        .dashboard-table th,.dashboard-table td{text-align:left;padding:11px 8px;border-bottom:1px solid #edf0f5}
        .dashboard-table th{font-size:12px;color:#667085}
        .stock-low{color:#c0392b;font-weight:600}
        .dashboard-empty{color:#667085;padding:12px 0}
        @media(max-width:900px){.dashboard-panels{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <!-- ACCIÓN: MOSTRAR NAVEGACIÓN PRINCIPAL -->
    <aside class="sidebar">
        <div class="sidebar-logo"><img src="../assets/img/logo/logowt.png" alt="Logo WT SERVIPHONE" class="logo-sidebar"><div class="logo-text"><h2>SYSMOVILWT</h2><span>Sistema de Gestión Empresarial</span></div></div>
        <nav class="sidebar-menu"><ul>
            <li class="active"><a href="dashboard.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li>
            <li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i><span>Inventario</span></a></li>
            <li><a href="ventas.php"><i class="fa-solid fa-cart-shopping"></i><span>Ventas</span></a></li>
            <li><a href="compras.php"><i class="fa-solid fa-bag-shopping"></i><span>Compras</span></a></li>
            <li><a href="clientes.php"><i class="fa-solid fa-users"></i><span>Clientes</span></a></li>
            <li><a href="proveedores.php"><i class="fa-solid fa-truck"></i><span>Proveedores</span></a></li>
            <li><a href="usuarios.php"><i class="fa-solid fa-user-shield"></i><span>Usuarios</span></a></li>
            <li><a href="serviciotecnico.php"><i class="fa-solid fa-screwdriver-wrench"></i><span>Servicio técnico</span></a></li>
            <li><a href="reportes.php"><i class="fa-solid fa-chart-column"></i><span>Reportes</span></a></li>
            <li><a href="configuracion.php"><i class="fa-solid fa-gear"></i><span>Configuración</span></a></li>
        </ul></nav>
        <button class="logout-button" id="btnLogout" type="button"><i class="fa-solid fa-right-from-bracket"></i><span>Cerrar sesión</span></button>
    </aside>

    <!-- ACCIÓN: MOSTRAR CONTEXTO Y FECHA -->
    <header class="topbar"><div class="topbar-left"><button class="menu-toggle" type="button"><i class="fa-solid fa-bars"></i></button><div class="topbar-title"><h1>Dashboard</h1><span>Resumen de la operación</span></div></div><div class="topbar-right"><div class="topbar-date"><i class="fa-regular fa-calendar"></i><span><?= h(date('d/m/Y')) ?></span></div></div></header>

    <main class="dashboard-main">
        <section class="dashboard-header"><div class="dashboard-title"><h2>Resumen del negocio</h2><p>Indicadores consultados directamente desde MySQL.</p></div></section>

        <!-- ACCIÓN: RESUMIR INDICADORES -->
        <section class="dashboard-cards">
            <article class="dashboard-card"><div class="card-icon products"><i class="fa-solid fa-boxes-stacked"></i></div><div class="card-info"><h3>Productos</h3><span><?= number_format($resumen['productos'], 0, ',', '.') ?></span></div></article>
            <article class="dashboard-card"><div class="card-icon sales"><i class="fa-solid fa-cart-shopping"></i></div><div class="card-info"><h3>Ventas de hoy</h3><span><?= number_format((int)$resumen['ventas_hoy'], 0, ',', '.') ?></span></div></article>
            <article class="dashboard-card"><div class="card-icon clients"><i class="fa-solid fa-users"></i></div><div class="card-info"><h3>Clientes</h3><span><?= number_format($resumen['clientes'], 0, ',', '.') ?></span></div></article>
            <article class="dashboard-card"><div class="card-icon income"><i class="fa-solid fa-coins"></i></div><div class="card-info"><h3>Ingresos del mes</h3><span><?= h(moneda($resumen['ingresos_mes'])) ?></span></div></article>
        </section>

        <!-- ACCIÓN: MOSTRAR ALERTAS Y ACTIVIDAD -->
        <section class="dashboard-panels">
            <div class="dashboard-panel"><h3><i class="fa-solid fa-triangle-exclamation"></i> Productos con stock bajo</h3>
                <?php if (!$productosBajos): ?><p class="dashboard-empty">No hay productos por debajo del stock mínimo.</p>
                <?php else: ?><div class="table-responsive"><table class="dashboard-table"><thead><tr><th>Código</th><th>Producto</th><th>Stock</th><th>Mínimo</th></tr></thead><tbody>
                    <?php foreach ($productosBajos as $producto): ?><tr><td><?= h($producto['codigo_producto']) ?></td><td><?= h($producto['nombre_producto']) ?></td><td class="stock-low"><?= (int)$producto['stock'] ?></td><td><?= (int)$producto['stock_minimo'] ?></td></tr><?php endforeach; ?>
                </tbody></table></div><?php endif; ?>
            </div>
            <div class="dashboard-panel"><h3><i class="fa-solid fa-clock-rotate-left"></i> Ventas recientes</h3>
                <?php if (!$actividad): ?><p class="dashboard-empty">Aún no hay ventas registradas.</p>
                <?php else: ?><div class="table-responsive"><table class="dashboard-table"><thead><tr><th>Fecha</th><th>Factura</th><th>Valor</th></tr></thead><tbody>
                    <?php foreach ($actividad as $evento): ?><tr><td><?= h(date('d/m/Y H:i', strtotime((string)$evento['fecha']))) ?></td><td><?= h($evento['descripcion']) ?></td><td><?= h(moneda($evento['valor'])) ?></td></tr><?php endforeach; ?>
                </tbody></table></div><?php endif; ?>
            </div>
        </section>
    </main>
    <script>
        document.getElementById('btnLogout')?.addEventListener('click', () => {
            if (confirm('¿Deseas cerrar la sesión?')) {
                localStorage.removeItem('usuarioActivo');
                window.location.href = '../login.html';
            }
        });
    </script>
</body>
</html>
