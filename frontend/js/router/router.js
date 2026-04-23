export function initRouter() {
    window.addEventListener("hashchange", handleRoute);

    handleRoute();
}

function handleRoute() {
    const route = location.hash || "#/";

    console.log("Route:", route);
    
}