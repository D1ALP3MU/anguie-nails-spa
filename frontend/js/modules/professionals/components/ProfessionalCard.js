export function ProfessionalCard(professional) {

    return `

        <article class="professional-card">

            <div class="professional-card__content">

                <h3>${professional.name}</h3>

                <p>
                    ${professional.specialty ?? "Profesional de belleza"}
                </p>

                ${professional.phone
            ? `<span>${professional.phone}</span>`
            : ""
        }

            </div>

        </article>

    `;
}