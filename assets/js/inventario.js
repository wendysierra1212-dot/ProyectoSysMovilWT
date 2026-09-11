/**
 * ==================================================
 * SYSMOVILWT
 * MÓDULO DE INVENTARIO
 * INTEGRACIÓN PHP + MYSQL
 * ==================================================
 */


/* ==================================================
   ELEMENTOS DEL DOM
   ================================================== */

const btnNuevoProducto =
    document.getElementById("btnNuevoProducto");

const btnCerrarModal =
    document.getElementById("btnCloseModal");

const btnCancelar =
    document.getElementById("btnCancelarProducto");

const modal =
    document.getElementById("productModal");

const formulario =
    document.getElementById("productForm");

const tipoProducto =
    document.getElementById("productoTipo");

const grupoSerial =
    document.getElementById("grupoSerial");

const tabla =
    document.getElementById("inventoryTableBody");

const buscador =
    document.getElementById("searchProduct");

const filtroCategoria =
    document.getElementById("filterCategory");

const filtroEstado =
    document.getElementById("filterStatus");

const btnReset =
    document.getElementById("btnResetFilters");


/* ==================================================
   INICIALIZACIÓN
   ================================================== */

document.addEventListener(
    "DOMContentLoaded",
    iniciar
);


function iniciar() {

    if (
        typeof inicializarCerrarSesion ===
        "function"
    ) {

        inicializarCerrarSesion();

    }


    if (
        typeof cargarFecha ===
        "function"
    ) {

        cargarFecha();

    }


    inicializarEventos();

}


/* ==================================================
   EVENTOS
   ================================================== */

function inicializarEventos() {


    /* ==============================================
       NUEVO PRODUCTO
       ============================================== */

    if (btnNuevoProducto) {

        btnNuevoProducto.addEventListener(
            "click",
            abrirNuevoProducto
        );

    }


    /* ==============================================
       CERRAR MODAL
       ============================================== */

    if (btnCerrarModal) {

        btnCerrarModal.addEventListener(
            "click",
            cerrarModal
        );

    }


    /* ==============================================
       CANCELAR
       ============================================== */

    if (btnCancelar) {

        btnCancelar.addEventListener(
            "click",
            cerrarModal
        );

    }


    /* ==============================================
       TIPO DE PRODUCTO
       ============================================== */

    if (tipoProducto) {

        tipoProducto.addEventListener(
            "change",
            controlarSerial
        );

    }


    /* ==============================================
       BÚSQUEDA
       ============================================== */

    if (buscador) {

        buscador.addEventListener(
            "input",
            filtrarTabla
        );

    }


    /* ==============================================
       FILTRO CATEGORÍA
       ============================================== */

    if (filtroCategoria) {

        filtroCategoria.addEventListener(
            "change",
            filtrarTabla
        );

    }


    /* ==============================================
       FILTRO ESTADO
       ============================================== */

    if (filtroEstado) {

        filtroEstado.addEventListener(
            "change",
            filtrarTabla
        );

    }


    /* ==============================================
       RESET FILTROS
       ============================================== */

    if (btnReset) {

        btnReset.addEventListener(
            "click",
            limpiarFiltros
        );

    }


    /* ==============================================
       CERRAR AL HACER CLIC FUERA
       ============================================== */

    if (modal) {

        modal.addEventListener(
            "click",
            function (e) {

                if (e.target === modal) {

                    cerrarModal();

                }

            }
        );

    }


    /* ==============================================
       BOTONES EDITAR
       ============================================== */

    inicializarBotonesEditar();

}


/* ==================================================
   NUEVO PRODUCTO
   ================================================== */

function abrirNuevoProducto() {

    if (!modal || !formulario) {

        return;

    }


    /*
     * El formulario vuelve a crear.php
     */

    formulario.action =
        "../productos/crear.php";


    /*
     * Quitamos cualquier ID de edición
     */

    formulario.removeAttribute(
        "data-editando"
    );


    /*
     * Limpiamos el formulario
     */

    formulario.reset();


    /*
     * Título del modal
     */

    cambiarTituloModal(
        "Nuevo Producto"
    );


    /*
     * Botón de guardar
     */

    cambiarTextoBoton(
        "Guardar Producto"
    );


    /*
     * Condición por defecto
     */

    establecerCondicion(
        "Nuevo"
    );


    /*
     * Mostrar modal
     */

    modal.style.display = "flex";


    controlarSerial();

}


/* ==================================================
   EDITAR PRODUCTO
   ================================================== */

function inicializarBotonesEditar() {

    const botonesEditar =
        document.querySelectorAll(
            ".btn-editar-producto"
        );


    botonesEditar.forEach(
        function (boton) {

            boton.addEventListener(
                "click",
                function (e) {

                    e.preventDefault();

                    cargarProductoParaEditar(
                        boton
                    );

                }
            );

        }
    );

}


/* ==================================================
   CARGAR PRODUCTO EN EL MODAL
   ================================================== */

function cargarProductoParaEditar(
    boton
) {

    if (!modal || !formulario) {

        return;

    }


    /*
     * Obtenemos los datos directamente
     * de los atributos data-* de la fila.
     */

    const id =
        boton.dataset.id;

    const codigo =
        boton.dataset.codigo || "";

    const nombre =
        boton.dataset.nombre || "";

    const tipo =
        boton.dataset.tipo || "";

    const marca =
        boton.dataset.marca || "";

    const modelo =
        boton.dataset.modelo || "";

    const imei =
        boton.dataset.imei || "";

    const descripcion =
        boton.dataset.descripcion || "";

    const precioCompra =
        boton.dataset.precioCompra || "";

    const precioVenta =
        boton.dataset.precioVenta || "";

    const stock =
        boton.dataset.stock || "0";

    const stockMinimo =
        boton.dataset.stockMinimo || "5";

    const garantia =
        boton.dataset.garantia || "0";

    const condicion =
        boton.dataset.condicion || "Nuevo";

    const categoria =
        boton.dataset.categoria || "";

    const proveedor =
        boton.dataset.proveedor || "";


    /*
     * Cargar campos
     */

    establecerValor(
        "productoCodigo",
        codigo
    );

    establecerValor(
        "productoNombre",
        nombre
    );

    establecerValor(
        "productoTipo",
        tipo
    );

    establecerValor(
        "productoMarca",
        marca
    );

    establecerValor(
        "productoCategoria",
        categoria
    );

    establecerValor(
        "productoProveedor",
        proveedor
    );

    establecerValor(
        "productoCosto",
        precioCompra
    );

    establecerValor(
        "productoPrecio",
        precioVenta
    );

    establecerValor(
        "productoStock",
        stock
    );

    establecerValor(
        "productoStockMinimo",
        stockMinimo
    );

    establecerValor(
        "productoGarantia",
        garantia
    );

    establecerValor(
        "productoSerial",
        imei
    );

    establecerValor(
        "productoDescripcion",
        descripcion
    );


    /*
     * Campos que no están en la tabla
     * principal pero pueden existir
     * en el formulario.
     */

    establecerValor(
        "productoModelo",
        modelo
    );


    /*
     * Condición
     */

    establecerCondicion(
        condicion
    );


    /*
     * Configurar formulario para UPDATE
     */

    formulario.action =
        "../productos/editar.php?id=" +
        encodeURIComponent(id);


    formulario.setAttribute(
        "data-editando",
        id
    );


    /*
     * Cambiar textos del modal
     */

    cambiarTituloModal(
        "Editar Producto"
    );

    cambiarTextoBoton(
        "Guardar Cambios"
    );


    /*
     * Mostrar modal
     */

    modal.style.display = "flex";


    controlarSerial();

}


/* ==================================================
   ESTABLECER VALOR
   ================================================== */

function establecerValor(
    id,
    valor
) {

    const elemento =
        document.getElementById(id);


    if (!elemento) {

        return;

    }


    elemento.value = valor;

}


/* ==================================================
   CONDICIÓN
   ================================================== */

function establecerCondicion(
    condicion
) {

    /*
     * Si existe un select de condición
     */

    const campo =
        document.getElementById(
            "productoCondicion"
        );


    if (campo) {

        campo.value =
            condicion || "Nuevo";

        return;

    }


    /*
     * Si no existe, buscamos un input
     * hidden llamado condicion.
     */

    let hidden =
        formulario
            ? formulario.querySelector(
                'input[name="condicion"]'
            )
            : null;


    if (hidden) {

        hidden.value =
            condicion || "Nuevo";

    }

}


/* ==================================================
   TÍTULO DEL MODAL
   ================================================== */

function cambiarTituloModal(
    texto
) {

    const titulo =
        modal
            ? modal.querySelector(
                ".inventory-modal-header h2"
            )
            : null;


    if (titulo) {

        titulo.textContent =
            texto;

        return;

    }


    /*
     * Alternativa por si el encabezado
     * utiliza otra estructura.
     */

    const tituloAlternativo =
        modal
            ? modal.querySelector(
                ".inventory-modal-header"
            )
            : null;


    if (
        tituloAlternativo &&
        tituloAlternativo.querySelector("h2")
    ) {

        tituloAlternativo
            .querySelector("h2")
            .textContent = texto;

    }

}


/* ==================================================
   TEXTO BOTÓN
   ================================================== */

function cambiarTextoBoton(
    texto
) {

    const boton =
        formulario
            ? formulario.querySelector(
                ".btn-save"
            )
            : null;


    if (boton) {

        boton.textContent =
            texto;

    }

}


/* ==================================================
   CERRAR MODAL
   ================================================== */

function cerrarModal() {

    if (!modal) {

        return;

    }


    modal.style.display =
        "none";


    if (formulario) {

        formulario.reset();

        formulario.action =
            "../productos/crear.php";

        formulario.removeAttribute(
            "data-editando"
        );

    }


    cambiarTituloModal(
        "Nuevo Producto"
    );


    cambiarTextoBoton(
        "Guardar Producto"
    );


    establecerCondicion(
        "Nuevo"
    );


    controlarSerial();

}


/* ==================================================
   CONTROL IMEI / SERIAL
   ================================================== */

function controlarSerial() {

    if (!tipoProducto || !grupoSerial) {

        return;

    }


    const tipo =
        tipoProducto.value;


    if (tipo === "Equipo") {

        grupoSerial.style.display =
            "block";

    }

    else {

        grupoSerial.style.display =
            "none";


        const serial =
            document.getElementById(
                "productoSerial"
            );


        if (serial) {

            /*
             * No borramos el serial si
             * estamos editando.
             */

            const editando =
                formulario &&
                formulario.getAttribute(
                    "data-editando"
                );


            if (!editando) {

                serial.value = "";

            }

        }

    }

}


/* ==================================================
   BÚSQUEDA Y FILTROS
   ================================================== */

function filtrarTabla() {

    if (!tabla) {

        return;

    }


    const texto =
        buscador
            ? buscador.value
                .trim()
                .toLowerCase()
            : "";


    const categoria =
        filtroCategoria
            ? filtroCategoria.value
                .trim()
                .toLowerCase()
            : "";


    const estado =
        filtroEstado
            ? filtroEstado.value
                .trim()
                .toLowerCase()
            : "";


    const filas =
        tabla.querySelectorAll("tr");


    filas.forEach(
        function (fila) {

            if (
                fila.classList.contains(
                    "inventory-empty"
                )
            ) {

                return;

            }


            const contenido =
                fila.textContent
                    .toLowerCase();


            const coincideTexto =
                texto === "" ||
                contenido.includes(
                    texto
                );


            const coincideCategoria =
                categoria === "" ||
                categoria.includes(
                    "todas"
                ) ||
                contenido.includes(
                    categoria
                );


            const coincideEstado =
                estado === "" ||
                estado.includes(
                    "todos"
                ) ||
                contenido.includes(
                    estado
                );


            if (
                coincideTexto &&
                coincideCategoria &&
                coincideEstado
            ) {

                fila.style.display =
                    "";

            }

            else {

                fila.style.display =
                    "none";

            }

        }
    );

}


/* ==================================================
   LIMPIAR FILTROS
   ================================================== */

function limpiarFiltros() {

    if (buscador) {

        buscador.value = "";

    }


    if (filtroCategoria) {

        filtroCategoria.selectedIndex =
            0;

    }


    if (filtroEstado) {

        filtroEstado.selectedIndex =
            0;

    }


    filtrarTabla();

}
