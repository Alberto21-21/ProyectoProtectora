import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import {
    obtenerHogar,
    guardarHogar
} from "./providers/hogarProvider.js";

// ELEMENTOS DE LA PÁGINA
const estado = document.querySelector("#mensajeHogar");
const seccion = document.querySelector("#seccionHogar");
const formulario = document.querySelector("#formularioHogar");
const mensajeGuardar = document.querySelector("#mensajeGuardarHogar");
const botonGuardar = document.querySelector("#botonGuardarHogar");
const botonCerrarSesion = document.querySelector("#botonCerrarSesion");

// CAMPOS DEL FORMULARIO
const tipoVivienda = document.querySelector("#tipoVivienda");
const superficie = document.querySelector("#superficieHogar");
const tieneJardin = document.querySelector("#tieneJardin");
const tieneTerraza = document.querySelector("#tieneTerraza");
const superficieExterior = document.querySelector("#superficieExterior");
const numeroPersonas = document.querySelector("#numeroPersonas");
const hayNinos = document.querySelector("#hayNinos");
const edadesNinos = document.querySelector("#edadesNinos");
const hayOtrosAnimales = document.querySelector("#hayOtrosAnimales");
const otrosAnimales = document.querySelector("#otrosAnimales");
const experiencia = document.querySelector("#experienciaHogar");

// PREFERENCIAS DE ADOPCIÓN
const minutosPaseos = document.querySelector("#minutosPaseos");
const minutosCuidados = document.querySelector("#minutosCuidados");
const nivelActividad = document.querySelector("#nivelActividad");
const especiePreferida = document.querySelector("#especiePreferida");
const tamanoPreferido = document.querySelector("#tamanoPreferido");
const edadMinima = document.querySelector("#edadMinima");
const edadMaxima = document.querySelector("#edadMaxima");
const aceptaNecesidades = document.querySelector("#aceptaNecesidades");
const cuidadosAsumibles = document.querySelector("#cuidadosAsumibles");

// BLOQUES CONDICIONALES
const campoExterior = document.querySelector("#campoExterior");
const campoEdades = document.querySelector("#campoEdades");
const campoOtrosAnimales = document.querySelector("#campoOtrosAnimales");

// MOSTRAR LOS CAMPOS QUE CORRESPONDEN
function actualizarCampos() {

    const hayExterior =
        tieneJardin.value === "1" || tieneTerraza.value === "1";

    campoExterior.hidden = !hayExterior;
    superficieExterior.disabled = !hayExterior;
    superficieExterior.required = hayExterior;

    const hayMenores = hayNinos.value === "1";

    campoEdades.hidden = !hayMenores;
    edadesNinos.disabled = !hayMenores;
    edadesNinos.required = hayMenores;

    const hayAnimales = hayOtrosAnimales.value === "1";

    campoOtrosAnimales.hidden = !hayAnimales;
    otrosAnimales.disabled = !hayAnimales;
    otrosAnimales.required = hayAnimales;
}

// RELLENAR EL FORMULARIO
function mostrarHogar(hogar) {

    tipoVivienda.value = hogar.tipo_vivienda;
    superficie.value = hogar.superficie;

    tieneJardin.value = hogar.tiene_jardin ? "1" : "0";
    tieneTerraza.value = hogar.tiene_terraza ? "1" : "0";

    superficieExterior.value = hogar.superficie_exterior ?? "";
    numeroPersonas.value = hogar.numero_personas;

    hayNinos.value = hogar.hay_ninos ? "1" : "0";
    edadesNinos.value = hogar.edades_ninos
        ? hogar.edades_ninos.join(", ")
        : "";

    hayOtrosAnimales.value = hogar.hay_otros_animales ? "1" : "0";
    otrosAnimales.value = hogar.otros_animales ?? "";
    experiencia.value = hogar.experiencia;

    // MOSTRAR LAS PREFERENCIAS GUARDADAS
    minutosPaseos.value = hogar.minutos_paseos ?? "";
    minutosCuidados.value = hogar.minutos_cuidados ?? "";
    nivelActividad.value = hogar.nivel_actividad ?? "";
    especiePreferida.value = hogar.especie_preferida ?? "";
    tamanoPreferido.value = hogar.tamano_preferido ?? "";
    edadMinima.value = hogar.edad_minima ?? "";
    edadMaxima.value = hogar.edad_maxima ?? "";
    cuidadosAsumibles.value = hogar.cuidados_asumibles ?? "";

    if (
        hogar.acepta_necesidades_especiales === null ||
        hogar.acepta_necesidades_especiales === undefined
    ) {
        aceptaNecesidades.value = "";
    } else {
        aceptaNecesidades.value =
            hogar.acepta_necesidades_especiales ? "1" : "0";
    }

    actualizarCampos();
}

// CONSULTAR LA SESIÓN Y EL HOGAR
async function cargarHogar() {

    const token = sessionStorage.getItem("token");

    if (!token) {
        estado.textContent = "Debes iniciar sesión para consultar tu hogar.";
        botonCerrarSesion.hidden = true;
        return;
    }

    estado.textContent = "Cargando hogar...";

    try {
        const usuario = await obtenerUsuario(token);

        if (usuario.rol !== "adoptante") {
            estado.textContent =
                "Esta sección está disponible para adoptantes.";
            return;
        }

        const resultado = await obtenerHogar(token);

        if (resultado.data) {
            mostrarHogar(resultado.data);
            estado.textContent = "";
        } else {
            actualizarCampos();
            estado.textContent = "Completa los datos de tu hogar.";
        }

        seccion.hidden = false;

    } catch (error) {
        estado.textContent =
            `No se pudo cargar el hogar: ${error.message}`;
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

// EVENTOS
tieneJardin.addEventListener("change", actualizarCampos);
tieneTerraza.addEventListener("change", actualizarCampos);
hayNinos.addEventListener("change", actualizarCampos);
hayOtrosAnimales.addEventListener("change", actualizarCampos);

botonCerrarSesion.addEventListener("click", salirSesion);

// GUARDAR EL HOGAR
async function enviarHogar(evento) {

    evento.preventDefault();

    const token = sessionStorage.getItem("token");

    if (!token) {
        mensajeGuardar.textContent = "Debes iniciar sesión.";
        return;
    }

    mensajeGuardar.textContent = "";

    // CONVERTIR LAS EDADES EN UNA LISTA DE NÚMEROS
    const edades = [];

    if (hayNinos.value === "1") {
        const textos = edadesNinos.value.split(",");

        for (const texto of textos) {
            const edad = Number(texto.trim());

            if (
                texto.trim() === "" ||
                !Number.isInteger(edad) ||
                edad < 0 ||
                edad > 17
            ) {
                mensajeGuardar.textContent =
                    "Indica edades enteras entre 0 y 17, separadas por comas.";
                return;
            }

            edades.push(edad);
        }

        if (edades.length > Number(numeroPersonas.value)) {
            mensajeGuardar.textContent =
                "El número de niños no puede superar el total de personas.";
            return;
        }
    }

    const hayExterior =
        tieneJardin.value === "1" || tieneTerraza.value === "1";

        // COMPROBAR EL INTERVALO DE EDAD
        if (edadMinima.value !== "" && edadMaxima.value !== "") {
            if (Number(edadMaxima.value) < Number(edadMinima.value)) {
                mensajeGuardar.textContent =
                    "La edad máxima no puede ser menor que la mínima.";
                return;
            }
        }

    const datos = {
        tipo_vivienda: tipoVivienda.value,
        superficie: Number(superficie.value),
        tiene_jardin: Number(tieneJardin.value),
        tiene_terraza: Number(tieneTerraza.value),
        superficie_exterior: hayExterior
            ? Number(superficieExterior.value)
            : null,
        numero_personas: Number(numeroPersonas.value),
        hay_ninos: Number(hayNinos.value),
        edades_ninos: hayNinos.value === "1" ? edades : null,
        hay_otros_animales: Number(hayOtrosAnimales.value),
        otros_animales: hayOtrosAnimales.value === "1"
            ? otrosAnimales.value.trim()
            : null,
        experiencia: experiencia.value.trim(),
        minutos_paseos: Number(minutosPaseos.value),
        minutos_cuidados: Number(minutosCuidados.value),
        nivel_actividad: nivelActividad.value,
        especie_preferida: especiePreferida.value || null,
        tamano_preferido: tamanoPreferido.value || null,
        edad_minima: edadMinima.value !== ""
            ? Number(edadMinima.value)
            : null,
        edad_maxima: edadMaxima.value !== ""
            ? Number(edadMaxima.value)
            : null,
        acepta_necesidades_especiales: Number(aceptaNecesidades.value),
        cuidados_asumibles: cuidadosAsumibles.value.trim() || null
        };

    botonGuardar.disabled = true;
    mensajeGuardar.textContent = "Guardando hogar...";

    try {
        const resultado = await guardarHogar(datos, token);

        mostrarHogar(resultado.data);
        estado.textContent = "";
        mensajeGuardar.textContent = resultado.message;

    } catch (error) {
        mensajeGuardar.textContent =
            `No se pudo guardar el hogar: ${error.message}`;

    } finally {
        botonGuardar.disabled = false;
    }
}

// ENVÍO DEL FORMULARIO
formulario.addEventListener("submit", enviarHogar);

// CARGA INICIAL
cargarHogar();