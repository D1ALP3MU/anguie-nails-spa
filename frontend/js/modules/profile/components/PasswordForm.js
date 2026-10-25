/*
 * Los tres campos llevan `autocomplete` para que el gestor de
 * contraseñas del navegador ofrezca la actual y guarde la nueva
 * en lugar de rellenarlas todas con lo mismo.
 */
export function PasswordForm() {

    return `

        <form class="perfil-form" id="password-form">

            <div class="form-group">

                <label for="password-actual">
                    Contraseña actual
                </label>

                <input
                    type="password"
                    id="password-actual"
                    name="password_actual"
                    autocomplete="current-password"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password-nueva">
                    Contraseña nueva
                </label>

                <input
                    type="password"
                    id="password-nueva"
                    name="password_nueva"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >

                <p class="form-ayuda">
                    Al menos 8 caracteres, y distinta de la actual.
                </p>

            </div>

            <div class="form-group">

                <label for="password-confirmacion">
                    Repite la contraseña nueva
                </label>

                <input
                    type="password"
                    id="password-confirmacion"
                    name="password_confirmacion"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >

            </div>

            <button type="submit" class="btn btn-primary">
                Cambiar contraseña
            </button>

        </form>

    `;
}
