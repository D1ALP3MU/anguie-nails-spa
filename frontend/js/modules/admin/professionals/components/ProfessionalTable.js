import { escapeHtml } from "../../../../utils/html.js";

export function ProfessionalTable(professionals) {

    if (!professionals.length) {
        return `
            <div class="clients-empty">
                <p>No hay profesionales registrados.</p>
            </div>
        `;
    }

    return `
        <div class="clients-table-wrapper">

            <table class="clients-table">

                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Especialidad</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    ${professionals.map(professional => {

                        const name = escapeHtml(professional.name);

                        const specialty = escapeHtml(
                            professional.specialty ?? "Sin especialidad"
                        );

                        const phone = escapeHtml(
                            professional.phone ?? "Sin teléfono"
                        );

                        return `
                            <tr>
                                <td>${name}</td>
                                <td>${specialty}</td>
                                <td>${phone}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-outline"
                                        data-edit-professional="${escapeHtml(professional.id)}"
                                    >
                                        Editar
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-danger"
                                        data-delete-professional="${escapeHtml(professional.id)}"
                                    >
                                        Dar de baja
                                    </button>
                                </td>
                            </tr>
                        `;

                    }).join("")}

                </tbody>

            </table>

        </div>
    `;
}
