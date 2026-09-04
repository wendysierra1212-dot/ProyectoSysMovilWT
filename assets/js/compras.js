/**
 * ==================================================
 * SYSMOVILWT
 * MÓDULO DE COMPRAS
 * VERSIÓN 2.0
 * ==================================================
 */

const comprasController = new ComprasController();

const inventario = new InventarioController();

let compraEditando = null;

/*==================================================
=            ELEMENTOS DEL DOM
==================================================*/

const btnNuevaCompra = document.getElementById("btnNuevaCompra");

const btnClosePurchaseModal = document.getElementById("btnClosePurchaseModal");

const btnCancelarCompra = document.getElementById("btnCancelarCompra");

const purchaseModal = document.getElementById("purchaseModal");

const purchaseForm = document.getElementById("purchaseForm");

const purchaseTableBody = document.getElementById("purchaseTableBody");

const detalleCompraBody = document.getElementById("detalleCompraBody");

const btnAgregarProductoCompra = document.getElementById("btnAgregarProductoCompra");

const totalCompra = document.getElementById("totalCompra");

const buscador = document.getElementById("searchCompra");

const filtroProveedor = document.getElementById("filterProveedor");

const filtroEstado = document.getElementById("filterEstado");

const btnResetFiltros = document.getElementById("btnResetFiltros");

/*==================================================
=            INICIALIZACIÓN
==================================================*/

document.addEventListener("DOMContentLoaded", iniciar);

function iniciar(){

    inicializarCerrarSesion();

    cargarFecha();

    inicializarEventos();

    cargarTabla();

}

/*==================================================
=            EVENTOS
==================================================*/

function inicializarEventos(){

    if(btnNuevaCompra){

        btnNuevaCompra.addEventListener("click", abrirModal);

    }

    if(btnClosePurchaseModal){

        btnClosePurchaseModal.addEventListener("click", cerrarModal);

    }

    if(btnCancelarCompra){

        btnCancelarCompra.addEventListener("click", cerrarModal);

    }

    if(btnAgregarProductoCompra){

        btnAgregarProductoCompra.addEventListener("click", agregarFilaProducto);

    }

    if(purchaseForm){

        purchaseForm.addEventListener("submit", guardarCompra);

    }

    if(buscador){

        buscador.addEventListener("input", buscarCompras);

    }

    if(filtroProveedor){

        filtroProveedor.addEventListener("change", aplicarFiltros);

    }

    if(filtroEstado){

        filtroEstado.addEventListener("change", aplicarFiltros);

    }

    if(btnResetFiltros){

        btnResetFiltros.addEventListener("click", limpiarFiltros);

    }

}

/*==================================================
=            MODAL
==================================================*/

function abrirModal(){

    purchaseModal.classList.add("show");

    purchaseForm.reset();

    detalleCompraBody.innerHTML = "";

    totalCompra.textContent = "$0";

    compraEditando = null;

}

function cerrarModal(){

    purchaseModal.classList.remove("show");

    purchaseForm.reset();

    detalleCompraBody.innerHTML = "";

    totalCompra.textContent = "$0";

    compraEditando = null;

}

/*==================================================
=            CARGAR TABLA
==================================================*/

function cargarTabla(){

    const compras = comprasController.obtenerCompras();

    renderizarTabla(compras);

}

/*==================================================
=            RENDERIZAR TABLA
==================================================*/

function renderizarTabla(compras){

    if(compras.length === 0){

        purchaseTableBody.innerHTML = `

            <tr class="purchase-empty">

                <td colspan="7">

                    <div class="empty-state">

                        <i class="fa-solid fa-bag-shopping"></i>

                        <h3>No hay compras registradas</h3>

                        <p>

                            Haz clic en <strong>Nueva Compra</strong>
                            para registrar la primera compra.

                        </p>

                    </div>

                </td>

            </tr>

        `;

        return;

    }

    purchaseTableBody.innerHTML = "";

    compras.forEach(compra => {

        purchaseTableBody.innerHTML += `

            <tr>

                <td>${compra.numeroFactura}</td>

                <td>${compra.fecha}</td>

                <td>${compra.proveedor}</td>

                <td>${compra.productos.length}</td>

                <td>$${compra.total.toLocaleString("es-CO")}</td>

                <td>

                    <span class="status-badge status-success">

                        ${compra.estado}

                    </span>

                </td>

                <td>

                    <button

                        class="btn-table btn-edit"

                        onclick="editarCompra('${compra.id}')">

                        <i class="fa-solid fa-pen"></i>

                    </button>

                    <button

                        class="btn-table btn-delete"

                        onclick="eliminarCompra('${compra.id}')">

                        <i class="fa-solid fa-trash"></i>

                    </button>

                </td>

            </tr>

        `;

    });

}

/*==================================================
=            AGREGAR FILA
==================================================*/

function agregarFilaProducto(){

    const productos = inventario.obtenerProductos();

    const fila = document.createElement("tr");

    fila.innerHTML = `

        <td>

            <select class="producto-select">

                <option value="">

                    Seleccione...

                </option>

                ${productos.map(producto => `

                    <option value="${producto.id}">

                        ${producto.codigo} - ${producto.nombre}

                    </option>

                `).join("")}

            </select>

        </td>

        <td>

            <input

                type="number"

                class="cantidad-input"

                min="1"

                value="1">

        </td>

        <td>

            <input

                type="number"

                class="costo-input"

                min="0"

                value="0">

        </td>

        <td>

            <span class="subtotal-text">

                $0

            </span>

        </td>

        <td>

            <button

                type="button"

                class="btn-delete-row">

                <i class="fa-solid fa-trash"></i>

            </button>

        </td>

    `;

    detalleCompraBody.appendChild(fila);

    inicializarEventosFila(fila);

}

/*==================================================
=            EVENTOS DE LA FILA
==================================================*/

function inicializarEventosFila(fila){

    const producto = fila.querySelector(".producto-select");

    const cantidad = fila.querySelector(".cantidad-input");

    const costo = fila.querySelector(".costo-input");

    const eliminar = fila.querySelector(".btn-delete-row");

    producto.addEventListener("change", ()=>{

        cargarCostoProducto(fila);

    });

    cantidad.addEventListener("input", ()=>{

        calcularSubtotalFila(fila);

    });

    costo.addEventListener("input", ()=>{

        calcularSubtotalFila(fila);

    });

    eliminar.addEventListener("click", ()=>{

        fila.remove();

        calcularTotalCompra();

    });

}
