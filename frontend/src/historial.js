import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import {
    obtenerHistorial,
    registrarObservacion
} from "./providers/historialProvider.js";

document.addEventListener("DOMContentLoaded", function () {
    // ELEMENTOS DEL DOM
    const tituloHistorial = document.querySelector("#tituloHistorial");
    const subtituloHistorial = document.querySelector("#subtituloHistorial");
    const mensajeHistorial = document.querySelector("#mensajeHistorial");
    const contenidoHistorial = document.querySelector("#contenidoHistorial");
    const seccionObservacion = document.querySelector("#seccionObservacion");
    const avisoHistorialBaja = document.querySelector("#avisoHistorialBaja");
    const tablaHistorial = document.querySelector("#tablaHistorial");

    const formularioObservacion = document.querySelector("#formularioObservacion");
    const descripcionObservacion = document.querySelector("#descripcionObservacion");
    const errorDescripcion = document.querySelector("#error-descripcion");
    const botonGuardarObservacion = document.querySelector("#botonGuardarObservacion");
    const mensajeObservacion = document.querySelector("#mensajeObservacion");
    const botonCerrarSesion = document.querySelector("#botonCerrarSesion");

    const rolesAutorizados = ["admin", "personal", "veterinario"];

    // NOMBRES QUE SE MUESTRAN PARA CADA TIPO DE REGISTRO
    const nombresTipos = {
        "observacion": "Observación general",
        "diagnostico": "Diagnóstico",
        "tratamiento": "Tratamiento",
        "vacunacion": "Vacunación",
        "revision": "Revisión"
    };

    // ID DEL ANIMAL QUE VIENE EN LA URL (historial.html?id=5)
    const parametros = new URLSearchParams(window.location.search);
    const idAnimal = parametros.get("id");

    // MOSTRAR UN MENSAJE AL USUARIO
    function mostrarMensaje(elemento, texto, color) {
        elemento.textContent = texto;
        elemento.style.color = color;
    }

    // CREAR UNA CELDA DE LA TABLA CON SU TEXTO
    function crearCelda(texto) {
        const celda = document.createElement("td");
        celda.style.padding = "10px";
        celda.style.verticalAlign = "top";
        celda.textContent = texto;
        return celda;
    }

    // PASAR LA FECHA A FORMATO ESPAÑOL (14/11/2026, 17:30)
    function formatearFecha(fecha) {
        const objetoFecha = new Date(fecha);
        return objetoFecha.toLocaleString("es-ES", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    // COMPROBAR SESIÓN Y PERMISOS
    async function comprobarAcceso() {
        const token = sessionStorage.getItem("token") || localStorage.getItem("token");

        if (!token) {
            window.location.href = "login.html";
            return;
        }

        if (!idAnimal) {
            mostrarMensaje(mensajeHistorial, "No se ha indicado ningún animal.", "red");
            return;
        }

        try {
            const usuario = await obtenerUsuario(token);

            if (!rolesAutorizados.includes(usuario.rol)) {
                mostrarMensaje(mensajeHistorial, "No tienes permisos para consultar el historial de los animales.", "red");
                return;
            }

            contenidoHistorial.hidden = false;
            cargarHistorial();

        } catch (error) {
            mostrarMensaje(mensajeHistorial, "No se pudo comprobar tu sesión: " + error.message, "red");
        }
    }

    // 
    // CARGAR EL HISTORIAL DESDE LA API
    // 
    async function cargarHistorial() {
        const token = sessionStorage.getItem("token") || localStorage.getItem("token");

        try {
            const resultado = await obtenerHistorial(idAnimal, token);
            const animal = resultado.data.animal;
            const registros = resultado.data.registros;

            tituloHistorial.textContent = "Historial de " + animal.nombre;
            subtituloHistorial.textContent = animal.especie + " · Estado actual: " + animal.estado;

            // Si el animal está dado de baja no se pueden añadir observaciones
            seccionObservacion.hidden = !animal.activo;
            avisoHistorialBaja.hidden = animal.activo;

            tablaHistorial.replaceChildren();

            if (registros.length === 0) {
                const filaVacia = document.createElement("tr");
                const celdaVacia = crearCelda("Este animal todavía no tiene registros en su historial.");
                celdaVacia.colSpan = 4;
                celdaVacia.style.textAlign = "center";
                filaVacia.appendChild(celdaVacia);
                tablaHistorial.appendChild(filaVacia);
                return;
            }

            for (let i = 0; i < registros.length; i++) {
                const registro = registros[i];
                const fila = document.createElement("tr");
                fila.style.borderBottom = "1px solid #eee";

                // El tipo se muestra en negrita para distinguir una observación de un diagnóstico
                const celdaTipo = crearCelda(nombresTipos[registro.tipo] || registro.tipo);
                celdaTipo.style.fontWeight = "600";

                let nombreAutor = "Usuario eliminado";
                if (registro.autor) {
                    nombreAutor = registro.autor.name + " (" + registro.autor.rol + ")";
                }

                fila.appendChild(crearCelda(formatearFecha(registro.fecha)));
                fila.appendChild(celdaTipo);
                fila.appendChild(crearCelda(registro.descripcion));
                fila.appendChild(crearCelda(nombreAutor));

                tablaHistorial.appendChild(fila);
            }

        } catch (error) {
            mostrarMensaje(mensajeHistorial, error.message, "red");
        }
    }
    // GUARDAR UNA NUEVA OBSERVACIÓN GENERAL
    if (formularioObservacion) {
        formularioObservacion.addEventListener("submit", async function (e) {
            e.preventDefault();

            const token = sessionStorage.getItem("token") || localStorage.getItem("token");
            const descripcion = descripcionObservacion.value.trim();

            errorDescripcion.textContent = "";

            if (descripcion === "") {
                errorDescripcion.textContent = "Debes escribir la observación.";
                return;
            }

            botonGuardarObservacion.disabled = true;
            mostrarMensaje(mensajeObservacion, "Guardando observación...", "#333");

            try {
                const res = await registrarObservacion(idAnimal, descripcion, token);

                mostrarMensaje(mensajeObservacion, res.message, "green");
                formularioObservacion.reset();

                // Recargar el historial para ver la observación nueva arriba del todo
                await cargarHistorial();

            } catch (error) {
                if (error.errores && error.errores.descripcion) {
                    errorDescripcion.textContent = error.errores.descripcion[0];
                    mostrarMensaje(mensajeObservacion, "Revisa la observación.", "red");
                } else {
                    mostrarMensaje(mensajeObservacion, error.message, "red");
                }
            } finally {
                botonGuardarObservacion.disabled = false;
            }
        });
    }
    // CERRAR SESIÓN
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

            sessionStorage.removeItem("token");
            localStorage.removeItem("token");
            window.location.href = "index.html";
        });
    }

    // INICIAR VERIFICACIÓN AL CARGAR LA PÁGINA
    comprobarAcceso();
});
