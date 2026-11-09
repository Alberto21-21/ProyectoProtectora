import {
    iniciarSesion,
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

// ELEMENTOS DEL FORMULARIO
const formulario = document.querySelector("#formularioLogin");
const estado = document.querySelector("#mensajeLogin");
const boton = document.querySelector("#botonLogin");

// CABECERA Y MODAL
const abrirLogin = document.querySelector("#abrirLogin");
const botonCerrarSesion = document.querySelector("#botonCerrarSesion");
const enlacePerfil = document.querySelector("#enlacePerfil");
const mensajeSesion = document.querySelector("#mensajeSesion");
const modalLogin = document.querySelector("#modalLogin");

// ACTUALIZAR LA CABECERA
function actualizarCabecera(sesionActiva) {

    if (abrirLogin) {
        abrirLogin.hidden = sesionActiva;
    }

    if (enlacePerfil) {
        enlacePerfil.hidden = !sesionActiva;
    }

    botonCerrarSesion.hidden = !sesionActiva;
}

// MOSTRAR MENSAJES
function mostrarMensaje(mensaje) {

    if (mensajeSesion) {
        mensajeSesion.textContent = mensaje;
    } else {
        estado.textContent = mensaje;
    }
}

// INICIAR SESION
async function enviarLogin(evento) {

    evento.preventDefault();

    const datos = {
        email: document.querySelector("#emailLogin").value.trim(),
        password: document.querySelector("#passwordLogin").value
    };

    estado.textContent = "Iniciando sesión...";
    boton.disabled = true;

    try {
        const resultado = await iniciarSesion(datos);

        sessionStorage.setItem("token", resultado.data.token);

        const usuario = await obtenerUsuario(resultado.data.token);

        actualizarCabecera(true);
        formulario.reset();
        estado.textContent = "";

        if (modalLogin) {
            modalLogin.close();
        }

        mostrarMensaje("Bienvenido, " + usuario.name + ".");

    } catch (error) {
        estado.textContent =
            `No se pudo iniciar sesión: ${error.message}`;

    } finally {
        boton.disabled = false;
    }
}

// CERRAR SESION
async function salirDeLaSesion() {

    const token = sessionStorage.getItem("token");

    if (!token) {
        actualizarCabecera(false);
        mostrarMensaje("No hay ninguna sesión activa.");
        return;
    }

    botonCerrarSesion.disabled = true;
    mostrarMensaje("Cerrando sesión...");

    try {
        const resultado = await cerrarSesion(token);

        sessionStorage.removeItem("token");

        actualizarCabecera(false);
        formulario.reset();
        mostrarMensaje(resultado.message);

    } catch (error) {
        mostrarMensaje(
            `No se pudo cerrar la sesión: ${error.message}`
        );

    } finally {
        botonCerrarSesion.disabled = false;
    }
}

// COMPROBAR LA SESION AL RECARGAR
async function comprobarSesion() {

    const token = sessionStorage.getItem("token");

    actualizarCabecera(false);

    if (!token) {
        return;
    }

    try {
        await obtenerUsuario(token);
        actualizarCabecera(true);

    } catch (error) {
        mostrarMensaje(
            "No se pudo comprobar la sesión. Vuelve a intentarlo."
        );
    }
}

// EVENTOS
formulario.addEventListener("submit", enviarLogin);
botonCerrarSesion.addEventListener("click", salirDeLaSesion);

// COMPROBACION INICIAL
comprobarSesion();