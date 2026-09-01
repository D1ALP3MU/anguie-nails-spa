import { initRouter } from "./router/router.js";

import {
    initAuth
} from "./modules/auth/services/auth.service.js";

import {
    store
} from "./state/store.js";

document.addEventListener("DOMContentLoaded", () => {

    const user = initAuth();

    store.user = user;

    initRouter();

});