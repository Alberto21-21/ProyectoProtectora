import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

// ELEMENTOS DE LA CABECERA
const enlaceIniciarSesion =
    document.querySelector("#enlaceIniciarSesion");

const botonCerrarSesion =
    document.querySelector("#botonCerrarSesion");

const mensaje = document.querySelector("#mensajeInformacion");

// COMPROBAR LA SESIÓN
async function comprobarSesion() {

    const token = sessionStorage.getItem("token")
        || localStorage.getItem("token");

    enlaceIniciarSesion.hidden = false;
    botonCerrarSesion.hidden = true;

    if (!token) {
        return;
    }

    try {
        await obtenerUsuario(token);

        enlaceIniciarSesion.hidden = true;
        botonCerrarSesion.hidden = false;

    } catch (error) {
        mensaje.textContent =
            "No se pudo comprobar la sesión. Puedes seguir consultando esta página.";
    }
}

// CERRAR SESIÓN
async function salirDeLaSesion() {

    const token = sessionStorage.getItem("token")
        || localStorage.getItem("token");

    if (!token) {
        window.location.href = "index.html";
        return;
    }

    botonCerrarSesion.disabled = true;

    try {
        await cerrarSesion(token);

        sessionStorage.removeItem("token");
        localStorage.removeItem("token");

        window.location.href = "index.html";

    } catch (error) {
        mensaje.textContent =
            "No se pudo cerrar sesión: " + error.message;

    } finally {
        botonCerrarSesion.disabled = false;
    }
}

// EVENTOS
botonCerrarSesion.addEventListener("click", salirDeLaSesion);

window.addEventListener("pageshow", comprobarSesion);