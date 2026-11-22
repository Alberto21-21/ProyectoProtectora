import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import {
    obtenerConversaciones,
    obtenerContactos,
    crearConversacion,
    obtenerConversacion,
    obtenerMensajes,
    enviarMensaje
} from "./providers/conversacionesProvider.js";

import {
    obtenerAnimales
} from "./providers/animalesProvider.js";

import {
    obtenerPusher,
    cerrarPusher
} from "./pusher.js";

document.addEventListener("DOMContentLoaded", function () {
    // -------------------------------------------------------------
    // ELEMENTOS DEL DOM
    // -------------------------------------------------------------
    const mensajeConversaciones = document.querySelector("#mensajeConversaciones");
    const contenidoConversaciones = document.querySelector("#contenidoConversaciones");
    const botonCerrarSesion = document.querySelector("#botonCerrarSesion");

    const listaConversaciones = document.querySelector("#listaConversaciones");
    const botonNuevaConversacion = document.querySelector("#botonNuevaConversacion");
    const avisoSinSeleccion = document.querySelector("#avisoSinSeleccion");

    const formularioNueva = document.querySelector("#formularioNuevaConversacion");
    const asuntoConversacion = document.querySelector("#asuntoConversacion");
    const animalConversacion = document.querySelector("#animalConversacion");
    const contactosConversacion = document.querySelector("#contactosConversacion");
    const mensajeInicial = document.querySelector("#mensajeInicialConversacion");
    const errorNueva = document.querySelector("#errorNuevaConversacion");
    const botonCrear = document.querySelector("#botonCrearConversacion");
    const botonCancelar = document.querySelector("#botonCancelarConversacion");

    const detalleConversacion = document.querySelector("#detalleConversacion");
    const tituloConversacion = document.querySelector("#tituloConversacion");
    const participantesConversacion = document.querySelector("#participantesConversacion");
    const animalDetalle = document.querySelector("#animalConversacionDetalle");
    const estadoTiempoReal = document.querySelector("#estadoTiempoReal");
    const listaMensajes = document.querySelector("#listaMensajes");
    const formularioMensaje = document.querySelector("#formularioMensaje");
    const textoMensaje = document.querySelector("#textoMensaje");
    const errorMensaje = document.querySelector("#errorMensaje");
    const botonEnviar = document.querySelector("#botonEnviarMensaje");

    // Solo el personal y los veterinarios usan las conversaciones
    const rolesTrabajadores = ["personal", "veterinario"];

    let token = null;
    let usuarioActual = null;
    let conversacionAbiertaId = null;

    // Tiempo real (T66)
    let nombreCanalActivo = null;
    let ultimoMensajeId = 0;
    const mensajesMostrados = new Set();

    // -------------------------------------------------------------
    // UTILIDADES
    // -------------------------------------------------------------
    function mostrarMensaje(texto, esError) {
        mensajeConversaciones.textContent = texto;
        mensajeConversaciones.className = texto
            ? "mensaje-estado " + (esError ? "mensaje-error" : "mensaje-ok")
            : "";
    }

    function formatearFecha(fechaIso) {
        if (!fechaIso) {
            return "";
        }

        return new Date(fechaIso).toLocaleString("es-ES", {
            day: "2-digit",
            month: "2-digit",
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    function nombresDeOtros(conversacion) {
        return conversacion.participantes
            .filter(function (participante) {
                return participante.id !== usuarioActual.id;
            })
            .map(function (participante) {
                return participante.name;
            })
            .join(", ");
    }

    function tituloDe(conversacion) {
        return conversacion.asunto || "Conversación con " + nombresDeOtros(conversacion);
    }

    function recortar(texto, maximo) {
        return texto.length > maximo ? texto.slice(0, maximo) + "…" : texto;
    }

    // MOSTRAR SOLO UNO DE LOS TRES PANELES DE LA DERECHA
    function mostrarPanel(panel) {
        avisoSinSeleccion.hidden = panel !== "aviso";
        formularioNueva.hidden = panel !== "nueva";
        detalleConversacion.hidden = panel !== "detalle";
    }

    // -------------------------------------------------------------
    // LISTA DE CONVERSACIONES
    // -------------------------------------------------------------
    function crearElementoConversacion(conversacion) {
        const elemento = document.createElement("li");

        const boton = document.createElement("button");
        boton.type = "button";
        boton.className = "item-conversacion";
        boton.dataset.id = conversacion.id;

        if (conversacion.id === conversacionAbiertaId) {
            boton.setAttribute("aria-current", "true");
        }

        const titulo = document.createElement("strong");
        titulo.textContent = tituloDe(conversacion);
        boton.appendChild(titulo);

        if (conversacion.animal) {
            const animal = document.createElement("span");
            animal.className = "animal-conversacion";
            animal.textContent = "Animal: " + conversacion.animal.nombre;
            boton.appendChild(animal);
        }

        const ultimo = document.createElement("span");
        ultimo.className = "ultimo-mensaje";
        ultimo.textContent = conversacion.ultimo_mensaje
            ? recortar(conversacion.ultimo_mensaje.texto, 60)
            : "Sin mensajes todavía";
        boton.appendChild(ultimo);

        const fecha = document.createElement("small");
        fecha.textContent = formatearFecha(conversacion.ultimo_mensaje_at);
        boton.appendChild(fecha);

        boton.addEventListener("click", function () {
            abrirConversacion(conversacion.id);
        });

        elemento.appendChild(boton);
        return elemento;
    }

    async function cargarConversaciones() {
        try {
            const resultado = await obtenerConversaciones(token);
            const conversaciones = resultado.data;

            listaConversaciones.replaceChildren();

            if (conversaciones.length === 0) {
                const vacio = document.createElement("li");
                vacio.className = "aviso-chat";
                vacio.textContent = "Todavía no tienes conversaciones.";
                listaConversaciones.appendChild(vacio);
                return;
            }

            conversaciones.forEach(function (conversacion) {
                listaConversaciones.appendChild(crearElementoConversacion(conversacion));
            });

        } catch (error) {
            mostrarMensaje("No se pudieron cargar las conversaciones: " + error.message, true);
        }
    }

    // -------------------------------------------------------------
    // DETALLE DE UNA CONVERSACIÓN
    // -------------------------------------------------------------
    function crearElementoMensaje(mensaje) {
        const elemento = document.createElement("li");
        elemento.className = "mensaje-chat";
        elemento.dataset.id = mensaje.id;

        if (mensaje.emisor.id === usuarioActual.id) {
            elemento.classList.add("mensaje-propio");
        }

        const meta = document.createElement("span");
        meta.className = "mensaje-meta";
        meta.textContent = mensaje.emisor.name + " · " + formatearFecha(mensaje.created_at);

        const texto = document.createElement("p");
        texto.textContent = mensaje.texto;

        elemento.appendChild(meta);
        elemento.appendChild(texto);
        return elemento;
    }

    function pintarMensajes(mensajes) {
        listaMensajes.replaceChildren();
        mensajesMostrados.clear();
        ultimoMensajeId = 0;

        mensajes.forEach(function (mensaje) {
            mensajesMostrados.add(mensaje.id);
            ultimoMensajeId = Math.max(ultimoMensajeId, mensaje.id);
        });

        if (mensajes.length === 0) {
            const vacio = document.createElement("li");
            vacio.className = "aviso-chat";
            vacio.textContent = "Todavía no hay mensajes.";
            listaMensajes.appendChild(vacio);
            return;
        }

        mensajes.forEach(function (mensaje) {
            listaMensajes.appendChild(crearElementoMensaje(mensaje));
        });

        listaMensajes.scrollTop = listaMensajes.scrollHeight;
    }

    // AÑADIR UN MENSAJE A LA CONVERSACIÓN ABIERTA SIN REPETIRLO
    // (puede llegar por WebSocket y también en la respuesta de la API)
    function anadirMensaje(mensaje) {
        if (mensajesMostrados.has(mensaje.id)) {
            return;
        }

        mensajesMostrados.add(mensaje.id);
        ultimoMensajeId = Math.max(ultimoMensajeId, mensaje.id);

        // Quitar el aviso de "sin mensajes" si estaba
        const aviso = listaMensajes.querySelector(".aviso-chat");
        if (aviso) {
            aviso.remove();
        }

        listaMensajes.appendChild(crearElementoMensaje(mensaje));
        listaMensajes.scrollTop = listaMensajes.scrollHeight;
    }

    function mostrarEstadoTiempoReal(estado) {
        if (!estadoTiempoReal) {
            return;
        }

        const conectado = estado === "connected";
                estadoTiempoReal.textContent = conectado
            ? "Conectado"
            : "Reconectando… los mensajes se recuperarán al volver.";
        estadoTiempoReal.className = "estado-tiempo-real " +
            (conectado ? "estado-conectado" : "estado-desconectado");
    }

    // RECUPERAR LOS MENSAJES GUARDADOS QUE SE HAYAN PERDIDO (al reconectar)
    async function sincronizarMensajes(idConversacion) {
        try {
            const resultado = await obtenerMensajes(idConversacion, token, {
                despues_de: ultimoMensajeId
            });

            if (idConversacion !== conversacionAbiertaId) {
                return;
            }

            resultado.data.forEach(anadirMensaje);

        } catch (error) {
            console.error("No se pudo sincronizar la conversación:", error);
        }
    }

    // MENSAJE RECIBIDO POR EL CANAL PRIVADO
    function recibirMensaje(datos) {
        const mensaje = datos && datos.mensaje;

        if (!mensaje) {
            return;
        }

        if (mensaje.conversacion_id === conversacionAbiertaId) {
            anadirMensaje(mensaje);
        }

        cargarConversaciones();
    }

    // CAMBIAR DE CANAL: solo se escucha la conversación abierta
    function suscribirConversacion(idConversacion) {
        const pusher = obtenerPusher();

        if (!pusher) {
            return;
        }

        if (nombreCanalActivo) {
            pusher.unsubscribe(nombreCanalActivo);
        }

        nombreCanalActivo = "private-conversacion." + idConversacion;

        const canal = pusher.subscribe(nombreCanalActivo);
        canal.bind("mensaje.enviado", recibirMensaje);

        // Se dispara al suscribirse y cada vez que Pusher se vuelve a conectar
        canal.bind("pusher:subscription_succeeded", function () {
            sincronizarMensajes(idConversacion);
        });

        canal.bind("pusher:subscription_error", function () {
            mostrarMensaje(
                "No se pudo abrir el canal en tiempo real de esta conversación.",
                true
            );
        });
    }

    function escucharConexion() {
        const pusher = obtenerPusher();

        if (!pusher) {
            return;
        }

        mostrarEstadoTiempoReal(pusher.connection.state);

        pusher.connection.bind("state_change", function (estados) {
            mostrarEstadoTiempoReal(estados.current);
        });
    }

    async function abrirConversacion(id) {
        mostrarMensaje("", false);
        errorMensaje.textContent = "";

        try {
            const [detalle, mensajes] = await Promise.all([
                obtenerConversacion(id, token),
                obtenerMensajes(id, token)
            ]);

            const conversacion = detalle.data;
            conversacionAbiertaId = conversacion.id;

            tituloConversacion.textContent = tituloDe(conversacion);
            participantesConversacion.textContent = "Participantes: " +
                conversacion.participantes.map(function (participante) {
                    return participante.name + " (" + participante.rol + ")";
                }).join(", ");

            animalDetalle.textContent = conversacion.animal
                ? "Animal: " + conversacion.animal.nombre + " (" + conversacion.animal.especie + ")"
                : "";
            animalDetalle.hidden = !conversacion.animal;

            pintarMensajes(mensajes.data);
            mostrarPanel("detalle");
            suscribirConversacion(conversacion.id);
            await cargarConversaciones();

        } catch (error) {
            mostrarMensaje("No se pudo abrir la conversación: " + error.message, true);
        }
    }

    // -------------------------------------------------------------
    // ENVIAR UN MENSAJE
    // -------------------------------------------------------------
    formularioMensaje.addEventListener("submit", async function (evento) {
        evento.preventDefault();
        errorMensaje.textContent = "";

        const texto = textoMensaje.value.trim();

        if (texto === "") {
            errorMensaje.textContent = "Escribe un mensaje.";
            return;
        }

        botonEnviar.disabled = true;

        try {
            const resultado = await enviarMensaje(conversacionAbiertaId, texto, token);

            anadirMensaje(resultado.data);
            textoMensaje.value = "";

            await cargarConversaciones();

        } catch (error) {
            errorMensaje.textContent = error.message;
        } finally {
            botonEnviar.disabled = false;
        }
    });

    // -------------------------------------------------------------
    // NUEVA CONVERSACIÓN
    // -------------------------------------------------------------
    async function prepararFormularioNueva() {
        errorNueva.textContent = "";
        formularioNueva.reset();
        contactosConversacion.replaceChildren();
        animalConversacion.replaceChildren(new Option("Sin animal", ""));

        try {
            const [contactos, animales] = await Promise.all([
                obtenerContactos(token),
                obtenerAnimales({}, token)
            ]);

            if (contactos.data.length === 0) {
                const aviso = document.createElement("p");
                aviso.className = "aviso-chat";
                aviso.textContent = "No hay otras personas con ficha laboral activa.";
                contactosConversacion.appendChild(aviso);
            }

            contactos.data.forEach(function (contacto) {
                const etiqueta = document.createElement("label");
                etiqueta.className = "contacto-conversacion";

                const casilla = document.createElement("input");
                casilla.type = "checkbox";
                casilla.value = contacto.id;

                const texto = document.createElement("span");
                texto.textContent = contacto.name + " (" + contacto.rol + ")";

                etiqueta.appendChild(casilla);
                etiqueta.appendChild(texto);
                contactosConversacion.appendChild(etiqueta);
            });

            animales.forEach(function (animal) {
                animalConversacion.appendChild(
                    new Option(animal.nombre + " (" + animal.especie + ")", animal.id)
                );
            });

        } catch (error) {
            errorNueva.textContent = "No se pudo preparar el formulario: " + error.message;
        }
    }

    botonNuevaConversacion.addEventListener("click", async function () {
        conversacionAbiertaId = null;

        if (nombreCanalActivo) {
            const pusher = obtenerPusher();

            if (pusher) {
                pusher.unsubscribe(nombreCanalActivo);
            }

            nombreCanalActivo = null;
        }

        mostrarPanel("nueva");
        await prepararFormularioNueva();
        await cargarConversaciones();
    });

    botonCancelar.addEventListener("click", function () {
        mostrarPanel("aviso");
    });

    formularioNueva.addEventListener("submit", async function (evento) {
        evento.preventDefault();
        errorNueva.textContent = "";

        const participantes = Array.from(
            contactosConversacion.querySelectorAll("input[type='checkbox']:checked")
        ).map(function (casilla) {
            return Number(casilla.value);
        });

        if (participantes.length === 0) {
            errorNueva.textContent = "Selecciona al menos un participante.";
            return;
        }

        const datos = { participantes: participantes };

        if (asuntoConversacion.value.trim() !== "") {
            datos.asunto = asuntoConversacion.value.trim();
        }

        if (animalConversacion.value !== "") {
            datos.animal_id = Number(animalConversacion.value);
        }

        if (mensajeInicial.value.trim() !== "") {
            datos.mensaje = mensajeInicial.value.trim();
        }

        botonCrear.disabled = true;

        try {
            const resultado = await crearConversacion(datos, token);
            await abrirConversacion(resultado.data.id);

        } catch (error) {
            errorNueva.textContent = error.message;
        } finally {
            botonCrear.disabled = false;
        }
    });

    // -------------------------------------------------------------
    // COMPROBAR EL ACCESO Y CARGAR LA PÁGINA
    // -------------------------------------------------------------
    async function comprobarAcceso() {
        token = sessionStorage.getItem("token") || localStorage.getItem("token");

        if (!token) {
            window.location.href = "login.html";
            return;
        }

        try {
            usuarioActual = await obtenerUsuario(token);

            if (!rolesTrabajadores.includes(usuarioActual.rol)) {
                mostrarMensaje(
                    "Las conversaciones son solo para el personal y los veterinarios.",
                    true
                );
                return;
            }

            contenidoConversaciones.hidden = false;
            mostrarPanel("aviso");
            escucharConexion();
            await cargarConversaciones();

        } catch (error) {
            mostrarMensaje("No se pudo comprobar tu sesión: " + error.message, true);
        }
    }

    // -------------------------------------------------------------
    // CERRAR SESIÓN
    // -------------------------------------------------------------
    if (botonCerrarSesion) {
        botonCerrarSesion.addEventListener("click", async function () {
            const tokenActual = sessionStorage.getItem("token") || localStorage.getItem("token");

            botonCerrarSesion.disabled = true;

            if (tokenActual) {
                try {
                    await cerrarSesion(tokenActual);
                } catch (error) {
                    console.error("Error al cerrar sesión en el servidor:", error);
                }
            }

            cerrarPusher();
            sessionStorage.removeItem("token");
            localStorage.removeItem("token");
            window.location.href = "index.html";
        });
    }

    comprobarAcceso();
});
