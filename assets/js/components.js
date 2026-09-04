/*=========================================================
=                 COMPONENTES V3
=========================================================*/

document.addEventListener("DOMContentLoaded", () => {

    cargarComponente(
        "../components/sidebar.html",
        "sidebar-container"
    );

    cargarComponente(
        "../components/topbar.html",
        "topbar-container"
    );

});


/*=========================================================
=                 CARGAR COMPONENTES
=========================================================*/

async function cargarComponente(ruta, contenedor){

    try{

        const respuesta = await fetch(ruta);

        if(!respuesta.ok){

            throw new Error(
                `No se pudo cargar ${ruta}`
            );

        }

        const html = await respuesta.text();

        document
            .getElementById(contenedor)
            .innerHTML = html;

    }

    catch(error){

        console.error(error);

    }

}