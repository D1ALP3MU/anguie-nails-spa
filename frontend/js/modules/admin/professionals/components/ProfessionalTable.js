import { escapeHtml } from "../../../../utils/html.js";
import { ActionMenu } from "../../../../components/ui/ActionMenu.js";

export function ProfessionalTable(professionals) {

    if (!professionals.length) {
        return `
            <div class="clients-empty">
                <p>No hay profesionales registrados.</p>
            </div>
        `;
    }

    const orNone = (value, text) => value
        ? escapeHtml(value)
        : `<span class="clients-table__none">${text}</span>`;

    return `
        <div class="clients-table-wrapper">

            <table class="clients-table">

                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Especialidad</th>
                        <th>Teléfono</th>
                        <th class="actions"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>

                <tbody>

                    ${professionals.map(professional => `
                        <tr>
                            <td data-primary>${escapeHtml(professional.name)}</td>
                            <td data-label="Especialidad">${orNone(professional.specialty, "Sin especialidad")}</td>
                            <td data-label="Teléfono">${orNone(professional.phone, "Sin teléfono")}</td>
                            <td class="actions">
                                ${ActionMenu([
                                    { label: "Editar", data: { "edit-professional": professional.id } },
                                    { label: "Dar de baja…", data: { "delete-professional": professional.id }, danger: true }
                                ], `Acciones para ${professional.name}`)}
                            </td>
                        </tr>
                    `).join("")}

                </tbody>

            </table>

        </div>
    `;
}
