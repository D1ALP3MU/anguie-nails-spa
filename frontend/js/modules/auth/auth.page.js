import {
    LoginForm
} from "./components/LoginForm.js";

import {
    RegisterForm
} from "./components/RegisterForm.js";

export async function LoginPage() {

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

export async function RegisterPage() {

    return `

        <section class="auth-page">
        
            <h1>Crear cuenta</h1>

            ${RegisterForm()}

        </section>

    `;

}
