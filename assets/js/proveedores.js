/**
 * ==================================================
 * SYSMOVILWT
 * MÓDULO DE PROVEEDORES
 * ==================================================
 */

const proveedoresController = new ProveedoresController();

let proveedorEditando = null;

/*==================================================
=            ELEMENTOS DEL DOM
==================================================*/

const suppliersTableBody =
    document.getElementById("suppliersTableBody");

const supplierModal =
    document.getElementById("supplierModal");

const supplierForm =
    document.getElementById("supplierForm");

const btnNuevoProveedor =
    document.getElementById("btnNuevoProveedor");

const btnCloseModal =
    document.getElementById("btnCloseModal");

const btnCancelarProveedor =
    document.getElementById("btnCancelarProveedor");

const searchProveedor =
    document.getElementById("searchProveedor");

const filterEstado =
    document.getElementById("filterEstado");

const filterTipo =
    document.getElementById("filterTipo");

const btnResetFilters =
    document.getElementById("btnResetFilters");

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

    cargarEstadisticas();

    cargarTabla();

    inicializarEventos();

    inicializarUbicacion();

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

        window.location.href = "../login.html";

        return;

    }

    document.getElementById(

        "topbarUser"

    ).textContent = usuario.nombre;

    document.getElementById(

        "topbarRole"

    ).textContent = usuario.rol;

}

/*==================================================
=            EVENTOS
==================================================*/

function inicializarEventos(){

    btnNuevoProveedor?.addEventListener(

        "click",

        ()=>{

            proveedorEditando = null;

            abrirModal();

        }

    );

    btnCloseModal?.addEventListener(

        "click",

        cerrarModal

    );

    btnCancelarProveedor?.addEventListener(

        "click",

        cerrarModal

    );

    supplierForm?.addEventListener(

        "submit",

        guardarProveedor

    );

    searchProveedor?.addEventListener(

        "input",

        buscarProveedor

    );

    filterEstado?.addEventListener(

        "change",

        aplicarFiltros

    );

    filterTipo?.addEventListener(

        "change",

        aplicarFiltros

    );

    btnResetFilters?.addEventListener(

        "click",

        limpiarFiltros

    );

}

/*==================================================
=            MODAL
==================================================*/

function abrirModal(){

    supplierModal.style.display = "flex";

    supplierForm.reset();

    habilitarFormulario();

    document.querySelector(

        ".btn-save"

    ).style.display = "inline-flex";

    document.querySelector(

        ".btn-save"

    ).textContent =

        proveedorEditando

        ? "Actualizar Proveedor"

        : "Guardar Proveedor";

}

function cerrarModal(){

    supplierModal.style.display = "none";

    supplierForm.reset();

    proveedorEditando = null;

    habilitarFormulario();

    document.querySelector(

        ".btn-save"

    ).style.display = "inline-flex";

    document.querySelector(

        ".btn-save"

    ).textContent =

        "Guardar Proveedor";

    const ciudad =

        document.getElementById(

            "ciudad"

        );

    ciudad.innerHTML = `

        <option value="">

            Seleccione una ciudad

        </option>

    `;

}

/*==================================================
=            UTILIDADES DEL FORMULARIO
==================================================*/

function habilitarFormulario(){

    supplierForm

    .querySelectorAll(

        "input, select, textarea"

    )

    .forEach(campo=>{

        campo.disabled = false;

    });

}

function deshabilitarFormulario(){

    supplierForm

    .querySelectorAll(

        "input, select, textarea"

    )

    .forEach(campo=>{

        campo.disabled = true;

    });

}

/*==================================================
=            OBTENER DATOS DEL FORMULARIO
==================================================*/

function obtenerDatosFormulario(){

    return{

        empresa:
            document.getElementById("empresa").value.trim(),

        tipoProveedor:
            document.getElementById("tipoProveedor").value,

        nit:
            document
                .getElementById("nit")
                .value
                .trim()
                .replace(/\s+/g,""),

        representante:
            document.getElementById("representante").value.trim(),

        contacto:
            document.getElementById("contacto").value.trim(),

        cargo:
            document.getElementById("cargo").value.trim(),

        celular:
            document.getElementById("celular").value.trim(),

        correo:
            document.getElementById("correo").value.trim(),

        pais:
            document.getElementById("pais").value,

        departamento:
            document.getElementById("departamento").value,

        ciudad:
            document.getElementById("ciudad").value,

        direccion:
            document.getElementById("direccion").value.trim()

    };

}

/*==================================================
=            LLENAR FORMULARIO
==================================================*/

function llenarFormularioProveedor(proveedor){

    document.getElementById("empresa").value =
        proveedor.empresa;

    document.getElementById("tipoProveedor").value =
        proveedor.tipoProveedor;

    document.getElementById("nit").value =
        proveedor.nit;

    document.getElementById("representante").value =
        proveedor.representante;

    document.getElementById("contacto").value =
        proveedor.contacto;

    document.getElementById("cargo").value =
        proveedor.cargo;

    document.getElementById("celular").value =
        proveedor.celular;

    document.getElementById("correo").value =
        proveedor.correo;

    document.getElementById("pais").value =
        proveedor.pais;

    document.getElementById("departamento").value =
        proveedor.departamento;

    const ciudad =

        document.getElementById("ciudad");

    ciudad.innerHTML = `

        <option value="">

            Seleccione una ciudad

        </option>

    `;

    const lista =

        ciudadesColombia[

            proveedor.departamento

        ] || [];

    lista.forEach(nombre=>{

        const option =

            document.createElement("option");

        option.value = nombre;

        option.textContent = nombre;

        ciudad.appendChild(option);

    });

    ciudad.value = proveedor.ciudad;

    document.getElementById("direccion").value =
        proveedor.direccion;

}

/*==================================================
=            GUARDAR
==================================================*/

function guardarProveedor(e){

    e.preventDefault();

    const datos =

        obtenerDatosFormulario();

    if(proveedorEditando){

        proveedoresController.actualizarProveedor(

            proveedorEditando,

            datos

        );

        registrarActividad(

            "proveedor",

            "editar",

            `Proveedor ${datos.empresa} actualizado.`

        );

    }else{

        proveedoresController.agregarProveedor(

            datos

        );

        registrarActividad(

            "proveedor",

            "crear",

            `Proveedor ${datos.empresa} registrado.`

        );

    }

    proveedorEditando = null;

    cargarTabla();

    cargarEstadisticas();

    cerrarModal();

}

/*==================================================
=            TABLA
==================================================*/

function cargarTabla(){

    const proveedores =

        proveedoresController.obtenerProveedores();

    renderizarTabla(

        proveedores

    );

}

function renderizarTabla(proveedores){

    if(proveedores.length === 0){

        suppliersTableBody.innerHTML = `

            <tr class="clients-empty">

                <td colspan="7">

                    <div class="empty-state">

                        <i class="fa-solid fa-truck"></i>

                        <h3>

                            No hay proveedores registrados

                        </h3>

                        <p>

                            Haz clic en
                            <strong>Nuevo Proveedor</strong>
                            para comenzar.

                        </p>

                    </div>

                </td>

            </tr>

        `;

        return;

    }

    suppliersTableBody.innerHTML = "";

    proveedores.forEach(proveedor=>{

        const badgeEstado =

            proveedor.estado === "Activo"

            ? "badge-success"

            : "badge-danger";

        suppliersTableBody.innerHTML += `

            <tr>

                <td>

                    <div class="table-client">

                        <div class="table-avatar">

                            <i class="fa-solid fa-building"></i>

                        </div>

                        <div class="table-info">

                            <strong>

                                ${proveedor.empresa}

                            </strong>

                            <small>

                                ${proveedor.tipoProveedor}

                            </small>

                        </div>

                    </div>

                </td>

                <td style="white-space:nowrap;">

                    ${String(proveedor.nit).replace(/\s+/g,"")}

                </td>

                <td>

                    <strong>

                        ${proveedor.contacto}

                    </strong>

                    <br>

                    <small>

                        ${proveedor.cargo || "Sin cargo"}

                    </small>

                </td>

                <td>

                    ${proveedor.ciudad}

                </td>

                <td>

                    ${proveedor.celular}

                </td>

                <td>

                    <span class="${badgeEstado}">

                        ${proveedor.estado}

                    </span>

                </td>

                <td>

                    <div class="table-actions">

                        <button

                            class="table-action view"

                            data-id="${proveedor.id}"

                            title="Ver">

                            <i class="fa-solid fa-eye"></i>

                        </button>

                        <button

                            class="table-action edit"

                            data-id="${proveedor.id}"

                            title="Editar">

                            <i class="fa-solid fa-pen"></i>

                        </button>

                        <button

                            class="table-action power"

                            data-id="${proveedor.id}"

                            title="Cambiar estado">

                            <i class="fa-solid fa-power-off"></i>

                        </button>

                    </div>

                </td>

            </tr>

        `;

    });

    inicializarBotonesTabla();

}

/*==================================================
=            ESTADÍSTICAS
==================================================*/

function cargarEstadisticas(){

    document.getElementById(

        "totalProveedores"

    ).textContent =

        proveedoresController.obtenerTotalProveedores();

    document.getElementById(

        "proveedoresActivos"

    ).textContent =

        proveedoresController.obtenerActivos();

    document.getElementById(

        "comprasProveedor"

    ).textContent =

        proveedoresController.obtenerCompras();

    document.getElementById(

        "valorComprado"

    ).textContent =

        "$ " +

        proveedoresController

            .obtenerValorComprado()

            .toLocaleString("es-CO");

}

/*==================================================
=            BUSCAR
==================================================*/

function buscarProveedor(){

    renderizarTabla(

        proveedoresController.buscar(

            searchProveedor.value

                .trim()

                .toLowerCase()

        )

    );

}

/*==================================================
=            FILTROS
==================================================*/

function aplicarFiltros(){

    renderizarTabla(

        proveedoresController.filtrar(

            filterEstado.value,

            filterTipo.value

        )

    );

}

function limpiarFiltros(){

    searchProveedor.value = "";

    filterEstado.value = "";

    filterTipo.value = "";

    cargarTabla();

}

/*==================================================
=            BOTONES DE LA TABLA
==================================================*/

function inicializarBotonesTabla(){

    document

        .querySelectorAll(".table-action.view")

        .forEach(boton=>{

            boton.onclick = ()=>{

                verProveedor(

                    boton.dataset.id

                );

            };

        });

    document

        .querySelectorAll(".table-action.edit")

        .forEach(boton=>{

            boton.onclick = ()=>{

                editarProveedor(

                    boton.dataset.id

                );

            };

        });

    document

        .querySelectorAll(".table-action.power")

        .forEach(boton=>{

            boton.onclick = ()=>{

                cambiarEstadoProveedor(

                    boton.dataset.id

                );

            };

        });

}

/*==================================================
=            VER
==================================================*/

function verProveedor(id){

    const proveedor =

        proveedoresController.obtenerProveedor(id);

    if(!proveedor){

        return;

    }

    proveedorEditando = null;

    abrirModal();

    llenarFormularioProveedor(

        proveedor

    );

    deshabilitarFormulario();

    document.querySelector(

        ".btn-save"

    ).style.display = "none";

}

/*==================================================
=            EDITAR
==================================================*/

function editarProveedor(id){

    const proveedor =

        proveedoresController.obtenerProveedor(id);

    if(!proveedor){

        return;

    }

    proveedorEditando = id;

    abrirModal();

    llenarFormularioProveedor(

        proveedor

    );

    habilitarFormulario();

    document.querySelector(

        ".btn-save"

    ).style.display = "inline-flex";

    document.querySelector(

        ".btn-save"

    ).textContent =

        "Actualizar Proveedor";

}

/*==================================================
=            CAMBIAR ESTADO
==================================================*/

function cambiarEstadoProveedor(id){

    const proveedor =

        proveedoresController.obtenerProveedor(id);

    if(!proveedor){

        return;

    }

    const accion =

        proveedor.estado === "Activo"

        ? "inactivar"

        : "activar";

    if(

        !confirm(

            `¿Desea ${accion} este proveedor?`

        )

    ){

        return;

    }

    proveedor.cambiarEstado();

    proveedoresController.guardarProveedores();

    registrarActividad(

        "proveedor",

        "estado",

        `Proveedor ${proveedor.empresa} ${accion}do.`

    );

    cargarTabla();

    cargarEstadisticas();

}

/*==================================================
=            DEPARTAMENTOS Y CIUDADES
==================================================*/

const ciudadesColombia = {

    "Antioquia":[
        "Medellín",
        "Bello",
        "Envigado",
        "Itagüí",
        "Rionegro"
    ],

    "Atlántico":[
        "Barranquilla",
        "Soledad",
        "Malambo"
    ],

    "Bogotá D.C.":[
        "Bogotá"
    ],

    "Bolívar":[
        "Cartagena",
        "Magangué"
    ],

    "Caldas":[
        "Manizales"
    ],

    "Cauca":[
        "Popayán"
    ],

    "Córdoba":[
        "Montería"
    ],

    "Cundinamarca":[
        "Soacha",
        "Chía",
        "Zipaquirá"
    ],

    "Huila":[
        "Neiva"
    ],

    "Magdalena":[
        "Santa Marta"
    ],

    "Meta":[
        "Villavicencio"
    ],

    "Nariño":[
        "Pasto"
    ],

    "Norte de Santander":[
        "Cúcuta"
    ],

    "Quindío":[
        "Armenia"
    ],

    "Risaralda":[
        "Pereira",
        "Dosquebradas"
    ],

    "Santander":[
        "Bucaramanga",
        "Floridablanca"
    ],

    "Tolima":[
        "Ibagué"
    ],

    "Valle del Cauca":[
        "Cali",
        "Palmira",
        "Jamundí",
        "Yumbo",
        "Buga",
        "Tuluá",
        "Cartago"
    ]

};

/*==================================================
=            UBICACIÓN
==================================================*/

function inicializarUbicacion(){

    const departamento =

        document.getElementById(

            "departamento"

        );

    const ciudad =

        document.getElementById(

            "ciudad"

        );

    if(

        !departamento ||

        !ciudad

    ){

        return;

    }

    ciudad.innerHTML = `

        <option value="">

            Seleccione una ciudad

        </option>

    `;

    departamento.onchange = ()=>{

        ciudad.innerHTML = `

            <option value="">

                Seleccione una ciudad

            </option>

        `;

        const lista =

            ciudadesColombia[

                departamento.value

            ] || [];

        lista.forEach(nombre=>{

            const option =

                document.createElement(

                    "option"

                );

            option.value = nombre;

            option.textContent = nombre;

            ciudad.appendChild(option);

        });

    };

}

/*==================================================
=            ACTUALIZACIÓN AUTOMÁTICA
==================================================*/

window.addEventListener(

    "focus",

    ()=>{

        cargarTabla();

        cargarEstadisticas();

    }

);

/*==================================================
=            FIN DEL ARCHIVO
==================================================*/

console.log(

    "%cSYSMOVILWT",

    "color:#dc2626;font-size:16px;font-weight:bold;"

);

console.log(

    "Módulo Proveedores v3.0 cargado correctamente."

);