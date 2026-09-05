/**
 * Estado compartido de la aplicación.
 *
 * No se escribe directamente: las mutaciones pasan por state/actions.js,
 * que además avisa a quien esté suscrito a través de core/events.js.
 */
export const store = {

    user: null,

};
