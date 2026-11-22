import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import {
    obtenerSolicitudesRevision,
    obtenerDetalleSolicitudRevision,
    guardarObservacionesSolicitud,
    registrarSeguimiento
} from "./providers/solicitudesProvider.js";

import { obtenerAnimal } from "./providers/animalesProvider.js";

// ELEMENTOS DE LA PÁGINA
const mensaje = document.querySelector("#mensajeRevision");
const contenido = document.querySelector("#contenidoRevision");
const tabla = document.querySelector("#tablaRevision");
const botonCerrarSesion = document.querySelector("#botonCerrarSesion");

// FILTROS
const formularioFiltros = document.querySelector("#formularioFiltrosRevision");
const buscar = document.querySelector("#buscarRevision");
const estado = document.querySelector("#estadoRevision");
const botonBuscar = document.querySelector("#botonBuscarRevision");
const limpiarFiltros = document.querySelector("#limpiarFiltrosRevision");

// PAGINACIÓN
const botonAnterior = document.querySelector("#paginaAnteriorRevision");
const botonSiguiente = document.querySelector("#paginaSiguienteRevision");
const numeroPagina = document.querySelector("#numeroPaginaRevision");

// MODAL DEL DETALLE
const modal = document.querySelector("#modalRevision");
const cerrarModal = document.querySelector("#cerrarModalRevision");
const tituloDetalle = document.querySelector("#tituloDetalleRevision");
const mensajeDetalle = document.querySelector("#mensajeDetalleRevision");
const contenidoDetalle = document.querySelector("#contenidoDetalleRevision");
const datosSolicitud = document.querySelector("#datosSolicitudRevision");
const datosAnimal = document.querySelector("#datosAnimalRevision");
const datosHogar = document.querySelector("#datosHogarRevision");

// ELEMENTOS DEL SEGUIMIENTO
const formularioSeguimiento = document.querySelector("#formularioSeguimiento");
const tipoSeguimiento = document.querySelector("#tipoSeguimiento");
const observacionesSeguimiento = document.querySelector("#observacionesSeguimiento");
const mensajeSeguimiento = document.querySelector("#mensajeSeguimiento");
const guardarSeguimiento = document.querySelector("#guardarSeguimiento");
const historialSeguimientos = document.querySelector("#historialSeguimientos");

// OBSERVACIONES INTERNAS
const formularioObservaciones = document.querySelector("#formularioObservacionesRevision");
const observacionesInternas = document.querySelector("#observacionesInternasRevision");
const mensajeObservaciones = document.querySelector("#mensajeObservacionesRevision");
const botonGuardarObservaciones = document.querySelector("#guardarObservacionesRevision");

// FICHA DEL ANIMAL
const modalAnimal = document.querySelector("#modalAnimalRevision");
const cerrarAnimal = document.querySelector("#cerrarAnimalRevision");
const tituloAnimal = document.querySelector("#tituloAnimalRevision");
const mensajeAnimal = document.querySelector("#mensajeAnimalRevision");
const contenidoAnimal = document.querySelector("#contenidoAnimalRevision");
const fotoAnimal = document.querySelector("#fotoAnimalRevision");
const datosFichaAnimal = document.querySelector("#datosFichaAnimalRevision");
const historiaAnimal = document.querySelector("#historiaAnimalRevision");
const cuidadosAnimal = document.querySelector("#cuidadosAnimalRevision");

// ESTADO DE LA PÁGINA
let paginaActual = 1;
let ultimaPagina = 1;
let solicitudSeleccionadaId = null;
let mostrandoFichaAnimal = false;
let posicionDetalle = 0;

// MOSTRAR LAS FECHAS
function formatearFecha(fecha) {

    if (!fecha) {
        return "Sin fecha";
    }

    return new Date(fecha).toLocaleString("es-ES");
}

// MOSTRAR EL LISTADO DE SOLICITUDES
function mostrarSolicitudes(solicitudes) {

    tabla.replaceChildren();

    solicitudes.forEach(function (solicitud) {

        const fila = document.createElement("tr");

        const adoptante = document.createElement("td");
        adoptante.textContent = solicitud.usuario
            ? solicitud.usuario.name
            : "Usuario no disponible";

        const animal = document.createElement("td");
        animal.textContent = solicitud.animal
            ? solicitud.animal.nombre
            : "Animal no disponible";

        const celdaEstado = document.createElement("td");
        const etiquetaEstado = document.createElement("span");

        etiquetaEstado.textContent = solicitud.estado;
        etiquetaEstado.className = "estado-solicitud";

        if (solicitud.estado === "pendiente") {
            etiquetaEstado.textContent = "Pendiente";
            etiquetaEstado.classList.add("estado-pendiente");

        } else if (solicitud.estado === "en_revision") {
            etiquetaEstado.textContent = "En revisión";
            etiquetaEstado.classList.add("estado-revision");

        } else if (solicitud.estado === "aprobada") {
            etiquetaEstado.textContent = "Aprobada";
            etiquetaEstado.classList.add("estado-aprobada");

        } else if (solicitud.estado === "rechazada") {
            etiquetaEstado.textContent = "Rechazada";
            etiquetaEstado.classList.add("estado-rechazada");

        } else if (solicitud.estado === "cancelada") {
            etiquetaEstado.textContent = "Cancelada";
            etiquetaEstado.classList.add("estado-cancelada");
        }

        celdaEstado.appendChild(etiquetaEstado);

        const fecha = document.createElement("td");
        fecha.textContent = formatearFecha(solicitud.created_at);

        const acciones = document.createElement("td");
        const botonDetalle = document.createElement("button");

        botonDetalle.type = "button";
        botonDetalle.textContent = "Revisar";
        botonDetalle.className =
            "enlace-ficha-compatibilidad boton-ficha-solicitud";

        botonDetalle.addEventListener("click", function () {
            abrirDetalleSolicitud(solicitud.id);
        });

        acciones.appendChild(botonDetalle);

        fila.append(
            adoptante,
            animal,
            celdaEstado,
            fecha,
            acciones
        );

        tabla.appendChild(fila);
    });
}

// CARGAR LAS SOLICITUDES PARA REVISIÓN
async function cargarSolicitudes() {

    const token = sessionStorage.getItem("token");

    if (!token) {
        window.location.href = "index.html";
        return;
    }

    mensaje.textContent = "Cargando solicitudes...";
    botonBuscar.disabled = true;
    botonAnterior.disabled = true;
    botonSiguiente.disabled = true;

    try {
        const filtros = {
            buscar: buscar.value.trim(),
            estado: estado.value,
            page: paginaActual
        };

        const resultado = await obtenerSolicitudesRevision(filtros, token);
        const listado = resultado.data;

        mostrarSolicitudes(listado.data);

        paginaActual = listado.current_page;
        ultimaPagina = listado.last_page;

        numeroPagina.textContent =
            "Página " + paginaActual + " de " + ultimaPagina;

        botonAnterior.disabled = paginaActual <= 1;
        botonSiguiente.disabled = paginaActual >= ultimaPagina;

        mensaje.textContent = listado.total === 0
            ? "No hay solicitudes con esos filtros."
            : "Solicitudes encontradas: " + listado.total;

    } catch (error) {
        tabla.replaceChildren();
        numeroPagina.textContent = "";
        mensaje.textContent = error.message;

    } finally {
        botonBuscar.disabled = false;
    }
}

// COMPROBAR EL ACCESO A LA PANTALLA
async function comprobarAcceso() {

    contenido.hidden = true;

    const token = sessionStorage.getItem("token");

    if (!token) {
        window.location.href = "index.html";
        return;
    }

    mensaje.textContent = "Comprobando acceso...";

    try {
        const usuario = await obtenerUsuario(token);

        if (usuario.rol !== "admin") {
            mensaje.textContent =
                "No tienes permiso para revisar las solicitudes.";
            return;
        }

        contenido.hidden = false;
        await cargarSolicitudes();

    } catch (error) {
        mensaje.textContent = error.message;
    }
}

// MOSTRAR UN DATO EN EL DETALLE
function mostrarDato(contenedor, etiqueta, valor) {

    const bloque = document.createElement("div");
    bloque.className = "dato-revision";

    const titulo = document.createElement("strong");
    titulo.textContent = etiqueta;

    const texto = document.createElement("p");

    if (valor === null || valor === undefined || valor === "") {
        texto.textContent = "No indicado";
    } else {
        texto.textContent = valor;
    }

    bloque.append(titulo, texto);
    contenedor.appendChild(bloque);
}

// MOSTRAR LAS ENTREVISTAS Y DECISIONES
function mostrarSeguimientos(seguimientos) {

    historialSeguimientos.replaceChildren();

    if (seguimientos.length === 0) {
        const aviso = document.createElement("p");
        aviso.textContent = "Todavía no hay actuaciones registradas.";

        historialSeguimientos.appendChild(aviso);
        return;
    }

    seguimientos.forEach(function (seguimiento) {

        const bloque = document.createElement("article");
        bloque.className = "actuacion-solicitud";

        const titulo = document.createElement("h4");

        if (seguimiento.tipo === "entrevista") {
            titulo.textContent = "Entrevista";
        } else if (seguimiento.tipo === "aprobacion") {
            titulo.textContent = "Aprobación";
        } else {
            titulo.textContent = "Rechazo";
        }

        const responsable = document.createElement("p");

        responsable.textContent = "Responsable: " +
            (seguimiento.responsable
                ? seguimiento.responsable.name
                : "No indicado");

        const fecha = document.createElement("p");
        fecha.textContent = "Fecha: " + seguimiento.fecha;

        const cambioEstado = document.createElement("p");
        cambioEstado.textContent =
            seguimiento.estado_anterior + " → " +
            seguimiento.estado_nuevo;

        const observaciones = document.createElement("p");
        observaciones.textContent = seguimiento.observaciones;

        bloque.append(
            titulo,
            responsable,
            fecha,
            cambioEstado,
            observaciones
        );

        historialSeguimientos.appendChild(bloque);
    });
}

// ABRIR EL DETALLE DE UNA SOLICITUD
async function abrirDetalleSolicitud(id) {

    const token = sessionStorage.getItem("token");

    if (!token) {
        window.location.href = "index.html";
        return;
    }

    solicitudSeleccionadaId = id;

    tituloDetalle.textContent = "Detalle de la solicitud";
    mensajeDetalle.textContent = "Cargando solicitud...";
    contenidoDetalle.hidden = true;

    datosSolicitud.replaceChildren();
    datosAnimal.replaceChildren();
    datosHogar.replaceChildren();

    formularioObservaciones.reset();
    mensajeObservaciones.textContent = "";
    botonGuardarObservaciones.disabled = true;

    if (!modal.open) {
        modal.showModal();
    }

    modal.scrollTop = 0;

    try {
        const resultado = await obtenerDetalleSolicitudRevision(id, token);

        if (solicitudSeleccionadaId !== id || !modal.open) {
            return;
        }

        const solicitud = resultado.data;
        const usuario = solicitud.usuario;
        const animal = solicitud.animal;

        tituloDetalle.textContent = "Solicitud nº " + solicitud.id;

        mostrarDato(datosSolicitud, "Estado", solicitud.estado);
        mostrarDato(
            datosSolicitud,
            "Fecha de solicitud",
            formatearFecha(solicitud.created_at)
        );

        if (usuario) {
            mostrarDato(datosSolicitud, "Nombre", usuario.name);
            mostrarDato(datosSolicitud, "Correo electrónico", usuario.email);
            mostrarDato(
                datosSolicitud,
                "Fecha de nacimiento",
                usuario.fecha_nacimiento
            );
        }

        mostrarDato(
            datosSolicitud,
            "Observaciones del adoptante",
            solicitud.observaciones
        );

        if (animal) {
            mostrarDato(datosAnimal, "Nombre", animal.nombre);
            mostrarDato(datosAnimal, "Especie", animal.especie);
            mostrarDato(datosAnimal, "Raza", animal.raza);
            mostrarDato(datosAnimal, "Estado actual", animal.estado);

        const botonFicha = document.createElement("button");

        botonFicha.type = "button";
        botonFicha.textContent = "Ver ficha del animal";
        botonFicha.className = "boton-ficha-revision";

        botonFicha.addEventListener("click", function () {
            abrirFichaAnimal(animal.id);
        });

            datosAnimal.appendChild(botonFicha);
        }

        mostrarHogar(usuario ? usuario.hogar : null);

        observacionesInternas.value =
            solicitud.observaciones_internas || "";

        formularioSeguimiento.reset();
        mensajeSeguimiento.textContent = "";

        mostrarSeguimientos(solicitud.seguimientos || []);

        const admiteActuaciones =
            solicitud.estado === "pendiente" ||
            solicitud.estado === "en_revision";

        formularioSeguimiento.hidden = !admiteActuaciones;

        mensajeDetalle.textContent = "";
        contenidoDetalle.hidden = false;
        botonGuardarObservaciones.disabled = false;

    } catch (error) {
        if (solicitudSeleccionadaId === id && modal.open) {
            mensajeDetalle.textContent = error.message;
        }
    }
}

// MOSTRAR EL HOGAR Y LAS PREFERENCIAS DEL ADOPTANTE
function mostrarHogar(hogar) {

    datosHogar.replaceChildren();

    if (!hogar) {
        const aviso = document.createElement("p");
        aviso.textContent = "El adoptante todavía no ha completado su hogar.";
        datosHogar.appendChild(aviso);
        return;
    }

    mostrarDato(datosHogar, "Tipo de vivienda", hogar.tipo_vivienda);
    mostrarDato(datosHogar, "Superficie (m²)", hogar.superficie);
    mostrarDato(datosHogar, "Jardín", mostrarSiNo(hogar.tiene_jardin));
    mostrarDato(datosHogar, "Terraza", mostrarSiNo(hogar.tiene_terraza));

    mostrarDato(
        datosHogar,
        "Superficie exterior (m²)",
        hogar.superficie_exterior
    );

    mostrarDato(datosHogar, "Personas en el hogar", hogar.numero_personas);
    mostrarDato(datosHogar, "Niños", mostrarSiNo(hogar.hay_ninos));

    if (Array.isArray(hogar.edades_ninos)) {
        mostrarDato(
            datosHogar,
            "Edades de los niños",
            hogar.edades_ninos.join(", ")
        );
    }

    mostrarDato(
        datosHogar,
        "Convive con otros animales",
        mostrarSiNo(hogar.hay_otros_animales)
    );

    mostrarDato(datosHogar, "Otros animales", hogar.otros_animales);
    mostrarDato(datosHogar, "Experiencia", hogar.experiencia);

    mostrarDato(
        datosHogar,
        "Tiempo diario para paseos (minutos)",
        hogar.minutos_paseos
    );

    mostrarDato(
        datosHogar,
        "Tiempo diario para cuidados (minutos)",
        hogar.minutos_cuidados
    );

    mostrarDato(datosHogar, "Nivel de actividad", hogar.nivel_actividad);
    mostrarDato(datosHogar, "Especie preferida", hogar.especie_preferida);
    mostrarDato(datosHogar, "Tamaño preferido", hogar.tamano_preferido);
    mostrarDato(datosHogar, "Edad mínima preferida", hogar.edad_minima);
    mostrarDato(datosHogar, "Edad máxima preferida", hogar.edad_maxima);

    mostrarDato(
        datosHogar,
        "Puede asumir necesidades especiales",
        mostrarSiNo(hogar.acepta_necesidades_especiales)
    );

    mostrarDato(
        datosHogar,
        "Cuidados que puede asumir",
        hogar.cuidados_asumibles
    );
}

// MOSTRAR LOS VALORES SÍ Y NO
function mostrarSiNo(valor) {

    if (valor === true || valor === 1 || valor === "1") {
        return "Sí";
    }

    if (valor === false || valor === 0 || valor === "0") {
        return "No";
    }

    return "No indicado";
}

// GUARDAR UNA ENTREVISTA O DECISIÓN
async function enviarSeguimiento(evento) {

    evento.preventDefault();

    const token = sessionStorage.getItem("token");
    const id = solicitudSeleccionadaId;

    if (!token || !id) {
        mensajeSeguimiento.textContent =
            "Debes iniciar sesión y seleccionar una solicitud.";
        return;
    }

    const datos = {
        tipo: tipoSeguimiento.value,
        observaciones: observacionesSeguimiento.value.trim()
    };

    mensajeSeguimiento.textContent = "Guardando actuación...";
    guardarSeguimiento.disabled = true;

    try {
        const resultado = await registrarSeguimiento(id, datos, token);

        if (solicitudSeleccionadaId === id && modal.open) {
            await abrirDetalleSolicitud(id);

            if (solicitudSeleccionadaId === id && modal.open) {
                mensajeSeguimiento.textContent = resultado.message;
            }
        }

        await cargarSolicitudes();

    } catch (error) {
        if (solicitudSeleccionadaId === id && modal.open) {
            mensajeSeguimiento.textContent = error.message;
        }

    } finally {
        guardarSeguimiento.disabled = false;
    }
}

// GUARDAR LAS OBSERVACIONES INTERNAS
async function guardarObservaciones(evento) {

    evento.preventDefault();

    const token = sessionStorage.getItem("token");
    const id = solicitudSeleccionadaId;

    if (!token) {
        window.location.href = "index.html";
        return;
    }

    if (!id) {
        return;
    }

    const datos = {
        observaciones_internas: observacionesInternas.value.trim()
    };

    mensajeObservaciones.textContent = "Guardando observaciones...";
    botonGuardarObservaciones.disabled = true;

    try {
        const resultado = await guardarObservacionesSolicitud(
            id,
            datos,
            token
        );

        if (solicitudSeleccionadaId === id && modal.open) {
            mensajeObservaciones.textContent = resultado.message;
        }

    } catch (error) {
        if (solicitudSeleccionadaId === id && modal.open) {
            mensajeObservaciones.textContent = error.message;
        }

    } finally {
        if (solicitudSeleccionadaId === id && modal.open) {
            botonGuardarObservaciones.disabled = false;
        }
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
        localStorage.removeItem("token");

        window.location.href = "index.html";

    } catch (error) {
        mensaje.textContent =
            "No se pudo cerrar sesión: " + error.message;

    } finally {
        botonCerrarSesion.disabled = false;
    }
}

// ABRIR LA FICHA DEL ANIMAL SIN SALIR DE SOLICITUDES
async function abrirFichaAnimal(id) {

    const token = sessionStorage.getItem("token");

    if (!token) {
        window.location.href = "index.html";
        return;
    }

    tituloAnimal.textContent = "Ficha del animal";
    mensajeAnimal.textContent = "Cargando ficha...";
    contenidoAnimal.hidden = true;
    datosFichaAnimal.replaceChildren();

    fotoAnimal.hidden = true;
    fotoAnimal.removeAttribute("src");

    mostrandoFichaAnimal = true;
        posicionDetalle = modal.scrollTop;
        modal.close();

    if (!modalAnimal.open) {
        modalAnimal.showModal();
    }

    modalAnimal.scrollTop = 0;

    try {
        const animal = await obtenerAnimal(id, token);

        if (!modalAnimal.open) {
            return;
        }

        tituloAnimal.textContent = "Ficha de " + animal.nombre;

        mostrarDato(datosFichaAnimal, "Especie", animal.especie);
        mostrarDato(datosFichaAnimal, "Raza", animal.raza);
        mostrarDato(datosFichaAnimal, "Sexo", animal.sexo);
        mostrarDato(datosFichaAnimal, "Tamaño", animal.tamano);
        mostrarDato(
            datosFichaAnimal,
            "Nacimiento aproximado",
            animal.fecha_nacimiento
        );
        mostrarDato(datosFichaAnimal, "Estado actual", animal.estado);
        mostrarDato(datosFichaAnimal, "Carácter", animal.caracter);
        mostrarDato(
            datosFichaAnimal,
            "Compatibilidad con niños",
            animal.con_ninos
        );
        mostrarDato(
            datosFichaAnimal,
            "Compatibilidad con otros animales",
            animal.con_otros_animales
        );

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

// BUSCAR CON LOS FILTROS
formularioFiltros.addEventListener("submit", function (evento) {
    evento.preventDefault();

    paginaActual = 1;
    cargarSolicitudes();
});

// LIMPIAR LOS FILTROS
limpiarFiltros.addEventListener("click", function () {
    formularioFiltros.reset();

    paginaActual = 1;
    cargarSolicitudes();
});

// CAMBIAR DE PÁGINA
botonAnterior.addEventListener("click", function () {
    if (paginaActual > 1) {
        paginaActual--;
        cargarSolicitudes();
    }
});

botonSiguiente.addEventListener("click", function () {
    if (paginaActual < ultimaPagina) {
        paginaActual++;
        cargarSolicitudes();
    }
});

// OBSERVACIONES INTERNAS
formularioObservaciones.addEventListener("submit", guardarObservaciones);
formularioSeguimiento.addEventListener("submit", enviarSeguimiento);

// CERRAR EL MODAL
cerrarModal.addEventListener("click", function () {
    modal.close();
});

modal.addEventListener("close", function () {
    if (!mostrandoFichaAnimal && !modal.open) {
        solicitudSeleccionadaId = null;
    }
});

// SESIÓN Y ACCESO
botonCerrarSesion.addEventListener("click", salirDeLaSesion);

window.addEventListener("pageshow", comprobarAcceso);

cerrarAnimal.addEventListener("click", function () {
    modalAnimal.close();
});

// VOLVER AL DETALLE AL CERRAR LA FICHA DEL ANIMAL
modalAnimal.addEventListener("close", function () {

    if (mostrandoFichaAnimal) {
        modal.showModal();
        modal.scrollTop = posicionDetalle;
        mostrandoFichaAnimal = false;
    }
});