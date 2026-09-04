/**
 * ==================================================
 * SYSMOVILWT
 * DASHBOARD
 * VERSIÓN 1.1
 * ==================================================
 */

const dashboard = new DashboardController();

/*=========================================================
=                 INICIALIZACIÓN
=========================================================*/

document.addEventListener(

    "DOMContentLoaded",

    iniciarDashboard

);

function iniciarDashboard(){

    inicializarCerrarSesion();

    cargarFecha();

    console.log("Dashboard iniciado.");

    cargarUsuario();

    cargarEstadisticas();

    cargarActividadReciente();

    cargarAlertas();

    inicializarAccesosRapidos();

}

/*=========================================================
=                 USUARIO
=========================================================*/

function cargarUsuario(){

    const usuario = JSON.parse(

        localStorage.getItem("usuarioActivo")

    );

    if(!usuario){

        window.location.href = "../login.html";

        return;

    }

    const nombreTopbar = document.getElementById("topbarUser");

    const rolTopbar = document.getElementById("topbarRole");

    if(nombreTopbar){

        nombreTopbar.textContent = usuario.nombre;

    }

    if(rolTopbar){

        rolTopbar.textContent = usuario.rol;

    }

}

/*=========================================================
=                 ESTADÍSTICAS
=========================================================*/

function cargarEstadisticas(){

    document.getElementById(

        "totalProductos"

    ).textContent =

        dashboard.obtenerTotalProductos();

    document.getElementById(

        "totalVentas"

    ).textContent =

        dashboard.obtenerTotalVentas();

    document.getElementById(

        "totalClientes"

    ).textContent =

        dashboard.obtenerTotalClientes();

    document.getElementById(

        "totalIngresos"

    ).textContent =

        "$ " +

        dashboard

        .obtenerValorInventario()

        .toLocaleString("es-CO");

}

/*=========================================================
=                 ACTIVIDAD RECIENTE
=========================================================*/

function cargarActividadReciente(){

    const panel = document.getElementById(

        "activityList"

    );

    if(!panel){

        return;

    }

    const actividades =

        dashboard.obtenerActividadReciente();

    if(actividades.length === 0){

        panel.innerHTML = `

            <p class="empty-message">

                No hay actividad registrada.

            </p>

        `;

        return;

    }

    let html = "";

    actividades.forEach(actividad=>{

        html += `

            <div class="activity-item">

                <div class="activity-icon">

                    ${obtenerIconoActividad(

                        actividad.tipo

                    )}

                </div>

                <div class="activity-content">

                    <strong>

                        ${actividad.descripcion}

                    </strong>

                    <small>

                        ${formatearTiempo(

                            actividad.fecha

                        )}

                    </small>

                </div>

            </div>

        `;

    });

    panel.innerHTML = html;

}

/*=========================================================
=                 ALERTAS
=========================================================*/

function cargarAlertas(){

    const panel = document.getElementById(

        "alertsList"

    );

    if(!panel){

        return;

    }

    const alertas =

        dashboard.obtenerAlertasStock();

    if(alertas.length === 0){

        panel.innerHTML = `

            <p class="empty-message">

                No existen alertas pendientes.

            </p>

        `;

        return;

    }

    let html = "";

    alertas.forEach(alerta=>{

        html += `

            <div class="alert-item">

                <i class="fa-solid fa-triangle-exclamation"></i>

                <span>

                    ${alerta.mensaje}

                </span>

            </div>

        `;

    });

    panel.innerHTML = html;

}

/*=========================================================
=                 ICONOS DE ACTIVIDAD
=========================================================*/

function obtenerIconoActividad(tipo){

    switch(tipo){

        case "producto":

            return '<i class="fa-solid fa-box"></i>';

        case "venta":

            return '<i class="fa-solid fa-cart-shopping"></i>';

        case "cliente":

            return '<i class="fa-solid fa-user"></i>';

        case "login":

            return '<i class="fa-solid fa-right-to-bracket"></i>';

        case "logout":

            return '<i class="fa-solid fa-right-from-bracket"></i>';

        default:

            return '<i class="fa-solid fa-circle-info"></i>';

    }

}

/*=========================================================
=                 FORMATEAR TIEMPO
=========================================================*/

function formatearTiempo(fecha){

    const ahora = new Date();

    const evento = new Date(fecha);

    const diferencia = Math.floor(

        (ahora - evento) / 1000

    );

    if(diferencia < 60){

        return "Hace unos segundos";

    }

    if(diferencia < 3600){

        return `Hace ${Math.floor(diferencia / 60)} minuto(s)`;

    }

    if(diferencia < 86400){

        return `Hace ${Math.floor(diferencia / 3600)} hora(s)`;

    }

    return evento.toLocaleDateString(

        "es-CO",

        {

            day:"2-digit",

            month:"short",

            year:"numeric"

        }

    );

}

/*=========================================================
=                 ACTUALIZAR DASHBOARD
=========================================================*/

function actualizarDashboard(){

    cargarEstadisticas();

    cargarActividadReciente();

    cargarAlertas();

}

/*=========================================================
=                 REFRESCO AUTOMÁTICO
=========================================================*/

window.addEventListener(

    "focus",

    actualizarDashboard

);

/*==================================================
=            ACCESOS RÁPIDOS
==================================================*/

function inicializarAccesosRapidos(){

    console.log("Inicializando accesos rápidos...");

    const tarjetas = document.querySelectorAll(".quick-card");

    console.log("Tarjetas encontradas:", tarjetas.length);

    tarjetas.forEach(tarjeta=>{

        tarjeta.addEventListener("click",()=>{

            console.log("Click en:", tarjeta.dataset.page);

            const pagina = tarjeta.dataset.page;

            if(pagina){

                window.location.href = pagina;

            }

        });

    });

}