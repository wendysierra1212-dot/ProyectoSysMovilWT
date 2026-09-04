document.addEventListener("DOMContentLoaded", () => {

    console.log("ServicioTecnico.js cargado");

    const btnNuevo = document.getElementById("btnNewService");
    const modal = document.getElementById("modalServicio");
    const btnCerrar = document.getElementById("btnCloseServiceModal");
    const btnCancelar = document.getElementById("btnCancelarServicio");

    console.log("Botón Nueva Orden:", btnNuevo);
    console.log("Modal:", modal);

    // ==========================================
    // ABRIR MODAL
    // ==========================================

    if (btnNuevo && modal) {

        btnNuevo.addEventListener("click", () => {

            console.log("CLICK EN NUEVA ORDEN");

            modal.classList.add("active");

        });

    } else {

        console.error(
            "No se encontró btnNewService o modalServicio"
        );

    }


    // ==========================================
    // CERRAR CON X
    // ==========================================

    if (btnCerrar && modal) {

        btnCerrar.addEventListener("click", () => {

            modal.classList.remove("active");

        });

    }


    // ==========================================
    // CERRAR CON CANCELAR
    // ==========================================

    if (btnCancelar && modal) {

        btnCancelar.addEventListener("click", () => {

            modal.classList.remove("active");

        });

    }


    // ==========================================
    // CERRAR HACIENDO CLICK EN EL FONDO
    // ==========================================

    if (modal) {

        modal.addEventListener("click", (event) => {

            if (event.target === modal) {

                modal.classList.remove("active");

            }

        });

    }


    // ==========================================
    // INICIALIZAR CONTROLADOR
    // ==========================================

    if (typeof ServicioTecnicoController === "function") {

        const elements = {

            modal: document.getElementById("modalServicio"),
            form: document.getElementById("formServicio"),

            btnNuevo: document.getElementById("btnNewService"),
            btnGuardar: document.getElementById("btnGuardarServicio"),
            btnCancelar: document.getElementById("btnCancelarServicio"),
            btnCerrarModal: document.getElementById("btnCloseServiceModal"),
            btnExportar: document.getElementById("btnExportarServicio"),
            btnResetFiltros: document.getElementById("btnResetFiltros"),

            txtBuscar: document.getElementById("searchService"),

            filtroEstado: document.getElementById("filterStatus"),
            filtroPrioridad: document.getElementById("filterPriority"),
            filtroTecnico: document.getElementById("filterTechnician"),

            tbody: document.getElementById("serviceTableBody"),

            totalServicios: document.getElementById("totalServices"),
            mostrando: document.getElementById("showingRecords"),
            paginaActual: document.getElementById("currentPage"),

            btnPaginaAnterior: document.getElementById("btnPreviousPage"),
            btnPaginaSiguiente: document.getElementById("btnNextPage"),

            kpiReceived: document.getElementById("kpiReceived"),
            kpiDiagnosis: document.getElementById("kpiDiagnosis"),
            kpiRepair: document.getElementById("kpiRepair"),
            kpiReady: document.getElementById("kpiReady"),
            kpiDelivered: document.getElementById("kpiDelivered"),
            kpiIncome: document.getElementById("kpiIncome")

        };

        window.servicioController =
            new ServicioTecnicoController(elements);

        try {

            servicioController.init();

            console.log("Controlador Servicio Técnico iniciado");

        } catch (error) {

            console.error(
                "Error al inicializar ServicioTecnicoController:",
                error
            );

        }

    } else {

        console.error(
            "ServicioTecnicoController no está disponible"
        );

    }

});