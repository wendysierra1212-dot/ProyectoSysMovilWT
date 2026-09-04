/* =========================================================
   CONTROLADOR DE USUARIOS - SYSMOVILWT
   ========================================================= */

/*
    Este controlador administra:

    - Empleados
    - Usuarios
    - Roles
    - Registro de usuarios
    - Actualización de usuarios
    - Activación / desactivación
    - Eliminación
    - Búsqueda y filtros
    - Estadísticas

    Por ahora trabajamos con datos simulados para construir
    y probar completamente la interfaz.

    Posteriormente estos datos se conectarán con MySQL.

    RELACIÓN:

    EMPLEADO
        ↓ id_empleado
    USUARIO
        ↓ id_rol
    ROL
*/


class UsuariosController {

    constructor() {

        /* =====================================================
           DATOS DE USUARIOS
           ===================================================== */

        this.usuarios = [];


        /* =====================================================
           DATOS DE EMPLEADOS
           ===================================================== */

        this.empleados = [];


        /* =====================================================
           DATOS DE ROLES
           ===================================================== */

        this.roles = [

            {
                id_rol: 1,

                nombre_rol: "Administrador",

                descripcion:
                    "Acceso completo a todos los módulos y funciones del sistema."
            },

            {
                id_rol: 2,

                nombre_rol: "Vendedor",

                descripcion:
                    "Acceso a ventas, clientes e inventario según permisos asignados."
            },

            {
                id_rol: 3,

                nombre_rol: "Técnico",

                descripcion:
                    "Acceso a la gestión y seguimiento del servicio técnico."
            }

        ];


        /* =====================================================
           CONTADORES
           ===================================================== */

        this.siguienteIdUsuario = 1;

        this.siguienteIdEmpleado = 1;

    }


    /* =========================================================
       OBTENER TODOS LOS USUARIOS
       ========================================================= */

    obtenerUsuarios() {

        return [...this.usuarios];

    }


    /* =========================================================
       OBTENER EMPLEADOS
       ========================================================= */

    obtenerEmpleados() {

        return [...this.empleados];

    }


    /* =========================================================
       OBTENER EMPLEADOS ACTIVOS
       ========================================================= */

    obtenerEmpleadosActivos() {

        return this.empleados.filter(

            empleado =>
                empleado.estado === "Activo"

        );

    }


    /* =========================================================
       OBTENER TODOS LOS ROLES
       ========================================================= */

    obtenerRoles() {

        return [...this.roles];

    }


    /* =========================================================
       BUSCAR USUARIO POR ID
       ========================================================= */

    obtenerUsuarioPorId(id) {

        return this.usuarios.find(

            usuario =>
                usuario.id_usuario === Number(id)

        );

    }


    /* =========================================================
       BUSCAR EMPLEADO POR ID
       ========================================================= */

    obtenerEmpleadoPorId(id) {

        return this.empleados.find(

            empleado =>
                empleado.id_empleado === Number(id)

        );

    }


    /* =========================================================
       BUSCAR ROL POR ID
       ========================================================= */

    obtenerRolPorId(id) {

        return this.roles.find(

            rol =>
                rol.id_rol === Number(id)

        );

    }


    /* =========================================================
       CREAR EMPLEADO
       ========================================================= */

    crearEmpleado(datos) {

        /* =====================================================
           VALIDAR DATOS OBLIGATORIOS
           ===================================================== */

        if (
            !datos.nombres ||
            !datos.nombres.trim()
        ) {

            return {

                correcto: false,

                errores: [
                    "Los nombres son obligatorios."
                ]

            };

        }


        if (
            !datos.apellidos ||
            !datos.apellidos.trim()
        ) {

            return {

                correcto: false,

                errores: [
                    "Los apellidos son obligatorios."
                ]

            };

        }


        if (
            !datos.cedula ||
            !datos.cedula.trim()
        ) {

            return {

                correcto: false,

                errores: [
                    "La cédula es obligatoria."
                ]

            };

        }


        if (
            !datos.cargo ||
            !datos.cargo.trim()
        ) {

            return {

                correcto: false,

                errores: [
                    "El cargo es obligatorio."
                ]

            };

        }


        /* =====================================================
           VALIDAR CÉDULA DUPLICADA
           ===================================================== */

        const existeCedula =
            this.empleados.some(

                empleado =>

                    empleado.cedula.toLowerCase() ===
                    datos.cedula.trim().toLowerCase()

            );


        if (existeCedula) {

            return {

                correcto: false,

                errores: [
                    "La cédula ya está registrada."
                ]

            };

        }


        /* =====================================================
           CREAR EMPLEADO
           ===================================================== */

        const empleado = {

            id_empleado:
                this.siguienteIdEmpleado,

            cedula:
                datos.cedula.trim(),

            nombres:
                datos.nombres.trim(),

            apellidos:
                datos.apellidos.trim(),

            cargo:
                datos.cargo.trim(),

            telefono:
                datos.telefono
                    ? datos.telefono.trim()
                    : "",

            correo:
                datos.correo
                    ? datos.correo.trim()
                    : "",

            direccion:
                datos.direccion
                    ? datos.direccion.trim()
                    : "",

            fecha_ingreso:
                datos.fecha_ingreso || "",

            estado:
                datos.estado || "Activo"

        };


        /* =====================================================
           AGREGAR EMPLEADO
           ===================================================== */

        this.empleados.push(empleado);

        this.siguienteIdEmpleado++;


        return {

            correcto: true,

            empleado: empleado

        };

    }


    /* =========================================================
       CREAR USUARIO
       ========================================================= */

    crearUsuario(datos) {

        /* =====================================================
           VALIDAR DATOS DE ACCESO
           ===================================================== */

        if (
            !datos.nombre_usuario ||
            !datos.nombre_usuario.trim()
        ) {

            return {

                correcto: false,

                errores: [
                    "El nombre de usuario es obligatorio."
                ]

            };

        }


        if (
            !datos.contrasena ||
            !datos.contrasena.trim()
        ) {

            return {

                correcto: false,

                errores: [
                    "La contraseña es obligatoria."
                ]

            };

        }


        if (
            datos.contrasena.length < 6
        ) {

            return {

                correcto: false,

                errores: [
                    "La contraseña debe tener mínimo 6 caracteres."
                ]

            };

        }


        if (
            datos.contrasena !==
            datos.confirmar_contrasena
        ) {

            return {

                correcto: false,

                errores: [
                    "Las contraseñas no coinciden."
                ]

            };

        }


        if (!datos.id_rol) {

            return {

                correcto: false,

                errores: [
                    "Debe seleccionar un rol."
                ]

            };

        }


        /* =====================================================
           VALIDAR USUARIO DUPLICADO
           ===================================================== */

        const existeUsuario =
            this.usuarios.some(

                existente =>

                    existente.nombre_usuario
                        .toLowerCase() ===
                    datos.nombre_usuario
                        .trim()
                        .toLowerCase()

            );


        if (existeUsuario) {

            return {

                correcto: false,

                errores: [
                    "El nombre de usuario ya está registrado."
                ]

            };

        }


        /* =====================================================
           CREAR EMPLEADO
           ===================================================== */

        const resultadoEmpleado =
            this.crearEmpleado({

                nombres:
                    datos.nombres,

                apellidos:
                    datos.apellidos,

                cedula:
                    datos.cedula,

                telefono:
                    datos.telefono,

                correo:
                    datos.correo,

                direccion:
                    datos.direccion,

                cargo:
                    datos.cargo,

                fecha_ingreso:
                    datos.fecha_ingreso,

                estado:
                    datos.estado || "Activo"

            });


        if (!resultadoEmpleado.correcto) {

            return resultadoEmpleado;

        }


        const empleado =
            resultadoEmpleado.empleado;


        /* =====================================================
           CREAR OBJETO USUARIO
           ===================================================== */

        const usuario = new Usuario(

            this.siguienteIdUsuario,

            datos.nombre_usuario.trim(),

            datos.contrasena,

            datos.estado || "Activo",

            null,

            empleado.id_empleado,

            Number(datos.id_rol)

        );


        /* =====================================================
           VALIDAR MODELO USUARIO
           ===================================================== */

        const errores =
            usuario.validar();


        if (errores.length > 0) {

            /* ================================================
               SI EL USUARIO NO ES VÁLIDO, RETIRAMOS EL
               EMPLEADO QUE ACABAMOS DE CREAR.
               ================================================ */

            this.empleados =
                this.empleados.filter(

                    empleadoActual =>
                        empleadoActual.id_empleado !==
                        empleado.id_empleado

                );


            return {

                correcto: false,

                errores: errores

            };

        }


        /* =====================================================
           GUARDAR USUARIO
           ===================================================== */

        this.usuarios.push(usuario);

        this.siguienteIdUsuario++;


        return {

            correcto: true,

            usuario: usuario,

            empleado: empleado

        };

    }


    /* =========================================================
       ACTUALIZAR USUARIO
       ========================================================= */

    actualizarUsuario(id, datos) {

        const usuario =
            this.obtenerUsuarioPorId(id);


        if (!usuario) {

            return {

                correcto: false,

                errores: [
                    "No se encontró el usuario."
                ]

            };

        }


        /* =====================================================
           VALIDAR NOMBRE DUPLICADO
           ===================================================== */

        const existeOtro =
            this.usuarios.some(

                existente =>

                    existente.id_usuario !== Number(id) &&

                    existente.nombre_usuario
                        .toLowerCase() ===
                    datos.nombre_usuario
                        .trim()
                        .toLowerCase()

            );


        if (existeOtro) {

            return {

                correcto: false,

                errores: [
                    "El nombre de usuario ya está registrado."
                ]

            };

        }


        /* =====================================================
           OBTENER EMPLEADO RELACIONADO
           ===================================================== */

        const empleado =
            this.obtenerEmpleadoPorId(
                usuario.id_empleado
            );


        if (empleado) {

            empleado.nombres =
                datos.nombres.trim();

            empleado.apellidos =
                datos.apellidos.trim();

            empleado.cedula =
                datos.cedula.trim();

            empleado.telefono =
                datos.telefono
                    ? datos.telefono.trim()
                    : "";

            empleado.correo =
                datos.correo
                    ? datos.correo.trim()
                    : "";

            empleado.direccion =
                datos.direccion
                    ? datos.direccion.trim()
                    : "";

            empleado.cargo =
                datos.cargo.trim();

            empleado.fecha_ingreso =
                datos.fecha_ingreso || "";

        }


        /* =====================================================
           ACTUALIZAR USUARIO
           ===================================================== */

        usuario.nombre_usuario =
            datos.nombre_usuario.trim();


        usuario.estado =
            datos.estado || usuario.estado;


        usuario.id_rol =
            Number(datos.id_rol);


        /* =====================================================
           ACTUALIZAR CONTRASEÑA
           ===================================================== */

        if (

            datos.contrasena &&

            datos.contrasena.trim() !== ""

        ) {

            usuario.contrasena =
                datos.contrasena;

        }


        return {

            correcto: true,

            usuario: usuario,

            empleado: empleado

        };

    }


    /* =========================================================
       ACTIVAR USUARIO
       ========================================================= */

    activarUsuario(id) {

        const usuario =
            this.obtenerUsuarioPorId(id);


        if (!usuario) {

            return false;

        }


        usuario.activar();

        return true;

    }


    /* =========================================================
       DESACTIVAR USUARIO
       ========================================================= */

    desactivarUsuario(id) {

        const usuario =
            this.obtenerUsuarioPorId(id);


        if (!usuario) {

            return false;

        }


        usuario.desactivar();

        return true;

    }


    /* =========================================================
       CAMBIAR ESTADO
       ========================================================= */

    cambiarEstado(id) {

        const usuario =
            this.obtenerUsuarioPorId(id);


        if (!usuario) {

            return false;

        }


        if (usuario.estaActivo()) {

            usuario.desactivar();

        } else {

            usuario.activar();

        }


        return true;

    }


    /* =========================================================
       ELIMINAR USUARIO
       ========================================================= */

    eliminarUsuario(id) {

        const indice =
            this.usuarios.findIndex(

                usuario =>
                    usuario.id_usuario === Number(id)

            );


        if (indice === -1) {

            return false;

        }


        const usuario =
            this.usuarios[indice];


        /* =====================================================
           ELIMINAR TAMBIÉN EL EMPLEADO RELACIONADO
           ===================================================== */

        this.empleados =
            this.empleados.filter(

                empleado =>
                    empleado.id_empleado !==
                    usuario.id_empleado

            );


        this.usuarios.splice(indice, 1);


        return true;

    }


    /* =========================================================
       BUSCAR Y FILTRAR USUARIOS
       ========================================================= */

    filtrarUsuarios(

        texto = "",

        estado = "",

        idRol = ""

    ) {

        const busqueda =
            texto.trim().toLowerCase();


        return this.usuarios.filter(

            usuario => {


                /* ============================================
                   INFORMACIÓN RELACIONADA
                   ============================================ */

                const empleado =
                    this.obtenerEmpleadoPorId(
                        usuario.id_empleado
                    );


                const rol =
                    this.obtenerRolPorId(
                        usuario.id_rol
                    );


                const nombreEmpleado =
                    empleado
                        ? `${empleado.nombres} ${empleado.apellidos}`
                        : "";


                const nombreRol =
                    rol
                        ? rol.nombre_rol
                        : "";


                /* ============================================
                   BÚSQUEDA
                   ============================================ */

                const coincideTexto =

                    busqueda === "" ||

                    usuario.nombre_usuario
                        .toLowerCase()
                        .includes(busqueda) ||

                    nombreEmpleado
                        .toLowerCase()
                        .includes(busqueda) ||

                    nombreRol
                        .toLowerCase()
                        .includes(busqueda);


                /* ============================================
                   FILTRO ESTADO
                   ============================================ */

                const coincideEstado =

                    estado === "" ||

                    usuario.estado === estado;


                /* ============================================
                   FILTRO ROL
                   ============================================ */

                const coincideRol =

                    idRol === "" ||

                    usuario.id_rol === Number(idRol);


                return (

                    coincideTexto &&

                    coincideEstado &&

                    coincideRol

                );

            }

        );

    }


    /* =========================================================
       ESTADÍSTICAS
       ========================================================= */

    obtenerEstadisticas() {

        const total =
            this.usuarios.length;


        const activos =
            this.usuarios.filter(

                usuario =>
                    usuario.estado === "Activo"

            ).length;


        const inactivos =
            this.usuarios.filter(

                usuario =>
                    usuario.estado === "Inactivo"

            ).length;


        const roles =
            this.roles.length;


        return {

            total: total,

            activos: activos,

            inactivos: inactivos,

            roles: roles

        };

    }


    /* =========================================================
       OBTENER INFORMACIÓN COMPLETA
       ========================================================= */

    obtenerUsuarioCompleto(id) {

        const usuario =
            this.obtenerUsuarioPorId(id);


        if (!usuario) {

            return null;

        }


        const empleado =
            this.obtenerEmpleadoPorId(
                usuario.id_empleado
            );


        const rol =
            this.obtenerRolPorId(
                usuario.id_rol
            );


        return {

            usuario: usuario,

            empleado: empleado,

            rol: rol

        };

    }

}


/* =========================================================
   INSTANCIA GLOBAL DEL CONTROLADOR
   ========================================================= */

const usuariosController =
    new UsuariosController();


/* =========================================================
   DISPONIBILIDAD GLOBAL
   ========================================================= */

window.usuariosController =
    usuariosController;
    