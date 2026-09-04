/**
 * ==================================================
 * SYSMOVILWT
 * MÓDULO DE INVENTARIO
 * VERSIÓN 1.1
 * ==================================================
 */

const inventario = new InventarioController();

let productoEditando = null;

/*==================================================
=            ELEMENTOS DEL DOM
==================================================*/

const btnNuevoProducto = document.getElementById("btnNuevoProducto");

const btnCerrarModal = document.getElementById("btnCloseModal");

const btnCancelar = document.getElementById("btnCancelarProducto");

const modal = document.getElementById("productModal");

const formulario = document.getElementById("productForm");

const tipoProducto = document.getElementById("productoTipo");

const categoriaProducto = document.getElementById("productoCategoria");

const grupoSerial = document.getElementById("grupoSerial");

const tabla = document.getElementById("inventoryTableBody");

const buscador = document.getElementById("searchProduct");

const filtroCategoria = document.getElementById("filterCategory");

const filtroEstado = document.getElementById("filterStatus");

const btnReset = document.getElementById("btnResetFilters");

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

    if(tipoProducto){

    tipoProducto.addEventListener("change", actualizarCategorias);

    }

    if(btnNuevoProducto){

        btnNuevoProducto.addEventListener("click", abrirModal);

    }

    if(btnCerrarModal){

        btnCerrarModal.addEventListener("click", cerrarModal);

    }

    if(btnCancelar){

        btnCancelar.addEventListener("click", cerrarModal);

    }

    if(formulario){

        formulario.addEventListener("submit", guardarProducto);

    }

    if(buscador){

        buscador.addEventListener("input", buscarProductos);

    }

    if(filtroCategoria){

        filtroCategoria.addEventListener("change", aplicarFiltros);

    }

    if(filtroEstado){

        filtroEstado.addEventListener("change", aplicarFiltros);

    }

    if(btnReset){

        btnReset.addEventListener("click", limpiarFiltros);

    }

}

/*==================================================
=            MODAL
==================================================*/

function abrirModal(){

    modal.style.display = "flex";

    actualizarCategorias();

}

function cerrarModal(){

    modal.style.display = "none";

    productoEditando = null;

    formulario.reset();

    actualizarCategorias();

    document.querySelector(".btn-save").textContent =

        "Guardar Producto";

}

/*==================================================
=            GUARDAR PRODUCTO
==================================================*/

function guardarProducto(e){

    e.preventDefault();

    const datos = {

    codigo: document.getElementById("productoCodigo").value.trim(),

    nombre: document.getElementById("productoNombre").value.trim(),

    tipo: document.getElementById("productoTipo").value,

    marca: document.getElementById("productoMarca").value,

    categoria: document.getElementById("productoCategoria").value,

    proveedor: document.getElementById("productoProveedor").value,

    estado: document.getElementById("productoEstado").value,

    costo: Number(document.getElementById("productoCosto").value),

    precio: Number(document.getElementById("productoPrecio").value),

    stock: Number(document.getElementById("productoStock").value),

    stockMinimo: Number(document.getElementById("productoStockMinimo").value),

    garantia: Number(document.getElementById("productoGarantia").value),

    ubicacion: document.getElementById("productoUbicacion").value.trim(),

    serial: document.getElementById("productoSerial").value.trim(),

    descripcion: document.getElementById("productoDescripcion").value.trim()

    };


   if(

    datos.codigo === "" ||

    datos.nombre === "" ||

    datos.tipo === "" ||

    datos.marca === "" ||

    datos.categoria === "" ||

    datos.proveedor === "" ||

    datos.precio <= 0

   ){

        alert("Complete todos los campos obligatorios.");

        return;

    }

    if(productoEditando){

        inventario.actualizarProducto(

            productoEditando.id,

            datos

        );

        registrarActividad(

            "producto",

            "editar",

            `Producto "${datos.nombre}" actualizado.`

        );

    }

    else{

        inventario.agregarProducto(datos);

        registrarActividad(

            "producto",

            "crear",

            `Producto "${datos.nombre}" registrado.`

        );

    }

    cerrarModal();

    cargarTabla();

}

/*==================================================
=            CARGAR TABLA
==================================================*/

function cargarTabla(){

    const productos = inventario.obtenerProductos();

    if(productos.length === 0){

        tabla.innerHTML = `

            <tr class="inventory-empty">

                <td colspan="8">

                    <div class="empty-state">

                        <i class="fa-solid fa-box-open"></i>

                        <h3>No hay productos registrados</h3>

                        <p>

                            Haz clic en <strong>Nuevo Producto</strong>
                            para comenzar.

                        </p>

                    </div>

                </td>

            </tr>

        `;

        return;

    }

    tabla.innerHTML = "";

    productos.forEach(producto => {

        tabla.innerHTML += `

            <tr>

                <td>${producto.codigo}</td>

                <td>${producto.nombre}</td>

                <td>${producto.marca}</td>

                <td>${producto.categoria}</td>

                <td>$${producto.precio.toLocaleString("es-CO")}</td>

                <td>${producto.stock}</td>

                <td>

                    <span class="status-badge ${obtenerClaseEstado(producto.obtenerEstadoStock())}">

                        ${producto.obtenerEstadoStock()}

                    </span>

                </td>

                <td>

                    <button

                        class="btn-table btn-edit"

                        title="Editar"

                        onclick="editarProducto('${producto.id}')">

                        <i class="fa-solid fa-pen"></i>

                    </button>

                    <button

                        class="btn-table btn-delete"

                        title="Eliminar"

                        onclick="eliminarProducto('${producto.id}')">

                        <i class="fa-solid fa-trash"></i>

                    </button>

                </td>

            </tr>

        `;

    });

}

/*==================================================
=            EDITAR
==================================================*/

function editarProducto(id){

    const producto = inventario.obtenerProducto(id);

    if(!producto){

        return;

    }

    productoEditando = producto;

    document.getElementById("productoCodigo").value = producto.codigo;

    document.getElementById("productoNombre").value = producto.nombre;

    document.getElementById("productoTipo").value = producto.tipo;

    actualizarCategorias();

    document.getElementById("productoMarca").value = producto.marca;

    document.getElementById("productoCategoria").value = producto.categoria;

    document.getElementById("productoProveedor").value = producto.proveedor;

    document.getElementById("productoEstado").value = producto.estado;

    document.getElementById("productoCosto").value = producto.costo;

    document.getElementById("productoPrecio").value = producto.precio;

    document.getElementById("productoStock").value = producto.stock;

    document.getElementById("productoStockMinimo").value = producto.stockMinimo;

    document.getElementById("productoGarantia").value = producto.garantia;

    document.getElementById("productoUbicacion").value = producto.ubicacion;

    document.getElementById("productoSerial").value = producto.serial;

    document.getElementById("productoDescripcion").value = producto.descripcion;

    document.querySelector(".btn-save").textContent =

        "Actualizar Producto";

    abrirModal();

}

/*==================================================
=            ELIMINAR
==================================================*/

function eliminarProducto(id){

    const confirmar = confirm(

        "¿Desea eliminar este producto?"

    );

    if(!confirmar){

        return;

    }

    const producto = inventario.obtenerProducto(id);

    if(!producto){

        return;

    }

    inventario.eliminarProducto(id);

    registrarActividad(

        "producto",

        "eliminar",

        `Producto "${producto.nombre}" eliminado.`

    );

    cargarTabla();

}

/*==================================================
=            BUSCAR PRODUCTOS
==================================================*/

function buscarProductos(){

    const texto = buscador.value.trim().toLowerCase();

    if(texto === ""){

        cargarTabla();

        return;

    }

    const productos = inventario.buscar(texto);

    renderizarTabla(productos);

}

/*==================================================
=            FILTROS
==================================================*/

function aplicarFiltros(){

    const categoria = filtroCategoria.value;

    const estado = filtroEstado.value;

    const productos = inventario.filtrar(

        categoria,

        estado

    );

    renderizarTabla(productos);

}

/*==================================================
=            LIMPIAR FILTROS
==================================================*/

function limpiarFiltros(){

    buscador.value = "";

    filtroCategoria.selectedIndex = 0;

    filtroEstado.selectedIndex = 0;

    cargarTabla();

}

/*==================================================
=            RENDERIZAR TABLA
==================================================*/

function renderizarTabla(productos){

    if(productos.length === 0){

        tabla.innerHTML = `

            <tr class="inventory-empty">

                <td colspan="8">

                    <div class="empty-state">

                        <i class="fa-solid fa-box-open"></i>

                        <h3>No se encontraron productos</h3>

                        <p>

                            No existen productos que coincidan con la búsqueda.

                        </p>

                    </div>

                </td>

            </tr>

        `;

        return;

    }

    tabla.innerHTML = "";

    productos.forEach(producto => {

        tabla.innerHTML += `

            <tr>

                <td>${producto.codigo}</td>

                <td>${producto.nombre}</td>

                <td>${producto.marca}</td>

                <td>${producto.categoria}</td>

                <td>$${producto.precio.toLocaleString("es-CO")}</td>

                <td>${producto.stock}</td>

                <td>

                    <span class="status-badge ${obtenerClaseEstado(producto.obtenerEstadoStock())}">

                        ${producto.obtenerEstadoStock()}

                    </span>

                </td>

                <td>

                    <button

                        class="btn-table btn-edit"

                        title="Editar"

                        onclick="editarProducto('${producto.id}')">

                        <i class="fa-solid fa-pen"></i>

                    </button>

                    <button

                        class="btn-table btn-delete"

                        title="Eliminar"

                        onclick="eliminarProducto('${producto.id}')">

                        <i class="fa-solid fa-trash"></i>

                    </button>

                </td>

            </tr>

        `;

    });

}

/*==================================================
=            BADGES DE ESTADO
==================================================*/

function obtenerClaseEstado(estado){

    switch(estado){

        case "Disponible":

            return "status-success";

        case "Stock Bajo":

            return "status-warning";

        case "Agotado":

            return "status-danger";

        default:

            return "";

    }

}/*==================================================
=      CATEGORÍAS DINÁMICAS
==================================================*/

function actualizarCategorias(){

    const tipo = tipoProducto.value;

    categoriaProducto.innerHTML = '<option value="">Seleccione...</option>';

    let categorias = [];

    switch(tipo){

        case "Equipo":

            categorias = [
                "Celulares",
                "Tablets",
                "Smartwatch"
            ];

            grupoSerial.style.display = "block";

            break;

        case "Accesorio":

            categorias = [
                "Cargadores",
                "Audífonos",
                "Fundas",
                "Vidrios templados",
                "Cables",
                "Parlantes",
                "Power Bank",
                "Proyectores",
                "Linternas",
                "Relojes Inteligentes",
                "Accesorios Gaming",
                "Periféricos",
                "Otros accesorios"
            ];

            grupoSerial.style.display = "none";

            document.getElementById("productoSerial").value = "";

            break;

        case "Repuesto":

            categorias = [
                "Pantallas",
                "Baterías",
                "Pines de carga",
                "Cámaras",
                "Flex",
                "Tapas",
                "Otros repuestos"
            ];

            grupoSerial.style.display = "none";

            document.getElementById("productoSerial").value = "";

            break;

        default:

            grupoSerial.style.display = "none";

            document.getElementById("productoSerial").value = "";

            return;

    }

    categorias.forEach(categoria => {

        const option = document.createElement("option");

        option.value = categoria;

        option.textContent = categoria;

        categoriaProducto.appendChild(option);

    });

}

