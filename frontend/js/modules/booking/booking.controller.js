import { deleteBooking } from "../../state/booking.store.js";

export function initBookingEvents() {

    document.addEventListener("click", handleDeleteBooking);

}

function handleDeleteBooking(event) {

    const deleteButton = event.target.closest("[data-delete-booking]");

    if (!deleteButton) return;

    const bookingId = deleteButton.dataset.deleteBooking;

    deleteBooking(bookingId);

    window.location.reload();
}