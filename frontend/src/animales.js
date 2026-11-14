import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import {
    crearAnimal,
    actualizarEstadoAnimal,
    darDeBajaAnimal,
    eliminarAnimal
} from "./providers/animalesProvider.js";

document.addEventListener("DOMContentLoaded", function () {
    // -------------------------------------------------------------
    // ELEMENTOS DEL DOM
    // -------------------------------------------------------------
    const mensajeAnimal = document.querySelector("#mensajeAnimal");
    const gestionAnimales = document.querySelector("#gestionAnimales");
    const formularioAnimal = document.querySelector("#formularioAnimal");
    const botonGuardar = document.querySelector("#botonGuardarAnimal");
    const fechaEntrada = document.querySelector("#fechaEntradaAnimal");
    const botonCerrarSesion = document.querySelector("#botonCerrarSesion");

    const etiquetaSeccionAnimales = document.querySelector("#etiquetaSeccionAnimales");
    const tituloSeccionAnimales = document.querySelector("#tituloSeccionAnimales");
    const subtituloAnimales = document.querySelector("#subtituloAnimales");

    const formularioFiltros = document.querySelector("#formularioFiltros");
    const buscarTexto = document.querySelector("#buscarTexto");
    const filtroEspecie = document.querySelector("#filtroEspecie");
    const filtroEstado = document.querySelector("#filtroEstado");
    const contenedorFiltroEstado = document.querySelector("#contenedorFiltroEstado");
    const botonLimpiarFiltros = document.querySelector("#botonLimpiarFiltros");
    const tablaCuerpoAnimales = document.querySelector("#tablaCuerpoAnimales");

    const modalDetalleAnimal = document.querySelector("#modalDetalleAnimal");
    const cerrarDetalleAnimal = document.querySelector("#cerrarDetalleAnimal");
    const enlaceEditarAnimal = document.querySelector("#enlaceEditarAnimal");

    // Elementos de la ficha modal
    const fichaNombre = document.querySelector("#fichaNombre");
    const fichaImagen = document.querySelector("#fichaImagen");
    const fichaEspecie = document.querySelector("#fichaEspecie");
    const fichaRaza = document.querySelector("#fichaRaza");
    const fichaSexo = document.querySelector("#fichaSexo");
    const fichaEstado = document.querySelector("#fichaEstado");
    const fichaPeso = document.querySelector("#fichaPeso");
    const fichaFechaEntrada = document.querySelector("#fichaFechaEntrada");
    const fichaMotivo = document.querySelector("#fichaMotivo");
    const fichaCaracter = document.querySelector("#fichaCaracter");
    const fichaNecesidades = document.querySelector("#fichaNecesidades");
    const fichaObservaciones = document.querySelector("#fichaObservaciones");

    // Elementos de transición de estado (T15)
    const bloqueCambioEstado = document.querySelector("#bloqueCambioEstado");
    const controlesTransicionEstado = document.querySelector("#controlesTransicionEstado");
    const selectNuevoEstado = document.querySelector("#selectNuevoEstado");
    const btnGuardarEstadoAnimal = document.querySelector("#btnGuardarEstadoAnimal");
    const mensajeEstadoModal = document.querySelector("#mensajeEstadoModal");
    const avisoEstadoFinal = document.querySelector("#avisoEstadoFinal");

    // Elementos de baja / eliminación (T16)
    const contenedorFiltroBajas = document.querySelector("#contenedorFiltroBajas");
    const filtroBajas = document.querySelector("#filtroBajas");
    const bloqueBajaAnimal = document.querySelector("#bloqueBajaAnimal");
    const motivoBajaAnimal = document.querySelector("#motivoBajaAnimal");
    const btnBajaAnimal = document.querySelector("#btnBajaAnimal");
    const btnEliminarAnimal = document.querySelector("#btnEliminarAnimal");
    const etiquetaMotivoBaja = document.querySelector("#etiquetaMotivoBaja");
    const mensajeBajaModal = document.querySelector("#mensajeBajaModal");
    const avisoAnimalBaja = document.querySelector("#avisoAnimalBaja");
    const fichaFechaBaja = document.querySelector("#fichaFechaBaja");
    const fichaMotivoBaja = document.querySelector("#fichaMotivoBaja");

    const rolesAutorizados = ["admin", "personal", "veterinario", "empleado"];
    let usuarioActual = null;
    let animalSeleccionadoId = null;

    const campos = [
        "nombre",
        "fotografia",
        "especie",
        "raza",
        "fecha_nacimiento",
        "sexo",
        "tamano",
        "peso",
        "fecha_entrada",
        "motivo",
        "estado",
        "caracter",
        "con_ninos",
        "con_otros_animales",
        "con_desconocidos",
        "necesidades_especiales",
        "observaciones"
    ];

    // TRANSICIONES PERMITIDAS SEGÚN EL ESTADO ACTUAL (T15)
    function obtenerTransicionesPermitidas(estadoActual) {
        const transiciones = {
            "En acogida": ["En tratamiento", "Disponible"],
            "En tratamiento": ["En acogida", "Disponible"],
            "Disponible": ["En acogida", "En tratamiento", "Reservado"],
            "Reservado": ["Disponible", "En acogida", "En tratamiento"],
            "Adoptado": []
        };
        return transiciones[estadoActual] || [];
    }

    // OBTENER LA FECHA DE HOY (AAAA-MM-DD)
    function fechaDeHoy() {
        const hoy = new Date();
        const mes = String(hoy.getMonth() + 1).padStart(2, "0");
        const dia = String(hoy.getDate()).padStart(2, "0");
        return hoy.getFullYear() + "-" + mes + "-" + dia;
    }

    // CONFIGURAR VISTA PARA ADOPTANTE O VISITANTE (SOLO CONSULTA Y FILTROS)
    function activarVistaAdoptante(haySesion) {
        if (gestionAnimales) gestionAnimales.hidden = true;
        if (contenedorFiltroEstado) contenedorFiltroEstado.hidden = true;
        if (contenedorFiltroBajas) contenedorFiltroBajas.hidden = true;
        if (botonCerrarSesion) botonCerrarSesion.hidden = !haySesion;

        if (etiquetaSeccionAnimales) etiquetaSeccionAnimales.textContent = "ADOPCIÓN";
        if (tituloSeccionAnimales) tituloSeccionAnimales.textContent = "Nuestros Animales";
        if (subtituloAnimales) {
            subtituloAnimales.textContent = "Conoce a nuestros compañeros disponibles y encuentra a tu nuevo mejor amigo.";
        }
        if (mensajeAnimal) mensajeAnimal.textContent = "";
    }

    // CONFIGURAR VISTA PARA PERSONAL AUTORIZADO (ALTA + GESTIÓN + ESTADOS)
    function activarVistaPersonal() {
        if (fechaEntrada) fechaEntrada.value = fechaDeHoy();
        if (gestionAnimales) gestionAnimales.hidden = false;
        if (contenedorFiltroEstado) contenedorFiltroEstado.hidden = false;
        if (contenedorFiltroBajas) contenedorFiltroBajas.hidden = false;
        if (botonCerrarSesion) botonCerrarSesion.hidden = false;

        if (etiquetaSeccionAnimales) etiquetaSeccionAnimales.textContent = "ADMINISTRACIÓN";
        if (tituloSeccionAnimales) tituloSeccionAnimales.textContent = "Gestión de Animales";
        if (subtituloAnimales) {
            subtituloAnimales.textContent = "Registra la llegada de nuevos animales y actualiza su situación en la protectora.";
        }
        if (mensajeAnimal) mensajeAnimal.textContent = "";
    }

    // COMPROBAR PERFIL Y ADAPTAR LA INTERFAZ
    async function comprobarAcceso() {
        const token = sessionStorage.getItem("token") || localStorage.getItem("token");

        if (!token) {
            activarVistaAdoptante(false);
            if (tablaCuerpoAnimales) cargarAnimales();
            return;
        }

        try {
            const usuario = await obtenerUsuario(token);
            usuarioActual = usuario;

            if (rolesAutorizados.includes(usuario.rol)) {
                activarVistaPersonal();
            } else {
                activarVistaAdoptante(true);
            }

            if (tablaCuerpoAnimales) {
                cargarAnimales();
            }

        } catch (error) {
            console.error("Error al comprobar el usuario:", error);
            activarVistaAdoptante(false);
            if (tablaCuerpoAnimales) cargarAnimales();
        }
    }

    // LIMPIAR MENSAJES DE ERROR CAMPO A CAMPO
    function limpiarErrores() {
        campos.forEach(campo => {
            const el = document.querySelector("#error-" + campo);
            if (el) el.textContent = "";
        });
    }

    // MOSTRAR ERRORES DE VALIDACIÓN BAJO CADA CAMPO
    function mostrarErrores(errores) {
        campos.forEach(campo => {
            if (errores[campo]) {
                const el = document.querySelector("#error-" + campo);
                if (el) el.textContent = errores[campo][0];
            }
        });
    }

    // -------------------------------------------------------------
    // ENVÍO DEL FORMULARIO DE ALTA DE ANIMAL
    // -------------------------------------------------------------
    if (formularioAnimal) {
        formularioAnimal.addEventListener("submit", async function (e) {
            e.preventDefault();

            const token = sessionStorage.getItem("token") || localStorage.getItem("token");
            if (!token) {
                if (mensajeAnimal) mensajeAnimal.textContent = "Debes iniciar sesión para dar de alta animales.";
                return;
            }

            limpiarErrores();
            const datos = new FormData(formularioAnimal);

            if (mensajeAnimal) mensajeAnimal.textContent = "Guardando animal...";
            if (botonGuardar) botonGuardar.disabled = true;

            try {
                const resultado = await crearAnimal(datos, token);

                if (mensajeAnimal) {
                    mensajeAnimal.textContent = `${resultado.message || '¡Animal registrado con éxito!'} (${resultado.data ? resultado.data.nombre : ''})`;
                    mensajeAnimal.style.color = "green";
                }

                formularioAnimal.reset();
                if (fechaEntrada) fechaEntrada.value = fechaDeHoy();

                if (tablaCuerpoAnimales) {
                    cargarAnimales();
                }

            } catch (error) {
                if (error.errores) {
                    mostrarErrores(error.errores);
                    if (mensajeAnimal) {
                        mensajeAnimal.textContent = "Revisa los campos marcados en el formulario.";
                        mensajeAnimal.style.color = "red";
                    }
                } else {
                    if (mensajeAnimal) {
                        mensajeAnimal.textContent = `No se pudo registrar el animal: ${error.message}`;
                        mensajeAnimal.style.color = "red";
                    }
                }
            } finally {
                if (botonGuardar) botonGuardar.disabled = false;
            }
        });
    }

    // -------------------------------------------------------------
    // BÚSQUEDA Y FILTRADO
    // -------------------------------------------------------------
    if (formularioFiltros) {
        formularioFiltros.addEventListener("submit", function (e) {
            e.preventDefault();
            cargarAnimales();
        });
    }

    // RECARGAR LA TABLA AL MARCAR O DESMARCAR "VER DADOS DE BAJA" (T16)
    if (filtroBajas) {
        filtroBajas.addEventListener("change", function () {
            cargarAnimales();
        });
    }

    if (botonLimpiarFiltros) {
        botonLimpiarFiltros.addEventListener("click", function () {
            if (buscarTexto) buscarTexto.value = "";
            if (filtroEspecie) filtroEspecie.value = "";
            if (filtroEstado) filtroEstado.value = "";
            if (filtroBajas) filtroBajas.checked = false;
            cargarAnimales();
        });
    }

    // -------------------------------------------------------------
    // CONSULTA Y CARGA DE ANIMALES DESDE LA API (DOM PURO)
    // -------------------------------------------------------------
    async function cargarAnimales() {
        if (!tablaCuerpoAnimales) return;

        const token = sessionStorage.getItem("token") || localStorage.getItem("token");
        const params = new URLSearchParams();

        if (buscarTexto && buscarTexto.value.trim() !== "") {
            params.append("buscar", buscarTexto.value.trim());
        }
        if (filtroEspecie && filtroEspecie.value !== "") {
            params.append("especie", filtroEspecie.value);
        }
        if (filtroEstado && !contenedorFiltroEstado?.hidden && filtroEstado.value !== "") {
            params.append("estado", filtroEstado.value);
        }
        if (filtroBajas && !contenedorFiltroBajas.hidden && filtroBajas.checked) {
            params.append("bajas", "1");
        }

        try {
            const respuesta = await fetch(`http://127.0.0.1:8000/api/animales?${params.toString()}`, {
                headers: {
                    "Accept": "application/json",
                    ...(token && { "Authorization": `Bearer ${token}` })
                }
            });

            const animales = await respuesta.json();
            tablaCuerpoAnimales.replaceChildren();

            if (!respuesta.ok) {
                if (mensajeAnimal) {
                    mensajeAnimal.textContent = animales.message || "Error al consultar los animales.";
                    mensajeAnimal.style.color = "red";
                }
                return;
            }

            if (animales.length === 0) {
                const filaVacia = document.createElement("tr");
                const celdaVacia = document.createElement("td");
                celdaVacia.colSpan = 5;
                celdaVacia.style.textAlign = "center";
                celdaVacia.textContent = "No se encontraron animales disponibles con esos filtros.";
                filaVacia.appendChild(celdaVacia);
                tablaCuerpoAnimales.appendChild(filaVacia);
                return;
            }

            animales.forEach(animal => {
                const fila = document.createElement("tr");

                const tdNombre = document.createElement("td");
                const strongNombre = document.createElement("strong");
                strongNombre.textContent = animal.nombre;
                tdNombre.appendChild(strongNombre);

                const tdEspecie = document.createElement("td");
                tdEspecie.textContent = animal.especie;

                const tdRaza = document.createElement("td");
                tdRaza.textContent = animal.raza || "Sin especificar";

                const tdEstado = document.createElement("td");
                tdEstado.textContent = animal.estado;

                const tdAcciones = document.createElement("td");
                const botonDetalle = document.createElement("button");
                botonDetalle.type = "button";
                botonDetalle.className = "boton-detalle";
                botonDetalle.textContent = "Ver Ficha";
                botonDetalle.addEventListener("click", function () {
                    verFichaAnimal(animal.id);
                });
                tdAcciones.appendChild(botonDetalle);

                fila.append(tdNombre, tdEspecie, tdRaza, tdEstado, tdAcciones);
                tablaCuerpoAnimales.appendChild(fila);
            });

        } catch (error) {
            console.error("Error al cargar la lista de animales:", error);
            if (mensajeAnimal) {
                mensajeAnimal.textContent = "Error al conectar con la API de Laravel.";
                mensajeAnimal.style.color = "red";
            }
        }
    }

    // -------------------------------------------------------------
    // CONSULTA DE FICHA DETALLADA Y CAMBIO DE ESTADO (T15)
    // -------------------------------------------------------------
    async function verFichaAnimal(id) {
        if (!modalDetalleAnimal) return;

        const token = sessionStorage.getItem("token") || localStorage.getItem("token");
        animalSeleccionadoId = id;

        try {
            const respuesta = await fetch(`http://127.0.0.1:8000/api/animales/${id}`, {
                headers: {
                    "Accept": "application/json",
                    ...(token && { "Authorization": `Bearer ${token}` })
                }
            });

            const animal = await respuesta.json();

            if (!respuesta.ok) {
                alert(animal.message || "No se pudo obtener la información de la ficha.");
                return;
            }

            const imgUrl = animal.fotografia || animal.imagen;

            if (fichaNombre) fichaNombre.textContent = `Ficha de ${animal.nombre}`;
            if (fichaImagen) {
                if (imgUrl) {
                    fichaImagen.src = `http://127.0.0.1:8000/storage/${imgUrl}`;
                    fichaImagen.alt = animal.nombre;
                    fichaImagen.hidden = false;
                } else {
                    fichaImagen.removeAttribute("src");
                    fichaImagen.hidden = true;
                }
            }

            if (fichaEspecie) fichaEspecie.textContent = animal.especie;
            if (fichaRaza) fichaRaza.textContent = animal.raza || "N/A";
            if (fichaSexo) fichaSexo.textContent = animal.sexo || "N/A";
            if (fichaEstado) fichaEstado.textContent = animal.estado;
            if (fichaPeso) fichaPeso.textContent = animal.peso ? `${animal.peso} kg` : "N/A";
            if (fichaFechaEntrada) fichaFechaEntrada.textContent = animal.fecha_entrada || "N/A";
            if (fichaMotivo) fichaMotivo.textContent = animal.motivo || "N/A";
            if (fichaCaracter) fichaCaracter.textContent = animal.caracter || "Sin especificar";
            if (fichaNecesidades) fichaNecesidades.textContent = animal.necesidades_especiales || "Ninguna";
            if (fichaObservaciones) fichaObservaciones.textContent = animal.observaciones || "Sin observaciones";

            // Configurar bloque de cambio de estado (solo para personal autorizado)
            const esAutorizado = usuarioActual && rolesAutorizados.includes(usuarioActual.rol);

            // Comprobar si el animal está dado de baja (T16)
            const estaDeBaja = animal.activo === false || animal.activo === 0;

            if (avisoAnimalBaja) {
                avisoAnimalBaja.hidden = !estaDeBaja;
                if (fichaFechaBaja) fichaFechaBaja.textContent = animal.fecha_baja || "N/A";
                if (fichaMotivoBaja) fichaMotivoBaja.textContent = animal.motivo_baja || "Sin motivo";
            }

            if (bloqueBajaAnimal) {
                bloqueBajaAnimal.hidden = !esAutorizado;

                // Si ya está dado de baja, solo se muestra la opción de eliminar
                if (etiquetaMotivoBaja) etiquetaMotivoBaja.hidden = estaDeBaja;
                if (motivoBajaAnimal) motivoBajaAnimal.hidden = estaDeBaja;
                if (btnBajaAnimal) btnBajaAnimal.hidden = estaDeBaja;

                if (motivoBajaAnimal) motivoBajaAnimal.value = "";
                if (mensajeBajaModal) mensajeBajaModal.textContent = "";
            }

            if (bloqueCambioEstado) {
                if (esAutorizado && !estaDeBaja) {
                    bloqueCambioEstado.hidden = false;
                    const transiciones = obtenerTransicionesPermitidas(animal.estado);

                    if (mensajeEstadoModal) mensajeEstadoModal.textContent = "";

                    if (transiciones.length > 0) {
                        if (controlesTransicionEstado) controlesTransicionEstado.hidden = false;
                        if (avisoEstadoFinal) avisoEstadoFinal.hidden = true;

                        if (selectNuevoEstado) {
                            selectNuevoEstado.replaceChildren();

                            const opcionDefault = document.createElement("option");
                            opcionDefault.value = "";
                            opcionDefault.textContent = "Selecciona nuevo estado...";
                            selectNuevoEstado.appendChild(opcionDefault);

                            transiciones.forEach(estado => {
                                const opcion = document.createElement("option");
                                opcion.value = estado;
                                opcion.textContent = estado;
                                selectNuevoEstado.appendChild(opcion);
                            });
                        }
                    } else {
                        if (controlesTransicionEstado) controlesTransicionEstado.hidden = true;
                        if (avisoEstadoFinal) avisoEstadoFinal.hidden = false;
                    }
                } else {
                    bloqueCambioEstado.hidden = true;
                }
            }

            if (enlaceEditarAnimal) {
                enlaceEditarAnimal.href = "editarAnimal.html?id=" + id;
                enlaceEditarAnimal.hidden = !esAutorizado;
            }

            if (!modalDetalleAnimal.open) {
                modalDetalleAnimal.showModal();
            }

        } catch (error) {
            console.error("Error obteniendo el detalle:", error);
            alert("No se pudo conectar con el servidor.");
        }
    }

    // EVENTO PARA GUARDAR EL CAMBIO DE ESTADO (T15)
    if (btnGuardarEstadoAnimal && selectNuevoEstado) {
        btnGuardarEstadoAnimal.addEventListener("click", async function () {
            const token = sessionStorage.getItem("token") || localStorage.getItem("token");
            const nuevoEstado = selectNuevoEstado.value;

            if (!nuevoEstado) {
                if (mensajeEstadoModal) {
                    mensajeEstadoModal.textContent = "Selecciona un estado permitido antes de guardar.";
                    mensajeEstadoModal.style.color = "red";
                }
                return;
            }

            btnGuardarEstadoAnimal.disabled = true;
            if (mensajeEstadoModal) {
                mensajeEstadoModal.textContent = "Actualizando estado...";
                mensajeEstadoModal.style.color = "#333";
            }

            try {
                const res = await actualizarEstadoAnimal(animalSeleccionadoId, nuevoEstado, token);

                if (mensajeAnimal) {
                    mensajeAnimal.textContent = res.message || "Estado actualizado correctamente.";
                    mensajeAnimal.style.color = "green";
                }

                await cargarAnimales();
                await verFichaAnimal(animalSeleccionadoId);

                if (mensajeEstadoModal) {
                    mensajeEstadoModal.textContent = res.message || "Estado actualizado correctamente.";
                    mensajeEstadoModal.style.color = "green";
                }

            } catch (error) {
                if (mensajeEstadoModal) {
                    mensajeEstadoModal.textContent = error.message || "No se pudo cambiar el estado.";
                    mensajeEstadoModal.style.color = "red";
                }
            } finally {
                btnGuardarEstadoAnimal.disabled = false;
            }
        });
    }

    // EVENTO PARA DAR DE BAJA UN ANIMAL - BAJA LÓGICA (T16)
    if (btnBajaAnimal && motivoBajaAnimal) {
        btnBajaAnimal.addEventListener("click", async function () {
            const token = sessionStorage.getItem("token") || localStorage.getItem("token");
            const motivo = motivoBajaAnimal.value.trim();

            if (motivo === "") {
                if (mensajeBajaModal) {
                    mensajeBajaModal.textContent = "Debes indicar el motivo de la baja.";
                    mensajeBajaModal.style.color = "red";
                }
                return;
            }

            const confirmado = confirm("¿Seguro que quieres dar de baja este animal? Se conservarán todos sus registros.");
            if (!confirmado) {
                return;
            }

            btnBajaAnimal.disabled = true;
            if (mensajeBajaModal) {
                mensajeBajaModal.textContent = "Dando de baja al animal...";
                mensajeBajaModal.style.color = "#333";
            }

            try {
                const res = await darDeBajaAnimal(animalSeleccionadoId, motivo, token);

                if (mensajeAnimal) {
                    mensajeAnimal.textContent = res.message;
                    mensajeAnimal.style.color = "green";
                }

                modalDetalleAnimal.close();
                await cargarAnimales();

            } catch (error) {
                if (mensajeBajaModal) {
                    if (error.errores && error.errores.motivo_baja) {
                        mensajeBajaModal.textContent = error.errores.motivo_baja[0];
                    } else {
                        mensajeBajaModal.textContent = error.message || "No se pudo dar de baja el animal.";
                    }
                    mensajeBajaModal.style.color = "red";
                }
            } finally {
                btnBajaAnimal.disabled = false;
            }
        });
    }

    // EVENTO PARA ELIMINAR UN ANIMAL DEFINITIVAMENTE (T16)
    if (btnEliminarAnimal) {
        btnEliminarAnimal.addEventListener("click", async function () {
            const token = sessionStorage.getItem("token") || localStorage.getItem("token");

            const confirmado = confirm("¿Seguro que quieres eliminar este animal definitivamente? Esta acción no se puede deshacer.");
            if (!confirmado) {
                return;
            }

            btnEliminarAnimal.disabled = true;
            if (mensajeBajaModal) {
                mensajeBajaModal.textContent = "Comprobando registros del animal...";
                mensajeBajaModal.style.color = "#333";
            }

            try {
                const res = await eliminarAnimal(animalSeleccionadoId, token);

                if (mensajeAnimal) {
                    mensajeAnimal.textContent = res.message;
                    mensajeAnimal.style.color = "green";
                }

                modalDetalleAnimal.close();
                await cargarAnimales();

            } catch (error) {
                // Si tiene registros, el servidor rechaza y explica el motivo
                if (mensajeBajaModal) {
                    mensajeBajaModal.textContent = error.message || "No se pudo eliminar el animal.";
                    mensajeBajaModal.style.color = "red";
                }
            } finally {
                btnEliminarAnimal.disabled = false;
            }
        });
    }

    if (cerrarDetalleAnimal) {
        cerrarDetalleAnimal.addEventListener("click", function () {
            modalDetalleAnimal.close();
        });
    }

    // -------------------------------------------------------------
    // CERRAR SESIÓN
    // -------------------------------------------------------------
    if (botonCerrarSesion) {
        botonCerrarSesion.addEventListener("click", async function () {
            const token = sessionStorage.getItem("token") || localStorage.getItem("token");

            if (!token) {
                window.location.href = "index.html";
                return;
            }

            botonCerrarSesion.disabled = true;

            try {
                await cerrarSesion(token);
                sessionStorage.removeItem("token");
                localStorage.removeItem("token");
                window.location.href = "index.html";
            } catch (error) {
                if (mensajeAnimal) mensajeAnimal.textContent = `No se pudo cerrar sesión: ${error.message}`;
            } finally {
                botonCerrarSesion.disabled = false;
            }
        });
    }

    // INICIAR VERIFICACIÓN AL CARGAR LA PÁGINA
    comprobarAcceso();
});