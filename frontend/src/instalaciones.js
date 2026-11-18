import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import {
    obtenerZonas,
    crearZona,
    actualizarZona,
    cambiarActivoZona,
    obtenerHabitaculos,
    crearHabitaculo,
    actualizarHabitaculo,
    cambiarActivoHabitaculo
} from "./providers/instalacionesProvider.js";

document.addEventListener("DOMContentLoaded", function () {
    // -------------------------------------------------------------
    // ELEMENTOS DEL DOM
    // -------------------------------------------------------------
    const mensajeInstalaciones = document.querySelector("#mensajeInstalaciones");
    const contenidoInstalaciones = document.querySelector("#contenidoInstalaciones");
    const botonCerrarSesion = document.querySelector("#botonCerrarSesion");

    // Filtros
    const formularioFiltros = document.querySelector("#formularioFiltros");
    const filtroZona = document.querySelector("#filtroZona");
    const filtroLimpieza = document.querySelector("#filtroLimpieza");
    const botonLimpiarFiltros = document.querySelector("#botonLimpiarFiltros");

    // Zonas
    const tablaZonas = document.querySelector("#tablaZonas");
    const abrirAltaZona = document.querySelector("#abrirAltaZona");
    const modalZona = document.querySelector("#modalZona");
    const cerrarModalZona = document.querySelector("#cerrarModalZona");
    const tituloModalZona = document.querySelector("#tituloModalZona");
    const formularioZona = document.querySelector("#formularioZona");
    const zonaId = document.querySelector("#zonaId");
    const zonaNombre = document.querySelector("#zonaNombre");
    const zonaDescripcion = document.querySelector("#zonaDescripcion");
    const botonGuardarZona = document.querySelector("#botonGuardarZona");
    const mensajeZona = document.querySelector("#mensajeZona");

    // Habitáculos
    const tablaHabitaculos = document.querySelector("#tablaHabitaculos");
    const abrirAltaHabitaculo = document.querySelector("#abrirAltaHabitaculo");
    const modalHabitaculo = document.querySelector("#modalHabitaculo");
    const cerrarModalHabitaculo = document.querySelector("#cerrarModalHabitaculo");
    const tituloModalHabitaculo = document.querySelector("#tituloModalHabitaculo");
    const formularioHabitaculo = document.querySelector("#formularioHabitaculo");
    const habitaculoId = document.querySelector("#habitaculoId");
    const habitaculoZona = document.querySelector("#habitaculoZona");
    const habitaculoIdentificador = document.querySelector("#habitaculoIdentificador");
    const habitaculoCapacidad = document.querySelector("#habitaculoCapacidad");
    const habitaculoLimpieza = document.querySelector("#habitaculoLimpieza");
    const habitaculoMantenimiento = document.querySelector("#habitaculoMantenimiento");
    const habitaculoUltimaLimpieza = document.querySelector("#habitaculoUltimaLimpieza");
    const habitaculoProximaLimpieza = document.querySelector("#habitaculoProximaLimpieza");
    const habitaculoObservaciones = document.querySelector("#habitaculoObservaciones");
    const botonGuardarHabitaculo = document.querySelector("#botonGuardarHabitaculo");
    const mensajeHabitaculo = document.querySelector("#mensajeHabitaculo");

    // CAMPOS QUE PUEDEN TENER ERRORES DE VALIDACIÓN
    const camposZona = ["nombre", "descripcion"];
    const camposHabitaculo = [
        "zona_id",
        "identificador",
        "capacidad",
        "estado_limpieza",
        "estado_mantenimiento",
        "fecha_ultima_limpieza",
        "fecha_proxima_limpieza",
        "observaciones"
    ];

    function obtenerToken() {
        // El token se mira primero en la sesion porque es donde se guarda al entrar.
        return sessionStorage.getItem("token") || localStorage.getItem("token");
    }

    function mostrarMensaje(elemento, texto) {
        elemento.textContent = texto;
    }

    function crearCelda(texto) {
        const celda = document.createElement("td");
        celda.textContent = texto;
        return celda;
    }

    // BOTONES DE LAS FILAS CON LAS MISMAS CLASES QUE LA TABLA DE USUARIOS
    function crearBoton(texto, clase) {
        const boton = document.createElement("button");
        boton.type = "button";
        boton.className = "boton-detalle " + clase;
        boton.textContent = texto;
        return boton;
    }

    // BORRAR LOS ERRORES DE UN FORMULARIO
    function limpiarErrores(prefijo, campos) {
        for (let i = 0; i < campos.length; i++) {
            const parrafo = document.querySelector("#error-" + prefijo + "-" + campos[i]);
            if (parrafo) parrafo.textContent = "";
        }
    }

    // ESCRIBIR CADA ERROR DEBAJO DE SU CAMPO
    function mostrarErrores(prefijo, campos, errores) {
        for (let i = 0; i < campos.length; i++) {
            const parrafo = document.querySelector("#error-" + prefijo + "-" + campos[i]);
            if (parrafo && errores[campos[i]]) {
                parrafo.textContent = errores[campos[i]][0];
            }
        }
    }

     // -------------------------------------------------------------
    // COMPROBAR QUE ES EL ADMINISTRADOR
    // -------------------------------------------------------------
    async function comprobarAcceso() {
        // Esta pagina solo la puede usar el admin, aunque la ruta tambien se protege en la API.
        const token = obtenerToken();

        if (!token) {
            window.location.href = "login.html";
            return;
        }

        try {
            const usuario = await obtenerUsuario(token);

            if (usuario.rol !== "admin") {
                mostrarMensaje(mensajeInstalaciones, "Solo el administrador puede gestionar las instalaciones.");
                return;
            }

            contenidoInstalaciones.hidden = false;
            await cargarZonas();
            await cargarHabitaculos();

        } catch (error) {
            mostrarMensaje(mensajeInstalaciones, "No se pudo comprobar tu sesión: " + error.message);
        }
    }

      // Al guardar se vuelve a cargar la tabla para que se vean los datos nuevos.
    // -------------------------------------------------------------
    // ZONAS
    // -------------------------------------------------------------
    async function cargarZonas() {
        try {
            const resultado = await obtenerZonas(obtenerToken());
            const zonas = resultado.data;

            pintarTablaZonas(zonas);
            rellenarSelectoresZona(zonas);

        } catch (error) {
            mostrarMensaje(mensajeInstalaciones, error.message);
        }
    }

    function pintarTablaZonas(zonas) {
        tablaZonas.replaceChildren();

        if (zonas.length === 0) {
            const fila = document.createElement("tr");
            const celda = crearCelda("Todavía no hay zonas.");
            celda.colSpan = 5;
            fila.appendChild(celda);
            tablaZonas.appendChild(fila);
            return;
        }

        for (let i = 0; i < zonas.length; i++) {
            const zona = zonas[i];
            const fila = document.createElement("tr");

            fila.appendChild(crearCelda(zona.nombre));
            fila.appendChild(crearCelda(zona.descripcion || "-"));
            fila.appendChild(crearCelda(zona.habitaculos_activos_count + " de " + zona.habitaculos_count));
            fila.appendChild(crearCelda(zona.activo ? "Activa" : "Desactivada"));

            const celdaAcciones = document.createElement("td");
            const acciones = document.createElement("div");
            acciones.className = "acciones-cuenta";

            const botonEditar = crearBoton("Editar", "boton-editar-usuario");
            botonEditar.addEventListener("click", function () {
                abrirEdicionZona(zona);
            });

            const botonActivo = crearBoton(zona.activo ? "Desactivar" : "Activar", "boton-desactivar-usuario");
            botonActivo.addEventListener("click", function () {
                cambiarEstadoZona(zona);
            });

            acciones.appendChild(botonEditar);
            acciones.appendChild(botonActivo);
            celdaAcciones.appendChild(acciones);
            fila.appendChild(celdaAcciones);

            tablaZonas.appendChild(fila);
        }
    }

    // EN EL FORMULARIO DE HABITÁCULOS SOLO SE PUEDEN ELEGIR ZONAS ACTIVAS;
    // EN EL FILTRO SALEN TODAS
    function rellenarSelectoresZona(zonas) {
        const filtroElegido = filtroZona.value;

        habitaculoZona.replaceChildren();
        filtroZona.replaceChildren();

        const opcionVacia = document.createElement("option");
        opcionVacia.value = "";
        opcionVacia.textContent = "Selecciona una zona";
        habitaculoZona.appendChild(opcionVacia);

        const opcionTodas = document.createElement("option");
        opcionTodas.value = "";
        opcionTodas.textContent = "Todas las zonas";
        filtroZona.appendChild(opcionTodas);

        for (let i = 0; i < zonas.length; i++) {
            if (zonas[i].activo) {
                const opcion = document.createElement("option");
                opcion.value = zonas[i].id;
                opcion.textContent = zonas[i].nombre;
                habitaculoZona.appendChild(opcion);
            }

            const opcionFiltro = document.createElement("option");
            opcionFiltro.value = zonas[i].id;
            opcionFiltro.textContent = zonas[i].nombre;
            filtroZona.appendChild(opcionFiltro);
        }

        filtroZona.value = filtroElegido;
    }

    // ABRIR EL MODAL VACÍO PARA DAR DE ALTA
    abrirAltaZona.addEventListener("click", function () {
        formularioZona.reset();
        zonaId.value = "";
        limpiarErrores("zona", camposZona);
        mostrarMensaje(mensajeZona, "");
        tituloModalZona.textContent = "Añadir zona";
        modalZona.showModal();
    });

    // ABRIR EL MODAL CON LOS DATOS DE LA ZONA PARA EDITARLA
    function abrirEdicionZona(zona) {
        limpiarErrores("zona", camposZona);
        mostrarMensaje(mensajeZona, "");
        zonaId.value = zona.id;
        zonaNombre.value = zona.nombre;
        zonaDescripcion.value = zona.descripcion || "";
        tituloModalZona.textContent = "Editar zona";
        modalZona.showModal();
    }

    cerrarModalZona.addEventListener("click", function () {
        modalZona.close();
    });

    formularioZona.addEventListener("submit", async function (e) {
        e.preventDefault();
        limpiarErrores("zona", camposZona);

        const datos = {
            nombre: zonaNombre.value.trim(),
            descripcion: zonaDescripcion.value.trim()
        };

        botonGuardarZona.disabled = true;
        mostrarMensaje(mensajeZona, "Guardando zona...");

        try {
            let resultado;

            if (zonaId.value) {
                resultado = await actualizarZona(zonaId.value, datos, obtenerToken());
            } else {
                resultado = await crearZona(datos, obtenerToken());
            }

            modalZona.close();
            mostrarMensaje(mensajeInstalaciones, resultado.message);
            await cargarZonas();
            await cargarHabitaculos();

        } catch (error) {
            if (error.errores) {
                mostrarErrores("zona", camposZona, error.errores);
                mostrarMensaje(mensajeZona, "Revisa los campos marcados.");
            } else {
                mostrarMensaje(mensajeZona, error.message);
            }
        } finally {
            botonGuardarZona.disabled = false;
        }
    });

    async function cambiarEstadoZona(zona) {
        let pregunta = "¿Quieres activar la zona " + zona.nombre + "?";

        if (zona.activo) {
            pregunta = "¿Quieres desactivar la zona " + zona.nombre + "?";
        }

        if (!confirm(pregunta)) {
            return;
        }

        try {
            const resultado = await cambiarActivoZona(zona.id, obtenerToken());
            mostrarMensaje(mensajeInstalaciones, resultado.message);
            await cargarZonas();

        } catch (error) {
            // Por ejemplo: la zona todavía tiene habitáculos activos
            mostrarMensaje(mensajeInstalaciones, error.message);
        }
    }

     // -------------------------------------------------------------
    // HABITÁCULOS
    // -------------------------------------------------------------
    async function cargarHabitaculos() {
        const filtros = {
            zona_id: filtroZona.value,
            estado_limpieza: filtroLimpieza.value
        };

        try {
            const resultado = await obtenerHabitaculos(filtros, obtenerToken());
            pintarTablaHabitaculos(resultado.data);

        } catch (error) {
            mostrarMensaje(mensajeInstalaciones, error.message);
        }
    }

    function pintarTablaHabitaculos(habitaculos) {
        tablaHabitaculos.replaceChildren();

        if (habitaculos.length === 0) {
            const fila = document.createElement("tr");
            const celda = crearCelda("No hay habitáculos con esos filtros.");
            celda.colSpan = 9;
            fila.appendChild(celda);
            tablaHabitaculos.appendChild(fila);
            return;
        }

        for (let i = 0; i < habitaculos.length; i++) {
            const habitaculo = habitaculos[i];
            const fila = document.createElement("tr");

            fila.appendChild(crearCelda(habitaculo.identificador));
            fila.appendChild(crearCelda(habitaculo.zona.nombre));
            fila.appendChild(crearCelda(habitaculo.capacidad));
            fila.appendChild(crearCelda(habitaculo.estado_limpieza));
            fila.appendChild(crearCelda(habitaculo.estado_mantenimiento));
            fila.appendChild(crearCelda(habitaculo.fecha_ultima_limpieza || "-"));
            fila.appendChild(crearCelda(habitaculo.fecha_proxima_limpieza || "-"));
            fila.appendChild(crearCelda(habitaculo.activo ? "Activo" : "Desactivado"));

            const celdaAcciones = document.createElement("td");
            const acciones = document.createElement("div");
            acciones.className = "acciones-cuenta";

            const botonEditar = crearBoton("Editar", "boton-editar-usuario");
            botonEditar.addEventListener("click", function () {
                abrirEdicionHabitaculo(habitaculo);
            });

            const botonActivo = crearBoton(habitaculo.activo ? "Desactivar" : "Activar", "boton-desactivar-usuario");
            botonActivo.addEventListener("click", function () {
                cambiarEstadoHabitaculo(habitaculo);
            });

            acciones.appendChild(botonEditar);
            acciones.appendChild(botonActivo);
            celdaAcciones.appendChild(acciones);
            fila.appendChild(celdaAcciones);

            tablaHabitaculos.appendChild(fila);
        }
    }

    // ABRIR EL MODAL VACÍO PARA DAR DE ALTA
    abrirAltaHabitaculo.addEventListener("click", function () {
        formularioHabitaculo.reset();
        habitaculoId.value = "";
        limpiarErrores("habitaculo", camposHabitaculo);
        mostrarMensaje(mensajeHabitaculo, "");
        tituloModalHabitaculo.textContent = "Añadir habitáculo";
        modalHabitaculo.showModal();
    });

    // ABRIR EL MODAL CON LOS DATOS DEL HABITÁCULO PARA EDITARLO
    function abrirEdicionHabitaculo(habitaculo) {
        limpiarErrores("habitaculo", camposHabitaculo);
        mostrarMensaje(mensajeHabitaculo, "");
        habitaculoId.value = habitaculo.id;
        habitaculoZona.value = habitaculo.zona_id;
        habitaculoIdentificador.value = habitaculo.identificador;
        habitaculoCapacidad.value = habitaculo.capacidad;
        habitaculoLimpieza.value = habitaculo.estado_limpieza;
        habitaculoMantenimiento.value = habitaculo.estado_mantenimiento;
        habitaculoUltimaLimpieza.value = habitaculo.fecha_ultima_limpieza || "";
        habitaculoProximaLimpieza.value = habitaculo.fecha_proxima_limpieza || "";
        habitaculoObservaciones.value = habitaculo.observaciones || "";
        tituloModalHabitaculo.textContent = "Editar habitáculo";
        modalHabitaculo.showModal();
    }

    cerrarModalHabitaculo.addEventListener("click", function () {
        modalHabitaculo.close();
    });

    formularioHabitaculo.addEventListener("submit", async function (e) {
        e.preventDefault();
        limpiarErrores("habitaculo", camposHabitaculo);

        const datos = {
            zona_id: habitaculoZona.value,
            identificador: habitaculoIdentificador.value.trim(),
            capacidad: habitaculoCapacidad.value,
            estado_limpieza: habitaculoLimpieza.value,
            estado_mantenimiento: habitaculoMantenimiento.value,
            fecha_ultima_limpieza: habitaculoUltimaLimpieza.value,
            fecha_proxima_limpieza: habitaculoProximaLimpieza.value,
            observaciones: habitaculoObservaciones.value.trim()
        };

        botonGuardarHabitaculo.disabled = true;
        mostrarMensaje(mensajeHabitaculo, "Guardando habitáculo...");

        try {
            let resultado;

            if (habitaculoId.value) {
                resultado = await actualizarHabitaculo(habitaculoId.value, datos, obtenerToken());
            } else {
                resultado = await crearHabitaculo(datos, obtenerToken());
            }

            modalHabitaculo.close();
            mostrarMensaje(mensajeInstalaciones, resultado.message);
            await cargarHabitaculos();
            await cargarZonas();

        } catch (error) {
            if (error.errores) {
                mostrarErrores("habitaculo", camposHabitaculo, error.errores);
                mostrarMensaje(mensajeHabitaculo, "Revisa los campos marcados.");
            } else {
                mostrarMensaje(mensajeHabitaculo, error.message);
            }
        } finally {
            botonGuardarHabitaculo.disabled = false;
        }
    });

    async function cambiarEstadoHabitaculo(habitaculo) {
        let pregunta = "¿Quieres activar el habitáculo " + habitaculo.identificador + "?";

        if (habitaculo.activo) {
            pregunta = "¿Quieres desactivar el habitáculo " + habitaculo.identificador + "?";
        }

        if (!confirm(pregunta)) {
            return;
        }

        try {
            const resultado = await cambiarActivoHabitaculo(habitaculo.id, obtenerToken());
            mostrarMensaje(mensajeInstalaciones, resultado.message);
            await cargarHabitaculos();
            await cargarZonas();

        } catch (error) {
            // Por ejemplo: su zona está desactivada
            mostrarMensaje(mensajeInstalaciones, error.message);
        }
    }

    // -------------------------------------------------------------
    // FILTROS
    // -------------------------------------------------------------
    formularioFiltros.addEventListener("submit", function (e) {
        e.preventDefault();
        cargarHabitaculos();
    });

    botonLimpiarFiltros.addEventListener("click", function () {
        filtroZona.value = "";
        filtroLimpieza.value = "";
        cargarHabitaculos();
    });

    // -------------------------------------------------------------
    // CERRAR SESIÓN
    // -------------------------------------------------------------
    botonCerrarSesion.addEventListener("click", async function () {
        const token = obtenerToken();

        botonCerrarSesion.disabled = true;

        if (token) {
            try {
                await cerrarSesion(token);
            } catch (error) {
                console.error("Error al cerrar sesión en el servidor:", error);
            }
        }

        sessionStorage.removeItem("token");
        localStorage.removeItem("token");
        window.location.href = "index.html";
    });

    comprobarAcceso();
});
