import {
    login
} from "./services/auth.service.js";

import {
    store
} from "../../state/store.js";

export function initAuthEvents() {

    document.addEventListener(
        "submit",
        handleLoginSubmit
    );

}

async function handleLoginSubmit(event) {

    const form = event.target.closest(
        "#login-form"
    );

    if (!form) return;

    event.preventDefault();

    const formData = new FormData(form);

    const email = formData.get("email");
    const password = formData.get("password");

    const button = form.querySelector(
        "button[type='submit']"
    );

    button.disabled = true;
    button.textContent = "Iniciando sesión...";

    try {

        const { user } = await login({
            email,
            password
        });

        store.user = user;

        alert(
            "Inicio de sesión exitoso."
        );

        window.location.hash = "#/";

    } catch (error) {

        console.error(
            "Error al iniciar sesión:",
            error
        );

        alert(
            error.message ||
            "No fue posible iniciar sesión."
        );

    } finally {

        button.disabled = false;
        button.textContent = "Iniciar sesión";

    }
}