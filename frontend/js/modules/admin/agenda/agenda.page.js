import { fetchAgenda } from "./agenda.api.js";
import { getProfessionals } from "../../professionals/professionals.service.js";
import { AgendaToolbar, PeriodSwitch } from "./components/AgendaToolbar.js";
import { AgendaList } from "./components/AgendaList.js";
import { initAgendaEvents, currentView, currentFilters } from "./agenda.controller.js";
import { todayISO, formatLongDate } from "../../../utils/date.js";

export async function AgendaPage() {

    const view = currentView();

    // Las dos peticiones son independientes: se piden a la vez.
    const [appointments, professionals] = await Promise.all([
        fetchAgenda(currentFilters()),
        getProfessionals()
    ]);

    initAgendaEvents(appointments);

    const hoy = formatLongDate(todayISO());

    return `

        <section class="clients-page">

            <div class="clients-page__header">

                <div>
                    <h1>Agenda</h1>
                    <p>${hoy.charAt(0).toUpperCase() + hoy.slice(1)}</p>
                </div>

                ${PeriodSwitch(view.periodo)}

            </div>

            ${AgendaToolbar(professionals, view, appointments)}

            ${AgendaList(appointments, view)}

        </section>

    `;
}
