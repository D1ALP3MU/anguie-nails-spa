import { initBookingEvents } from "./booking.controller.js";
import { fetchBookings } from "./services/booking.service.js";
import { escapeHtml } from "../../utils/html.js";

export async function BookingPage() {

    const bookings = await fetchBookings();

    initBookingEvents();

    return `

        <section class="booking-page">

            <div class="booking-page__header">

                <h1>
                    Mis citas
                </h1>

                <p>
                    Consulta y gestiona tus citas.
                </p>

            </div>

            <div class="booking-list">

                ${bookings.length
            ? bookings.map(booking => `

                    <article class="booking-card">

                        <h3>
                            ${escapeHtml(booking.servicio)}
                        </h3>

                        <p>
                            Profesional: ${escapeHtml(booking.profesional)}
                        </p>

                        <p>
                            Fecha: ${escapeHtml(booking.fecha)}
                        </p>

                        <p>
                            Hora: ${escapeHtml(booking.hora)}
                        </p>

                        <p>
                            Estado: ${escapeHtml(booking.estado)}
                        </p>

                        <p>
                            Notas: ${escapeHtml(booking.notas ?? "—")}
                        </p>

                        ${booking.estado === "pendiente"
                    ? `
                                <button
                                    class="btn btn-outline"
                                    data-delete-booking="${escapeHtml(booking.id_cita)}"
                                >
                                    Cancelar cita
                                </button>
                            `
                    : ""
                }

                    </article>

                `).join("")
            : `
                    <p>
                        No hay citas registradas.
                    </p>
                `
        }

            </div>

        </section>

    `;
}
