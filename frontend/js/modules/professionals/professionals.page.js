import { ProfessionalCard } from "./components/ProfessionalCard.js";
import { getProfessionals } from "./professionals.service.js";

export async function ProfessionalsPage() {

    const professionals = await getProfessionals();

    return `

        <section class="professionals-page">

            <div class="professionals-page__header">

                <h1>
                    Nuestro equipo
                </h1>

                <p>
                    Conoce a nuestros profesionales.
                </p>

            </div>

            <div class="professionals-grid">

                ${professionals.length
            ? professionals
                .map(professional =>
                    ProfessionalCard(professional)
                )
                .join("")
            : `
                            <p>
                                No hay profesionales disponibles.
                            </p>
                        `
        }

            </div>

        </section>

    `;
}