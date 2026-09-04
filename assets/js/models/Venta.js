/**
 * ==================================================
 * SYSMOVILWT
 * MODELO VENTA
 * ==================================================
 */

class Venta{

    constructor(usuario){

        this.id = crypto.randomUUID();

        this.numeroFactura = this.generarNumeroFactura();

        this.fecha = new Date();

        this.usuario = usuario;

        this.metodoPago = "Efectivo";

        this.detalles = [];

        this.subtotal = 0;

        this.iva = 0;

        this.descuento = 0;

        this.total = 0;

    }

    /*=========================================
    =            FACTURA
    =========================================*/

    generarNumeroFactura(){

        const consecutivo =

            Number(localStorage.getItem("facturaActual")) || 1;

        localStorage.setItem(

            "facturaActual",

            consecutivo + 1

        );

        return "V-" +

            String(consecutivo)

            .padStart(6,"0");

    }

    /*=========================================
    =            AGREGAR PRODUCTO
    =========================================*/

    agregarProducto(producto){

        const existente = this.detalles.find(

            item => item.productoId === producto.id

        );

        if(existente){

            existente.incrementarCantidad();

        }

        else{

            this.detalles.push(

                new DetalleVenta(producto)

            );

        }

        this.calcularTotales();

    }

    /*=========================================
    =            ELIMINAR
    =========================================*/

    eliminarProducto(productoId){

        this.detalles = this.detalles.filter(

            item => item.productoId !== productoId

        );

        this.calcularTotales();

    }

    /*=========================================
    =            AUMENTAR
    =========================================*/

    aumentarCantidad(productoId){

        const item = this.detalles.find(

            p => p.productoId === productoId

        );

        if(item){

            item.incrementarCantidad();

            this.calcularTotales();

        }

    }

    /*=========================================
    =            DISMINUIR
    =========================================*/

    disminuirCantidad(productoId){

        const item = this.detalles.find(

            p => p.productoId === productoId

        );

        if(!item){

            return;

        }

        if(item.cantidad === 1){

            this.eliminarProducto(productoId);

            return;

        }

        item.disminuirCantidad();

        this.calcularTotales();

    }

    /*=========================================
    =            DESCUENTO
    =========================================*/

    aplicarDescuento(valor){

        this.descuento = Number(valor);

        this.calcularTotales();

    }

    /*=========================================
    =            MÉTODO DE PAGO
    =========================================*/

    cambiarMetodoPago(metodo){

        this.metodoPago = metodo;

    }

    /*=========================================
    =            TOTALES
    =========================================*/

    calcularTotales(){

        this.subtotal = 0;

        this.detalles.forEach(item=>{

            this.subtotal += item.subtotal;

        });

        this.iva = this.subtotal * 0.19;

        this.total =

            this.subtotal +

            this.iva -

            this.descuento;

    }

    /*=========================================
    =            JSON
    =========================================*/

    toJSON(){

        return{

            id:this.id,

            numeroFactura:this.numeroFactura,

            fecha:this.fecha,

            usuario:this.usuario,

            metodoPago:this.metodoPago,

            detalles:this.detalles.map(

                item=>item.toJSON()

            ),

            subtotal:this.subtotal,

            iva:this.iva,

            descuento:this.descuento,

            total:this.total

        };

    }

}