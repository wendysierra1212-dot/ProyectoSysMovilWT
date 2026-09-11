<?php

require_once "../config/conexion.php";

$sql = "SELECT
            p.id_producto,
            p.codigo_producto,
            p.tipo_producto,
            p.nombre_producto,
            p.id_marca,
            p.marca,
            p.modelo,
            p.imei,
            p.descripcion,
            p.precio_compra,
            p.precio_venta,
            p.stock,
            p.stock_minimo,
            p.garantia_meses,
            p.condicion,
            p.id_categoria,
            p.id_proveedor,
            c.nombre_categoria,
            pr.nombre_proveedor
        FROM productos p
        INNER JOIN categorias c
            ON p.id_categoria = c.id_categoria
        INNER JOIN proveedores pr
            ON p.id_proveedor = pr.id_proveedor
        ORDER BY p.id_producto DESC";

$resultado = $conn->query($sql);

$totalProductos = 0;
$stockBajo = 0;
$productosAgotados = 0;

if ($resultado) {

    $totalProductos = $resultado->num_rows;

    $resultado->data_seek(0);

    while ($producto = $resultado->fetch_assoc()) {

        if ($producto["stock"] == 0) {
            $productosAgotados++;
        }

        if ($producto["stock"] > 0 && $producto["stock"] <= 5) {
            $stockBajo++;
        }
    }

    $resultado->data_seek(0);
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Inventario | SYSMOVILWT</title>

    <!-- Google Fonts -->

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">

    <!-- Font Awesome -->

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Estilos -->

    <link rel="stylesheet"
          href="../assets/css/styles.css">

    <link rel="stylesheet"
          href="../assets/css/components.css">

    <link rel="stylesheet"
          href="../assets/css/dashboard.css">

    <link rel="stylesheet"
          href="../assets/css/inventario.css">

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

        <h2>SYSMOVILWT</h2>

        <span>Sistema de Gestión Empresarial</span>

    </div>

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

                <li class="active"><a href="inventario.php"><i class="fa-solid fa-boxes-stacked"></i><span>Inventario</span></a></li>

                <li><a href="ventas.html"><i class="fa-solid fa-cart-shopping"></i><span>Ventas</span></a></li>
                
                <li>
                    <a href="compras.html">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <span>Compras</span>
                    </a>
                </li>
                <li><a href="clientes.html"><i class="fa-solid fa-users"></i><span>Clientes</span></a></li>

                <li><a href="proveedores.html"><i class="fa-solid fa-truck"></i><span>Proveedores</span></a></li>

                <li><a href="usuarios.html"><i class="fa-solid fa-user-shield"></i><span>Usuarios</span></a></li>

                <li><a href="servicio.html"><i class="fa-solid fa-screwdriver-wrench"></i><span>Servicio Técnico</span></a></li>

                <li><a href="reportes.html"><i class="fa-solid fa-chart-column"></i><span>Reportes</span></a></li>
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

            <span>Cerrar sesión</span>

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

                <h1>Inventario</h1>

                <span>Gestión de productos</span>

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

        <section class="inventory-container">

            <div class="inventory-header">

                <div>

                    <h2>

                        <i class="fa-solid fa-boxes-stacked"></i>

                        Inventario

                    </h2>

                    <p>

                        Administra todos los productos de WT SERVIPHONE.

                    </p>

                </div>

            </div>

            <div class="inventory-toolbar">

                <div class="inventory-search">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        id="searchProduct"
                        placeholder="Buscar producto...">

                </div>

                <select id="filterCategory">

                    <option>Todas las categorías</option>

                    <option>Celulares</option>

                    <option>Computadores</option>

                    <option>Portátiles</option>

                    <option>Tablets</option>

                    <option>Smartwatch</option>

                    <option>Consolas</option>

                    <option>Accesorios</option>

                </select>

                <select id="filterStatus">

                    <option>Todos los estados</option>

                    <option>Disponible</option>

                    <option>Stock Bajo</option>

                    <option>Agotado</option>

                </select>

                <button
                    id="btnResetFilters"
                    class="btn-reset">

                    <i class="fa-solid fa-rotate-right"></i>

                </button>

                <button
                    id="btnNuevoProducto"
                    class="btn-new">

                    <i class="fa-solid fa-plus"></i>

                    Nuevo Producto

                </button>

            </div>

            <!--=========================================
            =              TABLA
            ==========================================-->

            <div class="inventory-table-card">

                <table class="inventory-table">

                    <thead>

                        <tr>

                            <th>Código</th>

                            <th>Producto</th>

                            <th>Marca</th>

                            <th>Categoría</th>

                            <th>Precio</th>

                            <th>Stock</th>

                            <th>Estado</th>

                            <th>Acciones</th>

                        </tr>

                    </thead>

                   <tbody id="inventoryTableBody">

    <?php if ($resultado && $resultado->num_rows > 0): ?>

        <?php while ($producto = $resultado->fetch_assoc()): ?>

            <?php

            if ($producto["stock"] == 0) {

                $estado = "Agotado";

            } elseif ($producto["stock"] <= 5) {

                $estado = "Stock bajo";

            } else {

                $estado = "Disponible";

            }

            ?>

            <tr>

                <td>
                    <?= htmlspecialchars($producto["codigo_producto"]) ?>
                </td>

                <td>
                    <?= htmlspecialchars($producto["nombre_producto"]) ?>
                </td>

                <td>
                    <?= htmlspecialchars($producto["marca"] ?? "—") ?>
                </td>

                <td>
                    <?= htmlspecialchars($producto["nombre_categoria"]) ?>
                </td>

                <td>
                    $<?= number_format($producto["precio_venta"], 0, ',', '.') ?>
                </td>

                <td>
                    <?= $producto["stock"] ?>
                </td>

                <td>

                    <span class="status-badge">

                        <?= $estado ?>

                    </span>

                </td>

               <td>

    <a
        href="#"
        class="btn-editar-producto"
        title="Editar producto"

        data-id="<?= $producto["id_producto"] ?>"

        data-codigo="<?= htmlspecialchars($producto["codigo_producto"], ENT_QUOTES) ?>"

        data-nombre="<?= htmlspecialchars($producto["nombre_producto"], ENT_QUOTES) ?>"

        data-tipo="<?= htmlspecialchars($producto["tipo_producto"], ENT_QUOTES) ?>"

        data-marca="<?= htmlspecialchars($producto["marca"] ?? "", ENT_QUOTES) ?>"

        data-modelo="<?= htmlspecialchars($producto["modelo"] ?? "", ENT_QUOTES) ?>"

        data-imei="<?= htmlspecialchars($producto["imei"] ?? "", ENT_QUOTES) ?>"

        data-descripcion="<?= htmlspecialchars($producto["descripcion"] ?? "", ENT_QUOTES) ?>"

        data-precio-compra="<?= $producto["precio_compra"] ?>"

        data-precio-venta="<?= $producto["precio_venta"] ?>"

        data-stock="<?= $producto["stock"] ?>"

        data-stock-minimo="<?= $producto["stock_minimo"] ?>"

        data-garantia="<?= $producto["garantia_meses"] ?>"

        data-condicion="<?= htmlspecialchars($producto["condicion"] ?? "Nuevo", ENT_QUOTES) ?>"

        data-categoria="<?= $producto["id_categoria"] ?>"

        data-proveedor="<?= $producto["id_proveedor"] ?>">

        <i class="fa-solid fa-pen-to-square"></i>

    </a>


    <a
        href="../productos/eliminar.php?id=<?= $producto["id_producto"] ?>"
        title="Eliminar producto"
        onclick="return confirm('¿Está seguro de eliminar este producto?');">

        <i class="fa-solid fa-trash"></i>

    </a>

</td>
            </tr>

        <?php endwhile; ?>

    <?php else: ?>

        <tr class="inventory-empty">

            <td colspan="8">

                <div class="empty-state">

                    <i class="fa-solid fa-box-open"></i>

                    <h3>
                        No hay productos registrados
                    </h3>

                    <p>
                        Haz clic en
                        <strong>Nuevo Producto</strong>
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

    </main>

    <!--=========================================
    =              MODAL PRODUCTO
    ==========================================-->

    <div
        class="inventory-modal"
        id="productModal">

        <div class="inventory-modal-content">

            <div class="inventory-modal-header">

                <h2>

                    <i class="fa-solid fa-box"></i>

                    Nuevo Producto

                </h2>

                <button
                    id="btnCloseModal"
                    class="inventory-modal-close">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>

        <form
            id="productForm"
            class="inventory-form"
            method="POST"
            action="../productos/crear.php">
<input
    type="hidden"
    id="productoCondicion"
    name="condicion"
    value="Nuevo">
<!--=========================================
=              INFORMACIÓN GENERAL
==========================================-->

<div class="form-section">

    <h3>Información General</h3>

    <div class="form-grid">

        <div class="form-group">
            <label for="productoCodigo">Código *</label>
            <input type="text" id="productoCodigo" name="codigo_producto" required>
        </div>

        <div class="form-group">
            <label for="productoNombre">Nombre *</label>
           <input type="text" id="productoNombre" name="nombre_producto" required>
        </div>

       <div class="form-group">

    <label for="productoTipo">

        Tipo de Producto *

    </label>

    <select id="productoTipo" name="tipo_producto" required>

        <option value="">Seleccione...</option>

        <option value="Equipo">Equipo</option>

        <option value="Accesorio">Accesorio</option>

        <option value="Repuesto">Repuesto</option>

    </select>

    </div>

    <div class="form-group">

    <label for="productoMarca">

        Marca *

    </label>

    <select id="productoMarca" name="marca" required>

        <option value="">Seleccione...</option>

        <option>Apple</option>
        <option>Samsung</option>
        <option>Xiaomi</option>
        <option>Motorola</option>
        <option>Honor</option>
        <option>Huawei</option>
        <option>Oppo</option>
        <option>Vivo</option>
        <option>Realme</option>
        <option>Infinix</option>
        <option>Tecno</option>
        <option>Nokia</option>
        <option>LG</option>
        <option>Sony</option>
        <option>Otra</option>

    </select>

    </div>

    <div class="form-group">

     <label for="productoCategoria">

        Categoría *

     </label>

    <select id="productoCategoria" name="id_categoria" required>

    <option value="">Seleccione...</option>

    <?php

    $categoriasFormulario = $conn->query(
        "SELECT id_categoria, nombre_categoria
         FROM categorias
         WHERE estado = 'Activa'
         ORDER BY nombre_categoria"
    );

    while ($categoria = $categoriasFormulario->fetch_assoc()):

    ?>

        <option value="<?= $categoria['id_categoria'] ?>">
            <?= htmlspecialchars($categoria['nombre_categoria']) ?>
        </option>

    <?php endwhile; ?>

</select>

    </div>

         <div class="form-group">

         <label for="productoProveedor">
          Proveedor
         </label>
        <select id="productoProveedor" name="id_proveedor" required>

    <option value="">Seleccione...</option>

    <?php

    $proveedoresFormulario = $conn->query(
        "SELECT id_proveedor, nombre_proveedor
         FROM proveedores
         WHERE estado = 'Activo'
         ORDER BY nombre_proveedor"
    );

    while ($proveedor = $proveedoresFormulario->fetch_assoc()):

    ?>

        <option value="<?= $proveedor['id_proveedor'] ?>">
            <?= htmlspecialchars($proveedor['nombre_proveedor']) ?>
        </option>

    <?php endwhile; ?>

</select>
         </div>

        <div class="form-group">
            <label for="productoEstado">Estado</label>
            <select id="productoEstado">
                <option>Disponible</option>
                <option>Stock Bajo</option>
                <option>Agotado</option>
            </select>
        </div>

    </div>

</div>

<div class="form-section">

    <h3>Información Comercial</h3>

    <div class="form-grid">

        <div class="form-group">
            <label for="productoCosto">Precio Compra</label>
           <input
           type="number"
           id="productoCosto"
           name="precio_compra"
           min="0">

        </div>

        <div class="form-group">
            <label for="productoPrecio">Precio Venta *</label>
            <input
           type="number"
           id="productoPrecio"
           name="precio_venta"
           min="0"
           required>
        </div>

    </div>

</div>

<div class="form-section">

    <h3>Inventario</h3>

    <div class="form-grid">

        <div class="form-group">
            <label for="productoStock">Stock Inicial *</label>
            <input
            type="number"
            id="productoStock"
            name="stock"
            min="0"
            required>
        </div>

        <div class="form-group">
            <label for="productoStockMinimo">Stock Mínimo</label>
           <input
           type="number"
           id="productoStockMinimo"
           name="stock_minimo"
           min="0">
        </div>

        <div class="form-group">
        <label for="productoGarantia">Garantía (Meses)</label>
        <input
        type="number"
        id="productoGarantia"
        name="garantia_meses"
        min="0"
        placeholder="Ej: 12">
        </div>

        <div class="form-group">
            <label for="productoUbicacion">Ubicación</label>
            <input type="text" id="productoUbicacion">
        </div>

        <div class="form-group">
            <label for="productoCodigoBarras">Código de Barras</label>
            <input type="text" id="productoCodigoBarras">
        </div>

    </div>

</div>

    <div class="form-section">

    <h3>Información Adicional</h3>

    <div
    class="form-group"
    id="grupoSerial">

    <label for="productoSerial">

        IMEI / Serial

    </label>

    <input
    type="text"
    id="productoSerial"
    name="imei"
    placeholder="Solo para equipos">

    </div>

    <div class="form-group">

        <label for="productoDescripcion">

            Descripción

        </label>

        <textarea
        id="productoDescripcion"
        name="descripcion"
        rows="4"></textarea>
        </div>

</div>

<div class="inventory-form-actions">

    <button
        type="button"
        class="btn-cancel"
        id="btnCancelarProducto">

        Cancelar

    </button>

    <button
        type="submit"
        class="btn-save">

        Guardar Producto

    </button>

</div>

    <!--=========================================
    =              JAVASCRIPT
    ==========================================-->
<script src="../assets/js/utils.js"></script>

<script src="../assets/js/inventario.js"></script>

</body>

</html>
