export function ClientForm() {

    return `
        <form class="client-form" id="client-form">

            <div class="form-group">
                <label for="client-name">Nombre</label>
                <input
                    type="text"
                    id="client-name"
                    name="nombre"
                    placeholder="Nombre completo"
                    required
                >
            </div>

            <div class="form-group">
                <label for="client-email">Correo electrónico</label>
                <input
                    type="email"
                    id="client-email"
                    name="email"
                    placeholder="correo@ejemplo.com"
                    required
                >
            </div>

            <div class="form-group">
                <label for="client-password">Contraseña</label>
                <input
                    type="password"
                    id="client-password"
                    name="password"
                    placeholder="Mínimo 8 caracteres"
                    required
                >
            </div>

            <div class="form-group">
                <label for="client-phone">Teléfono</label>
                <input
                    type="tel"
                    id="client-phone"
                    name="telefono"
                    placeholder="Número de teléfono"
                    required
                >
            </div>

            <div class="form-group">
                <label for="client-address">Dirección</label>
                <input
                    type="text"
                    id="client-address"
                    name="direccion"
                    placeholder="Dirección"
                >
            </div>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Crear cliente
            </button>

        </form>
    `;
}