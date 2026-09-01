import { store } from "../../state/store.js";

export function Navbar() {

    const user = store.user;

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

                    <li>
                        <a href="#/booking">
                            Reservar
                        </a>
                    </li>

                </ul>

                <div class="navbar__actions">

                    ${user
            ? `
                                <span class="navbar__user">
                                    Hola, ${user.nombre}
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