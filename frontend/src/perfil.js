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

// ELEMENTOS DEL FORMULARIO
const formulario = document.querySelector("#formularioEditarPerfil");
const nombreEditar = document.querySelector("#nombreEditar");
const emailEditar = document.querySelector("#emailEditar");
const fechaNacimientoEditar = document.querySelector("#fechaNacimientoEditar");
const mensajeEditar = document.querySelector("#mensajeEditarPerfil");
const botonGuardar = document.querySelector("#botonGuardarPerfil");

// ELEMENTOS DEL FORMULARIO DE CONTRASEÑA
const formularioContrasena = document.querySelector("#formularioContrasena");
const passwordActual = document.querySelector("#passwordActual");
const passwordNueva = document.querySelector("#passwordNueva");
const confirmarPasswordNueva = document.querySelector("#confirmarPasswordNueva");
const mensajeContrasena = document.querySelector("#mensajeContrasena");
const botonGuardarContrasena = document.querySelector("#botonGuardarContrasena");

// BOTÓN PARA CERRAR SESIÓN
const botonCerrarSesion = document.querySelector("#botonCerrarSesion");
// ENLACE DE ADMINISTRACIÓN
const enlaceUsuarios = document.querySelector("#enlaceUsuarios");

// ELEMENTOS DE LA FOTOGRAFÍA
const formularioFotografia = document.querySelector("#formularioFotografia");
const fotografiaEditar = document.querySelector("#fotografiaEditar");
const fotografiaPerfil = document.querySelector("#fotografiaPerfil");
const avatarPredeterminado = document.querySelector("#avatarPredeterminado");
const mensajeFotografia = document.querySelector("#mensajeFotografia");
const botonGuardarFotografia = document.querySelector("#botonGuardarFotografia");

// MOSTRAR LA FOTOGRAFÍA
function mostrarFotografia(url) {

    if (url) {
        fotografiaPerfil.src = url;
        fotografiaPerfil.hidden = false;
        avatarPredeterminado.hidden = true;
    }
}

// MOSTRAR LOS DATOS Y RELLENAR EL FORMULARIO
function mostrarPerfil(usuario) {

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

        mostrarPerfil(usuario);
        mostrarFotografia(usuario.fotografia_url);

        datosPerfil.hidden = false;
        estado.textContent = "";

    } catch (error) {
        estado.textContent =
            `No se pudo cargar el perfil: ${error.message}`;
    }
}

// GUARDAR LOS CAMBIOS DEL PERFIL
async function guardarPerfil(evento) {

    evento.preventDefault();

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

    mensajeEditar.textContent = "Guardando cambios...";
    botonGuardar.disabled = true;

    try {
        const resultado = await actualizarPerfil(datos, token);

        mostrarPerfil(resultado.data);

        mensajeEditar.textContent = resultado.message;

    } catch (error) {
        mensajeEditar.textContent =
            `No se pudo actualizar el perfil: ${error.message}`;

    } finally {
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
        estado.textContent =
            `No se pudo cerrar sesión: ${error.message}`;

    } finally {
        botonCerrarSesion.disabled = false;
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
        mensajeFotografia.textContent =
            "Selecciona una fotografía.";
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

// GUARDAR LA NUEVA CONTRASEÑA
async function guardarContrasena(evento) {

    evento.preventDefault();

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

    mensajeContrasena.textContent = "Cambiando contraseña...";
    botonGuardarContrasena.disabled = true;

    try {
        const resultado = await cambiarContrasena(datos, token);

        formularioContrasena.reset();
        mensajeContrasena.textContent = resultado.message;

    } catch (error) {
        mensajeContrasena.textContent =
            `No se pudo cambiar la contraseña: ${error.message}`;

    } finally {
        botonGuardarContrasena.disabled = false;
    }
}

// ESCUCHAR LOS EVENTOS
formulario.addEventListener("submit", guardarPerfil);
botonCerrarSesion.addEventListener("click", salirSesion);
formularioFotografia.addEventListener("submit", guardarFotografia);
formularioContrasena.addEventListener("submit", guardarContrasena);

// CARGAR LOS DATOS AL ABRIR LA PÁGINA
cargarPerfil();