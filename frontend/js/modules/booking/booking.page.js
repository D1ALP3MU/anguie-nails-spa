import { initBookingEvents } from "./booking.controller.js";
import { fetchBookings } from "./services/booking.service.js";

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
                            ${booking.cliente}
                        </h3>

                        <p>
                            Servicio: ${booking.servicio}
                        </p>

                        <p>
                            Profesional: ${booking.profesional}
                        </p>

                        <p>
                            Fecha: ${booking.fecha}
                        </p>

                        <p>
                            Hora: ${booking.hora}
                        </p>

                        <p>
                            Estado: ${booking.estado}
                        </p>

                        <p>
                            Notas: ${booking.notas ?? "—"}
                        </p>

                        ${booking.estado === "pendiente"
                    ? `
                                <button
                                    class="btn btn-outline"
                                    data-delete-booking="${booking.id_cita}"
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