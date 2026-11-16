/*
 * Búsqueda de texto en listados que ya están en memoria.
 *
 * Sin importar tildes ni mayúsculas: «nunez» encuentra «Núñez». Cada
 * palabra se busca por separado y tienen que aparecer todas, en el
 * orden que sea: «ana medellín» encuentra a Ana si vive en Medellín.
 */

/**
 * Minúsculas y sin tildes. La ñ queda como n: buscar «nunez» debe
 * encontrar «Núñez», y nadie escribe la tilde de más en un buscador.
 */
export function normalize(text) {

    return String(text ?? "")
        .normalize("NFD")
        .replace(/[̀-ͯ]/g, "")
        .toLowerCase();
}

const PHONE_LIKE = /^[\d\s()+.-]+$/;

/**
 * @param {string} query Lo que se escribió en el buscador.
 * @param {object} fields
 * @param {Array<string|null>} fields.text  Campos de texto.
 * @param {Array<string|null>} [fields.phones] Teléfonos: se comparan
 *        solo por sus cifras, así «300-111» encuentra «3001112233».
 *
 * @returns {boolean} true si todas las palabras aparecen.
 */
export function matchesQuery(query, { text, phones = [] }) {

    const words = normalize(query).split(/\s+/).filter(Boolean);

    if (!words.length) return true;

    const haystack = normalize(text.join(" "));

    const digits = phones.map(p => String(p ?? "").replace(/\D/g, "")).join(" ");

    return words.every(word => {

        if (haystack.includes(word)) return true;

        // Una palabra hecha solo de cifras y separadores se compara
        // contra las cifras del teléfono.
        if (PHONE_LIKE.test(word)) {
            const wordDigits = word.replace(/\D/g, "");
            return wordDigits !== "" && digits.includes(wordDigits);
        }

        return false;
    });
}
