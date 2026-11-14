import { fetchSummary, fetchPayments } from "./reports.api.js";
import { SummaryCards } from "./components/SummaryCards.js";
import { BreakdownTable } from "./components/BreakdownTable.js";
import { PaymentsTable } from "./components/PaymentsTable.js";
import { initReportsEvents, currentPeriod, activePreset } from "./reports.controller.js";
import { escapeHtml } from "../../../utils/html.js";
import { formatDate } from "../../../utils/date.js";

const PRESETS = [
    ["mes", "Este mes"],
    ["mes-anterior", "Mes anterior"],
    ["anio", "Año"]
];

function PresetSwitch(active) {

    return `
        <div class="segmented" role="group" aria-label="Periodo">
            ${PRESETS.map(([valor, etiqueta]) => `
                <button
                    type="button"
                    class="segmented__option"
                    data-period="${valor}"
                    aria-pressed="${active === valor}"
                >${etiqueta}</button>
            `).join("")}
        </div>
    `;
}

/**
 * Cualquiera de las dos fechas puede llegar vacía si se borra del
 * formulario; formatDate("") lanzaría una excepción.
 */
function rangeText({ desde, hasta }) {

    const f = iso => escapeHtml(formatDate(iso));

    if (desde && hasta) return `entre el ${f(desde)} y el ${f(hasta)}`;
    if (desde) return `desde el ${f(desde)}`;
    if (hasta) return `hasta el ${f(hasta)}`;
    return "en el periodo";
}

export async function ReportsPage() {

    const period = currentPeriod();

    // Independientes: se piden a la vez.
    const [summary, payments] = await Promise.all([
        fetchSummary(period),
        fetchPayments(period)
    ]);

    initReportsEvents();

    // Sin cobros, los cuatro desgloses estarían vacíos: un solo aviso.
    const detalle = payments.length
        ? `
            ${BreakdownTable("Por forma de pago", summary.por_metodo, "Forma de pago")}
            ${BreakdownTable("Por profesional", summary.por_profesional, "Profesional")}
            ${BreakdownTable("Por servicio", summary.por_servicio, "Servicio")}
            ${PaymentsTable(payments)}
        `
        : `
            <p class="reporte-vacio">
                No hay cobros registrados ${rangeText(period)}.
            </p>
        `;

    return `

        <section class="clients-page">

            <div class="clients-page__header">
                <div>
                    <h1>Facturación</h1>
                    <p>Lo cobrado y lo que falta por cobrar.</p>
                </div>

                ${PresetSwitch(activePreset())}
            </div>

            <form class="agenda-filters" id="reporte-periodo">

                <div class="form-group">
                    <label for="reporte-desde">Desde</label>
                    <input type="date" id="reporte-desde" name="desde"
                        value="${escapeHtml(period.desde)}">
                </div>

                <div class="form-group">
                    <label for="reporte-hasta">Hasta</label>
                    <input type="date" id="reporte-hasta" name="hasta"
                        value="${escapeHtml(period.hasta)}">
                </div>

                <div class="agenda-filters__actions">
                    <button type="submit" class="btn btn-primary">Ver</button>
                </div>

            </form>

            ${SummaryCards(summary)}

            ${detalle}

        </section>

    `;
}
