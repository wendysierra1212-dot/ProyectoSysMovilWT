/*==================================================
=WT SERVIPHONE
=MODELO SERVICIO TÉCNICO
=VERSIÓN 1.1
==================================================*/

class ServicioTecnico {

    constructor(datos = {}) {

        /*=========================================
        =            IDENTIFICACIÓN
        =========================================*/

        this.id = datos.id || crypto.randomUUID();

        this.numeroOrden = datos.numeroOrden || "";

        this.fechaIngreso = datos.fechaIngreso || new Date().toISOString();

        this.fechaEntrega = datos.fechaEntrega || null;


        /*=========================================
        =            CLIENTE
        =========================================*/

        this.cliente = {

            id: datos.cliente?.id || "",

            nombre: datos.cliente?.nombre || "",

            documento: datos.cliente?.documento || "",

            telefono: datos.cliente?.telefono || "",

            correo: datos.cliente?.correo || ""

        };


        /*=========================================
        =            EQUIPO
        =========================================*/

        this.equipo = {

            tipo: datos.equipo?.tipo || "",

            marca: datos.equipo?.marca || "",

            modelo: datos.equipo?.modelo || "",

            imei: datos.equipo?.imei || "",

            serial: datos.equipo?.serial || "",

            color: datos.equipo?.color || "",

            password: datos.equipo?.password || ""

        };


        /*=========================================
        =            RECEPCIÓN
        =========================================*/

        this.recepcion = {

            accesorios: datos.recepcion?.accesorios || [],

            estadoFisico: datos.recepcion?.estadoFisico || "",

            observaciones: datos.recepcion?.observaciones || ""

        };


        /*=========================================
        =            DIAGNÓSTICO
        =========================================*/

        this.diagnostico = datos.diagnostico || "";


        /*=========================================
        =            REPARACIÓN
        =========================================*/

        this.repuestos = datos.repuestos || [];

        this.manoObra = Number(datos.manoObra) || 0;

        this.valorRepuestos = Number(datos.valorRepuestos) || 0;

        this.total = Number(datos.total) || 0;


        /*=========================================
        =            ASIGNACIÓN
        =========================================*/

        this.tecnico = datos.tecnico || "";

        this.prioridad = datos.prioridad || "Media";

        this.estado = datos.estado || "Recibido";


        /*=========================================
        =            GARANTÍA
        =========================================*/

        this.garantia = {

            aplica: datos.garantia?.aplica || false,

            dias: datos.garantia?.dias || 0,

            fechaVencimiento:
                datos.garantia?.fechaVencimiento || null

        };


        /*=========================================
        =            HISTORIAL
        =========================================*/

        this.historialEstados = datos.historialEstados || [];


        /*=========================================
        =            OBSERVACIONES
        =========================================*/

        this.observaciones = datos.observaciones || "";

    }

    /*=========================================
    =            GETTERS
    =========================================*/

    getId() {

        return this.id;

    }

    getNumeroOrden() {

        return this.numeroOrden;

    }

    getCliente() {

        return this.cliente;

    }

    getEquipo() {

        return this.equipo;

    }

    getRecepcion() {

        return this.recepcion;

    }

    getDiagnostico() {

        return this.diagnostico;

    }

    getTecnico() {

        return this.tecnico;

    }

    getEstado() {

        return this.estado;

    }

    getPrioridad() {

        return this.prioridad;

    }

    getTotal() {

        return this.total;

    }

    getGarantia() {

        return this.garantia;

    }

    getHistorialEstados() {

        return this.historialEstados;

    }

    getObservaciones() {

        return this.observaciones;

    }


    /*=========================================
    =            SETTERS
    =========================================*/

    setNumeroOrden(numeroOrden) {

        this.numeroOrden = numeroOrden;

    }

    setCliente(cliente) {

        this.cliente = {

            ...this.cliente,

            ...cliente

        };

    }

    setEquipo(equipo) {

        this.equipo = {

            ...this.equipo,

            ...equipo

        };

    }

    setRecepcion(recepcion) {

        this.recepcion = {

            ...this.recepcion,

            ...recepcion

        };

    }

    setDiagnostico(diagnostico) {

        this.diagnostico = diagnostico;

    }

    setTecnico(tecnico) {

        this.tecnico = tecnico;

    }

    setPrioridad(prioridad) {

        this.prioridad = prioridad;

    }

    setEstado(estado) {

        this.estado = estado;

    }

    setManoObra(valor) {

        this.manoObra = Number(valor) || 0;

    }

    setValorRepuestos(valor) {

        this.valorRepuestos = Number(valor) || 0;

    }

    setTotal(total) {

        this.total = Number(total) || 0;

    }

    setGarantia(garantia) {

        this.garantia = {

            ...this.garantia,

            ...garantia

        };

    }

    setObservaciones(observaciones) {

        this.observaciones = observaciones;

    }

        /*=========================================
    =            MÉTODOS DE NEGOCIO
    =========================================*/

    /**
     * Calcula automáticamente el valor total
     */
    calcularTotal() {

        this.total = this.manoObra + this.valorRepuestos;

        return this.total;

    }


    /**
     * Agrega un repuesto a la orden
     */
    agregarRepuesto(repuesto) {

        this.repuestos.push(repuesto);

        this.calcularValorRepuestos();

    }


    /**
     * Elimina un repuesto
     */
    eliminarRepuesto(index) {

        if (index >= 0 && index < this.repuestos.length) {

            this.repuestos.splice(index, 1);

            this.calcularValorRepuestos();

        }

    }


    /**
     * Recalcula el valor total de repuestos
     */
    calcularValorRepuestos() {

        this.valorRepuestos = this.repuestos.reduce((total, repuesto) => {

            return total + (Number(repuesto.valor) || 0);

        }, 0);

        this.calcularTotal();

    }


    /**
     * Cambia el estado del servicio
     */
    cambiarEstado(nuevoEstado) {

        this.estado = nuevoEstado;

        this.registrarCambioEstado(nuevoEstado);

    }


    /**
     * Guarda un historial de estados
     */
    registrarCambioEstado(estado) {

        this.historialEstados.push({

            estado,

            fecha: new Date().toISOString()

        });

    }


    /**
     * Asigna un técnico
     */
    asignarTecnico(nombreTecnico) {

        this.tecnico = nombreTecnico;

    }


    /**
     * Marca la orden como entregada
     */
    marcarComoEntregado() {

        this.estado = "Entregado";

        this.fechaEntrega = new Date().toISOString();

        this.registrarCambioEstado("Entregado");

    }


    /**
     * Genera un número de orden
     * Formato:
     * OST-20260729-0001
     */
    generarNumeroOrden(consecutivo = 1) {

        const hoy = new Date();

        const fecha =

            hoy.getFullYear().toString() +

            String(hoy.getMonth() + 1).padStart(2, "0") +

            String(hoy.getDate()).padStart(2, "0");

        this.numeroOrden =

            `OST-${fecha}-${String(consecutivo).padStart(4, "0")}`;

        return this.numeroOrden;

    }


    /**
     * Convierte el objeto a JSON
     */
    toJSON() {

        return {

            id: this.id,

            numeroOrden: this.numeroOrden,

            fechaIngreso: this.fechaIngreso,

            fechaEntrega: this.fechaEntrega,

            cliente: this.cliente,

            equipo: this.equipo,

            recepcion: this.recepcion,

            diagnostico: this.diagnostico,

            repuestos: this.repuestos,

            manoObra: this.manoObra,

            valorRepuestos: this.valorRepuestos,

            total: this.total,

            tecnico: this.tecnico,

            prioridad: this.prioridad,

            estado: this.estado,

            garantia: this.garantia,

            historialEstados: this.historialEstados,

            observaciones: this.observaciones

        };

    }
    /*=========================================
    =            VALIDACIONES
    =========================================*/

    /**
     * Valida la información mínima de una orden.
     * Retorna un arreglo con los errores encontrados.
     */
    validar() {

        const errores = [];

        /* Cliente */

        if (!this.cliente.nombre.trim()) {

            errores.push("El nombre del cliente es obligatorio.");

        }

        if (!this.cliente.documento.trim()) {

            errores.push("El documento del cliente es obligatorio.");

        }

        if (!this.cliente.telefono.trim()) {

            errores.push("El teléfono del cliente es obligatorio.");

        }


        /* Equipo */

        if (!this.equipo.tipo.trim()) {

            errores.push("Debe seleccionar el tipo de equipo.");

        }

        if (!this.equipo.marca.trim()) {

            errores.push("La marca del equipo es obligatoria.");

        }

        if (!this.equipo.modelo.trim()) {

            errores.push("El modelo del equipo es obligatorio.");

        }


        if (
            !this.equipo.imei.trim() &&
            !this.equipo.serial.trim()
        ) {

            errores.push(
                "Debe registrar el IMEI o el Serial del equipo."
            );

        }


        /* Servicio */

        if (!this.tecnico.trim()) {

            errores.push(
                "Debe asignarse un técnico responsable."
            );

        }

        if (!this.estado.trim()) {

            errores.push(
                "El estado de la orden es obligatorio."
            );

        }


        /* Valores */

        if (this.manoObra < 0) {

            errores.push(
                "La mano de obra no puede ser negativa."
            );

        }

        if (this.valorRepuestos < 0) {

            errores.push(
                "El valor de los repuestos no puede ser negativo."
            );

        }

        return errores;

    }


    /**
     * Indica si la orden es válida.
     */
    esValida() {

        return this.validar().length === 0;

    }


    /**
     * Verifica si el servicio ya fue entregado.
     */
    estaEntregado() {

        return this.estado === "Entregado";

    }


    /**
     * Verifica si la garantía continúa vigente.
     */
    garantiaVigente() {

        if (!this.garantia.aplica) {

            return false;

        }

        if (!this.garantia.fechaVencimiento) {

            return false;

        }

        return new Date() <=
               new Date(this.garantia.fechaVencimiento);

    }


    /**
     * Obtiene el tiempo transcurrido desde el ingreso.
     */
    obtenerDiasEnServicio() {

        const ingreso = new Date(this.fechaIngreso);

        const hoy = new Date();

        const diferencia = hoy - ingreso;

        return Math.floor(

            diferencia / (1000 * 60 * 60 * 24)

        );

    }


    /**
     * Reinicia completamente la orden.
     */
    limpiar() {

        Object.assign(this, new ServicioTecnico());

    }

        /*=========================================
    =            MÉTODOS ESTÁTICOS
    =========================================*/

    /**
     * Crea una instancia de ServicioTecnico
     * a partir de un objeto JSON.
     */
    static fromJSON(datos = {}) {

        return new ServicioTecnico(datos);

    }


    /**
     * Crea una copia profunda de la orden.
     */
    clonar() {

        return ServicioTecnico.fromJSON(

            structuredClone(this.toJSON())

        );

    }


    /**
     * Devuelve un resumen de la orden.
     */
    obtenerResumen() {

        return {

            id: this.id,

            numeroOrden: this.numeroOrden,

            cliente: this.cliente.nombre,

            equipo:

                `${this.equipo.marca} ${this.equipo.modelo}`.trim(),

            tecnico: this.tecnico,

            estado: this.estado,

            prioridad: this.prioridad,

            total: this.total,

            fechaIngreso: this.fechaIngreso

        };

    }


    /**
     * Verifica si la orden tiene garantía.
     */
    tieneGarantia() {

        return this.garantia.aplica === true;

    }


    /**
     * Devuelve el valor total formateado.
     */
    obtenerTotalFormateado() {

        return new Intl.NumberFormat("es-CO", {

            style: "currency",

            currency: "COP",

            minimumFractionDigits: 0

        }).format(this.total);

    }


    /**
     * Devuelve el nombre completo del equipo.
     */
    obtenerNombreEquipo() {

        return [

            this.equipo.tipo,

            this.equipo.marca,

            this.equipo.modelo

        ]

        .filter(Boolean)

        .join(" ");

    }


    /**
     * Verifica si la orden tiene accesorios registrados.
     */
    tieneAccesorios() {

        return Array.isArray(this.recepcion.accesorios)

            && this.recepcion.accesorios.length > 0;

    }


    /**
     * Verifica si existen repuestos asociados.
     */
    tieneRepuestos() {

        return this.repuestos.length > 0;

    }


    /**
     * Representación de texto del objeto.
     */
    toString() {

        return `[${this.numeroOrden}] ${this.cliente.nombre} - ${this.obtenerNombreEquipo()}`;

    }

}

/*==================================================
=            EXPORTACIÓN GLOBAL
==================================================*/

window.ServicioTecnico = ServicioTecnico;
