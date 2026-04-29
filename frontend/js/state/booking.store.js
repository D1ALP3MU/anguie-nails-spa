const STORAGE_KEY = "anguie_nails_bookings";

const bookingState = {
    bookings: loadBookings(),
};

// LOAD BOOKINGS
function loadBookings() {
    const savedBookings = localStorage.getItem(STORAGE_KEY);

    if (!savedBookings) {
        return [];
    }

    try {
        return JSON.parse(savedBookings);
    }
    catch (error) {
        console.error("Error al cargar las reservas", error);
    }
}

//SAVED BOOKINGS
function persistBookings() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(bookingState.bookings));
}

// ADD BOOKING
export function addBooking(booking) {

    bookingState.bookings.push(booking);

    persistBookings();

}

// GET BOOKINGS
export function getBookings() {

    return [ ...bookingState.bookings ];
    
}

// DELETE BOOKING
export function deleteBooking(bookingId) {

    bookingState.bookings = bookingState.bookings.filter(booking => booking.id !== bookingId);

    persistBookings();
}