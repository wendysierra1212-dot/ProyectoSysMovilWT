/**
 * ==================================================
 * SYSMOVILWT
 * CONTROLADOR DE VENTAS
 * ==================================================
 */

class VentasController{

    constructor(){

        this.inventario = new InventarioController();

        this.usuario = JSON.parse(

            localStorage.getItem("usuarioActivo")

        );

        this.venta = new Venta(

            this.usuario ?

            this.usuario.nombre :

            "Administrador"

        );

    }

    /*=========================================
    =            PRODUCTOS
    =========================================*/

    obtenerProductos(){

        return this.inventario.obtenerProductos();

    }

    obtenerProducto(id){

        return this.inventario.obtenerProducto(id);

    }

    buscarProductos(texto){

        return this.inventario.buscar(texto);

    }

    filtrarProductos(categoria, estado){

        return this.inventario.filtrar(

            categoria,

            estado

        );

    }

    /*=========================================
    =            CARRITO
    =========================================*/

    obtenerCarrito(){

        return this.venta.detalles;

    }

    obtenerVenta(){

        return this.venta;

    }

    obtenerCantidadProductos(){

        return this.venta.detalles.length;

    }

    carritoVacio(){

        return this.venta.detalles.length === 0;

    }

    /*=========================================
    =            AGREGAR PRODUCTO
    =========================================*/

    agregarProducto(productoId){

        const producto = this.obtenerProducto(

            productoId

        );

        if(!producto){

            return false;

        }

        if(producto.stock <= 0){

            alert(

                "Este producto no tiene existencias."

            );

            return false;

        }

        this.venta.agregarProducto(

            producto

        );

        return true;

    }

    /*=========================================
    =            ELIMINAR PRODUCTO
    =========================================*/

    eliminarProducto(productoId){

        this.venta.eliminarProducto(

            productoId

        );

    }

    /*=========================================
    =            AUMENTAR
    =========================================*/

    aumentarCantidad(productoId){

        this.venta.aumentarCantidad(

            productoId

        );

    }

    /*=========================================
    =            DISMINUIR
    =========================================*/

    disminuirCantidad(productoId){

        this.venta.disminuirCantidad(

            productoId

        );

    }

    /*=========================================
    =            VACIAR CARRITO
    =========================================*/

    cancelarVenta(){

        this.venta = new Venta(

            this.usuario ?

            this.usuario.nombre :

            "Administrador"

        );

    }

        /*=========================================
    =            SUBTOTAL
    =========================================*/

    obtenerSubtotal(){

        return this.venta.subtotal;

    }

    /*=========================================
    =            IVA
    =========================================*/

    obtenerIVA(){

        return this.venta.iva;

    }

    /*=========================================
    =            DESCUENTO
    =========================================*/

    obtenerDescuento(){

        return this.venta.descuento;

    }

    /*=========================================
    =            TOTAL
    =========================================*/

    obtenerTotal(){

        return this.venta.total;

    }

    /*=========================================
    =            MÉTODO DE PAGO
    =========================================*/

    obtenerMetodoPago(){

        return this.venta.metodoPago;

    }

    cambiarMetodoPago(metodo){

        this.venta.cambiarMetodoPago(

            metodo

        );

    }

    /*=========================================
    =            DESCUENTO
    =========================================*/

    aplicarDescuento(valor){

        this.venta.aplicarDescuento(

            valor

        );

    }

    /*=========================================
    =            FACTURA
    =========================================*/

    obtenerNumeroFactura(){

        return this.venta.numeroFactura;

    }

    /*=========================================
    =            FECHA
    =========================================*/

    obtenerFecha(){

        return this.venta.fecha;

    }

    /*=========================================
    =            USUARIO
    =========================================*/

    obtenerUsuario(){

        return this.venta.usuario;

    }

    /*=========================================
    =            VALIDAR CARRITO
    =========================================*/

    carritoTieneProductos(){

        return this.venta.detalles.length > 0;

    }

    /*=========================================
    =            VALIDAR STOCK
    =========================================*/

    validarStock(){

        for(const item of this.venta.detalles){

            const producto = this.obtenerProducto(

                item.productoId

            );

            if(!producto){

                return false;

            }

            if(producto.stock < item.cantidad){

                return false;

            }

        }

        return true;

    }

    /*=========================================
    =            OBTENER RESUMEN
    =========================================*/

    obtenerResumenVenta(){

        return{

            factura:this.obtenerNumeroFactura(),

            fecha:this.obtenerFecha(),

            usuario:this.obtenerUsuario(),

            metodoPago:this.obtenerMetodoPago(),

            subtotal:this.obtenerSubtotal(),

            iva:this.obtenerIVA(),

            descuento:this.obtenerDescuento(),

            total:this.obtenerTotal(),

            cantidadProductos:this.obtenerCantidadProductos()

        };

    }

        /*=========================================
    =            FINALIZAR VENTA
    =========================================*/

    finalizarVenta(){

        if(!this.carritoTieneProductos()){

            alert(

                "Debe agregar al menos un producto."

            );

            return false;

        }

        if(!this.validarStock()){

            alert(

                "No hay stock suficiente para completar la venta."

            );

            return false;

        }

        const ventas = JSON.parse(

            localStorage.getItem("ventas")

        ) || [];

        ventas.push(

            this.venta.toJSON()

        );

        localStorage.setItem(

            "ventas",

            JSON.stringify(ventas)

        );

        this.actualizarInventario();

        registrarActividad(

            "venta",

            "crear",

            `Venta ${this.venta.numeroFactura} registrada correctamente.`

        );

        this.cancelarVenta();

        return true;

    }

    /*=========================================
    =            ACTUALIZAR INVENTARIO
    =========================================*/

    actualizarInventario(){

        this.venta.detalles.forEach(item=>{

            const producto = this.obtenerProducto(

                item.productoId

            );

            if(!producto){

                return;

            }

            producto.stock -= item.cantidad;

            if(producto.stock < 0){

                producto.stock = 0;

            }

        });

        this.inventario.guardarProductos();

    }

    /*=========================================
    =            NUEVA VENTA
    =========================================*/

    nuevaVenta(){

        this.cancelarVenta();

    }

    /*=========================================
    =            ESTADÍSTICAS
    =========================================*/

    obtenerCantidadItems(){

        return this.venta.detalles.reduce(

            (total,item)=>

                total + item.cantidad,

            0

        );

    }

    obtenerValorVenta(){

        return this.venta.total;

    }

}