import { getBookings } from "../../state/booking.store.js";
import { initBookingEvents } from "./booking.controller.js";

export function BookingPage() {
    const bookings = getBookings();

    setTimeout(() => {
        initBookingEvents();
    }, 0);

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
                            <button class="btn btn-outline" data-delete-booking="${booking.id}">Eliminar</button>
                        </article>
                    `).join("") : `
                        <p>No hay reservas</p>
                    `}
            </div>

        </section>

    `;
}