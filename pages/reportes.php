<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../config/autenticacion.php';
exigirPermiso($conn, 'reportes', 'ver');

/* =========================================================
   ACCIÓN: VALIDAR FILTROS DE CONSULTA
   ========================================================= */
function h(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function moneda(mixed $value): string
{
    return '$ ' . number_format((float)$value, 0, ',', '.');
}

function fechaValida(string $fecha): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    return $date !== false && $date->format('Y-m-d') === $fecha;
}

$tipos = ['general', 'ventas', 'compras', 'inventario', 'clientes', 'servicio'];
$tipo = (string)($_GET['tipo'] ?? 'general');
if (!in_array($tipo, $tipos, true)) {
    $tipo = 'general';
}

$hoy = new DateTimeImmutable('today');
$inicioPredeterminado = $hoy->modify('first day of this month')->format('Y-m-d');
$inicio = (string)($_GET['inicio'] ?? $inicioPredeterminado);
$fin = (string)($_GET['fin'] ?? $hoy->format('Y-m-d'));
$error = '';
$filas = [];

if (!fechaValida($inicio) || !fechaValida($fin) || $inicio > $fin) {
    $error = 'Selecciona un rango de fechas válido. La fecha inicial debe ser anterior o igual a la final.';
    $inicio = $inicioPredeterminado;
    $fin = $hoy->format('Y-m-d');
}

/* =========================================================
   ACCIÓN: CONSULTAR TARJETAS DE RESUMEN
   ========================================================= */
$consultaResumen = static function (mysqli $db, string $sql, string $inicio, string $fin): array {
    $stmt = $db->prepare($sql);
    $stmt->bind_param('ss', $inicio, $fin);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
    return $row;
};

$resumenVentas = $consultaResumen($conn, "SELECT COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS valor FROM ventas WHERE estado = 'Completada' AND fecha_venta >= ? AND fecha_venta < DATE_ADD(?, INTERVAL 1 DAY)", $inicio, $fin);
$resumenCompras = $consultaResumen($conn, 'SELECT COUNT(*) AS cantidad, COALESCE(SUM(total), 0) AS valor FROM compras WHERE fecha_compra BETWEEN ? AND ?', $inicio, $fin);
$resumenProductos = $conn->query('SELECT COUNT(*) AS cantidad, COALESCE(SUM(stock), 0) AS unidades FROM productos')->fetch_assoc();
$resumenServicios = $consultaResumen($conn, 'SELECT COUNT(*) AS cantidad, COALESCE(SUM(costo), 0) AS valor FROM servicio_tecnico WHERE fecha_ingreso BETWEEN ? AND ?', $inicio, $fin);

/* =========================================================
   ACCIÓN: OBTENER DETALLE SEGÚN EL TIPO DE REPORTE
   ========================================================= */
switch ($tipo) {
    case 'ventas':
        $sql = "SELECT DATE_FORMAT(v.fecha_venta, '%Y-%m-%d %H:%i') AS fecha,
                       CONCAT('Factura ', v.numero_factura) AS descripcion,
                       CONCAT(e.nombres, ' ', e.apellidos) AS responsable,
                       v.estado, v.total AS valor
                FROM ventas v INNER JOIN empleados e ON e.id_empleado = v.id_empleado
                WHERE v.fecha_venta >= ? AND v.fecha_venta < DATE_ADD(?, INTERVAL 1 DAY)
                ORDER BY v.fecha_venta DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $inicio, $fin);
        break;

    case 'compras':
        $sql = "SELECT DATE_FORMAT(c.fecha_compra, '%Y-%m-%d') AS fecha,
                       CONCAT('Compra ', COALESCE(c.numero_factura_compra, CONCAT('#', c.id_compra))) AS descripcion,
                       CONCAT(e.nombres, ' ', e.apellidos, ' · ', p.nombre_proveedor) AS responsable,
                       'Registrada' AS estado, c.total AS valor
                FROM compras c INNER JOIN empleados e ON e.id_empleado = c.id_empleado
                INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
                WHERE c.fecha_compra BETWEEN ? AND ? ORDER BY c.fecha_compra DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $inicio, $fin);
        break;

    case 'inventario':
        $sql = "SELECT p.codigo_producto AS fecha,
                       CONCAT(p.nombre_producto, ' · ', COALESCE(p.marca, 'Sin marca')) AS descripcion,
                       COALESCE(c.nombre_categoria, 'Sin categoría') AS responsable,
                       CONCAT(p.stock, ' unidades') AS estado,
                       p.stock * p.precio_compra AS valor
                FROM productos p LEFT JOIN categorias c ON c.id_categoria = p.id_categoria
                ORDER BY p.nombre_producto";
        $stmt = $conn->prepare($sql);
        break;

    case 'clientes':
        $sql = "SELECT DATE_FORMAT(fecha_registro, '%Y-%m-%d') AS fecha,
                       CONCAT(nombres, ' ', apellidos) AS descripcion,
                       COALESCE(ciudad, 'Sin ciudad') AS responsable,
                       'Registrado' AS estado, 0 AS valor
                FROM clientes WHERE fecha_registro BETWEEN ? AND ?
                ORDER BY fecha_registro DESC, id_cliente DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $inicio, $fin);
        break;

    case 'servicio':
        $sql = "SELECT DATE_FORMAT(st.fecha_ingreso, '%Y-%m-%d') AS fecha,
                       CONCAT('Servicio #', st.id_servicio, ' · ', COALESCE(st.descripcion, 'Sin descripción')) AS descripcion,
                       COALESCE(CONCAT(e.nombres, ' ', e.apellidos), 'Sin técnico') AS responsable,
                       COALESCE(st.estado, 'Sin estado') AS estado, COALESCE(st.costo, 0) AS valor
                FROM servicio_tecnico st LEFT JOIN empleados e ON e.id_empleado = st.id_empleado
                WHERE st.fecha_ingreso BETWEEN ? AND ? ORDER BY st.fecha_ingreso DESC, st.id_servicio DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $inicio, $fin);
        break;

    default:
        $sql = "SELECT DATE_FORMAT(v.fecha_venta, '%Y-%m-%d %H:%i') AS fecha,
                       'Venta' AS tipo, CONCAT('Factura ', v.numero_factura) AS descripcion,
                       CONCAT(e.nombres, ' ', e.apellidos) AS responsable, v.estado, v.total AS valor
                FROM ventas v INNER JOIN empleados e ON e.id_empleado = v.id_empleado
                WHERE v.fecha_venta >= ? AND v.fecha_venta < DATE_ADD(?, INTERVAL 1 DAY)
                UNION ALL
                SELECT DATE_FORMAT(c.fecha_compra, '%Y-%m-%d') AS fecha,
                       'Compra' AS tipo, CONCAT('Compra ', COALESCE(c.numero_factura_compra, CONCAT('#', c.id_compra))) AS descripcion,
                       p.nombre_proveedor AS responsable, 'Registrada' AS estado, c.total AS valor
                FROM compras c INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
                WHERE c.fecha_compra BETWEEN ? AND ?
                UNION ALL
                SELECT DATE_FORMAT(st.fecha_ingreso, '%Y-%m-%d') AS fecha,
                       'Servicio técnico' AS tipo, CONCAT('Servicio #', st.id_servicio, ' · ', COALESCE(st.descripcion, 'Sin descripción')) AS descripcion,
                       COALESCE(CONCAT(e.nombres, ' ', e.apellidos), 'Sin técnico') AS responsable,
                       COALESCE(st.estado, 'Sin estado') AS estado, COALESCE(st.costo, 0) AS valor
                FROM servicio_tecnico st LEFT JOIN empleados e ON e.id_empleado = st.id_empleado
                WHERE st.fecha_ingreso BETWEEN ? AND ?
                ORDER BY fecha DESC LIMIT 300";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssss', $inicio, $fin, $inicio, $fin, $inicio, $fin);
        break;
}

$stmt->execute();
$filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$titulos = ['general' => 'Reporte general', 'ventas' => 'Ventas', 'compras' => 'Compras', 'inventario' => 'Inventario', 'clientes' => 'Clientes', 'servicio' => 'Servicio técnico'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes | SYSMOVILWT</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css"><link rel="stylesheet" href="../assets/css/components.css"><link rel="stylesheet" href="../assets/css/reportes.css">
    <style>
        .report-form{display:flex;flex-wrap:wrap;align-items:end;gap:16px}.report-form .form-group{min-width:180px;flex:1}.report-form label{display:block;margin-bottom:6px}.report-form input,.report-form select{width:100%;padding:11px;border:1px solid #d8deea;border-radius:8px}.report-table-card{overflow:auto}.report-table{min-width:800px;width:100%}.report-error{padding:12px;background:#fff0f0;color:#a52323;border-radius:8px;margin-bottom:16px}.report-total{font-weight:600}.report-export{display:flex;gap:10px;margin:12px 0}
        .reports-filters .report-form button.btn-reset{box-sizing:border-box;width:55px!important;min-width:55px;height:55px!important;min-height:55px;max-width:55px;flex:0 0 55px;padding:0!important;border:0!important;border-radius:16px;background:#F8FAFC;color:#475569;display:flex!important;align-items:center;justify-content:center;cursor:pointer;transition:.30s;font-size:18px;line-height:1}
        .reports-filters .report-form button.btn-reset:hover{background:var(--primary);color:#fff;transform:rotate(180deg)}
        .reports-filters .report-form button.btn-reset:focus-visible{outline:3px solid #cbd5e1;outline-offset:2px}
    </style>
    <link rel="stylesheet" href="../assets/css/acciones-compartidas.css">
</head>
<body>
    <!-- ACCIÓN: MOSTRAR NAVEGACIÓN PRINCIPAL -->
    <aside class="sidebar">
        <div class="sidebar-logo"><img src="../assets/img/logo/logowt.png" alt="Logo WT SERVIPHONE" class="logo-sidebar"><div class="logo-text"><h2>SYSMOVILWT</h2><span>Sistema de Gestión Empresarial</span></div></div>
        <nav class="sidebar-menu"><ul>
            <li><a href="dashboard.php"><i class="fa-solid fa-house"></i><span>Dashboard</span></a></li><li><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i><span>Inventario</span></a></li>
            <li><a href="ventas.php"><i class="fa-solid fa-cart-shopping"></i><span>Ventas</span></a></li><li><a href="compras.php"><i class="fa-solid fa-bag-shopping"></i><span>Compras</span></a></li>
            <li><a href="clientes.php"><i class="fa-solid fa-users"></i><span>Clientes</span></a></li><li><a href="proveedores.php"><i class="fa-solid fa-truck"></i><span>Proveedores</span></a></li>
            <li><a href="usuarios.php"><i class="fa-solid fa-user-shield"></i><span>Usuarios</span></a></li><li><a href="serviciotecnico.php"><i class="fa-solid fa-screwdriver-wrench"></i><span>Servicio técnico</span></a></li>
            <li class="active"><a href="reportes.php"><i class="fa-solid fa-chart-column"></i><span>Reportes</span></a></li><li><a href="configuracion.php"><i class="fa-solid fa-gear"></i><span>Configuración</span></a></li>
        </ul></nav><button class="logout-button" id="btnLogout" type="button"><i class="fa-solid fa-right-from-bracket"></i><span>Cerrar sesión</span></button>
    </aside>
    <header class="topbar"><div class="topbar-left"><button class="menu-toggle" type="button"><i class="fa-solid fa-bars"></i></button><div class="topbar-title"><h1>Reportes</h1><span>Consulta información registrada en MySQL</span></div></div><div class="topbar-right"><div class="topbar-date"><i class="fa-regular fa-calendar"></i><span><?= h(date('d/m/Y')) ?></span></div><button class="notification-button" type="button" aria-label="Notificaciones"><i class="fa-regular fa-bell"></i><span class="notification-dot"></span></button><div class="topbar-profile"><div class="profile-avatar">WT</div><div class="profile-info"><strong id="topbarUser">Leonardo</strong><span id="topbarRole">Administrador</span></div></div></div></header>
    <main class="reports-main">
        <section class="reports-header"><div class="reports-title"><h2><i class="fa-solid fa-chart-column"></i> Reportes del sistema</h2><p>Filtra y consulta ventas, compras, inventario, clientes y servicios técnicos.</p></div></section>

        <!-- ACCIÓN: FILTRAR EL REPORTE -->
        <section class="reports-filters"><form class="report-form" method="get" action="reportes.php">
            <div class="form-group"><label for="tipo">Tipo de reporte</label><select id="tipo" name="tipo">
                <?php foreach ($titulos as $valor => $etiqueta): ?><option value="<?= h($valor) ?>" <?= $tipo === $valor ? 'selected' : '' ?>><?= h($etiqueta) ?></option><?php endforeach; ?>
            </select></div>
            <div class="form-group"><label for="inicio">Fecha inicial</label><input type="date" id="inicio" name="inicio" value="<?= h($inicio) ?>" required></div>
            <div class="form-group"><label for="fin">Fecha final</label><input type="date" id="fin" name="fin" value="<?= h($fin) ?>" required></div>
            <button class="btn-generate" type="submit"><i class="fa-solid fa-magnifying-glass-chart"></i> Generar reporte</button>
            <button class="btn-reset" id="btnResetFilters" type="button" title="Limpiar filtros" aria-label="Limpiar filtros" data-default-start="<?= h($inicioPredeterminado) ?>" data-default-end="<?= h($hoy->format('Y-m-d')) ?>"><i class="fa-solid fa-rotate-right"></i></button>
        </form></section>

        <?php if ($error !== ''): ?><div class="report-error" role="alert"><?= h($error) ?></div><?php endif; ?>

        <!-- ACCIÓN: RESUMIR INDICADORES -->
        <section class="report-summary">
            <article class="report-card"><div class="report-card-icon sales"><i class="fa-solid fa-cart-shopping"></i></div><div><span>Ventas del rango</span><strong><?= (int)$resumenVentas['cantidad'] ?> · <?= h(moneda($resumenVentas['valor'])) ?></strong></div></article>
            <article class="report-card"><div class="report-card-icon purchases"><i class="fa-solid fa-bag-shopping"></i></div><div><span>Compras del rango</span><strong><?= (int)$resumenCompras['cantidad'] ?> · <?= h(moneda($resumenCompras['valor'])) ?></strong></div></article>
            <article class="report-card"><div class="report-card-icon products"><i class="fa-solid fa-boxes-stacked"></i></div><div><span>Productos / unidades</span><strong><?= (int)$resumenProductos['cantidad'] ?> / <?= (int)$resumenProductos['unidades'] ?></strong></div></article>
            <article class="report-card"><div class="report-card-icon services"><i class="fa-solid fa-screwdriver-wrench"></i></div><div><span>Servicios del rango</span><strong><?= (int)$resumenServicios['cantidad'] ?> · <?= h(moneda($resumenServicios['valor'])) ?></strong></div></article>
        </section>

        <!-- ACCIÓN: EXPORTAR RESULTADOS A CSV -->
        <div class="report-export"><button type="button" class="btn-export" onclick="exportarCSV()"><i class="fa-solid fa-file-csv"></i> Exportar CSV</button></div>

        <!-- ACCIÓN: MOSTRAR DETALLE DEL REPORTE -->
        <section class="report-table-card"><div class="table-header"><div><h3><i class="fa-solid fa-table-list"></i> <?= h($titulos[$tipo]) ?></h3><span><?= count($filas) ?> registro(s) en el resultado</span></div></div>
            <div class="table-responsive"><table class="report-table" id="tablaReporte"><thead><tr><th>Fecha / Código</th><?php if ($tipo === 'general'): ?><th>Tipo</th><?php endif; ?><th>Descripción</th><th>Responsable / Categoría</th><th>Estado / Unidades</th><th>Valor</th></tr></thead><tbody>
            <?php if (!$filas): ?><tr><td colspan="6"><div class="empty-report"><i class="fa-solid fa-chart-simple"></i><strong>No hay registros para mostrar</strong><span>Prueba otro rango o tipo de reporte.</span></div></td></tr>
            <?php else: foreach ($filas as $fila): ?><tr>
                <td><?= h($fila['fecha']) ?></td><?php if ($tipo === 'general'): ?><td><?= h($fila['tipo']) ?></td><?php endif; ?>
                <td><?= h($fila['descripcion']) ?></td><td><?= h($fila['responsable']) ?></td><td><?= h($fila['estado']) ?></td><td class="report-total"><?= h(moneda($fila['valor'])) ?></td>
            </tr><?php endforeach; endif; ?>
            </tbody></table></div>
        </section>
    </main>
    <script src="../assets/js/utils.js"></script>
    <script>
        function exportarCSV(){
            const filas=[...document.querySelectorAll('#tablaReporte tr')].map(f=>[...f.cells].map(c=>'"'+c.innerText.replaceAll('"','""')+'"').join(','));
            const archivo=new Blob(['\uFEFF'+filas.join('\r\n')],{type:'text/csv;charset=utf-8'});
            const enlace=document.createElement('a');enlace.href=URL.createObjectURL(archivo);enlace.download='reporte-<?= h($tipo) ?>-<?= h($inicio) ?>-<?= h($fin) ?>.csv';enlace.click();URL.revokeObjectURL(enlace.href);
        }
        document.getElementById('btnResetFilters')?.addEventListener('click', function () {
            const formulario = this.closest('form');
            formulario.elements.tipo.value = 'general';
            formulario.elements.inicio.value = this.dataset.defaultStart;
            formulario.elements.fin.value = this.dataset.defaultEnd;
            if (formulario.requestSubmit) formulario.requestSubmit();
            else formulario.submit();
        });
    </script>
</body>
</html>
