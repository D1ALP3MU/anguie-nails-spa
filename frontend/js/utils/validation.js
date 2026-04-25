export function isEmpty(value) {
    return !value.trim();
}

export function isPasteDate(date) {
    const today = new Date();

    today.setHours(0,0,0,0);

    return new Date(date) < today;
}