import {
    iniciarSesion,
    obtenerUsuario
} from "./providers/autenticacionProvider.js";

// OBTENER ELEMENTOS DEL FORMULARIO
const formulario = document.querySelector("#formularioLogin");
const estado = document.querySelector("#mensajeLogin");
const boton = document.querySelector("#botonLogin");

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