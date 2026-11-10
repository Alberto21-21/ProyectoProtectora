import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import {
    obtenerUsuarios,
    obtenerDetalleUsuario
} from "./providers/usuariosProvider.js";

// ELEMENTOS DEL LISTADO
const mensaje = document.querySelector("#mensajeUsuarios");
const gestionUsuarios = document.querySelector("#gestionUsuarios");
const formulario = document.querySelector("#formularioFiltros");
const buscar = document.querySelector("#buscarUsuario");
const rol = document.querySelector("#rolUsuario");
const estado = document.querySelector("#estadoUsuario");
const lista = document.querySelector("#listaUsuarios");
const botonBuscar = document.querySelector("#botonBuscarUsuarios");
const botonLimpiar = document.querySelector("#botonLimpiarFiltros");
const botonAnterior = document.querySelector("#paginaAnterior");
const botonSiguiente = document.querySelector("#paginaSiguiente");
const informacionPagina = document.querySelector("#informacionPagina");

// BOTÓN PARA CERRAR SESIÓN
const botonCerrarSesion = document.querySelector("#botonCerrarSesion");

// ELEMENTOS DEL DETALLE
const modalDetalle = document.querySelector("#modalDetalleUsuario");
const botonCerrarDetalle = document.querySelector("#cerrarDetalleUsuario");
const mensajeDetalle = document.querySelector("#mensajeDetalleUsuario");
const datosDetalle = document.querySelector("#datosDetalleUsuario");
const fotografiaDetalle = document.querySelector("#fotografiaDetalleUsuario");

// ESTADO DE LA PAGINACIÓN
let paginaActual = 1;
let ultimaPagina = 1;
let cargando = false;

// FILTROS APLICADOS AL LISTADO
let filtrosActuales = {
    buscar: "",
    rol: "",
    estado: ""
};

// COMPROBAR EL ACCESO
async function comprobarAcceso() {

    const token = sessionStorage.getItem("token");

    if (!token) {
        mensaje.textContent =
            "Debes iniciar sesión para acceder a esta página.";

        botonCerrarSesion.hidden = true;
        return;
    }

    mensaje.textContent = "Comprobando permisos...";

    try {
        const usuario = await obtenerUsuario(token);

        if (usuario.rol !== "admin") {
            mensaje.textContent =
                "No tienes permisos para acceder a esta página.";
            return;
        }

        gestionUsuarios.hidden = false;

        await cargarUsuarios(1);

    } catch (error) {
        mensaje.textContent =
            `No se pudo comprobar el acceso: ${error.message}`;
    }
}

// ACTIVAR O DESACTIVAR LOS CONTROLES
function actualizarControles() {

    botonBuscar.disabled = cargando;
    botonLimpiar.disabled = cargando;

    botonAnterior.disabled = cargando || paginaActual <= 1;
    botonSiguiente.disabled = cargando || paginaActual >= ultimaPagina;
}

// CARGAR EL LISTADO
async function cargarUsuarios(pagina) {

    if (cargando) {
        return;
    }

    cargando = true;
    actualizarControles();

    mensaje.textContent = "Cargando usuarios...";

    const filtros = {
        buscar: filtrosActuales.buscar,
        rol: filtrosActuales.rol,
        estado: filtrosActuales.estado,
        page: pagina
    };

    try {
        const token = sessionStorage.getItem("token");
        const resultado = await obtenerUsuarios(filtros, token);
        const paginacion = resultado.data;

        paginaActual = paginacion.current_page;
        ultimaPagina = paginacion.last_page;

        mostrarUsuarios(paginacion.data);

        informacionPagina.textContent =
            "Página " + paginaActual + " de " + ultimaPagina;

        if (paginacion.total === 0) {
            mensaje.textContent =
                "No se han encontrado usuarios con esos filtros.";
        } else {
            mensaje.textContent =
                "Usuarios encontrados: " + paginacion.total;
        }

    } catch (error) {
        mensaje.textContent =
            `No se pudieron cargar los usuarios: ${error.message}`;

    } finally {
        cargando = false;
        actualizarControles();
    }
}

// CREAR UNA CELDA CON TEXTO
function crearCelda(texto) {

    const celda = document.createElement("td");
    celda.textContent = texto;

    return celda;
}

// MOSTRAR LOS USUARIOS EN LA TABLA
function mostrarUsuarios(usuarios) {

    lista.replaceChildren();

    usuarios.forEach(function (usuario) {

        const fila = document.createElement("tr");

        fila.appendChild(crearCelda(usuario.name));
        fila.appendChild(crearCelda(usuario.email));
        fila.appendChild(crearCelda(usuario.rol));
        fila.appendChild(crearCelda(usuario.estado));

        const celdaDetalle = document.createElement("td");
        const botonDetalle = document.createElement("button");

        botonDetalle.type = "button";
        botonDetalle.textContent = "Ver detalle";
        botonDetalle.className = "boton-detalle";

        botonDetalle.setAttribute(
            "aria-label",
            "Ver detalle de " + usuario.name
        );

        botonDetalle.addEventListener("click", function () {
            consultarDetalle(usuario.id);
        });

        celdaDetalle.appendChild(botonDetalle);
        fila.appendChild(celdaDetalle);

        lista.appendChild(fila);
    });
}

// APLICAR LOS FILTROS
function buscarUsuarios(evento) {

    evento.preventDefault();

    filtrosActuales = {
        buscar: buscar.value.trim(),
        rol: rol.value,
        estado: estado.value
    };

    cargarUsuarios(1);
}

// LIMPIAR LOS FILTROS
function limpiarFiltros() {

    formulario.reset();

    filtrosActuales = {
        buscar: "",
        rol: "",
        estado: ""
    };

    cargarUsuarios(1);
}

// CONSULTAR EL DETALLE
async function consultarDetalle(id) {

    datosDetalle.hidden = true;
    fotografiaDetalle.hidden = true;
    fotografiaDetalle.removeAttribute("src");
    mensajeDetalle.textContent = "Cargando usuario...";

    modalDetalle.showModal();

    try {
        const token = sessionStorage.getItem("token");
        const resultado = await obtenerDetalleUsuario(id, token);
        const usuario = resultado.data;

        document.querySelector("#nombreDetalleUsuario").textContent =
            usuario.name;

        document.querySelector("#emailDetalleUsuario").textContent =
            usuario.email;

        document.querySelector("#fechaDetalleUsuario").textContent =
            usuario.fecha_nacimiento
                ? usuario.fecha_nacimiento.slice(0, 10)
                : "No indicada";

        document.querySelector("#rolDetalleUsuario").textContent =
            usuario.rol;

        document.querySelector("#estadoDetalleUsuario").textContent =
            usuario.estado;

        document.querySelector("#registroDetalleUsuario").textContent =
            usuario.created_at
                ? usuario.created_at.slice(0, 10)
                : "No indicada";

        if (usuario.fotografia_url) {
            fotografiaDetalle.src = usuario.fotografia_url;
            fotografiaDetalle.hidden = false;
        }

        datosDetalle.hidden = false;
        mensajeDetalle.textContent = "";

    } catch (error) {
        mensajeDetalle.textContent =
            `No se pudo cargar el usuario: ${error.message}`;
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
formulario.addEventListener("submit", buscarUsuarios);

botonLimpiar.addEventListener("click", limpiarFiltros);

botonAnterior.addEventListener("click", function () {
    if (paginaActual > 1) {
        cargarUsuarios(paginaActual - 1);
    }
});

botonSiguiente.addEventListener("click", function () {
    if (paginaActual < ultimaPagina) {
        cargarUsuarios(paginaActual + 1);
    }
});

botonCerrarDetalle.addEventListener("click", function () {
    modalDetalle.close();
});

botonCerrarSesion.addEventListener("click", salirSesion);

// COMPROBAR LOS PERMISOS AL ABRIR LA PÁGINA
comprobarAcceso();