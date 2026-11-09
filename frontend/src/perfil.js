import { obtenerUsuario } from "./providers/autenticacionProvider.js";

// OBTENER ELEMENTOS DEL HTML
const estado = document.querySelector("#mensajePerfil");
const datosPerfil = document.querySelector("#datosPerfil");
const nombre = document.querySelector("#nombrePerfil");
const email = document.querySelector("#emailPerfil");
const fechaNacimiento = document.querySelector("#fechaNacimientoPerfil");
const rol = document.querySelector("#rolPerfil");

// CONSULTAR EL PERFIL
async function cargarPerfil() {

    const token = sessionStorage.getItem("token");

    if (!token) {
        estado.textContent = "Debes iniciar sesión para consultar tu perfil.";
        return;
    }

    estado.textContent = "Cargando perfil...";

    try {
        const usuario = await obtenerUsuario(token);

        nombre.textContent = usuario.name;
        email.textContent = usuario.email;
        fechaNacimiento.textContent =
            usuario.fecha_nacimiento || "No indicada";
        rol.textContent = usuario.rol;

        datosPerfil.hidden = false;
        estado.textContent = "";

    } catch (error) {
        estado.textContent =
            `No se pudo cargar el perfil: ${error.message}`;
    }
}

// CARGAR EL PERFIL AL ABRIR LA PAGINA
cargarPerfil();