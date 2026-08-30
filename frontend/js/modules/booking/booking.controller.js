import { removeBooking } from "./services/booking.service.js";

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

    const confirmed = window.confirm(
        "¿Estás seguro de que deseas cancelar esta cita?"
    );

    if (!confirmed) return;

    try {

        await removeBooking(bookingId);

        window.location.reload();

    } catch (error) {

        console.error(
            "Error al cancelar la cita:",
            error
        );

        alert(
            "No fue posible cancelar la cita."
        );

    }

}