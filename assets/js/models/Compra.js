/**
 * ==========================================
 * Modelo Compra
 * SYSMOVILWT
 * ==========================================
 */

class Compra {

    constructor(datos = {}) {

        this.id = datos.id || crypto.randomUUID();

        this.numeroFactura = datos.numeroFactura || "";

        this.fecha = datos.fecha || "";

        this.proveedor = datos.proveedor || "";

        this.productos = datos.productos || [];

        this.total = Number(datos.total) || 0;

        this.estado = datos.estado || "Recibida";

        this.observaciones = datos.observaciones || "";

        this.fechaCreacion =
            datos.fechaCreacion || new Date().toISOString();

        this.fechaActualizacion =
            datos.fechaActualizacion || new Date().toISOString();

    }

    actualizar(datos = {}) {

        Object.assign(this, datos);

        this.fechaActualizacion =
            new Date().toISOString();

    }

    calcularTotal() {

        this.total = this.productos.reduce(

            (acumulado, producto) => {

                return acumulado +

                    (producto.cantidad * producto.costo);

            },

            0

        );

        return this.total;

    }

}
