/**
 * ==================================================
 * SYSMOVILWT
 * MODELO PROVEEDOR
 * ==================================================
 */

class Proveedor{

    constructor(datos = {}){

        this.id = datos.id || crypto.randomUUID();

        /*=========================================
        =            INFORMACIÓN GENERAL
        =========================================*/

        this.empresa = datos.empresa || "";

        this.nit = datos.nit || "";

        this.representante = datos.representante || "";

        /*=========================================
        =            CONTACTO
        =========================================*/

        this.contacto = datos.contacto || "";

        this.cargo = datos.cargo || "";

        this.celular = datos.celular || "";

        this.correo = datos.correo || "";

        /*=========================================
        =            UBICACIÓN
        =========================================*/

        this.pais = datos.pais || "Colombia";

        this.departamento = datos.departamento || "";

        this.ciudad = datos.ciudad || "";

        this.direccion = datos.direccion || "";

        /*=========================================
        =            INFORMACIÓN COMERCIAL
        =========================================*/

        this.productos = datos.productos || "";

        this.formaPago = datos.formaPago || "Transferencia";

        this.diasCredito = Number(datos.diasCredito) || 0;

        this.tipoProveedor = datos.tipoProveedor || "Distribuidor";

        this.estado = datos.estado || "Activo";

        /*=========================================
        =            INFORMACIÓN BANCARIA
        =========================================*/

        this.banco = datos.banco || "";

        this.tipoCuenta = datos.tipoCuenta || "Ahorros";

        this.numeroCuenta = datos.numeroCuenta || "";

        /*=========================================
        =            OBSERVACIONES
        =========================================*/

        this.observaciones = datos.observaciones || "";

        /*=========================================
        =            ESTADÍSTICAS
        =========================================*/

        this.totalCompras = Number(datos.totalCompras) || 0;

        this.valorComprado = Number(datos.valorComprado) || 0;

        this.ultimaCompra = datos.ultimaCompra || null;

        /*=========================================
        =            FECHAS
        =========================================*/

        this.fechaRegistro =

            datos.fechaRegistro ||

            new Date().toISOString();

        this.fechaActualizacion =

            datos.fechaActualizacion ||

            new Date().toISOString();

    }

    /*=========================================
    =            ACTUALIZAR
    =========================================*/

    actualizar(datos = {}){

        Object.assign(this, datos);

        this.fechaActualizacion =

            new Date().toISOString();

    }

    /*=========================================
    =            CAMBIAR ESTADO
    =========================================*/

    cambiarEstado(){

        this.estado =

            this.estado === "Activo"

            ? "Inactivo"

            : "Activo";

        this.fechaActualizacion =

            new Date().toISOString();

    }

    /*=========================================
    =            ESTÁ ACTIVO
    =========================================*/

    estaActivo(){

        return this.estado === "Activo";

    }

    /*=========================================
    =            REGISTRAR COMPRA
    =========================================*/

    registrarCompra(valor){

        this.totalCompras++;

        this.valorComprado += Number(valor);

        this.ultimaCompra =

            new Date().toISOString();

        this.fechaActualizacion =

            new Date().toISOString();

    }

    /*=========================================
    =            NOMBRE PARA LISTADOS
    =========================================*/

    obtenerNombre(){

        return this.empresa;

    }

}