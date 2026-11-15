import { fetchFreeSlots } from "./reserve.api.js";
import { createBooking } from "../booking/services/booking.service.js";
import {
    ProfessionalPicker,
    DayStrip,
    SlotGrid,
    Summary
} from "./components/ReserveParts.js";
import { registerCleanup } from "../../core/cleanup.js";
import { toastSuccess, toastError } from "../../components/ui/Toast.js";
import { todayISO, addDays, fromISODate, formatLongDate } from "../../utils/date.js";

/*
 * Estado de la reserva en curso.
 *
 * Vive en el módulo mientras la página está abierta; se crea de
 * nuevo cada vez que se entra (createState).
 */
let state = null;

/**
 * Número de la última consulta de horas. Si se cambia de día o de
 * profesional antes de que llegue la respuesta anterior, esa
 * respuesta llega tarde y se descarta: no debe pisar la nueva.
 */
let lastRequest = 0;

const DAYS_PER_PAGE = 7;

export function createState({ service, professionals, schedule }) {

    const today = todayISO();

    state = {
        service,
        professionals,
        schedule,
        // Con una sola profesional no hay nada que elegir.
        professionalId: professionals.length === 1 ? professionals[0].id : null,
        date: firstOpenDay(schedule, today),
        time: null,
        notes: "",
        page: 0,
        slots: { status: "idle" },
        submitting: false
    };

    return state;
}

export function currentState() {
    return state;
}

/* -------------------------------------------------------------------
   Días
   ------------------------------------------------------------------- */

function isoWeekday(iso) {

    // getDay(): 0 = domingo. El backend usa ISO: 7 = domingo.
    return fromISODate(iso).getDay() || 7;
}

function isOpen(schedule, iso) {

    return schedule.dias.includes(isoWeekday(iso));
}

function lastDay(schedule, today) {

    return addDays(today, schedule.antelacion_max_dias);
}

function firstOpenDay(schedule, today) {

    for (let i = 0; i <= schedule.antelacion_max_dias; i++) {

        const day = addDays(today, i);

        if (isOpen(schedule, day)) return day;
    }

    return null;
}

/**
 * La semana visible de la tira de días.
 */
export function visibleDays() {

    const today = todayISO();
    const last = lastDay(state.schedule, today);

    const start = addDays(today, state.page * DAYS_PER_PAGE);

    const days = [];

    for (let i = 0; i < DAYS_PER_PAGE; i++) {

        const date = addDays(start, i);

        // Más allá del plazo se muestra, pero como no disponible.
        days.push({ date, open: date <= last && isOpen(state.schedule, date) });
    }

    return {
        days,
        canPrev: state.page > 0,
        canNext: addDays(start, DAYS_PER_PAGE) <= last
    };
}

/* -------------------------------------------------------------------
   Pintado parcial
   ------------------------------------------------------------------- */

const PARTS = {
    pros: () => ProfessionalPicker(state.professionals, state.professionalId),
    days: () => {
        const { days, canPrev, canNext } = visibleDays();
        return DayStrip(days, state.date, { canPrev, canNext });
    },
    slots: () => SlotGrid(state.slots, state.time),
    summary: () => Summary(state)
};

export function renderPart(name) {

    return PARTS[name]();
}

/**
 * Repinta las partes indicadas, si están en la página.
 *
 * Si el foco estaba dentro de una de ellas, lo devuelve al botón
 * equivalente tras repintar: quien elige con el teclado no debe
 * perder su sitio cada vez que marca una opción.
 */
function paint(...names) {

    const active = document.activeElement;

    const keep = active?.closest("[id^='reserve-']")
        ? [...active.attributes].find(a => a.name.startsWith("data-"))
        : null;

    for (const name of names) {

        const el = document.getElementById(`reserve-${name}`);

        if (el) el.innerHTML = PARTS[name]();
    }

    if (keep && !active.isConnected) {
        document.querySelector(`[${keep.name}="${CSS.escape(keep.value)}"]`)?.focus();
    }
}

/* -------------------------------------------------------------------
   Horas libres
   ------------------------------------------------------------------- */

export async function loadSlots() {

    if (!state.professionalId || !state.date) {
        state.slots = { status: "idle" };
        paint("slots");
        return;
    }

    const request = ++lastRequest;

    state.slots = { status: "loading" };
    paint("slots");

    try {

        const result = await fetchFreeSlots({
            professionalId: state.professionalId,
            serviceId: state.service.id,
            date: state.date
        });

        if (request !== lastRequest) return;

        state.slots = { status: "ready", result };

    } catch (error) {

        if (request !== lastRequest) return;

        state.slots = {
            status: "error",
            message: error.message || "No fue posible consultar las horas libres."
        };
    }

    paint("slots", "summary");
}

/* -------------------------------------------------------------------
   Eventos
   ------------------------------------------------------------------- */

export function initReserveEvents() {

    document.addEventListener("click", handleClick);
    document.addEventListener("input", handleInput);

    registerCleanup(() => {
        document.removeEventListener("click", handleClick);
        document.removeEventListener("input", handleInput);
        // Una respuesta que llegue después de salir no debe pintar nada.
        lastRequest++;
    });
}

function handleClick(event) {

    const pro = event.target.closest("[data-pick-professional]");

    if (pro) {
        state.professionalId = pro.dataset.pickProfessional;
        state.time = null;
        paint("pros", "summary");
        loadSlots();
        return;
    }

    const day = event.target.closest("[data-pick-day]");

    if (day) {
        state.date = day.dataset.pickDay;
        state.time = null;
        paint("days", "summary");
        loadSlots();
        return;
    }

    if (event.target.closest("[data-days-prev]")) {
        state.page = Math.max(0, state.page - 1);
        paint("days");
        return;
    }

    if (event.target.closest("[data-days-next]")) {
        state.page++;
        paint("days");
        return;
    }

    const time = event.target.closest("[data-pick-time]");

    if (time) {
        state.time = time.dataset.pickTime;
        paint("slots", "summary");
        return;
    }

    const noteToggle = event.target.closest("[data-toggle-note]");

    if (noteToggle) {
        const note = document.getElementById("reserve-note");
        note.hidden = false;
        noteToggle.hidden = true;
        note.querySelector("textarea").focus();
        return;
    }

    if (event.target.closest("[data-confirm-booking]")) {
        submit();
    }
}

function handleInput(event) {

    if (event.target.id === "reserve-notes") {
        state.notes = event.target.value;
    }
}

async function submit() {

    const { professionalId, date, time, submitting } = state;

    if (!professionalId || !date || !time || submitting) return;

    state.submitting = true;
    paint("summary");

    try {

        // Sin id_cliente: el backend lo toma del token.
        await createBooking({
            id_servicio: Number(state.service.id),
            id_profesional: Number(professionalId),
            fecha: date,
            hora: time,
            notas: state.notes.trim() || null
        });

        toastSuccess(`Cita reservada para el ${formatLongDate(date)} a las ${time}.`);

        window.location.hash = "#/booking";

    } catch (error) {

        console.error("Error al reservar:", error);

        toastError(error.message || "No fue posible reservar la cita.");

        // Alguien la reservó entre que se mostró y se pidió: se
        // vuelven a pedir las horas para que desaparezca.
        if (error.status === 409) {
            state.time = null;
            loadSlots();
        }

    } finally {

        state.submitting = false;
        paint("summary");
    }
}
