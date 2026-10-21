import { fetchSummary, fetchPayments } from "./reports.api.js";
import { SummaryCards } from "./components/SummaryCards.js";
import { BreakdownTable } from "./components/BreakdownTable.js";
import { PaymentsTable } from "./components/PaymentsTable.js";
import { initReportsEvents, currentPeriod } from "./reports.controller.js";
import { escapeHtml } from "../../../utils/html.js";

export async function ReportsPage() {

    const period = currentPeriod();

    // Independientes: se piden a la vez.
    const [summary, payments] = await Promise.all([
        fetchSummary(period),
        fetchPayments(period)
    ]);

    initReportsEvents();

    return `

        <section class="clients-page">

            <div class="clients-page__header">
                <div>
                    <h1>Facturación</h1>
                    <p>Lo cobrado y lo que falta por cobrar.</p>
                </div>
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
                    <button type="button" class="btn btn-outline" data-period="mes">
                        Este mes
                    </button>
                    <button type="button" class="btn btn-outline" data-period="mes-anterior">
                        Mes anterior
                    </button>
                    <button type="button" class="btn btn-outline" data-period="anio">
                        Año
                    </button>
                </div>

            </form>

            ${SummaryCards(summary)}

            ${BreakdownTable(
                "Por forma de pago",
                summary.por_metodo,
                "Forma de pago"
            )}

            ${BreakdownTable(
                "Por profesional",
                summary.por_profesional,
                "Profesional"
            )}

            ${PaymentsTable(payments)}

        </section>

    `;
}
