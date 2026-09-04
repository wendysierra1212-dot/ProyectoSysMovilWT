/**
 * ==================================================
 * CONTROLADOR DE INVENTARIO
 * SYSMOVILWT
 * ==================================================
 */

class InventarioController {

    constructor() {

        this.storageKey = "sysmovilwt_productos";

        this.productos = this.cargarProductos();

    }

    /**
     * ============================
     * CARGAR PRODUCTOS
     * ============================
     */

    cargarProductos() {

        const datos = localStorage.getItem(this.storageKey);

        if (!datos) {

            return [];

        }

        return JSON.parse(datos).map(producto => new Producto(producto));

    }

    /**
     * ============================
     * GUARDAR PRODUCTOS
     * ============================
     */

    guardarProductos() {

        localStorage.setItem(

            this.storageKey,

            JSON.stringify(this.productos)

        );

    }

    /**
     * ============================
     * OBTENER TODOS
     * ============================
     */

    obtenerProductos() {

        return this.productos;

    }

    /**
     * ============================
     * AGREGAR PRODUCTO
     * ============================
     */

    agregarProducto(datos) {

        const producto = new Producto(datos);

        this.productos.push(producto);

        this.guardarProductos();

        return producto;

    }

    /**
     * ============================
     * BUSCAR POR ID
     * ============================
     */

    obtenerProducto(id) {

        return this.productos.find(

            producto => producto.id === id

        );

    }

    /**
     * ============================
     * EDITAR
     * ============================
     */

    actualizarProducto(id, datos) {

        const producto = this.obtenerProducto(id);

        if (!producto) {

            return false;

        }

        producto.actualizar(datos);

        this.guardarProductos();

        return true;

    }

    /**
     * ============================
     * ELIMINAR
     * ============================
     */

    eliminarProducto(id) {

        this.productos = this.productos.filter(

            producto => producto.id !== id

        );

        this.guardarProductos();

    }

    /**
     * ============================
     * BUSCAR
     * ============================
     */

    buscar(texto) {

        texto = texto.toLowerCase();

        return this.productos.filter(producto =>

            producto.codigo.toLowerCase().includes(texto)

            ||

            producto.nombre.toLowerCase().includes(texto)

            ||

            producto.marca.toLowerCase().includes(texto)

        );

    }

    /**
     * ============================
     * FILTRAR
     * ============================
     */

    filtrar(categoria, estado) {

        return this.productos.filter(producto => {

            const categoriaValida =

                !categoria ||

                producto.categoria === categoria;

            const estadoValido =

                !estado ||

                producto.obtenerEstadoStock() === estado;

            return categoriaValida && estadoValido;

        });

    }

}