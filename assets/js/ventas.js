/**
 * ==================================================
 * SYSMOVILWT
 * MÓDULO DE VENTAS
 * ==================================================
 */

const ventasController = new VentasController();

/*==================================================
=            ELEMENTOS DEL DOM
==================================================*/

const productsList = document.getElementById("productsList");

const cartItems = document.getElementById("cartItems");

const subtotalSale = document.getElementById("subtotalSale");

const ivaSale = document.getElementById("ivaSale");

const discountSale = document.getElementById("discountSale");

const totalSale = document.getElementById("totalSale");

const invoiceNumber = document.getElementById("invoiceNumber");

const saleDate = document.getElementById("saleDate");

const saleUser = document.getElementById("saleUser");

const btnCancelSale = document.getElementById("btnCancelSale");

const btnFinishSale = document.getElementById("btnFinishSale");

const searchSaleProduct = document.getElementById("searchSaleProduct");

const paymentOptions = document.querySelectorAll(

    "input[name='paymentMethod']"

);

/*==================================================
=            INICIALIZACIÓN
==================================================*/

document.addEventListener(

    "DOMContentLoaded",

    iniciar

);

function iniciar(){

    inicializarCerrarSesion();

    cargarFecha();

    cargarUsuario();

    cargarInformacionVenta();

    cargarProductos();

    renderizarCarrito();

    inicializarEventos();

}

/*==================================================
=            EVENTOS
==================================================*/

function inicializarEventos(){

    if(btnCancelSale){

        btnCancelSale.addEventListener(

            "click",

            cancelarVenta

        );

    }

    if(btnFinishSale){

        btnFinishSale.addEventListener(

            "click",

            finalizarVenta

        );

    }

    if(searchSaleProduct){

        searchSaleProduct.addEventListener(

            "input",

            buscarProductos

        );

    }

    paymentOptions.forEach(opcion=>{

        opcion.addEventListener(

            "change",

            cambiarMetodoPago

        );

    });

}

/*==================================================
=            USUARIO
==================================================*/

function cargarUsuario(){

    const usuario = JSON.parse(

        localStorage.getItem(

            "usuarioActivo"

        )

    );

    if(!usuario){

        window.location.href="../login.html";

        return;

    }

    document.getElementById(

        "topbarUser"

    ).textContent = usuario.nombre;

    document.getElementById(

        "topbarRole"

    ).textContent = usuario.rol;

    saleUser.textContent = usuario.nombre;

}

/*==================================================
=            INFORMACIÓN VENTA
==================================================*/

function cargarInformacionVenta(){

    invoiceNumber.textContent =

        ventasController.obtenerNumeroFactura();

    saleDate.textContent =

        new Date().toLocaleDateString(

            "es-CO"

        );

}

/*==================================================
=            CARGAR PRODUCTOS
==================================================*/

function cargarProductos(){

    const productos = ventasController.obtenerProductos();

    renderizarProductos(productos);

}

/*==================================================
=            RENDERIZAR PRODUCTOS
==================================================*/

function renderizarProductos(productos){

    if(productos.length === 0){

        productsList.innerHTML = `

            <div class="empty-cart">

                <i class="fa-solid fa-box-open"></i>

                <h4>

                    No existen productos registrados

                </h4>

                <p>

                    Agrega productos desde Inventario.

                </p>

            </div>

        `;

        return;

    }

    productsList.innerHTML = "";

    productos.forEach(producto=>{

        const stockClass = obtenerClaseStock(producto);

        let estado = "Disponible";

        let icono = "fa-mobile-screen-button";

        if(producto.stock <= 0){

            estado = "Agotado";

        }

        else if(producto.stock <= producto.stockMinimo){

            estado = "Stock Bajo";

        }

        switch(producto.categoria){

            case "Portátiles":

                icono = "fa-laptop";

            break;

            case "Computadores":

                icono = "fa-desktop";

            break;

            case "Tablets":

                icono = "fa-tablet-screen-button";

            break;

            case "Consolas":

                icono = "fa-gamepad";

            break;

            case "Smartwatch":

                icono = "fa-clock";

            break;

            case "Accesorios":

                icono = "fa-headphones";

            break;

        }

        productsList.innerHTML += `

            <div class="product-card-v2">

                <div class="product-top"></div>

                <div class="product-icon">

                    <i class="fa-solid ${icono}"></i>

                </div>

                <div class="product-body">

                    <h3>

                        ${producto.nombre}

                    </h3>

                    <span class="product-brand">

                        ${producto.marca} • ${producto.categoria}

                    </span>

                    <span class="product-code-v2">

                        Código: ${producto.codigo}

                    </span>

                    <div class="product-stock-row">

                        <span class="stock-badge ${stockClass}">

                            ${estado}
                        </span>

                        <span class="stock-number">

                            Stock: ${producto.stock}

                        </span>

                    </div>

                    <div class="product-price-v2">

                        $${producto.precio.toLocaleString("es-CO")}

                    </div>

                    <button

                        class="btn-add-v2"

                        onclick="agregarProducto('${producto.id}')">

                        <i class="fa-solid fa-cart-plus"></i>

                        Agregar al carrito

                    </button>

                </div>

            </div>

        `;

    });

}

/*==================================================
=            CLASE STOCK
==================================================*/

function obtenerClaseStock(producto){

    if(producto.stock <= 0){

        return "stock-empty";

    }

    if(producto.stock <= producto.stockMinimo){

        return "stock-low";

    }

    return "stock-high";

}

/*==================================================
=            BUSCADOR
==================================================*/

function buscarProductos(){

    const texto =

        searchSaleProduct.value

        .trim()

        .toLowerCase();

    if(texto === ""){

        cargarProductos();

        return;

    }

    const productos =

        ventasController.buscarProductos(

            texto

        );

    renderizarProductos(productos);

}

/*==================================================
=            AGREGAR PRODUCTO
==================================================*/

function agregarProducto(id){

    const agregado =

        ventasController.agregarProducto(id);

    if(!agregado){

        return;

    }

    renderizarCarrito();

}

/*==================================================
=            RENDERIZAR CARRITO
==================================================*/

function renderizarCarrito(){

    const carrito = ventasController.obtenerCarrito();

    if(carrito.length === 0){

        cartItems.innerHTML = `

            <div class="empty-cart">

                <i class="fa-solid fa-cart-shopping"></i>

                <h4>

                    Tu carrito está vacío

                </h4>

                <p>

                    Agrega productos para comenzar una venta.

                </p>

            </div>

        `;

        actualizarResumen();

        return;

    }

    cartItems.innerHTML = "";

    carrito.forEach(item=>{

        cartItems.innerHTML += `

            <div class="cart-item-v2">

                <div class="cart-top-bar"></div>

                <div class="cart-product">

                    <div class="cart-product-icon">

                        <i class="fa-solid fa-mobile-screen-button"></i>

                    </div>

                    <div class="cart-product-info">

                        <h4>

                            ${item.nombre}

                        </h4>

                        <span>

                            ${item.codigo}

                        </span>

                    </div>

                </div>

                <div class="cart-price-row">

                    <span>

                        Precio

                    </span>

                    <strong>

                        $${item.precio.toLocaleString("es-CO")}

                    </strong>

                </div>

                <div class="cart-quantity-row">

                    <span>

                        Cantidad

                    </span>

                    <div class="cart-buttons">

                        <span class="cart-quantity-number">

                            ${item.cantidad}

                        </span>

                        <button

                            class="btn-cart-circle"

                            onclick="disminuirCantidad('${item.productoId}')">

                            <i class="fa-solid fa-minus"></i>

                        </button>

                        <button

                            class="btn-cart-circle btn-cart-add"

                            onclick="aumentarCantidad('${item.productoId}')">

                            <i class="fa-solid fa-plus"></i>

                        </button>

                    </div>

                </div>

                <div class="cart-subtotal-row">

                    <div>

                        <span>

                            Subtotal

                        </span>

                        <strong>

                            $${item.subtotal.toLocaleString("es-CO")}

                        </strong>

                    </div>

                    <button

                        class="btn-delete-product"

                        onclick="eliminarProducto('${item.productoId}')">

                        <i class="fa-solid fa-trash"></i>

                        Eliminar

                    </button>

                </div>

            </div>

        `;

    });

    actualizarResumen();

}

/*==================================================
=            RESUMEN
==================================================*/

function actualizarResumen(){

    subtotalSale.textContent =

        "$" +

        ventasController.obtenerSubtotal()

        .toLocaleString("es-CO");

    ivaSale.textContent =

        "$" +

        ventasController.obtenerIVA()

        .toLocaleString("es-CO");

    discountSale.textContent =

        "$" +

        ventasController.obtenerDescuento()

        .toLocaleString("es-CO");

    totalSale.textContent =

        "$" +

        ventasController.obtenerTotal()

        .toLocaleString("es-CO");

}

/*==================================================
=            CARRITO
==================================================*/

function aumentarCantidad(id){

    ventasController.aumentarCantidad(id);

    renderizarCarrito();

}

function disminuirCantidad(id){

    ventasController.disminuirCantidad(id);

    renderizarCarrito();

}

function eliminarProducto(id){

    ventasController.eliminarProducto(id);

    renderizarCarrito();

}

/*==================================================
=            MÉTODO DE PAGO
==================================================*/

function cambiarMetodoPago(e){

    ventasController.cambiarMetodoPago(

        e.target.value

    );

}

/*==================================================
=            CANCELAR
==================================================*/

function cancelarVenta(){

    const confirmar = confirm(

        "¿Desea cancelar la venta actual?"

    );

    if(!confirmar){

        return;

    }

    ventasController.cancelarVenta();

    cargarInformacionVenta();

    renderizarCarrito();

}

/*==================================================
=            FINALIZAR
==================================================*/

function finalizarVenta(){

    const realizada =

        ventasController.finalizarVenta();

    if(!realizada){

        return;

    }

    document.getElementById(

        "modalInvoice"

    ).textContent =

        ventasController.obtenerNumeroFactura();

    document.getElementById(

        "modalTotal"

    ).textContent =

        totalSale.textContent;

    document.getElementById(

        "saleSuccessModal"

    ).style.display = "flex";

}

/*==================================================
=            NUEVA VENTA
==================================================*/

document

.getElementById("btnNewSale")

.addEventListener(

    "click",

    ()=>{

        document.getElementById(

            "saleSuccessModal"

        ).style.display = "none";

        ventasController.nuevaVenta();

        cargarInformacionVenta();

        cargarProductos();

        renderizarCarrito();

    }

);

/*==================================================
=            IMPRIMIR
==================================================*/

document

.getElementById("btnPrintInvoice")

.addEventListener(

    "click",

    ()=>{

        window.print();

    }

);