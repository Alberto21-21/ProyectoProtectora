// ENVIAR LOS DATOS DEL REGISTRO
export async function registrarUsuario(datos) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/register",
        {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json"
            },
            body: JSON.stringify(datos)
        }
    );

    if (!respuesta.ok) {
        throw new Error(`Error HTTP: ${respuesta.status}`);
    }

    return await respuesta.json();
}

// ENVIAR LOS DATOS DEL INICIO DE SESIÓN
export async function iniciarSesion(datos) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/login",
        {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json"
            },
            body: JSON.stringify(datos)
        }
    );

    if (!respuesta.ok) {
        throw new Error(`Error HTTP: ${respuesta.status}`);
    }

    return await respuesta.json();
}

// CONSULTAR EL USUARIO AUTENTICADO
export async function obtenerUsuario(token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/user",
        {
            method: "GET",
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            }
        }
    );

    if (!respuesta.ok) {
        throw new Error(`Error HTTP: ${respuesta.status}`);
    }

    return await respuesta.json();

    
}

// CERRAR SESION
export async function cerrarSesion(token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/logout",
        {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            }
        }
    );

    if (!respuesta.ok) {
        throw new Error(`Error HTTP: ${respuesta.status}`);
    }

    return await respuesta.json();
}

// ACTUALIZAR EL PERFIL DEL USUARIO
export async function actualizarPerfil(datos, token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/user",
        {
            method: "PUT",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "Authorization": "Bearer " + token
            },
            body: JSON.stringify(datos)
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudo actualizar el perfil."
        );
    }

    return resultado;
}

// ACTUALIZAR LA FOTOGRAFÍA DEL USUARIO
export async function actualizarFotografia(fotografia, token) {

    const datos = new FormData();
    datos.append("fotografia", fotografia);

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/user/fotografia",
        {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            },
            body: datos
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudo actualizar la fotografía."
        );
    }

    return resultado;
}

// CAMBIAR LA CONTRASEÑA DEL USUARIO
export async function cambiarContrasena(datos, token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/user/password",
        {
            method: "PUT",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "Authorization": "Bearer " + token
            },
            body: JSON.stringify(datos)
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudo cambiar la contraseña."
        );
    }

    return resultado;
}