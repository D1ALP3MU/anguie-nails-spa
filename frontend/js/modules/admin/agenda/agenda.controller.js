import { changeStatus, cancelAppointment } from "./agenda.api.js";
import {
    registerPayment,
    confirmPayment,
    voidPayment
} from "./payments.api.js";
import { Modal } from "../../../components/ui/Modal.js";
import { PaymentForm } from "./components/PaymentForm.js";
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

    document.addEventListener("submit", handleSubmit);

    registerCleanup(() => {

        document.removeEventListener("click", handleClick);

        document.removeEventListener("submit", handleSubmit);
    });
}

function findAppointment(id) {
    return loaded.find(a => Number(a.id_cita) === Number(id));
}

/**
 * Único punto de entrada de los envíos: la agenda tiene dos
 * formularios y cada manejador filtra por el suyo.
 */
async function handleSubmit(event) {

    await handleFilterSubmit(event);

    await handlePaymentSubmit(event);
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
        return;
    }

    const chargeBtn = event.target.closest("[data-charge-appointment]");

    if (chargeBtn) {
        openPaymentModal(Number(chargeBtn.dataset.chargeAppointment));
        return;
    }

    const confirmPaymentBtn = event.target.closest("[data-confirm-payment]");

    if (confirmPaymentBtn) {
        handleConfirmPayment(Number(confirmPaymentBtn.dataset.confirmPayment));
        return;
    }

    const voidBtn = event.target.closest("[data-void-payment]");

    if (voidBtn) {
        handleVoidPayment(Number(voidBtn.dataset.voidPayment));
        return;
    }

    if (event.target.closest("[data-close-modal]")) {
        closeModal();
        return;
    }

    if (event.target.classList.contains("modal-overlay")) {
        closeModal();
    }
}

/*
|--------------------------------------------------------------------------
| Cobros
|--------------------------------------------------------------------------
*/

function openPaymentModal(appointmentId) {

    if (document.querySelector(".modal-overlay")) return;

    const appointment = findAppointment(appointmentId);

    if (!appointment) return;

    document.body.insertAdjacentHTML(
        "beforeend",
        Modal({
            title: "Registrar cobro",
            content: PaymentForm(appointment)
        })
    );
}

async function handlePaymentSubmit(event) {

    const form = event.target.closest("#payment-form");

    if (!form) return;

    event.preventDefault();

    const data = Object.fromEntries(new FormData(form).entries());

    const button = form.querySelector("button[type='submit']");

    button.disabled = true;

    try {

        await registerPayment(Number(form.dataset.appointmentId), {
            monto: Number(data.monto),
            metodo: data.metodo,
            referencia_pago: data.referencia_pago || null,
            // La casilla solo aparece en el formulario cuando se marca.
            estado: data.pendiente ? "pendiente" : "pagado"
        });

        toastSuccess("Cobro registrado.");

        closeModal();

        await renderRoute();

    } catch (error) {

        console.error("Error al registrar el cobro:", error);

        toastError(error.message || "No fue posible registrar el cobro.");

        button.disabled = false;
    }
}

async function handleConfirmPayment(id) {

    try {

        await confirmPayment(id);

        toastSuccess("Cobro confirmado.");

        await renderRoute();

    } catch (error) {

        console.error("Error al confirmar el cobro:", error);

        toastError(error.message || "No fue posible confirmar el cobro.");
    }
}

async function handleVoidPayment(id) {

    const confirmed = await confirmAction({
        title: "Anular cobro",
        message: "El cobro quedará registrado como anulado y la cita "
            + "volverá a aparecer pendiente de cobro.",
        confirmText: "Anular",
        danger: true
    });

    if (!confirmed) return;

    try {

        await voidPayment(id);

        toastSuccess("Cobro anulado.");

        await renderRoute();

    } catch (error) {

        console.error("Error al anular el cobro:", error);

        toastError(error.message || "No fue posible anular el cobro.");
    }
}

function closeModal() {

    document.querySelector(".modal-overlay")?.remove();
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
