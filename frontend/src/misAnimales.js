import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import {
    obtenerMisAnimales
} from "./providers/animalesProvider.js";

document.addEventListener("DOMContentLoaded", function () {
    // -------------------------------------------------------------
    // ELEMENTOS DEL DOM
    // -------------------------------------------------------------
    const mensajeMisAnimales = document.querySelector("#mensajeMisAnimales");
    const seccionMisAnimales = document.querySelector("#seccionMisAnimales");
    const tablaMisAnimales = document.querySelector("#tablaMisAnimales");
    const botonCerrarSesion = document.querySelector("#botonCerrarSesion");

    // Solo el personal y los veterinarios pueden tener animales asignados
    const rolesTrabajadores = ["personal", "veterinario"];

    // MOSTRAR UN MENSAJE AL USUARIO
    function mostrarMensaje(texto, color) {
        mensajeMisAnimales.textContent = texto;
        mensajeMisAnimales.style.color = color;
    }

    // CREAR UNA CELDA DE LA TABLA CON SU TEXTO
    function crearCelda(texto) {
        const celda = document.createElement("td");
        celda.style.padding = "10px";
        celda.textContent = texto;
        return celda;
    }

    // COMPROBAR QUE EL USUARIO ES UN TRABAJADOR Y CARGAR SUS ANIMALES
    async function comprobarAcceso() {
        const token = sessionStorage.getItem("token") || localStorage.getItem("token");

        if (!token) {
            window.location.href = "login.html";
            return;
        }

        try {
            const usuario = await obtenerUsuario(token);

            if (!rolesTrabajadores.includes(usuario.rol)) {
                mostrarMensaje("No tienes permisos para consultar animales asignados.", "red");
                return;
            }

            seccionMisAnimales.hidden = false;
            cargarMisAnimales(token);

        } catch (error) {
            mostrarMensaje("No se pudo comprobar tu sesión: " + error.message, "red");
        }
    }

    // -------------------------------------------------------------
    // CARGAR LOS ANIMALES ASIGNADOS DESDE LA API
    // -------------------------------------------------------------
    async function cargarMisAnimales(token) {
        mostrarMensaje("Cargando tus animales...", "#333");

        try {
            const resultado = await obtenerMisAnimales(token);
            const animales = resultado.data;

            tablaMisAnimales.replaceChildren();
            mostrarMensaje("", "#333");

            if (animales.length === 0) {
                const filaVacia = document.createElement("tr");
                const celdaVacia = crearCelda("No tienes ningún animal asignado.");
                celdaVacia.colSpan = 6;
                celdaVacia.style.textAlign = "center";
                filaVacia.appendChild(celdaVacia);
                tablaMisAnimales.appendChild(filaVacia);
                return;
            }

            for (let i = 0; i < animales.length; i++) {
                const animal = animales[i];
                const fila = document.createElement("tr");

                const celdaNombre = crearCelda(animal.nombre);
                celdaNombre.style.fontWeight = "600";

                fila.appendChild(celdaNombre);
                fila.appendChild(crearCelda(animal.especie + (animal.raza ? " (" + animal.raza + ")" : "")));
                fila.appendChild(crearCelda(animal.estado));
                fila.appendChild(crearCelda(animal.caracter || "Sin especificar"));
                fila.appendChild(crearCelda(animal.necesidades_especiales || "Ninguna"));
                fila.appendChild(crearCelda(animal.instrucciones_cuidado || "Sin instrucciones"));

                tablaMisAnimales.appendChild(fila);
            }

        } catch (error) {
            mostrarMensaje(error.message, "red");
        }
    }

    // -------------------------------------------------------------
    // CERRAR SESIÓN
    // -------------------------------------------------------------
    if (botonCerrarSesion) {
        botonCerrarSesion.addEventListener("click", async function () {
            const token = sessionStorage.getItem("token") || localStorage.getItem("token");

            botonCerrarSesion.disabled = true;

            // Avisar al servidor para que borre el token (si falla, seguimos igualmente)
            if (token) {
                try {
                    await cerrarSesion(token);
                } catch (error) {
                    console.error("Error al cerrar sesión en el servidor:", error);
                }
            }

            // Borrar el token del navegador y volver al inicio siempre
            sessionStorage.removeItem("token");
            localStorage.removeItem("token");
            window.location.href = "index.html";
        });
    }

    // INICIAR VERIFICACIÓN AL CARGAR LA PÁGINA
    comprobarAcceso();
});
