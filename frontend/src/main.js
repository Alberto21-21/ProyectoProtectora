import { registrarUsuario } from "./providers/autenticacionProvider.js";


// OBTENER ELEMENTOS DEL FORMULARIO
const formulario = document.querySelector("#formularioRegistro");
const estado = document.querySelector("#mensajeRegistro");
const boton = document.querySelector("#botonRegistro");

// REGISTRAR USUARIO
async function enviarRegistro(evento) {

    evento.preventDefault();

    const datos = {
        name: document.querySelector("#nombreRegistro").value.trim(),
        email: document.querySelector("#emailRegistro").value.trim(),
        fecha_nacimiento: document.querySelector("#fechaNacimientoRegistro").value,
        password: document.querySelector("#passwordRegistro").value,
        password_confirmation: document.querySelector("#confirmarPassword").value
    };

    estado.textContent = "Creando cuenta...";
    boton.disabled = true;

    try {
        const resultado = await registrarUsuario(datos);

        estado.textContent = resultado.message;
        formulario.reset();

    } catch (error) {
        estado.textContent =
            `No se pudo crear la cuenta: ${error.message}`;

    } finally {
        boton.disabled = false;
    }
}

// ESCUCHAR EL ENVÍO DEL FORMULARIO
formulario.addEventListener("submit", enviarRegistro);