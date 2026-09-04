/**
 * ==================================================
 * DASHBOARD CONTROLLER
 * SYSMOVILWT
 * VERSIÓN 1.1
 * ==================================================
 */

class DashboardController{

    constructor(){

        this.inventario = new InventarioController();

    }

    /*=========================================
    =            PRODUCTOS
    =========================================*/

    obtenerTotalProductos(){

        return this.inventario.obtenerProductos().length;

    }

    /*=========================================
    =            VALOR DEL INVENTARIO
    =========================================*/

    obtenerValorInventario(){

        const productos = this.inventario.obtenerProductos();

        let total = 0;

        productos.forEach(producto=>{

            total += producto.precio * producto.stock;

        });

        return total;

    }

    /*=========================================
    =            VENTAS
    =========================================*/

    obtenerTotalVentas(){

        const ventas = JSON.parse(

            localStorage.getItem("ventas")

        ) || [];

        return ventas.length;

    }

    /*=========================================
    =            CLIENTES
    =========================================*/

    obtenerTotalClientes(){

        const clientes = JSON.parse(

            localStorage.getItem("sysmovilwt_clientes")

        ) || [];

        return clientes.filter(

            cliente => cliente.estado === "Activo"

        ).length;

    }

    /*=========================================
    =            ACTIVIDAD RECIENTE
    =========================================*/

    obtenerActividadReciente(){

        const actividades = JSON.parse(

            localStorage.getItem("sysmovilwt_actividad")

        ) || [];

        return actividades.slice(0,10);

    }

    /*=========================================
    =            ALERTAS
    =========================================*/

    obtenerAlertasStock(){

        const productos = this.inventario.obtenerProductos();

        const alertas = [];

        productos.forEach(producto=>{

            if(producto.stock === 0){

                alertas.push({

                    tipo:"agotado",

                    producto:producto.nombre,

                    mensaje:`${producto.nombre} está agotado.`

                });

            }

            else if(producto.stock <= producto.stockMinimo){

                alertas.push({

                    tipo:"stock",

                    producto:producto.nombre,

                    mensaje:`${producto.nombre} tiene stock bajo (${producto.stock}).`

                });

            }

        });

        return alertas;

    }

}