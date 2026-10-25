import { store } from "../../state/store.js";
import { escapeHtml } from "../../utils/html.js";
import { ROLES } from "../../constants/roles.js";
import { PasswordForm } from "./components/PasswordForm.js";
import { initProfileEvents } from "./profile.controller.js";

/**
 * Mi cuenta: los datos de la sesión y el cambio de contraseña.
 *
 * Los datos salen del token, que ya los trae, así que la página no
 * necesita pedir nada al abrirse.
 */
export async function ProfilePage() {

    const user = store.user;

    const rol = Number(user.id_rol) === ROLES.ADMIN
        ? "Administradora"
        : "Clienta";

    initProfileEvents();

    return `

        <section class="clients-page perfil-page">

            <header class="perfil-encabezado">

                <h1>Mi cuenta</h1>

                <dl class="perfil-datos">

                    <div>
                        <dt>Nombre</dt>
                        <dd>${escapeHtml(user.nombre)}</dd>
                    </div>

                    <div>
                        <dt>Correo</dt>
                        <dd>${escapeHtml(user.email)}</dd>
                    </div>

                    <div>
                        <dt>Perfil</dt>
                        <dd>${rol}</dd>
                    </div>

                </dl>

            </header>

            <section class="perfil-bloque">

                <h2>Cambiar contraseña</h2>

                <p class="perfil-nota">
                    Para cambiarla necesitas escribir la actual.
                </p>

                ${PasswordForm()}

            </section>

        </section>

    `;
}
