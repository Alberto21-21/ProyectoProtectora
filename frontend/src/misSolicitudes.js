import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import {
    obtenerMisSolicitudes,
    cancelarSolicitud
} from "./providers/solicitudesProvider.js";

import { obtenerAnimal } from "./providers/animalesProvider.js";

// ELEMENTOS DE LA PÁGINA
const mensaje = document.querySelector("#mensajeMisSolicitudes");
const seccion = document.querySelector("#seccionMisSolicitudes");
const tabla = document.querySelector("#tablaMisSolicitudes");
const botonCerrarSesion = document.querySelector("#botonCerrarSesion");

// MODAL DE LA FICHA DEL ANIMAL
const modalAnimal = document.querySelector("#modalAnimalSolicitud");
const cerrarAnimal = document.querySelector("#cerrarAnimalSolicitud");
const tituloAnimal = document.querySelector("#tituloAnimalSolicitud");
const mensajeAnimal = document.querySelector("#mensajeAnimalSolicitud");
const contenidoAnimal = document.querySelector("#contenidoAnimalSolicitud");
const fotoAnimal = document.querySelector("#fotoAnimalSolicitud");
const especieAnimal = document.querySelector("#especieAnimalSolicitud");
const razaAnimal = document.querySelector("#razaAnimalSolicitud");
const sexoAnimal = document.querySelector("#sexoAnimalSolicitud");
const estadoAnimal = document.querySelector("#estadoAnimalSolicitud");
const historiaAnimal = document.querySelector("#historiaAnimalSolicitud");
const cuidadosAnimal = document.querySelector("#cuidadosAnimalSolicitud");

// MOSTRAR LAS FECHAS
function formatearFecha(fecha) {

    if (!fecha) {
        return "Sin fecha";
    }

    return new Date(fecha).toLocaleString("es-ES");
}

// MOSTRAR LAS SOLICITUDES
function mostrarSolicitudes(solicitudes) {

    tabla.replaceChildren();

    if (solicitudes.length === 0) {
        mensaje.textContent = "Todavía no has enviado ninguna solicitud.";
        seccion.hidden = true;
        return;
    }

    solicitudes.forEach(function (solicitud) {

        const fila = document.createElement("tr");

        const nombre = document.createElement("td");
        nombre.textContent = solicitud.animal
            ? solicitud.animal.nombre
            : "Animal no disponible";

        const estado = document.createElement("td");
        const etiquetaEstado = document.createElement("span");

        etiquetaEstado.textContent = solicitud.estado;
        etiquetaEstado.className = "estado-solicitud";

        if (solicitud.estado === "pendiente") {
            etiquetaEstado.classList.add("estado-pendiente");

        } else if (solicitud.estado === "en_revision") {
            etiquetaEstado.textContent = "En revisión";
            etiquetaEstado.classList.add("estado-revision");

        } else if (solicitud.estado === "aprobada") {
            etiquetaEstado.classList.add("estado-aprobada");

        } else if (solicitud.estado === "rechazada") {
            etiquetaEstado.classList.add("estado-rechazada");

        } else if (solicitud.estado === "cancelada") {
            etiquetaEstado.classList.add("estado-cancelada");
        }
        
        estado.appendChild(etiquetaEstado);

        const fechaSolicitud = document.createElement("td");
        fechaSolicitud.textContent = formatearFecha(solicitud.created_at);

        const fechaActualizacion = document.createElement("td");
        fechaActualizacion.textContent = formatearFecha(solicitud.updated_at);

        const acciones = document.createElement("td");

        if (solicitud.animal) {
            const botonFicha = document.createElement("button");

            botonFicha.type = "button";
            botonFicha.textContent = "Ver ficha";
            botonFicha.className =
                "enlace-ficha-compatibilidad boton-ficha-solicitud";

            botonFicha.addEventListener("click", function () {
                abrirFichaAnimal(solicitud.animal_id);
            });

            acciones.appendChild(botonFicha);
        }

        // SOLO SE PUEDEN CANCELAR SOLICITUDES EN CURSO
        if (
            solicitud.estado === "pendiente"
            || solicitud.estado === "en_revision"
        ) {
            const botonCancelar = document.createElement("button");

            botonCancelar.type = "button";
            botonCancelar.className = "boton-cancelar-solicitud";
            botonCancelar.title = "Cancelar solicitud";
            botonCancelar.setAttribute("aria-label", "Cancelar solicitud");

            botonCancelar.textContent = "×";

            botonCancelar.addEventListener("click", async function () {

                const confirmar = window.confirm(
                    "¿Quieres cancelar esta solicitud de adopción?"
                );

                if (!confirmar) {
                    return;
                }

                const token = sessionStorage.getItem("token");

                if (!token) {
                    window.location.href = "index.html";
                    return;
                }

                botonCancelar.disabled = true;
                mensaje.textContent = "Cancelando solicitud...";

                try {
                    const resultado = await cancelarSolicitud(
                        solicitud.id,
                        token
                    );

                    solicitud.estado = resultado.data.estado;
                    solicitud.updated_at = resultado.data.updated_at;

                    mostrarSolicitudes(solicitudes);
                    mensaje.textContent = resultado.message;

                } catch (error) {
                    mensaje.textContent = error.message;
                    botonCancelar.disabled = false;
                }
            });

            acciones.appendChild(botonCancelar);
        }

        fila.append(
            nombre,
            estado,
            fechaSolicitud,
            fechaActualizacion,
            acciones
        );

        tabla.appendChild(fila);
    });

    mensaje.textContent = "";
    seccion.hidden = false;
}

// ABRIR LA FICHA SIN SALIR DE MIS SOLICITUDES
async function abrirFichaAnimal(idAnimal) {

    const token = sessionStorage.getItem("token");

    tituloAnimal.textContent = "Ficha del animal";
    mensajeAnimal.textContent = "Cargando ficha...";
    contenidoAnimal.hidden = true;
    fotoAnimal.hidden = true;
    fotoAnimal.removeAttribute("src");

    if (!modalAnimal.open) {
        modalAnimal.showModal();
    }

    modalAnimal.scrollTop = 0;

    try {
        const animal = await obtenerAnimal(idAnimal, token);

        tituloAnimal.textContent = "Ficha de " + animal.nombre;
        especieAnimal.textContent = animal.especie || "No indicada";
        razaAnimal.textContent = animal.raza || "No indicada";
        sexoAnimal.textContent = animal.sexo || "No indicado";
        estadoAnimal.textContent = animal.estado || "No indicado";

        historiaAnimal.textContent = animal.historia_publica
            || "Todavía no se ha publicado su historia.";

        cuidadosAnimal.textContent = animal.cuidados_publicos
            || "Consulta con la protectora los cuidados que necesita.";

        const rutaImagen = animal.imagen || animal.fotografia;

        let urlImagen = animal.imagen_url || animal.fotografia_url;

        if (!urlImagen && rutaImagen) {
            urlImagen = rutaImagen.startsWith("https://")
                ? rutaImagen
                : "http://127.0.0.1:8000/storage/" + rutaImagen;
        }

        if (urlImagen) {
            fotoAnimal.src = urlImagen;
            fotoAnimal.alt = "Fotografía de " + animal.nombre;
            fotoAnimal.hidden = false;
        }

        mensajeAnimal.textContent = "";
        contenidoAnimal.hidden = false;

    } catch (error) {
        mensajeAnimal.textContent = error.message;
    }
}

// CARGAR SOLO LAS SOLICITUDES PROPIAS
async function cargarSolicitudes() {

    seccion.hidden = true;
    tabla.replaceChildren();

    const token = sessionStorage.getItem("token");

    if (!token) {
        window.location.href = "index.html";
        return;
    }

    mensaje.textContent = "Cargando tus solicitudes...";

    try {
        const usuario = await obtenerUsuario(token);

        if (usuario.rol !== "adoptante") {
            mensaje.textContent =
                "Esta sección está disponible para adoptantes.";
            return;
        }

        const resultado = await obtenerMisSolicitudes(token);

        mostrarSolicitudes(resultado.data);

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

// EVENTOS
botonCerrarSesion.addEventListener("click", salirDeLaSesion);
window.addEventListener("pageshow", cargarSolicitudes);
cerrarAnimal.addEventListener("click", function () {
    modalAnimal.close();
});