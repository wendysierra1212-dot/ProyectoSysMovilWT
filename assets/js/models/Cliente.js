/**
 * ==================================================
 * SYSMOVILWT
 * MODELO CLIENTE
 * ==================================================
 */

class Cliente{

    constructor(datos = {}){

        this.id = datos.id || crypto.randomUUID();

        /*=========================================
        =            INFORMACIÓN PERSONAL
        =========================================*/

        this.tipoDocumento = datos.tipoDocumento || "CC";

        this.documento = datos.documento || "";

        this.nombres = datos.nombres || "";

        this.apellidos = datos.apellidos || "";

        this.fechaNacimiento = datos.fechaNacimiento || "";

        this.genero = datos.genero || "";

        /*=========================================
        =            CONTACTO
        =========================================*/

        this.telefono = datos.telefono || "";

        this.correo = datos.correo || "";

        this.direccion = datos.direccion || "";

        this.ciudad = datos.ciudad || "";

        /*=========================================
        =            INFORMACIÓN COMERCIAL
        =========================================*/

        this.tipoCliente = datos.tipoCliente || "Normal";

        this.estado = datos.estado || "Activo";

        this.observaciones = datos.observaciones || "";

        this.aceptaPromociones =

            datos.aceptaPromociones ?? true;

        /*=========================================
        =            ESTADÍSTICAS
        =========================================*/

        this.totalCompras =

            Number(datos.totalCompras) || 0;

        this.cantidadCompras =

            Number(datos.cantidadCompras) || 0;

        this.totalGastado =

            Number(datos.totalGastado) || 0;

        this.ultimaCompra =

            datos.ultimaCompra || null;

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
    =            NOMBRE COMPLETO
    =========================================*/

    obtenerNombreCompleto(){

        return `${this.nombres} ${this.apellidos}`.trim();

    }

    /*=========================================
    =            CLIENTE VIP
    =========================================*/

    esVIP(){

        return (

            this.totalGastado >= 20000000 ||

            this.cantidadCompras >= 10 ||

            this.tipoCliente === "VIP"

        );

    }

    /*=========================================
    =            REGISTRAR COMPRA
    =========================================*/

    registrarCompra(valorCompra){

        this.cantidadCompras++;

        this.totalGastado += Number(valorCompra);

        this.totalCompras = this.cantidadCompras;

        this.ultimaCompra = new Date().toISOString();

        if(this.esVIP()){

            this.tipoCliente = "VIP";

        }

        this.fechaActualizacion = new Date().toISOString();

    }

    /*=========================================
    =            ACTUALIZAR
    =========================================*/

    actualizar(datos = {}){

        Object.assign(this, datos);

        this.fechaActualizacion = new Date().toISOString();

    }

    /*=========================================
    =            ACTIVO
    =========================================*/

    estaActivo(){

        return this.estado === "Activo";

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
}