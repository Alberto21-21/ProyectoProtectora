import {
    iniciarSesion,
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

// OBTENER ELEMENTOS DEL FORMULARIO
const formulario = document.querySelector("#formularioLogin");
const estado = document.querySelector("#mensajeLogin");
const boton = document.querySelector("#botonLogin");
const botonCerrarSesion = document.querySelector("#botonCerrarSesion");

// INICIAR SESIÓN
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

    // CONSULTAR LOS DATOS MEDIANTE LA RUTA PROTEGIDA
    const token = sessionStorage.getItem("token");
    const usuario = await obtenerUsuario(token);

    botonCerrarSesion.hidden = false;
    
    estado.textContent =
        "Sesión iniciada correctamente. Bienvenido, " + usuario.name + ".";

    formulario.reset();

    } catch (error) {
        estado.textContent =
            `No se pudo iniciar sesión: ${error.message}`;

    } finally {
        boton.disabled = false;
    }
}

// ESCUCHAR EL ENVÍO DEL FORMULARIO
formulario.addEventListener("submit", enviarLogin);

// CERRAR SESION
async function salirDeLaSesion() {

    const token = sessionStorage.getItem("token");

    if (!token) {
        estado.textContent = "No hay ninguna sesión activa.";
        botonCerrarSesion.hidden = true;
        return;
    }

    estado.textContent = "Cerrando sesión...";
    botonCerrarSesion.disabled = true;

    try {
        const resultado = await cerrarSesion(token);

        // BORRAR EL TOKEN SOLO CUANDO LARAVEL CONFIRME EL CIERRE
        sessionStorage.removeItem("token");

        estado.textContent = resultado.message;
        botonCerrarSesion.hidden = true;
        formulario.reset();

    } catch (error) {
        estado.textContent =
            `No se pudo cerrar la sesión: ${error.message}`;

    } finally {
        botonCerrarSesion.disabled = false;
    }
}

// ESCUCHAR EL BOTON DE CIERRE
botonCerrarSesion.addEventListener("click", salirDeLaSesion);