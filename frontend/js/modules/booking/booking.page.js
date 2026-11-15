import { initBookingEvents } from "./booking.controller.js";
import { fetchBookings } from "./services/booking.service.js";
import { escapeHtml } from "../../utils/html.js";
import { formatCOP } from "../../utils/currency.js";
import { store } from "../../state/store.js";
import {
    todayISO,
    fromISODate,
    formatDate,
    formatLongDate
} from "../../utils/date.js";

/*
 * Mis citas.
 *
 * Lo primero que busca una clienta al entrar es cuándo es su
 * próxima cita: va destacada arriba. Debajo, las demás próximas; el
 * historial, plegado.
 */

const MONTH = new Intl.DateTimeFormat("es-CO", { month: "short" });
const WEEKDAY = new Intl.DateTimeFormat("es-CO", { weekday: "long" });

const hhmm = b => b.hora.slice(0, 5);

function isUpcoming(b) {

    return ["pendiente", "confirmada"].includes(b.estado)
        && new Date(`${b.fecha}T${b.hora}`).getTime() >= Date.now();
}

/**
 * «hoy», «mañana» o «en 4 días».
 */
function relative(fecha) {

    const days = Math.round(
        (fromISODate(fecha) - fromISODate(todayISO())) / 86_400_000
    );

    if (days === 0) return "hoy";
    if (days === 1) return "mañana";

    return `en ${days} días`;
}

function chip(estado) {

    return `
        <span class="agenda-estado agenda-estado--${escapeHtml(estado)}">
            ${escapeHtml(estado)}
        </span>
    `;
}

/**
 * Solo una cita pendiente se puede cancelar desde aquí: una ya
 * confirmada se cambia hablando con el salón.
 */
function cancelButton(b, extraClass = "") {

    if (b.estado !== "pendiente") return "";

    return `
        <button
            type="button"
            class="btn btn-sm btn-outline ${extraClass}"
            data-delete-booking="${escapeHtml(b.id_cita)}"
            aria-label="Cancelar la cita de ${escapeHtml(b.servicio)} del ${escapeHtml(formatLongDate(b.fecha))}"
        >Cancelar…</button>
    `;
}

function NextCard(b) {

    const d = fromISODate(b.fecha);

    return `
        <article class="mis-next" aria-labelledby="mis-next-title">

            <div class="mis-next__date" aria-hidden="true">
                <small>${escapeHtml(MONTH.format(d).replace(".", ""))}</small>
                <b>${d.getDate()}</b>
                <span>${escapeHtml(WEEKDAY.format(d))}</span>
            </div>

            <div class="mis-next__body">
                <p class="mis-next__eyebrow">Tu próxima cita · ${escapeHtml(relative(b.fecha))}</p>
                <h2 id="mis-next-title">${escapeHtml(b.servicio)}</h2>
                <p class="mis-next__when">
                    <span class="sr-only">${escapeHtml(formatLongDate(b.fecha))}, </span>
                    ${escapeHtml(hhmm(b))} · con ${escapeHtml(b.profesional)}
                </p>
                <p class="mis-next__meta">
                    ${chip(b.estado)}
                    <span>${formatCOP(Number(b.precio))} · se paga en el salón</span>
                </p>
                ${b.notas ? `<p class="mis-next__note">«${escapeHtml(b.notas)}»</p>` : ""}
            </div>

            <div class="mis-next__actions">${cancelButton(b)}</div>

        </article>
    `;
}

function UpcomingRow(b) {

    return `
        <li class="mis-row">
            <span class="mis-row__when">
                <b>${escapeHtml(formatDate(b.fecha))}</b>
                <small>${escapeHtml(hhmm(b))} · ${escapeHtml(relative(b.fecha))}</small>
            </span>
            <span class="mis-row__what">
                ${escapeHtml(b.servicio)}
                <small>con ${escapeHtml(b.profesional)}</small>
            </span>
            <span class="mis-row__estado">${chip(b.estado)}</span>
            <span class="mis-row__actions">${cancelButton(b)}</span>
        </li>
    `;
}

function HistoryRow(b) {

    return `
        <li class="mis-row mis-row--past">
            <span class="mis-row__when">
                <b>${escapeHtml(formatDate(b.fecha))}</b>
                <small>${escapeHtml(hhmm(b))}</small>
            </span>
            <span class="mis-row__what">
                ${escapeHtml(b.servicio)}
                <small>con ${escapeHtml(b.profesional)}</small>
            </span>
            <span class="mis-row__estado">${chip(b.estado)}</span>
            <span class="mis-row__actions"></span>
        </li>
    `;
}

export async function BookingPage() {

    const bookings = await fetchBookings();

    initBookingEvents();

    // Llegan ordenadas por fecha y hora ascendente.
    const upcoming = bookings.filter(isUpcoming);
    const history = bookings.filter(b => !isUpcoming(b)).reverse();

    const [next, ...rest] = upcoming;

    const nombre = store.user?.nombre?.split(" ")[0];

    return `

        <section class="clients-page mis-citas">

            <div class="clients-page__header">
                <div>
                    <h1>Mis citas</h1>
                    ${nombre ? `<p>Hola, ${escapeHtml(nombre)}.</p>` : ""}
                </div>
                <a href="#/services" class="btn btn-primary">
                    ${upcoming.length ? "Reservar otra cita" : "Reservar una cita"}
                </a>
            </div>

            ${next
                ? NextCard(next)
                : `
                    <div class="mis-empty">
                        <p>No tienes citas próximas.</p>
                        <a href="#/services" class="btn btn-outline">Ver servicios</a>
                    </div>
                `}

            ${rest.length
                ? `
                    <section class="mis-section" aria-labelledby="mis-t-proximas">
                        <h2 class="mis-section__title" id="mis-t-proximas">Otras próximas</h2>
                        <ol class="mis-list">${rest.map(UpcomingRow).join("")}</ol>
                    </section>
                `
                : ""}

            ${history.length
                ? `
                    <details class="mis-history">
                        <summary>
                            Historial · ${history.length === 1 ? "1 cita" : `${history.length} citas`}
                        </summary>
                        <ol class="mis-list">${history.map(HistoryRow).join("")}</ol>
                    </details>
                `
                : ""}

        </section>

    `;
}
