import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import {
    obtenerZonas,
    obtenerHabitaculos,
    obtenerFichaHabitaculo,
    alojarAnimal,
    obtenerHistorialAlojamientos,
    obtenerAnimalesParaAlojar
} from "./providers/instalacionesProvider.js";

document.addEventListener("DOMContentLoaded", function () {
   
    // ELEMENTOS DEL DOM
   
    const mensajeHabitaculos = document.querySelector("#mensajeHabitaculos");
    const contenidoHabitaculos = document.querySelector("#contenidoHabitaculos");
    const enlaceInstalaciones = document.querySelector("#enlaceInstalaciones");
    const botonCerrarSesion = document.querySelector("#botonCerrarSesion");

    // Filtros y listado
    const formularioFiltros = document.querySelector("#formularioFiltros");
    const filtroZona = document.querySelector("#filtroZona");
    const filtroLimpieza = document.querySelector("#filtroLimpieza");
    const botonLimpiarFiltros = document.querySelector("#botonLimpiarFiltros");
    const tablaHabitaculos = document.querySelector("#tablaHabitaculos");

    // Historial de alojamientos (T25)
    const tablaHistorialAlojamientos = document.querySelector("#tablaHistorialAlojamientos");

    // Modal alojar / trasladar animal (T25)
    const botonAbrirAlojar = document.querySelector("#botonAbrirAlojar");
    const modalAlojarAnimal = document.querySelector("#modalAlojarAnimal");
    const cerrarAlojarAnimal = document.querySelector("#cerrarAlojarAnimal");
    const formularioAlojarAnimal = document.querySelector("#formularioAlojarAnimal");
    const selectAnimalAlojar = document.querySelector("#selectAnimalAlojar");
    const selectHabitaculoDestino = document.querySelector("#selectHabitaculoDestino");
    const observacionesAlojar = document.querySelector("#observacionesAlojar");
    const mensajeAlojarAnimal = document.querySelector("#mensajeAlojarAnimal");
    const botonGuardarAlojamiento = document.querySelector("#botonGuardarAlojamiento");

    // Ficha
    const modalFichaHabitaculo = document.querySelector("#modalFichaHabitaculo");
    const cerrarFichaHabitaculo = document.querySelector("#cerrarFichaHabitaculo");
    const tituloFichaHabitaculo = document.querySelector("#tituloFichaHabitaculo");
    const mensajeFichaHabitaculo = document.querySelector("#mensajeFichaHabitaculo");
    const datosFichaHabitaculo = document.querySelector("#datosFichaHabitaculo");
    const fichaZona = document.querySelector("#fichaZona");
    const fichaPlazas = document.querySelector("#fichaPlazas");
    const fichaLimpieza = document.querySelector("#fichaLimpieza");
    const fichaMantenimiento = document.querySelector("#fichaMantenimiento");
    const fichaUltimaLimpieza = document.querySelector("#fichaUltimaLimpieza");
    const fichaProximaLimpieza = document.querySelector("#fichaProximaLimpieza");
    const fichaObservaciones = document.querySelector("#fichaObservaciones");
    const fichaActivo = document.querySelector("#fichaActivo");
    const listaRestricciones = document.querySelector("#listaRestricciones");
    const listaActuaciones = document.querySelector("#listaActuaciones");
    const tablaAnimalesAlojados = document.querySelector("#tablaAnimalesAlojados");

    // ROLES QUE PUEDEN CONSULTAR Y ALOJAR EN LOS HABITÁCULOS
    const rolesTrabajadores = ["admin", "personal", "veterinario"];

    function obtenerToken() {
        return sessionStorage.getItem("token") || localStorage.getItem("token");
    }

    function crearCelda(texto) {
        const celda = document.createElement("td");
        celda.textContent = texto;
        return celda;
    }

    function crearElementoLista(texto) {
        const elemento = document.createElement("li");
        elemento.textContent = texto;
        return elemento;
    }

    function formatearFechaHora(fechaIso) {
        if (!fechaIso) {
            return "-";
        }
        const fecha = new Date(fechaIso);
        if (Number.isNaN(fecha.getTime())) {
            return fechaIso;
        }
        return fecha.toLocaleString("es-ES", {
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    function mostrarErrorAlojamiento(error) {
        mensajeAlojarAnimal.replaceChildren();

        if (Array.isArray(error.motivos) && error.motivos.length > 0) {
            const titulo = document.createElement("strong");
            titulo.textContent = "Alojamiento rechazado:";
            mensajeAlojarAnimal.appendChild(titulo);

            const lista = document.createElement("ul");
            error.motivos.forEach(function (motivo) {
                lista.appendChild(crearElementoLista(motivo));
            });
            mensajeAlojarAnimal.appendChild(lista);
            return;
        }

        mensajeAlojarAnimal.textContent = error.message;
    }

   
    // COMPROBAR QUE ES UN TRABAJADOR
    
    async function comprobarAcceso() {
        const token = obtenerToken();

        if (!token) {
            window.location.href = "login.html";
            return;
        }

        try {
            const usuario = await obtenerUsuario(token);

            if (!rolesTrabajadores.includes(usuario.rol)) {
                mensajeHabitaculos.textContent = "No tienes permisos para consultar los habitáculos.";
                return;
            }

            // EL ENLACE A LA GESTIÓN SOLO LO VE EL ADMINISTRADOR
            enlaceInstalaciones.hidden = usuario.rol !== "admin";

            contenidoHabitaculos.hidden = false;
            await cargarZonas();
            await cargarHabitaculos();
            await cargarHistorial();

        } catch (error) {
            mensajeHabitaculos.textContent = "No se pudo comprobar tu sesión: " + error.message;
        }
    }

    // RELLENAR EL FILTRO DE ZONAS
    async function cargarZonas() {
        try {
            const resultado = await obtenerZonas(obtenerToken());
            const zonas = resultado.data;

            for (let i = 0; i < zonas.length; i++) {
                const opcion = document.createElement("option");
                opcion.value = zonas[i].id;
                opcion.textContent = zonas[i].nombre;
                filtroZona.appendChild(opcion);
            }

        } catch (error) {
            mensajeHabitaculos.textContent = error.message;
        }
    }

    
    // LISTADO DE HABITÁCULOS
    
    async function cargarHabitaculos() {
        const filtros = {
            zona_id: filtroZona.value,
            estado_limpieza: filtroLimpieza.value
        };

        try {
            const resultado = await obtenerHabitaculos(filtros, obtenerToken());
            pintarTablaHabitaculos(resultado.data);

        } catch (error) {
            mensajeHabitaculos.textContent = error.message;
        }
    }

    function pintarTablaHabitaculos(habitaculos) {
        tablaHabitaculos.replaceChildren();

        if (habitaculos.length === 0) {
            const fila = document.createElement("tr");
            const celda = crearCelda("No hay habitáculos con esos filtros.");
            celda.colSpan = 7;
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
            fila.appendChild(crearCelda(habitaculo.activo ? "Activo" : "Desactivado"));

            const celdaAcciones = document.createElement("td");
            const acciones = document.createElement("div");
            acciones.className = "acciones-cuenta";

            const botonFicha = document.createElement("button");
            botonFicha.type = "button";
            botonFicha.className = "boton-detalle";
            botonFicha.textContent = "Ver ficha";
            botonFicha.addEventListener("click", function () {
                verFicha(habitaculo.id);
            });

            acciones.appendChild(botonFicha);
            celdaAcciones.appendChild(acciones);
            fila.appendChild(celdaAcciones);

            tablaHabitaculos.appendChild(fila);
        }
    }

   
    // HISTORIAL DE ALOJAMIENTOS Y TRASLADOS (T25)
   
    async function cargarHistorial() {
        try {
            const resultado = await obtenerHistorialAlojamientos({}, obtenerToken());
            pintarTablaHistorial(resultado.data || []);
        } catch (error) {
            mensajeHabitaculos.textContent = error.message;
        }
    }

    function pintarTablaHistorial(movimientos) {
        tablaHistorialAlojamientos.replaceChildren();

        if (movimientos.length === 0) {
            const fila = document.createElement("tr");
            const celda = crearCelda("Aún no se han registrado alojamientos ni traslados.");
            celda.colSpan = 5;
            fila.appendChild(celda);
            tablaHistorialAlojamientos.appendChild(fila);
            return;
        }

        for (let i = 0; i < movimientos.length; i++) {
            const mov = movimientos[i];
            const fila = document.createElement("tr");

            const nombreAnimal = mov.animal
                ? mov.animal.nombre + " (" + mov.animal.especie + ")"
                : "Animal #" + mov.animal_id;

            const origen = mov.habitaculo_origen
                ? mov.habitaculo_origen.identificador
                : "Primer ingreso";

            const destino = mov.habitaculo_destino
                ? mov.habitaculo_destino.identificador
                : "-";

            const responsable = mov.responsable
                ? mov.responsable.name
                : "Usuario #" + mov.user_id;

            fila.appendChild(crearCelda(nombreAnimal));
            fila.appendChild(crearCelda(origen));
            fila.appendChild(crearCelda(destino));
            fila.appendChild(crearCelda(formatearFechaHora(mov.fecha_movimiento)));
            fila.appendChild(crearCelda(responsable));

            tablaHistorialAlojamientos.appendChild(fila);
        }
    }

  
    // MODAL ALOJAR / TRASLADAR ANIMAL (T25)
    
    async function abrirModalAlojar() {
        mensajeAlojarAnimal.textContent = "Cargando animales y habitáculos...";
        formularioAlojarAnimal.reset();
        selectAnimalAlojar.replaceChildren();
        selectHabitaculoDestino.replaceChildren();

        const opcionAnimalDefecto = document.createElement("option");
        opcionAnimalDefecto.value = "";
        opcionAnimalDefecto.textContent = "Selecciona un animal";
        selectAnimalAlojar.appendChild(opcionAnimalDefecto);

        const opcionHabDefecto = document.createElement("option");
        opcionHabDefecto.value = "";
        opcionHabDefecto.textContent = "Selecciona un habitáculo";
        selectHabitaculoDestino.appendChild(opcionHabDefecto);

        modalAlojarAnimal.showModal();

        try {
            const token = obtenerToken();
            const [resAnimales, resHabitaculos] = await Promise.all([
                obtenerAnimalesParaAlojar(token),
                obtenerHabitaculos({}, token)
            ]);

            const listaHabitaculos = resHabitaculos.data || [];
            const mapaHabitaculos = new Map();
            for (let i = 0; i < listaHabitaculos.length; i++) {
                mapaHabitaculos.set(listaHabitaculos[i].id, listaHabitaculos[i].identificador);
            }

            const listaAnimales = Array.isArray(resAnimales)
                ? resAnimales
                : (Array.isArray(resAnimales.data)
                    ? resAnimales.data
                    : (resAnimales.data?.data || []));

            for (let i = 0; i < listaAnimales.length; i++) {
                const animal = listaAnimales[i];
                if (animal.activo === false) {
                    continue;
                }

                const opcion = document.createElement("option");
                opcion.value = animal.id;

                const nombreHabActual = animal.habitaculo?.identificador
                    || (animal.habitaculo_id ? mapaHabitaculos.get(animal.habitaculo_id) : null);

                const ubicacionActual = nombreHabActual
                    ? " — Actual: " + nombreHabActual
                    : " — Sin alojar";

                opcion.textContent = animal.nombre + " (" + animal.especie + ")" + ubicacionActual;
                selectAnimalAlojar.appendChild(opcion);
            }

            for (let i = 0; i < listaHabitaculos.length; i++) {
                const hab = listaHabitaculos[i];
                if (!hab.activo || hab.estado_mantenimiento !== "Operativo") {
                    continue;
                }

                const opcion = document.createElement("option");
                opcion.value = hab.id;
                opcion.textContent = hab.identificador + " (" + hab.zona.nombre + " · Cap: " + hab.capacidad + ")";
                selectHabitaculoDestino.appendChild(opcion);
            }

            mensajeAlojarAnimal.textContent = "";

        } catch (error) {
            mensajeAlojarAnimal.textContent = error.message;
        }
    }

    botonAbrirAlojar.addEventListener("click", abrirModalAlojar);

    cerrarAlojarAnimal.addEventListener("click", function () {
        modalAlojarAnimal.close();
    });

    formularioAlojarAnimal.addEventListener("submit", async function (e) {
        e.preventDefault();

        const datos = {
            animal_id: Number(selectAnimalAlojar.value),
            habitaculo_destino_id: Number(selectHabitaculoDestino.value),
            observaciones: observacionesAlojar.value.trim() || null
        };

        botonGuardarAlojamiento.disabled = true;
        mensajeAlojarAnimal.textContent = "Guardando movimiento...";

        try {
            const resultado = await alojarAnimal(datos, obtenerToken());
            mensajeHabitaculos.textContent = resultado.message;
            modalAlojarAnimal.close();

            await cargarHabitaculos();
            await cargarHistorial();

        } catch (error) {
            mostrarErrorAlojamiento(error);
        } finally {
            botonGuardarAlojamiento.disabled = false;
        }
    });

    
    // FICHA DEL HABITÁCULO
   
    async function verFicha(id) {
        datosFichaHabitaculo.hidden = true;
        tituloFichaHabitaculo.textContent = "Ficha del habitáculo";
        mensajeFichaHabitaculo.textContent = "Cargando ficha...";
        modalFichaHabitaculo.showModal();

        try {
            const resultado = await obtenerFichaHabitaculo(id, obtenerToken());
            const ficha = resultado.data;
            const habitaculo = ficha.habitaculo;

            tituloFichaHabitaculo.textContent = "Habitáculo " + habitaculo.identificador;
            fichaZona.textContent = habitaculo.zona.nombre;
            fichaPlazas.textContent = ficha.plazas.ocupadas + " ocupadas de " + ficha.plazas.capacidad
                + " (" + ficha.plazas.libres + " libres)";
            fichaLimpieza.textContent = habitaculo.estado_limpieza;
            fichaMantenimiento.textContent = habitaculo.estado_mantenimiento;
            fichaUltimaLimpieza.textContent = habitaculo.fecha_ultima_limpieza || "Sin registrar";
            fichaProximaLimpieza.textContent = habitaculo.fecha_proxima_limpieza || "Sin programar";
            fichaObservaciones.textContent = habitaculo.observaciones || "Sin observaciones";
            fichaActivo.textContent = habitaculo.activo ? "Activo" : "Desactivado";

            pintarLista(listaRestricciones, ficha.restricciones, "Sin restricciones.");
            pintarActuaciones(ficha.actuaciones);
            pintarAnimales(ficha.animales);

            mensajeFichaHabitaculo.textContent = "";
            datosFichaHabitaculo.hidden = false;

        } catch (error) {
            mensajeFichaHabitaculo.textContent = error.message;
        }
    }

    function pintarLista(lista, textos, textoVacio) {
        lista.replaceChildren();

        if (textos.length === 0) {
            lista.appendChild(crearElementoLista(textoVacio));
            return;
        }

        for (let i = 0; i < textos.length; i++) {
            lista.appendChild(crearElementoLista(textos[i]));
        }
    }

    function pintarActuaciones(actuaciones) {
        listaActuaciones.replaceChildren();

        if (actuaciones.length === 0) {
            listaActuaciones.appendChild(crearElementoLista("No hay actuaciones previstas."));
            return;
        }

        for (let i = 0; i < actuaciones.length; i++) {
            let texto = actuaciones[i].tipo + ": " + actuaciones[i].descripcion;

            if (actuaciones[i].fecha) {
                texto = actuaciones[i].tipo + " (" + actuaciones[i].fecha + "): " + actuaciones[i].descripcion;
            }

            listaActuaciones.appendChild(crearElementoLista(texto));
        }
    }

    function pintarAnimales(animales) {
        tablaAnimalesAlojados.replaceChildren();

        if (animales.length === 0) {
            const fila = document.createElement("tr");
            const celda = crearCelda("No hay animales alojados.");
            celda.colSpan = 5;
            fila.appendChild(celda);
            tablaAnimalesAlojados.appendChild(fila);
            return;
        }

        for (let i = 0; i < animales.length; i++) {
            const fila = document.createElement("tr");

            fila.appendChild(crearCelda(animales[i].nombre));
            fila.appendChild(crearCelda(animales[i].especie));
            fila.appendChild(crearCelda(animales[i].sexo));
            fila.appendChild(crearCelda(animales[i].tamano || "-"));
            fila.appendChild(crearCelda(animales[i].estado));

            tablaAnimalesAlojados.appendChild(fila);
        }
    }

    cerrarFichaHabitaculo.addEventListener("click", function () {
        modalFichaHabitaculo.close();
    });

  
    // FILTROS
    
    formularioFiltros.addEventListener("submit", function (e) {
        e.preventDefault();
        cargarHabitaculos();
    });

    botonLimpiarFiltros.addEventListener("click", function () {
        filtroZona.value = "";
        filtroLimpieza.value = "";
        cargarHabitaculos();
    });

   
    // CERRAR SESIÓN
    
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