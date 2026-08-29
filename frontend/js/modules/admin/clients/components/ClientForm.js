import { escapeHtml } from "../../../../utils/html.js";

export function ClientForm(client = null) {

    const isEdit = client !== null;

    const name = escapeHtml(client?.name ?? "");
    const email = escapeHtml(client?.email ?? "");
    const phone = escapeHtml(client?.phone ?? "");
    const address = escapeHtml(client?.address ?? "");

    return `
        <form
            class="client-form"
            id="client-form"
            ${isEdit ? `data-client-id="${client.id}"` : ""}
        >

            <div class="form-group">
                <label for="client-name">Nombre</label>
                <input
                    type="text"
                    id="client-name"
                    name="nombre"
                    placeholder="Nombre completo"
                    value="${name}"
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
                    value="${email}"
                    required
                >
            </div>

            ${!isEdit ? `
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
            ` : ""}

            <div class="form-group">
                <label for="client-phone">Teléfono</label>
                <input
                    type="tel"
                    id="client-phone"
                    name="telefono"
                    placeholder="Número de teléfono"
                    value="${phone}"
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
                    value="${address}"
                >
            </div>

            <button
                type="submit"
                class="btn btn-primary"
            >
                ${isEdit ? "Actualizar cliente" : "Crear cliente"}
            </button>

        </form>
    `;
}