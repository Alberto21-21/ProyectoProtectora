// ENVIAR UNA SOLICITUD DE ADOPCIÓN
export async function crearSolicitud(datos, token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/solicitudes",
        {
            method: "POST",
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
            resultado.message || "No se pudo enviar la solicitud."
        );
    }

    return resultado;
}

// CONSULTAR LAS SOLICITUDES DEL ADOPTANTE
export async function obtenerMisSolicitudes(token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/solicitudes",
        {
            method: "GET",
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            }
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudieron consultar tus solicitudes."
        );
    }

    return resultado;
}