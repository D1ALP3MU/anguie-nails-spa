import { escapeHtml } from "../../../../utils/html.js";
import { formatCOP } from "../../../../utils/currency.js";
import {
    todayISO,
    addDays,
    formatLongDate,
    formatShortDate,
    nowHHMM
} from "../../../../utils/date.js";
import { ActionMenu } from "../../../../components/ui/ActionMenu.js";

/*
 * Agenda agrupada por día.
 *
 * Cada fila muestra una sola acción, la siguiente que toca en la vida
 * de la cita —confirmar, completar, cobrar—, y el resto en el menú
 * «⋯». Las reglas de qué se permite son las de antes; solo cambia
 * dónde aparece cada botón.
 */

function hhmm(appointment) {

    return appointment.hora.slice(0, 5);
}

/**
 * Una cita solo se confirma si todavía no ha ocurrido: el backend
 * rechaza pasar a «confirmada» un horario que ya pasó.
 */
function isUpcoming(appointment) {

    const when = new Date(`${appointment.fecha}T${appointment.hora}`);

    return when.getTime() >= Date.now();
}

const isOpen = a => a.estado === "pendiente" || a.estado === "confirmada";

/**
 * Cerrada y sin nada pendiente: cancelada, o completada y cobrada.
 */
function needsNothing(a) {

    if (a.estado === "cancelada") return true;

    return a.estado === "completada" && a.id_pago && a.estado_pago === "pagado";
}

/**
 * Todas las acciones posibles para una cita, en orden de prioridad.
 * La primera es la que se muestra como botón.
 */
function actionsFor(a) {

    const id = a.id_cita;

    const actions = [];

    if (a.estado === "completada" && !a.id_pago) {
        actions.push({ label: "Cobrar", data: { "charge-appointment": id }, primary: true });
    }

    if (a.id_pago && a.estado_pago === "pendiente") {
        actions.push({ label: "Confirmar pago", data: { "confirm-payment": a.id_pago } });
    }

    if (a.estado === "pendiente" && isUpcoming(a)) {
        actions.push({ label: "Confirmar", data: { "confirm-appointment": id } });
    }

    if (isOpen(a)) {
        actions.push({ label: "Completar", data: { "complete-appointment": id } });
    }

    // Se puede cobrar por adelantado: queda en el menú.
    if (isOpen(a) && !a.id_pago) {
        actions.push({ label: "Cobrar", data: { "charge-appointment": id } });
    }

    actions.push({
        label: "Ver historial de la clienta",
        href: `#/admin/clients/${a.id_cliente}/historial`
    });

    if (a.id_pago) {
        actions.push({ label: "Anular cobro…", data: { "void-payment": a.id_pago }, danger: true });
    }

    if (isOpen(a)) {
        actions.push({ label: "Cancelar cita…", data: { "cancel-appointment": id }, danger: true });
    }

    return actions;
}

function primaryButton(action) {

    const data = Object.entries(action.data)
        .map(([key, value]) => `data-${key}="${escapeHtml(value)}"`)
        .join(" ");

    return `
        <button
            type="button"
            class="btn btn-sm ${action.primary ? "btn-primary" : "btn-outline"}"
            ${data}
        >${escapeHtml(action.label)}</button>
    `;
}

function moneyCell(a) {

    if (a.estado === "cancelada" && !a.id_pago) {
        return `<span class="agenda-row__none">—</span>`;
    }

    if (a.id_pago) {

        const pagado = a.estado_pago === "pagado";

        return `
            <span class="${pagado ? "agenda-row__paid" : "agenda-row__verify"}">
                ${formatCOP(Number(a.monto_pago))}
            </span>
            <small>${pagado ? "pagado" : "por verificar"} · ${escapeHtml(a.metodo_pago)}</small>
        `;
    }

    return `
        ${formatCOP(Number(a.precio))}
        <small>sin cobrar</small>
    `;
}

function Row(a) {

    const actions = actionsFor(a);

    // Los enlaces no compiten por el botón principal: si lo único que
    // hay es «Ver historial», va en el menú.
    const first = actions[0]?.href ? null : actions[0];
    const rest = first ? actions.slice(1) : actions;

    const cliente = escapeHtml(a.cliente);

    return `
        <li class="agenda-row agenda-row--${escapeHtml(a.estado)}">

            <span class="agenda-row__time">${escapeHtml(hhmm(a))}</span>

            <span class="agenda-row__who">
                <strong>${cliente}</strong>
                <span>${escapeHtml(a.servicio)}</span>
            </span>

            <span class="agenda-row__pro">${escapeHtml(a.profesional)}</span>

            <span class="agenda-row__estado">
                <span class="agenda-estado agenda-estado--${escapeHtml(a.estado)}">
                    ${escapeHtml(a.estado)}
                </span>
            </span>

            <span class="agenda-row__money">${moneyCell(a)}</span>

            ${/*
                Sin espacios dentro: en móvil, si no hay botón, la celda
                queda :empty y su fila de la tarjeta desaparece.
              */ ""}
            <span class="agenda-row__actions">${first ? primaryButton(first).trim() : ""}</span>

            <span class="agenda-row__menu">
                ${ActionMenu(rest, `Más acciones para ${a.cliente}, ${hhmm(a)}`)}
            </span>

        </li>
    `;
}

function NowLine() {

    return `
        <li class="agenda-now" aria-label="Hora actual">
            <span>${nowHHMM()}</span>
        </li>
    `;
}

/**
 * Filas de hoy: lo ya cerrado antes de esta hora se pliega, y una
 * línea marca el momento actual entre las que quedan.
 */
function TodayRows(appointments) {

    const now = nowHHMM();

    const folded = appointments.filter(a => needsNothing(a) && hhmm(a) <= now);
    const visible = appointments.filter(a => !folded.includes(a));

    let rows = "";
    let nowPlaced = false;

    for (const a of visible) {

        if (!nowPlaced && hhmm(a) > now) {
            rows += NowLine();
            nowPlaced = true;
        }

        rows += Row(a);
    }

    if (!nowPlaced) rows += NowLine();

    const foldedBlock = folded.length
        ? `
            <details class="agenda-folded">
                <summary>
                    ${folded.length === 1
                        ? "1 cita ya cerrada hoy"
                        : `${folded.length} citas ya cerradas hoy`}
                </summary>
                <ol class="agenda-day__list">
                    ${folded.map(Row).join("")}
                </ol>
            </details>
        `
        : "";

    return `
        ${foldedBlock}
        <ol class="agenda-day__list">${rows}</ol>
    `;
}

function dayTitle(fecha) {

    const hoy = todayISO();

    const relative = {
        [hoy]: "Hoy",
        [addDays(hoy, 1)]: "Mañana",
        [addDays(hoy, -1)]: "Ayer"
    }[fecha];

    const long = formatLongDate(fecha);

    return relative
        ? { title: relative, detail: long }
        : { title: long.charAt(0).toUpperCase() + long.slice(1), detail: formatShortDate(fecha) };
}

function Day(fecha, appointments) {

    const { title, detail } = dayTitle(fecha);

    const count = appointments.length === 1 ? "1 cita" : `${appointments.length} citas`;

    const body = fecha === todayISO()
        ? TodayRows(appointments)
        : `<ol class="agenda-day__list">${appointments.map(Row).join("")}</ol>`;

    return `
        <section class="agenda-day">
            <h2 class="agenda-day__title">
                ${escapeHtml(title)}
                <span>${escapeHtml(detail)} · ${count}</span>
            </h2>
            ${body}
        </section>
    `;
}

function Empty(view) {

    const sinFiltros = !view.id_profesional && !view.estado;

    if (view.periodo === "hoy" && sinFiltros) {
        return `
            <div class="agenda-empty">
                <p>No hay citas para hoy.</p>
                <button type="button" class="btn btn-sm btn-outline" data-periodo="semana">
                    Ver los próximos 7 días
                </button>
            </div>
        `;
    }

    return `
        <div class="agenda-empty">
            <p>No hay citas que coincidan.</p>
            <button type="button" class="btn btn-sm btn-outline" data-clear-filters>
                Quitar filtros
            </button>
        </div>
    `;
}

/**
 * @param {object[]} appointments Ordenadas por fecha y hora (así llegan).
 * @param {object} view Periodo y filtros activos.
 */
export function AgendaList(appointments, view) {

    if (!appointments.length) return Empty(view);

    const byDay = new Map();

    for (const a of appointments) {

        if (!byDay.has(a.fecha)) byDay.set(a.fecha, []);

        byDay.get(a.fecha).push(a);
    }

    return [...byDay]
        .map(([fecha, list]) => Day(fecha, list))
        .join("");
}
