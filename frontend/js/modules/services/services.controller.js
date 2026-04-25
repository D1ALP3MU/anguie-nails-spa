import { Modal } from "../../../js/components/ui/Modal.js";
import { BookingForm } from "../booking/components/BookingForm.js";
import { isEmpty, isPasteDate } from "../../../js/utils/validation.js";
import { FormError } from "../../components/ui/FormError.js";
import { addBooking, getBookings } from "../../state/booking.store.js";

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

    document.addEventListener("submit", handleBookingSubmit);

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

function handleBookingSubmit(event) {
    const form = event.target;

    if (form.id !== "booking-form") return;

    event.preventDefault();

    clearFormErrors(form);

    const formData = new FormData(form);
    const name = formData.get("name");
    const date = formData.get("date");
    let hasErrors = false;

    // NAME VALIDATION
    if (isEmpty(name)) {
        showFieldError(form.elements.name, "El nombre es obligatorio");
        hasErrors = true;
    }

    //DATE VALIDATION
    if (isEmpty(date)) {
        showFieldError(form.elements.date, "La fecha es obligatoria");
        hasErrors = true;
    } else if (isPasteDate(date)){
        showFieldError(form.elements.date, "No puedes reservar fechas pasadas");
        hasErrors = true;
    }

    if (hasErrors) return;

    // console.log({name, date, serviceId: formData.get("serviceId"),});
    const booking = {
        id: crypto.randomUUID(),
        name,
        date,
        serviceId: formData.get("serviceId"),
        createdAt: new Date().toISOString(),
    };

    addBooking(booking);
    console.log("BOOKING CREATED", booking);
    console.log("CURRENT BOOKINGS", getBookings());   

    alert("Reserva creada correctamente!");

    closeModal();
    
}

function showFieldError(input, message) {
    input.classList.add("input-error");
    input.insertAdjacentHTML("afterend", FormError(message));
}

function clearFormErrors(form) {
    form.querySelectorAll(".form-error").forEach(error => {
        error.remove();
    });

    form.querySelectorAll(".input-error").forEach(input => {
        input.classList.remove("input-error");
    });
}