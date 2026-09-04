/**
 * ==================================================
 * SYSMOVILWT
 * MÓDULO CLIENTES
 * Versión 3.0
 * ==================================================
 * Autor: Leonardo De la Hoz
 * Proyecto ADSO - SENA
 * ==================================================
 * Este archivo administra completamente el módulo
 * de Clientes del sistema SYSMOVILWT.
 *
 * Responsabilidades:
 *
 * • CRUD de clientes
 * • Estadísticas
 * • Búsquedas
 * • Filtros
 * • Modal
 * • Tabla
 * • Actividades
 *
 * ==================================================
 */

/*==================================================
=            CONTROLADOR
==================================================*/

const clientesController = new ClientesController();

let clienteEditando = null;

/*==================================================
=            ELEMENTOS DEL DOM
==================================================*/

const clientsTableBody =
    document.getElementById("clientsTableBody");

const clientModal =
    document.getElementById("clientModal");

const clientForm =
    document.getElementById("clientForm");

const btnNuevoCliente =
    document.getElementById("btnNuevoCliente");

const btnCloseModal =
    document.getElementById("btnCloseModal");

const btnCancelarCliente =
    document.getElementById("btnCancelarCliente");

const btnResetFilters =
    document.getElementById("btnResetFilters");

const searchClient =
    document.getElementById("searchClient");

const filterEstado =
    document.getElementById("filterEstado");

const filterTipoCliente =
    document.getElementById("filterTipoCliente");

/*==================================================
=            INICIALIZACIÓN
==================================================*/

document.addEventListener(

    "DOMContentLoaded",

    iniciarModuloClientes

);

/*==================================================
=            ARRANQUE DEL MÓDULO
==================================================*/

function iniciarModuloClientes(){

    inicializarCerrarSesion();

    cargarFecha();

    cargarUsuario();

    inicializarEventos();

    cargarEstadisticas();

    cargarTabla();

}

/*==================================================
=            USUARIO ACTIVO
==================================================*/

function cargarUsuario(){

    const usuario = JSON.parse(

        localStorage.getItem(

            "usuarioActivo"

        )

    );

    if(!usuario){

        window.location.href = "../login.html";

        return;

    }

    const topbarUser =
        document.getElementById("topbarUser");

    const topbarRole =
        document.getElementById("topbarRole");

    if(topbarUser){

        topbarUser.textContent =
            usuario.nombre;

    }

    if(topbarRole){

        topbarRole.textContent =
            usuario.rol;

    }

}

/*==================================================
=            EVENTOS DEL MÓDULO
==================================================*/

function inicializarEventos(){

    /*------------------------------------------
    | Modal
    ------------------------------------------*/

    btnNuevoCliente?.addEventListener(

        "click",

        abrirModal

    );

    btnCloseModal?.addEventListener(

        "click",

        cerrarModal

    );

    btnCancelarCliente?.addEventListener(

        "click",

        cerrarModal

    );

    /*------------------------------------------
    | Formulario
    ------------------------------------------*/

    clientForm?.addEventListener(

        "submit",

        guardarCliente

    );

    /*------------------------------------------
    | Buscador
    ------------------------------------------*/

    searchClient?.addEventListener(

        "input",

        buscarClientes

    );

    /*------------------------------------------
    | Filtros
    ------------------------------------------*/

    filterEstado?.addEventListener(

        "change",

        aplicarFiltros

    );

    filterTipoCliente?.addEventListener(

        "change",

        aplicarFiltros

    );

    btnResetFilters?.addEventListener(

        "click",

        limpiarFiltros

    );

    /*------------------------------------------
    | Actualización automática
    ------------------------------------------*/

    window.addEventListener(

        "focus",

        actualizarModulo

    );

}

/*==================================================
=            ACTUALIZAR MÓDULO
==================================================*/

function actualizarModulo(){

    cargarTabla();

    cargarEstadisticas();

}

/*==================================================
=            GESTIÓN DEL MODAL
==================================================*/

/**
 * Abre el modal de Cliente.
 * Si existe un cliente en edición,
 * cambia automáticamente el título y el botón.
 */
function abrirModal(){

    clientModal.classList.add("active");

    actualizarEncabezadoModal();

}

/**
 * Cierra el modal y restaura el formulario
 * a su estado inicial.
 */
function cerrarModal(){

    clientModal.classList.remove("active");

    clientForm.reset();

    clienteEditando = null;

    habilitarFormulario();

    actualizarEncabezadoModal();

}

/*==================================================
=            ENCABEZADO DEL MODAL
==================================================*/

function actualizarEncabezadoModal(){

    const titulo =

        document.querySelector(

            ".crud-modal-header h2"

        );

    const btnGuardar =

        document.querySelector(

            ".btn-save"

        );

    if(clienteEditando){

        titulo.innerHTML = `

            <i class="fa-solid fa-user-pen"></i>

            Editar Cliente

        `;

        btnGuardar.textContent =

            "Actualizar Cliente";

    }

    else{

        titulo.innerHTML = `

            <i class="fa-solid fa-user-plus"></i>

            Nuevo Cliente

        `;

        btnGuardar.textContent =

            "Guardar Cliente";

        btnGuardar.style.display =

            "inline-flex";

    }

}

/*==================================================
=            FORMULARIO
==================================================*/

function habilitarFormulario(){

    clientForm

        .querySelectorAll(

            "input, select, textarea"

        )

        .forEach(campo=>{

            campo.disabled = false;

        });

}

function deshabilitarFormulario(){

    clientForm

        .querySelectorAll(

            "input, select, textarea"

        )

        .forEach(campo=>{

            campo.disabled = true;

        });

}

/*==================================================
=            GUARDAR CLIENTE
==================================================*/

function guardarCliente(evento){

    evento.preventDefault();

    const datos = obtenerDatosFormulario();

    try{

        if(clienteEditando){

            clientesController.actualizarCliente(

                clienteEditando,

                datos

            );

            registrarActividad(

                "cliente",

                "editar",

                `Cliente ${datos.nombres} ${datos.apellidos} actualizado.`

            );

        }

        else{

            clientesController.agregarCliente(

                datos

            );

            registrarActividad(

                "cliente",

                "crear",

                `Cliente ${datos.nombres} ${datos.apellidos} registrado.`

            );

        }

        clienteEditando = null;

        cerrarModal();

        cargarTabla();

        cargarEstadisticas();

    }

    catch(error){

        alert(error.message);

    }

}

/*==================================================
=            OBTENER DATOS DEL FORMULARIO
==================================================*/

function obtenerDatosFormulario(){

    return{

        tipoDocumento:

            document.getElementById(

                "tipoDocumento"

            ).value,

        documento:

            document.getElementById(

                "documento"

            ).value.trim(),

        nombres:

            document.getElementById(

                "nombres"

            ).value.trim(),

        apellidos:

            document.getElementById(

                "apellidos"

            ).value.trim(),

        fechaNacimiento:

            document.getElementById(

                "fechaNacimiento"

            ).value,

        genero:

            document.getElementById(

                "genero"

            ).value,

        telefono:

            document.getElementById(

                "telefono"

            ).value.trim(),

        correo:

            document.getElementById(

                "correo"

            ).value.trim(),

        ciudad:

            document.getElementById(

                "ciudad"

            ).value.trim(),

        direccion:

            document.getElementById(

                "direccion"

            ).value.trim(),

        tipoCliente:

            document.getElementById(

                "tipoCliente"

            ).value,

        estado:

            document.getElementById(

                "estadoCliente"

            ).value,

        observaciones:

            document.getElementById(

                "observaciones"

            ).value.trim(),

        aceptaPromociones:

            document.getElementById(

                "aceptaPromociones"

            ).checked

    };

}

/*==================================================
=            ESTADÍSTICAS
==================================================*/

function cargarEstadisticas(){

    actualizarTotalClientes();

    actualizarClientesVIP();

    actualizarClientesActivos();

    actualizarComprasClientes();

}

/*==================================================
=            TOTAL DE CLIENTES
==================================================*/

function actualizarTotalClientes(){

    document.getElementById(

        "totalClientes"

    ).textContent =

        clientesController.obtenerTotalClientes();

}

/*==================================================
=            CLIENTES VIP
==================================================*/

function actualizarClientesVIP(){

    document.getElementById(

        "clientesVIP"

    ).textContent =

        clientesController.obtenerClientesVIP().length;

}

/*==================================================
=            CLIENTES ACTIVOS
==================================================*/

function actualizarClientesActivos(){

    document.getElementById(

        "clientesActivos"

    ).textContent =

        clientesController.obtenerClientesActivos().length;

}

/*==================================================
=            TOTAL DE COMPRAS
==================================================*/

function actualizarComprasClientes(){

    let totalCompras = 0;

    clientesController

        .obtenerClientes()

        .forEach(cliente=>{

            totalCompras +=

                cliente.cantidadCompras;

        });

    document.getElementById(

        "totalComprasClientes"

    ).textContent =

        totalCompras;

}

/*==================================================
=            TABLA DE CLIENTES
==================================================*/

/**
 * Carga todos los clientes registrados
 * y envía la información al renderizador.
 */
function cargarTabla(){

    const clientes =

        clientesController.obtenerClientes();

    renderizarTabla(

        clientes

    );

}

/*==================================================
=            LIMPIAR TABLA
==================================================*/

function limpiarTabla(){

    clientsTableBody.innerHTML = "";

}

/*==================================================
=            TABLA VACÍA
==================================================*/

function mostrarTablaVacia(){

    clientsTableBody.innerHTML = `

        <tr class="clients-empty">

            <td colspan="7">

                <div class="empty-state">

                    <i class="fa-solid fa-user-group"></i>

                    <h3>

                        No hay clientes registrados

                    </h3>

                    <p>

                        Haz clic en

                        <strong>

                            Nuevo Cliente

                        </strong>

                        para comenzar.

                    </p>

                </div>

            </td>

        </tr>

    `;

}

/*==================================================
=            VALIDAR TABLA
==================================================*/

function validarTabla(clientes){

    if(clientes.length === 0){

        mostrarTablaVacia();

        return false;

    }

    return true;

}

/*==================================================
=            RENDERIZAR TABLA
==================================================*/

function renderizarTabla(clientes){

    if(!validarTabla(clientes)){

        return;

    }

    limpiarTabla();

    let filas = "";

    clientes.forEach(cliente=>{

        filas += crearFilaCliente(cliente);

    });

    clientsTableBody.innerHTML = filas;

    inicializarBotonesTabla();

}

/*==================================================
=            CREAR FILA
==================================================*/

function crearFilaCliente(cliente){

    return `

        <tr>

          <td class="col-cliente">

            ${crearColumnaCliente(cliente)}

          </td>

          <td class="col-documento">

            ${crearColumnaDocumento(cliente)}

          </td>

          <td class="col-contacto">

           ${crearColumnaContacto(cliente)}

          </td>

          <td class="col-ciudad">

           ${crearColumnaCiudad(cliente)}

          </td>

          <td class="col-compras">

           ${crearColumnaCompras(cliente)}

          </td>

          <td class="col-estado">

           ${crearColumnaEstado(cliente)}

          </td>

          <td class="col-acciones">

           ${crearColumnaAcciones(cliente)}

          </td>

        </tr>

    `;

}

/*==================================================
=            COLUMNA CLIENTE
==================================================*/

function crearColumnaCliente(cliente){

    return `

        <div class="table-info">

            <strong>

                ${cliente.obtenerNombreCompleto()}

            </strong>

            <small>

                ${cliente.tipoCliente}

            </small>

        </div>

    `;

}

/*==================================================
=            COLUMNA DOCUMENTO
==================================================*/

function crearColumnaDocumento(cliente){

    return `

        <div class="documento-info">

            <small>

                ${cliente.tipoDocumento}

            </small>

            <strong>

                ${cliente.documento}

            </strong>

        </div>

    `;

}

/*==================================================
=            COLUMNA CONTACTO
==================================================*/

function crearColumnaContacto(cliente){

    return `

        <div class="contacto-info">

            <strong>

                ${cliente.telefono}

            </strong>

            <small>

                ${cliente.correo || "-"}

            </small>

        </div>

    `;

}

/*==================================================
=            COLUMNA CIUDAD
==================================================*/

function crearColumnaCiudad(cliente){

    return cliente.ciudad || "-";

}

/*==================================================
=            COLUMNA COMPRAS
==================================================*/

function crearColumnaCompras(cliente){

    return cliente.cantidadCompras;

}

/*==================================================
=            COLUMNA ESTADO
==================================================*/

function crearColumnaEstado(cliente){

    const badge =

        cliente.estado === "Activo"

            ? "badge badge-success"
            : "badge badge-danger";

    return `

        <span class="${badge}">

            ${cliente.estado}

        </span>

    `;

}

/*==================================================
=            COLUMNA ACCIONES
==================================================*/

function crearColumnaAcciones(cliente){

    return `

        <div class="table-actions">

            <button

                class="table-action view"

                data-id="${cliente.id}"

                title="Ver Cliente">

                <i class="fa-solid fa-eye"></i>

            </button>

            <button

                class="table-action edit"

                data-id="${cliente.id}"

                title="Editar Cliente">

                <i class="fa-solid fa-pen"></i>

            </button>

            <button

                class="table-action power"

                data-id="${cliente.id}"

                title="${cliente.estado === "Activo" ? "Inactivar" : "Activar"}">

                <i class="fa-solid fa-power-off"></i>

            </button>

        </div>

    `;

}

/*==================================================
=            BÚSQUEDA DE CLIENTES
==================================================*/

function buscarClientes(){

    const texto =

        searchClient.value
            .trim()
            .toLowerCase();

    const clientes =

        clientesController.buscar(

            texto

        );

    renderizarTabla(

        clientes

    );

}

/*==================================================
=            FILTROS
==================================================*/

function aplicarFiltros(){

    const estado =

        filterEstado.value;

    const tipoCliente =

        filterTipoCliente.value;

    const clientes =

        clientesController.filtrar(

            estado,

            tipoCliente

        );

    renderizarTabla(

        clientes

    );

}

/*==================================================
=            LIMPIAR FILTROS
==================================================*/

function limpiarFiltros(){

    limpiarBuscador();

    limpiarSelects();

    cargarTabla();

}

/*==================================================
=            LIMPIAR BUSCADOR
==================================================*/

function limpiarBuscador(){

    searchClient.value = "";

}

/*==================================================
=            LIMPIAR SELECTS
==================================================*/

function limpiarSelects(){

    filterEstado.value = "";

    filterTipoCliente.value = "";

}

/*==================================================
=            RECARGAR TABLA
==================================================*/

function refrescarTabla(){

    cargarTabla();

    cargarEstadisticas();

}

/*==================================================
=            VER CLIENTE
==================================================*/

function verCliente(id){

    const cliente =

        clientesController.obtenerCliente(id);

    if(!cliente){

        return;

    }

    clienteEditando = null;

    abrirModal();

    cargarFormulario(cliente);

    deshabilitarFormulario();

    document.querySelector(

        ".btn-save"

    ).style.display = "none";

}

/*==================================================
=            EDITAR CLIENTE
==================================================*/

function editarCliente(id){

    const cliente =

        clientesController.obtenerCliente(id);

    if(!cliente){

        return;

    }

    clienteEditando = id;

    abrirModal();

    cargarFormulario(cliente);

}

/*==================================================
=            CAMBIAR ESTADO
==================================================*/

function cambiarEstadoCliente(id){

    if(

        !confirm(

            "¿Desea cambiar el estado de este cliente?"

        )

    ){

        return;

    }

    clientesController.cambiarEstado(id);

    registrarActividad(

        "cliente",

        "estado",

        "Se cambió el estado de un cliente."

    );

    refrescarTabla();

}

/*==================================================
=            CARGAR FORMULARIO
==================================================*/

function cargarFormulario(cliente){

    document.getElementById(

        "tipoDocumento"

    ).value = cliente.tipoDocumento;

    document.getElementById(

        "documento"

    ).value = cliente.documento;

    document.getElementById(

        "nombres"

    ).value = cliente.nombres;

    document.getElementById(

        "apellidos"

    ).value = cliente.apellidos;

    document.getElementById(

        "fechaNacimiento"

    ).value = cliente.fechaNacimiento;

    document.getElementById(

        "genero"

    ).value = cliente.genero;

    document.getElementById(

        "telefono"

    ).value = cliente.telefono;

    document.getElementById(

        "correo"

    ).value = cliente.correo;

    document.getElementById(

        "ciudad"

    ).value = cliente.ciudad;

    document.getElementById(

        "direccion"

    ).value = cliente.direccion;

    document.getElementById(

        "tipoCliente"

    ).value = cliente.tipoCliente;

    document.getElementById(

        "estadoCliente"

    ).value = cliente.estado;

    document.getElementById(

        "observaciones"

    ).value = cliente.observaciones;

    document.getElementById(

        "aceptaPromociones"

    ).checked =

        cliente.aceptaPromociones;

}

/*==================================================
=            BOTONES DE LA TABLA
==================================================*/

function inicializarBotonesTabla(){

    clientsTableBody.onclick = function(evento){

        const boton = evento.target.closest(".table-action");

        if(!boton){

            return;

        }

        const id = boton.dataset.id;

        if(boton.classList.contains("view")){

            verCliente(id);

            return;

        }

        if(boton.classList.contains("edit")){

            editarCliente(id);

            return;

        }

        if(boton.classList.contains("power")){

            cambiarEstadoCliente(id);

        }

    };

}

/*==================================================
=            UTILIDADES
==================================================*/

function obtenerCliente(id){

    return clientesController.obtenerCliente(id);

}

function obtenerTodosLosClientes(){

    return clientesController.obtenerClientes();

}

function existeCliente(id){

    return obtenerCliente(id) !== undefined;

}

/*==================================================
=            RECARGAR MÓDULO
==================================================*/

function recargarModulo(){

    cargarEstadisticas();

    cargarTabla();

}

/*==================================================
=            ACTUALIZACIÓN AUTOMÁTICA
==================================================*/

window.addEventListener(

    "focus",

    recargarModulo

);