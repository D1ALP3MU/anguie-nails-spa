import { escapeHtml } from "../../../../utils/html.js";
import { formatCOP } from "../../../../utils/currency.js";

const METODOS = [
    ["efectivo", "Efectivo"],
    ["tarjeta", "Tarjeta"],
    ["transferencia", "Transferencia"]
];

/**
 * Formulario de cobro.
 *
 * El monto viene sugerido con el precio congelado de la cita, pero
 * es editable: descuentos, propinas y redondeos son normales.
 */
export function PaymentForm(appointment) {

    const precio = Number(appointment.precio);

    return `
        <form
            class="client-form"
            id="payment-form"
            data-appointment-id="${escapeHtml(appointment.id_cita)}"
        >

            <p class="payment-form__resumen">
                ${escapeHtml(appointment.cliente)}
                ·
                ${escapeHtml(appointment.servicio)}
                <strong>${formatCOP(precio)}</strong>
            </p>

            <div class="form-group">
                <label for="payment-monto">Monto</label>
                <input
                    type="number"
                    id="payment-monto"
                    name="monto"
                    value="${escapeHtml(precio)}"
                    min="1"
                    step="1"
                    required
                >
                <small class="form-hint">
                    Sugerido: el precio de la cita. Puedes ajustarlo.
                </small>
            </div>

            <div class="form-group">
                <label for="payment-metodo">Forma de pago</label>
                <select id="payment-metodo" name="metodo" required>
                    ${METODOS.map(([valor, etiqueta]) => `
                        <option value="${valor}">${etiqueta}</option>
                    `).join("")}
                </select>
            </div>

            <div class="form-group">
                <label for="payment-referencia">Referencia</label>
                <input
                    type="text"
                    id="payment-referencia"
                    name="referencia_pago"
                    placeholder="Número de transferencia, voucher… (opcional)"
                    maxlength="255"
                >
            </div>

            <div class="form-group form-group--check">
                <label for="payment-pendiente">
                    <input
                        type="checkbox"
                        id="payment-pendiente"
                        name="pendiente"
                    >
                    Dejar como pendiente de verificar
                </label>
                <small class="form-hint">
                    Para una transferencia anunciada que todavía no has confirmado.
                </small>
            </div>

            <button type="submit" class="btn btn-primary">
                Registrar cobro
            </button>

        </form>
    `;
}
