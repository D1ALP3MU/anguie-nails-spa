import { config } from "../config/env.js";

// const API_URL = "http://localhost/anguie-nails/backend/api";

export async function apiFetch(endpoint, options = {}) {

    try {

        const response = await fetch(

            `${config}/${endpoint}`,

            {

            headers: {

                "Content-Type": "application/json",

            },

            ...options,

        });

        if (!response.ok) {

        throw new Error("API Error");

        }

        return await response.json();

    }

    catch(error) {

        console.error(error);

        throw error;

    }

}