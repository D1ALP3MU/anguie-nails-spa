import { changeStatus, cancelAppointment } from "./agenda.api.js";
import {
    registerPayment,
    confirmPayment,
    voidPayment
} from "./payments.api.js";
import { openModal, closeModal, isModalOpen } from "../../../components/ui/Modal.js";
import { PaymentForm } from "./components/PaymentForm.js";
import { registerCleanup } from "../../../core/cleanup.js";
import { renderRoute } from "../../../router/router.js";
import { toastSuccess, toastError } from "../../../components/ui/Toast.js";
import { confirmAction } from "../../../components/ui/ConfirmDialog.js";
import { todayISO, addDays } from "../../../utils/date.js";

/**
 * Periodo y filtros activos.
 *
 * Viven en el módulo y no en la URL para no complicar el enrutado
 * por hash, que ya usa el fragmento para la ruta. La contrapartida
 * es que no se pueden compartir por enlace ni sobreviven a una
 * recarga completa.
 *
 * Por defecto, hoy: es lo que se mira al abrir la agenda. Antes
 * abría con todo el histórico, empezando por lo más antiguo.
 */
const DEFAULT_VIEW = {
    periodo: "hoy",       // hoy | semana | todas | fechas
    desde: "",            // solo con periodo «fechas»
    hasta: "",
    id_profesional: "",
    estado: ""
};

let view = { ...DEFAULT_VIEW };

/**
 * Citas del último render, para poder reenviarlas completas al
 * cambiar su estado sin volver a pedirlas una por una.
 */
let loaded = [];

export function currentView() {
    return { ...view };
}

/**
 * Lo que se pide a la API: el periodo traducido a fechas.
 */
export function currentFilters() {

    const hoy = todayISO();

    const range = {
        hoy: { desde: hoy, hasta: hoy },
        semana: { desde: hoy, hasta: addDays(hoy, 6) },
        todas: {},
        fechas: { desde: view.desde, hasta: view.hasta }
    }[view.periodo];

    return {
        ...range,
        id_profesional: view.id_profesional,
        estado: view.estado
    };
}

export function initAgendaEvents(appointments) {

    loaded = appointments;

    document.addEventListener("click", handleClick);

    document.addEventListener("submit", handleSubmit);

    document.addEventListener("change", handleFilterChange);

    registerCleanup(() => {

        document.removeEventListener("click", handleClick);

        document.removeEventListener("submit", handleSubmit);

        document.removeEventListener("change", handleFilterChange);
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

    await handleDatesSubmit(event);

    await handlePaymentSubmit(event);
}

/**
 * Los selectores de profesional y estado se aplican al cambiar.
 */
function handleFilterChange(event) {

    const select = event.target.closest("#agenda-filtros select");

    if (!select) return;

    view = { ...view, [select.name]: select.value };

    renderRoute();
}

async function handleDatesSubmit(event) {

    const form = event.target.closest("#agenda-fechas");

    if (!form) return;

    event.preventDefault();

    const { desde, hasta } = Object.fromEntries(new FormData(form).entries());

    if (!desde && !hasta) {
        toastError("Elige al menos una fecha.");
        return;
    }

    // Las cadenas AAAA-MM-DD se ordenan igual que las fechas.
    if (desde && hasta && desde > hasta) {
        toastError("La fecha inicial no puede ser posterior a la final.");
        return;
    }

    view = { ...view, periodo: "fechas", desde, hasta };

    await renderRoute();
}

/**
 * Despliega u oculta las fechas a medida. No repinta: solo muestra
 * un formulario que ya está en la página.
 */
function toggleDates(button) {

    const form = document.getElementById("agenda-fechas");

    form.hidden = !form.hidden;

    button.setAttribute("aria-expanded", String(!form.hidden));

    if (!form.hidden) form.querySelector("input").focus();
}

function handleClick(event) {

    const periodBtn = event.target.closest("[data-periodo]");

    if (periodBtn) {
        view = { ...view, periodo: periodBtn.dataset.periodo, desde: "", hasta: "" };
        renderRoute();
        return;
    }

    const datesBtn = event.target.closest("[data-toggle-fechas]");

    if (datesBtn) {
        toggleDates(datesBtn);
        return;
    }

    if (event.target.closest("[data-clear-filters]")) {
        view = { ...DEFAULT_VIEW };
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
    }
}

/*
|--------------------------------------------------------------------------
| Cobros
|--------------------------------------------------------------------------
*/

function openPaymentModal(appointmentId) {

    if (isModalOpen()) return;

    const appointment = findAppointment(appointmentId);

    if (!appointment) return;

    openModal("Registrar cobro", PaymentForm(appointment));
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
