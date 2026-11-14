import { escapeHtml } from "../../../../utils/html.js";
import { formatCOP } from "../../../../utils/currency.js";
import { ActionMenu } from "../../../../components/ui/ActionMenu.js";

/**
 * Recorta un texto largo para que la tabla no se desborde.
 */
function shorten(text, limit = 60) {

    if (!text) return "";

    return text.length > limit
        ? text.slice(0, limit).trimEnd() + "…"
        : text;
}

export function ServiceTable(services) {

    if (!services.length) {
        return `
            <div class="clients-empty">
                <p>No hay servicios en el catálogo.</p>
            </div>
        `;
    }

    return `
        <div class="clients-table-wrapper">

            <table class="clients-table">

                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Descripción</th>
                        <th class="num">Duración</th>
                        <th class="num">Precio</th>
                        <th class="actions"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>

                <tbody>

                    ${services.map(service => `
                        <tr>
                            <td data-primary>${escapeHtml(service.name)}</td>
                            <td data-label="Descripción">
                                ${service.description
                                    ? escapeHtml(shorten(service.description))
                                    : `<span class="clients-table__none">Sin descripción</span>`}
                            </td>
                            <td data-label="Duración" class="num">${escapeHtml(service.duration)} min</td>
                            <td data-label="Precio" class="num">${formatCOP(service.price)}</td>
                            <td class="actions">
                                ${ActionMenu([
                                    { label: "Editar", data: { "edit-service": service.id } },
                                    { label: "Retirar del catálogo…", data: { "delete-service": service.id }, danger: true }
                                ], `Acciones para ${service.name}`)}
                            </td>
                        </tr>
                    `).join("")}

                </tbody>

            </table>

        </div>
    `;
}
