import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import { crearAnimal } from "./providers/animalesProvider.js";

// ELEMENTOS DE LA PÁGINA
const mensaje = document.querySelector("#mensajeAnimal");
const gestionAnimales = document.querySelector("#gestionAnimales");
const formulario = document.querySelector("#formularioAnimal");
const botonGuardar = document.querySelector("#botonGuardarAnimal");
const fechaEntrada = document.querySelector("#fechaEntradaAnimal");

// BOTÓN PARA CERRAR SESIÓN
const botonCerrarSesion = document.querySelector("#botonCerrarSesion");

// ROLES QUE PUEDEN DAR DE ALTA ANIMALES
const rolesPermitidos = ["admin", "personal", "veterinario"];

// CAMPOS DEL FORMULARIO (MISMO NOMBRE QUE EN LARAVEL)
const campos = [
    "nombre",
    "fotografia",
    "especie",
    "raza",
    "fecha_nacimiento",
    "sexo",
    "tamano",
    "peso",
    "fecha_entrada",
    "motivo",
    "estado",
    "caracter",
    "con_ninos",
    "con_otros_animales",
    "con_desconocidos",
    "necesidades_especiales",
    "observaciones"
];

// OBTENER LA FECHA DE HOY EN FORMATO AAAA-MM-DD
function fechaDeHoy() {

    const hoy = new Date();
    const mes = String(hoy.getMonth() + 1).padStart(2, "0");
    const dia = String(hoy.getDate()).padStart(2, "0");

    return hoy.getFullYear() + "-" + mes + "-" + dia;
}

// COMPROBAR EL ACCESO
async function comprobarAcceso() {

    const token = sessionStorage.getItem("token");

    if (!token) {
        mensaje.textContent =
            "Debes iniciar sesión para dar de alta animales.";

        botonCerrarSesion.hidden = true;
        return;
    }

    mensaje.textContent = "Comprobando permisos...";

    try {
        const usuario = await obtenerUsuario(token);

        if (!rolesPermitidos.includes(usuario.rol)) {
            mensaje.textContent =
                "No tienes permisos para dar de alta animales.";
            return;
        }

        // POR DEFECTO LA FECHA DE ENTRADA ES HOY
        fechaEntrada.value = fechaDeHoy();

        gestionAnimales.hidden = false;
        mensaje.textContent = "";

    } catch (error) {
        mensaje.textContent =
            `No se pudo comprobar el acceso: ${error.message}`;
    }
}

// BORRAR LOS ERRORES DE TODOS LOS CAMPOS
function limpiarErrores() {

    for (let i = 0; i < campos.length; i++) {
        document.querySelector("#error-" + campos[i]).textContent = "";
    }
}

// MOSTRAR EL ERROR DEBAJO DE CADA CAMPO
function mostrarErrores(errores) {

    for (let i = 0; i < campos.length; i++) {
        const campo = campos[i];

        if (errores[campo]) {
            document.querySelector("#error-" + campo).textContent =
                errores[campo][0];
        }
    }
}

// GUARDAR EL ANIMAL
async function guardarAnimal(evento) {

    evento.preventDefault();

    const token = sessionStorage.getItem("token");

    if (!token) {
        mensaje.textContent =
            "Debes iniciar sesión para dar de alta animales.";
        return;
    }

    limpiarErrores();

    // FORMDATA PORQUE SE ENVÍA LA FOTOGRAFÍA
    const datos = new FormData(formulario);

    mensaje.textContent = "Guardando animal...";
    botonGuardar.disabled = true;

    try {
        const resultado = await crearAnimal(datos, token);

        mensaje.textContent =
            resultado.message + " (" + resultado.data.nombre + ")";

        formulario.reset();
        fechaEntrada.value = fechaDeHoy();

    } catch (error) {

        if (error.errores) {
            mostrarErrores(error.errores);
            mensaje.textContent =
                "Revisa los campos marcados en el formulario.";
        } else {
            mensaje.textContent =
                `No se pudo registrar el animal: ${error.message}`;
        }

    } finally {
        botonGuardar.disabled = false;
    }
}

// CERRAR SESIÓN
async function salirSesion() {

    const token = sessionStorage.getItem("token");

    if (!token) {
        window.location.href = "index.html";
        return;
    }

    botonCerrarSesion.disabled = true;

    try {
        await cerrarSesion(token);

        sessionStorage.removeItem("token");

        window.location.href = "index.html";

    } catch (error) {
        mensaje.textContent =
            `No se pudo cerrar sesión: ${error.message}`;

    } finally {
        botonCerrarSesion.disabled = false;
    }
}

// ESCUCHAR LOS EVENTOS
formulario.addEventListener("submit", guardarAnimal);
botonCerrarSesion.addEventListener("click", salirSesion);

// COMPROBAR LOS PERMISOS AL ABRIR LA PÁGINA
comprobarAcceso();