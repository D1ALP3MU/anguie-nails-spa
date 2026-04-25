const bookingState = {
    bookings: [],
};

export function addBooking(booking) {

    bookingState.bookings.push(booking);

}

export function getBookings() {

    return [ ...bookingState.bookings ];
    
}