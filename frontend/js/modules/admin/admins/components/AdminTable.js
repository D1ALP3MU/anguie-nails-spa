import { escapeHtml } from "../../../../utils/html.js";
import { ActionMenu } from "../../../../components/ui/ActionMenu.js";

/**
 * Listado de administradores.
 *
 * La fila propia se marca y no ofrece el menú: la baja de una misma
 * la rechaza el backend igualmente, y mostrarla invitaría a un clic
 * que solo puede fallar.
 *
 * De paso eso cubre el caso del último administrador —si solo
 * queda uno, ese uno eres tú—, así que esa otra regla no se
 * alcanza desde aquí. Sigue viva en la API, que es la frontera
 * de verdad.
 */
export function AdminTable(admins, currentUserId) {

    if (!admins.length) {
        return `
            <div class="clients-empty">
                <p>No hay administradores registrados.</p>
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
                        <th class="actions"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>

                <tbody>

                    ${admins.map(admin => {

                        const soyYo = Number(admin.id) === Number(currentUserId);

                        return `
                            <tr>
                                <td data-primary>
                                    ${escapeHtml(admin.name)}
                                    ${soyYo
                                        ? `<span class="admins-yo">tú</span>`
                                        : ""}
                                </td>
                                <td data-label="Correo" class="wrap">${escapeHtml(admin.email)}</td>
                                <td class="actions">
                                    ${soyYo
                                        ? ""
                                        : ActionMenu([
                                            { label: "Desactivar…", data: { "delete-admin": admin.id }, danger: true }
                                        ], `Acciones para ${admin.name}`)}
                                </td>
                            </tr>
                        `;
                    }).join("")}

                </tbody>

            </table>

        </div>
    `;
}
