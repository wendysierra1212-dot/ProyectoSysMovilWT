/**
 * ==================================================
 * SYSMOVILWT
 * CONTROLADOR DE CLIENTES
 * ==================================================
 */

class ClientesController{

    constructor(){

        this.storageKey = "sysmovilwt_clientes";

        this.clientes = this.cargarClientes();

    }

    /*=========================================
    =            CARGAR
    =========================================*/

    cargarClientes(){

        const datos = localStorage.getItem(this.storageKey);

        if(!datos){

            return [];

        }

        return JSON.parse(datos).map(

            cliente => new Cliente(cliente)

        );

    }

    /*=========================================
    =            GUARDAR
    =========================================*/

    guardarClientes(){

        localStorage.setItem(

            this.storageKey,

            JSON.stringify(this.clientes)

        );

    }

    /*=========================================
    =            OBTENER TODOS
    =========================================*/

    obtenerClientes(){

        return this.clientes;

    }

    /*=========================================
    =            BUSCAR POR ID
    =========================================*/

    obtenerCliente(id){

        return this.clientes.find(

            cliente => cliente.id === id

        );

    }

    /*=========================================
    =            BUSCAR DOCUMENTO
    =========================================*/

    obtenerPorDocumento(documento){

        return this.clientes.find(

            cliente => cliente.documento === documento

        );

    }

    /*=========================================
    =            AGREGAR
    =========================================*/

    agregarCliente(datos){

        if(this.obtenerPorDocumento(datos.documento)){

            throw new Error(

                "Ya existe un cliente con ese documento."

            );

        }

        const cliente = new Cliente(datos);

        this.clientes.push(cliente);

        this.guardarClientes();

        return cliente;

    }

    /*=========================================
    =            ACTUALIZAR
    =========================================*/

    actualizarCliente(id, datos){

        const cliente = this.obtenerCliente(id);

        if(!cliente){

            return false;

        }

        cliente.actualizar(datos);

        this.guardarClientes();

        return true;

    }

    /*=========================================
    =            CAMBIAR ESTADO
    =========================================*/

    cambiarEstado(id){

        const cliente = this.obtenerCliente(id);

        if(!cliente){

            return false;

        }

        cliente.cambiarEstado();

        this.guardarClientes();

        return true;

    }

    /*=========================================
    =            BUSCAR
    =========================================*/

    buscar(texto){

        texto = texto.toLowerCase();

        return this.clientes.filter(cliente =>

            cliente.nombres.toLowerCase().includes(texto)

            ||

            cliente.apellidos.toLowerCase().includes(texto)

            ||

            cliente.documento.toLowerCase().includes(texto)

            ||

            cliente.telefono.toLowerCase().includes(texto)

            ||

            cliente.correo.toLowerCase().includes(texto)

        );

    }

    /*=========================================
    =            FILTRAR
    =========================================*/

    filtrar(estado,tipo){

        return this.clientes.filter(cliente=>{

            const estadoValido =

                !estado ||

                cliente.estado === estado;

            const tipoValido =

                !tipo ||

                cliente.tipoCliente === tipo;

            return estadoValido && tipoValido;

        });

    }

    /*=========================================
    =            REGISTRAR COMPRA
    =========================================*/

    registrarCompra(id,valor){

        const cliente = this.obtenerCliente(id);

        if(!cliente){

            return false;

        }

        cliente.registrarCompra(valor);

        this.guardarClientes();

        return true;

    }

    /*=========================================
    =            ESTADÍSTICAS
    =========================================*/

    obtenerClientesActivos(){

        return this.clientes.filter(

            cliente => cliente.estado === "Activo"

        );

    }

    obtenerClientesVIP(){

        return this.clientes.filter(

            cliente => cliente.esVIP()

        );

    }

    obtenerTotalClientes(){

        return this.clientes.length;

    }

}