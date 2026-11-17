import { PasswordInput } from "../../../components/ui/PasswordInput.js";

/*
 * `username` y `current-password` le dicen al gestor de contraseñas
 * del navegador qué es cada campo, para que ofrezca y rellene bien
 * la cuenta guardada.
 */
export function LoginForm() {

    return `

        <form
            class="auth-form"
            id="login-form"
        >

            <div class="form-group">

                <label for="login-email">
                    Correo electrónico
                </label>

                <input
                    type="email"
                    id="login-email"
                    name="email"
                    placeholder="tu@email.com"
                    autocomplete="username"
                    required
                >

            </div>

            <div class="form-group">

                <label for="login-password">
                    Contraseña
                </label>

                ${PasswordInput({
                    id: "login-password",
                    name: "password",
                    autocomplete: "current-password",
                    placeholder: "Tu contraseña",
                    minlength: 8
                })}

            </div>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Iniciar sesión
            </button>

        </form>

    `;
}
