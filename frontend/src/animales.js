document.addEventListener("DOMContentLoaded", function () {
    // -------------------------------------------------------------
    // CONTROL DE ACCESO POR ROL
    // -------------------------------------------------------------
    const rolesAutorizados = ["admin", "veterinario", "empleado"];
    const usuarioSesion = JSON.parse(localStorage.getItem("usuario") || "{}");
    const rolUsuario = usuarioSesion.rol || "adoptante";

    // Si el usuario no tiene rol autorizado, redirige al inicio
    /*if (!rolesAutorizados.includes(rolUsuario)) {
        alert("No tienes permisos para acceder a la gestión de animales.");
        window.location.href = "index.html";
        return;
    }*/

    // -------------------------------------------------------------
    // ELEMENTOS DEL DOM
    // -------------------------------------------------------------
    const formularioAnimal = document.querySelector("#formularioAnimal");
    const mensajeAnimal = document.querySelector("#mensajeAnimal");

    const formularioFiltros = document.querySelector("#formularioFiltros");
    const buscarTexto = document.querySelector("#buscarTexto");
    const filtroEspecie = document.querySelector("#filtroEspecie");
    const filtroEstado = document.querySelector("#filtroEstado");
    const botonLimpiarFiltros = document.querySelector("#botonLimpiarFiltros");
    const tablaCuerpoAnimales = document.querySelector("#tablaCuerpoAnimales");

    const modalDetalleAnimal = document.querySelector("#modalDetalleAnimal");
    const cerrarDetalleAnimal = document.querySelector("#cerrarDetalleAnimal");
    const contenidoFichaAnimal = document.querySelector("#contenidoFichaAnimal");

    const token = localStorage.getItem("token");

    // Cargar el listado inicial si existe la tabla en el HTML
    if (tablaCuerpoAnimales) {
        cargarAnimales();
    }

    // -------------------------------------------------------------
    // ENVÍO DEL FORMULARIO DE ALTA DE ANIMAL
    // -------------------------------------------------------------
    if (formularioAnimal) {
        formularioAnimal.addEventListener("submit", async function (e) {
            e.preventDefault();

            const formData = new FormData(formularioAnimal);

            try {
                const respuesta = await fetch("http://localhost:8000/api/animales", {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        ...(token && { "Authorization": `Bearer ${token}` })
                    },
                    body: formData
                });

                const resultado = await respuesta.json();

                if (respuesta.ok) {
                    if (mensajeAnimal) {
                        mensajeAnimal.textContent = "¡Animal registrado con éxito!";
                        mensajeAnimal.style.color = "green";
                    }
                    formularioAnimal.reset();

                    // Recargar la tabla tras registrar un nuevo animal
                    if (tablaCuerpoAnimales) {
                        cargarAnimales();
                    }
                } else {
                    if (mensajeAnimal) {
                        mensajeAnimal.textContent = resultado.message || "Error al guardar el animal.";
                        mensajeAnimal.style.color = "red";
                    }
                }
            } catch (error) {
                console.error("Error de conexión:", error);
                if (mensajeAnimal) {
                    mensajeAnimal.textContent = "Error al conectar con la API de Laravel.";
                    mensajeAnimal.style.color = "red";
                }
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
    // CONSULTA Y CARGA DE ANIMALES DE LA API
    // -------------------------------------------------------------
    async function cargarAnimales() {
        if (!tablaCuerpoAnimales) return;

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

            // Asignar evento a los botones de ficha
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

            // Datos básicos para todos los roles
            let htmlDetalle = `
                <h2>Ficha de ${animal.nombre}</h2>
                ${animal.imagen ? `<img src="http://localhost:8000/storage/${animal.imagen}" alt="${animal.nombre}" style="width:100%; max-height:220px; object-fit:cover; border-radius:12px; margin-bottom:15px;">` : ''}
                <p><strong>Especie:</strong> ${animal.especie}</p>
                <p><strong>Raza / Características:</strong> ${animal.raza || 'N/A'}</p>
                <p><strong>Sexo:</strong> ${animal.sexo || 'N/A'}</p>
                <p><strong>Estado:</strong> ${animal.estado}</p>
            `;

            // Información extendida reservada para Personal Autorizado
            if (rolesAutorizados.includes(rolUsuario)) {
                htmlDetalle += `
                    <hr style="margin: 15px 0; border: 0; border-top: 1px solid #F5F5F5;">
                    <p><strong>Peso:</strong> ${animal.peso ? animal.peso + ' kg' : 'N/A'}</p>
                    <p><strong>Fecha de entrada:</strong> ${animal.fecha_entrada || 'N/A'}</p>
                    <p><strong>Motivo / Procedencia:</strong> ${animal.motivo || 'N/A'}</p>
                    <p><strong>Carácter:</strong> ${animal.caracter || 'Sin especificar'}</p>
                    <p><strong>Necesidades especiales:</strong> ${animal.necesidades_especiales || 'Ninguna'}</p>
                    <p><strong>Observaciones del personal:</strong> ${animal.observaciones || 'Sin observaciones'}</p>
                `;
            }

            contenidoFichaAnimal.innerHTML = htmlDetalle;

            // ENLACE PARA EDITAR LA FICHA DE ESTE ANIMAL (T14)
            const enlaceEditarAnimal = document.querySelector("#enlaceEditarAnimal");
            enlaceEditarAnimal.href = "editarAnimal.html?id=" + id;
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
});