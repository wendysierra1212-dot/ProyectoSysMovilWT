/**
 * ==================================================
 * CONTROLADOR DE COMPRAS
 * SYSMOVILWT
 * ==================================================
 */

class ComprasController {

    constructor() {

        this.storageKey = "sysmovilwt_compras";

        this.compras = this.cargarCompras();

    }

    /*=========================================
    =            CARGAR COMPRAS
    =========================================*/

    cargarCompras() {

        const datos = localStorage.getItem(this.storageKey);

        if (!datos) {

            return [];

        }

        return JSON.parse(datos).map(

            compra => new Compra(compra)

        );

    }

    /*=========================================
    =            GUARDAR COMPRAS
    =========================================*/

    guardarCompras() {

        localStorage.setItem(

            this.storageKey,

            JSON.stringify(this.compras)

        );

    }

    /*=========================================
    =            OBTENER TODAS
    =========================================*/

    obtenerCompras() {

        return this.compras;

    }

    /*=========================================
    =            OBTENER POR ID
    =========================================*/

    obtenerCompra(id) {

        return this.compras.find(

            compra => compra.id === id

        );

    }

    /*=========================================
    =            AGREGAR
    =========================================*/

    agregarCompra(datos) {

        const compra = new Compra(datos);

        compra.calcularTotal();

        this.compras.push(compra);

        this.guardarCompras();

        return compra;

    }

    /*=========================================
    =            ACTUALIZAR
    =========================================*/

    actualizarCompra(id, datos) {

        const compra = this.obtenerCompra(id);

        if (!compra) {

            return false;

        }

        compra.actualizar(datos);

        compra.calcularTotal();

        this.guardarCompras();

        return true;

    }

    /*=========================================
    =            ELIMINAR
    =========================================*/

    eliminarCompra(id) {

        this.compras = this.compras.filter(

            compra => compra.id !== id

        );

        this.guardarCompras();

    }

    /*=========================================
    =            BUSCAR
    =========================================*/

    buscar(texto) {

        texto = texto.toLowerCase();

        return this.compras.filter(compra =>

            compra.numeroFactura.toLowerCase().includes(texto)

            ||

            compra.proveedor.toLowerCase().includes(texto)

        );

    }

}
