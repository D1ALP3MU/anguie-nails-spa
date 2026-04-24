import { Modal } from "../../../js/components/ui/Modal.js";
import { BookingForm } from "../booking/components/BookingForm.js";

let initialized = false;

export function initServicesEvents() {

    if (initialized) return;
    initialized = true;

    document.addEventListener("click", (event) => {

        const bookButton = event.target.closest("[data-book-service]");
        if (bookButton) {
            const serviceId = bookButton.dataset.bookService;

            openBookingModal(serviceId);
        }
        
        const closeButton = event.target.closest("[data-close-modal]");
        if (closeButton) {
            closeModal();
        }

        if(event.target.classList.contains("modal-overlay")) {
            closeModal();
        }
    });

}

function openBookingModal(serviceId) {
    const existingModal = document.querySelector(".modal-overlay");
    if (existingModal) return;

    const modalHTML = Modal({title: "Reservar cita", content: BookingForm(serviceId),});

    document.body.insertAdjacentHTML("beforeend", modalHTML);
}

function closeModal() {
    const modal = document.querySelector(".modal-overlay");

    if (modal) {
        modal.remove();
    }
}