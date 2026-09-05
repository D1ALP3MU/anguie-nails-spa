import { removeBooking } from "./services/booking.service.js";
import { toastSuccess, toastError } from "../../components/ui/Toast.js";
import { confirmAction } from "../../components/ui/ConfirmDialog.js";
import { renderRoute } from "../../router/router.js";

let initialized = false; // Variable para rastrear si los eventos ya han sido inicializados

export function initBookingEvents() {

    if (initialized) return; // Evita inicializar los eventos más de una vez

    document.addEventListener(
        "click",
        handleDeleteBooking
    );

    initialized = true; // Marca como inicializado para evitar duplicación de eventos

}

async function handleDeleteBooking(event) {

    const deleteButton = event.target.closest(
        "[data-delete-booking]"
    );

    if (!deleteButton) return;

    const bookingId = deleteButton.dataset.deleteBooking;

    const confirmed = await confirmAction({
        title: "Cancelar cita",
        message: "¿Seguro que deseas cancelar esta cita? El horario quedará libre para otra persona.",
        confirmText: "Sí, cancelar",
        cancelText: "Conservar",
        danger: true
    });

    if (!confirmed) return;

    try {

        await removeBooking(bookingId);

        toastSuccess("Cita cancelada correctamente.");

        await renderRoute();

    } catch (error) {

        console.error(
            "Error al cancelar la cita:",
            error
        );

        toastError(
            error.message ||
            "No fue posible cancelar la cita."
        );

    }

}