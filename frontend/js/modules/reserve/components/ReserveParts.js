import { escapeHtml } from "../../../utils/html.js";
import { formatCOP } from "../../../utils/currency.js";
import { fromISODate, formatLongDate } from "../../../utils/date.js";

/*
 * Piezas de la página de reserva.
 *
 * Son funciones puras del estado: el controlador repinta solo la
 * que cambia —elegir una hora no vuelve a pedir las profesionales—.
 */

const WEEKDAY = new Intl.DateTimeFormat("es-CO", { weekday: "short" });
const MONTH = new Intl.DateTimeFormat("es-CO", { month: "long" });

function initials(name) {

    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map(word => word[0].toUpperCase())
        .join("");
}

/**
 * «Mié 18 nov · 10:30».
 */
export function shortWhen(date, time) {

    const d = fromISODate(date);

    const day = WEEKDAY.format(d).replace(".", "");
    const month = new Intl.DateTimeFormat("es-CO", { month: "short" }).format(d).replace(".", "");

    return `${day.charAt(0).toUpperCase() + day.slice(1)} ${d.getDate()} ${month} · ${time}`;
}

/* -------------------------------------------------------------------
   Profesional
   ------------------------------------------------------------------- */

export function ProfessionalPicker(professionals, selectedId) {

    if (!professionals.length) {
        return `<p class="reserve-hint">Ahora mismo no hay profesionales disponibles.</p>`;
    }

    return `
        <div class="reserve-pros" role="radiogroup" aria-label="Profesional">
            ${professionals.map(p => {

                const on = String(p.id) === String(selectedId);

                return `
                    <button
                        type="button"
                        class="reserve-pro${on ? " is-on" : ""}"
                        role="radio"
                        aria-checked="${on}"
                        data-pick-professional="${escapeHtml(p.id)}"
                    >
                        <span class="reserve-pro__avatar" aria-hidden="true">${escapeHtml(initials(p.name))}</span>
                        <span>
                            ${escapeHtml(p.name)}
                            ${p.specialty ? `<small>${escapeHtml(p.specialty)}</small>` : ""}
                        </span>
                    </button>
                `;
            }).join("")}
        </div>
    `;
}

/* -------------------------------------------------------------------
   Día
   ------------------------------------------------------------------- */

/**
 * @param {Array<{date: string, open: boolean}>} days Una semana.
 */
export function DayStrip(days, selectedDate, { canPrev, canNext }) {

    const first = fromISODate(days[0].date);
    const last = fromISODate(days.at(-1).date);

    const months = MONTH.format(first) === MONTH.format(last)
        ? MONTH.format(first)
        : `${MONTH.format(first)} – ${MONTH.format(last)}`;

    return `
        <div class="reserve-days__head">
            <span class="reserve-days__month">${escapeHtml(months)}</span>
            <span class="reserve-days__nav">
                <button type="button" class="reserve-days__arrow" data-days-prev
                    aria-label="Semana anterior" ${canPrev ? "" : "disabled"}>‹</button>
                <button type="button" class="reserve-days__arrow" data-days-next
                    aria-label="Semana siguiente" ${canNext ? "" : "disabled"}>›</button>
            </span>
        </div>

        <div class="reserve-days" role="radiogroup" aria-label="Día">
            ${days.map(({ date, open }) => {

                const d = fromISODate(date);
                const on = date === selectedDate;
                const label = formatLongDate(date) + (open ? "" : " · cerrado");

                return `
                    <button
                        type="button"
                        class="reserve-day${on ? " is-on" : ""}"
                        role="radio"
                        aria-checked="${on}"
                        aria-label="${escapeHtml(label)}"
                        data-pick-day="${date}"
                        ${open ? "" : "disabled"}
                    >
                        <small>${escapeHtml(WEEKDAY.format(d).replace(".", ""))}</small>
                        <b>${d.getDate()}</b>
                    </button>
                `;
            }).join("")}
        </div>
    `;
}

/* -------------------------------------------------------------------
   Hora
   ------------------------------------------------------------------- */

/**
 * @param {object} slots Estado de las horas:
 *   { status: "idle"|"loading"|"ready"|"error", result?, message? }
 */
export function SlotGrid(slots, selectedTime) {

    if (slots.status === "idle") {
        return `<p class="reserve-hint">Elige una profesional para ver las horas libres.</p>`;
    }

    if (slots.status === "loading") {
        return `<p class="reserve-hint" aria-busy="true">Buscando horas libres…</p>`;
    }

    if (slots.status === "error") {
        return `<p class="reserve-hint">${escapeHtml(slots.message)}</p>`;
    }

    const { abierto, horas } = slots.result;

    if (!abierto) {
        return `<p class="reserve-hint">El salón no abre ese día.</p>`;
    }

    if (!horas.length) {
        return `<p class="reserve-hint">No quedan horas libres este día. Prueba con otro.</p>`;
    }

    const group = (title, list) => list.length
        ? `
            <h3 class="reserve-slots__title">${title}</h3>
            <div class="reserve-slots" role="radiogroup" aria-label="Hora, ${title.toLowerCase()}">
                ${list.map(h => {
                    const on = h === selectedTime;
                    return `
                        <button
                            type="button"
                            class="reserve-slot${on ? " is-on" : ""}"
                            role="radio"
                            aria-checked="${on}"
                            data-pick-time="${h}"
                        >${h}</button>
                    `;
                }).join("")}
            </div>
        `
        : "";

    return group("Mañana", horas.filter(h => h < "12:00"))
        + group("Tarde", horas.filter(h => h >= "12:00"));
}

/* -------------------------------------------------------------------
   Resumen
   ------------------------------------------------------------------- */

export function Summary(state) {

    const { service, professionals, professionalId, date, time, submitting } = state;

    const professional = professionals.find(p => String(p.id) === String(professionalId));

    const ready = professional && date && time;

    const when = date && time ? shortWhen(date, time) : "Elige día y hora";

    return `
        <div class="reserve-summary__full">
            <h2>${escapeHtml(service.name)}</h2>
            ${service.description
                ? `<p class="reserve-summary__desc">${escapeHtml(service.description)}</p>`
                : ""}

            <dl>
                <dt>Duración</dt><dd>${service.duration} min</dd>
                <dt>Con</dt><dd>${professional ? escapeHtml(professional.name) : "—"}</dd>
                <dt>Cuándo</dt><dd>${date && time ? escapeHtml(when) : "—"}</dd>
                <dt>Precio</dt><dd>${formatCOP(service.price)}</dd>
            </dl>
        </div>

        <p class="reserve-summary__compact" aria-hidden="true">
            <b>${escapeHtml(when)}</b>
            ${professional ? ` con ${escapeHtml(professional.name)}` : ""}
        </p>

        <button
            type="button"
            class="btn btn-primary"
            data-confirm-booking
            ${ready && !submitting ? "" : "disabled"}
        >${submitting ? "Reservando…" : "Confirmar cita"}</button>

        <p class="reserve-summary__fine">
            Se paga en el salón. Puedes cancelarla mientras esté pendiente.
        </p>
    `;
}
