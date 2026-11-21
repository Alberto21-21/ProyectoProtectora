import Pusher from "pusher-js";

const API = "http://127.0.0.1:8000";
let pusher = null;

export function obtenerPusher() {
    const token = sessionStorage.getItem("token");

    if (!token) {
        return null;
    }

    if (!pusher) {
        pusher = new Pusher(import.meta.env.VITE_REVERB_APP_KEY, {
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT,
            forceTLS: false,
            enabledTransports: ["ws"],
            cluster: "mt1",
            disableStats: true,
            authEndpoint: `${API}/api/broadcasting/auth`,
            auth: {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: "application/json"
                }
            }
        });
    }

    return pusher;
}

// LLAMAR AL CERRAR SESIÓN
export function cerrarPusher() {
    if (pusher) {
        pusher.disconnect();
        pusher = null;
    }
}