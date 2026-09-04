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

        window.location.href = "../login.html";

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