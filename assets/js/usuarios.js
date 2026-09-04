/* =========================================================
   GESTIÓN DE USUARIOS - SYSMOVILWT
   ========================================================= */

/*
    Este archivo controla la interfaz del módulo Usuarios.

    Flujo:

    1. Se registra la información del empleado.
    2. Se registran los datos de acceso.
    3. Se asigna un rol.
    4. El controlador crea el empleado.
    5. El controlador crea el usuario relacionado.

    Actualmente trabajamos con datos simulados.
*/


document.addEventListener("DOMContentLoaded", () => {


    /* =====================================================
       ELEMENTOS PRINCIPALES
       ===================================================== */

    const btnNuevoUsuario =
        document.getElementById("btnNuevoUsuario");

    const userModal =
        document.getElementById("userModal");

    const btnCloseModal =
        document.getElementById("btnCloseModal");

    const btnCancelarUsuario =
        document.getElementById("btnCancelarUsuario");

    const userForm =
        document.getElementById("userForm");


    /* =====================================================
       ELEMENTOS DEL FORMULARIO
       ===================================================== */

    const nombresEmpleado =
        document.getElementById("nombresEmpleado");

    const apellidosEmpleado =
        document.getElementById("apellidosEmpleado");

    const cedulaEmpleado =
        document.getElementById("cedulaEmpleado");

    const telefonoEmpleado =
        document.getElementById("telefonoEmpleado");

    const correoEmpleado =
        document.getElementById("correoEmpleado");

    const direccionEmpleado =
        document.getElementById("direccionEmpleado");

    const cargoEmpleado =
        document.getElementById("cargoEmpleado");

    const fechaIngresoEmpleado =
        document.getElementById("fechaIngresoEmpleado");


    const nombreUsuario =
        document.getElementById("nombreUsuario");

    const contrasena =
        document.getElementById("contrasena");

    const confirmarContrasena =
        document.getElementById("confirmarContrasena");

    const estadoUsuario =
        document.getElementById("estadoUsuario");


    const rolUsuario =
        document.getElementById("rolUsuario");

    const descripcionRol =
        document.getElementById("descripcionRol");

    const accessDescription =
        document.getElementById("accessDescription");


    const forzarCambioContrasena =
        document.getElementById("forzarCambioContrasena");

    const observacionesUsuario =
        document.getElementById("observacionesUsuario");


    /* =====================================================
       ELEMENTOS DE TABLA Y FILTROS
       ===================================================== */

    const usersTableBody =
        document.getElementById("usersTableBody");

    const searchUser =
        document.getElementById("searchUser");

    const filterEstado =
        document.getElementById("filterEstado");

    const filterRol =
        document.getElementById("filterRol");

    const btnResetFilters =
        document.getElementById("btnResetFilters");


    /* =====================================================
       ESTADÍSTICAS
       ===================================================== */

    const totalUsuarios =
        document.getElementById("totalUsuarios");

    const usuariosActivos =
        document.getElementById("usuariosActivos");

    const usuariosInactivos =
        document.getElementById("usuariosInactivos");

    const totalRoles =
        document.getElementById("totalRoles");


    /* =====================================================
       VARIABLE PARA EDITAR
       ===================================================== */

    let usuarioEditandoId = null;


    /* =====================================================
       INICIALIZACIÓN
       ===================================================== */

    inicializar();


    function inicializar() {

        cargarRoles();

        actualizarEstadisticas();

        renderizarUsuarios();

        configurarEventos();

    }


    /* =====================================================
       CONFIGURAR EVENTOS
       ===================================================== */

    function configurarEventos() {


        /* ================================================
           NUEVO USUARIO
           ================================================ */

        if (btnNuevoUsuario) {

            btnNuevoUsuario.addEventListener(
                "click",
                abrirModalNuevoUsuario
            );

        }


        /* ================================================
           CERRAR MODAL
           ================================================ */

        if (btnCloseModal) {

            btnCloseModal.addEventListener(
                "click",
                cerrarModal
            );

        }


        if (btnCancelarUsuario) {

            btnCancelarUsuario.addEventListener(
                "click",
                cerrarModal
            );

        }


        /* ================================================
           CERRAR AL HACER CLIC FUERA DEL MODAL
           ================================================ */

        if (userModal) {

            userModal.addEventListener(
                "click",
                (evento) => {

                    if (
                        evento.target === userModal
                    ) {

                        cerrarModal();

                    }

                }
            );

        }


        /* ================================================
           FORMULARIO
           ================================================ */

        if (userForm) {

            userForm.addEventListener(
                "submit",
                guardarUsuario
            );

        }


        /* ================================================
           CAMBIO DE ROL
           ================================================ */

        if (rolUsuario) {

            rolUsuario.addEventListener(
                "change",
                mostrarInformacionRol
            );

        }


        /* ================================================
           BÚSQUEDA
           ================================================ */

        if (searchUser) {

            searchUser.addEventListener(
                "input",
                renderizarUsuarios
            );

        }


        /* ================================================
           FILTRO ESTADO
           ================================================ */

        if (filterEstado) {

            filterEstado.addEventListener(
                "change",
                renderizarUsuarios
            );

        }


        /* ================================================
           FILTRO ROL
           ================================================ */

        if (filterRol) {

            filterRol.addEventListener(
                "change",
                renderizarUsuarios
            );

        }


        /* ================================================
           RESTABLECER FILTROS
           ================================================ */

        if (btnResetFilters) {

            btnResetFilters.addEventListener(
                "click",
                () => {

                    if (searchUser) {
                        searchUser.value = "";
                    }

                    if (filterEstado) {
                        filterEstado.value = "";
                    }

                    if (filterRol) {
                        filterRol.value = "";
                    }

                    renderizarUsuarios();

                }
            );

        }

    }


    /* =====================================================
       CARGAR ROLES
       ===================================================== */

    function cargarRoles() {

        if (!rolUsuario) {
            return;
        }


        const roles =
            usuariosController.obtenerRoles();


        rolUsuario.innerHTML = `
            <option value="">
                Seleccione un rol...
            </option>
        `;


        roles.forEach(rol => {

            const option =
                document.createElement("option");


            option.value =
                rol.id_rol;


            option.textContent =
                rol.nombre_rol;


            rolUsuario.appendChild(option);

        });


        /* ================================================
           CARGAR FILTRO DE ROLES
           ================================================ */

        if (filterRol) {

            filterRol.innerHTML = `
                <option value="">
                    Todos los roles
                </option>
            `;


            roles.forEach(rol => {

                const option =
                    document.createElement("option");


                option.value =
                    rol.id_rol;


                option.textContent =
                    rol.nombre_rol;


                filterRol.appendChild(option);

            });

        }

    }


    /* =====================================================
       MOSTRAR INFORMACIÓN DEL ROL
       ===================================================== */

    function mostrarInformacionRol() {

        const idRol =
            Number(rolUsuario.value);


        if (!idRol) {

            if (descripcionRol) {

                descripcionRol.value = "";

            }


            if (accessDescription) {

                accessDescription.textContent =
                    "Los permisos serán determinados por el rol seleccionado.";

            }

            return;

        }


        const rol =
            usuariosController.obtenerRolPorId(
                idRol
            );


        if (!rol) {
            return;
        }


        if (descripcionRol) {

            descripcionRol.value =
                rol.descripcion;

        }


        if (accessDescription) {

            accessDescription.textContent =
                rol.descripcion;

        }

    }


    /* =====================================================
       ABRIR MODAL NUEVO USUARIO
       ===================================================== */

    function abrirModalNuevoUsuario() {

        usuarioEditandoId = null;


        limpiarFormulario();


        const titulo =
            userModal.querySelector(
                ".crud-modal-header h2"
            );


        if (titulo) {

            titulo.innerHTML = `
                <i class="fa-solid fa-user-plus"></i>
                Nuevo Usuario
            `;

        }


        const botonGuardar =
            userForm.querySelector(
                ".btn-save"
            );


        if (botonGuardar) {

            botonGuardar.textContent =
                "Guardar Usuario";

        }


        userModal.classList.add("active");

    }


    /* =====================================================
       CERRAR MODAL
       ===================================================== */

    function cerrarModal() {

        userModal.classList.remove("active");

        usuarioEditandoId = null;

        limpiarFormulario();

    }


    /* =====================================================
       LIMPIAR FORMULARIO
       ===================================================== */

    function limpiarFormulario() {

        if (userForm) {

            userForm.reset();

        }


        if (descripcionRol) {

            descripcionRol.value = "";

        }


        if (accessDescription) {

            accessDescription.textContent =
                "Los permisos serán determinados por el rol seleccionado.";

        }


        if (estadoUsuario) {

            estadoUsuario.value =
                "Activo";

        }


        if (forzarCambioContrasena) {

            forzarCambioContrasena.checked =
                false;

        }


        usuarioEditandoId = null;

    }


    /* =====================================================
       GUARDAR USUARIO
       ===================================================== */

    function guardarUsuario(evento) {

        evento.preventDefault();


        /* ================================================
           VALIDAR CONTRASEÑAS
           ================================================ */

        if (
            contrasena.value !==
            confirmarContrasena.value
        ) {

            mostrarMensaje(
                "Las contraseñas no coinciden.",
                "error"
            );

            confirmarContrasena.focus();

            return;

        }


        /* ================================================
           VALIDAR ROL
           ================================================ */

        if (!rolUsuario.value) {

            mostrarMensaje(
                "Debe seleccionar un rol.",
                "error"
            );

            rolUsuario.focus();

            return;

        }


        /* ================================================
           CREAR OBJETO DE DATOS
           ================================================ */

        const datos = {

            /* ============================================
               EMPLEADO
               ============================================ */

            nombres:
                nombresEmpleado.value.trim(),

            apellidos:
                apellidosEmpleado.value.trim(),

            cedula:
                cedulaEmpleado.value.trim(),

            telefono:
                telefonoEmpleado.value.trim(),

            correo:
                correoEmpleado.value.trim(),

            direccion:
                direccionEmpleado.value.trim(),

            cargo:
                cargoEmpleado.value.trim(),

            fecha_ingreso:
                fechaIngresoEmpleado.value,


            /* ============================================
               USUARIO
               ============================================ */

            nombre_usuario:
                nombreUsuario.value.trim(),

            contrasena:
                contrasena.value,

            confirmar_contrasena:
                confirmarContrasena.value,

            estado:
                estadoUsuario.value,


            /* ============================================
               ROL
               ============================================ */

            id_rol:
                Number(rolUsuario.value),


            /* ============================================
               INFORMACIÓN ADICIONAL
               ============================================ */

            forzar_cambio_contrasena:
                forzarCambioContrasena.checked,

            observaciones:
                observacionesUsuario.value.trim()

        };


        /* ================================================
           CREAR USUARIO
           ================================================ */

        let resultado;


        if (usuarioEditandoId === null) {

            resultado =
                usuariosController.crearUsuario(
                    datos
                );

        } else {

            resultado =
                usuariosController.actualizarUsuario(
                    usuarioEditandoId,
                    datos
                );

        }


        /* ================================================
           RESULTADO
           ================================================ */

        if (!resultado.correcto) {

            const errores =
                resultado.errores || [];


            mostrarMensaje(
                errores.join("\n"),
                "error"
            );


            return;

        }


        /* ================================================
           GUARDADO CORRECTO
           ================================================ */

        cerrarModal();

        actualizarEstadisticas();

        renderizarUsuarios();


        mostrarMensaje(

            usuarioEditandoId === null

                ? "Usuario creado correctamente."

                : "Usuario actualizado correctamente.",

            "success"

        );

    }


    /* =====================================================
       RENDERIZAR USUARIOS
       ===================================================== */

    function renderizarUsuarios() {

        if (!usersTableBody) {
            return;
        }


        const texto =
            searchUser
                ? searchUser.value
                : "";


        const estado =
            filterEstado
                ? filterEstado.value
                : "";


        const idRol =
            filterRol
                ? filterRol.value
                : "";


        const usuarios =
            usuariosController.filtrarUsuarios(
                texto,
                estado,
                idRol
            );


        if (usuarios.length === 0) {

            usersTableBody.innerHTML = `

                <tr class="users-empty">

                    <td colspan="6">

                        <div class="empty-state">

                            <i class="fa-solid fa-user-shield"></i>

                            <h3>
                                No hay usuarios registrados
                            </h3>

                            <p>
                                Haz clic en
                                <strong>Nuevo Usuario</strong>
                                para comenzar.
                            </p>

                        </div>

                    </td>

                </tr>

            `;

            return;

        }


        usersTableBody.innerHTML = "";


        usuarios.forEach(usuario => {

            const empleado =
                usuariosController.obtenerEmpleadoPorId(
                    usuario.id_empleado
                );


            const rol =
                usuariosController.obtenerRolPorId(
                    usuario.id_rol
                );


            const fila =
                document.createElement("tr");


            const nombreEmpleado =
                empleado

                    ? `${empleado.nombres} ${empleado.apellidos}`

                    : "Sin empleado";


            const nombreRol =
                rol
                    ? rol.nombre_rol
                    : "Sin rol";


            const claseEstado =
                usuario.estado === "Activo"
                    ? "status-active"
                    : "status-inactive";


            const iconoEstado =
                usuario.estado === "Activo"
                    ? "fa-circle-check"
                    : "fa-circle-xmark";


            const ultimoAcceso =
                usuario.ultimo_acceso
                    ? usuario.ultimo_acceso
                    : "Nunca";


            fila.innerHTML = `

                <td>

                    <div class="user-table-info">

                        <div class="user-table-avatar">

                            ${obtenerIniciales(
                                nombreEmpleado
                            )}

                        </div>

                        <div>

                            <strong>
                                ${escapeHTML(
                                    usuario.nombre_usuario
                                )}
                            </strong>

                            <span>
                                ${empleado
                                    ? escapeHTML(
                                        empleado.correo || "Sin correo"
                                    )
                                    : "Sin correo"}
                            </span>

                        </div>

                    </div>

                </td>


                <td>

                    <div class="employee-table-info">

                        <strong>
                            ${escapeHTML(
                                nombreEmpleado
                            )}
                        </strong>

                        <span>
                            ${empleado
                                ? escapeHTML(
                                    empleado.cargo
                                )
                                : ""}
                        </span>

                    </div>

                </td>


                <td>

                    <span class="role-badge">

                        <i class="fa-solid fa-shield-halved"></i>

                        ${escapeHTML(
                            nombreRol
                        )}

                    </span>

                </td>


                <td>

                    <span class="status-badge ${claseEstado}">

                        <i class="fa-solid ${iconoEstado}"></i>

                        ${escapeHTML(
                            usuario.estado
                        )}

                    </span>

                </td>


                <td>

                    <span class="last-access">

                        ${escapeHTML(
                            ultimoAcceso
                        )}

                    </span>

                </td>


                <td>

                    <div class="table-actions">

                        <button
                            class="action-btn action-edit"
                            title="Editar usuario"
                            data-action="editar"
                            data-id="${usuario.id_usuario}">

                            <i class="fa-solid fa-pen"></i>

                        </button>


                        <button
                            class="action-btn action-status"
                            title="${
                                usuario.estado === "Activo"
                                    ? "Desactivar usuario"
                                    : "Activar usuario"
                            }"
                            data-action="estado"
                            data-id="${usuario.id_usuario}">

                            <i class="fa-solid ${
                                usuario.estado === "Activo"
                                    ? "fa-user-slash"
                                    : "fa-user-check"
                            }"></i>

                        </button>


                        <button
                            class="action-btn action-delete"
                            title="Eliminar usuario"
                            data-action="eliminar"
                            data-id="${usuario.id_usuario}">

                            <i class="fa-solid fa-trash"></i>

                        </button>

                    </div>

                </td>

            `;


            usersTableBody.appendChild(fila);

        });


        agregarEventosTabla();

    }


    /* =====================================================
       EVENTOS DE LA TABLA
       ===================================================== */

    function agregarEventosTabla() {

        const botones =
            usersTableBody.querySelectorAll(
                "[data-action]"
            );


        botones.forEach(boton => {

            boton.addEventListener(
                "click",
                manejarAccionTabla
            );

        });

    }


    /* =====================================================
       MANEJAR ACCIONES
       ===================================================== */

    function manejarAccionTabla(evento) {

        const boton =
            evento.currentTarget;


        const accion =
            boton.dataset.action;


        const id =
            Number(boton.dataset.id);


        switch (accion) {


            case "editar":

                editarUsuario(id);

                break;


            case "estado":

                cambiarEstadoUsuario(id);

                break;


            case "eliminar":

                eliminarUsuario(id);

                break;

        }

    }


    /* =====================================================
       EDITAR USUARIO
       ===================================================== */

    function editarUsuario(id) {

        const completo =
            usuariosController.obtenerUsuarioCompleto(
                id
            );


        if (!completo) {

            mostrarMensaje(
                "No se encontró el usuario.",
                "error"
            );

            return;

        }


        const usuario =
            completo.usuario;


        const empleado =
            completo.empleado;


        if (!empleado) {

            mostrarMensaje(
                "No se encontró la información del empleado.",
                "error"
            );

            return;

        }


        usuarioEditandoId =
            usuario.id_usuario;


        /* ================================================
           CARGAR EMPLEADO
           ================================================ */

        nombresEmpleado.value =
            empleado.nombres || "";


        apellidosEmpleado.value =
            empleado.apellidos || "";


        cedulaEmpleado.value =
            empleado.cedula || "";


        telefonoEmpleado.value =
            empleado.telefono || "";


        correoEmpleado.value =
            empleado.correo || "";


        direccionEmpleado.value =
            empleado.direccion || "";


        cargoEmpleado.value =
            empleado.cargo || "";


        fechaIngresoEmpleado.value =
            empleado.fecha_ingreso || "";


        /* ================================================
           CARGAR USUARIO
           ================================================ */

        nombreUsuario.value =
            usuario.nombre_usuario || "";


        estadoUsuario.value =
            usuario.estado || "Activo";


        contrasena.value = "";

        confirmarContrasena.value = "";


        /* ================================================
           CARGAR ROL
           ================================================ */

        rolUsuario.value =
            usuario.id_rol || "";


        mostrarInformacionRol();


        /* ================================================
           INFORMACIÓN ADICIONAL
           ================================================ */

        if (forzarCambioContrasena) {

            forzarCambioContrasena.checked =
                false;

        }


        if (observacionesUsuario) {

            observacionesUsuario.value =
                "";

        }


        /* ================================================
           CAMBIAR TÍTULO
           ================================================ */

        const titulo =
            userModal.querySelector(
                ".crud-modal-header h2"
            );


        if (titulo) {

            titulo.innerHTML = `

                <i class="fa-solid fa-user-pen"></i>

                Editar Usuario

            `;

        }


        const botonGuardar =
            userForm.querySelector(
                ".btn-save"
            );


        if (botonGuardar) {

            botonGuardar.textContent =
                "Actualizar Usuario";

        }


        /* ================================================
           ABRIR MODAL
           ================================================ */

        userModal.classList.add("active");

    }


    /* =====================================================
       CAMBIAR ESTADO
       ===================================================== */

    function cambiarEstadoUsuario(id) {

        const usuario =
            usuariosController.obtenerUsuarioPorId(
                id
            );


        if (!usuario) {

            return;

        }


        const accion =
            usuario.estado === "Activo"
                ? "desactivar"
                : "activar";


        const mensaje =
            usuario.estado === "Activo"

                ? "¿Deseas desactivar este usuario?"

                : "¿Deseas activar este usuario?";


        if (!confirm(mensaje)) {

            return;

        }


        usuariosController.cambiarEstado(id);


        actualizarEstadisticas();

        renderizarUsuarios();


        mostrarMensaje(

            accion === "activar"

                ? "Usuario activado correctamente."

                : "Usuario desactivado correctamente.",

            "success"

        );

    }


    /* =====================================================
       ELIMINAR USUARIO
       ===================================================== */

    function eliminarUsuario(id) {

        const usuario =
            usuariosController.obtenerUsuarioPorId(
                id
            );


        if (!usuario) {

            return;

        }


        const empleado =
            usuariosController.obtenerEmpleadoPorId(
                usuario.id_empleado
            );


        const nombre =
            empleado

                ? `${empleado.nombres} ${empleado.apellidos}`

                : usuario.nombre_usuario;


        const confirmar =
            confirm(

                `¿Deseas eliminar el usuario "${nombre}"?\n\n` +

                `Esta acción también eliminará el registro ` +

                `del empleado asociado.`

            );


        if (!confirmar) {

            return;

        }


        const eliminado =
            usuariosController.eliminarUsuario(
                id
            );


        if (!eliminado) {

            mostrarMensaje(
                "No fue posible eliminar el usuario.",
                "error"
            );

            return;

        }


        actualizarEstadisticas();

        renderizarUsuarios();


        mostrarMensaje(
            "Usuario eliminado correctamente.",
            "success"
        );

    }


    /* =====================================================
       ACTUALIZAR ESTADÍSTICAS
       ===================================================== */

    function actualizarEstadisticas() {

        const estadisticas =
            usuariosController.obtenerEstadisticas();


        if (totalUsuarios) {

            totalUsuarios.textContent =
                estadisticas.total;

        }


        if (usuariosActivos) {

            usuariosActivos.textContent =
                estadisticas.activos;

        }


        if (usuariosInactivos) {

            usuariosInactivos.textContent =
                estadisticas.inactivos;

        }


        if (totalRoles) {

            totalRoles.textContent =
                estadisticas.roles;

        }

    }


    /* =====================================================
       OBTENER INICIALES
       ===================================================== */

    function obtenerIniciales(nombre) {

        if (!nombre) {

            return "US";

        }


        const partes =
            nombre
                .trim()
                .split(" ")
                .filter(Boolean);


        if (partes.length === 1) {

            return partes[0]
                .substring(0, 2)
                .toUpperCase();

        }


        return (

            partes[0][0] +

            partes[partes.length - 1][0]

        ).toUpperCase();

    }


    /* =====================================================
       ESCAPAR HTML
       ===================================================== */

    function escapeHTML(texto) {

        if (
            texto === null ||
            texto === undefined
        ) {

            return "";

        }


        return String(texto)

            .replace(/&/g, "&amp;")

            .replace(/</g, "&lt;")

            .replace(/>/g, "&gt;")

            .replace(/"/g, "&quot;")

            .replace(/'/g, "&#039;");

    }


    /* =====================================================
       MENSAJES
       ===================================================== */

    function mostrarMensaje(
        mensaje,
        tipo = "success"
    ) {

        /* ================================================
           ELIMINAR MENSAJE ANTERIOR
           ================================================ */

        const anterior =
            document.querySelector(
                ".usuarios-message"
            );


        if (anterior) {

            anterior.remove();

        }


        const mensajeElemento =
            document.createElement("div");


        mensajeElemento.className =
            `usuarios-message ${tipo}`;


        mensajeElemento.innerHTML = `

            <div class="usuarios-message-icon">

                <i class="fa-solid ${
                    tipo === "success"

                        ? "fa-circle-check"

                        : "fa-circle-exclamation"
                }"></i>

            </div>


            <div class="usuarios-message-text">

                ${escapeHTML(mensaje)}

            </div>

        `;


        document.body.appendChild(
            mensajeElemento
        );


        /* ================================================
           MOSTRAR
           ================================================ */

        setTimeout(() => {

            mensajeElemento.classList.add(
                "show"
            );

        }, 10);


        /* ================================================
           OCULTAR
           ================================================ */

        setTimeout(() => {

            mensajeElemento.classList.remove(
                "show"
            );


            setTimeout(() => {

                mensajeElemento.remove();

            }, 300);

        }, 3500);

    }


});
