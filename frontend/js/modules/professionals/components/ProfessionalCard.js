import { escapeHtml } from "../../../utils/html.js";

export function ProfessionalCard(professional) {

    return `

        <article class="professional-card">

            <div class="professional-card__content">

                <h3>${escapeHtml(professional.name)}</h3>

                <p>
                    ${escapeHtml(professional.specialty ?? "Profesional de belleza")}
                </p>

                ${professional.phone
            ? `<span>${escapeHtml(professional.phone)}</span>`
            : ""
        }

            </div>

        </article>

    `;
}
