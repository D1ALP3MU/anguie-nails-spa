export function RegisterForm() {

    return `

        <form
            class="auth-form"
            id="register-form"
        >

            <div class="form-group">

                <label for="register-name">
                    Nombre completo
                </label>

                <input
                    type="text"
                    id="register-name"
                    name="nombre"
                    placeholder="Tu nombre completo"
                    autocomplete="name"
                    required
                >

            </div>

            <div class="form-group">

                <label for="register-email">
                    Correo electrónico
                </label>

                <input
                    type="email"
                    id="register-email"
                    name="email"
                    placeholder="correo@ejemplo.com"
                    autocomplete="email"
                    required
                >

            </div>

            <div class="form-group">

                <label for="register-password">
                    Contraseña
                </label>

                <input
                    type="password"
                    id="register-password"
                    name="password"
                    placeholder="Mínimo 8 caracteres"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >

            </div>

            <div class="form-group">

                <label for="register-phone">
                    Teléfono
                </label>

                <input
                    type="tel"
                    id="register-phone"
                    name="telefono"
                    placeholder="Número de teléfono"
                    autocomplete="tel"
                    required
                >

            </div>

            <div class="form-group">

                <label for="register-address">
                    Dirección
                </label>

                <input
                    type="text"
                    id="register-address"
                    name="direccion"
                    placeholder="Dirección"
                    autocomplete="street-address"
                >

            </div>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Registrarse
            </button>

        </form>

    `;
}