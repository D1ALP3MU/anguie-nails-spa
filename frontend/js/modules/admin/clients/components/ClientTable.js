import { escapeHtml } from "../../../../utils/html.js";

export function ClientTable(clients) {

    if (!clients.length) {
        return `
            <div class="clients-empty">
                <p>No hay clientes registrados.</p>
            </div>
        `;
    }

    return `
        <div class="clients-table-wrapper">

            <table class="clients-table">

                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    ${clients.map(client => {

                        const name = escapeHtml(client.name);
                        const email = escapeHtml(client.email);
                        const phone = escapeHtml(client.phone);
                        const address = escapeHtml(
                            client.address ?? "Sin dirección"
                        );

                        return `
                            <tr>
                                <td>${name}</td>
                                <td>${email}</td>
                                <td>${phone}</td>
                                <td>${address}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-outline"
                                        data-edit-client="${client.id}"
                                    >
                                        Editar
                                    </button>

                                    <button
                                        type="button"
                                        class="btn btn-danger"
                                        data-delete-client="${client.id}"
                                    >
                                        Eliminar
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
