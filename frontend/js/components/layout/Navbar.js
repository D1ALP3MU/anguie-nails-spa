import { store } from "../../state/store.js";
import { escapeHtml } from "../../utils/html.js";
import { ROLES } from "../../constants/roles.js";

export function Navbar() {

    const user = store.user;

    const isAdmin = Number(user?.id_rol) === ROLES.ADMIN;

    return `

        <nav class="navbar">

            <div class="navbar__container">

                <a href="#/" class="navbar__logo">
                    Anguie Nails
                </a>

                <ul class="navbar__menu">

                    <li>
                        <a href="#/">
                            Inicio
                        </a>
                    </li>

                    <li>
                        <a href="#/services">
                            Servicios
                        </a>
                    </li>

                    ${user
            ? `
                                <li>
                                    <a href="#/booking">
                                        Mis citas
                                    </a>
                                </li>
                            `
            : ""
        }

                    ${isAdmin
            ? `
                                <li>
                                    <a href="#/clients">
                                        Clientes
                                    </a>
                                </li>
                            `
            : ""
        }

                </ul>

                <div class="navbar__actions">

                    ${user
            ? `
                                <span class="navbar__user">
                                    Hola, ${escapeHtml(user.nombre)}
                                </span>

                                <button
                                    class="btn btn-outline"
                                    data-action="logout"
                                >
                                    Cerrar sesión
                                </button>
                            `
            : `
                                <button
                                    class="btn btn-outline"
                                    data-action="login"
                                >
                                    Iniciar Sesión
                                </button>

                                <button
                                    class="btn btn-primary"
                                    data-action="register"
                                >
                                    Registrarse
                                </button>
                            `
        }

                </div>

            </div>

        </nav>

    `;
}
