<?php
declare(strict_types=1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';
require_once __DIR__ . '/../config/actividad.php';
exigirPermiso($conn, 'dashboard', 'ver');

function h(mixed $valor): string { return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8'); }

/* ACCIÓN: consultar los indicadores que alimentan las tarjetas */
$resumen = [
    'productos' => (int)$conn->query("SELECT COUNT(*) FROM productos WHERE estado='Activo'")->fetch_row()[0],
    'ventas' => (int)$conn->query("SELECT COUNT(*) FROM ventas WHERE estado = 'Completada'")->fetch_row()[0],
    'clientes' => (int)$conn->query('SELECT COUNT(*) FROM clientes')->fetch_row()[0],
    'ingresos' => (float)$conn->query("SELECT COALESCE(SUM(total), 0) FROM ventas WHERE estado = 'Completada'")->fetch_row()[0],
];

/* ACCIÓN: consultar los eventos recientes de todos los módulos */
$actividad = [];
$mensajeActividad = '';
try {
    prepararTablaActividadSistema($conn);
    $actividad = $conn->query(
        'SELECT modulo, accion, actor_nombre, descripcion, fecha
         FROM actividad_sistema ORDER BY fecha DESC, id_actividad DESC LIMIT 10'
    )->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $e) {
    error_log('SYSMOVILWT dashboard bitácora: ' . $e->getMessage());
    $mensajeActividad = 'No fue posible cargar la bitácora. Verifica que las migraciones 002 y 005 estén instaladas.';
}

/* ACCIÓN: consultar productos en el mínimo o por debajo del mínimo configurado */
$alertasStock = $conn->query(
    "SELECT id_producto, codigo_producto, nombre_producto, stock, COALESCE(stock_minimo, 5) AS stock_minimo
     FROM productos
     WHERE estado = 'Activo' AND stock <= COALESCE(stock_minimo, 5)
     ORDER BY stock ASC, nombre_producto ASC
     LIMIT 10"
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
        .sidebar{width:250px}
        .topbar{left:250px;width:calc(100% - 250px);height:72px}
        .dashboard-main{margin-left:250px;margin-top:72px;padding-top:30px}
        .dashboard-header{display:none}
        .dashboard-grid{grid-template-columns:2fr 1fr;gap:22px}
        .activity-item{display:grid;grid-template-columns:22px 1fr;gap:8px;margin:0 0 10px;color:#334155;font-size:14px}
        .activity-item i{color:#334155;margin-top:3px}
        .activity-item small{color:#64748b}
        .activity-empty{color:#64748b;padding:4px 0}
        .stock-alert-item{display:flex;align-items:flex-start;gap:10px;margin:0 0 12px;color:#334155;font-size:13px}
        .stock-alert-item i{margin-top:3px;color:#dc2626}
        .stock-alert-item strong{display:block;color:#1f2937}
        .stock-alert-item small{color:#64748b}
        @media(max-width:900px){.dashboard-grid{grid-template-columns:1fr}}
        @media(max-width:900px){.sidebar{width:250px}.topbar{left:250px;width:calc(100% - 250px)}.dashboard-main{margin-left:250px}}
        @media(max-width:600px){.dashboard-main{padding:22px 16px;margin-left:0;margin-top:72px}.topbar{left:0;width:100%;padding:0 14px}.topbar-date{display:none}.topbar-right{gap:10px}.topbar-profile .profile-info{display:none}.dashboard-cards{grid-template-columns:1fr 1fr;gap:12px}.dashboard-card{padding:16px;gap:12px}.card-icon{width:50px;height:50px;font-size:21px}.card-info span{font-size:23px}}
    </style>
    <link rel="stylesheet" href="../assets/css/acciones-compartidas.css">
</head>
<body>
    <!-- ACCIÓN: presentar el menú principal del sistema -->
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
            <li><a href="serviciotecnico.php"><i class="fa-solid fa-screwdriver-wrench"></i><span>Servicio Técnico</span></a></li>
            <li><a href="reportes.php"><i class="fa-solid fa-chart-column"></i><span>Reportes</span></a></li>
            <li><a href="configuracion.php"><i class="fa-solid fa-gear"></i><span>Configuración</span></a></li>
        </ul></nav>
        <button class="logout-button" id="btnLogout" type="button"><i class="fa-solid fa-right-from-bracket"></i><span>Cerrar sesión</span></button>
    </aside>

    <!-- ACCIÓN: mostrar título, fecha y perfil del usuario -->
    <header class="topbar">
        <div class="topbar-left"><button class="menu-toggle" type="button"><i class="fa-solid fa-bars"></i></button><div class="topbar-title"><h1>Dashboard</h1><span>Sistema Integral de Gestión Empresarial</span></div></div>
        <div class="topbar-right"><div class="topbar-date"><i class="fa-regular fa-calendar"></i><span id="currentDate"></span></div><button class="notification-button" type="button" aria-label="Notificaciones"><i class="fa-regular fa-bell"></i><span class="notification-dot"></span></button><div class="topbar-profile"><div class="profile-avatar">WT</div><div class="profile-info"><strong id="topbarUser">Leonardo</strong><span id="topbarRole">Administrador</span></div></div></div>
    </header>

    <main class="dashboard-main">
        <section class="dashboard-header"><div class="dashboard-title"><h2>Resumen</h2><p>Bienvenido a SYSMOVILWT.</p></div></section>
        <!-- ACCIÓN: resumir productos, ventas, clientes e ingresos -->
        <section class="dashboard-cards">
            <article class="dashboard-card"><div class="card-icon products"><i class="fa-solid fa-boxes-stacked"></i></div><div class="card-info"><h3>Productos</h3><span><?= number_format($resumen['productos'], 0, ',', '.') ?></span></div></article>
            <article class="dashboard-card"><div class="card-icon sales"><i class="fa-solid fa-cart-shopping"></i></div><div class="card-info"><h3>Ventas</h3><span><?= number_format($resumen['ventas'], 0, ',', '.') ?></span></div></article>
            <article class="dashboard-card"><div class="card-icon clients"><i class="fa-solid fa-users"></i></div><div class="card-info"><h3>Clientes</h3><span><?= number_format($resumen['clientes'], 0, ',', '.') ?></span></div></article>
            <article class="dashboard-card"><div class="card-icon income"><i class="fa-solid fa-sack-dollar"></i></div><div class="card-info"><h3>Ingresos</h3><span>$ <?= number_format($resumen['ingresos'], 0, ',', '.') ?></span></div></article>
        </section>
        <!-- ACCIÓN: presentar actividad reciente y alertas -->
        <section class="dashboard-grid">
            <div class="dashboard-panel"><div class="panel-header"><h3><i class="fa-solid fa-clock-rotate-left"></i> Actividad reciente</h3></div><div class="panel-body">
                <?php if ($mensajeActividad !== ''): ?><p class="activity-empty"><?= h($mensajeActividad) ?></p>
                <?php elseif (!$actividad): ?><p class="activity-empty">No hay actividad registrada en los módulos todavía.</p>
                <?php else: foreach ($actividad as $evento): ?><div class="activity-item"><i class="fa-solid fa-circle-info"></i><div><strong><?= h($evento['descripcion']) ?></strong><br><small><?= h($evento['modulo']) ?> · <?= h($evento['accion']) ?> · Por: <?= h($evento['actor_nombre'] ?? 'Sistema') ?> · <?= h(date('d/m/Y H:i', strtotime((string)$evento['fecha']))) ?></small></div></div><?php endforeach; endif; ?>
            </div></div>
            <div class="dashboard-panel"><div class="panel-header"><h3><i class="fa-solid fa-triangle-exclamation"></i> Alertas de inventario</h3></div><div class="panel-body">
                <?php if (!$alertasStock): ?><p class="activity-empty">No hay productos con stock bajo.</p>
                <?php else: foreach ($alertasStock as $alerta): ?><div class="stock-alert-item"><i class="fa-solid fa-triangle-exclamation"></i><div><strong><?= h($alerta['nombre_producto']) ?><?= (int)$alerta['stock'] === 0 ? ' · Agotado' : ' · Stock bajo' ?></strong><small>Código <?= h($alerta['codigo_producto']) ?> · Stock: <?= (int)$alerta['stock'] ?> · Mínimo: <?= (int)$alerta['stock_minimo'] ?></small></div></div><?php endforeach; endif; ?>
            </div></div>
        </section>
    </main>
    <script src="../assets/js/utils.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof cargarFecha === 'function') cargarFecha();
            if (typeof inicializarCerrarSesion === 'function') inicializarCerrarSesion();
        });
    </script>
</body>
</html>
