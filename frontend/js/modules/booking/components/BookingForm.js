import { escapeHtml } from "../../../utils/html.js";

/**
 * Formulario de reserva.
 *
 * No pide nombre, correo ni teléfono: la cita se asocia al cliente
 * autenticado, y el backend toma su id_cliente del token.
 */
export function BookingForm(serviceId, professionals) {

    return `

        <form class="booking-form" id="booking-form">

            <input
                type="hidden"
                name="serviceId"
                value="${escapeHtml(serviceId)}"
            >

            <div class="form-group">

                <label for="professional">
                    Profesional
                </label>

                <select
                    id="professional"
                    name="professionalId"
                    required
                >

                    <option value="">
                        Selecciona un profesional
                    </option>

                    ${professionals.map(professional => `
                        <option value="${escapeHtml(professional.id)}">
                            ${escapeHtml(professional.name)}${professional.specialty
                                ? ` - ${escapeHtml(professional.specialty)}`
                                : ""}
                        </option>
                    `).join("")}

                </select>

            </div>

            <div class="form-group">

                <label for="date">
                    Fecha
                </label>

                <input
                    type="date"
                    id="date"
                    name="date"
                    min="${new Date().toISOString().slice(0, 10)}"
                    required
                >

            </div>

            <div class="form-group">

                <label for="time">
                    Hora
                </label>

                <input
                    type="time"
                    id="time"
                    name="time"
                    required
                >

            </div>

            <div class="form-group">

                <label for="notes">
                    Notas
                </label>

                <textarea
                    id="notes"
                    name="notes"
                    maxlength="1000"
                    placeholder="Información adicional (opcional)"
                ></textarea>

            </div>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Confirmar cita
            </button>

        </form>

    `;
}
