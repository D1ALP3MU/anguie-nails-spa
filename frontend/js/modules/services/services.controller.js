import { Modal } from "../../../js/components/ui/Modal.js";
import { BookingForm } from "../booking/components/BookingForm.js";
import { isEmpty, isPasteDate } from "../../../js/utils/validation.js";
import { FormError } from "../../components/ui/FormError.js";
import { createBooking } from "../booking/services/booking.service.js";
import { registerCleanup } from "../../core/cleanup.js";
import { getProfessionals } from "../professionals/professionals.service.js";
import { createClient } from "../admin/clients/clients.api.js";

export function initServicesEvents() {

    document.addEventListener(
        "click",
        handleDocumentClick
    );

    document.addEventListener(
        "submit",
        handleBookingSubmit
    );

    registerCleanup(() => {

        document.removeEventListener(
            "click",
            handleDocumentClick
        );

        document.removeEventListener(
            "submit",
            handleBookingSubmit
        );

    });

}

async function handleDocumentClick(event) {

    const bookButton = event.target.closest(

        "[data-book-service]"

    );

    if (bookButton) {

        const serviceId = bookButton.dataset.bookService;

        await openBookingModal(serviceId);

    }

    const closeButton = event.target.closest("[data-close-modal]");

    if (closeButton) {

        closeModal();

    }

    if (event.target.classList.contains("modal-overlay")) {

        closeModal();

    }
}

async function openBookingModal(serviceId) {

    const existingModal = document.querySelector(".modal-overlay");

    if (existingModal) return;

    const professionals = await getProfessionals();

    const modalHTML = Modal({
        title: "Reservar cita",
        content: BookingForm(
            serviceId,
            professionals
        ),
    });

    document.body.insertAdjacentHTML("beforeend", modalHTML);
}

function closeModal() {
    const modal = document.querySelector(".modal-overlay");

    if (modal) {
        modal.remove();
    }
}

async function handleBookingSubmit(event) {

    const form = event.target;

    if (form.id !== "booking-form") return;

    event.preventDefault();

    clearFormErrors(form);

    const formData = new FormData(form);

    const name = formData.get("name");
    const email = formData.get("email");
    const phone = formData.get("phone");
    const professionalId = formData.get("professionalId");
    const date = formData.get("date");
    const time = formData.get("time");
    const notes = formData.get("notes");
    const serviceId = formData.get("serviceId");

    let hasErrors = false;

    if (isEmpty(name)) {
        showFieldError(
            form.elements.name,
            "El nombre es obligatorio"
        );

        hasErrors = true;
    }

    if (isEmpty(email)) {
        showFieldError(
            form.elements.email,
            "El correo electrónico es obligatorio"
        );

        hasErrors = true;
    }

    if (isEmpty(phone)) {
        showFieldError(
            form.elements.phone,
            "El teléfono es obligatorio"
        );

        hasErrors = true;
    }

    if (isEmpty(professionalId)) {
        showFieldError(
            form.elements.professionalId,
            "Debes seleccionar un profesional"
        );

        hasErrors = true;
    }

    if (isEmpty(date)) {

        showFieldError(
            form.elements.date,
            "La fecha es obligatoria"
        );

        hasErrors = true;

    } else if (isPasteDate(date)) {

        showFieldError(
            form.elements.date,
            "No puedes reservar fechas pasadas"
        );

        hasErrors = true;
    }

    if (isEmpty(time)) {

        showFieldError(
            form.elements.time,
            "La hora es obligatoria"
        );

        hasErrors = true;
    }

    if (hasErrors) return;

    try {

        const password = crypto.randomUUID();

        const client = await createClient({
            nombre: name,
            email,
            password,
            telefono: phone
        });

        const booking = await createBooking({
            id_cliente: client.id_cliente,
            id_servicio: Number(serviceId),
            id_profesional: Number(professionalId),
            fecha: date,
            hora: time,
            estado: "pendiente",
            notas: notes || null
        });

        console.log("CLIENT CREATED", client);
        console.log("BOOKING CREATED", booking);

        alert("Cita creada correctamente.");

        closeModal();

    } catch (error) {

        console.error("Error al crear la cita:", error);

        alert(
            error.message ||
            "No fue posible crear la cita."
        );
    }
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