import { changeStatus, cancelAppointment } from "./agenda.api.js";
import { registerCleanup } from "../../../core/cleanup.js";
import { renderRoute } from "../../../router/router.js";
import { toastSuccess, toastError } from "../../../components/ui/Toast.js";
import { confirmAction } from "../../../components/ui/ConfirmDialog.js";

/**
 * Filtros activos.
 *
 * Viven en el módulo y no en la URL para no complicar el enrutado
 * por hash, que ya usa el fragmento para la ruta. La contrapartida
 * es que no se pueden compartir por enlace ni sobreviven a una
 * recarga completa.
 */
let filters = {};

/**
 * Citas del último render, para poder reenviarlas completas al
 * cambiar su estado sin volver a pedirlas una por una.
 */
let loaded = [];

export function currentFilters() {
    return { ...filters };
}

export function initAgendaEvents(appointments) {

    loaded = appointments;

    document.addEventListener("click", handleClick);

    document.addEventListener("submit", handleFilterSubmit);

    registerCleanup(() => {

        document.removeEventListener("click", handleClick);

        document.removeEventListener("submit", handleFilterSubmit);
    });
}

function findAppointment(id) {
    return loaded.find(a => Number(a.id_cita) === Number(id));
}

async function handleFilterSubmit(event) {

    const form = event.target.closest("#agenda-filters");

    if (!form) return;

    event.preventDefault();

    filters = Object.fromEntries(
        new FormData(form).entries()
    );

    await renderRoute();
}

function handleClick(event) {

    if (event.target.closest("[data-clear-filters]")) {
        filters = {};
        renderRoute();
        return;
    }

    const confirmBtn = event.target.closest("[data-confirm-appointment]");

    if (confirmBtn) {
        applyStatus(confirmBtn.dataset.confirmAppointment, "confirmada");
        return;
    }

    const completeBtn = event.target.closest("[data-complete-appointment]");

    if (completeBtn) {
        applyStatus(completeBtn.dataset.completeAppointment, "completada");
        return;
    }

    const cancelBtn = event.target.closest("[data-cancel-appointment]");

    if (cancelBtn) {
        handleCancel(Number(cancelBtn.dataset.cancelAppointment));
    }
}

async function applyStatus(id, estado) {

    const appointment = findAppointment(id);

    if (!appointment) return;

    try {

        await changeStatus(appointment, estado);

        toastSuccess(
            estado === "confirmada"
                ? "Cita confirmada."
                : "Cita marcada como completada."
        );

        await renderRoute();

    } catch (error) {

        console.error("Error al cambiar el estado de la cita:", error);

        toastError(
            error.message || "No fue posible actualizar la cita."
        );
    }
}

async function handleCancel(id) {

    const confirmed = await confirmAction({
        title: "Cancelar cita",
        message: "La clienta perderá su reserva y el horario quedará "
            + "libre para otra persona.",
        confirmText: "Cancelar cita",
        cancelText: "Volver",
        danger: true
    });

    if (!confirmed) return;

    try {

        await cancelAppointment(id);

        toastSuccess("Cita cancelada.");

        await renderRoute();

    } catch (error) {

        console.error("Error al cancelar la cita:", error);

        toastError(
            error.message || "No fue posible cancelar la cita."
        );
    }
}
