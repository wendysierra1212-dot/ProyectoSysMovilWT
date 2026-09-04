/**
 * ==================================================
 * SYSMOVILWT
 * CONTROLADOR DE PROVEEDORES
 * VERSIÓN 2.0
 * ==================================================
 */

class ProveedoresController{

    constructor(){

        this.storageKey = "sysmovilwt_proveedores";

        this.proveedores = this.cargarProveedores();

    }

    /*=========================================
    =            CARGAR
    =========================================*/

    cargarProveedores(){

        const datos = localStorage.getItem(

            this.storageKey

        );

        if(!datos){

            return [];

        }

        return JSON.parse(datos).map(

            proveedor => new Proveedor(proveedor)

        );

    }

    /*=========================================
    =            GUARDAR
    =========================================*/

    guardarProveedores(){

        localStorage.setItem(

            this.storageKey,

            JSON.stringify(this.proveedores)

        );

    }

    /*=========================================
    =            OBTENER TODOS
    =========================================*/

    obtenerProveedores(){

        return [...this.proveedores];

    }

    /*=========================================
    =            OBTENER POR ID
    =========================================*/

    obtenerProveedor(id){

        return this.proveedores.find(

            proveedor => proveedor.id === id

        ) || null;

    }

    /*=========================================
    =            AGREGAR
    =========================================*/

    agregarProveedor(datos){

        const proveedor = new Proveedor(datos);

        this.proveedores.push(proveedor);

        this.guardarProveedores();

        return proveedor;

    }

    /*=========================================
    =            ACTUALIZAR
    =========================================*/

    actualizarProveedor(id, datos){

        const proveedor =

            this.obtenerProveedor(id);

        if(!proveedor){

            return false;

        }

        proveedor.actualizar(datos);

        this.guardarProveedores();

        return true;

    }

    /*=========================================
    =            CAMBIAR ESTADO
    =========================================*/

    cambiarEstado(id){

        const proveedor =

            this.obtenerProveedor(id);

        if(!proveedor){

            return false;

        }

        proveedor.cambiarEstado();

        this.guardarProveedores();

        return true;

    }

    /*=========================================
    =            ELIMINAR
    =========================================*/

    eliminarProveedor(id){

        this.proveedores =

            this.proveedores.filter(

                proveedor => proveedor.id !== id

            );

        this.guardarProveedores();

    }

    /*=========================================
    =            BUSCAR
    =========================================*/

    buscar(texto){

        texto = texto

            .trim()

            .toLowerCase();

        if(texto === ""){

            return [...this.proveedores];

        }

        return this.proveedores.filter(proveedor =>

            proveedor.empresa

                .toLowerCase()

                .includes(texto)

            ||

            proveedor.nit

                .toLowerCase()

                .includes(texto)

            ||

            proveedor.contacto

                .toLowerCase()

                .includes(texto)

            ||

            proveedor.celular

                .toLowerCase()

                .includes(texto)

            ||

            proveedor.correo

                .toLowerCase()

                .includes(texto)

            ||

            proveedor.ciudad

                .toLowerCase()

                .includes(texto)

            ||

            proveedor.departamento

                .toLowerCase()

                .includes(texto)

        );

    }

    /*=========================================
    =            FILTRAR
    =========================================*/

    filtrar(estado, tipoProveedor){

        return this.proveedores.filter(proveedor=>{

            const estadoValido =

                !estado ||

                proveedor.estado === estado;

            const tipoValido =

                !tipoProveedor ||

                proveedor.tipoProveedor === tipoProveedor;

            return (

                estadoValido &&

                tipoValido

            );

        });

    }

    /*=========================================
    =            ESTADÍSTICAS
    =========================================*/

    obtenerTotalProveedores(){

        return this.proveedores.length;

    }

    obtenerActivos(){

        return this.proveedores.filter(

            proveedor =>

                proveedor.estado === "Activo"

        ).length;

    }

    obtenerInactivos(){

        return this.proveedores.filter(

            proveedor =>

                proveedor.estado === "Inactivo"

        ).length;

    }

    obtenerCompras(){

        return this.proveedores.reduce(

            (total, proveedor)=>

                total + proveedor.totalCompras,

            0

        );

    }

    obtenerValorComprado(){

        return this.proveedores.reduce(

            (total, proveedor)=>

                total + proveedor.valorComprado,

            0

        );

    }

    obtenerCiudades(){

        return [

            ...new Set(

                this.proveedores.map(

                    proveedor =>

                        proveedor.ciudad

                )

            )

        ];

    }

    obtenerTipos(){

        return [

            ...new Set(

                this.proveedores.map(

                    proveedor =>

                        proveedor.tipoProveedor

                )

            )

        ];

    }

    /*=========================================
    =            EXISTE NIT
    =========================================*/

    existeNit(nit, excluirId = null){

        return this.proveedores.some(proveedor =>

            proveedor.nit === nit &&

            proveedor.id !== excluirId

        );

    }

    /*=========================================
    =            TOTAL GENERAL
    =========================================*/

    obtenerResumen(){

        return{

            total:

                this.obtenerTotalProveedores(),

            activos:

                this.obtenerActivos(),

            inactivos:

                this.obtenerInactivos(),

            compras:

                this.obtenerCompras(),

            valor:

                this.obtenerValorComprado()

        };

    }

}