import { addBooking, getBookings, deleteBooking } from "../../../state/booking.store.js";

// CREATE BOOKING
export async function createBooking(bookingData) {

    await simulateDelay();

    const booking = {
        id: crypto.randomUUID(), ...bookingData,
        createdAt: new Date().toISOString(),
    };

    addBooking(booking);

    return booking;
}

// FETCH BOOKINGS
export async function fetchBookings() {

    await simulateDelay();

    return getBookings();

}

// DELETE BOOKING
export async function removeBooking(bookingId) {

    await simulateDelay();

    deleteBooking(bookingId);

}

// SIMULATE API DELAY
function simulateDelay() {

    return new Promise(resolve => {
        setTimeout(resolve, 1500);
    });

}