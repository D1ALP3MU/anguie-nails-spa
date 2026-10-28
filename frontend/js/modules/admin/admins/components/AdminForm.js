/**
 * Alta de administrador.
 *
 * La contraseña es temporal y se la pasas tú a la persona; ella la
 * cambia desde «Mi cuenta» al entrar. El aviso está en el
 * formulario porque es la parte del flujo que se olvida.
 */
export function AdminForm() {

    return `
        <form class="client-form" id="admin-form">

            <div class="form-group">
                <label for="admin-name">Nombre</label>
                <input
                    type="text"
                    id="admin-name"
                    name="nombre"
                    placeholder="Nombre completo"
                    minlength="3"
                    maxlength="100"
                    required
                >
            </div>

            <div class="form-group">
                <label for="admin-email">Correo electrónico</label>
                <input
                    type="email"
                    id="admin-email"
                    name="email"
                    placeholder="correo@ejemplo.com"
                    maxlength="100"
                    required
                >
                <p class="form-ayuda">
                    Con este correo iniciará sesión.
                </p>
            </div>

            <div class="form-group">
                <label for="admin-password">Contraseña temporal</label>
                <input
                    type="text"
                    id="admin-password"
                    name="password"
                    autocomplete="off"
                    minlength="8"
                    maxlength="255"
                    required
                >
                <p class="form-ayuda">
                    Al menos 8 caracteres. Pásasela en persona y
                    pídele que la cambie desde «Mi cuenta».
                </p>
            </div>

            <button type="submit" class="btn btn-primary">
                Crear administrador
            </button>

        </form>
    `;
}
