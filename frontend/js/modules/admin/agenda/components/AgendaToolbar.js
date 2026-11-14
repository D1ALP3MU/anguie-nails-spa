import { escapeHtml } from "../../../../utils/html.js";
import { formatShortDate } from "../../../../utils/date.js";

const PERIODOS = [
    ["hoy", "Hoy"],
    ["semana", "Próximos 7 días"],
    ["todas", "Todas"]
];

const ESTADOS = ["pendiente", "confirmada", "completada", "cancelada"];

/**
 * Selector del periodo: va en el encabezado, a la derecha del título.
 *
 * Con un rango de fechas a medida ninguna opción queda marcada: el
 * rango se ve en el botón «Elegir fechas» de la barra.
 */
export function PeriodSwitch(periodo) {

    return `
        <div class="segmented" role="group" aria-label="Periodo">
            ${PERIODOS.map(([valor, etiqueta]) => `
                <button
                    type="button"
                    class="segmented__option"
                    data-periodo="${valor}"
                    aria-pressed="${periodo === valor}"
                >${etiqueta}</button>
            `).join("")}
        </div>
    `;
}

function rangeLabel(view) {

    if (view.periodo !== "fechas") return "Elegir fechas…";

    const { desde, hasta } = view;

    if (desde && hasta) return `${formatShortDate(desde)} – ${formatShortDate(hasta)}`;

    return desde
        ? `Desde el ${formatShortDate(desde)}`
        : `Hasta el ${formatShortDate(hasta)}`;
}

/**
 * Filtros y resumen.
 *
 * Los selectores se aplican al cambiar, sin botón «Filtrar». Las
 * fechas a medida van plegadas: se usan poco y ocupaban media barra.
 */
export function AgendaToolbar(professionals, view, appointments) {

    const selected = (a, b) => String(a) === String(b) ? "selected" : "";

    const sinCobrar = appointments.filter(
        a => a.estado !== "cancelada" && !a.id_pago
    ).length;

    const total = appointments.length;

    return `
        <div class="agenda-toolbar">

            <form class="agenda-toolbar__filters" id="agenda-filtros">

                <select name="id_profesional" aria-label="Profesional">
                    <option value="">Todas las profesionales</option>
                    ${professionals.map(p => `
                        <option value="${escapeHtml(p.id)}" ${selected(p.id, view.id_profesional)}>
                            ${escapeHtml(p.name)}
                        </option>
                    `).join("")}
                </select>

                <select name="estado" aria-label="Estado">
                    <option value="">Todos los estados</option>
                    ${ESTADOS.map(e => `
                        <option value="${e}" ${selected(e, view.estado)}>
                            ${e.charAt(0).toUpperCase() + e.slice(1)}
                        </option>
                    `).join("")}
                </select>

                <button
                    type="button"
                    class="btn btn-sm ${view.periodo === "fechas" ? "btn-primary" : "btn-outline"}"
                    data-toggle-fechas
                    aria-expanded="false"
                    aria-controls="agenda-fechas"
                >${escapeHtml(rangeLabel(view))}</button>

            </form>

            <p class="agenda-toolbar__count" aria-live="polite">
                ${total === 1 ? "1 cita" : `${total} citas`}
                ${sinCobrar ? ` · ${sinCobrar} sin cobrar` : ""}
            </p>

            <form class="agenda-fechas" id="agenda-fechas" hidden>

                <div class="form-group">
                    <label for="agenda-desde">Desde</label>
                    <input type="date" id="agenda-desde" name="desde"
                        value="${escapeHtml(view.desde)}">
                </div>

                <div class="form-group">
                    <label for="agenda-hasta">Hasta</label>
                    <input type="date" id="agenda-hasta" name="hasta"
                        value="${escapeHtml(view.hasta)}">
                </div>

                <button type="submit" class="btn btn-sm btn-primary">Ver</button>

            </form>

        </div>
    `;
}
