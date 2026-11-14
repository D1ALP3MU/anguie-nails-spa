import { escapeHtml } from "../../../../utils/html.js";
import { ActionMenu } from "../../../../components/ui/ActionMenu.js";

/**
 * Listado de clientas.
 *
 * El nombre lleva al historial: es lo que más se consulta, y como
 * enlace se puede abrir en otra pestaña. Editar y eliminar van en el
 * menú «⋯».
 *
 * Cada celda declara su data-label: por debajo de 720px la tabla se
 * pinta como tarjetas y la etiqueta sustituye al encabezado.
 */
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
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th class="actions"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>

                <tbody>

                    ${clients.map(client => {

                        const id = escapeHtml(client.id);
                        const historial = `#/admin/clients/${id}/historial`;

                        return `
                            <tr>
                                <td data-primary>
                                    <a class="clients-table__link" href="${historial}">
                                        ${escapeHtml(client.name)}
                                    </a>
                                </td>
                                <td data-label="Correo" class="wrap">${escapeHtml(client.email)}</td>
                                <td data-label="Teléfono">${escapeHtml(client.phone)}</td>
                                <td data-label="Dirección">
                                    ${client.address
                                        ? escapeHtml(client.address)
                                        : `<span class="clients-table__none">Sin dirección</span>`}
                                </td>
                                <td class="actions">
                                    ${ActionMenu([
                                        { label: "Ver historial", href: historial },
                                        { label: "Editar", data: { "edit-client": client.id } },
                                        { label: "Eliminar…", data: { "delete-client": client.id }, danger: true }
                                    ], `Acciones para ${client.name}`)}
                                </td>
                            </tr>
                        `;

                    }).join("")}

                </tbody>

            </table>

        </div>
    `;
}
