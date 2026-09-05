import { fetchProfessionals } from "./professionals.api.js";
import { ProfessionalTable } from "./components/ProfessionalTable.js";
import { initProfessionalsAdminEvents } from "./professionals.controller.js";

export async function ProfessionalsAdminPage() {

    const professionals = await fetchProfessionals();

    initProfessionalsAdminEvents();

    return `

        <section class="clients-page">

            <div class="clients-page__header">

                <div>
                    <h1>Equipo</h1>
                    <p>Gestiona las profesionales del salón.</p>
                </div>

                <button
                    type="button"
                    class="btn btn-primary"
                    data-create-professional
                >
                    Nueva profesional
                </button>

            </div>

            ${ProfessionalTable(professionals)}

        </section>

    `;
}
