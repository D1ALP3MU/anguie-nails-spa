import { store } from "../../../state/store.js";
import { fetchAdmins } from "./admins.api.js";
import { AdminTable } from "./components/AdminTable.js";
import { initAdminsEvents } from "./admins.controller.js";

export async function AdminsPage() {

    const admins = await fetchAdmins();

    initAdminsEvents();

    return `

        <section class="clients-page">

            <div class="clients-page__header">

                <div>
                    <h1>Administradores</h1>
                    <p>Quién tiene acceso al panel del salón.</p>
                </div>

                <button
                    type="button"
                    class="btn btn-primary"
                    data-create-admin
                >
                    Nuevo administrador
                </button>

            </div>

            ${AdminTable(admins, store.user?.id_usuario)}

        </section>

    `;
}
