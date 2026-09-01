import { initRouter } from "./router/router.js";

import {
    initAuth
} from "./modules/auth/services/auth.service.js";

import {
    initAuthEvents
} from "./modules/auth/auth.controller.js";

import {
    store
} from "./state/store.js";

document.addEventListener("DOMContentLoaded", () => {

    const user = initAuth();

    store.user = user;

    initAuthEvents();

    initRouter();

});