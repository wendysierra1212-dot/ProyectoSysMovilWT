/**
 * ==========================================
 * Modelo Producto
 * SYSMOVILWT
 * ==========================================
 */

class Producto {

    constructor(datos = {}) {

        this.id = datos.id || crypto.randomUUID();

        this.codigo = datos.codigo || "";

        this.nombre = datos.nombre || "";

        // NUEVO
        this.tipo = datos.tipo || "";

        this.marca = datos.marca || "";

        this.categoria = datos.categoria || "";

        this.proveedor = datos.proveedor || "";

        this.costo = Number(datos.costo) || 0;

        this.precio = Number(datos.precio) || 0;

        this.stock = Number(datos.stock) || 0;

        this.stockMinimo = Number(datos.stockMinimo) || 0;

        // NUEVO
        this.garantia = Number(datos.garantia) || 0;

        this.ubicacion = datos.ubicacion || "";

        this.serial = datos.serial || "";

        this.descripcion = datos.descripcion || "";

        this.estado = datos.estado || "Disponible";

        this.fechaCreacion = datos.fechaCreacion || new Date().toISOString();

        this.fechaActualizacion = datos.fechaActualizacion || new Date().toISOString();

    }

    actualizar(datos = {}) {

        Object.assign(this, datos);

        this.fechaActualizacion = new Date().toISOString();

    }

    esStockBajo() {

        return this.stock <= this.stockMinimo;

    }

    obtenerEstadoStock() {

        if (this.stock <= 0) {

            return "Agotado";

        }

        if (this.esStockBajo()) {

            return "Stock Bajo";

        }

        return "Disponible";

    }

}
