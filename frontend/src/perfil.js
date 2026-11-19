import {
    obtenerUsuario,
    actualizarPerfil,
    actualizarFotografia,
    cambiarContrasena,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

// ELEMENTOS DEL PERFIL
const estado = document.querySelector("#mensajePerfil");
const datosPerfil = document.querySelector("#datosPerfil");
const nombre = document.querySelector("#nombrePerfil");
const email = document.querySelector("#emailPerfil");
const fechaNacimiento = document.querySelector("#fechaNacimientoPerfil");
const rol = document.querySelector("#rolPerfil");
const enlaceHogar = document.querySelector("#enlaceHogar");

// FORMULARIO DE EDICIÓN
const formulario = document.querySelector("#formularioEditarPerfil");
const nombreEditar = document.querySelector("#nombreEditar");
const emailEditar = document.querySelector("#emailEditar");
const fechaNacimientoEditar = document.querySelector("#fechaNacimientoEditar");
const mensajeEditar = document.querySelector("#mensajeEditarPerfil");
const botonGuardar = document.querySelector("#botonGuardarPerfil");

// VENTANA DE EDICIÓN
const modalEditarPerfil = document.querySelector("#modalEditarPerfil");
const abrirEditarPerfil = document.querySelector("#abrirEditarPerfil");
const cerrarEditarPerfil = document.querySelector("#cerrarEditarPerfil");

// FORMULARIO DE CONTRASEÑA
const formularioContrasena = document.querySelector("#formularioContrasena");
const passwordActual = document.querySelector("#passwordActual");
const passwordNueva = document.querySelector("#passwordNueva");
const confirmarPasswordNueva = document.querySelector("#confirmarPasswordNueva");
const mensajeContrasena = document.querySelector("#mensajeContrasena");
const botonGuardarContrasena = document.querySelector("#botonGuardarContrasena");

// VENTANA DE CONTRASEÑA
const modalContrasena = document.querySelector("#modalContrasena");
const abrirCambiarContrasena = document.querySelector("#abrirCambiarContrasena");
const cerrarContrasena = document.querySelector("#cerrarContrasena");

// CABECERA
const botonCerrarSesion = document.querySelector("#botonCerrarSesion");
const enlaceUsuarios = document.querySelector("#enlaceUsuarios");
const enlaceMisAnimales = document.querySelector("#enlaceMisAnimales");

// FOTOGRAFÍA
const formularioFotografia = document.querySelector("#formularioFotografia");
const fotografiaEditar = document.querySelector("#fotografiaEditar");
const fotografiaPerfil = document.querySelector("#fotografiaPerfil");
const avatarPredeterminado = document.querySelector("#avatarPredeterminado");
const mensajeFotografia = document.querySelector("#mensajeFotografia");
const botonGuardarFotografia = document.querySelector("#botonGuardarFotografia");

// ESTADO DE LOS FORMULARIOS
let usuarioActual = null;
let guardandoPerfil = false;
let guardandoContrasena = false;

// MOSTRAR LA FOTOGRAFÍA
function mostrarFotografia(url) {

    if (url) {
        fotografiaPerfil.src = url;
        fotografiaPerfil.hidden = false;
        avatarPredeterminado.hidden = true;
    } else {
        fotografiaPerfil.removeAttribute("src");
        fotografiaPerfil.hidden = true;
        avatarPredeterminado.hidden = false;
    }
}

// MOSTRAR LOS DATOS Y RELLENAR EL FORMULARIO
function mostrarPerfil(usuario) {

    usuarioActual = usuario;
    enlaceHogar.hidden = usuario.rol !== "adoptante";

    const fecha = usuario.fecha_nacimiento
        ? usuario.fecha_nacimiento.slice(0, 10)
        : "";

    nombre.textContent = usuario.name;
    email.textContent = usuario.email;
    fechaNacimiento.textContent = fecha || "No indicada";
    rol.textContent = usuario.rol;

    nombreEditar.value = usuario.name;
    emailEditar.value = usuario.email;
    fechaNacimientoEditar.value = fecha;
}

// CONSULTAR EL PERFIL
async function cargarPerfil() {

    const token = sessionStorage.getItem("token");

    if (!token) {
        estado.textContent =
            "Debes iniciar sesión para consultar tu perfil.";

        botonCerrarSesion.hidden = true;
        return;
    }

    estado.textContent = "Cargando perfil...";

    try {
        const usuario = await obtenerUsuario(token);

        enlaceUsuarios.hidden = usuario.rol !== "admin";

        enlaceMisAnimales.hidden =
            usuario.rol !== "personal" &&
            usuario.rol !== "veterinario";

        mostrarPerfil(usuario);
        mostrarFotografia(usuario.fotografia_url);

        datosPerfil.hidden = false;
        estado.textContent = "";

    } catch (error) {
        estado.textContent =
            `No se pudo cargar el perfil: ${error.message}`;
    }
}

// ABRIR LA EDICIÓN CON LOS DATOS GUARDADOS
function abrirEdicion() {

    if (!usuarioActual) {
        return;
    }

    mostrarPerfil(usuarioActual);

    mensajeEditar.textContent = "";
    estado.textContent = "";

    modalEditarPerfil.showModal();
}

// GUARDAR LOS DATOS
async function guardarPerfil(evento) {

    evento.preventDefault();

    if (guardandoPerfil) {
        return;
    }

    const token = sessionStorage.getItem("token");

    if (!token) {
        mensajeEditar.textContent =
            "Debes iniciar sesión para editar tu perfil.";
        return;
    }

    const datos = {
        name: nombreEditar.value.trim(),
        email: emailEditar.value.trim(),
        fecha_nacimiento: fechaNacimientoEditar.value
    };

    guardandoPerfil = true;
    botonGuardar.disabled = true;
    cerrarEditarPerfil.disabled = true;

    mensajeEditar.textContent = "Guardando cambios...";

    try {
        const resultado = await actualizarPerfil(datos, token);

        mostrarPerfil(resultado.data);

        mensajeEditar.textContent = "";
        modalEditarPerfil.close();

        estado.textContent = resultado.message;

    } catch (error) {
        mensajeEditar.textContent =
            `No se pudo actualizar el perfil: ${error.message}`;

    } finally {
        guardandoPerfil = false;
        botonGuardar.disabled = false;
        cerrarEditarPerfil.disabled = false;
    }
}

// ABRIR EL CAMBIO DE CONTRASEÑA
function abrirContrasena() {

    formularioContrasena.reset();
    mensajeContrasena.textContent = "";
    estado.textContent = "";

    modalContrasena.showModal();
}

// GUARDAR LA NUEVA CONTRASEÑA
async function guardarContrasena(evento) {

    evento.preventDefault();

    if (guardandoContrasena) {
        return;
    }

    const token = sessionStorage.getItem("token");

    if (!token) {
        mensajeContrasena.textContent =
            "Debes iniciar sesión para cambiar la contraseña.";
        return;
    }

    const datos = {
        password_actual: passwordActual.value,
        password: passwordNueva.value,
        password_confirmation: confirmarPasswordNueva.value
    };

    guardandoContrasena = true;
    botonGuardarContrasena.disabled = true;
    cerrarContrasena.disabled = true;

    mensajeContrasena.textContent = "Cambiando contraseña...";

    try {
        const resultado = await cambiarContrasena(datos, token);

        formularioContrasena.reset();
        mensajeContrasena.textContent = "";
        modalContrasena.close();

        estado.textContent = resultado.message;

    } catch (error) {
        mensajeContrasena.textContent =
            `No se pudo cambiar la contraseña: ${error.message}`;

    } finally {
        guardandoContrasena = false;
        botonGuardarContrasena.disabled = false;
        cerrarContrasena.disabled = false;
    }
}

// GUARDAR LA FOTOGRAFÍA
async function guardarFotografia(evento) {

    evento.preventDefault();

    const token = sessionStorage.getItem("token");
    const fotografia = fotografiaEditar.files[0];

    if (!token) {
        mensajeFotografia.textContent =
            "Debes iniciar sesión para cambiar la fotografía.";
        return;
    }

    if (!fotografia) {
        mensajeFotografia.textContent = "Selecciona una fotografía.";
        return;
    }

    mensajeFotografia.textContent = "Guardando fotografía...";
    botonGuardarFotografia.disabled = true;

    try {
        const resultado = await actualizarFotografia(fotografia, token);

        mostrarFotografia(resultado.data.fotografia_url);

        mensajeFotografia.textContent = resultado.message;
        formularioFotografia.reset();

    } catch (error) {
        mensajeFotografia.textContent =
            `No se pudo guardar la fotografía: ${error.message}`;

    } finally {
        botonGuardarFotografia.disabled = false;
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
        estado.textContent =
            `No se pudo cerrar sesión: ${error.message}`;

    } finally {
        botonCerrarSesion.disabled = false;
    }
}

// EVENTOS DE EDICIÓN
abrirEditarPerfil.addEventListener("click", abrirEdicion);

cerrarEditarPerfil.addEventListener("click", function () {
    if (!guardandoPerfil) {
        modalEditarPerfil.close();
    }
});

modalEditarPerfil.addEventListener("cancel", function (evento) {
    if (guardandoPerfil) {
        evento.preventDefault();
    }
});

formulario.addEventListener("submit", guardarPerfil);

// EVENTOS DE CONTRASEÑA
abrirCambiarContrasena.addEventListener("click", abrirContrasena);

cerrarContrasena.addEventListener("click", function () {
    if (!guardandoContrasena) {
        modalContrasena.close();
    }
});

modalContrasena.addEventListener("cancel", function (evento) {
    if (guardandoContrasena) {
        evento.preventDefault();
    }
});

// VACIAR LAS CONTRASEÑAS TAMBIÉN AL CERRAR SIN GUARDAR
modalContrasena.addEventListener("close", function () {
    formularioContrasena.reset();
    mensajeContrasena.textContent = "";
});

formularioContrasena.addEventListener("submit", guardarContrasena);

// FOTOGRAFÍA Y CIERRE DE SESIÓN
formularioFotografia.addEventListener("submit", guardarFotografia);
botonCerrarSesion.addEventListener("click", salirSesion);

// CARGAR EL PERFIL
cargarPerfil();