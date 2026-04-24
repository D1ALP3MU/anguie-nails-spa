const events = {};

export function on(event, callback) {

    if (!events[event]) {

        events[event] = [];

    }

    events[event].push(callback);

}

export function emit(event, data) {

    if (!events[event]) return;

    events[event].forEach(callback => {

        callback(data);

    });

}