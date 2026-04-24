import { apiFetch } from "./api.service.js";

export async function getServices() {

    return await apiFetch("services.php");

}