
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
require_once __DIR__ . '/../config/conexion.php';
$conn->set_charset('utf8mb4');

function h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function cop($value): string {
    return '$' . number_format((float)$value, 0, ',', '.');
}

$idVenta = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idVenta || $idVenta <= 0) {
    http_response_code(400);
    exit('Número de venta no válido.');
}

$stmt = $conn->prepare(
    "SELECT
        v.id_venta,
        v.numero_factura,
        v.fecha_venta,
        v.metodo_pago,
        v.subtotal,
        v.iva,
        v.descuento,
        v.total,
        v.estado,
        c.nombres AS cliente_nombres,
        c.apellidos AS cliente_apellidos,
        c.cedula AS cliente_cedula,
        c.telefono AS cliente_telefono,
        c.correo AS cliente_correo,
        c.direccion AS cliente_direccion,
        c.ciudad AS cliente_ciudad,
        e.nombres AS empleado_nombres,
        e.apellidos AS empleado_apellidos,
        e.cargo AS empleado_cargo
     FROM ventas v
     INNER JOIN empleados e ON e.id_empleado = v.id_empleado
     LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
     WHERE v.id_venta = ?
     LIMIT 1"
);

$stmt->bind_param('i', $idVenta);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$venta) {
    http_response_code(404);
    exit('La factura solicitada no existe.');
}

$stmt = $conn->prepare(
    "SELECT
        d.id_producto,
        d.cantidad,
        d.precio_unitario,
        d.subtotal,
        p.codigo_producto,
        p.nombre_producto,
        p.marca,
        p.modelo
     FROM detalle_venta d
     INNER JOIN productos p ON p.id_producto = d.id_producto
     WHERE d.id_venta = ?
     ORDER BY d.id_detalle_venta ASC"
);

$stmt->bind_param('i', $idVenta);
$stmt->execute();
$detalles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$nombreCliente = trim(
    ($venta['cliente_nombres'] ?? '') . ' ' .
    ($venta['cliente_apellidos'] ?? '')
);

if ($nombreCliente === '') {
    $nombreCliente = 'Cliente general';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Factura <?= h($venta['numero_factura']) ?> | WT SERVIPHONE</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 30px 16px;
    background: #f2f4f8;
    color: #202939;
    font-family: Poppins, Arial, sans-serif;
    font-size: 13px;
}

.invoice-actions {
    max-width: 850px;
    margin: 0 auto 16px;
    display: flex;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}

.action-btn {
    border: 0;
    border-radius: 8px;
    padding: 11px 15px;
    font: 600 12px Poppins, sans-serif;
    text-decoration: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-print {
    background: #c62828;
    color: #fff;
}

.btn-back {
    background: #fff;
    color: #27364b;
    border: 1px solid #e1e5ed;
}

.invoice {
    max-width: 850px;
    margin: auto;
    padding: 38px;
    background: #fff;
    border: 1px solid #e3e7ef;
    border-radius: 14px;
    box-shadow: 0 8px 28px #1720330d;
}

.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 24px;
    padding-bottom: 24px;
    border-bottom: 2px solid #c62828;
}

.brand h1 {
    margin: 0 0 6px;
    color: #c62828;
    font-size: 25px;
    font-weight: 700;
}

.brand p {
    margin: 3px 0;
    color: #687386;
    font-size: 11px;
}

.invoice-number {
    text-align: right;
    min-width: 180px;
}

.invoice-number h2 {
    margin: 0 0 8px;
    font-size: 22px;
    color: #172033;
}

.invoice-number strong {
    display: block;
    overflow-wrap: anywhere;
    color: #c62828;
}

.invoice-number small {
    display: block;
    margin-top: 7px;
    color: #687386;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin: 24px 0;
}

.info-card {
    padding: 15px;
    border: 1px solid #e6e9f0;
    border-radius: 9px;
    min-width: 0;
}

.info-card h3 {
    margin: 0 0 10px;
    font-size: 12px;
    color: #c62828;
    text-transform: uppercase;
    letter-spacing: .3px;
}

.info-card p {
    margin: 5px 0;
    overflow-wrap: anywhere;
}

.muted {
    color: #687386;
}

.table-wrap {
    width: 100%;
    overflow-x: auto;
}

.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
}

.items-table th {
    padding: 11px 9px;
    background: #f4f6fa;
    color: #455166;
    text-align: left;
    font-size: 11px;
}

.items-table td {
    padding: 12px 9px;
    border-bottom: 1px solid #e9edf3;
    vertical-align: top;
}

.items-table th:last-child,
.items-table td:last-child,
.items-table th:nth-last-child(2),
.items-table td:nth-last-child(2) {
    text-align: right;
    white-space: nowrap;
}

.product-name {
    font-weight: 600;
}

.product-detail {
    display: block;
    margin-top: 4px;
    color: #687386;
    font-size: 10px;
}

.totals {
    width: 100%;
    max-width: 330px;
    margin: 22px 0 0 auto;
}

.total-line {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    padding: 8px 0;
}

.total-line.final {
    margin-top: 5px;
    padding-top: 14px;
    border-top: 2px solid #c62828;
    font-size: 18px;
    font-weight: 700;
}

.total-line.final strong {
    color: #c62828;
}

.payment-info {
    margin-top: 25px;
    padding: 14px;
    background: #f7f8fb;
    border-radius: 9px;
}

.footer-note {
    margin-top: 28px;
    padding-top: 17px;
    border-top: 1px solid #e6e9f0;
    text-align: center;
    color: #687386;
    font-size: 11px;
}

@media (max-width: 600px) {
    body {
        padding: 12px 8px;
    }

    .invoice {
        padding: 18px 12px;
        border-radius: 9px;
    }

    .invoice-header {
        flex-direction: column;
        gap: 15px;
    }

    .invoice-number {
        text-align: left;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .items-table {
        min-width: 550px;
    }
}

@page {
    size: A4;
    margin: 12mm;
}

@media print {
    body {
        padding: 0;
        background: #fff;
        font-size: 11px;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .invoice-actions {
        display: none !important;
    }

    .invoice {
        max-width: none;
        margin: 0;
        padding: 0;
        border: 0;
        border-radius: 0;
        box-shadow: none;
    }

    .info-card,
    .payment-info,
    .items-table tr {
        break-inside: avoid;
    }

    .table-wrap {
        overflow: visible;
    }

    .items-table {
        min-width: 0;
    }
}

.invoice-logo {
    display: block;
    width: 190px;
    max-width: 100%;
    max-height: 95px;
    object-fit: contain;
    object-position: left center;
    margin-bottom: 8px;
}

.brand {
    min-width: 0;
}

@media print {
    .invoice-logo {
        max-height: 85px;
    }
}

</style>
</head>

<body>

<div class="invoice-actions">
    <a class="action-btn btn-back" href="ventas.php">
        <i class="fa-solid fa-arrow-left"></i> Volver a ventas
    </a>

    <button class="action-btn btn-print" type="button" onclick="window.print()">
        <i class="fa-solid fa-print"></i> Imprimir factura
    </button>
</div>

<main class="invoice">
    <header class="invoice-header">
        
<div class="brand">
    <img
        src="../assets/img/logo/logowt.png"
        alt="Logo WT SERVIPHONE"
        class="invoice-logo"
    >
    <p>Tu aliado en tecnología</p>
    <p>Venta de equipos, accesorios y servicio técnico</p>
</div>


        <div class="invoice-number">
            <h2>COMPROBANTE</h2>
            <strong><?= h($venta['numero_factura']) ?></strong>
            <small>
                Fecha:
                <?= h(date('d/m/Y H:i', strtotime($venta['fecha_venta']))) ?>
            </small>
            <small>Estado: <?= h($venta['estado']) ?></small>
        </div>
    </header>

    <section class="info-grid">
        <div class="info-card">
            <h3><i class="fa-solid fa-user"></i> Datos del cliente</h3>
            <p><strong><?= h($nombreCliente) ?></strong></p>

            <?php if (!empty($venta['cliente_cedula'])): ?>
                <p>Documento: <?= h($venta['cliente_cedula']) ?></p>
            <?php endif; ?>

            <?php if (!empty($venta['cliente_telefono'])): ?>
                <p>Teléfono: <?= h($venta['cliente_telefono']) ?></p>
            <?php endif; ?>

            <?php if (!empty($venta['cliente_correo'])): ?>
                <p>Correo: <?= h($venta['cliente_correo']) ?></p>
            <?php endif; ?>

            <?php if (!empty($venta['cliente_direccion'])): ?>
                <p>Dirección: <?= h($venta['cliente_direccion']) ?></p>
            <?php endif; ?>

            <?php if (!empty($venta['cliente_ciudad'])): ?>
                <p>Ciudad: <?= h($venta['cliente_ciudad']) ?></p>
            <?php endif; ?>
        </div>

        <div class="info-card">
            <h3><i class="fa-solid fa-store"></i> Datos de la venta</h3>
            <p>
                <span class="muted">Responsable:</span><br>
                <strong>
                    <?= h($venta['empleado_nombres'] . ' ' . $venta['empleado_apellidos']) ?>
                </strong>
            </p>
            <p>
                <span class="muted">Cargo:</span>
                <?= h($venta['empleado_cargo']) ?>
            </p>
            <p>
                <span class="muted">Método de pago:</span>
                <strong><?= h($venta['metodo_pago']) ?></strong>
            </p>
        </div>
    </section>

    <section>
        <h3>Productos vendidos</h3>

        <div class="table-wrap">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $d): ?>
                        <tr>
                            <td>
                                <span class="product-name"><?= h($d['nombre_producto']) ?></span>
                                <span class="product-detail">
                                    Código: <?= h($d['codigo_producto']) ?>
                                    <?php if (!empty($d['marca'])): ?>
                                        · <?= h($d['marca']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($d['modelo'])): ?>
                                        · <?= h($d['modelo']) ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td><?= (int)$d['cantidad'] ?></td>
                            <td><?= h(cop($d['precio_unitario'])) ?></td>
                            <td><strong><?= h(cop($d['subtotal'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$detalles): ?>
                        <tr>
                            <td colspan="4">No se encontraron productos para esta venta.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="totals">
        <div class="total-line">
            <span>Subtotal</span>
            <strong><?= h(cop($venta['subtotal'])) ?></strong>
        </div>

        <div class="total-line">
            <span>Descuento</span>
            <strong><?= h(cop($venta['descuento'])) ?></strong>
        </div>

        <div class="total-line">
            <span>IVA registrado</span>
            <strong><?= h(cop($venta['iva'])) ?></strong>
        </div>

        <div class="total-line final">
            <span>Total</span>
            <strong><?= h(cop($venta['total'])) ?></strong>
        </div>
    </section>

    <div class="payment-info">
        <strong><i class="fa-solid fa-circle-check"></i> Información de pago</strong>
        <p style="margin-bottom:0">
            Método: <?= h($venta['metodo_pago']) ?>.
            Estado de la venta: <?= h($venta['estado']) ?>.
        </p>
    </div>

    <footer class="footer-note">
        Gracias por confiar en WT SERVIPHONE.<br>
        Este documento es un comprobante interno de venta y no sustituye una factura electrónica cuando esta sea exigible.
    </footer>
</main>

</body>
</html>
