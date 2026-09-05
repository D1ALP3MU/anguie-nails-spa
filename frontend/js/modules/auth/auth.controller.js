import {
    login,
    register,
    logout
} from "./services/auth.service.js";

import {
    setUser
} from "../../state/actions.js";

import {
    takeRedirect
} from "../../api/session.js";

import {
    toastSuccess,
    toastError
} from "../../components/ui/Toast.js";

export function initAuthEvents() {

    document.addEventListener(
        "submit",
        handleLoginSubmit
    );

    document.addEventListener(
        "submit",
        handleRegisterSubmit
    );

    document.addEventListener(
        "click",
        handleAuthClick
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

        setUser(user);

        toastSuccess("Inicio de sesión exitoso.");

        // Devuelve al usuario a donde iba antes de que se le
        // pidiera iniciar sesión.
        window.location.hash = takeRedirect() || "#/";

    } catch (error) {

        console.error(
            "Error al iniciar sesión:",
            error
        );

        toastError(
            error.message ||
            "No fue posible iniciar sesión."
        );

    } finally {

        button.disabled = false;
        button.textContent = "Iniciar sesión";

    }
}

async function handleRegisterSubmit(event) {

    const form = event.target.closest(
        "#register-form"
    );

    if (!form) return;

    event.preventDefault();

    const formData = new FormData(form);

    const nombre = formData.get("nombre");
    const email = formData.get("email");
    const password = formData.get("password");
    const telefono = formData.get("telefono");
    const direccion = formData.get("direccion");

    const button = form.querySelector(
        "button[type='submit']"
    );

    button.disabled = true;
    button.textContent = "Registrando...";

    try {

        await register({
            nombre,
            email,
            password,
            telefono,
            direccion
        });

        toastSuccess("Cuenta creada correctamente. Ya puedes iniciar sesión.");

        window.location.hash = "#/login";

    } catch (error) {

        console.error(
            "Error al registrar usuario:",
            error
        );

        toastError(
            error.message ||
            "No fue posible crear la cuenta."
        );

    } finally {

        button.disabled = false;
        button.textContent = "Registrarse";

    }
}

function handleAuthClick(event) {

    const action = event.target.dataset.action;

    if (!action) return;

    if (action === "login") {

        window.location.hash = "#/login";

        return;
    }

    if (action === "register") {

        window.location.hash = "#/register";

        return;
    }

    if (action === "logout") {

        logout();

        setUser(null);

        window.location.hash = "#/";

    }

}