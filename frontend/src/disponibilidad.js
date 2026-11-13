// Importa las funciones para consultar y cerrar la sesión actual.
import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

// Importa las funciones que consultan y registran disponibilidades.
import {
    obtenerDisponibilidades,
    crearDisponibilidad,
    obtenerEmpleadosParaDisponibilidad
} from "./providers/disponibilidadProvider.js";

// Importa las funciones que consultan y registran ausencias.
import {
    obtenerAusencias,
    crearAusencia
} from "./providers/ausenciaProvider.js";

// Guarda referencias a los elementos del HTML que la página necesita usar.
const mensaje = document.querySelector("#mensajeDisponibilidad");
const gestion = document.querySelector("#gestionDisponibilidad");
const botonCerrarSesion = document.querySelector("#botonCerrarSesion");

const formularioDisponibilidad =
    document.querySelector("#formularioDisponibilidad");
const empleadoDisponibilidad =
    document.querySelector("#empleadoDisponibilidad");
const inicioDisponibilidad =
    document.querySelector("#inicioDisponibilidad");
const finDisponibilidad =
    document.querySelector("#finDisponibilidad");
const mensajeFormularioDisponibilidad =
    document.querySelector("#mensajeFormularioDisponibilidad");
const botonGuardarDisponibilidad =
    document.querySelector("#botonGuardarDisponibilidad");

const formularioAusencia =
    document.querySelector("#formularioAusencia");
const empleadoAusencia =
    document.querySelector("#empleadoAusencia");
const inicioAusencia =
    document.querySelector("#inicioAusencia");
const finAusencia =
    document.querySelector("#finAusencia");
const motivoAusencia =
    document.querySelector("#motivoAusencia");
const mensajeFormularioAusencia =
    document.querySelector("#mensajeFormularioAusencia");
const botonGuardarAusencia =
    document.querySelector("#botonGuardarAusencia");

const formularioFiltros =
    document.querySelector("#formularioFiltrosDisponibilidad");
const filtroEmpleado =
    document.querySelector("#filtroEmpleado");
const filtroFechaInicio =
    document.querySelector("#filtroFechaInicio");
const filtroFechaFin =
    document.querySelector("#filtroFechaFin");
const listaDisponibilidades =
    document.querySelector("#listaDisponibilidades");
const listaAusencias =
    document.querySelector("#listaAusencias");
const botonBuscar =
    document.querySelector("#botonBuscarDisponibilidad");

// Devuelve el token de sesión guardado cuando el usuario inició sesión.
function obtenerToken() {
    return sessionStorage.getItem("token");
}

// Convierte una fecha a texto local para mostrarla en las tablas.
function mostrarFecha(fecha) {
    if (!fecha) {
        return "Sin fecha";
    }

    return new Date(fecha).toLocaleString("es-ES");
}

// Crea una celda de tabla usando textContent para tratar el contenido como texto.
function crearCelda(texto) {
    const celda = document.createElement("td");
    celda.textContent = texto ?? "Sin datos";
    return celda;
}

// Añade una fila con un mensaje cuando una tabla no tiene resultados.
function mostrarFilaVacia(tabla, columnas, texto) {
    const fila = document.createElement("tr");
    const celda = crearCelda(texto);

    celda.colSpan = columnas;
    fila.appendChild(celda);
    tabla.replaceChildren(fila);
}

// Devuelve el nombre del usuario asociado al registro del empleado.
function nombreEmpleado(registro) {
    return registro.empleado?.user?.name
        || registro.empleado?.user?.email
        || "Empleado sin nombre";
}

// Rellena los tres selectores con los empleados que devuelve Laravel.
function cargarOpcionesEmpleados(empleados) {
    const selectores = [
        empleadoDisponibilidad,
        empleadoAusencia,
        filtroEmpleado
    ];

    selectores.forEach(function (selector) {
        // Conserva la primera opción del HTML y elimina opciones antiguas.
        selector.replaceChildren(selector.options[0]);

        empleados.forEach(function (empleado) {
            const opcion = document.createElement("option");

            opcion.value = empleado.id;
            opcion.textContent =
                `${empleado.user?.name || empleado.user?.email || "Empleado"} — ${empleado.puesto}`;

            selector.appendChild(opcion);
        });
    });
}

// Dibuja en la tabla los periodos de disponibilidad recibidos de Laravel.
function mostrarDisponibilidades(disponibilidades) {
    listaDisponibilidades.replaceChildren();

    if (disponibilidades.length === 0) {
        mostrarFilaVacia(
            listaDisponibilidades,
            3,
            "No hay disponibilidades en el periodo seleccionado."
        );
        return;
    }

    disponibilidades.forEach(function (disponibilidad) {
        const fila = document.createElement("tr");

        fila.appendChild(crearCelda(nombreEmpleado(disponibilidad)));
        fila.appendChild(crearCelda(mostrarFecha(disponibilidad.fecha_inicio)));
        fila.appendChild(crearCelda(mostrarFecha(disponibilidad.fecha_fin)));

        listaDisponibilidades.appendChild(fila);
    });
}

// Dibuja en la tabla las ausencias recibidas de Laravel.
function mostrarAusencias(ausencias) {
    listaAusencias.replaceChildren();

    if (ausencias.length === 0) {
        mostrarFilaVacia(
            listaAusencias,
            4,
            "No hay ausencias en el periodo seleccionado."
        );
        return;
    }

    ausencias.forEach(function (ausencia) {
        const fila = document.createElement("tr");

        fila.appendChild(crearCelda(nombreEmpleado(ausencia)));
        fila.appendChild(crearCelda(mostrarFecha(ausencia.fecha_inicio)));
        fila.appendChild(crearCelda(mostrarFecha(ausencia.fecha_fin)));
        fila.appendChild(crearCelda(ausencia.motivo || "Sin motivo indicado"));

        listaAusencias.appendChild(fila);
    });
}

// Consulta ambos listados usando el periodo y el empleado elegidos en los filtros.
async function consultarRegistros() {
    const token = obtenerToken();

    const filtros = {
        fecha_inicio: filtroFechaInicio.value,
        fecha_fin: filtroFechaFin.value,
        empleado_id: filtroEmpleado.value
    };

    botonBuscar.disabled = true;
    mensaje.textContent = "Consultando disponibilidades y ausencias...";

    try {
        const [resultadoDisponibilidades, resultadoAusencias] =
            await Promise.all([
                obtenerDisponibilidades(filtros, token),
                obtenerAusencias(filtros, token)
            ]);

        mostrarDisponibilidades(resultadoDisponibilidades.data);
        mostrarAusencias(resultadoAusencias.data);

        mensaje.textContent = "Consulta completada.";
    } catch (error) {
        mensaje.textContent = `No se pudieron consultar los datos: ${error.message}`;
    } finally {
        botonBuscar.disabled = false;
    }
}

// Establece un periodo inicial desde hoy hasta dentro de siete días.
function establecerPeriodoInicial() {
    const ahora = new Date();
    const dentroDeSieteDias = new Date(ahora);

    dentroDeSieteDias.setDate(ahora.getDate() + 7);

    const convertirAEntrada = function (fecha) {
        const compensacionZona = fecha.getTimezoneOffset() * 60000;
        return new Date(fecha.getTime() - compensacionZona)
            .toISOString()
            .slice(0, 16);
    };

    filtroFechaInicio.value = convertirAEntrada(ahora);
    filtroFechaFin.value = convertirAEntrada(dentroDeSieteDias);
}

// Comprueba que exista sesión y que la cuenta autenticada sea administradora.
async function comprobarAcceso() {
    const token = obtenerToken();

    if (!token) {
        mensaje.textContent =
            "Debes iniciar sesión como administrador para acceder.";
        botonCerrarSesion.hidden = true;
        return;
    }

    mensaje.textContent = "Comprobando permisos...";

    try {
        const usuario = await obtenerUsuario(token);

        if (usuario.rol !== "admin") {
            mensaje.textContent =
                "No tienes permisos para consultar esta página.";
            return;
        }

        // Muestra el panel que en el HTML empieza oculto con el atributo hidden.
        gestion.hidden = false;

        establecerPeriodoInicial();

        const resultadoEmpleados =
            await obtenerEmpleadosParaDisponibilidad(token);

        cargarOpcionesEmpleados(resultadoEmpleados.data);

        await consultarRegistros();
    } catch (error) {
        mensaje.textContent =
            `No se pudo cargar la página: ${error.message}`;
    }
}

// Registra una nueva disponibilidad para el empleado y horario elegidos.
async function guardarDisponibilidad(evento) {
    evento.preventDefault();

    const token = obtenerToken();
    const datos = {
        empleado_id: empleadoDisponibilidad.value,
        fecha_inicio: inicioDisponibilidad.value,
        fecha_fin: finDisponibilidad.value
    };

    botonGuardarDisponibilidad.disabled = true;
    mensajeFormularioDisponibilidad.textContent =
        "Guardando disponibilidad...";

    try {
        await crearDisponibilidad(datos, token);

        formularioDisponibilidad.reset();
        mensajeFormularioDisponibilidad.textContent =
            "Disponibilidad guardada correctamente.";

        await consultarRegistros();
    } catch (error) {
        mensajeFormularioDisponibilidad.textContent =
            `No se pudo guardar: ${error.message}`;
    } finally {
        botonGuardarDisponibilidad.disabled = false;
    }
}

// Registra una ausencia, incluyendo el motivo si se escribió uno.
async function guardarAusencia(evento) {
    evento.preventDefault();

    const token = obtenerToken();
    const datos = {
        empleado_id: empleadoAusencia.value,
        fecha_inicio: inicioAusencia.value,
        fecha_fin: finAusencia.value,
        motivo: motivoAusencia.value.trim() || null
    };

    botonGuardarAusencia.disabled = true;
    mensajeFormularioAusencia.textContent = "Registrando ausencia...";

    try {
        await crearAusencia(datos, token);

        formularioAusencia.reset();
        mensajeFormularioAusencia.textContent =
            "Ausencia registrada correctamente.";

        await consultarRegistros();
    } catch (error) {
        mensajeFormularioAusencia.textContent =
            `No se pudo registrar: ${error.message}`;
    } finally {
        botonGuardarAusencia.disabled = false;
    }
}

// Cierra la sesión y vuelve a la página de inicio.
async function salirSesion() {
    const token = obtenerToken();

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
        botonCerrarSesion.disabled = false;
    }
}

// Conecta el envío de cada formulario con su función correspondiente.
formularioDisponibilidad.addEventListener(
    "submit",
    guardarDisponibilidad
);

formularioAusencia.addEventListener(
    "submit",
    guardarAusencia
);

formularioFiltros.addEventListener(
    "submit",
    function (evento) {
        evento.preventDefault();
        consultarRegistros();
    }
);

// Conecta el botón de cierre de sesión.
botonCerrarSesion.addEventListener("click", salirSesion);

// Inicia la comprobación de sesión y carga inicial de la página.
comprobarAcceso();