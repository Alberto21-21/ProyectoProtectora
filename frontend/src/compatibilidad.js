import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import {
    obtenerCompatibilidad
} from "./providers/compatibilidadProvider.js";

// ELEMENTOS DE LA PÁGINA
const mensaje = document.querySelector("#mensajeCompatibilidad");
const seccion = document.querySelector("#seccionCompatibilidad");
const tabla = document.querySelector("#tablaCompatibilidad");
const botonCerrarSesion = document.querySelector("#botonCerrarSesion");

// MOSTRAR LOS RESULTADOS
function mostrarResultados(animales) {

    tabla.replaceChildren();

    if (animales.length === 0) {
        mensaje.textContent = "No hay animales recomendados con los datos actuales.";        
        seccion.hidden = true;
        return;
    }

    animales.forEach(function (animal) {

        const fila = document.createElement("tr");

        const nombre = document.createElement("td");
        const contenidoAnimal = document.createElement("div");
        contenidoAnimal.className = "animal-compatibilidad";

        if (animal.fotografia_url) {
            const imagen = document.createElement("img");

            imagen.src = animal.fotografia_url;
            imagen.alt = "Fotografía de " + animal.nombre;
            imagen.loading = "lazy";
            imagen.className = "foto-compatibilidad";

            contenidoAnimal.appendChild(imagen);
        }

        const nombreAnimal = document.createElement("span");
        nombreAnimal.textContent = animal.nombre;

        contenidoAnimal.appendChild(nombreAnimal);
        nombre.appendChild(contenidoAnimal);
        
        const estado = document.createElement("td");
        estado.textContent = animal.estado_compatibilidad;

        const puntos = document.createElement("td");

        if (animal.puntuacion === null) {
            puntos.textContent = "Sin puntuación";
        } else {
            puntos.textContent = animal.puntuacion + " / 100";
        }

        const observaciones = document.createElement("td");
        const lista = document.createElement("ul");

        const textos = [
            ...animal.motivos,
            ...animal.incompatibilidades,
            ...animal.pendientes
        ];

        textos.forEach(function (texto) {
            const elemento = document.createElement("li");
            elemento.textContent = texto;
            lista.appendChild(elemento);
        });

        observaciones.appendChild(lista);

        fila.append(nombre, estado, puntos, observaciones);
        tabla.appendChild(fila);
    });

    mensaje.textContent = "";
    seccion.hidden = false;
}

// CONSULTAR LA COMPATIBILIDAD
async function cargarCompatibilidad() {

    const token = sessionStorage.getItem("token");

    if (!token) {
        window.location.href = "index.html";
        return;
    }

    mensaje.textContent = "Calculando compatibilidad...";

    try {
        const usuario = await obtenerUsuario(token);

        if (usuario.rol !== "adoptante") {
            mensaje.textContent =
                "Esta sección está disponible para adoptantes.";
            return;
        }

        const resultado = await obtenerCompatibilidad(token);

        mostrarResultados(resultado.data.recomendados);

        if (resultado.data.recomendados.length === 0) {
            mensaje.textContent = resultado.message;
        }
        
    } catch (error) {
        mensaje.textContent = error.message;
    }
}

// CERRAR SESIÓN
async function salirDeLaSesion() {

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
            "No se pudo cerrar sesión: " + error.message;

    } finally {
        botonCerrarSesion.disabled = false;
    }
}

// EVENTOS Y CARGA INICIAL
botonCerrarSesion.addEventListener("click", salirDeLaSesion);

cargarCompatibilidad();