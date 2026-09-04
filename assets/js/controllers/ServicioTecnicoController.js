/**
 * ==========================================================
 * SYSMOVILWT
 * WT SERVIPHONE
 * ==========================================================
 * MÓDULO: SERVICIO TÉCNICO
 * ARCHIVO: ServicioTecnicoController.js
 * VERSIÓN: 2.0
 *
 * CRUD COMPLETO:
 * - Crear
 * - Consultar
 * - Editar
 * - Eliminar
 * - Cambiar estado
 * - Buscar
 * - Filtrar
 * - Paginación
 * - KPIs
 * - Exportar
 * - Imprimir
 * - LocalStorage
 * ==========================================================
 */

class ServicioTecnicoController {

    constructor(view) {

        this.view = view;

        /*==================================================
        =                 CAMPOS DEL FORMULARIO
        ==================================================*/

        this.campos = {

            nombreCliente:
                document.getElementById("nombreCliente"),

            documento:
                document.getElementById("documento"),

            telefono:
                document.getElementById("telefono"),

            correo:
                document.getElementById("correo"),

            tipoEquipo:
                document.getElementById("tipoEquipo"),

            marcaEquipo:
                document.getElementById("marcaEquipo"),

            modelo:
                document.getElementById("modelo"),

            imei:
                document.getElementById("imei"),

            colorEquipo:
                document.getElementById("colorEquipo"),

            password:
                document.getElementById("password"),

            fechaIngreso:
                document.getElementById("fechaIngreso"),

            tecnico:
                document.getElementById("tecnico"),

            prioridad:
                document.getElementById("prioridad"),

            estado:
                document.getElementById("estado"),

            fallaCliente:
                document.getElementById("fallaCliente"),

            diagnostico:
                document.getElementById("diagnostico"),

            repuestosUtilizados:
                document.getElementById("repuestosUtilizados"),

            manoObra:
                document.getElementById("manoObra"),

            valorRepuestos:
                document.getElementById("valorRepuestos"),

            valorTotal:
                document.getElementById("valorTotal"),

            observaciones:
                document.getElementById("observaciones")

        };

        /*==================================================
        =                    DATOS
        ==================================================*/

        this.servicios = [];

        this.servicioEditando = null;

        /*==================================================
        =                 PAGINACIÓN
        ==================================================*/

        this.registrosPorPagina = 10;

        this.paginaActual = 1;

        /*==================================================
        =                    FILTROS
        ==================================================*/

        this.filtros = {

            texto: "",

            estado: "",

            prioridad: "",

            tecnico: ""

        };

    }


    /*==================================================
    =                  INICIALIZACIÓN
    ==================================================*/

    init() {

        this.cargarDatos();

        this.registrarEventos();

        this.actualizarTecnicos();

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

        this.render();

    }


    /*==================================================
    =                 CARGAR DATOS
    ==================================================*/

    cargarDatos() {

        try {

            const datos =
                localStorage.getItem(
                    "serviciosTecnicos"
                );

            this.servicios =
                datos
                    ? JSON.parse(datos)
                    : [];

        } catch (error) {

            console.error(
                "Error cargando servicios:",
                error
            );

            this.servicios = [];

        }

    }


    /*==================================================
    =                 GUARDAR DATOS
    ==================================================*/

    guardarDatos() {

        localStorage.setItem(

            "serviciosTecnicos",

            JSON.stringify(
                this.servicios
            )

        );

    }


    /*==================================================
    =              GENERAR NÚMERO DE ORDEN
    ==================================================*/

    generarConsecutivo() {

        const hoy = new Date();

        const fecha =
            hoy.getFullYear().toString() +

            String(
                hoy.getMonth() + 1
            ).padStart(2, "0") +

            String(
                hoy.getDate()
            ).padStart(2, "0");

        const prefijo =
            `OST-${fecha}-`;

        const numeros =
            this.servicios

                .map(
                    servicio =>
                        servicio.numeroOrden
                )

                .filter(
                    numero =>
                        numero &&
                        numero.startsWith(
                            prefijo
                        )
                )

                .map(
                    numero =>
                        Number(
                            numero.replace(
                                prefijo,
                                ""
                            )
                        )
                )

                .filter(
                    numero =>
                        !isNaN(numero)
                );

        const siguiente =
            numeros.length
                ? Math.max(...numeros) + 1
                : 1;

        return (

            prefijo +

            String(siguiente)
                .padStart(4, "0")

        );

    }


    /*==================================================
    =               CREAR ORDEN NUEVA
    ==================================================*/

    crearOrdenNueva() {

        return {

            id:
                crypto.randomUUID(),

            numeroOrden:
                this.generarConsecutivo(),

            fechaIngreso:
                this.campos.fechaIngreso.value ||
                new Date()
                    .toISOString()
                    .split("T")[0],

            fechaEntrega:
                null,

            cliente: {

                id: "",

                nombre: "",

                documento: "",

                telefono: "",

                correo: ""

            },

            equipo: {

                tipo: "",

                marca: "",

                modelo: "",

                imei: "",

                serial: "",

                color: "",

                password: ""

            },

            recepcion: {

                tecnico: "",

                prioridad: "Media",

                estado: "Recibido"

            },

            fallaCliente: "",

            diagnostico: "",

            repuestosUtilizados: "",

            manoObra: 0,

            valorRepuestos: 0,

            valorTotal: 0,

            observaciones: "",

            fechaCreacion:
                Date.now(),

            fechaActualizacion:
                Date.now()

        };

    }


    /*==================================================
    =                  EVENTOS
    ==================================================*/

    registrarEventos() {

        /*------------------------------------------------
        NUEVA ORDEN
        ------------------------------------------------*/

        if (this.view.btnNuevo) {

            this.view.btnNuevo.addEventListener(
                "click",
                () => {

                    this.abrirModal();

                }
            );

        }


        /*------------------------------------------------
        CERRAR MODAL
        ------------------------------------------------*/

        if (this.view.btnCancelar) {

            this.view.btnCancelar.addEventListener(
                "click",
                () => {

                    this.cerrarModal();

                }
            );

        }


        if (this.view.btnCerrarModal) {

            this.view.btnCerrarModal.addEventListener(
                "click",
                () => {

                    this.cerrarModal();

                }
            );

        }


        /*------------------------------------------------
        GUARDAR
        ------------------------------------------------*/

        if (this.view.form) {

            this.view.form.addEventListener(
                "submit",
                event => {

                    event.preventDefault();

                    this.guardarServicio();

                }
            );

        }


        /*------------------------------------------------
        BUSCAR
        ------------------------------------------------*/

        if (this.view.txtBuscar) {

            this.view.txtBuscar.addEventListener(
                "input",
                event => {

                    this.filtros.texto =
                        event.target.value
                            .trim()
                            .toLowerCase();

                    this.paginaActual = 1;

                    this.render();

                }
            );

        }


        /*------------------------------------------------
        FILTRO ESTADO
        ------------------------------------------------*/

        if (this.view.filtroEstado) {

            this.view.filtroEstado.addEventListener(
                "change",
                event => {

                    this.filtros.estado =
                        event.target.value;

                    this.paginaActual = 1;

                    this.render();

                }
            );

        }


        /*------------------------------------------------
        FILTRO PRIORIDAD
        ------------------------------------------------*/

        if (this.view.filtroPrioridad) {

            this.view.filtroPrioridad.addEventListener(
                "change",
                event => {

                    this.filtros.prioridad =
                        event.target.value;

                    this.paginaActual = 1;

                    this.render();

                }
            );

        }


        /*------------------------------------------------
        FILTRO TÉCNICO
        ------------------------------------------------*/

        if (this.view.filtroTecnico) {

            this.view.filtroTecnico.addEventListener(
                "change",
                event => {

                    this.filtros.tecnico =
                        event.target.value;

                    this.paginaActual = 1;

                    this.render();

                }
            );

        }


        /*------------------------------------------------
        LIMPIAR FILTROS
        ------------------------------------------------*/

        if (this.view.btnResetFiltros) {

            this.view.btnResetFiltros.addEventListener(
                "click",
                () => {

                    this.limpiarFiltros();

                }
            );

        }


        /*------------------------------------------------
        EXPORTAR
        ------------------------------------------------*/

        if (this.view.btnExportar) {

            this.view.btnExportar.addEventListener(
                "click",
                () => {

                    this.exportarServicios();

                }
            );

        }


        /*------------------------------------------------
        PAGINACIÓN
        ------------------------------------------------*/

        if (this.view.btnPaginaAnterior) {

            this.view.btnPaginaAnterior.addEventListener(
                "click",
                () => {

                    this.paginaAnterior();

                }
            );

        }


        if (this.view.btnPaginaSiguiente) {

            this.view.btnPaginaSiguiente.addEventListener(
                "click",
                () => {

                    this.paginaSiguiente();

                }
            );

        }


        /*------------------------------------------------
        ACCIONES DE TABLA
        ------------------------------------------------*/

        if (this.view.tbody) {

            this.view.tbody.addEventListener(
                "click",
                event => {

                    const boton =
                        event.target.closest(
                            "button"
                        );

                    if (!boton) return;

                    const id =
                        boton.dataset.id;

                    if (
                        boton.classList.contains(
                            "btn-view"
                        )
                    ) {

                        this.verServicio(id);

                    }

                    if (
                        boton.classList.contains(
                            "btn-edit"
                        )
                    ) {

                        this.editarServicio(id);

                    }

                    if (
                        boton.classList.contains(
                            "btn-status"
                        )
                    ) {

                        this.cambiarEstado(id);

                    }

                    if (
                        boton.classList.contains(
                            "btn-print"
                        )
                    ) {

                        this.imprimirOrden(id);

                    }

                    if (
                        boton.classList.contains(
                            "btn-delete"
                        )
                    ) {

                        this.eliminarServicio(id);

                    }

                }
            );

        }


        /*------------------------------------------------
        CÁLCULO AUTOMÁTICO
        ------------------------------------------------*/

        const calcularTotal = () => {

            const manoObra =
                Number(
                    this.campos.manoObra?.value
                ) || 0;

            const repuestos =
                Number(
                    this.campos.valorRepuestos?.value
                ) || 0;

            if (this.campos.valorTotal) {

                this.campos.valorTotal.value =
                    manoObra + repuestos;

            }

        };


        if (this.campos.manoObra) {

            this.campos.manoObra.addEventListener(
                "input",
                calcularTotal
            );

        }


        if (this.campos.valorRepuestos) {

            this.campos.valorRepuestos.addEventListener(
                "input",
                calcularTotal
            );

        }

    }


    /*==================================================
    =                   ABRIR MODAL
    ==================================================*/

    abrirModal() {

        this.servicioEditando = null;

        this.limpiarFormulario();

        if (this.view.modal) {

            this.view.modal.classList.add(
                "active"
            );

        }

    }


    /*==================================================
    =                  CERRAR MODAL
    ==================================================*/

    cerrarModal() {

        if (this.view.modal) {

            this.view.modal.classList.remove(
                "active"
            );

        }

        if (this.view.form) {

            this.view.form.reset();

        }

        this.servicioEditando = null;

    }


    /*==================================================
    =              LIMPIAR FORMULARIO
    ==================================================*/

    limpiarFormulario() {

        if (!this.view.form) return;

        this.view.form.reset();

        if (this.campos.fechaIngreso) {

            this.campos.fechaIngreso.value =
                new Date()
                    .toISOString()
                    .split("T")[0];

        }

        if (this.campos.estado) {

            this.campos.estado.value =
                "Recibido";

        }

        if (this.campos.prioridad) {

            this.campos.prioridad.value =
                "Media";

        }

        if (this.campos.manoObra) {

            this.campos.manoObra.value = 0;

        }

        if (this.campos.valorRepuestos) {

            this.campos.valorRepuestos.value = 0;

        }

        if (this.campos.valorTotal) {

            this.campos.valorTotal.value = 0;

        }

    }


    /*==================================================
    =             OBTENER DATOS DEL FORMULARIO
    ==================================================*/

    obtenerDatosFormulario() {

        const servicio =
            this.crearOrdenNueva();

        servicio.cliente.nombre =
            this.campos.nombreCliente.value.trim();

        servicio.cliente.documento =
            this.campos.documento.value.trim();

        servicio.cliente.telefono =
            this.campos.telefono.value.trim();

        servicio.cliente.correo =
            this.campos.correo.value.trim();


        servicio.equipo.tipo =
            this.campos.tipoEquipo.value;

        servicio.equipo.marca =
            this.campos.marcaEquipo.value.trim();

        servicio.equipo.modelo =
            this.campos.modelo.value.trim();

        servicio.equipo.imei =
            this.campos.imei.value.trim();

        servicio.equipo.color =
            this.campos.colorEquipo.value.trim();

        servicio.equipo.password =
            this.campos.password.value.trim();


        servicio.fechaIngreso =
            this.campos.fechaIngreso.value;


        servicio.recepcion.tecnico =
            this.campos.tecnico.value;

        servicio.recepcion.prioridad =
            this.campos.prioridad.value;

        servicio.recepcion.estado =
            this.campos.estado.value;


        servicio.fallaCliente =
            this.campos.fallaCliente.value.trim();

        servicio.diagnostico =
            this.campos.diagnostico.value.trim();

        servicio.repuestosUtilizados =
            this.campos.repuestosUtilizados.value.trim();


        servicio.manoObra =
            Number(
                this.campos.manoObra.value
            ) || 0;

        servicio.valorRepuestos =
            Number(
                this.campos.valorRepuestos.value
            ) || 0;

        servicio.valorTotal =
            servicio.manoObra +
            servicio.valorRepuestos;


        servicio.observaciones =
            this.campos.observaciones.value.trim();


        return servicio;

    }


    /*==================================================
    =                 GUARDAR SERVICIO
    ==================================================*/

    guardarServicio() {

        const servicio =
            this.obtenerDatosFormulario();

        /*------------------------------------------------
        VALIDACIONES
        ------------------------------------------------*/

        if (
            !servicio.cliente.nombre ||
            !servicio.cliente.documento ||
            !servicio.cliente.telefono
        ) {

            alert(
                "Complete los datos obligatorios del cliente."
            );

            return;

        }


        if (
            !servicio.equipo.tipo ||
            !servicio.equipo.marca ||
            !servicio.equipo.modelo
        ) {

            alert(
                "Complete los datos obligatorios del equipo."
            );

            return;

        }


        if (
            !servicio.equipo.imei &&
            !servicio.equipo.serial
        ) {

            /*
             * El HTML actual maneja IMEI.
             * No bloqueamos el registro si el usuario
             * trabaja con otro identificador.
             */

        }


        const esEdicion =
            !!this.servicioEditando;


        /*------------------------------------------------
        EDITAR
        ------------------------------------------------*/

        if (esEdicion) {

            const anterior =
                this.buscarServicioPorId(
                    this.servicioEditando
                );

            if (anterior) {

                servicio.id =
                    anterior.id;

                servicio.numeroOrden =
                    anterior.numeroOrden;

                servicio.fechaCreacion =
                    anterior.fechaCreacion;

                servicio.fechaEntrega =
                    anterior.fechaEntrega;

            }

            servicio.fechaActualizacion =
                Date.now();

            this.actualizarServicio(
                servicio
            );


            this.registrarActividad(

                "Orden actualizada",

                `${servicio.numeroOrden} fue actualizada correctamente.`

            );

        }

        /*------------------------------------------------
        NUEVA ORDEN
        ------------------------------------------------*/

        else {

            this.crearServicio(
                servicio
            );


            this.registrarActividad(

                "Nueva orden",

                `${servicio.numeroOrden} fue registrada correctamente.`

            );

        }


        this.guardarDatos();

        this.cerrarModal();

        this.actualizarTecnicos();

        this.paginaActual = 1;

        this.render();

    }


    /*==================================================
    =                  CREAR SERVICIO
    ==================================================*/

    crearServicio(servicio) {

        this.servicios.unshift(
            servicio
        );

    }


    /*==================================================
    =                ACTUALIZAR SERVICIO
    ==================================================*/

    actualizarServicio(
        servicioActualizado
    ) {

        this.servicios =
            this.servicios.map(
                servicio =>

                    servicio.id ===
                    servicioActualizado.id

                        ? servicioActualizado

                        : servicio

            );

    }


    /*==================================================
    =                 BUSCAR POR ID
    ==================================================*/

    buscarServicioPorId(id) {

        return this.servicios.find(
            servicio =>
                servicio.id === id
        );

    }


    /*==================================================
    =                    EDITAR
    ==================================================*/

    editarServicio(id) {

        const servicio =
            this.buscarServicioPorId(id);

        if (!servicio) return;

        this.cargarFormulario(
            servicio
        );

    }


    /*==================================================
    =              CARGAR FORMULARIO
    ==================================================*/

    cargarFormulario(servicio) {

        this.campos.nombreCliente.value =
            servicio.cliente?.nombre || "";

        this.campos.documento.value =
            servicio.cliente?.documento || "";

        this.campos.telefono.value =
            servicio.cliente?.telefono || "";

        this.campos.correo.value =
            servicio.cliente?.correo || "";


        this.campos.tipoEquipo.value =
            servicio.equipo?.tipo || "";

        this.campos.marcaEquipo.value =
            servicio.equipo?.marca || "";

        this.campos.modelo.value =
            servicio.equipo?.modelo || "";

        this.campos.imei.value =
            servicio.equipo?.imei || "";

        this.campos.colorEquipo.value =
            servicio.equipo?.color || "";

        this.campos.password.value =
            servicio.equipo?.password || "";


        this.campos.fechaIngreso.value =
            (
                servicio.fechaIngreso || ""
            ).split("T")[0];


        this.campos.tecnico.value =
            servicio.recepcion?.tecnico || "";

        this.campos.prioridad.value =
            servicio.recepcion?.prioridad ||
            "Media";

        this.campos.estado.value =
            servicio.recepcion?.estado ||
            "Recibido";


        this.campos.fallaCliente.value =
            servicio.fallaCliente || "";

        this.campos.diagnostico.value =
            servicio.diagnostico || "";

        this.campos.repuestosUtilizados.value =
            servicio.repuestosUtilizados || "";


        this.campos.manoObra.value =
            servicio.manoObra || 0;

        this.campos.valorRepuestos.value =
            servicio.valorRepuestos || 0;

        this.campos.valorTotal.value =
            servicio.valorTotal || 0;


        this.campos.observaciones.value =
            servicio.observaciones || "";


        this.servicioEditando =
            servicio.id;


        if (this.view.modal) {

            this.view.modal.classList.add(
                "active"
            );

        }

    }


    /*==================================================
    =                   ELIMINAR
    ==================================================*/

    eliminarServicio(id) {

        const servicio =
            this.buscarServicioPorId(id);

        if (!servicio) return;

        const confirmar =
            confirm(
                `¿Deseas eliminar la orden ${servicio.numeroOrden}?`
            );

        if (!confirmar) return;


        this.servicios =
            this.servicios.filter(
                item =>
                    item.id !== id
            );


        this.guardarDatos();


        this.registrarActividad(

            "Orden eliminada",

            `${servicio.numeroOrden} fue eliminada correctamente.`

        );


        this.actualizarTecnicos();

        this.render();

    }


    /*==================================================
    =                   VER SERVICIO
    ==================================================*/

    verServicio(id) {

        const servicio =
            this.buscarServicioPorId(id);

        if (!servicio) return;


        const total =
            Number(
                servicio.valorTotal || 0
            ).toLocaleString(
                "es-CO",
                {
                    style: "currency",
                    currency: "COP",
                    maximumFractionDigits: 0
                }
            );


        alert(

`ORDEN: ${servicio.numeroOrden}

CLIENTE
${servicio.cliente.nombre}
Documento: ${servicio.cliente.documento}
Teléfono: ${servicio.cliente.telefono}

EQUIPO
${servicio.equipo.tipo}
${servicio.equipo.marca} ${servicio.equipo.modelo}
IMEI: ${servicio.equipo.imei || "No registrado"}

SERVICIO
Técnico: ${servicio.recepcion.tecnico || "Sin asignar"}
Prioridad: ${servicio.recepcion.prioridad}
Estado: ${servicio.recepcion.estado}

DIAGNÓSTICO
${servicio.diagnostico || "Pendiente"}

TOTAL
${total}`

        );

    }


    /*==================================================
    =                CAMBIAR ESTADO
    ==================================================*/

    cambiarEstado(id) {

        const servicio =
            this.buscarServicioPorId(id);

        if (!servicio) return;


        const estados = [

            "Recibido",

            "En Diagnóstico",

            "Pendiente Repuestos",

            "En Reparación",

            "Control de Calidad",

            "Listo para Entrega",

            "Entregado"

        ];


        const actual =
            servicio.recepcion.estado;

        const indice =
            estados.indexOf(actual);


        if (
            indice === -1 ||
            indice >= estados.length - 1
        ) {

            alert(
                "La orden ya se encuentra en el último estado."
            );

            return;

        }


        const nuevoEstado =
            estados[indice + 1];


        servicio.recepcion.estado =
            nuevoEstado;


        if (
            nuevoEstado ===
            "Entregado"
        ) {

            servicio.fechaEntrega =
                new Date()
                    .toISOString();

        }


        servicio.fechaActualizacion =
            Date.now();


        this.guardarDatos();


        this.registrarActividad(

            "Cambio de estado",

            `${servicio.numeroOrden} cambió a "${nuevoEstado}".`

        );


        this.render();

    }


    /*==================================================
    =                   IMPRIMIR
    ==================================================*/

    imprimirOrden(id) {

        const servicio =
            this.buscarServicioPorId(id);

        if (!servicio) return;


        const ventana =
            window.open(
                "",
                "_blank",
                "width=900,height=700"
            );


        if (!ventana) {

            alert(
                "El navegador bloqueó la ventana de impresión."
            );

            return;

        }


        ventana.document.write(`

<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<title>
Orden ${servicio.numeroOrden}
</title>

<style>

body{

    font-family:Arial,sans-serif;

    padding:40px;

    color:#1f2937;

}

h1{

    color:#dc2626;

}

h2{

    border-bottom:2px solid #e5e7eb;

    padding-bottom:8px;

}

.info{

    margin-bottom:25px;

}

.grid{

    display:grid;

    grid-template-columns:1fr 1fr;

    gap:20px;

}

.total{

    font-size:24px;

    font-weight:bold;

}

</style>

</head>

<body>

<h1>
WT SERVIPHONE
</h1>

<h2>
ORDEN DE SERVICIO
</h2>

<div class="info">

<strong>
${servicio.numeroOrden}
</strong>

<br>

Fecha de ingreso:
${servicio.fechaIngreso}

</div>

<div class="grid">

<div>

<h2>Cliente</h2>

<p>
${servicio.cliente.nombre}
</p>

<p>
Documento:
${servicio.cliente.documento}
</p>

<p>
Teléfono:
${servicio.cliente.telefono}
</p>

</div>

<div>

<h2>Equipo</h2>

<p>
${servicio.equipo.tipo}
</p>

<p>
${servicio.equipo.marca}
${servicio.equipo.modelo}
</p>

<p>
IMEI:
${servicio.equipo.imei || "No registrado"}
</p>

</div>

</div>

<h2>Servicio</h2>

<p>
Técnico:
${servicio.recepcion.tecnico || "Sin asignar"}
</p>

<p>
Estado:
${servicio.recepcion.estado}
</p>

<p>
Prioridad:
${servicio.recepcion.prioridad}
</p>

<p>
Falla reportada:
${servicio.fallaCliente || "No registrada"}
</p>

<p>
Diagnóstico:
${servicio.diagnostico || "Pendiente"}
</p>

<h2>Costos</h2>

<p>
Mano de obra:
$${Number(servicio.manoObra || 0)
    .toLocaleString("es-CO")}
</p>

<p>
Repuestos:
$${Number(servicio.valorRepuestos || 0)
    .toLocaleString("es-CO")}
</p>

<p class="total">

TOTAL:
$${Number(servicio.valorTotal || 0)
    .toLocaleString("es-CO")}

</p>

<h2>Observaciones</h2>

<p>
${servicio.observaciones || "Sin observaciones"}
</p>

<script>

window.onload = function(){

    window.print();

};

<\/script>

</body>

</html>

        `);


        ventana.document.close();

    }


    /*==================================================
    =             OBTENER FILTRADOS
    ==================================================*/

    obtenerServiciosFiltrados() {

        let servicios =
            [...this.servicios];


        /* TEXTO */

        if (this.filtros.texto) {

            const texto =
                this.filtros.texto;


            servicios =
                servicios.filter(
                    servicio => {

                        const contenido = [

                            servicio.numeroOrden,

                            servicio.cliente?.nombre,

                            servicio.cliente?.documento,

                            servicio.cliente?.telefono,

                            servicio.equipo?.imei,

                            servicio.equipo?.serial,

                            servicio.equipo?.marca,

                            servicio.equipo?.modelo

                        ]

                        .filter(Boolean)

                        .join(" ")

                        .toLowerCase();


                        return contenido.includes(
                            texto
                        );

                    }
                );

        }


        /* ESTADO */

        if (this.filtros.estado) {

            servicios =
                servicios.filter(
                    servicio =>
                        servicio.recepcion?.estado ===
                        this.filtros.estado
                );

        }


        /* PRIORIDAD */

        if (this.filtros.prioridad) {

            servicios =
                servicios.filter(
                    servicio =>
                        servicio.recepcion?.prioridad ===
                        this.filtros.prioridad
                );

        }


        /* TÉCNICO */

        if (this.filtros.tecnico) {

            servicios =
                servicios.filter(
                    servicio =>
                        servicio.recepcion?.tecnico ===
                        this.filtros.tecnico
                );

        }


        return servicios;

    }


    /*==================================================
    =              RENDER PRINCIPAL
    ==================================================*/

    render() {

        this.renderKPIs();

        this.renderTabla();

        this.renderResumen();

    }


    /*==================================================
    =                    KPIs
    ==================================================*/

    renderKPIs() {

        const estados = this.servicios.map(
            servicio =>
                servicio.recepcion?.estado
        );


        this.view.kpiReceived.textContent =
            estados.filter(
                estado =>
                    estado === "Recibido"
            ).length;


        this.view.kpiDiagnosis.textContent =
            estados.filter(
                estado =>
                    estado === "En Diagnóstico"
            ).length;


        this.view.kpiRepair.textContent =
            estados.filter(
                estado =>
                    estado === "En Reparación"
            ).length;


        this.view.kpiReady.textContent =
            estados.filter(
                estado =>
                    estado === "Listo para Entrega"
            ).length;


        this.view.kpiDelivered.textContent =
            estados.filter(
                estado =>
                    estado === "Entregado"
            ).length;


        const facturacion =
            this.servicios.reduce(
                (total, servicio) =>
                    total +
                    Number(
                        servicio.valorTotal || 0
                    ),
                0
            );


        this.view.kpiIncome.textContent =
            facturacion.toLocaleString(
                "es-CO",
                {
                    style: "currency",
                    currency: "COP",
                    maximumFractionDigits: 0
                }
            );

    }


    /*==================================================
    =                    TABLA
    ==================================================*/

    renderTabla() {

        const servicios =
            this.obtenerServiciosFiltrados();


        const totalPaginas =
            Math.max(
                1,
                Math.ceil(
                    servicios.length /
                    this.registrosPorPagina
                )
            );


        if (
            this.paginaActual >
            totalPaginas
        ) {

            this.paginaActual =
                totalPaginas;

        }


        const inicio =
            (
                this.paginaActual - 1
            ) *
            this.registrosPorPagina;


        const pagina =
            servicios.slice(
                inicio,
                inicio +
                this.registrosPorPagina
            );


        this.view.tbody.innerHTML =
            "";


        if (!pagina.length) {

            this.renderEstadoVacio();

            return;

        }


        pagina.forEach(
            servicio => {

                this.view.tbody.insertAdjacentHTML(

                    "beforeend",

                    this.crearFila(
                        servicio
                    )

                );

            }
        );

    }


    /*==================================================
    =                CREAR FILA
    ==================================================*/

    crearFila(servicio) {

        const estado =
            servicio.recepcion?.estado ||
            "Recibido";


        const prioridad =
            servicio.recepcion?.prioridad ||
            "Media";


        const tecnico =
            servicio.recepcion?.tecnico ||
            "Sin asignar";


        const fecha =
            servicio.fechaIngreso
                ? new Date(
                    servicio.fechaIngreso
                ).toLocaleDateString(
                    "es-CO"
                )
                : "-";


        const total =
            Number(
                servicio.valorTotal || 0
            ).toLocaleString(
                "es-CO",
                {
                    style: "currency",
                    currency: "COP",
                    maximumFractionDigits: 0
                }
            );


        const equipo = [

            servicio.equipo?.tipo,

            servicio.equipo?.marca,

            servicio.equipo?.modelo

        ]

        .filter(Boolean)

        .join(" ");


        const imei =
            servicio.equipo?.imei ||
            servicio.equipo?.serial ||
            "-";


        return `

<tr>

<td>

<strong>
${servicio.numeroOrden}
</strong>

</td>


<td>
${fecha}
</td>


<td>

<strong>
${servicio.cliente?.nombre || "-"}
</strong>

<br>

<small>
${servicio.cliente?.telefono || ""}
</small>

</td>


<td>
${equipo || "-"}
</td>


<td>
${imei}
</td>


<td>
${tecnico}
</td>


<td>

<span class="
priority
priority-${this.obtenerClasePrioridad(
    prioridad
)}
">

${prioridad}

</span>

</td>


<td>

<span class="
status-badge
status-${this.obtenerClaseEstado(
    estado
)}
">

${estado}

</span>

</td>


<td>

<strong>
${total}
</strong>

</td>


<td>

<div class="service-actions">

<button

class="btn-view"

data-id="${servicio.id}"

title="Ver orden">

<i class="fa-solid fa-eye"></i>

</button>


<button

class="btn-edit"

data-id="${servicio.id}"

title="Editar orden">

<i class="fa-solid fa-pen"></i>

</button>


<button

class="btn-status"

data-id="${servicio.id}"

title="Cambiar estado">

<i class="fa-solid fa-repeat"></i>

</button>


<button

class="btn-print"

data-id="${servicio.id}"

title="Imprimir">

<i class="fa-solid fa-print"></i>

</button>


<button

class="btn-delete"

data-id="${servicio.id}"

title="Eliminar">

<i class="fa-solid fa-trash"></i>

</button>

</div>

</td>

</tr>

`;

    }


    /*==================================================
    =               ESTADO VACÍO
    ==================================================*/

    renderEstadoVacio() {

        this.view.tbody.innerHTML = `

<tr class="service-empty">

<td colspan="10">

<div class="empty-state">

<i class="
fa-solid
fa-screwdriver-wrench
"></i>

<h3>

No existen órdenes de servicio registradas

</h3>

<p>

Haz clic en

<strong>
Nueva Orden
</strong>

para registrar el primer ingreso al taller técnico.

</p>

</div>

</td>

</tr>

`;

    }


    /*==================================================
    =                CLASE ESTADO
    ==================================================*/

    obtenerClaseEstado(estado) {

        switch (estado) {

            case "Recibido":
                return "received";

            case "En Diagnóstico":
                return "diagnosis";

            case "Pendiente Repuestos":
                return "pending";

            case "En Reparación":
                return "repair";

            case "Control de Calidad":
                return "quality";

            case "Listo para Entrega":
                return "ready";

            case "Entregado":
                return "delivered";

            default:
                return "received";

        }

    }


    /*==================================================
    =              CLASE PRIORIDAD
    ==================================================*/

    obtenerClasePrioridad(
        prioridad
    ) {

        switch (prioridad) {

            case "Baja":
                return "low";

            case "Media":
                return "medium";

            case "Alta":
                return "high";

            case "Urgente":
                return "urgent";

            default:
                return "medium";

        }

    }


    /*==================================================
    =                RESUMEN
    ==================================================*/

    renderResumen() {

        const servicios =
            this.obtenerServiciosFiltrados();


        const total =
            servicios.length;


        const totalPaginas =
            Math.max(
                1,
                Math.ceil(
                    total /
                    this.registrosPorPagina
                )
            );


        const inicio =
            total === 0
                ? 0
                :
                (
                    this.paginaActual - 1
                ) *
                this.registrosPorPagina;


        const mostrando =
            total === 0
                ? 0
                :
                Math.min(
                    this.registrosPorPagina,
                    total - inicio
                );


        this.view.totalServicios.textContent =
            total;


        this.view.mostrando.textContent =
            mostrando;


        this.view.paginaActual.textContent =
            `Página ${this.paginaActual} de ${totalPaginas}`;


        this.view.btnPaginaAnterior.disabled =
            this.paginaActual <= 1;


        this.view.btnPaginaSiguiente.disabled =
            this.paginaActual >= totalPaginas;

    }


    /*==================================================
    =              PÁGINA ANTERIOR
    ==================================================*/

    paginaAnterior() {

        if (
            this.paginaActual > 1
        ) {

            this.paginaActual--;

            this.render();

        }

    }


    /*==================================================
    =             PÁGINA SIGUIENTE
    ==================================================*/

    paginaSiguiente() {

        const total =
            this.obtenerServiciosFiltrados()
                .length;


        const totalPaginas =
            Math.max(
                1,
                Math.ceil(
                    total /
                    this.registrosPorPagina
                )
            );


        if (
            this.paginaActual <
            totalPaginas
        ) {

            this.paginaActual++;

            this.render();

        }

    }


    /*==================================================
    =               LIMPIAR FILTROS
    ==================================================*/

    limpiarFiltros() {

        this.filtros = {

            texto: "",

            estado: "",

            prioridad: "",

            tecnico: ""

        };


        if (this.view.txtBuscar) {

            this.view.txtBuscar.value =
                "";

        }


        if (this.view.filtroEstado) {

            this.view.filtroEstado.value =
                "";

        }


        if (this.view.filtroPrioridad) {

            this.view.filtroPrioridad.value =
                "";

        }


        if (this.view.filtroTecnico) {

            this.view.filtroTecnico.value =
                "";

        }


        this.paginaActual = 1;

        this.render();

    }


    /*==================================================
    =             ACTUALIZAR TÉCNICOS
    ==================================================*/

    actualizarTecnicos() {

        if (
            !this.view.filtroTecnico
        ) return;


        const tecnicoActual =
            this.filtros.tecnico;


        const tecnicos =
            [
                ...new Set(

                    this.servicios

                        .map(
                            servicio =>
                                servicio.recepcion
                                    ?.tecnico
                        )

                        .filter(Boolean)

                )
            ];


        this.view.filtroTecnico.innerHTML = `

<option value="">
Todos los técnicos
</option>

`;


        tecnicos.forEach(
            tecnico => {

                const option =
                    document.createElement(
                        "option"
                    );

                option.value =
                    tecnico;

                option.textContent =
                    tecnico;

                this.view.filtroTecnico
                    .appendChild(
                        option
                    );

            }
        );


        this.view.filtroTecnico.value =
            tecnicoActual;

    }


    /*==================================================
    =             REGISTRAR ACTIVIDAD
    ==================================================*/

    registrarActividad(
        accion,
        descripcion
    ) {

        try {

            const actividades =
                JSON.parse(

                    localStorage.getItem(
                        "sysmovilwt_actividad"
                    )

                ) || [];


            actividades.unshift({

                id: Date.now(),

                modulo:
                    "Servicio Técnico",

                accion:
                    accion,

                descripcion:
                    descripcion,

                fecha:
                    new Date()
                        .toISOString()

            });


            if (
                actividades.length > 50
            ) {

                actividades.length =
                    50;

            }


            localStorage.setItem(

                "sysmovilwt_actividad",

                JSON.stringify(
                    actividades
                )

            );

        } catch (error) {

            console.error(
                "Error registrando actividad:",
                error
            );

        }

    }


    /*==================================================
    =                  EXPORTAR CSV
    ==================================================*/

    exportarServicios() {

        if (!this.servicios.length) {

            alert(
                "No hay servicios para exportar."
            );

            return;

        }


        const encabezados = [

            "Orden",

            "Fecha",

            "Cliente",

            "Documento",

            "Teléfono",

            "Equipo",

            "IMEI",

            "Técnico",

            "Prioridad",

            "Estado",

            "Mano de Obra",

            "Repuestos",

            "Total"

        ];


        const filas =
            this.servicios.map(
                servicio => [

                    servicio.numeroOrden,

                    servicio.fechaIngreso,

                    servicio.cliente?.nombre ||
                        "",

                    servicio.cliente?.documento ||
                        "",

                    servicio.cliente?.telefono ||
                        "",

                    [

                        servicio.equipo?.tipo,

                        servicio.equipo?.marca,

                        servicio.equipo?.modelo

                    ]

                    .filter(Boolean)

                    .join(" "),

                    servicio.equipo?.imei ||
                        "",

                    servicio.recepcion?.tecnico ||
                        "",

                    servicio.recepcion?.prioridad ||
                        "",

                    servicio.recepcion?.estado ||
                        "",

                    servicio.manoObra ||
                        0,

                    servicio.valorRepuestos ||
                        0,

                    servicio.valorTotal ||
                        0

                ]

            );


        let csv =
            encabezados.join(";") +
            "\n";


        filas.forEach(
            fila => {

                csv +=

                    fila

                        .map(
                            dato =>
                                `"${String(dato)
                                    .replace(
                                        /"/g,
                                        '""'
                                    )}"`
                        )

                        .join(";") +

                    "\n";

            }
        );


        const blob =
            new Blob(

                [
                    "\ufeff" +
                    csv
                ],

                {
                    type:
                        "text/csv;charset=utf-8;"
                }

            );


        const enlace =
            document.createElement(
                "a"
            );


        enlace.href =
            URL.createObjectURL(
                blob
            );


        enlace.download =
            `ServiciosTecnicos_${
                new Date()
                    .toISOString()
                    .slice(0,10)
            }.csv`;


        enlace.click();


        URL.revokeObjectURL(
            enlace.href
        );

    }

}


/*==================================================
=             EXPONER GLOBALMENTE
==================================================*/

window.ServicioTecnicoController =
    ServicioTecnicoController;
