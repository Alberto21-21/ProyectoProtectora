import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

const API = "http://127.0.0.1:8000";
let echo = null;

export function obtenerEcho() {
    const token = sessionStorage.getItem("token");

    if (!token) {
        return null;
    }

    if (!echo) {
        echo = new Echo({
            broadcaster: "reverb",
            key: import.meta.env.VITE_REVERB_APP_KEY,
            wsHost: import.meta.env.VITE_REVERB_HOST,
            wsPort: import.meta.env.VITE_REVERB_PORT,
            wssPort: import.meta.env.VITE_REVERB_PORT,
            forceTLS: false,
            enabledTransports: ["ws", "wss"],
            authEndpoint: `${API}/api/broadcasting/auth`,
            auth: {
                headers: {
                    Authorization: `Bearer ${token}`,
                    Accept: "application/json"
                }
            }
        });
    }

    return echo;
}

// LLAMAR AL CERRAR SESIÓN
export function cerrarEcho() {
    if (echo) {
        echo.disconnect();
        echo = null;
    }
}