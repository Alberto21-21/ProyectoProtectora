import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import {
    obtenerAnimal,
    actualizarAnimal,
    actualizarImagenAnimal,
    obtenerDatosClinicos,
    actualizarDatosClinicos
} from "./providers/animalesProvider.js";

// ID DEL ANIMAL QUE VIENE EN LA URL (editarAnimal.html?id=1)
const parametros = new URLSearchParams(window.location.search);
const idAnimal = parametros.get("id");

// ELEMENTOS GENERALES
const mensajeFicha = document.querySelector("#mensajeFicha");
const datosAnimal = document.querySelector("#datosAnimal");
const nombreAnimalTitulo = document.querySelector("#nombreAnimalTitulo");
const botonCerrarSesion = document.querySelector("#botonCerrarSesion");
const enlaceUsuarios = document.querySelector("#enlaceUsuarios");

// ELEMENTOS DEL FORMULARIO DE DATOS GENERALES
const formularioAnimal = document.querySelector("#formularioAnimal");
const nombreEditar = document.querySelector("#nombreEditar");
const especieEditar = document.querySelector("#especieEditar");
const razaEditar = document.querySelector("#razaEditar");
const edadEditar = document.querySelector("#edadEditar");
const sexoEditar = document.querySelector("#sexoEditar");
const estadoEditar = document.querySelector("#estadoEditar");
const pesoEditar = document.querySelector("#pesoEditar");
const caracterEditar = document.querySelector("#caracterEditar");
const descripcionEditar = document.querySelector("#descripcionEditar");
const mensajeAnimal = document.querySelector("#mensajeAnimal");
const botonGuardarAnimal = document.querySelector("#botonGuardarAnimal");

// ELEMENTOS DE LA FOTOGRAFÍA
const formularioImagen = document.querySelector("#formularioImagen");
const imagenEditar = document.querySelector("#imagenEditar");
const imagenAnimal = document.querySelector("#imagenAnimal");
const imagenPredeterminada = document.querySelector("#imagenPredeterminada");
const mensajeImagen = document.querySelector("#mensajeImagen");
const botonGuardarImagen = document.querySelector("#botonGuardarImagen");

// ELEMENTOS DEL FORMULARIO CLÍNICO
const formularioClinico = document.querySelector("#formularioClinico");
const necesidadesEditar = document.querySelector("#necesidadesEditar");
const observacionesEditar = document.querySelector("#observacionesEditar");
const mensajeClinico = document.querySelector("#mensajeClinico");
const botonGuardarClinico = document.querySelector("#botonGuardarClinico");
const avisoClinico = document.querySelector("#avisoClinico");

// COMPROBAR SI EL ROL PUEDE EDITAR ANIMALES
function esPersonalAutorizado(rol) {
    return rol === "admin" || rol === "personal" || rol === "veterinario";
}

// COMPROBAR SI EL ROL PUEDE VER LOS DATOS CLÍNICOS
function puedeVerDatosClinicos(rol) {
    return rol === "admin" || rol === "veterinario";
}

// MOSTRAR LA FOTOGRAFÍA
function mostrarImagen(url) {

    if (url) {
        imagenAnimal.src = url;
        imagenAnimal.hidden = false;
        imagenPredeterminada.hidden = true;
    }
}

// RELLENAR EL FORMULARIO CON LOS DATOS GENERALES
function mostrarAnimal(animal) {

    nombreAnimalTitulo.textContent = animal.nombre;

    nombreEditar.value = animal.nombre;
    especieEditar.value = animal.especie;
    razaEditar.value = animal.raza || "";
    sexoEditar.value = animal.sexo;
    estadoEditar.value = animal.estado;
    caracterEditar.value = animal.caracter || "";
    descripcionEditar.value = animal.descripcion || "";

    // LA EDAD Y EL PESO PUEDEN SER 0, POR ESO SE COMPRUEBA NULL
    if (animal.edad !== null) {
        edadEditar.value = animal.edad;
    } else {
        edadEditar.value = "";
    }

    if (animal.peso !== null) {
        pesoEditar.value = animal.peso;
    } else {
        pesoEditar.value = "";
    }
}

// RELLENAR EL FORMULARIO CON LOS DATOS CLÍNICOS
function mostrarDatosClinicos(datos) {

    necesidadesEditar.value = datos.necesidades_especiales || "";
    observacionesEditar.value = datos.observaciones_clinicas || "";
}

// CARGAR LA FICHA DEL ANIMAL
async function cargarFicha() {

    const token = sessionStorage.getItem("token");

    if (!token) {
        mensajeFicha.textContent =
            "Debes iniciar sesión para editar animales.";

        botonCerrarSesion.hidden = true;
        return;
    }

    if (!idAnimal) {
        mensajeFicha.textContent =
            "No se ha indicado qué animal quieres editar.";
        return;
    }

    mensajeFicha.textContent = "Cargando ficha...";

    try {
        const usuario = await obtenerUsuario(token);
        enlaceUsuarios.hidden = usuario.rol !== "admin";

        if (!esPersonalAutorizado(usuario.rol)) {
            mensajeFicha.textContent =
                "No tienes permisos para editar animales.";
            return;
        }

        const animal = await obtenerAnimal(idAnimal);

        mostrarAnimal(animal);

        if (animal.imagen) {
            mostrarImagen("http://127.0.0.1:8000/storage/" + animal.imagen);
        }

        // SOLO VETERINARIO Y ADMINISTRADOR VEN LOS DATOS CLÍNICOS
        if (puedeVerDatosClinicos(usuario.rol)) {
            const resultado = await obtenerDatosClinicos(idAnimal, token);

            mostrarDatosClinicos(resultado.data);
            formularioClinico.hidden = false;
        } else {
            avisoClinico.hidden = false;
        }

        datosAnimal.hidden = false;
        mensajeFicha.textContent = "";

    } catch (error) {
        mensajeFicha.textContent =
            `No se pudo cargar la ficha: ${error.message}`;
    }
}

// GUARDAR LOS DATOS GENERALES
async function guardarAnimal(evento) {

    evento.preventDefault();

    const token = sessionStorage.getItem("token");

    if (!token) {
        mensajeAnimal.textContent =
            "Debes iniciar sesión para editar animales.";
        return;
    }

    const datos = {
        nombre: nombreEditar.value.trim(),
        especie: especieEditar.value,
        raza: razaEditar.value.trim(),
        edad: edadEditar.value,
        sexo: sexoEditar.value,
        estado: estadoEditar.value,
        peso: pesoEditar.value,
        caracter: caracterEditar.value.trim(),
        descripcion: descripcionEditar.value.trim()
    };

    mensajeAnimal.textContent = "Guardando cambios...";
    botonGuardarAnimal.disabled = true;

    try {
        const resultado = await actualizarAnimal(idAnimal, datos, token);

        mostrarAnimal(resultado.data);

        mensajeAnimal.textContent = resultado.message;

    } catch (error) {
        mensajeAnimal.textContent =
            `No se pudo actualizar el animal: ${error.message}`;

    } finally {
        botonGuardarAnimal.disabled = false;
    }
}

// GUARDAR LA FOTOGRAFÍA
async function guardarImagen(evento) {

    evento.preventDefault();

    const token = sessionStorage.getItem("token");
    const imagen = imagenEditar.files[0];

    if (!token) {
        mensajeImagen.textContent =
            "Debes iniciar sesión para cambiar la fotografía.";
        return;
    }

    if (!imagen) {
        mensajeImagen.textContent =
            "Selecciona una fotografía.";
        return;
    }

    mensajeImagen.textContent = "Guardando fotografía...";
    botonGuardarImagen.disabled = true;

    try {
        const resultado = await actualizarImagenAnimal(idAnimal, imagen, token);

        mostrarImagen(resultado.data.imagen_url);

        mensajeImagen.textContent = resultado.message;
        formularioImagen.reset();

    } catch (error) {
        mensajeImagen.textContent =
            `No se pudo guardar la fotografía: ${error.message}`;

    } finally {
        botonGuardarImagen.disabled = false;
    }
}

// GUARDAR LOS DATOS CLÍNICOS
async function guardarDatosClinicos(evento) {

    evento.preventDefault();

    const token = sessionStorage.getItem("token");

    if (!token) {
        mensajeClinico.textContent =
            "Debes iniciar sesión para editar los datos clínicos.";
        return;
    }

    const datos = {
        necesidades_especiales: necesidadesEditar.value.trim(),
        observaciones_clinicas: observacionesEditar.value.trim()
    };

    mensajeClinico.textContent = "Guardando datos clínicos...";
    botonGuardarClinico.disabled = true;

    try {
        const resultado = await actualizarDatosClinicos(idAnimal, datos, token);

        mostrarDatosClinicos(resultado.data);

        mensajeClinico.textContent = resultado.message;

    } catch (error) {
        mensajeClinico.textContent =
            `No se pudieron guardar los datos clínicos: ${error.message}`;

    } finally {
        botonGuardarClinico.disabled = false;
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
        mensajeFicha.textContent =
            `No se pudo cerrar sesión: ${error.message}`;

    } finally {
        botonCerrarSesion.disabled = false;
    }
}

// ESCUCHAR LOS EVENTOS
formularioAnimal.addEventListener("submit", guardarAnimal);
formularioImagen.addEventListener("submit", guardarImagen);
formularioClinico.addEventListener("submit", guardarDatosClinicos);
botonCerrarSesion.addEventListener("click", salirSesion);

// CARGAR LA FICHA AL ABRIR LA PÁGINA
cargarFicha();