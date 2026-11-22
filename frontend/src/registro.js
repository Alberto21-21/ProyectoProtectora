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

        const modalRegistro = document.querySelector("#modalRegistro");
        const modalLogin = document.querySelector("#modalLogin");

        formulario.reset();

        if (modalRegistro && modalLogin) {
            estado.textContent = "";

            modalRegistro.close();

            // PREPARAR EL INICIO DE SESION
            document.querySelector("#emailLogin").value = datos.email;
            document.querySelector("#passwordLogin").value = "";

            document.querySelector("#mensajeLogin").textContent =
                resultado.message + " Ya puedes iniciar sesión.";

            // LIMPIAR AVISOS ANTERIORES DE LA PORTADA
            const mensajeSesion = document.querySelector("#mensajeSesion");

            if (mensajeSesion) {
                mensajeSesion.textContent = "";
            }

            modalLogin.showModal();

        } else {
            estado.textContent = resultado.message;
        }

    } catch (error) {
        estado.textContent =
            `No se pudo crear la cuenta: ${error.message}`;

    } finally {
        boton.disabled = false;
}
}

// ESCUCHAR EL ENVÍO DEL FORMULARIO
formulario.addEventListener("submit", enviarRegistro);