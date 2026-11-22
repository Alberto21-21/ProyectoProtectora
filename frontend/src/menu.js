
const enlacesMenu = [
    { href: "index.html", texto: "Inicio" },
    { href: "animales.html", texto: "Animales" },
    { href: "sobreNosotros.html", texto: "Sobre nosotros"},
    { href: "perfil.html", texto: "Mi perfil", id: "enlacePerfil", roles: ["admin", "adoptante", "personal", "veterinario"] },
    { href: "usuarios.html", texto: "Usuarios", id: "enlaceUsuarios", roles: ["admin"] },
    { href: "revisionSolicitudes.html", texto: "Solicitudes", id: "enlaceRevisionSolicitudes", roles: ["admin"]},
    { href: "misAnimales.html", texto: "Mis animales", id: "enlaceMisAnimales", roles: ["personal", "veterinario"] },
    { href: "conversaciones.html", texto: "Conversaciones", id: "enlaceConversaciones", roles: ["personal", "veterinario"] },
    { href: "instalaciones.html", texto: "Instalaciones", id: "enlaceInstalaciones", roles: ["admin"] }
];

const paginaActual = window.location.pathname.split("/").pop() || "index.html";
const paginaMenu = {
    "editarAnimal.html": "animales.html",
    "historial.html": "animales.html"
}[paginaActual] || paginaActual;

async function obtenerRolActual() {
    const token = sessionStorage.getItem("token") || localStorage.getItem("token");

    if (!token) {
        return null;
    }

    try {
        const respuesta = await fetch("http://127.0.0.1:8000/api/user", {
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            }
        });

        if (!respuesta.ok) {
            return null;
        }

        const usuario = await respuesta.json();
        return usuario.rol;
    } catch (error) {
        return null;
    }
}

async function prepararMenu() {
    const menus = document.querySelectorAll('nav[aria-label="Navegación principal"]');

    if (menus.length === 0) {
        return;
    }

    const rol = await obtenerRolActual();

    menus.forEach(function (menu) {
        const boton = menu.querySelector("button");
        const fragmento = document.createDocumentFragment();

        enlacesMenu.forEach(function (definicion) {
            const enlacesCoincidentes = Array.from(menu.querySelectorAll('a[href="' + definicion.href + '"]'));
            let enlace = enlacesCoincidentes[0];

            enlacesCoincidentes.slice(1).forEach(function (duplicado) {
                duplicado.remove();
            });

            if (!enlace) {
                enlace = document.createElement("a");
                enlace.href = definicion.href;
            }

            enlace.textContent = definicion.texto;

            if (definicion.id) {
                enlace.id = definicion.id;
            }

            enlace.hidden = definicion.roles ? !definicion.roles.includes(rol) : false;
            enlace.removeAttribute("aria-current");

            if (definicion.href === paginaMenu) {
                enlace.setAttribute("aria-current", "page");
            }

            fragmento.appendChild(enlace);
        });

        menu.insertBefore(fragmento, boton);
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", prepararMenu);
} else {
    prepararMenu();
}