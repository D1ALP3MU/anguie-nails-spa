import { store } from "./store.js";

import { emit } from "../core/events.js";

export function setUser(user) {

    store.user = user;

    emit("userChanged", user);

}

export function setServices(services) {

    store.services = services;

    emit("servicesChanged", services);

}

export function addAppointment(appointment) {

    store.appointments.push(appointment);

    emit("appointmentsChanged", store.appointments);

}

export function setLoading(status) {

    store.ui.loading = status;

    emit("loadingChanged", status);

}