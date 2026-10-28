import { escapeHtml } from "../../../../utils/html.js";

/**
 * Listado de administradores.
 *
 * La fila propia se marca y no ofrece el botón de baja: el backend
 * la rechaza igualmente, pero mostrarla invitaría a un clic que
 * solo puede fallar.
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
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                    ${admins.map(admin => {

                        const soyYo = Number(admin.id) === Number(currentUserId);

                        return `
                            <tr>
                                <td>
                                    ${escapeHtml(admin.name)}
                                    ${soyYo
                                        ? `<span class="admins-yo">tú</span>`
                                        : ""}
                                </td>
                                <td>${escapeHtml(admin.email)}</td>
                                <td>
                                    ${soyYo
                                        ? `<span class="agenda-table__closed">Sin acciones</span>`
                                        : `<button
                                               type="button"
                                               class="btn btn-danger"
                                               data-delete-admin="${escapeHtml(admin.id)}"
                                           >
                                               Desactivar
                                           </button>`}
                                </td>
                            </tr>
                        `;
                    }).join("")}

                </tbody>

            </table>

        </div>
    `;
}
