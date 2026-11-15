import { fetchService, fetchSchedule } from "./reserve.api.js";
import { getProfessionals } from "../professionals/professionals.service.js";
import {
    createState,
    loadSlots,
    initReserveEvents,
    renderPart
} from "./reserve.controller.js";
import { escapeHtml } from "../../utils/html.js";
import { formatCOP } from "../../utils/currency.js";

/**
 * Reservar un servicio: #/reservar/{id}
 *
 * Página propia, y no un modal, para que tenga dirección: si hace
 * falta iniciar sesión, al volver se llega aquí mismo.
 *
 * Solo se ofrecen horas libres. Las calcula el backend con la misma
 * regla con la que después acepta la reserva.
 */
export async function ReservePage({ id }) {

    const [service, professionals, schedule] = await Promise.all([
        fetchService(id),
        getProfessionals(),
        fetchSchedule()
    ]);

    createState({ service, professionals, schedule });

    // Si ya hay profesional (porque solo hay una), las horas del
    // primer día llegan con la página y no un instante después.
    await loadSlots();

    initReserveEvents();

    return `

        <section class="clients-page reserve-page">

            <a href="#/services" class="historial-volver">← Servicios</a>

            <div class="clients-page__header">
                <div>
                    <h1>Reservar cita</h1>
                    <p>
                        ${escapeHtml(service.name)}
                        · ${service.duration} min
                        · ${formatCOP(service.price)}
                    </p>
                </div>
            </div>

            <div class="reserve-grid">

                <div class="reserve-steps">

                    <section class="reserve-step" aria-labelledby="reserve-t-pro">
                        <h2 class="reserve-step__title" id="reserve-t-pro">Profesional</h2>
                        <div id="reserve-pros">${renderPart("pros")}</div>
                    </section>

                    <section class="reserve-step" aria-labelledby="reserve-t-day">
                        <h2 class="reserve-step__title" id="reserve-t-day">Día</h2>
                        <div id="reserve-days">${renderPart("days")}</div>
                    </section>

                    <section class="reserve-step" aria-labelledby="reserve-t-time">
                        <h2 class="reserve-step__title" id="reserve-t-time">Hora</h2>
                        <div id="reserve-slots" aria-live="polite">${renderPart("slots")}</div>
                    </section>

                    <section class="reserve-step">
                        <button
                            type="button"
                            class="reserve-note-toggle"
                            data-toggle-note
                            aria-controls="reserve-note"
                        >+ Añadir una nota para la profesional</button>

                        <div class="form-group" id="reserve-note" hidden>
                            <label for="reserve-notes">Nota para la profesional</label>
                            <textarea
                                id="reserve-notes"
                                maxlength="1000"
                                rows="3"
                                placeholder="Por ejemplo: prefiero tonos claros"
                            ></textarea>
                        </div>
                    </section>

                </div>

                <aside class="reserve-summary" id="reserve-summary" aria-label="Resumen de la reserva">
                    ${renderPart("summary")}
                </aside>

            </div>

        </section>

    `;
}
