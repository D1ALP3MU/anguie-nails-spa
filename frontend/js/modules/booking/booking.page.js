import { getBookings } from "../../state/booking.store.js";

export function BookingPage() {
    const bookings = getBookings();

    return `

        <section class="booking-page">

            <h1>
                Reservas
            </h1>

            <div class="booking-list">
                ${bookings.length 
                    ? bookings.map(booking => `
                        <article class="booking-card">
                            <h3>${booking.name}</h3>
                            <p>Fecha: ${booking.date}</p>
                            <small>Servicio ID: ${booking.serviceId}</small>
                        </article>
                    `).join("") : `
                        <p>No hay reservas</p>
                    `}
            </div>

        </section>

    `;
}