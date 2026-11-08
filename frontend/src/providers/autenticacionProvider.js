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