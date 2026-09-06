import { escapeHtml } from "../../../../utils/html.js";

/**
 * Los límites replican los de ServiceValidator en el backend, para
 * que el formulario avise antes de enviar. La validación que manda
 * sigue siendo la del servidor.
 */
const MAX_DURATION = 240;
const MAX_PRICE = 1000000;

export function ServiceForm(service = null) {

    const isEdit = service !== null;

    const name = escapeHtml(service?.name ?? "");
    const description = escapeHtml(service?.description ?? "");
    const duration = escapeHtml(service?.duration ?? "");
    const price = escapeHtml(service?.price ?? "");

    return `
        <form
            class="client-form"
            id="service-form"
            ${isEdit ? `data-service-id="${escapeHtml(service.id)}"` : ""}
        >

            <div class="form-group">
                <label for="service-name">Nombre</label>
                <input
                    type="text"
                    id="service-name"
                    name="nombre"
                    placeholder="Manicura premium"
                    value="${name}"
                    minlength="3"
                    maxlength="100"
                    required
                >
            </div>

            <div class="form-group">
                <label for="service-description">Descripción</label>
                <textarea
                    id="service-description"
                    name="descripcion"
                    placeholder="Qué incluye el servicio (opcional)"
                    maxlength="500"
                    rows="3"
                >${description}</textarea>
            </div>

            <div class="form-group">
                <label for="service-duration">Duración en minutos</label>
                <input
                    type="number"
                    id="service-duration"
                    name="duracion"
                    placeholder="60"
                    value="${duration}"
                    min="1"
                    max="${MAX_DURATION}"
                    step="1"
                    required
                >
            </div>

            <div class="form-group">
                <label for="service-price">Precio</label>
                <input
                    type="number"
                    id="service-price"
                    name="precio"
                    placeholder="50000"
                    value="${price}"
                    min="1"
                    max="${MAX_PRICE}"
                    step="1"
                    required
                >
            </div>

            <button
                type="submit"
                class="btn btn-primary"
            >
                ${isEdit ? "Actualizar servicio" : "Crear servicio"}
            </button>

        </form>
    `;
}
