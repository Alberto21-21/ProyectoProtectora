document.addEventListener("DOMContentLoaded", function () {
    // -------------------------------------------------------------
    // CONTROL DE ACCESO POR ROL
    // -------------------------------------------------------------
    const rolesPermitidos = ["admin", "veterinario", "empleado"];
    
    // Obtener los datos del usuario almacenados tras el login
    const usuarioSesion = JSON.parse(localStorage.getItem("usuario") || "{}");
    const rolUsuario = usuarioSesion.rol;

    // Si el rol no está autorizado o no hay sesión, redirige al inicio
    if (!rolesPermitidos.includes(rolUsuario)) {
        alert("No tienes permisos para acceder a la gestión de animales.");
        window.location.href = "index.html";
        return;
    }

    // -------------------------------------------------------------
    // ENVÍO DEL FORMULARIO DE ALTA DE ANIMAL
    // -------------------------------------------------------------
    const formularioAnimal = document.querySelector("#formularioAnimal");
    const mensajeAnimal = document.querySelector("#mensajeAnimal");

    if (!formularioAnimal) return;

    formularioAnimal.addEventListener("submit", async function (e) {
        e.preventDefault();

        const formData = new FormData(formularioAnimal);
        const token = localStorage.getItem("token"); // Si usas Sanctum/API Tokens

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
});