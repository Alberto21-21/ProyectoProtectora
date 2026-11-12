import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import {
    obtenerFichaEmpleado,
    crearFichaEmpleado,
    actualizarFichaEmpleado
} from "./providers/empleadosProvider.js";

import {
    obtenerUsuarios,
    obtenerDetalleUsuario,
    crearUsuarioInterno,
    actualizarUsuario,
    desactivarUsuario
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

// ELEMENTOS DEL ALTA
const abrirAltaUsuario = document.querySelector("#abrirAltaUsuario");
const cerrarAltaUsuario = document.querySelector("#cerrarAltaUsuario");
const modalAltaUsuario = document.querySelector("#modalAltaUsuario");
const formularioAltaUsuario = document.querySelector("#formularioAltaUsuario");
const mensajeAltaUsuario = document.querySelector("#mensajeAltaUsuario");
const botonCrearUsuario = document.querySelector("#botonCrearUsuario");

// ELEMENTOS DE LA EDICIÓN
const modalEditarUsuario = document.querySelector("#modalEditarUsuario");
const cerrarEditarUsuario = document.querySelector("#cerrarEditarUsuario");
const formularioEditarUsuario = document.querySelector("#formularioEditarUsuario");
const nombreEditarUsuario = document.querySelector("#nombreEditarUsuario");
const emailEditarUsuario = document.querySelector("#emailEditarUsuario");
const fechaEditarUsuario = document.querySelector("#fechaEditarUsuario");
const mensajeEditarUsuario = document.querySelector("#mensajeEditarUsuario");
const botonActualizarUsuario = document.querySelector("#botonActualizarUsuario");

// ESTADO DE LA PÁGINA
let paginaActual = 1;
let ultimaPagina = 1;
let cargando = false;

let administradorActualId = null;
let usuarioEditarId = null;
let guardandoUsuario = false;
let guardandoEdicion = false;
let cargandoEdicion = false;
let desactivandoUsuario = false;

// ESTADO DE LAS FICHAS LABORALES
let fichaLaboralActual = null;
let usuarioFichaActualId = null;
let guardandoFicha = false;
let perfilVeterinarioActual = null;
let usuarioVeterinarioActualId = null;
let guardandoPerfilVeterinario = false;

// FILTROS APLICADOS
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

        administradorActualId = usuario.id;
        gestionUsuarios.hidden = false;

        await cargarUsuarios(1);

    } catch (error) {
        mensaje.textContent =
            `No se pudo comprobar el acceso: ${error.message}`;
    }
}

// ACTUALIZAR LOS CONTROLES
function actualizarControles() {

    const ocupado = cargando || desactivandoUsuario;

    botonBuscar.disabled = ocupado;
    botonLimpiar.disabled = ocupado;
    abrirAltaUsuario.disabled = ocupado;

    botonAnterior.disabled = ocupado || paginaActual <= 1;
    botonSiguiente.disabled = ocupado || paginaActual >= ultimaPagina;
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

        // VOLVER A UNA PÁGINA EXISTENTE SI EL LISTADO SE HA REDUCIDO
        if (paginacion.current_page > paginacion.last_page) {
            cargando = false;
            await cargarUsuarios(paginacion.last_page);
            return;
        }

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

// CREAR UNA CELDA
function crearCelda(texto) {

    const celda = document.createElement("td");
    celda.textContent = texto;

    return celda;
}

// MOSTRAR LOS USUARIOS
function mostrarUsuarios(usuarios) {

    lista.replaceChildren();

    usuarios.forEach(function (usuario) {

        const fila = document.createElement("tr");

        fila.appendChild(crearCelda(usuario.name));
        fila.appendChild(crearCelda(usuario.email));
        fila.appendChild(crearCelda(usuario.rol));
        fila.appendChild(crearCelda(usuario.estado));

        const celdaAcciones = document.createElement("td");
        const acciones = document.createElement("div");

        acciones.className = "acciones-cuenta";

        // BOTÓN DE DETALLE
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

        acciones.appendChild(botonDetalle);

        // BOTÓN DE EDICIÓN
        const botonEditar = document.createElement("button");

        botonEditar.type = "button";
        botonEditar.textContent = "Editar";
        botonEditar.className = "boton-detalle boton-editar-usuario";
        botonEditar.setAttribute(
            "aria-label",
            "Editar cuenta de " + usuario.name
        );

        botonEditar.addEventListener("click", function () {
            abrirEdicionUsuario(usuario.id);
        });

        acciones.appendChild(botonEditar);

        // NO MOSTRAR DESACTIVAR EN LA CUENTA PROPIA O EN UNA INACTIVA
        if (
            usuario.estado === "activo" &&
            usuario.id !== administradorActualId
        ) {
            const botonDesactivar = document.createElement("button");

            botonDesactivar.type = "button";
            botonDesactivar.textContent = "Desactivar";
            botonDesactivar.className =
                "boton-detalle boton-desactivar-usuario";

            botonDesactivar.setAttribute(
                "aria-label",
                "Desactivar cuenta de " + usuario.name
            );

            botonDesactivar.addEventListener("click", function () {
                confirmarDesactivacion(usuario, botonDesactivar);
            });

            acciones.appendChild(botonDesactivar);
        }

        celdaAcciones.appendChild(acciones);
        fila.appendChild(celdaAcciones);
        lista.appendChild(fila);
    });
}

// APLICAR LOS FILTROS
function buscarUsuarios(evento) {

    evento.preventDefault();

    if (cargando || desactivandoUsuario) {
        return;
    }

    filtrosActuales = {
        buscar: buscar.value.trim(),
        rol: rol.value,
        estado: estado.value
    };

    cargarUsuarios(1);
}

// LIMPIAR LOS FILTROS
function limpiarFiltros() {

    if (cargando || desactivandoUsuario) {
        return;
    }

    formulario.reset();

    filtrosActuales = {
        buscar: "",
        rol: "",
        estado: ""
    };

    cargarUsuarios(1);
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



// CONSULTAR EL DETALLE
async function consultarDetalle(id) {

    datosDetalle.hidden = true;
    fotografiaDetalle.hidden = true;
    fotografiaDetalle.removeAttribute("src");
    mensajeDetalle.textContent = "Cargando usuario...";
    modalDetalle.scrollTop = 0;
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

// ABRIR LA EDICIÓN DE UNA CUENTA
async function abrirEdicionUsuario(id) {

    usuarioEditarId = null;
    formularioEditarUsuario.reset();

    cargandoEdicion = true;
    botonActualizarUsuario.disabled = true;
    formularioEditarUsuario.hidden = true;
    mensajeEditarUsuario.textContent = "Cargando usuario...";

    modalEditarUsuario.showModal();

    try {
        const token = sessionStorage.getItem("token");
        const resultado = await obtenerDetalleUsuario(id, token);
        const usuario = resultado.data;

        usuarioEditarId = usuario.id;

        nombreEditarUsuario.value = usuario.name;
        emailEditarUsuario.value = usuario.email;

        fechaEditarUsuario.value = usuario.fecha_nacimiento
            ? usuario.fecha_nacimiento.slice(0, 10)
            : "";

        formularioEditarUsuario.hidden = false;
        mensajeEditarUsuario.textContent = "";

    } catch (error) {
        formularioEditarUsuario.hidden = false;

        mensajeEditarUsuario.textContent =
            `No se pudo cargar el usuario: ${error.message}`;

    } finally {
        cargandoEdicion = false;
        botonActualizarUsuario.disabled = usuarioEditarId === null;
    }
}

// GUARDAR LA EDICIÓN
async function guardarEdicionUsuario(evento) {

    evento.preventDefault();

    if (guardandoEdicion || usuarioEditarId === null) {
        return;
    }

    const token = sessionStorage.getItem("token");

    if (!token) {
        mensajeEditarUsuario.textContent =
            "Debes iniciar sesión para editar usuarios.";
        return;
    }

    const datos = {
        name: nombreEditarUsuario.value.trim(),
        email: emailEditarUsuario.value.trim(),
        fecha_nacimiento: fechaEditarUsuario.value
    };

    guardandoEdicion = true;
    botonActualizarUsuario.disabled = true;
    cerrarEditarUsuario.disabled = true;
    mensajeEditarUsuario.textContent = "Guardando cambios...";

    try {
        const resultado = await actualizarUsuario(
            usuarioEditarId,
            datos,
            token
        );

        modalEditarUsuario.close();
        formularioEditarUsuario.reset();
        usuarioEditarId = null;

        await cargarUsuarios(paginaActual);

        mensaje.textContent =
            resultado.message + " " + mensaje.textContent;

    } catch (error) {
        mensajeEditarUsuario.textContent =
            `No se pudo actualizar el usuario: ${error.message}`;

    } finally {
        guardandoEdicion = false;
        botonActualizarUsuario.disabled = false;
        cerrarEditarUsuario.disabled = false;
    }
}

// CONFIRMAR Y DESACTIVAR UNA CUENTA
async function confirmarDesactivacion(usuario, botonDesactivar) {

    if (desactivandoUsuario || cargando) {
        return;
    }

    const confirmado = window.confirm(
        "¿Quieres desactivar la cuenta de " + usuario.name +
        "? No podrá iniciar sesión y sus accesos actuales se revocarán."
    );

    if (!confirmado) {
        return;
    }

    const token = sessionStorage.getItem("token");

    if (!token) {
        mensaje.textContent =
            "Debes iniciar sesión para desactivar cuentas.";
        return;
    }

    desactivandoUsuario = true;
    botonDesactivar.disabled = true;
    actualizarControles();

    mensaje.textContent = "Desactivando cuenta...";

    try {
        const resultado = await desactivarUsuario(usuario.id, token);

        await cargarUsuarios(paginaActual);

        mensaje.textContent =
            resultado.message + " " + mensaje.textContent;

    } catch (error) {
        mensaje.textContent =
            `No se pudo desactivar la cuenta: ${error.message}`;

    } finally {
        desactivandoUsuario = false;
        botonDesactivar.disabled = false;
        actualizarControles();
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

// CARGAR LA FICHA LABORAL
async function cargarFichaLaboral(usuario) {

    const seccion = document.querySelector("#seccionFichaLaboral");
    const mensajeFicha = document.querySelector("#mensajeFichaLaboral");
    const botonCrear = document.querySelector("#botonCrearFichaLaboral");
    const botonEditar = document.querySelector("#botonEditarFichaLaboral");
    const formularioFicha = document.querySelector("#formularioFichaLaboral");

    usuarioFichaActualId = usuario.id;
    fichaLaboralActual = null;

    seccion.hidden = usuario.rol !== "personal";
    botonCrear.hidden = true;
    botonEditar.hidden = true;
    formularioFicha.hidden = true;

    if (usuario.rol !== "personal") {
        return;
    }

    mensajeFicha.textContent = "Cargando ficha laboral...";

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
        mensajeFicha.textContent = "";

    } catch (error) {
        mensajeFicha.textContent = error.message;

        if (error.message === "Esta cuenta todavía no tiene ficha laboral.") {
            botonCrear.hidden = false;
        }
    }
}

// ABRIR EL ALTA DE FICHA LABORAL
function abrirAltaFichaLaboral() {

    const formularioFicha = document.querySelector("#formularioFichaLaboral");

    formularioFicha.reset();

    document.querySelector("#estadoLaboralEmpleado").value = "activo";

    formularioFicha.hidden = false;
    document.querySelector("#mensajeFichaLaboral").textContent = "";
}

// ABRIR LA EDICIÓN DE FICHA LABORAL
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

// GUARDAR LA FICHA LABORAL
async function guardarFichaLaboral(evento) {

    evento.preventDefault();

    if (guardandoFicha) {
        return;
    }

    const token = sessionStorage.getItem("token");
    const mensajeFicha = document.querySelector("#mensajeFichaLaboral");
    const botonGuardar = document.querySelector("#botonGuardarFichaLaboral");

    const datos = {
        telefono: document.querySelector("#telefonoEmpleado").value.trim() || null,
        puesto: document.querySelector("#puestoEmpleado").value.trim(),
        estado_laboral:
            document.querySelector("#estadoLaboralEmpleado").value
    };

    guardandoFicha = true;
    botonGuardar.disabled = true;
    mensajeFicha.textContent = "Guardando ficha laboral...";

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
        mensajeFicha.textContent = error.message;

    } finally {
        guardandoFicha = false;
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

// EVENTOS DEL LISTADO
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

// EVENTOS DEL DETALLE
botonCerrarDetalle.addEventListener("click", function () {
    modalDetalle.close();
});

modalDetalle.addEventListener("cancel", function (evento) {
    if (guardandoFicha) {
        evento.preventDefault();
    }
});

// EVENTOS DEL ALTA
abrirAltaUsuario.addEventListener("click", function () {
    formularioAltaUsuario.reset();
    mensajeAltaUsuario.textContent = "";
    modalAltaUsuario.showModal();
});

cerrarAltaUsuario.addEventListener("click", function () {
    modalAltaUsuario.close();
});

modalAltaUsuario.addEventListener("cancel", function (evento) {
    if (guardandoUsuario) {
        evento.preventDefault();
    }
});

formularioAltaUsuario.addEventListener("submit", guardarUsuarioInterno);

// EVENTOS DE LA EDICIÓN
cerrarEditarUsuario.addEventListener("click", function () {
    if (!guardandoEdicion && !cargandoEdicion) {
        modalEditarUsuario.close();
    }
});

modalEditarUsuario.addEventListener("cancel", function (evento) {
    if (guardandoEdicion || cargandoEdicion) {
        evento.preventDefault();
    }
});

formularioEditarUsuario.addEventListener("submit", guardarEdicionUsuario);

// EVENTOS DE LA FICHA LABORAL
document.querySelector("#botonCrearFichaLaboral")
    .addEventListener("click", abrirAltaFichaLaboral);

document.querySelector("#botonEditarFichaLaboral")
    .addEventListener("click", abrirEdicionFichaLaboral);

document.querySelector("#formularioFichaLaboral")
    .addEventListener("submit", guardarFichaLaboral);

document.querySelector("#cancelarFichaLaboral")
    .addEventListener("click", function () {

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

    botonCerrarSesion.addEventListener("click", salirSesion);
    comprobarAcceso();