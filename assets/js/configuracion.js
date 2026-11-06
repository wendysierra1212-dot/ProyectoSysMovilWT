/* =========================================================
   SYSMOVILWT
   CONFIGURACIÓN - CONTROL DE PESTAÑAS
========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       PESTAÑAS DE CONFIGURACIÓN
    ===================================================== */

    const tabs = document.querySelectorAll(".config-tab");
    const contents = document.querySelectorAll(".config-tab-content");


    tabs.forEach(function (tab) {

        tab.addEventListener("click", function () {

            const tabName = this.getAttribute("data-tab");


            /* ---------------------------------------------
               QUITAR ESTADO ACTIVO DE TODAS LAS PESTAÑAS
            --------------------------------------------- */

            tabs.forEach(function (item) {

                item.classList.remove("active");

            });


            /* ---------------------------------------------
               OCULTAR TODOS LOS CONTENIDOS
            --------------------------------------------- */

            contents.forEach(function (content) {

                content.classList.remove("active");

            });


            /* ---------------------------------------------
               ACTIVAR PESTAÑA SELECCIONADA
            --------------------------------------------- */

            this.classList.add("active");


            /* ---------------------------------------------
               MOSTRAR CONTENIDO CORRESPONDIENTE
            --------------------------------------------- */

            const selectedContent = document.getElementById(
                "tab-" + tabName
            );


            if (selectedContent) {

                selectedContent.classList.add("active");

            } else {

                console.error(
                    "No se encontró el contenido para la pestaña:",
                    tabName
                );

            }

        });

    });

    /* =====================================================
       ACCIÓN: ABRIR LA PESTAÑA SOLICITADA EN EL ENLACE
    ===================================================== */

    const tabFromHash = window.location.hash.replace("#tab-", "");
    const requestedTab = Array.from(tabs).find(function (tab) {
        return tab.getAttribute("data-tab") === tabFromHash;
    });

    if (requestedTab) {
        requestedTab.click();
    }


    /* =====================================================
       FECHA ACTUAL
    ===================================================== */

    const currentDate = document.getElementById("currentDate");


    if (currentDate) {

        const fecha = new Date();


        const opciones = {
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric"
        };


        currentDate.textContent = fecha.toLocaleDateString(
            "es-CO",
            opciones
        );

    }


});
