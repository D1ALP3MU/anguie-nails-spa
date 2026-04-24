export function Navbar() {

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

                    <button class="btn btn-outline">
                        Iniciar Sesión
                    </button>

                    <button class="btn btn-primary">
                        Registrarse
                    </button>

                </div>
                
            </div> 

        </nav>

    `;
}