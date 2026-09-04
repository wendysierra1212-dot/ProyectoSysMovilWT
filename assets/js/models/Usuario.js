/* =========================================================
   MODELO USUARIO - SYSMOVILWT
   ========================================================= */

class Usuario {

    constructor(
        id_usuario = null,
        nombre_usuario = "",
        contrasena = "",
        estado = "Activo",
        ultimo_acceso = null,
        id_empleado = null,
        id_rol = null
    ) {

        this.id_usuario = id_usuario;

        this.nombre_usuario = nombre_usuario;

        this.contrasena = contrasena;

        this.estado = estado;

        this.ultimo_acceso = ultimo_acceso;

        this.id_empleado = id_empleado;

        this.id_rol = id_rol;

    }


    /* =====================================================
       VALIDAR USUARIO
       ===================================================== */

    validar() {

        const errores = [];


        if (!this.nombre_usuario ||
            this.nombre_usuario.trim() === "") {

            errores.push(
                "El nombre de usuario es obligatorio."
            );

        }


        if (this.nombre_usuario.length > 50) {

            errores.push(
                "El nombre de usuario no puede superar los 50 caracteres."
            );

        }


        if (!this.id_empleado) {

            errores.push(
                "Debe seleccionar un empleado."
            );

        }


        if (!this.id_rol) {

            errores.push(
                "Debe seleccionar un rol."
            );

        }


        if (
            this.estado !== "Activo" &&
            this.estado !== "Inactivo"
        ) {

            errores.push(
                "El estado del usuario no es válido."
            );

        }


        return errores;

    }


    /* =====================================================
       OBTENER NOMBRE DE ESTADO
       ===================================================== */

    getEstado() {

        return this.estado;

    }


    /* =====================================================
       VERIFICAR SI ESTÁ ACTIVO
       ===================================================== */

    estaActivo() {

        return this.estado === "Activo";

    }


    /* =====================================================
       ACTIVAR USUARIO
       ===================================================== */

    activar() {

        this.estado = "Activo";

    }


    /* =====================================================
       DESACTIVAR USUARIO
       ===================================================== */

    desactivar() {

        this.estado = "Inactivo";

    }


    /* =====================================================
       ACTUALIZAR ÚLTIMO ACCESO
       ===================================================== */

    registrarAcceso() {

        this.ultimo_acceso = new Date();

    }


    /* =====================================================
       OBTENER DATOS PARA GUARDAR
       ===================================================== */

    toJSON() {

        return {

            id_usuario: this.id_usuario,

            nombre_usuario: this.nombre_usuario,

            contrasena: this.contrasena,

            estado: this.estado,

            ultimo_acceso: this.ultimo_acceso,

            id_empleado: this.id_empleado,

            id_rol: this.id_rol

        };

    }


    /* =====================================================
       CREAR USUARIO DESDE OBJETO
       ===================================================== */

    static fromJSON(data) {

        if (!data) {

            return null;

        }


        return new Usuario(

            data.id_usuario ?? null,

            data.nombre_usuario ?? "",

            data.contrasena ?? "",

            data.estado ?? "Activo",

            data.ultimo_acceso ?? null,

            data.id_empleado ?? null,

            data.id_rol ?? null

        );

    }

}


/* =========================================================
   DISPONIBILIDAD GLOBAL
   ========================================================= */

window.Usuario = Usuario;
