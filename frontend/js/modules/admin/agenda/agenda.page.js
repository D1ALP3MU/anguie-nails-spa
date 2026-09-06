import { fetchAgenda } from "./agenda.api.js";
import { getProfessionals } from "../../professionals/professionals.service.js";
import { AgendaFilters } from "./components/AgendaFilters.js";
import { AgendaTable } from "./components/AgendaTable.js";
import { initAgendaEvents, currentFilters } from "./agenda.controller.js";

export async function AgendaPage() {

    const filters = currentFilters();

    // Las dos peticiones son independientes: se piden a la vez.
    const [appointments, professionals] = await Promise.all([
        fetchAgenda(filters),
        getProfessionals()
    ]);

    initAgendaEvents(appointments);

    return `

        <section class="clients-page">

            <div class="clients-page__header">

                <div>
                    <h1>Agenda</h1>
                    <p>Todas las citas del salón.</p>
                </div>

            </div>

            ${AgendaFilters(professionals, filters)}

            <p class="agenda-resumen">
                ${appointments.length === 1
                    ? "1 cita"
                    : `${appointments.length} citas`}
            </p>

            ${AgendaTable(appointments)}

        </section>

    `;
}
