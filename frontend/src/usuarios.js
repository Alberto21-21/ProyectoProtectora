import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";
import { obtenerFichaEmpleado,crearFichaEmpleado,actualizarFichaEmpleado} from "./providers/empleadosProvider.js";

import {
    obtenerUsuarios,
    obtenerDetalleUsuario,
    crearUsuarioInterno
} from "./providers/usuariosProvider.js";

import {obtenerPerfilVeterinario,crearPerfilVeterinario,actualizarPerfilVeterinario} from "./providers/veterinariosProvider.js";

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

// ELEMENTOS DEL ALTA DE USUARIOS
const abrirAltaUsuario = document.querySelector("#abrirAltaUsuario");
const cerrarAltaUsuario = document.querySelector("#cerrarAltaUsuario");
const modalAltaUsuario = document.querySelector("#modalAltaUsuario");
const formularioAltaUsuario = document.querySelector("#formularioAltaUsuario");
const mensajeAltaUsuario = document.querySelector("#mensajeAltaUsuario");
const botonCrearUsuario = document.querySelector("#botonCrearUsuario");

let guardandoUsuario = false;

// ESTADO DE LA PAGINACIÓN
let paginaActual = 1;
let ultimaPagina = 1;
let cargando = false;
let fichaLaboralActual = null;
let usuarioFichaActualId = null;
let guardandoFicha = false;
let perfilVeterinarioActual = null;
let usuarioVeterinarioActualId = null;
let guardandoPerfilVeterinario = false;

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

async function cargarFichaLaboral(usuario) {
    const seccion = document.querySelector("#seccionFichaLaboral");
    const mensaje = document.querySelector("#mensajeFichaLaboral");
    const botonCrear = document.querySelector("#botonCrearFichaLaboral");
    const botonEditar = document.querySelector("#botonEditarFichaLaboral");
    const formulario = document.querySelector("#formularioFichaLaboral");
    usuarioFichaActualId = usuario.id;
    fichaLaboralActual = null;

    seccion.hidden = usuario.rol !== "personal";
    botonCrear.hidden = true;
    botonEditar.hidden = true;
    formulario.hidden = true;

    if (usuario.rol !== "personal") {
        return;
    }

    mensaje.textContent = "Cargando ficha laboral...";

    try {
        const token = sessionStorage.getItem("token");
        const resultado = await obtenerFichaEmpleado(usuario.id, token);
        const empleado = resultado.data;
        fichaLaboralActual = empleado; 

        document.querySelector("#telefonoFichaLaboral").textContent =
            empleado.telefono || "No indicado";

        document.querySelector("#puestoFichaLaboral").textContent =
            empleado.puesto;

        document.querySelector("#estadoFichaLaboral").textContent =
            empleado.estado_laboral;

        botonEditar.hidden = false;
        mensaje.textContent = "";
        
         
    } catch (error) {
        mensaje.textContent = error.message;

        if (error.message === "Esta cuenta todavía no tiene ficha laboral.") {
            botonCrear.hidden = false;
        }
    }
}



async function cargarPerfilVeterinario(usuario) {
    const seccion = document.querySelector("#seccionPerfilVeterinario");
    const mensaje = document.querySelector("#mensajePerfilVeterinario");
    const botonCrear = document.querySelector("#botonCrearPerfilVeterinario");
    const botonEditar = document.querySelector("#botonEditarPerfilVeterinario");
    const formulario = document.querySelector("#formularioPerfilVeterinario");

    usuarioVeterinarioActualId = usuario.id;
    perfilVeterinarioActual = null;

    seccion.hidden = usuario.rol !== "veterinario";
    botonCrear.hidden = true;
    botonEditar.hidden = true;
    formulario.hidden = true;

    if (usuario.rol !== "veterinario") {
        return;
    }
    mensaje.textContent = "Cargando información veterinaria...";
 

    mensaje.textContent = "Cargando información veterinaria...";

    try {
        const token = sessionStorage.getItem("token");
        const resultado = await obtenerPerfilVeterinario(usuario.id, token);
        const veterinario = resultado.data;

        perfilVeterinarioActual = veterinario;
        document.querySelector("#especialidadVeterinario").textContent = veterinario.especialidad;

        document.querySelector("#telefonoPerfilVeterinario").textContent = veterinario.empleado?.telefono || "No indicado";

        document.querySelector("#estadoLaboralVeterinario").textContent = veterinario.empleado?.estado_laboral || "No indicado";
        
        botonEditar.hidden = false;
        mensaje.textContent = "";
    } catch (error) {
        mensaje.textContent = error.message;

        if (error.message === "Esta cuenta todavía no tiene perfil veterinario.") {
            botonCrear.hidden = false;
        }
    }
}

function abrirAltaPerfilVeterinario() {
    const formulario = document.querySelector("#formularioPerfilVeterinario");

    formulario.reset();
    formulario.hidden = false;
    document.querySelector("#mensajePerfilVeterinario").textContent = "";
}

function abrirEdicionPerfilVeterinario() {
    if (!perfilVeterinarioActual) {
        return;
    }

    document.querySelector("#especialidadVeterinarioInput").value = perfilVeterinarioActual.especialidad;
    document.querySelector("#telefonoVeterinarioInput").value = perfilVeterinarioActual.empleado?.telefono || "";
    document.querySelector("#formularioPerfilVeterinario").hidden = false;
    document.querySelector("#mensajePerfilVeterinario").textContent = "";
}

async function guardarPerfilVeterinario(evento) {
    evento.preventDefault();

    if (guardandoPerfilVeterinario) {
        return;
    }

    const token = sessionStorage.getItem("token");
    const mensaje = document.querySelector("#mensajePerfilVeterinario");
    const botonGuardar =
        document.querySelector("#botonGuardarPerfilVeterinario");

    const datos = {
        especialidad: document.querySelector("#especialidadVeterinarioInput").value.trim(),
        telefono: document.querySelector("#telefonoVeterinarioInput").value.trim() || null
    };

    guardandoPerfilVeterinario = true;
    botonGuardar.disabled = true;
    mensaje.textContent = "Guardando información veterinaria...";

    try {
        if (perfilVeterinarioActual) {
            await actualizarPerfilVeterinario(
                usuarioVeterinarioActualId,
                datos,
                token
            );
        } else {
            datos.user_id = usuarioVeterinarioActualId;
            await crearPerfilVeterinario(datos, token);
        }

        document.querySelector("#formularioPerfilVeterinario").hidden = true;

    await cargarFichaLaboral({
    id: usuarioVeterinarioActualId,
    rol: "veterinario"
});

    await cargarPerfilVeterinario({
    id: usuarioVeterinarioActualId,
    rol: "veterinario"
});

     
    } catch (error) {
        mensaje.textContent = error.message;
    } finally {
        guardandoPerfilVeterinario = false;
        botonGuardar.disabled = false;
    }
}

function abrirAltaFichaLaboral() {
    const formulario = document.querySelector("#formularioFichaLaboral");

    formulario.reset();
    document.querySelector("#estadoLaboralEmpleado").value = "activo";
    formulario.hidden = false;
    document.querySelector("#mensajeFichaLaboral").textContent = "";
}

function abrirEdicionFichaLaboral() {
    if (!fichaLaboralActual) {
        return;
    }

    document.querySelector("#telefonoEmpleado").value =
        fichaLaboralActual.telefono || "";

    document.querySelector("#puestoEmpleado").value =
        fichaLaboralActual.puesto;

    document.querySelector("#estadoLaboralEmpleado").value =
        fichaLaboralActual.estado_laboral;

    document.querySelector("#formularioFichaLaboral").hidden = false;
    document.querySelector("#mensajeFichaLaboral").textContent = "";
}

async function guardarFichaLaboral(evento) {
    evento.preventDefault();

    if (guardandoFicha) {
        return;
    }

    const token = sessionStorage.getItem("token");
    const mensaje = document.querySelector("#mensajeFichaLaboral");
    const botonGuardar = document.querySelector("#botonGuardarFichaLaboral");

    const datos = {
        telefono: document.querySelector("#telefonoEmpleado").value.trim() || null,
        puesto: document.querySelector("#puestoEmpleado").value.trim(),
        estado_laboral: document.querySelector("#estadoLaboralEmpleado").value
    };

    guardandoFicha = true;
    botonGuardar.disabled = true;
    mensaje.textContent = "Guardando ficha laboral...";

    try {
        if (fichaLaboralActual) {
            await actualizarFichaEmpleado(usuarioFichaActualId, datos, token);
        } else {
            datos.user_id = usuarioFichaActualId;
            await crearFichaEmpleado(datos, token);
        }

        document.querySelector("#formularioFichaLaboral").hidden = true;

        await cargarFichaLaboral({
            id: usuarioFichaActualId,
            rol: "personal"
        });
    } catch (error) {
        mensaje.textContent = error.message;
    } finally {
        guardandoFicha = false;
        botonGuardar.disabled = false;
    }
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
        await cargarFichaLaboral(usuario);
        await cargarPerfilVeterinario(usuario);

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

// CREAR UNA CUENTA INTERNA
async function guardarUsuarioInterno(evento) {

    evento.preventDefault();

    if (guardandoUsuario) {
        return;
    }

    const token = sessionStorage.getItem("token");

    if (!token) {
        mensajeAltaUsuario.textContent =
            "Debes iniciar sesión para crear usuarios.";
        return;
    }

    const datos = {
        name: document.querySelector("#nombreAltaUsuario").value.trim(),
        email: document.querySelector("#emailAltaUsuario").value.trim(),
        fecha_nacimiento: document.querySelector("#fechaAltaUsuario").value,
        rol: document.querySelector("#rolAltaUsuario").value,
        password: document.querySelector("#passwordAltaUsuario").value,
        password_confirmation:
            document.querySelector("#confirmarPasswordAltaUsuario").value
    };

    guardandoUsuario = true;
    botonCrearUsuario.disabled = true;
    cerrarAltaUsuario.disabled = true;
    mensajeAltaUsuario.textContent = "Creando cuenta...";

    try {
        const resultado = await crearUsuarioInterno(datos, token);

        formularioAltaUsuario.reset();
        mensajeAltaUsuario.textContent = "";
        modalAltaUsuario.close();

        // MOSTRAR EL LISTADO SIN FILTROS
        formulario.reset();

        filtrosActuales = {
            buscar: "",
            rol: "",
            estado: ""
        };

        await cargarUsuarios(1);

        mensaje.textContent =
            resultado.message + " " + mensaje.textContent;

    } catch (error) {
        mensajeAltaUsuario.textContent =
            `No se pudo crear la cuenta: ${error.message}`;

    } finally {
        guardandoUsuario = false;
        botonCrearUsuario.disabled = false;
        cerrarAltaUsuario.disabled = false;
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

// ABRIR EL FORMULARIO DE ALTA
abrirAltaUsuario.addEventListener("click", function () {
    formularioAltaUsuario.reset();
    mensajeAltaUsuario.textContent = "";
    modalAltaUsuario.showModal();
});

// CERRAR EL FORMULARIO DE ALTA
cerrarAltaUsuario.addEventListener("click", function () {
    modalAltaUsuario.close();
});

// EVITAR CERRAR CON ESCAPE MIENTRAS SE GUARDA
modalAltaUsuario.addEventListener("cancel", function (evento) {
    if (guardandoUsuario) {
        evento.preventDefault();
    }
});

// ENVIAR EL FORMULARIO DE ALTA
formularioAltaUsuario.addEventListener("submit", guardarUsuarioInterno);

document.querySelector("#botonCrearFichaLaboral").addEventListener("click", abrirAltaFichaLaboral);

document.querySelector("#botonEditarFichaLaboral").addEventListener("click", abrirEdicionFichaLaboral);

document.querySelector("#formularioFichaLaboral").addEventListener("submit", guardarFichaLaboral);
document.querySelector("#cancelarFichaLaboral").addEventListener("click", function () {
        const formularioFicha = document.querySelector("#formularioFichaLaboral");
        formularioFicha.reset();
        formularioFicha.hidden = true;
        document.querySelector("#mensajeFichaLaboral").textContent = "";
    });


document.querySelector("#botonCrearPerfilVeterinario").addEventListener("click", abrirAltaPerfilVeterinario);

document.querySelector("#botonEditarPerfilVeterinario").addEventListener("click", abrirEdicionPerfilVeterinario);

document.querySelector("#formularioPerfilVeterinario").addEventListener("submit", guardarPerfilVeterinario);

document.querySelector("#cancelarPerfilVeterinario").addEventListener("click", function () {
        const formulario = document.querySelector("#formularioPerfilVeterinario");
        formulario.reset();
        formulario.hidden = true;
        document.querySelector("#mensajePerfilVeterinario").textContent = "";
    });