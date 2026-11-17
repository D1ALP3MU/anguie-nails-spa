/**
 * Datos de contacto del salón.
 *
 * Un solo sitio: si cambia el WhatsApp o la dirección, se cambia
 * aquí y se actualiza en todas partes.
 *
 * El horario no está aquí a propósito: es el del .env del backend,
 * el mismo que decide qué horas se pueden reservar (/api/schedule).
 */

const address = "Cl. 80 # 48A-49";
const city = "Medellín";

// Número local de Colombia; el enlace de WhatsApp lo quiere con el
// prefijo del país (57) y sin espacios.
const whatsapp = "3128157317";

const instagram = "anguie.nails";

export const salon = {
    name: "Anguie Nails",
    tagline: `Manicura y pedicura en ${city}.`,

    address,
    city,

    mapsUrl: "https://www.google.com/maps/search/?api=1&query="
        + encodeURIComponent(`${address}, ${city}, Colombia`),

    whatsappDisplay: whatsapp.replace(/(\d{3})(\d{3})(\d{4})/, "$1 $2 $3"),
    whatsappUrl: `https://wa.me/57${whatsapp}`,

    instagramHandle: `@${instagram}`,
    instagramUrl: `https://www.instagram.com/${instagram}/`
};
