import {
    obtenerUsuario,
    cerrarSesion
} from "./providers/autenticacionProvider.js";

import { crearAnimal } from "./providers/animalesProvider.js";

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

    const formularioFiltros = document.querySelector("#formularioFiltros");
    const buscarTexto = document.querySelector("#buscarTexto");
    const filtroEspecie = document.querySelector("#filtroEspecie");
    const filtroEstado = document.querySelector("#filtroEstado");
    const botonLimpiarFiltros = document.querySelector("#botonLimpiarFiltros");
    const tablaCuerpoAnimales = document.querySelector("#tablaCuerpoAnimales");

    const modalDetalleAnimal = document.querySelector("#modalDetalleAnimal");
    const cerrarDetalleAnimal = document.querySelector("#cerrarDetalleAnimal");
    const contenidoFichaAnimal = document.querySelector("#contenidoFichaAnimal");
    const enlaceEditarAnimal = document.querySelector("#enlaceEditarAnimal");

    const rolesAutorizados = ["admin", "personal", "veterinario", "empleado"];

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

    // OBTENER LA FECHA DE HOY (AAAA-MM-DD)
    function fechaDeHoy() {
        const hoy = new Date();
        const mes = String(hoy.getMonth() + 1).padStart(2, "0");
        const dia = String(hoy.getDate()).padStart(2, "0");
        return hoy.getFullYear() + "-" + mes + "-" + dia;
    }

    // COMPROBAR ACCESO Y PERMISOS DE INICIO
    async function comprobarAcceso() {
        const token = sessionStorage.getItem("token") || localStorage.getItem("token");

        if (!token) {
            if (mensajeAnimal) mensajeAnimal.textContent = "Debes iniciar sesión para acceder a la gestión de animales.";
            if (botonCerrarSesion) botonCerrarSesion.hidden = true;
            return;
        }

        try {
            const usuario = await obtenerUsuario(token);

            if (!rolesAutorizados.includes(usuario.rol)) {
                alert("No tienes permisos para acceder a la gestión de animales.");
                window.location.href = "index.html";
                return;
            }

            if (fechaEntrada) fechaEntrada.value = fechaDeHoy();
            if (gestionAnimales) gestionAnimales.hidden = false;
            if (mensajeAnimal) mensajeAnimal.textContent = "";

            if (tablaCuerpoAnimales) {
                cargarAnimales();
            }

        } catch (error) {
            console.error("Error al comprobar el acceso:", error);
            if (mensajeAnimal) mensajeAnimal.textContent = `No se pudo comprobar el acceso: ${error.message}`;
        }
    }

    // LIMPIAR MENSÁJES DE ERROR CAMPO A CAMPO
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
    // BÚSQUEDA Y FILTRADO (T13)
    // -------------------------------------------------------------
    if (formularioFiltros) {
        formularioFiltros.addEventListener("submit", function (e) {
            e.preventDefault();
            cargarAnimales();
        });
    }

    if (botonLimpiarFiltros) {
        botonLimpiarFiltros.addEventListener("click", function () {
            if (buscarTexto) buscarTexto.value = "";
            if (filtroEspecie) filtroEspecie.value = "";
            if (filtroEstado) filtroEstado.value = "";
            cargarAnimales();
        });
    }

    // -------------------------------------------------------------
    // CONSULTA Y CARGA DE ANIMALES DESDE LA API
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
        if (filtroEstado && filtroEstado.value !== "") {
            params.append("estado", filtroEstado.value);
        }

        try {
            const respuesta = await fetch(`http://localhost:8000/api/animales?${params.toString()}`, {
                headers: {
                    "Accept": "application/json",
                    ...(token && { "Authorization": `Bearer ${token}` })
                }
            });

            const animales = await respuesta.json();
            tablaCuerpoAnimales.innerHTML = "";

            if (!respuesta.ok) {
                if (mensajeAnimal) {
                    mensajeAnimal.textContent = animales.message || "Error al consultar los animales.";
                    mensajeAnimal.style.color = "red";
                }
                return;
            }

            if (animales.length === 0) {
                tablaCuerpoAnimales.innerHTML = `<tr><td colspan="5" style="text-align:center;">No se encontraron animales registrados.</td></tr>`;
                return;
            }

            animales.forEach(animal => {
                const fila = document.createElement("tr");
                fila.innerHTML = `
                    <td><strong>${animal.nombre}</strong></td>
                    <td>${animal.especie}</td>
                    <td>${animal.raza || 'Sin especificar'}</td>
                    <td>${animal.estado}</td>
                    <td>
                        <button type="button" class="boton-detalle" data-id="${animal.id}">Ver Ficha</button>
                    </td>
                `;
                tablaCuerpoAnimales.appendChild(fila);
            });

            document.querySelectorAll(".boton-detalle").forEach(boton => {
                boton.addEventListener("click", function () {
                    verFichaAnimal(this.dataset.id);
                });
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
    // CONSULTA DE FICHA DETALLADA POR ROL (T13)
    // -------------------------------------------------------------
    async function verFichaAnimal(id) {
        if (!modalDetalleAnimal || !contenidoFichaAnimal) return;

        const token = sessionStorage.getItem("token") || localStorage.getItem("token");

        try {
            const respuesta = await fetch(`http://localhost:8000/api/animales/${id}`, {
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

            let htmlDetalle = `
                <h2>Ficha de ${animal.nombre}</h2>
                ${imgUrl ? `<img src="http://localhost:8000/storage/${imgUrl}" alt="${animal.nombre}" style="width:100%; max-height:220px; object-fit:cover; border-radius:12px; margin-bottom:15px;">` : ''}
                <p><strong>Especie:</strong> ${animal.especie}</p>
                <p><strong>Raza / Características:</strong> ${animal.raza || 'N/A'}</p>
                <p><strong>Sexo:</strong> ${animal.sexo || 'N/A'}</p>
                <p><strong>Estado:</strong> ${animal.estado}</p>
                <hr style="margin: 15px 0; border: 0; border-top: 1px solid #F5F5F5;">
                <p><strong>Peso:</strong> ${animal.peso ? animal.peso + ' kg' : 'N/A'}</p>
                <p><strong>Fecha de entrada:</strong> ${animal.fecha_entrada || 'N/A'}</p>
                <p><strong>Motivo / Procedencia:</strong> ${animal.motivo || 'N/A'}</p>
                <p><strong>Carácter:</strong> ${animal.caracter || 'Sin especificar'}</p>
                <p><strong>Necesidades especiales:</strong> ${animal.necesidades_especiales || 'Ninguna'}</p>
                <p><strong>Observaciones del personal:</strong> ${animal.observaciones || 'Sin observaciones'}</p>
            `;

            contenidoFichaAnimal.innerHTML = htmlDetalle;

            if (enlaceEditarAnimal) {
                enlaceEditarAnimal.href = "editarAnimal.html?id=" + id;
            }

            modalDetalleAnimal.showModal();

        } catch (error) {
            console.error("Error obteniendo el detalle:", error);
            alert("No se pudo conectar con el servidor.");
        }
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