/**
 * ==================================================
 * SYSMOVILWT
 * MODELO DETALLE DE VENTA
 * ==================================================
 */

class DetalleVenta{

    constructor(producto, cantidad = 1){

        this.id = crypto.randomUUID();

        this.productoId = producto.id;

        this.codigo = producto.codigo;

        this.nombre = producto.nombre;

        this.marca = producto.marca;

        this.categoria = producto.categoria;

        this.imagen = producto.imagen || "../assets/img/products/no-image.png";

        this.precio = Number(producto.precio);

        this.cantidad = Number(cantidad);

        this.subtotal = this.calcularSubtotal();

    }

    /*=========================================
    =            SUBTOTAL
    =========================================*/

    calcularSubtotal(){

        this.subtotal = this.precio * this.cantidad;

        return this.subtotal;

    }

    /*=========================================
    =            AUMENTAR CANTIDAD
    =========================================*/

    incrementarCantidad(){

        this.cantidad++;

        this.calcularSubtotal();

    }

    /*=========================================
    =            DISMINUIR CANTIDAD
    =========================================*/

    disminuirCantidad(){

        if(this.cantidad > 1){

            this.cantidad--;

            this.calcularSubtotal();

        }

    }

    /*=========================================
    =            ACTUALIZAR CANTIDAD
    =========================================*/

    actualizarCantidad(cantidad){

        cantidad = Number(cantidad);

        if(cantidad < 1){

            cantidad = 1;

        }

        this.cantidad = cantidad;

        this.calcularSubtotal();

    }

    /*=========================================
    =            CAMBIAR PRECIO
    =========================================*/

    actualizarPrecio(precio){

        this.precio = Number(precio);

        this.calcularSubtotal();

    }

    /*=========================================
    =            JSON
    =========================================*/

    toJSON(){

        return{

            id:this.id,

            productoId:this.productoId,

            codigo:this.codigo,

            nombre:this.nombre,

            marca:this.marca,

            categoria:this.categoria,

            imagen:this.imagen,

            precio:this.precio,

            cantidad:this.cantidad,

            subtotal:this.subtotal

        };

    }

}