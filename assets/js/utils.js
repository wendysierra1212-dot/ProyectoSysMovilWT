/*==================================================
=            CERRAR SESIÓN
==================================================*/

function inicializarCerrarSesion() {

    const btnLogout = document.getElementById("btnLogout");

    if (!btnLogout) {

        return;

    }

    btnLogout.addEventListener("click", () => {

        const confirmar = confirm(
            "¿Desea cerrar la sesión?"
        );

        if (!confirmar) {

            return;

        }

        localStorage.removeItem("usuarioActivo");

        window.location.href = "../auth/cerrar_sesion.php";

    });

}

/*==================================================
=            FECHA ACTUAL
==================================================*/

function cargarFecha(){

    const fecha = document.getElementById("currentDate");

    if(!fecha){

        return;

    }

    const hoy = new Date();

    const opciones = {

        weekday: "long",

        day: "numeric",

        month: "long",

        year: "numeric"

    };

    fecha.textContent = hoy.toLocaleDateString(

        "es-CO",

        opciones

    );

}

/*==================================================
=            PERFIL DE LA TOPBAR
==================================================*/

function cargarPerfilUsuario(){
    const perfil = document.querySelector(".topbar-profile .profile-info");
    if (!perfil) return;

    let usuario = null;
    try {
        usuario = JSON.parse(localStorage.getItem("usuarioActivo") || "null");
    } catch (error) {
        usuario = null;
    }
    if (!usuario) return;

    const nombre = perfil.querySelector("#topbarUser") || perfil.querySelector("strong");
    const rol = perfil.querySelector("#topbarRole") || perfil.querySelector("span");
    if (nombre) nombre.textContent = usuario.nombre || usuario.nombre_usuario || usuario.username || "Usuario";
    if (rol) rol.textContent = usuario.rol || usuario.nombre_rol || "Usuario";
}

document.addEventListener("DOMContentLoaded", cargarPerfilUsuario);

/*==================================================
=            VISIBILIDAD DE MÓDULOS POR ROL
==================================================*/

function ocultarModulosSinPermiso(){
    let usuario = null;
    try { usuario = JSON.parse(localStorage.getItem("usuarioActivo") || "null"); } catch (error) { return; }
    if (!usuario || !usuario.permisos) return;
    const rutas = {
        "dashboard.php": "dashboard", "dashboard.html": "dashboard",
        "inventario.php": "inventario", "inventario.html": "inventario",
        "ventas.php": "ventas", "ventas.html": "ventas",
        "compras.php": "compras", "compras.html": "compras",
        "clientes.php": "clientes", "clientes.html": "clientes",
        "proveedores.php": "proveedores", "proveedores.html": "proveedores",
        "usuarios.php": "usuarios", "usuarios.html": "usuarios",
        "serviciotecnico.php": "servicio_tecnico", "serviciotecnico.html": "servicio_tecnico",
        "reportes.php": "reportes", "reportes.html": "reportes",
        "configuracion.php": "configuracion", "configuracion.html": "configuracion"
    };
    document.querySelectorAll(".sidebar a[href]").forEach(enlace => {
        const ruta = enlace.getAttribute("href").split(/[?#]/)[0].toLowerCase().split("/").pop();
        const modulo = rutas[ruta];
        if (modulo && !usuario.permisos[modulo]?.ver) {
            const elemento = enlace.closest("li") || enlace;
            elemento.hidden = true;
        }
    });
}
document.addEventListener("DOMContentLoaded", ocultarModulosSinPermiso);

/*==================================================
=            MOSTRAR / OCULTAR SIDEBAR
==================================================*/

function inicializarSidebar(){
    const botones = document.querySelectorAll(".menu-toggle");
    if (!botones.length) return;

    let oculta = false;
    try {
        oculta = localStorage.getItem("sysmovilwt_sidebar_oculta") === "true";
    } catch (error) {
        oculta = false;
    }

    const aplicarEstado = () => {
        document.body.classList.toggle("sidebar-collapsed", oculta);
        botones.forEach(boton => {
            boton.setAttribute("aria-expanded", String(!oculta));
            boton.setAttribute("aria-label", oculta ? "Mostrar menú lateral" : "Ocultar menú lateral");
            boton.setAttribute("title", oculta ? "Mostrar menú lateral" : "Ocultar menú lateral");
        });
    };

    aplicarEstado();
    botones.forEach(boton => boton.addEventListener("click", () => {
        oculta = !oculta;
        aplicarEstado();
        try {
            localStorage.setItem("sysmovilwt_sidebar_oculta", String(oculta));
        } catch (error) {
            // El menú sigue funcionando aunque el almacenamiento esté bloqueado.
        }
    }));
}

document.addEventListener("DOMContentLoaded", inicializarSidebar);

/*==================================================
=            ACCIÓN DE CERRAR SESIÓN EN EL PERFIL
==================================================*/

function inicializarMenuPerfil(){
    const perfiles = document.querySelectorAll(".topbar-profile");
    const botonCerrarSesion = document.getElementById("btnLogout");
    if (!perfiles.length || !botonCerrarSesion) return;

    const contenedores = [];
    perfiles.forEach(perfil => {
        let contenedor = perfil.closest(".profile-menu-container");
        if (!contenedor) {
            contenedor = document.createElement("div");
            contenedor.className = "profile-menu-container";
            perfil.parentNode.insertBefore(contenedor, perfil);
            contenedor.appendChild(perfil);

            const menu = document.createElement("div");
            menu.className = "profile-menu";
            menu.hidden = true;
            menu.setAttribute("role", "menu");
            contenedor.appendChild(menu);
        }

        const menu = contenedor.querySelector(".profile-menu");
        if (menu && !menu.contains(botonCerrarSesion)) menu.appendChild(botonCerrarSesion);

        perfil.setAttribute("role", "button");
        perfil.setAttribute("tabindex", "0");
        perfil.setAttribute("aria-haspopup", "menu");
        perfil.setAttribute("aria-expanded", "false");
        perfil.setAttribute("aria-label", "Abrir menú del perfil");
        contenedores.push({perfil, contenedor, menu});

        const alternar = () => {
            const abrir = menu.hidden;
            contenedores.forEach(item => {
                item.menu.hidden = true;
                item.perfil.setAttribute("aria-expanded", "false");
            });
            menu.hidden = !abrir;
            perfil.setAttribute("aria-expanded", String(abrir));
        };

        perfil.addEventListener("click", alternar);
        perfil.addEventListener("keydown", evento => {
            if (evento.key === "Enter" || evento.key === " ") {
                evento.preventDefault();
                alternar();
            }
        });
        menu?.addEventListener("click", evento => evento.stopPropagation());
    });

    document.addEventListener("click", evento => {
        if (contenedores.some(item => item.contenedor.contains(evento.target))) return;
        contenedores.forEach(item => {
            item.menu.hidden = true;
            item.perfil.setAttribute("aria-expanded", "false");
        });
    });

    document.addEventListener("keydown", evento => {
        if (evento.key !== "Escape") return;
        contenedores.forEach(item => {
            item.menu.hidden = true;
            item.perfil.setAttribute("aria-expanded", "false");
        });
    });
}

document.addEventListener("DOMContentLoaded", inicializarMenuPerfil);
/*==================================================
=            ACTIVIDAD DEL SISTEMA
==================================================*/

function registrarActividad(tipo, accion, descripcion){

    const actividades = JSON.parse(

        localStorage.getItem("sysmovilwt_actividad")

    ) || [];

    actividades.unshift({

        tipo,

        accion,

        descripcion,

        fecha:new Date().toISOString()

    });

    if(actividades.length > 50){

        actividades.splice(50);

    }

    localStorage.setItem(

        "sysmovilwt_actividad",

        JSON.stringify(actividades)

    );

}

/*==================================================
=            OBTENER ACTIVIDAD
==================================================*/

function obtenerActividad(){

    return JSON.parse(

        localStorage.getItem("sysmovilwt_actividad")

    ) || [];

}
