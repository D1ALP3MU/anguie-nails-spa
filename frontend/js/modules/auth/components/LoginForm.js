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
                    required
                >

            </div>

            <div class="form-group">

                <label for="login-password">
                    Contraseña
                </label>

                <input
                    type="password"
                    id="login-password"
                    name="password"
                    placeholder="Tu contraseña"
                    minlength="8"
                    required
                >

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
