import {
    initAuthEvents
} from "./auth.controller.js";

import {
    LoginForm
} from "./components/LoginForm.js";

export async function LoginPage() {

    setTimeout(() => {
        initAuthEvents();
    }, 0);

    return `

        <section class="auth-page">

            <div class="auth-page__header">

                <h1>
                    Iniciar sesión
                </h1>

                <p>
                    Accede a tu cuenta de Anguie Nails.
                </p>

            </div>

            ${LoginForm()}

        </section>

    `;
}
